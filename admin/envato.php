<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setTitle("Envato")->header();

if (DEMO) die;
if (!isset($_GET['code'])) die;
$code = $_GET['code'];
$ok = false;

try {
    $api->setKey($code);
    $app = $api->getApplicationInfo();
    $ok = true;
    $studio->setopt("api.secretkey", $app->client_key);
    $studio->setopt("cron-token", "");
    $studio->setopt("google-enabled", "Off");
    $studio->setopt("push-token", "");
}
catch (Exception $e) {
?>
<div class="error"><?php echo $e->getMessage(); ?></div>
<?php
}

if ($ok) {
?>

<div class="panel">
    <h3>Thanks, <?php echo $app->username; ?>!</h3>
    <p>You've successfully registered your copy of SEO Studio. Enjoy!</p>
</div>

<?php
}
else {
?>

<div class="panel">
    <h3>Sorry!</h3>
    <p>It doesn't look like you've purchased SEO Studio on that account. Want to try a different account? You can continue using SEO Studio but you won't receive updates or support until you complete this step.</p>

    <a class="btn green envatoSigninButton" href="javascript:;" style="margin: 15px 0 0;">Try again</a> <a class="btn" href="index.php">Continue</a>
</div>

<?php
}

$page->footer();
?>
