<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Input\Operation;

use BitOasis\Bitfinex\Exception\OperationFailedException;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class CancelOrder implements Operation {

	/** @var int */
	protected $id;

	/** @var int */
	protected $cid;

	/** @var \DateTime */
	protected $cidDate;

	protected function __construct() {
	}

	public static function fromId($id) {
	    $operation = new static();
	    $operation->id = $id;
	    return $operation;
	}

	public static function fromCid($cid, \DateTime $cidDate) {
	    $operation = new static();
	    $operation->cid = $cid;
	    $operation->cidDate = $cidDate;
	    return $operation;
	}


	public function getOperationCode(): string {
		return 'oc';
	}

	public function getOperationData(): array {
		if ($this->id !== null) {
			return ['id' => $this->id];
		}
		return ['cid' => $this->cid, 'cid_date' => $this->cidDate->format('Y-m-d')];
	}

	public function getOperationNotificationCode(): string {
		return 'oc-req';
	}

	public function isCompleting(array $data): bool {
		if ($this->id !== null) {
			return $data[4][0] === $this->id;
		}
		return $data[4][2] === $this->cid; // todo: cid date
	}

	public function createResponse(array $data) {
		if ($data[6] === 'SUCCESS') {
			return;
		}
		throw new OperationFailedException($data[6] . ': ' . $data[7]);
	}

}