<?php
/**
 * carico-e.php — IL CARICO DELL ACADEMY (analisi tecnica ed. 10, § 4 e allegato A: RIFAI).
 * Stessa forma di carico-c.php: classi, provisioning, azioni dichiarate col loro livello, pagine, voci del rail.
 *
 * Resta il punto d innesto del carico. Dentro, per ora, solo i pezzi generici:
 *   - lo schema E (le tabelle proprie dell Academy, § 5) con la sua guardia di provisioning;
 *   - la Tabella Funzionale dei testi delle mail dell account e l icona sul telefono;
 *   - ciò che l azzeramento dei dati operativi non svuota (i testi delle mail: configurazione dell azienda);
 *   - le icone del libro (manuali e guida), usate dalla colonna, dai menu ☰ e dal catalogo;
 *   - il foglio della leggibilità, l ultimo del <head>;
 *   - i carichi a catena: indipendenza dalla cache dell hosting (-143), registro delle spedizioni (-144),
 *     voce del catalogo dei corsi nel rail (-190).
 * ⛔ Le azioni dei materiali, del catalogo, della lezione, del registro e di Iris le aggiungono qui i loro
 * blocchi (M, C · L, R, I), ognuna col suo livello nella mappa e la guardia ripetuta nel gestore.
 *
 * @package RelDoxAcademy
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-e/class-rdacademy-schema-e.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-e/class-rdacademy-testi-notifiche.php';   // i testi delle mail dell account
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-e/class-rdacademy-app-install.php';       // l icona sul telefono

Rdacademy_Testi_Notifiche::init();
Rdacademy_App_Install::init();   // manifesto, icone dal logo del cliente, service worker minimo

add_action( 'plugins_loaded', static function () {
	Rdacademy_Provisioning_Guard::provision( 'Rdacademy_Schema_E_V1' );
}, 6 );

/**
 * Le azioni del carico: chiave => array( livello, gestore ). Vuota finché i blocchi dell Academy non portano le loro.
 * @return array<string,array{0:string,1:callable}>
 */
function rdacademy_lotto_e_azioni(): array {
	return array();
}

add_filter( 'rdacademy_capability_map', static function ( array $map ): array {
	foreach ( rdacademy_lotto_e_azioni() as $azione => $def ) {
		$map[ $azione ] = $def[0];
	}
	return $map;
} );
foreach ( rdacademy_lotto_e_azioni() as $rdacademy_e_azione => $rdacademy_e_def ) {
	add_action( 'rdacademy_ajax_' . $rdacademy_e_azione, $rdacademy_e_def[1], 10, 1 );
}
unset( $rdacademy_e_azione, $rdacademy_e_def );

/* I testi delle mail sono configurazione dell azienda, non dato operativo: l azzeramento non li svuota. */
add_filter( 'rdacademy_reset_keep_tables', static function ( array $keep ): array {
	$keep[] = Rdacademy_Testi_Notifiche::TABELLA;
	return $keep;
} );

/**
 * I DISEGNI DEL LIBRO, IN UN POSTO SOLO: libro aperto con le righe. Li usano la colonna del computer, i menu ☰
 * e «Come si usa» alla porta: nessuna copia del tratto altrove. Una chiave sconosciuta dà la stringa vuota.
 */
function rdacademy_icona_tratto( string $k ): string {
	$t = array(
		'manuali' => '<path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/><path d="M5 9h3M5 13h3M16 9h3M16 13h3"/>',
	);
	return $t[ $k ] ?? '';
}

/* 0.1.04A — «manuali» e «guida» vengono dal registro di famiglia (rdacademy_nav_icon → guida). */

/* Il foglio della leggibilità, l ultimo del <head> (sovrascrive le misure dei fogli di prima). */
add_action( 'rdacademy_enqueue_frontend_assets', static function () {
	if ( ! Rdacademy_Auth::is_logged_in() ) return;
	echo '<link rel="stylesheet" href="' . esc_url( RDACADEMY_PLUGIN_URL . 'public/css/rdacademy-leggibilita.css' ) . '?v=' . esc_attr( RDACADEMY_VERSION ) . '">' . "\n";
}, 50 );

/* L app indipendente dalla cache dell hosting: porta neutra, anti-cache per primo, cookie riconosciuto dalle cache,
   Vary: Cookie, guardia della pagina, controllo nello Stato del sistema. Un innesto suo. */
require_once RDACADEMY_PLUGIN_DIR . 'includes/carico-e-143.php';

/* Il registro delle spedizioni (le mail spedite dalla casella di servizio). Un innesto suo. */
require_once RDACADEMY_PLUGIN_DIR . 'includes/carico-e-144.php';

/* La voce del catalogo dei corsi nel rail. Un innesto suo. */
require_once RDACADEMY_PLUGIN_DIR . 'includes/carico-e-190.php';
