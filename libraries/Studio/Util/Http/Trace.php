<?php

namespace Studio\Util\Http;

class Trace {

    protected $uri;
    protected $code;
    protected $bytes;

    protected $ip;
    protected $port;

    protected $totalTime;
    protected $connectTime;
    protected $nameLookupTime;

    protected $errorCode;
    protected $errorMessage;

    /**
     * Constructs a new trace instance.
     *
     * @param string $uri
     * @param \CurlHandle $handle
     */
    public function __construct($uri, $handle) {
        $this->uri = $uri;

        $this->code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $this->bytes = curl_getinfo($handle, CURLINFO_SIZE_DOWNLOAD);

        $this->ip = curl_getinfo($handle, CURLINFO_PRIMARY_IP);
        $this->port = 0; //curl_getinfo($handle, CURLINFO_PRIMARY_PORT);

        // Note: CURLINFO_PRIMARY_PORT is generating notices in PHP 7.0.32 due to a bug
        // It's not needed for this app so we'll just disable it

        $this->totalTime = curl_getinfo($handle, CURLINFO_TOTAL_TIME);
        $this->connectTime = curl_getinfo($handle, CURLINFO_CONNECT_TIME);
        $this->nameLookupTime = curl_getinfo($handle, CURLINFO_NAMELOOKUP_TIME);

        $this->errorCode = curl_errno($handle);
        $this->errorMessage = curl_error($handle);
    }

    /**
     * Returns the URL of the request.
     *
     * @return string
     */
    public function getUrl() {
        return $this->uri;
    }

    /**
     * Returns the status code for the request.
     *
     * @return int
     */
    public function getStatusCode() {
        return $this->code;
    }

    /**
     * Returns the total number of bytes downloaded in this request.
     *
     * @return int
     */
    public function getDownloadSize() {
        return $this->bytes;
    }

    /**
     * Returns the IP address of the remote server which we connected to.
     *
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Returns the port of the remote server which we connected to.
     *
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Returns the total number of seconds for which the transaction was active.
     *
     * @return double
     */
    public function getTotalTime() {
        return $this->totalTime;
    }

    /**
     * Returns the number of seconds spent establishing a connection to the remote server.
     *
     * @return double
     */
    public function getConnectTime() {
        return $this->connectTime;
    }

    /**
     * Returns the number of seconds spent looking up domain names for the request.
     *
     * @return double
     */
    public function getNameLookupTime() {
        return $this->nameLookupTime;
    }

    /**
     * Returns the CURL error code for this request. If no error occurred, the return value will be `0`.
     *
     * @return int
     */
    public function getErrorCode() {
        return $this->errorCode;
    }

    /**
     * Returns the CURL error message for this request. If no error occurred, the return value will be an empty string.
     *
     * @return string
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    /**
     * Returns `true` if this request had a CURL error.
     *
     * @return bool
     */
    public function hadError() {
        return $this->errorCode > 0;
    }

}
