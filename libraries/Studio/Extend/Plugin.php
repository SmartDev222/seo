<?php

namespace Studio\Extend;

class Plugin
{
    const NAME = "";
    const DESCRIPTION = "";
    const VERSION = "";
    const AUTHOR = "";
    const WEBSITE = "";
    const DISABLED = false;

    var $settings = false;

    function start() {

    }

    function hook($name, $callback) {
        global $studio;
        $studio->getPluginManager()->newHook($name, $this, $callback);
    }

    function onEnable() {
        return true;
    }

    function onDisable() {

    }

    function onUpdate() {

    }

    function settings() {

    }

    function setopt($name, $value) {
        global $studio;
        return $studio->setopt($name, $value);
    }

    function getopt($name, $default = null) {
        global $studio;
        return $studio->getopt($name, $default);
    }

    function showError($error) {
        echo "<div class='error'>$error</div>";
        return false;
    }

    protected function validateLanguageFiles() {
        global $studio;

        $curLang = new \Studio\Base\Language($studio->bindir, "en-us");
        $missing = false;

        foreach (scandir($studio->basedir . '/resources/languages/') as $folder) {
            if ($folder == "." || $folder == "..") continue;

            $lang = new \Studio\Base\Language($studio->basedir . '/resources/languages', str_replace("/", "", $folder));
            if (count($lang->translations) < count($curLang->translations)) $missing = true;
        }

        if ($missing) {
            $studio->setopt("update-missing-translations", "1");
        }
    }

    function getPluginDirURL() {
        global $page;
        return $page->getPath() . "includes/plugins/" . basename($this->pluginDir);
    }

    function getPage() {
        global $page;
        return $page->getPage();
    }

    function installLanguageFile($filePath, $fileName = null) {
        global $studio;

        $default = $studio->basedir . "/resources/bin/en-us/";
        if ($fileName == null) $fileName = basename($filePath);
        $saveto = $default . $fileName;

        if (!file_exists($filePath)) return $this->showError("Missing file $filePath");
        if (file_exists($saveto)) return true;

        if (!file_put_contents($saveto, file_get_contents($filePath))) {
            return $this->showError("Failed to write file $saveto");
        }

        $this->validateLanguageFiles();
        return true;
    }

    // to is relative to the base dir
    function copyFile($from, $to = "/") {
        global $studio;
        $filename = basename($from);
        if ($to == "") $to = "/";
        $to = $studio->basedir . $to . $filename;

        if (!file_exists($from)) return $this->showError("Missing file $from");
        if (!file_put_contents($to, file_get_contents($from))) {
            return $this->showError("Failed to write file $to");
        }

        return true;
    }

    // file is relative to the base dir
    function removeFile($file) {
        global $studio;
        $file = $studio->basedir . $file;

        if (!file_exists($file)) return true;
        if (is_dir($file)) return true;

        return unlink($file);
    }
}
