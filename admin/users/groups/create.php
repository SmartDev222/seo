<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(9)->header('users');

if (isset($_POST['name']) && !DEMO) {
    $name = $_POST['name'];
    $a = ($_POST['access-tools'] == "On") ? 1 : 0;
    $b = ($_POST['add-sites'] == "On") ? 1 : 0;
    $c = ($_POST['delete-sites'] == "On") ? 1 : 0;
    $d = ($_POST['record-tool-usage'] == "On") ? 1 : 0;
    $e = ($_POST['admin-access'] == "On") ? 1 : 0;
    $f = 0;

    if ($p = $studio->sql->prepare("INSERT INTO `groups` (`name`, `access-tools`, `add-sites`, `delete-sites`, `record-tool-usage`, `admin-access`, `allow-comparing`) VALUES (?, $a, $b, $c, $d, $e, $f);")) {
        $p->bind_param("s", $name);
        $p->execute();

        header("Location: index.php");
        die;
    }
}
?>
<form action="" method="POST">
    <div class="heading">
        <h1>New group</h1>
        <h2>Roles and permissions</h2>
    </div>

    <div class="panel">
        <h3>Group Name</h3>

        <input type="text" class="fancy" name="name" value="" style="margin: 0;">
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
                            <input type="hidden" name="access-tools" value="On">
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
                            <input type="hidden" name="add-sites" value="On">
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
                            <input type="hidden" name="delete-sites" value="On">
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
                            <input type="hidden" name="record-tool-usage" value="On">
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
                            <input type="hidden" name="admin-access" value="Off">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel">
        <input type="submit" class="btn blue" value="Create Group">
        <a class="btn" href="index.php">Cancel</a>
    </div>

</form>

<?php
$page->footer();
?>
