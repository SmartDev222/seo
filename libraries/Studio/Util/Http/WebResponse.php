<?php

namespace Studio\Util\Http;

use Exception;
use Studio\Util\Parsers\HTMLDocument;

class WebResponse {

    /**
     * The instance this response was constructed for.
     *
     * @var WebRequest
     */
    protected $request;

    /**
     * The headers in the response.
     *
     * @var string[]
     */
    protected $headers;

    /**
     * The response body.
     *
     * @var string
     */
    protected $body;

    /**
     * Final response information.
     *
     * @var array
     */
    protected $info;

    /**
     * Total number of seconds spent fulfilling the request.
     *
     * @var double
     */
    protected $totalTime;

    /**
     * Traces containing details on each individual request made from start to finish.
     *
     * @var Trace[]
     */
    protected $traces;

    /**
     * An array of all cookies in the response.
     *
     * @var WebResponseCookie[]
     */
    protected $cookies;

    /**
     * The HTTP version used for the request.
     *
     * @var string
     */
    protected $httpVersion;

    /**
     * The message sent alongside the HTTP version.
     *
     * @var string
     */
    protected $message;

    /**
     * Constructs a new `WebResponse` instance for the given CURL handle. Note that the handle is used immediately at
     * the time of construction to gather information for the response and can safely be closed after constructing
     * this instance.
     *
     * @param WebRequest $request
     * @param resource $handle
     * @param string[] $headers
     * @param string $body
     * @param Trace[] $traces
     * @param string $httpVersion
     */
    public function __construct(WebRequest $request, $handle, $headers, $body, $totalTime, $traces, $httpVersion, $httpMessage) {
        $this->request = $request;
        $this->headers = $headers;
        $this->body = $body;
        $this->totalTime = $totalTime;
        $this->traces = $traces;
        $this->httpVersion = $httpVersion;
        $this->message = $httpMessage;

        $this->info = [
            CURLINFO_EFFECTIVE_URL => @curl_getinfo($handle, CURLINFO_EFFECTIVE_URL),
            CURLINFO_HTTP_CODE => @curl_getinfo($handle, CURLINFO_HTTP_CODE),
            CURLINFO_FILETIME => @curl_getinfo($handle, CURLINFO_FILETIME),
            CURLINFO_TOTAL_TIME => @curl_getinfo($handle, CURLINFO_TOTAL_TIME),
            CURLINFO_NAMELOOKUP_TIME => @curl_getinfo($handle, CURLINFO_NAMELOOKUP_TIME),
            CURLINFO_CONNECT_TIME => @curl_getinfo($handle, CURLINFO_CONNECT_TIME),
            CURLINFO_SIZE_UPLOAD => @curl_getinfo($handle, CURLINFO_SIZE_UPLOAD),
            CURLINFO_SIZE_DOWNLOAD => @curl_getinfo($handle, CURLINFO_SIZE_DOWNLOAD),
            CURLINFO_SPEED_DOWNLOAD => @curl_getinfo($handle, CURLINFO_SPEED_DOWNLOAD),
            CURLINFO_SPEED_UPLOAD => @curl_getinfo($handle, CURLINFO_SPEED_UPLOAD),
            CURLINFO_REQUEST_SIZE => @curl_getinfo($handle, CURLINFO_REQUEST_SIZE),
            CURLINFO_CONTENT_TYPE => @curl_getinfo($handle, CURLINFO_CONTENT_TYPE),
            CURLINFO_SSL_VERIFYRESULT => @curl_getinfo($handle, CURLINFO_SSL_VERIFYRESULT)
        ];
    }

    /**
     * Returns an array of all headers in the response.
     *
     * @return array
     */
    public function getHeaders() {
        $headers = [];

        foreach ($this->headers as $name => $values) {
            $name = str_replace('-', ' ', $name);
            $name = ucwords($name);
            $name = str_replace(' ', '-', $name);

            $headers[$name] = $values;
        }

        return $headers;
    }

    /**
     * Returns the value of the specified case-insensitive header, or returns `null`. Note that if the header was sent
     * multiple times, the returned value will be an array of all values.
     *
     * @param string $name
     * @return string|string[]|null
     */
    public function getHeader($name) {
        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
    }

    /**
     * Returns an array of cookies set by the server.
     *
     * @return WebResponseCookie[]
     */
    public function getCookies() {
        if (is_null($this->cookies)) {
            $cookies = [];
            $definitions = $this->getHeader('Set-Cookie');

            if (is_null($definitions)) return $this->cookies = [];
            if (is_string($definitions)) $definitions = [$definitions];

            foreach ($definitions as $definition) {
                try {
                    $cookies[] = new WebResponseCookie($definition);
                }
                catch (Exception $e) {}
            }

            $this->cookies = $cookies;
        }

        return $this->cookies;
    }

    /**
     * Returns an associative array of cookies and values set by the server. Expired cookies are not included.
     *
     * @return string[]
     */
    public function getCookiesAssoc() {
        $cookies = [];

        foreach ($this->getCookies() as $cookie) {
            if ($cookie->getExpirationTime() > time()) {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $cookies;
    }

    /**
     * Returns the body of the response as a string.
     *
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Returns an `HTMLDocument` instance for the HTML in this response.
     *
     * @return HTMLDocument
     */
    public function getDom() {
        return new HTMLDocument($this->getBody());
    }

    /**
     * Returns the parsed data from the response in JSON format. If an error occurs during parsing, an `Exception` is
     * thrown. Return value is associative unless `false` is passed as the sole argument.
     *
     * @return mixed
     * @throws Exception
     */
    public function getJson($associative = true) {
        $parsed = @json_decode($this->getBody(), $associative);

        if (is_null($parsed)) {
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse body as a JSON-encoded string');
            }
        }

        return $parsed;
    }

    /**
     * Returns the trace of the response. This is an array of traces, where each trace is an array containing both a
     * `from` and `to` url, and a `time` measurement in milliseconds.
     *
     * @return Trace[]
     */
    public function getTraces() {
        return $this->traces;
    }

    /**
     * Returns the status code of the response.
     *
     * @return int
     */
    public function getStatusCode() {
        return $this->info[CURLINFO_HTTP_CODE];
    }

    /**
     * Returns the HTTP version.
     *
     * @return string
     */
    public function getHttpVersion() {
        return $this->httpVersion;
    }

    /**
     * Returns the HTTP message, such as `200 OK` or `404 Not Found`.
     *
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Returns the final destination URL of the response.
     *
     * @return string
     */
    public function getUrl() {
        $url = $this->info[CURLINFO_EFFECTIVE_URL];

        if (!$url && !empty($this->traces)) {
            $lastTrace = $this->traces[count($this->traces) - 1];

            if ($lastTrace) {
                $url = $lastTrace->getUrl();
            }
        }

        return $url;
    }

    /**
     * Returns the value of the `CURLINFO_` key for the final connection, or `null` if no value is available. Note that
     * the return values from this method do not consider redirects.
     *
     * @param int $info
     * @return mixed
     */
    public function getInfo($info) {
        if (isset($this->info[$info])) {
            return $this->info[$info];
        }
    }

    /**
     * Returns the total number of seconds the request took to complete, including redirects.
     *
     * @return double
     */
    public function getTotalTime() {
        return $this->totalTime;
    }

}
