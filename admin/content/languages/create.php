<?php
require "../../includes/init.php";
$page->setPath("../../../")->requirePermission('admin-access')->setPage(11)->header('content');

$file = $studio->basedir . "/resources/bin/en-us/";
if (!file_exists($file)) die("Not found: $file");

$error = "";
$autoFiles = [];

if (isset($_POST['name'])) {
    if (isset($_POST['auto'])) {
        if (isset($_POST['autoTranslateLanguage'])) {
            $code = $_POST['autoTranslateLanguage'];

            if (!preg_match('/^[a-z]{2}$/', $code)) {
                die('security: regex mismatch');
            }

            try {
                $results = $api->getTranslations($code)->results;

                foreach ($results as $result) {
                    $autoFiles[$result['filename']] = $result['translations'];
                }
            }
            catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
        else {
            $error = 'Please select a language from the list to auto translate.';
        }
    }
    else if (!DEMO) {
        $name = trim($_POST['name']);
        $locale = $_POST['locale'];
        $dir = $_POST['dir'];
        $google = $_POST['google'];

        if (strlen($name) == 0) {
            $error = "Please enter a the name of the language.";
        }
        if ($error == "") {
            $chkp = $studio->sql->prepare("SELECT * FROM languages WHERE name = ? OR locale = ?");
            $chkp->bind_param("ss", $name, $locale);
            $chkp->execute();
            $chkp->store_result();

            if ($chkp->num_rows !== 0) {
                $error = "A language with that locale or name already exists.";
            }
            else {
                $saveTo = $studio->basedir . "/resources/languages/$locale/";
                if (file_exists($saveTo)) {
                    $r = rename($saveTo, $studio->basedir . "/resources/bin/$locale-old-" . time() . "/");
                    if ($r === false) {
                        $error = "Directory already exists: $saveTo. The system failed to automatically remove it. Please rename the directory to something else, like $locale-old.";
                    }
                }
            }

            if ($error == "") {
                $b = mkdir($saveTo);
                if ($b === false) {
                    $error = "Failed to make directory: $saveTo. Please make it manually and set its mode (chmod) to something PHP can access, like 0777.";
                }
                else {
                    $chkp = $studio->sql->prepare("SELECT * FROM languages WHERE name = ? OR locale = ?");
                    $chkp->bind_param("ss", $name, $locale);
                    $chkp->execute();
                    $chkp->store_result();

                    if ($chkp->num_rows !== 0) {
                        $error = "A language with that locale or name already exists.";
                    }
                    else {
                        foreach (scandir($file) as $f) {
                            if (substr($f, 0, 1) == ".") continue;

                            $data = str_replace("\r\n", "\n", file_get_contents($file . $f));
                            $data = trim(str_replace("\n", "", $data));
                            $data = preg_replace('/\s+/', ' ', $data);

                            $items = json_decode($data, true);

                            foreach ($items as $in => $out) {
                                if (!isset($_POST[basename(str_replace(".", "_", $f), '.json') . ':' . md5($in)])) die("Missing field");

                                $items[$in] = $_POST[basename(str_replace(".", "_", $f), '.json') . ':' . md5($in)];
                            }

                            if (defined("JSON_PRETTY_PRINT")) {
                                $new = json_encode($items, JSON_PRETTY_PRINT);
                            }
                            else {
                                $new = json_encode($items);
                            }

                            $bool = file_put_contents($saveTo . $f, $new);
                            if ($bool === false) $studio->showFatalError("Failed to write to $file. Please try making this directory and all files inside have chmod 0777. Contact support for further assistance.");
                        }

                        $p = $studio->sql->prepare("INSERT INTO languages (name, locale, dir, google) VALUES (?, ?, ?, ?)");
                        $p->bind_param("ssss", $name, $locale, $dir, $google);
                        $p->execute();
                        $id = $studio->sql->insert_id;
                        $p->close();

                        header("Location: index.php?success=1");
                        die;
                    }
                }
            }
        }
    }
}
?>

<?php if ($error != "") { ?>
<div class="error"> <?php echo $error; ?> </div>
<?php } ?>

<section>
	<div class="header with-border">
		<h1>
			<a class="back" href="index.php">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-circle" viewBox="0 0 16 16">
					<path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
				</svg>
			</a>
			Create new language
		</h1>
	</div>

<form action="" method="post">
    <div class="panel p-0 pb-4">
        <h3>Language settings</h3>

        <div class="row">
            <div class="col-md-6">
                <div style="margin: 0 0 15px;">
                    <p style="margin: 0 0 2px;">Display Name</p>
                    <input type="text" name="name" class="fancy" value="<?php echo (isset($_POST['name']) ? sanitize_attribute($_POST['name']) : ''); ?>" style="margin: 0;">
                </div>

                <div style="margin: 0;">
                    <p style="margin: 0 0 2px;">Locale</p>
                    <select name="locale" class="fancy">
                        <?php
                        $locales = trim(file_get_contents($studio->basedir . "/resources/bin/locales"));
                        $locales = explode("\n", $locales);

                        $posted = null;
                        if (isset($_POST['locale'])) $posted = $_POST['locale'];

                        foreach ($locales as $locale) {
                            $locale = trim($locale);
                        ?>
                        <option value="<?php echo $locale; ?>" <?php if ($posted == $locale) echo "selected"; ?>><?php echo $locale; ?></option>
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
                        <option value="<?php echo $locale; ?>"><?php echo $locale; ?></option>
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
                        <?php
                        $posted = null;
                        if (isset($_POST['dir'])) $posted = $_POST['dir'];
                        ?>
                        <option value="ltr" <?php if ($posted == "ltr") echo "selected"; ?>>Left to right</option>
                        <option value="rtl" <?php if ($posted == "rtl") echo "selected"; ?>>Right to left</option>
                    </select>
                </div>

                <div style="margin: 0;">
                    <p style="margin: 0 0 2px;">Google</p>
                    <select name="google" class="fancy" style="margin: 0;">
                        <?php
                        $googles = trim(file_get_contents($studio->basedir . "/resources/bin/google"));
                        $googles = explode("\n", $googles);

                        $posted = null;
                        if (isset($_POST['google'])) $posted = $_POST['google'];

                        foreach ($googles as $google) {
                            $google = trim($google);
                        ?>
                        <option value="<?php echo $google; ?>" <?php if ($posted == $google) echo "selected"; ?>><?php echo $google; ?></option>
                        <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="panel px-0 py-4" style="padding-bottom: 0; border-bottom: 1px solid #eeeeee; padding-left: 35px;">
        <div class="row">
            <div class="col-md-6">
                <h1 style="margin-bottom: 5px; font-size: 22px;">Edit translations</h1>
                <p>
                    Go through the phrases below and translate them using the text boxes on the right side. You can
                    press the <kbd>TAB</kbd> key to quickly move from one phrase to the next.
                </p>
            </div>
            <div class="col-md-6">
                <div class="auto-translate">
                    <h2>Auto translate <span class="new">NEW</span></h2>
                    <p>
                        Choose one of the available languages below to automatically translate all phrases in that
                        language. Then, please review each phrase for correctness.
                    </p>

                    <div class="union">
                        <select name="autoTranslateLanguage" class="fancy">
                            <!--
                                Is a language missing from this list?
                                Let me know at support@getseostudio.com!
                            -->
                            <option value="" disabled selected>Choose one...</option>
                            <option value="ar">Arabic</option>
                            <option value="hy">Armenian</option>
                            <option value="az">Azerbaijani</option>
                            <option value="be">Belarusian</option>
                            <option value="bn">Bengali</option>
                            <option value="bs">Bosnian</option>
                            <option value="bg">Bulgarian</option>
                            <option value="ca">Catalan</option>
                            <option value="zh">Chinese (Simplified)</option>
                            <option value="hr">Croatian</option>
                            <option value="cs">Czech</option>
                            <option value="da">Danish</option>
                            <option value="nl">Dutch</option>
                            <option value="en">English</option>
                            <option value="et">Estonian</option>
                            <option value="fi">Finnish</option>
                            <option value="fr">French</option>
                            <option value="gl">Galician</option>
                            <option value="ka">Georgian</option>
                            <option value="de">German</option>
                            <option value="el">Greek</option>
                            <option value="he">Hebrew</option>
                            <option value="hi">Hindi</option>
                            <option value="hu">Hungarian</option>
                            <option value="id">Indonesian</option>
                            <option value="it">Italian</option>
                            <option value="ja">Japanese</option>
                            <option value="kk">Kazakh</option>
                            <option value="ko">Korean</option>
                            <option value="ky">Kyrgyz</option>
                            <option value="lv">Latvian</option>
                            <option value="lt">Lithuanian</option>
                            <option value="mk">Macedonian</option>
                            <option value="ms">Malay</option>
                            <option value="mn">Mongolian</option>
                            <option value="fa">Persian</option>
                            <option value="pl">Polish</option>
                            <option value="pt">Portuguese</option>
                            <option value="ro">Romanian</option>
                            <option value="ru">Russian</option>
                            <option value="sr">Serbian</option>
                            <option value="sk">Slovak</option>
                            <option value="sl">Slovenian</option>
                            <option value="es">Spanish</option>
                            <option value="sv">Swedish</option>
                            <option value="th">Thai</option>
                            <option value="tr">Turkish</option>
                            <option value="uk">Ukrainian</option>
                            <option value="ur">Urdu</option>
                            <option value="uz">Uzbek</option>
                            <option value="vi">Vietnamese</option>
                        </select>
                        <input type="submit" class="btn" value="Translate" name="auto">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    foreach (scandir($file) as $f) {
        if (substr($f, 0, 1) == ".") continue;

        $data = str_replace("\r\n", "\n", file_get_contents($file . $f));
        $data = trim(str_replace("\n", "", $data));
        $data = preg_replace('/\s+/', ' ', $data);

        $items = json_decode($data, true);

        echo "<div class=\"panel px-0 py-4\"><h3>{$f}</h3>";
        echo "<table class=\"translate\">";

        foreach ($items as $in => $out) {
            $fieldName = basename(str_replace(".", "_", $f), '.json') . ':' . md5($in);
            if (isset($_POST[$fieldName])) $out = $_POST[$fieldName];
            if (isset($autoFiles[$f])) {
                if (isset($autoFiles[$f][$in])) {
                    $out = $autoFiles[$f][$in];
                }
            }
    ?>
    <tr>
        <td><?php echo $in; ?></td>
        <td><input type="text" class="select-all" name="<?php echo $fieldName; ?>" value="<?php echo sanitize_attribute($out); ?>"></td>
    </tr>
    <?php
        }

        echo "</table></div>";
    }
    ?>

    <div class="panel px-0 py-4">
        <input type="submit" name="submit" class="btn blue" value="Create">
    </div>
</form>
</section>

<?php
$page->footer();
?>
