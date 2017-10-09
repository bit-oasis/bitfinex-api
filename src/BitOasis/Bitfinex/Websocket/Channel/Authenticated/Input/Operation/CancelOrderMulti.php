<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation;

use BitOasis\Bitfinex\Utils\ClientOrderIdUtils;
use BitOasis\Bitfinex\Exception\OperationFailedException;
use InvalidArgumentException;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class CancelOrderMulti implements Operation {

	/** Resolve operation only if all order notifications are received */
	const RESOLVE_TYPE_ALL = 'all';
	
	/** Resolve operation if any order notification is received */
	const RESOLVE_TYPE_ANY = 'any';

	/** @var int[] */
	protected $ids = [];

	/** @var array */
	protected $cIds = [];

	/** @var mixed */
	protected $resolveType;

	protected function __construct($resolveType) {
		$this->validateResolveType($resolveType);
		$this->resolveType = $resolveType;
	}

	/**
	 * @param array $ids
	 * @param $resolveType
	 * @return CancelOrderMulti
	 * @throws InvalidArgumentException
	 */
	public static function fromIds(array $ids, $resolveType = self::RESOLVE_TYPE_ALL): CancelOrderMulti {
		if (empty($ids)) {
			throw new InvalidArgumentException('Array cannot be empty!');
		}
	    $operation = new static($resolveType);
		foreach ($ids as $id) {
			if (!is_numeric($id)) {
				throw new InvalidArgumentException("'$id' is not valid order ID!");
			}
			$id = (int)$id;
			$operation->ids[$id] = $id;
		}
	    return $operation;
	}

	/**
	 * @param array $items array of arrays [0 => 'cid', 1 => 'cidDate']
	 * @param $resolveType
	 * @return CancelOrderMulti
	 * @throws InvalidArgumentException
	 */
	public static function fromCids(array $items, $resolveType = self::RESOLVE_TYPE_ALL): CancelOrderMulti {
		if (empty($items)) {
			throw new InvalidArgumentException('Array cannot be empty!');
		}
	    $operation = new static($resolveType);
		foreach ($items as $key => $item) {
			if (count($item) < 2) {
				throw new InvalidArgumentException("Missing 'cid' and/or 'cidDate' values in array on $key index!");
			}
			$cId = $item[0];
			$cIdDate = $item[1];
			if (!is_numeric($cId)) {
				throw new InvalidArgumentException("'{$cId}' is not valid cid on $key index!");
			}
			if (!$cIdDate instanceof \DateTime) {
				throw new InvalidArgumentException("'{$cIdDate}' is not valid cidDate (\DateTime) on $key index!");
			}
			$cId = (int)$cId;
			$operation->cIds[$cId] = [$cId, $cIdDate];
		}
	    return $operation;
	}

	public function getOperationCode(): string {
		return 'oc_multi';
	}

	public function getOperationData(): array {
		if (!empty($this->ids)) {
			$name = 'id';
			$mappedItems = array_values($this->id);
		} else {
			$name = 'cid';
			$mappedItems = array_map(function($item) {
				return [$item[0], ClientOrderIdUtils::createCidDate($item[1])];
			}, array_values($this->cIds));
		}
		
		return [$name => $mappedItems];
	}

	public function getOperationNotificationCode(): string {
		return 'oc_req';
	}

	public function isCompleting(array $data): bool {
		$isCompleting = false;
		if (isset($this->ids[$data[4][0]])) {
			unset($this->ids[$data[4][0]]);
			$isCompleting = true;
		} else if (isset($this->cIds[$data[4][2]]) && ClientOrderIdUtils::compareCidDates($data[4][4], $this->cIds[$data[4][2]][1])) {
			return true;
			//TODO: notifications for multi-cancel doesn't send CID right now
//			unset($this->cIds[$data[4][2]]);
//			$isCompleting = true;
		}
		
		//TODO: what to do if $data[6] !== 'SUCCESS'?
		return $isCompleting && ($this->resolveType === self::RESOLVE_TYPE_ALL ? $this->isEmpty() : true);
	}

	public function createResponse(array $data) {
		if ($this->resolveType === self::RESOLVE_TYPE_ALL && !empty($this->ids)) {//!$this->isEmpty()) {
			throw new OperationFailedException('Trying to create response when not all orders were resolved!');
		}
	}

	/**
	 * @param $type
	 * @throws InvalidArgumentException
	 */
	protected function validateResolveType($type) {
		if (!in_array($type, [self::RESOLVE_TYPE_ALL, self::RESOLVE_TYPE_ANY], true)) {
			throw new InvalidArgumentException("'$type' is not valid resolve type!");
		}
	}
	
	protected function isEmpty(): bool {
		return empty($this->ids) && empty($this->cIds);
	}

}
