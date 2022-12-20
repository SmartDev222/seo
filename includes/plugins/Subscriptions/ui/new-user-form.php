<p>Plan (overrides group)</p>
<select name="plan" class="fancy">
    <?php
    foreach ($plans as $plan)
    echo "<option value=\"" . $plan['assign'] . "\">" . $plan['name'] . "</option>";
    ?>
</select>
