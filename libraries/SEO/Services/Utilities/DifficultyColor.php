<?php

namespace SEO\Services\Utilities;

class DifficultyColor {

	public static function getColor($score) {
		if (!$score) {
			$score = 0;
		}

		$colors = [
			'green' => [126, 184, 93],
			'orange' => [234, 160, 73],
			'red' => [232, 95, 63]
		];

		if ($score < 40) {
			return static::hex($colors['green']);
		}

		else if ($score <= 60) {
			return static::hex($colors['orange']);
		}

		else {
			return static::hex($colors['red']);
		}
	}

	private static function hex($color) {
		return sprintf("#%02x%02x%02x", $color[0], $color[1], $color[2]);
	}

}
