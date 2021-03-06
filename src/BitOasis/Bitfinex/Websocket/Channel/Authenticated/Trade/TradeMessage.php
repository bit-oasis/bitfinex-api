<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Trade;

use BitOasis\Bitfinex\Utils\DateTimeUtils;


/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class TradeMessage {

	/** @var int */
	protected $id;

	/** @var string */
	protected $pair;

	/** @var int in milliseconds */
	protected $timestamp;

	/** @var int */
	protected $orderId;

	/** @var float */
	protected $execAmount;

	/** @var float */
	protected $execPrice;

	/** @var string|null */
	protected $orderType;

	/** @var float|null */
	protected $orderPrice;

	/** @var bool */
	protected $isMaker;

	/** @var float|null */
	protected $fee;

	/** @var string|null */
	protected $feeCurrency;

	public function __construct(int $id, string $pair, int $timestamp, int $orderId, float $execAmount, float $execPrice, string $orderType = null, float $orderPrice = null, bool $isMaker = null, float $fee = null, string $feeCurrency = null) {
		$this->id = $id;
		$this->pair = $pair;
		$this->timestamp = $timestamp;
		$this->orderId = $orderId;
		$this->execAmount = $execAmount;
		$this->execPrice = $execPrice;
		$this->orderType = $orderType;
		$this->orderPrice = $orderPrice;
		$this->isMaker = $isMaker;
		$this->fee = $fee;
		$this->feeCurrency = $feeCurrency;
	}

	/**
	 * @param array $data
	 * @return TradeMessage
	 * @link https://bitfinex.readme.io/v2/reference#ws-auth-trades
	 */
	public static function fromWebsocketData(array $data): TradeMessage {
		if (count($data) < 10) {
			//in case of te update
			$data[9] = null;
			$data[10] = null;
		}
		return new static(
			$data[0],
			$data[1],
			$data[2],
			$data[3],
			$data[4],
			$data[5],
			$data[6],
			$data[7],
			$data[8] === 1,
			$data[9],
			$data[10]
		);
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getPair(): string {
		return $this->pair;
	}

	/**
	 * @return int in milliseconds
	 */
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	/**
	 * @return int
	 */
	public function getOrderId(): int {
		return $this->orderId;
	}

	/**
	 * @return float
	 */
	public function getExecAmount(): float {
		return $this->execAmount;
	}

	/**
	 * @return float
	 */
	public function getExecPrice(): float {
		return $this->execPrice;
	}

	/**
	 * @return string|null
	 */
	public function getOrderType() {
		return $this->orderType;
	}

	/**
	 * @return float|null
	 */
	public function getOrderPrice() {
		return $this->orderPrice;
	}

	/**
	 * @return bool
	 */
	public function isMaker(): bool {
		return $this->isMaker;
	}

	/**
	 * @return float|null
	 */
	public function getFee() {
		return $this->fee;
	}

	/**
	 * @return string|null
	 */
	public function getFeeCurrency() {
		return $this->feeCurrency;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateTime(): \DateTime {
		return DateTimeUtils::createDateTimeFromTimestamp($this->timestamp);
	}

	/**
	 * @return bool
	 */
	public function isBuyOrder(): bool {
		return $this->execAmount > 0;
	}

	/**
	 * @return bool
	 */
	public function isSellOrder(): bool {
		return $this->execAmount < 0;
	}

	/**
	 * @return bool
	 */
	public function isBuy(): bool {
		return $this->execAmount > 0 XOR $this->isMaker;
	}

	/**
	 * @return bool
	 */
	public function isSell(): bool {
		return $this->execAmount < 0 XOR $this->isMaker;
	}

}
