<?php

use Studio\Display\TimeAgo;

require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(14)->setTitle("Updates")->header();

if (!isset($_GET['force-check'])) {
    $studio->checkUpdates();
}
else {
    $studio->checkUpdates(0, true);
    header("Location: updates.php");
    die;
}

$q = $studio->sql->query("SELECT * FROM updates WHERE updateStatus = 1 ORDER BY updateTime DESC");
$statuses = array(
    0 => 'Ready',
    1 => 'Installed',
    2 => 'Failed'
);

$days = [];

while ($row = $q->fetch_array()) {
    $date = date('F d, Y', $row['updateTime']);

    // Group updates by date
    if (!isset($days[$date])) {
        $ago = new TimeAgo($row['updateTime']);
        $days[$date] = [
            'ago' => $ago->get(),
            'timestamp' => $row['updateTime'],
            'version' => $row['updateVersion'],
            'updates' => [],
            'description' => ''
        ];
    }

    $version = $row['updateVersion'];
    $changes = [];

    // Extract the changes and description
    $split = explode("\n", $row['updateInfo']);
    foreach ($split as $line) {
        $line = trim($line);

        if (substr($line, 0, 2) == '- ') {
            $changes[] = substr($line, 2);
        }
        elseif (!empty($line)) {
            $days[$date]['description'] .= $line . ' ';
        }
    }

    // Add the update
    $days[$date]['updates'][] = [
        'version' => $version,
        'changes' => $changes
    ];
}

?>

<div class="heading">
    <h1>Update history</h1>
    <h2>See what's been installed</h2>
</div>
<div class="panel v2 back">
    <a href="./updates.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<?php if (empty($days)) { ?>

    <div class="panel v2">
        No updates have been installed at this time.
    </div>

<?php } else { ?>

    <div class="panel v2 update-log">
        <?php
            foreach ($days as $date => $day) {
                $ago = $day['ago'];
                $version = $day['version'];
                $timestamp = $day['timestamp'];
                $updates = array_reverse($day['updates']);
        ?>
        <div class="entry">
            <div class="entry-head">
                <h3><?php echo $date; ?></h3>
                <div class="time icon-left">
                    <span data-time="<?php echo $timestamp; ?>"><i class="material-icons">query_builder</i></span>
                    <?php echo $ago; ?>
                </div>
                <div class="time-lookalike version">
                    <span data-time="<?php echo $timestamp; ?>"><i class="material-icons">public</i></span>
                    <?php echo $version; ?>
                </div>
            </div>

            <div class="changelog">
                <?php if (!empty($description)) { ?>
                    <p><?php echo $description; ?></p>
                <?php } ?>

                <ul>
                    <?php
                        foreach ($updates as $update) {
                            foreach ($update['changes'] as $change) {
                    ?>
                    <li><?php echo $change; ?></li>
                    <?php
                            }
                        }
                    ?>
                </ul>
            </div>
        </div>
        <?php
            }
        ?>
    </div>

<?php } ?>

<script>
$(".viewmore").on('click', function() {
    var tr = $(this).parent().parent();
    var expandid = tr.attr("data-expand");

    $(".expand").each(function() {
        if ($(this).attr("id") != expandid) {
            $(this).addClass("hidden");
        }
    });

    $("#"+expandid).toggleClass("hidden");
});
</script>

<?php
$page->footer();
?>
