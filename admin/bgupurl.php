<?php

require "includes/init.php";
$page->requirePermission('admin-access');

if (isset($_POST['url']) && !DEMO) {
	$url = $_POST['url'];

	$studio->setopt("public-url", $url);

	// v1.84.15 -- Send the new URL to the API if required by an activated service
	if ($studio->getopt("google-enabled") == "On" || $studio->getopt("cron-token") != "") {
		$api->updateURL($url);
	}
}

?>
