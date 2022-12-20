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

class ClickThroughRow {

	/**
	 * The clickthrough row element. For search result rows, this should contain all information for the result,
	 * such as the citation and description.
	 *
	 * @var HTMLDocumentNode
	 */
	public $rowElement;

	/**
	 * The index of the row element in the target link's parent chain from the top down (0 is document root).
	 *
	 * @var int
	 */
	public $rowElementIndex;

	/**
	 * The original clickthrough link that was targeted.
	 *
	 * @var HTMLDocumentNode
	 */
	public $targetLinkElement;

	/**
	 * The parents of the target link.
	 *
	 * @var HTMLDocumentNode[]
	 */
	public $targetLinkParents;

	/**
	 * Other clickthrough rows which were identified as children of this one. Note that this will be flattened to a
	 * single layer of results (i.e. there won't be nested children).
	 *
	 * @var ClickThroughRow[]
	 */
	public $children = [];

	/**
	 * The target link for this row.
	 *
	 * @var string
	 */
	public $targetLink;

}
