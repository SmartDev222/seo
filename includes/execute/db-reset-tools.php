<?php

// Resets the tools and categories back to factory default.
// Require this into the page with $studio visible in the current scope.

if (!isset($allowExecuting)) die;
if (!isset($studio)) throw new Exception("Missing studio object.");

$studio->setopt("tools", serialize(array(
    'Tools' => array(
        'alexa-rank',
        'bing-serp',
        'competition',
        'crawlability',
        'extract-meta-tags',
        'google-serp',
        'headers',
        'high-quality-backlinks',
        'indexed-pages',
        'keyword-density',
        'keyword-research',
        'link-analysis',
        'mobile-support',
        'new-backlinks',
        'poor-backlinks',
        'robots-txt',
        'sitemap',
        'speed-test',
        'submit-sitemap',
        'top-referrers',
        'top-search-queries'
    )
)));
$studio->setopt("categories", serialize(array(
    'Tools'
)));

?>
