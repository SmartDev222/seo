<?php

require "../includes/init.php";
$page->setTitle("Email Verification")->setPage(3)->setPath("../")->header();

if (DEMO) {
	die("Demo mode.");
}

if (!$account->isLoggedIn() || $account->isVerified()) {
    header("Location: index.php");
    $studio->stop();
}

if (isset($_GET['token'])) {
	require dirname(__FILE__) . '/confirm-handle.php';
	die;
}

if (isset($_GET['resend'])) {
	try {
		$account->sendVerificationEmail($account->getId(), $account->getEmail());
		header("Location: confirm.php?resent=1");
		$studio->stop();
	}
	catch (Exception $e) {
		echo "<div class=\"error\">Error sending mail!</div>";
		$studio->stop();
	}
}

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Email Verification"); ?></h1>
    </div>
</section>

<section class="login-form">
    <div class="container">
		<h2><?php pt("We've emailed you a link to activate your account."); ?></h2>

		<p style="text-align: center; margin-bottom: 10px;">
			<?php pt('Check your inbox at {$1}', $account->getEmail()); ?>
		</p>

		<?php if (!isset($_GET['resent'])) { ?>
		<p style="text-align: center">
			<?php pt("Didn't receive the email?"); ?>
			<a href="?resend"><?php pt("Click here to resend it."); ?></a>
		</p>
		<?php } else { ?>
		<p style="text-align: center; color: green;">
			<?php pt("We've resent the email. Please allow some time for it to arrive."); ?>
		</p>
		<?php } ?>
    </div>
</section>

<?php
$page->footer();
?>
