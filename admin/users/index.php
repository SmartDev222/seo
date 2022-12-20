<?php
require "../includes/init.php";

if (isset($_GET['export'])) {
    $q = $studio->sql->query("SELECT id, email FROM accounts ORDER BY id ASC");
    $csv = "Id,Email\n";

    while ($row = $q->fetch_array()) {
        $csv .= sprintf("\"%s\",\"%s\"\n", $row['id'], $row['email']);
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=studio-users.csv');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($csv));
    flush();
    echo $csv;
    die;
}

$page->setPath("../../")->requirePermission('admin-access')->setPage(9)->header('users');

# Get total number of rows

$q = $studio->sql->query("SELECT COUNT(*) FROM accounts");
$r = $q->fetch_array();
$total = $r[0];

# Pagination

$curPage = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1);
$pagination = new Studio\Display\Pagination($curPage, 15, $total);
$p = $pagination->getQuery();

# Sorting

$sort = "id ASC";

$q = $studio->sql->query("SELECT * FROM accounts ORDER BY $sort $p");
$groups = array();
$admins = array();
?>

<section>
    <div class="table-heading">
        <div class="title">
            <h1>Users</h1>
            <p>
                <?php echo $total; ?>
                result<?php echo $total !== 1 ? 's' : ''; ?>
            </p>
        </div>

        <div class="actions">
            <a class="btn btn-sm btn-light" href="?export">
                Export
            </a>
            <a class="btn btn-sm btn-primary" href="create.php">
                Create new
            </a>
        </div>
    </div>

    <div class="table-responsive-md">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="75px" class="center">Id</th>
                    <th>Email</th>
                    <th>Group</th>
                    <th width="180px">Status</th>
                    <th width="160px" class="right">Last Online</th>
                    <th width="160px" class="right">Date Created</th>
                    <th width="100px">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $q->fetch_array()) {
                ?>
                <tr>
                    <td class="center"><?php echo $row['id']; ?></td>
                    <td><a class="fill" href="user.php?id=<?php echo $row['id']; ?>"><?php echo $row['email']; ?></a></td>
                    <td><?php

                    $id = $row['groupId'];
					$isAdmin = false;

                    echo "<a class=\"fill\" href=\"group.php?id=$id\">";
                    if (isset($groups[$id])) {
						$isAdmin = $admins[$id];
						echo $groups[$id];
					}
                    else {
                        $o = $studio->sql->query("SELECT * FROM `groups` WHERE id='$id'");

                        if ($o->num_rows == 0) echo "No group";
                        else {
                            $r = $o->fetch_array();
							$isAdmin = $r['admin-access'] > 0;
                            $groups[$id] = $r['name'];
                            $admins[$id] = $isAdmin;
                            echo $r['name'];
                        }
                    }
                    echo "</a>";

                    ?></td>
                    <td class="text-nowrap">
						<?php if ($isAdmin) { ?>
                        	<span class="badge bg-primary">Admin</span>
						<?php } ?>

                        <?php if ($row['verified'] > 0) {?>
                        	<span class="badge bg-success">Verified</span>
                        <?php } else { ?>
                        	<span class="badge bg-light text-dark">Not verified</span>
                        <?php } ?>
                    </td>
                    <td>
                        <div class="time right">
                            <?php echo (new \Studio\Display\TimeAgo($row['timeLastLogin']))->get(); ?>
                            <span data-time="<?php echo $row['timeLastLogin']; ?>"><i class="material-icons">access_time</i></span>
                        </div>
                    </td>
                    <td>
                        <div class="time right">
                            <?php echo (new \Studio\Display\TimeAgo($row['timeCreated']))->get(); ?>
                            <span data-time="<?php echo $row['timeCreated']; ?>"><i class="material-icons">access_time</i></span>
                        </div>
                    </td>
                    <td class="right">
                        <div class="dropdown">
                            <button class="btn" type="button" data-bs-toggle="dropdown" data-bs-boundary="window">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots" viewBox="0 0 16 16">
                                    <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="user.php?id=<?php echo $row['id']; ?>">Edit</a></li>
                                <li><a class="dropdown-item text-danger" href="user.php?id=<?php echo $row['id']; ?>&remove">Delete</a></li>
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

    <?php
    $pagination->show("right no-margin-bottom");
    ?>
</section>

<?php
$page->footer();
?>
