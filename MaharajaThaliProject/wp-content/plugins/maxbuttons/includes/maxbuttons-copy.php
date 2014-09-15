<?php
if (isset($_GET['id']) && $_GET['id'] != '') {
	$button = maxbuttons_get_button($_GET['id']);
	
	$data = array(
		'name' => $button->name,
		'description' => $button->description,
		'url' => $button->url,
		'new_window' => $button->new_window,
		'nofollow' => $button->nofollow,
		'text' => $button->text,
		'text_font_family' => $button->text_font_family,
		'text_font_size' => $button->text_font_size,
		'text_font_style' => $button->text_font_style,
		'text_font_weight' => $button->text_font_weight,
		'text_color' => $button->text_color,
		'text_color_hover' => $button->text_color_hover,
		'text_shadow_offset_left' => $button->text_shadow_offset_left,
		'text_shadow_offset_top' => $button->text_shadow_offset_top,
		'text_shadow_width' => $button->text_shadow_width,
		'text_shadow_color' => $button->text_shadow_color,
		'text_shadow_color_hover' => $button->text_shadow_color_hover,
		'text_padding_top' => $button->text_padding_top,
		'text_padding_bottom' => $button->text_padding_bottom,
		'text_padding_left' => $button->text_padding_left,
		'text_padding_right' => $button->text_padding_right,
		'border_radius_top_left' => $button->border_radius_top_left,
		'border_radius_top_right' => $button->border_radius_top_right,
		'border_radius_bottom_left' => $button->border_radius_bottom_left,
		'border_radius_bottom_right' => $button->border_radius_bottom_right,
		'border_style' => $button->border_style,
		'border_width' => $button->border_width,
		'border_color' => $button->border_color,
		'border_color_hover' => $button->border_color_hover,
		'box_shadow_offset_left' => $button->box_shadow_offset_left,
		'box_shadow_offset_top' => $button->box_shadow_offset_top,
		'box_shadow_width' => $button->box_shadow_width,
		'box_shadow_color' => $button->box_shadow_color,
		'box_shadow_color_hover' => $button->box_shadow_color_hover,
		'gradient_start_color' => $button->gradient_start_color,
		'gradient_start_color_hover' => $button->gradient_start_color_hover,
		'gradient_end_color' => $button->gradient_end_color,
		'gradient_end_color_hover' => $button->gradient_end_color_hover,
		'gradient_start_opacity' => $button->gradient_start_opacity,
		'gradient_end_opacity' => $button->gradient_end_opacity,
		'gradient_start_opacity_hover' => $button->gradient_start_opacity_hover,
		'gradient_end_opacity_hover' => $button->gradient_end_opacity_hover,
		'gradient_stop' => $button->gradient_stop,
		'container_enabled' => $button->container_enabled,
		'container_width' => $button->container_width,
		'container_margin_top' => $button->container_margin_top,
		'container_margin_right' => $button->container_margin_right,
		'container_margin_bottom' => $button->container_margin_bottom,
		'container_margin_left' => $button->container_margin_left,
		'container_alignment' => $button->container_alignment,
		'container_center_div_wrap_enabled' => $button->container_center_div_wrap_enabled,
		'status' => $button->status
	);

	global $wpdb;
	$wpdb->insert(maxbuttons_get_buttons_table_name(), $data);
	$button_id = $wpdb->insert_id;
}
?>
<script type="text/javascript">
	<?php if (isset($_GET['id']) && $_GET['id'] != '') { ?>
		window.location = "<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=button&id=<?php echo $button_id ?>";
	<?php } else { ?>
		window.location = "<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=list";
	<?php } ?>
</script>
