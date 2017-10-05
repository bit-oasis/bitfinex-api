<?php

namespace BitOasis\Bitfinex\Http;

use Nette\Utils\Json;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class Response {

	/** @var string */
	protected $content;

	/** @var int */
	protected $code;

	public function __construct(string $content, int $code) {
		$this->content = $content;
		$this->code = $code;
	}

	/**
	 * @return string
	 */
	public function getContent(): string {
		return $this->content;
	}

	public function getArrayContent(): array {
	    return Json::decode($this->content, JSON_OBJECT_AS_ARRAY);
	}

	/**
	 * @return int
	 */
	public function getCode(): int {
		return $this->code;
	}

}