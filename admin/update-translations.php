<?php
$noTranslationRedir = true;
require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(11)->setTitle("Translate")->header();

# Get language files

$update = array();
$o = $studio->sql->query("SELECT * FROM languages");

while ($r = $o->fetch_array()) {
    $locale = $r['locale'];
    $update[($r['name'])] = new Studio\Base\Language(dirname(dirname(__FILE__)) . "/resources/languages", $locale);
}

$binLang = new Studio\Base\Language(dirname(dirname(__FILE__)) . "/resources/bin", "en-us");

$binFiles = array();
foreach (scandir(dirname(dirname(__FILE__)) . "/resources/bin/en-us/") as $file) {
    if ($file == "." || $file == "..") continue;
    $binFiles[$file] = array();

    $lang = str_replace("\r\n", "\n", file_get_contents(dirname(dirname(__FILE__)) . "/resources/bin/en-us/" . $file));
    $lang = trim(str_replace("\n", "", $lang));
    $lang = preg_replace('/\s+/', ' ', $lang);
    $items = json_decode($lang, true);

    foreach ($items as $i => $v) {
        $binFiles[$file][$i] = $v;
    }
}

# Post

if (isset($_POST['submit']) && !DEMO) {
    $o = $studio->sql->query("SELECT * FROM languages");

    while ($r = $o->fetch_array()) {
        $locale = $r['locale'];
        $name = $r['name'];
        $dir = dirname(dirname(__FILE__)) . "/resources/languages/$locale/";

        foreach ($binFiles as $origFileName => $origTranslations) {
            $target = $dir . $origFileName;
            $changed = 0;

            if (!file_exists($target)) {
                // the file doesn't exist, create it
                $items = array();

                foreach ($origTranslations as $i => $v) {
                    $posted = md5($name . ':' . $i);
                    if (!isset($_POST[$posted])) die("Missing POST field $posted");
                    $items[$i] = $_POST[$posted];
                }

                $changed = 1;
            }
            else {
                // the file exists, open it and add the missing translations

                $lang = str_replace("\r\n", "\n", file_get_contents($dir . '/' . $origFileName));
                $lang = trim(str_replace("\n", "", $lang));
                $lang = preg_replace('/\s+/', ' ', $lang);
                $items = json_decode($lang, true);

                foreach ($origTranslations as $i => $v) {
                    if (!isset($items[$i])) {
                        $changed++;
                        $posted = md5($name . ':' . $i);
                        if (!isset($_POST[$posted])) die("Missing POST field $posted");
                        $items[$i] = $_POST[$posted];
                    }
                }
            }

            if (defined("JSON_PRETTY_PRINT")) {
                $new = json_encode($items, JSON_PRETTY_PRINT);
            }
            else {
                $new = json_encode($items);
            }

            if ($changed > 0) {
                $bool = file_put_contents($target, $new);
                if ($bool === false) $studio->showFatalError("Failed to write to $file. Please try making this directory and all files inside have chmod 0777. Contact support for further assistance.");
            }
        }
    }

    $studio->setopt('update-missing-translations', '0');
    header("Location: index.php?success=1");
    die;
}

?>

<div class="panel">
    <h3>Add missing translations</h3>
    <p>New translations have been registered in the system but haven't yet been added to your language(s). Please review the translations below to add them to your language(s).</p>
</div>

<form action="" method="post">
    <?php
    foreach ($update as $langName => $language) {
        $items = $language->translations;
    ?>
    <div class="panel">
        <h3><?php echo $langName; ?></h3>
        <table class="translate">
    <?php
        foreach ($binLang->translations as $in => $out) {
            if (!isset($items[$in])) {
    ?>
    <tr>
        <td><?php echo $in; ?></td>
        <td><input type="text" name="<?php echo md5($langName . ':' . $in); ?>" value="<?php echo sanitize_attribute($out); ?>"></td>
    </tr>
    <?php
            }
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
