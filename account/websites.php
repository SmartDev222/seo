<?php

require "../includes/init.php";
$page->setTitle("Manage websites")->setPage(3)->setPath("../")->header()->requireLogin();

$form = new \Studio\Forms\NewWebsiteForm;
$myID = $account->getId();
$websites = $studio->sql->query("SELECT domain, userId FROM websites WHERE userId = $myID ORDER BY timeCreated DESC");

$canRemoveSites = $account->group()['delete-sites'] > 0;
$canAddSites = $account->group()['add-sites'] > 0;

if (isset($_GET['remove']) && $canRemoveSites) {
    if (!DEMO) {
        $p = $studio->sql->prepare("DELETE FROM websites WHERE userId = $myID AND domain = ?;");
        $d = $_GET['remove'];
        $p->bind_param("s", $d);
        $p->execute();

        if ($studio->sql->affected_rows == 1) {
            $p->close();
            $studio->redirect("account/websites.php");
        }
    }
}

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Manage websites"); ?></h1>
    </div>
</section>

<section class="websites">
    <div class="container">
        <div class="advertising">
            <div class="content">
                <h3><?php pt("Your websites"); ?></h3>

                <div class="list">
                    <?php
                    while ($site = $websites->fetch_array()) {
                    ?>

                    <div class="item" style="position: relative;">
                        <a href="../tools.php?site=<?php echo $site['domain']; ?>" style="color: #555;"><?php echo $site['domain']; ?></a>

                        <?php if ($canRemoveSites) { ?>
                            <a href="websites.php?remove=<?php echo $site['domain']; ?>" title="Delete website" style="position: absolute; top: 0; right: 0; display: inline-block; height: 38px; width: 50px; text-align: center; color: #555; font-size: 21px; line-height: 38px; text-decoration: none !important;">&times;</a>
                        <?php } ?>
                    </div>

                    <?php
                    }

                    if ($websites->num_rows == 0) echo "<div style='padding: 15px;'>" . rt("You haven't added any sites yet.") . "</div>";
                    ?>
                </div>
            </div>
            <div class="slots">
                <?php
                    if (($snippet = Ads::commit('300x250')) !== false) {
                        echo $snippet;
                    }
                    else if (($snippet = Ads::commit('250x250')) !== false) {
                        echo $snippet;
                    }
                    else if (($snippet = Ads::commit('200x200')) !== false) {
                        echo $snippet;
                    }
                ?>
            </div>
        </div>
    </div>
</section>

<?php if ($canAddSites) { ?>
<section class="websites">
    <div class="container">
        <h3><?php pt("Add a new website"); ?></h3>

        <form action="" method="post">
            <?php

            $form->showErrors();

            ?>
            <input type="text" class="text-input" name="url" placeholder="<?php pt("Enter domain name"); ?>" />
            <input type="submit" value="<?php pt("Add"); ?>" />
        </form>
    </div>
</section>
<?php } ?>

<?php
$page->footer();
?>
