<?php

use Studio\Util\StyleComposer;

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(19)->header('customize');

$colors = array(
	'header' => array(
		'_desc' => 'The navigation and logo bar on top of the website.',
		'background' => array(
			'desc' => 'The background color of the header.',
			'default' => '313D4C',
			'background' => 'header'
		),
		'link-color' => array(
			'desc' => 'The color of navigation links when inactive.',
			'default' => 'CCCCCC',
			'foreground' => 'header nav ul li a'
		),
		'active-link-color' => array(
			'desc' => 'The color of navigation links when active.',
			'default' => 'FFFFFF',
			'foreground' => 'header nav ul li a:focus, header nav ul li a:hover'
		),
		'website-picker-background' => array(
			'desc' => 'The background color of the website selection box on the tools page.',
			'default' => '29313B',
			'background' => 'section.website',
			'borderBottom' => 'header nav ul li a .arrow-down.dark'
		),
		'website-picker-foreground' => array(
			'desc' => 'The text color of the website input on the tools page.',
			'default' => '757575',
			'foreground' => 'section.website input[name=site]::placeholder'
		),
		'website-picker-dropdown' => array(
			'desc' => 'The background color of the website selection dropdown.',
			'default' => '3E4B5B',
			'background' => 'section.website .select .dropdown'
		),
		'website-picker-submit-background' => array(
			'desc' => 'The background color of the submit button that appears when typing in a new site.',
			'default' => '256397',
			'background' => 'section.website .apply button'
		),
		'website-picker-submit-foreground' => array(
			'desc' => 'The text color of the submit button that appears when typing in a new site.',
			'default' => 'FFFFFF',
			'foreground' => 'section.website .apply button'
		),
		'tool-header-background' => array(
			'desc' => 'The background color of the tool name header on tool pages.',
			'default' => '3F4E61',
			'background' => 'section.title.tool-title',
			'borderBottom' => 'header nav ul li a .arrow-down.tool'
		),
		'tool-header-foreground' => array(
			'desc' => 'The text color of the tool name header on tool pages.',
			'default' => 'EEEEEE',
			'foreground' => 'section.title.tool-title .flex .name h1'
		),
		'tool-website-picker-background' => array(
			'desc' => 'The background color of the website selection box on top of tools.',
			'default' => '3A4858',
			'background' => 'section.website.alt'
		),
		'title-header-background' => array(
			'desc' => 'The background color for generic title headers (such as contact page).',
			'default' => '2196F3',
			'background' => 'section.title',
			'borderBottom' => 'header nav ul li a .arrow-down.blue',
		),
	),
	'jumbotron' => array(
		'_desc' => 'The jumbotron is the big banner near the top of the home page.',
		'background' => array(
			'desc' => 'The background color of the big banner.',
			'default' => '2196F3',
			'background' => 'section.jumbo',
			'borderBottom' => 'header nav ul li a .arrow-down.home',
			'foreground' => 'section.jumbo a:hover'
		),
		'foreground' => array(
			'desc' => 'The color of text in the big banner.',
			'default' => 'FFFFFF',
			'foreground' => 'section.jumbo'
		),
		'button' => array(
			'desc' => 'The color of the button in the big banner.',
			'default' => 'FFFFFF',
			'foreground' => 'section.jumbo a',
			'background' => 'section.jumbo a:hover',
			'border' => 'section.jumbo a'
		),
	),
	'homepage' => array(
		'_desc' => 'Colors for other elements on the home page.',
		'icon-hover-color' => array(
			'desc' => 'The color of icons when hovered.',
			'default' => '2196F3',
			'foreground' => 'section.icons .col-md-4:hover .icon i'
		),
		'call-to-action-background' => array(
			'desc' => 'The background color of the call to action.',
			'default' => 'E4E4E4',
			'background' => 'section.home-tools'
		),
		'call-to-action-textbox' => array(
			'desc' => 'The background color of the call to action textbox.',
			'default' => 'D1D1D1',
			'background' => 'section.home-tools input[type=text]'
		),
		'call-to-action-textbox-fore' => array(
			'desc' => 'The text color of the call to action textbox.',
			'default' => '333333',
			'foreground' => 'section.home-tools input[type=text]'
		),
		'call-to-action-button-background' => array(
			'desc' => 'The background color of the call to action button.',
			'default' => '2196F3',
			'background' => 'section.home-tools button[type=submit]'
		),
		'call-to-action-button-foreground' => array(
			'desc' => 'The text color of the call to action button.',
			'default' => 'FFFFFF',
			'foreground' => 'section.home-tools button[type=submit]'
		),
	),
	'tools' => array(
		'_desc' => 'Colors for tool results.',
		'submit-button' => array(
			'desc' => 'The color of the submit button on tools which have a form.',
			'default' => '3F91CA',
			'background' => 'section.tool .form-container form button[type=submit]',
			'border' => 'section.tool .form-container form button[type=submit]',
		),
		'table-odd-background' => array(
			'desc' => 'The background color of odd rows in tables.',
			'default' => 'F6FBFC',
			'background' => 'section.tool .table-container table tbody tr.odd td'
		)
	)
);

$composer = new StyleComposer($studio->basedir . '/resources/styles/custom.css');

if (isset($_POST['save']) && !DEMO) {
	foreach ($_POST as $key => $value) {
		if (substr($key, 0, 6) != 'color:') continue;
		list($groupId, $colorId) = explode('+', substr($key, 6));

		$group = $colors[$groupId];
		$color = $group[$colorId];
		$value = str_replace('#', '', trim($_POST[$key]));
		$default = $color['default'];
		$saveKey = md5($groupId . '+' . $colorId);

		// If the default is selected, then remove any custom rules, they're unnecessary
		if (strtolower($value) === strtolower($default)) {
			$composer->remove($saveKey, $color);
		}

		// Otherwise, apply them
		else {
			$composer->add($saveKey, $color, $value);
		}
	}

	$composer->save();
	$studio->setopt('customCssTime', time());

    header("Location: colors.php?success=1");
    die;
}

?>

<form action="" method="post">
	<input type="hidden" name="save" value="1">

    <div class="save-container">
        <div class="saveable auto case-insensitive">
			<div class="panel v2 colors">
				<?php
					foreach ($colors as $groupId => $colors) {
						$groupName = ucwords(str_replace('-', ' ', $groupId));
				?>
				<div class="color-group">
					<h2>
						<?php echo $groupName; ?>
						<?php if (isset($colors['_desc'])) { ?>
							<p><?php echo $colors['_desc']; ?></p>
						<?php } ?>
					</h2>

					<?php
							foreach ($colors as $colorId => $color) {
								if ($colorId === '_desc') continue;
								$saveKey = md5($groupId . '+' . $colorId);
								$colorName = ucwords(str_replace('-', ' ', $colorId));

								$colorHex = $composer->get($saveKey, $color);
								$colorValue = '#' . $colorHex;
								$modified = $colorHex != $color['default'];
					?>
					<div class="color-row" color-default="<?php echo $color['default']; ?>">
						<div class="left">
							<strong><?php echo $colorName; ?></strong>
							<p><?php echo $color['desc']; ?></p>
						</div>
						<div class="right">
							<a class="reset <?php if (!$modified) { ?>hidden<?php } ?>" title="Reset this color to the default">
								<i class="material-icons">format_color_reset</i>
							</a>
							<div class="picker-container">
								<div class="picker-button">
									<div class="indicator" style="background-color: <?php echo $colorValue; ?>;"></div>
									<i class="material-icons">keyboard_arrow_down</i>
								</div>

								<div class="picker hidden" acp-color="<?php echo $colorValue; ?>"></div>
								<input type="hidden" name="color:<?php echo $groupId . '+' . $colorId; ?>" value="<?php echo $colorValue; ?>">
							</div>
						</div>
					</div>
					<?php
							}
					?>
					</div>
				<?php
					}
				?>
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

<script src="../../resources/scripts/color.js"></script>
<script>
	var rows = $('.color-row');
	var showingPicker;

	rows.each(function(i, div) {
		var row = $(div);
		var container = row.find('.picker-container');
		var button = container.find('.picker-button');
		var indicator = button.find('.indicator');
		var pickerElement = container.find('.picker');
		var input = container.find('input');
		var picker = AColorPicker.from(pickerElement);

		var resetButton = row.find('.reset');
		var defaultColor = row.attr('color-default');
		var defaultHex = '#' + defaultColor.toLowerCase();

		picker.on('change', function(p, color) {
			var hex = AColorPicker.parseColor(color, 'hex');
			indicator.css({ backgroundColor: hex });
			input.val(hex).trigger('change');

			if (hex.toLowerCase() != defaultHex) {
				resetButton.removeClass('hidden');
			}
			else {
				resetButton.addClass('hidden');
			}
		});

		button.on('click', function() {
			var showing = showingPicker;

			if (showingPicker) {
				showingPicker.addClass('hidden');
				showingPicker = null;
			}

			if (pickerElement == showing) {
				return;
			}

			if (pickerElement.hasClass('hidden')) {
				showingPicker = pickerElement;
			}

			pickerElement.toggleClass('hidden');
		});

		resetButton.on('click', function() {
			indicator.css({ backgroundColor: '#' + defaultColor });
			input.val('#' + defaultColor).trigger('change');
			picker[0].setColor('#' + defaultColor);
			resetButton.addClass('hidden');
		});
	});

	$(document).on('mousedown', function(event) {
		$target = $(event.target);

		if (!$target.closest('.picker-button').length) {
			if ($target.closest('.picker').length == 0) {
				$('.picker').addClass('hidden');
				showingPicker = null;
			}
		}
	});
</script>

<?php
$page->footer();
?>
