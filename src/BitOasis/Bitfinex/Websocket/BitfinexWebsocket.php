<?php

namespace BitOasis\Bitfinex\Websocket;

use BitOasis\Bitfinex\Exception\AuthenticationFailedException;
use BitOasis\Bitfinex\Exception\CannotAddSubscriberException;
use BitOasis\Bitfinex\Websocket\Channel\Authenticated\AuthenticatedChannel;
use BitOasis\Bitfinex\Websocket\Channel\Authenticated\AuthenticatedSubchannel;
use Nette\Utils\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class BitfinexWebsocket implements LoggerAwareInterface {
	use LoggerAwareTrait;

	const WEBSOCKET_URL = 'wss://api.bitfinex.com/ws/2';

	/** @var AuthenticatedChannel */
	protected $authChannel;

	/** @var BitfinexWebsocketSubscriber[] */
	protected $subscribers = [];

	/** @var LoopInterface */
	protected $loop;

	/** @var string */
	protected $origin = 'localhost';

	/** @var WebSocket|null */
	protected $connection;

	/** @var Deferred|null */
	protected $closeDeferred;

	/** @var TimerInterface|null */
	protected $reconnectTimer;

	public function __construct(string $apiKey = null, string $apiSecret = null, LoopInterface $loop) {
		$this->authChannel = new AuthenticatedChannel($apiKey, $apiSecret, $loop);
		$this->subscribers[] = $this->authChannel;
		$this->loop = $loop;
		$this->logger = new NullLogger();
	}

	public function setLogger(LoggerInterface $logger) {
		$this->authChannel->setLogger($logger);
		$this->logger = $logger;
	}

	/**
	 * @param BitfinexWebsocketSubscriber $subscriber
	 * @throws CannotAddSubscriberException
	 */
	public function addSubscriber(BitfinexWebsocketSubscriber $subscriber) {
		if ($this->isRunning()) {
			throw new CannotAddSubscriberException("Can't add subscriber when websocket is connected and running!");
		}
		if ($subscriber instanceof AuthenticatedSubchannel && $subscriber->isAuthenticatedChannelRequired()) {
			$this->authChannel->addSubchannel($subscriber);
		} else {
			$this->subscribers[] = $subscriber;
		}
		$this->logger->debug('New subscriber {subscriber}', ['subscriber' => $subscriber]);
	}

	/**
	 * @param string $origin
	 */
	public function setOrigin(string $origin) {
		$this->origin = $origin;
	}

	public function connect(): PromiseInterface {
		$connector = new Connector($this->loop);
		return $connector(self::WEBSOCKET_URL, [], ['Origin' => $this->origin])->then(function(WebSocket $conn) {
			$this->connection = $conn;

			$conn->on('message', function(MessageInterface $msg) use($conn) {
				$stringMsg = (string)$msg;
				$data = Json::decode($stringMsg, JSON_OBJECT_AS_ARRAY);
				if (!(isset($data[1]) && $data[1] === 'hb')) {
					$this->logger->debug('Bitfinex message received: {message}', ['message' => $stringMsg]);
				}
				$event = $data['event'] ?? null;
				if($event === null) {
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketMessageReceived($data);
					}
				} else if($event === 'subscribed') {
					$this->logger->debug('Subscribed to channel {channel}', ['channel' => $stringMsg]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketChannelSubscribed($data);
					}
				} else if($event === 'unsubscribed') {
					$this->logger->debug('Unsubscribed from channel {channel}', ['channel' => $stringMsg]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketChannelUnsubscribed($data);
					}
				} else if($event === 'auth') {
					if ($data['status'] !== 'OK') {
						$this->logger->error('Websocket authenticated failed: {error}', ['error' => $stringMsg]);
						throw new AuthenticationFailedException('Bitfinex authentication failed: ' . $data['msg']);
					}
					$this->logger->notice('Websocket authenticated');
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketAuthenticated();
					}
				} else if($event === 'error') {
					$this->logger->warning('Error event received: {error}', ['error' => $stringMsg]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketErrorMessage($data);
					}
				} else if ($event === 'info') {
					if (isset($data['version']) && !isset($data['code'])) {
						$this->logger->notice('Websocket connected to API version {version}', ['version' => $data['version']]);
						foreach ($this->subscribers as $subscriber) {
							$subscriber->onWebsocketConnected($conn, $data['version']);
						}
					} else if (isset($data['code'])) {
						if ($data['code'] === 20060) { // 20060 : Entering in Maintenance mode. Please pause any activity and resume after receiving the info message 20061 (it should take 120 seconds at most).
							$this->logger->notice('Bitfinex maintenance mode started: {data}', ['data' => $stringMsg]);
							foreach ($this->subscribers as $subscriber) {
								$subscriber->onMaintenanceStarted();
							}
						} else if ($data['code'] === 20061) { // 20061 : Maintenance ended. You can resume normal activity. It is advised to unsubscribe/subscribe again all channels.
							$this->logger->notice('Bitfinex maintenance mode ended: {data}', ['data' => $stringMsg]);
							foreach ($this->subscribers as $subscriber) {
								$subscriber->onMaintenanceEnded($conn);
							}
						} else if ($data['code'] === 20051) { // 20051 : Stop/Restart Websocket Server (please reconnect)
							$this->logger->notice('Bitfinex websocket restart requested: {data}', ['data' => $stringMsg]);
							$conn->close();
						}
					}
				}
			});

			$conn->on('close', function($code = null, $reason = null) {
				$this->logger->notice('Bitfinex websocket closed with code {code} and reason {reason}, reconnecting in 10 seconds', ['code' => $code, 'reason' => $reason]);
				foreach ($this->subscribers as $subscriber) {
					$subscriber->onWebsocketClosed();
				}
				$this->connection = null;
				if ($this->closeDeferred !== null) {
					$this->closeDeferred->resolve();
					$this->closeDeferred = null;
				} else {
					$this->reconnectTimer = $this->loop->addTimer(10, function() {
						$this->reconnectTimer = null;
						$this->connect();
					});
				}
			});

			$this->logger->debug('Connecting to Bitfinex websocket');
		}, function(\Exception $e) {
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onWebsocketClosed();
			}
			throw $e;
		});
	}

	public function close(): PromiseInterface {
		if ($this->reconnectTimer !== null) {
			$this->reconnectTimer->cancel();
			$this->reconnectTimer = null;
		}
		if (!$this->isRunning()) {
			return \React\Promise\resolve();
		}
		if ($this->closeDeferred === null) {
			$this->closeDeferred = new Deferred();
			$promise = $this->closeDeferred->promise();
			$this->connection->close();
			return $promise;
		}
		return $this->closeDeferred->promise();
	}

	protected function isRunning(): bool {
		return $this->connection !== null;
	}

}