<?php

$tempPageTitle = "{{title}}";
$tempPageName = "{{filename}}";

require "includes/init.php";
$page->setTitle($tempPageTitle)->setPage(-1);

$targetFile = $studio->basedir . '/resources/pages/' . $tempPageName;

if (!file_exists($targetFile)) {
	http_response_code(404);
	echo "<h1>404 Not Found</h1>";
	die;
}

$page->header();

?>

<section class="title">
    <div class="container">
        <h1><?php pt($tempPageTitle); ?></h1>
    </div>
</section>

<section class="custom-content">
    <div class="container">
        <?php echo file_get_contents($targetFile); ?>
    </div>
</section>

<?php
$page->footer();
?>
