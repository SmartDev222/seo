<?php

if (isset($_GET['check'])) die(" check ok ");

require_once dirname(dirname(__FILE__)) . "/init.php";

if ($studio->errors instanceof Studio\Base\ErrorHandler) {
    $old = $studio->errors->setMode('cron');
}

require dirname(dirname(__FILE__)) . "/update.php";
require dirname(dirname(__FILE__)) . "/itemupdate.php";

@ini_set('max_execution_time', 120);

# Update URL
# Only runs if you've joined the Google Network or enabled the cron service.
# (The URL to your Studio is required for these services to work - they have to connect to your app)

if ($studio->getopt("google-enabled") == "On" || $studio->getopt("cron-token") != "") {
    try {
        $api->updateURL($studio->getopt("public-url"));
    }
    catch (Exception $e) {
        // do nothing...
    }
}

# Clean expired cache

$days = (int)$studio->getopt("cache-duration");
$expired = time() - (86400 * $days);
$studio->sql->query("DELETE FROM cache WHERE `time` < $expired;");

# Usage stats

$sent = "not sent (disabled)";
if ($studio->getopt('send-usage-info') == "On") {
    $lastUsageReport = $studio->getopt('last-uinfo-report');

    if ($lastUsageReport <= (time() - (86400*2.7))) {
        $usage = new Studio\Common\Usage();
        $usage->generate()->saveFile()->send();
        $sent = "sent";
    }
    else $sent = "not sent (delayed)";
}

# Check for updates

$speed = isset($_GET['fast']) ? 60 : 3600;
$studio->checkUpdates($speed);

# Perform automatic upgrades if enabled

$backups = ($studio->getopt("automatic-updates-backup") == "On");
$numUpdates = 0;
$successfulUpdates = 0;
$maxUpdatesPerRun = 15;
$extra = "";

if ($studio->getopt('automatic-updates') == "On") {
    $q = $studio->sql->query("SELECT * FROM updates WHERE updateStatus = 0 ORDER BY updateTime ASC"); //oldest first (have to go in order)

    while ($row = $q->fetch_array()) {
        if ($numUpdates > $maxUpdatesPerRun) continue;
        if (!empty($row['updateWarning'])) {
            $extra = ", halted automatic updates (one or more updates require review by admin)";
            break;
        }

        $update = new Update($row['token'], true);
        $success = $update->run($backups);
        $numUpdates++;

        if ($success) {
            $successfulUpdates++;

            $studio->addActivity(new Studio\Common\Activity(
                Studio\Common\Activity::SUCCESS,
                "Installed new update v" . $row['updateVersion'] . "."
            ));
        }
        else {
            $studio->addActivity(new Studio\Common\Activity(
                Studio\Common\Activity::ERROR,
                "Failed to install new update v" . $row['updateVersion'] . " due to an error."
            ));
        }
    }
}

$studio->getPluginManager()->call("cron_run");

echo $successfulUpdates . " successful updates, $numUpdates total updates, backups " . ($backups ? "enabled" : "disabled") . ", cache cleared with interval ($days days), usage report $sent" . $extra;

if (!isset($key)) $studio->setopt("cron-last-run", time());

if (isset($key)) {
    unlink("cron._{$key}.key");
}
?>
