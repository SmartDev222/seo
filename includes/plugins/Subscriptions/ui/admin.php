<div class="panel">
    <h3>Subscription Plan <a href="sub-plans.php?edit=<?php echo $plan['id']; ?>">(<?php echo $plan['name']; ?>)</a></h3>

    <?php
    if (isset($plan_data)) {
    ?>
    <ul>
        <li><?php
        if ($plan_data['active']) echo "Active";
        else echo "Awaiting payment";
        ?></li>
        <li><?php
        if ($plan_data['active']) {
            if ($plan_data['expires_on'] == 0) echo "Never expires";
            else echo "Expires on " . date("F j, Y", $plan_data['expires_on']);
        }
        else echo "Subscription is not paid for, user cannot use tools at this time.";
        ?></li><?php
        if ($plan_data['active']) {
        ?>
        <li>First subscribed on <?php echo date("F j, Y", $plan_data['subscribed_on']); ?></li>
        <li>Last payment received on <?php if (isset($plan_data['last_payment_on'])) echo date("F j, Y", $plan_data['last_payment_on']); else echo "(never)"; ?></li>
        <li><?php
        if ($plan_data['duration'] == "onetime") echo "Submitted one-time payment.";
        if ($plan_data['duration'] == "monthly") echo "Paying monthly.";
        if ($plan_data['duration'] == "annually") echo "Paying yearly.";
        ?></li>
        <?php
        }?>
    </ul>
    <br>
    <a class="btn" href="?id=<?php echo $user_id; ?>&submanual">Manual adjust</a>
    <?php
    }
    else {
    ?>
    <p>This user is not connected to a subscription plan.</p>

    <?php
    }
    if ($user_id > 1) {
    ?>
    <a class="btn blue" href="?id=<?php echo $user_id; ?>&subplan">Change plan</a>
    <?php
    } else echo "<p>You cannot add this user to a plan.</p>";
    ?>
</div>
