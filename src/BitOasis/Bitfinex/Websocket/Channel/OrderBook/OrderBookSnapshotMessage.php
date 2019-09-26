<?php

namespace BitOasis\Bitfinex\Websocket\Channel\OrderBook;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class OrderBookSnapshotMessage {

	/** @var OrderBookMessage[] */
	protected $orders;

	/**
	 * @param OrderBookMessage[] $orders
	 */
	public function __construct(array $orders) {
		$this->orders = $orders;
	}

	/**
	 * @return OrderBookMessage[]
	 */
	public function getOrders(): array {
		return $this->orders;
	}
}
