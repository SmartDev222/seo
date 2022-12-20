<?php

namespace Studio\Util;

use Exception;
use PHPMailer\PHPMailer;
use Studio\Util\Super\SendGridMailer;

class Mail {

	/**
	 * Retrieves the client to use for sending mail. This can throw an `Exception` if there's an issue with the
	 * configured mail settings.
	 *
	 * @return PHPMailer
	 * @throws Exception
	 */
	public static function getClient() {
		global $studio;

		$mode = static::getMode();

		switch ($mode) {
			case 'php': return static::createInstancePHP();
			case 'smtp': return static::createInstanceSMTP();
			case 'mailgun': return static::createInstanceMailGun();
			case 'sendgrid': return static::createInstanceSendGrid();
			default: return null;
		}
	}

	/**
	 * Returns which mode to use for sending mail, e.g. `php`, `smtp`, `mailgun`, `sendgrid`, ...
	 *
	 * @return string
	 */
	private static function getMode() {
		global $studio;
		return $studio->getopt('mail-server', 'php');
	}

	/**
	 * Creates a new instance of the mailer with default options.
	 *
	 * @return PHPMailer
	 * @internal
	 */
	private static function createDefaultInstance() {
		$mailer = new PHPMailer(true);
		$mailer->From = static::getFromAddress();
		$mailer->FromName = static::getFromName();

		return $mailer;
	}

	/**
	 * Creates a new instance of the mailer with default options. The returned object is a `SendGridMailer` which
	 * will submit to the SendGrid HTTP API.
	 *
	 * @return SendGridMailer
	 * @internal
	 */
	private static function createDefaultInstanceSG() {
		$mailer = new SendGridMailer(true);
		$mailer->From = static::getFromAddress();
		$mailer->FromName = static::getFromName();

		return $mailer;
	}

	/**
	 * Creates a new instance of the mailer configured to use PHP's `mail()` function.
	 *
	 * @return PHPMailer
	 * @internal
	 */
	private static function createInstancePHP() {
		return static::createDefaultInstance();
	}

	/**
	 * Creates a new instance of the mailer configured to use SMTP.
	 *
	 * @return PHPMailer
	 * @internal
	 */
	private static function createInstanceSMTP() {
		global $studio;

		// Create the mailer
		$mailer = static::createDefaultInstance();
		$mailer->isSMTP();

		// Apply hostname and port
        $mailer->Host = $studio->getopt('smtp-server', 'localhost');
		$mailer->Port = $studio->getopt('smtp-server-port', 587);

		// Apply authentication
		if ($studio->getopt('smtp-auth', 'On') == 'On') {
			$mailer->SMTPAuth = true;
			$mailer->Username = $studio->getopt('smtp-user');
			$mailer->Password = $studio->getopt('smtp-pass');
		}

		// Apply security
		$mailer->SMTPSecure = $studio->getopt('smtp-secure', '');

		return $mailer;
	}

	/**
	 * Creates a new instance of the mailer configured to use MailGun.
	 *
	 * @return PHPMailer
	 * @internal
	 */
	private static function createInstanceMailGun() {
		global $studio;

		// Get the mailgun credentials
		$username = $studio->getopt('mailgun-smtp-user');
		$password = $studio->getopt('mailgun-smtp-pass');

		if (is_null($username) || is_null($password)) {
			throw new Exception('Cannot send email with mailgun because the credentials are not configured');
		}

		// Create the mailer
		$mailer = static::createDefaultInstance();
		$mailer->isSMTP();
        $mailer->Host = 'smtp.mailgun.org';
		$mailer->Port = 587;
		$mailer->SMTPSecure = 'tls';
		$mailer->SMTPAuth = true;
		$mailer->Username = $username;
		$mailer->Password = $password;

		return $mailer;
	}

	/**
	 * Creates a new instance of the mailer configured to use SendGrid.
	 *
	 * @return PHPMailer
	 * @internal
	 */
	private static function createInstanceSendGrid() {
		global $studio;

		// Get the mailgun credentials
		$key = $studio->getopt('sendgrid-key');
		$api = $studio->getopt('sendgrid-api') === 'On';

		if (is_null($key)) {
			throw new Exception('Cannot send email with sendgrid because the credentials are not configured');
		}

		// Create the mailer
		$mailer = $api ? static::createDefaultInstanceSG() : static::createDefaultInstance();
		$mailer->isSMTP();
        $mailer->Host = 'smtp.sendgrid.net';
		$mailer->Port = 587;
		$mailer->SMTPSecure = 'tls';
		$mailer->SMTPAuth = true;
		$mailer->Username = 'apikey';
		$mailer->Password = $key;

		return $mailer;
	}

	/**
	 * Returns the email address to use for the `From` header in outgoing mail.
	 *
	 * @return string
	 */
	public static function getFromAddress() {
		global $studio;

		$email = $studio->getopt('email-from', 'webmaster@example.com');

		if (empty($email)) {
			$email = 'webmaster@example.com';
		}

		return $email;
	}

	/**
	 * Returns the name to use for the `From` header in outgoing mail.
	 *
	 * @return string
	 */
	public static function getFromName() {
		global $studio;

		$name = $studio->getopt('email-from-name', 'SEO Studio');

		if (empty($name)) {
			$name = 'SEO Studio';
		}

		return $name;
	}

	/**
	 * Returns an associative array containing the `subject` and `message` for the given email template. If an array is
	 * passed for the `$variables` parameter, then the variables will be replaced in the template content.
	 *
	 * @param string $name
	 * @param string[]|null $variables
	 * @return string[]
	 */
	public static function getEmailTemplate($name, $variables = null) {
		global $studio;

		if ($p = $studio->sql->prepare('SELECT `subject`, `message` FROM `mail_templates` WHERE `name` = ?')) {
			$p->bind_param('s', $name);
			$p->execute();
			$p->store_result();

			if ($p->num_rows !== 1) {
				throw new Exception('Unable to find email template "' . $name . '"');
			}

			$p->bind_result($subject, $message);
			$p->fetch();
			$p->close();

			return array(
				'subject' => static::injectVariables($subject, $variables),
				'message' => static::injectVariables($message, $variables)
			);
		}
		else {
			throw new Exception('SQL error when querying for email template "' . $name . '"');
		}
	}

	/**
	 * Returns the `$content` with variables replaced.
	 *
	 * @param string $content
	 * @param string[]|null $variables
	 * @return string
	 */
	protected static function injectVariables($content, $variables) {
		if (is_array($variables)) {
			foreach ($variables as $key => $value) {
				if (is_string($key) && is_string($value)) {
					$content = preg_replace('/{{\s*' . preg_quote($key, '/') . '\s*}}/i', $value, $content);
				}
			}
		}

		return $content;
	}

	/**
	 * Registers a new email template in the database if it doesn't already exist.
	 *
	 * @param string $name
	 * @param string $subject
	 * @param string $message
	 * @return void
	 */
	public static function registerEmailTemplate($name, $subject, $message) {
		global $studio;

		// Look for an existing template
		if ($p = $studio->sql->prepare('SELECT * FROM `mail_templates` WHERE `name` = ?')) {
			$p->bind_param('s', $name);
			$p->execute();
			$p->store_result();

			// Add the template if not found
			if ($p->num_rows === 0) {
				$p->close();

				$p = $studio->sql->prepare('INSERT INTO `mail_templates` (`name`, `subject`, `message`) VALUES (?, ?, ?)');
				$p->bind_param('sss', $name, $subject, $message);
				$p->execute();

				if ($p->errno > 0) {
					throw new Exception('SQL error when registering email template "' . $name . '"');
				}

				$p->close();
			}
		}
	}

}
