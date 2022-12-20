<?php

namespace Studio\Extend;

class PluginManager
{
    private $plugins = array();
    private $hooks = array();

    /**
     * Returns an array of Plugins representing all active plugins.
     * @return array<Plugin> array of all loaded plugins.
     */
    public function getPlugins() {
        return $this->plugins;
    }

    /**
     * Registers and enables a plugin in the system.
     * @param Plugin $plugin The plugin to enable.
     */
    public function registerPlugin($plugin) {
        $this->plugins[] = $plugin;
    }

    public function call($action, $pass = array()) {
        foreach ($this->hooks as $hook) {
            if ($hook['action'] == $action) {
                call_user_func_array(array($hook['plugin'], $hook['run']), $pass);
            }
        }
    }

    public function callCombined($action, $pass = array()) {
        $returns = [];

        foreach ($this->hooks as $hook) {
            if ($hook['action'] == $action) {
                $r = call_user_func_array(array($hook['plugin'], $hook['run']), $pass);

                if ($r != null && is_array($r)) $returns = array_merge($returns, $r);
                else if ($r != null) $returns[] = $r;
            }
        }

        return $returns;
    }

    public function start() {
        foreach ($this->plugins as $p) {
            $p->start();
        }
    }

    public function newHook($action, $plugin, $name) {
        $this->hooks[] = array(
            'action' => $action,
            'plugin' => $plugin,
            'run' => $name
        );
    }

    public function callMethod($method) {

    }

    /**
     * Synchronizes plugins from the plugins directory on the disk.
     *
     * @return void
     */
    public static function registerPluginsFromDisk() {
        global $studio;

        $pluginsDir = $studio->basedir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins";
        $new = 0;

        foreach (scandir($pluginsDir) as $dir) {
            if ($dir == "." || $dir == "..") continue;
            if (substr($dir, 0, 1) == ".") continue;
            if (!is_dir($pluginsDir . "/" . $dir)) continue;

            $p = $studio->sql->prepare("SELECT * FROM plugins WHERE directory = ?");
            $p->bind_param("s", $dir);
            $p->execute();
            $p->store_result();

            if ($p->num_rows == 0) {
                $p->close();

                $className = $dir;
                $file = "$pluginsDir/$dir/$className.php";
                if (!file_exists($file)) continue;
                require_once $file;

                if (!class_exists($className, false)) {
                    $renameTo = "$pluginsDir/.$dir";
                    $op = "renamed to .$dir";

                    if (file_exists($renameTo)) {
                        if (!delTree("$pluginsDir/$dir")) {
                            die("Please manually delete this directory to proceed: $pluginsDir/$dir");
                        }

                        $op = "deleted";
                    }
                    else {
                        if (!rename("$pluginsDir/$dir", $renameTo)) {
                            $op = "deleted";

                            if (!delTree("$pluginsDir/$dir")) {
                                die("Please manually delete this directory to proceed: $pluginsDir/$dir");
                            }
                        }
                    }

                    die("
                        <h1>Warning!</h1><br>
                        <p>Attempted to load the plugin at <strong>{$file}</strong> but it did not contain the required class <strong>{$className}</strong>.</p>
                        <p>To avoid system errors, the plugin directory has been {$op}. <a href=''>Click here to continue.</a></p>
                    ");
                }

                $o = new $className;
                $name = $o::NAME;
                $version = $o::VERSION;

                $p = $studio->sql->prepare("INSERT INTO plugins (name, directory, market_id, enabled, version, update_available) VALUES (?, ?, 0, 0, ?, '')");
                $p->bind_param("sss", $name, $dir, $version);
                $p->execute();

                if ($p->error) {
                    die("An error occurred when registering the plugin '{$dir}': " . $p->error);
                }

                $p->close();

                $new++;
                unset($o);
            }
        }

        return $new;
    }
}
