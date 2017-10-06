<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input;

use BitOasis\Bitfinex\Exception\NotConnectedException;
use BitOasis\Bitfinex\Exception\OperationFailedException;
use BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation\Operation;
use BitOasis\Bitfinex\Websocket\Channel\Authenticated\BitfinexAuthenticatedSubchannel;
use Nette\Utils\Json;
use React\Promise\Promise;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class InputChannel extends BitfinexAuthenticatedSubchannel {

	protected $operationsByReplyCode = [];

	public function onWebsocketMessageReceived($data) {
		if ($data[0] === 0 && $data[1] === 'n' && isset($this->operationsByReplyCode[$data[2][1]])) {
			/** @var OperationAndDeferred $od */
			foreach ($this->operationsByReplyCode[$data[2][1]] as $key => $od) {
				if ($od->getOperation()->isCompleting($data[2])) {
					try {
						$reply = $od->getOperation()->createResponse($data[2]);
						$od->getDeferred()->resolve($reply);
					} catch (OperationFailedException $e) {
						$od->getDeferred()->reject($e->getMessage());
					}
					unset($this->operationsByReplyCode[$key]);
				}
			}
		}
	}

	public function process(Operation $operation): Promise {
		if (!$this->authenticated) {
			throw new NotConnectedException();
		}
		$json = Json::encode([
			0,
			$operation->getOperationCode(),
			null,
			$operation->getOperationData(),
		]);
		echo($json . "\n");
		$this->connection->send($json);

		if (!isset($this->operationsByReplyCode[$operation->getOperationNotificationCode()])) {
			$this->operationsByReplyCode[$operation->getOperationNotificationCode()] = [];
		}

		$od = new OperationAndDeferred($operation);
		$this->operationsByReplyCode[$operation->getOperationNotificationCode()][] = $od;
		return $od->promise();
	}

}