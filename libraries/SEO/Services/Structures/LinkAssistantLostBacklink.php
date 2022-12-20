<?php

namespace SEO\Services\Structures;

class LinkAssistantLostBacklink {

	/**
	 * The date (in format `Y-m-d`) that the backlink was found/lost.
	 *
	 * @var string
	 */
	public $new_lost_date;

	/**
	 * The type of backlink record (either `new` or `lost`).
	 *
	 * @var string
	 */
	public $new_lost_type;

	/**
	 * The URL that this backlink was found on.
	 *
	 * @var string
	 */
	public $url_from;

	/**
	 * The URL that this backlink points to.
	 *
	 * @var string
	 */
	public $url_to;

	/**
	 * The title of the page this link was found on.
	 *
	 * @var string|null
	 */
	public $title;

	/**
	 * The anchor text if applicable, or alt text for image links.
	 *
	 * @var string|null
	 */
	public $anchor;

	/**
	 * Not used.
	 *
	 * @var string|null
	 */
	public $alt;

	/**
	 * The type of link (`href` or `redirect`).
	 *
	 * @var string
	 */
	public $link_type;

	/**
	 * Whether or not this is a nofollow link.
	 *
	 * @var bool
	 */
	public $nofollow = false;

	/**
	 * Whether or not this is an image link.
	 *
	 * @var bool
	 */
	public $image = false;

	/**
	 * The source URL of the image when applicable.
	 *
	 * @var string|null
	 */
	public $image_source;

	/**
	 * The reason the link was lost, if applicable.
	 *
	 * - `page_not_found`
	 * - `crawl_error`
	 * - `page_dropped`
	 * - `redirect`
	 * - `not_canonical`
	 * - `noindex`
	 * - `link_removed`
	 * - `broken_redirect`
	 * - `other`
	 *
	 * @var string|null
	 */
	public $reason_lost;

	/**
	 * The page authority of the refering page's URL.
	 *
	 * @var int
	 */
	public $inlink_rank;

	/**
	 * The domain authority of the refering page's URL.
	 *
	 * @var int
	 */
	public $domain_inlink_rank;

	/**
	 * The date (in format `Y-m-d`) that the backlink was first seen.
	 *
	 * @var string
	 */
	public $first_seen;

}
