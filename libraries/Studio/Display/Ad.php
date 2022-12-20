<?php

namespace Studio\Display;
use Exception;

class Ad
{
    function __construct($size, $customCSS = "") {
        $allowed = array("728x90", "120x600", "250x250", "468x60");
        if (!in_array($size, $allowed)) throw new Exception("Ad size '$size' is not supported at this time.");

        $this->show($size, $customCSS);
    }

    private function show($size, $css) {
        global $studio;

        if ($studio->getopt("enable-ads") != "On") return;
        if ($studio->getopt("ad-$size") == "") return;

        echo "<div class='ab' style='$css'>";
        echo $studio->getopt("ad-$size");
        echo "</div>";
    }
}
