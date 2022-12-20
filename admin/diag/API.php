<?php

class API extends Studio\Util\Diagnostic
{
    var $name = "Test API connection";

    function __construct($sql) {
        global $api;

        try {
            $api->getLatestNews();
        }
        catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->pass();
    }
}
