<?php

namespace BitOasis\Bitfinex\Websocket\Channel;

use BitOasis\Bitfinex\Constant\Symbol;
use Ratchet\Client\WebSocket;
use React\Promise\Promise;
use BitOasis\Bitfinex\Websocket\ConnectionWebsocketSubscriberAdapter;
use BitOasis\Bitfinex\Exception\InvalidSymbolException;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
abstract class BitfinexPublicChannel extends ConnectionWebsocketSubscriberAdapter {

	/** @var string */
	protected $symbol;

	/** @var int */
	protected $channelId;

	public function __construct(string $symbol) {
		$this->validateSymbol($symbol);
		$this->symbol = $symbol;
	}
	
	/**
	 * @param string $symbol
	 * @throws InvalidSymbolException
	 */
	protected function validateSymbol(string $symbol) {
		if (!Symbol::isTrading($symbol)) {
			throw new InvalidSymbolException('Only trading symbols are supported');
		}
	}

	public function onMaintenanceEnded(WebSocket $conn) {
		$this->unsubscribe($conn)->done(function() {
			$this->subscribe($this->connection);
		});
	}

	protected abstract function subscribe(WebSocket $conn): Promise;
	protected abstract function unsubscribe(WebSocket $conn): Promise;
}
