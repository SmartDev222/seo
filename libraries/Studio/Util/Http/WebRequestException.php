<?php

namespace Studio\Util\Http;

use Exception;

class WebRequestException extends Exception {

    /**
     * @var WebRequest
     */
    protected $request;

    /**
     * Constructs a new `WebRequestException` instance for the given request.
     *
     * @param WebRequest $request
     * @param string $message
     * @param int $code
     */
    public function __construct(WebRequest $request, $message, $code) {
        parent::__construct($message, $code);
        $this->request = $request;
    }

    /**
     * Returns the request which triggered this exception.
     *
     * @return WebRequest
     */
    public function getRequest() {
        return $this->request;
    }

}
