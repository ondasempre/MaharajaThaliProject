<?php
/*
* Plugin Name: Wikipedia linker
* Description: Allow to create automatically wikipedia links
* Author: Dario Casalinuovo, Flavio Macciocchi
*/

function wikilinker_func($atts) {
	ob_start();

	$name = $atts['name'];

	$html = "<a href='http://it.wikipedia.org/wiki/"
		.$name."'>".$name."</a>";

	$output = ob_get_clean();

	return $html.$output;
}

add_action('init', 'register_wikilinker');

function register_wikilinker() {
	add_shortcode('wikilinker', 'wikilinker_func');
}


