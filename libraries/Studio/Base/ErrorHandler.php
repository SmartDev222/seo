<?php

namespace Studio\Base;

use Exception;
use Raven_Client;
use Raven_ErrorHandler;
use ReflectionClass;

class ErrorHandler {

	/**
	 * The client to use for error reporting.
	 *
	 * @var Raven_Client
	 */
	public $client;

	/**
	 * The error handler to use.
	 *
	 * @var Raven_ErrorHandler
	 */
	protected $handler;

	/**
	 * The studio instance we're handling errors for.
	 *
	 * @var Studio
	 */
	protected $studio;

	/**
	 * Sets whether or not errors will be reported to the developer.
	 *
	 * @var bool
	 */
	public $allowErrorReporting = true;

	/**
	 * Sets whether or not errors will be logged to the filesystem.
	 *
	 * @var bool
	 */
	public $allowErrorLogging = true;

	/**
	 * Sets whether or not error output will be shown on the page.
	 *
	 * @var bool
	 */
	public $allowErrorOutput = true;

	/**
	 * An array storing error hashes we've encountered during this run.
	 *
	 * @var string[]
	 */
	protected $cache = array();
	protected $eventIds = array();

	/**
	 * Constructs a new `ErrorHandler` instance.
	 *
	 * @param Studio $studio
	 */
	public function __construct($studio) {
		$this->studio = $studio;
		$this->client = new Raven_Client('https://908d00dbe2ba4a9da7f515774e082a32@o90645.ingest.sentry.io/5602780');
		$this->handler = new Raven_ErrorHandler($this->client);
	}

	/**
	 * Registers the error handler for the specified mode.
	 *
	 * @param string $mode
	 * @return void
	 */
	public function register($mode) {
		$this->allowErrorReporting = $this->studio->getopt('send-errors') == 'On';
		$this->allowErrorLogging = true;
		$this->allowErrorOutput = $this->studio->config['errors']['show'];

		// If errors are not anonymized, add some information about the site admin
		// This helps me quickly identify all of the errors in your app if you contact me for support
		if ($this->studio->getopt("errors-anonymous") != "On" && $this->studio->getopt('api.secretkey')) {
			$this->client->user_context([
				'id' => 'API-' . substr(md5($this->studio->getopt('api.secretkey')), 0, 8),
				'key' => $this->studio->getopt('api.secretkey'),
				'url' => $this->studio->getopt('public-url')
			]);
		}

		// Otherwise, we can identify as "anonymous" to prevent raven from autofilling information
		else {
			$this->client->user_context([
				'id' => 'ANON-' . substr(md5(__FILE__ . $this->studio->getopt('install-time')), 0, 8),
				'url' => 'ANON'
			]);
		}

		// Add environment information
		$this->client->setEnvironment($mode);
		$this->client->setRelease(Studio::VERSION_STR);
		$this->client->setAppPath($this->studio->basedir);
		$this->client->extra_context([
			'google' => $this->studio->getopt('google-enabled') == 'On',
			'cron' => $this->studio->getopt('cron-token', '') != '',
			'autoupdates' => $this->studio->getopt('automatic-updates') == 'On'
		]);

		// Configure the CA bundle
		$this->client->ca_cert = $this->studio->basedir . '/resources/certificates/cacert.pem';

		// Disable native error handling
		@ini_set('display_errors', 0);
		@ini_set('log_errors', 0);

		// Listen for errors
		$this->registerExceptionHandler();
		$this->registerErrorHandler();
		$this->registerShutdownFunction();
	}

	/**
	 * Listens for uncaught exceptions.
	 *
	 * @return void
	 */
	protected function registerExceptionHandler() {
		set_exception_handler(function($exception) {
			$this->handleException($exception);
		});
	}

	/**
	 * Listens for general warnings, notices, etc.
	 *
	 * @return void
	 */
	protected function registerErrorHandler() {
		set_error_handler(function($severity, $message, $file, $line) {
			if ((error_reporting() & $severity)) {
				$this->handleError($severity, $message, $file, $line);
			}
		});
	}

	/**
	 * Listens for fatal errors.
	 *
	 * @return void
	 */
	protected function registerShutdownFunction() {
		register_shutdown_function(function() {
			$error = error_get_last();

			if (!is_null($error)) {
				$this->handleFatalError($error);
			}
		});
	}

	/**
	 * Handles uncaught exceptions.
	 *
	 * @param Exception $exception
	 * @return void
	 */
	protected function handleException($exception) {
		$hash = $this->getExceptionHash($exception);

		if ($this->canReportError($hash, $exception->getFile())) {
			$this->handler->handleException($exception);
			$this->eventIds[$hash] = $this->getEventId();
		}

		$reflect = new ReflectionClass($exception);
		$shortName = $reflect->getShortName();
		$message = sprintf(
			"Uncaught exception '%s' with message '%s' in %s:%d Stack trace: %s",
			$shortName,
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			$exception->getTraceAsString()
		);

		$this->postProcessError($hash, E_ERROR, $message, $exception->getFile(), $exception->getLine(), $exception->getTrace());
	}

	/**
	 * Handles uncaught errors.
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return void
	 */
	protected function handleError($severity, $message, $file, $line) {
		$hash = $this->getErrorHash($severity, $message, $file, $line);

		// Skip code that is silenced with the '@' operator
		if (error_reporting() === 0) {
			return;
		}

		// Report all other errors
		if ($this->canReportError($hash, $file)) {
			$this->handler->handleError($severity, $message, $file, $line);
			$this->eventIds[$hash] = $this->getEventId();
		}

		$this->postProcessError($hash, $severity, $message, $file, $line);
	}

	/**
	 * Handles fatal shutdown errors.
	 *
	 * @param array $error
	 * @return void
	 */
	protected function handleFatalError($error) {
		if ($error['type'] !== E_ERROR) {
			return;
		}

		if ($this->allowErrorReporting) {
			$this->handler->handleFatalError();
		}

		$hash = '0' . md5($error['message'] . $error['file'] . $error['line']);
		$this->postProcessError($hash, E_ERROR, $error['message'], $error['file'], $error['line'], []);
	}

	/**
	 * Logs and outputs the given error details where applicable.
	 *
	 * @param string $hash
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return void
	 */
	protected function postProcessError($hash, $severity, $message, $file, $line) {
		$this->log($hash, $severity, $message, $file, $line);
		$this->show($hash, $severity, $message, $file, $line);
	}

	/**
	 * Logs the given error details to a file.
	 *
	 * @param string $hash
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return void
	 */
	protected function log($hash, $severity, $message, $file, $line) {
		if ($this->allowErrorLogging) {
			if (is_writable($this->studio->basedir)) {
				$shortFileName = str_replace('\\', '/', str_ireplace($this->studio->basedir, '', $file));
				$type = $this->getErrorType($severity);
				$eventId = $this->getEventId($hash);
				$report = $eventId ? $eventId : "0";
				$errorLine = "$hash\t$type\t$message\t$shortFileName\t$line\t" . date(DATE_ATOM) . "\t$report" . PHP_EOL;

				$logFilePath = $this->studio->basedir . '/.studio.log';
				$setPermissions = !file_exists($logFilePath);

				$handle = fopen($logFilePath, 'a');
				fwrite($handle, $errorLine);
				fclose($handle);

				if ($setPermissions) {
					@chmod($logFilePath, 0600);
				}
			}
		}
	}

	/**
	 * Prints the given error details to the output, and terminates the page.
	 *
	 * @param string $hash
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return void
	 */
	protected function show($hash, $severity, $message, $file, $line) {
		$isFatalError = $severity === E_ERROR;
		$shortFileName = str_replace('\\', '/', str_ireplace($this->studio->basedir, '', $file));
		$errorLabel = $this->getErrorLabel($severity);

		// Fetch the ID of the event that was sent (for customer support purposes)
		$eventId = $this->getEventId($hash);
		$errorReportRay = $eventId ? $eventId : "Not reported";

		// Retrieve the base error template
		$errorTemplate = file_get_contents($this->studio->basedir . "/resources/bin/error.html");
		$errorTemplate = str_replace("<!-- ray -->", $errorReportRay, $errorTemplate);

		// Handle fatal errors
		if ($isFatalError) {
			// Set the status code if possible
			if (!headers_sent()) {
				@http_response_code(500);
			}

			// If output is allowed, let's print a generic error message that looks like PHP's
			if ($this->allowErrorOutput) {
				echo sprintf(
					"<strong>%s</strong>: %s in <strong>%s</strong> on line <strong>%d</strong> %s<br>\n",
					$errorLabel,
					$message,
					$shortFileName,
					$line,
					$eventId ? "(error report: $eventId) " : ""
				);
			}

			// Otherwise use the error template
			else {
				echo sanitize_trusted($errorTemplate);
			}

			die;
		}

		// Handle soft errors
		else {
			// If output is allowed, let's print a generic error message that looks like PHP's
			// Otherwise we don't want to output anything
			if ($this->allowErrorOutput) {
				echo sprintf(
					"<strong>%s</strong>: %s in <strong>%s</strong> on line <strong>%d</strong> %s<br>\n",
					$errorLabel,
					$message,
					$shortFileName,
					$line,
					$eventId ? "(error report: $eventId) " : ""
				);
			}
		}
	}

	/**
	 * Returns a string representing the given `E_` type.
	 *
	 * @param int $severity
	 * @return string
	 */
	protected function getErrorType($severity) {
		static $types = array(
            E_ERROR => "FATAL",
            E_PARSE => "PARSE",
            E_WARNING => "WARNING",
            E_NOTICE => "NOTICE",
            E_STRICT => "STRICT",
            E_DEPRECATED => "DEPRECATED",
            E_USER_ERROR => "USER_ERROR",
            E_USER_WARNING => "USER_WARNING",
            E_USER_NOTICE => "USER_NOTICE"
		);

		return $types[$severity];
	}

	/**
	 * Returns a string representing the given `E_` type.
	 *
	 * @param int $severity
	 * @return string
	 */
	protected function getErrorLabel($severity) {
		static $types = array(
            E_ERROR => "Fatal error",
            E_PARSE => "Parse error",
            E_WARNING => "Warning",
            E_NOTICE => "Notice",
            E_STRICT => "Strict",
            E_DEPRECATED => "Deprecated",
            E_USER_ERROR => "Error",
            E_USER_WARNING => "Warning",
            E_USER_NOTICE => "Notice"
		);

		return $types[$severity];
	}

	/**
	 * Returns a unique hash for the given error.
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return string
	 */
	protected function getErrorHash($severity, $message, $file, $line) {
		return '1' . md5(implode(':', [$severity, $message, $file, $line]));
	}

	/**
	 * Returns a unique hash for a given exception.
	 *
	 * @param Exception $exception
	 * @return string
	 */
	protected function getExceptionHash($exception) {
		return '2' . md5(implode(':', [
			$exception->getMessage(),
			$exception->getCode(),
			$exception->getFile(),
			$exception->getLine()
		]));
	}

	/**
	 * Returns `true` if we can report the error.
	 *
	 * @param string $hash
	 * @param string $file
	 * @return bool
	 */
	protected function canReportError($hash, $file) {
		// Skip if cached
		if (in_array($hash, $this->cache)) {
			return false;
		}

		// Skip unknown files (probably a PHP config warning)
		if (strtolower($file) === 'unknown') {
			return false;
		}

		// Skip errors from the error handler
		// Because infinite loops are bad!
		if ($file === __FILE__) {
			return false;
		}

		$this->cache[] = $hash;
		return $this->allowErrorReporting;
	}

    /**
     * Log a message to sentry.
     *
     * @param string $message The message (primary description) for the event.
     * @param array $params params to use when formatting the message.
     * @param array $data Additional attributes to pass with this event (see Sentry docs).
     * @param bool|array $stack
     * @param mixed $vars
     * @return string|null
     */
    public function sendMessage($message, $params = array(), $data = array(), $stack = false, $vars = null) {
		if ($this->allowErrorReporting) {
			$hash = '3' . md5($message);
			if (in_array($hash, $this->cache)) return;
			$this->cache[] = $hash;

			return $this->client->captureMessage($message, $params, $data, $stack, $vars);
		}
	}

	/**
	 * Returns the last sent event ID, or `null`.
	 *
	 * @param string|null $hash
	 * @return string|null
	 */
	public function getEventId($hash = null) {
		if ($hash && array_key_exists($hash, $this->eventIds)) {
			return $this->eventIds[$hash];
		}

		return $this->client->getLastEventID();
	}

	/**
	 * Sets the mode of the error handler.
	 *
	 * @param string $mode
	 * @return string The old mode.
	 */
	public function setMode($mode) {
		$old = $this->client->getEnvironment();
		$this->client->setEnvironment($mode);
		return $old;
	}

}
