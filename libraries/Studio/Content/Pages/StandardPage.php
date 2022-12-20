<?php

namespace Studio\Content\Pages;

use Exception;
use Studio\Base\Studio;

class StandardPage {

	/**
	 * The studio instance this page belongs to.
	 *
	 * @var Studio
	 */
	private $studio;

	/**
	 * The identifier for this page.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The language for this page.
	 *
	 * @var string
	 */
	public $locale;

	/**
	 * @param string $id
	 * @param string $locale
	 * @return void
	 */
	public function __construct($id, $locale) {
		global $studio;

		$this->studio = $studio;
		$this->id = $id;
		$this->locale = $locale;
	}


	/**
	 * Returns the title of the page or a blank string if not set.
	 *
	 * @return string
	 */
	public function getTitle() {
		$titles = json_decode($this->studio->getopt('standard-page-titles-' . $this->locale, '{}'), true);
		$languageFile = $this->studio->basedir . '/resources/languages/' . $this->locale . '/pages.json';

		if (isset($titles[$this->id])) {
			return $titles[$this->id];
		}

		if (file_exists($languageFile)) {
			$data = json_decode(file_get_contents($languageFile), true);

			if ($this->id === 'home' && $this->studio->getopt('title-home')) {
				if (isset($data['@PageTitle:Home'])) {
					return $data['@PageTitle:Home'];
				}
			}

			if ($this->id === 'tools' && $this->studio->getopt('title-tools')) {
				if (isset($data['@PageTitle:Tools'])) {
					return $data['@PageTitle:Tools'];
				}
			}
		}

		if ($this->id === 'home') return 'Home';
		if ($this->id === 'tools') return 'Tools';

		return '';
	}

	/**
	 * Sets the title of the page.
	 *
	 * @param string $title The new title.
	 * @return void
	 */
	public function setTitle($title) {
		$titles = json_decode($this->studio->getopt('standard-page-titles-' . $this->locale, '{}'), true);
		$titles[$this->id] = trim($title);

		if ($title === '') {
			unset($titles[$this->id]);
		}

		$this->studio->setopt('standard-page-titles-' . $this->locale, json_encode($titles));
	}

	/**
	 * Returns an array of meta tags for this page.
	 *
	 * @return array
	 */
	public function getMetaTags() {
		$data = json_decode($this->studio->getopt('standard-page-meta-' . $this->locale, '{}'), true);
		$tags = isset($data[$this->id]) ? $data[$this->id] : [];

		if (!array_key_exists('description', $tags)) {
			if ($this->id === 'home' && $this->studio->getopt('description-home')) {
				$tags['description'] = $this->studio->getopt('description-home');
			}

			if ($this->id === 'tools' && $this->studio->getopt('description-tools')) {
				$tags['description'] = $this->studio->getopt('description-tools');
			}
		}

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
		$data = json_decode($this->studio->getopt('standard-page-meta-' . $this->locale, '{}'), true);
		$meta = $this->getMetaTags();

		$name = strtolower($name);
		$content = trim($content);

		$meta[$name] = $content;

		if ($content === '') {
			unset($meta[$name]);
		}

		$data[$this->id] = $meta;
		$this->studio->setopt('standard-page-meta-' . $this->locale, json_encode($data));
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
	 * Returns the custom HTML to include at the middle of the tool's page.
	 *
	 * @return string
	 */
	public function getMiddleHTML() {
		return $this->readCustomFile('middle');
	}

	/**
	 * Sets the HTML content for the middle portion of the tool's page.
	 *
	 * @param string $html
	 * @return void
	 */
	public function setMiddleHTML($html) {
		$this->writeCustomFile('middle', $html);
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
		$target = $dir . '/std-' . $this->id . '-' . $name . '-' . $this->locale . '.html';

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
		$target = $dir . '/std-' . $this->id . '-' . $name . '-' . $this->locale . '.html';

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
