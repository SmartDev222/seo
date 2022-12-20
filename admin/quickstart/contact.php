<?php

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(0x3000)->header();

$extensionName = 'Easy Contact Page';
$resolverName = 'quickstart-resolve-contact';

// Find the backlinks plugin id
$plugin = null;
$q = $studio->sql->query('SELECT * FROM `plugins` WHERE `name` = "' . $extensionName . '";');

if ($q->num_rows > 0) {
	$plugin = $q->fetch_object();
}

if (is_null($plugin)) {
	echo "Could not find the $extensionName extension installed!";
	die;
}

// Handle post

$baseDir = dirname(dirname(dirname(__FILE__)));
$pluginsDir = $baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins";
$id = $plugin->id;

if (isset($_GET['enable']) && !DEMO) {
	$enable = trim($_GET['enable']) == '1';

	$studio->setopt($resolverName, 'On');

	// First always enable the plugin, even if we intend to disable it
	// This will allow cleanup if there are leftover files
	if (!$plugin->enabled) {
		$dir = $plugin->directory;

		require_once "$pluginsDir/$dir/$dir.php";

		$o = new $dir;
		$o->baseDir = dirname(dirname(dirname(__FILE__)));
		$o->pluginDir = $o->baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . $dir;

		$studio->sql->query("UPDATE plugins SET `enabled` = 1 WHERE id = $id");
		$res = $o->onEnable();
		unset($o);

		if (!$res) {
			$studio->sql->query("UPDATE plugins SET `enabled` = 0 WHERE id = $id");
			echo "<div class='error'>The plugin failed to enable</div>";
		}
	}

	// Disable the plugin if requested
	if (!$enable) {
		$dir = $plugin->directory;

		require_once "$pluginsDir/$dir/$dir.php";
		$o = new $dir;
		$o->baseDir = dirname(dirname(dirname(__FILE__)));
		$o->pluginDir = $o->baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . $dir;

		$b = $o->onDisable();
		unset($o);

		if ($b !== false) {
			$studio->sql->query("UPDATE plugins SET `enabled` = 0 WHERE id = $id");
			header("Location: ../settings/extensions.php?disabled=1");
			die;
		}
	}

	// Redirect to plugin options
	if ($enable) {
		header("Location: ../plugin-options.php?id=" . $id);
		die;
	}
}

?>

<div class="header-v2 purple">
    <h1>Quick start</h1>
    <p>We'll help you get started quickly</p>
</div>

<div class="quickstart-prompt">
	<h2>Contact extension</h2>
	<p>
		Do you want to enable the contact page extension? This will add a contact page to the script.
		After enabling, you will be taken to the extension's settings page where you should configure it.
	</p>

	<div class="actions">
		<a href="?enable=1" class="btn blue">Enable</a>
		<a href="?enable=0" class="btn">No thanks</a>
	</div>
</div>

<?php
$page->footer();
?>
