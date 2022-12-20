<?php

namespace Studio\Forms;

use Studio\Common\Activity;

class LoginForm extends Form
{
    public $email;
    public $password;

    public function __construct() {
        $this->errors = array();

        if (isset($_POST['email']) && isset($_POST['password'])) {
            $this->post();
        }
    }

    public function post() {
        global $account, $studio;

        if (!$this->validate()) {
            if ($this->email != "") {
                $studio->addActivity(new Activity(
                    Activity::WARNING,
                    "Failed login attempt for email " . $this->email . " from " . $_SERVER['REMOTE_ADDR']
                ));
            }

            return;
        };

        $studio->addActivity(new Activity(
            Activity::INFO,
            "Successful login for email " . $this->email . " from " . $_SERVER['REMOTE_ADDR']
        ));

        $account->login($this->email, $this->password);

        if (isset($_SESSION['return'])) {
            $return = $_SESSION['return'];
            unset($_SESSION['return']);
            $studio->redirect("tool.php?id=" . urlencode($return));
        }

        $studio->redirect("account");
    }

    public function validate() {
        global $studio;

        $this->email = $_POST['email'];
        $this->password = $_POST['password'];

        # Check if account with that email exists

        if ($p = $studio->sql->prepare("SELECT email, password FROM accounts WHERE email = ?")) {
            $p->bind_param("s", $this->email);
            $p->execute();
            $p->store_result();

            if ($p->num_rows == 1) {
                $p->bind_result($email, $password);
                $p->fetch();

                usleep(rand(350000, 1800000));
                if (crypt($this->password, $password) === $password) {
                    $this->password = $password;

                    return true;
                }
                else $this->errors[] = rt("Incorrect email or password.");
            }
            else $this->errors[] = rt("Incorrect email or password.");
        }
        else $this->errors[] = "Database error. #1";

        return false;
    }
}
