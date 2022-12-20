<?php
$plansPricingPage = true;
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(0)->setTitle("Plans & Pricing")->header();

require "../includes/plugins/Subscriptions/ui/plans.php";

$page->footer();
