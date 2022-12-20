<?php

require "includes/init.php";
$page->setTitle("Pricing")->setPage("pricing")->header();

require "includes/plugins/Subscriptions/public/pricing.php";

$page->footer();
?>
