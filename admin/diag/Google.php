<?php

class Google extends Studio\Util\Diagnostic
{
    var $name = "Perform a test Google search";

    function __construct($sql) {
        $google = new SEO\Services\Google(new SEO\Helper\Url("bing.com"));
        try {
            $google->query("hello world");
        }
        catch (Exception $e) {
            $ignore = (stripos($e->getMessage(), 'blocked') !== false) ? ' (ignore if google proxying is enabled)' : '';

            $this->fail($e->getMessage() . $ignore);
        }

        $this->pass();
    }
}
