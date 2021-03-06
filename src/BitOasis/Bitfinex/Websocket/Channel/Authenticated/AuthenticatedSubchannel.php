<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated;

use BitOasis\Bitfinex\Websocket\BitfinexWebsocketSubscriber;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface AuthenticatedSubchannel extends BitfinexWebsocketSubscriber {

	public function onAuthChannelStarted();

	public function onAuthChannelStopped();

}
