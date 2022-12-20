<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access');

if (DEMO) die;
$hash = md5(rand() . rand() . rand() . $_SERVER['REMOTE_ADDR']) . rand() . substr(md5(rand()), 5, 11);
file_put_contents("../includes/execute/cron._{$hash}.key", strrev(md5($_SERVER["REMOTE_ADDR"])));

header("Location: ../includes/execute/cron.php?key=$hash");

$page->footer();
?>
