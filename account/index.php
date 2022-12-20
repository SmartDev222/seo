<?php

require "../includes/init.php";
$page->setTitle("Account")->setPage(3)->setPath("../")->header()->requireLogin();

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Account"); ?></h1>
    </div>
</section>

<section class="account">
    <div class="container">
        <div class="advertising vertical">
            <div class="content">
                <div class="row">
                    <div class="col-md-4">
                        <a href="websites.php">
                            <div class="option">
                                <i class="material-icons">&#xE894;</i>

                                <h3><?php pt("Manage sites"); ?></h3>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="settings.php">
                            <div class="option">
                                <i class="material-icons">&#xE8B8;</i>

                                <h3><?php pt("Edit account"); ?></h3>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="signout.php">
                            <div class="option">
                                <i class="material-icons">&#xE879;</i>

                                <h3><?php pt("Sign out"); ?></h3>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="slots">
                <?php
                    if (($snippet = Ads::commit('728x90')) !== false) {
                        echo $snippet;
                    }
                    else if (($snippet = Ads::commit('468x60')) !== false) {
                        echo $snippet;
                    }
                ?>
            </div>
        </div>
    </div>
</section>

<?php
$page->footer();
?>
