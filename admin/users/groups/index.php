<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(9)->header('users');

$q = $studio->sql->query("SELECT * FROM `groups` ORDER BY id ASC");
?>

<section>
    <div class="table-heading">
        <div class="title">
            <h1>Groups</h1>
            <p>
                <?php echo $q->num_rows; ?>
                result<?php echo $q->num_rows !== 1 ? 's' : ''; ?>
            </p>
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
                    <th width="60px" class="center">Id</th>
                    <th>Name</th>
					<th>Permissions</th>
                    <th width="100px" class="center">Users</th>
                    <th width="100px"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $q->fetch_array()) {
                ?>
                <tr>
                    <td class="center"><?php echo $row['id']; ?></td>
                    <td><a class="fill" href="edit.php?id=<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a></td>
					<td><?php
						if ($row['admin-access']) echo '<span class="badge bg-primary">Admin</span> ';
						if ($row['access-tools']) echo '<span class="badge bg-light text-dark">Access tools</span> ';
						if ($row['add-sites']) echo '<span class="badge bg-light text-dark">Add sites</span> ';
						if ($row['delete-sites']) echo '<span class="badge bg-light text-dark">Delete sites</span> ';
						if ($row['record-tool-usage']) echo '<span class="badge bg-light text-dark">Record usage</span> ';
					?></td>
                    <td class="center"><?php

                    $o = $studio->sql->query("SELECT COUNT(*) FROM accounts WHERE groupId='{$row['id']}'");
                    $r = $o->fetch_array();
                    echo number_format($r[0]);

                    ?></td>
                    <td class="right">
                        <div class="dropdown">
                            <button class="btn" type="button" data-bs-toggle="dropdown" data-bs-boundary="window">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16">
                                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="edit.php?id=<?php echo $row['id']; ?>">Edit</a></li>
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
