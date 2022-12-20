<?php
require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(9)->header('users');

if (isset($_POST['email']) && !DEMO) {
    $email = trim(strtolower($_POST['email']));
    $password = trim($_POST['password']);
    $group = $_POST['group'];

    if (!is_numeric($group)) die;

    if ($p = $studio->sql->prepare("SELECT email FROM accounts WHERE email = ?")) {
        $p->bind_param("s", $email);
        $p->execute();
        $p->store_result();

        if ($p->num_rows > 0) $studio->showError("Email already exists.");
        else {
            $time = time();

            if ($p = $studio->sql->prepare("INSERT INTO accounts (email, password, timeLastLogin, timeCreated, groupId) VALUES (?, ?, {$time}, {$time}, $group);")) {
                # Generate a secure blowfish salt and hash the password
                # Note: Blowfish is only supported on PHP 5.3.7+

                $salt = str_replace("+", ".", substr(base64_encode(rand(111111,999999).rand(111111,999999).rand(11111,99999)), 0, 22));
                $salt = '$' . implode('$', array("2y", str_pad(11, 2, "0", STR_PAD_LEFT), $salt));
                $password = @crypt($password, $salt);

                if (!$password) $password = @crypt($password);
                if (!$password) $password = md5($password);

                $p->bind_param("ss", $email, $password);
                $p->execute();

                if ($studio->sql->affected_rows == 1) {
                    $insert_id = $studio->sql->insert_id;

                    $p->close();

                    $studio->addActivity(new Studio\Common\Activity(
                        Studio\Common\Activity::INFO,
                        "New account created by admin (" . $account->getEmail() . "), email " . $email . ", from " . $_SERVER['REMOTE_ADDR']
                    ));

                    $plugins->call("admin_new_user", [$insert_id]);

                    header("Location: index.php?success=1");
                    die;
                }
                else $studio->showError("Database error #1");
            }
            else $studio->showError("Database error #2");
        }
    }
}
?>

<div class="panel">
    <form action="" method="post">
        <p>Email</p>
        <input type="text" name="email" class="fancy" value="">

        <p>Password</p>
        <input type="password" name="password" class="fancy" value="">

        <p>Group</p>
        <select class="fancy" name="group">
            <?php
            $o = $studio->sql->query("SELECT * FROM `groups` ORDER BY id ASC");
            while ($row = $o->fetch_array()) {
                echo "<option value=\"{$row['id']}\">{$row['name']}</option>";
            }
            ?>
        </select>

        <?php $plugins->call("admin_new_user_form"); ?>

        <input type="submit" class="btn blue" value="Create">
        <a class="btn gray" href="index.php">Cancel</a>
    </form>
</div>

<?php
$page->footer();
?>
