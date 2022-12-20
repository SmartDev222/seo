<?php

namespace Studio\Content;

class AdsManager {

	public static $numBannersPlaced = 0;

	/**
	 * Returns true if the specified banner size is enabled and would not surpass the maximum ad banner count.
	 *
	 * You can also pass an array of multiple sizes, in which case true will be returned if any of them are eligible.
	 *
	 * @param string|string[] $size
	 * @return bool
	 */
	public static function enabled($size) {
		global $studio, $account;

		if (static::$numBannersPlaced < static::getMaxBannerCount()) {
			$group = $account->group();

			if ($group !== null && !static::getPreviewMode()) {
				$groupStates = json_decode($studio->getopt('ads-disabled-groups', '{}'), true);

				if (isset($groupStates[$group['id']]) && $groupStates[$group['id']]) {
					return false;
				}
			}

			return !empty(static::get($size));
		}
	}

	/**
	 * Returns the HTML code for the specified banner size or a blank string if not enabled.
	 *
	 * You can pass multiple sizes as an array and the first available size will be returned.
	 *
	 * @param string|string[] $size
	 * @param string $classes
	 * @return string
	 */
	public static function get($size, $classes = '') {
		global $studio;

		if (is_array($size)) {
			foreach ($size as $name) {
				$value = $studio->getopt('ad-' . $name, '');

				if (empty($value) && static::getPreviewMode()) {
					if ($name === 'header' || $name === 'footer') {
						$value = '<div style="background-color: #dddddd; padding: 50px 30px; text-align: center; font-size: 28px; color: #999999; font-weight: 500;">Full width ' . $name . ' slot</div>';
					}
					else {
						$value = "<img src=\"https://placehold.co/$name\">";
					}
				}

				if (!empty($value)) {
					return "<div class=\"slot slot-$name $classes\"><div class=\"slot-inner\">$value</div></div>";
				}
			}

			return '';
		}

		$value = $studio->getopt('ad-' . $size, '');

		if (empty($value) && static::getPreviewMode()) {
			if ($size === 'header' || $size === 'footer') {
				$value = '<div style="background-color: #dddddd; padding: 50px 30px; text-align: center; font-size: 28px; color: #999999; font-weight: 500;">Full width ' . $size . ' slot</div>';
			}
			else {
				$value = "<img src=\"https://placehold.co/$size\">";
			}
		}

		if (!empty($value)) {
			return "<div class=\"slot slot-$size $classes\"><div class=\"slot-inner\">$value</div></div>";
		}

		return '';
	}

	/**
	 * Returns the maximum number of ads per page.
	 *
	 * @return int
	 */
	private static function getMaxBannerCount() {
		global $studio;
		$count = intval($studio->getopt('max-ads-per-page', '0'));
		if ($count <= 0) $count = 10000;
		return $count;
	}

	/**
	 * Returns true if we're in preview mode.
	 *
	 * @return bool
	 */
	private static function getPreviewMode() {
		global $studio;
		return $studio->getopt('ads-preview') === 'On';
	}

	/**
	 * Registers an intent and commitment to display an ad of the specified size. Returns the code snippet if the ad
	 * is available or false otherwise.
	 *
	 * When an array of sizes is passed, the first available size is returned.
	 *
	 * @param string|string[] $size
	 * @param string $classes
	 * @return bool
	 */
	public static function commit($size, $classes = '') {
		if (static::enabled($size)) {
			static::$numBannersPlaced++;
			return static::get($size, $classes);
		}

		return false;
	}

	/**
	 * Takes an array of ad banner sizes and returns the first one that is eligible for display, or null.
	 *
	 * Note: Returns the ad size (same as input), not the ad banner code.
	 *
	 * @param string[] $sizes
	 * @return string|null
	 */
	public static function resolve($sizes) {
		global $studio;

		foreach ($sizes as $size) {
			$value = $studio->getopt('ad-' . $size, '');

			if (!empty($value)) {
				return $size;
			}
		}
	}

}
