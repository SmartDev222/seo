<?php

require dirname(dirname(dirname(__FILE__))) . '/init.php';

// Display errors
@set_error_handler(null);
$studio->config['errors']['show'] = true;
$reportErrors = false;
@error_reporting(E_ALL);
@ini_set('display_errors', 'On');

// Session logging
function logDebuggingSession($command) {
    global $studio;

    $sessions = unserialize(base64_decode($studio->getopt('remote-debugging-sessions'))); // base64_encode(serialize([]))
    $sessions[] = [
        time(),
        $_SERVER['REMOTE_ADDR'],
        $command
    ];

    $studio->setopt('remote-debugging-sessions', base64_encode(serialize($sessions)));
}

// Don't run when the extension is disabled
if (!$studio->getopt('remote-debugging-enabled')) die('This extension is not enabled.');

// Ensure we have a valid access code
if (!isset($_POST['code'])) die('Missing posted parameter code.');
if (empty($studio->getopt('remote-debugging-code'))) die('No access code available.');
if (strtolower(trim($_POST['code'])) !== strtolower($studio->getopt('remote-debugging-code'))) die('Invalid access code.');
