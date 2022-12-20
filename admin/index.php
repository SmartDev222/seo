<?php

use Studio\Base\PlatformMeta;

require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(1)->header();

$version = PlatformMeta::VERSION_STR;
$parts = explode('.', $version);
if (count($parts) > 2) unset($parts[2]);
$major = implode('.', $parts);
$muted = $studio->getopt('mute_updates') === $major;

if (isset($_GET['mute_updates']) && !DEMO) {
    $studio->setopt('mute_updates', $major);
    $muted = true;
}

$startWeek = strtotime('today') - 604800;
$endWeek = strtotime('today') - 1;

// Tools

$tools = 0;
$toolsGraph = array();

$q = $studio->sql->query("SELECT COUNT(*) AS count FROM history WHERE useTime >= $startWeek AND useTime <= $endWeek");
$row = $q->fetch_array(); $tools = $row['count']; $q->close();

for ($time = $startWeek; $time < $endWeek; $time += 86400) {
    $endTime = $time + 86399;
    $q = $studio->sql->query("SELECT COUNT(*) AS count FROM history WHERE useTime >= $time AND useTime <= $endTime");
    $row = $q->fetch_array();
    $toolsGraph[] = $row['count']; $q->close();
}

// Websites

$websites = 0;
$websitesGraph = array();

$q = $studio->sql->query("SELECT COUNT(DISTINCT(domain)) AS count FROM history WHERE useTime >= $startWeek AND useTime <= $endWeek");
$row = $q->fetch_array(); $websites = $row['count']; $q->close();

for ($time = $startWeek; $time < $endWeek; $time += 86400) {
    $endTime = $time + 86399;
    $q = $studio->sql->query("SELECT COUNT(DISTINCT(domain)) AS count FROM history WHERE useTime >= $time AND useTime <= $endTime");
    $row = $q->fetch_array();
    $websitesGraph[] = $row['count']; $q->close();
}

// Users

$users = 0;
$usersGraph = array();

$q = $studio->sql->query("SELECT COUNT(DISTINCT(`address`)) AS count FROM history WHERE useTime >= $startWeek AND useTime <= $endWeek");
$row = $q->fetch_array(); $users = $row['count']; $q->close();

for ($time = $startWeek; $time < $endWeek; $time += 86400) {
    $endTime = $time + 86399;
    $q = $studio->sql->query("SELECT COUNT(DISTINCT(`address`)) AS count FROM history WHERE useTime >= $time AND useTime <= $endTime");
    $row = $q->fetch_array();
    $usersGraph[] = $row['count']; $q->close();
}

// Accounts

$accounts = 0;
$accountsGraph = array();

$q = $studio->sql->query("SELECT COUNT(*) AS count FROM accounts WHERE timeCreated >= $startWeek AND timeCreated <= $endWeek");
$row = $q->fetch_array(); $accounts = $row['count']; $q->close();

for ($time = $startWeek; $time < $endWeek; $time += 86400) {
    $endTime = $time + 86399;
    $q = $studio->sql->query("SELECT COUNT(*) AS count FROM accounts WHERE timeCreated >= $time AND timeCreated <= $endTime");
    $row = $q->fetch_array();
    $accountsGraph[] = $row['count']; $q->close();
}

?>

<div class="header-v2 blue">
    <h1>Dashboard</h1>
    <p>Welcome back to your studio!</p>
</div>

<div class="dashboard">
    <div class="stats">
        <div class="col">
            <h2 title="The number of times your tools have been used in the last week.">Tools used</h2>
            <p><?php echo number_format($tools); ?> this week</p>

            <div class="sparkline"><!--<?php echo implode(',', $toolsGraph); ?>--></div>
        </div>
        <div class="col">
            <h2 title="The number of unique domain names that were entered into tools in the last week.">Websites analyzed</h2>
            <p><?php echo number_format($websites); ?> this week</p>

            <div class="sparkline"><!--<?php echo implode(',', $websitesGraph); ?>--></div>
        </div>
        <div class="col">
            <h2 title="The number of unique users, including guests, who used tools in the last week.">Users</h2>
            <p><?php echo number_format($users); ?> this week</p>

            <div class="sparkline"><!--<?php echo implode(',', $usersGraph); ?>--></div>
        </div>
        <div class="col">
            <h2 title="The number of new account signups in the last week.">New accounts</h2>
            <p><?php echo number_format($accounts); ?> this week</p>

            <div class="sparkline"><!--<?php echo implode(',', $accountsGraph); ?>--></div>
        </div>
    </div>

    <?php if (!$muted) { ?>
    <div class="update banner">
        <div class="close">
            <a href="?mute_updates=1" title="Hide until the next release">&times;</a>
        </div>

        <div class="icon">
            <img src="data:image/png;base64,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">
        </div>

        <h2>May 2022</h2>
        <h3>Version 1.86</h3>
        <p>
            You've been updated to the May 2022 release. There are several new features and improvements.
            Check the release notes to see what's new. I hope you enjoy it!
        </p>

        <a href="news.php" class="button">View release notes</a>
    </div>
    <?php } ?>

    <?php
        $q = $studio->sql->query("SELECT * FROM activity ORDER BY id DESC LIMIT 25");
        $rows = array();

        while ($row = $q->fetch_object()) {
            $rows[] = $row;
        }

        if (DEMO) {
            $rows = array(
                (object)array('type' => 'info', 'time' => time() - (86400 * 3), 'message' => 'Successful login for email admin from 192.168.1.101.'),
                (object)array('type' => 'info', 'time' => time() - (86400 * 6) - 30, 'message' => 'Successful login for email jane@example.com from 124.32.14.211'),
                (object)array('type' => 'warn', 'time' => time() - (86400 * 6), 'message' => 'Failed login attempt for email jane@example.com from 124.32.14.211'),
                (object)array('type' => 'success', 'time' => time() - (86400 * 8), 'message' => 'Installed new update v1.82.'),
                (object)array('type' => 'info', 'time' => time() - (86400 * 12), 'message' => 'Successful login for email admin from 192.168.1.101.'),
                (object)array('type' => 'info', 'time' => time() - (86400 * 15), 'message' => 'New account created by user, email jane@example.com, from 124.32.14.211'),
                (object)array('type' => 'success', 'time' => time() - (86400 * 18), 'message' => 'Installed new update v1.81.3.'),
                (object)array('type' => 'info', 'time' => time() - (86400 * 25), 'message' => 'New account created by user, email john@example.com, from 123.45.67.21'),
                (object)array('type' => 'info', 'time' => time() - (86400 * 36), 'message' => 'Successful login for email admin from 192.168.1.101.'),
                (object)array('type' => 'info', 'time' => time() - (86400 * 42), 'message' => 'Successful login for email admin from 192.168.1.100.'),
            );
        }

        if (count($rows) < 10) {
            $rows[] = (object)array(
                'message' => 'Welcome to your new studio!',
                'time' => 1483228799,
                'type' => 'success'
            );
        }

        if ($q->num_rows > 0) {
    ?>
    <div class="activity">
        <h2>Recent activity</h2>
        <p>Keep an eye on things around here!</p>

        <div class="log">
            <?php
                foreach ($rows as $row) {
                    $icon = 'info';
                    $message = $row->message;
                    $time = $row->time;
                    $type = $row->type;

                    if (strpos($message, 'Successful login') === 0) $icon = 'account_circle';
                    if (strpos($message, 'Failed login') === 0) $icon = 'error';
                    if (strpos($message, 'New account') === 0) $icon = 'add_circle';
                    if (strpos($message, 'subscribed to and paid') > 0) $icon = 'monetization_on';
                    if (strpos($message, 'had a failed or disputed transaction') > 0) $icon = 'report';
                    if (strpos($message, 'Installed new update') === 0) $icon = 'new_releases';
            ?>
            <div class="record record-<?php echo $type; ?> icon-split">
                <div class="icon">
                    <i class="material-icons"><?php echo $icon; ?></i>
                </div>
                <div class="details">
                    <p><?php echo sanitize_html($message); ?></p>
                </div>
                <div class="time">
                    <div class="time right">
                        <?php echo (new \Studio\Display\TimeAgo($time))->get(); ?>
                        <span data-time="<?php echo $time; ?>"><i class="material-icons">access_time</i></span>
                    </div>
                </div>
            </div>
            <?php
                }
            ?>
        </div>
    </div>
    <?php
        }
    ?>
</div>

<script src="../resources/scripts/sparklines.js"></script>
<script type="text/javascript">
    var width = $('.sparkline').width();
    var numDays = 7;
    var barSpacing = 3;
    var barWidth = (width / numDays) - (barSpacing * ((numDays-1)/numDays));

    $('.sparkline').sparkline('html', {
        type: 'bar',
        spotColor: false,
        highlightSpotColor: null,
        spotRadius: 0,
        width: (width) + 'px',
        height: '28px',
        barWidth: (barWidth) + 'px',
        barSpacing: (barSpacing) + 'px',
        chartRangeMin: 0,
        barColor: '#146fe2',
        tooltipFormat: '<span class="date">{{offset:names}}</span><span class="value">{{value}}</span>',
        tooltipValueLookups: {
            names: {
                0: '<?php echo date('D, F d', $startWeek); ?>',
                1: '<?php echo date('D, F d', $startWeek + 86400); ?>',
                2: '<?php echo date('D, F d', $startWeek + 172800); ?>',
                3: '<?php echo date('D, F d', $startWeek + 259200); ?>',
                4: '<?php echo date('D, F d', $startWeek + 345600); ?>',
                5: '<?php echo date('D, F d', $startWeek + 432000); ?>',
                6: '<?php echo date('D, F d', $startWeek + 518400); ?>',
            }
        }
    });
</script>

<?php
$page->footer();
?>
