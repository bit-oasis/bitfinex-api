<?php

namespace BitOasis\Bitfinex\Response;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class TradingSymbolTicker {

	/** @var string */
	protected $symbol;

	/** @var float */
	protected $bid;

	/** @var float */
	protected $bidSize;

	/** @var float */
	protected $ask;

	/** @var float */
	protected $askSize;

	/** @var float */
	protected $dailyChange;

	/** @var float */
	protected $dailyChangePerc;

	/** @var float */
	protected $lastPrice;

	/** @var float */
	protected $volume;

	/** @var float */
	protected $high;

	/** @var float */
	protected $low;

	/**
	 * Ticker constructor.
	 * @param string $symbol
	 * @param float $bid
	 * @param float $bidSize
	 * @param float $ask
	 * @param float $askSize
	 * @param float $dailyChange
	 * @param float $dailyChangePerc
	 * @param float $lastPrice
	 * @param float $volume
	 * @param float $high
	 * @param float $low
	 */
	public function __construct(string $symbol, float $bid, float $bidSize, float $ask, float $askSize, float $dailyChange, float $dailyChangePerc, float $lastPrice, float $volume, float $high, float $low) {
		$this->symbol = $symbol;
		$this->bid = $bid;
		$this->bidSize = $bidSize;
		$this->ask = $ask;
		$this->askSize = $askSize;
		$this->dailyChange = $dailyChange;
		$this->dailyChangePerc = $dailyChangePerc;
		$this->lastPrice = $lastPrice;
		$this->volume = $volume;
		$this->high = $high;
		$this->low = $low;
	}

	public static function fromRestApiResponse(array $params): TradingSymbolTicker {
	    return new static($params[0], $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10]);
	}

	/**
	 * @return string
	 */
	public function getSymbol(): string {
		return $this->symbol;
	}

	/**
	 * @return float
	 */
	public function getBid(): float {
		return $this->bid;
	}

	/**
	 * @return float
	 */
	public function getBidSize(): float {
		return $this->bidSize;
	}

	/**
	 * @return float
	 */
	public function getAsk(): float {
		return $this->ask;
	}

	/**
	 * @return float
	 */
	public function getAskSize(): float {
		return $this->askSize;
	}

	/**
	 * @return float
	 */
	public function getDailyChange(): float {
		return $this->dailyChange;
	}

	/**
	 * @return float
	 */
	public function getDailyChangePerc(): float {
		return $this->dailyChangePerc;
	}

	/**
	 * @return float
	 */
	public function getLastPrice(): float {
		return $this->lastPrice;
	}

	/**
	 * @return float
	 */
	public function getVolume(): float {
		return $this->volume;
	}

	/**
	 * @return float
	 */
	public function getHigh(): float {
		return $this->high;
	}

	/**
	 * @return float
	 */
	public function getLow(): float {
		return $this->low;
	}

}