<?php

namespace Studio\Util;

class StyleComposer {

	private $path;
	private $lines;

	private $customRules = array();
	private $customLineStart;
	private $customLineEnd;

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

			foreach ($this->lines as $i => $line) {
				if (is_null($this->customLineStart)) {
					if (substr($line, 0, 22) === '/* Customization start') {
						$this->customLineStart = $i;
					}
				}
				else if (is_null($this->customLineEnd)) {
					$trimmed = trim($line);

					if (substr($line, 0, 20) === '/* Customization end') {
						$this->customLineEnd = $i;
					}
					else if (!empty($trimmed)) {
						// This line should be a rule - parse it
						if (preg_match('/^\/\*\s([a-f0-9]+)\s\*\/\s([^{]+)\s+\{\s([^:]+):\s#([a-z0-9]+);\s\}\s*$/', $trimmed, $matches)) {
							$this->customRules[($matches[1])] = array(
								'selector' => $matches[2],
								'rule' => $matches[3],
								'color' => $matches[4]
							);
						}
					}
				}
			}
		}
	}

	public function save() {
		if (is_null($this->customLineStart) && is_null($this->customLineEnd)) {
			if (!empty(trim($this->lines[count($this->lines) - 1]))) {
				$this->lines[] = '';
			}

			$this->customLineStart = count($this->lines);
			$this->customLineEnd = $this->customLineStart;
		}

		$linesBefore = array_slice($this->lines, 0, $this->customLineStart);
		$linesAfter = array_slice($this->lines, $this->customLineEnd + 1);
		$linesRules = array();
		$linesRules[] = '/* Customization start -- do not edit this section */';

		foreach ($this->customRules as $hash => $rule) {
			$linesRules[] = "/* {$hash} */ {$rule['selector']} { {$rule['rule']}: #{$rule['color']}; }";
		}

		$linesRules[] = '/* Customization end */';
		$lines = array_merge($linesBefore, $linesRules, $linesAfter);

		if (!empty(trim($lines[count($lines) - 1]))) {
			$lines[] = '';
		}

		$content = implode("\n", $lines);
		@file_put_contents($this->path, $content);
	}

	public function get($key, $config) {
		if ($color = $this->_getRule($key, 'background')) {
			return $color;
		}

		if ($color = $this->_getRule($key, 'foreground')) {
			return $color;
		}

		if ($color = $this->_getRule($key, 'border')) {
			return $color;
		}

		if ($color = $this->_getRule($key, 'borderBottom')) {
			return $color;
		}

		return $config['default'];
	}

	public function add($key, $config, $color) {
		if (isset($config['background'])) {
			$this->_addBackground($key, $config['background'], $color);
		}

		if (isset($config['border'])) {
			$this->_addBorder($key, $config['border'], $color);
		}

		if (isset($config['foreground'])) {
			$this->_addForeground($key, $config['foreground'], $color);
		}

		if (isset($config['borderBottom'])) {
			$this->_addBorderBottom($key, $config['borderBottom'], $color);
		}
	}

	public function remove($key, $config) {
		if (isset($config['background'])) {
			$this->_removeRule($key, 'background');
		}

		if (isset($config['border'])) {
			$this->_removeRule($key, 'border');
		}

		if (isset($config['foreground'])) {
			$this->_removeRule($key, 'foreground');
		}

		if (isset($config['borderBottom'])) {
			$this->_removeRule($key, 'borderBottom');
		}
	}

	private function _addBackground($key, $selector, $color) {
		$hash = md5($key . '~background');
		$this->customRules[$hash] = array(
			'selector' => $selector,
			'rule' => 'background-color',
			'color' => $color
		);
	}

	private function _addForeground($key, $selector, $color) {
		$hash = md5($key . '~foreground');
		$this->customRules[$hash] = array(
			'selector' => $selector,
			'rule' => 'color',
			'color' => $color
		);
	}

	private function _addBorder($key, $selector, $color) {
		$hash = md5($key . '~border');
		$this->customRules[$hash] = array(
			'selector' => $selector,
			'rule' => 'border-color',
			'color' => $color
		);
	}

	private function _addBorderBottom($key, $selector, $color) {
		$hash = md5($key . '~borderBottom');
		$this->customRules[$hash] = array(
			'selector' => $selector,
			'rule' => 'border-bottom-color',
			'color' => $color
		);
	}

	private function _removeRule($key, $type) {
		$hash = md5($key . '~' . $type);

		if (isset($this->customRules[$hash])) {
			unset($this->customRules[$hash]);
		}
	}

	private function _getRule($key, $type) {
		$hash = md5($key . '~' . $type);

		if (isset($this->customRules[$hash])) {
			return $this->customRules[$hash]['color'];
		}
	}

}
