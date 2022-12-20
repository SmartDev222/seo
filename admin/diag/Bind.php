<?php

class Bind extends Studio\Util\Diagnostic
{
    var $name = "Check mysqlnd support";

    function __construct($sql) {
        if (!function_exists('mysqli_stmt_bind_param')) $this->fail("mysqlnd not installed, fallback support will be used.");

        // an error will be outputted if the library fails.
        // diagnostics script will see the error and show that there was a problem.

        $this->pass();
    }
}
