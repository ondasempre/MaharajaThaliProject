<?php
    $settings = rw_settings();
?>
<div class="wrap rw-dir-ltr">
    <form id="rw_advanced_settings_form" method="post" action="">
        <div id="poststuff">
            <div id="rw_wp_set">
                <div id="rw_identify_by" class="has-sidebar has-right-sidebar">
                    <div class="has-sidebar-content">
                        <div class="postbox rw-body">
                            <h3><?php _e('Visitor Identification Method', WP_RW__ID) ?></h3>
                            <div class="inside rw-ui-content-container rw-no-radius" style="padding: 5px; width: 610px;">
                                <div class="rw-ui-img-radio rw-ui-hor<?php if ('laccount' === $settings->identify_by) echo ' rw-selected';?>">
                                    <input type="radio" name="rw_identify_by" value="laccount" <?php if ('laccount' === $settings->identify_by) echo ' checked="checked"';?>> <span><?php _e('Identify visitor by Cookie / Device.', WP_RW__ID) ?></span>
                                </div>
                                <div class="rw-ui-img-radio rw-ui-hor<?php if ('ip' === $settings->identify_by) echo ' rw-selected';?>"<?php if (!ratingwidget()->_eccbc87e4b5ce2fe28308fd9f2a7baf3()) : ?> data-alert="<?php _e('Visitor by IP identification is only supported in Professional plan and above.', WP_RW__ID) ?>"<?php endif ?>>
                                    <input type="radio" name="rw_identify_by" value="ip" <?php if ('ip' === $settings->identify_by) echo ' checked="checked"';?>> <span><?php _e('Identify visitor by IP / Location. <b>Especially for Voting Contests</b> (included in Professional Plan).', WP_RW__ID) ?></span>
                                </div>
                                <div class="rw-ui-img-radio rw-ui-hor<?php if ('account' === $settings->identify_by) echo ' rw-selected';?>" data-alert="<?php _e('Social connect is part of our upcoming Roadmap, but it\'s not ready yet. Appreciate your patience.', WP_RW__ID) ?>">
                                    <input type="radio" name="rw_identify_by" value="account" <?php if ('account' === $settings->identify_by) echo ' checked="checked"';?>> <span><?php _e('Require connect with Social identity like Facebook and Google connect (Coming soon...).', WP_RW__ID) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="rw_flash_settings" class="has-sidebar has-right-sidebar">
                    <div class="has-sidebar-content">
                        <div class="postbox rw-body">
                            <h3><?php _e('Flash Dependency', WP_RW__ID) ?></h3>
                            <div class="inside rw-ui-content-container rw-no-radius" style="padding: 5px; width: 610px;">
                                <div class="rw-ui-img-radio rw-ui-hor<?php if ($settings->flash_dependency) echo ' rw-selected';?>">
                                    <i class="rw-ui-sprite rw-ui-flash"></i> <input type="radio" name="rw_flash_dependency" value="true" <?php if ($settings->flash_dependency) echo ' checked="checked"';?>> <span><?php _e('Enable Flash dependency (track devices using LSO).', WP_RW__ID) ?></span>
                                </div>
                                <div class="rw-ui-img-radio rw-ui-hor<?php if (!$settings->flash_dependency) echo ' rw-selected';?>">
                                    <i class="rw-ui-sprite rw-ui-flash-disabled"></i> <input type="radio" name="rw_flash_dependency" value="false" <?php if (!$settings->flash_dependency) echo ' checked="checked"';?>> <span><?php _e('Disable Flash dependency (devices with identical IPs won\'t be distinguished).', WP_RW__ID) ?></span>
                                </div>
                                <span style="font-size: 10px; background: white; padding: 2px; border: 1px solid gray; display: block; margin-top: 5px; font-weight: bold; background: rgb(240,240,240); color: black;">Flash dependency <b style="text-decoration: underline;">don't</b> means that if a user don't have a flash player installed on his browser then it will stuck. The reason to disable flash is for users which have flash blocking add-ons (e.g. FF Flashblock add-on), which is quite rare.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="rw_mobile_settings" class="has-sidebar has-right-sidebar">
                    <div class="has-sidebar-content">
                        <div class="postbox rw-body">
                            <h3><?php _e('Mobile Settings', WP_RW__ID) ?></h3>
                            <div class="inside rw-ui-content-container rw-no-radius" style="padding: 5px; width: 610px;">
                                <div class="rw-ui-img-radio rw-ui-hor<?php if ($settings->show_on_mobile) echo ' rw-selected';?>">
                                    <i class="rw-ui-sprite rw-ui-mobile"></i> <input type="radio" name="rw_show_on_mobile" value="true" <?php if ($settings->show_on_mobile) echo ' checked="checked"';?>> <span>Show ratings on Mobile devices.</span>
                                </div>
                                <div class="rw-ui-img-radio rw-ui-hor<?php if (!$settings->show_on_mobile) echo ' rw-selected';?>">
                                    <i class="rw-ui-sprite rw-ui-mobile-disabled"></i> <input type="radio" name="rw_show_on_mobile" value="false" <?php if (!$settings->show_on_mobile) echo ' checked="checked"';?>> <span>Hide ratings on Mobile devices.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="rw_critical_actions" class="has-sidebar has-right-sidebar">
                    <div class="has-sidebar-content">
                        <div class="postbox rw-body">
                            <h3><?php _e('Critical Actions', WP_RW__ID) ?></h3>
                            <div class="inside rw-ui-content-container rw-no-radius">
                                <script type="text/javascript">
                                    (function($){
                                        if (typeof(RWM) === "undefined"){ RWM = {}; }
                                        if (typeof(RWM.Set) === "undefined"){ RWM.Set = {}; }
                                        
                                        RWM.Set.clearHistory = function(event)
                                        {
                                            if (confirm("Are you sure you want to delete all your ratings history?"))
                                            {
                                                $("#rw_delete_history").val("true");
                                                $("#rw_advanced_settings_form").submit(); 
                                            }
                                            
                                            event.stopPropagation();
                                        };
                                        
                                        RWM.Set.restoreDefaults = function(event)
                                        {
                                            if (confirm("Are you sure you want to restore to factory settings?"))
                                            {
                                                $("#rw_restore_defaults").val("true");
                                                $("#rw_advanced_settings_form").submit(); 
                                            }
                                            
                                            event.stopPropagation();
                                        };
                                        
                                        $(document).ready(function(){
                                            $("#rw_delete_history_con .rw-ui-button").click(RWM.Set.clearHistory);
                                            $("#rw_delete_history_con .rw-ui-button input").click(RWM.Set.clearHistory);

                                            $("#rw_restore_defaults_con .rw-ui-button").click(RWM.Set.restoreDefaults);
                                            $("#rw_restore_defaults_con .rw-ui-button input").click(RWM.Set.restoreDefaults);
                                        });
                                    })(jQuery);
                                </script>
                                <table cellspacing="0">
                                    <tr class="rw-odd" id="rw_restore_defaults_con">
                                        <td class="rw-ui-def">
                                            <input type="hidden" id="rw_restore_defaults" name="rw_restore_defaults" value="false" />
                                            <span class="rw-ui-button" onclick="RWM.firstUse();">
                                                <input type="button" style="background: none;" value="Restore to Defaults" onclick="RWM.firstUse();" />
                                            </span>
                                        </td>
                                        <td><span><?php _e('Restore all Rating-Widget settings to factory.', WP_RW__ID) ?></span></td>
                                    </tr>    
                                    <tr class="rw-even" id="rw_delete_history_con">
                                        <td>
                                            <input type="hidden" id="rw_delete_history" name="rw_delete_history" value="false" />
                                            <span class="rw-ui-button rw-ui-critical">
                                                <input type="button" style="background: none;" value="New Account (Delete History)" />
                                            </span>
                                        </td>
                                        <td><span><?php _e('Create new FREE ratings account.', WP_RW__ID) ?></span><br /><span><b style="color: red;"><?php _e('Notice: All your current ratings data will be lost.', WP_RW__ID) ?></b></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="rw_wp_set_widgets">
                <?php rw_require_once_view('save.php'); ?>
            </div>            
        </div>
    </form>
</div>
