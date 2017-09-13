<?php

namespace BitOasis\Bitfinex\Websocket\Channel\OrderBook;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class OrderBookMessage {

	/** @var float */
	protected $price;

	/** @var int */
	protected $count;

	/** @var float */
	protected $amount;

	public function __construct(float $price, int $count, float $amount) {
		$this->price = $price;
		$this->count = $count;
		$this->amount = $amount;
	}

	public function getPrice(): float {
		return $this->price;
	}

	public function getCount(): int {
		return $this->count;
	}

	public function getAmount(): float {
		return $this->amount;
	}

	public function isBid(): bool {
		return $this->amount > 0;
	}

	public function isAsk(): bool {
	    return $this->amount < 0;
	}

	public function shouldBeRemoved(): bool {
	    return $this->count === 0;
	}

}