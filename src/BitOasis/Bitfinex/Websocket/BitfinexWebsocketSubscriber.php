<?php

namespace BitOasis\Bitfinex\Websocket;

use Ratchet\Client\WebSocket;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
interface BitfinexWebsocketSubscriber {

	public function isAuthenticatedChannelRequired(): bool;

	public function onWebsocketConnected(WebSocket $conn, $version);

	public function onWebsocketClosed();

	public function onWebsocketAuthenticated();

	public function onWebsocketChannelSubscribed($data);

	public function onWebsocketChannelUnsubscribed($data);

	public function onWebsocketMessageReceived($data);

	public function onWebsocketErrorMessage($data);

	public function onMaintenanceStarted();

	public function onMaintenanceEnded(WebSocket $conn);

}