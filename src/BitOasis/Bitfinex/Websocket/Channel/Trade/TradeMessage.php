<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Trade;

use BitOasis\Bitfinex\Utils\DateTimeUtils;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class TradeMessage {
	
	/** @var string */
	protected $id;
	
	/** @var int in milliseconds */
	protected $timestamp;
	
	/** @var float */
	protected $amount;
	
	/** @var float */
	protected $price;
	
	/**
	 * 
	 * @param mixed $id
	 * @param int $timestamp in milliseconds
	 * @param float $amount
	 * @param float $price
	 */
	public function __construct($id, int $timestamp, float $amount, float $price) {
		$this->id = $id;
		$this->timestamp = $timestamp;
		$this->amount = $amount;
		$this->price = $price;
	}

	public function getId(): string {
		return $this->id;
	}

	/**
	 * @return int in milliseconds
	 */
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateTime(): \DateTime {
		return DateTimeUtils::createDateTimeFromTimestamp($this->timestamp);
	}

	public function getAmount(): float {
		return $this->amount;
	}

	public function getPrice(): float {
		return $this->price;
	}

	public function isBuy(): bool {
		return $this->amount > 0;
	}

	public function isSell(): bool {
		return $this->amount < 0;
	}

}
