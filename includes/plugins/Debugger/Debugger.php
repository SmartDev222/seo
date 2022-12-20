<?php

class Debugger extends Studio\Extend\Plugin {

    const NAME = "Debugger";
    const DESCRIPTION = "This plugin allows customer support to remotely debug complex issues with your installation.";
    const VERSION = "1.0";

    var $settings = true;

    function settings() {
        global $studio;

        require dirname(__FILE__) . '/settings.php';
    }

    function onEnable() {
        $this->setopt("remote-debugging-enabled", true);
        $this->setopt("remote-debugging-code", '');
        $this->setopt("remote-debugging-sessions", base64_encode(serialize([])));

        return true;
    }

    function onDisable() {
        $this->setopt("remote-debugging-enabled", false);
    }

}
