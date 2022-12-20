<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(6)->setTitle("Cache")->header();

# Calculate disk space usage for cache

$dbName = $studio->config['database']['name'];

$q = $studio->sql->query("SELECT table_name AS `Table`, round(((data_length + index_length) / 1024 / 1024), 2) `SizeMB` FROM information_schema.TABLES WHERE table_schema = '$dbName' AND table_name = 'cache';");

$row = $q->fetch_array();
$size = round($row['SizeMB'], 1);

$available = disk_free_space(getcwd());
if ($available === false) $available = "unknown";
else $available = round($available / 1024 / 1024);

$pct = round((100 * ((float)$size / (float)$available)), 2);

if (isset($_GET['clear']) && !DEMO) {
    $clear = $_GET['clear'];

    if ($clear == "all") {
        $studio->sql->query("DELETE FROM cache;");

        header("Location: cache.php");
        die;
    }

    if ($clear == "expired") {
        $days = (int)$studio->getopt("cache-duration");
        $expired = time() - (86400 * $days);
        $studio->sql->query("DELETE FROM cache WHERE `time` < $expired;");

        header("Location: cache.php?e=$expired");
        die;
    }
}
?>

<div class="panel">
    <div class="small-center-container">
        <div class="progressbar">
            <div class="progress" style="width: <?php echo $pct; ?>%;"></div>
        </div>

        <div class="progressinfo">
            <?php echo $size; ?> / <?php echo $available; ?> MB
        </div>
    </div>
</div>

<div class="panel">
    <h3>Cache configuration</h3>

    <ul class="settings-list">
        <li><strong>Caching:</strong> &nbsp; <?php echo $studio->getopt("cache"); ?></li>
        <li><strong>Cache duration:</strong> &nbsp; <?php echo $studio->getopt("cache-duration"); ?> days</li>
    </ul>

    <div style="margin: 10px 0 0;">
        <a href="settings.php" class="btn blue">Edit settings</a>
    </div>
</div>

<div class="panel">
    <h3>Clear expired cache</h3>

    <p>This function is automatically run by the cron. If you have not configured the <a href="cron.php">cron job</a>, you will want to manually execute it <a href="cron.php">at this page</a>.</p>

    <div style="margin: 20px 0 0;">
        <a href="?clear=expired" class="btn">Clear expired cache</a>
    </div>
</div>

<div class="panel">
    <h3>Clear all cache</h3>

    <p>This will completely remove all cache records from the database. Doing so will force all tools to update their records next time they are run. Please note that it can take some time for the cache disk usage above to update.</p>

    <div style="margin: 20px 0 0;">
        <a href="?clear=all" class="btn">Clear all cache</a>
    </div>
</div>

<?php
$page->footer();
?>
