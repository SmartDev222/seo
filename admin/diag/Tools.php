<?php

class Tools extends Studio\Util\Diagnostic
{
    var $name = "Check tools and categories validity";

    function __construct($sql) {
        global $studio;

        $tools = $studio->getopt("tools");
        $categories = $studio->getopt("categories");

        $b = @unserialize($tools);
        if (!is_array($b)) $this->fail("Opt 'tools' is corrupt.", "db-reset-tools.php");

        $b = @unserialize($categories);
        if (!is_array($b)) $this->fail("Opt 'categories' is corrupt.", "db-reset-tools.php");

        $this->pass();
    }
}
