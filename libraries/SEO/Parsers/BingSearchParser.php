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

use Exception;
use SEO\Parsers\Google\TestExplorerMatch;
use Studio\Util\Parsers\HTMLDocument;

class BingSearchParser {

	/**
	 * The parsed document instance.
	 *
	 * @var HTMLDocument
	 */
	private $document;

	/**
	 * The extracted search result rows.
	 *
	 * @var GoogleSearchResult[]
	 */
	private $results;

	/**
	 * The estimated total number of search results available for this query.
	 *
	 * @var int
	 */
	private $numTotalResults;

	/**
	 * The element that contains all of the search results or `null` if unsure.
	 *
	 * @var HTMLDocumentNode
	 */
	private $container;

	public function __construct($html) {
		$html = $this->optimizeInput($html);

		$this->document = new HTMLDocument($html);
		$this->numTotalResults = $this->extractNumResults();
		$this->results = $this->extractResultRows();

		if ($this->numTotalResults === null) {
			$this->numTotalResults = count($this->results);
		}
	}


	/**
	 * Returns an array of results.
	 *
	 * @return GoogleSearchResult[]
	 */
	public function getResults() {
		return $this->results;
	}

	/**
	 * Returns the number of results available for the current data set.
	 *
	 * @return int
	 */
	public function getNumResults() {
		return count($this->results);
	}

	/**
	 * Returns the estimated total number of results available for this query.
	 *
	 * @return int
	 */
	public function getNumTotalResults() {
		return $this->numTotalResults;
	}

	/**
	 * Returns the DOM element that is believed to contain the search results for this page. Returns `null` if a
	 * container element could not be reliably determined.
	 *
	 * @return HTMLDocumentNode|null
	 */
	public function getContainer() {
		return $this->container;
	}

	/**
	 * Extracts the number of total results from the top of the page. Returns `null` upon failure.
	 *
	 * @return int|null
	 */
	private function extractNumResults() {
		$target = $this->document->find('.sb_count', 0);
		$noResultsElement = $this->document->find('.b_no', 0);

		if ($noResultsElement) {
			return 0;
		}

		if (!$target) {
			return null;
		}

		$text = trim($target->getPlainText());

		if (preg_match('/\b([\d,.]+)\b/m', $text, $matches)) {
			$number = intval(preg_replace('/[^\d]/', '', $matches[1]));

			return $number;
		}

		return 0;
	}

	private function extractResultRows() {
		$rows = $this->document->find('.b_algo');
		$results = [];

		foreach ($rows as $index => $row) {
			$headingElement = $row->find('h2', 0);
			$linkElement = $row->find('a', 0);
			$captionElement = $row->find('.b_caption p', 0);
			$citationElement = $row->find('cite', 0);

			if (!$headingElement || !$linkElement || !$citationElement) {
				continue;
			}

			$title = trim($headingElement->getPlainText());
			$href = $linkElement->getAttribute('href');
			$citation = trim($citationElement->getPlainText());
			$description = $captionElement ? trim($captionElement->getPlainText()) : '';

			if (substr($href, 0, 1) === '/') continue;

			$result = new GoogleSearchResult();
			$result->index = $index;
			$result->node = $row;
			$result->title = $this->correctText($title);
			$result->description = $this->correctText($description);
			$result->citation = $this->correctText($citation);
			$result->href = $href;

			// TODO: Implement site link extraction
			$result->siteLinks = [];

			$results[] = $result;
		}

		if (count($results) === 0 && $this->numTotalResults > 0) {
			throw new Exception('Bing parse error: Missing elements caused result extraction fault');
		}

		return $results;
	}

	/**
	 * Reduces the size of the given HTML string by stripping out scripts and styles.
	 *
	 * @param string $html
	 * @return string
	 */
	private function optimizeInput($html) {
		return preg_replace('/<(?:script|style)\b[^<]*(?:(?!<\/(?:script|style)>)<[^<]*)*<\/(?:script|style)>/i', '', $html);
	}

	/**
	 * Applies corrections and entity decoding to the given text.
	 *
	 * @param TextExplorerMatch|string|null $text
	 * @return string
	 */
	private function correctText($text) {
		if ($text instanceof TestExplorerMatch) {
			$text = $text->value;
		}

		if (is_null($text)) {
			return '';
		}

		$text = html_entity_decode(urldecode($text), ENT_QUOTES);
		$text = preg_replace('/\b +([,.!?%$]) +/', '$1 ', $text);

		return $text;
	}

}
