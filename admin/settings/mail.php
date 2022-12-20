<?php

use Studio\Util\Mail;

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Mail")->header();

if (isset($_POST['smtp-server-port']) && !DEMO) {
    # Update config file if needed

    $ierror = "";

    $bools = array(
        'smtp-auth', 'sendgrid-api'
    );
    $ints = array(
        "smtp-server-port"
    );
    $strs = array(
        'email-from', 'email-from-name',
        'mail-server',
        'smtp-server', 'smtp-user', 'smtp-pass', 'smtp-secure',
        'mailgun-smtp-user', 'mailgun-smtp-pass',
        'sendgrid-key'
    );

    foreach ($bools as $bool) {
        if (!isset($_POST[$bool])) $studio->showFatalError("Missing POST parameter $bool");

        $val = $_POST[$bool];
        if ($val != "On" && $val != "Off") $val = "Off";

        $studio->setopt($bool, $val);
    }

    foreach ($ints as $int) {
        if (!isset($_POST[$int])) $studio->showFatalError("Missing POST parameter $int");

        $val = $_POST[$int];
        if (!is_numeric($val)) $val = 0;

        $studio->setopt($int, $val);
    }
    foreach ($strs as $str) {
        if (!isset($_POST[$str])) $studio->showFatalError("Missing POST parameter $str");

        $val = $_POST[$str];
        $studio->setopt($str, $val);
    }

    header("Location: mail.php?success=1&{$ierror}");
    die;
}

$posted = false;
$success = false;

if (isset($_POST['_doTestEmail'])) {
    $posted = true;

    try {
        $mailer = Mail::getClient();
        $mailer->addAddress($_POST['email']);
        $mailer->Subject = 'This is a test email';
        $mailer->Body = "Hello!\n\nThis is a test email sent per your request. Everything appears to be working!\n\nThanks!\nSEO Studio";
        $success = $mailer->send();

        if (!$success) {
            $success = $mailer->ErrorInfo;
        }
    }
    catch (Exception $e) {
        $success = $e->getMessage();
    }
}

?>

<div class="heading">
    <h1>Mail</h1>
    <h2>Configure outgoing mail</h2>
</div>

<div class="panel v2 back">
    <a href="../settings.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<?php if ($posted && $success === true) { ?>
    <div class="success">
        <i class="material-icons">check</i>
        <span>Test email sent successfully.</span>
    </div>
<?php } else if ($posted) { ?>
    <div class="error">
        <i class="material-icons">error_outline</i>
        <span>We couldn't send your test email: <?php echo $success; ?></span>
    </div>
<?php } ?>

<form action="" method="post">
    <div class="save-container">
        <div class="saveable">
            <div class="panel v2">
                <div class="setting-group">
                    <h3>Email</h3>

                    <div class="setting select">
                        <label for="$ctlInput1">
                            Method
                        </label>
                        <div class="dropdown">
                            <select id="$ctlInput1" name="mail-server">
                                <option value="php" <?php if ($studio->getopt("mail-server", 'php') == "php") echo "selected"; ?>>Built-in PHP mail()</option>
                                <option value="smtp" <?php if ($studio->getopt("mail-server") == "smtp") echo "selected"; ?>>SMTP</option>
                                <option value="mailgun" <?php if ($studio->getopt("mail-server") == "mailgun") echo "selected"; ?>>Mailgun</option>
                                <option value="sendgrid" <?php if ($studio->getopt("mail-server") == "sendgrid") echo "selected"; ?>>SendGrid</option>
                            </select>
                        </div>
                    </div>

                    <div class="setting text">
                        <label for="$ctlInput2">
                            From Address
                            <span class="help tooltip" title="This is the email address that your emails will appear to be sent from. If you're using a service like Mailgun or SendGrid, then this should match the domain or email on your account. I recommend using a 'noreply' address here."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput2" type="text" name="email-from" value="<?php echo $studio->getopt('email-from'); ?>" placeholder="webmaster@example.com">
                        </div>
                    </div>

                    <div class="setting text">
                        <label for="$ctlInput3">
                            From Name
                            <span class="help tooltip" title="This is the name that your emails will appear to be sent from. It can be anything you want, such as your brand, company, or website name."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput3" type="text" name="email-from-name" value="<?php echo $studio->getopt('email-from-name'); ?>" placeholder="SEO Studio">
                        </div>
                    </div>
                </div>

                <div class="setting-group collapsed mail-server smtp" style="display: none;">
                    <h3>SMTP Settings</h3>

                    <div class="setting text">
                        <label for="$ctlInput4">
                            SMTP Host
                            <span class="help tooltip" title="Enter the address of the SMTP server. This can be a hostname or an IP address."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput4" type="text" name="smtp-server" value="<?php echo $studio->getopt('smtp-server'); ?>" placeholder="localhost">
                        </div>
                    </div>

                    <div class="setting int">
                        <label for="$ctlInput5">
                            SMTP Port
                        </label>
                        <div class="int">
                            <input id="$ctlInput5" type="number" name="smtp-server-port" value="<?php echo $studio->getopt('smtp-server-port'); ?>" placeholder="587">
                        </div>
                    </div>

                    <div class="setting select">
                        <label for="$ctlInput9">
                            SMTP Security
                            <span class="help tooltip" title="Please choose the form of encryption that your SMTP server uses. It is highly recommended to use encryption if available."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="dropdown">
                            <select id="$ctlInput9" name="smtp-secure">
                                <option value="" <?php if (empty($studio->getopt("smtp-secure"))) echo "selected"; ?>>None</option>
                                <option value="tls" <?php if ($studio->getopt("smtp-secure") == "tls") echo "selected"; ?>>TLS</option>
                                <option value="ssl" <?php if ($studio->getopt("smtp-secure") == "ssl") echo "selected"; ?>>SSL</option>
                            </select>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="ctlSwitch6">
                            SMTP Authentication
                            <span class="help tooltip" title="Enable this option if your SMTP server requires username and password authentication."><i class="material-icons">&#xE8FD;</i>
                        </label>

                        <div class="switch" id="ctlSwitch6">
                            <input type="hidden" name="smtp-auth" value="<?php echo $studio->getopt("smtp-auth", 'On'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting text requires-authentication" style="display: none;">
                        <label for="$ctlInput7">
                            SMTP Username
                        </label>
                        <div class="text">
                            <input id="$ctlInput7" type="text" name="smtp-user" value="<?php echo $studio->getopt('smtp-user'); ?>" placeholder="Enter username...">
                        </div>
                    </div>

                    <div class="setting text requires-authentication" style="display: none;">
                        <label for="$ctlInput8">
                            SMTP Password
                        </label>
                        <div class="text">
                            <input id="$ctlInput8" type="password" name="smtp-pass" value="<?php echo $studio->getopt('smtp-pass'); ?>" placeholder="Enter password...">
                        </div>
                    </div>
                </div>

                <div class="setting-group collapsed mail-server mailgun" style="display: none;">
                    <h3>Mailgun Settings</h3>

                    <div class="setting text">
                        <label for="$ctlInput10">
                            SMTP Username
                            <span class="help tooltip" title="Enter the SMTP username for your Mailgun domain. You can find this from the Domain Settings section on their control panel."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput10" type="text" name="mailgun-smtp-user" value="<?php echo $studio->getopt('mailgun-smtp-user'); ?>" placeholder="Enter username...">
                        </div>
                    </div>

                    <div class="setting text">
                        <label for="$ctlInput11">
                            SMTP Password
                            <span class="help tooltip" title="Enter the SMTP password for your Mailgun domain. You can find this from the Domain Settings section on their control panel."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput11" type="password" name="mailgun-smtp-pass" value="<?php echo $studio->getopt('mailgun-smtp-pass'); ?>" placeholder="Enter password...">
                        </div>
                    </div>
                </div>

                <div class="setting-group collapsed mail-server sendgrid" style="display: none;">
                    <h3>SendGrid Settings</h3>

                    <div class="setting text">
                        <label for="$ctlInput12">
                            API Key
                        </label>
                        <div class="text">
                            <input id="$ctlInput12" type="text" name="sendgrid-key" value="<?php echo $studio->getopt('sendgrid-key'); ?>" placeholder="Enter key...">
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="ctlSwitchSG1">
                            Force connections over the API
                            <span class="help tooltip" title="This will force the script to use the SendGrid REST API for outgoing emails instead of SMTP. This is not recommended unless you're unable to send mail due to your web host blocking the necessary ports."><i class="material-icons">&#xE8FD;</i>
                        </label>

                        <div class="switch" id="ctlSwitchSG1">
                            <input type="hidden" name="sendgrid-api" value="<?php echo $studio->getopt("sendgrid-api", 'Off'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="save">
            <div class="save-box">
                <button type="submit">
                    <span>Save changes</span>
                    <span>Saved</span>
                    <img src="../../resources/images/load32.gif" width="16px" height="16px">
                </button>
            </div>
        </div>
    </div>
</form>
<div class="panel info border-top">
    <h3>Send a test email</h3>
    <p style="margin-bottom: 25px;">Enter an email address below and we'll send a test email with your current settings.</p>

    <form action="" method="post" id="sendTestEmailForm">
        <input type="hidden" name="_doTestEmail" value="1">
        <input type="text" class="textbox-v2" name="email" placeholder="Email..." style="width: 300px;" value="<?php
            if (isset($_POST['email'])) {
                $email = $_POST['email'];
                echo str_replace('"', '&quot;', $email);
            }
        ?>">
        <input type="submit" class="btn blue" value="Send">
    </form>
</div>

<?php
    $contactId = null;
    $q = $studio->sql->query('SELECT * FROM `plugins` WHERE `name` LIKE "%contact%" AND `enabled` = 1;');

    if ($q->num_rows > 0) {
        $row = $q->fetch_object();
        $contactId = $row->id;
    }

    if (!is_null($contactId)) {
?>
<div class="panel info">
    <h3>Contact page extension</h3>
    <p style="margin-bottom: 25px; max-width: 750px;">
        It looks like you're using the contact page extension. Make sure to check the settings for that extension, because
        it can override some of the above options.
    </p>

    <a class="btn" href="../plugin-options.php?id=<?php echo $contactId; ?>">Contact settings</a>
</div>
<?php
    }
?>

<script type="text/javascript">
    $(".collapsed.mail-server." + $("select[name=mail-server]").val()).show();
    $("select[name=mail-server]").on('change', function() {
        $(".collapsed.mail-server").hide();
        $(".collapsed.mail-server." + $("select[name=mail-server]").val()).show();
        $(".collapsed.mail-server.all").show();
    });

    var auth = $("input[name=smtp-auth]").val() === 'On';
    var authInputs = $('.requires-authentication');
    if (auth) authInputs.show();

    $("input[name=smtp-auth]").on('change', function() {
        auth = $("input[name=smtp-auth]").val() === 'On';
        if (auth) authInputs.show();
        else authInputs.hide();
    });
</script>

<?php
$page->footer();
?>
