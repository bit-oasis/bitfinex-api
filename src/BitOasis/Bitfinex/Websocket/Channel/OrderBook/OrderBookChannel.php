<?php

namespace BitOasis\Bitfinex\Websocket\Channel\OrderBook;

use BitOasis\Bitfinex\Constant\Frequency;
use BitOasis\Bitfinex\Constant\Precision;
use BitOasis\Bitfinex\Exception\SubscriptionFailedException;
use BitOasis\Bitfinex\Websocket\HeartBeat;
use BitOasis\Bitfinex\Websocket\Channel\BitfinexPublicChannel;
use Nette\Utils\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class OrderBookChannel extends BitfinexPublicChannel implements LoggerAwareInterface {
	use LoggerAwareTrait;
	
	const CHANNEL_NAME = 'book';

	/** @var string */
	protected $precision;

	/** @var string */
	protected $frequency;

	/** @var int */
	protected $length;

	/** @var HeartBeat */
	protected $hb;

	/** @var OrderBookChannelSubscriber[] */
	protected $subscribers = [];

	/** @var Deferred[] */
	protected $subscribeDeferred = [];

	/** @var Deferred[] */
	protected $unsubscribeDeferred = [];

	/** @var bool */
	protected $lastStatusSentWasStarted = false;

	public function __construct(string $symbol, string $precision = Precision::P0, string $frequency = Frequency::F0, int $length = 100, LoopInterface $loop) {
		parent::__construct($symbol);
		$this->precision = $precision;
		$this->frequency = $frequency;
		$this->length = $length;
		$this->hb = new HeartBeat([$this, 'onHeartBeatFailure'], [$this, 'onHeartBeatResumed'], $loop);
		$this->logger = new NullLogger();
	}

	public function addOrderBookChannelSubscriber(OrderBookChannelSubscriber $subscriber) {
	    $this->subscribers[] = $subscriber;
	}

	public function onWebsocketConnected(WebSocket $conn, $version) {
		parent::onWebsocketConnected($conn, $version);
		$this->subscribe($conn);
	}

	public function onWebsocketClosed() {
		parent::onWebsocketClosed();
		$this->removeStoppedChannelData();
	}

	public function onWebsocketMessageReceived($data) {
		if ($this->channelId !== null && isset($data[0]) && $data[0] === $this->channelId) {
			$this->hb->heartBeat($this->channelId);
			if ($data[1] === 'hb') {
				return;
			}
			$update = $data[1];
			if (count($update) === 0 || is_array($update[0])) {
				foreach ($update as $item) {
					$message = new OrderBookMessage($item[0], $item[1], $item[2]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onOrderBookUpdateReceived($message);
					}
				}
			} else {
				$message = new OrderBookMessage($update[0], $update[1], $update[2]);
				foreach ($this->subscribers as $subscriber) {
					$subscriber->onOrderBookUpdateReceived($message);
				}
			}
		}
	}

	public function onWebsocketErrorMessage($data) {
		if ($this->areChannelDataValid($data)) {
			$this->logger->error("Can't subscribe to orderbook channel: {message} ({code})", ['message' => $data['msg'], 'code' => $data['code']]);
			foreach ($this->subscribeDeferred as $deferred) {
				$deferred->reject();
			}
			$this->subscribeDeferred = [];
			throw new SubscriptionFailedException("Can't subscribe to orderbook channel: $data[msg] ($data[code])"); // todo: handle specific situations
		}
	}

	public function onHeartBeatFailure($channelId) {
		$this->logger->warning('Heartbeat failure');
		$this->fireOnOrderBookStopped();
	}

	public function onHeartBeatResumed($channelId) {
		$this->fireOnOrderBookStarted();
	}

	protected function areChannelDataValid($data): bool {
		return isset($data['channel'], $data['symbol'], $data['prec'], $data['freq'], $data['len'])
			&& $data['channel'] === self::CHANNEL_NAME && $data['symbol'] === $this->symbol && $data['prec'] === $this->precision
			&& $data['freq'] === $this->frequency && (int)$data['len'] === $this->length;
	}

	public function onMaintenanceStarted() {
		$this->fireOnOrderBookStopped();
	}

	public function onWebsocketChannelSubscribed($data) {
		if ($this->areChannelDataValid($data) && isset($data['chanId'])) {
			foreach ($this->subscribeDeferred as $deferred) {
				$deferred->resolve($data['chanId']);
			}
			$this->subscribeDeferred = [];

			$this->channelId = $data['chanId'];
			$this->hb->addChannel($this->channelId);
			foreach ($this->subscribers as $subscriber) {
				$subscriber->clearOrderBook();
			}
			$this->fireOnOrderBookStarted();
		}
	}

	public function onWebsocketChannelUnsubscribed($data) {
		if ($this->channelId !== null && $data['chanId'] === $this->channelId) {
			if ($data['status'] === 'OK') {
				foreach ($this->unsubscribeDeferred as $deferred) {
					$deferred->resolve();
				}
				$this->unsubscribeDeferred = [];
				$this->removeStoppedChannelData();
			} else {
				throw new SubscriptionFailedException("Can't unsubscribe from orderbook channel: $data[msg] ($data[code])"); // todo: handle specific situations
			}
		}
	}

	public function __toString() {
	    return $this->symbol . ' ' . self::CHANNEL_NAME . ' channel with ' . ($this->channelId === null ? '' : 'chanId=' . $this->channelId . ', ') . 'prec=' . $this->precision . ', freq=' . $this->frequency . ', len=' . $this->length;
	}

	protected function subscribe(WebSocket $conn): Promise {
		$deferred = new Deferred();
		if (empty($this->subscribeDeferred)) {
			$data = Json::encode([
				'event' => 'subscribe',
				'channel' => self::CHANNEL_NAME,
				'symbol' => $this->symbol,
				'prec' => $this->precision,
				'freq' => $this->frequency,
				'len' => $this->length,
			]);
			$conn->send($data);
			$this->logger->debug('Websocket message sent: {data}', ['data' => $data]);
		}
		$this->subscribeDeferred[] = $deferred;
		return $deferred->promise();
	}

	protected function unsubscribe(WebSocket $conn): Promise {
		$deferred = new Deferred();
		if (empty($this->unsubscribeDeferred)) {
			$data = Json::encode([
				'event' => 'unsubscribe',
				'chanId' => $this->channelId,
			]);
			$conn->send($data);
			$this->logger->debug('Websocket message sent: {data}', ['data' => $data]);
		}
		$this->unsubscribeDeferred[] = $deferred;
		return $deferred->promise();
	}

	protected function removeStoppedChannelData() {
		if ($this->channelId !== null) {
			$this->hb->removeChannel($this->channelId);
			$this->fireOnOrderBookStopped();
		}
		$this->channelId = null;
	}

	protected function fireOnOrderBookStarted() {
		if (!$this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = true;
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onOrderBookStarted();
			}
		}
	}

	protected function fireOnOrderBookStopped() {
		if ($this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = false;
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onOrderBookStopped();
			}
		}
	}

}