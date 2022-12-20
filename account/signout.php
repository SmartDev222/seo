<?php

require "../includes/init.php";
$page->setTitle("Account")->setPage(3)->setPath("../")->header()->requireLogin();

$account->logout();
$studio->redirect("");

?>
