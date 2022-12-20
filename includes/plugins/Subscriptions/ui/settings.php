<?php

if (isset($_POST['sub-plans-show-disabled'])) {
    $plans_show_disabled = ($_POST['sub-plans-show-disabled'] == "On" ? "On" : "Off");
    $plans_page = ($_POST['sub-plans-page'] == "On" ? "On" : "Off");
    $paypal_email = $_POST['sub-paypal-email'];
    $currency_code = $_POST['sub-currency'];
    $currency_symbol = $_POST['sub-currency-symbol'];

    $studio->setopt("sub-plans-show-disabled", $plans_show_disabled);
    $studio->setopt("sub-plans-page", $plans_page);
    $studio->setopt("sub-paypal-email", $paypal_email);
    $studio->setopt("sub-currency", $currency_code);
    $studio->setopt("sub-currency-symbol", $currency_symbol);


}
?>

<div class="heading">
    <h1>Subscription options</h1>
    <h2>Change how subscriptions work</h2>
</div>

<form action="" method="post">
    <div class="panel settings">
        <div class="setting">
            <table>
                <tr>
                    <td width="50%">
                        Show the pricing page in navbar
                    </td>
                    <td>
                        <div class="toggle">
                            <input type="hidden" name="sub-plans-page" value="<?php echo $studio->getopt('sub-plans-page'); ?>">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="setting">
            <table class="large">
                <tr>
                    <td width="50%">
                        PayPal account email
                        <span><strong>Must</strong> be the primary email on the account you'd like to receive funds in.</span>
                    </td>
                    <td>
                        <input type="text" name="sub-paypal-email" class="fancy" value="<?php echo $studio->getopt('sub-paypal-email'); ?>">
                    </td>
                </tr>
            </table>
        </div>
        <div class="setting">
            <table class="">
                <tr>
                    <td width="50%">
                        Currency code
                    </td>
                    <td>
                        <select name="sub-currency">
                            <?php $cur = $studio->getopt("sub-currency"); ?>

                            <option value="USD" <?php if ($cur == "USD") echo "selected"; ?>>USD</option>
                            <option value="AUD" <?php if ($cur == "AUD") echo "selected"; ?>>AUD</option>
                            <option value="BRL" <?php if ($cur == "BRL") echo "selected"; ?>>BRL (in-country clients only)</option>
                            <option value="CAD" <?php if ($cur == "CAD") echo "selected"; ?>>CAD</option>
                            <option value="CZK" <?php if ($cur == "CZK") echo "selected"; ?>>CZK</option>
                            <option value="DKK" <?php if ($cur == "DKK") echo "selected"; ?>>DKK</option>
                            <option value="EUR" <?php if ($cur == "EUR") echo "selected"; ?>>EUR</option>
                            <option value="HKD" <?php if ($cur == "HKD") echo "selected"; ?>>HKD</option>
                            <option value="ILS" <?php if ($cur == "ILS") echo "selected"; ?>>ILS</option>
                            <option value="MYR" <?php if ($cur == "MYR") echo "selected"; ?>>MYR (in-country clients only)</option>
                            <option value="MXN" <?php if ($cur == "MXN") echo "selected"; ?>>MXN</option>
                            <option value="NOK" <?php if ($cur == "NOK") echo "selected"; ?>>NOK</option>
                            <option value="NZD" <?php if ($cur == "NZD") echo "selected"; ?>>NZD</option>
                            <option value="PHP" <?php if ($cur == "PHP") echo "selected"; ?>>PHP</option>
                            <option value="PLN" <?php if ($cur == "PLN") echo "selected"; ?>>PLN</option>
                            <option value="GBP" <?php if ($cur == "GBP") echo "selected"; ?>>GBP</option>
                            <option value="SGD" <?php if ($cur == "SGD") echo "selected"; ?>>SGD</option>
                            <option value="SEK" <?php if ($cur == "SEK") echo "selected"; ?>>SEK</option>
                            <option value="CHF" <?php if ($cur == "CHF") echo "selected"; ?>>CHF</option>
                            <option value="TWD" <?php if ($cur == "TWD") echo "selected"; ?>>TWD</option>
                            <option value="THB" <?php if ($cur == "THB") echo "selected"; ?>>THB</option>
                            <option value="TRY" <?php if ($cur == "TRY") echo "selected"; ?>>TRY</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <div class="setting">
            <table class="">
                <tr>
                    <td width="50%">
                        Currency symbol
                    </td>
                    <td>
                        <input type="text" name="sub-currency-symbol" class="small" value="<?php echo $studio->getopt('sub-currency-symbol'); ?>">
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel settings">
        <h3>Disallowed tools</h3>

        <div class="setting">
            <table>
                <tr>
                    <td width="50%">
                        Show tools that are not included in the user's plan
                        <span>The user will be prompted to upgrade when clicking on a tool they cannot use.</span>
                    </td>
                    <td>
                        <div class="toggle">
                            <input type="hidden" name="sub-plans-show-disabled" value="<?php echo $studio->getopt('sub-plans-show-disabled'); ?>">
                            <div class="handle"></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel">
        <input type="submit" class="btn blue" value="Save">
    </div>
</form>
