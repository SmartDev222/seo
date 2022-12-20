<?php

require "../includes/init.php";
$page->setTitle("Edit account")->setPage(3)->setPath("../")->header()->requireLogin();

$error1 = "";
$error2 = "";

if (isset($_POST['password']) && !DEMO) {
    $new = trim($_POST['password']);
    $confirm = trim($_POST['password2']);

    if (strlen($new) >= 6) {
        if ($new != $confirm) {
            $error2 = rt("Passwords don't match.");
        }
        else {
            $userid = $account->getId();

            $salt = str_replace("+", ".", substr(base64_encode(rand(111111,999999).rand(111111,999999).rand(11111,99999)), 0, 22));
            $salt = '$' . implode('$', array("2y", str_pad(11, 2, "0", STR_PAD_LEFT), $salt));
            $new = @crypt($new, $salt);

            if (!$new) $new = @crypt($new);
            if (!$new) $new = md5($new);

            $p = $studio->sql->prepare("UPDATE accounts SET password = ? WHERE id = $userid");
            $p->bind_param("s", $new);
            $p->execute();
            $p->close();

            $studio->redirect("account/login.php");
        }
    }
    else $error2 = rt("Password must be at least 6 characters.");
}

if (isset($_POST['email']) && !DEMO) {
    $email = strtolower(trim($_POST['email']));

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) $error1 = rt("Invalid email.");

    $userid = $account->getId();

    if ($error1 == "") {
        if ($p = $studio->sql->prepare("SELECT email FROM accounts WHERE email=? AND id != $userid")) {
            $p->bind_param("s", $email);
            $p->execute();
            $p->store_result();
            $existing = $p->num_rows;
            $p->close();

            if ($existing == 0) {

            }
            else $error1 = rt("There is an account with that email, please login.");
        }
        else $error1 = "Database error. #1";
    }

    if ($error1 == "") {
        $p = $studio->sql->prepare("UPDATE accounts SET email = ? WHERE id = $userid");
        $p->bind_param("s", $email);
        $p->execute();
        $p->close();

        $studio->redirect("account/login.php");
    }
}

?>

<section class="title">
    <div class="container">
        <h1><?php pt("Edit account"); ?></h1>
    </div>
</section>

<section class="websites">
    <div class="container">
        <h3><?php pt("Change email"); ?></h3>

        <?php if ($error1 != "") echo "<div class='error'>$error1</div>"; ?>

        <form action="" method="post">
            <input class="text-input" type="text" name="email" placeholder="<?php pt("Email address"); ?>" value="<?php echo $account->getEmail(); ?>">
            <input type="submit" value="<?php pt("Change email"); ?>">
        </form>
    </div>
</section>

<section class="websites">
    <div class="container">
        <h3><?php pt("Change password"); ?></h3>

        <?php if ($error2 != "") echo "<div class='error'>$error2</div>"; ?>

        <form action="" method="post">
            <input class="text-input" type="password" name="password" placeholder="<?php pt("Password"); ?>" />
            <input class="text-input" type="password" name="password2" placeholder="<?php pt("Repeat password"); ?>" />
            <input type="submit" value="<?php pt("Change password"); ?>">
        </form>
    </div>
</section>

<?php
$page->footer();
?>
