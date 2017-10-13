<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Order;

use BitOasis\Bitfinex\Websocket\Channel\Authenticated\ConnectionAuthenticatedSubchannelAdapter;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class OrderChannel extends ConnectionAuthenticatedSubchannelAdapter {

	/** @var OrderChannelSubscriber[] */
	protected $subscribers = [];

	/**
	 * @param OrderChannelSubscriber $subscriber
	 */
	public function addOrderChannelSubscriber(OrderChannelSubscriber $subscriber) {
	    $this->subscribers[] = $subscriber;
	}

	public function onWebsocketMessageReceived($data) {
		if (count($data) > 2 && $data[0] === 0) {
			if ($data[1] === 'os') {
				foreach ($data[2] as $item) {
					$this->fireOnUpdateReceived(OrderMessage::fromWebsocketData($item));
				}
			} else if(in_array($data[1], ['on', 'ou', 'oc'], true)) {
				$this->fireOnUpdateReceived(OrderMessage::fromWebsocketData($data[2]));
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
