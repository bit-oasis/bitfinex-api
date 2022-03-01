<?php

namespace BitOasis\Bitfinex\ExchangeRate;

use Bunny\Channel;
use Bunny\Message;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
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

	const FIAT_RATES_EXCHANGE = 'bitoasis.fiat-rates';
	const FIAT_RATES_EXCHANGE_TYPE = 'direct';

	/** @var int|null */
	protected $prefetchCount = 25;

	/** @var string */
	protected $queueName;

	/** @var ExchangeRateUpdateListener[] */
	protected $exchangeRateUpdateListeners = [];

	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * @param int|null $prefetchCount NULL to not set RMQ QOS
	 * @throws InvalidArgumentException
	 */
	public function setPrefetchCount($prefetchCount): void {
		if ($prefetchCount !== null && $prefetchCount < 1) {
			throw new InvalidArgumentException($prefetchCount . ' is not a valid prefetch count!');
		}
		$this->prefetchCount = $prefetchCount;
	}

	public function addExchangeRateUpdateListener(ExchangeRateUpdateListener $listener): void {
		$this->exchangeRateUpdateListeners[] = $listener;
	}

	public function initializeMq(Channel $channel): PromiseInterface {
		return $channel->exchangeDeclare(self::FIAT_RATES_EXCHANGE, self::FIAT_RATES_EXCHANGE_TYPE, false, true)
			->then(function() use($channel) {
				return $channel->queueDeclare('', false, true, true);
			})->then(function(MethodQueueDeclareOkFrame $queueDeclareReply) use ($channel) {
				$this->queueName = $queueDeclareReply->queue;
				$bindings = [];
				$bindings[] = $channel->queueBind($this->queueName, self::FIAT_RATES_EXCHANGE, 'update');
				return Promise\all($bindings);
			})->then(function() use($channel) {
				if ($this->prefetchCount === null) {
					//Should return something, but the value is not used in next hanndler anyway...
					return Promise\resolve();
				}
				return $channel->qos(0, $this->prefetchCount);
			})->then(function() use($channel) {
				$consumers = [];
				$consumers[] = $channel->consume([$this, 'updateExchangeRate'], $this->queueName);
				return Promise\all($consumers);
			})->then(function() {
				$this->logger->info('RabbitMQ {type} exchange {exchange} declared', ['exchange' => self::FIAT_RATES_EXCHANGE, 'type' => self::FIAT_RATES_EXCHANGE_TYPE]);
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
		$channel->ack($message)->done(
			null,
			function($e) {
				\print_r($e);
			}
		);
	}

	protected function fireExchangeRateUpdated(array $data): void {
		if (!isset($data['currency'], $data['exchangeRate'], $data['timestamp'])) {
			$this->logger->error('Invalid exchange rate update values - ' . Json::encode($data));
			return;
		}

		foreach ($this->exchangeRateUpdateListeners as $listener) {
			try {
				$listener->onExchangeRateUpdated($data['currency'], $data['exchangeRate'], $data['timestamp']);
			} catch (\Throwable $e) {
				$loggerData = [
					'class' => \get_class($listener),
					'message' => $e->getMessage(),
				];
				$this->logger->error('Exchange rate update listener [{class}] thrown an exception: {message}', $loggerData);
			}
		}
	}
}
