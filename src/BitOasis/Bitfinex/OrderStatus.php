<?php

namespace BitOasis\Bitfinex;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class OrderStatus {

	/** @var int */
	protected $id;

	/** @var int */
	protected $gid;

	/** @var int */
	protected $cid;

	/** @var string */
	protected $symbol;

	/** @var \DateTime */
	protected $dateCreated;

	/** @var \DateTime */
	protected $dateUpdated;

	/** @var float */
	protected $amount;

	/** @var float */
	protected $originalAmount;

	/** @var string */
	protected $type;

	/** @var string */
	protected $previousType;

	/** @var string */
	protected $orderStatus;

	/** @var float */
	protected $price;

	/** @var float */
	protected $avgPrice;

	/** @var float */
	protected $trailingPrice;

	/** @var float */
	protected $auxLimitPrice;

	/** @var bool */
	protected $notify;

	/** @var bool */
	protected $hidden;

	/** @var int */
	protected $placedId;

	public function __construct(int $id, int $gid, int $cid, string $symbol, \DateTime $dateCreated, \DateTime $dateUpdated, float $amount, float $originalAmount, string $type, string $previousType, string $orderStatus, float $price, float $avgPrice, float $trailingPrice, float $auxLimitPrice, bool $notify, bool $hidden, int $placedId) {
		$this->id = $id;
		$this->gid = $gid;
		$this->cid = $cid;
		$this->symbol = $symbol;
		$this->dateCreated = $dateCreated;
		$this->dateUpdated = $dateUpdated;
		$this->amount = $amount;
		$this->originalAmount = $originalAmount;
		$this->type = $type;
		$this->previousType = $previousType;
		$this->orderStatus = $orderStatus;
		$this->price = $price;
		$this->avgPrice = $avgPrice;
		$this->trailingPrice = $trailingPrice;
		$this->auxLimitPrice = $auxLimitPrice;
		$this->notify = $notify;
		$this->hidden = $hidden;
		$this->placedId = $placedId;
	}


	public static function fromWebsocketData(array $data) {
		return new static(
			$data[0],
			$data[1],
			$data[2],
			$data[3],
			$data[4],
			$data[5],
			$data[6],
			$data[7],
			$data[8],
			$data[9],
			$data[13],
			$data[16],
			$data[17],
			$data[18],
			$data[19],
			$data[23],
			$data[24],
			$data[25]
		);
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getGid(): int {
		return $this->gid;
	}

	/**
	 * @return int
	 */
	public function getCid(): int {
		return $this->cid;
	}

	/**
	 * @return string
	 */
	public function getSymbol(): string {
		return $this->symbol;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateCreated(): \DateTime {
		return $this->dateCreated;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateUpdated(): \DateTime {
		return $this->dateUpdated;
	}

	/**
	 * @return float
	 */
	public function getAmount(): float {
		return $this->amount;
	}

	/**
	 * @return float
	 */
	public function getOriginalAmount(): float {
		return $this->originalAmount;
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getPreviousType(): string {
		return $this->previousType;
	}

	/**
	 * @return string
	 */
	public function getOrderStatus(): string {
		return $this->orderStatus;
	}

	/**
	 * @return float
	 */
	public function getPrice(): float {
		return $this->price;
	}

	/**
	 * @return float
	 */
	public function getAvgPrice(): float {
		return $this->avgPrice;
	}

	/**
	 * @return float
	 */
	public function getTrailingPrice(): float {
		return $this->trailingPrice;
	}

	/**
	 * @return float
	 */
	public function getAuxLimitPrice(): float {
		return $this->auxLimitPrice;
	}

	/**
	 * @return bool
	 */
	public function isNotify(): bool {
		return $this->notify;
	}

	/**
	 * @return bool
	 */
	public function isHidden(): bool {
		return $this->hidden;
	}

	/**
	 * @return int
	 */
	public function getPlacedId(): int {
		return $this->placedId;
	}

}