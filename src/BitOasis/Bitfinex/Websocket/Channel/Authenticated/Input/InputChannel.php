<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input;

use BitOasis\Bitfinex\Exception\NotConnectedException;
use BitOasis\Bitfinex\Exception\OperationFailedException;
use BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation\Operation;
use BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation\MultiOperation;
use BitOasis\Bitfinex\Websocket\Channel\Authenticated\ConnectionAuthenticatedSubchannelAdapter;
use Nette\Utils\Json;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\Promise\Promise;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class InputChannel extends ConnectionAuthenticatedSubchannelAdapter implements LoggerAwareInterface {

	use LoggerAwareTrait;

	protected $operationsByReplyCode = [];

	protected $multiOperations = [];

	public function onWebsocketMessageReceived($data) {
		if ($data[0] === 0 && $data[1] === 'n') {
			if (isset($this->operationsByReplyCode[$data[2][1]])) {
				$this->resolveOperations($this->operationsByReplyCode[$data[2][1]], $data);
			}
			$this->resolveOperations($this->multiOperations, $data);
		}
	}

	public function process(Operation $operation): Promise {
		if (!$this->authenticated) {
			throw new NotConnectedException("Can't process order when channel is not authenticated!");
		}
		$json = Json::encode([
			0,
			$operation->getOperationCode(),
			null,
			$operation->getOperationData(),
		]);

		$this->logger->debug('New Bitfinex input operation: {operation}', ['operation' => $json]);

		$this->connection->send($json);

		$od = new OperationAndDeferred($operation);
		if ($operation instanceof MultiOperation) {
			$this->multiOperations[] = $od;
		}
		$operationNotificationCode = $operation->getOperationNotificationCode();
		if (!empty($operationNotificationCode)) {
			if (!isset($this->operationsByReplyCode[$operationNotificationCode])) {
				$this->operationsByReplyCode[$operationNotificationCode] = [];
			}

			$this->operationsByReplyCode[$operationNotificationCode][] = $od;
		}
		return $od->promise();
	}

	public function __toString() {
		return 'input channel';
	}

	/**
	 * @param OperationAndDeferred[] $operations
	 * @param array $data
	 */
	protected function resolveOperations(array &$operations, array $data) {
		foreach ($operations as $key => $od) {
			if ($od->getOperation()->isCompleting($data[2])) {
				try {
					$reply = $od->getOperation()->createResponse($data[2]);
					$od->getDeferred()->resolve($reply);
				} catch (OperationFailedException $e) {
					$od->getDeferred()->reject($e);
				}
				unset($operations[$key]);
			}
		}
	}

}