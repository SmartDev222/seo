<?php
require "includes/init.php";
require "../includes/update.php";

$downloadError = false;
$downloadErrorMessage = '';

if (isset($_GET['download']) && !DEMO) {
    $token = $_GET['download'];

    $update = new Update($token);
    $success = $update->download();

    if (!$success) {
        $downloadError = true;
        $downloadErrorMessage = $update->error;
    }
    else {
        $filePath = $update->updateFile;

        if (file_exists($filePath)) {
            $file_name = basename($filePath);
            $file_name = str_replace("tmp-", "update-", $file_name);

            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=$file_name");
            header("Content-Length: " . filesize($filePath));

            ob_end_flush();

            readfile($filePath);
            @unlink($filePath);
            exit;
        }
    }
}

$page->setPath("../")->requirePermission('admin-access')->setPage(14)->setTitle("Updates")->header();

$q = $studio->sql->query("SELECT * FROM updates WHERE updateStatus <> 1 ORDER BY id DESC");
$q2 = $studio->sql->query("SELECT * FROM plugins WHERE update_available != ''");

$updates = [];
$items = [];

$rows = $q->num_rows + $q2->num_rows;

if (!$api->isAuthorized()) {
    require "_locked.php";
}

$updateChangeLog = array();
$updateObjs = array();

if ($rows > 0) {
    while ($row = $q->fetch_array()) {
        $updates[] = $row['token'];
        $updateObjs[] = $row;
    }

    $updateObjs = array_reverse($updateObjs);

    foreach ($updateObjs as $i => $row) {
        $info = "\n" . trim($row['updateInfo']);
        $changesLocal = explode("\n- ", $info);
        $updateObjs[$i]['changelog'] = array();

        foreach ($changesLocal as $change) {
            if (empty(trim($change))) continue;
            $updateChangeLog[] = trim($change);
            $updateObjs[$i]['changelog'][] = trim($change);
        }
    }
    ?>

    <div class="panel v2">
        <h2>
            <i class="material-icons">&#xE923;</i>
            Advanced updates
        </h2>

        <p>This utility allows you to see the individual updates and manually download update files. Click on an update below to view its details.</p>

        <ul class="options v2">
            <li>
                <a class="doManualUpdate"><i class="material-icons">&#xE2C4;</i> <strong>Install all updates</strong></a>
            </li>
            <li>
                <a href="updates.php?force-check"><i class="material-icons">refresh</i> <strong>Refresh updates</strong></a>
            </li>
            <li>
                <a href="support.php"><i class="material-icons">&#xE8FD;</i> <strong>Report a problem</strong></a>
            </li>
            <li>
                <a href="updates.php"><i class="material-icons">&#xE5C4;</i> <strong>Go back</strong></a>
            </li>
        </ul>
    </div>

    <?php foreach (array_reverse($updateObjs) as $row) { ?>
    <div class="panel v2 collapsible collapsed">
        <h2>
            <?php echo $row['updateVersion']; ?>
            <sub>Released <?php
                echo (new \Studio\Display\TimeAgo($row['updateTime']))->get();
                ?></sub>
            <?php if ($row['updateStatus'] == 2) { ?><sub class="error" style="border: 0;">An error occurred when attempting to install this update: <?php echo $row['updateError']; ?></sub><?php } ?>
        </h2>

        <div class="collapsible">
            <p>This update makes the following changes:</p>

            <ul class="list v2">
                <?php foreach ($row['changelog'] as $change) { ?>
                    <li><?php echo $change; ?></li>
                <?php } ?>
            </ul>

            <p>It also overwrites these files:</p>

            <ul class="list v2">
                <?php
                $files = unserialize($row['updateFiles']);
                $base = dirname(dirname(__FILE__));
                foreach ($files as $f) {
                    $path = $base . '/' . $f;

                    if (file_exists($path))
                        echo "<li>$f</li>";
                }
                ?>
            </ul>

            <ul class="options v2">
                <li>
                    <a href="?download=<?php echo $row['token']; ?>"><i class="material-icons">&#xE2C4;</i> <strong>Download source</strong></a>
                </li>
            </ul>
        </div>
    </div>
    <?php } ?>

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

            $.post("bgiupdate.php", { id: marketId }, function(data) {
                var pct = 100 * (currentUpdate / numberUpdates);
                $(".progress").stop().animate({ width: pct + '%' }, 1000, 'linear');
                currentUpdate++;
                runNextItemUpdate();
            }).fail(function() {
                var pct = 100 * (currentUpdate / numberUpdates);
                $(".progress").animate({ width: pct + '%' }, 1000, 'linear');
                currentUpdate++;
                runNextItemUpdate();
            });
        }

        $(".doManualUpdate").on('click', function(e) {
            var modal = $(".modal-bg.update-modal");
            modal.show();

            runNextUpdate();
            e.stopPropagation();
        });

        $(".viewmore").on('click', function() {
            var tr = $(this).parent().parent();
            var expandid = tr.attr("data-expand");

            $(".expand").each(function() {
                if ($(this).attr("id") != expandid) {
                    $(this).addClass("hidden");
                }
            });

            $("#"+expandid).toggleClass("hidden");
        })

        $(".collapsed").on('click', function() {
            $(this).removeClass("collapsed");
        });

    </script>

    <?php
}

if ($rows == 0) {
    header("Location: updates.php");
}

$page->footer();
?>
