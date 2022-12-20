<?php

namespace Studio\Common;

use Exception;

class Activity
{
    public $message;
    public $type;
    public $time;

    const ERROR = "error";
    const WARNING = "warn";
    const NOTICE = "notice";
    const INFO = "info";
    const SUCCESS = "success";

    public function __construct($type, $message, $time = 0) {
        $types = array("error", "warn", "notice", "info", "success");

        $this->type = $type;
        $this->message = $message;
        $this->time = $time;

        if (!in_array($type, $types)) throw new Exception("Unknown activity type '$type'");
        if ($this->time == 0) $this->time = time();
    }
}
