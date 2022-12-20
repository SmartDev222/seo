<?php

namespace Studio\Tools;

use Exception;
use SEO\Common\SEOException;
use SEO\Services\LinkAssistant;
use Studio\Util\Http\WebRequestException;

class NewBacklinks extends Tool
{
    var $name = "New Backlinks";
    var $id = "new-backlinks";
    var $icon = "new-backlinks";
    var $template = "backlinks.html";

    public function run() {
        try {
            $links = new LinkAssistant();

            $backlinks = $links->getNewBacklinks(
                $this->url->getAbsoluteURL(),
                250,
                '1 year',
                'host'
            );

            $this->data = [
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
            'title' => rt('New Backlinks')
        ]);
    }
}
