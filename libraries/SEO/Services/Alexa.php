<?php

namespace SEO\Services;

use SEO\Helper\DOM;
use SEO\Common\SEOException;
use Exception;

/**
 * Alexa Scraper
 * Developed by Bailey Herbert
 * https://baileyherbert.com/
 *
 * This class was created for SEO Studio and can be used in compliance with the license you purchased for that product.
 * View CodeCanyon license specifications here: http://codecanyon.net/licenses ("Standard")
 */

class Alexa
{
    private $url;
    private $dom;

    public function __construct($url) {
        new DOM;
        $this->url = $url;

        $ch = curl_init("https://www.alexa.com/siteinfo/{$url->domain}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_ENCODING, "");
    	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 9.0; AOL 9.7; AOLBuild 4343.19; Windows NT 6.1; WOW64; Trident/5.0; FunWebProducts)");
    	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "DNT: 1"
        ));
    	$data = curl_exec($ch);

        if (curl_errno($ch) > 0) {
            throw new SEOException("Connect error", 1);
        }

        $this->dom = \SEO\Helper\str_get_html($data);
        if (!is_object($this->dom)) {
            throw new SEOException("Alexa parse failed, try again.", 1);
        }
    }

    public function globalRank() {
        try {
            $rank = $this->dom->find('.rank-global p.data', 0);
            if (is_null($rank)) throw new Exception('Cannot select rank.');

            $rank = preg_replace('/[^0-9]/', '', $rank->plaintext);
            return (int)$rank;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function percentBounceRate() {
        try {
            $metrics = $this->dom->find('#card_metrics', 0);
            if (is_null($metrics)) throw new Exception('Cannot select site metrics.');

            $column = $metrics->find('.Third', 2);
            if (is_null($metrics)) throw new Exception('Cannot select site metric column.');

            $element = $column->find('.small', 0);
            if (is_null($element)) throw new Exception('Cannot select bounce rate element.');

            $text = $element->plaintext;
            $text = trim(substr($text, 0, strpos($text, '%')));

            return (float)$text;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function dailyPageviewsPerVisitor() {
        try {
            $metrics = $this->dom->find('#card_metrics', 0);
            if (is_null($metrics)) throw new Exception('Cannot select site metrics.');

            $column = $metrics->find('.Third', 0);
            if (is_null($metrics)) throw new Exception('Cannot select site metric column.');

            $element = $column->find('.small', 0);
            if (is_null($element)) throw new Exception('Cannot select daily pageviews element.');

            $text = $element->plaintext;
            $text = trim(substr($text, 0, strpos($text, '%')));

            return (float)$text;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function dailyTimeOnSite() {
        try {
            $metrics = $this->dom->find('#card_metrics', 0);
            if (is_null($metrics)) throw new Exception('Cannot select site metrics.');

            $column = $metrics->find('.Third', 1);
            if (is_null($metrics)) throw new Exception('Cannot select site metric column.');

            $element = $column->find('.small', 0);
            if (is_null($element)) throw new Exception('Cannot select daily time element.');

            $text = trim($element->plaintext);
            $text = trim(substr($text, 0, strpos($text, ' ')));
            // 2019: convert to hours, minutes, seconds

            return $text;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function percentTrafficFromSearch() {
        try {
            $panel = $this->dom->find('.traffic_source .FolderTarget .row-fluid', 0);
            if (is_null($panel)) throw new Exception('Cannot select traffic sources panel.');

            $sites = array();

            foreach ($panel->find('.flex') as $row) {
                $nameColumn = $row->find('.Third', 0);
                $progressColumn = $row->find('.ThirdFull', 0);

                $percent = (float)preg_replace('/[^0-9\.]/', '', $progressColumn->find('.num', 0)->plaintext);
                $domain = trim($nameColumn->plaintext);

                $sites[$domain] = $percent;
            }

            $myDomain = str_ireplace('www.', '', $this->url->domain);

            if (isset($sites[$myDomain])) {
                if ($sites[$myDomain] > 0) {
                    return $sites[$myDomain];
                }
            }

            return false;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function topSearchKeywords() {
        try {
            $results = array();

            $table = $this->dom->find('.topkeywords .table .Body', 0);
            if (is_null($table)) throw new Exception('Cannot select top keywords table.');

            foreach ($table->find('.Row') as $row) {
                $keyword = trim($row->find('.keyword', 0)->plaintext);
                $searchTrafficRaw = trim($row->find('.metric_one', 0)->plaintext);
                $voiceShareRaw = trim($row->find('.metric_two', 0)->plaintext);

                $searchTrafficPercent = (float)preg_replace('/[^0-9.]/', '', $searchTrafficRaw);

                $results[] = array(
                    'keyword' => $keyword,
                    'percent' => $searchTrafficPercent
                );
            }

            return $results;
        }
        catch (Exception $e) {
            return array();
        }
    }

    public function topReferrers() {
        try {
            $results = array();

            $panel = $this->dom->find('#card_metrics', 0);
            if (is_null($panel)) throw new Exception('Cannot select site metrics panel.');

            $column = $panel->find('.stream .flex .Half', 0);
            if (is_null($column)) throw new Exception('Cannot select referral sites column.');

            foreach ($column->find('p') as $item) {
                $text = trim($item->plaintext);
                $columns = preg_split('/[\s]+/', $text);

                if (count($columns) == 2) {
                    $percent = (float)preg_replace('/[^0-9.]/', '', $columns[0]);
                    $domain = $columns[1];

                    $results[] = array(
                        'domain' => $domain,
                        'percent' => $percent
                    );
                }
            }

            if (empty($results)) return false;
            return $results;
        }
        catch (Exception $e) {
            return false;
        }
    }

    public function numberBacklinks() {
        try {
            $section = $this->dom->find('.linksin', 0);
            if (is_null($section)) return false;

            $data = $section->find('.enun .data', 0);
            if (is_null($data)) return false;

            return (int)preg_replace('/[^0-9]/', '', $data->plaintext);
        }
        catch (Exception $e) {
            return false;
        }
    }
}
