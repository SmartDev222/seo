<?php
require "../includes/init.php";

if (isset($_GET['status'])) {
    $bool = $studio->getopt('cron-last-run') > (time() - 43200);

    echo json_encode([
        'success' => $bool
    ]);
    die;
}

$page->setPath("../../")->requirePermission('admin-access')->setPage(22)->header('services');

if (isset($_POST['doJoinCronService']) && !DEMO) {
    $url = $_POST['url'];

    try {
        $data = $api->enableCron($url);
        if ($data->enabled) {
            $studio->setopt('cron-token', $data->token);

            header("Location: cron.php");
            die;
        }

        echo "<div class='error'>Couldn't enable the cron service.</div>";
    }
    catch (Exception $e) {
        echo "<div class='error'>{$e->getMessage()}</div>";
    }
}

if (isset($_GET['stopcron']) && !DEMO) {
    try {
        $data = $api->disableCron();
        if ($data->disabled) {
            $studio->setopt('cron-token', '');
            $studio->setopt('cron-last-run', 0);

            header("Location: cron.php");
            die;
        }

        echo "<div class='error'>Couldn't disable the cron service right now. You can try contacting customer support.</div>";
    }
    catch (Exception $e) {
        echo "<div class='error'>{$e->getMessage()}</div>";
    }
}

if ($studio->getopt('cron-last-run') < (time() - 43200) || DEMO) {
    if ($studio->getopt('cron-token') != '' && !DEMO) {
?>
<div class="panel v2 cron-ping">
    <svg class="spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>

    <h2>We're setting up your cron job</h2>
    <p>
        Stand by while we take care of some things on our end. This may take a minute or two. You can safely leave this
        page and come back later to check on the status.
    </p>

    <div class="action" style="margin-top: 40px;">
        <a class="subscribe gray" href="?stopcron">Cancel</a>
    </div>
</div>

<script>
    function check() {
        $.get('cron.php?status', function(data) {
            if (data.success) {
                window.location.reload(true);
            }
            else {
                setTimeout(check, 3000);
            }
        }, 'json');
    }

    setTimeout(check, 3000);
</script>
<?php
die;
    }
?>

<div class="panel v2 google-proxy-sub white">
    <h2>Configure your cron job in a click</h2>
    <p>
        We'll have our servers remotely ping your cron script every few hours. It's safe and totally free. Just click
        the button below to activate it, and we'll take care of the rest.
    </p>

    <div class="action">
        <form action="" method="post">
            <input type="hidden" name="url">
            <?php
                if (!$api->isAuthorized()) {
            ?>
            <a class="subscribe green envatoSigninButton" href="javascript:;">Activate</a>
            <?php
            }
            else {
            ?>
            <input type="submit" name="doJoinCronService" value="Activate" class="subscribe">
            <?php
            }
            ?>
        </form>
    </div>
</div>

<div class="panel v2 google-proxy-sub white">
    <h2>
        Configure it manually
    </h2>

    <p>Ask your web host for help with configuring the following cron job.</p>

    <?php
        $pathToCron = $studio->basedir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "execute" . DIRECTORY_SEPARATOR . "cron.php";
        if (DEMO) $pathToCron = '/home/path/disabled/on/demo';
    ?>
    <pre style="margin: 20px 0 0; padding: 13px 20px; color: #777;">0,30 * * * * php -q <?php echo $pathToCron; ?></pre>
</div>

<?php
}
else {
?>
<div class="panel v2 google-proxy-sub white">
    <i class="check material-icons">check</i>

    <h2>Cron is working!</h2>
    <p>
        Your cron job is configured and helping to keep your application running smoothly. It last ran <?php
     echo (new \Studio\Display\TimeAgo($studio->getopt('cron-last-run')))->get(); ?>.
    </p>

    <?php
    if ($studio->getopt("cron-token") != "") {
    ?>
    <div class="action">
        <a class="subscribe gray" href="?stopcron">Disable</a>
    </div>
    <?php } ?>
</div>

<?php
}
?>

<script type="text/javascript">
    var url = window.location.href;
    $("input[name=url]").val(url.substring(0, url.indexOf("/admin")+1));
</script>

<?php
$page->footer();
?>
