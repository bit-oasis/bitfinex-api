<?php

namespace BitOasis\Bitfinex\Websocket\Channel\OrderBook;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
interface OrderBookChannelSubscriber {

	public function onOrderBookUpdateReceived(OrderBookMessage $message);

	public function onOrderBookSnapshotReceived(OrderBookSnapshotMessage $message);

	public function onOrderBookStarted();

	public function onOrderBookStopped();

	public function clearOrderBook();

}