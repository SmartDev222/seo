<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

$data = unserialize($studio->getopt("tools"));
$tools = array();

foreach ($data as $category => $ids) {
    foreach ($ids as $id) {
		$tool = $studio->getToolById($id);
		if (!$tool) continue;
		$tools[$id] = $tool->name;
    }
}

$https = stripos($studio->getopt('public-url'), 'https://') === 0;
$enabled = $studio->getopt('embedding-enabled') === 'On';
$code = null;

if (isset($_POST['tool'])) {
	$tool = $_POST['tool'];

	if (preg_match('/[^a-z0-9_\-\.]/', $tool)) {
		die;
	}

	$url = $studio->getopt('public-url');
	$toolPath = "embed.php?id=$tool";
	$options = "";
	$optionsArr = array();
	$resize = isset($_POST['opt3']);
	$uniqueId = substr(md5(rand() . time()), 0, 6);
	$scroll = "";

	if ($studio->usePermalinks()) {
		$toolPath = $studio->permalinks->getLink('embed.php')->getUri([
			'id' => $tool
		]);
	}

	if (!isset($_POST['opt1'])) $optionsArr[] = 'h=0';
	if (!isset($_POST['opt2'])) $optionsArr[] = 'si=0';
	if (!isset($_POST['opt3'])) $optionsArr[] = 'r=0'; else {
		$optionsArr[] = 'r=' . $uniqueId;
		$scroll = "scrolling=\"no\" ";
	}
	if (isset($_POST['opt4'])) $optionsArr[] = 'cookies=0';
	if (isset($_POST['opt5'])) $optionsArr[] = 'parent=' . $uniqueId;
	if (!isset($_POST['opt2'])) $optionsArr[] = 'site=';

	if (!empty($optionsArr)) {
		$options = '&' . implode('&', $optionsArr);
	}

	$code = "<iframe width=\"100%\" height=\"500\" allowtransparency=\"true\" frameborder=\"0\" id=\"$uniqueId\" {$scroll}src=\"{$url}{$toolPath}{$options}\"></iframe>";

	if ($resize && isset($_POST['opt5'])) {
		$script = trim(file_get_contents($studio->bindir . '/embed-mix.html'));
		$code .= PHP_EOL;
		$code .= str_replace('$id', $uniqueId, $script);
	}
	else {
		if ($resize) {
			$script = trim(file_get_contents($studio->bindir . '/embed.html'));
			$code .= PHP_EOL;
			$code .= str_replace('$id', $uniqueId, $script);
		}

		if (isset($_POST['opt5'])) {
			$script = trim(file_get_contents($studio->bindir . '/embed-hijack.html'));
			$code .= PHP_EOL;
			$code .= str_replace('$id', $uniqueId, $script);
		}
	}
}

function checked($i, $default = true) {
	if (isset($_POST['tool'])) {
		return isset($_POST['opt' . $i]) ? 'checked' : '';
	}

	return $default ? 'checked' : '';
}

asort($tools);

?>

<section>
	<div class="header with-border">
		<h1>
			<a class="back" href="index.php">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
					<path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
				</svg>
			</a>
			Embed tools
		</h1>
		<p>
			You can place individual tools on another website by generating an embed code snippet below.
		</p>
	</div>

	<?php if (!$https) { ?>
		<div class="warning rounded-3 mb-3">
			Your website is not using HTTPS, so embeds will not work on secure sites.
		</div>
	<?php } ?>

	<?php if (!$enabled) { ?>
		<div class="error rounded-3 mb-3">
			Tool embedding is currently disabled. Please enable it in <a href="embed-settings.php">settings</a>.
		</div>
	<?php } ?>

	<div class="row">
		<div class="col-md-5">
			<div class="card mb-3">
				<div class="card-header">
					Generate embed code
				</div>
				<div class="card-body">
					<form action="" method="post">
						<div class="mb-3">
							<label class="form-label">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-nut-fill" viewBox="0 0 16 16">
									<path d="M4.58 1a1 1 0 0 0-.868.504l-3.428 6a1 1 0 0 0 0 .992l3.428 6A1 1 0 0 0 4.58 15h6.84a1 1 0 0 0 .868-.504l3.429-6a1 1 0 0 0 0-.992l-3.429-6A1 1 0 0 0 11.42 1H4.58zm5.018 9.696a3 3 0 1 1-3-5.196 3 3 0 0 1 3 5.196z"/>
								</svg>
								Tool
							</label>
							<div class="form-text">Select which tool you want to generate an embed snippet for.</div>
							<select name="tool" class="form-select">
								<?php foreach ($tools as $id => $tool) { ?>
									<option value="<?php echo $id; ?>" <?php
										if (isset($_POST['tool'])) {
											if ($_POST['tool'] === $id) {
												echo "selected";
											}
										}
										else if (isset($_GET['id'])) {
											if ($_GET['id'] === $id) {
												echo "selected";
											}
										}
									?>><?php echo $tool; ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">
								<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
									<path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"/>
								</svg>
								Options
							</label>
							<div class="form-check-container">
								<div class="form-check">
									<input id="opt1" name="opt1" value="1" <?php echo checked(1); ?> type="checkbox" class="form-check-input">
									<label class="form-check-label" for="opt1">
										Show tool icon and name
									</label>
								</div>
								<div class="form-check">
									<input id="opt2" name="opt2" value="1" <?php echo checked(2); ?> type="checkbox" class="form-check-input">
									<label class="form-check-label" for="opt2">
										Show site input bar
										<span class="badge help" title="When disabled, you will need to pass the ?site= query parameter to trigger the tool for that domain." data-bs-toggle="tooltip" data-bs-placement="top">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question" viewBox="0 0 16 16">
												<path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
											</svg>
										</span>
									</label>
								</div>
								<div class="form-check">
									<input id="opt3" name="opt3" value="1" <?php echo checked(3); ?> type="checkbox" class="form-check-input">
									<label class="form-check-label" for="opt3">
										Resize frame to fit contents
									</label>
								</div>
								<div class="form-check">
									<input id="opt4" name="opt4" value="1" <?php echo checked(4); ?> type="checkbox" class="form-check-input">
									<label class="form-check-label" for="opt4">
										Disable cookies
									</label>
								</div>
								<div class="form-check">
									<input id="opt5" name="opt5" value="1" <?php echo checked(5, false); ?> type="checkbox" class="form-check-input">
									<label class="form-check-label" for="opt5">
										Open results in parent page
										<span class="badge help" title="When enabled, using the embedded tool will redirect the parent page to the tool results on your website (not recommended)." data-bs-toggle="tooltip" data-bs-placement="top">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question" viewBox="0 0 16 16">
												<path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
											</svg>
										</span>
									</label>
								</div>
							</div>
						</div>

						<input type="submit" class="btn btn-primary" value="Generate">
					</form>
				</div>
				<?php if ($code) { ?>
					<div class="card-footer">
						<p>
							Here's your embed snippet! Just copy this onto another website, and the tool will be
							embedded. You can check the preview on the right to see how it will look.
						</p>
						<pre class="snippet"><code><?php echo str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $code); ?></code></pre>
					</div>
				<?php } ?>
			</div>
			<div class="card">
				<div class="card-header">
					Settings
				</div>
				<div class="card-body">
					<p class="m-0">
						You can manage whether tool embedding is enabled, as well as configure whitelisted domains,
						from the
						<a href="embed-settings.php">embedding settings</a>.
					</p>
				</div>
			</div>
		</div>
		<div class="col-md-7">
			<div class="card">
				<div class="card-header">
					Preview
				</div>
				<div class="card-body">
					<?php if ($code) { ?>
					<div style="max-width: 980px;">
						<?php echo $code; ?>
					</div>
					<?php } else { ?>
					Generate an embed code first to see a preview...
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</section>

<script>
	var snippet = $('.snippet');

	snippet.on('mouseup', function() {
		var sel, range;
		var el = $(this)[0];

		if (window.getSelection && document.createRange) {
			sel = window.getSelection();
			if (sel.toString() == '') {
				window.setTimeout(function() {
					range = document.createRange();
					range.selectNodeContents(el);
					sel.removeAllRanges();
					sel.addRange(range);
				}, 1);
			}
		}
		else if (document.selection) {
			sel = document.selection.createRange();

			if (sel.text == '') {
				range = document.body.createTextRange();
				range.moveToElementText(el);
				range.select();
			}
		}
	});
</script>

<?php
$page->footer();
?>
