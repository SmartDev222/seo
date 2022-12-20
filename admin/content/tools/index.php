<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

$tools = $studio->getTools();
$toolsDisabled = json_decode($studio->getopt('tools.disabled', '[]'), true);

/**
 * Returns `true` if the specified tool ID is enabled.
 *
 * @param string $toolId
 * @return bool
 */
function isToolEnabled($toolId) {
    global $toolsDisabled;
    return !in_array($toolId, $toolsDisabled);
}

/**
 * Sets the enabled state of the specified tool ID.
 *
 * @param string $toolId
 * @param bool $enabled
 * @return void
 */
function setToolEnabled($toolId, $enabled = true) {
    global $studio, $toolsDisabled;

    // Add the ID to the array if we need to disable it
    if (!$enabled) {
        if (!in_array($toolId, $toolsDisabled)) {
            $toolsDisabled[] = $toolId;
        }
    }

    // Remove it from the array if we're enabling it
    else {
        if (in_array($toolId, $toolsDisabled)) {
            $toolsDisabled = array_diff($toolsDisabled, array($toolId));
        }
    }

    $studio->setopt('tools.disabled', json_encode($toolsDisabled));
}

if (isset($_GET['toggle']) && !DEMO) {
	$toolId = $_GET['toggle'];

	foreach ($tools as $tool) {
		if ($tool->id === $toolId) {
			setToolEnabled($tool->id, !isToolEnabled($tool->id));
			header('Location: index.php');
			die;
		}
	}

	die("Tool not found!");
}
?>

<section>
    <div class="table-heading">
        <div class="title">
            <h1>Tools</h1>
        </div>
		<div class="actions">
			<a class="btn" href="embed.php">Embed tools</a>
		</div>
    </div>

    <div class="table-responsive-md">
        <table class="table table-hover">
            <thead>
                <tr>
					<th width="250px">Name</th>
					<th width="250px">Id</th>
					<th>Status</th>
                    <th width="90px"></th>
                </tr>
            </thead>
            <tbody>
				<?php
				foreach ($tools as $tool) {
					$toolPage = $tool->getPage('en-us');
				?>
				<tr>
					<td class="text-nowrap">
						<a class="fill" href="edit.php?id=<?php echo $tool->id; ?>">
							<img src="<?php echo $page->getPath() . sanitize_attribute($toolPage->getIcon()); ?>" height="24px" class="me-2">
							<?php echo $tool->name; ?>
						</a>
					</td>
					<td class="text-nowrap">
						<a class="fill" href="edit.php?id=<?php echo $tool->id; ?>">
							<?php echo $tool->id; ?>
						</a>
					</td>
					<td class="text-nowrap"><?php
						if (isToolEnabled($tool->id)) {
							echo '<span class="badge bg-success">Enabled</span> ';
						}
						else {
							echo '<span class="badge bg-danger">Disabled</span> ';
						}

						if ($toolPage->hasCustomizations()) {
							echo '<span class="badge bg-light text-dark">Customized</span> ';
						}
						else {
							echo '<span class="badge bg-light text-dark">Not customized</span> ';
						}
					?></td>
                    <td class="right">
                        <div class="dropdown">
                            <button class="btn" type="button" data-bs-toggle="dropdown" data-bs-boundary="window">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16">
                                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="edit.php?id=<?php echo $tool->id; ?>">Edit</a></li>
                                <li><a class="dropdown-item" href="embed.php?id=<?php echo $tool->id; ?>">Generate embed</a></li>
                                <li><a class="dropdown-item" href="?toggle=<?php echo $tool->id; ?>"><?php
									echo (isToolEnabled($tool->id)) ? 'Disable' : 'Enable';
								?></a></li>
                            </ul>
                        </div>
                    </td>
				</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	</div>
</section>

<?php
$page->footer();
?>
