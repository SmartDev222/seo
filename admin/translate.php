<?php
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(11)->setTitle("Translate")->header();

$name = $_GET['name'];
if (!ctype_alnum(str_replace(array("_","-","."), "", $name))) die;

# Get language files

$update = array();
$o = $studio->sql->query("SELECT * FROM languages");
while ($r = $o->fetch_array()) {
    $update[($r['name'])] = dirname(dirname(__FILE__)) . "/resources/languages/{$r['locale']}/$name.json";
}

# Post

if (isset($_POST['submit']) && !DEMO) {
    foreach ($update as $langName => $file) {
        $lang = str_replace("\r\n", "\n", file_get_contents($file));
        $lang = trim(str_replace("\n", "", $lang));
        $lang = preg_replace('/\s+/', ' ', $lang);
        $items = json_decode($lang, true);

        foreach ($items as $in => $out) {
            $input = str_replace('.', '_', md5($langName) . ':' . basename($file, '.json') . ':' . md5($in));
            if (!isset($_POST[$input])) die("Missing POST field $input");

            $items[$in] = $_POST[$input];
        }

        if (defined("JSON_PRETTY_PRINT")) {
            $new = json_encode($items, JSON_PRETTY_PRINT);
        }
        else {
            $new = json_encode($items);
        }

        $bool = file_put_contents($file, $new);
        if ($bool === false) $studio->showFatalError("Failed to write to $file. Please try making this directory and all files inside have chmod 0777. Contact support for further assistance.");
    }

    header("Location: translate.php?name=$name&success=1");
    die;
}
?>

<div class="panel">
    <h3>Translate <?php echo sanitize_html($_GET['name']); ?>.json</h3>
    <p>Your changes have been saved. Next, please check and make any necessary translations below.</p>
</div>

<form action="" method="post">
    <?php
    foreach ($update as $langName => $file) {
        $lang = str_replace("\r\n", "\n", file_get_contents($file));
        $lang = trim(str_replace("\n", "", $lang));
        $lang = preg_replace('/\s+/', ' ', $lang);
        $items = json_decode($lang, true);
    ?>
    <div class="panel">
        <h3><?php echo $langName; ?></h3>
        <table class="translate">
    <?php
        foreach ($items as $in => $out) {
    ?>
    <tr>
        <td><?php echo $in; ?></td>
        <td><input type="text" name="<?php echo str_replace('.', '_', md5($langName) . ':' . basename($file, '.json') . ':' . md5($in)); ?>" value="<?php echo sanitize_attribute($out); ?>"></td>
    </tr>
    <?php
        }
    ?>
        </table>
    </div>
    <?php
    }
    ?>

    <div class="panel">
        <input type="submit" class="btn blue" value="Save" name="submit">
    </div>
</form>

<?php
$page->footer();
?>
