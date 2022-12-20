<?php

namespace Studio\Common;

use Studio\Base\Studio;
use Exception;

class Usage
{
    const SAVE_FILENAME = "last-usage.txt";

    public $report;

    /**
     * Generates a new anonymous usage report.
     */
    public function generate() {
        global $studio, $api;
        if (!isset($studio)) throw new Exception("Missing studio global.");
        if (!isset($api)) throw new Exception("Missing api global.");

        $lastTime = $studio->getopt('last-uinfo-report');

        $cv = curl_version();
        $data = array(
            'php' => array(
                'version' => phpversion(),
                'extensions' => array(
                    'mysqli' => phpversion('mysqli'),
                    'curl' => $cv['version'],
                    'json' => phpversion('json'),
                    'zip' => phpversion('zip')
                )
            ),
            'studio' => array(
                'version' => Studio::VERSION,
                'auth' => $api->isAuthorized()
            ),
            'curl' => array(
                'ssl' => ($cv['features'] & CURL_VERSION_SSL),
                'zlib' => $cv['libz_version'],
                'ssl_version' => $cv['ssl_version']
            ),
            'mysql' => $studio->sql->server_info,
            'most_popular_tools' => array()
        );

        if (!is_numeric($lastTime)) $lastTime = 0;
        $q = $studio->sql->query("SELECT useTime, toolId FROM history WHERE useTime > $lastTime");
        while ($row = $q->fetch_array()) {
            $id = $row['toolId'];
            if (!isset($data['most_popular_tools'][$id])) $data['most_popular_tools'][$id] = 1;
            else $data['most_popular_tools'][$id]++;
        }

        if (defined("JSON_PRETTY_PRINT")) {
            $report = json_encode($data, JSON_PRETTY_PRINT);
        }
        else {
            $report = json_encode($data);
        }

        $this->report = $report;
        return $this;
    }

    /**
     * Sends the generated report to the update server.
     */
    public function send() {
        global $studio, $api;

        try {
            if ($api->reportUsage($this->report)) {
                $studio->setopt('last-uinfo-report', time());
                $this->saveFile();
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
            die;
        }

        return $this;
    }

    /**
     * Saves the generated report to the disk for the user to view it.
     */
    public function saveFile() {
        $reportSizeBytes = strlen($this->report) + 4;
        $available = @disk_free_space(__DIR__);

        if ($available !== false && $available <= $reportSizeBytes) {
            return $this;
        }

        $filename = self::SAVE_FILENAME;
        $path = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/bin/{$filename}";
        if (is_writable(dirname($path))) @file_put_contents($path, $this->report);
        return $this;
    }
}

?>
