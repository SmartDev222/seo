<?php

class Fileperms extends Studio\Util\Diagnostic
{
    var $name = "Check file permissions";

    function __construct($sql) {
        global $studio;

        $failedFile = $this->scan(dirname(dirname(dirname(__FILE__))));
        if ($failedFile !== "") $this->fail("Cannot write to $failedFile, updates may not work.");

        $this->pass();
    }

    function scan($path) {
        foreach (scandir($path) as $file) {
            if ($file == "." || $file == "..") continue;
            $filepath = $path . DIRECTORY_SEPARATOR . $file;

            if (!is_writable($filepath)) return $filepath;
            if (is_dir($filepath)) {
                $r = $this->scan($filepath);
                if ($r != "") return $r;
            }
        }

        return "";
    }
}
