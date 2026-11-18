<?php
/**
 * Rdacademy_Uploads_JS — IL CARICATORE DEL COMPONENTE DI CARICAMENTO RIUSABILE.
 * -----------------------------------------------------------------
 * Copiato da Reldox_Uploads_JS (pkg-anagrafica-1b3-uploads-js), RelDox CRD 3.55.09G.
 *
 * Espone window.RdacademyUploads = { DropZone, List, Banner, Toast, config }
 * caricando rdacademy-uploads.css + rdacademy-uploads.js dal hook della cornice
 * `rdacademy_enqueue_frontend_assets` con uscita diretta <link>/<script> (copiato:
 * il layout proprio non usa wp_head/wp_footer, quindi wp_enqueue_* lì non va).
 *
 * Config emessa lato server (copiato): apiBase, nonce `wp_rest`, maxSize,
 * allowedExtensions/allowedMimes dalla fonte unica Rdacademy_Uploads_Entity, i18n.
 * NUOVO: le scritture portano anche X-RDACADEMY-Nonce, letto a tempo di esecuzione
 * da window.RDACADEMY.nonce (il gettone del canale dell app, già nella pagina).
 *
 * TAGLI: la pagina admin di prova «smoke» e il suo enqueue (CRM); le stringhe
 * della selezione multipla (barra BUG-116 del CRM, non portata).
 * Solo per chi ha la sessione del prodotto: le porte pubbliche non lo caricano.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Uploads_JS {

	public static function init(): void {
		add_action( 'rdacademy_enqueue_frontend_assets', array( __CLASS__, 'enqueue_frontend' ), 7 );
	}

	public static function enqueue_frontend(): void {
		if ( ! Rdacademy_Auth::is_logged_in() ) {
			return;
		}
		$base = RDACADEMY_PLUGIN_URL . 'includes/uploads-js/assets/';
		$v    = RDACADEMY_VERSION;
		echo '<link rel="stylesheet" href="' . esc_url( $base . 'rdacademy-uploads.css' ) . '?v=' . esc_attr( $v ) . '">' . "\n";
		echo '<script>window.RdacademyUploads = window.RdacademyUploads || {}; window.RdacademyUploads.config = ' . wp_json_encode( self::build_config() ) . ';</script>' . "\n";
		echo '<script src="' . esc_url( $base . 'rdacademy-uploads.js' ) . '?v=' . esc_attr( $v ) . '" defer></script>' . "\n";
	}

	private static function build_config(): array {
		$map = Rdacademy_Uploads_Entity::ALLOWED_MIME;
		return array(
			'apiBase'           => esc_url_raw( rest_url( Rdacademy_Uploads_REST::NS . '/' . Rdacademy_Uploads_REST::BASE ) ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'maxSize'           => (int) Rdacademy_Uploads_Entity::MAX_SIZE_BYTES,
			'allowedExtensions' => array_keys( $map ),
			'allowedMimes'      => array_values( array_unique( array_values( $map ) ) ),
			'maxConcurrent'     => 3,
			'i18n'              => self::strings_it_IT(),
			'version'           => RDACADEMY_VERSION,
		);
	}

	/** Stringhe it_IT (copiate; tolte quelle della selezione multipla, aggiunte quelle della conferma in pagina). */
	private static function strings_it_IT(): array {
		return array(
			'dropzone_label'         => 'Trascina i file qui',
			'dropzone_or'            => 'oppure',
			'dropzone_select'        => 'Seleziona dal computer',
			'dropzone_help'          => 'Max 20 MB per file. Formati ammessi: PDF, immagini, Office, archivi, email.',
			'dropzone_drop_active'   => 'Rilascia per caricare',
			'dropzone_aria'          => 'Area di caricamento file. Premi Invio o Spazio per aprire il selettore.',
			'upload_success'         => '%FILENAME% caricato',
			'upload_error'           => 'Errore: %FILENAME%',
			'duplicate_banner'       => '%FILENAME% è stato caricato. Un file identico è già presente nel sistema: il nuovo allegato è collegato allo stesso contenuto fisico.',
			'duplicate_banner_close' => 'Chiudi',
			'duplicate_banner_aria'  => 'Notifica file duplicato',
			'list_title'             => 'Allegati',
			'list_empty_title'       => 'Nessun allegato presente',
			'list_empty_hint'        => 'Trascina o seleziona un file per iniziare',
			'list_loading'           => 'Caricamento elenco…',
			'list_refresh'           => 'Aggiorna',
			'list_item_date'         => 'Caricato %DATE%',
			'list_item_download'     => 'Scarica %FILENAME%',
			'list_item_delete'       => 'Elimina %FILENAME%',
			'list_item_deleted'      => 'Eliminato',
			'list_count'             => '%COUNT% allegati',
			'list_count_one'         => '1 allegato',
			'confirm_delete'         => 'Eliminare «%FILENAME%»? Il file sparisce anche dal disco se nessun altro lo usa.',
			'confirm_yes'            => 'Elimina',
			'confirm_no'             => 'Annulla',
			'err_invalid_mime'       => 'Tipo di file non supportato',
			'err_invalid_size'       => 'File troppo grande (max 20 MB)',
			'err_empty_file'         => 'Il file è vuoto',
			'err_unauthenticated'    => 'Sessione scaduta. Ricarica la pagina.',
			'err_forbidden'          => 'Permessi insufficienti per questo oggetto.',
			'err_too_large_server'   => 'File troppo grande per il server.',
			'err_network'            => 'Errore di rete. Riprova.',
			'err_generic'            => 'Errore imprevisto. Riprova.',
			'unit_bytes'             => 'B',
			'unit_kb'                => 'KB',
			'unit_mb'                => 'MB',
			'unit_gb'                => 'GB',
		);
	}
}
