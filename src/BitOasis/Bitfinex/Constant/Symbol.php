<?php

namespace BitOasis\Bitfinex\Constant;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
final class Symbol {

	const TBTCUSD = 'tBTCUSD';
	const TETHUSD = 'tETHUSD';
	const TXRPUSD = 'tXRPUSD';

	public static function isTrading(string $symbol): bool {
	    return $symbol !== '' && $symbol[0] === 't';
	}

}