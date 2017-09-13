<?php

namespace BitOasis\Bitfinex\Websocket;

use BitOasis\Bitfinex\Exception\AuthenticationFailedException;
use BitOasis\Bitfinex\Exception\CannotAddSubscriberException;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class BitfinexWebsocket implements LoggerAwareInterface {
	use LoggerAwareTrait;

	const WEBSOCKET_URL = 'wss://api.bitfinex.com/ws/2';

	/** @var BitfinexWebsocketSubscriber[] */
	protected $subscribers = [];

	/** @var string */
	protected $apiKey;

	/** @var string */
	protected $apiSecret;

	/** @var LoopInterface */
	protected $loop;

	/** @var string */
	protected $origin = 'localhost';

	/** @var bool */
	protected $running = false;

	public function __construct(string $apiKey = null, string $apiSecret = null, LoopInterface $loop) {
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;
		$this->loop = $loop;
		$this->logger = new NullLogger();
	}

	/**
	 * @param BitfinexWebsocketSubscriber $subscriber
	 * @throws CannotAddSubscriberException
	 */
	public function addSubscriber(BitfinexWebsocketSubscriber $subscriber) {
		if ($this->running) {
			throw new CannotAddSubscriberException("Can't add subscriber when websocket is connected and running!");
		}
	    $this->subscribers[] = $subscriber;
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
			$this->running = true;

			$conn->on('message', function(MessageInterface $msg) use($conn) {
				$data = Json::decode($msg, JSON_OBJECT_AS_ARRAY);
				$this->logger->debug('Bitfinex message received: {message}', ['message' => $data]);
				$event = isset($data['event']) ? $data['event'] : null;
				if($event === null) {
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketMessageReceived($data);
					}
				} else if($event === 'subscribed') {
					$this->logger->debug('Subscribed to channel {channel}', ['channel' => $data]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketChannelSubscribed($data);
					}
				} else if($event === 'unsubscribed') {
					$this->logger->debug('Unsubscribed from channel {channel}', ['channel' => $data]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketChannelUnsubscribed($data);
					}
				} else if($event === 'auth') {
					if ($data['status'] !== 'OK') {
						$this->logger->error('Websocket authenticated failed: {error}', ['error' => $data]);
						throw new AuthenticationFailedException('Bitfinex authentication failed: ' . $data['msg']);
					}
					$this->logger->info('Websocket authenticated');
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketAuthenticated();
					}
				} else if($event === 'error') {
					$this->logger->warning('Error event received: {error}', ['error' => $data]);
					foreach ($this->subscribers as $subscriber) {
						$subscriber->onWebsocketErrorMessage($data);
					}
				} else if ($event === 'info') {
					if (isset($data['version']) && !isset($data['code'])) {
						$this->logger->info('Websocket connected to API version {version}', ['version' => $data['version']]);
						foreach ($this->subscribers as $subscriber) {
							$subscriber->onWebsocketConnected($conn, $data['version']);
						}
						if ($this->isAuthChannelRequired()) {
							$this->logger->debug('Authentication requested');
							$conn->send(Json::encode($this->getAuthentication()));
						}
					} else if (isset($data['code'])) {
						if ($data['code'] === 20060) { // 20060 : Entering in Maintenance mode. Please pause any activity and resume after receiving the info message 20061 (it should take 120 seconds at most).
							$this->logger->info('Bitfinex maintenance mode started: {data}', ['data' => $data]);
							foreach ($this->subscribers as $subscriber) {
								$subscriber->onMaintenanceStarted();
							}
						} else if ($data['code'] === 20061) { // 20061 : Maintenance ended. You can resume normal activity. It is advised to unsubscribe/subscribe again all channels.
							$this->logger->info('Bitfinex maintenance mode ended: {data}', ['data' => $data]);
							foreach ($this->subscribers as $subscriber) {
								$subscriber->onMaintenanceEnded($conn);
							}
						} else if ($data['code'] === 20051) { // 20051 : Stop/Restart Websocket Server (please reconnect)
							$this->logger->info('Bitfinex websocket restart requested: {data}', ['data' => $data]);
							$conn->close();
						}
					}
				}
			});

			$conn->on('close', function($code = null, $reason = null) {
				$this->running = false;
				$this->logger->info('Bitfinex websocket closed with code {code} and reason {reason}, reconnecting in 10 seconds', ['code' => $code, 'reason' => $reason]);
				foreach ($this->subscribers as $subscriber) {
					$subscriber->onWebsocketClosed();
				}
				$this->loop->addTimer(10, function() {
					$this->connect();
				});
			});

			$this->logger->debug('Connecting to Bitfinex websocket');
		}, function(\Exception $e) {
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onWebsocketClosed();
			}
			throw $e;
		});
	}

	protected function isAuthChannelRequired(): bool {
	    foreach ($this->subscribers as $subscriber) {
	    	if ($subscriber->isAuthenticatedChannelRequired()) {
	    		return true;
		    }
	    }
	    return false;
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

}