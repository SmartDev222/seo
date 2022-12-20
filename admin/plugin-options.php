<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(3)->setTitle("Plugins")->header();

$baseDir = dirname(dirname(__FILE__));
$pluginsDir = $baseDir . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "plugins";

$id = $_GET['id'];
if (!is_numeric($id)) die;

$q = $studio->sql->query("SELECT * FROM plugins WHERE id = $id");
if ($q->num_rows == 0) die;

$row = $q->fetch_object();
$dir = $row->directory;

?>

<div class="heading">
    <h1>Edit extension</h1>
    <h2>Change extension settings</h2>
</div>

<div class="panel v2 back">
    <a href="settings/extensions.php">
        <i class="material-icons">&#xE5C4;</i> Extensions
    </a>
</div>

<?php
if (!DEMO) {
    $className = $dir;
    require_once "$pluginsDir/$dir/$className.php";
    $o = new $className;
    $o->pluginDir = "$pluginsDir/$dir";
    $o->settings();
}
else {
    echo "<div class='panel v2'><p>Plugin options are hidden on the demo.</p></div>";
}

$page->footer();
?>
