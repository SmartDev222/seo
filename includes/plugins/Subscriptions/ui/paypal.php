<?php

if ($pay == "onetime") {
?>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" id="cbf0">
    <input type="hidden" name="cmd" value="_xclick">
    <input type="hidden" name="business" value="<?php echo $studio->getopt("sub-paypal-email"); ?>">
    <input type="hidden" name="charset" value="utf-8">
    <input type="hidden" name="no_shipping" value="1">
    <input type="hidden" name="rm" value="2">
    <input type="hidden" name="cbt" value="Finish">
    <input type="hidden" name="currency_code" value="<?php echo $studio->getopt("sub-currency"); ?>">
    <input type="hidden" name="return" value="<?php echo $studio->getopt("public-url"); ?>account/?ps=1">
    <input type="hidden" name="cancel_return" value="<?php echo $studio->getopt("public-url"); ?>">
    <input type="hidden" name="notify_url" value="<?php echo $studio->getopt("public-url"); ?>ipn.php">
    <input type="hidden" name="<email>" value="<?php echo $account->getEmail(); ?>">
    <input type="hidden" name="item_name" value="<?php echo $plan['name']; ?> <?php pt("Plan - Onetime"); ?> (ID <?php echo $account->getId(); ?>)">
    <input type="hidden" name="amount" value="<?php echo $plan['cost']['onetime']; ?>">
    <input type="hidden" name="custom" value="<?php echo $account->getId(); ?>,0,<?php echo md5($account->getEmail()); ?>">
</form>

<p><strong><?php pt("Please wait..."); ?></strong></p>

<script type="text/javascript">
    document.getElementById("cbf0").submit();
</script>

<?php
}

if ($pay == "monthly") {
?>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" id="cbf0">
    <input type="hidden" name="cmd" value="_xclick-subscriptions">
    <input type="hidden" name="business" value="<?php echo $studio->getopt("sub-paypal-email"); ?>">
    <input type="hidden" name="charset" value="utf-8">
    <input type="hidden" name="no_shipping" value="1">
    <input type="hidden" name="no_note" value="1">
    <input type="hidden" name="rm" value="2">
    <input type="hidden" name="currency_code" value="<?php echo $studio->getopt("sub-currency"); ?>">
    <input type="hidden" name="cbt" value="Finish">
    <input type="hidden" name="return" value="<?php echo $studio->getopt("public-url"); ?>account/?ps=1">
    <input type="hidden" name="cancel_return" value="<?php echo $studio->getopt("public-url"); ?>">
    <input type="hidden" name="notify_url" value="<?php echo $studio->getopt("public-url"); ?>ipn.php">
    <input type="hidden" name="<email>" value="<?php echo $account->getEmail(); ?>">
    <input type="hidden" name="item_name" value="<?php echo $plan['name']; ?> <?php pt("Plan - Monthly"); ?> (ID <?php echo $account->getId(); ?>)">
    <input type="hidden" name="a3" value="<?php echo $plan['cost']['monthly']; ?>">
    <input type="hidden" name="p3" value="1">
    <input type="hidden" name="t3" value="M">
    <input type="hidden" name="src" value="1">
    <input type="hidden" name="sra" value="1">
    <input type="hidden" name="custom" value="<?php echo $account->getId(); ?>,1,<?php echo md5($account->getEmail()); ?>">
</form>

<p><strong><?php pt("Please wait..."); ?></strong></p>

<script type="text/javascript">
    document.getElementById("cbf0").submit();
</script>

<?php
}

if ($pay == "annually") {
?>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" id="cbf0">
    <input type="hidden" name="cmd" value="_xclick-subscriptions">
    <input type="hidden" name="business" value="<?php echo $studio->getopt("sub-paypal-email"); ?>">
    <input type="hidden" name="charset" value="utf-8">
    <input type="hidden" name="no_shipping" value="1">
    <input type="hidden" name="no_note" value="1">
    <input type="hidden" name="rm" value="2">
    <input type="hidden" name="currency_code" value="<?php echo $studio->getopt("sub-currency"); ?>">
    <input type="hidden" name="cbt" value="Finish">
    <input type="hidden" name="return" value="<?php echo $studio->getopt("public-url"); ?>account/?ps=1">
    <input type="hidden" name="cancel_return" value="<?php echo $studio->getopt("public-url"); ?>">
    <input type="hidden" name="notify_url" value="<?php echo $studio->getopt("public-url"); ?>ipn.php">
    <input type="hidden" name="<email>" value="<?php echo $account->getEmail(); ?>">
    <input type="hidden" name="item_name" value="<?php echo $plan['name']; ?> <?php pt("Plan - Yearly"); ?> (ID <?php echo $account->getId(); ?>)">
    <input type="hidden" name="a3" value="<?php echo $plan['cost']['annually']; ?>">
    <input type="hidden" name="p3" value="1">
    <input type="hidden" name="t3" value="Y">
    <input type="hidden" name="src" value="1">
    <input type="hidden" name="sra" value="1">
    <input type="hidden" name="custom" value="<?php echo $account->getId(); ?>,2,<?php echo md5($account->getEmail()); ?>">
</form>

<p><strong><?php pt("Please wait..."); ?></strong></p>

<script type="text/javascript">
    document.getElementById("cbf0").submit();
</script>

<?php
}
?>
