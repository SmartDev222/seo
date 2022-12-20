<?php

/**
 * This is original, proprietary code that has been made available as a component of the greater application. Private
 * or commercial derivative works are permitted. The source code for this file must not be made public or redistributed
 * without express constent from the author. Do not redistribute!
 *
 * @copyright
 * @author Bailey Herbert
 */

namespace SEO\Parsers;

use Studio\Util\Parsers\HTMLDocumentNode;

class GoogleSearchResult {

	/**
	 * The index of the search result on its page (zero-based).
	 *
	 * @var int
	 */
	public $index = 0;

	/**
	 * The title of the search result.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * The citation under the title which typically shows the link structure.
	 *
	 * @var string
	 */
	public $citation = '';

	/**
	 * The description of the search result.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * The target URL for this search result.
	 *
	 * @var string
	 */
	public $href = '';

	/**
	 * The site links that appeared under this result.
	 *
	 * @var GoogleSearchResultSiteLink[]
	 */
	public $siteLinks = [];

	/**
	 * The document node that this result was parsed from.
	 *
	 * @var HTMLDocumentNode
	 */
	public $node;

	/**
	 * Returns an associative array containing all JSON-serializable properties from the result.
	 *
	 * @return array
	 */
	public function detach() {
		return [
			'index' => $this->index,
			'title' => $this->title,
			'citation' => $this->citation,
			'description' => $this->description,
			'href' => $this->href,
			'siteLinks' => $this->siteLinks
		];
	}

}
