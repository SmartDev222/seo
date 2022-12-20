<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(3)->setTitle("Settings")->header();
?>

<div class="heading">
    <h1>Settings</h1>
    <h2>Customize your studio</h2>
</div>

<div class="panel v2">
    <div class="menu v2">
        <ul>
            <li>
                <a href="settings/general.php">
                    <i class="material-icons">&#xE873;</i>
                    <strong>Configuration</strong>
                    <p>General application settings such as errors and updates.</p>
                </a>
            </li>
            <li>
                <a href="settings/accounts.php">
                    <i class="material-icons">&#xE7FD;</i>
                    <strong>Accounts</strong>
                    <p>Basic settings for your website's users and public portal.</p>
                </a>
            </li>
            <li>
                <a href="settings/custom-html.php">
                    <i class="material-icons">&#xE86F;</i>
                    <strong>Custom HTML</strong>
                    <p>Add custom HTML code to your site's head and body tags.</p>
                </a>
            </li>
            <li>
                <a href="settings/cache.php">
                    <i class="material-icons">&#xE1C2;</i>
                    <strong>Cache</strong>
                    <p>Manage how the application caches tool results.</p>
                </a>
            </li>
            <li>
                <a href="settings/mail.php">
                    <i class="material-icons">&#xE0BE;</i>
                    <strong>Mail</strong>
                    <p>Configure how the system should send outgoing mail.</p>
                </a>
            </li>
            <li>
                <a href="settings/email-templates.php">
                    <i class="material-icons">drafts</i>
                    <strong>Email templates</strong>
                    <p>Configure the content of outgoing system mail.</p>
                </a>
            </li>
            <li>
                <a href="settings/stylesheet.php">
                    <i class="material-icons">gradient</i>
                    <strong>Stylesheet</strong>
                    <p>Edit your custom stylesheet to override styles.</p>
                </a>
            </li>
            <li>
                <a href="settings/extensions.php">
                    <i class="material-icons">&#xE87B;</i>
                    <strong>Extensions</strong>
                    <p>Manage or install new extensions in the application.</p>
                </a>
            </li>
            <li>
                <a href="settings/permalinks.php">
                    <i class="material-icons">&#xE87B;</i>
                    <strong>Permalinks</strong>
                    <p>Enable and customize fancy permalinks for all pages.</p>
                </a>
            </li>
        </ul>
    </div>
</div>


<?php
$page->footer();
?>
