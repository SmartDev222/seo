<?php

namespace SEO\Services;

use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use JsonMapper;
use JsonMapper_Exception;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\RSA;
use ReflectionException;
use SEO\Common\SEOException;
use SEO\Services\Structures\LinkAssistantAuthority;
use SEO\Services\Structures\LinkAssistantBacklink;
use SEO\Services\Structures\LinkAssistantBacklinkCounts;
use SEO\Services\Structures\LinkAssistantBacklinkSearch;
use SEO\Services\Structures\LinkAssistantLostBacklink;
use SEO\Services\Structures\LinkAssistantMetrics;
use SEO\Services\Structures\LinkAssistantReferringDomain;
use Studio\Util\Http\WebRequest;
use Studio\Util\Http\WebRequestException;
use Studio\Util\Http\WebResponse;

/**
 * This class was developed for SEO Studio and can be used in compliance with the license you purchased for that
 * product. You can view the CodeCanyon license specifications here: http://codecanyon.net/licenses
 *
 * - Do not share, redistribute, or sell this source code.
 * - Do not embed this code in any other software or application for publication, distribution, or resale.
 * - Do not publish this source code in any manner.
 * - Do not modify this source code to abuse the underlying service.
 *
 * Note: The data source for this class (WebMeUp & Link Assistant) is proprietary. Do not take this code and use it in
 * your own application without understanding the implications of using their data. For the purposes of SEO Studio,
 * scraping and reusing this data is legal under United States federal law barring exceptional use cases.
 *
 * The source code within this class uses a 2048-bit RSA public key for its operation. Do not share this key with
 * anyone under any circumstance!
 *
 * @author Bailey Herbert <hello@bailey.sh>
 * @copyright http://codecanyon.net/licenses
 * @package SEO\Services
 */
class LinkAssistant {

	const RSA_KEY = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApi6yrejeRrOjhREwMzFR6CS0G40xCaPBWIxIpTzS1l6vIQ6vz6Ofku2hBxidFAP1QIpCzTBvZxfe5UN6OYVKtzOwqjH9WSBurgppkuUs5WXYIl4JO81GgXl4LeXo5xiy0JnOJdByU7BkD4xFnC2zj65/4X0d4knWYvlVGQ5NDluNLBCfYXOGl3/JQ3OPKgKWTJ67BlaafKk699DuDq7oddpZIQDUgGNH3HKHiL9eyyI46R77YceB6bVtksa9mgHY7eVnbfmd/fWDLnxb28kow8Ct1WXYSH274mtQ69mT7oiTamLQBPCcQbCzFDMVZHRmrROrOlndACSqmUHjhxcLzwIDAQAB";
	const AES_KEY = "w46Gp9gPCmMioWX4";
	const USER_AGENT = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36";

	const REQUEST_LINKASSISTANT_API = "aHR0cHM6Ly9hcGkubGluay1hc3Npc3RhbnQuY29tL3YxL2JsZXg=";
	const REQUEST_LINKASSISTANT_BACKLINKS = "aHR0cHM6Ly9iYWNrbGlua3MubGluay1hc3Npc3RhbnQuY29tL2JsZXg=";
	const REQUEST_BACKLINKS = "aHR0cHM6Ly9hcGkuc2VvcG93ZXJzdWl0ZS5jb20vYmFja2xpbmtz";

	/**
	 * @var LinkAssistantState
	 */
	protected $state;
	protected $internUnauthorizedReattempt;

	/**
	 * Constructs a new `LinkAssistant` instance.
	 */
	public function __construct() {
		$this->state = new LinkAssistantState();
		$this->state->load();
	}

	/**
	 * Returns an object with backlink counts for the specified URL.
	 *
	 * @param string $target An absolute URL with a scheme and trailing slash.
	 * @return LinkAssistantBacklinkCounts
	 *
	 * @throws WebRequestException
	 * @throws Exception
	 */
	public function getBacklinkCounts($target) {
		$request = $this->getRequest(static::REQUEST_LINKASSISTANT_BACKLINKS, '/backlinksCount', [
			'mode' => 'exactDomain',
			'apiKey' => $this->encryptAESKey('', '944248165', '350552e32d11d3fe'),
			'version' => 15,
			'url' => urlencode($target),
			'extended' => 1
		]);

		$response = $this->checkResponse($request->get());

		if (!$response) {
			throw new SEOException('Failed to fetch backlink counts (status 401)', 0x75101);
		}

		$mapper = new JsonMapper();
		return $mapper->map($response->getJson(false), new LinkAssistantBacklinkCounts());
	}

	/**
	 * Returns an object with backlink counts for the specified URL.
	 *
	 * @param string|string[] $targets One or more absolute URLs with a scheme and trailing slash.
	 * @param string $mode
	 *   - `host`
	 *   - `domain`
	 *   - `url`
	 *
	 * @return LinkAssistantMetrics[]
	 *
	 * @throws WebRequestException
	 * @throws Exception
	 */
	public function getMetrics($targets, $mode = 'domain') {
		if (empty($targets)) {
			return [];
		}

		$request = $this->getRequest(static::REQUEST_BACKLINKS, sprintf('/v%s/get-metrics', $this->state->getApiVersion()), [
			'spsrequestid' => $this->getRequestId(),
			'apikey' => $this->getApiKey()
		]);

		$response = $this->checkResponse($request->post([
			'target' => (array)$targets,
			'mode' => $mode
		], 'json'));

		$mapper = new JsonMapper();
		return $mapper->mapArray($response->getJson(false)->metrics, [], LinkAssistantMetrics::class);
	}

	/**
	 * Returns an array of new backlinks for the specified URL.
	 *
	 * @param string $target An absolute URL with a scheme and trailing slash.
	 * @param int $limit The number of results to fetch.
	 * @param string $since Defaults to `1 month`.
	 * @param string $mode
	 * @param string $orderBy
	 *   - Sort by date: `new_lost_date` (default)
	 *   - Sort by domain rank: `domain_inlink_rank`
	 *   - Sort by page rank: `inlink_rank`
	 * @return LinkAssistantLostBacklink[]
	 *
	 * @throws WebRequestException
	 * @throws Exception
	 */
	public function getNewBacklinks($target, $limit = 100, $since = '1 month', $mode = 'domain', $orderBy = 'new_lost_date') {
		$request = $this->getRequest(static::REQUEST_BACKLINKS, sprintf('/v%s/get-new-lost-backlinks', $this->state->getApiVersion()), [
			'spsrequestid' => $this->getRequestId(),
			'apiKey' => $this->getApiKey()
		]);

		$response = $this->checkResponse($request->post([
			'target' => $target,
			'mode' => $mode,
			'new_lost_type' => 'new',
			'date_from' => (new DateTime())->sub(DateInterval::createFromDateString($since))->format('Y-m-d'),
			'date_to' => (new DateTime())->format('Y-m-d'),
			'order_by' => $orderBy,
			'limit' => $limit
		], 'json'));

		if (!$response) {
			return $this->getNewBacklinks($target, $limit, $since, $mode, $orderBy);
		}

		$mapper = new JsonMapper();
		return $mapper->mapArray($response->getJson(false)->new_lost_backlinks, [], LinkAssistantLostBacklink::class);
	}

	/**
	 * Performs a search for backlinks.
	 *
	 * @param LinkAssistantBacklinkSearch $search
	 *
	 * @return LinkAssistantBacklink[]
	 * @throws Exception
	 * @throws SEOException
	 * @throws WebRequestException
	 * @throws ReflectionException
	 * @throws JsonMapper_Exception
	 * @throws InvalidArgumentException
	 */
	public function getBacklinks(LinkAssistantBacklinkSearch $search) {
		$params = $search->toParameters();
		$params['spsrequestid'] = $this->getRequestId();
		$params['apikey'] = $this->getApiKey();

		$request = $this->getRequest(
			static::REQUEST_BACKLINKS,
			sprintf('/v%s/get-backlinks', $this->state->getApiVersion()),
			$params
		);

		if (!($response = $this->checkResponse($request->get()))) {
			return $this->getBacklinks($search);
		}

		$mapper = new JsonMapper();
		return $mapper->mapArray($response->getJson(false)->backlinks, [], LinkAssistantBacklink::class);
	}

	/**
	 * Returns page and domain authority scores for one or more links.
	 *
	 * @param array|string $links
	 *   One or more links (either as a string or an array) to check scores for. These can be simple hostname strings,
	 *   in which the home page is inferred, or absolute URLs to specific pages.
	 *
	 * @return LinkAssistantAuthority[]
	 * @throws Exception
	 * @throws SEOException
	 * @throws WebRequestException
	 */
	public function getAuthorityScores($links) {
		if (empty($links)) {
			return [];
		}

		$request = $this->getRequest(static::REQUEST_BACKLINKS, sprintf('/v%s/get-inlink-rank', $this->state->getApiVersion()), [
			'spsrequestid' => $this->getRequestId(),
			'apiKey' => $this->getApiKey()
		]);

		$response = $this->checkResponse($request->post([
			'target' => (array) $links
		], 'json'));

		if (!$response) {
			return $this->getAuthorityScores($links);
		}

		$mapper = new JsonMapper();
		return $mapper->mapArray($response->getJson(false)->pages, [], LinkAssistantAuthority::class);
	}

	/**
	 * Returns an array of referring domains for the specified URL.
	 *
	 * @param string $target An absolute URL with a scheme and trailing slash.
	 * @param int $limit The number of results to fetch.
	 * @param string $mode
	 *   - `domain`
	 *   - `host`
	 *   - `url`
	 * @param string $orderBy
	 *   - Sort by date: `date_found` (default)
	 *   - Sort by domain rank: `domain_inlink_rank`
	 *   - Sort by page rank: `inlink_rank`
	 *
	 * @return LinkAssistantReferringDomain[]
	 *
	 * @throws WebRequestException
	 * @throws Exception
	 */
	public function getReferringDomains($target, $limit = 100, $mode = 'domain', $orderBy = null) {
		$request = $this->getRequest(static::REQUEST_BACKLINKS, sprintf('/v%s/get-refdomains', $this->state->getApiVersion()), [
			'spsrequestid' => $this->getRequestId(),
			'apikey' => $this->getApiKey(),
			'target' => $target,
			'mode' => $mode,
			'order_by' => $orderBy,
			'limit' => $limit
		]);

		$response = $this->checkResponse($request->get());

		if (!$response) {
			return $this->getReferringDomains($target, $limit, $mode, $orderBy);
		}

		$mapper = new JsonMapper();
		return $mapper->mapArray($response->getJson(false)->refdomains, [], LinkAssistantReferringDomain::class);
	}

	/**
	 * Returns information about the current subscription.
	 *
	 * @return array
	 *
	 * @throws WebRequestException
	 * @throws Exception
	 */
	public function getSubscriptionInfo() {
		$request = $this->getRequest(static::REQUEST_BACKLINKS, '/v1.0/get-subscription-info', [
			'spsrequestid' => $this->getRequestId(),
			'apikey' => $this->getApiKey(),
			'output' => 'json'
		]);

		$response = $this->checkResponse($request->get());

		if (!$response) {
			return $this->getSubscriptionInfo();
		}

		return $response->getJson()['subscription_info'];
	}

	/**
	 * Returns the current key to use for user authentication. This will automatically generate a new key if necessary.
	 *
	 * @return string
	 * @throws SEOException
	 */
	protected function getApiKey() {
		$key = $this->state->getApiAccessKey();

		if (is_null($key)) {
			$request = $this->getRequest(static::REQUEST_LINKASSISTANT_API, '/get-access');
			$message = $this->encryptBlexRequest('', '944248165', $this->state->getPersonalId());
			$response = $request->post($message, 'application/json');

			if ($response->getStatusCode() !== 200) {
				throw new SEOException(
					sprintf('Failed to fetch access key (status %d)', $response->getStatusCode()),
					0x75200
				);
			}

			$body = $response->getJson();
			$key = $this->decryptBlexAccessData($body['data']);
			$version = $body['version'];

			$this->state->setApiAccessKey($key);
			$this->state->setApiVersion($version);
			$this->state->save();
		}

		return $key;
	}

	/**
	 * Regenerates the API key.
	 *
	 * @return string
	 * @throws SEOException
	 */
	public function regenerateApiKey() {
		$this->state->setApiAccessKey(null);
		return $this->getApiKey();
	}

	/**
	 * Parses the given base64-encoded cipher data into an ephemeral API key.
	 *
	 * @param string $data
	 * @return string
	 */
	protected function decryptBlexAccessData($data) {
		$rsa = new RSA();
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		$rsa->loadKey(static::RSA_KEY, RSA::PUBLIC_FORMAT_PKCS1_RAW);

		$decrypted = $rsa->decrypt(base64_decode($data));

		if (!is_string($decrypted)) {
			throw new Exception('Key decryption failed');
		}

		if ($decrypted[0] !== '{') {
			throw new Exception('Key decryption did not yield a JSON object');
		}

		$result = json_decode($decrypted, true);

		if (!array_key_exists('key', $result)) {
			throw new Exception('Key was not found in the decrypted object');
		}

		return $result['key'];
	}

	/**
	 * Encrypts a request payload for blex authentication and returns a JSON string.
	 *
	 * @param string $key
	 * @param string $hash
	 * @param string $personalID
	 * @return string
	 */
	protected function encryptBlexRequest($key, $hash, $personalID) {
		$rsa = new RSA();
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		$rsa->loadKey(static::RSA_KEY, RSA::PUBLIC_FORMAT_PKCS1_RAW);

		$cipher = base64_encode($rsa->encrypt(json_encode([
			'key' => $key,
			'hash' => $hash,
			'personalID' => $personalID
		])));

		return json_encode([
			'data' => $cipher
		], JSON_UNESCAPED_SLASHES);
	}

	/**
	 * Builds an AES key with the given credentials.
	 *
	 * @param string $key
	 * @param string $hash
	 * @param string $personalId
	 * @return string
	 */
	protected function encryptAESKey($key, $hash, $personalId) {
		$message = sprintf('&key=%s&hash=%s&personalID=%s', $key, $hash, $personalId);

		$aes = new AES(AES::MODE_ECB);
		$aes->setKey(static::AES_KEY);
		$result = $aes->encrypt($message);

		return strtoupper(bin2hex($result));
	}

	/**
	 * Builds a web request instance.
	 *
	 * @param string $base
	 * @param string $path
	 * @param array $parameters
	 * @return WebRequest
	 */
	protected function getRequest($base, $path, $parameters = []) {
		if ($this->state->getRateLimited()) {
			throw new SEOException('Rate limited! Please check back later!', 0x75000);
		}

		$parameters = !empty($parameters) ? ('?' . http_build_query($parameters)) : '';

		$request = new WebRequest(sprintf('%s/%s%s', base64_decode($base), ltrim($path, '/'), $parameters));
		$request->setUserAgent(static::USER_AGENT);
		$request->setTimeout(25);

		return $request;
	}

	/**
	 * Checks the response for issues.
	 *
	 * - When a rate limit is detected, the state is updated and an exception is thrown.
	 * - When our token has expired, the state is updated and `false` is returned.
	 * - For other erroneous response codes, an exception is thrown.
	 * - For normal responses, the response object is returned directly.
	 *
	 * @param WebResponse $response
	 * @return WebResponse|false
	 * @throws SEOException
	 */
	protected function checkResponse(WebResponse $response) {
		global $studio;

		$decoded = parse_url($response->getUrl());
		$url = $decoded['scheme'] . '://' . $decoded['host'] . '/' . $decoded['path'];

		if ($response->getStatusCode() === 429) {
			$this->state->setRateLimited()->save();
			throw new SEOException('Rate limited! Please check back later!', 0x75001);
		}

		if ($response->getStatusCode() === 401) {
			if ($this->internUnauthorizedReattempt) {
				$studio->errors->sendMessage(
					sprintf('Critical: Failed to reauthorize for %s', $url),
					[],
					['state' => $this->state->dto()]
				);

				throw new SEOException('Critical authorization failure!', 0x75002);
			}

			$this->internUnauthorizedReattempt = true;
			$this->state->setApiAccessKey(null)->save();
			return false;
		}

		if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
			$studio->errors->sendMessage(
				sprintf('Got status %d on %s', $response->getStatusCode(), $url),
				[],
				['state' => $this->state->dto()]
			);

			throw new SEOException(
				sprintf('Encountered a request error (status %d)', $response->getStatusCode()),
				0x75010
			);
		}

		if ($response->getStatusCode() >= 500) {
			throw new SEOException(
				sprintf('Service unavailable (status %d)', $response->getStatusCode()),
				0x75011
			);
		}

		return $response;
	}

	/**
	 * Generates a unique request identifier.
	 *
	 * @return string
	 */
	protected function getRequestId() {
		return 'sps-client-' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			random_int(0, 0xffff), random_int(0, 0xffff),
			random_int(0, 0xffff),
			random_int(0, 0x0fff) | 0x4000,
			random_int(0, 0x3fff) | 0x8000,
			random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
		);
	}

}
