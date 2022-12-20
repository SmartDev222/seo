<?php

namespace Studio\Tools;

use Exception;
use SEO\Common\SEOException;
use SEO\Services\LinkAssistant;
use SEO\Services\Structures\LinkAssistantBacklink;
use SEO\Services\Structures\LinkAssistantBacklinkSearch;
use Studio\Util\Http\WebRequestException;

class PoorBacklinks extends Tool
{
    var $name = "Poor Backlinks";
    var $id = "poor-backlinks";
    var $icon = "poor-backlinks";
    var $template = "backlinks.html";

    public function run() {
        try {
            $links = new LinkAssistant();

            $search = new LinkAssistantBacklinkSearch();
            $search->target = $this->url->getAbsoluteURL();
            $search->limit = 500;
            $search->orderBy = 'date_found';
            $search->perDomain = 5;
            $search->mode = 'host';

            $backlinks = $links->getBacklinks($search);

            $this->data = [
                'backlinks' => array_slice(array_filter($backlinks, function(LinkAssistantBacklink $backlink) {
                    return $backlink->inlink_rank < 7 && !$backlink->nofollow;
                }), 0, 250)
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
            'title' => rt('Poor Backlinks')
        ]);
    }
}
