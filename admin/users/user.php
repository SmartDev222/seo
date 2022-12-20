<?php
require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(9)->header('users');

$id = $_GET['id'];
if (!is_numeric($id)) $studio->showFatalError("Invalid parameter");

$q = $studio->sql->query("SELECT * FROM accounts WHERE id = $id");
if ($q->num_rows == 0) {
    header("Location: index.php");
    die;
}

$user = $q->fetch_array();

// Get group

$gid = $user['groupId'];
$o = $studio->sql->query("SELECT * FROM `groups` WHERE id='$gid'");
if ($o->num_rows == 0) $studio->showFatalError("Unknown group $gid");
$group = $o->fetch_array();

if ($group['admin-access']) $icon = "gavel";
else $icon = "account_circle";

// Delete websites

if (isset($_GET['deletesite']) && !DEMO) {
    $siteId = $_GET['deletesite'];

    $p = $studio->sql->prepare("DELETE FROM websites WHERE userId = {$id} AND id = ?;");
    $p->bind_param("i", $siteId);
    $p->execute();
    $p->close();
}

if (isset($_GET['verify']) && !DEMO) {
    $studio->sql->query("UPDATE accounts SET verified = 1 WHERE id = {$id}");
    $user['verified'] = 1;
}

?>

<div class="panel">
    <div class="float-right">
        <span class="time-label">
            Last seen:
            <div class="time right">
                <?php echo (new \Studio\Display\TimeAgo($user['timeLastLogin']))->get(); ?>
                <span data-time="<?php echo $user['timeLastLogin']; ?>"><i class="material-icons">access_time</i></span>
            </div>
        </span>
        <span class="time-label">
            Joined:
            <div class="time right">
                <?php echo (new \Studio\Display\TimeAgo($user['timeCreated']))->get(); ?>
                <span data-time="<?php echo $user['timeCreated']; ?>"><i class="material-icons">access_time</i></span>
            </div>
        </span>
        <div class="user-controls">
            <?php if ($user['verified'] == 0) { ?>
            <a class="btn small" href="?id=<?php echo $id; ?>&verify">Confirm email</a>
            <?php } ?>
            <a class="btn small" href="?id=<?php echo $id; ?>&password">New password</a>
            <a class="btn small" href="?id=<?php echo $id; ?>&email">Edit email</a>
        	<a class="btn small" href="?id=<?php echo $id; ?>&group">Edit group</a>
            <a class="btn small red" href="?id=<?php echo $id; ?>&remove">Delete</a>
        </div>
    </div>

    <div class="float-left icon">
        <i class="material-icons"><?php echo $icon; ?></i>
    </div>
    <div class="float-left text">
        <h3><?php echo $user['email']; ?></h3>
        <p><?php echo $group['name']; ?> user</p>
    </div>
</div>

<?php

// Delete

if (isset($_GET['remove'])) {
    if ($_GET['remove'] == "confirm" && !DEMO) {
        $studio->sql->query("DELETE FROM history WHERE userId=$id");
        $studio->sql->query("DELETE FROM websites WHERE userId=$id");
        $studio->sql->query("DELETE FROM accounts WHERE id=$id");

        $plugins->call("admin_delete_user", [$id]);

        header("Location: index.php");
        die;
    }
    else {
?>
<div class="panel">
    <h3>Confirm deletion</h3>
    <p style="margin-bottom: 20px;">Are you sure you want to delete this user? This will erase their saved data including websites and tool usage history. It cannot be undone.</p>

    <a class="btn red" href="?id=<?php echo $id; ?>&remove=confirm">Delete account</a> <a class="btn" href="?id=<?php echo $id; ?>">Cancel</a>
</div>
<?php
    }
}

// Edit email

if (isset($_GET['email'])) {
    if (isset($_POST['newEmail']) && !DEMO) {
        if ($p = $studio->sql->prepare("UPDATE accounts SET email=? WHERE id=$id")) {
            $p->bind_param("s", strtolower(trim($_POST['newEmail'])));
            $p->execute();

            header("Location: user.php?id=$id");
            die;
        }
    }
?>
<div class="panel">
    <h3>Enter new email</h3>
    <form action="" method="post">
        <input type="text" name="newEmail" class="fancy" value="<?php echo $user['email']; ?>">
        <input type="submit" class="btn blue" value="Update">
    </form>
</div>
<?php
}

// Edit group

if (isset($_GET['group'])) {
    if (isset($_POST['group']) && !DEMO) {
        if ($p = $studio->sql->prepare("UPDATE accounts SET groupId=? WHERE id=$id")) {
            $p->bind_param("i", strtolower(trim($_POST['group'])));
            $p->execute();

            header("Location: user.php?id=$id");
            die;
        }
    }
?>
<div class="panel">
    <h3>Choose a group</h3>
    <form action="" method="post">
        <select class="fancy" name="group">
            <?php
            $o = $studio->sql->query("SELECT * FROM `groups` ORDER BY id ASC");
            while ($row = $o->fetch_array()) {
                $selected = (($user['groupId'] == $row['id']) ? "selected" : "");
                echo "<option value=\"{$row['id']}\" $selected>{$row['name']}</option>";
            }
            ?>
        </select>
        <input type="submit" class="btn blue" value="Update">
    </form>
</div>
<?php
}

// Edit password

if (isset($_GET['password'])) {
    if (isset($_POST['newpw']) && !DEMO) {
        if ($p = $studio->sql->prepare("UPDATE accounts SET password=? WHERE id=$id")) {
            $password = $_POST['newpw'];
            $salt = str_replace("+", ".", substr(base64_encode(rand(111111,999999).rand(111111,999999).rand(11111,99999)), 0, 22));
            $salt = '$' . implode('$', array("2y", str_pad(11, 2, "0", STR_PAD_LEFT), $salt));
            $password = @crypt($password, $salt);

            if (!$password) $password = @crypt($password);
            if (!$password) $password = md5($password);

            $p->bind_param("s", $password);
            $p->execute();

            header("Location: user.php?id=$id");
            die;
        }
    }
?>
<div class="panel">
    <h3>Enter new password</h3>
    <form action="" method="post">
        <input type="password" name="newpw" class="fancy" value="">
        <input type="submit" class="btn blue" value="Update">
    </form>
</div>
<?php
}

// New website

if (isset($_GET['addsite'])) {
    if (isset($_POST['url']) && !DEMO) {
        if ($p = $studio->sql->prepare("UPDATE accounts SET password=? WHERE id=$id")) {
            $url = strtolower(trim($_POST['url']));

            try {
                $url = new \SEO\Helper\Url($url);
                $domain = str_replace("www.", "", $url->domain);

                $time = time();
                $p = $studio->sql->prepare("INSERT INTO websites (userId, domain, timeCreated, timeAccessed) VALUES (?, ?, $time, $time);");
                $p->bind_param("is", $id, $domain);
                $p->execute();

                header("Location: user.php?id=$id");
                die;
            }
            catch (\Exception $e) {
                $studio->showError("Invalid website");
            }
        }
    }
?>
<div class="panel">
    <h3>Add new website</h3>
    <form action="" method="post">
        <input type="text" name="url" class="fancy" value="" placeholder="Enter URL or domain name">
        <input type="submit" class="btn blue" value="Add">
    </form>
</div>
<?php
}

$plugins->call("admin_user_profile", [$user['id']]);
?>

<div class="panel">
    <div class="pull-right">
        <a class="btn small" style="margin-top: -4px;" href="?id=<?php echo $id; ?>&addsite">Add new</a>
    </div>

    <h3>Websites</h3>

    <div class="table-container scroll">
        <table class="table">
            <thead>
                <tr>
                    <th class="center" width="60px">Id</th>
                    <th>Domain</th>
                    <th class="right" width="150px">Last used</th>
                    <th class="right" width="150px">Date created</th>
                    <th class="right" width="115px">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = $studio->sql->query("SELECT * FROM websites WHERE userId={$user['id']} ORDER BY id ASC");

                while ($row = $q->fetch_array()) {
                ?>
                <tr>
                    <td class="center"><?php echo $row['id']; ?></td>
                    <td><?php echo $row['domain']; ?></td>
                    <td class="right">
                        <div class="time right">
                            <?php echo (new \Studio\Display\TimeAgo($row['timeAccessed']))->get(); ?>
                            <span data-time="<?php echo $row['timeAccessed']; ?>"><i class="material-icons">access_time</i></span>
                        </div>
                    </td>
                    <td class="right">
                        <div class="time right">
                            <?php echo (new \Studio\Display\TimeAgo($row['timeCreated']))->get(); ?>
                            <span data-time="<?php echo $row['timeCreated']; ?>"><i class="material-icons">access_time</i></span>
                        </div>
                    </td>
                    <td class="right">
                        <a class="btn tiny" href="?id=<?php echo $id; ?>&deletesite=<?php echo $row['id']; ?>">Delete</a>
                    </td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <h3>Recent Activity</h3>

    <div class="table-container scroll">
        <table class="table thin">
            <thead>
                <tr>
                    <th class="center" width="60px">Id</th>
                    <th>Tool</th>
                    <th>Domain</th>
                    <th>Extra info</th>
                    <th class="right" width="150px">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $minTime = time() - (86400 * 31);
                $q = $studio->sql->query("SELECT * FROM history WHERE userId={$user['id']} AND useTime > $minTime ORDER BY id DESC LIMIT 300");

                while ($row = $q->fetch_array()) {
                ?>
                <tr>
                    <td class="center"><?php echo $row['id']; ?></td>
                    <td><?php echo $row['toolId']; ?></td>
                    <td><?php echo DEMO ? 'Hidden on demo' : $row['domain']; ?></td>
                    <td><?php echo DEMO ? 'Hidden on demo' : $row['data']; ?></td>
                    <td class="right">
                        <div class="time right">
                            <?php echo (new \Studio\Display\TimeAgo($row['useTime']))->get(); ?>
                            <span data-time="<?php echo $row['useTime']; ?>"><i class="material-icons">access_time</i></span>
                        </div>
                    </td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$page->footer();
?>
