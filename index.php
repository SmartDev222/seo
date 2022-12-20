<?php

use Studio\Content\Pages\StandardPageManager;

require "includes/init.php";
$page->setId('home')->setPage(1)->header();

?>

<section class="jumbo">
    <div class="container">
        <h1><?php pt("Get ahead of your competition"); ?></h1>
        <h2><?php pt("Powerful next-generation tools for search engine optimization at your fingertips."); ?></h2>

        <a href="tools.php"><?php pt("Get started"); ?></a>
    </div>
</section>

<?php
    $home = StandardPageManager::getPage('home', $language->locale);
    $html = $home->getMiddleHTML();

    if ($html !== '') {
?>

<section class="custom-content home-content--middle">
        <div class="container">
            <?php echo $html; ?>
        </div>
</section>

<?php
    }
?>

<section class="icons">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="icon"><i class="material-icons">favorite</i></div>
                <h3><?php pt("Simple"); ?></h3>
                <p><?php pt("Get the information you need quickly and neatly, with no hassle."); ?></p>
            </div>
            <div class="col-md-4">
                <div class="icon"><i class="material-icons">layers</i></div>
                <h3><?php pt("Organized"); ?></h3>
                <p><?php pt("Create an account and easily switch between multiple websites."); ?></p>
            </div>
            <div class="col-md-4">
                <div class="icon"><i class="material-icons">verified_user</i></div>
                <h3><?php pt("Safe"); ?></h3>
                <p><?php pt("Our tools are safe and don't harm your search engine ranks."); ?></p>
            </div>
        </div>
    </div>
</section>

<section class="home-tools">
    <div class="container">
        <form action="tools.php" method="get">
            <input type="text" class="text-input" name="site" placeholder="<?php pt("www.example.com"); ?>">
            <button type="submit" class="loadable"><?php pt("Get started"); ?></button>
        </form>
    </div>
</section>

<?php
    $html = $home->getBottomHTML();

    if ($html !== '') {
?>

<section class="custom-content home-content--bottom">
        <div class="container">
            <?php echo $html; ?>
        </div>
</section>

<?php
    }
?>

<?php
$page->footer();
?>
