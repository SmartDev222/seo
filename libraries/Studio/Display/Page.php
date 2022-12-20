<?php

namespace Studio\Display;

use Exception;

class Page
{
    private $id;
    private $title;
    private $description;
    private $page;
    private $path;
    private $studio;
    private $meta = array();

    public function __construct($studio) {
        $this->page = 1;
        $this->path = "";
        $this->title = "SEO Studio";
        $this->description = "";
        $this->studio = $studio;
    }

    /**
     * Sets the title of the current page.
     * @param String $title The new title to display
     */
    public function setTitle($title = "") {
        $this->title = $title;
        return $this;
    }

    /**
     * Sets the ID of the current page.
     * @param string $id
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the meta tags for this page.
     *
     * You can pass an associative array, or pass the name as arg1 and value as arg2.
     *
     * @param string[]|string $meta
     * @param string|null $value
     * @return void
     */
    public function setMeta($meta, $value = null) {
        if ($value !== null) {
            $meta = array($meta => $value);
        }

        foreach ($meta as $name => $content) {
            $this->meta[$name] = $content;
        }
    }

    /**
     * Returns all meta tags for this page.
     *
     * @return string[]
     */
    public function getMeta() {
        return $this->meta;
    }

    /**
     * Sets the file path to get back to the main directory (such as ../)
     * @param String $path The new path to use.
     */
    public function setPath($path = "") {
        $this->path = $path;
        $this->studio->path = $path;
        return $this;
    }

    /**
     * Sets the meta description of the current page (for search engines).
     * @param String $description The new description to output.
     */
    public function setDescription($description = "") {
        $this->description = $description;
        return $this;
    }

    /**
     * Shows the specified page as active in the site navigation.
     * @param int $page The page number to highlight.
     */
    public function setPage($page = 1) {
        $this->page = $page;
        return $this;
    }

    /**
     * @return String Current page title.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return String Current path to main directory.
     */
    public function getPath() {
        global $studio;

        if (!$studio->permalinks->isNative()) {
            return $studio->permalinks->getPathTraversal();
        }

        return $this->path;
    }

    /**
     * @return String Current page description for search engines.
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @return int Current page number to highlight in site navigation.
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * Loads and outputs the header HTML.
     */
    public function header() {
        global $language, $account; // do not remove, global is needed in the required file below

        $header = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates/header.php";

        if (!file_exists($header)) throw new Exception("Header file does not exist");
        require_once $header;

        return $this;
    }

    /**
     * Loads and outputs the header HTML.
     */
    public function embedHeader() {
        global $language, $account; // do not remove, global is needed in the required file below

        $header = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates/embed-header.php";

        if (!file_exists($header)) throw new Exception("Embed header file does not exist");
        require_once $header;

        return $this;
    }

    /**
     * Loads and outputs the footer HTML.
     */
    public function footer() {
        $footer = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates/footer.php";

        if (!file_exists($footer)) throw new Exception("Footer file does not exist");
        require_once $footer;

        $this->studio->stop();
    }

    /**
     * Loads and outputs the footer HTML.
     */
    public function embedFooter() {
        $footer = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates/embed-footer.php";

        if (!file_exists($footer)) throw new Exception("Embed footer file does not exist");
        require_once $footer;

        $this->studio->stop();
    }

    /**
     * Checks if the user is logged in and has a permission, or redirects them.
     * @param String $permission The permission (column name) from the database.
     */
    public function hasPermission($permission) {
        global $account, $studio;

        if (!$account->isLoggedIn()) {
            return false;
        }

        if (!$account->isVerified() && !$account->group()['admin-access']) {
            if (substr($_SERVER['SCRIPT_NAME'], -19) !== "account/confirm.php") {
                $studio->redirect('account/confirm.php');
            }
        }

        $group = $account->group();

        if (!isset($group[strtolower($permission)]) || !$group[strtolower($permission)]) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the user is currently logged in and redirects them if not.
     */
    public function requireLogin() {
        if (!$this->studio->logged_in) {
            $this->studio->redirect("account/login.php");
        }
    }
}
