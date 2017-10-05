<?php

namespace BitOasis\Bitfinex\Rest;

use BitOasis\Bitfinex\Http\Client;
use BitOasis\Bitfinex\Response;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class Ticker {

	const URI_SUFFIX = '/tickers';
	const METHOD = Client::METHOD_GET;

	/** @var RestClient */
	protected $client;

	public function __construct(RestClient $client) {
		$this->client = $client;
	}

	public function getTicker(array $symbols): Response\Ticker {
		$response = $this->client->processRequest(self::URI_SUFFIX . '?symbols=' . implode(',', $symbols));
		return $this->createResponse($response->getArrayContent());
	}

	protected function createResponse(array $params): Response\Ticker {
	    return Response\Ticker::fromRestApiResponse($params);
	}

}