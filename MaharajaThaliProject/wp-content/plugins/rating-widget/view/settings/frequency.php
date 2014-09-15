<?php
    $frequency = rw_options()->frequency;
?>
<div id="rw_frequency_settings" class="has-sidebar has-right-sidebar">
    <div class="has-sidebar-content">
        <div class="postbox rw-body">
            <h3>Voting Frequency Settings</h3>
            <div class="inside rw-ui-content-container rw-no-radius" style="padding: 5px; width: 610px;">
                <div class="rw-ui-img-radio rw-ui-hor<?php if ($frequency == -1) echo ' rw-selected';?>" onclick="RWM.Set.frequency(RW.FREQUENCY.ONCE);">
                    <input type="radio" name="rw_frequency" value="-1" <?php if ($frequency == -1) echo ' checked="checked"';?>> <span><b>Once</b> - user can vote only once per rating.</span>
                </div>
                <div class="rw-ui-img-radio rw-ui-hor<?php if ($frequency == 1) echo ' rw-selected';?>" onclick="RWM.Set.frequency(RW.FREQUENCY.DAILY);">
                    <input type="radio" name="rw_frequency" value="1" <?php if ($frequency == 1) echo ' checked="checked"';?>> <span><b>Daily</b> - user can vote once a day for every rating.</span>
                </div>
                <div class="rw-ui-img-radio rw-ui-hor<?php if ($frequency == 7) echo ' rw-selected';?>" onclick="RWM.Set.frequency(RW.FREQUENCY.WEEKLY);">
                    <input type="radio" name="rw_frequency" value="7" <?php if ($frequency == 7) echo ' checked="checked"';?>> <span><b>Weekly</b> - user can vote once a week (7 days) for every rating.</span>
                </div>
                <div class="rw-ui-img-radio rw-ui-hor<?php if ($frequency == 30) echo ' rw-selected';?>" onclick="RWM.Set.frequency(RW.FREQUENCY.MONTHLY);">
                    <input type="radio" name="rw_frequency" value="30" <?php if ($frequency == 30) echo ' checked="checked"';?>> <span><b>Monthly</b> - user can vote once a month (30 days) for every rating.</span>
                </div>
                <div class="rw-ui-img-radio rw-ui-hor<?php if ($frequency == 365) echo ' rw-selected';?>" onclick="RWM.Set.frequency(RW.FREQUENCY.YEARLY);">
                    <input type="radio" name="rw_frequency" value="365" <?php if ($frequency == 365) echo ' checked="checked"';?>> <span><b>Annually</b> - user can vote once a year (365 days) for every rating.</span>
                </div>
                <div class="rw-ui-img-radio rw-ui-hor<?php if ($frequency == 0) echo ' rw-selected';?>" onclick="RWM.Set.frequency(RW.FREQUENCY.UNLIMITED);">
                    <input type="radio" name="rw_frequency" value="0" <?php if ($frequency == 0) echo ' checked="checked"';?>> <span><b>Unlimited</b> - user can vote as many times as he likes.</span>
                </div>
            </div>
        </div>
    </div>
</div>
