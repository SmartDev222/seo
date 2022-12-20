<?php

require "../includes/init.php";
$page->requirePermission('admin-access');

$resolverName = 'quickstart-resolve-search';
$redirectTo = '../google.php';

$studio->setopt($resolverName, 'On');
header("Location: $redirectTo");
die;

?>
