<?php

namespace BitOasis\Bitfinex\Response;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class Ticker {

	/** @var TradingSymbolTicker[] */
	protected $tickerSymbols = [];

	/**
	 * Ticker constructor.
	 * @param TradingSymbolTicker[] $tickerSymbols
	 */
	public function __construct(array $tickerSymbols) {
		foreach ($tickerSymbols as $tickerSymbol) {
			$this->tickerSymbols[$tickerSymbol->getSymbol()] = $tickerSymbol;
		}
	}

	/**
	 * @return TradingSymbolTicker[]
	 */
	public function getTickerSymbols(): array {
		return $this->tickerSymbols;
	}

	/**
	 * @param string $symbol
	 * @return TradingSymbolTicker|null
	 */
	public function getTickerForSymbol(string $symbol): TradingSymbolTicker {
	    return isset($this->tickerSymbols[$symbol]) ? $this->tickerSymbols[$symbol] : null;
	}

	public static function fromRestApiResponse(array $params): Ticker {
		return new static(array_map(function(array $ticker) {
			return TradingSymbolTicker::fromRestApiResponse($ticker);
		}, $params));
	}

}