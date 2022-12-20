<?php
$plan = null;
$plans = json_decode($this->getopt("sub-plans"), true);

foreach ($plans as $p) {
    if (in_array($account->groupId, $p['groups'])) {
        $plan = $p;
    }
}

$billingData = json_decode($this->getopt("sub-user-" . $account->getId()), true);

if (isset($_GET['ps'])) {
?>

<div class="billing-panel green">
    <h3>Payment successful!</h3>
    <p>Your account will be switched to the new plan within the next few minutes.</p>
</div>

<?php
}
if (isset($_GET['switchplan'])) {
    $to = (int)$_GET['switchplan'] - 1;
    if (!isset($plans[$to])) die;

    if (isset($billingData['expires_on']) && $billingData['expires_on'] > time()) {
?>

<div class="billing-panel red">
    <h3><?php pt("Cannot switch plans"); ?></h3>
    <p><?php pt("You cannot switch plans while you have an active subscription. Please cancel your subscription at PayPal and wait for it to expire."); ?></p>
</div>

<?php
    }
    else {
        $user_id = $account->getId();
        $new = $plans[$to];
        $group_id = $new['assign'];

        if (!$page->hasPermission("admin-access")) {

            if ($new['cost']['onetime'] == 0 && $new['cost']['monthly'] == 0 && $new['cost']['annually'] == 0) {
                $this->setopt("sub-user-$user_id", json_encode([
                    "active" => true,
                    "expires_on" => 0,
                    "subscribed_on" => time(),
                    "duration" => "onetime"
                ]));
            }
            else {
                $this->setopt("sub-user-$user_id", json_encode([
                    "active" => false
                ]));
            }

            $studio->sql->query("UPDATE accounts SET groupId = $group_id WHERE id = $user_id");

            header("Location: index.php");
            die;
        }
        else {
?>
<div class="billing-panel red">
    <h3><?php pt("Cannot upgrade"); ?></h3>
    <p><?php pt("Admin accounts cannot join a plan. You always have access to all tools."); ?></p>
</div>
<?php
        }
    }
}
?>
<div class="billing-panel">
    <h3><?php
        if ($plan) {
            echo sanitize_html($plan['name']);
            echo sanitize_html(rt('Plan'));
        }
        else {
            echo sanitize_html(rt("You're not assigned to a plan"));
        }
    ?></h3>

    <?php
    if ($page->hasPermission("admin-access")) {
        echo "<p>".rt("Admins cannot be assigned to plans. You have access to all tools.")."</p>";
    }
    elseif (!$billingData || !$billingData['active']) {
        echo "<p>".rt("Your subscription is not active. Please submit payment to activate it.")."</p><br>";

        if (isset($_POST['pay'])) {
            $pay = $_POST['pay'];
            require __DIR__ . "/paypal.php";

            $studio->stop();
        }
    ?>
    <form action="" method="post">
        <select name="pay">
            <?php
            if ($plan['cost']['onetime'] != "0") echo "<option value='onetime'>".rt("Buy permanent access")." @ " . $studio->getopt("sub-currency-symbol") . $plan['cost']['onetime'] . "</option>";
            if ($plan['cost']['monthly'] != "0") echo "<option value='monthly'>".rt("Pay monthly")." @ " . $studio->getopt("sub-currency-symbol") . $plan['cost']['monthly'] . " ".rt("/mo")."</option>";
            if ($plan['cost']['annually'] != "0") echo "<option value='annually'>".rt("Pay yearly")." @ " . $studio->getopt("sub-currency-symbol") . $plan['cost']['annually'] . " ".rt("/yr")."</option>";
            ?>
        </select>

        <input type="submit" class="btn" value="<?php pt("Pay now"); ?>">
        </form>
    <?php
    }
    else {
    ?>
	<ul>
		<?php if ($billingData['expires_on'] > 0) { ?>
		<li><?php
            $tr = rt("Renews on {expire_date} for {currency_symbol}{plan_cost}");
            $tr = str_replace("{expire_date}", date("F j, Y", $billingData['expires_on']), $tr);
            $tr = str_replace("{currency_symbol}", $studio->getopt("sub-currency-symbol"), $tr);
            $tr = str_replace("{plan_cost}", $plan['cost'][($billingData['duration'])], $tr);

            echo sanitize_trusted($tr);
		?></li>
		<li><?php
            $tr = rt("You can cancel your subscription at {paypal_link}.");
            $tr = str_replace("{paypal_link}", '<a href="https://paypal.com/">'.rt("paypal.com").'</a>', $tr);
            echo sanitize_trusted($tr);
        ?></li>
		<?php } else { ?>
		<li><?php pt("Never expires"); ?></li>
		<?php } ?>
	</ul>
    <?php
    }
    ?>
</div>
