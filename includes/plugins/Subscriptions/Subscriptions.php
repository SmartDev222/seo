<?php

use Studio\Extend\Plugin;

class Subscriptions extends Plugin
{
    const NAME = "Subscriptions & Plans";
    const DESCRIPTION = "Charge users money to use the tools in your Studio.";
    const AUTHOR = "Bailey Herbert";
    const VERSION = "1.2";

    function start() {
        $this->hook("admin_nav", "showNavigation");
        $this->hook("custom_head", "injectCSS");
        $this->hook("custom_head", "checkSignupPage");
        $this->hook("page_menu_1", "addMenuButton");
        $this->hook("register_form_new_user", "newUser");
        $this->hook("footer", "showBillingPanel");
        // $this->hook("tools_before", "checkSubscription");
        $this->hook("tool_classes", "checkToolClass");
        $this->hook("tool_init", "checkToolPermission");
        $this->hook("admin_user_profile", "adminUserProfile");

        $this->hook("admin_new_user_form", "newUserAdminForm");
        $this->hook("admin_new_user", "newUserAdmin");
    }

    function onUpdate() {
        $this->installLanguageFile($this->pluginDir . "/bin/plugin.subscriptions.json");
    }

    function newUserAdmin($user_id) {
        global $studio;
        $planId = (int)$_POST['plan'];
        $studio->sql->query("UPDATE accounts SET groupId = $planId WHERE id = $user_id");
        $this->setopt("sub-user-$user_id", json_encode([
            "active" => false
        ]));
    }

    function newUserAdminForm() {
        $plans = json_decode($this->getopt("sub-plans"), true);
        require __DIR__ . "/ui/new-user-form.php";
    }

    function adminUserProfile($user_id) {
        global $user, $studio;

        $opt = $this->getopt("sub-user-$user_id");
        if ($opt) {
            $plan_data = json_decode($opt, true);
        }

        $q = $studio->sql->query("SELECT groupId FROM accounts WHERE id = $user_id");
        $row = $q->fetch_object();

        $plan = null;
        $plans = json_decode($this->getopt("sub-plans"), true);
        foreach ($plans as $i => $p) {
            if (in_array($row->groupId, $p['groups'])) {
                $p['id'] = $i;
                $plan = $p;
            }
        }

        if (isset($_GET['submanual'])) require __DIR__ . "/ui/admin-manual.php";
        elseif (isset($_GET['subplan'])) require __DIR__ . "/ui/admin-plan.php";
        else require __DIR__ . "/ui/admin.php";
    }

    function showBillingPanel() {
        global $page, $studio, $account;
        if ($page->getTitle() != "Account") return;

        echo "<div class='container'>";
        require __DIR__ . "/ui/billing.php";
        echo "</div>";
    }

    function newUser($user_id) {
        global $studio;

        $plan = (int)$_GET['plan'] - 1;

        $plans = json_decode($this->getopt("sub-plans"), true);
        $plan = $plans[$plan];
        $group_id = $plan['assign'];

        if ($plan['cost']['onetime'] == 0 && $plan['cost']['monthly'] == 0 && $plan['cost']['annually'] == 0) {
            $this->setopt("sub-user-$user_id", json_encode([
                "active" => true,
                "expires_on" => 0,
                "subscribed_on" => time(),
                "duration" => "onetime"
            ]));
        }
        else {
            $this->setopt("sub-user-$user_id", json_encode([
                "active" => false
            ]));
        }

        $studio->sql->query("UPDATE accounts SET groupId = $group_id WHERE id = $user_id");
    }

    function injectCSS() {
        $path = $this->getPluginDirURL() . "/src/icons.css";
        $path2 = $this->getPluginDirURL() . "/src/pricing.css";

        echo "<link rel='stylesheet' type='text/css' href='$path'>";
        echo "<link rel='stylesheet' type='text/css' href='$path2'>";
    }

    function checkSignupPage() {
        global $page, $studio;

        if ($page->getTitle() == "Register") {
            if (!isset($_GET['plan'])) {
                $studio->redirect("pricing.php");
            }

            $plan = $_GET['plan'];
            if (!is_numeric($plan)) die;
            $plan = (int)$plan - 1;

            $plans = json_decode($this->getopt("sub-plans"), true);
            if (!isset($plans[$plan])) $studio->showFatalError("Plan not found");
        }
    }

    function checkToolPermission($id) {
        global $studio, $account;

        if (!$account->isLoggedIn()) {
            $studio->redirect("pricing.php");
        }

        $user = $this->getopt("sub-user-{$account->getId()}");

        if ($user) {
            $plan_data = json_decode($user, true);

            if (!$plan_data['active']) $studio->redirect("account/");
            if ($plan_data['expires_on'] > 0) {
                if ($plan_data['expires_on'] < time()) {
                    $plan_data['active'] = false;
                    $this->setopt("sub-user-{$account->getId()}", json_encode($plan_data));
                    $studio->redirect("account/");
                }
            }

            $plans = json_decode($this->getopt("sub-plans"), true);
            $plan = null;
            foreach ($plans as $p) {
                if (in_array($account->groupId, $p['groups'])) $plan = $p;
            }

            if (!$plan) return;

            if (!in_array($id, $plan['tools_enabled'])) {
                $studio->redirect("pricing.php?no=1");
            }
        }
    }

    function checkToolClass($id) {
        global $studio, $account;

        if (!$account->isLoggedIn()) {
            return;
        }

        $user = $this->getopt("sub-user-{$account->getId()}");

        if ($user) {
            $plan_data = json_decode($user, true);

            if (!$plan_data['active']) return 'locked';
            if ($plan_data['expires_on'] > 0) {
                if ($plan_data['expires_on'] < time()) {
                    return 'locked';
                }
            }

            $plans = json_decode($this->getopt("sub-plans"), true);
            $plan = null;
            foreach ($plans as $p) {
                if (in_array($account->groupId, $p['groups'])) $plan = $p;
            }

            if (!$plan) return;

            if (!in_array($id, $plan['tools_enabled'])) {
                return 'locked';
            }
        }
    }

    function checkSubscription() {
        global $studio, $account, $tools, $categories;

        if (!$account->isLoggedIn()) {
            $studio->redirect("pricing.php");
        }

        $user = $this->getopt("sub-user-{$account->getId()}");

        if ($user) {
            $plan_data = json_decode($user, true);

            if (!$plan_data['active']) $studio->redirect("account/");
            if ($plan_data['expires_on'] > 0) {
                if ($plan_data['expires_on'] < time()) {
                    $plan_data['active'] = false;
                    $this->setopt("sub-user-{$account->getId()}", json_encode($plan_data));
                    $studio->redirect("account/");
                }
            }

            $plans = json_decode($this->getopt("sub-plans"), true);
            $plan = null;
            foreach ($plans as $p) {
                if (in_array($account->groupId, $p['groups'])) $plan = $p;
            }

            if (!$plan) return;

            // here's a cool little hack
            // let's overwrite the $tools and $categories variables on the tools.php page so it only shows the enabled tools

            $newtools = [];

            if ($this->getopt("sub-plans-show-disabled") == "Off") {
                foreach ($tools as $cat => $tarr) {
                    $newtools[$cat] = [];

                    foreach ($tarr as $i => $id) {
                        if (in_array($id, $plan['tools_enabled'])) {
                            $newtools[$cat][] = $id;
                        }
                    }
                }

                foreach ($newtools as $cat => $tarr) {
                    if (count($tarr) == 0) {
                        foreach ($categories as $i => $v) {
                            if ($v == $cat) {
                                unset($categories[$i]);
                            }
                        }
                    }
                }

                $tools = $newtools;
            }

        }
    }

    function addMenuButton() {
        global $page;

        if ($this->getopt("sub-plans-page") != "On") return;

        $path = $page->getPath();
        $a = ($page->getPage() == "pricing" ? "<div class='arrow-down blue'></div>" : "");

        echo "<li><a href=\"{$path}pricing.php\"><div data-icon-e=\"m\" class=\"icon\"></div>".rt("Pricing")."$a</a></li>";
    }

    function showNavigation() {
        global $plansPricingPage, $plansSettingsPage, $page;

        $path = $page->getPath();
        $a = "";
        $b = "";
        if (isset($plansPricingPage)) $a = "active";
        if (isset($plansSettingsPage)) $b = "active";
?>

<li class="title">
    Subscriptions
</li>
<li class="<?php echo $a; ?>">
    <a href="<?php echo $path; ?>admin/sub-plans.php">
        <i class='material-icons'>shopping_basket</i>
        Plans
    </a>
</li>
<li class="<?php echo $b; ?>">
    <a href="<?php echo $path; ?>admin/sub-settings.php">
        <i class='material-icons'>credit_card</i>
        Options
    </a>
</li>
<?php
    }

    function onEnable() {
        global $studio;

        if ($this->getopt("sub-plans") == null) {
            $free_id = (int)$this->getopt("default-group");

            $toolsList = $studio->getTools();
            $tools = [];

            foreach ($toolsList as $tool) {
                $tools[] = $tool->id;
            }

            $plans = json_encode([
                [
                    "name" => "Free",
                    "groups" => [$free_id],
                    "assign" => $free_id,
                    "cost" => [
                        "onetime" => 0,
                        "monthly" => 0,
                        "annually" => 0
                    ],
                    "tools_enabled" => $tools,
                    "show" => true
                ]
            ]);

            $this->setopt("sub-plans", $plans);
            $this->setopt("sub-plans-page", "On");
            $this->setopt("sub-plans-show-disabled", "On");
            $this->setopt("sub-plans-disabled-action", "redirect");
            $this->setopt("sub-plans-disabled-redirect", "/plans.php");
            $this->setopt("sub-paypal-email", "");
            $this->setopt("sub-currency", "USD");
            $this->setopt("sub-currency-symbol", "$");
        }

        $this->copyFile($this->pluginDir . "/bin/sub-plans.php", "/admin/");
        $this->copyFile($this->pluginDir . "/bin/sub-settings.php", "/admin/");
        $this->copyFile($this->pluginDir . "/bin/pricing.php", "/");
        $this->copyFile($this->pluginDir . "/bin/ipn.php", "/");
        $this->installLanguageFile($this->pluginDir . "/bin/plugin.subscriptions.json");

        return true;
    }

    function onDisable() {
        $this->removeFile("/admin/sub-plans.php");
        $this->removeFile("/admin/sub-settings.php");
        $this->removeFile("/pricing.php");
        $this->removeFile("/ipn.php");
    }
}
