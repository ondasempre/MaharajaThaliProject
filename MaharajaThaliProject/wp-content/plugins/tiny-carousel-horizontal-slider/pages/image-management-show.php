<?php if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); } ?>
<?php
// Form submitted, check the data
if (isset($_POST['frm_tch_display']) && $_POST['frm_tch_display'] == 'yes')
{
	$did = isset($_GET['did']) ? $_GET['did'] : '0';
	
	$tch_success = '';
	$tch_success_msg = FALSE;
	
	// First check if ID exist with requested ID
	$sSql = $wpdb->prepare(
		"SELECT COUNT(*) AS `count` FROM ".TinyCarouselTable."
		WHERE `tch_id` = %d",
		array($did)
	);
	$result = '0';
	$result = $wpdb->get_var($sSql);
	
	if ($result != '1')
	{
		?><div class="error fade"><p><strong><?php _e('Oops, selected details doesnt exist', 'TinyCarousel'); ?></strong></p></div><?php
	}
	else
	{
		// Form submitted, check the action
		if (isset($_GET['ac']) && $_GET['ac'] == 'del' && isset($_GET['did']) && $_GET['did'] != '')
		{
			//	Just security thingy that wordpress offers us
			check_admin_referer('tch_form_show');
			
			//	Delete selected record from the table
			$sSql = $wpdb->prepare("DELETE FROM `".TinyCarouselTable."`
					WHERE `tch_id` = %d
					LIMIT 1", $did);
			$wpdb->query($sSql);
			
			//	Set success message
			$tch_success_msg = TRUE;
			$tch_success = __('Selected record was successfully deleted.', 'TinyCarousel');
		}
	}
	
	if ($tch_success_msg == TRUE)
	{
		?><div class="updated fade"><p><strong><?php echo $tch_success; ?></strong></p></div><?php
	}
}
?>
<div class="wrap">
  <div id="icon-edit" class="icon32 icon32-posts-post"></div>
    <h2><?php _e('Tiny carousel horizontal slider', 'TinyCarousel'); ?>
	<a class="add-new-h2" href="<?php echo TinyCarousel_ADMIN_URL; ?>&amp;ac=add"><?php _e('Add New', 'TinyCarousel'); ?></a></h2>
    <div class="tool-box">
	<?php
		$sSql = "SELECT * FROM `".TinyCarouselTable."` order by tch_id desc";
		$myData = array();
		$myData = $wpdb->get_results($sSql, ARRAY_A);
		?>
		<script language="JavaScript" src="<?php echo TinyCarousel_PLUGIN_URL; ?>/pages/setting.js"></script>
		<form name="frm_tch_display" method="post">
      <table width="100%" class="widefat" id="straymanage">
        <thead>
          <tr>
            <th class="check-column" scope="col"><input type="checkbox" name="tch_group_item[]" /></th>
			<th scope="col"><?php _e('Short code', 'TinyCarousel'); ?></th>
			<!--<th scope="col"><?php //_e('Slider width', 'TinyCarousel'); ?></th>-->
            <th scope="col"><?php _e('Image folder', 'TinyCarousel'); ?></th>
			<th scope="col"><?php _e('Image width', 'TinyCarousel'); ?></th>
			<th scope="col"><?php _e('Image height', 'TinyCarousel'); ?></th>
			<!--<th scope="col"><?php //_e('Display', 'TinyCarousel'); ?></th>-->
			<th scope="col"><?php _e('Controls', 'TinyCarousel'); ?></th>
          </tr>
        </thead>
		<tfoot>
          <tr>
            <th class="check-column" scope="col"><input type="checkbox" name="tch_group_item[]" /></th>
			<th scope="col"><?php _e('Short code', 'TinyCarousel'); ?></th>
			<!--<th scope="col"><?php //_e('Slider width', 'TinyCarousel'); ?></th>-->
            <th scope="col"><?php _e('Image folder', 'TinyCarousel'); ?></th>
			<th scope="col"><?php _e('Image width', 'TinyCarousel'); ?></th>
			<th scope="col"><?php _e('Image height', 'TinyCarousel'); ?></th>
			<!--<th scope="col"><?php //_e('Display', 'TinyCarousel'); ?></th>-->
			<th scope="col"><?php _e('Controls', 'TinyCarousel'); ?></th>
          </tr>
        </tfoot>
		<tbody>
			<?php 
			$i = 0;
			if(count($myData) > 0 )
			{
				foreach ($myData as $data)
				{
					?>
					<tr class="<?php if ($i&1) { echo'alternate'; } else { echo ''; }?>">
						<td align="left"><input type="checkbox" value="<?php echo $data['tch_id']; ?>" name="tch_group_item[]"></th>
						<td>[tiny-carousel-slider id="<?php echo $data['tch_id']; ?>"]
						<div class="row-actions">
							<span class="edit"><a title="Edit" href="<?php echo TinyCarousel_ADMIN_URL; ?>&amp;ac=edit&amp;did=<?php echo $data['tch_id']; ?>"><?php _e('Edit', 'TinyCarousel'); ?></a> | </span>
							<span class="trash"><a onClick="javascript:tch_delete('<?php echo $data['tch_id']; ?>')" href="javascript:void(0);"><?php _e('Delete', 'TinyCarousel'); ?></a></span> 
						</div>
						</td>
						<!--<td><?php //echo $data['tch_viewport']; ?></td>-->
						<td><?php echo $data['tch_folder']; ?></td>
						<td><?php echo $data['tch_width']; ?></td>
						<td><?php echo $data['tch_height']; ?></td>
						<!--<td><?php //echo $data['tch_display']; ?></td>-->
						<td><?php echo $data['tch_controls']; ?></td>
					</tr>
					<?php 
					$i = $i+1; 
				} 	
			}
			else
			{
				?><tr><td colspan="6" align="center"><?php _e('No records available.', 'TinyCarousel'); ?></td></tr><?php 
			}
			?>
		</tbody>
        </table>
		<?php wp_nonce_field('tch_form_show'); ?>
		<input type="hidden" name="frm_tch_display" value="yes"/>
      </form>	
	  <div class="tablenav">
	  <h2>
	  <a class="button add-new-h2" href="<?php echo TinyCarousel_ADMIN_URL; ?>&amp;ac=add"><?php _e('Add New', 'TinyCarousel'); ?></a>
	  <a class="button add-new-h2" target="_blank" href="<?php echo TinyCarousel_FAV; ?>"><?php _e('Help', 'TinyCarousel'); ?></a>
	  </h2>
	  </div>
	  <div style="height:5px"></div>
	<h3><?php _e('Plugin configuration option', 'TinyCarousel'); ?></h3>
	<ol>
		<li><?php _e('Add the plugin in the posts or pages using short code.', 'TinyCarousel'); ?></li>
		<li><?php _e('Add directly in to the theme using PHP code.', 'TinyCarousel'); ?></li>
	</ol>
	<p class="description">
	<?php _e('Check official website for more information', 'TinyCarousel'); ?>
	<a target="_blank" href="<?php echo TinyCarousel_FAV; ?>"><?php _e('click here', 'TinyCarousel'); ?></a><br />
	<?php _e('Dont upload your original images into plug-in folder. if you upload the images into plug-in folder, you may lose the images when you update the plug-in to next version.', 'TinyCarousel'); ?>
	</p>
	</div>
</div>