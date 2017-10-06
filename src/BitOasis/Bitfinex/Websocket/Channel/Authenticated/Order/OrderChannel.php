<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Order;

use BitOasis\Bitfinex\Websocket\Channel\Authenticated\BitfinexAuthenticatedSubchannel;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class OrderChannel extends BitfinexAuthenticatedSubchannel {

	/** @var OrderChannelSubscriber[] */
	protected $subscribers = [];

	/**
	 * @param OrderChannelSubscriber $subscriber
	 */
	public function addOrderChannelSubscriber(OrderChannelSubscriber $subscriber) {
	    $this->subscribers[] = $subscriber;
	}

	public function onWebsocketMessageReceived($data) {
		if (count($data) > 2 && $data[0] === 0 && in_array($data[1], ['os', 'on', 'ou', 'oc'], true)) {
			$update = $data[2];
			if ($data[1] === 'os') {
				foreach ($update as $item) {
					$this->fireOnUpdateReceived(OrderMessage::fromWebsocketData($item));
				}
			} else {
				$this->fireOnUpdateReceived(OrderMessage::fromWebsocketData($update));
			}
		}
	}

	public function onAuthChannelStarted() {
		foreach ($this->subscribers as $subscriber) {
			$subscriber->onOrderStarted();
		}
	}

	public function onAuthChannelStopped() {
		foreach ($this->subscribers as $subscriber) {
			$subscriber->onOrderStopped();
		}
	}

	protected function fireOnUpdateReceived(OrderMessage $message) {
		foreach ($this->subscribers as $subscriber) {
			$subscriber->onOrderUpdateReceived($message);
		}
	}

}
