<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(16)->setTitle("Diagnostics")->header();

$errorLogPath = dirname(dirname(__FILE__)) . "/.studio.log";

if (isset($_GET['clear'])) {
    if (file_exists($errorLogPath)) {
        @unlink($errorLogPath);
    }

    header('Location: diagnostics.php?success=1');
    die;
}
?>

<div class="heading">
    <h1>Diagnostics</h1>
    <h2>Find and fix problems</h2>
</div>

<div class="panel v2 back">
    <a href="support.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<div class="row">
    <div class="col-md-9">
        <div class="panel">
            <h3>Run tests</h3>
            <p style="margin: 0 0 15px;" class="to-hide">If you're experiencing technical problems with SEO Studio, try running a diagnostics test to detect the issue.</p>

            <a class="btn blue to-hide" href="javascript:;" onclick="run()">Start</a>

            <div class="table-container thin hidden">
                <table class="table test-table">
                    <thead>
                    <tr>
                        <th>Test</th>
                        <th class="center" width="100px">Result</th>
                        <th>Information</th>
                        <th class="right"></th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <?php
                $marginBottom = 15;
                $text = "Below are the recent recorded errors in your system.";

                $errors = trim((file_exists($errorLogPath)) ? file_get_contents($errorLogPath) : "");

                if (empty($errors) || DEMO) {
                    $marginBottom = 0;
                    $text = "There are no recorded errors in the system at this time.";
                    $errors = '';
                }

                $lines = array_reverse(explode("\n", $errors));
                $numLines = (count($lines) > 25) ? 25 : count($lines);
            ?>

            <div class="pull-right">
                <a class="btn" href="?clear=1">Clear</a>
            </div>

            <h3>Errors</h3>
            <p style="margin-bottom: <?php echo $marginBottom; ?>px;"><?php echo $text; ?></p>

            <?php
            if ($marginBottom > 0) {
            ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Code</th>
                        <th>Error</th>
                        <th>File</th>
                        <th>Line</th>
                        <th>Reported</th>
                        <th class="right">Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    for ($i = 0; $i < $numLines; $i++) {
                        if (!isset($row[1]) || !isset($row[5])) continue;

                        $row = explode("\t", trim($lines[$i]));
                        $time = strtotime($row[5]);
                        ?>
                        <tr>
                            <td><?php echo ucfirst(strtolower(sanitize_html($row[1]))); ?></td>
                            <td><?php echo sanitize_html($row[2]); ?></td>
                            <td><?php echo sanitize_html($row[3]); ?></td>
                            <td><?php echo sanitize_html($row[4]); ?></td>
                            <td><?php if ($row[6] != "0") echo "<a href='javascript: void;' onclick='prompt(\"To reference this error, you can send the following ray to the developer.\", \"{$row[6]}\")'>Yes</a>"; else echo "No"; ?></td>
                            <td>
                                <div class="time right">
                                    <?php echo (new \Studio\Display\TimeAgo($time))->get(); ?>
                                    <span data-time="<?php echo $time; ?>"><i class="material-icons">access_time</i></span>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <?php
                }
            ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="panel">
            <h3>Server</h3>
            <ul>
                <li>Studio <?php echo Studio\Base\Studio::VERSION_STR; ?></li>
                <li>PHP <?php echo phpversion(); ?></li>
                <li>CURL <?php $c = curl_version(); echo $c['version']; ?> (<?php echo (constant('CURL_VERSION_SSL')) ? "SSL" : "no SSL"; ?>)</li>
                <li>JSON <?php echo phpversion('json'); ?></li>
                <li>MySQL <?php echo $studio->sql->server_info; ?></li>
            </ul>
        </div>
        <div class="panel">
            <h3>API Key</h3>
            <p style="font-size: 16px; color: #777;"><?php
            $key = $studio->getopt("api.secretkey");

            if ($key) {
                echo DEMO ? 'Hidden on demo.' : $key;
            ?>
            <br>

            <?php if (!DEMO) { ?><a class="btn green envatoSigninButton" href="javascript:;" style="margin: 15px 0 0;">Reauthorize</a><?php } ?>
            <a class="btn" href="<?php echo $api->getPurchaseURL(); ?>" style="margin: 15px 0 0;">Buy new license</a>
            <?php
            }
            else { ?>
                You have not verified your copy of SEO Studio yet.
                <br>

                <a class="btn green envatoSigninButton" href="javascript:;" style="margin: 15px 0 0;">Unlock</a>
                <a class="btn" href="<?php echo $api->getPurchaseURL(); ?>" style="margin: 15px 0 0;">Buy new license</a>
            <?php } ?></p>
        </div>
        <?php if ($key) { ?>
        <div class="panel">
            <h3>License</h3>
            <?php
            try {
                $l = $api->getApplicationInfo();
                if (DEMO) $l->purchase_code = 'Hidden on demo';

                echo "<ul>";
                echo "<li><strong>Type:</strong> {$l->license}</li>";
                echo "<li><strong>User:</strong> {$l->username}</li>";
                echo "<li><strong>Purchase code:</strong> <a href='javascript:;' onclick=\"$(this).parent().html('{$l->purchase_code}');\">(click to show)</a></li>";
                echo "</ul>";
            }
            catch (Exception $e) {
                echo "Cannot get license info right now ({$e->getMessage()}).";
            }
            ?>
        </div>
        <?php } ?>
    </div>
</div>

<div class="modal-bg md-diag">
    <div class="modal">
        <div class="t">
            Running diagnostics
        </div>
        <div class="b center">
            <div style="padding: 0 0 30px;">
                <img src="../resources/images/load32.gif" width="16px">
            </div>
            <div class="progressbar">
                <div class="progress"></div>
            </div>
            <div class="status">
                This may take a minute, please wait...
            </div>
        </div>
    </div>
</div>

<script>
    var files = ["Settings", "API", "API2", "Tools", "Fileperms", "Google", "Bing", "DOM", "Robots", "Passwords", "Zip", "Bind"];
    var tests = files.length;
    var current = 0;

    function runTest() {
        var file = files.shift();

        current++;
        $.post("bgdiagnose.php", {id: file}, function(data) {
            var success = "<strong style='color:#4CAF50;'>OK</strong>";
            if (!data.success) success = "<strong style='color:#F44336;'>FAIL</strong>";
            var fix = "";
            if (data.execute) {
                fix = "<a class='btn tiny green' href='javascript:;' onclick='runFix(\""+data.execute+"\");'>Fix</a>";
            }
            $(".test-table tbody").append("<tr><td>" + data.test + "</td><td class='center'>" + success + "</td><td>" + data.message + "</td><td class='right'>"+fix+"</td></tr>");

            var w = Math.ceil(100 * (current / tests));
            $(".md-diag .progress").stop().animate({width: w+'%'}, 1500, "linear");

            if (files.length > 0) {
                runTest();
            }
            else {
                $(".md-diag .progress").stop().animate({width: w+'%'}, 500, "linear");
                setTimeout(function() { $(".md-diag").hide(); }, 500);
            }
        }, 'json').fail(function() {
            var success = "<strong style='color:#F44336;'>FAIL</strong>";
            $(".test-table tbody").append("<tr><td>" + file + "</td><td class='center'>" + success + "</td><td>The test did not run successfully.</td><td></td></tr>");

            var w = Math.ceil(100 * (current / tests));
            $(".md-diag .progress").stop().animate({width: w+'%'}, 1500, "linear");

            if (files.length > 0) {
                runTest();
            }
            else {
                $(".md-diag .progress").stop().animate({width: w+'%'}, 500, "linear");
                setTimeout(function() { $(".md-diag").hide(); }, 500);
            }
        });
    }

    function run() {
        // aaaaahh! run!!!!

        $(".table-container").removeClass("hidden");
        $(".to-hide").hide();
        $(".md-diag").show();
        runTest();
    }
</script>

<?php
$page->footer();
?>
