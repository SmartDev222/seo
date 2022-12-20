<?php

namespace Studio\Content\Icons;

class IconManager {

	/**
	 * Icon set cache.
	 *
	 * @var IconSet[]|null
	 */
	private static $sets = null;

	/**
	 * Returns an array of all available icon sets.
	 *
	 * @return IconSet[]
	 */
	public static function getIconSets() {
		global $studio;

		if (!static::$sets) {
			static::$sets = [
				new IconSet('Black', $studio->basedir . '/resources/iconsets/classic-black'),
				new IconSet('Blue', $studio->basedir . '/resources/iconsets/classic-blue'),
				new IconSet('Colored', $studio->basedir . '/resources/iconsets/classic-colored'),
				new IconSet('Flat', $studio->basedir . '/resources/iconsets/classic-flat')
			];
		}

		return static::$sets;
	}

	/**
	 * Translates an old tool icon name into the colored set.
	 *
	 * @return string|null
	 */
	public static function translateOldIcon($iconName) {
		switch ($iconName) {
			case 'advertising': return 'classic-colored/0';
			case 'alexa-rank': return 'classic-colored/34';
			case 'article': return 'classic-colored/3';
			case 'bing-serp': return 'classic-colored/74';
			case 'clean-code': return 'classic-colored/8';
			case 'competition': return 'classic-colored/27';
			case 'crawlability': return 'classic-colored/58';
			case 'extract-meta-tags': return 'classic-colored/69';
			case 'geo-targeting': return 'classic-colored/17';
			case 'google-serp': return 'classic-colored/73';
			case 'headers': return 'classic-colored/24';
			case 'high-quality-backlinks': return 'classic-colored/18';
			case 'indexed-pages': return 'classic-colored/63';
			case 'investment': return 'classic-colored/41';
			case 'keyword-density': return 'classic-colored/42';
			case 'keyword-research': return 'classic-colored/32';
			case 'link-analysis': return 'classic-colored/38';
			case 'mobile-support': return 'classic-colored/29';
			case 'new-backlinks': return 'classic-colored/25';
			case 'poor-backlinks': return 'classic-colored/71';
			case 'robots': return 'classic-colored/43';
			case 'robots-builder': return 'classic-colored/46';
			case 'sitemap': return 'classic-colored/70';
			case 'speed-test': return 'classic-colored/49';
			case 'submit-sitemaps': return 'classic-colored/72';
			case 'top-referrers': return 'classic-colored/31';
			case 'top-search-queries': return 'classic-colored/53';
		}
	}

}
