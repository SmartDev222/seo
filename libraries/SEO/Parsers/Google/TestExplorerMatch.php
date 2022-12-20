<?php

/**
 * This is original, proprietary code that has been made available as a component of the greater application. Private
 * or commercial derivative works are permitted. The source code for this file must not be made public or redistributed
 * without express constent from the author. Do not redistribute!
 *
 * @copyright
 * @author Bailey Herbert
 */

namespace SEO\Parsers\Google;

use Studio\Util\Parsers\HTMLDocumentNode;

class TestExplorerMatch {

	/**
	 * The index of the matched element or `null` if it isn't a text element.
	 *
	 * @var int|null
	 */
	public $index;

	/**
	 * The text that was matched.
	 *
	 * @var string
	 */
	public $value;

	/**
	 * The element that was matched.
	 *
	 * @var HTMLDocumentNode
	 */
	public $element;

}
