<?php

namespace Studio\Tools;

class SpeedTest extends Tool
{
    var $name = "Speed Test";
    var $id = "speed-test";
    var $icon = "speed-test";
    var $template = "speedtest.html";

    protected $chosenRegion = null;

    public function prerun($url) {
        $this->template = 'speedtest-inactive.html';

        if (isset($_POST['region'])) {
            $this->chosenRegion = trim(strtolower($_POST['region']));

            $found = false;
            foreach ($this->getRegions() as $id) {
                if ($id == $this->chosenRegion) $found = true;
            }

            if ($found) {
                $this->template = 'speedtest.html';
            }
            else {
                $this->chosenRegion = null;
            }
        }
    }

    /**
     * Returns the region (or `undefined`) to run.
     */
    private function getActiveRegion() {
        return $this->chosenRegion;
    }

    /**
     * Returns the region to select in the dropdown.
     */
    private function getSelectedRegion() {
        global $studio;

        if (!is_null($this->chosenRegion)) {
            return $this->chosenRegion;
        }

        if (isset($_SESSION['speedtest_region'])) {
            return $_SESSION['speedtest_region'];
        }

        return $studio->getopt('speedtest-default-region', 'us-east-1');
    }

    /**
     * Starts a new test and returns the Test ID, or returns `null` if the test couldn't be started (e.g. during high
     * load tests may be rejected).
     *
     * @param string $region
     * @return string|null
     */
    private function startTest($region) {
        $ch = new \Studio\Util\CURL("https://tools.pingdom.com/v1/tests/create");
        $ch->setopt(CURLOPT_TIMEOUT, 10);
        $ch->setopt(CURLOPT_POSTFIELDS, json_encode(array(
            "region" => $region,
            "url" => "http://{$this->url->domain}/"
        )));
        $ch->setopt(CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, 0);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, false);
        $ch->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36");
        $ch->setopt(CURLOPT_REFERER, "https://tools.pingdom.com/");
        $ch->get();

        $data = $ch->data;
        $data = json_decode($data, true);

        if (isset($data['id'])) {
            return $data['id'];
        }
    }

    public function run() {
        global $studio;

        @ini_set('max_execution_time', 180);

        if (OPENSSL_VERSION_NUMBER < 0x009080bf) {
            throw new \Exception('Please upgrade to OpenSSL 1.0.0 or above to use this tool.');
        }

        $testId = null;
        $region = $this->getActiveRegion();
        $this->data['region'] = $region;

        // Don't run until the user selects a region
        if (!$region) {
            return;
        }

        // Save their selection in the session for convenience
        $_SESSION['speedtest_region'] = $region;

        for ($i = 0; $i < 3; $i++) {
            if ($testId = $this->startTest($region)) {
                break;
            }
            else {
                if ($i === 2) {
                    throw new \Exception('Test server is busy, please try again in a few minutes.');
                }

                sleep(2);
            }
        }

        $statusURL = "https://tools.pingdom.com/v1/tests/{$testId}";
        $i = 0;
        while (true) {
            if (++$i > 8) throw new \Exception(rt("Timeout"));

            sleep(3);

            $ch = new \Studio\Util\CURL($statusURL);
            $ch->setopt(CURLOPT_TIMEOUT, 20);
            $ch->setopt(CURLOPT_SSL_VERIFYHOST, 0);
            $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
            $ch->setopt(CURLOPT_FOLLOWLOCATION, false);
            $ch->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36");
            $ch->setopt(CURLOPT_REFERER, "https://tools.pingdom.com/");
            $ch->get();

            $data = json_decode($ch->data, true);

            // Ping the database connection
            // This is necessary on servers with short timeouts because this tool can take so long to compute
            @$studio->sql->ping();

            if ($ch->info[CURLINFO_HTTP_CODE] === 404) {
                // At this point, a 404 means we should try again momentarily
                continue;
            }

            if (!isset($data['status'])) {
                throw new \Exception('Test server is busy, please try again in a few minutes.');
            }

            if ($data['status'] == 3) {
                $resources = $data['resources'];

                if (!isset($resources['metrics']) || !isset($resources['har'])) {
                    throw new \Exception('Test server is busy, please try again in a few minutes.');
                }

                $this->getResource('har', $resources['har']);
                $this->getResource('metrics', $resources['metrics']);

                // Make sure our data is valid
                if (!is_object($this->data['metrics']) || !isset($this->data['metrics']->yslow_score)) {
                    throw new \Exception('Unexpected error, please try again later');
                }

                if (isset($this->data['har']->log) && !is_object($this->data['har']->log)) {
                    throw new \Exception('Test server is busy, please try again in a few minutes.');
                }

                break;
            }
        }
    }

    protected function getResource($name, $resource) {
        $ch = new \Studio\Util\CURL("https://tools.pingdom.com" . $resource);
        $ch->setopt(CURLOPT_TIMEOUT, 15);
        $ch->setopt(CURLOPT_SSL_VERIFYHOST, 0);
        $ch->setopt(CURLOPT_SSL_VERIFYPEER, false);
        $ch->setopt(CURLOPT_FOLLOWLOCATION, false);
        $ch->setopt(CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36");
        $ch->setopt(CURLOPT_REFERER, "https://tools.pingdom.com/");
        $ch->get();

        $this->data[$name] = json_decode($ch->data);
    }

    protected function getResponseCodes() {
        $entries = $this->data['har']->log->entries;
        $codes = array(404 => 0, 403 => 0, 401 => 0, 302 => 0, 301 => 0, 200 => 0, 204 => 0);

        foreach ($entries as $entry) {
            $code = $entry->response->status;

            if (isset($codes[$code])) {
                $codes[$code] ++;
            }
            else {
                $codes[$code] = 1;
            }
        }

        return $codes;
    }

    protected function getRequestsByContentType() {
        $entries = $this->data['har']->log->entries;
        $types = array();

        foreach ($entries as $entry) {
            $type = $entry->response->content->_resourceType;

            if (isset($types[$type])) {
                $types[$type]['requests'] += 1;
            }
            else {
                $types[$type] = array(
                    'content' => $type,
                    'requests' => 1
                );
            }
        }

        foreach ($types as $type => $params) {
            $types[$type]['percent'] = round(($params['requests'] / count($entries)) * 100, 2) . '%';
        }

        usort($types, function($a, $b) {
            if ($a['requests'] == $b['requests']) return 0;
            return $a['requests'] < $b['requests'] ? 1 : -1;
        });

        return $types;
    }

    protected function getSizeByContentType() {
        $entries = $this->data['har']->log->entries;
        $types = array();
        $totalSize = 0;

        foreach ($entries as $entry) {
            $type = $entry->response->content->_resourceType;
            $size = $entry->response->content->size;
            $totalSize += $size;

            if (isset($types[$type])) {
                $types[$type]['requests'] += 1;
                $types[$type]['size'] += $size;
            }
            else {
                $types[$type] = array(
                    'content' => $type,
                    'requests' => 1,
                    'size' => $size
                );
            }
        }

        foreach ($types as $type => $params) {
            $types[$type]['percent'] = ($totalSize > 0 ? round(($params['size'] / $totalSize) * 100, 2) : 0) . '%';
        }

        usort($types, function($a, $b) {
            if ($a['size'] == $b['size']) return 0;
            return $a['size'] < $b['size'] ? 1 : -1;
        });

        foreach ($types as $type => $params) {
            if ($params['size'] >= 1000000) $types[$type]['size'] = round($params['size'] / 1000000, 1) . ' MB';
            else $types[$type]['size'] = round($params['size'] / 1000, 1) . ' KB';
        }

        if ($totalSize >= 1000000) $this->data['totalSize'] = round($totalSize / 1000000, 1) . ' MB';
        else $this->data['totalSize'] = round($totalSize / 1000, 1) . ' KB';

        return $types;
    }

    public function output() {
        $html = $this->getTemplate();
        $region = $this->getSelectedRegion();

        if (isset($this->data['har'])) {
            $units = array(
                'UNIT_MILLISECOND' => 'ms',
                'UNIT_SECOND' => 's'
            );

            $metrics = $this->data['metrics'];

            $pageOverallScore = $metrics->yslow_score->current . '%';
            $pageOverallLoadTime = $metrics->load_time->current . ' ' . $units[$metrics->load_time->unit];
            $pageOverallSize = $metrics->size->current;
            $pageOverallRequests = $metrics->request->current;

            switch ($metrics->size->unit) {
                case 'UNIT_KILOBYTE':
                    $pageOverallSize *= 1000;
                    break;
                case 'UNIT_MEGABYTE':
                    $pageOverallSize *= 1000000;
                    break;
            }

            if ($pageOverallSize >= 1000000) $pageOverallSize = round($pageOverallSize / 1000000, 1) . ' MB';
            else $pageOverallSize = round($pageOverallSize / 1000, 1) . ' KB';

            $html = str_replace("[[SCORE]]", $pageOverallScore, $html);
            $html = str_replace("[[TIME]]", $pageOverallLoadTime, $html);
            $html = str_replace("[[SIZE]]", $pageOverallSize, $html);
            $html = str_replace("[[REQUESTS]]", $pageOverallRequests, $html);

            $codes = $this->getResponseCodes();

            $sizeByContentType = $this->getSizeByContentType();
            $requestsByContentType = $this->getRequestsByContentType();

            foreach ($codes as $code => $numRequests) {
                $html = str_replace("[[REQUESTS:{$code}]]", number_format($numRequests), $html);
            }

            $contentSizeHTML = '';

            foreach ($sizeByContentType as $i => $resource) {
                $type = $resource['content'];
                $percent = $resource['percent'];
                $size = $resource['size'];

                $odd = (($i % 2 == 0) ? "odd" : "");

                $contentSizeHTML .= ("
                    <tr class='$odd'>
                        <td>{$type}</td>
                        <td>{$percent}</td>
                        <td>{$size}</td>
                    </tr>
                ");
            }

            $html = str_replace('[[DYNAMIC_CONTENT_SIZE]]', $contentSizeHTML, $html);

            $contentRequestsHTML = '';

            foreach ($requestsByContentType as $i => $resource) {
                $type = $resource['content'];
                $percent = $resource['percent'];
                $requests = $resource['requests'];

                $odd = (($i % 2 == 0) ? "odd" : "");

                $contentRequestsHTML .= ("
                    <tr class='$odd'>
                        <td>{$type}</td>
                        <td>{$percent}</td>
                        <td>{$requests}</td>
                    </tr>
                ");
            }

            $html = str_replace('[[DYNAMIC_CONTENT_REQUESTS]]', $contentRequestsHTML, $html);
        }

        $regionsHTML = '';
        foreach ($this->getRegions() as $name => $id) {
            $selected = $region == $id ? 'selected' : '';
            $regionsHTML .= ("
                <option ${selected} value='{$id}'>{$name}</option>
            ");
        }

        $html = str_replace('[[REGIONS]]', $regionsHTML, $html);

        echo sanitize_trusted($html);
    }

    protected function getCacheKey() {
        $region = $this->getActiveRegion();
        $block = floor(time() / 120);

        if (!$region) {
            $region = '@def';
        }

        return ("speedtest:{$region}:{$block}");
    }

    public function getRegions() {
        static $regions = array(
            'Tokyo, Japan' => 'ap-northeast-1',
            'Frankfurt, Germany' => 'eu-central-1',
            'London, United Kingdom' => 'eu-west-2',
            'Washington D.C, United States' => 'us-east-1',
            'San Francisco, United States' => 'us-west-1',
            'Sydney, Australia' => 'ap-southeast-2',
            'São Paulo, Brazil' => 'sa-east-1'
        );

        return $regions;
    }

}
