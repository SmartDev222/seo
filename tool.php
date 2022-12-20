<?php

require "includes/init.php";

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
        $currentWebsite = $url->domain;

        // Store the current website in cookies
        if ($studio->getopt('no-cookie-siteselector') !== 'On') {
            $account->setCurrentWebsite($currentWebsite);
        }
    }
    catch (Exception $e) {
        $badInput = true;
    }
}

if (is_null($currentWebsite)) {
    $currentWebsite = $account->getCurrentWebsite();
}

if (!isset($_GET['id'])) {
    $studio->redirect("tools.php");
}

if (!$account->isLoggedIn() && $studio->getopt("login-tools") == "On") {
    $_SESSION['return'] = (isset($_GET['id']) ? urlencode($_GET['id']) : "");
    $studio->redirect("account/login.php");
}

$tools = $studio->getTools();
$tool = null;

foreach ($tools as $t) {
    if ($t->id == $_GET['id']) {
        $tool = $t;
    }
}

if ($currentWebsite == null && $studio->getopt('show-tools-without-site') !== 'On') {
    $studio->redirect("tools.php");
}

$toolEnabled = true;
if ($tool) {
    $toolsDisabled = json_decode($studio->getopt('tools.disabled', '[]'), true);
    $toolEnabled = !in_array($tool->id, $toolsDisabled);
}

if ($tool == null || !$toolEnabled) {
    $page->setTitle("Not Found")->setPage(2.5)->header();

    echo sanitize_trusted("<section class=\"generic\">
        <div class=\"container\">
            <h3>" . sanitize_html(rt("Tool not found")) . "</h3>
        </div>
    </section>");

    if (function_exists('http_response_code')) {
        http_response_code(404);
    }

    $page->footer();
    die;
}

$plugins->call("tool_init", [$tool->id]);

if ($studio->getopt('show-tools-without-site') === 'On' || $currentWebsite) {
    $tool->prerun($currentWebsite);
}

$topFileHtml = dirname(__FILE__) . '/resources/templates/custom/' . $tool->id . '-top.html';
$topFilePHP = dirname(__FILE__) . '/resources/templates/custom/' . $tool->id . '-top.php';
$bottomFileHtml = dirname(__FILE__) . '/resources/templates/custom/' . $tool->id . '-bottom.html';
$bottomFilePHP = dirname(__FILE__) . '/resources/templates/custom/' . $tool->id . '-bottom.php';

$toolPage = $tool->getPage($language->locale);
$title = rt($tool->name);

if ($toolPage->getTitle() !== '') {
    $title = $toolPage->getTitle();
}

$page->setMeta($toolPage->getMetaTags());

$page->setTitle($title)->setPage(2.5)->header();
?>

<?php if ($studio->getopt('experimental-tool-design') === 'On') { ?>
    <section class="title tool-title">
        <div class="container">
            <div class="flex">
                <div class="icon">
                    <img src="<?php echo sanitize_attribute($toolPage->getIcon()); ?>">
                </div>
                <div class="name">
                    <h1><?php echo sanitize_html(rt($tool->name)); ?></h1>
                </div>
            </div>
        </div>
    </section>

    <?php if ($tool->requiresWebsite) { ?>
    <section class="website alt">
        <div class="container">
            <?php
            if ($account->isLoggedIn()) {
            ?>
            <form action="" method="get" class="loadable">
                <div class="site-selector">
                    <?php if (!$requireSavedProjects) { ?>
                    <?php if (!$studio->usePermalinks()) { ?>
                    <input type="hidden" name="id" value="<?php echo sanitize_attribute($_GET['id']); ?>">
                    <?php } ?>
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

                                        if ($currentWebsite == $site['domain']) $active = " class=\"active\"";
                                ?>
                                <li<?php echo $active; ?>><a href="?site=<?php echo sanitize_attribute($site['domain']); ?>&id=<?php echo sanitize_attribute($_GET['id']); ?>"><?php echo sanitize_html($site['domain']); ?></a></li>
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
            <form action="" method="get" class="loadable">
                <div class="site-selector">
                    <?php if (!$studio->usePermalinks()) { ?>
                    <input type="hidden" name="id" value="<?php echo sanitize_attribute($_GET['id']); ?>">
                    <?php } ?>
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
    <?php } ?>
<?php } else { ?>
    <section class="title">
        <div class="container">
            <h1><?php echo sanitize_html(rt($tool->name)); ?></h1>
        </div>
    </section>
<?php } ?>

<?php if (($snippet = Ads::commit('header')) !== false) { ?>
    <?php if ($studio->getopt('ad-header-container') === 'On') { ?><div class="container"><?php } ?>
    <?php echo $snippet; ?>
    <?php if ($studio->getopt('ad-header-container') === 'On') { ?></div><?php } ?>
<?php } ?>

<?php

if (file_exists($topFileHtml)) {
    $html = file_get_contents($topFileHtml);
    $allowedPostFlag = stripos($html, 'no-post') === false || empty($_POST);
    $allowedWebsiteFlag = stripos($html, 'no-website') === false || (!$currentWebsite && $tool->requiresWebsite);

    if ($allowedPostFlag && $allowedWebsiteFlag) {
        echo sanitize_trusted($html);
    }
}

if (file_exists($topFilePHP)) {
    require $topFilePHP;
}

$topContent = $toolPage->getTopHTML();
$bottomContent = $toolPage->getBottomHTML();

if (!empty($topContent)) {
?>
<section class="custom-content tool-top">
    <div class="container">
        <div class="content">
            <div class="slots">
                <?php
                    if (strlen($topContent) > 4000 && ($snippet = Ads::commit('300x600')) !== false) {
                        echo $snippet;
                    }
                    else if (($snippet = Ads::commit('300x250')) !== false) {
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

            <?php echo $topContent; ?>
        </div>
    </div>
</section>
<?php
}

if (!$currentWebsite && $tool->requiresWebsite) {
?>
<section class="generic">
    <div class="container">
        <div class="advertising">
            <div class="content">
                <h3><?php echo sanitize_html(rt("Enter a website above to get started.")); ?></h3>
            </div>
            <div class="slots">
                <?php
                    if (empty($topContent) && empty($bottomContent)) {
                        if (($snippet = Ads::commit('468x60')) !== false) {
                            echo $snippet;
                        }
                        else if (($snippet = Ads::commit('300x250')) !== false) {
                            echo $snippet;
                        }
                        else if (($snippet = Ads::commit('250x250')) !== false) {
                            echo $snippet;
                        }
                        else if (($snippet = Ads::commit('200x200')) !== false) {
                            echo $snippet;
                        }
                    }
                ?>
            </div>
        </div>
    </div>
</section>
<?php
    if (file_exists($bottomFileHtml)) {
        $html = file_get_contents($bottomFileHtml);
        $allowedPostFlag = stripos($html, 'no-post') === false || empty($_POST);
        $allowedWebsiteFlag = stripos($html, 'no-website') === false || (!$currentWebsite && $tool->requiresWebsite);

        if ($allowedPostFlag && $allowedWebsiteFlag) {
            echo sanitize_trusted($html);
        }
    }

    if (file_exists($bottomFilePHP)) {
        require $bottomFilePHP;
    }

    if (!empty($bottomContent)) {
    ?>
    <section class="custom-content tool-bottom">
        <div class="container">
            <div class="content">
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

                <?php echo $bottomContent; ?>
            </div>
        </div>
    </section>
    <?php
    }

    $page->footer();
}
else {
?>
<section class="tool">
    <div class="container">
        <div class="advertising">
            <div class="content">
<?php
    if (empty($topContent)) {
        if (($snippet = Ads::commit('728x90', 'center mb-2')) !== false) {
            echo $snippet;
        }
        else if (($snippet = Ads::commit('468x60', 'center mb-2')) !== false) {
            echo $snippet;
        }
    }

    try {
        $url = !!$currentWebsite ? new \SEO\Helper\Url($currentWebsite) : null;
        $plugins->call("tool_head", [$tool, $url]);
        $tool->start($url);
    }
    catch (Exception $e) {
        $plugins->call("tool_error");
        echo sanitize_trusted("<section class=\"generic error-message\">
            <div>
                <img src=\"{$page->getPath()}resources/images/error128.png\" width=\"64px\" />
            </div>
            <p>{$studio->ptext($e->getMessage())}</p>
        </section>");
    }

    $plugins->call("tool_foot", [$tool, $url]);
?>
<?php
    if (empty($bottomContent)) {
        if (($snippet = Ads::commit('728x90', 'center mt-2')) !== false) {
            echo $snippet;
        }
    }
?>
            </div>
            <div class="slots">
                <?php
                    $bottomContent = $toolPage->getBottomHTML();
                    if (empty($bottomContent) && empty($topContent) && ($snippet = Ads::commit('120x600')) !== false) {
                        echo $snippet;
                    }
                ?>
            </div>
        </div>
    </div>
</section>

<?php
    if (file_exists($bottomFileHtml)) echo sanitize_trusted(file_get_contents($bottomFileHtml));
    if (file_exists($bottomFilePHP)) require $bottomFilePHP;

    if (!empty($bottomContent)) {
    ?>
    <section class="custom-content tool-bottom">
        <div class="container">
            <div class="content">
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

                <?php echo $bottomContent; ?>
            </div>
        </div>
    </section>
    <?php
    }

    $page->footer();
}
?>
