<?php

use SEO\Common\SEOException;

if (isset($_GET['cookies']) && $_GET['cookies'] === "0") {
    define('DISABLE_COOKIES', true);
}

define('STUDIO_EMBEDDED', true);

require "includes/init.php";

if ($studio->getopt('embedding-enabled') !== 'On') {
    die("We do not allow our tools to be embedded, apologies!");
}

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$allowedDomains = [];
$allowedDomainsInput = trim($studio->getopt('embedding-domains', ''));

if (!empty($allowedDomainsInput)) {
    $lines = preg_split("/(?:\r?\n)+/", $allowedDomainsInput);

    $currentDomain = '';
    $parsed = @parse_url($referer);

    if ($parsed && isset($parsed['host'])) {
        $currentDomain = $parsed['host'];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        $line = preg_quote($line, '/');
        $line = str_replace('\\*', '.+', $line);
        $line = '/^' . $line . '$/';
        $allowedDomains[] = $line;
    }

    if (!empty($allowedDomains)) {
        $match = false;

        $publicLink = $studio->getopt('public-url');
        $publicParsed = @parse_url($publicLink);

        if ($publicParsed && isset($publicParsed['host'])) {
            $allowedDomains[] = '/^' . preg_quote($publicParsed['host']) . '$/';
        }

        foreach ($allowedDomains as $exp) {
            if (preg_match($exp, $currentDomain)) {
                $match = true;
                break;
            }
        }

        if (!$match) {
            die("We do not allow our tools to be embedded on this website, apologies!");
        }
    }
}

$requireSavedProjects = $studio->getopt('project-mode') === 'On';
$currentWebsite = null;
$noCookies =
    isset($_GET['cookies']) ? $_GET['cookies'] === "0" :
    $studio->getopt('no-cookie-siteselector-embed', $studio->getopt('no-cookie-siteselector')) === 'On';

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
elseif (isset($_GET['site']) && !empty($_GET['site'])) {
    $site = $_GET['site'];

    try {
        $url = new \SEO\Helper\Url($site);
        $currentWebsite = $url->domain;

        // Store the current website in cookies
        if (!$noCookies) {
            $account->setCurrentWebsite($currentWebsite);
        }
    }
    catch (Exception $e) {
        $badInput = true;
    }
}

if (is_null($currentWebsite) && !$noCookies) {
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

if ($tool == null) {
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

$page->setTitle(rt($tool->name))->setPage(2.5)->embedHeader();

$showToolHeader = !isset($_GET['h']) || $_GET['h'] == "1";
$showSiteInput = !isset($_GET['si']) || $_GET['si'] == "1";

if (isset($_GET['site']) && !empty($_GET['site']) && !$noCookies) {
    try {
        $account->setCurrentWebsite(($_GET['site']));
    }
    catch (SEOException $e) {
        echo "Invalid site.";
        die;
    }
}
?>

<?php if ($showToolHeader) { ?>
    <section class="title tool-title embedded <?php echo (!$showSiteInput) ? 'no-input' : '' ?>">
        <div class="container">
            <div class="flex">
                <div class="icon">
                    <img src="resources/icons/<?php echo sanitize_attribute($tool->icon); ?>.png">
                </div>
                <div class="name">
                    <h1><?php echo sanitize_html(rt($tool->name)); ?></h1>
                </div>
            </div>
        </div>
    </section>
<?php } ?>

<?php if ($tool->requiresWebsite && $showSiteInput) { ?>
<section class="website alt embedded <?php echo (!$showToolHeader) ? 'no-header' : '' ?>">
    <div class="container">
        <form action="" method="get" class="loadable">
            <input type="hidden" name="id" value="<?php echo sanitize_attribute($_GET['id']); ?>">

            <?php if (isset($_GET['h'])) { ?>
            <input type="hidden" name="h" value="<?php echo sanitize_attribute($_GET['h']); ?>">
            <?php } ?>

            <?php if (isset($_GET['si'])) { ?>
            <input type="hidden" name="si" value="<?php echo sanitize_attribute($_GET['si']); ?>">
            <?php } ?>

            <?php if (isset($_GET['r'])) { ?>
            <input type="hidden" name="r" value="<?php echo sanitize_attribute($_GET['r']); ?>">
            <?php } ?>

            <input type="text" class="text-input" name="site" placeholder="<?php pt("Click here to enter a website..."); ?>" value="<?php
                if ($currentWebsite != null) echo sanitize_html($currentWebsite);
            ?>"/>
            <div class="apply">
                <button type="submit" class="loadable"><?php pt("Submit"); ?></button>
            </div>
        </form>
    </div>
</section>
<?php } ?>

<section class="embed <?php echo (!$showSiteInput && !$showToolHeader) ? 'no-header' : ''; ?>">
<?php

if (!$currentWebsite && $tool->requiresWebsite) {
?>
<section class="generic">
    <div class="container">
        <h3><?php echo sanitize_html(rt("Enter a website above to get started.")); ?></h3>
    </div>
</section>
<?php
    $page->embedFooter();
}
else {
?>
<section class="tool">
    <div class="container">
<?php
    try {
        $url = !!$currentWebsite ? new \SEO\Helper\Url($currentWebsite) : null;
        $plugins->call("tool_head", [$tool, $url]);
        $tool->start($url);
    }
    catch (Exception $e) {
        $plugins->call("tool_error");
        $message = sanitize_html($e->getMessage());

        echo sanitize_trusted("<section class=\"generic error-message\">
            <div class=\"container\">
                <div>
                    <img src=\"{$page->getPath()}resources/images/error128.png\" width=\"64px\" />
                </div>
                <p>{$message}</p>
            </div>
        </section>");

        $page->embedFooter();
        die;
    }

    $plugins->call("tool_foot", [$tool, $url]);
    $page->embedFooter();
?>
</div>
</section>
<?php
}
?>
</section>
