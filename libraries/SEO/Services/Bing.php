<?php

namespace SEO\Services;

use Exception;
use \SEO\Common as Common;
use SEO\Parsers\BingSearchParser;
use Studio\Util\Http\WebRequest;

/**
 * Bing Scraper
 * Developed by Bailey Herbert
 * https://baileyherbert.com/
 *
 * This class was created for SEO Studio and can be used in compliance with the license you purchased for that product.
 * View CodeCanyon license specifications here: http://codecanyon.net/licenses ("Standard")
 */

class Bing
{
    private $url;

    public function __construct($url) {
        new \SEO\Helper\DOM;
        $this->url = $url;
    }

    /**
     * Performs a Bing query and returns an \SEO\Services\BingResult object
     * @param string $query The search query to perform
     * @param int    $start  The first row of results to retrieve (default 0)
     * @throws \SEO\Common\SEOException when blocked (code 1) or connect error (code 2)
     * @return BingSearchParser object containing results
     */
    public function query($query, $start = 0, $num = 10, $html = null) {
        global $studio;

        if ($html === null) {
            $query = urlencode($query);

            if ($start > 0) $start = "&first=$start";
            else $start = "";

            if ($num > 10) $num = "&count=$num";
            else $num = "";

            $web = new WebRequest("https://www.bing.com/search?q={$query}{$start}{$num}");
            $web->setTimeout(10);
            $web->setHeader('DNT', '1');
            $web->setReferer('https://www.bing.com/search');
            $web->setOption(CURLOPT_FOLLOWLOCATION, false);

            $ipv4 = (int)$studio->getopt('bing-ipv4-enforced', '0');

            if ($ipv4 > 0 && defined('CURL_IPRESOLVE_V4')) {
                $web->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }

            $response = $web->get();
            $code = $response->getStatusCode();
            $data = $response->getBody();

            if ($code === 301 || $code === 302) {
                $location = $response->getHeader('location');
                $location = is_array($location) ? $location[0] : $location;

                if (!is_string($location)) {
                    throw new Exception('Illegal redirect');
                }

                if (stripos($location, 'cn.bing.com') !== false) {
                    if ($ipv4 === 0) {
                        $studio->setopt('bing-ipv4-enforced', '1');
                        return $this->query($query, $start, $num, $html);
                    }

                    throw new Common\SEOException("Bing rejected our query because origin country is China", 1);
                }
            }

            if ($code !== 200) {
                throw new Common\SEOException("Unexpected response from Bing ($code)", 1);
            }
        }
        else {
            $data = $html;
        }

        return new BingSearchParser($data);
    }

    /**
    * Gets the indexed pages for the website
    * @param int    $page  The results page to retrieve (default 1)
    * @throws \SEO\Common\SEOException when blocked (code 1) or connect error (code 2)
    * @return BingSearchParser object containing results
    */
    public function getIndexedPages($page = 0, $num = 10) {
        return $this->query("site:{$this->url->domain}", $page, $num);
    }
}
