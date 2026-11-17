<?php
/**
 * NUOVO — IL CARICO DEL LOTTO D (sorgenti 3-5, Impostazioni › Database completa,
 * allegati). SPEC-GUSCIO-BASE § 4.
 *
 * Il file principale lo richiede SOLO SE ESISTE, dopo B e C. Qui il lotto
 * aggiunge i suoi file e le SUE righe negli innesti predisposti dai lotti A e C:
 *   - rdacademy_capability_map / rdacademy_ajax_<azione>   le azioni del lotto (tutte `admin`)
 *   - rdacademy_schema_flag_map                       le sue classi di schema per la guardia dei flag
 *   - rdacademy_reset_keep_tables                     ciò che l azzeramento non svuota, col motivo
 *   - rdacademy_stato_sistema_sorgenti                le sorgenti nella pagina Stato sistema (lotto B)
 *   - rdacademy_sorgenti_tabelle                      la tabella d esempio di ogni sorgente
 *   - rdacademy_uploads_entita                        il tipo d oggetto di prova degli allegati
 *   - rdacademy_db_reset_prima / _dopo                (innesto LOTTO-D in Rdacademy_Impostazioni::ajax_db_reset)
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-sorgenti.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-schema-datasources-v1.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-datasource.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-tabella-senza-casa.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-table-map.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-table-owner.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-schema-sorgente-esempio-v1.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-sorgente-provisioner.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-dimensioni.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-d/class-rdacademy-database.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/backup/class-rdacademy-backup.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-schema-uploads-blob-v1.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-schema-uploads-v1.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-uploads-entity.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-uploads-rest.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/uploads-js/class-rdacademy-uploads-js.php';

/* ── Le classi di schema del lotto: guardia dei flag + provisioning flag-verificato ── */
function rdacademy_lotto_d_schemi(): array {
	return array(
		'Rdacademy_Schema_Datasources_V1'  => array( Rdacademy_Schema_Datasources_V1::TABLE ),
		'Rdacademy_Schema_Uploads_Blob_V1' => array( Rdacademy_Schema_Uploads_Blob_V1::TABLE ),
		'Rdacademy_Schema_Uploads_V1'      => array( Rdacademy_Schema_Uploads_V1::TABLE ),
	);
}
add_filter( 'rdacademy_schema_flag_map', static function ( array $map ): array {
	return $map + rdacademy_lotto_d_schemi();
} );
add_action( 'plugins_loaded', static function () {
	foreach ( array_keys( rdacademy_lotto_d_schemi() ) as $cls ) {
		Rdacademy_Provisioning_Guard::provision( $cls );
	}
}, 6 );

/* ── La tabella d esempio di ogni sorgente (il prodotto la sostituisce con le sue) ── */
add_filter( 'rdacademy_sorgenti_tabelle', static function ( array $t ): array {
	foreach ( array_keys( Rdacademy_Sorgenti::MAPPA ) as $k ) {
		if ( ! isset( $t[ $k ] ) ) {
			$t[ $k ] = array( $k . '_esempio' => array( 'Rdacademy_Schema_Sorgente_Esempio_V1', 'create' ) );
		}
	}
	return $t;
}, 5 );

/**
 * ── Il tipo d oggetto di prova degli allegati: l utente del prodotto ──
 * Nessuna interfaccia nuova: è il soggetto su cui il banco prova il motore e la
 * REST. Lo vede e lo modifica l amministratore o l utente stesso.
 */
add_filter( 'rdacademy_uploads_entita', static function ( array $e ): array {
	$mio = static function ( int $id, array $u ): bool {
		return (int) $u['id'] === $id || Rdacademy_Router::user_can_admin( $u );
	};
	$e['utente'] = array( 'tabella' => 'users', 'scope' => array( 'internal' ), 'vedere' => $mio, 'modificare' => $mio );
	return $e;
}, 5 );

/* ── Le azioni del lotto: UNA tabella, livello e handler insieme (forma di carico-c) ── */
function rdacademy_lotto_d_azioni(): array {
	return array(
		'db_panoramica'     => array( 'admin', array( 'Rdacademy_Database', 'ajax_db_panoramica' ) ),
		'datasource_get'    => array( 'admin', array( 'Rdacademy_Database', 'ajax_datasource_get' ) ),
		'datasource_test'   => array( 'admin', array( 'Rdacademy_Database', 'ajax_datasource_test' ) ),
		'datasource_save'   => array( 'admin', array( 'Rdacademy_Database', 'ajax_datasource_save' ) ),
		'datasource_reset'  => array( 'admin', array( 'Rdacademy_Database', 'ajax_datasource_reset' ) ),
		'db_capacity'       => array( 'admin', array( 'Rdacademy_Database', 'ajax_db_capacity' ) ),
		'db_quota_save'     => array( 'admin', array( 'Rdacademy_Database', 'ajax_db_quota_save' ) ),
		'db_optimize'       => array( 'admin', array( 'Rdacademy_Database', 'ajax_db_optimize' ) ),
		'backup_run'        => array( 'admin', array( 'Rdacademy_Database', 'ajax_backup_run' ) ),
		'backup_list'       => array( 'admin', array( 'Rdacademy_Database', 'ajax_backup_list' ) ),
		'restore_preview'   => array( 'admin', array( 'Rdacademy_Database', 'ajax_restore_preview' ) ),
		'restore_apply'     => array( 'admin', array( 'Rdacademy_Database', 'ajax_restore_apply' ) ),
	);
}
add_filter( 'rdacademy_capability_map', static function ( array $map ): array {
	foreach ( rdacademy_lotto_d_azioni() as $azione => $def ) {
		$map[ $azione ] = $def[0];
	}
	return $map;
} );
foreach ( rdacademy_lotto_d_azioni() as $rdacademy_d_azione => $rdacademy_d_def ) {
	add_action( 'rdacademy_ajax_' . $rdacademy_d_azione, $rdacademy_d_def[1], 10, 1 );
}
unset( $rdacademy_d_azione, $rdacademy_d_def );

Rdacademy_Uploads_REST::init();
Rdacademy_Uploads_JS::init();

/* ── Ciò che l azzeramento dei dati operativi NON svuota (con il motivo) ── */
add_filter( 'rdacademy_reset_keep_tables', static function ( array $keep ): array {
	$keep[] = Rdacademy_Schema_Datasources_V1::TABLE; // registro delle sorgenti: configurazione dell installazione
	$keep[] = 'license_activation';              // ancora di licenza (lotto B la dichiara già: qui la cucitura, idempotente)
	$keep[] = 'impostazioni';                    // impostazioni (il nucleo la tiene già: dichiarata anche qui per il lotto)
	return $keep;
} );

/*
 * ── L azzeramento cancella anche i FILE (REGOLE § 3.9) ──
 * Prima: si leggono i percorsi dei blob se `uploads_blob` sta per essere
 * svuotata. Dopo: si tolgono i file. Gli innesti sono in ajax_db_reset (LOTTO-D).
 */
add_action( 'rdacademy_db_reset_prima', static function ( array $svuota ) {
	$GLOBALS['rdacademy_d_blob_da_togliere'] = in_array( RDACADEMY_TABLE_PREFIX . Rdacademy_Schema_Uploads_Blob_V1::TABLE, $svuota, true )
		? Rdacademy_Uploads_Entity::percorsi_blob() : array();
} );
add_action( 'rdacademy_db_reset_dopo', static function ( array $svuota ) {
	$p = (array) ( $GLOBALS['rdacademy_d_blob_da_togliere'] ?? array() );
	if ( $p ) {
		$n = Rdacademy_Uploads_Entity::togli_file( $p );
		error_log( '[RelDoxAcademy] azzeramento: ' . $n . ' file di allegati tolti dal disco su ' . count( $p ) );
	}
	unset( $GLOBALS['rdacademy_d_blob_da_togliere'] );
} );

/* ── Stato sistema (lotto B): le sorgenti 3-5, solo chiavi di sola lettura ── */
add_filter( 'rdacademy_stato_sistema_sorgenti', static function ( array $righe ): array {
	$parole = array(
		'not_configured' => 'NON IN USO · ricade sul BASE',
		'disabled'       => 'NON IN USO · spenta (provisioning non riuscito)',
		'unprovisioned'  => 'IN USO · tabelle non ancora sul suo database',
		'live'           => 'IN USO · tabelle sul suo database',
	);
	foreach ( Rdacademy_Sorgenti::chiavi() as $k ) {
		$pub     = Rdacademy_DataSource::get_public( $k );
		$righe[] = array(
			'label'   => $pub['n'] . ' · ' . $pub['label'],
			'db_name' => (string) $pub['db_name'],
			'db_host' => (string) $pub['db_host'],
			'stato'   => $parole[ $pub['state'] ] ?? $pub['state'],
		);
	}
	return $righe;
} );

/* ── Il foglio della linguetta Database (dal <head> della cornice, solo collegati) ── */
add_action( 'rdacademy_enqueue_frontend_assets', static function () {
	if ( ! Rdacademy_Auth::is_logged_in() ) return;
	echo '<link rel="stylesheet" href="' . esc_url( RDACADEMY_PLUGIN_URL . 'public/css/rdacademy-lotto-d.css' ) . '?v=' . esc_attr( RDACADEMY_VERSION ) . '">' . "\n";
}, 6 );
