<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation;

use BitOasis\Bitfinex\Exception\OperationFailedException;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class NewOrder implements Operation {

	/** @var int|null */
	protected $gid;

	/** @var int */
	protected $cid;

	/** @var string */
	protected $type;

	/** @var string */
	protected $symbol;

	/** @var float|string */
	protected $amount;

	/** @var float|null */
	protected $price;

	/** @var float|null */
	protected $priceTrailing;

	/** @var float|null */
	protected $priceAuxLimit;

	/** @var bool|null */
	protected $hidden;

	/** @var bool|null */
	protected $postonly;

	/** @var array|null */
	protected $meta;

	public function __construct(int $cid, string $type, string $symbol, float $amount, float $price = null, float $priceAuxLimit = null, array $meta = null) {
		$this->cid = $cid;
		$this->type = $type;
		$this->symbol = $symbol;
		$this->amount = $amount;
		$this->price = $price;
		$this->priceAuxLimit = $priceAuxLimit;
		$this->meta = $meta;
	}

	/**
	 * @param int|null $gid
	 */
	public function setGid(int $gid) {
		$this->gid = $gid;
	}

	/**
	 * @param float|null $priceTrailing
	 */
	public function setPriceTrailing(float $priceTrailing) {
		$this->priceTrailing = $priceTrailing;
	}

	/**
	 * @param float|null $priceAuxLimit
	 * @deprecated use constructor instead
	 */
	public function setPriceAuxLimit(float $priceAuxLimit) {
		$this->priceAuxLimit = $priceAuxLimit;
	}

	/**
	 * @param bool|null $hidden
	 */
	public function setHidden(bool $hidden = true) {
		$this->hidden = $hidden;
	}

	/**
	 * @param bool|null $postonly
	 */
	public function setPostonly(bool $postonly = true) {
		$this->postonly = $postonly;
	}


	public function getOperationCode(): string {
		return 'on';
	}

	public function getOperationData(): array {
		$data = [
			'cid' => $this->cid,
			'type' => $this->type,
			'symbol' => $this->symbol,
			'amount' => (string)$this->amount,
		];
		if ($this->gid !== null) {
			$data['gid'] = $this->gid;
		}
		if ($this->price !== null) {
			$data['price'] = (string)$this->price;
		}
		if ($this->priceTrailing !== null) {
			$data['price_trailing'] = $this->priceTrailing;
		}
		if ($this->priceAuxLimit !== null) {
			$data['price_aux_limit'] = (string)$this->priceAuxLimit;
		}
		if ($this->hidden !== null) {
			$data['hidden'] = $this->hidden ? 1 : 0;
		}
		if ($this->postonly !== null) {
			$data['postonly'] = $this->postonly ? 1 : 0;
		}
		if (!empty($this->meta)) {
			$data['meta'] = $this->meta;
		}
		return $data;
	}

	public function getOperationNotificationCode(): string {
		return 'on-req';
	}

	public function isCompleting(array $data): bool {
		return $data[4][2] === $this->cid;
	}

	public function createResponse(array $data): int {
		if ($data[6] === 'SUCCESS') {
			return $data[4][0];
		}
		throw new OperationFailedException($data[6] . ': ' . $data[7]);
	}

}