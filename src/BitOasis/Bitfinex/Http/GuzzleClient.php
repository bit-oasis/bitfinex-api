<?php

namespace BitOasis\Bitfinex\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Nette\NotImplementedException;
use React\Promise\Promise;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class GuzzleClient implements Client {

	/** @var ClientInterface */
	protected $guzzleClient;

	public function __construct(ClientInterface $guzzleClient) {
		$this->guzzleClient = $guzzleClient;
	}

	public function query(string $uri, string $method, array $headers = [], string $body = null): Response {
		$request = new Request($method, $uri, $headers, $body);
		$response = $this->guzzleClient->send($request);
		return new Response($response->getBody()->getContents(), $response->getStatusCode());
	}

	public function asyncQuery(string $uri, string $method, array $headers = [], string $body = null): Promise {
		throw new NotImplementedException();
	}

}