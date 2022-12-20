<?php
$plansSettingsPage = true;
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(0)->setTitle("Subscription Settings")->header();

require "../includes/plugins/Subscriptions/ui/settings.php";

$page->footer();
