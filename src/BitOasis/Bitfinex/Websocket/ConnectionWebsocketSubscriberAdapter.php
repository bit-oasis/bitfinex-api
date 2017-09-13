<?php

namespace BitOasis\Bitfinex\Websocket;

use Ratchet\Client\WebSocket;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class ConnectionWebsocketSubscriberAdapter implements BitfinexWebsocketSubscriber {

	/** @var WebSocket */
	protected $connection;

	public function isAuthenticatedChannelRequired() {
		return false;
	}

	public function onWebsocketConnected(WebSocket $conn, $version) {
		$this->connection = $conn;
	}

	public function onWebsocketClosed() {
		$this->connection = null;
	}

	public function onWebsocketAuthenticated() {
	}

	public function onWebsocketChannelSubscribed($data) {
	}

	public function onWebsocketChannelUnsubscribed($data) {
	}

	public function onWebsocketMessageReceived($data) {
	}

	public function onWebsocketErrorMessage($data) {
	}

	protected function isWebsocketConnected() {
	    return $this->connection !== null;
	}

	public function onMaintenanceStarted() {
	}

	public function onMaintenanceEnded(WebSocket $conn) {
	}

}