<?php

namespace Studio\Base;

use Exception;
use mysqli;
use Studio\Tools\Tool;

class Studio extends PlatformMeta
{
    public $config;
    /**
     * @var mysqli
     */
    public $sql;
    private $account;
    private $page;
    private $pluginManager;

    /**
     * The error handler for this studio.
     *
     * @var ErrorHandler
     */
    public $errors;

    /**
     * The permalink handler for this studio.
     *
     * @var PermalinkHandler
     */
    public $permalinks;

    public $bindir;
    public $basedir;

    /**
     * A boolean containing whether or not the user is logged in.
     */
    public $logged_in;

    /**
     * The internal path for redirects. Not to be confused with $page->path.
     */
    public $path;

    private $optCache = array();
    private $optMissingCache = array();

    public function __construct() {
        global $load_plugins;

        $this->errors = new ErrorHandler($this);

        $base = dirname(dirname(dirname(dirname(__FILE__))));
        if (file_exists($base . "/lock") || file_exists($base . "/resources/bin/lock")) {
            die(
                "<h1>Critical error</h1><p>A recent update failed during file extraction. In order to protect the " .
                "system, the site has been disabled. Please contact customer support for help on restoring the " .
                "overwritten files."
            );
        }

        $configPath = dirname(dirname(dirname(dirname(__FILE__)))) . "/config.php";
        if (!file_exists($configPath)) throw new Exception("Config file does not exist.", 1);
        $this->config = require_once $configPath;

        if (!defined('DEMO')) {
            define('DEMO', false);
            define('DEMO_USER', '');
            define('DEMO_PASS', '');
        }

        $this->pluginManager = new \Studio\Extend\PluginManager();
        $this->basedir = $base;
        $this->bindir = $base . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "bin";
        $this->permalinks = new PermalinkHandler($this);

        $this->connectDatabase();
        @date_default_timezone_set($this->getopt('timezone', 'UTC'));

        if (!isset($load_plugins)) $this->loadPlugins();
    }

    /**
     * Returns the current version of the script.
     *
     * @return int
     */
    public function getVersion() {
        return self::VERSION;
    }

    /**
     * Connects to the database.
     * @throws Exception (code 2) on failure to connect to database.
     */
    private function connectDatabase() {
        global $continueWithoutSQL;

        $host = $this->config['database']['host'];
        $username = $this->config['database']['username'];
        $password = $this->config['database']['password'];
        $name = $this->config['database']['name'];
        $port = $this->config['database']['port'];

        $this->sql = @new mysqli($host, $username, $password, $name, $port);
        $error = $this->sql->connect_error;

        if ($error) {
            if (!isset($continueWithoutSQL)) {
                $base = dirname(dirname(dirname(dirname(__FILE__))));
                $data = file_get_contents($base . "/resources/bin/error.html");

                if ($this->config['errors']['show']) {
                    $data = str_replace("<!-- error -->", "Failed to connect to the database: $error", $data);
                }
                else {
                    $data = str_replace("<!-- error -->", "Failed to connect to the database.", $data);
                }

                http_response_code(500);
                die($data);
            }
            else $this->sql = null;
        }
    }

    private function loadPlugins() {
        $baseDir = dirname(dirname(dirname(dirname(__FILE__))));
        $pluginsDir = $baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins";
        $studio = $this;

        $q = $this->sql->query("SELECT id, directory FROM plugins WHERE enabled = 1");

        while ($pluginRow = $q->fetch_object()) {
            $pluginDir = $pluginRow->directory;

            $pluginPath = $pluginsDir . DIRECTORY_SEPARATOR . $pluginDir;
            $className = $pluginDir;
            $mainFile = "{$pluginPath}/$className.php";

            if (!file_exists($pluginPath)) {
                $this->sql->query("DELETE FROM plugins WHERE id = {$pluginRow->id}");
                continue;
            }

            require $mainFile;

            $plugin = new $className;
            $plugin->pluginDir = $pluginPath;
            $plugin->baseDir = $baseDir;

            $this->getPluginManager()->registerPlugin($plugin);
        }
    }

    public function getPluginManager() {
        return $this->pluginManager;
    }

    /**
     * Redirects the user to another page and terminates the existing page.
     * @param String  $location URL or file path to redirect to.
     * @param boolean $basedir  True to automatically prepend the path to the main directory.
     */
    public function redirect($location, $basedir = true) {
        if ($basedir) {
            $path = $this->path;

            if (!$this->permalinks->isNative()) {
                $path = $this->permalinks->getPathTraversal();
            }

            $location = $path . $location;
        }

        header("Location: {$location}");
        echo "<h1>This page has moved to <a href=\"{$location}\">here</a>.</h1>";
        $this->stop();
    }

    /**
     * Displays a red error message at the call location.
     * @param String $error The error message to show the user.
     */
    public function showError($error) {
        echo "<div class=\"error\">$error</div>";
    }

    /**
     * Stops the page with a fatal error message.
     * @param String $error The error message to show the user.
     */
    public function showFatalError($error) {
        echo "<h1>Fatal error</h1><p>$error</p>";
        die;
    }

    /**
     * Escapes data for placement inside an HTML tag attribute.
     * @param String $data Data to escape.
     * @return String Escaped data.
     */
    public function attr($data) {
        return str_replace('"', '&quot;', $data);
    }

    /**
     * Escapes text to prevent HTML injection.
     */
    public function ptext($text) {
        $text = str_replace("<", "&lt;", $text);
        $text = str_replace(">", "&gt;", $text);
        return $text;
    }

    /**
     * @return Tool[]
     */
    public function getTools() {
        $tools = array();
        $dir = dirname(dirname(__FILE__)) . "/Tools/";

        foreach (scandir($dir) as $file) {
            if ($file == "." || $file == "..") continue;
            if ($file == "Tool.php") continue;
            if (substr($file, -4) != ".php") continue;

            $className = "\Studio\Tools\\" . substr($file, 0, -4);
            require_once $dir . $file;

            $tools[] = new $className;
        }

        return $tools;
    }

    /**
     * @return Tool object for the requested tool ID or NULL if not exists
     */
    public function getToolById($id) {
        $list = $this->getTools();

        foreach ($list as $tool)
            if ($tool->id == $id) return $tool;

        return null;
    }

    /**
     * @param String $name of the setting/option to retrieve.
     * @param mixed $default
     * @return String containing current value of the option or NULL if not found.
     */
    function getopt($name, $default = null) {
        if (array_key_exists($name, $this->optCache)) {
            return $this->optCache[$name];
        }

        if (array_key_exists($name, $this->optMissingCache)) {
            return $default;
        }

        if ($this->sql === null) return $default;

        $query = "SELECT `name`, `value` FROM settings WHERE `name`=?";
        $p = $this->sql->prepare($query);

        // This prepared statement is randomly failing for some users
        // Let's report the error to sentry for further investigation
        if ($p === false) {
            $this->errors->sendMessage('Getopt prepared statement returned false', [], [
                'extra' => [
                    'query' => $query,
                    'optname' => $name,
                    'error' => $this->sql->error,
                    'errno' => $this->sql->errno
                ]
            ]);

            $this->showFatalError('Internal error, try again');
        }

        $p->bind_param("s", $name);
        $p->execute();
        $p->store_result();

        if ($p->num_rows !== 1) {
            $this->optMissingCache[$name] = true;
            return $default;
        }

        $n = null;
        $value = null;

        $p->bind_result($n, $value);
        $p->fetch();
        $p->close();

        $this->optCache[$name] = $value;

        return $value;
    }

    /**
     * @param String $name  of the setting/option to set.
     * @param String $value of the setting/option to set.
     */
    function setopt($name, $value = "") {
        if (array_key_exists($name, $this->optMissingCache)) {
            unset($this->optMissingCache[$name]);
        }

        if ($this->getopt($name) === null) {
            $p = $this->sql->prepare("INSERT INTO settings (name, value) VALUES (?, ?)");
            $p->bind_param("ss", $name, $value);
            $p->execute();
            $p->close();
            $this->optCache[$name] = $value;

            return true;
        }

        $p = $this->sql->prepare("UPDATE settings SET `value` = ? WHERE `name` = ?");
        $p->bind_param("ss", $value, $name);
        $p->execute();
        $p->close();
        $this->optCache[$name] = $value;
    }

    /**
     * Closes necessary objects (like MySQLi) and halts the page.
     */
    function stop() {
        $this->sql->close();

        exit;
    }

    /**
     * Inserts an activity into the database.
     * @param Studio\Common\Activity $activity The activity object to insert.
     * @throws Exception when the supplied parameter is not a valid Activity object.
     */
    public function addActivity($activity) {
        if (!($activity instanceof \Studio\Common\Activity)) {
            throw new Exception("Supplied argument is not an activity");
        }

        $p = $this->sql->prepare("INSERT INTO activity (`type`, `message`, `time`) VALUES (?, ?, ?)");
        $p->bind_param("ssi", $activity->type, $activity->message, $activity->time);
        $p->execute();
        $p->close();
    }

    /**
     * Checks and syncs the updates database with the update server if it hasn't been synced recently (otherwise skips).
     * You do not need an API token to check updates, but you'll need one to download the updates.
     * @param int $minTime How many seconds after the last update check we're allowed to re-check. (3600)
     * @param boolean $ferrors True to reset all failed updates back to "Available" status
     */
    public function checkUpdates($minTime = 3600, $ferrors = false) {
        global $api;

        if ($this->getopt('last-update-check') < (time() - $minTime)) {
            try {
                $updates = $api->getAvailableUpdates(self::VERSION);
            }
            catch (Exception $e) {
                $this->setopt('last-update-check', time());
                return;
            }

            $this->sql->query("DELETE FROM updates WHERE updateStatus <> 1");

            foreach ($updates->updates as $update) {
                $update = (object)$update;

                $this->sql->query("DELETE FROM updates WHERE token='{$update->token}' AND updateStatus = 1");

                $q = $this->sql->query("SELECT COUNT(*) FROM updates WHERE token = '{$update->token}'");
                $r = $q->fetch_array();
                if ($r[0] == 0) {
                    $files = serialize($update->affected_files);
                    $token = $update->token;
                    $name = $update->name;
                    $version = $update->version['str'];
                    $time = $update->release_time;
                    $info = $update->info;
                    $type = $update->type;
                    $warning = $update->warning;

                    $p = $this->sql->prepare("INSERT INTO updates (token, updateName, updateType, updateVersion, updateInfo, updateFiles, updateTime, updateStatus, updateError, updateWarning) VALUES (?, ?, ?, ?, ?, ?, ?, 0, '', ?)");
                    $p->bind_param("ssssssis", $token, $name, $type, $version, $info, $files, $time, $warning);
                    $p->execute();
                    $p->close();
                }
            }

            if ($ferrors) $this->sql->query("UPDATE updates SET updateStatus=0 WHERE updateStatus=2");

            // check plugins and themes

            $items = [];

            $q = $this->sql->query("SELECT * FROM plugins WHERE market_id > 0");
            while ($item = $q->fetch_object()) {
                $items[] = [
                    "id" => $item->market_id,
                    "version" => $item->version
                ];
            }

            $this->setopt('last-update-check', time());
        }
    }

    /**
     * Sends an error anonymously to the API.
     */
    public function reportError($type, $message, $file, $line) {
        global $api;

        try {
            $ray = $api->reportError($type, $message, $file, $line, self::VERSION, phpversion(), ($this->getopt("errors-anonymous") == "On"));
        }
        catch (Exception $e) {
            return 0;
        }

        return $ray;
    }

    /**
     * Returns `true` if we should use permalinks for this instance.
     *
     * @return bool
     */
    public function usePermalinks() {
        return $this->getopt('permalinks.enabled') === 'On';
    }

}
