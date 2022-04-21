<?php

namespace BitOasis\Bitfinex\Utils;

use BitOasis\Bitfinex\Constant\Symbol;
use InvalidArgumentException;

/**
 * @author David Fiedor <davefu@seznam.cz>
 * @deprecated
 */
class ConsoleUtils {
	
	const SHORTCUT = 'shortcut';
	const DESCRIPTION = 'description';
	const PAIR = 'pair';
	const SYMBOL = 'symbol';
	
	/** @var string[] */
	protected static $consoleOptions = [
		'btc' => [
			self::SHORTCUT => 'b',
			self::DESCRIPTION => 'BTC',
			self::PAIR => 'BTC-AED',
			self::SYMBOL => Symbol::TBTCUSD,
		],
		'eth' => [
			self::SHORTCUT => 'e',
			self::DESCRIPTION => 'ETH',
			self::PAIR => 'ETH-AED',
			self::SYMBOL => Symbol::TETHUSD,
		],
		'xrp' => [
			self::SHORTCUT => 'x',
			self::DESCRIPTION => 'XRP',
			self::PAIR => 'XRP-AED',
			self::SYMBOL => Symbol::TXRPUSD,
		],
	];

	private function __construct() {;
	}

	/**
	 * @return array [option => [shortcut, description, pair, symbol]]
	 */
	public function getFullConsoleOptions(): array {
		return static::$consoleOptions;
	}

	/**
	 * @return string[]
	 */
	public static function getConsoleOptions(): array {
		return array_keys(static::$consoleOptions);
	}

	/**
	 * @param string $option
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public static function getOptionShortcut(string $option): string {
		static::validateOption($option);
		return static::$consoleOptions[$option][self::SHORTCUT];
	}

	/**
	 * @param string $option
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public static function getOptionDescription(string $option): string {
		static::validateOption($option);
		return static::$consoleOptions[$option][self::DESCRIPTION];
	}

	/**
	 * @param string $option
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public static function getOptionPair(string $option): string {
		static::validateOption($option);
		return static::$consoleOptions[$option][self::PAIR];
	}

	/**
	 * @param string $option
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public static function getOptionSymbol(string $option): string {
		static::validateOption($option);
		return static::$consoleOptions[$option][self::SYMBOL];
	}

	/**
	 * @param string $option
	 * @throws InvalidArgumentException
	 */
	protected static function validateOption(string $option) {
		if (!isset(static::$consoleOptions[$option])) {
			throw new InvalidArgumentException("Option '$option' doesn't exist!");
		}
	}

}
