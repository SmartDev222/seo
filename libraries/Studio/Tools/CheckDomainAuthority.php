<?php

namespace Studio\Tools;

use Exception;
use SEO\Services\LinkAssistant;

class CheckDomainAuthority extends Tool
{
    var $name = "Check Domain Authority";
    var $id = "domain-authority";
    var $icon = "p6";
    var $template = "stat.html";

    public function run() {
		$links = new LinkAssistant();
		$scores = $links->getAuthorityScores($this->url->getAbsoluteURL());

		if (empty($scores)) {
			throw new Exception(rt("No rank available yet."));
		}

		$this->data = $scores[0]->domain_inlink_rank;
    }

    public function output() {
        $html = $this->getTemplate();
        $html = str_replace("[[NAME]]", rt("Domain Authority"), $html);
        $html = str_replace("[[VALUE]]", $this->data . " / 100", $html);
        echo sanitize_trusted($html);
    }
}
