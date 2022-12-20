<?php

namespace Studio\Util;

class Diagnostic
{
    var $name = "Unknown test";

    function fail($message="", $fix="") {
        die(json_encode([
            'test' => $this->name,
            'success' => false,
            'message' => $message,
            "execute" => $fix
        ]));
    }

    function pass($message="", $fix="") {
        die(json_encode([
            'test' => $this->name,
            'success' => true,
            'message' => $message,
            "execute" => $fix
        ]));
    }
}
