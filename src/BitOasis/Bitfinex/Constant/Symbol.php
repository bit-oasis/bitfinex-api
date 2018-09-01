<?php

namespace BitOasis\Bitfinex\Constant;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
final class Symbol {

	const TBTCUSD = 'tBTCUSD';
	const TETHUSD = 'tETHUSD';
	const TETCUSD = 'tETCUSD';
	const TETCBTC = 'tETCBTC';
	const TETHBTC = 'tETHBTC';
	const TXRPBTC = 'tXRPBTC';
	const TBCHUSD = 'tBCHUSD';
	const TBCHBTC = 'tBCHBTC';
	const TBCHETH = 'tBCHETH';
	const TXRPUSD = 'tXRPUSD';
	const TLTCUSD = 'tLTCUSD';
	const TLTCBTC = 'tLTCBTC';
	const TZECUSD = 'tZECUSD';
	const TZECBTC = 'tZECBTC';
	const TXMRUSD = 'tXMRUSD';
	const TXLMUSD = 'tXLMUSD';

	public static function isTrading(string $symbol): bool {
	    return $symbol !== '' && $symbol[0] === 't';
	}

}