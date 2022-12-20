<?php

namespace Studio\Tools;

use SEO\Helper\Url;
use SEO\Services\LinkAssistant;

class Competition extends Tool
{
    var $name = "Competition";
    var $id = "competition";
    var $icon = "competition";
    var $template = "competition.html";

    var $keyword = "";
    var $you;

    public function prerun($url) {
        if (isset($_POST['keyword'])) {
            $this->keyword = sanitize_attribute(strtolower(trim($_POST['keyword'])));
        }
    }

    public function run() {
        @ini_set('max_execution_time', 120);

        if ($this->keyword != "") {
            $links = new LinkAssistant();
            $google = new \Studio\Ports\Google($this->url);
            $query = $google->query($_POST['keyword'], 1, 100);

            $results = array();
            $resultLinks = array();
            $targetRank = -1;

            foreach ($query->getResults() as $result) {
                $url = @parse_url($result->href);
                if (!is_array($url) || !isset($url['host'])) continue;
                $host = strtolower($url['host']);
                $stats = array();
                $isTarget = false;

                if ($host === strtolower($this->url->getHostname())) {
                    $isTarget = true;
                }

                else if (str_replace('www.', '', $host) === str_replace('www.', '', strtolower($this->url->getHostname()))) {
                    $isTarget = true;
                }

                if ($targetRank < 0 && $isTarget) {
                    $targetRank = count($results) + 1;
                }

                $resultLinks[] = (new Url($result->href))->getAbsoluteURL();
                $results[] = [
                    'rank' => count($results) + 1,
                    'title' => $result->title,
                    'host' => $host,
                    'url' => $result->href,
                    'is_target' => $isTarget
                ];
            }

            // If the target wasn't found, show only the first 20 results
            if ($targetRank < 0) {
                $resultLinks = array_slice($resultLinks, 0, 20);
                $results = array_slice($results, 0, 20);
            }

            // Otherwise, show the target with the 15 links before
            else {
                $results = array_slice($results, max($targetRank - 6, 0), 11);
                $resultLinks = array_slice($resultLinks, max($targetRank - 6, 0), 11);
            }

            // Look up website statistics
            $resultLinks[] = $this->url->getAbsoluteURL();
            $stats = $links->getMetrics($resultLinks);
            $statsForTarget = array_pop($stats);

            if (!$statsForTarget) {
                $this->data = [];
                return;
            }

            foreach ($results as $i => &$result) {
                $result['metrics'] = $stats[$i];
            }

            $targetResult = [
                'rank' => $targetRank,
                'host' => $this->url->getHostname(),
                'url' => $this->url->getAbsoluteURL(),
                'metrics' => $statsForTarget
            ];

            // Look up authorities
            $scores = $links->getAuthorityScores($resultLinks);
            $scoresForTarget = array_pop($scores);

            foreach ($results as $i => &$result) {
                $result['inlink_rank'] = $scores[$i]->inlink_rank;
                $result['domain_inlink_rank'] = $scores[$i]->domain_inlink_rank;
            }

            $targetResult['inlink_rank'] = $scoresForTarget->inlink_rank;
            $targetResult['domain_inlink_rank'] = $scoresForTarget->domain_inlink_rank;

            $this->data = [
                'results' => $results,
                'target' => $targetResult,
                'showTarget' => $targetRank < 0
            ];
        }
    }

    public function output() {
        echo $this->renderTemplate([
            'keyword' => $this->keyword
        ]);
    }

    public function record($data = "") {
        if ($this->keyword != "") parent::record("Keyword: " . $this->keyword);
    }

    protected function getCacheKey() {
        return $this->id . ":" . $this->keyword;
    }
}
