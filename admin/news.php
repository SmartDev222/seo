<?php

use Studio\Util\ParsedownExtra;

require "includes/init.php";
$page->setPath("../")->requirePermission('admin-access')->setPage(30)->header();

$releasesDir = dirname(__DIR__) . '/content/releases/';
$releaseFileNames = array_diff(scandir($releasesDir), array('.', '..'));
$releases = array();

foreach ($releaseFileNames as $fileName) {
    if (preg_match('/^v(\d+\.\d+)\.md$/', $fileName, $matches)) {
        $versionParts = explode('.', $matches[1]);
        $sort = (10000 * $versionParts[0]) + $versionParts[1];

        $releases[] = array(
            'file' => $releasesDir . $fileName,
            'version' => $matches[1],
            'sort' => $sort
        );
    }
}

usort($releases, function($a, $b) {
    return $a['sort'] < $b['sort'] ? 1 : -1;
});

$targetVersion = $releases[0]['version'];
$targetRelease;

if (isset($_GET['v'])) {
    if (!preg_match('/^\d+\.\d+$/', $_GET['v'])) {
        die;
    }

    $targetVersion = $_GET['v'];
}

foreach ($releases as $release) {
    if ($release['version'] === $targetVersion) {
        $targetRelease = $release;
    }
}

if (!$targetRelease) {
    die("Release not found.");
}

$parsedown = new ParsedownExtra();
$html = $parsedown->text(file_get_contents($targetRelease['file']));
$html = str_replace('src="/release/', 'src="../content/releases/v' . $targetRelease['version'] . '/', $html);

?>

<div class="header-v2 gray">
    <h1>News</h1>
    <p>Changes and announcements</p>
</div>

<div class="version-tabs">
    <label class="arrow-container">
        <select name="version">
            <?php
                foreach ($releases as $release) {
            ?>
            <option value="<?php echo $release['version']; ?>"<?php echo ($targetVersion === $release['version'] ? ' selected' : ''); ?>>Version <?php echo $release['version']; ?></option>
            <?php
                }
            ?>
        </select>

        <i class="material-icons">arrow_drop_down</i>
        <img src="../resources/images/load32.gif" width="16px">
    </label>

    <script type="text/javascript">
        var container = $('.arrow-container');
        var versionInput = $('select[name=version]');
        var currentVersion = '<?php echo $targetVersion; ?>';

        versionInput.on('change', function() {
            var value = versionInput.val();

            if (value != currentVersion) {
                window.location = 'news.php?v=' + value;
                container.addClass('loading');
            }
        });
    </script>
</div>

<div class="document markdown">
    <?php echo $html; ?>
</div>

<?php
$page->footer();
?>
