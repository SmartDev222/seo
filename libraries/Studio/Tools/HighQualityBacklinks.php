<?php

namespace Studio\Tools;

use Exception;
use SEO\Common\SEOException;
use SEO\Services\LinkAssistant;
use SEO\Services\Structures\LinkAssistantBacklink;
use SEO\Services\Structures\LinkAssistantBacklinkSearch;
use Studio\Util\Http\WebRequestException;

class HighQualityBacklinks extends Tool
{
    var $name = "High Quality Backlinks";
    var $id = "high-quality-backlinks";
    var $icon = "high-quality-backlinks";
    var $template = "backlinks.html";

    public function run() {
        try {
            $links = new LinkAssistant();

            $search = new LinkAssistantBacklinkSearch();
            $search->target = $this->url->getAbsoluteURL();
            $search->limit = 250;
            $search->orderBy = 'inlink_rank';
            $search->perDomain = 5;
            $search->mode = 'host';

            $backlinks = $links->getBacklinks($search);

            $this->data = [
                'counts' => $links->getBacklinkCounts($this->url->getAbsoluteURL()),
                'backlinks' => $backlinks
            ];
        }
        catch (SEOException $ex) {
            throw new Exception(rt($ex->getMessage()), $ex->getCode());
        }
        catch (WebRequestException $ex) {
            throw new Exception(rt("We're having some technical difficulties. Please try again later."));
        }
        catch (Exception $ex) {
            throw new Exception(rt("Unknown error."));
        }
    }

    public function output() {
        echo $this->renderTemplate([
            'title' => rt('Top Backlinks')
        ]);
    }
}
