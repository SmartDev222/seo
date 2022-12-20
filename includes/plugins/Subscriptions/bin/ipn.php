<?php
require "includes/init.php";

// Reference: https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNandPDTVariables/

$_POST['cmd'] = "_notify-validate";
$req = http_build_query($_POST, '', '&');

$ch = curl_init("https://www.paypal.com/cgi-bin/webscr");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);

$res = curl_exec($ch);
if (curl_errno($ch) > 0) {
    http_response_code(500);
    die("error");
}

if (strcmp ($res, "VERIFIED") == 0) {
    if (!isset($_POST['payment_status'])) $_POST['payment_status'] = "";
    if (!isset($_POST['mc_gross'])) $_POST['mc_gross'] = "0";

    $payment_status = $_POST['payment_status'];

    if (!isset($_POST['txn_type'])) {
        die; // chargeback, check post:case_type, see ref above
    }

    $txn_type = $_POST['txn_type'];

    if (!isset($_POST['custom'])) die;
    $custom = $_POST['custom'];
    if (stripos($custom, ",") === false) die;

    $customParts = explode(",", $custom);
    $user_id = $customParts[0];
    $pay_cycle = $customParts[1];
    $user_email_md5 = $customParts[2];

    if (!is_numeric($user_id)) die;

    $total = (float)$_POST['mc_gross'];
    $business = $_POST['business'];

    if ($payment_status == "Completed") {
        if ($business != $studio->getopt("sub-paypal-email")) {
            http_response_code(403);
            die("invalid");
        }

        $q = $studio->sql->query("SELECT * FROM accounts WHERE id = $user_id");
        if ($q->num_rows == 0) {
            http_response_code(503);
            die("error");
        }

        $user = $q->fetch_object();
        if (md5($user->email) != $user_email_md5) {
            http_response_code(503);
            die("error");
        }

        $plan_info = json_decode($studio->getopt("sub-user-$user_id"), true);
        $plan = null;
        $plans = json_decode($studio->getopt("sub-plans"), true);

        foreach ($plans as $p) {
            if (in_array($user->groupId, $p['groups'])) {
                $plan = $p;
            }
        }

        if ($plan == null) die;

        if ($txn_type == "web_accept") {
            if ($total != $plan['cost']['onetime']) {
                http_response_code(503);
                die("invalid");
            }
        }
        if ($txn_type == "subscr_payment") {
            if ($pay_cycle == 1 && $total != $plan['cost']['monthly']) {
                http_response_code(503);
                die("invalid");
            }
            if ($pay_cycle == 2 && $total != $plan['cost']['annually']) {
                http_response_code(503);
                die("invalid");
            }
            if ($pay_cycle != 1 && $pay_cycle != 2) die;
        }

        $expires = 0;
        $duration = "onetime";
        if ($pay_cycle == 1) {
            $expires = strtotime("+1 month");
            $duration = "monthly";
        }
        if ($pay_cycle == 2) {
            $expires = strtotime("+1 year");
            $duration = "yearly";
        }

        $plan_info['active'] = true;
        $plan_info['duration'] = $duration;
        $plan_info['expires_on'] = $expires;
        if (!isset($plan_info['subscribed_on'])) $plan_info['subscribed_on'] = time();
        $plan_info['last_payment_on'] = time();

        $studio->setopt("sub-user-$user_id", json_encode($plan_info));

        $type = Studio\Common\Activity::INFO;
        $message = "{$user->email} subscribed to and paid for the " . $plan['name'] . " plan! ($total)";
        $act = new Studio\Common\Activity($type, $message, time());
        $studio->addActivity($act);
    }

    if ($txn_type == "subscr_failed" || $txn_type == "subscr_eot" || $txn_type == "new_case") {
        if ($business != $studio->getopt("sub-paypal-email")) {
            http_response_code(403);
            die("invalid");
        }

        $q = $studio->sql->query("SELECT * FROM accounts WHERE id = $user_id");
        if ($q->num_rows == 0) {
            http_response_code(503);
            die("error");
        }

        $user = $q->fetch_object();
        if (md5($user->email) != $user_email_md5) {
            http_response_code(503);
            die("error");
        }

        $plan_info = json_decode($studio->getopt("sub-user-$user_id"), true);
        $plan = null;
        $plans = json_decode($studio->getopt("sub-plans"), true);

        foreach ($plans as $p) {
            if (in_array($user->groupId, $p['groups'])) {
                $plan = $p;
            }
        }

        if ($plan == null) die;

        $plan_info['active'] = false;
        if ($plan_info['expires'] > time()) $plan_info['expires'] = time() - 1;

        $studio->setopt("sub-user-$user_id", json_encode($plan_info));

        $type = Studio\Common\Activity::ERROR;
        $message = "{$user->email} had a failed or disputed transaction for " . $plan['name'] . " plan! ($txn_type)";
        $act = new Studio\Common\Activity($type, $message, time());
        $studio->addActivity($act);
    }

    die;
}
else if (strcmp ($res, "INVALID") == 0) {
	http_response_code(501);
    die("invalid");
}

http_response_code(500);
die("invalid");
?>
