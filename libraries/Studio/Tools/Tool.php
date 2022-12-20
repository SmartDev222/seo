<?php

namespace Studio\Tools;

use Exception;
use SEO\Services\Utilities\AuthorityColor;
use SEO\Services\Utilities\DifficultyColor;
use Studio\Content\Tools\ToolPage;
use Studio\Content\Tools\ToolPageManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Tool
{
    /**
     * The display name of the tool (able to be translated by the translation system).
     */
    var $name = "Untitled";
    /**
     * The ID of the tool, used primarily for URLs and database records.
     */
    var $id = "untitled";
    /**
     * The name/ID of the icon file to use (where value [x] resolves to /resources/icons/[x].png).
     */
    var $icon = 0;
    /**
     * The template file to use, including the extension (where value [x] resolves to /resources/templates/[x]).
     */
    var $template = "404";
    /**
     * Whether or not the tool requires a website to work.
     */
    var $requiresWebsite = true;

    /**
     * The \SEO\Helper\URL object to execute the tool for.
     * @var \SEO\Helper\Url
     */
    protected $url;
    /**
     * The data to be saved by run() and loaded by output().
     */
    protected $data;

    /**
     * Returns the page instance for this tool on the given language.
     *
     * @param string $language The target locale.
     * @return ToolPage
     */
    public function getPage($language) {
        return ToolPageManager::getPage($this, $language);
    }

    /**
     * Initiates the tool to collect and output data.
     * @param \SEO\Helper\Url $url URL object for the site to collect data on.
     */
    public function start($url) {
        $this->url = $url;

        if (!$this->load()) {
            $this->run();
            $this->save();
        }

        $this->output();
        $this->record();
    }

    /**
     * Records the current tool usage in the database.
     * @param String $data Extra information about this tool session.
     */
    public function record($data = "") {
        global $studio, $account;

        if ($p = $studio->sql->prepare("INSERT INTO history (userId, address, domain, data, toolId, useTime) VALUES (?, ?, ?, ?, ?, " . time() . ")")) {
            if ($account->isLoggedIn()) $userId = $account->getId();
            else $userId = "-1";

            $address = $_SERVER['REMOTE_ADDR'];
            $domain = !is_null($this->url) ? $this->url->domain : 'none';
            $toolId = $this->id;

            $p->bind_param("issss", $userId, $address, $domain, $data, $toolId);
            $p->execute();

            if ($studio->sql->affected_rows > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Executes before any page output (such as headers). Useful for AJAX (JSON responses). Be sure to call $studio->stop(); if necessary.
     * @param \SEO\Helper\Url $url URL object for the site to collect data on.
     */
    public function prerun($url) {

    }

    /**
     * Stops the tool, includes the footer, and terminates the webpage completely.
     */
    protected function stop() {
        global $page, $studio;

        if (isset($page)) $page->footer();
        $studio->stop();
    }

    /**
     * Collects all data.
     * @throws Exception containing a specific message and code when any error occurs.
     */
    public function run() {
        throw new Exception("No runner configured");
    }

    /**
     * Outputs collected data in the form of a template.
     * @throws Exception when the template does not exist.
     */
    public function output() {
        throw new Exception("No outputter configured");
    }

    /**
     * Gets the HTML code of this tool's template file.
     * @throws Exception on missing template file.
     * @return String HTML
     */
    protected function getTemplate() {
        $dir = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates/";
        $file = $dir . $this->template;

        if (file_exists($file)) {
            $str = file_get_contents($file);
            $translate = $this->getTranslations($str);

            foreach ($translate as $trans) {
                $str = str_replace("{{" . $trans . "}}", rt($trans), $str);
            }

            return $str;
        }

        throw new Exception("Missing template file {$this->template}");
    }

    /**
     * Renders the template with Twig using the given context array. The tool's data is automatically supplied to the
     * template as context. You can also pass additional custom context into this method. Note that any custom context
     * keys which collide with keys from the tool's data will override the latter.
     *
     * @param array $context
     * @return string
     */
    protected function renderTemplate($context = []) {
        global $page;

        try {
            $context = array_merge((array)$this->data, $context);

            $dir = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates";
            $loader = new FilesystemLoader($dir);
            $twig = new Environment($loader);

            $twig->addFilter(new TwigFilter('rt', 'rt'));

            $twig->addFunction(new TwigFunction('authority_color', function($value) {
                return AuthorityColor::getColor($value);
            }));

            $twig->addFunction(new TwigFunction('difficulty_color', function($value) {
                return DifficultyColor::getColor($value);
            }));

            $twig->addGlobal('path', $page->getPath());

            $overrideName = str_replace('.html', '.override.html', $this->template);
            $overridePath = $dir . '/' . $overrideName;

            $context['has_data'] = isset($this->data);
            $context['show_tables'] = !defined('STUDIO_EMBEDDED') || isset($this->data);

            if (file_exists($overridePath)) {
                return $twig->render($overrideName, $context);
            }

            return $twig->render($this->template, $context);
        }
        catch (Exception $ex) {
            throw new Exception("Rendering error: " . $ex->getMessage());
        }
    }

    protected function getTranslations($str) {
        $startDelimiter = "{{";
        $endDelimiter = "}}";

        $contents = array();
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;

        while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($str, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }

        return $contents;
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
     * Returns a string representing this specific tool session, so cache can be stored.
     * Modify this only when your tool can take input of any kind.
     * The key should include any input and the tool's ID (format <toolid>:<input>).
     * @return String Cache key or "" (blank) to prevent caching.
     */
    protected function getCacheKey() {
        return $this->id;
    }

    /**
     * Loads data from cache if cache exists and has not expired.
     * @return boolean TRUE if data has been loaded from cache, FALSE otherwise.
     */
    public function load() {
        global $studio;
        $key = $this->getCacheKey();
        $targetDomain = $this->url ? $this->url->domain : '@global';

        if ($studio->getopt("cache") != "On") return;
        if ($key == "") return false;

        if ($p = $studio->sql->prepare("SELECT id, domain, name, data, `time` FROM cache WHERE domain = ? AND name = ?")) {
            $p->bind_param("ss", $targetDomain, $key);
            $p->execute();
            $p->store_result();

            if ($p->num_rows > 0) {
                $p->bind_result($id, $domain, $name, $data, $time);
                $p->fetch();

                $minCacheTime = time() - (86400 * $studio->getopt("cache-duration"));
                if ($time > $minCacheTime) {
                    $this->data = @unserialize(base64_decode($data));
                    if (!$this->data) return false;
                    $p->close();
                    return true;
                }
                else {
                    $p->close();

                    if ($p = $studio->sql->prepare("DELETE FROM cache WHERE domain = ? AND name = ?")) {
                        $p->bind_param("ss", $targetDomain, $key);
                        $p->execute();
                        $p->close();
                    }
                }
            }
        }

        return false;
    }

    public function save() {
        global $studio;

        if (is_array($this->data) && empty($this->data)) return;
        if ($this->data === null) return;
        if ($studio->getopt("cache") != "On") return;

        $key = $this->getCacheKey();
        $data = base64_encode(serialize($this->data));
        $targetDomain = $this->url ? $this->url->domain : '@global';

        if ($key == "") return false;

        if ($p = $studio->sql->prepare("DELETE FROM cache WHERE domain = ? AND name = ?")) {
            $p->bind_param("ss", $targetDomain, $key);
            $p->execute();
            $p->close();
        }

        if ($p = $studio->sql->prepare("INSERT INTO cache (domain, name, data, `time`) VALUES (?, ?, ?, '".time()."')")) {
            $p->bind_param("sss", $targetDomain, $key, $data);
            $p->execute();
            $p->close();
        }
    }
}
