<?php

if (isset($_POST['name'])) {
    $name = $_POST['name'];
    $groups = (isset($_POST['groups']) ? $_POST['groups'] : null);
    $tools_enabled = (isset($_POST['tools_enabled']) ? $_POST['tools_enabled'] : []);
    $onetime = $_POST['onetime'];
    $monthly = $_POST['monthly'];
    $annually = $_POST['annually'];
    $assign = $_POST['assign'];
    $show = $_POST['show'] == "On";

    if ($groups != null) {
        $plan['assign'] = $assign;

        $plan['cost']['annually'] = $annually;
        $plan['cost']['monthly'] = $monthly;
        $plan['cost']['onetime'] = $onetime;

        $plan['name'] = $name;
        $plan['groups'] = $groups;
        $plan['tools_enabled'] = $tools_enabled;
        $plan['show'] = $show;

        $plans[$id] = $plan;
        $studio->setopt("sub-plans", json_encode($plans));

        header("Location: sub-plans.php?success");
        die;
    }
    else {
        $studio->showError("Please choose at least one group to apply this plan to.");
    }
}

?>

<div class="heading">
    <h1>Edit plan</h1>
    <h2><?php echo $plan['name']; ?></h2>
</div>

<div class="panel v2 back">
    <a href="sub-plans.php">
        <i class="material-icons">&#xE5C4;</i> Back
    </a>
</div>

<form action="" method="post">
    <div class="panel">
        <p>Name</p>
        <input type="text" class="fancy" name="name" value="<?php echo sanitize_attribute($plan['name']); ?>">

        <p>Group <a href="javascript:void(0);" onclick="alert('Because all users are assigned to a group, SEO Studio uses groups to determine which plan a user is subscribed to.\n\nEach plan should have its own group, and all users on that group will be considered a part of that plan.\n\nYou can create a new group from the Members admin page.')">(?)</a></p>
        <select multiple name="groups[]" class="fancy">
            <?php
            $q = $studio->sql->query("SELECT * FROM `groups` ORDER BY `admin-access`, id DESC");
            while ($g = $q->fetch_object()) {
                $s = (in_array($g->id, $plan['groups']) ? "selected" : "");
                echo "<option value='{$g->id}' $s>{$g->name}</option>";
            }
            ?>
        </select>

        <p>Allowed tools (ctrl+click to select multiple)</p>
        <select multiple name="tools_enabled[]" class="fancy" style="height: 300px;">
            <?php
            $tools = $studio->getTools();

            foreach ($tools as $tool) {
                $s = (in_array($tool->id, $plan['tools_enabled']) ? "selected" : "");
                echo "<option value='{$tool->id}' $s>{$tool->name}</option>";
            }
            ?>
        </select>
    </div>

    <div class="panel">
        <h3>Pricing</h3>
        <p>If a field is 0, then that payment option is disabled. If all fields are 0, then the plan is free. Otherwise, the user can pick which of the enabled payment options they want to pay.</p><br>

        <p>One-time cost:</p>
        <input type="text" class="fancy" name="onetime" value="<?php echo $plan['cost']['onetime']; ?>">

        <p>Monthly cost:</p>
        <input type="text" class="fancy" name="monthly" value="<?php echo $plan['cost']['monthly']; ?>">

        <p>Annual cost:</p>
        <input type="text" class="fancy" name="annually" value="<?php echo $plan['cost']['annually']; ?>">
    </div>

    <div class="panel">
        <h3>Options</h3>

        <p>When a user purchases this plan, move them to the following group (should usually be the same as the group above):</p>
        <select name="assign" class="fancy">
            <?php
            $q = $studio->sql->query("SELECT * FROM `groups` ORDER BY `admin-access`, id DESC");
            while ($g = $q->fetch_object()) {
                $s = ($plan['assign'] == $g->id ? "selected" : "");
                echo "<option value='{$g->id}' $s>{$g->name}</option>";
            }
            ?>
        </select>

        <p>Visibility on pricing page:</p>
        <select name="show" class="fancy">
            <option value="On" <?php if ($plan['show']) echo "selected"; ?>>Show this plan on the pricing page</option>
            <option value="Off" <?php if (!$plan['show']) echo "selected"; ?>>Do not show this plan</option>
        </select>
    </div>

    <div class="panel">
        <input type="submit" class="btn blue" value="Save">
    </div>
</div>
