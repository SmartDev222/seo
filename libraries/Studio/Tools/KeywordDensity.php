<?php

namespace Studio\Tools;

use Studio\Util\Helpers\BoxPlot;
use Studio\Util\Http\WebRequest;
use Studio\Util\Parsers\HTMLDocument;

class KeywordDensity extends Tool
{
    var $name = "Keyword Density";
    var $id = "keyword-density";
    var $template = "keyword-density.html";
    var $icon = "keyword-density";

    var $path;

    public function prerun($url) {
        $this->path = "/";
        if (isset($_POST['path'])) {
            $p = $_POST['path'];
            if ($p == "" || ($p != "" && substr($p, 0, 1) != "/")) $p = "/$p";

            $this->path = $p;
        }
    }

    public function run() {
        if (stripos($this->path, "://") !== false) throw new \Exception("Invalid path");

        $this->data = array(
            'title' => null,
            'description' => null,
            'site' => null,
            'words' => array()
        );

        // Download the page
        $request = new WebRequest("http://" . $this->url->domain . $this->path);
        $request->setTimeout(10);
        $response = $request->get();
        $dom = $response->getDom();

        // Start processing!
        $words = $this->getWords($dom);
        $occurrences = $this->getOccurrences($words);
        $plot = new BoxPlot($occurrences);
        $weights = [];
        $highestWeight = 0;

        // Get the title and meta description
        list($title, $description) = $this->getMetaTags($dom);

        // We'll have to calculate a weight for each word based on its stats
        foreach ($occurrences as $word => $occurrences) {
            $multiplier = $occurrences / $plot->getHighest();
            $weight = $occurrences;

            $inTitle = $this->getWordCount($title, $word);
            $inDescription = $this->getWordCount($description, $word);

            if ($inTitle) $weight += $plot->getHighest() * $inTitle;
            if ($inDescription) $weight += ($plot->getHighest() * 0.66) * $inDescription;

            $finalWeight = $weight * $multiplier;
            if ($finalWeight > $highestWeight) $highestWeight = $finalWeight;

            if (mb_strlen($word, 'UTF-8') > 3) $weights[] = [
                'word' => $word,
                'weight' => number_format($finalWeight, 1, '.', ''),
                'occurrences' => $occurrences,
                'percent' => '0.00%',
                'inTitle' => $inTitle > 0,
                'inDescription' => $inDescription > 0
            ];
        }

        // Update the percentages
        if ($highestWeight > 0) {
            foreach ($weights as &$weight) {
                $weight['percent'] = number_format(100 * ($weight['weight'] / $highestWeight), 1) . '%';
            }
        }

        // Store the top 100 keywords
        $this->data['title'] = $title;
        $this->data['description'] = $description;
        $this->data['words'] = array_slice($weights, 0, 100);
    }

    public function output() {
        global $page;
        $path = $page->getPath();

        $html = $this->getTemplate();
        $html = str_replace("[[SITE]]", $this->data['site'], $html);
        $html = str_replace("[[PATH]]", $this->path, $html);

        if ($this->data['title'] == null) $this->data['title'] = "-";
        if ($this->data['description'] == null) $this->data['description'] = "-";

        $metaHTML = "<tr>
            <td>" . rt("Title") . "</td>
            <td>{$this->data['title']}</td>
        </tr><tr class=\"odd\">
            <td>" . rt("Description") . "</td>
            <td>{$this->data['description']}</td>
        </tr>";

        $words = $this->data['words'];
        usort($words, function($a, $b) {
            if ($a['weight'] < $b['weight']) {
                return 1;
            }
            else if ($a['weight'] > $b['weight']) {
                return -1;
            }

            return 0;
        });

        $itemsHTML = "";
        $i = 0;
        foreach ($words as $row) {
            $word = $row['word'];
            $weight = $row['weight'];
            $occurrences = $row['occurrences'];
            $inTitle = $row['inTitle'];
            $inDescription = $row['inDescription'];

            $odd = (($i++ % 2 == 0) ? "odd" : "");

            $title = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" />";
            if (!$inTitle) $title = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" />";

            $desc = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" />";
            if (!$inDescription) $desc = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" />";

            $itemsHTML .= "<tr class=\"$odd\">
                <td>$word</td>
                <td class=\"center\">$occurrences</td>
                <td class=\"center\">$title</td>
                <td class=\"center\">$desc</td>
                <td class=\"center\">$weight%</td>
            </tr>";
        }

        $html = str_replace("[[META]]", $metaHTML, $html);
        $html = str_replace("[[ITEMS]]", $itemsHTML, $html);

        echo sanitize_trusted($html);
    }

    public function record($data = "") {
        parent::record($this->path);
    }

    protected function getCacheKey() {
        return "";
    }

    /**
     * Extracts the page's title and description and returns them in an ordered array. The type of the values is
     * `string` and will be blank strings when not found.
     *
     * @param HTMLDocument $dom
     * @return string[]
     */
    private function getMetaTags(HTMLDocument $dom) {
        $title = '';
        $description = '';

        if (!is_null($titleElement = $dom->getElementByTagName('title'))) {
            $title = trim($titleElement->getPlainText());
        }

        if (!is_null($metaElement = $dom->find('meta[name=description]', 0))) {
            $content = $metaElement->getAttribute('content');
            $description = is_string($content) ? trim($content) : null;
        }

        return [mb_strtolower($title, 'UTF-8'), mb_strtolower($description, 'UTF-8')];
    }

    /**
     * Returns an array of words and phrases that occur on the
     *
     * @param Url $url
     * @return string[]
     */
    private function getWords(HTMLDocument $dom) {
        $text = $dom->getPlainText();
        $text = trim(preg_replace('/\s\s+/', ' ', mb_strtolower($text, 'UTF-8')));

        $symbols = explode(' ', $text);
        $words = [];
        $lastWord = null;
        $previousLastWords = [];

        foreach ($symbols as $symbol) {
            $symbol = trim($symbol, '.,!?$%()[]{}":/');

            if (preg_match('/^([\x21-\x2E\x5B-\x60\x7B-\x7E\x3A-\x40])+$/', $symbol)) continue;
            if (preg_match('/(https?:\/\/)/i', $symbol)) continue;
            if (preg_match('/^\d{1,4}$/', $symbol)) continue;
            if (strlen($symbol) < 3 || strlen($symbol) > 32) continue;
            if (substr($symbol, 0, 1) === '&' && substr($symbol, -1) === ';') continue;
            if (in_array($symbol, $this->getCommonWords())) continue;

            $symbol = str_replace('<', '&lt;', $symbol);
            $symbol = str_replace('>', '&gt;', $symbol);

            if (!is_null($lastWord)) {
                $words[] = $lastWord . ' ' . $symbol;
            }

            if (count($previousLastWords) === 3) {
                $words[] = $previousLastWords[0] . ' ' . $previousLastWords[1] . ' ' . $previousLastWords[2];
                array_shift($previousLastWords);
            }

            $previousLastWords[] = $symbol;
            $lastWord = $symbol;
            $words[] = $symbol;
        }

        return $words;
    }

    /**
     * Returns an array holding the number of times each word or phrase was found. The array holds the words as the
     * keys and the number of occurrences as the values.
     *
     * @param string[] $words
     * @return int[]
     */
    private function getOccurrences($words) {
        $temp = [];

        foreach ($words as $word) {
            if (!array_key_exists($word, $temp)) {
                $temp[$word] = 0;
            }

            $temp[$word]++;
        }

        arsort($temp);
        return $temp;
    }

    /**
     * Performs a unicode-friendly word count operation which checks that the given needle is free of any word
     * boundaries (must either have surrounding whitespace or be at the beginning/end of a line).
     *
     * @param string $haystack
     * @param string $needle
     * @return int
     */
    private function getWordCount($haystack, $needle) {
        $haystackLength = mb_strlen($haystack, 'utf-8');
        $needleLength = mb_strlen($needle, 'utf-8');
        $offset = 0;
        $count = 0;

        if (!$haystackLength || !$needleLength) return 0;

        while (true) {
            $startIndex = mb_stripos($haystack, $needle, $offset, 'utf-8');
            if ($startIndex === false) break;

            $endIndex = $startIndex + $needleLength;
            $offset = $startIndex + $needleLength;

            $characterBefore = $startIndex > 0 ? mb_substr($haystack, $startIndex - 1, 1, 'utf-8') : null;
            $characterAfter = $endIndex < $haystackLength ? mb_substr($haystack, $endIndex, 1, 'utf-8') : null;
            $regex = '/\W/';

            if (is_null($characterBefore) || preg_match($regex, $characterBefore)) {
                if (is_null($characterAfter) || preg_match($regex, $characterAfter)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Returns an array of the most common English words.
     *
     * @return string[]
     */
    private function getCommonWords() {
        static $common;

        if (is_null($common)) {
            $common = [
                'the', 'and', 'that', 'have', 'for', 'not', 'with', 'from', 'you',
                'your'
            ];
        }

        return $common;
    }

}
