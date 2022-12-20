<?php

class Robots extends Studio\Util\Diagnostic
{
    var $name = "Test Robots.txt parser";

    function __construct($sql) {
        $txt = new SEO\Parsers\Robots("# This is a test robots.txt file

Sitemap: http://www.google.com/sitemap.xml
User-agent: *
Disallow: / # This is a test comment on a rule.");

        list($crawlable, $rule) = $txt->canCrawl("Googlebot", "/hello-world.html");
        if ($crawlable) $this->fail("Failed to parse rules.");

        $sitemaps = $txt->getSitemaps();
        if (count($sitemaps) !== 1) $this->fail("Failed to parse sitemap listing.");

        $this->pass();
    }
}
