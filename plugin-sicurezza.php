<?php

/*

Plugin Name: Plugin Sicurezza
Description: elimina le info sulla versione di WordPress installata nel sito / elimina suggerimenti in caso di errori di accesso al sito

*/

/* INIZIO CODICE */

// rimuove dalla <head> la info sulla versione di wordpress installata sul sito
remove_action('wp_head', 'wp_generator');

// toglie suggerimenti nella pagine di login in caso di login errato
add_filter('login_errors',create_function('$a', "return null;"));

/* FINE CODICE */

?>