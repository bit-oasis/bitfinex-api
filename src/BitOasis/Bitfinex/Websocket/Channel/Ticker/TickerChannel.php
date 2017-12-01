<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Ticker;

use BitOasis\Bitfinex\Exception\SubscriptionFailedException;
use BitOasis\Bitfinex\Websocket\HeartBeat;
use Nette\Utils\Json;
use Psr\Log\NullLogger;
use Ratchet\Client\WebSocket;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\EventLoop\LoopInterface;
use BitOasis\Bitfinex\Websocket\Channel\BitfinexPublicChannel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class TickerChannel extends BitfinexPublicChannel implements LoggerAwareInterface {
	use LoggerAwareTrait;
	
	const CHANNEL_NAME = 'ticker';

	/** @var HeartBeat */
	protected $hb;

	/** @var TickerChannelSubscriber[] */
	protected $subscribers = [];

	/** @var Deferred|null */
	protected $subscribeDeferred;

	/** @var Deferred|null */
	protected $unsubscribeDeferred;

	/** @var bool */
	protected $lastStatusSentWasStarted = false;

	public function __construct(string $symbol, LoopInterface $loop) {
		parent::__construct($symbol);
		$this->hb = new HeartBeat([$this, 'onHeartBeatFailure'], [$this, 'onHeartBeatResumed'], $loop);
		$this->logger = new NullLogger();
	}


	public function addTickerChannelSubscriber(TickerChannelSubscriber $subscriber) {
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
			$message = new TickerMessage($update[0], $update[2], $update[4], $update[5] * 100, $update[6], $update[8], $update[9]);
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onTickerUpdateReceived($message);
			}
		}
	}

	public function onWebsocketErrorMessage($data) {
		if ($this->areChannelDataValid($data)) {
			$this->logger->error("Can't subscribe to orderbook channel: {message} ({code})", ['message' => $data['msg'], 'code' => $data['code']]);
			if ($this->subscribeDeferred !== null) {
				$this->subscribeDeferred->reject();
				$this->subscribeDeferred = null;
			}
			throw new SubscriptionFailedException("Can't subscribe to orderbook channel: $data[msg] ($data[code])"); // todo: handle specific situations
		}
	}

	public function onHeartBeatFailure($channelId) {
		$this->logger->warning('Heartbeat failure');
		$this->fireOnTickerStopped();
	}

	public function onHeartBeatResumed($channelId) {
		$this->fireOnTickerStarted();
	}

	protected function areChannelDataValid($data): bool {
		return isset($data['channel'], $data['symbol']) && $data['channel'] === self::CHANNEL_NAME && $data['symbol'] === $this->symbol;
	}

	public function onMaintenanceStarted() {
		$this->fireOnTickerStopped();
	}

	public function onWebsocketChannelSubscribed($data) {
		if ($this->areChannelDataValid($data) && isset($data['chanId'])) {
			if ($this->subscribeDeferred !== null) {
				$this->subscribeDeferred->resolve($data['chanId']);
				$this->subscribeDeferred = null;
			}

			$this->channelId = $data['chanId'];
			$this->hb->addChannel($this->channelId);
			$this->fireOnTickerStarted();
		}
	}

	public function onWebsocketChannelUnsubscribed($data) {
		if ($this->channelId !== null && $data['chanId'] === $this->channelId) {
			if ($data['status'] === 'OK') {
				$this->removeStoppedChannelData();
			} else {
				throw new SubscriptionFailedException("Can't unsubscribe from orderbook channel: $data[msg] ($data[code])"); // todo: handle specific situations
			}
		}
	}

	public function __toString() {
		return $this->symbol . ' ' . self::CHANNEL_NAME . ' channel with ' . ($this->channelId === null ? '' : 'chanId=' . $this->channelId . ', ');
	}

	protected function subscribe(WebSocket $conn): Promise {
		if ($this->subscribeDeferred === null) {
			$data = Json::encode([
				'event' => 'subscribe',
				'channel' => self::CHANNEL_NAME,
				'symbol' => $this->symbol,
			]);
			$conn->send($data);
			$this->logger->debug('Websocket message sent: {data}', ['data' => $data]);
			$this->subscribeDeferred = new Deferred();
		}
		return $this->subscribeDeferred->promise();
	}

	protected function unsubscribe(WebSocket $conn): Promise {
		if ($this->unsubscribeDeferred === null) {
			$data = Json::encode([
				'event' => 'unsubscribe',
				'chanId' => $this->channelId,
			]);
			$conn->send($data);
			$this->logger->debug('Websocket message sent: {data}', ['data' => $data]);
			$this->unsubscribeDeferred = new Deferred();
		}
		return $this->unsubscribeDeferred->promise();
	}

	protected function removeStoppedChannelData() {
		if ($this->channelId !== null) {
			$this->hb->removeChannel($this->channelId);
			if ($this->subscribeDeferred !== null) {
				$this->subscribeDeferred->reject();
				$this->subscribeDeferred = null;
			}
			if ($this->unsubscribeDeferred !== null) {
				$this->unsubscribeDeferred->resolve();
				$this->unsubscribeDeferred = null;
			}
			$this->fireOnTickerStopped();
		}
		$this->channelId = null;
	}

	protected function fireOnTickerStarted() {
		if (!$this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = true;
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onTickerStarted();
			}
		}
	}

	protected function fireOnTickerStopped() {
		if ($this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = false;
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onTickerStopped();
			}
		}
	}

}
