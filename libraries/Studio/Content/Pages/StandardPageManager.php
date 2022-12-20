<?php

namespace Studio\Content\Pages;

class StandardPageManager {

	/**
	 * Retrieves a page in a specific language.
	 *
	 * @param string $name
	 * @param string $locale
	 * @return StandardPage
	 */
	public static function getPage($name, $locale) {
		return new StandardPage($name, $locale);
	}

}
