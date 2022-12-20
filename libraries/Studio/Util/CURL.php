<?php

namespace Studio\Util;

/**
 * Object-oriented CURL with included support for FOLLOWLOCATION on servers with open_basedir restrictions.
 */
class CURL
{
    private $url;
    private $options;
    /**
     * The error number for the HTTP request (or 0 if no error).
     */
    public $errno;
    /**
     * A string formatted error message about the HTTP request.
     */
    public $error;
    /**
     * A string containing the data returned from the HTTP request.
     */
    public $data;
    /**
     * An array of all CURLINFO variables about the operation.
     */
    public $info;

    public function __construct($url) {
        $this->url = $url;
        $this->options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_ENCODING => "",
                CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36"
        );
    }

    /**
     * Executes the CURL request and updates properties.
     * @throws Exception on connect error (code 1).
     */
    public function get() {
        $this->errno = 0;
        $this->error = '';
        $this->data = '';
        $this->info = array();

        if ($this->options[CURLOPT_FOLLOWLOCATION]) $url = $this->follow($this->url);
        else $url = $this->url;

        if ($this->options[CURLOPT_FOLLOWLOCATION]) unset($this->options[CURLOPT_FOLLOWLOCATION]);

        $ch = curl_init($url);
        @curl_setopt_array($ch, $this->options);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        @session_write_close();
        $data = curl_exec($ch);
        @session_start();

        $this->errno = curl_errno($ch);
        $this->error = curl_error($ch);

        if ($this->errno > 0) {
            throw new \Exception("Connect failed: {$this->error}", 1);
        }

        $this->data = $data;

        $this->info = array(
            CURLINFO_EFFECTIVE_URL => @curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
            CURLINFO_HTTP_CODE => @curl_getinfo($ch, CURLINFO_HTTP_CODE),
            CURLINFO_FILETIME => @curl_getinfo($ch, CURLINFO_FILETIME),
            CURLINFO_TOTAL_TIME => @curl_getinfo($ch, CURLINFO_TOTAL_TIME),
            CURLINFO_NAMELOOKUP_TIME => @curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME),
            CURLINFO_CONNECT_TIME => @curl_getinfo($ch, CURLINFO_CONNECT_TIME),
            CURLINFO_SIZE_UPLOAD => @curl_getinfo($ch, CURLINFO_SIZE_UPLOAD),
            CURLINFO_SIZE_DOWNLOAD => @curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD),
            CURLINFO_SPEED_DOWNLOAD => @curl_getinfo($ch, CURLINFO_SPEED_DOWNLOAD),
            CURLINFO_SPEED_UPLOAD => @curl_getinfo($ch, CURLINFO_SPEED_UPLOAD),
            CURLINFO_REQUEST_SIZE => @curl_getinfo($ch, CURLINFO_REQUEST_SIZE),
            CURLINFO_CONTENT_TYPE => @curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
            CURLINFO_SSL_VERIFYRESULT => @curl_getinfo($ch, CURLINFO_SSL_VERIFYRESULT)
        );
    }

    /**
     * Follows redirects to find the final URL.
     * @param String $url The URL to resolve.
     * @param int $try (For internal usage) the number of redirects so far, for limitation purposes.
     */
    public function follow($url, $try = 1) {
        $lasturl = $url;

        $ch = curl_init(trim($url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36");
        if (isset($this->options[CURLOPT_USERAGENT])) curl_setopt($ch, CURLOPT_USERAGENT, $this->options[CURLOPT_USERAGENT]);

        $data = curl_exec($ch);

        curl_close($ch);
        $data = trim($data);
        $data = explode(PHP_EOL, $data);

        if ($try > $this->options[CURLOPT_MAXREDIRS]) {
            throw new \Exception("Too many redirects ($try).", 1);
        }

        foreach ($data as $row) {
            $e = explode(': ', $row);

            if (strtolower($e[0]) == 'location') {
                $href = $e[1];
                $p = parse_url($lasturl);

                if (substr($href, 0, 2) == "//") $href = $p['scheme'] . "://" . substr($href, 2);
                if (substr($href, 0, 1) == "/") $href = $p['scheme'] . "://" . $p['host'] . $href;
                if (substr($href, 0, 2) == "./") $href = $p['scheme'] . "://" . $p['host'] . "/" . substr($href, 2);
                if (stripos($href, "://") === false) $href = $p['scheme'] . "://" . $p['host'] . "/" . $href;

                $lasturl = $this->follow($href, ++$try);
            }
        }

        return trim($lasturl);
    }

    /**
     * Sets a CURL option.
     * @param CURLOPT $opt The CURLOPT_ constant.
     * @param mixed $val The value to set the option to.
     */
    public function setopt($opt, $val) {
        $this->options[$opt] = $val;
    }
}
