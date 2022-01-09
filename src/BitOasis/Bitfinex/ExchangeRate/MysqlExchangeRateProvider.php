<?php

namespace BitOasis\Bitfinex\ExchangeRate;

use BitOasis\Bitfinex\Exception\ExchangeRateNotFoundException;
use Foowie\ReactMySql\Pool;
use Foowie\ReactMySql\Result;
use InvalidArgumentException;
use Nette\Utils\DateTime;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
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

	/** @var \React\EventLoop\Timer\TimerInterface|\React\EventLoop\TimerInterface */
	protected $forceUpdateCache;

	/** @var int [seconds] */
	protected $forceUpdateCacheInterval = 30;

	/** @var float[] associative array [currency => exchange_rate] */
	private $cache;

	/** @var Promise\Deferred|null */
	private $deferred;

	/** @var DateTime[] */
	private $exchangeRateLastUpdated;

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
		if (method_exists($this->forceUpdateCache, 'cancel')) {
			$this->forceUpdateCache->cancel();
		} else {
			\React\EventLoop\Loop::get()->cancelTimer($this->forceUpdateCache);
		}
		$this->forceUpdateCache = $this->createForceUpdateCacheTimer();
	}

	/**
	 * @inheritDoc
	 */
	public function getExchangeRate(string $currency): ExtendedPromiseInterface {
		return $this->loadExchangeRates()
			->then(function(array $exchangeRates) use($currency) {
				if (!isset($exchangeRates[$currency])) {
					return Promise\reject(new ExchangeRateNotFoundException('Cannot find USD exchange rate for ' . $currency . ' currency!'));
				}

				return $exchangeRates[$currency];
			});
	}

	/**
	 * @inheritDoc
	 */
	public function getExchangeRates(): ExtendedPromiseInterface {
		return $this->loadExchangeRates();
	}

	public function onExchangeRateUpdated(string $currency, float $exchangeRate, float $timestamp): void {
		$dateUpdated = DateTime::from($timestamp);
		if (!isset($this->exchangeRateLastUpdated[$currency]) || $this->exchangeRateLastUpdated[$currency] <= $dateUpdated) {
			$this->exchangeRateLastUpdated[$currency] = $dateUpdated;
			$this->cache[$currency] = $exchangeRate;
			$this->logger->info('USD exchange rate updated [{currency} - {rate}]', ['currency' => $currency, 'rate' => $exchangeRate]);
		}
	}

	protected function loadExchangeRates(): ExtendedPromiseInterface {
		if ($this->cache !== null) {
			return Promise\resolve($this->cache);
		}

		if ($this->deferred !== null) {
			return $this->deferred->promise();
		}

		$this->deferred = new Promise\Deferred();
		$this->pool->query('SELECT currency_id, exchange_rate, last_update FROM usd_exchange_rate')
			->then(function(Result $result) {
				$this->logger->debug('USD exchange rates loaded from DB');
				$this->exchangeRateLastUpdated = [];

				foreach ($result->getResult() as $row) {
					$this->cache[$row['currency_id']] = $row['exchange_rate'];
					$this->exchangeRateLastUpdated[$row['currency_id']] = DateTime::from($row['last_update']);
				}
			})->done(function() {
				$this->deferred->resolve($this->cache);
				$this->deferred = null;
			});

		return $this->deferred->promise();
	}

	/**
	 * @return \React\EventLoop\Timer\TimerInterface|\React\EventLoop\TimerInterface
	 */
	protected function createForceUpdateCacheTimer() {
		return $this->loop->addPeriodicTimer($this->forceUpdateCacheInterval, function() {
			$this->cache = null;
		});
	}
}
