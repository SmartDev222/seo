<?php

namespace Studio\Display;

use \Exception;

class AdminPage
{
    private $title;
    private $description;
    private $page;
    private $path;
    private $studio;

    public function __construct($studio) {
        $this->page = 1;
        $this->path = "";
        $this->title = "Dashboard";
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
    public function header($navigationName = null) {
        global $studio;

        $header = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates/admin-header.php";
        $navigationFile = dirname(dirname(dirname(dirname(__FILE__)))) . "/admin/includes/navigation.php";

        if (!file_exists($header)) throw new Exception("Header file does not exist");

        // This variable will be used in the header file
        $navigationTable = isset($navigationName) ? require($navigationFile) : null;
        $navigation = isset($navigationTable) ? $navigationTable[$navigationName] : null;

        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
        $adminPath = str_replace('\\', '/', $studio->basedir . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR);
        $currentPage = '';

        if (substr($scriptPath, 0, strlen($adminPath)) === $adminPath) {
            $currentPage = substr($scriptPath, strlen($adminPath));
        }

        if (isset($navigation) && !empty($currentPage)) {
            $match = null;
            $directories = [];

            foreach ($navigation['links'] as $link) {
                if ($link['url'] === $currentPage) {
                    $match = $link;
                }

                $dir = dirname($link['url']) . '/';

                if (!isset($directories[$dir])) {
                    $directories[$dir] = [];
                }

                $directories[$dir][] = $link['url'];
            }

            if (!isset($match)) {
                $dir = dirname($currentPage) . '/';

                if (isset($directories[$dir])) {
                    $target = $directories[$dir];

                    if (count($target) === 1) {
                        $currentPage = $target[0];
                    }
                }
            }
        }

        require_once $header;
        return $this;
    }

    /**
     * Loads and outputs the footer HTML.
     */
    public function footer() {
        $footer = dirname(dirname(dirname(dirname(__FILE__)))) . "/resources/templates/admin-footer.php";

        if (!file_exists($footer)) throw new Exception("Footer file does not exist");
        require_once $footer;
    }

    /**
     * Checks if the user is currently logged in and redirects them if not.
     */
    public function requireLogin() {
        if (!$this->studio->logged_in) {
            $this->studio->redirect("account/login.php");
        }
    }

    /**
     * Checks if the user is logged in and has a permission, or redirects them.
     * @param String $permission The permission (column name) from the database.
     */
    public function requirePermission($permission) {
        global $account;

        if ($permission == 'admin-access' && DEMO) return $this;

        // First require login
        $this->requireLogin();

        // Now fetch the group from the database
        $q = $this->studio->sql->query("SELECT * FROM `groups` WHERE id='{$account->groupId}'");
        $row = @$q->fetch_array();

        if ($q->num_rows == 0 || $this->studio->sql->error != "") $this->studio->redirect("account/login.php");
        if (!isset($row[strtolower($permission)])) $this->studio->redirect("account/login.php");

        // Check for the permission

        if (!$row[strtolower($permission)]) $this->studio->redirect("account/login.php");

        return $this;
    }

    public function activeLink($pageId, $className) {
        if ($this->page == $pageId) echo " class=\"$className\"";
    }
}
