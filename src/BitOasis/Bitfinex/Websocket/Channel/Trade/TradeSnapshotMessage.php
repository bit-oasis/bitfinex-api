<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Trade;

/**
 * @author David Fiedor <davefu@seznam.cz>
 */
class TradeSnapshotMessage {

	/** @var TradeMessage[] */
	protected $trades;

	/**
	  @param TradeMessages[] $trades
	 */
	public function __construct(array $trades) {
		$this->trades = $trades;
	}

	/**
	 * @return TradeMessage[]
	 */
	public function getTrades(): array {
		return $this->trades;
	}

	/**
	 * @param bool $asc if TRUE sort ascendetically, descendetically otherwise
	 * @return TradeMessage[]
	 */
	public function getTradesOrderedByTimestamp(bool $asc = true): array {
		$trades = $this->trades;
		\usort($trades, function(TradeMessage $a, TradeMessage $b) use($asc) {
			if ($asc) {
				return $a->getTimestamp() <=> $b->getTimestamp();
			}
			return $b->getTimestamp() <=> $a->getTimestamp();
		});
		return $trades;
	}
}
