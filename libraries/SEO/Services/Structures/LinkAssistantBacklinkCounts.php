<?php

namespace SEO\Services\Structures;

class LinkAssistantBacklinkCounts {

	/**
	 * @var IBacklinks
	 */
	public $backlinks;

	/**
	 * @var IDomains
	 */
	public $domains;

	/**
	 * @var IDoFollowCount
	 */
	public $ips;

	/**
	 * @var IDoFollowCount
	 */
	public $cBlocks;

	/**
	 * @var IDoFollowCount
	 */
	public $anchors;

	/**
	 * @var int
	 */
	public $anchorUrls;

	/**
	 * @var ITopTLD
	 */
	public $topTLD;

	/**
	 * @var ITopCountry
	 */
	public $topCountry;

	/**
	 * @var ITopAnchor
	 */
	public $topAnchorsByBacklinks;

	/**
	 * @var ITopAnchor
	 */
	public $topAnchorsByDomains;

	/**
	 * @var ITopAnchorUrl
	 */
	public $topAnchorUrlsByBacklinks;

	/**
	 * @var ITopAnchorUrl
	 */
	public $topAnchorUrlsByDomains;

}

class IBacklinks {
	/**
	 * @var int
	 */
	public $total;

	/**
	 * @var int
	 */
	public $doFollow;

	/**
	 * @var int
	 */
	public $fromHomePage;

	/**
	 * @var int
	 */
	public $doFollowFromHomePage;

	/**
	 * @var int
	 */
	public $text;

	/**
	 * @var int
	 */
	public $toHomePage;
}

class IDomains {
	/**
	 * @var int
	 */
	public $total;

	/**
	 * @var int
	 */
	public $doFollow;

	/**
	 * @var int
	 */
	public $fromHomePage;

	/**
	 * @var int
	 */
	public $toHomePage;
}

class IDoFollowCount {
	/**
	 * @var int
	 */
	public $total;

	/**
	 * @var int
	 */
	public $doFollow;
}

class ITopTLD {
	/**
	 * @var ITopTLDLine[]
	 */
	public $line;
}

class ITopTLDLine {
	/**
	 * The TLD (such as `com` or `net`).
	 * @var string
	 */
	public $label;

	/**
	 * @var int
	 */
	public $count;
}

class ITopCountry {
	/**
	 * @var ITopCountryLine[]
	 */
	public $line;
}

class ITopCountryLine {
	/**
	 * The two-letter country code (such as `us` or `ca`).
	 * @var string
	 */
	public $code;

	/**
	 * The country label (such as `USA` or `Canada`).
	 * @var string
	 */
	public $label;

	/**
	 * @var int
	 */
	public $count;
}

class ITopAnchor {
	/**
	 * @var ITopAnchorLine[]
	 */
	public $line;
}

class ITopAnchorLine {
	/**
	 * The anchor text or image alt.
	 * @var string|null
	 */
	public $anchor;

	/**
	 * Whether or not this entry is for textual links (false for images).
	 * @var bool
	 */
	public $text;

	/**
	 * @var int
	 */
	public $count;
}

class ITopAnchorUrl {
	/**
	 * @var ITopAnchorUrlLine[]
	 */
	public $line;
}

class ITopAnchorUrlLine {
	/**
	 * An absolute URL for this entry, pointing either to the domain level or an absolute page.
	 * @var string
	 */
	public $label;

	/**
	 * @var int
	 */
	public $count;
}

