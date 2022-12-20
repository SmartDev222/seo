<?php

// Make sure this is an include from password-reset.php
if (!isset($page) || !isset($_GET['token']) || DEMO) {
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

// Get the form instance
$form = new \Studio\Forms\PasswordResetForm($token, $accountId, $expires);

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Reset Password"); ?></h1>
    </div>
</section>

<section class="login-form">
    <div class="container">
		<?php if ($form->error) { ?>
			<div class="error-fixed"><?php echo $form->error; ?></div>
		<?php } else { ?>
			<form action="" method="post">
				<h2><?php pt("Enter your new password"); ?></h2>

				<?php
				$form->showErrors();
				?>

				<input type="password" name="password" placeholder="<?php pt("Password"); ?>" />
				<input type="password" name="password_repeat" placeholder="<?php pt("Repeat password"); ?>" />

				<button type="submit" class="loadable"><?php pt("Reset"); ?></button>
			</form>
		<?php } ?>
    </div>
</section>

<?php
$page->footer();
?>
