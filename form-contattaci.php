<?php
/*
Plugin Name: Contact Form Plugin <----
Plugin URI: http://ondasempre.wordpress.com
Description: Semplice form per richiedere informazioni.
Version: 1.0
Author: Flavio Macciocchi, Dario Casalinuovo
Author URI: http://ondasempre.wordpress.com
*/
 
function html_form_code() {
    echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
    echo '<p>';
    echo 'Your Name* <br/>';
    echo '<input type="text" name="cf-name" pattern="[a-zA-Z0-9 ]+" value="' . ( isset( $_POST["cf-name"] ) ? esc_attr( $_POST["cf-name"] ) : '' ) . '" size="40" />';
    echo '</p>';
    echo '<p>';
    echo 'Your Email* <br/>';
    echo '<input type="email" name="cf-email" value="' . ( isset( $_POST["cf-email"] ) ? esc_attr( $_POST["cf-email"] ) : '' ) . '" size="40" />';
    echo '</p>';
    echo '<p>';
    echo 'Subject* <br/>';
    echo '<input type="text" name="cf-subject" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf-subject"] ) ? esc_attr( $_POST["cf-subject"] ) : '' ) . '" size="40" />';
    echo '</p>';
    echo '<p>';
    echo 'Your Message* <br/>';
    echo '<textarea rows="10" cols="35" name="cf-message">' . ( isset( $_POST["cf-message"] ) ? esc_attr( $_POST["cf-message"] ) : '' ) . '</textarea>';
    echo '</p>';
    echo '<p><input type="submit" name="cf-submitted" value="Send"></p>';
    echo '</form>';
}
 
function deliver_mail() {
 
    // se premi il bottone send, manda la mail all'admin.
    if ( isset( $_POST['cf-submitted'] ) ) {
 
        // pulisce i valori del form.	
        $name    = sanitize_text_field( $_POST["cf-name"] );
        $email   = sanitize_email( $_POST["cf-email"] );
        $subject = sanitize_text_field( $_POST["cf-subject"] );
        $message = esc_textarea( $_POST["cf-message"] );
 
        // recupera la mail dell'admin.
        $to = get_option( 'admin_email' );
 
        $headers = "From: $name <$email>" . "\r\n";
 
        // se la mail è stata inviata con successo, ritorna un messaggio.
        if ( wp_mail( $to, $subject, $message, $headers ) ) {
            echo '<div>';
            echo '<p>Grazie per averci contattati, ti risponderemo quanto prima.</p>';
            echo '</div>';
        } else {
            echo 'Il messaggio non è stato inviato a causa di un errore.';
        }
    }
}
 
function cf_shortcode() {
    ob_start();
    deliver_mail();
    html_form_code();
 
    return ob_get_clean();
}
 
// tag 
add_shortcode( 'sitepoint_contact_form', 'cf_shortcode' );
 
?>