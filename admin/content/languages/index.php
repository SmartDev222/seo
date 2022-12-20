<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

$q = $studio->sql->query("SELECT * FROM languages ORDER BY id ASC");

if (isset($_POST['default']) && !DEMO) {
    $default = $_POST['default'];
    if (!is_numeric($default)) die;

    $checkq = $studio->sql->query("SELECT * FROM languages WHERE id = $default");
    if ($checkq->num_rows !== 1) die;
    $r = $checkq->fetch_array();

    $studio->setopt("default-language", $r['locale']);

    header("Location: index.php");
    die;
}
?>

<section>
    <div class="table-heading">
        <div class="title">
            <h1>Manage languages</h1>
            <!-- <p>
                <?php echo $q->num_rows; ?>
                result<?php $q->num_rows !== 1 ? 's' : ''; ?>
            </p> -->
        </div>

        <div class="actions">
            <a class="btn btn-sm btn-primary" href="create.php">
                Create new
            </a>
        </div>
    </div>

    <div class="table-responsive-md">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="80px" class="center">Id</th>
                    <th width="240px">Name</th>
                    <th width="130px">Locale</th>
                    <th>Status</th>
                    <th width="90px"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $q->fetch_array()) {
                    $isDefault = $studio->getopt('default-language', 'en-us') === $row['locale'];
                ?>
                <tr>
                    <td class="center">
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="fill">
                            <?php echo $row['id']; ?>
                        </a>
                    </td>
                    <td>
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="fill">
                            <?php echo $row['name']; ?>
                        </a>
                    </td>
                    <td><?php echo $row['locale']; ?></td>
                    <td class="text-nowrap">
                        <span class="badge bg-success">Active</span>
                        <?php if ($isDefault) { ?>
                            <span class="badge bg-primary">Default</span>
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
                                <li><a class="dropdown-item" href="edit.php?id=<?php echo $row['id']; ?>">Edit</a></li>
                                <?php if (!$isDefault) { ?>
                                <li>
                                    <form method="post" action="">
                                        <input type="hidden" name="default" value="<?php echo $row['id']; ?>">
                                        <button class="dropdown-item">Set as default</a>
                                    </form>
                                </li>
                                <?php } ?>
                                <li><a class="dropdown-item text-danger" href="edit.php?id=<?php echo $row['id']; ?>&remove">Delete</a></li>
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
