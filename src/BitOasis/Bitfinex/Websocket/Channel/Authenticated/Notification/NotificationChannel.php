<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Notification;

use BitOasis\Bitfinex\Websocket\Channel\Authenticated\ConnectionAuthenticatedSubchannelAdapter;


/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class NotificationChannel extends ConnectionAuthenticatedSubchannelAdapter {

	/** @var NotificationChannelSubscriber[] */
	protected $subscribers = [];

	/**
	 * @param NotificationChannelSubscriber $subscriber
	 */
	public function addNotificationChannelSubscriber(NotificationChannelSubscriber $subscriber) {
	    $this->subscribers[] = $subscriber;
	}

	public function onWebsocketMessageReceived($data) {
		if (count($data) > 2 && $data[0] === 0 && $data[1] === 'n') {
			$message = NotificationMessage::fromWebsocketData($data[2]);
			foreach ($this->subscribers as $subscriber) {
				$subscriber->onNotificationReceived($message);
			}
		}
	}

	public function onAuthChannelStarted() {
		foreach ($this->subscribers as $subscriber) {
			$subscriber->onNotificationStarted();
		}
	}

	public function onAuthChannelStopped() {
		foreach ($this->subscribers as $subscriber) {
			$subscriber->onNotificationStopped();
		}
	}

	public function __toString() {
		return 'notification authenticated channel';
	}

}
