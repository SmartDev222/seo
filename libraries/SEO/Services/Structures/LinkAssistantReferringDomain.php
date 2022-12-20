<?php

namespace SEO\Services\Structures;

class LinkAssistantReferringDomain {

	/**
	 * The referring domain name.
	 * @var string
	 */
	public $refdomain;

	/**
	 * The number of backlinks this domain has pointing to the target domain.
	 * @var int
	 */
	public $backlinks;

	/**
	 * The number of dofollow backlinks this domain has pointing to the target domain.
	 * @var int
	 */
	public $dofollow_backlinks;

	/**
	 * The date (in `Y-m-d` format) that this domain was first seen pointing to the target domain.
	 *
	 * @var string
	 */
	public $first_seen;

	/**
	 * The domain authority score for link.
	 * @var int
	 */
	public $domain_inlink_rank;

}
