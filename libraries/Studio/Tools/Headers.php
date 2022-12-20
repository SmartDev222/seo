<?php

namespace Studio\Tools;

use Exception;

class Headers extends Tool
{
    var $name = "Headers";
    var $id = "headers";
    var $template = "headers.html";
    var $icon = "headers";

    var $path = "/";

    public function prerun($url) {
        if (isset($_POST['path'])) {
            $p = $_POST['path'];
            if ($p == "" || ($p != "" && substr($p, 0, 1) != "/")) $p = "/$p";

            $this->path = $p;
        }
    }

    public function run() {
        if (stripos($this->path, "://") !== false) throw new Exception(rt("Invalid path"));

        $this->data = array(
            'raw' => null,
            'headers' => array(),
            'redirect' => null,
            'site' => null
        );

        $ch = new \Studio\Util\CURL("http://" . $this->url->domain);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, true);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_TIMEOUT, 10);
        $ch->setopt(CURLOPT_HEADER, true);
        $ch->setopt(CURLOPT_NOBODY, true);
        $ch->get();

        $effective = $ch->info[CURLINFO_EFFECTIVE_URL];
        $p = parse_url($effective);
        $this->data['site'] = $p['scheme'] . "://" . $p['host'];

        $ch = new \Studio\Util\CURL($this->data['site'] . $this->path);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, false);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_TIMEOUT, 10);
        $ch->setopt(CURLOPT_HEADER, true);
        $ch->setopt(CURLOPT_NOBODY, true);
        $ch->get();

        $this->data['raw'] = $ch->data;

        $data = trim($ch->data);
        $data = explode(PHP_EOL, $data);

        $this->data['headers'][] = array("Status", $ch->info[CURLINFO_HTTP_CODE], ($ch->info[CURLINFO_HTTP_CODE] == 200));
        if ($ch->info[CURLINFO_HTTP_CODE] == 301) $this->data['redirect'] = rt("This page permanently (301) redirects. See below for the new Location.");
        if ($ch->info[CURLINFO_HTTP_CODE] == 302) $this->data['redirect'] = rt("This page temporarily (302) redirects. See below for the new Location.");

        foreach ($data as $row) {
            if (strpos($row, ":") === false) continue;

            list($header, $value) = explode(':', $row);
            $this->data['headers'][] = array($header, trim($value), true);
        }
    }

    public function output() {
        global $page;

        $path = $page->getPath();
        $html = $this->getTemplate();

        $itemsHTML = "";
        foreach ($this->data['headers'] as $i => $header) {
            $odd = (($i % 2 == 0) ? "" : "odd");
            $check = "<img src=\"{$path}resources/images/check32.png\" width=\"16px\" alt=\"Yes\" style=\"margin: -1px 0 0 0;\" />";
            if (!$header[2]) $check = "<img src=\"{$path}resources/images/x32.png\" width=\"16px\" alt=\"No\" style=\"margin: -1px 0 0 0;\" />";

            if ($header[0] == "Status") $header[0] = "<strong>" . rt("Status") . "</strong>";

            $itemsHTML .= "<tr class=\"$odd\">
                <td>{$header[0]}</td>
                <td>{$header[1]}</td>
                <td class=\"center\">{$check}</td>
            </tr>";
        }

        if (!$this->data['redirect']) $this->data['redirect'] = rt("This page does not redirect.");

        $html = str_replace("[[REDIRECT]]", $this->data['redirect'], $html);
        $html = str_replace("[[ITEMS]]", $itemsHTML, $html);
        $html = str_replace("[[CODE]]", $this->data['raw'], $html);
        $html = str_replace("[[PATH]]", $this->path, $html);
        $html = str_replace("[[SITE]]", $this->data['site'], $html);
        echo sanitize_trusted($html);
    }

    public function record($data = "") {
        parent::record($this->path);
    }

    protected function getCacheKey() {
        return "";
    }
}
