<?php

class Zip extends Studio\Util\Diagnostic
{
    var $name = "Test file compression library";

    function __construct($sql) {
        $zip = new Studio\Util\Zip;
        $zip->create_dir("hello/");
        $zip->create_file("1234", "hello/world.txt");
        $zip->zipped_file();

        // an error will be outputted if the library fails.
        // diagnostics script will see the error and show that there was a problem.

        $this->pass();
    }
}
