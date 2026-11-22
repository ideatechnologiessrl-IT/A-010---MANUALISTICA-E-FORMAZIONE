<?php
/**
 * Plugin Name:       RelDoxAcademy
 * Plugin URI:        https://www.ideatechnologies.com
 * Description:       RelDoxAcademy — i corsi on line e i manuali dei prodotti della famiglia RelDox. Prodotto della famiglia RelDox.
 * Version:           0.1.10
 * Author:            Idea Technologies srl soc. Uninominale
 * Author URI:        https://www.ideatechnologies.com
 * License:           Proprietary
 * Text Domain:       reldox-academy
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 *
 * Origine: copia del prodotto di base indicato nell analisi tecnica (ed. 4, § 15), con nome e segno rdacademy
 * (analisi tecnica RelDoxAcademy ed. 4, § 1 e allegato A; REGOLE-FAMIGLIA v4 § 2).
 * Ogni file è stato letto per intero prima di copiarlo: docs/REGISTRO-LETTURA-BLOCCO-G.md
 * del repository A-010. La ristorazione e il motore delle API non entrano; i manuali
 * dei prodotti entrano come materiali (D28, D29).
 *
 * 0.1.01A — LA COPIA DI BASE (Formazione F005, «genera» dell owner 17/11/2026 15:28).
 * 0.1.02A — il wizard scrive il database prima di wp-settings.php anche nel wp-config italiano
 *          e sposta da solo un blocco scritto dopo (owner 18/11/2026 14:37, Formazione F013).
 * 0.1.03A — la grafica di famiglia: app.css e il foglio di leggibilità della famiglia copiati interi
 *          (Formazione F014).
 * 0.1.04A — SPEC-GRAFICA-RELDOX-v5: foglio comune e registro delle icone copiati dal nodo e caricati per primi;
 *          barra laterale comune con la cima vuota (S-1), due loghi in testata col predefinito Idea Technologies;
 *          blu vecchio, testo sotto i 13px ed emoji tolti; i cinque fogli chiamati e assenti scritti o non più chiamati (BUG-2)
 *          (Formazione F016).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RDACADEMY_VERSION',       '0.1.10' );
define( 'RDACADEMY_STAGE',         'Alfa' );   // REGOLE-FAMIGLIA § 9.1: lo stadio lo decide l owner
// § 9.1 — la lettera dello stadio si scrive ATTACCATA al numero: A=Alfa, B=Beta, G=Gold, L=Live.
define( 'RDACADEMY_VERSION_LABEL', RDACADEMY_VERSION . ( array( 'Alfa' => 'A', 'Beta' => 'B', 'Gold' => 'G', 'Live' => 'L' )[ RDACADEMY_STAGE ] ?? '' ) );
// RP-VERSIONE-PANNELLO-MINIMO (copiato da RelDox) — il pacchetto e un NOME breve, la cronaca sta nel CHANGELOG.
define( 'RDACADEMY_PACKAGE',       '// 0.1.10A | F023 | licenza' );
// 0.8.2A (RF-BUG-022) — la data la scrive il compositore del pacchetto insieme al manifesto, MAI A MANO.
define( 'RDACADEMY_PACKAGE_DATE',  '2026-11-22' );
define( 'RDACADEMY_PRODUCT',       'RelDoxAcademy' );
define( 'RDACADEMY_VENDOR',        'Idea Technologies srl soc. Uninominale' );   // 0.1.05A (BUG-3): la ragione sociale, come nei documenti
define( 'RDACADEMY_VENDOR_URL',    'https://www.ideatechnologies.com' );
// Utente di servizio: vive nel BASE di QUESTO prodotto, nessun conflitto con gli altri della famiglia.
// Indirizzo deciso dall owner (domanda 3 del § 15 dell analisi, D31, 17/11/2026 15:58).
define( 'RDACADEMY_SERVICE_EMAIL', 'academy@reldox.it' );
define( 'RDACADEMY_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'RDACADEMY_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'RDACADEMY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Credenziali DB — copiato da RelDox (pkg6.1-cleanup, Stadio 3).
 *
 * Le credenziali del BASE sono lette ESCLUSIVAMENTE da wp-config.php. Il plugin
 * NON contiene valori di ripiego. Se manca anche una sola costante RDACADEMY_DB_*,
 * il plugin non si avvia: compare l installazione guidata e il sito resta in
 * piedi (REGOLE-FAMIGLIA § 3.5).
 *
 * NB: $GLOBALS['rdacademy_db_defaults_applied'] resta inizializzato a [] per
 * compatibilita con Rdacademy_DB_Config::get(), che lo legge come fonte di verita
 * per discriminare l origine di ogni costante. Vuoto = tutte da wp-config.
 */
$GLOBALS['rdacademy_db_defaults_applied'] = [];

/*
 * TAGLIO (lotto A): il percorso di RESET «a semaforo» di RelDox
 * (resetreldox_phase, Reldox_Reset_Path) non entra nel guscio: e escluso dalla
 * SPEC § 0 («reset path»).
 */

/**
 * Gate fail-loud: verifica che TUTTE le costanti RDACADEMY_DB_* siano definite.
 * Se anche una sola manca, il plugin NON si avvia: si registra il wizard di
 * installazione e il file ritorna prima dei require_once.
 */
$rdacademy_db_required = [
	'RDACADEMY_DB_HOST',
	'RDACADEMY_DB_NAME',
	'RDACADEMY_DB_USER',
	'RDACADEMY_DB_PASS',
	'RDACADEMY_DB_CHARSET',
];

$rdacademy_db_missing = array_values( array_filter(
	$rdacademy_db_required,
	static function ( $const_name ) {
		return ! defined( $const_name );
	}
) );

if ( ! empty( $rdacademy_db_missing ) ) {

	// Copiato da RelDox v3.15.50: il wizard e l unica superficie d installazione
	// finche il BASE non e in wp-config. add_action esiste gia in ogni contesto
	// normale; il controllo protegge i boot parziali da un errore fatale.
	if ( function_exists( 'add_action' ) ) {
		require_once RDACADEMY_PLUGIN_DIR . 'includes/bootstrap/class-rdacademy-bootstrap-wizard.php';
		Rdacademy_Bootstrap_Wizard::boot();
	}

	// Avvio interrotto: niente classi, niente admin, niente router. Il sito
	// WordPress resta perfettamente funzionante; solo il guscio dorme.
	return;
}

unset( $rdacademy_db_required, $rdacademy_db_missing );

define( 'RDACADEMY_TABLE_PREFIX', 'rdacademy_' );

define( 'RDACADEMY_APP_SLUG', 'academy' );

require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-db-config.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-db.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-option.php';   // lo stato del prodotto vive sul BASE: va caricata SUBITO dopo Rdacademy_DB e prima di ogni classe schema (RP-_1-NON-E-DI-RELDOX)
require_once RDACADEMY_PLUGIN_DIR . 'includes/helpers.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-lingua.php';   // la lingua della persona (IT/EN/DE, come il prodotto di base)
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-activator.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-schema-flag-guard.php';   // un segnaposto non e una prova: verifica fisica prima del maybe_rebuild (BUG-53 di RelDox)
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-provisioning-guard.php';  // prima di ogni classe schema (BUG-07 di RelDox)
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-security.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-rest-auth.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-auth.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-roles.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-user-prefs.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-password-policy.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-password-token.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-imap-diretta.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-casella.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-password-mail.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-indurimento.php';   // 0.1.07A — F32: riga «Indurimento: n su 18» (SPEC-INDURIMENTO v2)
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-interruttori.php';   // 0.1.08A — F34: interruttori A1 e A2, spenti di serie (SPEC-INDURIMENTO v2 § D.2)
add_action( 'plugins_loaded', array( 'Rdacademy_Interruttori', 'init' ), 20 );   // dopo il provisioning (priorità 5): il BASE è pronto; Rdacademy_Interruttori::init
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-admin.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-router.php';
// F023_LICENZA — 0.1.10A: la «Licenza d uso» come RelDox CRD 3.76.03G (voce 150), prodotto della chiave `reldox-academy`
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license-validate.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license-testi.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license-pdf.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license-firma.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license-verifica.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-licenza-stato.php';
if ( class_exists( 'Rdacademy_Licenza_Stato' ) ) { Rdacademy_Licenza_Stato::init(); }
require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license-admin.php';

/*
 * NUOVO — I CARICHI DEI LOTTI B, C, D (SPEC § 4).
 * Ogni lotto aggiunge il SUO file di carico e i suoi file, senza toccare questo.
 * Si richiede solo se il file esiste: un lotto non ancora consegnato non e un
 * errore fatale. L ordine e fisso: B (menu WP), C (pagine dell app), D (sorgenti).
 */
foreach ( array( 'carico-b.php', 'carico-c.php', 'carico-d.php', 'carico-e.php' ) as $rdacademy_carico ) {   // E: il carico dell Academy (materiali, catalogo, lezione, registro, Iris)
	if ( @is_readable( RDACADEMY_PLUGIN_DIR . 'includes/' . $rdacademy_carico ) ) {
		require_once RDACADEMY_PLUGIN_DIR . 'includes/' . $rdacademy_carico;
	}
}
unset( $rdacademy_carico );

/*
 * IL MISURATORE DELLA PASSWORD, UNA COPIA SOLA — copiato da RelDox 3.16.197 (S258).
 * Sincrono, niente defer, priorita 4: le superfici che scrivono una password
 * (imposta-password, scheda utente, Preferenze) montano JS inline nel body e
 * chiamano window.RxPwdMeter a tempo di esecuzione. Vale anche per le pagine
 * PUBBLICHE: questo hook e emesso dal <head> di layout.php.
 * TAGLIO: reldox-origin.js, reldox-ctx-icons.js, reldox-crea-sotto.js,
 * reldox-inbox-bar.js e il motore DSL delle viste (tutti CRM).
 */
add_action( 'rdacademy_enqueue_frontend_assets', static function () {
	echo '<script src="'
		. esc_url( RDACADEMY_PLUGIN_URL . 'public/js/rdacademy-pwd-meter.js' )
		. '?v=' . esc_attr( RDACADEMY_VERSION )
		. '"></script>' . "\n";
}, 4 );

// v3.11.187 di RelDox — RP-DEPLOY-OPCACHE AUTOMATICO. Su hosting condiviso,
// dopo un deploy il bytecode PHP resta in OPcache finche non si resetta a mano.
// Helper guardato: nessun warning se OPcache non c e o e disabilitato.
if ( ! function_exists( 'rdacademy_opcache_flush' ) ) {
	function rdacademy_opcache_flush() {
		if ( ! function_exists( 'opcache_reset' ) ) return false;
		if ( function_exists( 'opcache_get_status' ) ) {
			$st = @opcache_get_status( false );
			if ( is_array( $st ) && empty( $st['opcache_enabled'] ) ) return false;
		}
		return @opcache_reset();
	}
}
// Aggancio 1 — istante esatto del replace via wp-admin: appena WP finisce di
// scrivere i file del nostro plugin, svuota OPcache.
add_action( 'upgrader_process_complete', function( $upgrader, $hook_extra ) {
	if ( empty( $hook_extra['type'] ) || $hook_extra['type'] !== 'plugin' ) return;
	$me      = plugin_basename( __FILE__ );
	$plugins = isset( $hook_extra['plugins'] ) ? (array) $hook_extra['plugins'] : array();
	// 'install' (sostituzione ZIP) non popola sempre ['plugins']: in dubbio, resetta.
	if ( empty( $plugins ) || in_array( $me, $plugins, true ) ) {
		rdacademy_opcache_flush();
	}
}, 10, 2 );

/*
 * PROVISIONING su plugins_loaded — copiato da RelDox (closure priorita 5).
 * Idempotente: crea le tabelle mancanti. Due tempi, come in RelDox:
 *   1. cambio di versione -> Activator::activate() (tabelle + semi + versione);
 *   2. sempre -> il nucleo passa dal Provisioning Guard, che verifica il flag
 *      sul fatto (le tabelle esistono davvero) e ritenta da solo se manca.
 * TAGLIO: snapshot pre-migrazione (Reldox_Backup), modo dichiarato di
 * Reldox_Table_Owner, le sessanta classi schema del CRM, sent_at_snapshot.
 */
add_action( 'plugins_loaded', function() {
	$db_ver = (string) get_option( 'rdacademy_db_version', '0' );
	if ( version_compare( $db_ver, RDACADEMY_VERSION, '<' ) ) {
		try {
			Rdacademy_Activator::activate();
		} catch ( Throwable $rdacademy_e ) {
			// Un boot non muore per lo schema: il guard qui sotto ritenta e lo dice.
			error_log( '[RelDoxAcademy] attivazione: ' . $rdacademy_e->getMessage() );
		}
		// Aggancio 2 — al primo caricamento con versione nuova (anche via FTP) svuota OPcache.
		rdacademy_opcache_flush();
	}

	// AP-FASE0-FLUSH-REWRITE (RelDox 3.15.52): primo boot completo (booted_version
	// vuota = post-wizard). L attivazione e bypassata nel flusso needs_db, quindi le
	// rewrite rules /academy/ non sono mai state generate: si programma il flush.
	$rdacademy_booted = (string) get_option( 'rdacademy_booted_version', '' );
	if ( $rdacademy_booted === '' ) {
		update_option( 'rdacademy_flush_rewrite', true );
	}
	if ( $rdacademy_booted !== RDACADEMY_VERSION ) {
		update_option( 'rdacademy_booted_version', RDACADEMY_VERSION ); // sempre
	}

	// Il nucleo, flag-verificato (BUG-07 di RelDox): un flag che mente si cancella e si ritenta.
	Rdacademy_Provisioning_Guard::provision( 'Rdacademy_Activator' );
}, 5 );

register_activation_hook( __FILE__,   array( 'Rdacademy_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Rdacademy_Activator', 'deactivate' ) );

function rdacademy_init() {
	$admin = new Rdacademy_Admin();
	$admin->init();
	$router = new Rdacademy_Router();
	$router->init();
	$license_admin = new Rdacademy_License_Admin();   // F023_LICENZA
	$license_admin->init();

	// Rigenera le rewrite rules se necessario dopo l attivazione.
	// ⛔ CORRETTO RISPETTO A RELDOX: `flush_rewrite_rules()` stava QUI, su
	// plugins_loaded, quando `$wp_rewrite` non esiste ancora (nasce in
	// wp-settings.php DOPO plugins_loaded): `flush_rewrite_rules()` usciva muta
	// e l option veniva cancellata lo stesso. Si sposta su `init` 99, dopo che
	// il router ha registrato le sue regole su `init` 10.
	add_action( 'init', static function () {
		if ( get_option( 'rdacademy_flush_rewrite' ) ) {
			flush_rewrite_rules( false );
			delete_option( 'rdacademy_flush_rewrite' );
		}
	}, 99 );
}
add_action( 'plugins_loaded', 'rdacademy_init' );
