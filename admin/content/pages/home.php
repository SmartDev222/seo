<?php

use Studio\Content\Pages\StandardPage;
use Studio\Content\Pages\StandardPageManager;

require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

$languages = [];
$languageFiles = [];

/**	@var StandardPage[] */
$pages = [];

$q = $studio->sql->query("SELECT * FROM languages ORDER BY id ASC");
while ($row = $q->fetch_array()) {
	$languages[$row['locale']] = $row['name'];
	$pages[$row['locale']] = StandardPageManager::getPage('home', $row['locale']);

	$file = $studio->basedir . "/resources/languages/" . $row['locale'] . "/home.json";
	$languageFiles[$row['locale']] = json_decode(file_get_contents($file), true);
}

if (isset($_POST['isPostback']) && !DEMO) {
	foreach ($languages as $locale => $name) {
		$languagePage = $pages[$locale];
		$languageFile = $languageFiles[$locale];

		$pageTitle = $_POST['title-' . $locale];
		$contentMiddle = $_POST['content-middle-' . $locale];
		$contentBottom = $_POST['content-bottom-' . $locale];

		$metaDescription = $_POST['meta-description-' . $locale];
		$metaKeywords = $_POST['meta-keywords-' . $locale];

		$ogTitle = $_POST['og-title-' . $locale];
		$ogType = $_POST['og-type-' . $locale];
		$ogImage = $_POST['og-image-' . $locale];
		$ogSiteName = $_POST['og-site-name-' . $locale];
		$ogDescription = $_POST['og-description-' . $locale];

		// Save translations
		foreach ($languageFile as $in => $out) {
            $input = $locale . ':' . md5($in);
            if (!isset($_POST[$input])) die("Missing POST field $input");

            $languageFile[$in] = $_POST[$input];
		}

        if (defined("JSON_PRETTY_PRINT")) $new = json_encode($languageFile, JSON_PRETTY_PRINT);
        else $new = json_encode($languageFile);
        file_put_contents($studio->basedir . "/resources/languages/" . $locale . "/home.json", $new);

		// Save content
		$languagePage->setTitle($pageTitle);
		$languagePage->setMiddleHTML($contentMiddle);
		$languagePage->setBottomHTML($contentBottom);

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

	header('Location: home.php?success=1');
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
			Edit Home
		</h1>

		<div class="card">
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
						<button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-translations" type="button">
							<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe2" viewBox="0 0 16 16">
								<path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855-.143.268-.276.56-.395.872.705.157 1.472.257 2.282.287V1.077zM4.249 3.539c.142-.384.304-.744.481-1.078a6.7 6.7 0 0 1 .597-.933A7.01 7.01 0 0 0 3.051 3.05c.362.184.763.349 1.198.49zM3.509 7.5c.036-1.07.188-2.087.436-3.008a9.124 9.124 0 0 1-1.565-.667A6.964 6.964 0 0 0 1.018 7.5h2.49zm1.4-2.741a12.344 12.344 0 0 0-.4 2.741H7.5V5.091c-.91-.03-1.783-.145-2.591-.332zM8.5 5.09V7.5h2.99a12.342 12.342 0 0 0-.399-2.741c-.808.187-1.681.301-2.591.332zM4.51 8.5c.035.987.176 1.914.399 2.741A13.612 13.612 0 0 1 7.5 10.91V8.5H4.51zm3.99 0v2.409c.91.03 1.783.145 2.591.332.223-.827.364-1.754.4-2.741H8.5zm-3.282 3.696c.12.312.252.604.395.872.552 1.035 1.218 1.65 1.887 1.855V11.91c-.81.03-1.577.13-2.282.287zm.11 2.276a6.696 6.696 0 0 1-.598-.933 8.853 8.853 0 0 1-.481-1.079 8.38 8.38 0 0 0-1.198.49 7.01 7.01 0 0 0 2.276 1.522zm-1.383-2.964A13.36 13.36 0 0 1 3.508 8.5h-2.49a6.963 6.963 0 0 0 1.362 3.675c.47-.258.995-.482 1.565-.667zm6.728 2.964a7.009 7.009 0 0 0 2.275-1.521 8.376 8.376 0 0 0-1.197-.49 8.853 8.853 0 0 1-.481 1.078 6.688 6.688 0 0 1-.597.933zM8.5 11.909v3.014c.67-.204 1.335-.82 1.887-1.855.143-.268.276-.56.395-.872A12.63 12.63 0 0 0 8.5 11.91zm3.555-.401c.57.185 1.095.409 1.565.667A6.963 6.963 0 0 0 14.982 8.5h-2.49a13.36 13.36 0 0 1-.437 3.008zM14.982 7.5a6.963 6.963 0 0 0-1.362-3.675c-.47.258-.995.482-1.565.667.248.92.4 1.938.437 3.008h2.49zM11.27 2.461c.177.334.339.694.482 1.078a8.368 8.368 0 0 0 1.196-.49 7.01 7.01 0 0 0-2.275-1.52c.218.283.418.597.597.932zm-.488 1.343a7.765 7.765 0 0 0-.395-.872C9.835 1.897 9.17 1.282 8.5 1.077V4.09c.81-.03 1.577-.13 2.282-.287z"/>
							</svg>
							Translations
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
					<div class="card mb-4 tab-pane show active" id="tab-content">
						<div class="card-body">
							<div class="mb-3">
								<label class="form-label">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tag-fill" viewBox="0 0 16 16">
										<path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
									</svg>
									Page title
								</label>
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
									Middle content
								</label>
								<div class="form-text">This content will show below the jumbotron.</div>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<textarea class="ckeditor" id="editor-middle-content-<?php echo $locale; ?>" name="content-middle-<?php echo $locale; ?>"><?php echo $p->getMiddleHTML(); ?></textarea>
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
								<div class="form-text">This content will show at the bottom of the page.</div>
								<?php foreach ($pages as $locale => $p) { ?>
									<div class="language-specific" data-locale="<?php echo $locale; ?>">
										<textarea class="ckeditor" id="editor-bottom-content-<?php echo $locale; ?>" name="content-bottom-<?php echo $locale; ?>"><?php echo $p->getBottomHTML(); ?></textarea>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
					<div class="card mb-4 tab-pane" id="tab-translations">
						<div class="card-body">
							<?php foreach ($languageFiles as $locale => $items) { ?>
							<div class="language-specific" data-locale="<?php echo $locale; ?>">
								<?php
									foreach ($items as $in => $out) {
								?>
								<div class="mb-3">
									<label class="form-label">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-translate" viewBox="0 0 16 16">
											<path d="M4.545 6.714 4.11 8H3l1.862-5h1.284L8 8H6.833l-.435-1.286H4.545zm1.634-.736L5.5 3.956h-.049l-.679 2.022H6.18z"/>
											<path d="M0 2a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v3h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-3H2a2 2 0 0 1-2-2V2zm2-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H2zm7.138 9.995c.193.301.402.583.63.846-.748.575-1.673 1.001-2.768 1.292.178.217.451.635.555.867 1.125-.359 2.08-.844 2.886-1.494.777.665 1.739 1.165 2.93 1.472.133-.254.414-.673.629-.89-1.125-.253-2.057-.694-2.82-1.284.681-.747 1.222-1.651 1.621-2.757H14V8h-3v1.047h.765c-.318.844-.74 1.546-1.272 2.13a6.066 6.066 0 0 1-.415-.492 1.988 1.988 0 0 1-.94.31z"/>
										</svg>
										<?php echo $in; ?>
									</label>
									<input class="form-control" type="text" name="<?php echo $locale . ':' . md5($in); ?>" value="<?php echo sanitize_attribute($out); ?>">
								</div>
								<?php
									}
								?>
							</div>
							<?php } ?>
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
