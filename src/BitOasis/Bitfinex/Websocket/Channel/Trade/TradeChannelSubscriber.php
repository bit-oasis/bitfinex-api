<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Trade;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface TradeChannelSubscriber {
	
	public function onTradeUpdateReceived(TradeMessage $message);

	public function onTradeStarted();

	public function onTradeStopped();

}
