<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(3)->setTitle("Install plugin")->header();

$baseDir = dirname(dirname(__FILE__));
$pluginsDir = $baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR;

if (isset($_POST['upload']) && !DEMO) {
    $file = $_FILES['plugin'];
    if (substr($file['name'], -4) != ".zip" && substr($file['name'], -7) != ".plugin") die("You must upload a .zip or .plugin file!");

    $tmpPath = $baseDir . "/resources/bin/plugin-" . rand() . rand() . substr(md5(rand()), 4, 9) . ".zip";

    if (move_uploaded_file($file['tmp_name'], $tmpPath)) {
        $market_id = 0;
        $version = "";

        if (substr($file['name'], -7) == ".plugin") {
            $zip = file_get_contents($tmpPath);
            $zip = str_replace("*MKT^", "PK", $zip);
            $parts = explode("||MKT-||", $zip);

            $mkdata = $parts[1];
            $mkdata = json_decode(base64_decode($mkdata), true);
            $market_id = $mkdata['market_id'];
            $version = $mkdata['version'];
            $pluginName = $mkdata['name'];

            file_put_contents($tmpPath, $parts[0]);
        }

        $zip = new Studio\Util\Zip;
        $zip->read_zip($tmpPath);

        $directoryName = "";

        foreach ($zip->dirs as $dir) {
            if (stripos($dir, "/") === false && $directoryName == "") $directoryName = $dir;

            if (file_exists($pluginsDir . $dir)) {
                unlink($tmpPath);
                die("Directory already exists: {$pluginsDir}{$dir}<br>Cannot install duplicate plugin.");
            }
        }

        foreach ($zip->files as $file) {
            $path = $pluginsDir . $file['dir'] . DIRECTORY_SEPARATOR . $file['name'];
            $dir = dirname($path);
            if (!file_exists($dir)) mkdir($dir, 0777, true);

            if (file_put_contents($path, $file['data']) === false) {
                unlink($tmpPath);
                die("Failed to write: $path");
            }
        }

        if ($market_id > 0) {
            $studio->sql->query("DELETE FROM plugins WHERE market_id = $market_id");
            $studio->sql->query("DELETE FROM plugins WHERE directory = '$directoryName'");

            $p = $studio->sql->prepare("INSERT INTO plugins (name, directory, market_id, enabled, version, update_available) VALUES (?, ?, $market_id, 0, '$version', '')");
            $p->bind_param("ss", $pluginName, $directoryName);
            $p->execute();
        }

        unlink($tmpPath);
        header("Location: plugins.php");
        die;
    }

}
?>

<div class="heading">
    <h1>Install an extension</h1>
    <h2>Upload the extension file and we'll do the rest</h2>
</div>

<div class="panel">
    <p>Please upload the extension's file. It must be in .zip or .plugin format.</p>

    <form action="" method="post" enctype="multipart/form-data" style="margin: 25px 0 0;">
        <input type="file" name="plugin"> <input type="submit" class="btn blue small" value="Install" name="upload">
        <a class="btn small" href="settings/extensions.php">Cancel</a>
    </form>
</div>

<?php
$page->footer();
?>
