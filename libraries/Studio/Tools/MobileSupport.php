<?php

namespace Studio\Tools;

class MobileSupport extends Tool
{
    var $name = "Mobile Support Test";
    var $id = "mobile-support";
    var $template = "mobile-support.html";
    var $icon = "mobile-support";

    var $mobile = false;
    var $items = array();

    public function run() {
        global $studio;

        $desktop = new \Studio\Util\CURL("http://" . $this->url->domain);
        $desktop->setopt(CURLOPT_RETURNTRANSFER, true);
        $desktop->setopt(CURLOPT_FOLLOWLOCATION, true);
        $desktop->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $desktop->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $desktop->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.63 Safari/537.36");
        $desktop->setopt(CURLOPT_TIMEOUT, 10);
        $desktop->get();

        if ($desktop->errno > 0) {
            throw new \Exception(rt('Couldn\'t connect to {$1}', $this->url->domain));
        }

        $desktopURL = $desktop->info[CURLINFO_EFFECTIVE_URL];

        $ch = new \Studio\Util\CURL("http://" . $this->url->domain);
        $ch->setopt(CURLOPT_RETURNTRANSFER, true);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5 Build/MOB30H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.89 Mobile Safari/537.36");
        $ch->setopt(CURLOPT_TIMEOUT, 10);
        $ch->get();

        if ($ch->errno > 0) {
            throw new \Exception(rt('Couldn\'t connect to {$1}', $this->url->domain));
        }

        $mobileURL = $ch->info[CURLINFO_EFFECTIVE_URL];

        if ($mobileURL != $desktopURL) {
            $this->items[] = array(
                'type' => "Redirect",
                'info' => $mobileURL,
                'mobile' => true
            );
            $this->mobile = true;
        }

        # Use the DOM to find stylesheets and meta tags

        new \SEO\Helper\DOM;
        $dom = \SEO\Helper\str_get_html($desktop->data);

        if (!$dom) {
            throw new \Exception(rt('Failed to download webpage: {$1}', 'Got invalid response!'));
        }

        $viewport = $dom->find("meta[name=viewport]", 0, true);
        if ($viewport) {
            $content = sanitize_attribute($viewport->content);

            $this->items[] = array(
                'type' => rt("Meta"),
                'info' => "&lt;meta name=\"viewport\" content=\"$content\"&gt;",
                'mobile' => true
            );
            $this->mobile = true;
        }

        if (stripos($desktop->data, "@media")) {
            $this->items[] = array(
                'type' => rt("Media Query"),
                'info' => $desktopURL,
                'mobile' => true
            );
            $this->mobile = true;
        }

        foreach ($dom->find("link[rel=stylesheet]", null, true) as $link) {
            $href = $link->href;

            if (substr($href, 0, 2) == "//") $href = "https://" . substr($href, 2);
            if (substr($href, 0, 1) == "/") $href = "http://{$this->url->domain}".$href;
            if (substr($href, 0, 2) == "./") $href = "http://{$this->url->domain}".substr($href, 2);
            if (stripos($href, "://") === false) $href = "http://{$this->url->domain}/".$href;

            $ch = new \Studio\Util\CURL($href);
            $ch->setopt(CURLOPT_RETURNTRANSFER, true);
            $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
            $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
            $ch->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5 Build/MOB30H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.89 Mobile Safari/537.36");
            $ch->setopt(CURLOPT_TIMEOUT, 10);
            try {
                $ch->get();
                if (stripos($ch->data, "@media")) {
                    $this->items[] = array(
                        'type' => rt("Stylesheet"),
                        'info' => $href,
                        'mobile' => true
                    );
                    $this->mobile = true;
                }
                else {
                    $this->items[] = array(
                        'type' => rt("Stylesheet"),
                        'info' => $href,
                        'mobile' => false
                    );
                }
            } catch(\Exception $e) {
                $this->items[] = array(
                    'type' => rt("Stylesheet"),
                    'info' => $href,
                    'mobile' => false
                );
            }
        }

    }

    public function output() {
        global $page;
        $path = $page->getPath();
        $html = $this->getTemplate();

        if ($this->mobile) {
            $html = str_replace("[[ICON]]", "<img src=\"{$path}resources/images/check128.png\" width=\"64px\" />", $html);
            $html = str_replace("[[LABEL]]", "<p class=\"success-label\">" . rt("This website supports mobile devices.") . "</p>", $html);
        }
        else {
            $html = str_replace("[[ICON]]", "<img src=\"{$path}resources/images/error128.png\" width=\"64px\" />", $html);
            $html = str_replace("[[LABEL]]", "<p class=\"error-label\">" . rt("This website does not support mobile devices.") . "</p>", $html);
        }

        $itemsHTML = "";
        foreach ($this->items as $i => $item) {
            $odd = (($i % 2 == 0) ? "" : "odd");
            $mobile = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" />";
            if (!$item['mobile']) $mobile = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" />";

            $itemsHTML .= "<tr class=\"$odd\">
                <td class=\"center\">{$item['type']}</td>
                <td>{$item['info']}</td>
                <td class=\"center\">{$mobile}</td>
            </tr>";
        }

        $html = str_replace("[[ITEMS]]", $itemsHTML, $html);
        echo sanitize_trusted($html);
    }

    protected function getCacheKey() {
        return "";
    }
}
