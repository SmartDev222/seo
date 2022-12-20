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

use SEO\Parsers\Google\ClickThroughRow;
use SEO\Parsers\Google\TestExplorerMatch;
use SEO\Parsers\Google\TextExplorer;
use Studio\Util\Parsers\HTMLDocument;
use Studio\Util\Parsers\HTMLDocumentNode;

/**
 * This is a new parser for Google Search which uses a sort of "machine learning" strategy to parse the page regardless
 * of its layout. This makes it much more resilient against layout and interface changes.
 */
class GoogleSearchParser {

	/**
	 * The parsed document instance.
	 *
	 * @var HTMLDocument
	 */
	private $document;

	/**
	 * The clickthrough attribute names that were used for this page.
	 *
	 * @var string[]
	 */
	private $clickThroughAttributes;

	/**
	 * The clickthrough links for this page. All search results should have at least one of these, but some of these
	 * won't be for search results at all.
	 *
	 * @var HTMLDocumentNode[]
	 */
	private $clickThroughLinks;

	/**
	 * The clickthrough rows for this page, which will include search result rows, rich snippets, and some other
	 * contaminants which we're not interested in.
	 *
	 * @var ClickThroughRow[]
	 */
	private $clickThroughRows;

	/**
	 * The search results that have been parsed from the page.
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
	 * @var HTMLDocumentNode|null
	 */
	private $container;

	/**
	 * Creates a new `GoogleSearchParser` instance.
	 *
	 * @param string $html
	 */
	public function __construct($html) {
		$html = $this->optimizeInput($html);

		$this->clickThroughAttributes = $this->getClickThroughAttributes($html);
		$this->document = new HTMLDocument($html);
		$this->clickThroughLinks = $this->getClickThroughLinks();
		$this->clickThroughRows = $this->getClickThroughRows();
		$this->results = $this->getSearchResults();

		// Extract the search container
		$this->container = $this->getDeepestCommonParent(array_map(function(GoogleSearchResult $result) {
			return $result->node;
		}, $this->results));

		// Find the number of available results
		// Note that this won't always be available
		$this->numTotalResults = $this->extractNumResults();
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
	 * Reduces the size of the given HTML string by stripping out scripts and styles.
	 *
	 * @param string $html
	 * @return string
	 */
	private function optimizeInput($html) {
		return preg_replace('/<(?:script|style)\b[^<]*(?:(?!<\/(?:script|style)>)<[^<]*)*<\/(?:script|style)>/i', '', $html);
	}

	/**
	 * Parses search results.
	 *
	 * @return GoogleSearchResult[]
	 */
	private function getSearchResults() {
		$results = [];
		$rank = 0;

		foreach ($this->clickThroughRows as $index => $row) {
			$text = $this->getTextElements($row->rowElement, count($row->children) > 0 ? $row->children[0]->rowElement : null);
			$explorer = new TextExplorer($row, $text);

			$title = $explorer->getTitle();
			$citation = $explorer->getCitation([$title]);
			$description = $explorer->getDescription([$title, $citation]);

			if ($title && $citation && $description) {
				$result = new GoogleSearchResult();
				$result->index = $rank++;
				$result->title = $this->correctText($title);
				$result->description = $this->correctText($description);
				$result->citation = $this->correctText($citation);
				$result->href = $row->targetLink;
				$result->node = $row->rowElement;

				if (strlen($result->description) > 1000) {
					continue;
				}

				if (count($row->children) > 0) {
					$childIndex = 0;
					foreach ($row->children as $child) {
						$childText = $this->getTextElements($child->rowElement, null);
						$childExplorer = new TextExplorer($child, $childText);

						$childTitle = $childExplorer->getTitle();
						$childDescription = $childExplorer->getTitle([$childTitle]);

						if ($childTitle) {
							$siteLink = $result->siteLinks[] = new GoogleSearchResultSiteLink();
							$siteLink->index = $childIndex++;
							$siteLink->title = $this->correctText($childTitle);
							$siteLink->description = $this->correctText($childDescription);
							$siteLink->href = $child->targetLink;
						}
					}
				}

				$results[] = $result;
			}
		}

		return $results;
	}

	/**
	 *
	 * @param HTMLDocumentNode $element
	 * @param HTMLDocumentNode|null $stopAt
	 * @return HTMLDocumentNode[]
	 */
	private function getTextElements(HTMLDocumentNode $element, $stopAt, &$results = [], $first = true) {
		$text = $this->getText($element);

		if (is_string($text) && strlen($text) > 0) {
			$results[] = $element->tag === 'text' ? $element->parent : $element;
			return false;
		}

		foreach ($element->nodes as $node) {
			if ($node === $stopAt || $node->parent === $stopAt) {
				break;
			}

			$text = $this->getText($node);

			if (is_string($text) && strlen($text) > 0) {
				$results[] = $element->tag === 'text' ? $element->parent : $element;
				break;
			}
			else if (is_null($text) && !is_null($node->nodes)) {
				foreach ($node->nodes as $innerNode) {
					if ($this->getTextElements($innerNode, $stopAt, $results, false) === false) {
						break;
					}
				}
			}
		}

		if ($first) {
			foreach ($results as $index => $element) {
				$parents = $this->getParentChain($element);
				foreach ($parents as $parent) {
					if (in_array($parent, $results, true)) {
						// unset($results[$index]);
						break;
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Retrieves the text from the given node.
	 *
	 * @param HTMLDocumentNode $element
	 * @return string
	 */
	private function getText(HTMLDocumentNode $element) {
		if ($element->tag === 'br') return '';
		if (isset($element->_[5])) {
			return $element->_[5];
		}

		switch ($element->nodetype) {
			case 3: return $this->document->restoreNoise($element->_[4]);
			case 2: return '';
			case 6: return '';
		}

		if (strcasecmp($element->tag, 'script') === 0) return '';
		if (strcasecmp($element->tag, 'style') === 0) return '';

		return null;
	}

	/**
	 * Returns an array of clickthrough rows.
	 *
	 * @return ClickThroughRow[]
	 */
	private function getClickThroughRows() {
		$tree = new GoogleSearchParserTree();
		$parentHashes = [];
		$parentChains = [];

		/** @var ClickThroughRow[] */
		$rows = [];

		/** @var ClickThroughRow[] */
		$resultRows = [];

		// Find and record all parents for each target element
		// We'll also track the number of times we hit each parent for the next step
		foreach ($this->clickThroughLinks as $targetLinkElement) {
			$parents = [];
			$parentTarget = $targetLinkElement;

			while (!is_null($parent = $parentTarget->parent)) {
				array_unshift($parents, $parent);
				$parentTarget = $parent;
				$parentHash = spl_object_hash($parent);

				if (!array_key_exists($parentHash, $parentHashes)) {
					$parentHashes[$parentHash] = 0;
				}

				$parentHashes[$parentHash]++;
			}

			$parentChains[spl_object_hash($targetLinkElement)] = $parents;
		}

		// Iterate over links and find their highest distinct parent
		// For search results, each row of results will be the highest distinct parent
		// Note that other non-search result elements may still be contaminating the data set at this point
		foreach ($this->clickThroughLinks as $targetLinkElement) {
			$parents = $parentChains[spl_object_hash($targetLinkElement)];

			$highestDistinctParent = null;
			$highestDistinctParentIndex = null;

			for ($index = count($parents) - 1; $index > 0; $index--) {
				$parent = $parents[$index];
				$parentHash = spl_object_hash($parent);

				if ($parentHashes[$parentHash] > 1) {
					break;
				}

				$highestDistinctParent = $parent;
				$highestDistinctParentIndex = $index;
			}

			// Filter elements with no unique parent - these cannot be search results
			if (is_null($highestDistinctParent)) {
				continue;
			}

			$targetLink = $this->getClickThroughLinkRedirect($targetLinkElement);

			// Build the row
			$row = $rows[] = new ClickThroughRow();
			$row->rowElement = $highestDistinctParent;
			$row->rowElementIndex = $highestDistinctParentIndex;
			$row->targetLinkElement = $targetLinkElement;
			$row->targetLinkParents = $parents;
			$row->targetLink = $targetLink;

			// Add the row to the tree
			$tree->addRow($row);
		}

		foreach ($rows as $row) {
			$target = $row->rowElement;
			$originalParentChain = [];

			while (!is_null($target = $target->parent)) {
				$matches = $tree->getDirectDescendantRows($target);
				$matches = array_filter($matches, function($element) use ($row) {
					return $element->rowElement !== $row->rowElement;
				});

				$originalParentChain[] = [$target, $matches];
			}

			$sortedParentChain = array_values($originalParentChain);
			uasort($sortedParentChain, function($a, $b) {
				$a = count($a[1]);
				$b = count($b[1]);

				if ($a === $b) return 0;
				return $a < $b ? 1 : -1;
			});

			list($resultContainerElement, $resultContainerDescendants) = reset($sortedParentChain);
			$resultContainerIndex = key($sortedParentChain);

			if ($resultContainerIndex > 0) {
				$slice = array_slice($originalParentChain, 0, $resultContainerIndex);
				$sortedSlice = array_values($slice);

				uasort($sortedSlice, function($a, $b) {
					$a = count($a[1]);
					$b = count($b[1]);

					if ($a === $b) return 0;
					return $a < $b ? -1 : 1;
				});

				// Find the best index to merge into
				// NOTE: It might be better to find the last element in the slice with the lowest count
				$bestCandidateIndex = count($slice) - 1;
				$bestCandidateCount = count($slice[$bestCandidateIndex]);

				$newTargetElement = $slice[$bestCandidateIndex][0];
				$existingRow = $tree->getRowAt($newTargetElement);

				if ($existingRow !== null) {
					$existingRow->children[] = $row;
				}
				else {
					$row->rowElement = $newTargetElement;
					$row->rowElementIndex -= $bestCandidateIndex + 1;
					$tree->updateRow($row);

					$resultRows[] = $row;
				}

				continue;
			}
			else {
				$resultRows[] = $row;
			}
		}

		return $resultRows;
	}

	/**
	 * Returns all clickthrough link elements.
	 *
	 * @return HTMLDocumentNode[]
	 */
	private function getClickThroughLinks() {
		$links = [];

		foreach ($this->clickThroughAttributes as $attributeName) {
			$matches = $this->document->find("a[{$attributeName}^=/url?]");
			$eligible = [];

			foreach ($matches as $match) {
				$value = $match->getAttribute($attributeName);

				// Skip links to webcache
				// This is extremely important, because such links add noise and confuse the distinct-parent tree
				// algorithm, which can produce unintended results
				if (preg_match('/(&|&amp;)url=https?:\/\/translate\.google\.com\//m', $value)) continue;
				if (preg_match('/(&|&amp;)url=https?:\/\/\w+cache\.googleusercontent\.com\//m', $value)) continue;

				$eligible[] = $match;
			}

			$links += $eligible;
		}

		return $links;
	}

	/**
	 * Checks the given HTML for clickthrough attributes and returns the attribute names that were detected.
	 *
	 * @param string $html
	 * @return string[]
	 */
	private function getClickThroughAttributes($html) {
		$attributeNames = [];
		preg_match_all('/\b([^=\s]+)=["\'](\/url\?[^"\']+)[\'"]/', $html, $clickThroughResults, PREG_SET_ORDER);

		foreach ($clickThroughResults as $result) {
			if (!array_key_exists($result[1], $attributeNames)) {
				$attributeNames[$result[1]] = true;
			}
		}

		return array_keys($attributeNames);
	}

	/**
	 * Returns the URL that the given clickthrough link will redirect to or `null` if parsing failed.
	 *
	 * @param HTMLDocumentNode $link
	 * @return string|null
	 */
	private function getClickThroughLinkRedirect(HTMLDocumentNode $link) {
		$value = null;

		foreach ($this->clickThroughAttributes as $attributeName) {
			if ($link->hasAttribute($attributeName)) {
				$value = $link->getAttribute($attributeName);
				break;
			}
		}

		if (is_null($value)) {
			return null;
		}

		if (!preg_match('/[\?&]([^=]+)=((?:https:\/\/|http:\/\/|\/\/)[^&]+)/', $value, $matches)) {
			return null;
		}

		return html_entity_decode(urldecode($matches[2]), ENT_QUOTES);
	}

	/**
	 * Returns an array of all parents for an element.
	 *
	 * @param HTMLDocumentNode $element
	 * @return HTMLDocumentNode[]
	 */
	private function getParentChain(HTMLDocumentNode $element) {
		$parents = [];
		$parentTarget = $element;

		while (!is_null($parent = $parentTarget->parent)) {
			array_unshift($parents, $parent);
			$parentTarget = $parent;
		}

		return $parents;
	}

	/**
	 * Returns the deepest parent in common for all of the given elements. Returns `null` if there is no common parent.
	 *
	 * @param HTMLDocumentNode[] $elements
	 * @return HTMLDocumentNode
	 */
	private function getDeepestCommonParent($elements) {
		$parentHitsHashed = [];
		$parentInstances = [];

		foreach ($elements as $element) {
			$chain = $this->getParentChain($element);

			foreach ($chain as $parentElement) {
				$hash = spl_object_hash($parentElement);

				if (!array_key_exists($hash, $parentHitsHashed)) {
					$parentHitsHashed[$hash] = 1;
					$parentInstances[$hash] = $parentElement;
				}
				else {
					$parentHitsHashed[$hash]++;
				}
			}
		}

		$lowestCount = PHP_INT_MAX;
		$lowestElement = null;

		foreach ($parentHitsHashed as $parentHash => $count) {
			$parentElement = $parentInstances[$parentHash];

			if ($count <= $lowestCount) {
				$lowestElement = $parentElement;
				$lowestCount = $count;
			}
		}

		return $lowestElement;
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

	/**
	 * Extracts the number of total results from the page if possible. Falls back to reporting the number of results
	 * available in the current data set.
	 *
	 * @return int
	 */
	private function extractNumResults() {
		$nodes = $this->getTextNodesBeforeElement($this->document->find('body', 0), $this->container);

		foreach ($nodes as $node) {
			$text = trim($node->getPlainText());

			if (preg_match('/\b([\d,.]+)\b/m', $text, $matches)) {
				$number = intval(preg_replace('/[^\d]/', '', $matches[1]));

				return $number;
			}
		}

		return count($this->results);
	}

	/**
	 * Returns all text nodes that occur in the given element until the second element is reached. You can read the
	 * text within these nodes using `$node->getPlainText()`.
	 *
	 * @param HTMLDocumentNode $element
	 * @param HTMLDocumentNode|null $stopAtElement
	 * @return HTMLDocumentNode[]
	 */
	private function getTextNodesBeforeElement(HTMLDocumentNode $element, $stopAtElement, $isInner = false, &$nodes = []) {
		foreach ($element->nodes as $node) {
			if ($node->nodetype === 3) {
				$nodes[] = $node;
			}

			foreach ($node->nodes as $node) {
				if ($node === $stopAtElement) {
					return $isInner ? false : $nodes;
				}

				$children = $this->getTextNodesBeforeElement($node, $stopAtElement, true, $nodes);

				if (false === $children) {
					return $isInner ? false : $nodes;
				}
			}
		}

		return $nodes;
	}

}
