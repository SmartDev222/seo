<?php

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Permalinks")->header();

$action = 'optin';
$enabled = $studio->getopt('permalinks.enabled') === 'On';

if (isset($_POST['@action'])) {
	$action = $_POST['@action'];
}

$_DEFAULTS = array(
	'tools.php' => 'tools',
	'tool.php' => 'tools/$id',
	'account/index.php' => 'account',
	'account/login.php' => 'account/login',
	'account/register.php' => 'account/register',
	'account/password-reset.php' => 'account/password-reset',
	'account/confirm.php' => 'account/confirm',
	'account/websites.php' => 'account/websites',
	'account/settings.php' => 'account/settings',
	'account/signout.php' => 'account/signout',
	'contact.php' => 'contact',
	'embed.php' => 'embed',
	'terms.php' => 'terms',
	'privacy.php' => 'privacy',
	'pricing.php' => 'pricing'
);

if (!DEMO) {
	if ($action === 'check') {
		$htaccess = $studio->basedir . '/admin/includes/.htaccess';
		$htaccess = str_replace('/', DIRECTORY_SEPARATOR, $htaccess);
		$dir = dirname($htaccess);

		$echo = $studio->basedir . '/admin/includes/test.php';
		$echo = str_replace('/', DIRECTORY_SEPARATOR, $echo);

		if (file_exists($htaccess) && !is_writeable($htaccess)) {
			die("<strong>Error:</strong> The test file is not writeable: $htaccess");
		}

		if (!is_writeable($dir)) {
			die("<strong>Error:</strong> The test directory is not writeable: $dir");
		}

		$htContent  = '<ifModule mod_rewrite.c>' . PHP_EOL;
		$htContent .= '	RewriteEngine On' . PHP_EOL;
		$htContent .= '	RewriteRule ^test-rewrite$ test.php [NC,L,QSA]' . PHP_EOL;
		$htContent .= '</ifModule>' . PHP_EOL;

		$echoContent  = '<?php' . PHP_EOL;
		$echoContent .= 'echo "working";' . PHP_EOL;

		if (file_put_contents($htaccess, $htContent) === false) {
			die("<strong>Error:</strong> Error writing to: $htaccess");
		}

		if (file_put_contents($echo, $echoContent) === false) {
			die("<strong>Error:</strong> Error writing to: $echo");
		}
	}

	if ($action === 'compatible') {
		$htaccess = $studio->basedir . '/admin/includes/.htaccess';
		$htaccess = str_replace('/', DIRECTORY_SEPARATOR, $htaccess);

		$echo = $studio->basedir . '/admin/includes/test.php';
		$echo = str_replace('/', DIRECTORY_SEPARATOR, $echo);

		if (file_exists($htaccess)) {
			@unlink($htaccess);
		}

		if (file_exists($echo)) {
			@unlink($echo);
		}
	}

	if ($action === 'enable') {
		$studio->setopt('permalinks.enabled', 'On');

		// Create default permalinks
		foreach ($_DEFAULTS as $page => $perma) {
			$studio->permalinks->setDefaultLink($page, $perma);
		}

		// Save changes
		try {
			$studio->permalinks->save();
			$studio->permalinks->write();
		}
		catch (Exception $e) {
			die("
				<strong>Failed:</strong> The script was not able to automatically write changes to the
				<code>.htaccess</code> file in the script's root directory. Please ensure that the file has correct
				permissions and try again.
			");
		}

		header("Location: permalinks.php");
		die;
	}
}

if ($enabled && isset($_POST['doPostback']) && !DEMO) {
	$bools = array(
        "permalinks.enabled",
        "permalinks.redirect",
        "permalinks.permanent"
    );

    foreach ($bools as $bool) {
        if (!isset($_POST[str_replace('.', '_', $bool)])) $studio->showFatalError("Missing POST parameter $bool");

        $val = $_POST[str_replace('.', '_', $bool)];
        if ($val != "On" && $val != "Off") $val = "Off";

        $studio->setopt($bool, $val);
    }

	$pages = array(
		'tools.php',
		'tool.php',
		'terms.php',
		'privacy.php',
		'account/index.php',
		'account/login.php',
		'account/register.php',
		'account/password-reset.php',
		'account/confirm.php',
		'account/websites.php',
		'account/settings.php',
		'account/signout.php',
		'embed.php',
		'contact.php',
		'pricing.php'
	);

	foreach ($pages as $pageName) {
		$postName = str_replace('.', '_', $pageName);

		if (!isset($_POST['permalink:' . $postName])) {
			die("Missing permalink input: $postName!");
		}

		$value = ltrim(trim($_POST['permalink:' . $postName]), '/');

		if ($value === '') {
			$value = $_DEFAULTS[$pageName];
		}

		$studio->permalinks->setLink($pageName, $value);
	}

	foreach ($studio->getTools() as $tool) {
		$pageName = 'tool.php?id=' . $tool->id;
		$postName = str_replace('.', '_', $pageName);

		if (!isset($_POST['permalink:' . $postName])) {
			die("Missing permalink input: $postName!");
		}

		$value = ltrim(trim($_POST['permalink:' . $postName]), '/');

		if ($value === '') {
			$studio->permalinks->removeLink($pageName);
		}
		else {
			$studio->permalinks->setLink($pageName, $value);
		}
	}

	// Save changes
	try {
		$studio->permalinks->save();

		if ($studio->getopt('permalinks.enabled') === 'On') {
			$studio->permalinks->write();
		}
		else {
			$studio->permalinks->unwrite();
		}
	}
	catch (Exception $e) {
		die("
			<strong>Failed:</strong> The script was not able to automatically write changes to the
			<code>.htaccess</code> file in the script's root directory. Please ensure that the file has correct
			permissions and try again.
		");
	}

	header("Location: permalinks.php?success=1");
	die;
}

?>

<div class="heading">
    <h1>Permalinks</h1>
    <h2>Configure permalinks</h2>
</div>

<div class="panel v2 back">
    <a href="../settings.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<?php if (!$enabled) { ?>
<div class="content-wrapper">
	<div class="permalinks-overview">
		<?php if ($action === 'optin') { ?>

			<h2>Enable custom permalinks</h2>
			<p>
				Permalinks can make your website appear more professional. They can also help organize your website and tools
				for search optimization.
			</p>

			<form action="" method="post">
				<input type="hidden" name="@action" value="check" />
				<input type="submit" class="med blue btn" value="Check eligibility" />
			</form>

		<?php } elseif ($action === 'check') { ?>

			<svg class="generic-spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>
			<h2>Checking your server</h2>
			<p>
				Please wait while we perform some compatibility tests with your browser.
			</p>

			<form id="f1" action="" method="post">
				<input type="hidden" name="@action" value="" />
			</form>

			<script type="text/javascript">
				var form = $('#f1');
				var action = form.find('input').first();

				$(function() { setTimeout(function() {
					$.get('../includes/test-rewrite', function(data, status, xhr) {
						if (data.trim() === 'working') {
							action.val('compatible');
							form.submit();
							console.log('All good');
						}
						else {
							console.error('The mod_rewrite test script returned a non-erroneous but unexpected response:');
							console.error(data);

							alert('Received an unexpected response! Please check the development console for more details (Ctrl+Shift+J).');
						}
					}).fail(function(xhr) {
						switch (xhr.status) {
							case 404: alert('Your server is not compatible at this time because your web server does not support automatic rewrite rules.'); break;
							case 500: alert('Your server is not compatible due to an unknown configuration error.'); break;
							default: alert('Your server is not compatible (error ' + xhr.status + ')');
						}

						window.location = 'permalinks.php';
					});
				}, 1500) });
			</script>

		<?php } elseif ($action === 'compatible') { ?>

			<h2>Your server is compatible!</h2>
			<p>
				Click the button below to enable permalinks. The old links for your pages will be redirected to their
				new locations. You will have a chance to customize them later.
			</p>

			<!-- <p class="warn">
				<span class="material-icons">warning</span>

				Enabling this feature for an existing website can negatively affect its
				existing search optimization. Please make sure you know what you're doing.
			</p> -->

			<form action="" method="post">
				<input type="hidden" name="@action" value="enable" />
				<input type="submit" class="med green btn" value="Enable permalinks" />
			</form>

		<?php } ?>
	</div>
</div>
<?php } else { ?>

	<form action="" method="post">
	<input type="hidden" name="doPostback" value="1">

    <div class="save-container">
        <div class="saveable">
            <div class="panel v2">
				<p class="permalink-info">
					Permalinks are now enabled. You can use this page to customize your permalinks, or you can leave
					them blank to use their default values. We'll automatically redirect old links to their new
					permalinks for you.
				</p>
				<p class="permalink-info bottom">
					<strong>Warning:</strong> This feature is experimental and writes the permalinks directly to your
					<code>.htaccess</code> file. If something goes wrong, it could break your website until the file
					is fixed or reverted. Proceed with caution!
				</p>

                <div class="setting-group">
                    <h3>Options</h3>

                    <div class="setting toggle">
                        <label data-switch="ctlSwitch1">
                            Enable permalinks
                        </label>

                        <div class="switch" id="ctlSwitch1">
                            <input type="hidden" name="permalinks.enabled" value="<?php echo $studio->getopt("permalinks.enabled", 'On'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                    <div class="setting toggle">
                        <label data-switch="ctlSwitch2">
                            Redirect old links
                            <span class="help tooltip" title="Controls whether old URLs will be automatically redirected to their new permalinks."><i class="material-icons">&#xE8FD;</i>
                        </label>

                        <div class="switch" id="ctlSwitch2">
                            <input type="hidden" name="permalinks.redirect" value="<?php echo $studio->getopt("permalinks.redirect", 'On'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
                    <div class="setting toggle">
                        <label data-switch="ctlSwitch3">
                            Use permanent redirection
                            <span class="help tooltip" title="When enabled, old links will be redirected to their new permalinks with a permanent status code (301). This will help search engines index the new links as soon as possible."><i class="material-icons">&#xE8FD;</i>
                        </label>

                        <div class="switch" id="ctlSwitch3">
                            <input type="hidden" name="permalinks.permanent" value="<?php echo $studio->getopt("permalinks.permanent", 'On'); ?>">
                            <div class="handle"></div>
                        </div>
                    </div>
				</div>

                <div class="setting-group">
                    <h3>Standard pages</h3>

                    <div class="setting text">
                        <label for="$ctlInput1">
                            Tools
                        </label>
                        <div class="text">
                            <input id="$ctlInput1" type="text" name="permalink:tools.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('tools.php')->getPermalink()); ?>" placeholder="tools">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput2">
                            Individual tool
                            <span class="help tooltip" title="This permalink accepts a variable ($id) to represent the ID of the tool being used."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput2" type="text" name="permalink:tool.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('tool.php')->getPermalink()); ?>" placeholder="tools/$id">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput12">
                            Terms of Service
                        </label>
                        <div class="text">
                            <input id="$ctlInput12" type="text" name="permalink:terms.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('terms.php')->getPermalink()); ?>" placeholder="terms">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput13">
                            Privacy Policy
                        </label>
                        <div class="text">
                            <input id="$ctlInput13" type="text" name="permalink:privacy.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('privacy.php')->getPermalink()); ?>" placeholder="privacy">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput3">
                            Account &raquo; Home
                        </label>
                        <div class="text">
                            <input id="$ctlInput3" type="text" name="permalink:account/index.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/index.php')->getPermalink()); ?>" placeholder="account">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput4">
                            Account &raquo; Login
                        </label>
                        <div class="text">
                            <input id="$ctlInput4" type="text" name="permalink:account/login.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/login.php')->getPermalink()); ?>" placeholder="account/login">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput5">
                            Account &raquo; Register
                        </label>
                        <div class="text">
                            <input id="$ctlInput5" type="text" name="permalink:account/register.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/register.php')->getPermalink()); ?>" placeholder="account/register">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput6">
                            Account &raquo; Password Reset
                        </label>
                        <div class="text">
                            <input id="$ctlInput6" type="text" name="permalink:account/password-reset.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/password-reset.php')->getPermalink()); ?>" placeholder="account/password-reset">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput7">
                            Account &raquo; Confirm
                        </label>
                        <div class="text">
                            <input id="$ctlInput7" type="text" name="permalink:account/confirm.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/confirm.php')->getPermalink()); ?>" placeholder="account/confirm">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput8">
                            Account &raquo; Websites
                        </label>
                        <div class="text">
                            <input id="$ctlInput8" type="text" name="permalink:account/websites.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/websites.php')->getPermalink()); ?>" placeholder="account/websites">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput9">
                            Account &raquo; Settings
                        </label>
                        <div class="text">
                            <input id="$ctlInput9" type="text" name="permalink:account/settings.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/settings.php')->getPermalink()); ?>" placeholder="account/settings">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput10">
                            Account &raquo; Sign out
                        </label>
                        <div class="text">
                            <input id="$ctlInput10" type="text" name="permalink:account/signout.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('account/signout.php')->getPermalink()); ?>" placeholder="account/signout">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput11">
                            Embedded tool
                            <span class="help tooltip" title="This page is used to embed tools onto other websites. It generally will not be visible to visitors."><i class="material-icons">&#xE8FD;</i>
                        </label>
                        <div class="text">
                            <input id="$ctlInput11" type="text" name="permalink:embed.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('embed.php')->getPermalink()); ?>" placeholder="embed">
                        </div>
                    </div>
				</div>

                <div class="setting-group">
                    <h3>
						Extension pages
						<p>These are only applicable if you have enabled their extensions.</p>
					</h3>

                    <div class="setting text">
                        <label for="$ctlInput20">
                            Contact
                        </label>
                        <div class="text">
                            <input id="$ctlInput20" type="text" name="permalink:contact.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('contact.php')->getPermalink()); ?>" placeholder="contact">
                        </div>
                    </div>
                    <div class="setting text">
                        <label for="$ctlInput21">
                            Pricing plans
                        </label>
                        <div class="text">
                            <input id="$ctlInput21" type="text" name="permalink:pricing.php" value="<?php echo sanitize_attribute($studio->permalinks->getLink('pricing.php')->getPermalink()); ?>" placeholder="pricing">
                        </div>
                    </div>
				</div>

                <div class="setting-group individual-tools">
                    <h3>
						Individual tools
						<p>Leave these blank to use the individual tool permalink above.</p>
					</h3>

					<?php
						$tools = $studio->getTools();
						$index = 30;

						foreach ($tools as $tool) {
							$link = $studio->permalinks->getLink("tool.php?id={$tool->id}");
							$value = $link !== null ? $link->getPermalink() : '';
							$default = $studio->permalinks->getLink('tool.php')->getUri(array(
								'id' => $tool->id
							));
					?>

                    <div class="setting text">
                        <label for="$ctlInput<?php echo $index; ?>">
                            Tools &raquo; <?php echo $tool->name; ?>
                        </label>
                        <div class="text">
                            <input id="$ctlInput<?php echo $index; ?>" data-id="<?php echo $tool->id; ?>" type="text" name="permalink:tool.php?id=<?php echo $tool->id; ?>" value="<?php echo sanitize_attribute($value); ?>" placeholder="<?php echo sanitize_attribute($default); ?>">
                        </div>
                    </div>

					<?php
							$index++;
						}
					?>
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

	<script type="text/javascript">
		var toolInput = $('input[name="permalink:tool.php"]');
		var individualTools = $('.individual-tools');
		var individualToolInputs = individualTools.find('input[type=text]');

		toolInput.on('change', function() {
			var value = toolInput.val();

			if (value.indexOf('$id') < 0 || value.trim().length == 0) {
				value = '/tools/$id';
			}

			individualToolInputs.each(function(index, element) {
				var input = individualToolInputs.filter(element);
				input.attr('placeholder', value.replace('$id', input.data('id')));
			});
		});
	</script>

<?php } ?>

<?php
$page->footer();
?>
