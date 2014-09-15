<?php
add_shortcode('maxbutton', 'maxbuttons_button_shortcode');
function maxbuttons_button_shortcode($atts) {
	extract(shortcode_atts(array(
		'id' => '',
		'text' => '',
		'url' => '',
		'window' => '',
		'nofollow' => '',
		'externalcss' => '',
		'externalcsspreview' => '',		// Only used in maxbuttons-button-css.php
		'ignorecontainer' => '',		// Internal use only on button list pages and the TinyMCE dialog
		'exclude' => ''
	), $atts));
	
	$button_id = "{$id}";
	
	if ($button_id != '') {
		$button = maxbuttons_get_button($button_id);
		
		if (isset($button)) {
			// If we're not in the admin and the button is in the trash, just return nothing
			if (!is_admin() && $button->status == 'trash') {
				return '';
			}
			
			// Check to handle excludes
			if ("{$exclude}" != '') {
				global $post;
				
				// Don't render the button if excluded from the current post/page
				$exclude = explode(',', "{$exclude}");
				if (in_array($post->ID, $exclude)) {
					return '';
				}
			}
			
			if ($button->gradient_stop != '') {
				$gradient_stop = strlen($button->gradient_stop) == 1 ? '0' . $button->gradient_stop : $button->gradient_stop;
			} else {
				$gradient_stop = '45'; // Default
			}
			
			$external_css = false;
			if ("{$externalcss}" != '') {
				if ("{$externalcss}" == 'true') {
					$external_css = true;
				}
			} else {
				if ($button->external_css == 'on') {
					$external_css = true;
				}
			}
			
			// Check to exit early with external css preview
			$external_css_preview = false;
			if ("{$externalcsspreview}" != '') {
				if ("{$externalcsspreview}" == 'true') {
					$external_css_preview = true;
				}
			}
			
			// Check to ignore container
			$ignore_container = false;
			if ("{$ignorecontainer}" != '') {
				if ("{$ignorecontainer}" == 'true') {
					$ignore_container = true;
				}
			}
			
			// Check to set !important
			$important = '';
			if ($button->important_css == 'on') {
				$important = ' !important';
			}
			
			// Initialize the css
			$css = '';
			
			if (!$external_css && !$external_css_preview) {
				// Add the opening <style> tag
				$css .= '<style type="text/css">';
			}
			
			// The container style
			if ($button->container_enabled == 'on') {
				$css .= 'div.maxbutton-' . $button->id . '-container { ';

				if ($button->container_alignment != '') {
					$css .= $button->container_alignment . $important . '; ';
				}
				
				if ($button->container_width != '') {
					$css .= 'width: ' . $button->container_width . $important . '; ';
				}
				
				if ($button->container_margin_top != '') {
					$css .= 'margin-top: ' . $button->container_margin_top . $important . '; ';
				}

				if ($button->container_margin_right != '') {
					$css .= 'margin-right: ' . $button->container_margin_right . $important . '; ';
				}
				
				if ($button->container_margin_bottom != '') {
					$css .= 'margin-bottom: ' . $button->container_margin_bottom . $important . '; ';
				}
				
				if ($button->container_margin_left != '') {
					$css .= 'margin-left: ' . $button->container_margin_left . $important . '; ';
				}
				
				$css .= '} ';
			}

			$button_url = "{$url}" != '' ? "{$url}" : $button->url;

			// Gradients
			$gradient_start_color = maxbuttons_hex2rgba($button->gradient_start_color, $button->gradient_start_opacity);
			$gradient_end_color = maxbuttons_hex2rgba($button->gradient_end_color, $button->gradient_end_opacity);
			
			// The button style
			$css .= 'a.maxbutton-' . $button->id . ' { ';
			$css .= 'text-decoration: none' . $important . '; ';
			$css .= 'color: ' . $button->text_color . $important . '; ';
			$css .= 'font-family: ' . $button->text_font_family . $important . '; ';
			$css .= 'font-size: ' . $button->text_font_size . $important . '; ';
			$css .= 'font-style: ' . $button->text_font_style . $important . '; ';
			$css .= 'font-weight: ' . $button->text_font_weight . $important . '; ';
			$css .= 'padding-top: ' . $button->text_padding_top . $important . '; ';
			$css .= 'padding-right: ' . $button->text_padding_right . $important . '; ';
			$css .= 'padding-bottom: ' . $button->text_padding_bottom . $important . '; ';
			$css .= 'padding-left: ' . $button->text_padding_left . $important . '; ';
			$css .= 'background-color: ' . $button->gradient_start_color . $important . '; ';
			$css .= 'background: linear-gradient(' . $gradient_start_color . ' ' . $gradient_stop . '%, ' . $gradient_end_color . '); ';
			$css .= 'background: -moz-linear-gradient(' . $gradient_start_color . ' ' . $gradient_stop . '%, ' . $gradient_end_color . '); ';
			$css .= 'background: -o-linear-gradient(' . $gradient_start_color . ' ' . $gradient_stop . '%, ' . $gradient_end_color . '); ';
			$css .= 'background: -webkit-gradient(linear, left top, left bottom, color-stop(.' . $gradient_stop . ', ' . $gradient_start_color . '), color-stop(1, ' . $gradient_end_color . ')); ';
			$css .= 'border-style: ' . $button->border_style . $important . '; ';
			$css .= 'border-width: ' . $button->border_width . $important . '; ';
			$css .= 'border-color: ' . $button->border_color . $important . '; ';
			$css .= 'box-sizing: border-box' . $important . '; ';
		
			if (maxbuttons_border_radius_values_are_equal($button->border_radius_top_left, $button->border_radius_top_right, $button->border_radius_bottom_left, $button->border_radius_bottom_right)) {
				$css .= 'border-radius: ' . $button->border_radius_top_left . $important . '; ';
				$css .= '-moz-border-radius: ' . $button->border_radius_top_left . $important . '; ';
				$css .= '-webkit-border-radius: ' . $button->border_radius_top_left . $important . '; ';
			}
			else {
				$css .= 'border-top-left-radius: ' . $button->border_radius_top_left . $important . '; ';
				$css .= 'border-top-right-radius: ' . $button->border_radius_top_right . $important . '; ';
				$css .= 'border-bottom-left-radius: ' . $button->border_radius_bottom_left . $important . '; ';
				$css .= 'border-bottom-right-radius: ' . $button->border_radius_bottom_right . $important . '; ';
				$css .= '-moz-border-radius-topleft: ' . $button->border_radius_top_left . $important . '; ';
				$css .= '-moz-border-radius-topright: ' . $button->border_radius_top_right . $important . '; ';
				$css .= '-moz-border-radius-bottomleft: ' . $button->border_radius_bottom_left . $important . '; ';
				$css .= '-moz-border-radius-bottomright: ' . $button->border_radius_bottom_right . $important . '; ';
				$css .= '-webkit-border-top-left-radius: ' . $button->border_radius_top_left . $important . '; ';
				$css .= '-webkit-border-top-right-radius: ' . $button->border_radius_top_right . $important . '; ';
				$css .= '-webkit-border-bottom-left-radius: ' . $button->border_radius_bottom_left . $important . '; ';
				$css .= '-webkit-border-bottom-right-radius: ' . $button->border_radius_bottom_right . $important . '; ';
			}
		
			$css .= 'text-shadow: ' . $button->text_shadow_offset_left . ' ' . $button->text_shadow_offset_top . ' ' . $button->text_shadow_width . ' ' . $button->text_shadow_color . $important . '; ';
			$css .= 'box-shadow: ' . $button->box_shadow_offset_left . ' ' . $button->box_shadow_offset_top . ' ' . $button->box_shadow_width . ' ' . $button->box_shadow_color . $important . '; ';
			
			// PIE
			$css .= '-pie-background: linear-gradient(' . $gradient_start_color . ' ' . $gradient_stop . '%, ' . $gradient_end_color . '); ';
			$css .= 'position: relative' . $important . '; ' ;
			$css .= 'behavior: url("' . MAXBUTTONS_PLUGIN_URL . '/pie/PIE.htc"); ';
			$css .= '} ';
			
			// The button style - visited
			$css .= 'a.maxbutton-' . $button->id . ':visited { ';
			$css .= 'text-decoration: none' . $important . '; ';
			$css .= 'color: ' . $button->text_color . $important . '; ';
			$css .= '} ';
		
			if ($button_url != '') {
				// Hover gradients
				$gradient_start_color_hover = maxbuttons_hex2rgba($button->gradient_start_color_hover, $button->gradient_start_opacity_hover);
				$gradient_end_color_hover = maxbuttons_hex2rgba($button->gradient_end_color_hover, $button->gradient_end_opacity_hover);

				// The button style - hover
				$css .= 'a.maxbutton-' . $button->id . ':hover { ';
				$css .= 'text-decoration: none' . $important . '; ';
				$css .= 'color: ' . $button->text_color_hover . $important . '; ';
				$css .= 'background-color: ' . $button->gradient_start_color_hover . $important . '; ';
				$css .= 'background: linear-gradient(' . $gradient_start_color_hover . ' ' . $gradient_stop . '%, ' . $gradient_end_color_hover . '); ';
				$css .= 'background: -moz-linear-gradient(' . $gradient_start_color_hover . ' ' . $gradient_stop . '%, ' . $gradient_end_color_hover . '); ';
				$css .= 'background: -o-linear-gradient(' . $gradient_start_color_hover . ' ' . $gradient_stop . '%, ' . $gradient_end_color_hover . '); ';
				$css .= 'background: -webkit-gradient(linear, left top, left bottom, color-stop(.' . $gradient_stop . ', ' . $gradient_start_color_hover . '), color-stop(1, ' . $gradient_end_color_hover . ')); ';
				$css .= 'border-color: ' . $button->border_color_hover . $important . '; ';
				$css .= 'text-shadow: ' . $button->text_shadow_offset_left . ' ' . $button->text_shadow_offset_top . ' ' . $button->text_shadow_width . ' ' . $button->text_shadow_color_hover . $important . '; ';
				$css .= 'box-shadow: ' . $button->box_shadow_offset_left . ' ' . $button->box_shadow_offset_top . ' ' . $button->box_shadow_width . ' ' . $button->box_shadow_color_hover . $important . '; ';
				
				// PIE
				$css .= '-pie-background: linear-gradient(' . $gradient_start_color_hover . ' ' . $gradient_stop . '%, ' . $gradient_end_color_hover . '); ';
				$css .= 'position: relative' . $important . '; ' ;
				$css .= 'behavior: url("' . MAXBUTTONS_PLUGIN_URL . '/pie/PIE.htc"); ';
				$css .= '}';
			}
			
			if (!$external_css && !$external_css_preview) {
				// Close the style element
				$css .= '</style>';
			}
			
			if ($external_css_preview) {
				return $css;
			}
			
			$button_text = "{$text}" != '' ? "{$text}" : $button->text;
			$button_window = '';
			$button_nofollow = '';
			
			// Check to open the link in a new window
			if ("{$window}" != '') {
				if ("{$window}" == 'new') {
					$button_window = 'target="_blank"';
				}
			} else {
				if ($button->new_window == 'on') {
					$button_window = 'target="_blank"';
				}
			}
			
			// Check to add rel="nofollow" to the link
			if ("{$nofollow}" != '') {
				if ("{$nofollow}" == 'true') {
					$button_nofollow = 'rel="nofollow"';
				}
			} else {
				if ($button->nofollow == 'on') {
					$button_nofollow = 'rel="nofollow"';
				}
			}
			
			// Initialize the output
			$output = '';
			
			// Check to add the css
			if (!$external_css) {
				$output .= $css;
			}
			
			if (!$ignore_container) {
				// Check to add the center div wrapper
				if ($button->container_center_div_wrap_enabled == 'on') {				
					$output .= '<div align="center">';
				}
				
				// Check to add the container
				if ($button->container_enabled == 'on') {				
					$output .= '<div class="maxbutton-' . $button->id . '-container">';
				}
			}
			
			// If no button url then don't output the href
			if ($button_url == '') {
				$output .= '<a class="maxbutton-' . $button->id . '">' . $button_text . '</a>';
			} else {
				$output .= '<a class="maxbutton-' . $button->id . '" href="' . $button_url . '" ' . $button_window . ' ' . $button_nofollow . '>' . $button_text . '</a>';
			}
			
			if (!$ignore_container) {
				// Check to close the container
				if ($button->container_enabled == 'on') {
					$output .= '</div>';
					
					// Might need to clear the float
					if ($button->container_alignment == 'float: right' || $button->container_alignment == 'float: left') {
						$output .= '<div style="clear: both;"></div>';
					}
				}
				
				// Check to close the center div wrapper
				if ($button->container_center_div_wrap_enabled == 'on') {				
					$output .= '</div>';
				}
			}
			
			return $output;
		}
	}
}

function maxbuttons_border_radius_values_are_equal($top_left, $top_right, $bottom_left, $bottom_right) {
	if ($top_left == $top_right && $top_left == $bottom_left && $top_left == $bottom_right &&
		$top_right == $top_left && $top_right == $bottom_left && $top_right == $bottom_right &&
		$bottom_left == $top_left && $bottom_left == $top_right && $bottom_left == $bottom_right &&
		$bottom_right == $top_left && $bottom_right == $top_right && $bottom_right == $bottom_left) {
		return true;
	}
	
	// Otherwise
	return false;
}
?>