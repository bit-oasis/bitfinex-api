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
	const TBCHUSD = 'tBCHN:USD';
	const TBCHBTC = 'tBCHN:BTC';
	const TBCHETH = 'tBCHN:ETH';
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
	const TDAIUSD = 'tDAIUSD';
	const TDAIBTC = 'tDAIBTC';
	const TMKRUSD = 'tMKRUSD';
	const TMKRBTC = 'tMKRBTC';
	const TREPUSD = 'tREPUSD';
	const TREPBTC = 'tREPBTC';
	const TKNCUSD = 'tKNCUSD';
	const TKNCBTC = 'tKNCBTC';
	const TUNIUSD = 'tUNIUSD';
	const TUNIUST = 'tUNIUST';
	const TYFIUSD = 'tYFIUSD';
	const TBALUSD = 'tBALUSD';
	const TCOMPUSD = 'tCOMP:USD';
	const TSNXUSD = 'tSNXUSD';
	const TDOGEUSD = 'tDOGE:USD';
	const TDOGEUST = 'tDOGE:UST';
	const TAAVEUSD = 'tAAVE:USD';
	const TAAVEUST = 'tAAVE:UST';
	const TBNTUSD = 'tBNTUSD';
	const TENJUSD = 'tENJUSD';
	const TLRCUSD = 'tLRCUSD';
	const TMNAUSD = 'tMNAUSD';
	const TMATICUSD = 'tMATIC:USD';
	const TMATICUST = 'tMATIC:UST';
	const TSTJUSD = 'tSTJUSD';
	const TSUSHIUSD = 'tSUSHI:USD';
	const TSUSHIUST = 'tSUSHI:UST';
	const TUDCUSD = 'tUDCUSD';
	const TWAVESUSD = 'tWAVES:USD';
	const TWAVESUST = 'tWAVES:UST';
	const TSOLUSD = 'tSOLUSD';
	const TSOLUST = 'tSOLUST';
	const TADAUSD = 'tADAUSD';
	const TADAUST = 'tADAUST';
	const TDOTUSD = 'tDOTUSD';
	const TDOTUST = 'tDOTUST';
	const TSHIBUSD = 'tSHIB:USD';
	const TSHIBUST = 'tSHIB:UST';
	const TAVAXUSD = 'tAVAX:USD';
	const TAVAXUST = 'tAVAX:UST';
	const TLUNAUSD = 'tLUNA:USD';
	const TLUNAUST = 'tLUNA:UST';
	const TNEARUSD = 'tNEAR:USD';
	const TNEARUST = 'tNEAR:UST';
	const TFTMUSD = 'tFTMUSD';
	const TFTMUST = 'tFTMUST';
	const TWBTUSD = 'tWBTUSD';
	const TATOUSD = 'tATOUSD';
	const TATOUST = 'tATOUST';

	public static function isTrading(string $symbol): bool {
	    return $symbol !== '' && $symbol[0] === 't';
	}

}
