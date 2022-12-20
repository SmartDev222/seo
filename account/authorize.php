<?php

/**
 * This file is used for the implementation of single sign-on or custom authentication procedures. Sending a user to
 * this page will automatically sign them in given the correct seed and token query parameters.
 *
 * The values for these parameters are as follows:
 *
 *     • The `seed` is the time that this token was generated (with time()).
 * 	   • The `token` is the user's ID followed by an md5 hash comprised of several data related to them.
 *
 * To calculate the `token`, you will first create an md5 hash of the following columns from the database for the user
 * of interest:
 *
 * 	   • Their email address.
 *     • Their hashed password (starting with $2y$11$)
 * 	   • The seed timestamp.
 *
 * Hash these together like md5(email + password + seed), with no delimiters. Then, prefix the hash with the user's ID.
 *
 * EXAMPLE: For a user with an ID of 230, an email of john.doe@example.com, a hashed password of
 * $2y$11$NjM3ODAfMjc4ODM1NjA4McFm5HT81hHeITL/d3Q1Bs/Zj2IAtT1M6, and a seed of 1583235489,
 * we will generate the hash as follows:
 *
 * 		Input: md5("john.doe@example.com$2y$11$NjM3ODAfMjc4ODM1NjA4McFm5HT81hHeITL/d3Q1Bs/Zj2IAtT1M61583235489")
 *  	Output: 02b73080c852aaeb96f1c3e1c461ba46
 *
 * Then prefix their ID (230) onto the hash to generate the `token`: 23002b73080c852aaeb96f1c3e1c461ba46
 * Then, send them to this page with these attributes:
 *
 * 		authorize.php?seed=1583235489&token=02b73080c852aaeb96f1c3e1c461ba46
 *
 * They will be automatically signed into their account and redirected to the tools page. You can override the redirect
 * URI with the `redirect` parameter (make sure to urlencode it). The URL is only valid for 2 minutes.
 *
 */

require "../includes/init.php";

if (!isset($_GET['seed'])) die('Missing <code>seed</code> parameter.');
if (!isset($_GET['token'])) die('Missing <code>token</code> parameter.');

$token = $_GET['token'];
$seed = $_GET['seed'];
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '../tools.php';

if (!preg_match('/^\d+$/', $seed)) die('The <code>seed</code> parameter is invalid.');
if (!preg_match('/^(\d+)([a-f0-9]{32})$/', $token, $matches)) die('The <code>token</code> parameter is invalid.');

$userId = $matches[1];
$userHash = $matches[2];

// Find the user in the database

if ($p = $studio->sql->prepare("SELECT email, password FROM accounts WHERE id = ?")) {
	$p->bind_param("i", $userId);
	$p->execute();
	$p->store_result();

	if ($p->num_rows === 1) {
		$p->bind_result($email, $password);
		$p->fetch();

		if (($expectedHash = md5($email . $password . $seed)) !== $userHash) {
			die('The <code>token</code> parameter is invalid.');
		}

		if ($seed < (time() - 120) || $seed > time()) {
			die('The <code>token</code> has expired.');
		}

        $studio->addActivity(new \Studio\Common\Activity(
            \Studio\Common\Activity::INFO,
            "Successful login via token for email " . $email . " from " . $_SERVER['REMOTE_ADDR']
		));

		$account->login($email, $password);
		$studio->redirect($redirect, false);
	}
	else {
		die('The <code>token</code> parameter does not match an account.');
	}
}
else {
	die('Internal error.');
}
