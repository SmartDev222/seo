<?php

namespace Studio\Forms;

class NewWebsiteForm extends Form
{
    private $www_domain;
    private $domain;

    public function __construct() {
        $this->errors = array();

        if (isset($_POST['url'])) {
            $this->post();
        }
    }

    public function validate() {
        global $account, $studio;

        $url = strtolower(trim($_POST['url']));

        try {
            $url = new \SEO\Helper\Url($url);
            $this->domain = str_replace("www.", "", $url->domain);
            $this->www_domain = "www." . $this->domain;

            if ($p = $studio->sql->prepare("SELECT domain FROM websites WHERE (domain LIKE ? OR domain LIKE ?) AND userId=?")) {
                $d = $this->domain;
                $ww = $this->www_domain;
                $gid = $account->getId();

                $p->bind_param("ssi", $d, $ww, $gid);
                $p->execute();
                $p->store_result();

                if ($p->num_rows == 0) {
                    $p->close();

                    return true;
                }
                else $this->errors[] = rt("Website already exists.");
            }
            else $this->errors[] = "Database error. #2";
        }
        catch (\Exception $e) {
            $this->errors[] = rt("Invalid website.");
        }

        return false;
    }

    public function post() {
        global $account, $studio;

        if (DEMO) return;

        if ($this->validate()) {
            $time = time();

            $p = $studio->sql->prepare("INSERT INTO websites (userId, domain, timeCreated, timeAccessed) VALUES (?, ?, $time, $time);");
            $id = $account->getId();
            $d = $this->domain;
            $p->bind_param("is", $id, $d);
            $p->execute();

            if ($studio->sql->affected_rows == 1) {
                $p->close();
                $studio->redirect("account/websites.php");
            }
            else $this->errors[] = "Database error. #1";
        }
    }
}
