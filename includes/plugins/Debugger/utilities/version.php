<?php

use Studio\Base\Studio;

require dirname(dirname(__FILE__)) . '/init.php';

logDebuggingSession('Check current version');

echo 'Build: #' . Studio::VERSION . PHP_EOL;
echo 'Version: ' . Studio::VERSION_STR . PHP_EOL;
echo 'Released: ' . Studio::VERSION_DATE . PHP_EOL . PHP_EOL;

date_default_timezone_set('America/Phoenix');
echo 'Last checked for updates at: ' . date('d F Y g:i:s a', $studio->getopt('last-update-check'));
echo PHP_EOL . PHP_EOL;

echo 'Available updates:' . PHP_EOL;

$q = $studio->sql->query("SELECT * FROM updates WHERE updateStatus <> 1 ORDER BY id DESC");
if ($q->num_rows === 0) echo 'None.';
while ($row = $q->fetch_array()) {
    echo sprintf(
        "%s\t%s\t%s\t%s",
        $row['token'],
        $row['updateVersion'],
        $row['updateTime'],
        $row['updateError']
    ) . PHP_EOL;
}

echo PHP_EOL . PHP_EOL;
echo 'Installed updates:' . PHP_EOL;

$q = $studio->sql->query("SELECT * FROM updates WHERE updateStatus = 1 ORDER BY id DESC");
if ($q->num_rows === 0) echo 'None.';
while ($row = $q->fetch_array()) {
    echo sprintf(
        "%s\t%s\t%s",
        $row['token'],
        $row['updateVersion'],
        $row['updateTime']
    ) . PHP_EOL;
}
