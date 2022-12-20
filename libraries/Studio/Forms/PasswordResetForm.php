<?php

namespace Studio\Forms;

use Studio\Common\Activity;

class PasswordResetForm extends Form
{

	/**
	 * @var string
	 */
	public $token;

	/**
	 * @var int
	 */
	public $accountId;

	/**
	 * @var string
	 */
	public $accountEmail;

	/**
	 * @var int
	 */
	public $expiration;

	/**
	 * @var string
	 */
	private $accountPassword;

	/**
	 * @var string|null
	 */
	public $error;

	/**
	 * Constructs a new `PasswordResetForm`
	 *
	 * @param string $token
	 * @param int $accountId
	 * @param int $expiration
	 */
    public function __construct($token, $accountId, $expiration) {
        $this->errors = array();

		$this->token = $token;
		$this->accountId = $accountId;
		$this->expiration = $expiration;

		if ($this->validate()) {
			if ($this->validateToken()) {
				if ($this->expiration > time()) {
					if (isset($_POST['password']) && isset($_POST['password_repeat'])) {
						$this->commit();
					}

					return;
				}
				else {
					$this->error = rt('The link you followed has expired. Please try requesting another reset link.');
				}
			}
			else {
				$this->error = rt('The link you followed has expired. Please try requesting another reset link.');
			}
		}
    }

	private function validateToken() {
		global $studio;

		$secret = $studio->config['session']['token'];
		$hash = hash('sha256', sprintf("%s(%d:%d+%s)", $secret, $this->expiration, $this->accountId, $this->accountPassword));

		return $this->token === $hash;
	}

    public function validate() {
        global $studio;

        # Check if account with that email exists

        if ($p = $studio->sql->prepare("SELECT `password`, `email` FROM `accounts` WHERE `id` = ?")) {
            $p->bind_param("i", $this->accountId);
            $p->execute();
            $p->store_result();

            if ($p->num_rows == 1) {
                $p->bind_result($password, $email);
				$p->fetch();

				$this->accountPassword = $password;
				$this->accountEmail = $email;

				if (isset($_POST['password'])) {
					$password = trim($_POST['password']);
					$confirm = trim($_POST['password_repeat']);

					if (empty($password)) {
						$this->errors[] = rt("Please enter a new password.");
					}

					else if ($password !== $confirm) {
						$this->errors[] = rt("Passwords don't match.");
					}

					else if (strlen($password) < 6) {
						$this->errors[] = rt("Password must be at least 6 characters.");
					}

					return empty($this->errors);
				}

				return true;
            }
            else $this->error = rt("The link you followed has expired. Please try requesting another reset link.");
        }
        else $this->error = "Database error. #1";

        return false;
	}

	private function commit() {
		global $studio, $account;

		$password = trim($_POST['password']);

		if ($p = $studio->sql->prepare("UPDATE accounts SET `password` = ? WHERE `id` = ?")) {
            # Generate a secure blowfish salt and hash the password
            # Note: Blowfish is only supported on PHP 5.3.7+

            $salt = str_replace("+", ".", substr(base64_encode(rand(111111,999999).rand(111111,999999).rand(11111,99999)), 0, 22));
            $salt = '$' . implode('$', array("2y", str_pad(11, 2, "0", STR_PAD_LEFT), $salt));
            $password = @crypt($password, $salt);

            if (!$password) $password = @crypt($password);
            if (!$password) $password = md5($password);

            $p->bind_param("si", $password, $this->accountId);
            $p->execute();

            if ($studio->sql->affected_rows == 1) {
                $p->close();

                $studio->addActivity(new Activity(
                    Activity::INFO,
                    "Successful password reset for " . $this->accountEmail . " from " . $_SERVER['REMOTE_ADDR']
                ));

                $account->login($this->accountEmail, $password);
                $studio->redirect("account/websites.php");
            }
            else $this->errors[] = "Database error. #3";
        }
        else $this->errors[] = "Database error. #2";
	}

}
