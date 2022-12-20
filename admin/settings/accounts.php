<?php
require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Accounts")->header();

if (isset($_POST['allow-registration']) && !DEMO) {
    $ierror = "";

    $bools = array(
        "allow-registration", "show-login",
        "login-tools", "show-tools", 'project-mode',
        'password-reset-enabled',
        'email-verification',
    );

    foreach ($bools as $bool) {
        if (!isset($_POST[$bool])) $studio->showFatalError("Missing POST parameter $bool");

        $val = $_POST[$bool];
        if ($val != "On" && $val != "Off") $val = "Off";

        $studio->setopt($bool, $val);
    }

    header("Location: accounts.php?success=1&{$ierror}");
    die;
}

?>

<div class="heading">
    <h1>Accounts</h1>
    <h2>Change user functionality</h2>
</div>

<form action="" method="post">
    <div class="panel v2 back">
        <a href="../settings.php">
            <i class="material-icons">&#xE5C4;</i> Back
        </a>
    </div>
    <div class="save-container">
        <div class="saveable">
            <div class="panel v2">
                <div class="setting-group">
                    <h3>Visitors</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_01">Allow visitors to sign up <span class="help tooltip" title="If enabled, visitors on your website can create their own accounts."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_01">
                            <input type="hidden" name="allow-registration" value="<?php echo $studio->getopt("allow-registration"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_02">Show the login button <span class="help tooltip" title="If disabled, any and all 'login' or 'sign in' buttons will be hidden from the public portal."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_02">
                            <input type="hidden" name="show-login" value="<?php echo $studio->getopt("show-login"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Access</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_03">Only logged in users can use tools <span class="help tooltip" title="If enabled, visitors must sign in before they can use any tools."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_03">
                            <input type="hidden" name="login-tools" value="<?php echo $studio->getopt("login-tools"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_04">Hide the tools page for guests <span class="help tooltip" title="If enabled, the tools page will not be visible in the site navigation unless the user is logged in."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_04">
                            <input type="hidden" name="show-tools" value="<?php echo $studio->getopt("show-tools"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Projects</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_05">Require users to save sites to their account <span class="help tooltip" title="If enabled, users who are signed in must save a site to their account before they can use tools on that site. This is ideal, for example, when using the script as a SaaS."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_05">
                            <input type="hidden" name="project-mode" value="<?php echo $studio->getopt("project-mode"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Emails</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_18">Require email verification <span class="help tooltip" title="If enabled, new users must verify their email address before they can sign in for the first time. You will be able to manually override this per-user from the admin panel. Make sure your mail settings are working correctly, otherwise the email will fail to send and the account won't be created."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_18">
                            <input type="hidden" name="email-verification" value="<?php echo $studio->getopt("email-verification", 'Off'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_15">Enable forgot password <span class="help tooltip" title="If enabled, visitors can click the 'forgot password' link on the login page to request a reset email be sent to them. Make sure your mail settings are working correctly, otherwise users will receive an email and won't be able to reset their password with this method."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_15">
                            <input type="hidden" name="password-reset-enabled" value="<?php echo $studio->getopt("password-reset-enabled", 'On'); ?>">
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

<?php
$page->footer();
?>
