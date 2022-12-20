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
use Studio\Util\Parsers\HTMLDocumentNode;

class GoogleSearchParserTree {

	/**
	 * @var ClickThroughRow[][]
	 */
	private $parentIndexes = [];

	/**
	 * @var ClickThroughRow[][]
	 */
	private $parentDirectIndexes = [];

	/**
	 * @var array[]
	 */
	private $rowIndexes = [];

	/**
	 * @var ClickThroughRow[]
	 */
	private $rowElements = [];

	/**
	 * Adds the specified row to the tree.
	 *
	 * @param ClickThroughRow $row
	 * @return void
	 */
	public function addRow(ClickThroughRow $row) {
		$this->rowIndexes[spl_object_hash($row)] = $this->indexRow($row);
	}

	/**
	 * Updates internal records of the given row's parents after it has moved.
	 *
	 * @param ClickThroughRow $row
	 * @return void
	 */
	public function updateRow(ClickThroughRow $row) {
		$rowHash = spl_object_hash($row);
		$record = $this->rowIndexes[$rowHash];

		if ($record['rowElementHash'] !== spl_object_hash($row->rowElement)) {
			foreach ($record['parents'] as $parent) {
				$parentHash = spl_object_hash($parent);
				unset($this->parentIndexes[$parentHash][$rowHash]);
			}

			$directParentHash = spl_object_hash($record['directParent']);
			unset($this->rowElements[$record['rowElementHash']]);
			unset($this->parentDirectIndexes[$directParentHash][$rowHash]);

			$this->rowIndexes[spl_object_hash($row)] = $this->indexRow($row);
		}
	}

	/**
	 * Indexes the given row and returns an array of information.
	 *
	 * @param ClickThroughRow $row
	 * @return array
	 */
	private function indexRow(ClickThroughRow $row) {
		$parents = [];
		$parentTarget = $row->rowElement;
		$rowHash = spl_object_hash($row);

		while (!is_null($parent = $parentTarget->parent)) {
			array_unshift($parents, $parent);

			$parentTarget = $parent;
			$parentHash = spl_object_hash($parent);

			if (!array_key_exists($parentHash, $this->parentIndexes)) {
				$this->parentIndexes[$parentHash] = [];
			}

			$this->parentIndexes[$parentHash][$rowHash] = $row;
		}

		$directParent = $row->rowElement->parent;
		$directParentHash = spl_object_hash($directParent);
		$this->parentDirectIndexes[$directParentHash][$rowHash] = $row;

		$rowElementHash = spl_object_hash($row->rowElement);
		$this->rowElements[$rowElementHash] = $row;

		return [
			'row' => $row,
			'rowElementHash' => $rowElementHash,
			'parents' => $parents,
			'directParent' => $directParent
		];
	}

	/**
	 * Returns all rows directly under the specified node.
	 *
	 * @param HTMLDocumentNode $node
	 * @return ClickThroughRow[]
	 */
	public function getDirectDescendantRows(HTMLDocumentNode $node) {
		$nodeHash = spl_object_hash($node);

		if (array_key_exists($nodeHash, $this->parentDirectIndexes)) {
			return array_values($this->parentDirectIndexes[$nodeHash]);
		}

		return [];
	}

	/**
	 * Returns all rows under the specified node (including those nested within children).
	 *
	 * @param HTMLDocumentNode $node
	 * @return ClickThroughRow[]
	 */
	public function getDescendantRows(HTMLDocumentNode $node) {
		$nodeHash = spl_object_hash($node);

		if (array_key_exists($nodeHash, $this->parentDirectIndexes)) {
			return array_values($this->parentIndexes[$nodeHash]);
		}

		return [];
	}

	/**
	 * Returns an array of parent elements for the given row. The elements will be ordered from top down, meaning the
	 * first element is the document root.
	 *
	 * @param ClickThroughRow $row
	 * @return HTMLDocumentNode[]
	 */
	public function getParents(ClickThroughRow $row) {
		$rowHash = spl_object_hash($row);

		if (array_key_exists($rowHash, $this->rowIndexes)) {
			return $this->rowIndexes[$rowHash]['parents'];
		}

		return [];
	}

	/**
	 * Returns the parent element for the given row.
	 *
	 * @param ClickThroughRow $row
	 * @return HTMLDocumentNode
	 */
	public function getDirectParent(ClickThroughRow $row) {
		$rowHash = spl_object_hash($row);

		if (array_key_exists($rowHash, $this->rowIndexes)) {
			return $this->rowIndexes[$rowHash]['directParent'];
		}

		throw new \Exception('Direct parent not found. This is a logic error!');
	}

	/**
	 * Returns the row whose `rowElement` matches the given element or `null` if not found.
	 *
	 * @param HTMLDocumentNode $element
	 * @return ClickThroughRow|null
	 */
	public function getRowAt(HTMLDocumentNode $element) {
		$hash = spl_object_hash($element);

		if (array_key_exists($hash, $this->rowElements)) {
			return $this->rowElements[$hash];
		}
	}

}
