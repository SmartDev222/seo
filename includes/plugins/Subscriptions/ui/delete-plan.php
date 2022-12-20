<?php
$users = 0;

foreach ($plan['groups'] as $group_id) {
    $q = $studio->sql->query("SELECT COUNT(*) FROM accounts WHERE groupId = $group_id");
    $r = $q->fetch_array();
    $users += $r[0];
}

if (isset($_POST['go'])) {
    if ($users > 0) {
        if (!isset($_POST['newplan'])) {
            die("There are users still on this plan.");
        }

        $new_id = (int)$_POST['newplan'];
        foreach ($plan['groups'] as $group_id) {
            $studio->sql->query("UPDATE accounts SET groupId = $new_id WHERE groupId = $group_id AND id > 1");
        }
    }

    $newplans = [];
    foreach ($plans as $i => $v) {
        if ($i == $id) continue;
        $newplans[] = $v;
    }

    $studio->setopt("sub-plans", json_encode($newplans));
    header("Location: sub-plans.php");
    die;
}
?>
<form action="" method="post">
    <div class="panel">
        <h3>Are you sure?</h3>
        <p>You're about to delete the <strong><?php echo $plan['name']; ?></strong> plan from your system.</p>

        <?php


        if ($users > 0) {
        ?>
        <br>
        <p>You have <strong><?php echo $users; ?></strong> users on this plan. Please choose a new plan for these users:</p>
        <select name="newplan" class="fancy">
            <?php
            foreach ($plans as $i => $v) {
                if ($i == $id) continue;
                echo "<option value='".$v['assign']."'>" . $v['name'] . "</option>";
            }
            ?>
        </select>
        <?php
        }
        ?>

        <br>
        <input type="submit" name="go" class="btn red" value="Confirm deletion">
        <a class="btn" href="sub-plans.php">Cancel</a>
    </div>
</form>
