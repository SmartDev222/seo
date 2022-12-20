<?php

class ItemUpdate extends Update
{
    public function download() {
        global $api;

        try {
            $file = $api->downloadItem($this->token);
        }
        catch (Exception $e) {
            return $this->fail($e->getMessage());
        }

        if (!is_writable($this->bindir)) return $this->fail("Insufficient bin permissions");

        $this->updateFile = $this->bindir . "/" . "tmp-" . md5(rand() .':'. $this->token) . ".zip";
        $b = file_put_contents($this->updateFile, $file);
        if ($b === false) return $this->fail("Update error 1x20");
        unset($file);

        return true;
    }

    public function upgrade() {
        return true;
    }

    public function afterwards($dir) {
        if (file_exists($dir)) {
            $name = basename($dir);
            $main = $dir . "/" . $name . ".php";

            if (file_exists($main)) {
                require $main;
                $o = new $name;
				$o->baseDir = dirname(dirname(__FILE__));
				$o->pluginDir = $dir;
                $o->onUpdate();
            }
        }

        return null;
    }

    public function fail($error) {
        $this->error = $error;
        return false;
    }

    public function setUpdateStatus($status, $error = "") {
        if ($status == 1) {
            $this->sql->query("UPDATE plugins SET update_available='' WHERE market_id={$this->token}");
            $this->sql->query("UPDATE themes SET update_available='' WHERE market_id={$this->token}");
        }
    }
}
