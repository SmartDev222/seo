<?php

use Studio\Util\Mail;

require "includes/init.php";
$page->setTitle("Contact us")->setPage(4)->header();

$error = "";
$name = "";
$email = "";
$message = "";

if ($studio->getopt("contact-email-sendto") === "youremail@example.com") {
    die("<div style='padding: 30px; text-align: center; color: #666;'>This extension has not been configured. Please configure it from settings before trying to load this page.</div>");
}

function doSendEmail($name, $email, $subject, $message) {
    global $studio;

    if (DEMO) return true;

    $to = $studio->getopt("contact-email-sendto");
    $fromName = Mail::getFromName();

    // Switched to universal mailer in v1.84
    $mailer = Mail::getClient();
    $mailer->FromName = $name;
    $mailer->addReplyTo($email, $name);
    $mailer->addCustomHeader('X-Studio-UserEmail', $email);
    $mailer->addCustomHeader('X-Studio-UserAddress', $_SERVER['REMOTE_ADDR']);
    $mailer->addAddress($to, $fromName);
    $mailer->Subject = $subject;
    $mailer->Body = $message;

    return $mailer->send();
}

if (isset($_POST['doPostbackForm'])) {
    if (!isset($_POST['name'])) die;
    if (!isset($_POST['email'])) die;
    if (!isset($_POST['message'])) die;

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    $ip = $_SERVER['REMOTE_ADDR'];

    $sent = $studio->getopt("contact-send-history");
    $sent = @unserialize(base64_decode($sent));
    $found = 0;

    if (!is_array($sent)) {
        $sent = array();
    }

    foreach ($sent as $item) {
        if ($item[0] == $ip && $item[1] > (time() - 300)) {
            $found++;
        }
    }

    $blockspam = ($studio->getopt("contact-block-spam") == "On");
    $blockspeed = ($studio->getopt("contact-block-speed") == "On");

    if ($found >= 2 && $blockspeed) {
        $error = rt("You're submitting the form too fast. Try again in a few minutes.");
    }
    else {
        if ($studio->getopt('contact-legal-affirmation') === 'On') {
            $privacyPath = $studio->basedir . '/privacy.php';

            if (file_exists($privacyPath)) {
                if (!isset($_POST['affirm_privacy']) || $_POST['affirm_privacy'] !== 'Y') {
                    $error = rt("You must agree to our privacy policy.");
                }
            }
        }

        if ($error === '') {
            if ($name == "" || $email == "" || $message == "") {
                $error = rt("Please enter all fields.");
            }
            else {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    if ($blockspam && (strlen($message) < 10 || strlen($message) > 600)) {
                        $error = rt("Your message looks like spam. Try again.");
                    }
                    else {
                        $newsent = [];
                        foreach ($sent as $item) {
                            if ($item[1] > (time() - 300)) {
                                $newsent[] = $item;
                            }
                        }
                        $newsent[] = [
                            $ip, time()
                        ];

                        $studio->setopt("contact-send-history", base64_encode(serialize($newsent)));
                        $success = false;

                        try {
                            $subject = $studio->getopt('contact-subject', 'Message from $name (sent from contact page)');
                            if (!is_string($subject) || empty($subject)) $subject = 'Message from $name (sent from contact page)';
                            $subject = str_replace('$name', $name, $subject);

                            $success = doSendEmail($name, $email, $subject, $message);
                        }
                        catch (Exception $e) {
                            $success = false;
                        }

                        if (!$success) {
                            $error = rt("We couldn't send your message right now. Try again shortly.");
                        }
                        else {
                            header("Location: contact.php?sent");
                            die;
                        }
                    }
                }
                else {
                    $error = rt("Please enter a valid email.");
                }
            }
        }
    }
}

if (!isset($_GET['sent'])) {
?>

<section class="title">
    <div class="container">
        <h1><?php pt("Contact us"); ?></h1>
    </div>
</section>

<section class="contact">
    <div class="container">
        <h1><?php pt("Email us"); ?></h1>

        <?php if ($error != "") { ?><div class="error" style="margin: 0 0 20px;"><?php echo $error; ?></div><?php } ?>

        <form action="" method="post">
            <div class="row">
                <div class="col-md-6">
                    <label for="cb001"><?php pt("Your Name"); ?></label>
                    <input type="text" id="cb001" name="name" value="<?php echo $name; ?>">
                </div>
                <div class="col-md-6">
                    <label for="cb002"><?php pt("Your Email"); ?></label>
                    <input type="text" id="cb002" name="email" value="<?php echo $email; ?>">
                </div>
            </div>

            <label for="cb003"><?php pt("Message"); ?></label>
            <textarea id="cb003" name="message"><?php echo $message; ?></textarea>

            <?php
            if ($studio->getopt('contact-legal-affirmation') === 'On') {
                $privacyPath = $studio->basedir . '/privacy.php';

                $privacy = null;

                if (file_exists($privacyPath)) {
                    $privacy = '<a target="_blank" href="' . $page->getPath() . 'privacy.php">' . rt("Privacy Policy") . "</a>";
                }

                if ($privacy) {
            ?>
            <div class="legal-container">
                <div class="legal">
                    <label for="legal_privacy">
                        <input type="checkbox" name="affirm_privacy" id="legal_privacy" value="Y" <?php
                            if (isset($_POST['affirm_privacy']) && $_POST['affirm_privacy'] === 'Y') echo "checked";
                        ?>>
                        <span><?php echo rt('I agree to the {$1}', $privacy); ?></span>
                    </label>
                </div>
            </div>
            <?php
                    }
                }
            ?>

            <input type="submit" name="doPostbackForm" value="<?php pt("Send"); ?>">
        </form>
    </div>
</section>

<?php
}
else {
?>

<section class="title">
    <div class="container">
        <h1><?php pt("Contact us"); ?></h1>
    </div>
</section>

<section class="contact">
    <div class="container">
        <h1 style="border: 0;"><?php pt("Message sent!"); ?></h1>
    </div>
</section>

<?php
}
$page->footer();
?>
