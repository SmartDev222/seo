<?php

namespace SEO\Services\Structures;

use Studio\Util\Http\WebRequest;

class SpyFuParameters {

	/**
	 * @var string
	 */
	public $url;

	/**
	 * @var string
	 */
	public $csrfToken;

	/**
	 * @var string|null
	 */
	public $newRelicToken;

	/**
	 * @var array
	 */
	public $cookies = [];

	/**
	 * Applies the parameters to the given request object.
	 *
	 * @param WebRequest $request
	 * @return void
	 */
	public function apply(WebRequest $request) {
		$request->setReferer($this->url);
		$request->setCookies($this->cookies);
		$request->setHeader('X-CSRF-TOKEN', $this->csrfToken);

		if (isset($this->newRelicToken)) {
			$request->setHeader('X-NewRelic-ID', $this->newRelicToken);
		}
	}

}
