<?php

$plans = json_decode($studio->getopt("sub-plans"), true);
$numColumns = 1;
$tempTools = [];

$showPlans = [];
$pricing = [];

function metric($input) {
    return is_numeric($input) ? $input : 0;
}

foreach ($plans as $i => $plan) {
    if ($plan['show']) {
        foreach ($plan['tools_enabled'] as $id) {
            $tool = $studio->getToolById($id);
            if (!$tool) continue;
            if (!isset($tempTools[$id])) $tempTools[$id] = 1;
            else $tempTools[$id]++;
        }

        $numColumns++;

        if ($plan['cost']['onetime'] > 0 && !in_array("onetime", $pricing)) $pricing[] = "onetime";
        if ($plan['cost']['monthly'] > 0 && !in_array("monthly", $pricing)) $pricing[] = "monthly";
        if ($plan['cost']['annually'] > 0 && !in_array("annually", $pricing)) $pricing[] = "annually";

        $metric = metric($plan['cost']['onetime']) + metric($plan['cost']['monthly']) + metric($plan['cost']['annually']);

        $showPlans[$i] = $metric;
    }
}

$width = round(100 * (1 / $numColumns), 10);
$tools = [];

arsort($tempTools);
asort($showPlans);

foreach ($tempTools as $id => $num) {
    $tools[] = $id;
}

$symbol = $studio->getopt("sub-currency-symbol");
?>

<section class="title">
    <div class="container">
        <h1><?php pt("Pricing"); ?></h1>
    </div>
</section>

<section class="pricing">
    <div class="container">
        <?php if (isset($_GET['no'])) { ?>
        <div class="redbox">
            <?php pt("Your plan does not include that tool."); ?>
        </div>
        <?php } ?>

        <div class="columns">
            <div class="column sidebar" style="width: <?php echo $width; ?>%;">
                <div class="head">
                    &nbsp;
                </div>
                <div class="body">
                    <?php
                    foreach ($tools as $id) {
                        $tool = $studio->getToolById($id);
                        $tool->name = rt($tool->name);
                        echo "<div class=\"feature\">{$tool->name}</div>";
                    }
                    foreach ($pricing as $p) {
                        $label = rt("Pay once");
                        if ($p == "monthly") $label = rt("Pay monthly");
                        if ($p == "annually") $label = rt("Pay yearly");

                        echo "<div class=\"feature price\">$label</div>";
                    }
                    ?>
                </div>
            </div>

            <?php
            $myi = 0;
            $i = 0;
            foreach ($showPlans as $num => $x) {
                $plan = $plans[$num];
                if (in_array($account->groupId, $plan['groups'])) $myi = $i;
                $i++;
            }

            $i = 0;
            foreach ($showPlans as $num => $x) {
                $plan = $plans[$num];
                if (!$plan['show']) continue;
            ?>
            <div class="column plan">
                <div class="head">
                    <?php echo sanitize_html($plan['name']); ?>
                </div>
                <div class="body">
                    <?php
                    foreach ($tools as $id) {
                        $tool = $studio->getToolById($id);
                        $tool->name = rt($tool->name);

                        if (in_array($id, $plan['tools_enabled']))
                            echo "<div class=\"feature on\"><strong>{$tool->name}</strong><i class=\"material-icons\">&#xE876;</i></div>";
                        else
                            echo "<div class=\"feature off\"><strong>{$tool->name}</strong><i class=\"material-icons\">&#xE14C;</i></div>";
                    }

                    $free = true;
                    foreach ($pricing as $p) {
                        if ($plan['cost'][$p] > 0) $free = false;
                    }

                    echo "<div class='bbr'>";
                    foreach ($pricing as $p) {
                        if ($free) {
                            echo "<div class=\"feature price\">" . rt("Free") . "</div>";
                        }
                        else {
                            $label = "";
                            if ($p == "monthly") $label = " " . rt("/mo");
                            if ($p == "annually") $label = " " . rt("/yr");

                            if ($plan['cost'][$p] == 0)
                                echo "<div class=\"feature price\">-</div>";
                            else
                                echo "<div class=\"feature price\"><em>$symbol</em>" . $plan['cost'][$p] ." $label</div>";
                        }
                    }
                    echo "</div>";
                    ?>

                    <div class="foot">
                        <?php if ($account->isLoggedIn()) { ?>
                            <?php if (in_array($account->groupId, $plan['groups'])) echo "<div class=\"current-plan\"><strong>".rt("Current Plan")."</strong></div>"; else echo ""; ?>
                        <?php } ?>

                        <div class="action">
                            <?php if (!$account->isLoggedIn() || !in_array($account->groupId, $plan['groups'])) { ?><a class="btn" href="<?php
                                if (!$account->isLoggedIn()) echo "account/register.php?plan="; else echo "account/index.php?switchplan="; ?><?php echo $num+1; ?>"><?php

                                if ($account->isLoggedIn()) {
                                    if ($i < $myi) pt("Downgrade");
                                    else pt("Upgrade");
                                }
                                else pt("Sign up");

                            ?></a><?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                $i++;
            }
            ?>
        </div>
    </div>
</section>
