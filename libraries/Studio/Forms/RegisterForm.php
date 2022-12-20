<?php

namespace Studio\Forms;

use Exception;
use Studio\Common\Activity;

class RegisterForm extends Form
{
    private $email;
    private $password;

    public function __construct() {
        $this->errors = array();

        if (isset($_POST['email'])) {
            $this->post();
        }
    }

    public function validate() {
        global $studio;

        $email = strtolower(trim($_POST['email']));
        $password = trim($_POST['password']);
        $confirm = trim($_POST['password2']);

        if ($email == "" || $password == "") {
            $this->errors[] = rt("Please enter an email and password.");
        }

        if ($password !== $confirm) {
            $this->errors[] = rt("Passwords don't match.");
        }

        if (strlen($password) < 6) {
            $this->errors[] = rt("Password must be at least 6 characters.");
        }

        if ($studio->getopt('signup-legal-affirmation') === 'On') {
            $privacyPath = $studio->basedir . '/privacy.php';
            $termsPath = $studio->basedir . '/terms.php';

            if (file_exists($termsPath)) {
                if (!isset($_POST['affirm_terms']) || $_POST['affirm_terms'] !== 'Y') {
                    $this->errors[] = rt("You must agree to our terms of service.");
                }
            }

            if (file_exists($privacyPath)) {
                if (!isset($_POST['affirm_privacy']) || $_POST['affirm_privacy'] !== 'Y') {
                    $this->errors[] = rt("You must agree to our privacy policy.");
                }
            }
        }

        $p = $studio->getPluginManager()->callCombined("register_validate");
        foreach ($p as $fromp) {
            if (is_array($fromp)) {
                foreach ($fromp as $str) {
                    $this->errors[] = $str;
                }
            }
            else {
                $this->errors[] = $fromp;
            }
        }

        if (empty($this->errors)) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false)
                $this->errors[] = rt("Invalid email.");
        }

        if (empty($this->errors)) {
            if ($p = $studio->sql->prepare("SELECT email FROM accounts WHERE email=?")) {
                $p->bind_param("s", $email);
                $p->execute();
                $p->store_result();
                $existing = $p->num_rows;
                $p->close();

                if ($existing == 0) {
                    $this->email = $email;
                    $this->password = $password;

                    return true;
                }
                else $this->errors[] = rt("There is an account with that email, please login.");
            }
            else $this->errors[] = "Database error. #1";
        }

        return false;
    }

    public function post() {
        global $account, $studio;

        if (DEMO) {
            $this->errors[] = "You can't create accounts in demo mode.";
            return;
        }

        if (!$this->validate()) return;
        $time = time();

        $requireVerification = $studio->getopt('email-verification', 'Off') === 'On';
        $verified = $requireVerification ? 0 : 1;

        if ($p = $studio->sql->prepare("INSERT INTO accounts (email, password, timeLastLogin, timeCreated, groupId, verified) VALUES (?, ?, {$time}, {$time}, 1, {$verified});")) {
            # Generate a secure blowfish salt and hash the password
            # Note: Blowfish is only supported on PHP 5.3.7+

            $salt = str_replace("+", ".", substr(base64_encode(rand(111111,999999).rand(111111,999999).rand(11111,99999)), 0, 22));
            $salt = '$' . implode('$', array("2y", str_pad(11, 2, "0", STR_PAD_LEFT), $salt));
            $password = @crypt($this->password, $salt);

            if (!$password) $password = @crypt($password);
            if (!$password) $password = md5($password);

            $p->bind_param("ss", $this->email, $password);
            $p->execute();

            if ($studio->sql->affected_rows == 1) {
                $insert_id = $studio->sql->insert_id;

                // Require email verification
                if ($requireVerification) {
                    try {
                        $account->sendVerificationEmail($insert_id, $this->email);
                    }
                    catch (Exception $e) {
                        // If sending the verification email fails, delete the account and halt
                        $studio->sql->query("DELETE FROM accounts WHERE id = {$insert_id}");
                        $this->errors[] = rt("We couldn't send your message right now. Try again shortly.");
                        return false;
                    }
                }

                $p->close();

                $studio->addActivity(new Activity(
                    Activity::INFO,
                    "New account created by user, email " . $this->email . ", from " . $_SERVER['REMOTE_ADDR']
                ));

                $studio->getPluginManager()->call("register_form_new_user", [$insert_id]);

                $account->login($this->email, $password);
                $studio->redirect("account/websites.php");
            }
            else $this->errors[] = "Database error. #3";
        }
        else $this->errors[] = "Database error. #2";
    }

}
