<?php

namespace Studio\Ports;

use Exception;

class Bing extends \SEO\Services\Bing
{
    public function query($query, $start = 0, $num = 10, $html = null) {
		global $studio;

		$isGoogleEnabled = $studio->getopt('google-enabled') === 'On';
		$lastErrorTime = intval($studio->getopt('bing-error-time', '0'));
		$attemptedRemote = false;

		// Use the Google Network to get search results if we are having trouble scraping Bing
		// Yes, the Google Network supports getting Bing search results :)
		if ($isGoogleEnabled && $lastErrorTime >= time() - 86400) {
			try {
				return $this->fetchRemotely($query, $start, $num);
			}
			catch (Exception $ex) {
				$attemptedRemote = true;
			}
		}

		// The last request was long enough ago, let's try to use our own server
		try {
			$result = parent::query($query, $start, $num);
			$studio->setopt('bing-error-time', '0');

			return $result;
		}
		catch (Exception $e) {
			$studio->setopt('bing-error-time', time());

			// Fetch from the network
			if ($isGoogleEnabled && !$attemptedRemote) {
				return $this->fetchRemotely($query, $start, $num);
			}

			// Otherwise, throw the error
			throw $e;
		}
    }

    private function fetchRemotely($query, $start, $num) {
        global $api;

        $html = $api->getBingHTML($query, $start, $num);
        return parent::query($query, $start, $num, $html);
    }
}
