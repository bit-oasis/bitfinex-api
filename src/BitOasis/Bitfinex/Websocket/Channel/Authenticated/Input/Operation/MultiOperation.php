<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Input\Operation;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
interface MultiOperation extends Operation {

	/** Resolve operation only if all order notifications are received */
	const RESOLVE_TYPE_ALL = 'all';

	/** Resolve operation if any order notification is received */
	const RESOLVE_TYPE_ANY = 'any';

}
