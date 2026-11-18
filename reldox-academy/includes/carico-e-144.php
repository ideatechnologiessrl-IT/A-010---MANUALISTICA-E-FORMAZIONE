<?php
/**
 * IL REGISTRO DELLE SPEDIZIONI. Un innesto suo, richiesto da carico-e.php (analisi tecnica ed. 12: TIENI, ridotto).
 *  - tabella `spedizioni` (Rdacademy_Schema_Spedizioni_V1, flag suo, indipendente dallo schema E);
 *  - una riga per ogni mail della casella di servizio (Rdacademy_Spedizioni);
 *  - in Gestione utenti la pagina «Registro delle spedizioni» (/academy/spedizioni) e, sulla riga, la pillola rossa di un invito
 *    non partito o respinto; nella linguetta «Log» dell account;
 *  - «Controlla i rifiuti»: gli avvisi di mancata consegna tornati nella casella, abbinati alla loro riga. Solo col pulsante:
 *    nella copia non c è il pianificatore degli invii a cui il prodotto di partenza lo agganciava.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-e/class-rdacademy-spedizioni.php';

add_action( 'plugins_loaded', static function () {
	Rdacademy_Provisioning_Guard::provision( 'Rdacademy_Schema_Spedizioni_V1' );
}, 7 );

function rdacademy_azioni_144(): array {
	return array(
		'spedizioni_elenco'  => array( 'admin', array( 'Rdacademy_Spedizioni', 'ajax_elenco' ) ),
		'spedizioni_rifiuti' => array( 'admin', array( 'Rdacademy_Spedizioni', 'ajax_rifiuti' ) ),
	);
}
add_filter( 'rdacademy_capability_map', static function ( array $map ): array {
	foreach ( rdacademy_azioni_144() as $azione => $def ) $map[ $azione ] = $def[0];
	return $map;
} );
foreach ( rdacademy_azioni_144() as $rdacademy_az144 => $rdacademy_def144 ) {
	add_action( 'rdacademy_ajax_' . $rdacademy_az144, $rdacademy_def144[1], 10, 1 );
}
unset( $rdacademy_az144, $rdacademy_def144 );

/* /academy/spedizioni — solo amministratori; si apre da Gestione utenti (non è una voce del menu). */
add_filter( 'rdacademy_page_access', static function ( array $map ): array { $map['spedizioni'] = 'admin'; return $map; } );
add_filter( 'rdacademy_page_map', static function ( array $map ): array { $map['spedizioni'] = 'spedizioni.php'; return $map; } );

/* Il registro è un dato operativo: l azzeramento dei dati operativi lo svuota come gli altri; la tabella rinasce al boot dopo (il flag
   si verifica sulla tabella, Rdacademy_Schema_Flag_Guard). */

/* Il foglio di stile del registro delle spedizioni, dopo quello della leggibilità. */
add_action( 'rdacademy_enqueue_frontend_assets', static function () {
	echo '<link rel="stylesheet" href="' . esc_url( RDACADEMY_PLUGIN_URL . 'public/css/rdacademy-144.css' ) . '?v=' . esc_attr( RDACADEMY_VERSION ) . '">' . "\n";
}, 61 );
