<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Trade;

use BitOasis\Bitfinex\Websocket\Channel\Authenticated\ConnectionAuthenticatedSubchannelAdapter;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class TradeChannel extends ConnectionAuthenticatedSubchannelAdapter {

	/** @var TradeChannelSubscriber[] */
	protected $subscribers = [];

	/**
	 * @param TradeChannelSubscriber $subscriber
	 */
	public function addTradeChannelSubscriber(TradeChannelSubscriber $subscriber) {
	    $this->subscribers[] = $subscriber;
	}

	public function onWebsocketMessageReceived($data) {
		if (count($data) > 2 && $data[0] === 0 && $data[1] === 'tu') {
			$update = $data[2];
			if (is_array($update) && count($update) > 0 && !is_array($update[0])) {
				$message = TradeMessage::fromWebsocketData($update);
				foreach ($this->subscribers as $subscriber) {
					$subscriber->onTradeUpdateReceived($message);
				}
			}
		}
	}

	public function onAuthChannelStarted() {
		foreach ($this->subscribers as $subscriber) {
			$subscriber->onTradeStarted();
		}
	}

	public function onAuthChannelStopped() {
		foreach ($this->subscribers as $subscriber) {
			$subscriber->onTradeStopped();
		}
	}

}
