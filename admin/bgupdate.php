<?php

require "includes/init.php";
require "../includes/update.php";

// Validate the parameters and environment

if (!isset($_POST['token'])) die;
$page->requirePermission('admin-access');
$token = $_POST['token'];

// Fetch details about the update
$updateVersion = null;

$p = $studio->sql->prepare('SELECT updateVersion FROM updates WHERE token = ? AND updateStatus <> 1 LIMIT 1');
$p->bind_param('s', $token);
$p->execute();
$p->store_result();

if ($p->num_rows < 1) die("failed");

$p->bind_result($updateVersion);
$p->fetch();
$p->close();

// Run the update

$update = new Update($_POST['token']);
$success = $update->run($studio->getopt("automatic-updates-backup") == "On");

// Done!

if ($success) {
	$studio->addActivity(new Studio\Common\Activity(
		Studio\Common\Activity::SUCCESS,
		"Installed new update v" . $updateVersion . "."
	));

	die("successful");
}
else {
	$studio->addActivity(new Studio\Common\Activity(
		Studio\Common\Activity::ERROR,
		"Failed to install new update v" . $updateVersion . " due to an error."
	));

	die("failed");
}

?>
