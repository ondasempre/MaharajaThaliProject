<?php
$result = '';

if(is_admin()) {
	wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css', '', '4.0.1', false);
}

if ($_POST) {
	if (isset($_POST['button-id']) && isset($_POST['bulk-action-select'])) {
		if ($_POST['bulk-action-select'] == 'trash') {
			$count = 0;
			
			foreach ($_POST['button-id'] as $id) {
				maxbuttons_button_move_to_trash($id);
				$count++;
			}
			
			if ($count == 1) {
				$result = __('Moved 1 button to the trash.', 'maxbuttons');
			}
			
			if ($count > 1) {
				$result = __('Moved ', 'maxbuttons') . $count . __(' buttons to the trash.', 'maxbuttons');
			}
		}
	}
}

if (isset($_GET['message']) && $_GET['message'] == '1') {
	$result = __('Moved 1 button to the trash.', 'maxbuttons');
}

$published_buttons = maxbuttons_get_published_buttons();
$published_buttons_count = maxbuttons_get_published_buttons_count();
$trashed_buttons_count = maxbuttons_get_trashed_buttons_count();
?>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#bulk-action-all").click(function() {
			jQuery("#maxbuttons input[name='button-id[]']").each(function() {
				if (jQuery("#bulk-action-all").is(":checked")) {
					jQuery(this).attr("checked", "checked");
				}
				else {
					jQuery(this).removeAttr("checked");
				}
			});
		});
		
		<?php if ($result != '') { ?>
			jQuery("#maxbuttons .message").show();
		<?php } ?>
	});
</script>

<div id="maxbuttons">
	<div class="wrap">
		<div class="icon32">
			<a href="http://maxbuttons.com" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/mb-32.png" alt="MaxButtons" /></a>
		</div>
		
		<h2 class="title"><?php _e('MaxButtons: Button List', 'maxbuttons') ?></h2>
		
		<div class="logo">
			<?php _e('Brought to you by', 'maxbuttons') ?>
			<a href="http://maxfoundry.com/products/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/max-foundry.png" alt="Max Foundry" /></a>
			<?php printf(__('Upgrade to MaxButtons Pro today! %sClick Here%s', 'maxbuttons'), '<a href="http://www.maxbuttons.com/pricing/?utm_source=wordpress&utm_medium=mbrepo&utm_content=button-list-upgrade&utm_campaign=plugin">', '</a>' ) ?>
		</div>
		
		<div class="clear"></div>
		<div class="main">
			<h2 class="tabs">
				<span class="spacer"></span>
				<a class="nav-tab nav-tab-active" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-controller&action=list"><?php _e('Buttons', 'maxbuttons') ?></a>
				<a class="nav-tab" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-pro"><?php _e('Go Pro', 'maxbuttons') ?></a>
				<?php if(current_user_can('manage_options')) { ?>
				<a class="nav-tab" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-settings"><?php _e('Settings', 'maxbuttons') ?></a>
				<a class="nav-tab" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-support"><?php _e('Support', 'maxbuttons') ?></a>
				<?php } ?>
			</h2>

			<div class="form-actions">
				<a class="button-primary" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-controller&action=button"><?php _e('Add New', 'maxbuttons') ?></a>
			</div>

			<?php if ($result != '') { ?>
				<div class="message"><?php echo $result ?></div>
			<?php } ?>
			
			<p class="status">
				<strong><?php _e('All', 'maxbuttons') ?></strong> <span class="count">(<?php echo $published_buttons_count ?>)</span>

				<?php if ($trashed_buttons_count > 0) { ?>
					<span class="separator">|</span>
					<a href="<?php echo admin_url() ?>admin.php?page=maxbuttons-controller&action=list&status=trash"><?php _e('Trash', 'maxbuttons') ?></a> <span class="count">(<?php echo $trashed_buttons_count ?>)</span>
				<?php } ?>
			</p>
			
			<form method="post">
				<select name="bulk-action-select" id="bulk-action-select">
					<option value=""><?php _e('Bulk Actions', 'maxbuttons') ?></option>
					<option value="trash"><?php _e('Move to Trash', 'maxbuttons') ?></option>
				</select>
				<input type="submit" class="button" value="<?php _e('Apply', 'maxbuttons') ?>" />
			
				<div class="button-list">		
					<table cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<th><input type="checkbox" name="bulk-action-all" id="bulk-action-all" /></th>
							<th><?php _e('Button', 'maxbuttons') ?></th>
							<th><?php _e('Name and Description', 'maxbuttons') ?></th>
							<th><?php _e('Shortcode', 'maxbuttons') ?></th>
							<th><?php _e('Actions', 'maxbuttons') ?></th>
						</tr>
						<?php foreach ($published_buttons as $b) { ?>
							<tr>
								<td valign="center">
									<input type="checkbox" name="button-id[]" id="button-id-<?php echo $b->id ?>" value="<?php echo $b->id ?>" />
								</td>
								<td>
									<div class="shortcode-container">
										<?php echo do_shortcode('[maxbutton id="' . $b->id . '" externalcss="false" ignorecontainer="true"]') ?>
									</div>
								</td>
								<td>
									<a class="button-name" href="<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=button&id=<?php echo $b->id ?>"><?php echo $b->name ?></a>
									<br />
									<p><?php echo $b->description ?></p>
								</td>
								<td>
									[maxbutton id="<?php echo $b->id ?>"]
								</td>
								<td>
									<a href="<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=button&id=<?php echo $b->id ?>"><?php _e('Edit', 'maxbuttons') ?></a>
									<span class="separator">|</span>
									<a href="<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=copy&id=<?php echo $b->id ?>"><?php _e('Copy', 'maxbuttons') ?></a>
									<span class="separator">|</span>
									<a href="<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=trash&id=<?php echo $b->id ?>"><?php _e('Move to Trash', 'maxbuttons') ?></a>
								</td>
							</tr>
						<?php } ?>
					</table>
				</div>
			</form>
		</div>
	</div>
	<div class="ad-wrap">
        <div class="ads">
            <h3><?php _e('Get MaxButtons Pro for $19', 'maxbuttons'); ?></h3>
            <p><?php _e('Do so much more with MB Pro.  Get 2 free buttons packs when you buy.  Just use MBFREE at checkout.', 'maxbuttons'); ?></p>
            <p><strong><?php _e('Some extra features for going Pro:', 'maxbuttons'); ?></strong></p>
            <ul>
                <li><?php _e('Great Support', 'maxbuttons'); ?></li>
                <li><?php _e('Pre-Made Button Packs', 'maxbuttons'); ?></li>
                <li><?php _e('Two Lines of Editable Text', 'maxbuttons'); ?></li>
                <li><?php _e('Add An Icon To Your Buttons', 'maxbuttons'); ?></li>
                <li><?php _e('Google Web Fonts', 'maxbuttons'); ?></li>
                <li><?php _e('Many more benefits!', 'maxbuttons'); ?></li>
            </ul>
            <a class="button-primary" href="http://www.maxbuttons.com/pricing/?utm_source=wordpress&utm_medium=mbrepo&utm_content=button-list-sidebar-19&utm_campaign=plugin"><?php _e('Get MaxButtons Pro Now!', 'maxbuttons'); ?></a>
        </div>
        <div class="ads">
            <h3><i class="fa fa-cogs"></i> <?php _e('Font Awesome Support', 'maxbuttons'); ?></h3>
            <p><?php _e('With MaxButtons Pro you have access to all 439 Font Awesome icons, ready to add to your buttons.', 'maxbuttons'); ?></p>
            <p><?php _e('Never upload another icon again, just choose an icon and go about your normal button-making business.', 'maxbuttons'); ?></p>
            <a class="button-primary" href="http://www.maxbuttons.com/pricing/?utm_source=wordpress&utm_medium=mbrepo&utm_content=button-list-sidebar-99&utm_campaign=plugin"><?php _e('Use Font Awesome!', 'maxbuttons'); ?> <i class="fa fa-arrow-circle-right"></i></a>
        </div>
	</div>

</div>
