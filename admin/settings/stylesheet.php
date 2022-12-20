<?php

use Studio\Util\StyleComposer;

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->header();

$path = __DIR__ . '/../../resources/styles/custom.css';
$code = '';
$custom = '';

if (file_exists($path)) {
	$composer = new StyleComposer($path);
	$code = implode("\n", $composer->getOutsideLines());
	$custom = implode("\n", $composer->getInsideLines());
}

if (isset($_POST['code']) && !DEMO) {
	$newCode = $_POST['code'];
	$code = str_replace("\r\n", "\n", $newCode);
	$lines = explode("\n", $code);

	if (!empty($lines[count($lines) - 1])) $code .= "\n\n";
	else $code .= "\n";

	$code .= $custom;
	file_put_contents($path, $code);

	$studio->setopt('customCssTime', time());
	header("Location: stylesheet.php?success");
	die;
}

?>

<div class="heading">
    <h1>Stylesheet</h1>
    <h2>Edit custom styles</h2>
</div>

<form action="" method="post">
    <div class="panel v2 back">
        <a href="../settings.php">
            <i class="material-icons">&#xE5C4;</i> Back
        </a>
    </div>
    <div class="panel v2">
		<textarea name="code" class="editor"><?php echo $code; ?></textarea>
		<input style="margin-top: 20px;" type="submit" class="btn blue" value="Save">

		<script src="../../resources/scripts/codemirror.css.js"></script>
		<script>
			var editor = CodeMirror.fromTextArea(document.querySelector('.editor'), {
				lineNumbers: true,
				mode: 'css',
				lineSeparator: '\n',
				indentUnit: 4
			});
		</script>
	</div>
</form>

<?php
$page->footer();
?>
