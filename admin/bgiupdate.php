<?php

$load_plugins = false;

require "includes/init.php";
require "../includes/update.php";
require "../includes/itemupdate.php";

// Validate the parameters and environment

$page->requirePermission('admin-access');
$id = $_POST['id'];
if (!is_numeric($id)) die;

// Run the update

$dir = null;
$theme = false;
$plugin = false;
$item = null;

$q1 = $studio->sql->query("SELECT * FROM plugins WHERE market_id = $id");
if ($q1 && $q1->num_rows > 0) {
    $plugin = true;
    $item = $q1->fetch_object();
    $dir = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . $item->directory;
}
$q2 = $studio->sql->query("SELECT * FROM themes WHERE market_id = $id");
if ($q2 && $q2->num_rows > 0) {
    $theme = true;
    $item = $q1->fetch_object();
}

$update = new ItemUpdate($id);
$success = $update->run($studio->getopt("automatic-updates-backup") == "On");
if ($dir != null) {
    $update->afterwards($dir);
}

if ($plugin) $studio->sql->query("UPDATE plugins SET version='{$item->update_available}', update_available='' WHERE market_id = $id");
if ($theme) $studio->sql->query("UPDATE themes SET version='{$item->update_available}', update_available='' WHERE market_id = $id");

// Done!

die($success ? "successful" : "failed");
?>
