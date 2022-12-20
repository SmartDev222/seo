<?php

class EasyContact extends Studio\Extend\Plugin
{
    const NAME = "Easy Contact Page";
    const DESCRIPTION = "Quickly add a contact form to your website without ever touching code.";
    const VERSION = "1.0";

    var $settings = true;

    function start() {
        $this->hook("page_menu_2", "showContactMenu");
        $this->hook("custom_head", "links");
        $this->hook("admin_head", "showWarning");
    }

    function showWarning() {
        global $studio, $page;

        if ($this->getopt("contact-email-sendto") === "youremail@example.com") {
            $pluginId = null;
            $q = $studio->sql->query('SELECT * FROM `plugins` WHERE `name` = "' . static::NAME . '";');

            if ($q->num_rows > 0) {
                $row = $q->fetch_object();
                $pluginId = $row->id;
            }

            echo "<div class='warning'><i class=\"material-icons\">error_outline</i><span>The contact page will not work until you <a href='{$page->getPath()}admin/plugin-options.php?id={$pluginId}'>configure the extension</a>.</span></div>";
        }
    }

    function showContactMenu($path, $page) {
        $selector = ($page == 4) ? '<div class="arrow-down blue"></div>' : "";
        $contact = rt("Contact");

        echo <<<MENU
            <li>
                <a href="./{$path}contact.php">
                    <div data-icon="&" class="icon"></div>
                    {$contact}
                    {$selector}
                </a>
            </li>
MENU;
    }

    function links() {
        if ($this->getPage() == 4) {
            $link = $this->getPluginDirURL() . "/contact.css";
            echo "<link rel='stylesheet' type='text/css' href='$link'>" . PHP_EOL;
        }
    }

    function settings() {
        global $studio;

        if (isset($_POST['contact-email-sendto'])) {
            $this->setopt("contact-email-sendto", $_POST['contact-email-sendto']);
            $this->setopt("contact-block-speed", $_POST['contact-block-speed']);
            $this->setopt("contact-block-spam", $_POST['contact-block-spam']);
            $this->setopt("contact-subject", $_POST['contact-subject']);

            $id = $_GET['id'];
            $studio->redirect("admin/plugin-options.php?id=$id&success=1");
        }

        require dirname(__FILE__) . "/settings.php";
    }

    function onEnable() {
        $this->setopt("contact-email-sendto", "youremail@example.com");
        $this->setopt("contact-email-sendfrom", "webmaster@getseostudio.com");
        $this->setopt("contact-block-speed", "On");
        $this->setopt("contact-block-spam", "On");
        $this->setopt("contact-send-history", base64_encode(serialize([])));

        if (!$this->copyFile(dirname(__FILE__) . "/contact.php", "/")) return false;
        return $this->installLanguageFile(dirname(__FILE__) . "/lang.json", "plugin.contact.json");
    }

    function onDisable() {
        $this->removeFile("/contact.php");
    }
}
