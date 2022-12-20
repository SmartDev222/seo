<?php

namespace SEO\Services\Structures;

class LinkAssistantBacklink {

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

	/**
	 * The date (in format `Y-m-d`) that the backlink was last seen.
	 *
	 * @var string
	 */
	public $last_visited;

}
