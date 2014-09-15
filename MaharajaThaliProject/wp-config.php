<?php
/**
 * Il file base di configurazione di WordPress.
 *
 * Questo file definisce le seguenti configurazioni: impostazioni MySQL,
 * Prefisso Tabella, Chiavi Segrete, Lingua di WordPress e ABSPATH.
 * E' possibile trovare ultetriori informazioni visitando la pagina: del
 * Codex {@link http://codex.wordpress.org/Editing_wp-config.php
 * Editing wp-config.php}. E' possibile ottenere le impostazioni per
 * MySQL dal proprio fornitore di hosting.
 *
 * Questo file viene utilizzato, durante l'installazione, dallo script
 * rimepire i valori corretti.
 *
 * @package WordPress
 */

// ** Impostazioni MySQL - E? possibile ottenere questoe informazioni
// ** dal proprio fornitore di hosting ** //
/** Il nome del database di WordPress */
define('DB_NAME', 'raja');

/** Nome utente del database MySQL */
define('DB_USER', 'root');

/** Password del database MySQL */
define('DB_PASSWORD', 'root');

/** Hostname MySQL  */
define('DB_HOST', 'localhost');

/** Charset del Database da utilizare nella creazione delle tabelle. */
define('DB_CHARSET', 'utf8');

/** Il tipo di Collazione del Database. Da non modificare se non si ha
idea di cosa sia. */
define('DB_COLLATE', '');

/**#@+
 * Chiavi Univoche di Autenticazione e di Salatura.
 *
 * Modificarle con frasi univoche differenti!
 * E' possibile generare tali chiavi utilizzando {@link https://api.wordpress.org/secret-key/1.1/salt/ servizio di chiavi-segrete di WordPress.org}
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'M*iq-HSve@Fqd_R}swgru@IlnM%n}@(S++Q6m{|~5?ugDwKgUp!SG5(s~;a)!+~H');
define('SECURE_AUTH_KEY',  'j3t;-s#ZLE+o_b{1HU,2%+!+w0xSA~V5lH++Dz^D~{|xcoDSws2]xM+jMQ>_+<,|');
define('LOGGED_IN_KEY',    'MtM<ayHHy#o*<??,6y(7vkpopC~w~7|&;*+<(l1*8s%mOO}|Fp/J ^l&QG8{95D(');
define('NONCE_KEY',        'q*Y=NGGV_O.mbt&%x10o|9{@9[@uy^<5VT$Qn5 5_~E?@[+e-S9Ta0/KQ1]AUv+o');
define('AUTH_SALT',        '!m5lJ36lJI]gM csu[:F|H^&4SNE)66Dd(8#/XN!+fJENs6@QarHhWn*1N[W?xM;');
define('SECURE_AUTH_SALT', 'Ap?/v$Pio0iL;v`S*|6=&)<Dkt)Ft)+r7l#g/cdZ~Gd5QtpAo98mgb+Hi <qko>,');
define('LOGGED_IN_SALT',   '3G7g{;3HcA074_7CnK2(ZsD5*{rpWki=Q5cSy0;KgO EvxU$<*b?UW$xh?SgI]5_');
define('NONCE_SALT',       'I`X0D]((X!2F6OdcGmYQ30M-+h^|F_&une%G_M^H3Fp?rXyHU]Kx.-1pr(Y*[9d7');

/**#@-*/

/**
 * Prefisso Tabella del Database WordPress .
 *
 * E' possibile avere installazioni multiple su di un unico database if you give each a unique
 * fornendo a ciascuna installazione un prefisso univoco.
 * Solo numeri, lettere e sottolineatura!
 */
$table_prefix  = 'wp_';

/**
 * Lingua di Localizzazione di WordPress, di base Inglese.
 *
 * Modificare questa voce per localizzare WordPress. Occorre che nella cartella
 * wp-content/languages sia installato un file MO corrispondente alla lingua
 * selezionata. Ad esempio, installare de_DE.mo in to wp-content/languages ed
 * impostare WPLANG a 'de_DE' per abilitare il supporto alla lingua tedesca.
 *
 */
define('WPLANG', 'it_IT');

/**
 *
 * Modificare questa voce a TRUE per abilitare la visualizzazione degli avvisi
 * durante lo sviluppo.
 * E' fortemente raccomandato agli svilupaptori di temi e plugin di utilizare
 * WP_DEBUG all'interno dei loro ambienti di sviluppo.
 */
define('WP_DEBUG', false);

/* Finito, interrompere le modifiche! Buon blogging. */

/** Path assoluto alla directory di WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Imposta lle variabili di WordPress ed include i file. */
require_once(ABSPATH . 'wp-settings.php');
/** Permette aggiornamento di wordpress senza usare FTP */
define('FS_METHOD','direct');
