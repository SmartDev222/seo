<?php

require "includes/init.php";

$page->requirePermission('admin-access');

if (!isset($_POST['id'])) {
    die;
}

$id = $_POST['id'];

if (DEMO) {
    die(json_encode([
        'test' => $id,
        'success' => true,
        'message' => 'Skipped due to demo mode.'
    ]));
}

if (stripos($id, ".") !== false) die;
if (stripos($id, "/") !== false) die;

$file = "diag/$id.php";
if (!file_exists($file)) {
    die(json_encode([
        'test' => $id,
        'success' => false,
        'message' => 'Failed to run diagnosis.'
    ]));
}

require $file;
$o = new $id($studio->sql);

?>
