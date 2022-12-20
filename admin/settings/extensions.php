<?php

use Studio\Extend\PluginManager;

require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(3)->setTitle("Extensions")->header();

$baseDir = dirname(dirname(dirname(__FILE__)));
$pluginsDir = $baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins";
$new = 0;

function delTree($dir) {
    $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

// Debugging for rare extension issues
if (isset($_GET['debug']) && !DEMO) {
    $debug = $_GET['debug'];

    function scanAllDir($dir) {
        $result = [];

        foreach (scandir($dir) as $filename) {
            if ($filename[0] === '.') continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

            if (is_dir($filePath)) {
                foreach (scanAllDir($filePath) as $childFilename) {
                    $result[] = $filename . DIRECTORY_SEPARATOR . $childFilename;
                }
            }
            else {
                $result[] = $filename;
            }
        }

        return $result;
    }

    $files = scanAllDir($pluginsDir);

    // List files
    if (empty($debug)) {
        echo "<strong>Files:</strong><br>";
        echo "<ul>";

        foreach ($files as $index => $relativeName) {
            $path = $pluginsDir . DIRECTORY_SEPARATOR . $relativeName;
            $hash = md5_file($path);
            $encodedName = sanitize_attribute($index);

            echo "<li><a href='?debug=contents:$encodedName'><code>$relativeName:$hash</code></a></li>";
        }

        echo "</ul><br>";
        echo "<strong>Options:</strong>";
        echo "<ul>";

        echo "<li><a href='?debug=includes'>Test includes</a></li>";
        echo "<li><a href='?debug=registration'>Test disk registration</a></li>";

        echo "</ul>";
    }

    else if (substr($debug, 0, 9) === 'contents:') {
        $index = intval(substr($debug, 9));
        $relativeName = $files[$index];
        $absoluteName = $pluginsDir . DIRECTORY_SEPARATOR . $relativeName;

        echo "<strong>Reading:</strong> $relativeName<br>";
        echo "<strong>Accessible:</strong> ";
        echo (is_readable($absoluteName) ? "Yes" : "No") . "<br><br>";

        $contents = file_get_contents($absoluteName);
        echo "<pre>" . sanitize_html($contents) . "</pre><br>";
        echo "...Done!";
    }

    else if ($debug === 'includes') {
        echo "<strong>Testing includes...</strong><br>";
        echo "<pre>";

        foreach (scandir($pluginsDir) as $dir) {
            if ($dir == "." || $dir == "..") continue;
            if (substr($dir, 0, 1) == ".") continue;
            if (!is_dir($pluginsDir . "/" . $dir)) continue;


            $className = $dir;
            $file = "$pluginsDir/$dir/$className.php";

            echo "Starting target: $dir (resolved to $file)\n";

            if (!file_exists($file)) {
                echo "File does not exist! Skipping...\n\n";
                continue;
            }

            echo "File found. Importing it...\n";
            require_once $file;

            if (!class_exists($className, false)) {
                echo "WARNING! The class name '$className' was not found after importing the file.\n\n";
                continue;
            }

            echo "Creating an instance of the class...\n";
            $o = new $className;

            echo "Extension name: " . $o::NAME . PHP_EOL;
            echo "Extension version: " . $o::VERSION . PHP_EOL . PHP_EOL;
        }

        echo "Finished.\n";
        echo "</pre>";
    }

    else if ($debug === 'registration') {
        echo "<strong>Testing registration...</strong><br>";
        echo "<pre>";

        foreach (scandir($pluginsDir) as $dir) {
            if ($dir == "." || $dir == "..") continue;
            if (substr($dir, 0, 1) == ".") continue;
            if (!is_dir($pluginsDir . "/" . $dir)) continue;

            $className = $dir;
            $file = "$pluginsDir/$dir/$className.php";

            echo "Starting target: $dir (resolved to $file)\n";
            echo "Checking for an existing registered extension...\n";

            $p = $studio->sql->prepare("SELECT * FROM plugins WHERE directory = ?");
            $p->bind_param("s", $dir);
            $p->execute();
            $p->store_result();

            if (!$p) {
                echo "Got a falsy prepared response!\n";
                echo $studio->sql->error . "\n\n";
                continue;
            }

            if ($p->errno) {
                echo "ERROR! Got a statement error! " . $p->error . "\n\n";
                continue;
            }

            if ($studio->sql->errno) {
                echo "ERROR! Got a driver error! " . $studio->sql->error . "\n\n";
                continue;
            }

            echo "Got rows: " . $p->num_rows . "\n";

            if ($p->num_rows === 0) {
                $p->close();

                if (!file_exists($file)) {
                    echo "File does not exist! Skipping...\n\n";
                    continue;
                }

                echo "File found. Importing it...\n";
                require_once $file;

                if (!class_exists($className, false)) {
                    $renameTo = "$pluginsDir/.$dir";
                    $op = "renamed to .$dir";

                    echo "ERROR! The class name '$className' was not found after importing the file.\n";
                    echo "The class should be renamed to: $renameTo\n";

                    if (file_exists($renameTo)) {
                        echo "ERROR! The target file already exists.\n\n";
                    }
                    else {
                        echo "Operation: The plugin will be renamed.\n\n";
                    }

                    continue;
                }

                echo "Creating an instance of the class...\n";
                $o = new $className;
                $name = $o::NAME;
                $version = $o::VERSION;

                echo "Extension name: " . $o::NAME . PHP_EOL;
                echo "Extension version: " . $o::VERSION . PHP_EOL . PHP_EOL;

                echo "Creating the insertion statement...\n";
                $p = $studio->sql->prepare("INSERT INTO plugins (name, directory, market_id, enabled, version, update_available) VALUES (?, ?, 0, 0, ?, '')");

                if (!$p) {
                    echo "Got a falsy prepared response!\n";
                    echo $studio->sql->error . "\n\n";
                    continue;
                }

                echo "Looks good!\n\n";
            }

            else {
                echo "Nothing to do.\n\n";
            }
        }

        echo "Finished.\n";
        echo "</pre>";
    }

    die;
}

if (!isset($_GET['installed'])) {
    $new = PluginManager::registerPluginsFromDisk();
}

if ($new) {
    header("Location: extensions.php?installed=$new");
    die;
}

if (isset($_GET['enable']) && !DEMO) {
    $id = $_GET['enable'];
    if (!is_numeric($id)) die;

    $q = $studio->sql->query("SELECT * FROM plugins WHERE id = $id");
    $r = $q->fetch_object();
    $dir = $r->directory;

    require_once "$pluginsDir/$dir/$dir.php";

    $o = new $dir;
    $o->baseDir = dirname(dirname(dirname(__FILE__)));
    $o->pluginDir = $o->baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . $dir;

    $studio->sql->query("UPDATE plugins SET enabled = 1 WHERE id = $id");
    $enable = $o->onEnable();
    unset($o);

    if ($enable) {
        header("Location: extensions.php?enabled=1");
        die;
    }
    else {
        $studio->sql->query("UPDATE plugins SET enabled = 0 WHERE id = $id");
        echo "<div class='error'>The plugin failed to enable</div>";
    }
}
if (isset($_GET['disable']) && !DEMO) {
    $id = $_GET['disable'];
    if (!is_numeric($id)) die;

    $q = $studio->sql->query("SELECT * FROM plugins WHERE id = $id");
    $r = $q->fetch_object();
    $dir = $r->directory;

    require_once "$pluginsDir/$dir/$dir.php";
    $o = new $dir;
    $o->baseDir = dirname(dirname(dirname(__FILE__)));
    $o->pluginDir = $o->baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR . $dir;

    $b = $o->onDisable();
    unset($o);

    if ($b !== false) {
        $studio->sql->query("UPDATE plugins SET enabled = 0 WHERE id = $id");
        header("Location: extensions.php?disabled=1");
        die;
    }
}

if (isset($_GET['uninstall']) && !DEMO) {
    $id = $_GET['uninstall'];
    if (!is_numeric($id)) die;

    $q = $studio->sql->query("SELECT * FROM plugins WHERE id = $id");
    if ($q->num_rows == 0) die;

    $r = $q->fetch_object();
    $dir = $r->directory;

    $studio->sql->query("DELETE FROM plugins WHERE id = $id");

    if (file_exists("$pluginsDir/$dir") && $dir != "") {
        $ok = delTree("$pluginsDir/$dir");
        if (!$ok) {
            echo "<div class='error'>Failed to remove $pluginsDir/$dir directory, please do it manually.</div>";
        }
        else {
            header("Location: extensions.php?uninstalled=1");
            die;
        }
    }
}
?>

<div class="heading">
<h1>Extensions</h1>
<h2>Upgrade your studio</h2>
</div>

<div class="panel v2 back">
<a href="../settings.php">
<i class="material-icons">&#xE5C4;</i> Back
</a>
</div>

<div class="panel">
<div class="pull-right">
<a class="btn" href="../install-plugin.php" style="margin-top: -10px;">Install an extension</a>
</div>
<h3>Installed extensions</h3>

<div class="table-container">
<table class="table plugins">
<thead>
<tr>
<th width="52px"></th>
<th>Info</th>
<th class="center" width="140px">Version</th>
<th class="center" width="140px">Status</th>
<th class="right" width="260px">Actions</th>
</tr>
</thead>
<tbody>
<?php
$q = $studio->sql->query("SELECT * FROM plugins ORDER BY id ASC");

while ($row = $q->fetch_object()) {
    $dname = $row->directory;

    $file = "$pluginsDir/$dname/$dname.php";
    if (!file_exists($file)) {
        header("Location: extensions.php?uninstall={$row->id}");
        die;
    }
    require_once $file;

    $o = new $dname;

    if ($o::DISABLED) {
        continue;
    }

    $description = $o::DESCRIPTION;
    $settings = $o->settings;

    unset($o);
    ?>
    <tr>
    <td class="right">
    <?php echo ($row->enabled ? "<i class='material-icons green'>check_circle</i>" : "<i class='material-icons red'>highlight_off</i>"); ?>
    </td>
    <td>
    <strong><?php echo $row->name; ?></strong>
    <p><?php echo $description; ?></p>
    </td>
    <td class="center"><?php echo $row->version; ?></td>
    <td class="center"><?php echo ($row->enabled ? "Active" : "Disabled"); ?></td>
    <td class="right">
    <?php if ($row->enabled && $settings) { ?>
        <a class="btn small" href="../plugin-options.php?id=<?php echo $row->id; ?>">Settings</a>
        <?php } ?>

        <?php if ($row->market_id > 0) { ?>
            <a class="btn small" href="https://getseostudio.com/plugins/item?id=<?php echo $row->market_id; ?>" target="_blank">Market</a>
            <?php } ?>

            <?php if ($row->enabled) { ?>
                <a class="btn small" href="?disable=<?php echo $row->id; ?>">Disable</a>
                <?php } else { ?>
                    <a class="btn small" href="?enable=<?php echo $row->id; ?>">Enable</a>
                    <a class="btn small red" href="?uninstall=<?php echo $row->id; ?>">Uninstall</a>
                    <?php } ?>

                    </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
                </table>
                </div>
                </div>

                <?php
                $page->footer();
                ?>
