<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(16)->setTitle("Send Feedback")->header();

if (isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (strlen($message) == 0) {
        die('Enter a message. <a href="">OK</a>');
    }

    try {
        $api->sendFeedback($message);

?>

    <div class="heading">
        <h1>Send feedback</h1>
        <h2>Suggest new features and improvements</h2>

        <p>
            Use this form to send us general feedback and feature requests. We'll consider your feedback when working on
            future updates. We cannot reply to you from here, so if you need support then please
            <a href="support.php">contact us directly</a>.
        </p>
    </div>

    <div class="panel v2 back">
        <a href="support.php">
            <i class="material-icons">&#xE5C4;</i> Back
        </a>
    </div>

    <div class="panel v2">
        <p style="max-width: 700px; margin: 0 0 25px;">
            We've received your message. We won't be able to respond, but we'll consider it for future updates. By the way, did you know you can get email notifications when new updates are out?
        </p>
        <a class="btn blue" target="_blank" href="http://eepurl.com/c-Aqq1">Email me updates</a>
    </div>


<?php
    }
    catch (Exception $e) {
        echo "<div class='error'>{$e->getMessage()}</div>";
    }
}
else {
    ?>


    <div class="heading">
        <h1>Send feedback</h1>
        <h2>Suggest new features and improvements</h2>

        <p>
            Use this form to send us general feedback and feature requests. We'll consider your feedback when working on
            future updates. We cannot reply to you from here, so if you need support then please
            <a href="support.php">contact us directly</a>.
        </p>
    </div>

    <div class="panel v2 back">
        <a href="support.php">
            <i class="material-icons">&#xE5C4;</i> Back
        </a>
    </div>

    <div class="panel v2">
        <div class="feedback">
            <form action="" method="post">
                <p>Enter your comments.</p>
                <textarea name="message"></textarea>
                <input type="submit" value="Send">
            </form>
        </div>
    </div>


    <?php
}
$page->footer();
?>
