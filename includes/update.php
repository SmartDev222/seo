<?php

use Studio\Base\ErrorHandler;

/**
 * Class for performing a secure automated upgrade from the API.
 */
class Update
{
    protected $sql;

    public $token;
    public $basedir;
    public $bindir;

    public $updateFile;
    public $upgradeFile;
    public $upgradeAfterFile;
    public $backupFile;

    public $error;

    public $directories;
    public $files;
    public $upgradeFileContents;
    public $upgradeAfterFileContents;

    protected $isPush;

    public function __construct($id, $isPush = false) {
        global $studio;

        $this->sql = $studio->sql;
        $this->token = $id;
        $this->basedir = dirname(dirname(__FILE__));
        $this->bindir = $this->basedir . "/resources/bin";

        $this->directories = array();
        $this->files = array();
        $this->isPush = $isPush;
    }

    /**
     * Runs all methods in the order they are recommended.
     * @return boolean true if update was successful, false otherwise.
     */
    public function run($createBackup = false) {
        global $studio;
        if (defined('DEMO') && DEMO && defined('DEMO_UPDATE') && DEMO_UPDATE) return false;

        if ($studio->errors instanceof ErrorHandler) {
            $old = $studio->errors->setMode('updates');
        }

        if (!$this->download()) return false;
        if (!$this->preparePackage()) return false;
        if (!$this->hasFilePermissions()) return false;
        if ($createBackup && !$this->createBackup()) return false;
        if (!$this->upgrade()) return false;
        if (!$this->extract()) return false;
        if (!$this->upgradeAfter()) return false;

        $this->cleanup();
        $this->setUpdateStatus(1);
        $this->validateLanguageFiles();

        if ($studio->errors) $studio->errors->setMode($old);

        return true;
    }

    public function fail($error) {
        $this->error = $error;

        $p = $this->sql->prepare("UPDATE updates SET updateStatus=2, updateError=? WHERE token=?");
        $p->bind_param("ss", $error, $this->token);
        $p->execute();
        $p->close();

        return false;
    }

    /**
     * Downloads the update, stores it on the disk, and sets the $::updateFile path.
     * @return boolean true on success or false on failure.
     */
    public function download() {
        global $api;

        try {
            $file = $api->downloadUpdate($this->token);
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

    /**
     * Reads and prepares the update package file for the upgrade.
     * @return boolean true on success or false on failure.
     */
    public function preparePackage() {
        $zip = new Studio\Util\Zip;
        $zip->read_zip($this->updateFile);

        foreach ($zip->dirs as $dir) {
            $real = substr($dir, 6);
            if ($real != "") $this->directories[] = $real;
        }

        foreach ($zip->files as $file) {
            if (stripos($file['dir'], "../") !== false) return $this->fail("Security blocked");

            if (substr($file['dir'], 0, 5) == "files") {
                $dir = substr($file['dir'], 6);
                $filepath = $dir . '/' . $file['name'];
                if ($dir == '') $filepath = $file['name'];
                $this->files[$filepath] = $file;
            }
            else {
                if ($file['name'] == "upgrade.php") {
                    $this->upgradeFileContents = $file['data'];
                }
                else if ($file['name'] == "upgrade-after.php") {
                    $this->upgradeAfterFileContents = $file['data'];
                }
            }
        }

        if ($this->upgradeFileContents == null) return $this->fail("Corrupt package (MISS_UF)");

        $this->upgradeFile = $this->bindir . DIRECTORY_SEPARATOR . "upgrade.php";
        $b = file_put_contents($this->upgradeFile, $this->upgradeFileContents);
        if ($b === false) return $this->fail("Insufficient permissions [FS_MISS_UF]");

        if ($this->upgradeAfterFileContents !== null) {
            $this->upgradeAfterFile = $this->bindir . DIRECTORY_SEPARATOR . "upgrade-after.php";
            $b = file_put_contents($this->upgradeAfterFile, $this->upgradeAfterFileContents);
            if ($b === false) return $this->fail("Insufficient permissions [FS_MISS_UF]");
        }

        return true;
    }

    /**
     * Checks if we have permission to create and modify all affected files/directories in the update package.
     * @return boolean true if we can write all files and directories, false otherwise.
     */
    public function hasFilePermissions() {
        foreach ($this->directories as $dir) {
            $target = $this->basedir . DIRECTORY_SEPARATOR . $dir;

            if (file_exists($target)) {
                if (!is_writable($target)) {
                    return $this->fail("Insufficient permissions [AFF_DIRS_CHK]");
                }
            }
            else {
                $parent = dirname($target);
                if (file_exists($parent)) {
                    if (!is_writable($parent)) {
                        return $this->fail("Insufficient permissions [AFF_DIRS_PARENT_CHK]");
                    }
                }
            }
        }

        foreach ($this->files as $file => $fargs) {
            $target = $this->basedir . DIRECTORY_SEPARATOR . $file;

            if (file_exists($target)) {
                if (!is_writable($target)) {
                    return $this->fail("Insufficient permissions [AFF_FILES_CHK]");
                }
            }
            else {
                $parent = dirname($target);
                if (file_exists($parent)) {
                    if (!is_writable($parent)) {
                        return $this->fail("Insufficient permissions [AFF_FILES_PARENT_CHK]");
                    }
                }
            }
        }

        return true;
    }

    /**
     * Creates and saves a zip backup of all existing files that will be overwritten in the update.
     * @todo Remember to check if backups are enabled before calling this.
     * @return boolean true on success, false on failure.
     */
    public function createBackup() {
        $backup = new Studio\Util\Zip;

        foreach ($this->directories as $d) {
            $backup->create_dir($d);
        }

        foreach ($this->files as $filepath => $f) {
            $path = $this->basedir . DIRECTORY_SEPARATOR . $filepath;

            if (file_exists($path)) {
                $data = file_get_contents($path);
                if ($data === false) return $this->fail("Failed to backup (+r)");
                $backup->create_file($data, $filepath);
            }
        }

        $time = time();
        $this->backupFile = $this->bindir . DIRECTORY_SEPARATOR . "/backup-update-{$this->token}-{$time}.zip";

        $saveBackup = file_put_contents($this->backupFile, $backup->zipped_file());
        if ($saveBackup === false || !file_exists($this->backupFile)) return $this->fail("Failed to backup (+w)");

        return true;
    }

    /**
     * Executes the upgrade script inside the package.
     * @return boolean true on success, false on failure.
     */
    public function upgrade() {
        global $studio;

        $this->setUpdateStatus(2, "Execute upgrade failed");

        try {
            $sql = $this->sql;
            $bool = require($this->upgradeFile);

            if ($bool !== true) {
                throw new Exception("Received a negative return from upgradeDatabase call.");
            }
        }
        catch (Exception $e) {
            if ($studio->errors) {
                $studio->errors->client->captureException($e, [
                    'extra' => [
                        'token' => $this->token
                    ]
                ]);
            }

            $str = "Running automated (user-requested) upgrade: " . $this->token . PHP_EOL . PHP_EOL;
            $str .= "Directories:" . PHP_EOL;

            foreach ($this->directories as $d) {
                $str .= " - " . $d . PHP_EOL;
            }

            $str .= PHP_EOL . "Files:" . PHP_EOL;
            foreach ($this->files as $f => $farr) {
                $str .= " - " . $f . PHP_EOL;
            }

            $str .= PHP_EOL;
            $str .= "Exception in upgrade script: " . $e->getMessage() . ": " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
            $str .= "Stack trace: " . $e->getTraceAsString();

            $time = time();
            $errorLog = $this->bindir . DIRECTORY_SEPARATOR . "upgrade-{$this->token}-{$time}.txt";
            file_put_contents($errorLog, $str);

            return $this->fail("Execute upgrade failed");
        }

        return true;
    }

    /**
     * Executes the upgrade script inside the package.
     * @return boolean true on success, false on failure.
     */
    public function upgradeAfter() {
        global $studio;

        if (!$this->upgradeAfterFile) {
            return true;
        }

        $this->setUpdateStatus(2, "Execute upgrade failed");

        try {
            $sql = $this->sql;
            $bool = require($this->upgradeAfterFile);

            if ($bool !== true) {
                throw new Exception("Received a negative return from upgradeAfter call.");
            }
        }
        catch (Exception $e) {
            if ($studio->errors) {
                $studio->errors->client->captureException($e, [
                    'extra' => [
                        'token' => $this->token
                    ]
                ]);
            }

            $str = "Running automated (user-requested) upgrade (after): " . $this->token . PHP_EOL . PHP_EOL;
            $str .= "Directories:" . PHP_EOL;

            foreach ($this->directories as $d) {
                $str .= " - " . $d . PHP_EOL;
            }

            $str .= PHP_EOL . "Files:" . PHP_EOL;
            foreach ($this->files as $f => $farr) {
                $str .= " - " . $f . PHP_EOL;
            }

            $str .= PHP_EOL;
            $str .= "Exception in upgrade script: " . $e->getMessage() . ": " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
            $str .= "Stack trace: " . $e->getTraceAsString();

            $time = time();
            $errorLog = $this->bindir . DIRECTORY_SEPARATOR . "upgrade-after-{$this->token}-{$time}.txt";
            file_put_contents($errorLog, $str);

            return $this->fail("Execute after upgrade failed");
        }

        return true;
    }

    /**
     * Extracts the contents of the upgrade package onto the disk.
     * If file extraction fails mid-way, the entire application is locked for security until the files can be reverted.
     * @return boolean true on success, false on failure.
     */
    public function extract() {
        foreach ($this->directories as $dir) {
            $target = $this->basedir . DIRECTORY_SEPARATOR . $dir;

            if (!file_exists($target)) {
                if (mkdir($target, 0777, true) == false) return $this->fail("Mkdir failed");
            }
        }

        $writes = array();

        foreach ($this->files as $filepath => $f) {
            $target = $this->basedir . DIRECTORY_SEPARATOR . $filepath;

            if (!file_exists(dirname($target))) if (mkdir(dirname($target), 0777, true) == false) return $this->fail("Mkdir failed");

            if (file_put_contents($target, $f['data']) === false) {
                if (count($writes) > 0) {
                    // We've overwritten at least one file but ran into an error.
                    // To protect the integrity of the Studio, it will be disabled until an admin can restore the files from the backup.

                    $writes["FAILED_ON"] = $target;

                    file_put_contents($this->bindir . "/lock", print_r($writes, true));
                    file_put_contents($this->basedir . "/lock", print_r($writes, true));
                    return $this->fail("Extraction failed +w SEVERE");
                }
                return $this->fail("Extraction failed +w");
            }

            $writes[] = $target;
        }

        return true;
    }

    /**
     * Removes any temporary files that exist from the upgrade process.
     */
    public function cleanup() {
        if ($this->upgradeFile != null && file_exists($this->upgradeFile)) unlink($this->upgradeFile);
        if ($this->upgradeAfterFile != null && file_exists($this->upgradeAfterFile)) unlink($this->upgradeAfterFile);
        if ($this->updateFile != null && file_exists($this->updateFile)) unlink($this->updateFile);
    }

    /**
     * Checks the default (bin/en-us) language file to see if there's any new translations.
     * The admin will be prompted automatically to address them.
     */
    public function validateLanguageFiles() {
        global $studio;

        $curLang = new Studio\Base\Language($this->bindir, "en-us");
        $missing = false;

        foreach (scandir($this->basedir . '/resources/languages/') as $folder) {
            if ($folder == "." || $folder == "..") continue;

            $lang = new Studio\Base\Language($this->basedir . '/resources/languages', str_replace("/", "", $folder));
            if (count($lang->translations) < count($curLang->translations)) $missing = true;
        }

        if ($missing) $studio->setopt("update-missing-translations", "1");
    }

    /**
     * Sets the update status and error message (optional).
     * @param int $status The status code to set. (1 = installed, 2 = failed)
     * @param String $error The error message to set. (defaults to blank)
     */
    public function setUpdateStatus($status, $error = "") {
        $p = $this->sql->prepare("UPDATE updates SET updateStatus=?, updateError=? WHERE token=?");
        $p->bind_param("iss", $status, $error, $this->token);
        $p->execute();
        $p->close();
    }
}
