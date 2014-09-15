<?php 

function wppp_render_paypal_button_with_other_amt($args)
{
	extract( shortcode_atts( array(
		'email' => '',
		'description' => '',	
		'currency' => 'USD',
		'reference' => '',	
		'return' => site_url(),
		'country_code' => '',
		'button_image' => '',
                'button_text' => '',
		'cancel_url' => '',
                'new_window' => '',
                'tax' => '',
	), $args));	
	
        $email = apply_filters('wppp_widget_any_amt_email', $email);

	$output = "";
	$payment_button_img_src = get_option('payment_button_type');
	if(!empty($button_image)){
		$payment_button_img_src = $button_image;
	}

	if(empty($email)){
		$output = '<p style="color: red;">Error! Please enter your PayPal email address for the payment using the "email" parameter in the shortcode</p>';
		return $output;
	}
		
	if(empty($description)){
		$output = '<p style="color: red;">Error! Please enter a description for the payment using the "description" parameter in the shortcode</p>';
		return $output;
	}

        $window_target = '';
        if(!empty($new_window)){
            $window_target = 'target="_blank"';
        }
	$output .= '<div class="wp_paypal_button_widget_any_amt">';
	$output .= '<form name="_xclick" class="wp_accept_pp_button_form_any_amount" action="https://www.paypal.com/cgi-bin/webscr" method="post" '.$window_target.'>';

	$output .= '<div class="wp_pp_button_amount_section">Amount: <input type="text" name="amount" value="" size="5"> '.$currency.'</div>';

	if(!empty($reference)){
		$output .= '<div class="wp_pp_button_reference_section">';
		$output .= '<label for="wp_pp_button_reference">'.$reference.'</label>';
		$output .= '<br />';
		$output .= '<input type="hidden" name="on0" value="Reference" />';
		$output .= '<input type="text" name="os0" value="" class="wp_pp_button_reference" />';
		$output .= '</div>';
	}
			
	$output .= '<input type="hidden" name="cmd" value="_xclick">';
	$output .= '<input type="hidden" name="business" value="'.$email.'">';
	$output .= '<input type="hidden" name="currency_code" value="'.$currency.'">';
	$output .= '<input type="hidden" name="item_name" value="'.stripslashes($description).'">';
	$output .= '<input type="hidden" name="return" value="'.$return.'" />';
        if(is_numeric($tax)){
            $output .= '<input type="hidden" name="tax" value="'.$tax.'" />';
        }
	if(!empty($cancel_url)){
		$output .= '<input type="hidden" name="cancel_return" value="'.$cancel_url.'" />';
	}
	if(!empty($country_code)){
		$output .= '<input type="hidden" name="lc" value="'.$country_code.'" />';
	}

	$output .= '<div class="wp_pp_button_submit_btn">';
        if(!empty($button_text)){//Use text button
            $button_class = apply_filters('wppp_text_button_class','');
            $output .= '<input type="submit" name="submit" class="'.$button_class.'" value="'.$button_text.'" />';
        }
        else{//Use image button
            $output .= '<input type="image" id="buy_now_button" src="'.$payment_button_img_src.'" border="0" name="submit" alt="Make payments with PayPal">';
        }	
	$output .= '</div>';
	$output .= '</form>';
	$output .= '</div>';
	return $output;
}

function wppp_render_paypal_button_form($args)
{	
	extract( shortcode_atts( array(
		'email' => 'your@paypal-email.com',
		'currency' => 'USD',
		'options' => 'Payment for Service 1:15.50|Payment for Service 2:30.00|Payment for Service 3:47.00',
		'return' => site_url(),
		'reference' => 'Your Email Address',
		'other_amount' => '',
		'country_code' => '',
		'payment_subject' => '',
		'button_image' => '',
                'button_text' => '',
		'cancel_url' => '',
                'new_window' => '',
                'tax' => '',
	), $args));
	
        $email = apply_filters('wppp_widget_email', $email);
                
	$options = explode( '|' , $options);
	$html_options = '';
	foreach( $options as $option ) {
		$option = explode( ':' , $option );
		$name = esc_attr( $option[0] );
		$price = esc_attr( $option[1] );
		$html_options .= "<option data-product_name='{$name}' value='{$price}'>{$name} - {$price}</option>";
	}
	
	$payment_button_img_src = get_option('payment_button_type');
	if(!empty($button_image)){
		$payment_button_img_src = $button_image;
	}
        
        $window_target = '';
        if(!empty($new_window)){
            $window_target = 'target="_blank"';
        }
	
?>
<div class="wp_paypal_button_widget">
	<form name="_xclick" class="wp_accept_pp_button_form" action="https://www.paypal.com/cgi-bin/webscr" method="post" <?php echo $window_target; ?> >	
		<div class="wp_pp_button_selection_section">
		<select class="wp_paypal_button_options">
			<?php echo $html_options; ?>
		</select>
		</div>
		
		<?php 
		if(!empty($other_amount)){
			echo '<div class="wp_pp_button_other_amt_section">';
			echo 'Other Amount: <input type="text" name="other_amount" value="" size="4"> '.$currency;
			echo '</div>';
		}

                if(!empty($reference)){
                    echo '<div class="wp_pp_button_reference_section">';
                    echo '<label for="wp_pp_button_reference">'.$reference.'</label>';
                    echo '<br />';
                    echo '<input type="hidden" name="on0" value="Reference" />';
                    echo '<input type="text" name="os0" value="" class="wp_pp_button_reference" />';
                    echo '</div>';
                }

		if(!empty($payment_subject)){
		?>
		<input type="hidden" name="on1" value="Payment Subject" />
		<input type="hidden" name="os1" value="<?php echo $payment_subject; ?>" />
		<?php } ?>
		
		<input type="hidden" name="cmd" value="_xclick">
		<input type="hidden" name="business" value="<?php echo $email; ?>">
		<input type="hidden" name="currency_code" value="<?php echo $currency; ?>">
		<input type="hidden" name="item_name" value="">
		<input type="hidden" name="amount" value="">
		<input type="hidden" name="return" value="<?php echo $return; ?>" />
		<input type="hidden" name="email" value="" />
		<?php
                if(is_numeric($tax)){
                    echo '<input type="hidden" name="tax" value="'.$tax.'" />';
                }                
		if(!empty($cancel_url)){
			echo '<input type="hidden" name="cancel_return" value="'.$cancel_url.'" />';
		}
		if(!empty($country_code)){
			echo '<input type="hidden" name="lc" value="'.$country_code.'" />';
		}
                
                echo '<div class="wp_pp_button_submit_btn">';
                if(!empty($button_text)){//Use text button
                    $button_class = apply_filters('wppp_text_button_class','');
                    echo '<input type="submit" name="submit" class="'.$button_class.'" value="'.$button_text.'" />';
                }
		else{//Use image button
                    echo '<input type="image" id="buy_now_button" src="'.$payment_button_img_src.'" border="0" name="submit" alt="Make payments with PayPal">';
                }
		echo '</div>';
                ?>
	</form>	
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.wp_accept_pp_button_form').submit(function(e){	
		var form_obj = $(this);
		var options_name = form_obj.find('.wp_paypal_button_options :selected').attr('data-product_name');
		form_obj.find('input[name=item_name]').val(options_name);
		
		var options_val = form_obj.find('.wp_paypal_button_options').val();
		var other_amt = form_obj.find('input[name=other_amount]').val();
		if (!isNaN(other_amt) && other_amt.length > 0){
			options_val = other_amt;
		}
		form_obj.find('input[name=amount]').val(options_val);
		return;
	});
});
</script>
<?php 
}
