<?php

namespace Studio\Base;

class Language
{
    public $language;
    public $basedir;
    public $file;
    public $dir;
    public $google;
    public $locale;
    public $name;
    public $countriesLocale;
    public $translations = array();
    public $loadedFiles = array();

    public function __construct($basedir, $name = '') {
        $this->basedir = $basedir;
        if ($name == '') {
            $this->load();
            $this->read();
            $this->verify();
        }
        else {
            $this->file = $this->basedir . "/" . $name . "/";
            if (!file_exists($this->file)) {
                die("Load translation file failed: {$this->language}");
            }

            $this->read();
        }
    }

    protected function load() {
        $this->language = $this->getLanguage();
        $this->file = $this->basedir . "/" . $this->language . "/";

        if (!file_exists($this->file)) {
            if (!isset($_SESSION['language'])) die("Load default translation file failed: {$this->language}");
            unset($_SESSION['language']);
            $this->load();
        }
    }

    public function read() {
        foreach (scandir($this->file) as $file) {
            if (substr($file, 0, 1) == ".") continue;
            if (strtolower(substr($file, -5)) !== ".json") continue;
            if (!is_file($this->file . $file)) continue;

            $data = str_replace("\r\n", "\n", file_get_contents($this->file . $file));
            $data = trim(str_replace("\n", "", $data));
            $data = preg_replace('/\s+/', ' ', $data);

            $items = json_decode($data, true);

            if (!is_array($items)) {
                die("Invalid language file: {$this->file}{$file}");
            }

            foreach ($items as $in => $out) {
                if (!isset($this->translations[$in]))
                    $this->translations[$in] = $out;
            }

            $this->loadedFiles[] = $file;
        }
    }

    public function verify() {
        global $studio;

        $q = $studio->sql->query("SELECT * FROM languages WHERE locale = '{$this->language}'");
        if ($q->num_rows == 0) {
            $this->translations = array();
            if (isset($_SESSION['language'])) {
                unset($_SESSION['language']);
                $this->load();
            }
            else {
                $studio->showFatalError("Language system corrupted: please ensure the default language is properly configured. (Try running diagnostics)");
            }
        }

        $row = $q->fetch_array();
        $this->dir = $row['dir'];
        $this->google = $row['google'];
        $this->locale = $row['locale'];
        $this->name = $row['name'];
        $this->countriesLocale = $row['countries'];
    }

    public function getLanguage() {
        global $studio;

        if (isset($_GET['setlang'])) {
            $setlang = $_GET['setlang'];

            $p = $studio->sql->prepare("SELECT * FROM languages WHERE locale = ?");
            $p->bind_param("s", $setlang);
            $p->execute();
            $p->store_result();

            if ($p->num_rows == 1) {
                $_SESSION['language'] = $setlang;
            }
        }

        if (!isset($_SESSION['language'])) return $studio->getopt("default-language");
        return $_SESSION['language'];
    }

    public function getCountries() {
        global $studio;

        $path = $studio->bindir . '/countries/' . $this->countriesLocale . '.json';
        $contents = json_decode(file_get_contents($path), true);

        return $contents['countries'];
    }

    public function translate($phrase) {
        if (isset($this->translations[$phrase])) return $this->translations[$phrase];
        return $phrase;
    }
}

?>
