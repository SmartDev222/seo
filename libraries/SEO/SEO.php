<?php

$files = array(
    "Common/SEOException.php",
    "Helper/DOM.php",
    "Helper/Url.php",
    "Services/Google.php",
    "Services/Bing.php",
    "Services/Alexa.php",
    "Services/SEOprofiler.php"
);

foreach ($files as $file) {
    require_once $file;
}

?>
