<?php

require "../includes/init.php";
$page->setTitle("Login")->setPage(3)->setPath("../")->header();

if (isset($_SESSION[$account->sessname])) {
    header("Location: index.php");
    $studio->stop();
}

$form = new \Studio\Forms\LoginForm;
$passwordResetEmail = $form->email ? preg_replace('/=+$/', '', base64_encode($form->email)) : '';

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Login"); ?></h1>
    </div>
</section>

<section class="login-form">
    <div class="container">
        <form action="" method="post">
            <?php
            if (!isset($_POST['email']) && isset($_GET['reset'])) {
            ?>
            <div class="success"><?php pt("We've emailed you a link to reset your password."); ?></div>
            <?php
            }
            $form->showErrors();
            ?>
            <input autofocus type="text" name="email" placeholder="<?php pt("Email address"); ?>" value="<?php echo (DEMO && defined('DEMO_USER')) ? DEMO_USER : ''; if (isset($_POST['email']) && !DEMO) echo sanitize_attribute($_POST['email']); ?>" />
            <input type="password" name="password" placeholder="<?php pt("Password"); ?>" value="<?php echo (DEMO && defined('DEMO_PASS')) ? DEMO_PASS : ''; ?>" />
            <button type="submit" class="loadable"><?php pt("Login"); ?></button>

            <div class="info">
                <?php if ($studio->getopt('password-reset-enabled', 'On') === 'On') { ?>
                <a href="password-reset.php?dispatch=<?php echo $passwordResetEmail; ?>"><?php pt("forgot password"); ?></a>
                <span class="sep">|</span><?php } ?>

                <a href="register.php"><?php pt("create an account"); ?></a>
            </div>
        </form>
    </div>
</section>

<?php
$page->footer();
?>
