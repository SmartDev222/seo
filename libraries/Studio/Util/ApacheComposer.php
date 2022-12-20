<?php

namespace Studio\Util;

use Exception;
use Studio\Base\Permalink;

class ApacheComposer {

	private $path;
	private $lines = array();

	private $customRules = array();
	private $customLineStart;
	private $customLineEnd;

	private $rewriteEngineState = 'On';
	private $directorySlashState = 'Off';
	private $rewriteOptionsState = 'AllowNoSlash';

	public function __construct($filePath) {
		$this->path = $filePath;
		$this->_load();
	}

	public function getLines() {
		return $this->lines;
	}

	public function getOutsideLines() {
		if (is_null($this->customLineStart) || is_null($this->customLineEnd)) {
			return $this->lines;
		}

		$linesBefore = array_slice($this->lines, 0, $this->customLineStart);
		$linesAfter = array_slice($this->lines, $this->customLineEnd + 1);

		return array_merge($linesBefore, $linesAfter);
	}

	public function getInsideLines() {
		if (is_null($this->customLineStart) || is_null($this->customLineEnd)) {
			return array();
		}

		return array_slice($this->lines, $this->customLineStart, $this->customLineEnd - $this->customLineStart + 1);
	}

	private function _load() {
		if (file_exists($this->path)) {
			$contents = file_get_contents($this->path);
			$this->lines = explode("\n", str_replace("\r\n", "\n", $contents));

			$reachedFixes = false;

			foreach ($this->lines as $i => $line) {
				if (is_null($this->customLineStart)) {
					if (substr($line, 0, 20) === '### Begin permalinks') {
						$this->customLineStart = $i;
					}
				}
				else if (is_null($this->customLineEnd)) {
					$trimmed = trim($line);

					if (substr($trimmed, 0, 18) === '### End permalinks') {
						$this->customLineEnd = $i;
					}
					else if ($trimmed === '<IfModule mod_rewrite.c>' || $trimmed === '</IfModule>') {
						continue;
					}
					else if ($trimmed === '<IfModule mod_authz_core.c>') {
						continue;
					}
					else if (substr($trimmed, 0, 14) === 'RewriteOptions') {
						$this->rewriteOptionsState = substr($trimmed, 15);
					}
					else if ($trimmed === '# Fix for directory slashes' || $reachedFixes) {
						$reachedFixes = true;
						continue;
					}
					else if (substr($trimmed, 0, 13) === 'RewriteEngine') {
						$this->rewriteEngineState = substr($trimmed, 14);
					}
					else if (substr($trimmed, 0, 14) === 'DirectorySlash') {
						$this->directorySlashState = substr($trimmed, 15);
					}
					else if (!empty($trimmed)) {
						$this->customRules[] = $trimmed;
					}
				}
			}
		}
	}

	public function save() {
		if (is_null($this->customLineStart) && is_null($this->customLineEnd)) {
			if (count($this->lines) > 0 && !empty(trim($this->lines[count($this->lines) - 1]))) {
				$this->lines[] = '';
			}

			$this->customLineStart = count($this->lines);
			$this->customLineEnd = $this->customLineStart;
		}

		$linesBefore = array_slice($this->lines, 0, $this->customLineStart);
		$linesAfter = array_slice($this->lines, $this->customLineEnd + 1);
		$linesRules = array();
		$linesRules[] = '### Begin permalinks -- do not edit this section!';
		$linesRules[] = '<IfModule mod_rewrite.c>';
		$linesRules[] = '	RewriteEngine ' . $this->rewriteEngineState;
		$linesRules[] = '	DirectorySlash ' . $this->directorySlashState;
		$linesRules[] = '';

		foreach ($this->customRules as $line) {
			$linesRules[] = '	' . $line;
		}

		$linesRules[] = '';
		$linesRules[] = '	# Fix for directory slashes';
		$linesRules[] = '	<IfModule mod_authz_core.c>';

		if ($this->rewriteOptionsState) {
			$linesRules[] = '		RewriteOptions ' . $this->rewriteOptionsState;
			$linesRules[] = '';
		}

		$linesRules[] = '		RewriteCond %{REQUEST_FILENAME} -d';
		$linesRules[] = '		RewriteCond %{REQUEST_URI} ^(.+[^/])$';
		$linesRules[] = '		RewriteRule ^ %1/ [R,L,QSA]';
		$linesRules[] = '	</IfModule>';

		$linesRules[] = '</IfModule>';
		$linesRules[] = '### End permalinks';
		$lines = array_merge($linesBefore, $linesRules, $linesAfter);

		if (!empty(trim($lines[count($lines) - 1]))) {
			$lines[] = '';
		}

		$content = trim(implode("\n", $lines)) . "\n";
		if (@file_put_contents($this->path, $content) === false) {
			throw new Exception('Failed to write to file: ' . $this->path);
		}
	}

	/**
	 * Overwrites rewrite rules with new rules for the given links.
	 *
	 * @param Permalink[] $links
	 * @return void
	 */
	public function import($links) {
		$this->customRules = array();

		foreach ($links as $link) {
			$exp = $link->getApacheExp();
			$target = $link->getApacheTarget();

			$this->customRules[] = "RewriteRule $exp $target [NC,L,QSA]";
		}
	}

	/**
	 * Removes the custom content from the file.
	 *
	 * @return void
	 */
	public function remove() {
		if (is_null($this->customLineStart) && is_null($this->customLineEnd)) {
			if (!empty(trim($this->lines[count($this->lines) - 1]))) {
				$this->lines[] = '';
			}

			$this->customLineStart = count($this->lines);
			$this->customLineEnd = $this->customLineStart;
		}

		$linesBefore = array_slice($this->lines, 0, $this->customLineStart);
		$linesAfter = array_slice($this->lines, $this->customLineEnd + 1);
		$lines = array_merge($linesBefore, $linesAfter);

		if (!empty(trim($lines[count($lines) - 1]))) {
			$lines[] = '';
		}

		$content = trim(implode("\n", $lines)) . "\n";
		if (@file_put_contents($this->path, $content) === false) {
			throw new Exception('Failed to write to file: ' . $this->path);
		}
	}

}
