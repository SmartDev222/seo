<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(2)->setTitle("Activity Log")->header();

if (DEMO) die;

# Get total number of rows

$q = $studio->sql->query("SELECT COUNT(*) FROM activity");
$r = $q->fetch_array();
$total = $r[0];

# Pagination

$curPage = ((isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1);
$pagination = new Studio\Display\Pagination($curPage, 30, $total);
$p = $pagination->getQuery();

# Sorting

$sort = "id DESC";

# Run the query

$q = $studio->sql->query("SELECT * FROM activity ORDER BY $sort $p");
$users = array();
?>

<div class="panel">
    <div class="pull-right">
        <?php
        $pagination->show("right tmfix");
        ?>
    </div>
    <h3>Latest Activity</h3>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Message</th>
                    <th width="150px" class="right">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $q->fetch_array()) {
                ?>
                <tr class="activity activity-<?php echo $row['type']; ?>">
                    <td>
                        <?php
                        echo sanitize_html($row['message']);
                        ?>
                    </td>
                    <td>
                        <div class="time right">
                            <?php echo (new \Studio\Display\TimeAgo($row['time']))->get(); ?>
                            <span data-time="<?php echo $row['time']; ?>"><i class="material-icons">access_time</i></span>
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
</div>

<?php
$page->footer();
?>
