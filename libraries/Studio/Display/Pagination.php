<?php

namespace Studio\Display;

class Pagination
{
    public $pages;
    public $page;
    public $perPage;

    public function __construct($currentPage, $perPage, $totalRows) {
        $this->pages = ceil($totalRows / $perPage);
        if ($this->pages == 0) $this->pages = 1;

        $this->page = $currentPage;
        if ($this->page < 1) $this->page = 1;
        if ($this->page > $this->pages) $this->page = $this->pages;

        $this->perPage = $perPage;
    }

    /**
     * Gets the "LIMIT x,x" segment for a query, based on the current page.
     */
    public function getQuery() {
        $start = ($this->perPage * $this->page) - $this->perPage;
        return "LIMIT $start, {$this->perPage}";
    }

    public function getPageList() {
        $farLeft = $this->page - 2;
        $farRight = $this->page + 2;

        if ($farLeft < 1) $farLeft = 1;
        if ($farRight > $this->pages) $farRight = $this->pages;

        $diffRight = $farRight - $this->page;
        $diffLeft = $this->page - $farLeft;

        if ($diffRight < 2) $farLeft -= abs(2 - $diffRight);
        if ($diffLeft < 2) $farRight += abs(2 - $diffLeft);

        if ($farLeft < 1) $farLeft = 1;
        if ($farRight > $this->pages) $farRight = $this->pages;

        $pages = array();

        for ($x = $farLeft; $x <= $farRight; $x++) {
            $pages[] = $x;
        }

        return $pages;
    }

    /**
     * Builds a URL query (/page.php?query1=&query2=) including the new page number and existing GET fields.
     */
    private function buildLink($page) {
        $query = "?";

        foreach ($_GET as $i => $v) {
            if ($i == "page") continue;
            $query .= "$i=$v&";
        }

        $query .= "page=$page";
        return $query;
    }

    /**
     * Prints out a styled pagination.
     * @param String $customClass (optional) custom class to include in the pagination div (such as 'right' or 'left')
     */
    public function show($customClass = "") {
        echo "<div class=\"pagination $customClass\"><ul>";

        if ($this->page > 1) {
            $href = $this->buildLink($this->page - 1);
            echo "<li><a href=\"$href\">&laquo; Previous</a></li>";
        }

        foreach ($this->getPageList() as $page) {
            $active = (($page == $this->page) ? "active" : "");
            $href = $this->buildLink($page);
            echo "<li class=\"$active\"><a href=\"$href\">$page</a></li>";
        }

        if ($this->page < $this->pages) {
            $href = $this->buildLink($this->page + 1);
            echo "<li><a href=\"$href\">Next &raquo;</a></li>";
        }

        echo "</ul></div>";
    }
}
