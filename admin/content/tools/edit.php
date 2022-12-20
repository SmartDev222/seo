<?php

use Studio\Content\Icons\IconManager;

require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

if (!isset($_GET['id'])) {
	die;
}

$tool = $studio->getToolById($_GET['id']);
if (!$tool) die("Tool not found");
$globalToolPage = $tool->getPage('');

$iconSets = IconManager::getIconSets();
$currentIcon = $globalToolPage->getIconFromSet();

$languages = [];
$languageFiles = [];

/**	@var Page[] */
$pages = [];

$q = $studio->sql->query("SELECT * FROM languages ORDER BY id ASC");
while ($row = $q->fetch_array()) {
	$languages[$row['locale']] = $row['name'];
	$pages[$row['locale']] = $tool->getPage($row['locale']);

	$file = $studio->basedir . "/resources/languages/" . $row['locale'] . "/tools.json";
	$languageFiles[$row['locale']] = json_decode(file_get_contents($file), true);
}

if (isset($_POST['isPostback']) && !DEMO) {
	foreach ($languages as $locale => $name) {
		$languagePage = $pages[$locale];
		$languageFile = $languageFiles[$locale];

		$toolName = $_POST['tool-name-' . $locale];
		$pageTitle = $_POST['title-' . $locale];
		$contentTop = $_POST['content-top-' . $locale];
		$contentBottom = $_POST['content-bottom-' . $locale];

		$iconDir = $_POST['icon-set'];
		$iconId = $_POST['icon-id'];

		$metaDescription = $_POST['meta-description-' . $locale];
		$metaKeywords = $_POST['meta-keywords-' . $locale];

		$ogTitle = $_POST['og-title-' . $locale];
		$ogType = $_POST['og-type-' . $locale];
		$ogImage = $_POST['og-image-' . $locale];
		$ogSiteName = $_POST['og-site-name-' . $locale];
		$ogDescription = $_POST['og-description-' . $locale];

		// Set the tool name
		// This must be done with the language file
		$languageFilePath = $studio->basedir . "/resources/languages/" . $locale . "/tools.json";
		$languageFile[$tool->name] = $toolName;
		file_put_contents($languageFilePath, json_encode($languageFile, JSON_PRETTY_PRINT));

		// Save content
		$languagePage->setTitle($pageTitle);
		$languagePage->setTopHTML($contentTop);
		$languagePage->setBottomHTML($contentBottom);

		// Save icon
		if ($iconId !== '' && $iconDir !== '') {
			$languagePage->setIcon($iconDir, $iconId);
		}

		// Save meta tags
		$languagePage->setMetaTag('description', $metaDescription);
		$languagePage->setMetaTag('keywords', $metaKeywords);

		// Save open graph
		$languagePage->setMetaTag('og:title', $ogTitle);
		$languagePage->setMetaTag('og:type', $ogType);
		$languagePage->setMetaTag('og:image', $ogImage);
		$languagePage->setMetaTag('og:site_name', $ogSiteName);
		$languagePage->setMetaTag('og:description', $ogDescription);
	}

	header('Location: edit.php?id=' . $tool->id . '&success=1');
	die;
}

?>

<script src="../../../resources/ckeditor/ckeditor.js"></script>

<section>
	<div class="header" style="max-width: 1120px;">
		<h1>
			<a class="back" href="index.php">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
					<path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
				</svg>
			</a>
			<?php echo $tool->name; ?>
		</h1>
	</div>

	<form action="" method="post">
		<input type="hidden" name="isPostback" value="1">

		<div class="save-container">
			<div class="navigation-container">
				<div class="navigation">
					<div class="nav nav-pills">
						<button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-content" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-text" viewBox="0 0 16 16">
								<path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
								<path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8zm0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"/>
							</svg>
							Content
						</button>
						<button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-icon" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-image" viewBox="0 0 16 16">
								<path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
								<path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
							</svg>
							Icon
						</button>
						<button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-meta" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
								<path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
							</svg>
							Meta tags
						</button>
						<button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-social" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
								<path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/>
							</svg>
							Social
						</button>
					</div>
				</div>
				<div class="saveable tab-content">
					<div class="language-picker">
						<select class="form-select" id="languagePicker">
							<?php
								foreach ($languages as $locale => $name) {
									$isDefault = $studio->getopt('default-language', 'en-us') === $locale;
							?>
							<option value="<?php echo $locale; ?>" <?php
								if ($isDefault) echo 'selected';
							?>><?php echo $name; ?></option>
							<?php
								}
							?>
						</select>
					</div>
					<div class="card mb-4 tab-pane show active" id="tab-content">
						<div class="card-body">
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag-fill" viewBox="0 0 16 16">
										<path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
									</svg>
									Tool name
								</label>
								<div class="form-text">Shown on the tools list, and at the top of the tool's results page.</div>
								<?php foreach ($languageFiles as $locale => $file) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<input type="text" class="form-control" name="tool-name-<?php echo $locale; ?>" value="<?php echo sanitize_attribute($file[$tool->name]); ?>">
									</div>
								<?php } ?>
							</div>
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag-fill" viewBox="0 0 16 16">
										<path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
									</svg>
									Page title
								</label>
								<div class="form-text">Leave blank to use the tool name.</div>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<input type="text" class="form-control" name="title-<?php echo $locale; ?>" value="<?php echo sanitize_attribute($p->getTitle()); ?>">
									</div>
								<?php } ?>
							</div>
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill" viewBox="0 0 16 16">
										<path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zM4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM4 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1h-4z"/>
									</svg>
									Top content
								</label>
								<div class="form-text">This content will show at the top of the page above the tool's results.</div>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<textarea class="ckeditor" id="editor-top-content-<?php echo $locale; ?>" name="content-top-<?php echo $locale; ?>"><?php echo $p->getTopHTML(); ?></textarea>
									</div>
								<?php } ?>
							</div>
							<div class="mb-0">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text-fill" viewBox="0 0 16 16">
										<path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zM4.5 9a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM4 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm.5 2.5a.5.5 0 0 1 0-1h4a.5.5 0 0 1 0 1h-4z"/>
									</svg>
									Bottom content
								</label>
								<div class="form-text">This content will show at the bottom of the page below the tool's results.</div>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<textarea class="ckeditor" id="editor-bottom-content-<?php echo $locale; ?>" name="content-bottom-<?php echo $locale; ?>"><?php echo $p->getBottomHTML(); ?></textarea>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
					<div class="card mb-4 tab-pane" id="tab-icon">
						<div class="card-body">
							<input type="hidden" name="icon-set" value="<?php echo $currentIcon->set->dirName; ?>">
							<input type="hidden" name="icon-id" value="<?php echo $currentIcon->id; ?>">

							<ul class="nav nav-pills mb-3">
								<?php
									foreach ($iconSets as $set) {
										$active = $set === $currentIcon->set ? 'active' : '';
								?>
								<li class="nav-item">
									<button class="icon-drawer-tab nav-link <?php echo $active; ?>" data-set="<?php echo $set->dirName; ?>" data-bs-toggle="tab" data-bs-target="#icons-<?php echo $set->dirName; ?>" type="button">
										<?php echo $set->name; ?>
									</button>
								</li>
								<?php
									}
								?>
							</ul>

							<div class="tab-content">
								<?php
									foreach ($iconSets as $set) {
										$active = $set === $currentIcon->set ? 'active' : '';
								?>
								<div class="tab-pane <?php echo $active; ?>" id="icons-<?php echo $set->dirName; ?>">
									<div class="icon-drawer" data-set="<?php echo $set->dirName; ?>">
										<?php foreach ($set->getIcons() as $id) { ?>
											<div class="icon" data-set="<?php echo $set->dirName; ?>" data-id="<?php echo $id; ?>">
												<img src="../../../resources/iconsets/<?php echo $set->dirName; ?>/<?php echo $id; ?>.png" alt="<?php echo $id; ?>">
												<?php echo $id; ?>
											</div>
										<?php } ?>
										<?php if (!$set->isInstalled()) { ?>
											<div class="not-installed">
												<p>This icon set is not installed.</p>
											</div>
										<?php } ?>
									</div>
								</div>
								<?php
									}
								?>
							</div>
						</div>
					</div>
					<div class="card mb-4 tab-pane" id="tab-meta">
						<div class="card-body">
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots-fill" viewBox="0 0 16 16">
										<path d="M16 8c0 3.866-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7zM5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0zm4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0zm3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
									</svg>
									Description
								</label>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<textarea class="form-control" name="meta-description-<?php echo $locale; ?>"><?php echo $p->getMetaTag('description'); ?></textarea>
									</div>
								<?php } ?>
							</div>
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tags-fill" viewBox="0 0 16 16">
										<path d="M2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586V2zm3.5 4a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
										<path d="M1.293 7.793A1 1 0 0 1 1 7.086V2a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l.043-.043-7.457-7.457z"/>
									</svg>
									Keywords
								</label>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<textarea class="form-control" name="meta-keywords-<?php echo $locale; ?>"><?php echo $p->getMetaTag('keywords'); ?></textarea>
									</div>
								<?php } ?>
								<div class="form-text">Separate keywords with commas.</div>
							</div>
						</div>
					</div>
					<div class="card mb-4 tab-pane" id="tab-social">
						<div class="card-header">
							Open Graph
						</div>
						<div class="card-body">
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-vector-pen" viewBox="0 0 16 16">
										<path fill-rule="evenodd" d="M10.646.646a.5.5 0 0 1 .708 0l4 4a.5.5 0 0 1 0 .708l-1.902 1.902-.829 3.313a1.5 1.5 0 0 1-1.024 1.073L1.254 14.746 4.358 4.4A1.5 1.5 0 0 1 5.43 3.377l3.313-.828L10.646.646zm-1.8 2.908-3.173.793a.5.5 0 0 0-.358.342l-2.57 8.565 8.567-2.57a.5.5 0 0 0 .34-.357l.794-3.174-3.6-3.6z"/>
										<path fill-rule="evenodd" d="M2.832 13.228 8 9a1 1 0 1 0-1-1l-4.228 5.168-.026.086.086-.026z"/>
									</svg>
									Title
								</label>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<input type="text" class="form-control" name="og-title-<?php echo $locale; ?>" value="<?php echo sanitize_attribute($p->getMetaTag('og:title')); ?>">
									</div>
								<?php } ?>
							</div>
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-folder-fill" viewBox="0 0 16 16">
										<path d="M9.828 3h3.982a2 2 0 0 1 1.992 2.181l-.637 7A2 2 0 0 1 13.174 14H2.825a2 2 0 0 1-1.991-1.819l-.637-7a1.99 1.99 0 0 1 .342-1.31L.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3zm-8.322.12C1.72 3.042 1.95 3 2.19 3h5.396l-.707-.707A1 1 0 0 0 6.172 2H2.5a1 1 0 0 0-1 .981l.006.139z"/>
									</svg>
									Type
								</label>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<input type="text" class="form-control" name="og-type-<?php echo $locale; ?>" value="<?php echo sanitize_attribute($p->getMetaTag('og:type')); ?>">
									</div>
								<?php } ?>
							</div>
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-image" viewBox="0 0 16 16">
										<path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
										<path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2h-12zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1h12z"/>
									</svg>
									Image
								</label>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<input type="text" class="form-control" name="og-image-<?php echo $locale; ?>" value="<?php echo sanitize_attribute($p->getMetaTag('og:image')); ?>" placeholder="Example: https://">
									</div>
								<?php } ?>
							</div>
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-fill" viewBox="0 0 16 16">
										<path fill-rule="evenodd" d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6zm5-.793V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"/>
										<path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"/>
									</svg>
									Site name
								</label>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<input type="text" class="form-control" name="og-site-name-<?php echo $locale; ?>" value="<?php echo sanitize_attribute($p->getMetaTag('og:site_name')); ?>">
									</div>
								<?php } ?>
							</div>
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots-fill" viewBox="0 0 16 16">
										<path d="M16 8c0 3.866-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.584.296-1.925.864-4.181 1.234-.2.032-.352-.176-.273-.362.354-.836.674-1.95.77-2.966C.744 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7zM5 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0zm4 0a1 1 0 1 0-2 0 1 1 0 0 0 2 0zm3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
									</svg>
									Description
								</label>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<textarea class="form-control" name="og-description-<?php echo $locale; ?>"><?php echo $p->getMetaTag('og:description'); ?></textarea>
									</div>
								<?php } ?>
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
						<img src="../../../resources/images/load32.gif" width="16px" height="16px">
					</button>
				</div>
			</div>
		</div>
	</form>
</section>

<script>
	var drawer = $('.icon-drawer');
	var drawerTabs = $('.icon-drawer-tab');
	var icons = drawer.find('.icon');

	var iconSetInput = $('input[name=icon-set]');
	var iconIdInput = $('input[name=icon-id]');

	var activeIconElement = null;

	function updateActiveElement() {
		if (activeIconElement) {
			activeIconElement.removeClass('active');
		}

		activeIconElement = icons.filter('[data-set="' + iconSetInput.val() + '"][data-id="' + iconIdInput.val() + '"]');
		activeIconElement.addClass('active');
	}

	// Changing tabs
	drawerTabs.on('shown.bs.tab', function(event) {
		var tab = drawerTabs.filter(event.target);
		var setName = tab.data('set');
		var match = icons.filter('[data-set="' + setName + '"][data-id="' + iconIdInput.val() + '"]');

		if (match.length > 0) {
			iconSetInput.val(setName).trigger('change');
			updateActiveElement();
		}
	});

	// Clicking icons
	icons.on('mousedown', function() {
		var icon = icons.filter(this);

		iconSetInput.val(icon.data('set')).trigger('change');
		iconIdInput.val(icon.data('id')).trigger('change');
		updateActiveElement();
	});

	updateActiveElement();
</script>

<script>
	var languagePicker = $('#languagePicker');
	var languageElements = $('.language-specific');

	function applyLanguage() {
		var selection = languagePicker.val();
		languageElements.removeClass('visible');
		languageElements.filter('[data-locale="' + selection + '"]').addClass('visible');
	}

	languagePicker.on('change', function() {
		applyLanguage();
	});

	applyLanguage();
</script>

<?php
$page->footer();
?>
