<?php

namespace SEO\Services;

class LinkAssistantState {

	/**
	 * A random 128-bit hexadecimal string representing the current user. This generally should remain identical for
	 * the lifetime of the product, but can be renewed under exceptional circumstances.
	 *
	 * @var string|null
	 */
	private $personalId;

	/**
	 * The version to use for the API.
	 *
	 * @var string|null
	 */
	private $apiVersion;

	/**
	 * The access key to use for user-authenticated requests.
	 *
	 * @var string|null
	 */
	private $apiAccessKey;

	/**
	 * The expiration timestamp of the access key in unix seconds.
	 *
	 * @var int|null
	 */
	private $apiAccessKeyExpiration;

	/**
	 * The expiration timestamp of the most recent rate limit event.
	 *
	 * @var int|null
	 */
	private $rateLimitExpiration;

	/**
	 * Loads values from the database.
	 *
	 * @return void
	 */
	public function load() {
		global $studio;

		$state = $studio->getopt('linkassistant.state');

		if (is_string($state)) {
			$state = json_decode($state);

			$this->personalId = $state->personalId;
			$this->apiVersion = $state->apiVersion;
			$this->apiAccessKey = $state->apiAccessKey;
			$this->apiAccessKeyExpiration = $state->apiAccessKeyExpiration;
			$this->rateLimitExpiration = $state->rateLimitExpiration;
		}
	}

	/**
	 * Saves values to the database.
	 *
	 * @return void
	 */
	public function save() {
		global $studio;

		$studio->setopt('linkassistant.state', json_encode($this->dto()));
	}

	/**
	 * A random 64-bit hexadecimal string representing the current user. This generally should remain identical for
	 * the lifetime of the product, but can be renewed under exceptional circumstances.
	 *
	 * @return string
	 */
	public function getPersonalId() {
		if (!$this->personalId) {
			$hex = bin2hex(random_bytes(8));
			$this->setPersonalId($hex);
			$this->save();
		}

 		return $this->personalId;
	}

	/**
	 * Sets the personal ID to the given value. Pass `null` to automatically generate a new ID next time it's needed.
	 *
	 * @param string|null $value
	 * @return $this
	 */
	public function setPersonalId($value) {
		$this->personalId = $value;
		return $this;
	}

	/**
	 * Returns the version to use for the API.
	 *
	 * @return string
	 */
	public function getApiVersion() {
		if (!is_string($this->apiVersion)) {
			return '1.0';
		}

		return $this->apiVersion;
	}

	/**
	 * Sets the version to use for the API. This should be a string in the format `x.x`.
	 *
	 * @param string $version
	 * @return $this
	 */
	public function setApiVersion($version) {
		$this->apiVersion = $version;
		return $this;
	}

	/**
	 * Returns the access key to use for user-authenticated requests. Returns `null` if not configured or expired.
	 *
	 * @return string|null
	 */
	public function getApiAccessKey() {
		if ($this->apiAccessKeyExpiration && $this->apiAccessKeyExpiration <= time()) {
			return null;
		}

		return $this->apiAccessKey;
	}

	/**
	 * Sets the access key to use for user-authenticated requests.
	 *
	 * @param string|null $key
	 * @param int $duration Defaults to `3599` (seconds)
	 * @return $this
	 */
	public function setApiAccessKey($key, $duration = 3599) {
		$this->apiAccessKey = $key;
		$this->apiAccessKeyExpiration = time() + $duration;
		return $this;
	}

	/**
	 * Returns true if we're currently rate limited.
	 *
	 * @return bool
	 */
	public function getRateLimited() {
		return $this->rateLimitExpiration && $this->rateLimitExpiration > time();
	}

	/**
	 * Registers a rate limit event.
	 *
	 * @param int $duration Defaults to `1800` (seconds)
	 * @return $this
	 */
	public function setRateLimited($duration = 1800) {
		$this->rateLimitExpiration = time() + $duration;
		return $this;
	}

	/**
	 * Builds the state into an array for data transfer and storage.
	 *
	 * @return array
	 */
	public function dto() {
		return [
			'personalId' => $this->personalId,
			'apiVersion' => $this->apiVersion,
			'apiAccessKey' => $this->apiAccessKey,
			'apiAccessKeyExpiration' => $this->apiAccessKeyExpiration,
			'rateLimitExpiration' => $this->rateLimitExpiration
		];
	}

}
