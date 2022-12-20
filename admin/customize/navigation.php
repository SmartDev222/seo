<?php
require "../includes/init.php";
$page->setPath("../../")->requirePermission('admin-access')->setPage(19)->header('customize');

if (isset($_POST['nav']) && !DEMO) {
    $inputs = array(
        "navlink1", "navlink1i", "navlink1t",
        "navlink2", "navlink2i", "navlink2t",
        "navlink3", "navlink3i", "navlink3t"
    );

    foreach ($inputs as $input) {
        if (!isset($_POST[$input])) $studio->showFatalError("Missing POST parameter $input");
        $studio->setopt($input, $_POST[$input]);
    }

    $checkboxes = array(
        "navlink1b",
        "navlink2b",
        "navlink3b",
    );

    foreach ($checkboxes as $name) {
        $value = isset($_POST[$name]) && $_POST[$name] === '1' ? '1' : '0';
        $studio->setopt($name, $value);
    }

    header("Location: navigation.php?success=1");
    die;
}
?>

<div class="panel">
    <h3>Customize navigation links</h3>
    <p>You can add up to three custom navigation links to the header. You can also disable existing links from the <a href="../settings/navigation.php">advanced navigation settings</a>.</p>

    <form style="margin: 25px 0 0;" action="" method="post">
        <div class="custom-navlink">
            <input type="text" name="navlink1t" placeholder="Enter text..." value="<?php echo $studio->getopt('navlink1t'); ?>">
            <input type="text" name="navlink1" placeholder="http://..." value="<?php echo $studio->getopt('navlink1'); ?>">
            <input type="text" name="navlink1i" placeholder="Choose icon..." class="choose-icon" value="<?php echo $studio->getopt('navlink1i'); ?>">

            <label for="navlink1b">
                <input type="checkbox" name="navlink1b" id="navlink1b" value="1" <?php echo $studio->getopt('navlink1b') === '1' ? 'checked' : ''; ?>>
                Open in new tab
            </label>
        </div>
        <div class="custom-navlink">
            <input type="text" name="navlink2t" placeholder="Enter text..." value="<?php echo $studio->getopt('navlink2t'); ?>">
            <input type="text" name="navlink2" placeholder="http://..." value="<?php echo $studio->getopt('navlink2'); ?>">
            <input type="text" name="navlink2i" placeholder="Choose icon..." class="choose-icon" value="<?php echo $studio->getopt('navlink2i'); ?>">

            <label for="navlink2b">
                <input type="checkbox" name="navlink2b" id="navlink2b" value="1" <?php echo $studio->getopt('navlink2b') === '1' ? 'checked' : ''; ?>>
                Open in new tab
            </label>
        </div>
        <div class="custom-navlink">
            <input type="text" name="navlink3t" placeholder="Enter text..." value="<?php echo $studio->getopt('navlink3t'); ?>">
            <input type="text" name="navlink3" placeholder="http://..." value="<?php echo $studio->getopt('navlink3'); ?>">
            <input type="text" name="navlink3i" placeholder="Choose icon..." class="choose-icon" value="<?php echo $studio->getopt('navlink3i'); ?>">

            <label for="navlink3b">
                <input type="checkbox" name="navlink3b" id="navlink3b" value="1" <?php echo $studio->getopt('navlink3b') === '1' ? 'checked' : ''; ?>>
                Open in new tab
            </label>
        </div>

        <input type="submit" class="btn blue" name="nav" value="Save">
    </form>
</div>

<div class="icon-picker">
    <div class="picker-window">
        <div class="content">
            <div class="icon-grid" id="grid">
                <a class="icon-element" data-id=""><span></span></a>
            </div>
        </div>
        <div class="commit">
            <a class="btn gray" id="cancel">Cancel</a>
            <a class="btn blue" id="select">Select</a>
        </div>
    </div>
</div>

<script type="text/template" id="icon-template">
    <a class="icon-element" data-id="a"><span>a</span></a>
</script>

<script>
    var picker = $('.icon-picker');
    var contentWindow = picker.find('.content');
    var grid = $('#grid');
    var template = $('#icon-template').html();
    var icons = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"#$%^\'()*+,-./:;<=>?@[]^_`{|}~\\\ue000\ue001\ue002\ue003\ue004\ue005\ue006\ue007\ue008\ue009\ue00a\ue00b\ue00c\ue00d\ue00e\ue00f\ue010\ue011\ue012\ue013\ue014\ue015\ue016\ue017\ue018\ue019\ue01a\ue01b\ue01c\ue01d\ue01e\ue01f\ue020\ue021\ue022\ue023\ue024\ue025\ue026\ue027\ue028'.split('');
    var existing = '';
    var intended = '';
    var selection = null;

    icons.forEach(function(icon) {
        var element = $(template);
        element.attr('data-id', icon);
        element.find('span').html(icon);
        element.appendTo(grid);
    });

    var iconElements = $('#grid .icon-element');

    $('.choose-icon').on('focus', function(e) {
        existing = e.target.value;
        selection = $(e.target);
        intended = existing;

        var te = $('[data-id="' + existing + '"]');

        iconElements.removeClass('active');
        te.addClass('active');
        picker.addClass('active');

        if (te.length) {
            te[0].scrollIntoView();
            contentWindow.scrollTop(contentWindow.scrollTop() - 40);
        }
    });

    $('#cancel').on('click', function() {
        selection.val(existing);
        selection = null;
        picker.removeClass('active');
    });

    $('#select').on('click', function() {
        selection.val(intended).blur();
        selection = null;
        intended = '';
        picker.removeClass('active');
    });

    iconElements.on('click', function(e) {
        intended = $(this).attr('data-id');
        selection.val(intended);
        iconElements.removeClass('active');
        $(this).addClass('active');
    });

    iconElements.on('dblclick', function(e) {
        intended = $(this).attr('data-id');
        selection.val(intended);
        iconElements.removeClass('active');
        $(this).addClass('active');

        selection.val(intended).blur();
        selection = null;
        intended = '';
        picker.removeClass('active');
    });

    picker.on('click', function(e) {
        if (e.target === picker[0]) {
            selection.val(existing);
            selection = null;
            picker.removeClass('active');
        }
    });
</script>

<?php
$page->footer();
?>
