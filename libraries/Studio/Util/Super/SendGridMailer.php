<?php

namespace Studio\Util\Super;

use PHPMailer\PHPMailer;
use Studio\Util\Http\WebRequest;
use Studio\Util\Http\WebRequestException;

class SendGridMailer extends PHPMailer {

	/**
	 * Sends the message using the SendGrid API.
	 *
	 * @return bool False on error. See the `ErrorInfo` property for details of the error.
	 */
	public function send() {
		$content = array(array(
			'type' => $this->ContentType,
			'value' => $this->Body
		));

		$data = array(
			'personalizations' => array(
				array(
					'to' => array(
						array(
							'email' => $this->to[0][0],
							'name' => $this->to[0][1]
						)
					)
				)
			),
			'from' => array(
				'email' => $this->From,
				'name' => $this->FromName
			),
			'subject' => $this->Subject,
			'content' => $content
		);

		// Add reply to if enabled
		if (count($this->ReplyTo) > 0) {
			foreach ($this->ReplyTo as $options) {
				$data['reply_to'] = array(
					'email' => $options[0],
					'name' => $options[1]
				);
				break;
			}
		}

		try {
			$request = new WebRequest('https://api.sendgrid.com/v3/mail/send');
			$request->setHeader('Authorization', 'Bearer ' . $this->Password);
			$response = $request->post($data, 'json');

			if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
				return true;
			}

			if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
				$body = $response->getJson();

				if (isset($body['errors'])) {
					$message = $body['errors'][0]['message'];
					$field = $body['errors'][0]['field'];

					$this->ErrorInfo = $message . ' (Field: ' . $field . ')';
					return false;
				}

				$this->ErrorInfo = 'Unknown error, status code ' . $response->getStatusCode();
				return false;
			}

			$this->ErrorInfo = 'Got unexpected status code ' . $response->getStatusCode();
			return false;
		}
		catch (WebRequestException $e) {
			$this->ErrorInfo = 'WebRequestException: ' . $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine();
			return false;
		}
	}

}
