<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Notification;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
interface NotificationChannelSubscriber {

	public function onNotificationReceived(NotificationMessage $message);

	public function onNotificationStarted();

	public function onNotificationStopped();

}
