<?php

require "../includes/init.php";
$page->requirePermission('admin-access');

$resolverName = 'quickstart-resolve-mail';
$redirectTo = '../settings/mail.php';

$studio->setopt($resolverName, 'On');
header("Location: $redirectTo");
die;

?>
