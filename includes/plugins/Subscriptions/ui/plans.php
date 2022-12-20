<?php
$plans = json_decode($studio->getopt("sub-plans"), true);

if (!$plans) {
    die("
        <h1>Hmm, that's not normal!</h1>
        <p>
            The <strong>sub-plans</strong> database option didn't contain the correct data or was corrupted.
            You can re-enable the extension to rebuild the option, but please note that this could erase your existing
            plans in the case of corruption.
        </p>
        <p>
            Contact support if you need help. :)
        </p>
    ");
}

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $plan = null;

    foreach ($plans as $i => $v) {
        if ($id == $i) $plan = $v;
    }

    if ($plan == null) die("No such plan");

    require __DIR__ . "/edit-plan.php";
    die;
}
if (isset($_GET['new'])) {
    require __DIR__ . "/new-plan.php";
    die;
}
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $plan = null;

    foreach ($plans as $i => $v) {
        if ($id == $i) $plan = $v;
    }

    if ($plan == null) die("No such plan");

    require __DIR__ . "/delete-plan.php";
    die;
}
?>

<div class="heading">
    <h1>Plans</h1>
    <h2>Configure pricing plans</h2>
</div>

<div class="panel">
    <div class="pull-right">
        <a class="btn small blue" href="?new" style="vertical-align: top; margin-top: -5px;">New plan</a>
    </div>
    <h3>Plans</h3>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th width="70px" class="center">ID</th>
                    <th>Name</th>
                    <th class="right">Groups</th>
                    <th width="140px" class="right">Assign to</th>
                    <th width="120px" class="center"># Users</th>
                    <th width="200px" class="right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($plans as $id => $plan) {
                ?>
                <tr>
                    <td class="center"><?php echo $id + 1; ?></td>
                    <td class=""><?php echo $plan['name']; ?></td>
                    <td class="right"><?php
                    $groups = [];
                    foreach ($plan['groups'] as $group_id) {
                        $q = $studio->sql->query("SELECT * FROM `groups` WHERE id = $group_id");
                        if ($q->num_rows > 0) {
                            $group = $q->fetch_object();
                            $groups[] = $group->name;
                        }
                    }

                    echo implode(", ", $groups);
                    ?></td>
                    <td class="right"><?php
                    $q = $studio->sql->query("SELECT * FROM `groups` WHERE id = " . $plan['assign']);
                    $group = $q->fetch_object();
                    echo $group->name;
                    ?></td></td>
                    <td class="center"><?php
                    $users = 0;

                    foreach ($plan['groups'] as $group_id) {
                        $q = $studio->sql->query("SELECT COUNT(*) FROM accounts WHERE groupId = $group_id");
                        $r = $q->fetch_array();
                        $users += $r[0];
                    }

                    echo number_format($users);
                    ?></td>
                    <td class="right">
                        <a class="btn tiny" href="?edit=<?php echo $id; ?>">Edit</a>
                        <a class="btn tiny red" href="?remove=<?php echo $id; ?>">Delete</a>
                    </td>
                </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
