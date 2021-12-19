<?php

namespace BitOasis\Bitfinex\ExchangeRate;

use React\Promise\ExtendedPromiseInterface;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface ExchangeRateProvider {

	public function getExchangeRate(string $currency): ExtendedPromiseInterface;

	public function getExchangeRates(): ExtendedPromiseInterface;
}
