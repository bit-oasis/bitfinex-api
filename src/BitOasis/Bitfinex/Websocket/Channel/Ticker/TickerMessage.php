<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Ticker;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class TickerMessage {

	/** @var float */
	protected $bid;

	/** @var float */
	protected $ask;

	/** @var float */
	protected $dailyChange;

	/** @var float */
	protected $dailyPercentageChange;

	/** @var float */
	protected $lastPrice;

	/** @var float */
	protected $high;

	/** @var float */
	protected $low;

	public function __construct(float $bid, float $ask, float $dailyChange, float $dailyPercentageChange, float $lastPrice, float $high, float $low) {
		$this->bid = $bid;
		$this->ask = $ask;
		$this->dailyChange = $dailyChange;
		$this->dailyPercentageChange = $dailyPercentageChange;
		$this->lastPrice = $lastPrice;
		$this->high = $high;
		$this->low = $low;
	}


	public function getBid(): float {
		return $this->bid;
	}

	public function getAsk(): float {
		return $this->ask;
	}

	public function getDailyChange(): float {
		return $this->dailyChange;
	}

	public function getDailyPercentageChange(): float {
		return $this->dailyPercentageChange;
	}

	public function getLastPrice(): float {
		return $this->lastPrice;
	}

	public function getHigh(): float {
		return $this->high;
	}

	public function getLow(): float {
		return $this->low;
	}

}
