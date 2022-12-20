<?php

require "../includes/init.php";
$page->requirePermission('admin-access');

$resolverName = 'quickstart-resolve-logo';
$redirectTo = '../header.php';

$studio->setopt($resolverName, 'On');
header("Location: $redirectTo");
die;

?>
