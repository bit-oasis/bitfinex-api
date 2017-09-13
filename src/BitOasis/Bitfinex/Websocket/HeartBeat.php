<?php

namespace BitOasis\Bitfinex\Websocket;

use BitOasis\Bitfinex\Exception\NoSuchChannelException;
use React\EventLoop\LoopInterface;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class HeartBeat {

	/** @var int seconds */
	protected $upToDateInterval = 7;

	/** @var callable */
	protected $failureCallback;

	/** @var callable */
	protected $resumedCallback;

	protected $channels = [];

	public function __construct(callable $failureCallback, callable $resumedCallback, LoopInterface $loop) {
		$this->failureCallback = $failureCallback;
		$this->resumedCallback = $resumedCallback;
		$loop->addPeriodicTimer(1, [$this, 'checkChannels']);
	}

	/**
	 * @param int $upToDateInterval
	 */
	public function setUpToDateInterval(int $upToDateInterval) {
		$this->upToDateInterval = $upToDateInterval;
	}

	public function addChannel(int $channelId) {
	    $this->channels[$channelId] = time();
	}

	public function removeChannel(int $channelId) {
	    if (isset($this->channels[$channelId])) {
	    	unset($this->channels[$channelId]);
	    }
	}

	/**
	 * @param int $channelId
	 * @return bool
	 * @throws NoSuchChannelException
	 */
	public function isUpToDate(int $channelId): bool {
		if (!array_key_exists($channelId, $this->channels)) {
			throw new NoSuchChannelException('No channel with ID ' . $channelId);
		}
		if ($this->channels[$channelId] === null) {
			return false;
		}
		return (time() - $this->channels[$channelId]) <= $this->upToDateInterval;
	}

	/**
	 * @param int $channelId
	 * @throws NoSuchChannelException
	 */
	public function heartBeat(int $channelId) {
		if (!array_key_exists($channelId, $this->channels)) {
			throw new NoSuchChannelException('No channel with ID ' . $channelId);
		}
		if ($this->channels[$channelId] === null) {
			call_user_func($this->resumedCallback, $channelId);
		}
		$this->channels[$channelId] = time();
	}

	public function checkChannels() {
	    foreach ($this->channels as $channelId => $time) {
	    	$now = time();
		    if ($time !== null && ($now - $time) > $this->upToDateInterval) {
		    	$this->channels[$channelId] = null;
		    	call_user_func($this->failureCallback, $channelId);
		    }
	    }
	}

	public function clear() {
	    $this->channels = [];
	}

}