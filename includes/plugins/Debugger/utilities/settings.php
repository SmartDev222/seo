<?php

use Studio\Base\Studio;

require dirname(dirname(__FILE__)) . '/init.php';

logDebuggingSession('List current settings');

$settings = [
    'allow-registration',
    'show-login',
    'email-verification',
    'cache',
    'cache-duration',
    'default-group',
    'current-theme',
    'send-errors',
    'send-usage-info',
    'usage-info-level',
    'automatic-updates',
    'updates-skip-modified',
    'automatic-updates-backup',
    'tools',
    'categories',
    'push-updates',
    'ssl-updates',
    'default-language',
    'token',
    'last-update-check',
    'last-uinfo-report',
    'update-missing-translations',
    'push-token',
    'cron-last-run',
    'cron-token',
    'errors-anonymous',
    'api.secretkey', // don't worry, this is your api key for the seostudio api, we know it already and this is here to check that it matches
    'google-enabled',
    'public-url',
    'login-tools',
    'show-tools',
    'project-mode',
    'show-tools-without-site',
    'allow-siteless-tools',
    'experimental-tool-design',
    'smtp-secure',
    'google-consecutive-failures',
    'google-next-time'
];

echo 'Build: #' . Studio::VERSION . PHP_EOL . PHP_EOL;

foreach ($settings as $name) {
    $value = $studio->getopt($name);

    if (is_string($value)) $value = '"' . $value . '"';
    else if (is_null($value)) $value = 'NULL';

    echo sprintf('%s:  %s', $name, $value);
    echo PHP_EOL . PHP_EOL;
}
