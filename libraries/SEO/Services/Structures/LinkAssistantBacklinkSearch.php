<?php

namespace SEO\Services\Structures;

class LinkAssistantBacklinkSearch {

	/**
	 * The aim of the request as a root domain, host (subdomain) or absolute URL.
	 *
	 * @var string
	 */
	public $target;

	/**
	 * The mode of operation as one of the following:
	 *
	 * - `domain` – matches the hostname and all subdomains
	 * - `host` – matches the exact hostname
	 * - `url` – matches the exact URL
	 *
	 * Defaults to `host` if not specified.
	 *
	 * @var string|null
	 */
	public $mode;

	/**
	 * The maximum number of results to return (between 1 and 10,000). Defaults to `100` if not specified.
	 *
	 * @var int|null
	 */
	public $limit;

	/**
	 * The field by which the results will be sorted in descending order. Must be one of the following:
	 *
	 * - `date_found` – newest backlinks first
	 * - `domain_inlink_rank` – highest domain authority scores first
	 * - `inlink_rank` – highest page authority scores first
	 *
	 * Defaults to `date_found` if not specified.
	 *
	 * @var string
	 */
	public $orderBy;

	/**
	 * The maximum number of backlinks to include per referring domain (between 1 and 100). The backlinks will be
	 * chosen based on the search order. Defaults to all if not specified.
	 *
	 * @var int|null
	 */
	public $perDomain;

	/**
	 * Converts the search object into an array of query parameters.
	 *
	 * @return array
	 */
	public function toParameters() {
		return [
			'target' => $this->target,
			'mode' => $this->mode,
			'limit' => $this->limit,
			'order_by' => $this->orderBy,
			'per_domain' => $this->perDomain
		];
	}

}
