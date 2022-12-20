<?php

namespace Studio\Content\Tools;

use Studio\Tools\Tool;

class ToolPageManager {

	/**
	 * Returns the `ToolPage` instance for the given tool and language combination.
	 *
	 * @param Tool $tool
	 * @param string $language The target language locale (such as `en-us`)
	 * @return ToolPage
	 */
	public static function getPage(Tool $tool, $language) {
		return new ToolPage($tool, $language);
	}

}
