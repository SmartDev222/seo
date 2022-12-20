<?php
namespace SEO\Parsers;

/**
 * Robots.txt Parser
 * Developed by Bailey Herbert
 * https://baileyherbert.com/
 *
 * Welcome to the only PHP class in the world that can fully parse robots.txt files in full compliance with Google specifications.
 *
 * This class was created for SEO Studio and can be used under terms defined by the license you purchased from CodeCanyon.
 * View CodeCanyon license specifications here: http://codecanyon.net/licenses ("Standard")
 *
 * This parser fully complies with specifications at https://developers.google.com/webmasters/control-crawl-index/docs/robots_txt
 *
 * Parsing method:
 * 1) Go through each line, ignoring comments and skipping blank lines.
 * 2) Extract the field and values, ignoring comments, with field case-insensitive (<field>:<value><#comments>).
 * 3) Look for user-agents (allow for misspelling 'useragent') and record the rules that follow.
 * 4) Allow for group rules (multiple user-agents specified for one set of rules).
 * 5) Remove trailing wildcards (*) since these are actually ignored by crawlers.
 * 6) Preserve the order of rules for finding precedence later.
 * 7) Record sitemaps separately (allowing for multiple sitemaps).
 */

/**
 * Parser for robots.txt files which complies fully with Google Specifications
 */
class Robots
{
    private $rules = array();

    public function __construct($txt) {
        $this->rules = $this->parse($txt);
    }

    public function getAllRules() {
        $r = array(
            'sitemaps' => $this->rules['sitemaps'],
            'agents' => array()
        );

        foreach ($this->rules['engines'] as $engine => $rules) {
            $r['agents'][$engine] = $rules;
        }

        return $r;
    }

    public function getAgentRules($agent) {
        $agent = strtolower($agent);

        if (isset($this->rules['engines'][$agent])) {
            return $this->rules['engines'][$agent];
        }
    }

    public function getSitemaps() {
        $r = array();

        foreach ($this->rules['sitemaps'] as $i => $s) {
            $r[] = array(
                'href' => $s,
                'priority' => (count($this->rules['sitemaps']) - $i)
            );
        }

        return $r;
    }

    /**
     * Checks if the user agent can crawl the page at path.
     * @param String $userAgent The user agent of the crawler.
     * @param String $path The path to check against (URL excluding scheme and domain name).
     * @return array in format [boolean canCrawl, String[/null] matchedRule]
     */
    public function canCrawl($userAgent, $path) {
        $rules = $this->rules['engines'];
        $canCrawl = false;
        $match = "";

        # First, check the global rules

        if (isset($rules['*'])) {
            $r = array();

            foreach ($rules['*']['allow'] as $i=>$v) $r[$i] = "a:" . $v;
            foreach ($rules['*']['disallow'] as $i=>$v) {
                if (trim($v) == "" || trim($v) == " ") $r[$i] = "a:";
                else $r[$i] = "d:" . $v;
            }

            foreach ($r as $line) {
                $p = substr($line, 2);
                if (stripos($p, "*") !== false) {
                    // there's one or more wildcard in this path

                    $e_path = $path;
                    $e_p = $p;

                    if (substr($e_p, -1) == '$') {
                        // Trailing $, require exact match (using fnmatch)
                        if (fnmatch(substr($e_p, 0, -1), $e_path)) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                    else {
                        // No trailing $, allow a match as long as it starts with the rule path

                        $lastWildcard = strrpos($e_p, "*");
                        $trailing = substr($e_p, $lastWildcard + 1);

                        $posRealpath = strrpos($e_path, $trailing);
                        $e_realpath = substr($e_path, 0, $posRealpath + strlen($trailing));

                        if (fnmatch($e_p, $e_realpath)) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                }
                else {
                    // no wildcards

                    if (substr($p, -1) == '$') {
                        // there has to be an exact match - nothing can follow

                        if ($path == substr($p, 0, -1)) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                    else {
                        // no $ symbol, do a simple string comparison

                        if (substr($path, 0, strlen($p)) == $p) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                }
            }
        }

        # Now check the individual search engine's rules

        if (isset($rules[strtolower($userAgent)])) {
            $r = array();

            foreach ($rules[strtolower($userAgent)]['allow'] as $i=>$v) $r[$i] = "a:" . $v;
            foreach ($rules[strtolower($userAgent)]['disallow'] as $i=>$v) {
                if (trim($v) == "" || trim($v) == " ") $r[$i] = "a:";
                else $r[$i] = "d:" . $v;
            }

            foreach ($r as $line) {
                $p = substr($line, 2);
                if (stripos($p, "*") !== false) {
                    // there's one or more wildcard in this path

                    $e_path = $path;
                    $e_p = $p;

                    if (substr($e_p, -1) == '$') {
                        // Trailing $, require exact match (using fnmatch)
                        if (fnmatch(substr($e_p, 0, -1), $e_path)) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                    else {
                        // No trailing $, allow a match as long as it starts with the rule path

                        $lastWildcard = strrpos($e_p, "*");
                        $trailing = substr($e_p, $lastWildcard + 1);

                        $posRealpath = strrpos($e_path, $trailing);
                        $e_realpath = substr($e_path, 0, $posRealpath + strlen($trailing));

                        if (fnmatch($e_p, $e_realpath)) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                }
                else {
                    // no wildcards

                    if (substr($p, -1) == '$') {
                        // there has to be an exact match - nothing can follow

                        if ($path == substr($p, 0, -1)) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                    else {
                        // no $ symbol, do a simple string comparison

                        if (substr($path, 0, strlen($p)) == $p) {
                            if (substr($line, 0, 1) == "a") $canCrawl = true;
                            if (substr($line, 0, 1) == "d") $canCrawl = false;
                            $match = (($canCrawl == true) ? "Allow: " : "Disallow: ") . $p;
                        }
                    }
                }
            }
        }

        return array($canCrawl, $match);
    }

    /**
     * Parses the provided robots.txt contents into an array of rules.
     * @param String $txt The contents of the robots.txt file
     * @return array of rules
     */
    public function parse($txt) {
        $rules = array(
            'sitemaps' => array(),
            'engines' => array()
        );

        $txt = str_replace("\r\n", "\n", $txt);
        $lines = explode("\n", $txt);

        $processingAgent = false;
        $tmpAgents = array();
        $tmpRules = array('allow' => array('/'), 'disallow' => array());

        foreach ($lines as $line) {
            $line = trim($line);

            if (substr($line, 0, 1) == "#") continue;
            if ($line == "" || $line == " ") continue;

            $cleaned = $line;
            if (stripos($line, "#") !== false) {
                $cleaned = trim(substr($line, 0, stripos($line, "#")));
            }

            if (strpos($cleaned, ":") === false) continue;

            list($field, $value) = explode(":", $cleaned, 2);
            $field = strtolower(trim($field));
            $value = trim($value);

            if ($field == "useragent") $field = "user-agent";

            if ($field == "user-agent") {
                if ($processingAgent && (count($tmpRules['allow']) > 0 || count($tmpRules['disallow']) > 0)) {
                    foreach ($tmpAgents as $a) {
                        $a = strtolower($a);

                        if (isset($rules['engines'][$a])) {
                            # Merge new data into the engine

                            $start = count($rules['engines'][$a]['allow']) + count($rules['engines'][$a]['disallow']);

                            foreach ($tmpRules['allow'] as $i => $tmpa) $rules['engines'][$a]['allow'][$start+$i] = $tmpa;
                            foreach ($tmpRules['disallow'] as $i => $tmpd) $rules['engines'][$a]['disallow'][$start+$i] = $tmpd;
                        }
                        else {
                            # Create the new engine

                            $rules['engines'][$a] = $tmpRules;
                        }
                    }

                    $tmpRules = array('allow' => array('/'), 'disallow' => array());
                    $tmpAgents = array();
                }

                $tmpAgents[] = $value;
                $processingAgent = true;
            }
            else {
                if ($field == "sitemap") {
                    $rules['sitemaps'][] = $value;
                }
                if ($field == "disallow") {
                    if (substr($value, -1) == "*") $value = substr($value, 0, -1);
                    $next = count($tmpRules['disallow']) + count($tmpRules['allow']);
                    $tmpRules['disallow'][$next] = $value;
                }
                if ($field == "allow") {
                    if (substr($value, -1) == "*") $value = substr($value, 0, -1);
                    $next = count($tmpRules['disallow']) + count($tmpRules['allow']);
                    $tmpRules['allow'][$next] = $value;
                }
            }
        }
        if ($processingAgent) {
            foreach ($tmpAgents as $a) {
                $a = strtolower($a);

                if (isset($rules['engines'][$a])) {
                    # Merge new data into the engine

                    $start = count($rules['engines'][$a]['allow']) + count($rules['engines'][$a]['disallow']);

                    foreach ($tmpRules['allow'] as $i => $tmpa) $rules['engines'][$a]['allow'][$start+$i] = $tmpa;
                    foreach ($tmpRules['disallow'] as $i => $tmpd) $rules['engines'][$a]['disallow'][$start+$i] = $tmpd;
                }
                else {
                    # Create the new engine

                    $rules['engines'][$a] = $tmpRules;
                }
            }
        }

        if (!isset($rules['engines']['*'])) {
            $rules['engines']['*'] = array(
                'allow' => array("/"),
                'disallow' => array()
            );
        }

        return $rules;
    }
}

if(!function_exists('fnmatch')) {
    function fnmatch($pattern, $string) {
        return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
    }
}
