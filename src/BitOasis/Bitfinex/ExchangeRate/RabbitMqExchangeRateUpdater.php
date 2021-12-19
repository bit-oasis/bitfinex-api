<?php

namespace BitOasis\Bitfinex\ExchangeRate;

use Bunny\Channel;
use Bunny\Message;
use InvalidArgumentException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\Promise;
use React\Promise\PromiseInterface;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class RabbitMqExchangeRateUpdater implements LoggerAwareInterface {
	use LoggerAwareTrait;

	const WEB_RPC_EXCHANGE = 'bitoasis.web-events';
	const WEB_RPC_EXCHANGE_TYPE = 'direct';

	/** @var int */
	protected $prefetchCount = 25;

	/** @var ExchangeRateUpdateListener[] */
	protected $exchangeRateUpdateListeners = [];

	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function setPrefetchCount(int $prefetchCount): void {
		if ($prefetchCount < 1) {
			throw new InvalidArgumentException($prefetchCount . ' is not a valid prefetch count!');
		}
		$this->prefetchCount = $prefetchCount;
	}

	public function addExchangeRateUpdateListener(ExchangeRateUpdateListener $listener): void {
		$this->exchangeRateUpdateListeners[] = $listener;
	}

	public function initializeMq(Channel $channel): PromiseInterface {
		return $channel->exchangeDeclare(self::WEB_RPC_EXCHANGE, self::WEB_RPC_EXCHANGE_TYPE, false, true)
			->then(function() use($channel) {
				$queues = [];
				$queues[] = $channel->queueDeclare('usd-exchange-rate.update', false, true);
				return Promise\all($queues);
			})->then(function() use($channel) {
				$bindings = [];
				$bindings[] = $channel->queueBind('usd-exchange-rate.update', self::WEB_RPC_EXCHANGE, 'update');
				return Promise\all($bindings);
			})->then(function() use($channel) {
				return $channel->qos(0, $this->prefetchCount);
			})->then(function() use($channel) {
				$consumers = [];
				$consumers[] = $channel->consume([$this, 'updateExchangeRate'], 'usd-exchange-rate.update', 'usd-exchange-rate.update');
				return Promise\all($consumers);
			})->then(function() {
				$this->logger->info('RabbitMQ {type} exchange {exchange} declared', ['exchange' => self::WEB_RPC_EXCHANGE, 'type' => self::WEB_RPC_EXCHANGE_TYPE]);
			});
	}

	public function updateExchangeRate(Message $message, Channel $channel) {
		try {
			$data = Json::decode($message->content, JSON_OBJECT_AS_ARRAY);
		} catch (JsonException $e) {
			$this->logger->error('Cannot decode JSON exchange rate update message - ' . $message->content);
			return $channel->ack($message)->done();
		}

		$this->fireExchangeRateUpdated($data);
		$channel->ack($message)->done();
	}

	protected function fireExchangeRateUpdated(array $data): void {
		if (!isset($data['currency'], $data['exchangeRate'], $data['timestamp'])) {
			$this->logger->error('Invalid exchange rate update values - ' . Json::encode($data));
			return;
		}

		foreach ($this->exchangeRateUpdateListeners as $listener) {
			$listener->onExchangeRateUpdated($data['currency'], $data['exchangeRate'], $data['timestamp']);
		}
	}
}
