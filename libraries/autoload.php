<?php

function autoload($className)
{
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    $filePath = dirname(__FILE__) . "/" . $fileName;

    if (file_exists($filePath)) {
        require $filePath;
    }
}

spl_autoload_register('autoload');

require_once dirname(__FILE__) . "/Sanitize.php";
require_once dirname(__FILE__) . "/Raven/Autoloader.php";
Raven_Autoloader::register();
