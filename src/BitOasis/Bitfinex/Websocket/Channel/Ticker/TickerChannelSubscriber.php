<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Ticker;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface TickerChannelSubscriber {

	public function onTickerUpdateReceived(TickerMessage $message);

	public function onTickerStarted();

	public function onTickerStopped();

}
