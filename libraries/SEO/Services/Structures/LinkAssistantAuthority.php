<?php

namespace SEO\Services\Structures;

class LinkAssistantAuthority {

	/**
	 * The URL as it was provided in the request's input.
	 * @var string
	 */
	public $url;

	/**
	 * The page authority score for link.
	 * @var int
	 */
	public $inlink_rank;

	/**
	 * The domain authority score for link.
	 * @var int
	 */
	public $domain_inlink_rank;

}
