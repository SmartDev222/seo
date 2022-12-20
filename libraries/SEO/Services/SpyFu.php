<?php

namespace SEO\Services;

use Exception;
use InvalidArgumentException;
use JsonMapper;
use ReflectionException;
use JsonMapper_Exception;
use SEO\Common\SEOException;
use SEO\Services\Structures\SpyFuParameters;
use SEO\Services\Structures\SpyFuValuableKeyword;
use Studio\Util\Http\WebRequest;
use Studio\Util\Http\WebRequestException;

class SpyFu {

	protected $parameterCache = [];

	/**
	 * Initializes a request and returns the necessary parameters. The response is cached in memory and will be reused
	 * for subsequent requests with the same hostname.
	 *
	 * @param string $hostName
	 *
	 * @return SpyFuParameters
	 * @throws WebRequestException
	 * @throws SEOException
	 */
	protected function getParameters($hostName) {
		if (array_key_exists($hostName, $this->parameterCache)) {
			return $this->parameterCache[$hostName];
		}

		$url = sprintf('https://www.spyfu.com/overview/domain?query=%s', urlencode($hostName));
		$request = new WebRequest($url);
		$request->setTimeout(10);

		$response = $request->get();
		$cookies = $response->getCookiesAssoc();

		if ($response->getStatusCode() !== 200) {
			throw new SEOException(sprintf('Got status code %d during initialization', $response->getStatusCode()), 0x76100);
		}

		$html = $response->getBody();

		preg_match('/<meta name="token" content="([^"]+)">/m', $html, $tokenMatch);
		preg_match('/{xpid:"([^"]+)",/m', $html, $newRelicMatch);

		$csrfToken = $tokenMatch ? $tokenMatch[1] : null;
		$newRelicToken = $newRelicMatch ? $newRelicMatch[1] : null;

		if (!$csrfToken) {
			throw new SEOException('Failed to extract CSRF token', 0x76101);
		}

		$params = $this->parameterCache[$hostName] = new SpyFuParameters();
		$params->url = $url;
		$params->csrfToken = $csrfToken;
		$params->newRelicToken = $newRelicToken;
		$params->cookies = $cookies;

		return $params;
	}

	/**
	 * Executes a request and returns the response as JSON (object parsing).
	 *
	 * @param string $hostName The hostname to use for request initialization (CSRF, relic, etc)
	 * @param string $path The path for the API request (`/NsaApi/:path`)
	 * @param array $body The request body to be encoded with JSON
	 * @return mixed
	 *
	 * @throws WebRequestException
	 * @throws SEOException
	 * @throws Exception
	 */
	private function send($hostName, $path, $body) {
		$params = $this->getParameters($hostName);

		// Build the request
		$request = new WebRequest(sprintf('https://www.spyfu.com/NsaApi/%s', ltrim($path, '/')));
		$request->setTimeout(10);
		$params->apply($request);

		// Execute & fetch the response
		$response = $request->post($body, 'json');

		// Handle errors
		if ($response->getStatusCode() !== 200) {
			throw new SEOException(sprintf('Got status code %d during request', $response->getStatusCode()), 0x76200);
		}

		// Return the response as JSON
		return $response->getJson(false);
	}

	/**
	 * Returns the most valuable keywords for the given hostname.
	 *
	 * @param string $hostName The hostname such as `example.com`
	 * @param string $countryCode The two-letter country code from the list of supported countries
	 * @param int $count The number of results to retrieve (defaults to 5)
	 *
	 * @return SpyFuValuableKeyword[]
	 *
	 * @throws WebRequestException
	 * @throws SEOException
	 * @throws Exception
	 */
	public function getMostValuableKeywords($hostName, $countryCode = 'us', $count = 5) {
		$response = $this->send($hostName, '/Serp/GetMostValuableKeywords', [
			'adultFilter' => false,
			'countryCode' => strtoupper($countryCode),
			'isOptimized' => true,
			'pageSize' => $count,
			'query' => $hostName,
			'startingRow' => 1
		]);

		$mapper = new JsonMapper();
		$keywords = $mapper->mapArray($response->keywords, [], SpyFuValuableKeyword::class);

		foreach ($keywords as &$keyword) {
			$keyword->countryCode = strtolower($countryCode);
		}

		return $keywords;
	}

	/**
	 * Returns an array of supported country codes. Examples include `us` and `in`.
	 *
	 * @return string[]
	 */
	public function getCountryCodes() {
		return ['us', 'au', 'br', 'ca', 'de', 'fr', 'in', 'uk'];
	}

}
