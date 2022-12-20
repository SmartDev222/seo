<?php

namespace Studio\Content\Icons;

class IconSet {

	/**
	 * The user-friendly name of the icon set.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Absolute path to the icon set's directory. The path is not guaranteed to exist.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * The name of the icon set's directory, commonly used to reference the icon set.
	 *
	 * @var string
	 */
	public $dirName;

	public function __construct($name, $path) {
		$this->name = $name;
		$this->path = $path;
		$this->dirName = basename($path);
	}

	/**
	 * Returns `true` if the icon set is installed.
	 *
	 * @return bool
	 */
	public function isInstalled() {
		return file_exists($this->path);
	}

	/**
	 * Returns a sorted array of icon IDs in this set.
	 *
	 * @return int[]
	 */
	public function getIcons() {
		$icons = [];

		if ($this->isInstalled()) {
			$files = scandir($this->path);

			foreach ($files as $file) {
				if (substr($file, 0, 1) !== '.' && substr($file, -4) === '.png') {
					$icons[] = intval(substr($file, 0, -4));
				}
			}
		}

		natsort($icons);

		return $icons;
	}

}
