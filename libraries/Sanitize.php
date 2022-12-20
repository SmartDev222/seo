<?php

class Sanitize {

	/**
	 * Sanitizes the given output for use within an HTML attribute.
	 *
	 * @param mixed $output
	 * @param int $quotes Which style of quotes to escape (defaults to both).
	 * @return string
	 */
	public static function attribute($output, $quotes = QUOTES_SINGLE | QUOTES_DOUBLE) {
		if (is_string($output)) {
			if (($quotes & QUOTES_SINGLE) > 0) {
				$output = str_replace("'", '&apos;', $output);
			}

			if (($quotes & QUOTES_DOUBLE) > 0) {
				$output = str_replace('"', '&quot;', $output);
			}
		}

		return $output;
	}

	/**
	 * Sanitizes trusted output.
	 *
	 * @param mixed $output
	 * @return string
	 */
	public static function trusted($output) {
		return $output;
	}

	/**
	 * Sanitizes the output for inclusion in HTML. The angle bracket characters (<>) will be escaped.
	 *
	 * @param mixed $output
	 * @return string
	 */
	public static function html($output) {
		if (is_string($output)) {
			$output = str_replace('>', '&gt;', $output);
			$output = str_replace('<', '&lt;', $output);
		}

		return $output;
	}

	/**
	 * Sanitizes untrusted output for generic usage (within an attribute or HTML).
	 *
	 * @param mixed $output
	 * @return string
	 */
	public static function generic($output) {
		$output = static::attribute($output);
		$output = static::html($output);

		return $output;
	}

}

define('QUOTES_SINGLE', 1);
define('QUOTES_DOUBLE', 2);
