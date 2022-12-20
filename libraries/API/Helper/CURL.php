<?php

namespace API\Helper;

use Studio\Base\Studio;
use Exception;

class CURL
{
    private $ch;
    public $options;

    public $errno;
    public $error;

    public function __construct($path, $auth = "", $timeout = 5) {
        global $studio;

        $this->ch = curl_init("https://api.getseostudio.com/v1{$path}");
        $this->options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => "SEO Studio v" . Studio::VERSION_STR
        );

        if ($auth) $this->options[CURLOPT_HTTPHEADER] = array("Authorization: $auth");
    }

    public function exec($array = false) {
        foreach ($this->options as $opt => $val) {
            curl_setopt($this->ch, $opt, $val);
        }

        $data = @curl_exec($this->ch);
        $this->errno = curl_errno($this->ch);
        $this->error = curl_error($this->ch);

        if ($this->errno == 6 || $this->errno == 7 || $this->errno == 28) throw new Exception("We can't connect to the API right now, so some functions may not work properly.");
        if ($this->errno == 35) throw new Exception("Failed to establish SSL connection with the API.");
        if ($this->errno > 0) throw new Exception("An error occurred when connecting to the API: {$this->error}");

        $json = @json_decode($data, true);
        if (json_last_error() != JSON_ERROR_NONE) throw new Exception("Received a bad response from the API server.");

        if (isset($json['success']) && !$json['success']) {
            if (isset($json['msg'])) throw new Exception($json['msg']);
            if (isset($json['message'])) throw new Exception($json['message']);
        }
        if (isset($json['error'])) {
            if (isset($json['msg'])) throw new Exception($json['msg']);
            if (isset($json['message'])) throw new Exception($json['message']);
        }

        $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if ($code != 200) throw new Exception("Got unexpected status code: $code");

        if (!$array) $json = (object)$json;
        return $json;
    }

    public function execRaw() {
        foreach ($this->options as $opt => $val) {
            curl_setopt($this->ch, $opt, $val);
        }

        $data = @curl_exec($this->ch);
        $this->errno = curl_errno($this->ch);
        $this->error = curl_error($this->ch);

        if ($this->errno == 6 || $this->errno == 7 || $this->errno == 28) throw new Exception("We can't connect to the API right now, so some functions may not work properly.");
        if ($this->errno == 35) throw new Exception("The API server failed to prove its identity, so we rejected the request.");
        if ($this->errno > 0) throw new Exception("An error occurred when connecting to the API: {$this->error}");

        $json = @json_decode($data, true);
        if (json_last_error() == JSON_ERROR_NONE) {
            if (isset($json['success']) && !$json['success']) throw new Exception($json['message']);
            if (isset($json['error'])) {
                if (isset($json['msg'])) throw new Exception($json['msg']);
                if (isset($json['message'])) throw new Exception($json['message']);
            }
        }

        $code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        if ($code != 200) throw new Exception("Got unexpected status code: $code");

        return $data;
    }

    public function post($fields) {
        $this->options[CURLOPT_POST] = true;
        $this->options[CURLOPT_POSTFIELDS] = $fields;
    }
}
