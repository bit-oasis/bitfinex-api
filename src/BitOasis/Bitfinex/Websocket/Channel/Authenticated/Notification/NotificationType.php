<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Notification;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class NotificationType {

	const ON_REQ = 'on-req';
	const OC_REQ = 'oc-req';
	const UCA = 'uca';
	const FON_REQ = 'fon-req';
	const FOC_REQ = 'foc-req';
	const DEPOSIT_NEW = 'deposit_new';
	const DEPOSIT_COMPLETE = 'deposit_complete';

}