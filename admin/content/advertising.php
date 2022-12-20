<?php
require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(11)->header('content');

$q = $studio->sql->query("SELECT * FROM `groups` ORDER BY id ASC");
$groups = [];

while ($row = $q->fetch_array()) {
	$groups[] = $row;
}

$pages = [
	'home' => ['name' => 'Home', 'default' => false],
	'tools' => ['name' => 'Tools', 'default' => true],
	'tool' => ['name' => 'Individual tools', 'default' => true]
];

if (isset($_POST['isPostback']) && !DEMO) {
    $ints = array(
        "max-ads-per-page"
    );

    $bools = array(
        "ad-header-container", "ad-footer-container", "ads-preview"
    );

    $strs = array(
        "ad-728x90", "ad-120x600", "ad-468x60",
		"ad-300x250", "ad-300x600", "ad-250x250",
		"ad-200x200", "ad-header", "ad-footer"
    );

    foreach ($bools as $bool) {
        if (!isset($_POST[$bool])) $_POST[$bool] = 'Off';

        $val = $_POST[$bool];
        if ($val != "On" && $val != "Off") $val = "Off";

        $studio->setopt($bool, $val);
    }

    foreach ($ints as $int) {
        if (!isset($_POST[$int])) $studio->showFatalError("Missing POST parameter $int");

        $val = $_POST[$int];
		if (!preg_match('/^[0-9]+$/', $val)) $val = 0;
		$val = intval($val);
		if ($val < 0) $val = 0;
		if ($val > 1000) $val = 1000;

        $studio->setopt($int, $val);
    }

    foreach ($strs as $str) {
        if (!isset($_POST[$str])) $studio->showFatalError("Missing POST parameter $str");

        $val = $_POST[$str];
        $studio->setopt($str, $val);
    }

	$groupStates = [];

	foreach ($groups as $group) {
		$fieldName = 'group_' . $group['id'];
		$fieldValue = (isset($_POST[$fieldName]) && $_POST[$fieldName] === '1');
		$groupStates[$group['id']] = $fieldValue;
	}

	$studio->setopt('ads-disabled-groups', json_encode($groupStates));

    header("Location: advertising.php?success=1");
    die;
}

$groupStates = json_decode($studio->getopt('ads-disabled-groups', '{}'), true);
?>

<section>
	<div class="header" style="max-width: 1120px;">
		<h1>
			Manage advertising
		</h1>
	</div>

	<form action="" method="post">
		<input type="hidden" name="isPostback" value="1">

		<div class="save-container">
			<div class="navigation-container">
				<div class="navigation">
					<div class="nav nav-pills">
						<button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-banners" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-aspect-ratio" viewBox="0 0 16 16">
								<path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5v-9zM1.5 3a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-13z"/>
								<path d="M2 4.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1H3v2.5a.5.5 0 0 1-1 0v-3zm12 7a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H13V8.5a.5.5 0 0 1 1 0v3z"/>
							</svg>
							Banners
						</button>
						<button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-settings" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
								<path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
								<path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
							</svg>
							Settings
						</button>
					</div>
				</div>
				<div class="saveable tab-content">
					<div class="tab-pane show active" id="tab-banners">
						<div class="card mb-4">
							<div class="card-header">
								Preview
							</div>
							<div class="card-body">
								<div class="mb-0">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
											<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
											<path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
										</svg>
										Preview mode
									</label>
									<div class="form-text">
										This option will fill empty ad slots with preview images. Use this to see where your ads will be placed.<br>
										Note: Advertising slots may be added, moved, or removed based on your custom page content.
									</div>
									<div class="form-check-container">
										<div class="form-check">
											<input class="form-check-input" name="ads-preview" type="checkbox" value="On" id="adPreviewMode" <?php if ($studio->getopt("ads-preview") === 'On') echo 'checked'; ?>>
											<label class="form-check-label" for="adPreviewMode">
												Activate previews
											</label>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="card mb-4">
							<div class="card-header">
								Sizes
							</div>
							<div class="card-body">
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Leaderboard (728x90)
									</label>
									<textarea class="form-control" name="ad-728x90" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-728x90"); ?></textarea>
								</div>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Skyscraper (120x600)
									</label>
									<textarea class="form-control" name="ad-120x600" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-120x600"); ?></textarea>
								</div>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Banner (468x60)
									</label>
									<textarea class="form-control" name="ad-468x60" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-468x60"); ?></textarea>
								</div>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Inline Rectangle (300x250)
									</label>
									<textarea class="form-control" name="ad-300x250" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-300x250"); ?></textarea>
								</div>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Half Page (300x600)
									</label>
									<textarea class="form-control" name="ad-300x600" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-300x600"); ?></textarea>
								</div>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Small (250x250)
									</label>
									<textarea class="form-control" name="ad-250x250" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-250x250"); ?></textarea>
								</div>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Small Square (200x200)
									</label>
									<textarea class="form-control" name="ad-200x200" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-200x200"); ?></textarea>
								</div>
							</div>
						</div>
						<div class="card mb-4">
							<div class="card-header">
								Full width
							</div>
							<div class="card-body">
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Header (full width)
									</label>
									<div class="form-text">This will be placed under the header and should fill the width of the page to look good.</div>
									<textarea class="form-control" name="ad-header" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-header"); ?></textarea>
									<div class="form-check-container">
										<div class="form-check">
											<input class="form-check-input" name="ad-header-container" type="checkbox" value="On" id="adHeaderContainer" <?php if ($studio->getopt("ad-header-container") === 'On') echo 'checked'; ?>>
											<label class="form-check-label" for="adHeaderContainer">
												Constrain to content container
												<span class="badge help" title="Places the ad banner inside the content container so it will only take up the same width as the main content." data-bs-toggle="tooltip" data-bs-placement="top">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question" viewBox="0 0 16 16">
														<path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
													</svg>
												</span>
											</label>
										</div>
									</div>
								</div>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-code-square" viewBox="0 0 16 16">
											<path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
											<path d="M6.854 4.646a.5.5 0 0 1 0 .708L4.207 8l2.647 2.646a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 0 1 .708 0zm2.292 0a.5.5 0 0 0 0 .708L11.793 8l-2.647 2.646a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708 0z"/>
										</svg>
										Footer (full width)
									</label>
									<div class="form-text">This will be placed above the footer and should fill the width of the page to look good.</div>
									<textarea class="form-control" name="ad-footer" rows="3" spellcheck="false"><?php echo $studio->getopt("ad-footer"); ?></textarea>
									<div class="form-check-container">
										<div class="form-check">
											<input class="form-check-input" name="ad-footer-container" type="checkbox" value="On" id="adFooterContainer" <?php if ($studio->getopt("ad-footer-container") === 'On') echo 'checked'; ?>>
											<label class="form-check-label" for="adFooterContainer">
												Constrain to content container
												<span class="badge help" title="Places the ad banner inside the content container so it will only take up the same width as the main content." data-bs-toggle="tooltip" data-bs-placement="top">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question" viewBox="0 0 16 16">
														<path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
													</svg>
												</span>
											</label>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="card mb-4 tab-pane" id="tab-settings">
						<div class="card-header">
							Settings
						</div>
						<div class="card-body">
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-speedometer" viewBox="0 0 16 16">
										<path d="M8 2a.5.5 0 0 1 .5.5V4a.5.5 0 0 1-1 0V2.5A.5.5 0 0 1 8 2zM3.732 3.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707zM2 8a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 8zm9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5zm.754-4.246a.389.389 0 0 0-.527-.02L7.547 7.31A.91.91 0 1 0 8.85 8.569l3.434-4.297a.389.389 0 0 0-.029-.518z"/>
										<path fill-rule="evenodd" d="M6.664 15.889A8 8 0 1 1 9.336.11a8 8 0 0 1-2.672 15.78zm-4.665-4.283A11.945 11.945 0 0 1 8 10c2.186 0 4.236.585 6.001 1.606a7 7 0 1 0-12.002 0z"/>
									</svg>
									Max number of ads per page
								</label>
								<div class="form-text">We won't show more than this number of ads at once. Set to 0 for unlimited ads.</div>
								<input type="number" class="form-control" name="max-ads-per-page" value="<?php echo sanitize_attribute($studio->getopt('max-ads-per-page', 0)); ?>">
							</div>
							<!--
							Coming soon...
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-square" viewBox="0 0 16 16">
										<path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
										<path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1v-1c0-1-1-4-6-4s-6 3-6 4v1a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12z"/>
									</svg>
									Show ads on these pages
								</label>
								<div class="form-text">Uncheck a page to prevent ads from showing on it.</div>
								<div class="form-check-container">
									<?php foreach ($pages as $id => $data) { ?>
										<div class="form-check">
											<input class="form-check-input" name="ads_<?php echo $id; ?>" type="checkbox" value="1" id="ads_<?php echo $id; ?>" <?php if (false) echo 'checked'; ?>>
											<label class="form-check-label" for="ads_<?php echo $id; ?>">
												<?php echo $data['name']; ?>
											</label>
										</div>
									<?php } ?>
								</div>
							</div> -->
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-square" viewBox="0 0 16 16">
										<path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
										<path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1v-1c0-1-1-4-6-4s-6 3-6 4v1a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12z"/>
									</svg>
									Disable ads for specific groups
								</label>
								<div class="form-text">Select which user groups shouldn't see ads.</div>
								<div class="form-check-container">
									<?php
									foreach ($groups as $i => $group) {
										$checked = isset($groupStates[$group['id']]) && $groupStates[$group['id']];
									?>
										<div class="form-check">
											<input class="form-check-input" name="group_<?php echo $group['id']; ?>" type="checkbox" value="1" id="groupCheckbox<?php echo $i; ?>" <?php if ($checked) echo 'checked'; ?>>
											<label class="form-check-label" for="groupCheckbox<?php echo $i; ?>">
												<?php echo $group['name']; ?>
											</label>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="save no-padding">
				<div class="save-box right-on-mobile">
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
