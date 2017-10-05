<?php

namespace BitOasis\Bitfinex\Rest;

use BitOasis\Bitfinex\Http\Client;
use BitOasis\Bitfinex\Http\Response;
use Nette\Utils\Json;
use React\Promise\Promise;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class RestClient {

	/** @var Client */
	protected $httpClient;

	/** @var string */
	protected $key;

	/** @var string */
	protected $secret;

	/**
	 * RestClient constructor.
	 * @param Client $httpClient
	 * @param string $key
	 * @param string $secret
	 */
	public function __construct(string $key, string $secret, Client $httpClient) {
		$this->httpClient = $httpClient;
		$this->key = $key;
		$this->secret = $secret;
	}

	public function processRequest(string $suffix, array $params = [], $method = Client::METHOD_POST): Response {
		list($headers, $content) = $this->getHeadersAndContent($suffix, $params);
		return $this->httpClient->query($this->getUrl($suffix), $method, $headers, $content);
	}

	public function processAsyncRequest(string $suffix, array $params = [], $method = Client::METHOD_POST): Promise {
		list($headers, $content) = $this->getHeadersAndContent($suffix, $params);
		return $this->httpClient->asyncQuery($this->getUrl($suffix), $method, $headers, $content);
	}

	protected function getHeadersAndContent(string $suffix, array $params = []) {
		$params['nonce'] = $this->getNonce();
		$params['request'] = $this->getPath($suffix);

		$payload = base64_encode(Json::encode($params));

		$headers = [
			'X-BFX-APIKEY' => $this->key,
			'X-BFX-PAYLOAD' => $payload,
			'X-BFX-SIGNATURE' => $this->getSignature($payload),
		];
		return [$headers, $payload];
	}

	protected function getUrl($suffix) {
		return "https://api.bitfinex.com" . $this->getPath($suffix);
	}

	protected function getPath($suffix) {
		return "/v2$suffix";
	}

	/**
	 * @param $payload
	 * @return string
	 */
	protected function getSignature($payload) {
		return strtolower(hash_hmac('sha384', $payload, $this->secret));
	}

	/**
	 * @return string
	 */
	protected function getNonce() {
		$microTime = explode(' ', microtime());
		return $microTime[1] . substr($microTime[0], 2, 6);
	}

}