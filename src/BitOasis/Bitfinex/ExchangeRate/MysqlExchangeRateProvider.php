<?php

namespace BitOasis\Bitfinex\ExchangeRate;

use BitOasis\Bitfinex\Exception\ExchangeRateNotFoundException;
use Foowie\ReactMySql\Pool;
use Foowie\ReactMySql\Result;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Promise;
use React\Promise\ExtendedPromiseInterface;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class MysqlExchangeRateProvider implements ExchangeRateProvider, ExchangeRateUpdateListener, LoggerAwareInterface {
	use LoggerAwareTrait;

	/** @var Pool */
	protected $pool;

	/** @var LoopInterface */
	protected $loop;

	/** @var TimerInterface */
	protected $forceUpdateCache;

	/** @var int [seconds] */
	protected $forceUpdateCacheInterval = 60;

	/** @var float[] associative array [currency => exchange_rate] */
	private $cache;

	/** @var float */
	private $cacheLastUpdated;

	public function __construct(Pool $pool, LoopInterface $loop) {
		$this->pool = $pool;
		$this->loop = $loop;
		$this->forceUpdateCache = $this->createForceUpdateCacheTimer();
		$this->logger = new NullLogger();
	}

	/**
	 * @param int $forceUpdateCacheInterval in seconds
	 * @throws InvalidArgumentException
	 */
	public function setForceUpdateCacheInterval(int $forceUpdateCacheInterval): void {
		if ($forceUpdateCacheInterval < 1) {
			throw new InvalidArgumentException($forceUpdateCacheInterval . ' is not a valid interval!');
		}

		$this->forceUpdateCacheInterval = $forceUpdateCacheInterval;
		$this->forceUpdateCache->cancel();
		$this->forceUpdateCache = $this->createForceUpdateCacheTimer();
	}

	public function getExchangeRate(string $currency): ExtendedPromiseInterface {
		return $this->loadExchangeRates()
			->then(function(array $exchangeRates) use($currency) {
				if (!isset($exchangeRates[$currency])) {
					return Promise\reject(new ExchangeRateNotFoundException('Cannot find USD exchange rate for ' . $currency . ' currency!'));
				}

				return $exchangeRates[$currency];
			});
	}

	public function getExchangeRates(): ExtendedPromiseInterface {
		return $this->loadExchangeRates();
	}

	public function onExchangeRateUpdated(string $currency, float $exchangeRate, float $timestamp): void {
		if (($this->cacheLastUpdated ?? 0) <= $timestamp) {
			$this->cacheLastUpdated = $timestamp;
			$this->cache[$currency] = $exchangeRate;
			$this->logger->info('USD exchange rate updated [{currency} - {rate}]', ['currency' => $currency, 'rate' => $exchangeRate]);
		}
	}

	protected function loadExchangeRates(): ExtendedPromiseInterface {
		if ($this->cache !== null) {
			return Promise\resolve($this->cache);
		}

		return $this->pool->query('SELECT currency_id, exchange_rate FROM usd_exchange_rate')
			->then(function(Result $result) {
				$this->logger->debug('USD exchange rates loaded from DB');
				$this->cacheLastUpdated = \microtime();

				foreach ($result->getResult() as $row) {
					$this->cache[$row['currency_id']] = $row['exchange_rate'];
				}

				return $this->cache;
			});
	}

	protected function createForceUpdateCacheTimer(): TimerInterface {
		return $this->loop->addPeriodicTimer($this->forceUpdateCacheInterval, function() {
			$this->cache = null;
		});
	}
}
