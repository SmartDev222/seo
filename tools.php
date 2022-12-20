<?php

use Studio\Content\Pages\StandardPageManager;

require "includes/init.php";
$page->setId('tools')->setPage(2)->header();

$requireSavedProjects = $studio->getopt('project-mode') === 'On';
$currentWebsite = null;

if (isset($_GET['site']) && $account->isLoggedIn() && $requireSavedProjects) {
    if ($p = $studio->sql->prepare("SELECT domain, userId FROM websites WHERE userId = {$account->getId()} AND domain LIKE ? ORDER BY timeCreated DESC")) {
        $p->bind_param("s", $_GET['site']);
        $p->execute();
        $p->store_result();

        if ($p->num_rows == 1) {
            $p->bind_result($domain, $userId);
            $p->fetch();

            $account->setCurrentWebsite($domain);
            $studio->redirect("tools.php");
        }
        else {
            $studio->redirect("account/websites.php");
        }
    }
}
elseif (isset($_GET['site'])) {
    $site = $_GET['site'];

    try {
        $url = new \SEO\Helper\Url($site);

        // Store the current website in cookies
        if ($studio->getopt('no-cookie-siteselector') !== 'On') {
            $account->setCurrentWebsite($url->domain);
            $studio->redirect("tools.php");
        }

        // Store the current website on the page
        else {
            $currentWebsite = $url->domain;
        }
    }
    catch (Exception $e) {
        $badInput = true;
    }
}

if (is_null($currentWebsite)) {
    $currentWebsite = $account->getCurrentWebsite();
}

$toolsPage = StandardPageManager::getPage('tools', $language->locale);

?>

<section class="website">
    <div class="container">
        <?php
        if ($account->isLoggedIn()) {
        ?>
        <form action="" method="get">
            <div class="site-selector">
                <?php if (!$requireSavedProjects) { ?>
                <input type="text" class="text-input" name="site" placeholder="<?php pt("Click here to enter a website..."); ?>" value="<?php
                    if ($currentWebsite != null) echo sanitize_html($currentWebsite);
                ?>"/>
                <?php } ?>
                <div class="apply hidden">
                    <button type="submit" class="loadable"><?php pt("Submit"); ?></button>
                </div>
                <div class="select <?php echo ($requireSavedProjects) ? 'project' : '' ?>">
                    <?php if ($requireSavedProjects) { ?>
                    <div class="label">
                        <?php
                            if ($currentWebsite != null) echo sanitize_html($currentWebsite);
                            else echo "Select a site...";
                        ?>
                    </div>
                    <?php } ?>
                    <div class="button <?php echo ($requireSavedProjects) ? 'project' : '' ?>">
                        <div class="arrow-down"></div>
                    </div>
                    <div class="dropdown">
                        <ul>
                            <?php
                            $websites = $studio->sql->query("SELECT domain, userId FROM websites WHERE userId = {$account->getId()} ORDER BY timeCreated DESC");

                            if ($websites->num_rows == 0) {
                            ?>
                            <li>
                                <a><?php pt("You haven't added any sites yet."); ?></a>
                            </li>
                            <?php
                            }
                            else {
                                while ($site = $websites->fetch_array()) {
                                    $active = "";

                                    if ($account->getCurrentWebsite() == $site['domain']) $active = " class=\"active\"";
                            ?>
                            <li<?php echo $active; ?>><a href="?site=<?php echo sanitize_attribute($site['domain']); ?>"><?php echo sanitize_html($site['domain']); ?></a></li>
                            <?php
                                }
                            }
                            ?>
                            <li><hr /></li>
                            <li><a href="account/websites.php"><div class="icon" data-icon="P"></div> <?php pt("Manage sites"); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </form>
        <?php
        }
        else {
        ?>
        <form action="" method="get">
            <div class="site-selector">
                <input type="text" class="text-input" name="site" placeholder="<?php pt("Click here to enter a website..."); ?>" value="<?php
                    if ($currentWebsite != null) echo sanitize_html($currentWebsite);
                ?>"/>
                <div class="apply hidden">
                    <button type="submit" class="loadable"><?php pt("Submit"); ?></button>
                </div>
            </div>
        </form>
        <?php
        }
        ?>
    </div>
</section>

<?php if (($snippet = Ads::commit('header')) !== false) { ?>
    <?php if ($studio->getopt('ad-header-container') === 'On') { ?><div class="container"><?php } ?>
    <?php echo $snippet; ?>
    <?php if ($studio->getopt('ad-header-container') === 'On') { ?></div><?php } ?>
<?php } ?>

<?php
    $topHtml = $toolsPage->getTopHTML();
    $bottomHtml = $toolsPage->getBottomHTML();

    if ($topHtml !== '') {
?>

<section class="custom-content tool-content--top">
        <div class="container">
            <?php echo $topHtml; ?>
        </div>
</section>

<?php
    }
?>

<section class="tools <?php if ($topHtml) echo 'top-html '; if ($bottomHtml) echo 'bottom-html '; ?>">
    <div class="container">
        <div class="advertising">
            <div class="content">
        <?php

        if (isset($badInput)) $studio->showError(rt("Please enter a valid website."));

        if ($currentWebsite == null && $studio->getopt('show-tools-without-site') !== 'On') {
            if ($account->isLoggedIn()) $message = rt("Choose a website from the dropdown above to get started.");
            else $message = rt("Enter a website above to get started.");

            $message = sanitize_html($message);
            echo "<h3>$message</h3>";
            $page->footer();
            die;
        }

        $tools = unserialize($studio->getopt("tools"));
        $categories = unserialize($studio->getopt("categories"));
        $catDescriptions = json_decode($studio->getopt('category-descriptions', '{}'), true);
        $toolsDisabled = json_decode($studio->getopt('tools.disabled', '[]'), true);
        $toolClasses = $studio->getTools();

        /**
         * Returns `true` if the specified tool ID is enabled.
         *
         * @param string $toolId
         * @return bool
         */
        function isToolEnabled($toolId) {
            global $toolsDisabled;
            return !in_array($toolId, $toolsDisabled);
        }

        $plugins->call("tools_before");

        foreach ($categories as $index => $cat) {
            $sectionTools = $tools[$cat];

            // First, count the number of enabled tools
            $numEnabledTools = 0;
            foreach ($sectionTools as $id) {
                $isEnabled = isToolEnabled($id);

                $icon = '';
                foreach ($toolClasses as $t) if ($t->id == $id) {
                    $icon = $t->icon;
                }

                if ($isEnabled && !$icon) {
                    $isEnabled = false;
                }

                $numEnabledTools += $isEnabled ? 1 : 0;
            }

            // Skip the category if there aren't any tools to show
            if ($numEnabledTools === 0) {
                continue;
            }

            // Get the category description
            $description = '';
            if (isset($catDescriptions[$cat])) {
                if (isset($catDescriptions[$cat][$language->locale])) {
                    $description = $catDescriptions[$cat][$language->locale];
                }
            }
        ?>

        <div class="category">
            <div class="title">
                <h2><?php echo sanitize_html(rt("@$cat")); ?></h2>
                <?php if ($description !== '') { ?>
                <p><?php echo $description; ?></p>
                <?php } ?>
            </div>

            <div class="tools">
        <?php
            foreach ($sectionTools as $id) {
                $icon = "";
                $name = "";
                $toolPage = null;
                foreach ($toolClasses as $t) if ($t->id == $id) {
                    $icon = 'resources/icons/' . $t->icon . '.png';
                    $name = $t->name;
                    $toolPage = $t->getPage('');

                    if ($toolPage) {
                        $icon = $toolPage->getIcon();
                    }
                }

                // Skip this tool if not enabled
                if (!isToolEnabled($id)) {
                    continue;
                }

                $classNames = $plugins->callCombined('tool_classes', array($id));
                $customClasses = '';

                if ($classNames && is_array($classNames) && !empty($classNames)) {
                    $customClasses = ' ' . implode(' ', $classNames);
                }

                $domain = $studio->getopt('no-cookie-siteselector') === 'On' ? "&site=$currentWebsite" : '';

                if ($icon != "") {
        ?>
                <a class="tool<?php echo $customClasses; ?>" href="tool.php?id=<?php echo sanitize_attribute($id . $domain); ?>">
                    <div class="tool-loader"></div>
                    <div class="tc">
                        <div>
                            <img src="<?php echo $icon; ?>">
                            <span><?php et($name); ?></span>
                        </div>
                    </div>
                </a>
        <?php
                }
            }
        ?>
            </div>

            <?php
                if (($index + 1) % 3 === 0) {
                    if (($snippet = Ads::commit('468x60')) !== false) {
                        echo $snippet;
                    }
                }
            ?>
        </div>

        <?php
            $plugins->call("tools_category_after");
        }

        $plugins->call("tools_after");
        ?>
            </div>
            <div class="slots">
        <?php
            if (Ads::enabled(['300x600', '300x250'])) {
                $size = Ads::resolve(['300x600', '300x250']);

                if (($snippet = Ads::commit('300x600')) !== false) {
                    echo $snippet;
                }

                if (($snippet = Ads::commit('300x250')) !== false) {
                    echo $snippet;
                }

                if (($snippet = Ads::commit('300x250')) !== false) {
                    echo $snippet;
                }
            }
            else {
                if (($snippet = Ads::commit('120x600')) !== false) {
                    echo $snippet;
                }

                if (($snippet = Ads::commit('120x600')) !== false) {
                    echo $snippet;
                }
            }
            ?>
        </div>
            </div>
        </div>
    </div>
</section>

<?php
    if ($bottomHtml !== '') {
?>

<section class="custom-content tool-content--bottom">
        <div class="container">
            <?php echo $bottomHtml; ?>
        </div>
</section>

<?php
    }
?>

<div style="width: 1px; height: 1px; overflow: hidden;">
    <img src="resources/images/b-load32.gif">
</div>

<?php
$page->footer();
?>
