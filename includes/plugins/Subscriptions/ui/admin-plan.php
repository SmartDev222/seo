<?php

if (isset($_POST['groupId'])) {
    $new = (int)$_POST['groupId'];
    $studio->sql->query("UPDATE accounts SET groupId=$new WHERE id = $user_id");
    header("Location: user.php?id=$user_id");
    die;
}

?>
<div class="panel">
    <h3>Subscription Plan <a href="sub-plans.php?edit=<?php echo $plan['id']; ?>">(<?php echo sanitize_html($plan['name']); ?>)</a></h3>

    <form action="" method="post">
        <p style="margin: 0 0 5px;">Set new plan:</p>
        <select class="fancy" name="groupId">
            <?php foreach ($plans as $p) { ?>
                <option value="<?php echo $p['assign']; ?>" <?php
                    if ($p['assign'] == $row->groupId) echo " selected";
                ?>><?php echo $p['name']; ?></option>
            <?php } ?>
        </select>

        <input type="submit" class="btn blue" value="Save">
    </form>
</div>
