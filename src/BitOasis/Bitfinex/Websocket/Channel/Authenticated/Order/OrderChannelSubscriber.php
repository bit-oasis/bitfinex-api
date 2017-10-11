<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Order;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface OrderChannelSubscriber {

	public function onOrderUpdateReceived(OrderMessage $message);

	public function onOrderStarted();

	public function onOrderStopped();

}
