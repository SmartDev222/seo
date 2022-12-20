<?php

namespace Studio\Base;

use Exception;
use Studio\Common\Activity;
use Studio\Util\Mail;

class Account
{
    private $active;
    private $email;
    private $password;
    private $user;

    /**
     * @var Studio
     */
    private $studio;
    private $site;

    public $groupId;
    private $group;

    public $sessname;

    /**
     * @param Studio $studio
     */
    public function __construct($studio) {
        $this->studio = $studio;
        $this->sessname = $studio->config['session']['token'];

        if (isset($_SESSION[$this->sessname])) {
            $this->email = $_SESSION[$this->sessname]['email'];
            $this->password = $_SESSION[$this->sessname]['password'];

            if ($user = $this->verify()) {
                $this->active = true;
                $this->user = $user;
                $studio->logged_in = true;
                $this->groupId = $user['groupId'];

                $studio->getPluginManager()->call("user_auth", [$user]);
            }
            else {
                $this->logout();
            }
        }

        if (isset($_SESSION['site'])) {
            $this->site = $_SESSION['site'];

            if ($this->active) {
                $time = time();

                if ($p = $studio->sql->prepare("UPDATE websites SET timeAccessed=$time WHERE userId={$user['id']} AND domain=?")) {
                    $p->bind_param("s", $this->site);
                    $p->execute();
                }
            }
        }
    }

    /**
     * Compares the email and password in the session to the actual database session.
     * @return mixed Array of user data on success, or false on failure.
     */
    private function verify() {
        if ($p = $this->studio->sql->prepare("SELECT id, email, password, groupId, verified FROM accounts WHERE email = ? AND password = ?")) {
            $p->bind_param("ss", $this->email, $this->password);
            $p->execute();
            $p->store_result();

            if ($p->num_rows == 1) {
                $p->bind_result($id, $email, $password, $gid, $verified);
                $p->fetch();
                $p->close();

                $this->studio->sql->query("UPDATE accounts SET timeLastLogin='".time()."' WHERE id=$id");

                return array(
                    'id' => $id,
                    'email' => $email,
                    'password' => $password,
                    'groupId' => $gid,
                    'verified' => $verified
                );
            }
        }

        return false;
    }

    /**
     * Signs the user out of the current session.
     */
    public function logout() {
        session_destroy();
    }

    /**
     * @return boolean Whether or not the user is logged in.
     */
    public function isLoggedIn() {
        return $this->active;
    }

    /**
     * @return boolean Whether or not the user verified their email (or is exempt).
     */
    public function isVerified() {
        if ($this->user == null) {
            throw new Exception(
                'Attempt to access user data for an unauthenticated visitor (check isLoggedIn() first)'
            );
        }

        return $this->user['verified'] > 0;
    }

    /**
     * @return int Current user's ID.
     */
    public function getId() {
        if ($this->user == null) {
            throw new Exception(
                'Attempt to access user data for an unauthenticated visitor (check isLoggedIn() first)'
            );
        }

        return $this->user['id'];
    }

    /**
     * @return String Current user's email address.
     */
    public function getEmail() {
        if ($this->user == null) {
            throw new Exception(
                'Attempt to access user data for an unauthenticated visitor (check isLoggedIn() first)'
            );
        }

        return $this->user['email'];
    }

    /**
     * @return String Current user's password (hashed).
     */
     public function getPassword() {
        if ($this->user == null) {
            throw new Exception(
                'Attempt to access user data for an unauthenticated visitor (check isLoggedIn() first)'
            );
        }

         return $this->user['password'];
     }

     /**
      * @return array Current selected website or NULL if none selected.
      */
     public function getCurrentWebsite() {
         return $this->site;
     }

     /**
      * Returns the accounts's group properties.
      *
      * @return array|null
      */
     public function group() {
        if (is_null($this->group)) {
            $q = $this->studio->sql->query("SELECT * FROM `groups` WHERE id='{$this->groupId}'");
            $row = @$q->fetch_array();

            if ($q->num_rows == 0 || $this->studio->sql->error != "") {
                return null;
            }

            $this->group = $row;
        }

        return $this->group;
     }

     /**
      * Sets the selected website or domain name for the user, assuming it has already been validated.
      * @throws \SEO\Common\SEOException when the URL is invalid.
      */
     public function setCurrentWebsite($domain) {
         $url = new \SEO\Helper\Url($domain);

         $_SESSION['site'] = $domain;
         $this->site = $domain;
     }

     /**
      * Authenticates the user using the provided email and password (assuming they have already been validated and are correct)
      */
     public function login($email, $password) {
        $_SESSION[$this->sessname] = array(
            'email' => $email,
            'password' => $password
        );
     }

    public function sendVerificationEmail($accountId, $email) {
        global $studio;

        $path = 'account/confirm.php';
        $link = $studio->permalinks->getLink($path);

        if ($link !== null && $studio->usePermalinks()) {
            $path = $link->getPermalink();
        }

        $template = Mail::getEmailTemplate('account:verify_email', array(
            'link' => rtrim($studio->getopt('public-url'), '/') . '/' . $path . '?token=' . urlencode($this->_generateToken($accountId, $email)),
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR']
        ));

        $mailer = Mail::getClient();
        $mailer->addCustomHeader('X-Studio-UserAddress', $_SERVER['REMOTE_ADDR']);
        $mailer->addAddress($email);
        $mailer->Subject = $template['subject'];
        $mailer->Body = $template['message'];

        if (!$mailer->send()) {
            $studio->addActivity(new Activity(
                Activity::ERROR,
                "Error sending verification email to " . $email . ": " . $mailer->ErrorInfo .
                " (requested by " . $_SERVER['REMOTE_ADDR'] . ")"
            ));

            throw new Exception($mailer->ErrorInfo);
        }
    }

	private function _generateToken($id, $email) {
		global $studio;

		$expires = time() + 3600;
		$secret = $studio->config['session']['token'];
		$hash = hash('sha256', sprintf("%s(%d:%d+%s)", $secret, $expires, $id, $email));
		$data = array('expires' => $expires, 'id' => $id, 'token' => $hash);

		return preg_replace('/=+$/', '', base64_encode(json_encode($data)));
	}

}
