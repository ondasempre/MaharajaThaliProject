<?php
/*
Plugin Name: Google Analytics By Flavio M.
Plugin URI: http://ondasempre.wordpress.com
Description: Plugin wordpress per attivare le statistiche di Analytics
Author: Flavio Macciocchi
Version: 1.0
Author URI: http://ondasempre.wordpress.com
Requires at least: 3.8
Tested up to: 3.9
Text Domain: google-analytics
Domain Path: /languages
License: GPL
*/


// Esci se si accede direttamente
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 *  Classe Google Analytics.
 */
class Google_Analytics {

	/**
	 * Construttore - attiva il nuovo plugin all'interno del CMS
	 * @since    1.0
	 */
	public function __construct() {
		
		// Definisco le costanti
		define( 'VERSION', '1.0' );
		define( 'SLUG', plugin_basename(__FILE__));
		define( 'PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		//Filtri
		add_filter( "plugin_action_links_".SLUG , array( $this,'add_settings_link') );

		//Azioni attive
		add_action('admin_init', array($this,'add_settings_to_admin'));
		add_action('admin_head-options-general.php', array($this,'add_settings_help_tab'), 20 );
		add_action('wp_footer', array($this,'add_analytics_code'), 9999);
		add_action('plugins_loaded', array($this,'load_plugin_textdomain'));
	}

	/**
	 * Aggiungo il link delle impostazioni su wordpress
	 * @since    1.0
	 */
	public function add_settings_link( $links ) {
	    $settings_link = '<a href="options-general.php#ega_ua_code">'.__('Settings','google-analytics').'</a>';
	  	array_push( $links, $settings_link );
	  	return $links;
	}

	/**
	 * Aggiungo il link nelle opzioni generali di wordpress (in fondo alla pagina visualizzata troverai il Form per inserire l'ID) 
	 * @since    1.0
	 */
	public function add_settings_to_admin(){
		
		register_setting(
			'general',	// Pagina di opzioni
			'ega_options',	// Nome opzionale
			array(&$this,'ega_validate_options')	// callback di validazione
		);
		
		add_settings_field(
			'ega_ua_code',	// id
			__('Google Analytics UA Tracking ID', 'google-analytics'),	// opzione
			array(&$this, 'ega_setting_input'),	// display callback
			'general',	// pagina opzioni
			'default'	// pagina di sessione
		);

	}

	/**
	 * Campo per le opzioni 
	 * @since    1.0
	 */
	public function ega_setting_input() {
		
		// prendi l'opzione 'ega_ua_code' dal database
		$options = get_option( 'ega_options' );
		$value = $options['ega_ua_code'];

		?>
		// Form ID
		<input id="ega_ua_code" name="ega_options[ega_ua_code]" type="text" class="regular-text ltr" value="<?php echo esc_attr( $value ); ?>" />
		<p class="description"><?php _e('Inserisci in questo form il codice fornito da Goolge Analytics, in questo modo il tuo sito sarà monitorato.','easy-peasy-google-analytics');?></p>
		<?php
	}

	/**
	 * Convalida il nuovo campo inserito
	 * @since    1.0
	 */
	public function ega_validate_options( $input ) {
		$valid = array();
		$valid['ega_ua_code'] = sanitize_text_field( $input['ega_ua_code'] );

		// Something dirty entered? Warn user.
		if( $valid['ega_ua_code'] != $input['ega_ua_code'] ) {
			add_settings_error(
				'ega_ega_ua_code',	// setting title
				'ega_texterror',	// error ID
				__('Codice errato, prova a iserire un codice valido.','google-analytics'),	// errore
				'error'	// type of message
			);		
		}

		return $valid;
	}

	/**
	 * Pagina di aiuto per Admin
	 * @since    1.0
	 */
	public function add_settings_help_tab() {

		$help_tab_content = '<ul>';
		$help_tab_content .= '<li>'.__('To get your analytics UA code you need to login into your Google Analytics control panel','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Into the topbar of the page click the &quot;Admin&quot; link.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Click the &quot;Tracking Info&quot; link.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Then click the &quot;Tracking Code&quot; link.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Copy and paste the Tracking ID number into the option in your WordPress admin panel here.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '</ul>';
		$help_tab_content .= '<p>'.__('A tracking ID number looks like this UA-2986XXXX-X','easy-peasy-google-analytics').'</p>';
		
		get_current_screen()->add_help_tab( array(
			'id'       => 'ega_settings_help',
			'title'    => __('Google Analytics UA Code', 'easy-peasy-google-analytics'),
			'content'  => '<br/>'.$help_tab_content
		));

	}

	/**
	 * Aggiunge il codice di tracciamento in fondo alla pagina. Il codice dello script è fornito da Google.
	 * @since    1.0
	 */
	public function add_analytics_code() {

		$options = get_option( 'ega_options' );
		$ua_id = $options['ega_ua_code'];

		//Get current website url and format it to work with the google api
		$home_url = get_home_url();
		$find = array( 'http://', 'https://', 'www.');
		$replace = '';
		$output = str_replace( $find, $replace, $home_url );

		if($ua_id !== '') {
			echo "
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '".$ua_id."', '".$output."');
ga('send', 'pageview');
</script>";

		}

	}

	/**
	 * Posizione per la lingua
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'easy-peasy-google-analytics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}

$GLOBALS['google_analytics'] = new Google_Analytics();