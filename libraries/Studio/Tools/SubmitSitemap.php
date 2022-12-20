<?php

namespace Studio\Tools;

class SubmitSitemap extends Tool
{
    var $name = "Submit Sitemaps";
    var $id = "submit-sitemap";
    var $icon = "submit-sitemaps";
    var $template = "submit-sitemap.html";

    public function run() {
        global $studio;

        $this->data = array(
            'sitemaps' => array(),
            'code' => 0
        );

        $total = 0;

        if (isset($_GET['sitemap'])) {
            $sitemap = $_GET['sitemap'];
            $sitemap = unserialize(base64_decode($sitemap));
            $checkid = strrev(md5(strrev(urlencode($_GET['sitemap']))));
            $sec = $_GET['sec'];

            if ($checkid != $sec) {
                $studio->showError(rt("Invalid security token"));
                $this->stop();
            }

            $url = $sitemap;
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                $studio->showError(rt("Invalid sitemap"));
                $this->stop();
            }

            $ch = new \Studio\Util\CURL("http://www.google.com/ping?sitemap=" . urlencode($url));
            $ch->setopt(CURLOPT_RETURNTRANSFER, true);
            $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
            $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
            $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
            $ch->setopt(CURLOPT_TIMEOUT, 10);
            $ch->get();

            $ch = new \Studio\Util\CURL("http://www.bing.com/ping?sitemap=" . urlencode($url));
            $ch->setopt(CURLOPT_RETURNTRANSFER, true);
            $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
            $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
            $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
            $ch->setopt(CURLOPT_TIMEOUT, 10);
            $ch->get();

            $this->stop();
        }

        # First, check robots.txt for a sitemap file.

        $ch = new \Studio\Util\CURL("http://" . $this->url->domain . "/robots.txt");
        $ch->setopt(CURLOPT_RETURNTRANSFER, true);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_TIMEOUT, 10);

        try {
            $ch->get();
            $code = $ch->info[CURLINFO_HTTP_CODE];
        }
        catch (\Exception $e) {
            $code = 404;
        }

        if ($code == 200) {
            # We have a working sitemap file. Let's see if it has any sitemaps.

            $s = new \SEO\Parsers\Robots($ch->data);
            $sitemaps = $s->getSitemaps();

            if (count($sitemaps) > 0) {
                foreach ($sitemaps as $arr) {
                    $contents = "";

                    $ch2 = new \Studio\Util\CURL($arr['href']);
                    $ch2->setopt(CURLOPT_RETURNTRANSFER, true);
                    $ch2->setopt(CURLOPT_FOLLOWLOCATION, true);
                    $ch2->setopt(CURLOPT_SSL_VERIFYPEER, false);
                    $ch2->setopt(CURLOPT_SSL_VERIFYHOST, false);
                    $ch2->setopt(CURLOPT_TIMEOUT, 10);

                    try {
                        $ch2->get();
                        $code = $ch2->info[CURLINFO_HTTP_CODE];
                    }
                    catch (\Exception $e) {
                        $code = 404;
                    }

                    $contents = "";
                    $d = 0;
                    if ($code == 200 && substr(strtolower($arr['href']), -4) == ".xml") $contents = $ch2->data;
                    if ($code == 200) $d = 50;

                    $h = str_replace("https://", "http://", ($arr['href']));

                    $this->data['sitemaps'][$h] = array(
                        'xml' => $contents,
                        'code' => $code,
                        'd' => $d
                    );

                    if ($total++ > 50) {
                        break;
                    }
                }
            }
        }

        # Then, check for the actual sitemap.xml file if it isn't already in the array.

        $ch = new \Studio\Util\CURL("http://" . $this->url->domain . "/sitemap.xml");
        $ch->setopt(CURLOPT_RETURNTRANSFER, true);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_TIMEOUT, 10);

        try {
            $ch->get();
            $code = $ch->info[CURLINFO_HTTP_CODE];
        }
        catch (\Exception $e) {
            $code = 404;
        }

        $contents = "";
        $d = 0;
        $h = "http://" . $this->url->domain . "/sitemap.xml";

        if ($code == 200) {
            $contents = $ch->data;
            $d = 100;
            $h = str_replace("https://", "http://", $ch->info[CURLINFO_EFFECTIVE_URL]);
        }

        if (isset($this->data['sitemaps'][$h])) {
            $this->data['sitemaps'][$h]['d'] = $d;
        }
        else {
            $this->data['sitemaps'][$h] = array(
                'xml' => $contents,
                'code' => $code,
                'd' => $d
            );
        }
    }

    public function output() {
        global $page;

        $sitemapsHTML = "";
        $i = 0;

        foreach ($this->data['sitemaps'] as $url => $params) {
            if ($params['code'] != 200) continue;

            $i++;

            $domain = parse_url($url);
            $type = "?";

            if (isset($domain['path'])) {
                if (strpos($domain['path'], ".") === false) $type = "?";
                else $type = strtoupper(substr($domain['path'], strpos($domain['path'], ".")+1));
            }

            if ($type != "XML") {
                $params['d'] = ceil($params['d'] * .67);
            }

            $codeimg = "<img src=\"" . $page->getPath() . "resources/images/x32.png\" width=\"16px\" style=\"margin: -2px 3px 0 0;\">";
            if ($params['code'] == 200) $codeimg = "<img src=\"" . $page->getPath() . "resources/images/check32.png\" width=\"16px\" style=\"margin: -2px 3px 0 0;\">";

            $odd = (($i % 2 == 0) ? "odd" : "");
            $id = urlencode(base64_encode(serialize($url)));
            $sec = strrev(md5(strrev($id)));

            $sitemapsHTML .= "<tr class=\"$odd\">
                <td>{$url}</td>
                <td class=\"center\">{$codeimg} {$params['code']}</td>
                <td class=\"center\">{$type}</td>
                <td class=\"center\"><a class=\"btn btn-green submit\" data-id=\"$id\" data-sec=\"$sec\">" . rt("Submit") . "</a></td>
            </tr>";
        }

        if ($i == 0) {
            throw new \Exception(rt("No sitemaps found to submit"));
        }

        $html = $this->getTemplate();
        $html = str_replace("[[SITEMAPS]]", $sitemapsHTML, $html);

        echo sanitize_trusted($html);
    }

    protected function getCacheKey() {
        return "";
    }
}
