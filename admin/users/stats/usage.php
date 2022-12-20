<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(9)->header('users');

# Get total number of rows

$q = $studio->sql->query("SELECT COUNT(*) FROM history");
$r = $q->fetch_array();
$total = $r[0];

# Pagination

$curPage = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1);
$pagination = new Studio\Display\Pagination($curPage, 20, $total);
$p = $pagination->getQuery();

# Sorting

$sort = "id DESC";

# Run the query

$q = $studio->sql->query("SELECT * FROM history ORDER BY $sort $p");
$users = array();
?>

<section>
    <div class="table-heading">
        <div class="title">
            <h1>Tool usage</h1>
            <p>
                <?php echo $total; ?>
                result<?php echo $total !== 1 ? 's' : ''; ?>
            </p>
        </div>
        <div class="actions align-self-end">
			<?php
				$pagination->show("m-0");
			?>
        </div>
    </div>

    <div class="table-responsive-md">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="60px" class="center">Id</th>
                    <th>User</th>
                    <th>Tool</th>
                    <th>Domain</th>
                    <th>Extra info</th>
                    <th width="150px" class="right">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $q->fetch_array()) {
                ?>
                <tr>
                    <td class="center"><?php echo $row['id']; ?></td>
                    <td>
                        <?php

                        if ($row['userId'] > 0) {
                            $userId = $row['userId'];
                            if (!isset($users[$userId])) {
                                $o = $studio->sql->query("SELECT email, id FROM accounts WHERE id='$userId'");

                                if ($o->num_rows == 0) echo "Unknown";
                                else {
                                    $r = $o->fetch_array();
                                    $users[$userId] = $r['email'];

                                    echo "<a href=\"../user.php?id=$userId\">" . $r['email'] . "</a>";
                                }
                            }
                            else echo "<a href=\"../user.php?id=$userId\">" . $users[$userId] . "</a>";
                        }
                        else {
                            if (DEMO) echo 'Demo guest';
                            else echo $row['address'] . " (guest)";
                        }

                        ?>
                    </td>
                    <td><?php echo $row['toolId']; ?></td>
                    <td><?php echo DEMO ? 'hidden.xyz' : $row['domain']; ?></td>
                    <td><?php echo DEMO ? 'Tool input hidden on demo' : sanitize_html($row['data']); ?></td>
                    <td>
                        <div class="time right">
                            <?php echo (new \Studio\Display\TimeAgo($row['useTime']))->get(); ?>
                            <span data-time="<?php echo $row['useTime']; ?>"><i class="material-icons">access_time</i></span>
                        </div>
                    </td>
                </tr>
                <?php
                }

				if ($q->num_rows === 0) {
				?>
				<tr>
					<td colspan="6" class="center">
						Nothing to show.
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
</div>

<?php
$page->footer();
?>
