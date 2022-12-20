<?php

namespace Studio\Tools;

use SEO\Services\Alexa;
use SEO\Services\Google;
use SEO\Services\KeywordsEverywhere;
use Exception;
use SEO\Services\Keywords;
use SEO\Services\SpyFu;

class TopSearchQueries extends Tool
{
    var $name = "Top Search Queries";
    var $id = "top-search-queries";
    var $icon = "top-search-queries";
    var $template = "search-queries.html";

    public function run() {
        // The countries to check search queries for
        // Each additional country can add an additional 2-3 seconds to the tool's runtime
        // Note that only a handful of countries are supported, see SEO\Services\SpyFu::getCountryCodes()
        $countries = ['us', 'de', 'in'];

        $sf = new SpyFu();
        $keywords = [];

        foreach ($countries as $country) {
            foreach ($sf->getMostValuableKeywords($this->url->getHostname(), $country) as $keyword) {
                $keywords[] = $keyword;
            }
        }

        $this->data = [
            'keywords' => $keywords
        ];
    }

    public function output() {
        echo $this->renderTemplate();
    }
}
