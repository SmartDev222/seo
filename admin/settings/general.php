<?php
use Studio\Tools\SpeedTest;

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Configuration")->header();

if (isset($_POST['show-errors']) && !DEMO) {
    # Update config file if needed

    $ierror = "";

    if ( ($_POST['show-errors'] == "On" && $studio->config['errors']['show'] == false) || ($_POST['show-errors'] == "Off" && $studio->config['errors']['show'] == true) ) {
        $conf = file_get_contents("../../config.php");
        if ($conf === false) $studio->showFatalError("Failed to read /config.php file. Please ensure it has the proper permissions.");

        $current = (($studio->config['errors']['show'] == true) ? "true" : "false");
        $new = (($_POST['show-errors'] == "On") ? "true" : "false");
        $newconf = str_replace('"show" => ' . $current, '"show" => ' . $new, $conf);

        $w = file_put_contents("../../resources/bin/config.backup-".time().".php", $conf);
        if ($w === false) $studio->showFatalError("Failed to make a configuration backup to /resources/bin/, please ensure the directory is writeable.");

        $w = file_put_contents("../../config.php", $newconf);
        if ($w === false) $studio->showFatalError("Failed to overwrite /config.php file, please ensure it is writeable. If you are going to change the chmod, only add writing permissions, do not modify the read permissions.");
    }

    $studio->setopt('speedtest-default-region', $_POST['speedtest-default-region']);
    $studio->setopt('tools-default-country', $_POST['tools-default-country']);
    $studio->setopt('timezone', $_POST['timezone']);

    $bools = array(
        "automatic-updates", "automatic-updates-backup", "send-errors", "send-usage-info", "errors-anonymous",
        'show-tools-without-site', 'allow-siteless-tools',
        'experimental-tool-design', 'no-cookie-siteselector',
        'allow-local-hostnames'
    );

    foreach ($bools as $bool) {
        if (!isset($_POST[$bool])) $studio->showFatalError("Missing POST parameter $bool");

        $val = $_POST[$bool];
        if ($val != "On" && $val != "Off") $val = "Off";

        $studio->setopt($bool, $val);
    }

    if ($_POST['show-tools-without-site'] == 'On') {
        $studio->setopt('experimental-tool-design', 'On');
    }

    header("Location: general.php?success=1&{$ierror}");
    die;
}

?>

<div class="heading">
    <h1>Configuration</h1>
    <h2>Change general settings</h2>
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
                    <h3>Studio</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_01">Automatically update Studio <span class="help tooltip" title="The application will automatically be updated to the latest version. This might overwrite any changes you make to the script's code."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_01">
                            <input type="hidden" name="automatic-updates" value="<?php echo $studio->getopt("automatic-updates"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_02">Save files before updating <span class="help tooltip" title="If enabled, a backup .zip file containing the previous version of all files modified by updates will be saved to the disk, in case something goes wrong or gets overwritten accidentally."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_02">
                            <input type="hidden" name="automatic-updates-backup" value="<?php echo $studio->getopt("automatic-updates-backup"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting select">
                        <label for="Ctl_Dropdown_01">Timezone <span class="help tooltip" title="Set this to your local or nearest timezone for accurate time calculations."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="dropdown">
                            <select id="Ctl_Dropdown_01" name="timezone">
                                <?php
                                $current = $studio->getopt('timezone', 'UTC');
                                $timezones = timezone_identifiers_list();
                                $now = time();

                                foreach ($timezones as $index => $name) {
                                ?>
                                <option value="<?php echo $name; ?>" <?php if ($name == $current) echo "selected"; ?>><?php echo $name; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Errors</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_04">Send errors to the developer <span class="help tooltip" title="When an error occurs in the script, it will be sent to the developer so that it may be fixed in future versions. If you don't want to send errors, or are modifying the script, you should turn this option off."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_04">
                            <input type="hidden" name="send-errors" value="<?php echo $studio->getopt("send-errors"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_05">Anonymous error reports <span class="help tooltip" title="If enabled, any error reports your script sends to the developer will be anonymous. If disabled, the developer will be sent your Envato username so he can see the error if you contact support."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_05">
                            <input type="hidden" name="errors-anonymous" value="<?php echo $studio->getopt("errors-anonymous"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_03">Show errors <span class="help tooltip" title="If enabled, any errors the script encounters will be shown in full detail. This should be turned off in production."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_03">
                            <input type="hidden" name="show-errors" value="<?php if ($studio->config['errors']['show'] == true) echo "On"; else echo "Off"; ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Telemetry</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_06">Send anonymous usage info <span class="help tooltip" title="Automatically send telemetry including tool usage and software versions to support development. We'll use this information to update the most popular features first."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_06">
                            <input type="hidden" name="send-usage-info" value="<?php echo $studio->getopt("send-usage-info"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                </div>

                <div class="setting-group">
                    <h3>Tools</h3>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_061">Show tools when a site is not selected</label>

                        <div class="switch" id="Ctl_Switch_061">
                            <input type="hidden" name="show-tools-without-site" value="<?php echo $studio->getopt("show-tools-without-site"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_07">Allow some tools to be used without selecting a site <span class="help tooltip" title="Some tools may not require a site to work, such as the keyword research tool. If you enable this option, these tools will work without selecting a site."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_07">
                            <input type="hidden" name="allow-siteless-tools" value="<?php echo $studio->getopt("allow-siteless-tools"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_08">Enable tool switching <span class="help tooltip" title="Changes the design of tool pages to let users easily use the tool on another site or quickly switch to a different tool."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_08">
                            <input type="hidden" name="experimental-tool-design" value="<?php echo $studio->getopt("experimental-tool-design"); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_09">Use cookie-free site selector <span class="help tooltip" title="The script stores the user's current selected website using a cookie. Enabling this option will prevent that behavior, and instead use posted forms to track the website. Warning: This will degrade user experience."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_09">
                            <input type="hidden" name="no-cookie-siteselector" value="<?php echo $studio->getopt("no-cookie-siteselector", 'Off'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting toggle">
                        <label data-switch="Ctl_Switch_2A">Allow local hostnames and IPs <span class="help tooltip" title="This will allow local IP addresses and non-qualified hostnames (such as http://example/ that lacks a proper domain name) to be entered into the tools. This is useful when working in a local environment and testing local websites, but otherwise is a security risk."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="switch" id="Ctl_Switch_2A">
                            <input type="hidden" name="allow-local-hostnames" value="<?php echo $studio->getopt("allow-local-hostnames", 'Off'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>

                    <div class="setting select">
                        <label for="Ctl_Dropdown_02">Default test location <span class="help tooltip" title="Tools which support testing at different geographical areas will use this as their default location."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="dropdown">
                            <select id="Ctl_Dropdown_02" name="speedtest-default-region">
                                <?php
                                $tool = new SpeedTest();
                                $regions = $tool->getRegions();

                                $current = $studio->getopt('speedtest-default-region');
                                if (!$current) $current = 'us-east-1';

                                foreach ($regions as $name => $id) {
                                ?>
                                <option value="<?php echo $id; ?>" <?php if ($id == $current) echo "selected"; ?>><?php echo $name; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class="setting select">
                        <label for="Ctl_Dropdown_020">Default country <span class="help tooltip" title="Tools which require a country selection will select this as the default."><i class="material-icons">&#xE8FD;</i></span></label>

                        <div class="dropdown">
                            <select id="Ctl_Dropdown_020" name="tools-default-country">
                                <?php
                                $path = $studio->bindir . '/countries/en.json';
                                $countries = json_decode(file_get_contents($path), true)['countries'];

                                $current = $studio->getopt('tools-default-country');
                                if (!$current) $current = 'US';

                                foreach ($countries as $code => $name) {
                                    if (is_array($name)) {
                                        $name = $name[0];
                                    }

                                ?>
                                <option value="<?php echo $code; ?>" <?php if ($code == $current) echo "selected"; ?>><?php echo $name; ?></option>
                                <?php } ?>
                            </select>
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
