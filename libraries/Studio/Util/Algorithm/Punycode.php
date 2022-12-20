<?php

namespace Studio\Util\Algorithm;

use Exception;

/**
 * This is a Punycode converter that fully complies with RFC3492 and RFC5891. Punycode is a standard for domain name
 * internationalization, and allows domain names to appear as though they contain unicode characters.
 *
 * For example, the domain `名がドメイン.com` loads in a browser, but doesn't actually exist. Instead, this gets
 * converted to punycode, resulting in the domain name `xn--v8jxj3d1dzdz08w.com` which is what actually loads behind
 * the scenes.
 *
 * Adapted by Bailey from: https://github.com/bestiejs/punycode.js ❤
 */
class Punycode {

    private static $maxInt = 2147483647;
    private static $base = 36;
    private static $tMin = 1;
    private static $tMax = 26;
    private static $skew = 38;
    private static $damp = 700;
    private static $initialBias = 72;
    private static $initialN = 128;
    private static $delimiter = '-';
    private static $baseMinusTMin = 35;

    /**
     * Converts a string of unicode symbols to a punycode string. Only the non-ASCII parts of the string will be
     * converted (i.e. it doesn't matter if you call it with a domain that's already fully ASCII).
     *
     * @param string $input
     * @return string
     * @throws Exception
     */
    public static function encode($input) {
        $output = [];

        // Convert the input in UCS-2 to an array of unicode code points
        $input = self::ucs2Decode($input);

        // Cache the length
        $inputLength = count($input);

        // Initialize the state
        $n = self::$initialN;
        $delta = 0;
        $bias = self::$initialBias;

        // Handle basic code points
        foreach ($input as $currentValue) {
            if ($currentValue < 0x80) {
                $output[] = chr($currentValue);
            }
        }

        $basicLength = count($output);
        $handledCPCount = $basicLength;

        // `handledCPCount` is the number of code points that have been handled;
        // `basicLength` is the number of basic code points.

        // Finish the basic string with a delimiter unless it's empty
        if ($basicLength) {
            $output[] = self::$delimiter;
        }

        // Main encoding loop
        while ($handledCPCount < $inputLength) {
            // All non-basic code points < n have been handled already, find the next larger one
            $m = self::$maxInt;

            foreach ($input as $currentValue) {
                if ($currentValue >= $n && $currentValue <= $m) {
                    $m = $currentValue;
                }
            }

            // Increase `delta` enough to advance the decoder's <n,i> state to <m,0> but guard against overflow
            $handledCPCountPlusOne = $handledCPCount + 1;
            if ($m - $n > floor(self::$maxInt - $delta) / $handledCPCountPlusOne) {
                throw new Exception('Overflow');
            }

            $delta += ($m - $n) * $handledCPCountPlusOne;
            $n = $m;

            foreach ($input as $currentValue) {
                if ($currentValue < $n && ++$delta > self::$maxInt) {
                    throw new Exception('Overflow');
                }

                if ($currentValue == $n) {
                    // Represent delta as a generalized variable-length integer
                    $q = $delta;

                    for ($k = self::$base;; $k += self::$base) {
                        $t = $k <= $bias ? self::$tMin : ($k >= $bias + self::$tMax ? self::$tMax : $k - $bias);
                        if ($q < $t) break;

                        $qMinusT = $q - $t;
                        $baseMinusT = self::$base - $t;

                        $output[] = chr(self::digitToBasic($t + $qMinusT % $baseMinusT, 0));
                        $q = floor($qMinusT / $baseMinusT);
                    }

                    $output[] = chr(self::digitToBasic($q, 0));
                    $bias = self::adapt($delta, $handledCPCountPlusOne, $handledCPCount == $basicLength);
                    $delta = 0;
                    ++$handledCPCount;
                }
            }

            ++$delta;
            ++$n;
        }

        return implode('', $output);
    }

    /**
     * Converts a punycode string of ascii characters to a string of unicode symbols. Only the punycoded parts of the
     * input will be converted (i.e. it doesn't matter if you call it on a string that has already been converted to
     * unicode).
     *
     * @param string $input
     * @return string
     * @throws Exception
     */
    public static function decode($input) {
        // Convert to utf-16le
        $input = iconv(mb_detect_encoding($input), 'utf-16le', $input);

        // Initialize
        $output = [];
        $inputLength = strlen($input) / 2;
        $i = 0;
        $n = self::$initialN;
        $bias = self::$initialBias;

        // Handle the basic code points: let `basic` be the number of input code points before the last delimiter, or
        // `0` if there is none, then copy the first basic code points to the output.

        $basic = strrpos($input, self::$delimiter) / 2;
        if ($basic === false) $basic = 0;

        for ($j = 0; $j < $basic; ++$j) {
            // If it's not a basic code point
            if (self::charCodeAt($input, $j) >= 0x80) {
                throw new Exception('Not a basic character');
            }

            $output[] = self::charCodeAt($input, $j);
        }

        // Main decoding loop: start just after the last delimiter if any basic code points were copied
        // Start at the beginning otherwise.

        for ($index = $basic > 0 ? $basic + 1 : 0; $index < $inputLength;) {
            $oldi = $i;

            for ($w = 1, $k = self::$base;; $k += self::$base) {
                if ($index >= $inputLength) {
                    throw new Exception('Invalid input');
                }

                $digit = self::basicToDigit(self::charCodeAt($input, $index++));

                if ($digit >= self::$base || $digit > floor((self::$maxInt - $i) / $w)) {
                    throw new Exception('Overflow');
                }

                $i += $digit * $w;
                $t = $k <= $bias ? self::$tMin : ($k >= $bias + self::$tMax ? self::$tMax : $k - $bias);
                $baseMinusT = self::$base - $t;

                if ($digit < $t) break;
                if ($w > floor(self::$maxInt / $baseMinusT)) {
                    throw new Exception('Overflow');
                }

                $w *= $baseMinusT;
            }

            $out = count($output) + 1;
            $bias = self::adapt($i - $oldi, $out, $oldi == 0);

            if (floor($i / $out) > self::$maxInt - $n) {
                throw new Exception('Overflow');
            }

            $n += floor($i / $out);
            $i %= $out;

            array_splice($output, $i++, 0, $n);
        }

        $str = '';
        foreach ($output as $point) {
            $str .= mb_convert_encoding('&#' . intval($point) . ';', 'utf-8', 'html-entities');
        }

        return $str;
    }

    /**
     * Bias adaption function as per section 3.4 of RFC 3492.
     */
    private static function adapt($delta, $numPoints, $firstTime) {
        $k = 0;
        $delta = $firstTime ? floor($delta / self::$damp) : $delta >> 1;
        $delta += floor($delta / $numPoints);

        for (; $delta > self::$baseMinusTMin * self::$tMax >> 1; $k += self::$base) {
            $delta = floor($delta / self::$baseMinusTMin);
        }

        return floor($k + (self::$baseMinusTMin + 1) * $delta / ($delta + self::$skew));
    }

    /**
     * Converts a digit/integer into a basic code point.
     *
     * @param int $digit
     * @param int $flag
     * @return int
     */
    private static function digitToBasic($digit, $flag) {
        // 0..25 map to ASCII a..z or A..Z
        // 26..35 map to ASCII 0..9
        return $digit + 22 + 75 * ($digit < 26) - (($flag != 0) << 5);
    }

    /**
     * Converts a basic code point into a digit/integer.
     *
     * @param int $codePoint
     * @return int
     */
    private static function basicToDigit($codePoint) {
        if ($codePoint - 0x30 < 0x0A) return $codePoint - 0x16;
        if ($codePoint - 0x41 < 0x1A) return $codePoint - 0x41;
        if ($codePoint - 0x61 < 0x1A) return $codePoint - 0x61;

        return self::$base;
    }

    /**
     * Creates an array containing the numeric code points of each unicode character in the string. The resulting
     * code points will match UTF-16.
     *
     * @param string $string
     * @return int[]
     */
    private static function ucs2Decode($string) {
        $string = iconv(mb_detect_encoding($string), 'utf-16le', $string);
        $length = strlen($string) / 2;

        $output = [];
        $counter = 0;

        while ($counter < $length) {
            $value = self::charCodeAt($string, $counter++);

            if ($value >= 0xD800 && $value <= 0xDBFF && $counter < $length) {
                // It's a high surrogate, and there is a next character
                $extra = self::charCodeAt($string, $counter++);

                if (($extra & 0xFC00) == 0xDC00) {
                    // Low surrogate
                    $output[] = (($value & 0x3FF) << 10) + ($extra & 0x3FF) + 0x10000;
                }
                else {
                    // It's an unmatched surrogate; only append this code unit, in case the
                    // next code unit is the high surrogate of a surrogate pair.
                    $output[] = $value;
                    $counter--;
                }
            }
            else {
                $output[] = $value;
            }
        }

        return $output;
    }

    /**
     * Returns the character code at the given index of a `utf-16le` string.
     *
     * @param string $string
     * @param int $offset
     */
    private static function charCodeAt($string, $offset) {
        return ord($string[($offset * 2)]) + (ord($string[($offset * 2) + 1]) << 8);
    }

    /**
     * A simple `array_map`-like wrapper to work with domain name strings or email addresses.
     *
     * @param string $string
     * @param callable $fn
     * @return string
     */
    private static function mapDomain($string, $fn) {
        $parts = explode('@', $string);
        $result = '';

        if (count($parts) > 1) {
            // In email addresses, only the domain should be punycoded
            // leave the local part (everything up to the '@') intact
            $result = $parts[0] . '@';
            $string = $parts[1];
        }

        $string = preg_replace('/[\x2E\x3002\xFF0E\xFF61]/u', '.', $string);
        $labels = explode('.', $string);
        $encoded = implode('.', array_map($fn, $labels));

        return $result . $encoded;
    }

    /**
     * Converts a unicode string representing a domain name to punycode. Only the non-ASCII parts of the domain name
     * will be converted (i.e. it doesn't matter if you call it with a domain that's already fully ASCII).
     * This method will correctly preserve the domain TLD at the end of the domain name.
     *
     * @param string $input
     * @return string
     * @throws Exception
     */
    public static function toAscii($input) {
        return self::mapDomain($input, function($string) {
            if (!preg_match('/[^\0-\x7E]/', $string)) return $string;
            return 'xn--' . self::encode($string);
        });
    }

    /**
     * Converts a punycode string representing a domain name to unicode. Only the punycoded parts of the input will
     * be converted (i.e. it doesn't matter if you call it on a string that has already been converted to unicode).
     * This method will correctly preserve the domain TLD at the end of the domain name.
     *
     * @param string $input
     * @return string
     * @throws Exception
     */
    public static function toUnicode($input) {
        return self::mapDomain($input, function($string) {
            if (!preg_match('/^xn--/', $string)) return $string;
            return self::decode(strtolower(substr($string, 4)));
        });
    }

}
