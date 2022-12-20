<?php

use Studio\Util\StyleComposer;

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(19)->header('customize');

$composer = new StyleComposer($studio->basedir . '/resources/styles/custom.css');
$headerBackgroundColor = $composer->get(md5('header+background'), array(
	'desc' => 'The background color of the header.',
	'default' => '313D4C',
	'background' => 'header'
));

if (isset($_POST['isPostback']) && !DEMO) {
	// Logo file
	if (isset($_FILES['logo']) && !empty($_FILES['logo']['name'])) {
		$image = $_FILES['logo'];
		$extension = pathinfo(basename($image['name']), PATHINFO_EXTENSION);

		if ($extension != "png") {
			die("Invalid extension: $extension");
		}

		$b = file_put_contents(
			$studio->basedir . "/resources/images/logo-old.".time().".png",
			file_get_contents($studio->basedir . "/resources/images/logo.png")
		);

		if ($b === false) {
			die("Could not write to /resources/images/");
		}

		if(move_uploaded_file($image['tmp_name'], $studio->basedir . "/resources/images/logo.png")) {
			$studio->setopt('logo-timestamp', time());
		}
		else {
			die("Could not write to /resources/images/logo.png");
		}
	}

	// Favicon file
	if (isset($_FILES['favicon']) && !empty($_FILES['favicon']['name'])) {
		$image = $_FILES['favicon'];
		$extension = pathinfo(basename($image['name']), PATHINFO_EXTENSION);
		$mime = '';

		switch ($extension) {
			case 'png': $mime = 'image/png'; break;
			case 'ico': $mime = 'image/x-icon'; break;
			default: die("Please upload a PNG or ICO image for the favicon!");
		}

		$targetPath = $studio->basedir . '/favicon.' . $extension;
		$backupPath = $studio->basedir . '/resources/images/favicon-old.' . $extension;

		if (file_exists($targetPath)) {
			if (file_put_contents($backupPath, file_get_contents($targetPath)) === false) {
				die("Could not write to /resources/images/");
			}
		}

		if (move_uploaded_file($image['tmp_name'], $targetPath)) {
			$studio->setopt('favicon-name', 'favicon.' . $extension);
			$studio->setopt('favicon-mime', $mime);
			$studio->setopt('favicon-timestamp', time());
		}
		else {
			die("Could not write to /favicon.$extension");
		}
	}

	// Logo size
	if (in_array($_POST['logo-size'], ['small', 'medium', 'large', 'xlarge'])) {
        $studio->setopt('logo-height', $_POST['logo-size']);
    }

	header("Location: branding.php?success=1");
	die;
}
?>

<section class="with-heading">
    <h1>Assets</h1>

	<form action="" method="post" enctype="<?php echo (DEMO ? '' : 'multipart/form-data'); ?>">
		<input type="hidden" name="isPostback" value="1">

		<div class="save-container">
			<div class="saveable">
				<div class="card mb-4">
					<div class="card-header">
						Logo
					</div>
					<div class="card-body">
						<div class="row mb-3">
							<div class="col-md-3 align-self-center">
								<label class="form-label mb-0">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-image-fill" viewBox="0 0 16 16">
										<path d="M.002 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-12a2 2 0 0 1-2-2V3zm1 9v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12zm5-6.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0z"/>
									</svg>
									Preview
								</label>
							</div>
							<div class="col-md-9">
								<div style="background-color: #<?php echo $headerBackgroundColor; ?>" class="p-3 d-inline-block rounded-3 align-middle border">
									<img id="previewImage" src="../../resources/images/logo.png?t=<?php echo $studio->getopt('logo-timestamp', 0); ?>" height="<?php
										$sizes = [
											'small' => '45px',
											'medium' => '54px',
											'large' => '60px',
											'xlarge' => '70px'
										];

										echo $sizes[$studio->getopt('logo-height', 'small')];
									?>">
								</div>
							</div>
						</div>
						<div class="row mb-3">
							<div class="col-md-3 align-self-center">
								<label for="logoFileInput" class="form-label mb-0">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-arrow-up-fill" viewBox="0 0 16 16">
										<path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zM6.354 9.854a.5.5 0 0 1-.708-.708l2-2a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 8.707V12.5a.5.5 0 0 1-1 0V8.707L6.354 9.854z"/>
									</svg>
									Upload new image
								</label>
							</div>
							<div class="col-md-9">
								<input class="form-control" type="file" id="logoFileInput" name="logo">
								<div class="form-text">We recommend choosing a horizontal, transparent PNG image.</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-3 align-self-center">
								<label for="logoSizeInput" class="form-label mb-0">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-aspect-ratio-fill" viewBox="0 0 16 16">
										<path d="M0 12.5v-9A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5zM2.5 4a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 1 0V5h2.5a.5.5 0 0 0 0-1h-3zm11 8a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-1 0V11h-2.5a.5.5 0 0 0 0 1h3z"/>
									</svg>
									Adjust size
								</label>
							</div>
							<div class="col-md-9">
								<select class="form-select" name="logo-size" id="logoSizeInput">
									<option value="small" <?php if ($studio->getopt('logo-height', 'small') === 'small') echo 'selected'; ?>>Small (45px)</option>
									<option value="medium" <?php if ($studio->getopt('logo-height') === 'medium') echo 'selected'; ?>>Medium (54px)</option>
									<option value="large" <?php if ($studio->getopt('logo-height') === 'large') echo 'selected'; ?>>Large (60px)</option>
									<option value="xlarge" <?php if ($studio->getopt('logo-height') === 'xlarge') echo 'selected'; ?>>Extra Large (70px)</option>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-4">
					<div class="card-header">
						Favicon
					</div>
					<div class="card-body">
						<div class="row mb-3">
							<div class="col-md-3 align-self-center">
								<label class="form-label mb-0">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-image-fill" viewBox="0 0 16 16">
										<path d="M.002 3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-12a2 2 0 0 1-2-2V3zm1 9v1a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12zm5-6.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0z"/>
									</svg>
									Preview
								</label>
							</div>
							<div class="col-md-9">
								<div class="p-3 d-inline-block rounded-3 align-middle me-2 faviconPreviewContainer border" style="background-color: #fff;">
									<?php if ($studio->getopt('favicon-name')) { ?>
										<img class="previewFavicon" src="../../<?php echo $studio->getopt('favicon-name'); ?>?t=<?php echo $studio->getopt('favicon-timestamp', 0); ?>">
									<?php } else { ?>
										<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#aaa" class="bi bi-x-circle" viewBox="0 0 16 16">
											<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
											<path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
										</svg>
									<?php } ?>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-3 align-self-center">
								<label for="faviconFileInput" class="form-label mb-0">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-arrow-up-fill" viewBox="0 0 16 16">
										<path d="M9.293 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.707A1 1 0 0 0 13.707 4L10 .293A1 1 0 0 0 9.293 0zM9.5 3.5v-2l3 3h-2a1 1 0 0 1-1-1zM6.354 9.854a.5.5 0 0 1-.708-.708l2-2a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 8.707V12.5a.5.5 0 0 1-1 0V8.707L6.354 9.854z"/>
									</svg>
									Upload new image
								</label>
							</div>
							<div class="col-md-9">
								<input class="form-control" type="file" id="faviconFileInput" name="favicon">
								<div class="form-text">We recommend choosing a transparent 32x32 PNG or ICO image.</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="save no-padding">
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
</section>

<script>
	var logoFileInput = $('#logoFileInput');
	var faviconFileInput = $('#faviconFileInput');

	var sizeInput = $('#logoSizeInput');
	var previewImage = $('#previewImage');
	var previewFavicon = $('.previewFavicon');

	var sizes = {
		'small': '45px',
		'medium': '54px',
		'large': '60px',
		'xlarge': '70px'
	};

	logoFileInput.on('change', () => {
		if (logoFileInput[0].files.length > 0) {
			previewImage.attr('src', URL.createObjectURL(logoFileInput[0].files[0]));
		}
	});

	faviconFileInput.on('change', () => {
		if (faviconFileInput[0].files.length > 0) {
			if (previewFavicon.length === 0) {
				$('.faviconPreviewContainer').html(`<img class="previewFavicon">`);
				previewFavicon = $('.previewFavicon');
			}

			previewFavicon.attr('src', URL.createObjectURL(faviconFileInput[0].files[0]));
		}
	});

	sizeInput.on('change', () => {
		var size = sizeInput.val();
		var height = sizes[size];

		previewImage.attr('height', height);
	})
</script>

<?php
$page->footer();
?>
