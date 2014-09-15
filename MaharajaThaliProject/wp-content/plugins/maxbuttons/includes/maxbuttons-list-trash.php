<?php
$result = '';

if ($_POST) {
	if (isset($_POST['button-id']) && isset($_POST['bulk-action-select'])) {
		if ($_POST['bulk-action-select'] == 'restore') {
			$count = 0;
			
			foreach ($_POST['button-id'] as $id) {
				maxbuttons_button_restore($id);
				$count++;
			}

			if ($count == 1) {
				$result = __('Restored 1 button.', 'maxbuttons');
			}
			
			if ($count > 1) {
				$result = __('Restored ', 'maxbuttons') . $count . __(' buttons.', 'maxbuttons');
			}
		}
		
		if ($_POST['bulk-action-select'] == 'delete') {
			$count = 0;
			
			foreach ($_POST['button-id'] as $id) {
				maxbuttons_button_delete_permanently($id);
				$count++;
			}

			if ($count == 1) {
				$result = __('Deleted 1 button.', 'maxbuttons');
			}
			
			if ($count > 1) {
				$result = __('Deleted ', 'maxbuttons') . $count . __(' buttons.', 'maxbuttons');
			}
		}
	}
}

if (isset($_GET['message']) && $_GET['message'] == '1restore') {
	$result = __('Restored 1 button.', 'maxbuttons');
}

if (isset($_GET['message']) && $_GET['message'] == '1delete') {
	$result = __('Deleted 1 button.', 'maxbuttons');
}

$trashed_buttons = maxbuttons_get_trashed_buttons();
$trashed_buttons_count = maxbuttons_get_trashed_buttons_count();
$published_buttons_count = maxbuttons_get_published_buttons_count();
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
			<a href="http://maxfoundry.com/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/max-foundry.png" alt="Max Foundry" /></a>
			<?php printf(__('makers of %sMaxGalleria%s and %sMaxInbound%s', 'maxbuttons'), '<a href="http://maxgalleria.com/?ref=mbfree" target="_blank">', '</a>', '<a href="http://maxinbound.com/?ref=mbfree" target="_blank">', '</a>') ?>
		</div>
		
		<div class="clear"></div>
		
		<h2 class="tabs">
			<span class="spacer"></span>
			<a class="nav-tab nav-tab-active" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-controller&action=list"><?php _e('Buttons', 'maxbuttons') ?></a>
			<a class="nav-tab" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-pro"><?php _e('Go Pro', 'maxbuttons') ?></a>
			<?php if(current_user_can('manage_options')) { ?>
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
			<a href="<?php echo admin_url() ?>admin.php?page=maxbuttons-controller&action=list"><?php _e('All', 'maxbuttons') ?></a> <span class="count">(<?php echo $published_buttons_count ?>)</span>
			<span class="separator">|</span>
			<strong><?php _e('Trash', 'maxbuttons') ?></strong> <span class="count">(<?php echo $trashed_buttons_count ?>)</span>
		</p>
		
		<form method="post">
			<select name="bulk-action-select" id="bulk-action-select">
				<option value=""><?php _e('Bulk Actions', 'maxbuttons') ?></option>
				<option value="restore"><?php _e('Restore', 'maxbuttons') ?></option>
				<option value="delete"><?php _e('Delete Permanently', 'maxbuttons') ?></option>
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
					<?php foreach ($trashed_buttons as $b) { ?>
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
								<strong><?php echo $b->name ?></strong>
								<br />
								<p><?php echo $b->description ?></p>
							</td>
							<td>
								[maxbutton id="<?php echo $b->id ?>"]
							</td>
							<td>
								<a href="<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=restore&id=<?php echo $b->id ?>"><?php _e('Restore', 'maxbuttons') ?></a>
								<span class="separator">|</span>
								<a href="<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=delete&id=<?php echo $b->id ?>"><?php _e('Delete Permanently', 'maxbuttons') ?></a>
							</td>
						</tr>
					<?php } ?>
				</table>
			</div>
		</form>
	</div>
</div>
