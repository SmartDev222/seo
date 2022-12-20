<?php

namespace Studio\Tools;

use \Studio\Util\CURL;
use \SEO\Helper\DOM;
use Exception;

class ExtractMetaTags extends Tool
{
    var $name = "Extract Meta Tags";
    var $id = "extract-meta-tags";
    var $icon = "extract-meta-tags";
    var $template = "meta-tags.html";

    var $path = "/";
    var $domain;

    public function prerun($url) {
        if (isset($_POST['path'])) {
            $this->path = $_POST['path'];
        }
    }

    public function run() {
        $ch = new CURL("http://" . $this->url->domain . $this->path);
        $ch->setopt(CURLOPT_TIMEOUT, 15);
        $ch->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_FAILONERROR, true);
        $ch->get();

        if ($ch->errno > 0) {
            throw new Exception(rt('Failed to download webpage: {$1}', $ch->error));
        }

        new DOM;

        $tags = array();
        $dom = \SEO\Helper\str_get_html($ch->data);
        $googleUses = array("viewport", "description", "robots", "googlebot");
        $bingUses = array("viewport", "description", "robots", "keywords", "bingbot");

        if (!$dom) {
            throw new Exception(rt('Failed to download webpage: {$1}', 'Got invalid response!'));
        }

        if ($dom->find("title", 0)) {
            $tags[] = array('name' => "title", 'content' => $dom->find("title", 0)->plaintext, 'google' => true, 'bing' => true);
        }

        foreach ($dom->find("meta") as $meta) {
            if (!isset($meta->content)) $meta->content = "";
            if (!isset($meta->name)) continue;

            $g = in_array(strtolower($meta->name), $googleUses);
            $b = in_array(strtolower($meta->name), $bingUses);

            $tags[] = array('name' => $meta->name, 'content' => $meta->content, 'google' => $g, 'bing' => $b);
        }

        $p = parse_url($ch->info[CURLINFO_EFFECTIVE_URL]);

        $this->data = array(
            'tags' => $tags,
            'domain' => strtolower($p['scheme'] . "://" . $p['host'])
        );
    }

    public function output() {
        global $page;
        $path = $page->getPath();

        $html = $this->getTemplate();
        $html = str_replace("[[SITE]]", $this->data['domain'], $html);
        $html = str_replace("[[PATH]]", $this->path, $html);

        $itemsHTML = "";
        foreach ($this->data['tags'] as $i => $row) {
            $google = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" />";
            $bing = $google;

            if (!$row['google']) $google = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" />";
            if (!$row['bing']) $bing = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" />";

            $odd = (($i % 2 == 0) ? "" : "odd");
            $itemsHTML .= "<tr class=\"$odd\">
                <td>{$row['name']}</td>
                <td>{$row['content']}</td>
                <td class=\"center\">{$google}</td>
                <td class=\"center\">{$bing}</td>
            </tr>";
        }

        $html = str_replace("[[ITEMS]]", $itemsHTML, $html);

        echo sanitize_trusted($html);
    }

    public function record($data = "") {
        parent::record($this->path);
    }

    protected function getCacheKey() {
        return "";
    }
}
