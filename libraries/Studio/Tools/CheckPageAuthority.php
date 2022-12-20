<?php

namespace Studio\Tools;

use Exception;
use SEO\Services\LinkAssistant;

class CheckPageAuthority extends Tool
{
    var $name = "Check Page Authority";
    var $id = "page-authority";
    var $icon = "p7";
    var $template = "stat-with-path.html";

    var $path;

    public function prerun($url) {
        $this->path = "/";

        if (isset($_POST['path'])) {
            $p = $_POST['path'];

			if (strpos($p, '://') !== false) {
				$parsed = parse_url($p);

				if (!is_array($parsed)) {
					throw new Exception(rt("Invalid URL"));
				}

				if (isset($parsed['path'])) {
					$this->path = $parsed['path'];
				}
				else {
					$this->path = '/';
				}

				return;
			}

            if ($p == "" || ($p != "" && substr($p, 0, 1) != "/")) $p = "/$p";

            $this->path = $p;
        }
    }

    public function run() {
		$url = $this->url->scheme . '://' . $this->url->getHostname();
		$url .= '/' . ltrim($this->path ?: '', '/');

		$links = new LinkAssistant();
		$scores = $links->getAuthorityScores($url);
		$score = !empty($scores) ? $scores[0]->inlink_rank : 0;

		$this->data = [
			'url' => $url,
			'score' => $score
		];
    }

    public function output() {
        $p = parse_url($this->data['url']);
        $sitePrefix = $p['scheme'] . "://" . $p['host'];

        $html = $this->getTemplate();
        $html = str_replace("[[NAME]]", rt("Page Authority"), $html);
        $html = str_replace("[[VALUE]]", $this->data['score'] . ' / 100', $html);
        $html = str_replace("[[SITE]]", $sitePrefix, $html);
        $html = str_replace("[[PATH]]", sanitize_attribute($this->path), $html);
        echo sanitize_trusted($html);
    }

    protected function getCacheKey() {
        return $this->url->getHostname() . ':' . $this->path;
    }
}
