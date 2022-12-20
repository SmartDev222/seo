<?php

namespace SEO\Helper;

/**
 * Converts a URL into a universal object
 */
class Url
{
    public $domain;
    public $scheme;
    public $path;
    public $query;

    public function __construct($url) {
        global $studio;

        if (stripos($url, "://") === false)
            $url = "http://" . $url;

        if (filter_var($url, FILTER_VALIDATE_URL) === false)
            throw new \SEO\Common\SEOException("Invalid URL");

        $parse = @parse_url($url);
        if (!isset($parse['host']))
            throw new \SEO\Common\SEOException("Invalid URL");

        if (isset($parse['host'])) $this->domain = strtolower($parse['host']);
        if (isset($parse['scheme'])) $this->scheme = strtolower($parse['scheme']);
        if (isset($parse['path'])) $this->path = $parse['path'];
        if (isset($parse['query'])) $this->query = $parse['query'];

        if ($studio->getopt('allow-local-hostnames') !== 'On') {
            if (strpos($this->domain, '.') === false) {
                throw new \SEO\Common\SEOException("Invalid URL");
            }

            if (filter_var($this->domain, FILTER_VALIDATE_IP) !== false) {
                if (filter_var($this->domain, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    throw new \SEO\Common\SEOException("Invalid URL");
                }
            }
        }
    }

    /**
     * Returns the hostname.
     *
     * @return string
     */
    public function getHostname() {
        return $this->domain;
    }

    /**
     * Returns an absolute URL.
     *
     * @return string
     */
    public function getAbsoluteURL() {
        $path = $this->path ?: '/';
        $scheme = $this->scheme ?: 'https';

        return $scheme . '://' . $this->domain . $path;
    }

}
