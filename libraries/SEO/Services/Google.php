<?php

namespace SEO\Services;
use \SEO\Common as Common;
use \SEO\Helper as Helper;
use SEO\Parsers\GoogleSearchParser;

/**
 * Google Scraper
 * Developed by Bailey Herbert
 * https://baileyherbert.com/
 *
 * This class was created for SEO Studio and can be used in compliance with the license you purchased for that product.
 * View CodeCanyon license specifications here: http://codecanyon.net/licenses ("Standard")
 */

class Google
{
    private $url;

    protected $countryCode;
    protected $latitude;
    protected $longitude;

    public function __construct($url, $countryCode = null, $latitude = null, $longitude = null) {
        $this->url = $url;
        $this->countryCode = $countryCode;
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        new Helper\DOM;
    }

    /**
     * Performs a Google query and returns an \SEO\Services\GoogleResult object
     * @param String $query The search query to perform
     * @param int    $page  The results page to retrieve (default 1)
     * @param String $html  The HTML code to use, or null to download from Google (default null)
     * @throws \SEO\Common\SEOException when blocked (code 1) or connect error (code 2)
     * @return GoogleSearchParser object containing results
     */
    public function query($query, $page = 1, $num = 10, $html = null) {
        global $language, $studio;

        $query = urlencode($query);
        $start = ($num * $page) - $num;

        if ($start > 0) $start = "&start=$start";
        else $start = "";

        if ($num > 10) $num = "&num=$num";
        else $num = "";

        $uule = $this->getUULE();
        if (!empty($uule)) {
            $uule = '&uule=' . $uule;
        }

        $country = "";
        if (isset($this->countryCode)) {
            $country = "&gl=" . strtoupper($this->countryCode);
        }

        $ch = curl_init("https://www.google.com/search?q={$query}{$country}&gws_rd=ssl{$start}{$num}&ie=UTF-8{$uule}");
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	curl_setopt($ch, CURLOPT_ENCODING, '');
    	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36");

        if ($html == null) {
            $data = curl_exec($ch);
            $code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch) > 0) throw new Common\SEOException("CURL error: " . curl_error($ch), 2);
            if ($code != 200) throw new Common\SEOException("Blocked (http $code)", 1);
        }
        else {
            $data = $html;
        }

        /**
         * Developer note -- 02/09/2022
         *
         * The following parser was introduced in v1.85.5 and is based on my own machine learning strategy for parsing
         * search results without hardcoding element queries. This parser should be resilient to most interface changes
         * by Google, although it's still very new and more testing in the wild is ideal.
         *
         * If you have any issues or questions with regards to this parser, please contact me at hello@bailey.sh.
         *
         * Copyright © 2022 Bailey Herbert
         * Redistribution or publicization of this code is prohibited.
         */
        return new GoogleSearchParser($data);
    }

    protected function getUULE() {
        if (is_int($this->latitude) && is_int($this->longitude)) {
            $lines = ['role:1', 'producer:12', 'provenance:6'];

            $microtime = explode(".", strval(microtime(true)));
            $lines[] = sprintf('timestamp:%s', str_pad($microtime[0] . $microtime[1], 16, '0'));
            $lines[] = 'latlng{';
            $lines[] = sprintf('latitude_e7:%s', strval($this->latitude));
            $lines[] = sprintf('longitude_e7:%s', strval($this->longitude));
            $lines[] = '}';
            $lines[] = 'radius:93000';

            $encoded = urlencode(base64_encode(implode("\n", $lines)));
            return 'a+' . $encoded;
        }

        return '';
    }

    /**
    * Gets the indexed pages for the website
    * @param int    $page  The results page to retrieve (default 1)
    * @param int    $num   The number of results to retrieve per page (default is at recommended value of 10)
    * @throws \SEO\Common\SEOException when blocked (code 1) or connect error (code 2)
    * @return GoogleSearchParser object containing results
    */
    public function getIndexedPages($page = 1, $num = 10) {
        return $this->query("site:{$this->url->domain}", $page, $num);
    }

    /**
     * Gets the monthly number of searches for a specific query.
     * @param String $query The search query
     * @return array (cpc, vol, value, keyword) or (0, 0, 0, "")
     */
    public static function getSearchVolume($query) {
        $query = strtolower(trim($query));
        $myWords = explode(" ", $query);

        $export = "https://app.serps.com/tools/keywords.json";

        $ch = curl_init($export);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_ENCODING, "identity");
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36");
        curl_setopt($ch, CURLOPT_REFERER, "https://serps.com/tools/keyword-research/");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'q' => $query
        ));
        $data = @curl_exec($ch);
        if (curl_errno($ch) > 0) return array(0, 0, 0, "");

        $data = @json_decode($data, true);
        if (!$data) return array(0, 0, 0, "");

        foreach ($data['keywords'] as $keyword) {
            $info = $keyword['Tool'];

            $cpc = $info['cpc'];
            $text = $info['text'];
            $vol = $info['vol'];
            $value = $info['value'] / 100;

            $kwWords = explode(" ", $text);
            $match = true;

            if (count($kwWords) != count($myWords)) $match = false;
            foreach ($myWords as $w) if (!in_array($w, $kwWords)) $match = false;

            if ($match) {
                return array($cpc, $vol, $value, $text);
            }
        }

        $max = 0;
        $maxVol = 0;

        foreach ($data['keywords'] as $i => $keyword) {
            $info = $keyword['Tool'];
            $vol = $info['vol'];

            if ($vol > $maxVol) {
                $max = $i;
                $maxVol = $vol;
            }
        }

        if ($maxVol === 0) return array(0, 0, 0, "");

        $info = $data['keywords'][$max]['Tool'];

        $cpc = $info['cpc'] / 100;
        $text = $info['text'];
        $vol = $info['vol'];
        $value = $info['value'] / 100;

        return array($cpc, $vol, $value, $text);
    }
}
