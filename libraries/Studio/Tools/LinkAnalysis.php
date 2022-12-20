<?php

namespace Studio\Tools;

class LinkAnalysis extends Tool
{
    var $name = "Link Analysis";
    var $id = "link-analysis";
    var $template = "links.html";
    var $icon = "link-analysis";

    var $path;
    var $domain;

    public function run() {
        $this->path = "/";
        if (isset($_POST['path'])) {
            $p = $_POST['path'];
            if ($p == "" || ($p != "" && substr($p, 0, 1) != "/")) $p = "/$p";

            $this->path = $p;
        }

        $ch = new \Studio\Util\CURL("http://" . $this->url->domain . $this->path);
        $ch->setopt(CURLOPT_TIMEOUT, 10);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
        $ch->setopt(CURLOPT_MAXREDIRS, 10);
        $ch->get();

        $effective = $ch->info[CURLINFO_EFFECTIVE_URL];
        $p = parse_url($effective);
        $this->domain = $p['scheme'] . "://" . $p['host'];

        new \SEO\Helper\DOM;
        $dom = \SEO\Helper\str_get_html($ch->data);

        if (!$dom) {
            throw new \Exception(rt('Failed to download webpage: {$1}', 'Got invalid response!'));
        }

        $stats = array(
            'internal' => 0,
            'external' => 0,
            'nofollow' => 0
        );
        $links = array();

        $allowFollowing = true;
        if ($dom->find("meta[name=robots]", 0)) {
            if (stripos($dom->find("meta[name=robots]", 0)->content, "nofollow") !== false) {
                $allowFollowing = false;
            }
        }

        foreach ($dom->find("a[href]") as $a) {
            $href = $a->href;

            if ($href == "") $href = $effective;
            if (substr($href, 0, 2) == "//") $href = "https://" . substr($href, 2);
            if (substr($href, 0, 1) == "/") $href = "http://{$this->url->domain}" . $href;
            if (substr($href, 0, 2) == "./") $href = "http://{$this->url->domain}" . substr($href, 2);
            if (stripos($href, "://") === false) $href = "http://{$this->url->domain}/" . $href;

            $internal = true;
            $follow = $allowFollowing;

            $p = parse_url($href);
            if (isset($p['host'])) {
                if (stripos($p['host'], $this->url->domain) === false) {
                    $internal = false;
                }
            }

            if ($a->rel) {
                if (stripos($a->rel, "nofollow") !== false) {
                    $follow = false;
                }
            }

            if (!$follow && !$internal) $stats['nofollow']++;
            if (!$internal) $stats['external']++;
            if ($internal) $stats['internal']++;

            $links[] = array(
                'href' => $href,
                'text' => $a->plaintext,
                'follow' => $follow,
                'internal' => $internal
            );
        }

        $this->data = array(
            'd' => array(
                $links,
                $stats
            ),
            'domain' => $this->domain
        );
    }

    public function output() {
        global $page;

        $path = $page->getPath();
        $html = $this->getTemplate();

        $links = $this->data['d'][0];
        $stats = $this->data['d'][1];

        $html = str_replace("[[SITE]]", $this->data['domain'], $html);
        $html = str_replace("[[PATH]]", $this->path, $html);

        $html = str_replace("[[NUM_LINKS]]", number_format(count($links)), $html);
        $html = str_replace("[[NUM_EXTERNAL]]", number_format($stats['external']), $html);
        $html = str_replace("[[NUM_INTERNAL]]", number_format($stats['internal']), $html);
        $html = str_replace("[[NUM_NOFOLLOW]]", number_format($stats['nofollow']), $html);

        $linksHTML = "";
        foreach ($links as $i => $link) {
            $href = $link['href'];
            $text = $link['text'];
            $follow = $link['follow'];
            $internal = $link['internal'];

            $type = ($internal ? rt("Internal") : "<strong>" . rt("External") . "</strong>");
            $followImage = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" />";
            if (!$follow) $followImage = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" />";

            $odd = (($i % 2 == 0) ? "" : "odd");
            $linksHTML .= "<tr class=\"$odd\">
                <td>$href</td>
                <td>$text</td>
                <td class=\"center\">$type</td>
                <td class=\"center\">$followImage</td>
            </tr>";
        }

        $html = str_replace("[[LINKS]]", $linksHTML, $html);

        echo sanitize_trusted($html);
    }

    public function record($data = "") {
        parent::record($this->path);
    }

    protected function getCacheKey() {
        return "";
    }
}
