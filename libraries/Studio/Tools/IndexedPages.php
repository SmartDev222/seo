<?php

namespace Studio\Tools;

use Exception;

class IndexedPages extends Tool
{
    var $name = "Indexed Pages";
    var $id = "indexed-pages";
    var $icon = "indexed-pages";
    var $template = "indexed-pages.html";

    public function run() {
        $data = array(
            'google' => 0,
            'bing' => 0
        );

        try {
            $google = new \Studio\Ports\Google($this->url);
            $parser = $google->getIndexedPages();
            $data['google'] = $parser->getNumTotalResults();
        }
        catch (Exception $e) {
            $data['google'] = 0;
        }

        try {
            $bing = new \Studio\Ports\Bing($this->url);
            $parser = $bing->getIndexedPages();
            $data['bing'] = $parser->getNumTotalResults();
        }
        catch (Exception $e) {
            var_dump($e);
            $data['bing'] = 0;
        }

        $this->data = $data;
    }

    public function output() {
        $html = $this->getTemplate();

        $html = str_replace("[[GOOGLE]]", number_format($this->data['google']), $html);
        $html = str_replace("[[BING]]", number_format($this->data['bing']), $html);

        echo sanitize_trusted($html);
    }
}
