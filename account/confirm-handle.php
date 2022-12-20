<?php

// Make sure this is an include from confirm.php

use Studio\Common\Activity;

if (!isset($page) || !isset($_GET['token'])) {
	die;
}

// Get the token from the URL
$token = $_GET['token'];
if (strlen($token) > 256) die;

// Try to decode the token
$decoded = @base64_decode($token);
if (!is_string($decoded) || empty($decoded)) $studio->redirect('login.php');

// Try to parse the token as JSON
$parsed = @json_decode($decoded, true);
if (json_last_error() !== JSON_ERROR_NONE) die;
if (!is_array($parsed)) die;
if (!isset($parsed['expires']) || !isset($parsed['id']) || !isset($parsed['token'])) die;
if (!is_int($parsed['expires']) || !is_int($parsed['id']) || !is_string($parsed['token'])) die;
if ($parsed['expires'] <= 0 || $parsed['expires'] > time() + 86400) die;

// Extract parameters
$expires = $parsed['expires'];
$accountId = $parsed['id'];
$token = $parsed['token'];

// Check the token
function checkToken($expires, $token) {
	global $studio, $account;

	$secret = $studio->config['session']['token'];
	$hash = hash('sha256', sprintf("%s(%d:%d+%s)", $secret, $expires, $account->getId(), $account->getEmail()));

	if ($hash !== $token) {
		return false;
	}

	if ($expires < time()) {
		return false;
	}

	return true;
}

$ok = checkToken($expires, $token);

if ($ok) {
	$studio->sql->query("UPDATE accounts SET verified = 1 WHERE id = {$account->getId()}");
	$studio->addActivity(new Activity(
		Activity::INFO,
		"User " . $account->getEmail() . " verified their email address!"
	));
	$studio->redirect("tools.php");
}

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Email Verification"); ?></h1>
    </div>
</section>

<section class="login-form">
    <div class="container">
		<div class="error-fixed">
			<?php pt("The link you followed has expired."); ?>
		</div>
    </div>
</section>

<?php
$page->footer();
?>
