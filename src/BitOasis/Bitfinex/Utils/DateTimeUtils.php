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
		return new \DateTime('@' . self::getUnixTimestamp($timestamp));
	}

	/**
	 * @param string $format \DateTime format string
	 * @param int $timestamp in milliseconds
	 * @return string
	 */
	public static function formatTimestamp(string $format, int $timestamp): string {
		return date($format, self::getUnixTimestamp($timestamp));
	}

	/**
	 * @param int $timestamp in milliseconds
	 * @return int
	 */
	public static function getUnixTimestamp(int $timestamp): int {
		return (int)($timestamp / 1000);
	}

}
