<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated;

use BitOasis\Bitfinex\Websocket\HeartBeat;
use Ratchet\Client\WebSocket;
use React\Promise;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use BitOasis\Bitfinex\Websocket\ConnectionWebsocketSubscriberAdapter;
use BitOasis\Bitfinex\Exception\CannotAddSubscriberException;
use BitOasis\Bitfinex\Exception\SubscriptionFailedException;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class AuthenticatedChannel extends ConnectionWebsocketSubscriberAdapter implements LoggerAwareInterface {
	use LoggerAwareTrait;

	const CHANNEL_ID = 0;

	/** @var string */
	protected $apiKey;

	/** @var string */
	protected $apiSecret;

	/** @var HeartBeat */
	protected $hb;

	/** @var AuthenticatedSubchannel[] */
	protected $subchannels = [];

	/** @var bool */
	protected $authenticated = false;

	/** @var Deferred|null */
	protected $subscribeDeferred;

	/** @var Deferred|null */
	protected $unsubscribeDeferred;

	/** @var bool */
	protected $lastStatusSentWasStarted = false;

	public function __construct(string $apiKey = null, string $apiSecret = null, LoopInterface $loop) {
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;
		$this->hb = new HeartBeat([$this, 'onHeartBeatFailure'], [$this, 'onHeartBeatResumed'], $loop);
		$this->logger = new NullLogger();
	}

	/**
	 * @param AuthenticatedSubchannel $subchannel
	 * @throws CannotAddSubscriberException
	 */
	public function addSubchannel(AuthenticatedSubchannel $subchannel) {
		if (!$subchannel->isAuthenticatedChannelRequired()) {
			throw new CannotAddSubscriberException("Can't add subchannel without authentication!");
		}
		$this->subchannels[] = $subchannel;
	}

	public function isAuthenticatedChannelRequired(): bool {
		return true;
	}

	public function onWebsocketConnected(WebSocket $conn, $version) {
		parent::onWebsocketConnected($conn, $version);
		$this->subscribe($conn);
		foreach ($this->subchannels as $subchannel) {
			$subchannel->onWebsocketConnected($conn, $version);
		}
	}

	public function onWebsocketClosed() {
		$this->removeStoppedChannelData();
		foreach ($this->subchannels as $subchannel) {
			$subchannel->onWebsocketClosed();
		}
		parent::onWebsocketClosed();
	}

	public function onWebsocketAuthenticated() {
		$this->authenticated = true;
		foreach ($this->subchannels as $subchannel) {
			$subchannel->onWebsocketAuthenticated();
		}
		if ($this->subscribeDeferred !== null) {
			$this->subscribeDeferred->resolve();
			$this->subscribeDeferred = null;
		}
		$this->hb->addChannel(self::CHANNEL_ID);
		$this->fireOnAuthChannelStarted();
	}

	public function onWebsocketChannelSubscribed($data) {
		foreach ($this->subchannels as $subchannel) {
			$subchannel->onWebsocketChannelSubscribed($data);
		}
	}

	public function onWebsocketChannelUnsubscribed($data) {
		if ($this->unsubscribeDeferred !== null && $data['chanId'] === self::CHANNEL_ID) {
			if ($data['status'] === 'OK') {
				$this->removeStoppedChannelData();
				foreach ($this->subchannels as $subchannel) {
					$subchannel->onWebsocketChannelUnsubscribed($data);
				}
			} else {
				throw new SubscriptionFailedException("Can't unsubscribe from authenticated channel: $data[msg] ($data[code])"); // todo: handle specific situations
			}
		}
	}

	public function onWebsocketMessageReceived($data) {
		if (isset($data[0]) && $data[0] === self::CHANNEL_ID) {
			$this->hb->heartBeat(self::CHANNEL_ID);
			if ($data[1] === 'hb') {
				return;
			}
			foreach ($this->subchannels as $subchannel) {
				$subchannel->onWebsocketMessageReceived($data);
			}
		}
	}

	public function onWebsocketErrorMessage($data) {
		foreach ($this->subchannels as $subchannel) {
			$subchannel->onWebsocketErrorMessage($data);
		}
	}

	public function onMaintenanceStarted() {
		$this->fireOnAuthChannelStopped();
		foreach ($this->subchannels as $subchannel) {
			$subchannel->onMaintenanceStarted();
		}
	}

	public function onMaintenanceEnded(WebSocket $conn) {
		$this->unsubscribe($conn)->done(function() {
			$this->subscribe($this->connection);
		});
		foreach ($this->subchannels as $subchannel) {
			$subchannel->onMaintenanceEnded($conn);
		}
	}

	public function __toString() {
		return 'authenticated channel with chanId=' . self::CHANNEL_ID;
	}

	protected function isAuthChannelRequired(): bool {
		return !empty($this->subchannels);
	}

	protected function subscribe(WebSocket $conn): ExtendedPromiseInterface {
		if ($this->isAuthChannelRequired()) {
			if ($this->subscribeDeferred === null) {
				$this->logger->debug('Authentication requested');
				$conn->send(Json::encode($this->getAuthentication()));
				$this->subscribeDeferred = new Deferred();
			}
			return $this->subscribeDeferred->promise();
		}
		return Promise\resolve();
	}

	protected function unsubscribe(WebSocket $conn): ExtendedPromiseInterface {
		if ($this->isAuthChannelRequired()) {
			if ($this->unsubscribeDeferred === null) {
				$data = [
					'event' => 'unsubscribe',
					'chanId' => self::CHANNEL_ID,
				];
				$conn->send(Json::encode($data));
				$this->logger->debug('Websocket message sent: {data}', ['data' => $data]);
				$this->unsubscribeDeferred = new Deferred();
			}
			return $this->unsubscribeDeferred->promise();
		}
		return Promise\resolve();
	}

	protected function getAuthentication(): array {
		$nonce = $this->getNonce();
		$payload = 'AUTH' . $nonce;
		return [
			'event' => 'auth',
			'apiKey' => $this->apiKey,
			'authSig' => $this->getSignature($payload),
			'authPayload' => $payload,
			'authNonce' => $nonce,
		];
	}

	protected function getSignature(string $payload): string {
		return Strings::lower(hash_hmac('sha384', $payload, $this->apiSecret));
	}

	protected function getNonce(): string {
		$microTime = explode(' ', microtime());
		return $microTime[1] . substr($microTime[0], 2, 6);
	}

	public function onHeartBeatFailure($channelId) {
		$this->logger->warning('Heartbeat failure');
		$this->fireOnAuthChannelStopped();
	}

	public function onHeartBeatResumed($channelId) {
		$this->fireOnAuthChannelStarted();
	}

	protected function fireOnAuthChannelStarted() {
		if (!$this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = true;
			foreach ($this->subchannels as $subchannel) {
				$subchannel->onAuthChannelStarted();
			}
		}
	}

	protected function fireOnAuthChannelStopped() {
		if ($this->lastStatusSentWasStarted) {
			$this->lastStatusSentWasStarted = false;
			foreach ($this->subchannels as $subchannel) {
				$subchannel->onAuthChannelStopped();
			}
		}
	}

	protected function removeStoppedChannelData() {
		if ($this->authenticated) {
			$this->hb->removeChannel(self::CHANNEL_ID);
			if ($this->subscribeDeferred !== null) {
				$this->subscribeDeferred->reject();
				$this->subscribeDeferred = null;
			}
			if ($this->unsubscribeDeferred !== null) {
				$this->unsubscribeDeferred->resolve();
				$this->unsubscribeDeferred = null;
			}
			$this->fireOnAuthChannelStopped();
		}
		$this->authenticated = false;
	}

}