<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

$pages = array(
	'Home' => ['home.php', 'The home page of the website.'],
	'Tools' => ['tools.php', 'The tools page which lists all available tools.'],
	'Terms of Service' => ['terms.php', 'Optional terms of service page.'],
	'Privacy Policy' => ['privacy.php', 'Optional privacy policy page.']
);

$activePages = array('Home', 'Tools');

if (file_exists($studio->basedir . DIRECTORY_SEPARATOR . 'terms.php')) $activePages[] = 'Terms of Service';
if (file_exists($studio->basedir . DIRECTORY_SEPARATOR . 'privacy.php')) $activePages[] = 'Privacy Policy';
?>

<section>
    <div class="table-heading">
        <div class="title">
            <h1>Default pages</h1>
        </div>
    </div>

    <div class="table-responsive-md">
        <table class="table table-hover">
            <thead>
                <tr>
					<th width="250px">Name</th>
					<th>Description</th>
					<th>Status</th>
                    <th width="90px"></th>
                </tr>
            </thead>
			<tbody>
				<?php foreach ($pages as $name => $config) {
					$link = $config[0];
					$desc = $config[1];
				?>
				<tr>
					<td class="text-nowrap"><a class="fill" href="<?php echo $link; ?>"><?php echo $name; ?></a></td>
					<td class="text-nowrap"><a class="fill" href="<?php echo $link; ?>"><?php echo $desc; ?></a></td>
					<td class="text-nowrap">
						<?php if (in_array($name, $activePages)) { ?>
						<span class="badge bg-success">Published</span>
						<?php } else { ?>
						<span class="badge bg-light text-dark">Draft</span>
						<?php } ?>
					</td>
					<td class="right">
						<div class="dropdown">
                            <button class="btn" type="button" data-bs-toggle="dropdown" data-bs-boundary="window">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16">
                                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $link; ?>">Edit</a></li>
                            </ul>
                        </div>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
</section>

<?php
$page->footer();
?>
