<?php

$db = $config['database'];

# Connect to database

$sql = new mysqli($db['host'], $db['username'], $db['password'], $db['name'], $db['port']);
if (mysqli_connect_error($sql)) die("<h1>Database error</h1><!-- " . mysqli_connect_errno($sql) . " -->");


?>
