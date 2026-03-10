<?php

namespace BitOasis\Bitfinex\Websocket\Channel;

use BitOasis\Bitfinex\Constant\Symbol;
use BitOasis\Bitfinex\Exception\InvalidSymbolException;
use BitOasis\Bitfinex\Exception\SubscriptionFailedException;
use BitOasis\Bitfinex\Websocket\ConnectionWebsocketSubscriberAdapter;
use Ratchet\Client\WebSocket;
use React\Promise\Promise;

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

	/**
	 * @throws SubscriptionFailedException
	 * @throws InvalidSymbolException
	 */
	protected function throwCodeBasedException(int $errorCode, string $errorMessage, string $symbol) {
		$channelName = $this->getChannelName();
		switch ($errorCode) {
			case 10300: throw new InvalidSymbolException("Can't subscribe to $channelName channel for symbol $symbol. Symbol is invalid.");
			default: throw new SubscriptionFailedException("Can't subscribe to $channelName channel: $errorMessage ($errorCode)");
		}
	}

	public function onMaintenanceEnded(WebSocket $conn) {
		$this->unsubscribe($conn)->done(function() {
			$this->subscribe($this->connection);
		});
	}

	abstract protected function subscribe(WebSocket $conn): Promise;

	abstract protected function unsubscribe(WebSocket $conn): Promise;

	abstract protected function getChannelName(): string;

}
