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

class GoogleSearchResultSiteLink {

	/**
	 * The index of this site link as it appeared in the results (zero-based).
	 *
	 * @var int
	 */
	public $index = 0;

	/**
	 * The title of this site link.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * The description for this site link (if available).
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * The target URL for this site link.
	 *
	 * @var string
	 */
	public $href = '';

}
