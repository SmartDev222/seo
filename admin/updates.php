<?php

use Studio\Base\PlatformMeta;

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

$q = $studio->sql->query("SELECT * FROM updates WHERE updateStatus <> 1 ORDER BY id DESC");
$q2 = $studio->sql->query("SELECT * FROM plugins WHERE update_available != ''");

$updates = [];
$items = [];

$rows = $q->num_rows + $q2->num_rows;

if (!$api->isAuthorized()) {
    require "_locked.php";
}

$updateWarnings = array();
$updateErrors = array();
$updateChangeLog = array();
$updateObjs = array();

if ($rows > 0) {
    while ($row = $q->fetch_array()) {
        $updates[] = $row['token'];
        $updateObjs[] = $row;

        if ($row['updateWarning'])
            $updateWarnings[] = $row['updateWarning'];

        if ($row['updateError'])
            $updateErrors[] = $row['updateError'];
    }

    $updateObjs = array_reverse($updateObjs);

    foreach ($updateObjs as $row) {
        $info = "\n" . trim($row['updateInfo']);
        $changesLocal = explode("\n- ", $info);

        foreach ($changesLocal as $change) {
            if (empty(trim($change))) continue;
            $updateChangeLog[] = trim($change);
        }
    }
?>

<div class="panel v2">
    <h2>
        <i class="material-icons">&#xE923;</i>
        Updates available!
    </h2>

    <p>Would you like to install the following updates?</p>
    <ul class="list v2">
        <?php foreach ($updateChangeLog as $change) { ?>
        <li><?php echo $change; ?></li>
        <?php } ?>
    </ul>

    <?php if (!empty($updateErrors)) { ?>
    <p style="color: #c92a2a;">We ran into errors when installing your updates:</p>
    <ul class="list v2">
        <?php foreach ($updateErrors as $error) { ?>
        <li style="color: #c92a2a;"><?php echo $error; ?></li>
        <?php } ?>
    </ul>
    <?php } ?>

    <ul class="options v2">
        <li>
            <a class="doManualUpdate"><i class="material-icons">&#xE2C4;</i> <strong>Download and install</strong></a>
        </li>
        <li>
            <a href="update-advanced.php"><i class="material-icons">&#xE5CF;</i> More options</a>
        </li>
    </ul>
</div>

<div class="modal-bg update-modal">
    <div class="modal">
        <div class="b center">
            <div style="padding: 0 0 30px;">
                <img src="../resources/images/load32.gif" width="16px">
            </div>
            <div class="progressbar">
                <div class="progress"></div>
            </div>
            <div class="status">
                We're installing your updates
            </div>
        </div>
    </div>
</div>

<div class="modal-bg warning-modal">
    <div class="modal">
        <div class="title">
            <div class="text">
                Important update information
            </div>
            <a class="close-button">
                &times;
            </a>
        </div>
        <div class="body">
            <p><strong>Do not ignore this!</strong> You must review the following warnings before installing these updates. They may potentially impact your data and/or customization.</p>

            <ul>
                <?php
                foreach ($updateWarnings as $warning) {
                ?>

                <li><?php echo $warning; ?></li>

                <?php
                }
                ?>
            </ul>
        </div>
        <div class="actions">
            <a class="btn blue action-install">Install</a>
            <a class="btn action-cancel">Cancel</a>
        </div>
    </div>
</div>

<script type="text/javascript">
    var updates = [<?php
        $updates = array_reverse($updates);
        foreach ($updates as $i => $v) {
            echo "'" . $v . "'" . (($i < (count($updates) - 1)) ? ", " : "");
        }
    ?>];
    var items = [<?php
        foreach ($items as $i => $item) {
            echo "{$item[1]}" . (($i < (count($items) - 1)) ? ", " : "");
        }
    ?>];

    var numberUpdates = updates.length + items.length;
    var currentUpdate = 1;
    var numberWarnings = <?php echo count($updateWarnings); ?>;
    var warningModal = $('.warning-modal');

    function runNextUpdate() {
        if (updates.length == 0) {
            if (items.length == 0) {
                setTimeout(function() {
                    window.location.reload(true);
                }, 2500);
                return;
            }

            runNextItemUpdate();
            return;
        }

        var updateNumber = updates.shift();

        $.post("bgupdate.php", { token: updateNumber }, function(data) {
            var pct = 100 * (currentUpdate / numberUpdates);
            $(".progress").stop().animate({ width: pct + '%' }, 1000, 'linear');
            currentUpdate++;
            runNextUpdate();
        }).fail(function() {
            var pct = 100 * (currentUpdate / numberUpdates);
            $(".progress").animate({ width: pct + '%' }, 1000, 'linear');
            currentUpdate++;
            runNextUpdate();
        });
    }

    function runNextItemUpdate() {
        if (items.length == 0) {
            setTimeout(function() {
                window.location.reload(true);
            }, 2500);
            return;
        }

        var marketId = items.shift();
        var progress = $('.progress');

        $.post("bgiupdate.php", { id: marketId }, function(data) {
            var pct = 100 * (currentUpdate / numberUpdates);
            progress.stop().animate({ width: pct + '%' }, 1000, 'linear');
            currentUpdate++;
            runNextItemUpdate();
        }).fail(function() {
            var pct = 100 * (currentUpdate / numberUpdates);
            progress.animate({ width: pct + '%' }, 1000, 'linear');
            currentUpdate++;
            runNextItemUpdate();
        });
    }

    function startUpdating() {
        var modal = $(".modal-bg.update-modal");
        modal.show();

        runNextUpdate();
    }

    $(".doManualUpdate").on('click', function(e) {
        if (numberWarnings == 0) {
            startUpdating();
            e.stopPropagation();
        }
        else {
            warningModal.show();
        }
    });

    $(".viewmore").on('click', function() {
        var tr = $(this).parent().parent();
        var expandid = tr.data("expand");

        $(".expand").each(function() {
            if ($(this).attr("id") != expandid) {
                $(this).addClass("hidden");
            }
        });

        $("#" + expandid).toggleClass("hidden");
    });

    warningModal.find('.close-button').on('click', function() {
        warningModal.hide();
    });

    warningModal.find('.action-cancel').on('click', function() {
        warningModal.hide();
    });

    warningModal.find('.action-install').on('click', function(e) {
        warningModal.hide();
        startUpdating();
        e.stopPropagation();
    });

</script>

<?php
}

if ($rows == 0) {
?>

    <div class="panel v2">
        <h2>
            <i class="material-icons">&#xE923;</i>
            No updates available
        </h2>

        <p>We couldn't find any new updates for you. Last checked <?php
            $time = $studio->getopt('last-update-check');
            echo (new \Studio\Display\TimeAgo($time))->get();
            ?>.</p>

        <p style="margin-top: 15px; margin-bottom: 0;">
            <strong>Installed version:</strong>
            <?php echo PlatformMeta::VERSION_STR; ?>
        </p>

        <p style="margin-top: 2px; margin-bottom: 0;">
            <strong>Build date:</strong>
            <?php echo PlatformMeta::VERSION_DATE; ?>
        </p>

        <ul class="options v2">
            <li>
                <a href="?force-check" onclick="this.classList.add('spinning-icon')"><i class="material-icons">&#xE5D5;</i> <strong>Check for updates</strong></a>
            </li>
            <li>
                <a href="updatelog.php"><i class="material-icons">&#xE889;</i> View history</a>
            </li>
        </ul>
    </div>

<?php
}

$page->footer();
?>
