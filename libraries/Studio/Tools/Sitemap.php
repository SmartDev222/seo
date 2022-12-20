<?php

namespace Studio\Tools;

use Studio\Util\Http\WebRequest;
use Studio\Util\Parsers\HTMLDocument;

class Sitemap extends Tool
{
    var $name = "Sitemap";
    var $id = "sitemap";
    var $icon = "sitemap";
    var $template = "sitemap.html";

    public function run() {
        # First, check robots.txt for a sitemap file.

        $this->data = array(
            'sitemaps' => array(),
            'code' => 0
        );

        $total = 0;

        $request = new WebRequest('http://' . $this->url->getHostname() . '/robots.txt');
        $request->setTimeout(10);

        try {
            $response = $request->get();
            $code = $response->getStatusCode();
        }
        catch (\Exception $e) {
            $code = 404;
        }

        if ($code == 200) {
            # We have a working sitemap file. Let's see if it has any sitemaps.

            $s = new \SEO\Parsers\Robots($response->getBody());
            $sitemaps = $s->getSitemaps();

            if (count($sitemaps) > 0) {
                foreach ($sitemaps as $arr) {
                    $contents = "";

                    $request = new WebRequest($arr['href']);
                    $request->setTimeout(10);

                    try {
                        $response = $request->get();
                        $code = $response->getStatusCode();
                    }
                    catch (\Exception $e) {
                        $code = 404;
                    }

                    $contents = "";
                    $d = 0;

                    if ($code == 200) {
                        $contents = $response->getBody();
                        $d = 50;
                    }

                    $h = trim($response->getUrl());

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

        $h = 'http://' . $this->url->getHostname() . '/sitemap.xml';
        $request = new WebRequest($h);
        $request->setTimeout(10);

        try {
            $response = $request->get();
            $code = $response->getStatusCode();
        }
        catch (\Exception $e) {
            $code = 404;
        }

        $contents = "";
        $d = 0;
        $h = $response ? trim($response->getUrl()) : $h;

        if ($code == 200) {
            $contents = $response->getBody();
            $numHops = count($response->getTraces());
            $d = 100;

            if ($numHops > 1) {
                $d -= ($numHops - 1) * 2;
            }
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

        $dom = new HTMLDocument($contents);
        $extended = $dom->find('sitemap loc');
        $baseD = $d;

        // We'll only do a single layer of this for performance reasons
        foreach ($extended as $element) {
            $url = trim($element->getPlainText());

            if (preg_match('/<!\[cdata\[(.+)\]\]/i', $url, $matches)) {
                $url = trim($matches[1]);
            }

            $request = new WebRequest($url);
            $request->setTimeout(10);

            try {
                $response = $request->get();
                $code = $response->getStatusCode();
            }
            catch (\Exception $e) {
                $code = 404;
            }

            $d = floor($baseD * .9);
            $numHops = count($response->getTraces());

            if ($numHops > 1) {
                $d -= ($numHops - 1) * 2;
            }

            $this->data['sitemaps'][trim($response->getUrl())] = array(
                'xml' => $response->getBody(),
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
            $i++;

            $domain = parse_url($url);
            $type = "?";
            $entries = '?';

            if (isset($domain['path'])) {
                if (strpos($domain['path'], ".") === false) $type = "?";
                else $type = strtoupper(substr($domain['path'], strpos($domain['path'], ".")+1));
            }

            $selector = '<loc>';
            if ($type === 'RSS') $selector = '<link>';

            if ($type != "XML" && $type !== 'RSS') {
                $params['d'] = ceil($params['d'] * .67);
            }
            else if ($params['code'] === 200) {
                $entries = number_format(substr_count(strtolower($params['xml']), $selector));
            }

            $codeimg = "<img src=\"" . $page->getPath() . "resources/images/x32.png\" width=\"16px\" style=\"margin: -2px 3px 0 0;\">";
            if ($params['code'] == 200) $codeimg = "<img src=\"" . $page->getPath() . "resources/images/check32.png\" width=\"16px\" style=\"margin: -2px 3px 0 0;\">";

            $odd = (($i % 2 == 0) ? "odd" : "");

            $sitemapsHTML .= "<tr class=\"$odd\">
                <td>{$url}</td>
                <td class=\"center\">{$codeimg} {$params['code']}</td>
                <td class=\"center\">{$type}</td>
                <td class=\"center\">{$entries}</td>
                <td class=\"center\">{$params['d']}%</td>
            </tr>";
        }

        $html = $this->getTemplate();
        $html = str_replace("[[SITEMAPS]]", $sitemapsHTML, $html);

        echo sanitize_trusted($html);
    }

    protected function getCacheKey() {
        return "";
    }
}
