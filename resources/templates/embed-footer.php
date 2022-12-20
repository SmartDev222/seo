
	<?php if (($snippet = Ads::commit('footer')) !== false && DEMO) { ?>
		<?php if ($this->studio->getopt('ad-footer-container') === 'On') { ?><div class="container"><?php } ?>
		<?php echo $snippet; ?>
		<?php if ($this->studio->getopt('ad-footer-container') === 'On') { ?></div><?php } ?>
	<?php } ?>

	<?php $this->studio->getPluginManager()->call("footer"); ?>

	<template id="loader">
		<div class="loadable-overlay">
			<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-loader-quarter" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
				<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
				<line x1="12" y1="6" x2="12" y2="3" />
				<line x1="6" y1="12" x2="3" y2="12" />
				<line x1="7.75" y1="7.75" x2="5.6" y2="5.6" />
			</svg>
		</div>
	</template>

	<script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/jquery.min.js"></script>
	<script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/jquery-ui.min.js"></script>
	<script type="text/javascript">
		var path = "<?php echo $this->getPath(); ?>";
	</script>
	<script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/studio.js?b=<?php echo $this->studio->getVersion(); ?>"></script>

	<?php
	// iframe resizing
	$uniqueId = isset($_GET['r']) && $_GET['r'] != '0' ? $_GET['r'] : null;
	if ($uniqueId && strlen($uniqueId) != 6) die;
	if ($uniqueId) {
	?>

	<script>
		var id = "<?php echo sanitize_attribute($uniqueId); ?>";

		function sendHeight() {
			parent.postMessage(id + ':' + document.body.offsetHeight, '*');
		}

		sendHeight();
		setInterval(sendHeight, 100);
	</script>

	<?php } ?>

	<?php
	// parent hijacking
	$uniqueId = isset($_GET['parent']) && $_GET['parent'] != '0' ? $_GET['parent'] : null;
	if ($uniqueId && strlen($uniqueId) != 6) die;
	if ($uniqueId) {
		$redirect = '';

		if (isset($_GET['id'])) {
			$redirect = 'tool.php?id=' . urlencode($_GET['id']);
		}
	?>

	<script>
		var id = "<?php echo sanitize_attribute($uniqueId); ?>";
		var forms = $('form');
		var redir = "<?php echo sanitize_attribute($redirect); ?>";
		console.log(redir);

		$.each(forms, function() {
			var form = forms.filter(this);

			form.on('submit', function(e) {
				if (form.hasClass('remote-triggered')) {
					e.preventDefault();
					return;
				}

				form.addClass('remote-triggered');
				e.preventDefault();

				var data = {
					action: (new URL(redir, window.location.href)).href || window.location.href,
					method: form.attr('method') || 'get',
					inputs: []
				};

				var inputs = form.find(':input');

				$.each(inputs, function() {
					var input = inputs.filter(this);
					if (!input.attr('name')) return;

					data.inputs.push({
						name: input.attr('name'),
						value: input.val()
					});
				});

				parent.postMessage(id + '#' + JSON.stringify(data), '*');
			});
		});
	</script>

	<?php } ?>

	<?php echo $this->studio->getopt("custom-body-html"); ?>
</body>
</html>
