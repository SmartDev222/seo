<?php

namespace Studio\Forms;

use Exception;
use Studio\Common\Activity;
use Studio\Util\Mail;

class PasswordResetRequestForm extends Form
{
	public $email;
	public $sent = false;

	private $accountId;
	private $accountPassword;

    public function __construct() {
        $this->errors = array();

        if (isset($_POST['email']) && !DEMO) {
            $this->post();
        }
    }

    public function post() {
        global $studio;

        if (!$this->validate()) {
            return;
		};

		try {
			$path = 'account/password-reset.php';
			$link = $studio->permalinks->getLink($path);

			if ($link !== null && $studio->usePermalinks()) {
				$path = $link->getPermalink();
			}

			$template = Mail::getEmailTemplate('account:forgot_password', array(
				'link' => rtrim($studio->getopt('public-url'), '/') . '/' . $path . '?token=' . urlencode($this->generateToken()),
				'email' => $this->email,
				'ip' => $_SERVER['REMOTE_ADDR']
			));

			// Switched to universal mailer in v1.84
			$mailer = Mail::getClient();
			$mailer->addCustomHeader('X-Studio-UserAddress', $_SERVER['REMOTE_ADDR']);
			$mailer->addAddress($this->email);
			$mailer->Subject = $template['subject'];
			$mailer->Body = $template['message'];

			if (!$mailer->send()) {
				$this->errors[] = rt("We couldn't send your message right now. Try again shortly.");
				$studio->addActivity(new Activity(
					Activity::ERROR,
					"Error sending password reset for " . $this->email . ": " . $mailer->ErrorInfo .
					" (requested by " . $_SERVER['REMOTE_ADDR'] . ")"
				));
				return;
			}

			$studio->addActivity(new Activity(
				Activity::INFO,
				"Sent password reset email to " . $this->email . " (requested by " . $_SERVER['REMOTE_ADDR'] . ")"
			));

			$studio->redirect('account/login.php?reset=1');
		}
		catch (Exception $ex) {
			$this->errors[] = rt("We couldn't send your message right now. Try again shortly.");
			$studio->addActivity(new Activity(
				Activity::ERROR,
				"Failed to send password reset for " . $this->email . ": " . $ex->getMessage() .
				" (requested by " . $_SERVER['REMOTE_ADDR'] . ")"
			));
		}
	}

	private function generateToken() {
		global $studio;

		$expires = time() + 3600;
		$secret = $studio->config['session']['token'];
		$hash = hash('sha256', sprintf("%s(%d:%d+%s)", $secret, $expires, $this->accountId, $this->accountPassword));
		$data = array('expires' => $expires, 'id' => $this->accountId, 'token' => $hash);

		return preg_replace('/=+$/', '', base64_encode(json_encode($data)));
	}

    public function validate() {
        global $studio;

        $this->email = $_POST['email'];

        # Check if account with that email exists

        if ($p = $studio->sql->prepare("SELECT id, password FROM accounts WHERE email = ?")) {
            $p->bind_param("s", $this->email);
            $p->execute();
            $p->store_result();

            if ($p->num_rows == 1) {
                $p->bind_result($id, $password);
				$p->fetch();

				$this->accountId = $id;
				$this->accountPassword = $password;

				return true;
            }
            else $this->errors[] = rt("We couldn't find an account with that email.");
        }
        else $this->errors[] = "Database error. #1";

        return false;
    }
}
