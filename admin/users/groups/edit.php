<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(9)->header('users');

$id = $_GET['id'];
if (!is_numeric($id)) die;

$q = $studio->sql->query("SELECT * FROM `groups` WHERE id = $id");
if ($q->num_rows !== 1) die("Group Not Found");

$group = $q->fetch_array();

function toToggleBool($int) {
    return (($int === "1") ? "On" : "Off");
}

if (isset($_POST['name']) && !DEMO) {
    $name = $_POST['name'];
    $a = ($_POST['access-tools'] == "On") ? 1 : 0;
    $b = ($_POST['add-sites'] == "On") ? 1 : 0;
    $c = ($_POST['delete-sites'] == "On") ? 1 : 0;
    $d = ($_POST['record-tool-usage'] == "On") ? 1 : 0;
    $e = ($_POST['admin-access'] == "On") ? 1 : 0;

    if ($p = $studio->sql->prepare("UPDATE `groups` SET name = ?, `access-tools`=$a, `add-sites`=$b, `delete-sites`=$c, `record-tool-usage`=$d, `admin-access`=$e WHERE id = $id")) {
        $p->bind_param("s", $name);
        $p->execute();

        header("Location: edit.php?id=$id&success=1");
        die;
    }
}

$o = $studio->sql->query("SELECT COUNT(*) FROM accounts WHERE groupId='{$group['id']}'");
$r = $o->fetch_array();
$rows = number_format($r[0]);

if ($rows == 0 && isset($_GET['remove'])) {
    if ($_GET['remove'] == "confirm" && !DEMO) {
        $studio->sql->query("DELETE FROM `groups` WHERE id = $id");
        header("Location: index.php?success=1");
        die;
    }
    else {
?>
<div class="panel">
    <h3>Are you sure you want to delete this group?</h3>
    <p style="margin-bottom: 20px;">You will not be able to restore it.</p>

    <a class="btn red" href="?id=<?php echo $id; ?>&remove=confirm">Delete group</a> <a class="btn" href="?id=<?php echo $id; ?>">Cancel</a>
</div>
<?php
    }
}
?>
<form action="" method="POST">

    <div class="panel">
        <h3>Group Name</h3>

        <input type="text" class="fancy" name="name" value="<?php echo $group['name']; ?>" style="margin: 0;">
    </div>

    <div class="panel settings">
        <h3>Permissions</h3>

        <div class="setting">
            <table>
                <tr>
                    <td width="50%">
                        Can use tools
                    </td>
                    <td>
                        <div class="toggle">
                            <input type="hidden" name="access-tools" value="<?php echo toToggleBool($group['access-tools']); ?>">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="setting">
            <table>
                <tr>
                    <td width="50%">
                        Can add websites
                    </td>
                    <td>
                        <div class="toggle">
                            <input type="hidden" name="add-sites" value="<?php echo toToggleBool($group['add-sites']); ?>">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="setting">
            <table>
                <tr>
                    <td width="50%">
                        Can delete their websites
                    </td>
                    <td>
                        <div class="toggle">
                            <input type="hidden" name="delete-sites" value="<?php echo toToggleBool($group['delete-sites']); ?>">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="setting">
            <table>
                <tr>
                    <td width="50%">
                        Record tool usage
                    </td>
                    <td>
                        <div class="toggle">
                            <input type="hidden" name="record-tool-usage" value="<?php echo toToggleBool($group['record-tool-usage']); ?>">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="setting">
            <table>
                <tr>
                    <td width="50%">
                        Can access & use admin panel
                    </td>
                    <td>
                        <div class="toggle">
                            <input type="hidden" name="admin-access" value="<?php echo toToggleBool($group['admin-access']); ?>">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel">
        <input type="submit" class="btn blue" value="Save">
        <a class="btn gray" href="index.php">Back to groups</a>
    </div>

    <div class="panel">
        <h3>Delete this group</h3>
        <p>You can delete this group if there's no users assigned to it. <?php
        if ($rows > 0) echo "<strong>There are $rows users assigned to this group.</strong>";
        ?></p>

        <?php if ($rows == 0) { ?>
            <a style="margin: 20px 0 0;" class="btn red" href="?id=<?php echo $group['id']; ?>&remove">Delete group</a>
        <?php } ?>
    </div>

</form>

<?php
$page->footer();
?>
