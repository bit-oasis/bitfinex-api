<?php

namespace BitOasis\Bitfinex\Http;

use React\Promise\Promise;

interface Client {

	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	public function query(string $uri, string $method, array $headers = [], string $body = null): Response;

	public function asyncQuery(string $uri, string $method, array $headers = [], string $body = null): Promise;

}