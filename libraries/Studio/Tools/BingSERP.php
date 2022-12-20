<?php

namespace Studio\Tools;

use SEO\Helper\Url;
use SEO\Services\LinkAssistant;

class BingSERP extends Tool
{
    var $name = "Bing SERP";
    var $id = "bing-serp";
    var $template = "serp.html";
    var $icon = "bing-serp";

    var $keyword;

    public function prerun($url) {
        $this->keyword = "";
        if (isset($_POST['keyword'])) $this->keyword = trim(strtolower($_POST['keyword']));
    }

    public function run() {
        if ($this->keyword !== "") {
            $lookingFor = str_replace('www.', '', $this->url->getHostname());
            $lookingForExp = '/\b' . preg_quote($lookingFor, '/') . '/im';

            $targetRank = null;
            $currentIndex = 0;
            $results = [];

            /**
             * This variable adjusts the number of pages to fetch from search results.
             *
             * PLEASE READ CAREFULLY: If you are using the free included Bing proxying service, changing this can
             * quickly deplete your rate limit and drastically increase the time this tool takes to load.
             *
             * This will be increased to 2-3 in a future update once the service is capable of delivering multiple
             * pages quickly. Right now, it's simply too slow.
             */
            $maxPages = 2;

            for ($page = 1; $page <= $maxPages; $page++) {
                $google = new \Studio\Ports\Bing($this->url);
                $parser = $google->query($this->keyword, $currentIndex, 100);

                foreach ($parser->getResults() as $result) {
                    $index = ++$currentIndex;
                    $isMatch = preg_match($lookingForExp, $result->href);

                    if ($targetRank === null && $isMatch) {
                        $targetRank = $index;
                    }

                    $arr = $result->detach();
                    $arr['rank'] = $index;
                    $arr['hostname'] = (new Url($result->href))->getHostname();
                    $arr['is_match'] = $isMatch;

                    $results[] = $arr;
                }

                if ($targetRank !== null) {
                    break;
                }

                if ($parser->getNumTotalResults() < $parser->getNumResults() || $parser->getNumTotalResults() === 0) {
                    break;
                }
            }

            $chunks = array_chunk($results, 100, true);
            $links = new LinkAssistant();

            foreach ($chunks as $chunk) {
                $urls = [];
                $indices = [];

                foreach ($chunk as $index => $result) {
                    $urls[] = $result['href'];
                    $indices[] = $index;
                }

                foreach ($links->getAuthorityScores($urls) as $index => $scores) {
                    $index = $indices[$index];
                    $results[$index]['inlink_rank'] = $scores->inlink_rank;
                    $results[$index]['domain_inlink_rank'] = $scores->domain_inlink_rank;
                }
            }

            $this->data = [
                'results' => $results,
                'rank' => $targetRank,
                'numRows' => count($results)
            ];
        }
    }

    public function output() {
        if ($this->data) {
            $rank = $this->data['rank'];
            $count = $this->data['numRows'];

            if ($rank === null) {
                $rank = rt('{$1} did not appear in the first {$2} results.', $this->url->domain, $count);
            }
            else {
                $rank = rt('{$1} was #{$2} in the search results.', $this->url->domain, $rank);
            }
        }
        else {
            $rank = rt("Enter a keyword to find your rank.");
        }

        echo $this->renderTemplate([
            'title' => rt("Bing Results"),
            'keyword' => $this->keyword,
            'rank' => $rank
        ]);

        return;
    }

    public function record($data = "") {
        if ($this->keyword != "") parent::record("Keyword: " . $this->keyword);
    }

    protected function getCacheKey() {
        return $this->id . ":" . $this->keyword . ":v2";
    }
}
