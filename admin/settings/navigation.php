<?php

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Navigation")->header();
if (isset($_POST['postback']) && !DEMO) {
    $bools = array(
		"nav-show-home",
		"nav-show-tools",
		"nav-show-login",
		"nav-show-account"
    );

    $strs = array(
        'nav-logo-url'
    );

    foreach ($bools as $bool) {
        if (!isset($_POST[$bool])) $studio->showFatalError("Missing POST parameter $bool");

        $val = $_POST[$bool];
        if ($val != "On" && $val != "Off") $val = "Off";

        $studio->setopt($bool, $val);
    }

    foreach ($strs as $str) {
        if (!isset($_POST[$str])) $studio->showFatalError("Missing POST parameter $str");

        $val = $_POST[$str];
        $studio->setopt($str, $val);
    }

    header("Location: navigation.php?success=1");
    die;
}
?>

<div class="heading">
    <h1>Navigation</h1>
    <h2>Change navigation settings</h2>
</div>

<form action="" method="post">
	<input type="hidden" name="postback" value="1" />

    <div class="panel v2 back">
        <a href="../settings.php">
            <i class="material-icons">&#xE5C4;</i> Back
        </a>
	</div>

    <div class="save-container">
        <div class="saveable">
            <div class="panel v2">
                <div class="setting-group">
                    <h3>General</h3>

                    <div class="setting text">
                        <label for="$ctlInput1">
                            Logo link
                            <span class="help tooltip" title="This is the link that the header logo points to when clicked. The variable %homedir% will automatically be replaced with a relative path to the main directory (such as ./ or ../../ depending on the current location). This means you can use a link such as '%homedir%/tools.php' to reliably go to the tools page. If you want to use an absolute link, insert a full address starting with http:// or https://."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput1" type="text" name="nav-logo-url" value="<?php echo $studio->getopt('nav-logo-url', '%homedir%'); ?>" placeholder="Default: %homedir%">
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Navigation</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_01">Show the Home tab in navigation</label>

                        <div class="switch" id="Ctl_Switch_01">
                            <input type="hidden" name="nav-show-home" value="<?php echo $studio->getopt("nav-show-home", 'On'); ?>">
                            <div class="handle"></div>
                        </div>
					</div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_02">Show the Tools tab in navigation</label>

                        <div class="switch" id="Ctl_Switch_02">
                            <input type="hidden" name="nav-show-tools" value="<?php echo $studio->getopt("nav-show-tools", 'On'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_03">Show the Login tab in navigation</label>

                        <div class="switch" id="Ctl_Switch_03">
                            <input type="hidden" name="nav-show-login" value="<?php echo $studio->getopt("nav-show-login", 'On'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_04">Show the Account tab in navigation</label>

                        <div class="switch" id="Ctl_Switch_04">
                            <input type="hidden" name="nav-show-account" value="<?php echo $studio->getopt("nav-show-account", 'On'); ?>">
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

<script type="text/javascript">
    $("input[name=url]").val(window.location.href);
</script>

<?php
$page->footer();
?>
