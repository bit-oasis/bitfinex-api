<?php

namespace BitOasis\Bitfinex\ExchangeRate;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface ExchangeRateUpdateListener {

	public function onExchangeRateUpdated(string $currency, float $exchangeRate, float $timestamp): void;
}
