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
	const TXRPUSD = 'tXRPUSD';
	const TLTCUSD = 'tLTCUSD';
	const TLTCBTC = 'tLTCBTC';
	const TZECUSD = 'tZECUSD';
	const TZECBTC = 'tZECBTC';
	const TXMRUSD = 'tXMRUSD';
	const TXLMUSD = 'tXLMUSD';
	const TBCHUSD = 'tBABUSD';
	const TBCHBTC = 'tBABBTC';
	const TBCHETH = 'tBABETH';
	const TBSVUSD = 'tBSVUSD';
	const TBSVBTC = 'tBSVBTC';

	public static function isTrading(string $symbol): bool {
	    return $symbol !== '' && $symbol[0] === 't';
	}

}