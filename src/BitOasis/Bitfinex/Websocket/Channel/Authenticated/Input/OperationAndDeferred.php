<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input;

use BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation\Operation;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class OperationAndDeferred {

	/** @var Operation */
	protected $operation;

	/** @var Deferred */
	protected $deferred;

	public function __construct(Operation $operation) {
		$this->operation = $operation;
		$this->deferred = new Deferred();
	}

	public function getOperation(): Operation {
		return $this->operation;
	}

	public function getDeferred(): Deferred {
		return $this->deferred;
	}

	public function promise(): Promise {
	    return $this->deferred->promise();
	}

}