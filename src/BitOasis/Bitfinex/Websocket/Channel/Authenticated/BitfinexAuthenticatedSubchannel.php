<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated;

use BitOasis\Bitfinex\Websocket\ConnectionWebsocketSubscriberAdapter;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class BitfinexAuthenticatedSubchannel extends ConnectionWebsocketSubscriberAdapter implements AuthenticatedSubchannel {

	/** @var bool */
	protected $authenticated = false;

	public function isAuthenticatedChannelRequired(): bool {
		return true;
	}

	public function onWebsocketAuthenticated() {
		$this->authenticated = true;
	}

	public function onWebsocketClosed() {
		$this->authenticated = false;
		parent::onWebsocketClosed();
	}

	public function onAuthChannelStarted() {
	}

	public function onAuthChannelStopped() {
	}

}
