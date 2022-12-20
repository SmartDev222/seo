<?php

class API2 extends Studio\Util\Diagnostic
{
    var $name = "Test API key";

    function __construct($sql) {
        global $api, $studio;

        if ($studio->getopt("api.secretkey") == "") {
            $this->pass("No key (this copy is not licensed)");
        }

        try {
            $api->getApplicationInfo();
        }
        catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->pass();
    }
}
