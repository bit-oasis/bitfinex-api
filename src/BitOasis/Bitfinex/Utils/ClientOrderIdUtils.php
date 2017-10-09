<?php

namespace BitOasis\Bitfinex\Utils;

use Nette\Utils\DateTime;
use InvalidArgumentException;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class ClientOrderIdUtils {

	const CID_DATE_FORMAT = 'Y-m-d';

	private function __construct() {
	}

	/**
	 * @param $firstCid
	 * @param $firstCidDate
	 * @param $secondCid
	 * @param $secondCidDate
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public static function compareCids($firstCid, $firstCidDate, $secondCid, $secondCidDate): bool {
		$firstCid = (int)$firstCid;
		$secondCid = (int)$secondCid;
		return $firstCid === $secondCid && self::compareCidDates($firstCidDate, $secondCidDate);
	}

	/**
	 * @param $firstCidDate
	 * @param $secondCidDate
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public static function compareCidDates($firstCidDate, $secondCidDate): bool {
		$firstDate = self::getCidDate($firstCidDate);
		$secondDate = self::getCidDate($secondCidDate);
		return $firstDate === $secondDate;
	}

	/**
	 * @param \DateTime $date
	 * @return string
	 */
	public static function createCidDate(\DateTime $date): string {
		return $date->format(self::CID_DATE_FORMAT);
	}

	/**
	 * @param int $timestamp in milliseconds
	 * @return string
	 */
	public static function createCidDateFromTimestamp(int $timestamp): string {
		return DateTimeUtils::formatTimestamp(self::CID_DATE_FORMAT, $timestamp);
	}

	/**
	 * @param $date
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected static function getCidDate($date): string {
		if (is_numeric($date)) {
			$date = DateTimeUtils::getUnixTimestamp($date);
		}
		try {
			return self::createCidDate(DateTime::from($date));
		} catch (\Exception $e) {
			throw new InvalidArgumentException('Provided value is not valid cidDate!');
		}
	}

}
