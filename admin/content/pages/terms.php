<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

$basedir = $studio->basedir . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'pages';
$tosFile = $basedir . DIRECTORY_SEPARATOR . 'tos.html';
$tosPublicFile = $studio->basedir . DIRECTORY_SEPARATOR . 'terms.php';

if (!file_exists($basedir)) {
	if (!mkdir($basedir)) {
		echo "<div class='error'>Failed to create necessary directory: $basedir</div>";
	}
}

$tos = file_exists($tosFile) ? file_get_contents($tosFile) : '';
$errors = [];

function checkFile($path) {
	global $errors;

	$dir = dirname($path);

	if (!file_exists($dir)) {
		$dir = dirname($dir);
	}

	if (file_exists($path)) {
		if (!is_readable($path)) {
			$errors[] = "File $path is not readable";
		}
		else if (!is_writable($path)) {
			$errors[] = "File $path is not writable";
		}
	}
	else if (file_exists($dir)) {
		if (!is_readable($dir)) {
			$errors[] = "Directory $dir is not readable";
		}
		else if (!is_writable($dir)) {
			$errors[] = "Directory $dir is not writable";
		}
	}
}

checkFile($tosFile);
checkFile($tosPublicFile);

function saveFile($saveFileName, $publicFileName, $html, $title) {
	global $studio;

	if (empty($html)) {
		if (file_exists($saveFileName)) {
			if (!unlink($saveFileName)) {
				echo "<div class='error'>Failed to delete file: $saveFileName</div>";
				return false;
			}
		}

		if (file_exists($publicFileName)) {
			if (!unlink($publicFileName)) {
				echo "<div class='error'>Failed to delete file: $publicFileName</div>";
				return false;
			}
		}
	}
	else {
		if (!file_put_contents($saveFileName, $html)) {
			echo "<div class='error'>Failed to write file: $saveFileName</div>";
			return false;
		}

		if (!file_exists($publicFileName)) {
			$source = $studio->basedir . '/resources/templates/custom.php';

			if (!file_exists($source)) {
				echo "<div class='error'>Missing internal file: $source</div>";
				return false;
			}

			$fileName = str_replace('"', '\\"', basename($saveFileName));
			$title = str_replace('"', '\\"', basename($title));

			$php = file_get_contents($source);
			$php = str_replace('{{filename}}', $fileName, $php);
			$php = str_replace('{{title}}', $title, $php);

			if (!file_put_contents($publicFileName, $php)) {
				echo "<div class='error'>Failed to write file: $publicFileName</div>";
				return false;
			}
		}
	}

	return true;
}

if (isset($_POST['isPostback']) && file_exists($basedir) && !DEMO) {
	$tosHTML = trim($_POST['tos']);

	$success = saveFile($tosFile, $tosPublicFile, $tosHTML, "Terms of Service");
    $bools = array(
        "signup-legal-affirmation", "contact-legal-affirmation"
    );

    foreach ($bools as $bool) {
        if (!isset($_POST[$bool])) $studio->showFatalError("Missing POST parameter $bool");

        $val = $_POST[$bool];
        if ($val != "On" && $val != "Off") $val = "Off";

        $studio->setopt($bool, $val);
    }

	if ($success) {
		header("Location: terms.php?success=1");
		die;
	}
}

$languageFilePath = $studio->bindir . '/en-us/pages_custom.json';

if (isset($_GET['import']) && !DEMO) {
	$targetTranslations = array(
		'I agree to the {$1}' => 'I agree to the {$1}',
		"Terms of Service" => "Terms of Service",
		"Privacy Policy" => "Privacy Policy",
		"You must agree to our terms of service." => "You must agree to our terms of service.",
		"You must agree to our privacy policy." => "You must agree to our privacy policy.",
		'Copyright &copy; {$1}' => 'Copyright &copy; {$1}'
	);

	file_put_contents($languageFilePath, json_encode($targetTranslations, JSON_PRETTY_PRINT));

	$curLang = new Studio\Base\Language($studio->bindir, "en-us");
	$missing = false;

	foreach (scandir($studio->basedir . '/resources/languages/') as $folder) {
		if ($folder == "." || $folder == "..") continue;

		$lang = new Studio\Base\Language($studio->basedir . '/resources/languages', str_replace("/", "", $folder));
		if (count($lang->translations) < count($curLang->translations)) $missing = true;
	}

	if ($missing) {
		$studio->setopt("update-missing-translations", "1");
	}

	header("Location: terms.php");
}

$needsImport = !file_exists($languageFilePath);
?>

<script src="../../../resources/ckeditor/ckeditor.js"></script>

<section>
	<h1>
		<a class="back" href="index.php">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
				<path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
			</svg>
		</a>
		Edit Terms of Service
	</h1>

	<form action="" method="post">
		<input type="hidden" name="isPostback" value="1" />

		<div class="save-container">
			<div class="saveable">
				<div class="panel v2" style="padding: 0;">
					<?php if ($needsImport) { ?>
					<div class="import-translations" style="margin-top: 0;">
						<h3>Localization available</h3>
						<p>
							Enabling these pages will add new text to the website, such as page titles, form checkbox
							labels, and error messages. Would you like to import these texts into your languages so you
							can edit and translate them?
						</p>

						<a class="btn" href="?import" target="_blank">Import</a>
					</div>
					<?php } ?>

					<?php if (!empty($errors)) { ?>
					<div class="error-box" style="margin-top: 0;">
						<h3>Issues detected</h3>
						<p>
							We identified the following file permission issues which will prevent this feature from
							working. Please use a file manager or FTP to change the <code>chmod</code> or ownership of the
							below paths.
						</p>

						<ul>
							<?php foreach ($errors as $error) { ?>
							<li><?php echo $error; ?></li>
							<?php } ?>
						</ul>
					</div>
					<?php } ?>

					<textarea class="ckeditor" id="tos" name="tos"><?php echo $tos; ?></textarea>

					<h2 style="margin-top: 40px; margin-bottom: 0;">Options</h2>

					<div class="setting-group">
						<div class="setting toggle">
							<label data-switch="Ctl_Switch_01">Require agreement on signup page <span class="help tooltip" title="The user registration page will require users to affirm their agreement to these documents."><i class="material-icons">&#xE8FD;</i></span></label>

							<div class="switch" id="Ctl_Switch_01">
								<input type="hidden" name="signup-legal-affirmation" value="<?php echo $studio->getopt("signup-legal-affirmation", 'Off'); ?>">
								<div class="handle"></div>
							</div>
						</div>
						<div class="setting toggle">
							<label data-switch="Ctl_Switch_02">Require agreement on contact page <span class="help tooltip" title="The contact page will require users to affirm their agreement to these documents before sending their message."><i class="material-icons">&#xE8FD;</i></span></label>

							<div class="switch" id="Ctl_Switch_02">
								<input type="hidden" name="contact-legal-affirmation" value="<?php echo $studio->getopt("contact-legal-affirmation", 'Off'); ?>">
								<div class="handle"></div>
							</div>
						</div>
					</div>
				</div>
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
</section>

<?php
$page->footer();
?>
