<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation;

use InvalidArgumentException;
use BitOasis\Bitfinex\Exception\OperationFailedException;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class OrderMulti implements MultiOperation {

	/** @var Operation[][] */
	protected $operationsByReplyCode = [];

	/** @var int */
	protected $operationsCount;

	/** @var int */
	protected $resolvedCount = 0;

	/** @var array */
	protected $results = [];

	/** @var \Exception[] */
	protected $errors = [];

	/** @var mixed */
	protected $resolveType;

	/**
	 * @param Operation[] $operations
	 * @param $resolveType
	 * @throws InvalidArgumentException
	 */
	protected function __construct(array $operations, $resolveType = self::RESOLVE_TYPE_ALL) {
		$this->validateResolveType($resolveType);
		$this->resolveType = $resolveType;
		if(empty($operations)) {
			throw new InvalidArgumentException('Array cannot be empty!');
		}
		foreach ($operations as $operation) {
			if (!$operation instanceof Operation) {
				throw new InvalidArgumentException('Provided operation is not implementing Operation interface!');
			}
			if ($operation instanceof self) {
				throw new InvalidArgumentException('Provided operation cannot be OrderMulti type!');
			}
			
			$code = $operation->getOperationNotificationCode();
			if (!isset($this->operationsByReplyCode[$code])) {
				$this->operationsByReplyCode[$code] = [];
			}
			$this->operationsByReplyCode[$code][] = $operation;
		}
		$this->operationsCount = count($operations);
	}

	/**
	 * Operation will be resolved only when all operations are resolved, otherwise it will be rejected
	 * @param Operation[] $operations
	 * @return \static
	 * @throws InvalidArgumentException
	 */
	public static function resolveAll(array $operations) {
		return new static($operations,  self::RESOLVE_TYPE_ALL);
	}

	/**
	 * Operation will be resolved if at least one operation is resolved
	 * @param Operation[] $operations
	 * @return \static
	 * @throws InvalidArgumentException
	 */
	public static function resolveAny(array $operations) {
		return new static($operations,  self::RESOLVE_TYPE_ANY);
	}

	public function getOperationCode(): string {
		return 'ox_multi';
	}

	public function getOperationData(): array {
		$data = [];
		foreach ($this->operationsByReplyCode as $code => $operations) {
			foreach ($operations as $operation) {
				$data[] = [$code, $operation->getOperationData()];
			}
		}
		return $data;
	}

	public function getOperationNotificationCode(): string {
		return 'ox_multi-req';
	}

	public function isCompleting(array $data): bool {
		if ($this->hasMainErrorOccurred($data)) {
			return true;
		}
		$code = $data[4][2];
		if (!isset($this->operationsByReplyCode[$code])) {
			return false;
		}
		
		foreach ($this->operationsByReplyCode[$code] as $operation) {
			if ($operation->isCompleting($data)) {
				$this->resolvedCount++;
				try {
					$this->results[] = $operation->createResponse($data);
				} catch (OperationFailedException $e) {
					$this->errors[] = $e;
					if ($this->resolveType === self::RESOLVE_TYPE_ALL) {
						return true;
					}
				}
			}
		}		
		
		return $this->isCompleted();
	}

	public function createResponse(array $data) {
		if ($this->hasMainErrorOccurred($data)) {
			throw new OperationFailedException($data[6] . ': ' . $data[7]);
		}
		if (!$this->isCompleted()) {
			throw new OperationFailedException('Trying to create response when not all operations were resolved!');
		}
		if ($this->resolveType === self::RESOLVE_TYPE_ALL) {
			if (!empty($this->errors)) {
				throw reset($this->errors);
			}
			return $this->results;
		}
		if (!empty($this->results)) {
			return reset($this->results);
		}
		throw reset($this->errors);
	}

	protected function hasMainErrorOccurred(array $data) {
		return $data[1] === $this->getOperationNotificationCode() && $data[6] === 'ERROR';
	}

	protected function isCompleted(): bool {
		return $this->resolvedCount === $this->operationsCount || ($this->resolveType === self::RESOLVE_TYPE_ANY && count($this->results) > 0);
	}

	/**
	 * @param $resolveType
	 * @throws InvalidArgumentException
	 */
	protected function validateResolveType($resolveType) {
		if (!in_array($resolveType, [self::RESOLVE_TYPE_ALL, self::RESOLVE_TYPE_ANY], true)) {
			throw new InvalidArgumentException("'$resolveType' is not valid resolve type!");
		}
	}

}
