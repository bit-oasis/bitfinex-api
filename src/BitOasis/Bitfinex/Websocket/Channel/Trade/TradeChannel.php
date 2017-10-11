<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Trade;

use BitOasis\Bitfinex\Websocket\HeartBeat;
use Psr\Log\NullLogger;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\EventLoop\LoopInterface;
use Ratchet\Client\WebSocket;
use Nette\Utils\Json;
use BitOasis\Bitfinex\Websocket\Channel\BitfinexPublicChannel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use BitOasis\Bitfinex\Exception\SubscriptionFailedException;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class TradeChannel extends BitfinexPublicChannel implements LoggerAwareInterface {
	use LoggerAwareTrait;
	
	const CHANNEL_NAME = 'trades';

	/** @var HeartBeat */
	protected $hb;

	/** @var TradeChannelSubscriber[] */
	protected $subscribers = [];

	/** @var Deferred[] */
	protected $subscribeDeferred = [];

	/** @var Deferred[] */
	protected $unsubscribeDeferred = [];

	/** @var bool */
	protected $lastStatusSentWasStarted = false;

	public function __construct(string $symbol, LoopInterface $loop) {
		parent::__construct($symbol);
		$this->hb = new HeartBeat([$this, 'onHeartBeatFailure'], [$this, 'onHeartBeatResumed'], $loop);
		$this->logger = new NullLogger();
	}


	public function addTradeChannelSubscriber(TradeChannelSubscriber $subscriber) {
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
			$update = in_array($data[1], ['te', 'tu'], true) ? $data[2] : $data[1];
			if (count($update) === 0 || is_array($update[0])) {
				foreach ($update as $item) {
					$message = new TradeMessage($item[0], $item[1], $item[2], $item[3]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onTradeUpdateReceived($message);
					}
				}
			} else {
				$message = new TradeMessage($update[0], $update[1], $update[2], $update[3]);
				foreach ($this->subscribers as $subscriber) {
					$subscriber->onTradeUpdateReceived($message);
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
		$this->fireOnTradeStopped();
	}

	public function onHeartBeatResumed($channelId) {
		$this->fireOnTradeStarted();
	}

	protected function areChannelDataValid($data): bool {
		return isset($data['channel'], $data['symbol']) && $data['channel'] === self::CHANNEL_NAME && $data['symbol'] === $this->symbol;
	}

	public function onMaintenanceStarted() {
		$this->fireOnTradeStopped();
	}

	public function onWebsocketChannelSubscribed($data) {
		if ($this->areChannelDataValid($data) && isset($data['chanId'])) {
			foreach ($this->subscribeDeferred as $deferred) {
				$deferred->resolve($data['chanId']);
			}
			$this->subscribeDeferred = [];

			$this->channelId = $data['chanId'];
			$this->hb->addChannel($this->channelId);
			$this->fireOnTradeStarted();
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

	protected function subscribe(WebSocket $conn): Promise {
		$deferred = new Deferred();
		if (empty($this->subscribeDeferred)) {
			$conn->send(Json::encode([
				'event' => 'subscribe',
				'channel' => self::CHANNEL_NAME,
				'symbol' => $this->symbol,
			]));
		}
		$this->subscribeDeferred[] = $deferred;
		return $deferred->promise();
	}

	protected function unsubscribe(WebSocket $conn): Promise {
		$deferred = new Deferred();
		if (empty($this->unsubscribeDeferred)) {
			$conn->send(Json::encode([
				'event' => 'unsubscribe',
				'chanId' => $this->channelId,
			]));
		}
		$this->unsubscribeDeferred[] = $deferred;
		return $deferred->promise();
	}

	protected function removeStoppedChannelData() {
		if ($this->channelId !== null) {
			$this->hb->removeChannel($this->channelId);
			$this->fireOnTradeStopped();
		}
		$this->channelId = null;
	}

	protected function fireOnTradeStarted() {
		if (!$this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = true;
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onTradeStarted();
			}
		}
	}

	protected function fireOnTradeStopped() {
		if ($this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = false;
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onTradeStopped();
			}
		}
	}

}
