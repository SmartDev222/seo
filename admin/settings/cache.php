<?php
require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Accounts")->header();


if (isset($_POST['cache']) && !DEMO) {
    $ierror = "";

    $bools = array(
        "cache"
    );
    $ints = array(
        "cache-duration"
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

    header("Location: cache.php?success=1&{$ierror}");
    die;
}

?>

<div class="heading">
    <h1>Cache</h1>
    <h2>Improve tool performance</h2>
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
                <?php if ($studio->getopt('cron-last-run') < (time() - 43200)) { ?>
                <div class="warning v2">
                    <div class="icon">
                        <i class="material-icons">&#xE002;</i>
                    </div>

                    <p>Looks like your cron job might not be running. The system can't clear expired cache from the database without a running cron job. Go to the Services page for more information.</p>
                </div>
                <?php } ?>

                <div class="setting-group">
                    <h3>Storage</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_01">Save tool results to the database <span class="help tooltip" title="If enabled, tool results will be cached in the database to help reduce server load and bandwidth."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_01">
                            <input type="hidden" name="cache" value="<?php echo $studio->getopt("cache"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting int">
                        <label for="Ctl_Number_02">Default number of days to store results <span class="help tooltip" title="Enter the number of days until cached entries expire, which will force tools to use new data. Some tools enforce a specific number of days and will ignore this option."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="int">
                            <input type="number" id="Ctl_Number_02" name="cache-duration" value="<?php echo $studio->getopt("cache-duration"); ?>">
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
