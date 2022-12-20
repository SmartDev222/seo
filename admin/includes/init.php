<?php

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 'On');

ob_start();
session_start();

require_once dirname(dirname(dirname(__FILE__))) . "/libraries/autoload.php";

$studio = new \Studio\Base\Studio();
$studio->errors->register('admin');
$studio->errors->allowErrorOutput = true;
$studio->errors->allowErrorLogging = false;

$plugins = $studio->getPluginManager();
$account = new \Studio\Base\Account($studio);
$page = new \Studio\Display\AdminPage($studio);
$api = new \API\API($studio->getopt("api.secretkey"));

if (!defined('DEMO')) {
    define('DEMO', false);
    define('DEMO_USER', '');
    define('DEMO_PASS', '');
}

# Call the studio_loaded plugin hook

$plugins->start();
$plugins->call("studio_loaded");

$update_count = 0;

# Find the number of available updates

$q = $studio->sql->query("SELECT COUNT(*) FROM updates WHERE updateStatus != 1");
$r = $q->fetch_array();
$update_count += $r[0];

$q = $studio->sql->query("SELECT COUNT(*) FROM plugins WHERE update_available != ''");
$r = $q->fetch_array();
$update_count += $r[0];

if (!function_exists('sanitize_html')) {
	/**
	 * Sanitizes the output for inclusion in HTML. The angle bracket characters (<>) will be escaped.
	 *
	 * @param mixed $output
	 * @return string
	 */
    function sanitize_html($output) {
        return Sanitize::html($output);
    }
}

if (!function_exists('sanitize_trusted')) {
	/**
	 * Sanitizes trusted output.
	 *
	 * @param mixed $output
	 * @return string
	 */
    function sanitize_trusted($output) {
        return Sanitize::trusted($output);
    }
}

if (!function_exists('sanitize_generic')) {
	/**
	 * Sanitizes untrusted output for generic usage (within an attribute or HTML).
	 *
	 * @param mixed $output
	 * @return string
	 */
    function sanitize_generic($output) {
        return Sanitize::generic($output);
    }
}

if (!function_exists('sanitize_attribute')) {
	/**
	 * Sanitizes the given output for use within an HTML attribute.
	 *
	 * @param mixed $output
	 * @param int $quotes Which style of quotes to escape (defaults to both).
	 * @return string
	 */
    function sanitize_attribute($output, $quotes = QUOTES_SINGLE | QUOTES_DOUBLE) {
        return Sanitize::attribute($output, $quotes);
    }
}

?>
