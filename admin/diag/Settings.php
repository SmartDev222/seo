<?php

class Settings extends Studio\Util\Diagnostic
{
    var $name = "Check settings are valid";

    function __construct($sql) {
        global $studio;

        $bools = ["allow-registration", "show-login", "email-verification", "cache", "send-errors", "send-usage-info", "automatic-updates", "updates-skip-modified",
                  "automatic-updates-purchased", "automatic-updates-backup", "push-updates", "ssl-updates", "errors-anonymous", "google-enabled"];

        foreach ($bools as $setting) {
            $value = $studio->getopt($setting);
            if ($value != "On" && $value != "Off") $this->fail("Boolean option '$setting' has an invalid value.");
        }

        $this->pass();
    }
}
