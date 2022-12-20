<?php

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Email templates")->header();

$templates = array();

$q = $studio->sql->query('SELECT `name`, `subject`, `message` FROM `mail_templates` ORDER BY `name` ASC');

while ($row = $q->fetch_object()) {
	// Build a fancy representation of the name
	$nameParts = explode(':', $row->name);
	$prettyName = sprintf('%s: %s', ucfirst($nameParts[0]), str_replace('_', ' ', ucfirst($nameParts[1])));

	// Save extra data for the row
	$row->id = md5($row->name);
	$row->prettyName = $prettyName;

	// Append
	$templates[] = $row;
}

if (isset($_POST['doPostback']) && !DEMO) {
	foreach ($templates as $template) {
		$subjectInputName = $template->id . '1';
		$messageInputName = $template->id . '2';

		// Skip if either input is missing
		if (!isset($_POST[$subjectInputName])) continue;
		if (!isset($_POST[$messageInputName])) continue;

		$subject = $_POST[$subjectInputName];
		$message = $_POST[$messageInputName];

		// Skip if the content hasn't changed
		if ($subject === $template->subject && $message === $template->message) continue;

		// Apply the changes
		$p = $studio->sql->prepare('UPDATE `mail_templates` SET `subject` = ?, `message` = ? WHERE `name` = ?');
		$p->bind_param('sss', $subject, $message, $template->name);
		$p->execute();
		$p->close();
	}

    header("Location: email-templates.php?success=1");
    die;
}

?>

<div class="heading">
    <h1>Email templates</h1>
    <h2>Change the text in system emails</h2>
</div>

<div class="panel v2 back">
    <a href="../settings.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<div class="panel v2 selector">
	<div class="wrapper">
		<label for="mailTemplateDropDown">Select email template to edit</label>

		<select id="mailTemplateDropDown">
			<option value="" disabled selected>Select one...</option>

			<?php foreach ($templates as $template) { ?>
				<option value="<?php echo $template->id; ?>">
					<?php echo $template->prettyName; ?>
				</option>
			<?php } ?>
		</select>
	</div>
</div>

<form action="" method="post">
	<input type="hidden" name="doPostback" value="1">

    <div class="save-container">
        <div class="saveable">
			<div class="panel v2 fill-block">
				<p>To edit a mail template, select it from the dropdown above.</p>
			</div>

			<?php foreach ($templates as $template) { ?>
				<div class="panel v2 template hidden" id="<?php echo $template->id; ?>">
					<div class="setting-group">
						<div class="setting textarea">
							<label for="$ctlInput<?php echo $template->id; ?>1">
								Subject
							</label>
							<div class="text">
								<input id="$ctlInput<?php echo $template->id; ?>1" type="text" name="<?php echo $template->id; ?>1" value="<?php echo sanitize_attribute($template->subject); ?>" placeholder="Enter subject...">
							</div>
						</div>

						<div class="setting textarea">
							<label for="$ctlInput<?php echo $template->id; ?>2">
								Message
							</label>
							<div class="text">
								<textarea id="$ctlInput<?php echo $template->id; ?>2" name="<?php echo $template->id; ?>2" rows="10" placeholder="Enter message..."><?php
									echo $template->message;
								?></textarea>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
        </div>

        <div class="save">
            <div class="save-box">
                <button type="submit">
                    <span>Save changes</span>
                    <span>Saved</span>
                    <img src="../../resources/images/load32.gif" width="16px" height="16px">
                </button>
            </div>
        </div>
    </div>
</form>

<script type="text/javascript">
	var dropdown = $('#mailTemplateDropDown');
	var templatePanels = $('.panel.template');
	var fillPanel = $('.fill-block');

	function updateTemplateSelection() {
		var targetId = dropdown.val();
		var targetPanel = $('#' + targetId);

		templatePanels.addClass('hidden');
		targetPanel.removeClass('hidden');

		if (targetPanel.length > 0) {
			fillPanel.hide();
		}
		else {
			fillPanel.show();
		}
	}

	dropdown.on('change', updateTemplateSelection);
	updateTemplateSelection();
</script>

<?php
$page->footer();
?>
