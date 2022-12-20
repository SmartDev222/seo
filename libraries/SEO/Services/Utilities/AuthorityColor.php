<?php

namespace SEO\Services\Utilities;

class AuthorityColor {

	public static function getColor($score) {
		if (!$score) {
			$score = 0;
		}

		$colors = [
			'green' => [0, 150, 0],
			'yellow' => [184, 226, 70],
			'orange' => [236, 187, 39],
			'red' => [180, 0, 0]
		];

		if ($score < 25) {
			return static::tween(0, 25, $colors['red'], $colors['orange'], $score);
		}

		else if ($score < 50) {
			return static::tween(25, 50, $colors['orange'], $colors['yellow'], $score);
		}

		else {
			return static::tween(50, 100, $colors['yellow'], $colors['green'], $score);
		}
	}

	private static function tween($start, $end, $colorStart, $colorEnd, $value) {
		$proportion = ($value - $start) / ($end - $start);

		$red = static::scale($colorStart[0], $colorEnd[0], $proportion);
		$green = static::scale($colorStart[1], $colorEnd[1], $proportion);
		$blue = static::scale($colorStart[2], $colorEnd[2], $proportion);

		return sprintf("#%02x%02x%02x", $red, $green, $blue);
	}

	private static function scale($a, $b, $proportion) {
		return floor($a + ($b - $a) * $proportion);
	}

}
