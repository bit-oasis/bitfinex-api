<?php

namespace BitOasis\Bitfinex\Websocket\Channel\Authenticated\Notification;

use BitOasis\Bitfinex\Utils\DateTimeUtils;


/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class NotificationMessage {

	/** @var int in milliseconds */
	protected $timestamp;

	/** @var string */
	protected $type;

	/** @var int|null */
	protected $messageId;

	/** @var array|null */
	protected $info;

	/** @var int|null */
	protected $code;

	/** @var string */
	protected $status;

	/** @var string */
	protected $text;

	public function __construct(int $timestamp, string $type, string $status, string $text, int $messageId = null, array $info = null, int $code = null) {
		$this->timestamp = $timestamp;
		$this->type = $type;
		$this->messageId = $messageId;
		$this->info = $info;
		$this->code = $code;
		$this->status = $status;
		$this->text = $text;
	}

	/**
	 * @param array $data
	 * @return NotificationMessage
	 * @link https://bitfinex.readme.io/v2/reference#ws-auth-notifications
	 */
	public static function fromWebsocketData(array $data): NotificationMessage {
		return new static(
			$data[0],
			$data[1],
			$data[6],
			$data[7],
			$data[2],
			$data[4],
			$data[5]
		);
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	public function isType(string $type): bool {
	    return $this->type === $type;
	}

	/**
	 * @return int|null
	 */
	public function getMessageId() {
		return $this->messageId;
	}

	/**
	 * @return array|null
	 */
	public function getInfo() {
		return $this->info;
	}

	/**
	 * @return int|null
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @return string
	 */
	public function getStatus(): string {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function getText(): string {
		return $this->text;
	}

	/**
	 * @return int
	 */
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateTime(): \DateTime {
		return DateTimeUtils::createDateTimeFromTimestamp($this->timestamp);
	}

	public function __toString() {
		return $this->type . ' - ' . $this->status . ' - ' . $this->getDateTime()->format('Y-m-d H:i:s') . ' - ' . $this->text;
	}

}
