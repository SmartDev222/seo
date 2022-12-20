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

class TextExplorer {

	/**
	 * @var ClickThroughRow
	 */
	private $row;

	/**
	 * @var HTMLDocumentNode[]
	 */
	private $textElements;

	/**
	 * @var string[]
	 */
	private $textElementStrings;

	/**
	 * @var string
	 */
	private $host;

	public function __construct(ClickThroughRow $row, $textElements) {
		$this->row = $row;
		$this->textElements = array_values($textElements);
		$this->textElementStrings = [];
		$this->host = parse_url($this->row->targetLink, PHP_URL_HOST);

		foreach ($this->textElements as $index => $element) {
			$this->textElementStrings[$index] = trim($element->getPlainText());
		}
	}

	/**
	 * Detects and returns the title.
	 *
	 * @param HTMLDocumentNode[]|TestExplorerMatch[] $exclusions Elements to skip checking.
	 * @return TestExplorerMatch|null
	 */
	public function getTitle($exclusions = []) {
		$matchElement = null;
		$matchValue = null;
		$matchIndex = -1;

		$exclusions = $this->normalizeExclusions($exclusions);

		// Detect from headings
		if (!is_null($element = $this->getLastOf(['h1', 'h2', 'h3', 'h4', 'h5']))) {
			$match = new TestExplorerMatch();
			$match->element = $element;
			$match->value = trim($element->getPlainText());
			$match->index = array_search($element, $this->textElements, true) ?: null;

			return $match;
		}

		// Detect from strings
		// We'll effectively return the last line of text before we hit the citation, unless we hit a link
		foreach ($this->textElementStrings as $index => $string) {
			$element = $this->textElements[$index];

			if ($this->isCitation($string) || $element->tag === 'cite') {
				break;
			}

			if (!in_array($element, $exclusions, true)) {
				$matchIndex = $index;
				$matchValue = $string;
				$matchElement = $element;

				if ($element->tag === 'a') {
					break;
				}
			}
		}

		if (!is_null($matchElement)) {
			$match = new TestExplorerMatch();
			$match->element = $matchElement;
			$match->value = $matchValue;
			$match->index = $matchIndex;

			return $match;
		}
	}

	/**
	 * Detects and returns the citation.
	 *
	 * @param HTMLDocumentNode[]|TestExplorerMatch[] $exclusions Elements to skip checking.
	 * @return TestExplorerMatch|null
	 */
	public function getCitation($exclusions = []) {
		$matchElement = null;
		$matchValue = null;
		$matchIndex = -1;

		$exclusions = $this->normalizeExclusions($exclusions);

		// Detect from strings
		foreach ($this->textElementStrings as $index => $string) {
			$element = $this->textElements[$index];

			if (!in_array($element, $exclusions, true) && $this->isCitation($string)) {
				$matchIndex = $index;
				$matchValue = $string;
				$matchElement = $element;
			}
		}

		// Detect from <cite> element
		if (is_null($matchElement)) {
			$element = $this->row->rowElement->find('cite', 0);

			if (is_object($element)) {
				$match = new TestExplorerMatch();
				$match->element = $element;
				$match->value = trim($element->getPlainText());
				$match->index = array_search($element, $this->textElements, true) ?: null;
			}
		}

		if (!is_null($matchElement)) {
			$match = new TestExplorerMatch();
			$match->element = $matchElement;
			$match->value = $matchValue;
			$match->index = $matchIndex;

			return $match;
		}

		return null;
	}

	/**
	 * Detects and returns the description.
	 *
	 * @param HTMLDocumentNode[]|TestExplorerMatch[] $exclusions Elements to skip checking.
	 * @return TestExplorerMatch|null
	 */
	public function getDescription($exclusions = []) {
		$exclusions = $this->normalizeExclusions($exclusions);

		// Find what elements we have to work with
		$elementsAvailable = [];
		foreach ($this->textElements as $index => $element) {
			if (!in_array($element, $exclusions, true)) {
				$elementsAvailable[] = [
					'index' => $index,
					'element' => $element,
					'value' => $this->textElementStrings[$index]
				];
			}
		}

		// If multiple strings are left, filter out any duplicates
		$duplicateIndex = [];

		foreach ($exclusions as $element) {
			$text = trim($element->getPlainText());
			$duplicateIndex[$text] = true;
		}

		$unique = array_values(array_filter($elementsAvailable, function($el) use ($duplicateIndex) {
			if (array_key_exists($el['value'], $duplicateIndex)) {
				return false;
			}

			$duplicateIndex[$el['value']] = true;
			return true;
		}));

		if (count($unique) === 0) {
			return null;
		}

		// If we only have a single string left, return it
		if (count($unique) === 1) {
			$match = new TestExplorerMatch();
			$match->element = $unique[0]['element'];
			$match->value = $unique[0]['value'];
			$match->index = $unique[0]['index'];

			return $match;
		}

		// Sort the remaining elements by length
		usort($unique, function($a, $b) {
			$a = strlen($a['value']);
			$b = strlen($b['value']);

			if ($a === $b) return 0;
			return $a < $b ? 1 : -1;
		});

		$match = new TestExplorerMatch();
		$match->element = $unique[0]['element'];
		$match->value = $unique[0]['value'];
		$match->index = $unique[0]['index'];

		return $match;
	}

	/**
	 * Returns true if the given string *looks like* a citation.
	 *
	 * @param string $string
	 * @return bool
	 */
	public function isCitation($string) {
		$host = str_replace('www.', '', $this->host);
		$host = preg_quote($host, '/');

		if (preg_match("/^(https?:\/\/)?(www\.)?$host/", $string)) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the last element that matches the specified tag(s).
	 *
	 * @param string|string[] $tags
	 * @return HTMLDocumentNode|null
	 */
	public function getLastOf($tags) {
		$tags = (array) $tags;
		$last = null;

		foreach ($this->textElements as $element) {
			foreach ($tags as $tag) {
				if ($element->tag === $tag) {
					$last = $element;
				}
			}
		}

		return $last;
	}

	/**
	 * Normalizes exclusions, converting any match objects into nodes.
	 *
	 * @param HTMLDocumentNode[]|TestExplorerMatch[] $exclusions
	 * @return HTMLDocumentNode[]
	 */
	private function normalizeExclusions($exclusions) {
		$results = [];

		foreach ($exclusions as $exclusion) {
			if ($exclusion instanceof TestExplorerMatch) {
				$results[] = $exclusion->element;
			}
			else if ($exclusion !== null) {
				$results[] = $exclusion;
			}
		}

		return $results;
	}

}
