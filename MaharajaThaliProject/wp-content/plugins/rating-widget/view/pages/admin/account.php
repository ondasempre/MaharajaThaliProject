<?php
    $settings = rw_settings();
?>
<div class="wrap rw-dir-ltr">
    <h2 class="nav-tab-wrapper rw-nav-tab-wrapper">
        <a href="<?php rw_admin_plugin_url('account') ?>" class="nav-tab nav-tab-active"><?php _e('Account', WP_RW__ID);?></a>
    <?php if (!ratingwidget()->_c4ca4238a0b923820dcc509a6f75849b()) : ?>
        <a href="<?php echo ratingwidget()->GetUpgradeUrl() ?>" class="nav-tab" target="_blank"><?php _e('Upgrade', WP_RW__ID);?></a>
    <?php endif ?>
    </h2>
    <div id="poststuff">
        <div id="rw_wp_set">
            <div class="has-sidebar has-right-sidebar">
                <div class="has-sidebar-content">
                    <div class="postbox rw-body">
                        <h3><?php _e('Account Details', WP_RW__ID) ?></h3>
                        <div class="inside rw-ui-content-container rw-no-radius">
                            <table cellspacing="0">
                            <?php
                                $profile = array();
                                if (WP_RW__OWNER_ID && is_numeric(WP_RW__OWNER_ID))
                                    $profile[] = array('id' => 'user_id', 'title' => __('User ID', WP_RW__ID), 'value' => WP_RW__OWNER_ID);
                                    
                                if (WP_RW__OWNER_EMAIL && strpos(WP_RW__OWNER_EMAIL, '@'))
                                    $profile[] = array('id' => 'email', 'title' => __('User Email', WP_RW__ID), 'value' => WP_RW__OWNER_EMAIL);
                                    
                                if (WP_RW__SITE_ID && is_numeric(WP_RW__SITE_ID))
                                    $profile[] = array('id' => 'site_id', 'title' => __('Site ID', WP_RW__ID), 'value' => WP_RW__SITE_ID);
                                    
                                $profile[] = array('id' => 'public', 'title' => __('Site Public Key', WP_RW__ID), 'value' => WP_RW__SITE_PUBLIC_KEY);

                                $profile[] = array('id' => 'secret', 'title' => __('Site Secret', WP_RW__ID), 'value' => ((WP_RW__SITE_SECRET_KEY && '' !== WP_RW__SITE_SECRET_KEY) ? WP_RW__SITE_SECRET_KEY : __('No Secret', WP_RW__ID)));

                                $profile[] = array('id' => 'plan', 'title' => __('Site Plan', WP_RW__ID), 'value' => is_string(WP_RW__SITE_PLAN) ? strtoupper(WP_RW__SITE_PLAN) : 'FREE');
                            ?>
                            <?php $odd = true; foreach ($profile as $p) : ?>
                                <tr class="rw-<?php echo $odd ? 'odd' : 'even' ?>">
                                    <td class="rw-ui-def">
                                        <span><?php echo $p['title'] ?>:</span>
                                    </td>
                                    <td><span style="font-size: 14px; color: green;"><?php echo htmlspecialchars($p['value']) ?></span></td>
                                    <td style="text-align: right; width: 210px;">
                                <?php if ('plan' === $p['id']) : ?>
                                    <?php if (!ratingwidget()->_c4ca4238a0b923820dcc509a6f75849b()) : ?>
                                        <a href="<?php echo ratingwidget()->GetUpgradeUrl() ?>" onclick="_gaq.push(['_trackEvent', 'upgrade', 'wordpress', 'gopro_button', 1, true]); _gaq.push(['_link', this.href]); return false;" class="button-secondary gradient rw-upgrade-button" target="_blank" style="float: right; margin-left: 5px;"><?php _e('Upgrade', WP_RW__ID) ?></a> 
                                    <?php else : ?>
                                        <a href="<?php echo ratingwidget()->GetUpgradeUrl() ?>" onclick="_gaq.push(['_trackEvent', 'change-plan', 'wordpress', 'account', 1, true]); _gaq.push(['_link', this.href]); return false;" class="button-secondary gradient rw-upgrade-button" target="_blank" style="float: right; margin-left: 5px;"><?php _e('Change Plan', WP_RW__ID) ?></a> 
                                    <?php endif; ?>
                                        <form action="" method="POST">
                                            <input type="hidden" name="rw_action" value="sync_license">
                                            <?php wp_nonce_field('sync_license') ?>
                                            <input type="submit" class="button" value="<?php _e('Sync License', WP_RW__ID) ?>" style="float: right;">
                                        </form>
                                <?php elseif ('secret' === $p['id']) : ?>
                                        <form action="" method="POST" onsubmit="var secret = prompt('<?php _e('What is your secret key?', WP_RW__ID) ?>'); if (null == secret || '' === secret) return false; jQuery('input[name=rw_secret]').val(secret); return true;">
                                            <input type="hidden" name="rw_action" value="update_secret">
                                            <input type="hidden" name="rw_secret" value="">
                                            <?php wp_nonce_field('update_secret') ?>
                                            <input type="submit" class="button" value="<?php _e('Update Secret', WP_RW__ID) ?>">
                                        </form>
                                <?php endif;?>
                                    </td>
                                </tr>
                            <?php $odd = !$odd; endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
