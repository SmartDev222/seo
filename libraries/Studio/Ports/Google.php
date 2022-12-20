<?php

namespace Studio\Ports;

use Exception;

class Google extends \SEO\Services\Google
{
    public function query($query, $page = 1, $num = 10, $html = null) {
        global $studio, $api;

        if ($studio->getopt('google-enabled') == 'On' && isset($api)) {
            $nextTime = $studio->getopt('google-next-time', 0);
            $forceRemote = $studio->getopt('google-force-remote', false);
            $attemptedRemote = false;

            if ($nextTime > time() || $forceRemote) {
                try {
                    // The last request was too recent, so let's use the network instead
                    return $this->fetchRemotely($query, $page, $num);
                }
                catch (Exception $ex) {
                    $attemptedRemote = true;
                }
            }

            // The last request was long enough ago, let's try to use our own server
            try {
                $result = parent::query($query, $page, $num, $html);
                $studio->setopt('google-consecutive-failures', 0);
                $studio->setopt('google-next-time', time() + 180);

                return $result;
            }
            catch (Exception $e) {
                if ($e->getCode() == 1) {
                    // The request was blocked
                    $consecutive = $studio->getopt('google-consecutive-failures', 0);
                    $studio->setopt('google-consecutive-failures', $consecutive + 1);

                    // Determine the next request time
                    $duration = 3600;
                    if ($consecutive >= 1 && $consecutive <= 6) $duration = 3600 * $consecutive;
                    else if ($consecutive >= 7) $duration = 86400;
                    $studio->setopt('google-next-time', time() + $duration);

                    // Fetch from the network
                    if (!$attemptedRemote) {
                        return $this->fetchRemotely($query, $page, $num);
                    }
                }

                throw $e;
            }
        }
        else {
            return parent::query($query, $page, $num, $html);
        }
    }

    private function fetchRemotely($query, $page, $num) {
        global $language, $api;

        $tld = (isset($language)) ? $language->google : '.com';
        $start = ($num * $page) - $num;
        $google = $api->getGoogleHTML($query, $tld, $num, $start, $this->countryCode, $this->getUULE());

        return parent::query($query, $page, $num, $google);
    }
}
