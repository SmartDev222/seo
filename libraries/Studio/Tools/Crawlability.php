<?php

namespace Studio\Tools;

use SEO\Parsers\Robots;
use Studio\Util\CURL;
use Exception;
use Sanitize;

class Crawlability extends Tool
{
    var $name = "Crawlability Test";
    var $id = "crawlability";
    var $template = "crawlability.html";
    var $icon = "crawlability";

    var $crawlable;
    var $indexable;
    var $path;
    var $raw;
    var $sitePrefix;

    public function prerun($url) {
        $this->path = "/";
        if (isset($_POST['path'])) {
            $p = $_POST['path'];
            if ($p == "" || ($p != "" && substr($p, 0, 1) != "/")) $p = "/$p";

            $this->path = $p;
        }
    }

    public function run() {
        if (stripos($this->path, "://") !== false) throw new Exception(rt("Invalid path"));

        $this->data = array(
            'crawlable' => null,
            'indexable' => null,
            'raw' => null,
            'sitePrefix' => null,
            'data' => null
        );

        $href = "http://" . $this->url->domain . "/robots.txt";

        $ch = new \Studio\Util\CURL($href);
        $ch->setopt(CURLOPT_RETURNTRANSFER, true);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_TIMEOUT, 10);

        $this->data['raw'] = "# 404 Not Found ";
        try {
            $ch->get();
            $code = $ch->info[CURLINFO_HTTP_CODE];

            $a = rt('Robots.txt file was not found (error {$1}) at {$2}', $code, $ch->info[CURLINFO_EFFECTIVE_URL]);
            $b = rt('Robots.txt is temporarily unavailable (error {$1}) at {$2}', $code, $ch->info[CURLINFO_EFFECTIVE_URL]);

            if ($code >= 400 && $code < 500) throw new Exception($a);
            if ($code >= 500 && $code < 600) throw new Exception($b);
            $this->data['raw'] = $ch->data;
        }
        catch (Exception $e) {

        }

        $robots = new Robots($this->data['raw']);

        # Check page for meta crawlability

        $ch = new CURL("http://{$this->url->domain}" . $this->path);
        $ch->setopt(CURLOPT_RETURNTRANSFER, true);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_TIMEOUT, 10);
        $ch->get();

        $effective = $ch->info[CURLINFO_EFFECTIVE_URL];

        new \SEO\Helper\DOM;
        $dom = \SEO\Helper\str_get_html($ch->data);

        if (!$dom) {
            throw new Exception(rt('Failed to download webpage: {$1}', 'Got invalid response!'));
        }

        $crawlable = array(
            'Google' => $robots->canCrawl('Googlebot', $this->path),
            'Bing' => $robots->canCrawl('Bingbot', $this->path)
        );
        $indexable = array(
            'Google' => array(true, "none"),
            'Bing' => array(true, "none")
        );

        $metaRobots = $dom->find("meta[name=robots]", 0);
        $metaGoogle = $dom->find("meta[name=googlebot]", 0);
        $metaBing = $dom->find("meta[name=bingbot]", 0);
        if ($metaRobots) {
            if (stripos($metaRobots->content, "noindex") !== false) {
                $indexable['Google'] = array(false, "&lt;meta name=\"robots\" content=\"{$metaRobots->content}\"&gt;");
                $indexable['Bing'] = array(false, "&lt;meta name=\"robots\" content=\"{$metaRobots->content}\"&gt;");
            }
            if (stripos($metaRobots->content, "nofollow") !== false) {
                $crawlable['Google'] = array(false, "&lt;meta name=\"robots\" content=\"{$metaRobots->content}\"&gt;");
                $crawlable['Bing'] = array(false, "&lt;meta name=\"robots\" content=\"{$metaRobots->content}\"&gt;");
            }
        }
        if ($metaGoogle) {
            if (stripos($metaGoogle->content, "noindex") !== false) $indexable['Google'] = array(false, str_ireplace("noindex", "<strong>noindex</strong>", "&lt;meta name=\"googlebot\" content=\"{$metaGoogle->content}\"&gt;"));
            if (stripos($metaGoogle->content, "nofollow") !== false) {
                $crawlable['Google'] = array(false, "&lt;meta name=\"googlebot\" content=\"{$metaGoogle->content}\"&gt;");
            }
        }
        if ($metaBing) {
            if (stripos($metaBing->content, "noindex") !== false) $indexable['Bing'] = array(false, str_ireplace("noindex", "<strong>noindex</strong>", "&lt;meta name=\"bingbot\" content=\"{$metaBing->content}\"&gt;"));
            if (stripos($metaBing->content, "nofollow") !== false) {
                $crawlable['Bing'] = array(false, "&lt;meta name=\"bingbot\" content=\"{$metaBing->content}\"&gt;");
            }
        }

        if ($crawlable['Google'][0] == false && $indexable['Google'][0]) $indexable['Google'] = array(false, "Inherited (cannot crawl)");
        if ($crawlable['Bing'][0] == false && $indexable['Bing'][0]) $indexable['Bing'] = array(false, "Inherited (cannot crawl)");

        $this->data['crawlable'] = $crawlable;
        $this->data['indexable'] = $indexable;

        $p = parse_url($effective);
        $this->data['sitePrefix'] = $p['scheme'] . "://" . $p['host'];
    }

    public function output() {
        global $page, $studio;

        $sitePrefix = $this->data['sitePrefix'];
        $crawlable = $this->data['crawlable'];
        $indexable = $this->data['indexable'];
        $raw = $this->data['raw'];

        $path = $page->getPath();
        $html = $this->getTemplate();

        $style = "style=\"margin: -1px 5px 0 0;\"";

        $gindexable = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" $style />";
        if (!$indexable['Google'][0]) $gindexable = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" $style />";

        $bindexable = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" $style />";
        if (!$indexable['Bing'][0]) $bindexable = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" $style />";

        $bcrawl = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" $style />";
        if (!$crawlable['Bing'][0]) $bcrawl = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" $style />";

        $gcrawl = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" $style />";
        if (!$crawlable['Google'][0]) $gcrawl = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" $style />";

        $info = "<ul>
        <li><strong>Google crawl rule</strong>: {$crawlable['Google'][1]}</li>
        <li><strong>Bing crawl rule</strong>: {$crawlable['Bing'][1]}</li>
        <li style=\"padding-top: 8px;\"><strong>Google index rule</strong>: {$indexable['Google'][1]}</li>
        <li><strong>Bing index rule</strong>: {$indexable['Bing'][1]}</li>
        </ul>";

        $itemsHTML = "<tr>
            <td>$this->path</td>
            <td>$info</td>
            <td class=\"center\">
                $gcrawl " . rt("Google") . "<br />
                $bcrawl " . rt("Bing") . "
            </td>
            <td class=\"center\">
                $gindexable " . rt("Google") . "<br />
                $bindexable " . rt("Bing") . "
            </td>
        </tr>";

        $html = str_replace("[[ITEMS]]", $itemsHTML, $html);
        $html = str_replace("[[CODE]]", $raw, $html);
        $html = str_replace("[[SITE]]", $sitePrefix, $html);
        $html = str_replace("[[PATH]]", sanitize_attribute($this->path), $html);
        echo sanitize_trusted($html);
    }

    public function record($data = "") {
        parent::record($this->path);
    }

    protected function getCacheKey() {
        return "";
    }
}
