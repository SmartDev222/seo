<?php

class Bing extends Studio\Util\Diagnostic
{
    var $name = "Perform a test Bing search";

    function __construct($sql) {
        $bing = new SEO\Services\Bing(new SEO\Helper\Url("google.com"));
        try {
            $bing->query("hello world");
        }
        catch (Exception $e) {
            $this->fail($e->getMessage());
        }

        $this->pass();
    }
}
