<?php
global $pagenow;
?>

<?php // Only run in post/page creation and edit screens ?>
<?php if (in_array($pagenow, array('post.php', 'page.php', 'post-new.php', 'post-edit.php'))) { ?>
	<?php $published_buttons = maxbuttons_get_published_buttons(); ?>
	
	<script type="text/javascript">
		function insertButtonShortcode(button_id) {
			if (button_id == "") {
				alert("<?php _e('Please select a button.', 'maxbuttons') ?>");
				return;
			}
			
			// Send shortcode to the editor
			window.send_to_editor('[maxbutton id="' + button_id + '"]');
		}
	</script>
	
	<div id="select-maxbutton-container" style="display: none;">
		<div class="wrap">
			<h2 style="padding-top: 3px; padding-left: 40px; background: url(<?php echo MAXBUTTONS_PLUGIN_URL . '/images/mb-32.png' ?>) no-repeat;">
				<?php _e('Insert Button into Editor', 'maxbuttons') ?>
			</h2>

			<p><?php _e('Select a button from the list below to place the button shortcode in the editor.', 'maxbuttons') ?></p>
			
			<table cellpadding="5" cellspacing="5" width="100%">
			<?php foreach ($published_buttons as $button) { ?>
				<tr>
					<td>
						<a href="#" onclick="insertButtonShortcode(<?php echo $button->id ?>); return false;"><?php _e('Insert This Button', 'maxbuttons') ?></a> <span class="raquo">&raquo;</span>
					</td>
					<td style="padding: 10px 0px 10px 0px;">
						<?php echo do_shortcode('[maxbutton id="' . $button->id . '" externalcss="false" ignorecontainer="true"]') ?>
					</td>
				</tr>
			<?php } ?>
			</table>

			<a class="button-secondary" style="margin-left: 10px; margin-top: 10px;" onclick="tb_remove();"><?php _e('Cancel', 'maxbuttons') ?></a>
		</div>
	</div>
<?php } ?>