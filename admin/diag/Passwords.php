<?php

class Passwords extends Studio\Util\Diagnostic
{
    var $name = "Test secure password hashing";

    function __construct($sql) {
        $salt = str_replace("+", ".", substr(base64_encode(rand(111111,999999).rand(111111,999999).rand(11111,99999)), 0, 22));
        $salt = '$' . implode('$', array("2y", str_pad(11, 2, "0", STR_PAD_LEFT), $salt));
        $password = @crypt("abc123", $salt);

        if (!$password) $this->fail("Secure salt password crypt failed. This is severe.");

        $this->pass();
    }
}
