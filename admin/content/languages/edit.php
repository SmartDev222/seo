<?php
require "../../includes/init.php";

$id = $_GET['id'];
if (!is_numeric($id)) die;

$q = $studio->sql->query("SELECT * FROM languages WHERE id = $id");
if ($q->num_rows == 0) die("404");

$lang = $q->fetch_array();
$file = $studio->basedir . "/resources/languages/" . $lang['locale'] . "/";
if (!file_exists($file)) die("Not found: $file");

$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

if (isset($_GET['cdelete']) && !DEMO) {
    if ($studio->getopt("default-language") == $lang['locale']) die("You cannot delete this language because it is currently set as the default.");

    $studio->sql->query("DELETE FROM languages WHERE id = $id");

    header("Location: index.php");
    die;
}

if (isset($_POST['name']) && !DEMO) {
    $name = $_POST['name'];
    $locale = $_POST['locale'];
    $countries = $_POST['countries'];
    $dir = $_POST['dir'];
    $google = $_POST['google'];

    foreach (scandir($file) as $f) {
        if (substr($f, 0, 1) == ".") continue;

        $data = str_replace("\r\n", "\n", file_get_contents($file . $f));
        $data = trim(str_replace("\n", "", $data));
        $data = preg_replace('/\s+/', ' ', $data);

        $items = json_decode($data, true);

        foreach ($items as $in => $out) {
            if (!isset($_POST[basename(str_replace(".", "_", $f), '.json') . ':' . md5($in)])) die("Missing field: $in");

            $items[$in] = $_POST[basename(str_replace(".", "_", $f), '.json') . ':' . md5($in)];
        }

        if (defined("JSON_PRETTY_PRINT")) {
            $new = json_encode($items, JSON_PRETTY_PRINT);
        }
        else {
            $new = json_encode($items);
        }

        $bool = file_put_contents($file . $f, $new);
        if ($bool === false) $studio->showFatalError("Failed to write to $file. Please try making this directory and all files inside have chmod 0777. Contact support for further assistance.");
    }

    $p = $studio->sql->prepare("UPDATE languages SET name=?, locale=?, dir=?, google=?, countries=? WHERE id=$id");
    $p->bind_param("sssss", $name, $locale, $dir, $google, $countries);
    $p->execute();
    $p->close();

    if ($locale != $lang['locale']) {
        // we changed names

        $base = $studio->basedir . "/resources/languages/";
        if (file_exists($base . $name)) {
            rename($base . $name, $base . $name . "-old-" . rand());
        }
        $bool = rename($base . $lang['locale'], $base . $locale);
        if ($bool === false) {
            $studio->showFatalError("Failed to move {$base}{$lang['locale']}: permission denied");
        }

        if ($studio->getopt("default-language") == $lang['locale']) {
            $studio->setopt("default-language", $locale);
        }
    }

    header("Location: edit.php?id=$id&success=1");
    die;
}
?>

<?php if (isset($_GET['remove'])) { ?>

<div class="panel">
    <h3>Confirm Deletion</h3>
    <p style="margin: 0 0 15px;">The language will be removed from the database, but the language files (located in /resources/languages/<?php echo $lang['locale']; ?>/) will not be deleted from the disk.</p>

    <a class="btn red" href="?id=<?php echo $id; ?>&cdelete=1">Delete</a>
    <a class="btn" href="?id=<?php echo $id; ?>">Cancel</a>
</div>

<?php } ?>

<section>
	<div class="header with-border">
		<h1>
			<a class="back" href="index.php">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
					<path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
				</svg>
			</a>
			Edit <?php echo $lang['name']; ?>
		</h1>
        <div class="actions">
            <a class="btn btn-danger" href="?remove&id=<?php echo $id; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                    <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1H2.5zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5zM8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5zm3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0z"/>
                </svg>
                Delete
            </a>
        </div>
	</div>

<form action="" method="post">
    <div class="panel p-0 pb-4">
        <h3>Language settings</h3>

        <div class="row">
            <div class="col-md-6">
                <div style="margin: 0 0 15px;">
                    <p style="margin: 0 0 2px;">Display Name</p>
                    <input type="text" name="name" class="fancy" value="<?php echo sanitize_attribute($lang['name']); ?>" style="margin: 0;">
                </div>

                <div style="margin: 0;">
                    <p style="margin: 0 0 2px;">Locale</p>
                    <select name="locale" class="fancy">
                        <?php
                        $locales = trim(file_get_contents($studio->basedir . "/resources/bin/locales"));
                        $locales = explode("\n", $locales);

                        foreach ($locales as $locale) {
                            $locale = trim($locale);
                        ?>
                        <option value="<?php echo $locale; ?>" <?php if ($lang['locale'] == $locale) echo "selected"; ?>><?php echo $locale; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>

                <div style="margin: 0;">
                    <p style="margin: 0 0 2px;" title="This is the language that will be used while listing country names.">Locale for country names</p>
                    <select name="countries" class="fancy">
                        <?php
                        $countriesDir = $studio->bindir . '/countries';
                        $countryFileNames = scandir($countriesDir);
                        $countries = [];

                        foreach ($countryFileNames as $fileName) {
                            if (substr($fileName, 0, 1) === '.') continue;

                            $filePath = $countriesDir . '/' . $fileName;
                            $contents = json_decode(file_get_contents($filePath), true);

                            $countries[] = $contents['locale'];
                        }

                        foreach ($countries as $locale) {
                            $locale = trim($locale);
                        ?>
                        <option value="<?php echo $locale; ?>" <?php if ($lang['countries'] == $locale) echo "selected"; ?>><?php echo $locale; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div style="margin: 0 0 15px;">
                    <p style="margin: 0 0 2px;">Display</p>
                    <select name="dir" class="fancy" style="margin: 0;">
                        <option value="ltr" <?php if ($lang['dir'] == "ltr") echo "selected"; ?>>Left to right</option>
                        <option value="rtl" <?php if ($lang['dir'] == "rtl") echo "selected"; ?>>Right to left</option>
                    </select>
                </div>

                <div style="margin: 0;">
                    <p style="margin: 0 0 2px;">Google</p>
                    <select name="google" class="fancy" style="margin: 0;">
                        <?php
                        $googles = trim(file_get_contents($studio->basedir . "/resources/bin/google"));
                        $googles = explode("\n", $googles);

                        foreach ($googles as $google) {
                            $google = trim($google);
                        ?>
                        <option value="<?php echo $google; ?>" <?php if ($lang['google'] == $google) echo "selected"; ?>><?php echo $google; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>

        <input type="submit" class="btn blue" value="Save">
        <a class="btn" href="index.php">Cancel</a>
    </div>

    <?php
    foreach (scandir($file) as $f) {
        if (substr($f, 0, 1) == ".") continue;

        $data = str_replace("\r\n", "\n", file_get_contents($file . $f));
        $data = trim(str_replace("\n", "", $data));
        $data = preg_replace('/\s+/', ' ', $data);

        $items = json_decode($data, true);

        echo "<div class=\"panel px-0 py-4 translation-group\"><div class='pull-right'><a class='btn small raw'>Raw</a></div><h3>{$f}</h3>";
        echo "<table class=\"translate\">";

        foreach ($items as $in => $out) {
    ?>
    <tr>
        <td><?php echo $in; ?></td>
        <td class="pe-0"><input type="text" name="<?php echo basename(str_replace(".", "_", $f), '.json') . ':' . md5($in); ?>" value="<?php echo sanitize_attribute($out); ?>"></td>
    </tr>
    <?php
        }

        echo "</table>";
        echo "<pre class='code-editor hidden'>" . file_get_contents($file . $f) . "</pre></div>";
    }
    ?>

    <div class="panel px-0 py-4">
        <input type="submit" class="btn blue" value="Save">
    </div>
</form>
</section>

<?php
$page->footer();
?>
