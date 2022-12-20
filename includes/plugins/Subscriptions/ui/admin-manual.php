<?php

if (isset($_GET['add'])) {
    $label = $_GET['add'];
    if ($plan_data['expires_on'] == 0) $plan_data['expires_on'] = time();
    $new = strtotime("+1 $label", $plan_data['expires_on']);

    $plan_data['expires_on'] = $new;
    $plan_data['active'] = true;
    if (!isset($plan_data['last_payment_on'])) $plan_data['last_payment_on'] = time();
    if (!isset($plan_data['subscribed_on'])) $plan_data['subscribed_on'] = time();
    if (!isset($plan_data['duration'])) $plan_data['duration'] = "onetime";

    $this->setopt("sub-user-$user_id", json_encode($plan_data));

    header("Location: user.php?id=$user_id");
    die;
}
if (isset($_GET['set'])) {
    if ($_GET['set'] == "now") {
        $new = time();

        $plan_data['expires_on'] = $new;
        $plan_data['active'] = false;
        if (!isset($plan_data['last_payment_on'])) $plan_data['last_payment_on'] = time();
        if (!isset($plan_data['subscribed_on'])) $plan_data['subscribed_on'] = time();
        if (!isset($plan_data['duration'])) $plan_data['duration'] = "onetime";

        $this->setopt("sub-user-$user_id", json_encode($plan_data));

        header("Location: user.php?id=$user_id");
        die;
    }
    if ($_GET['set'] == "0") {
        $new = 0;

        $plan_data['expires_on'] = $new;
        $plan_data['active'] = true;
        if (!isset($plan_data['last_payment_on'])) $plan_data['last_payment_on'] = time();
        if (!isset($plan_data['subscribed_on'])) $plan_data['subscribed_on'] = time();
        if (!isset($plan_data['duration'])) $plan_data['duration'] = "onetime";

        $this->setopt("sub-user-$user_id", json_encode($plan_data));

        header("Location: user.php?id=$user_id");
        die;
    }
}
?>
<div class="panel">
    <h3>Subscription Plan <a href="sub-plans.php?edit=<?php echo $plan['id']; ?>">(<?php echo sanitize_html($plan['name']); ?>)</a></h3>

    <p style="margin: 0 0 5px;">Change expiration date:</p>
    <a class="btn tiny" href="?id=<?php echo $user_id; ?>&submanual&add=month">+1 month</a>
    <a class="btn tiny" href="?id=<?php echo $user_id; ?>&submanual&add=year">+1 year</a>
    <a class="btn tiny" href="?id=<?php echo $user_id; ?>&submanual&set=0">Never expire</a>
    <a class="btn tiny red" href="?id=<?php echo $user_id; ?>&submanual&set=now">Expire now</a>

    <br><br>

    <p>Modifying the subscription here will not stop the PayPal Subscription from auto-renewing. You can search "<strong>ID <?php echo $user['id']; ?></strong>" in PayPal to find the subscription.</p>
</div>
