<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(16)->setTitle("Help")->header();
?>

<div class="heading">
    <h1>Help</h1>
    <h2>Support & diagnostics</h2>
</div>

<div class="panel v2">
    <div class="menu v2">
        <ul>
            <li>
                <a href="../content/documents/documentation.html" target="_blank">
                    <i class="material-icons">&#xE24D;</i>
                    <strong>Documentation</strong>
                    <p>View product documentation for usage and other general info.</p>
                </a>
            </li>
            <li>
                <a href="diagnostics.php">
                    <i class="material-icons">&#xE868;</i>
                    <strong>Diagnostics</strong>
                    <p>Run automatic tests to find issues and errors in your app.</p>
                </a>
            </li>
            <li>
                <a href="https://codecanyon.net/item/seo-studio-professional-tools-for-seo/17022701/support" target="_blank">
                    <i class="material-icons">&#xE887;</i>
                    <strong>Contact support</strong>
                    <p>Send an email to customer support. We're here for you!</p>
                </a>
            </li>
            <li>
                <a href="send-feedback.php">
                    <i class="material-icons">&#xE87F;</i>
                    <strong>Request a feature</strong>
                    <p>Have a brilliant new idea? Send it to us using this feedback form.</p>
                </a>
            </li>
        </ul>
    </div>
</div>


<?php
$page->footer();
?>
