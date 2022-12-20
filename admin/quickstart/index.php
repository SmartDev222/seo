<?php

require "../includes/init.php";

$page->setPath("../../")->requirePermission('admin-access');
$studio->setopt('welcome-guide', 'Off');

$page->setPage(0x3000)->header();

$tasks = array(
	array(
		'url' => "../updates.php",
		'resolve' => function() use ($studio) { return $studio->getopt('last-update-check') >= time() - 86400; },
		'icon' => "e8b8",
		'name' => "Check for updates",
		'description' => "We distribute small patches over the air. Click here to make sure you're up to date."
	),
	array(
		'url' => "contact.php",
		'resolve' => function() use ($studio) { return $studio->getopt('quickstart-resolve-contact') === 'On'; },
		'icon' => "f22e",
		'name' => "Configure contact page",
		'description' => "Click here to enable and configure the contact page extension."
	),
	array(
		'url' => "mail.php",
		'resolve' => function() use ($studio) { return $studio->getopt('quickstart-resolve-mail') === 'On'; },
		'icon' => "e0be",
		'name' => "Configure outgoing mail",
		'description' => "Click here to change how emails are sent by the script."
	),
	array(
		'url' => "search.php",
		'resolve' => function() use ($studio) { return $studio->getopt('google-enabled') === 'On' || $studio->getopt('quickstart-resolve-search') === 'On'; },
		'icon' => "e80b",
		'name' => "Configure automatic search proxies",
		'description' => "Free automatic proxies for Google and Bing with a peer-to-peer infastructure."
	),
	array(
		'url' => "../cron.php",
		'resolve' => function() use ($studio) { return $studio->getopt('cron-last-run') >= time() - (86400 * 3); },
		'icon' => "e924",
		'name' => "Configure the cron job",
		'description' => "A cron job keeps this script working. We can configure it automatically for you."
	),
	array(
		'url' => "logo.php",
		'resolve' => function() use ($studio) { return $studio->getopt('quickstart-resolve-logo') === 'On'; },
		'icon' => "e3f4",
		'name' => "Change the logo",
		'description' => "Fit the script to your company or brand by uploading a logo."
	),
);

$remaining = 0;

foreach ($tasks as $task) {
	$remaining += $task['resolve']($studio) ? 0 : 1;
}

if ($remaining === 0) {
	$studio->setopt('welcome-guide-finished', 'On');
}

?>

<div class="header-v2 purple">
    <h1>Quick start</h1>
    <p>We'll help you get started quickly</p>
</div>

<div class="quickstart-intro">
	<h2>Introduction</h2>
	<p>
		Welcome to SEO Studio! Below is a list of quick start wizards that will help you quickly configure various
		important parts of the script. Please review them as soon as possible.
	</p>
</div>

<div class="quickstart-list">
	<?php
	foreach ($tasks as $task) {
		$isResolved = !! $task['resolve']($studio);
		if ($isResolved) $task['icon'] = 'e2e6';
	?>
	<a target="_blank" class="task <?php echo $isResolved ? 'resolved' : ''; ?>" href="<?php echo $task['url']; ?>">
		<div class="icon">
			<i class="material-icons">&#x<?php echo $task['icon']; ?>;</i>
		</div>
		<div class="details">
			<p>
				<strong><?php echo $task['name']; ?></strong>
				<?php echo $task['description']; ?>
			</p>
		</div>
	</a>
	<?php } ?>
</div>

<script type="text/javascript">
	var el = $('.quickstart-list');

	$(window).on('focus', function() {
		$.get(window.location.href).then(function(data) {
			var res = $(data);
			var target = res.find('.quickstart-list');

			el.html(target.html());
		});
	});
</script>

<?php
$page->footer();
?>
