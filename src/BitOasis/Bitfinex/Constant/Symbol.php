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
	const TEOSUSD = 'tEOSUSD';
	const TEOSBTC = 'tEOSBTC';
	const TEOSETH = 'tEOSETH';
	const TOMGUSD = 'tOMGUSD';
	const TOMGBTC = 'tOMGBTC';
	const TZRXUSD = 'tZRXUSD';
	const TZRXBTC = 'tZRXBTC';
	const TBATUSD = 'tBATUSD';
	const TBATBTC = 'tBATBTC';
	const TALGUSD = 'tALGUSD';
	const TALGBTC = 'tALGBTC';
	const TUSTUSD = 'tUSTUSD';
	const TBTCUST = 'tBTCUST';
	const TETHUST = 'tETHUST';
	const TNEOUSD = 'tNEOUSD';
	const TNEOBTC = 'tNEOBTC';
	const TXTZUSD = 'tXTZUSD';
	const TXTZBTC = 'tXTZBTC';

	public static function isTrading(string $symbol): bool {
	    return $symbol !== '' && $symbol[0] === 't';
	}

}
