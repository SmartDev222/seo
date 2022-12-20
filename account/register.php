<?php

require "../includes/init.php";
$page->setTitle("Register")->setPage(3)->setPath("../")->header();

if ($studio->getopt("allow-registration") == "Off") {
    $page->footer();
    $studio->stop();
}

if (isset($_SESSION[$account->sessname])) {
    header("Location: index.php");
    $studio->stop();
}

$form = new \Studio\Forms\RegisterForm;

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Register"); ?></h1>
    </div>
</section>

<section class="login-form">
    <div class="container">
        <form action="" method="post">
            <?php
            $form->showErrors();
            ?>
            <input autofocus type="text" name="email" placeholder="<?php pt("Email address"); ?>" />
            <input type="password" name="password" placeholder="<?php pt("Password"); ?>" />
            <input type="password" name="password2" placeholder="<?php pt("Repeat password"); ?>" />

            <?php
            $plugins->call("register_form");
            ?>

            <?php
            if ($studio->getopt('signup-legal-affirmation') === 'On') {
                $privacyPath = $studio->basedir . '/privacy.php';
                $termsPath = $studio->basedir . '/terms.php';

                $terms = null;
                $privacy = null;

                if (file_exists($termsPath)) {
                    $terms = '<a target="_blank" href="' . $page->getPath() . 'terms.php">' . rt("Terms of Service") . "</a>";
                }

                if (file_exists($privacyPath)) {
                    $privacy = '<a target="_blank" href="' . $page->getPath() . 'privacy.php">' . rt("Privacy Policy") . "</a>";
                }

                if ($terms || $privacy) echo "<div class=\"legal-container\">";

                if ($terms) {
            ?>
            <div class="legal">
                <label for="legal_terms">
                    <input type="checkbox" name="affirm_terms" id="legal_terms" value="Y">
                    <span><?php echo rt('I agree to the {$1}', $terms); ?></span>
                </label>
            </div>
            <?php
                }

                if ($privacy) {
            ?>
            <div class="legal">
                <label for="legal_privacy">
                    <input type="checkbox" name="affirm_privacy" id="legal_privacy" value="Y">
                    <span><?php echo rt('I agree to the {$1}', $privacy); ?></span>
                </label>
            </div>
            <?php
                }
                if ($terms || $privacy) echo "</div>";
            }
            ?>

            <button type="submit" class="loadable"><?php pt("Register"); ?></button>

            <div class="info">
                <?php pt("Already have an account?"); ?> <a href="login.php"><?php pt("Login"); ?></a>
            </div>
        </form>
    </div>
</section>

<?php
$page->footer();
?>
