<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Input\Operation;

use BitOasis\Bitfinex\Exception\OperationFailedException;

interface Operation {

	public function getOperationCode(): string;

	public function getOperationData(): array;

	public function getOperationNotificationCode(): string;

	public function isCompleting(array $data): bool;

	/**
	 * @param array $data
	 * @return mixed
	 * @throws OperationFailedException
	 */
	public function createResponse(array $data);

}