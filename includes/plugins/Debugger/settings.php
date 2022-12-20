<?php
    if (isset($_POST['csrf']) && $_POST['csrf'] == $studio->config['session']['token']) {
        if ($_POST['action'] == 'code') {
            $code = strtoupper(substr(md5(time()), 0, 3).
                    substr(md5(__FILE__), 3, 3).
                    substr(md5($_SERVER['REMOTE_ADDR'] . floor(time() / 15)), 6, 3).
                    substr(md5($_SERVER['REMOTE_ADDR'] . time() . __FILE__), 9, 3));

            $studio->setopt('remote-debugging-code', $code);

            if (isset($_SERVER['REQUEST_URI'])) {
                header('Location: ' . $_SERVER['REQUEST_URI']);
                die;
            }
        }

        if ($_POST['action'] == 'clear') {
            $studio->setopt('remote-debugging-sessions', base64_encode(serialize([])));

            if (isset($_SERVER['REQUEST_URI'])) {
                header('Location: ' . $_SERVER['REQUEST_URI']);
                die;
            }
        }
    }

    $code = $studio->getopt('remote-debugging-code');
    $sessions = unserialize(base64_decode($studio->getopt('remote-debugging-sessions')));

    krsort($sessions);
    $sessions = array_slice($sessions, 0, 100);
?>

<div class="panel v2">
    <h3>Remote access code</h3>
    <p>
        This access code will allow the developer to remotely debug errors in the script. This will NOT grant them
        access to your files, databases, or user data. To disable access, simply disable the extension.
    </p>

    <form action="" method="post" style="margin-top: 20px;">
        <input type="hidden" name="csrf" value="<?php echo $studio->config['session']['token']; ?>">
        <input type="hidden" name="action" value="code">
        <?php if (empty($code)) { ?>
        <input type="submit" class="btn blue" value="Generate code">
        <?php } else { ?>
        <pre style="display: inline-block; border: 1px solid #eee; background-color: #f5f5f5; border-radius: 3px; padding: 5px 13px; font-size: 16px; vertical-align: middle; margin-right: 5px;"><?php echo $code; ?></pre>
        <input type="submit" class="btn blue" value="Regenerate code" style="vertical-align: middle;">
        <?php } ?>
    </form>
</div>
<div class="panel v2">
    <div class="pull-right">
        <form action="" method="post">
            <input type="hidden" name="csrf" value="<?php echo $studio->config['session']['token']; ?>">
            <input type="hidden" name="action" value="clear">
            <input type="submit" class="btn" value="Clear" style="vertical-align: top; margin-top: -5px;">
        </form>
    </div>
    <h3>Session history</h3>

    <div class="table-container" style="max-height: 500px; overflow-y: auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th class="left" width="160px">IP address</th>
                    <th class="right" width="150px">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $i => $session) { ?>
                    <tr>
                        <td><?php echo $session[2]; ?></td>
                        <td class="left"><?php echo $session[1]; ?></td>
                        <td class="right">
                            <div class="time right">
                                <?php echo (new \Studio\Display\TimeAgo($session[0]))->get(); ?>
                                <span data-time="<?php echo $session[0]; ?>"><i class="material-icons">access_time</i></span>
                            </div>
                        </td>
                    </tr>
                <?php } if (empty($sessions)) { ?>
                    <tr><td colspan="3">No connections have been made yet.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
