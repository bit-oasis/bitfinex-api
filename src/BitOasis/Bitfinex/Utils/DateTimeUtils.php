<?php

namespace BitOasis\Bitfinex\Utils;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class DateTimeUtils {

	private function __construct() {
	}

	/**
	 * @param int $timestamp in milliseconds
	 * @return \DateTime
	 */
	public static function createDateTimeFromTimestamp(int $timestamp): \DateTime {
		$unix = $timestamp / 1000;
		return new \DateTime('@' . $unix);
	}

}
