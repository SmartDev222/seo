<?php
    use SEO\Services\KeywordsEverywhere;

    require "includes/init.php";
    $page->setPath("../")->requirePermission('admin-access')->setPage(22)->setTitle("Keywords")->header();

    $sharing = $studio->getopt('keywords.sharing');
    if (is_null($sharing)) $sharing = true;

    $key = $studio->getopt('keywords.key');
    if (empty($key)) $key = '';

    if (isset($_POST['key']) && !DEMO) {
        $key = trim($_POST['key']);
        $studio->setopt('keywords.key', $key);

        if (!empty($key)) {
            try {
                if (!KeywordsEverywhere::checkApiKey($key)) {
                    $studio->setopt('keywords.key', null);
?>
<div class="error">
    <i class="material-icons">error_outline</i>
    <span>The key you entered is not valid. Please try again.</span>
</div>
<?php
                }
                else {
?>
<div class="success">
    <i class="material-icons">check</i>
    <span>The key you entered was tested and is working successfully. Your tools now have access to real keyword data.</span>
</div>
<?php
                }
            }
            catch (Exception $e) {
?>
<div class="error">
    <i class="material-icons">error_outline</i>
    <span>Error when testing key: <?php echo $e->getMessage(); ?></span>
</div>
<?php
            }
        }
    }

    if (isset($_GET['toggle'])) {
        $sharing = !$sharing;
        $studio->setopt('keywords.sharing', $sharing);
    }
?>

<div class="heading">
    <h1>Keywords</h1>
    <h2>Configure data source</h2>

    <p>
        Due to external data sources becoming more scarce, we now require a free API key in order to get keyword data,
        including CPC, volume, and competition.
    </p>
</div>

<div class="panel v2 back">
    <a href="./services.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<div class="panel v2">
    <p style="margin-bottom: 25px;">
        Click the button to obtain a free key. Then, enter it in the textbox below.
    </p>
    <a href="https://keywordseverywhere.com/first-install-addon.html" target="_blank" class="btn green">Get a free key</a>
</div>

<div class="panel v2">
    <h2>
        <i class="material-icons">settings</i>
        Enter your key
    </h2>

    <form action="" method="post">
        <input type="text" class="fancy" name="key" placeholder="Please enter your key here..." value="<?php echo $key; ?>">
        <input type="submit" class="btn blue" value="Save">
    </form>
</div>

<div class="panel v2">
    <h2>
        <i class="material-icons">share</i>
        Data sharing
    </h2>

    <p style="margin-bottom: 15px;">
        This option is enabled by default. When enabled, your script will send a copy of keyword data (search volume,
        cpc, and competition) to our servers. We will build a database of this information to help ensure our keyword
        tools continue working in the future. This does not share any personal data and will not degrade the performance of your tools.
    </p>

    <p style="margin-bottom: 15px;">
        If you don't want to help us keep these tools working in the future, you can disable this option.
    </p>

    <?php if ($sharing) { ?><a class="btn" href="?toggle">Disable data sharing</a><?php } else { ?>
    <a class="btn blue" href="?toggle">Enable data sharing</a><?php } ?>
</div>

<?php
    $page->footer();
?>
