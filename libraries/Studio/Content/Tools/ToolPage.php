<?php

namespace Studio\Content\Tools;

use Exception;
use Studio\Base\Studio;
use Studio\Content\Icons\IconManager;
use Studio\Content\Icons\IconSet;
use Studio\Tools\Tool;

class ToolPage {

	/**
	 * The studio instance this page belongs to.
	 *
	 * @var Studio
	 */
	private $studio;

	/**
	 * The tool that this page is for.
	 *
	 * @var Tool
	 */
	private $tool;

	/**
	 * The language locale to target.
	 *
	 * @var string
	 */
	private $locale;

	/**
	 * Constructs a new `ToolPage` instance for the given tool.
	 *
	 * @param Tool $tool
	 * @param string $language The target language locale (such as `en-us`)
	 * @return void
	 */
	public function __construct(Tool $tool, $language) {
		global $studio;

		$this->studio = $studio;
		$this->tool = $tool;
		$this->locale = $language;
	}

	/**
	 * Returns true if this page has been customized.
	 *
	 * @return bool
	 */
	public function hasCustomizations() {
		$icons = json_decode($this->studio->getopt('tool-icons', '{}'), true);
		if (isset($icons[$this->tool->id])) return true;

		$titles = json_decode($this->studio->getopt('tool-page-titles-' . $this->locale, '{}'), true);
		if (isset($titles[$this->tool->id])) return true;

		return !empty($this->getMetaTags());
	}

	/**
	 * Returns a path to the icon to use for the tool.
	 *
	 * @return string
	 */
	public function getIcon() {
		$icons = json_decode($this->studio->getopt('tool-icons', '{}'), true);

		if (isset($icons[$this->tool->id])) {
			return 'resources/iconsets/' . $icons[$this->tool->id] . '.png';
		}
		else if (($translated = IconManager::translateOldIcon($this->tool->icon)) !== null) {
			return 'resources/iconsets/' . $translated . '.png';
		}

		return 'resources/icons/' . $this->tool->icon . '.png';
	}

	/**
	 * Sets the icon for this tool.
	 *
	 * @param string $theme The name of the icon set.
	 * @param int|string $id The numerical ID of the icon in the set.
	 * @return void
	 */
	public function setIcon($theme, $id) {
		$icons = json_decode($this->studio->getopt('tool-icons', '{}'), true);
		$icons[$this->tool->id] = $theme . '/' . $id;
		$this->studio->setopt('tool-icons', json_encode($icons));
	}

	/**
	 * Returns an object containing:
	 *
	 * - `set` – The icon set this tool is currently using.
	 * - `id` – The identifier of the icon within the set or a blank string if not found.
	 *
	 * @return object
	 */
	public function getIconFromSet() {
		$sets = IconManager::getIconSets();
		$icons = json_decode($this->studio->getopt('tool-icons', '{}'), true);
		$dirname = null;
		$basename = null;

		if (isset($icons[$this->tool->id])) {
			$icon = $icons[$this->tool->id];
			$dirname = dirname($icon);
			$basename = basename($icon);
		}
		else if (($translated = IconManager::translateOldIcon($this->tool->icon)) !== null) {
			$dirname = dirname($translated);
			$basename = basename($translated);
		}

		if ($dirname !== null) {
			foreach ($sets as $set) {
				if ($set->dirName === $dirname) {
					return (object) [
						'set' => $set,
						'id' => intval($basename)
					];
				}
			}
		}

		return (object) [
			'set' => $sets[0],
			'id' => ''
		];
	}

	/**
	 * Returns the title of the page or a blank string if not set.
	 *
	 * @return string
	 */
	public function getTitle() {
		$titles = json_decode($this->studio->getopt('tool-page-titles-' . $this->locale, '{}'), true);

		if (isset($titles[$this->tool->id])) {
			return $titles[$this->tool->id];
		}

		return '';
	}

	/**
	 * Sets the title of the page.
	 *
	 * @param string $title The new title.
	 * @return void
	 */
	public function setTitle($title) {
		$titles = json_decode($this->studio->getopt('tool-page-titles-' . $this->locale, '{}'), true);
		$titles[$this->tool->id] = trim($title);

		if (empty($title)) {
			unset($titles[$this->tool->id]);
		}

		$this->studio->setopt('tool-page-titles-' . $this->locale, json_encode($titles));
	}

	/**
	 * Returns an array of meta tags for this page.
	 *
	 * @return array
	 */
	public function getMetaTags() {
		$data = json_decode($this->studio->getopt('tool-page-meta-' . $this->locale, '{}'), true);
		$tags = isset($data[$this->tool->id]) ? $data[$this->tool->id] : [];

		foreach ($tags as $key => $value) {
			if (is_string($value)) {
				$tags[$key] = trim(preg_replace('/ *(?:\r?\n)+/m', ' ', $value), ',');
			}
		}

		return $tags;
	}

	/**
	 * Returns the value of the specified meta tag.
	 *
	 * @param string $tagName
	 * @param string $default
	 * @return string
	 */
	public function getMetaTag($tagName, $default = '') {
		$tags = $this->getMetaTags();
		$tagName = strtolower($tagName);

		if (array_key_exists($tagName, $tags)) {
			return $tags[$tagName];
		}

		return $default;
	}

	/**
	 * Sets the value of the specified meta tag.
	 *
	 * @param string $name
	 * @param string $content
	 * @return void
	 */
	public function setMetaTag($name, $content) {
		$data = json_decode($this->studio->getopt('tool-page-meta-' . $this->locale, '{}'), true);
		$meta = $this->getMetaTags();

		$name = strtolower($name);
		$content = trim($content);

		$meta[$name] = $content;

		if (empty($content)) {
			unset($meta[$name]);
		}

		$data[$this->tool->id] = $meta;
		$this->studio->setopt('tool-page-meta-' . $this->locale, json_encode($data));
	}

	/**
	 * Returns the custom HTML to include at the top of the tool's page.
	 *
	 * @return string
	 */
	public function getTopHTML() {
		return $this->readCustomFile('top');
	}

	/**
	 * Sets the HTML content for the top portion of the tool's page.
	 *
	 * @param string $html
	 * @return void
	 */
	public function setTopHTML($html) {
		$this->writeCustomFile('top', $html);
	}

	/**
	 * Returns the custom HTML to include at the bottom of the tool's page.
	 *
	 * @return string
	 */
	public function getBottomHTML() {
		return $this->readCustomFile('bottom');
	}

	/**
	 * Sets the HTML content for the bottom portion of the tool's page.
	 *
	 * @param string $html
	 * @return void
	 */
	public function setBottomHTML($html) {
		$this->writeCustomFile('bottom', $html);
	}

	/**
	 * Returns the content inside the custom file with the specified name.
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception
	 */
	private function readCustomFile($name) {
		$dir = $this->studio->basedir . '/resources/templates/custom';
		$target = $dir . '/' . $this->tool->id . '-' . $name . '-' . $this->locale . '.html';

		if (file_exists($target)) {
			return file_get_contents($target);
		}

		return '';
	}

	/**
	 * Writes the given content to the custom file with the specified name.
	 *
	 * @param string $name
	 * @param string $content
	 * @return void
	 * @throws Exception
	 */
	private function writeCustomFile($name, $content) {
		$dir = $this->studio->basedir . '/resources/templates/custom';
		$target = $dir . '/' . $this->tool->id . '-' . $name . '-' . $this->locale . '.html';

		if (empty($content)) {
			if (file_exists($target)) {
				if (unlink($target) === false) {
					throw new Exception('Error when deleting file: ' . $target);
				}
			}

			return;
		}

		if (!file_exists($dir)) {
			if (!mkdir($dir, 0777, true)) {
				throw new Exception('Error when creating directory: ' . $dir);
			}
		}

		if (file_put_contents($target, $content) === false) {
			throw new Exception('Error when writing to file: ' . $target);
		}
	}

}
