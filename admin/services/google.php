<?php
require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(22)->header('services');

if (!$api->isAuthorized()) {
    require "_locked.php";
    $page->footer();
    $studio->stop();
}

if (isset($_GET['setForceRemote'])) {
    $shouldForceRemote = trim($_GET['setForceRemote']) === '1';
    $studio->setopt('google-force-remote', $shouldForceRemote);
    header("Location: google.php?success=1");
    die;
}

if ($studio->getopt("google-enabled") != "On" || DEMO) {
    if (isset($_POST['email']) && !DEMO) {
        try {
            $ok = $api->enableGoogle($_POST['url'], $_POST['email']);
            if ($ok) {
                $studio->setopt("google-enabled", "On");
                header("Location: google.php?success=1");
                die;
            }
            echo "<div class='error'>We couldn't sign you up for the network right now.</div>";
        }
        catch (Exception $e) {
            echo "<div class='error'>{$e->getMessage()}</div>";
        }
    }
?>

<div class="panel v2 google-proxy">
    <i class="material-icons icon">
        cloud
    </i>

    <h2>Keep your tools working</h2>
    <p>Free built-in service to automatically proxy your Google searches.</p>
</div>

<div class="panel v2 google-proxy-sub">
    <h2>Proxies are expensive</h2>
    <p>
        Don't let your tools break when Google limits your script's web searching capabilities. Don't waste money on
        expensive proxies, either! Our network of servers will perform searches for you, no payment necessary.
    </p>
</div>

<div class="panel v2 google-proxy-sub start">
    <h2>Ready to get started?</h2>
    <p>
        Read our terms and conditions first so you understand how it all works.
    </p>
    <iframe src="../../resources/eulas/google.html?v=2.0"></iframe>

    <div class="start">
        <a class="subscribe">Activate</a>
    </div>
</div>

<div class="modal-container" id="googleModal">
    <div class="modal">
        <form action="" method="post">
            <div class="modal-title">
                <p>Activation</p>
                <a class="close"><i class="material-icons">close</i></a>
            </div>
            <div class="modal-content">
                <p>
                    Please enter your email address to activate the service. Don't worry, we won't spam you or send marketing
                    emails. We'll only use this to notify you if the terms and conditions change.
                </p>

                <input type="hidden" name="url" value="">
                <input type="text" name="email" class="fancy" placeholder="Email..." style="margin: 15px 0 5px;" <?php if (DEMO) echo 'disabled'; ?> value="<?php echo (DEMO ? 'Disabled on demo.' : ''); ?>">
            </div>
            <div class="modal-footer">
                <input type="submit" class="btn blue" value="Activate">
            </div>
            <div class="modal-loading">
                <svg class="spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    var url = window.location.href;
    $("input[name=url]").val(url.substring(0, url.indexOf("/admin") + 1));

    var modalContainer = $('#googleModal');
    var modal = modalContainer.find('.modal');
    var loading = modalContainer.find('.modal-loading');
    var submitButton = modalContainer.find('input[type=submit]');
    var closeButton = modalContainer.find('a.close');

    modalContainer.on('click', function(e) {
        if (e.target === modalContainer[0]) {
            modalContainer.removeClass('active');
        }
    })

    $('.subscribe').on('click', function() {
        modalContainer.addClass('active');
    });

    closeButton.on('click', function() {
        modalContainer.removeClass('active');
    });

    submitButton.on('click', function() {
        loading.addClass('active');
    });
</script>

<?php
}
else {

    if (isset($_GET['disable'])) {
        try {
            $ok = $api->disableGoogle();
            if ($ok) {
                $studio->setopt("google-enabled", "Off");
                header("Location: google.php?success=1");
                die;
            }
            echo "<div class='error'>We couldn't disable your network membership right now. Try contacting support.</div>";
        }
        catch (Exception $e) {
            echo "<div class='error'>{$e->getMessage()}</div>";
        }
    }

    $stats = $api->getGoogleStats();
    $errors = $stats->requests - $stats->successful;
    $errorRate = $stats->requests > 0 ? round(100 * ($errors / $stats->requests), 2) : 0;
    $usagePercent = round(100 * ($stats->spent / $stats->limit), 2);
    $usagePercentFriendly = ceil(100 * ($stats->spent / $stats->limit));
?>

<div class="heading">
    <h1>Google</h1>
    <h2>Proxy service</h2>
</div>

<div class="panel v2 back">
    <a href="./services.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<div class="panel v2 google-proxy-sub white">
    <i class="check material-icons">check</i>

    <h2>Proxying is active!</h2>
    <p>
        Your script is connected to the Google service. Tools will automatically use this service to get search results
        when necessary.
    </p>
    <div class="action">
        <a class="subscribe gray" href="?disable">Disable</a>
    </div>
</div>

<div class="panel v2 google-stats">
    <div class="usage">
        <h3>Your usage</h3>
        <p>You've used <?php echo number_format($usagePercentFriendly); ?>% of your daily limit in the last 24 hours.</p>

        <div class="graph">
            <div class="number">
                <?php echo number_format($stats->spent); ?>
            </div>
            <div class="bar-container">
                <div class="bar">
                    <div class="fill" style="width: <?php echo $usagePercent; ?>%;"></div>
                </div>
            </div>
            <div class="number">
                <?php echo number_format($stats->limit); ?>
            </div>
        </div>
    </div>
    <div class="cols">
        <div class="col">
            <strong><?php echo number_format($stats->reach); ?></strong>
            <span title="The number of different servers that performed searches for you.">Servers reached (30 days)</span>
        </div>
        <div class="col">
            <strong><?php echo number_format($stats->requests); ?></strong>
            <span title="The number of search requests sent by your application.">Requests (30 days)</span>
        </div>
        <div class="col">
            <strong><?php echo $errorRate; ?>%</strong>
            <span title="The percent of requests whose target server was unavailable. This does not mean your tools failed, as the service retries the request with a different server.">Error rate (30 days)</span>
        </div>
    </div>
</div>

<?php
}
$page->footer();
?>
