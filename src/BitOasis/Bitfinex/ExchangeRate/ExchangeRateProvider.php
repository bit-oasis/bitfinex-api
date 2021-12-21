<?php

namespace BitOasis\Bitfinex\ExchangeRate;

use React\Promise\ExtendedPromiseInterface;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface ExchangeRateProvider {

	/**
	 * @return ExtendedPromiseInterface returs float value
	 */
	public function getExchangeRate(string $currency): ExtendedPromiseInterface;

	/**
	 * @return ExtendedPromiseInterface returned value is associative array [currency => (float)exchange_rate]
	 */
	public function getExchangeRates(): ExtendedPromiseInterface;
}
