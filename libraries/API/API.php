<?php

namespace API;

use API\Helper\CURL;

class API
{
    private $authorized = false;
    private $key;

    function __construct($key = "") {
        $this->key = $key;
        if ($key) $this->authorized = true;
    }

    function setAuthorized($authorized = false) {
        $this->authorized = $authorized;
    }

    function getAuthorized() {
        return $this->authorized;
    }

    function isAuthorized() {
        return $this->getAuthorized();
    }

    function getAuthorizationURL() {
        return "https://api.getseostudio.com/v1/authorization?method=connect&redirect_uri=";
    }

    function getPurchaseURL() {
        return "https://api.getseostudio.com/v1/buy";
    }

    function getApplicationInfo() {
        $ch = new CURL("/application/info", $this->key);
        return $ch->exec();
    }

    function getLatestNews() {
        $ch = new CURL("/public/news");
        $data = $ch->exec();

        return $data->html;
    }

    function getNewMarketItems($since = 0) {
        return 0;
    }

    function updateURL($public_url) {
        $ch = new CURL("/application/update", $this->key, 5);
        $ch->post(array(
            "public_url" => $public_url
        ));

        return $ch->exec();
    }

    function checkCronEligible($url) {
        $ch = new CURL("/cron/eligible?url=" . urlencode($url), $this->key);
        $data = $ch->exec();

        return $data->eligible;
    }

    function enableCron($url) {
        $ch = new CURL("/cron/enable?url=" . urlencode($url), $this->key, 15);
        $data = $ch->exec();

        return $data;
    }

    function disableCron() {
        $ch = new CURL("/cron/disable", $this->key);
        $data = $ch->exec();

        return $data;
    }

    function getCronInfo() {
        $ch = new CURL("/cron/info", $this->key);
        return $ch->exec();
    }

    function enablePushUpdates($url) {
        $ch = new CURL("/updates/push-enable?url=" . urlencode($url), $this->key, 15);
        return $ch->exec();
    }

    function disablePushUpdates() {
        $ch = new CURL("/updates/push-disable", $this->key);
        $data = $ch->exec();

        return $data->disabled;
    }

    function getAvailableUpdates($curVersion = 1) {
        $x = defined('BETA') ? '&beta=1' : '';
        $ch = new CURL("/updates/available?version=" . urlencode($curVersion) . $x, $this->key, 10);
        return $ch->exec();
    }

    function getAvailableItemUpdates($items) {
        $ch = new CURL("/updates/items", $this->key, 15);
        $ch->post(array(
            "items" => json_encode($items)
        ));

        return $ch->exec();
    }

    function downloadUpdate($token) {
        $ch = new CURL("/updates/download?token=" . urlencode($token), $this->key, 20);
        return $ch->execRaw();
    }

    function downloadItem($id) {
        $ch = new CURL("/updates/download-item?id=" . urlencode($id), $this->key, 20);
        return $ch->execRaw();
    }

    function reportError($type, $message, $file, $line, $studio_version, $php_version, $anonymous = 1) {
        $ch = new CURL("/report/error", $this->key);
        $ch->post(array(
            "type" => $type,
            "message" => $message,
            "file" => $file,
            "line" => $line,
            "studio_version" => $studio_version,
            "php_version" => $php_version,
            "anonymous" => $anonymous
        ));

        $data = $ch->exec();
        return $data->ray;
    }

    function sendFeedback($message) {
        $ch = new CURL("/report/feedback", $this->key);
        $ch->post(array(
            "message" => $message
        ));

        $data = $ch->exec();
        return $data->message;
    }

    function reportUsage($json) {
        $ch = new CURL("/report/usage", $this->key);
        $ch->post(array(
            "report" => $json
        ));

        $data = $ch->exec();
        return $data->success;
    }

    function getTicketList() {
        $ch = new CURL("/support/tickets", $this->key);
        $data = $ch->exec();
        return $data;
    }

    function createTicket($email, $message, $subject, $mailbox = "support") {
        $ch = new CURL("/support/new-ticket", $this->key);
        $ch->post(array(
            "email" => $email,
            "message" => $message,
            "subject" => $subject,
            "mailbox" => $mailbox
        ));

        $data = $ch->exec();
        return $data->success;
    }

    function createTicketResponse($id, $message) {
        $ch = new CURL("/support/new-post", $this->key);
        $ch->post(array(
            "id" => $id,
            "message" => $message
        ));

        $data = $ch->exec();
        return $data->success;
    }

    function enableGoogle($url, $email) {
        $ch = new CURL("/google/enable?url=" . urlencode($url) . "&email=" . urlencode($email), $this->key, 15);
        $data = $ch->exec();

        return $data->success;
    }

    function disableGoogle() {
        $ch = new CURL("/google/disable", $this->key);
        $data = $ch->exec();

        return $data->success;
    }

    function getGoogleHTML($query, $tld, $num, $start, $countryCode = null, $uule = null) {
        $ch = new CURL("/google/get", $this->key, 20);
        $ch->post(array(
            "query" => $query,
            "tld" => $tld,
            "num" => $num,
            "start" => $start,
            "country" => $countryCode,
            "uule" => $uule
        ));

        $data = $ch->exec();

        if ($data->success) return $data->data;
        return null;
    }

    function getGoogleSuggestions($query, $language, $country) {
        $ch = new CURL("/google/suggestions", $this->key, 20);
        $ch->post(array(
            "query" => $query,
            "hl" => $language,
            "gl" => $country
        ));

        $data = $ch->exec();

        if ($data->success) return $data->data;
        return null;
    }

    function getBingHTML($query, $start, $num = 10) {
        $ch = new CURL("/google/bing", $this->key, 20);
        $ch->post(array(
            "query" => $query,
            "page" => $start,
            "num" => $num
        ));

        $data = $ch->exec();

        if ($data->success) return $data->data;
        return null;
    }

    function getGoogleStats() {
        $ch = new CURL("/google/stats", $this->key);
        return $ch->exec();
    }

    function getTranslations($languageCode) {
        $ch = new CURL("/translations/get?code=" . urlencode($languageCode), $this->key, 10);
        return $ch->exec();
    }

    function setKey($key) {
        $this->key = $key;
        if ($key) $this->authorized = true;
    }

    function getKey($key) {
        return $this->key;
    }
}
