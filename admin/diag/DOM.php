<?php

class DOM extends Studio\Util\Diagnostic
{
    var $name = "Test DOM parser";

    function __construct($sql) {
        new SEO\Helper\DOM;

        $html = "<div><strong>Hello world</strong></div>";
        $dom = SEO\Helper\str_get_html($html);

        if (!$dom) $this->fail("Failed to parse basic HTML (failed stage 1)");
        if (!$dom->find("div", 0)) $this->fail("Failed to parse basic HTML (failed stage 2)");
        if (!$dom->find("div strong", 0)) $this->fail("Failed to parse basic HTML (failed stage 3)");

        if ($dom->find("div strong", 0)->plaintext != "Hello world") $this->fail("Failed to parse basic HTML (failed stage 4)");

        $this->pass();
    }
}
