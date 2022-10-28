<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Order;

use BitOasis\Bitfinex\Utils\DateTimeUtils;
use BitOasis\Bitfinex\Constant\OrderType;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class OrderMessage {

	/** @var array */
	const STOP_ORDER_TYPES = [OrderType::EXCHANGE_STOP_LIMIT, OrderType::EXCHANGE_STOP, OrderType::STOP_LIMIT, OrderType::STOP];

	/** @var int */
	protected $id;

	/** @var int|null */
	protected $gid;

	/** @var int */
	protected $cid;

	/** @var string */
	protected $symbol;

	/** @var int in milliseconds */
	protected $timestampCreated;

	/** @var int|null in milliseconds */
	protected $timestampUpdated;

	/** @var float */
	protected $remainingAmount;

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

	/** @var float|null */
	protected $trailingPrice;

	/** @var float|null */
	protected $auxLimitPrice;

	/** @var bool */
	protected $notify;

	/** @var bool */
	protected $hidden;

	/** @var int|null */
	protected $placedId;

	public function __construct(int $id, /*?int */$gid, int $cid, string $symbol, int $timestampCreated, /*?int */ $timestampUpdated, float $remainingAmount, float $originalAmount, string $type, /*?string */$previousType, string $orderStatus, float $price, float $avgPrice, /*?float */$trailingPrice, /*?float */$auxLimitPrice, bool $notify, bool $hidden, int $placedId = null) {
		$this->id = $id;
		$this->gid = $gid;
		$this->cid = $cid;
		$this->symbol = $symbol;
		$this->timestampCreated = $timestampCreated;
		$this->timestampUpdated = $timestampUpdated;
		$this->remainingAmount = $remainingAmount;
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

	/**
	 * @param array $data
	 * @return \static
	 * @link https://bitfinex.readme.io/v2/reference#ws-auth-orders
	 */
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
	 * @return int|null
	 */
	public function getGid()/*: ?int*/ {
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
	 * @return int in milliseconds
	 */
	public function getTimestampCreated(): int {
		return $this->timestampCreated;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateCreated(): \DateTime {
		return DateTimeUtils::createDateTimeFromTimestamp($this->timestampCreated);
	}

	/**
	 * @return int|null in milliseconds
	 */
	public function getTimestampUpdated() {
		return $this->timestampUpdated;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getDateUpdated() {
		return $this->timestampUpdated === null ? null : DateTimeUtils::createDateTimeFromTimestamp($this->timestampUpdated);
	}

	/**
	 * @return float
	 */
	public function getRemainingAmount(): float {
		return $this->remainingAmount;
	}

	/**
	 * @return float
	 */
	public function getRemainingAmountAbs(): float {
		return abs($this->remainingAmount);
	}

	/**
	 * @return float
	 */
	public function getOriginalAmount(): float {
		return $this->originalAmount;
	}

	/**
	 * @return float
	 */
	public function getOriginalAmountAbs(): float {
		return abs($this->originalAmount);
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
	 * @return float|null
	 */
	public function getTrailingPrice()/*: ?float*/ {
		return $this->trailingPrice;
	}

	/**
	 * @return float|null
	 */
	public function getAuxLimitPrice()/*: ?float*/ {
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
	 * @return int|null
	 */
	public function getPlacedId() {
		return $this->placedId;
	}

	/**
	 * @return bool
	 */
	public function isStatusActive(): bool {
		return 0 === strpos($this->orderStatus, 'ACTIVE');
	}

	/**
	 * @return bool
	 */
	public function isStatusCanceled(): bool {
		return 0 === strpos($this->orderStatus, 'CANCELED');
	}

	/**
	 * @return bool
	 */
	public function isStatusExecuted(): bool {
		return 0 === strpos($this->orderStatus, 'EXECUTED');
	}

	/**
	 * For multi-trade orders status will contain all the historical statuses, and will be
	 * truncated from the beginning, e.g.
	 * "(-41.37139577), PARTIALLY FILLED @ 12.58(-15.53), PARTIALLY FILLED @ 12.58(-41.34952975), PARTIALLY FILLED @ 12.58(-41.34340231)"
	 *
	 * The real status will be the last one (comma separated sections) => PARTIALLY FILLED @ 12.58(-41.34340231)
	 *
	 * In Case of partial order got Executed or Canceled
	 * We will receive status the actual status from beginning: e.g. CANCELED was:  12.58(-15.53), PARTIALLY FILLED @ 12.58(-41.34952975), PARTIALLY FILLED @ 12.58(-41.34340231), PARTIALLY FILLED @ 12.58(-15.71)
	 *
	 * @return bool
	 */
	public function isStatusPartiallyFilled(): bool {
		$status = $this->orderStatus;

		if ($this->remainingAmount === 0.0 || $this->remainingAmount === $this->originalAmount) {
			return false;
		}

		if (
			0 === strpos($status, 'ACTIVE') ||
			0 === strpos($status, 'CANCELED') ||
			0 === strpos($status, 'EXECUTED') ||
			0 === strpos($status, 'INSUFFICIENT MARGIN') ||
			0 === strpos($status, 'RSN_BOOK_SLIP')
		) {
			return false;
		}

		$sections = explode(', ', $status);

		return 0 === strpos(end($sections), 'PARTIALLY FILLED');
	}

	/**
	 * @return bool
	 */
	public function isStatusInsufficientMargin(): bool {
		return 0 === strpos($this->orderStatus, 'INSUFFICIENT MARGIN');
	}

	/**
	 * @return bool
	 */
	public function isStatusOrderBookSlip(): bool {
		return 0 === strpos($this->orderStatus, 'RSN_BOOK_SLIP');
	}

	/**
	 * @return bool
	 */
	public function isLive(): bool {
	    return $this->isStatusActive() || $this->isStatusPartiallyFilled();
	}

	/**
	 * @return bool
	 */
	public function isCanceled(): bool {
		return $this->isStatusCanceled() || $this->isStatusInsufficientMargin() || $this->isStatusOrderBookSlip();
	}

	/**
	 * @return bool
	 */
	public function isBuy(): bool {
		return $this->originalAmount > 0;
	}

	/**
	 * @return bool
	 */
	public function isSell(): bool {
		return $this->originalAmount < 0;
	}

	/**
	 * @return bool
	 */
	public function isStopPriceTriggered(): bool {
		$isActive = $this->isStatusActive();
		if (in_array($this->type, [OrderType::EXCHANGE_STOP, OrderType::STOP]) && !$this->isCanceled() && (!$isActive || $this->isStatusPartiallyFilled())) {
			return true;
		}
		return $this->previousType !== null && in_array($this->previousType, self::STOP_ORDER_TYPES) && $this->type !== $this->previousType;
	}

}
