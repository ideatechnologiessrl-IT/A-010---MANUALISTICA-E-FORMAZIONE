<?php
/**
 * Rdacademy_Uploads_REST — GLI ENDPOINT REST DEGLI ALLEGATI.
 * -----------------------------------------------------------------
 * Copiato da Reldox_Uploads_REST (pkg-anagrafica-1b2), RelDox CRD 3.55.09G.
 *
 * Base: /wp-json/rdacademy/v1/uploads/
 *   POST   /attach          multipart → {ok, upload_id, blob_id, blob_was_new, meta}
 *   GET    /list            ?entity_type=&entity_id=&scope=&include_deleted=
 *   GET    /{id}/download   streaming con Range
 *   POST   /{id}/delete     → {ok, upload_id, file}
 *   GET    /{id}/meta
 *
 * PERMESSI DALLA SESSIONE DEL PRODOTTO: Rdacademy_REST_Auth::dual_auth_check (che nel
 * guscio riconosce SOLO la sessione, SPEC § 2.1) e, per ogni scrittura, ANCHE
 * il gettone del canale dell app nell intestazione X-RDACADEMY-Nonce (stessa regola
 * dei REST del lotto C, «una credenziale per pagina», REGOLE § 5): in RelDox un
 * POST da un altra scheda dello stesso sito passava con il solo cookie.
 * Il permesso sull OGGETTO lo decide il registro (Rdacademy_Uploads_Entity::entita()).
 *
 * DIFFERENZE, DICHIARATE: `/soft-delete` si chiama `/delete` perché toglie
 * anche il file all ultimo riferimento; il Content-Type del download è il MIME
 * del registro (letto dal contenuto), non ricostruito dal nome; l elenco non
 * espone storage_path né lo sha256 completo. TAGLIO: surface, entity_master_id.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Uploads_REST {

	const NS   = 'rdacademy/v1';
	const BASE = 'uploads';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/** Lettura: la sessione del prodotto. */
	public static function permesso_lettura( $request = null ) {
		return Rdacademy_REST_Auth::dual_auth_check( $request );
	}

	/** Scrittura: la sessione E il gettone del canale dell app. */
	public static function permesso_scrittura( $request = null ) {
		$ok = Rdacademy_REST_Auth::dual_auth_check( $request );
		if ( $ok !== true ) {
			return $ok;
		}
		$n = ( $request instanceof WP_REST_Request ) ? (string) $request->get_header( 'x_rdacademy_nonce' ) : '';
		if ( $n === '' || ! wp_verify_nonce( $n, 'rdacademy_ajax' ) ) {
			return new WP_Error( 'rdacademy_rest_nonce', 'Token di sicurezza non valido o scaduto. Ricaricare la pagina.', array( 'status' => 403 ) );
		}
		return true;
	}

	public static function register_routes(): void {
		$r = array( __CLASS__, 'permesso_lettura' );
		$w = array( __CLASS__, 'permesso_scrittura' );
		register_rest_route( self::NS, '/' . self::BASE . '/attach', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_attach' ), 'permission_callback' => $w ) );
		register_rest_route( self::NS, '/' . self::BASE . '/list', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_list' ), 'permission_callback' => $r ) );
		register_rest_route( self::NS, '/' . self::BASE . '/(?P<id>\d+)/download', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_download' ), 'permission_callback' => $r ) );
		register_rest_route( self::NS, '/' . self::BASE . '/(?P<id>\d+)/delete', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_delete' ), 'permission_callback' => $w ) );
		register_rest_route( self::NS, '/' . self::BASE . '/(?P<id>\d+)/meta', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_meta' ), 'permission_callback' => $r ) );
	}

	private static function utente(): ?array {
		$u = Rdacademy_Auth::get_current_user();
		return ( is_array( $u ) && ! empty( $u['id'] ) ) ? $u : null;
	}

	public static function rest_attach( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'no_file', 'message' => 'Parametro «file» assente nella richiesta.' ), 400 );
		}
		$res = Rdacademy_Uploads_Entity::attach(
			(string) $request->get_param( 'entity_type' ),
			(int) $request->get_param( 'entity_id' ),
			(string) ( $request->get_param( 'scope' ) ?: 'internal' ),
			(array) $files['file']
		);
		if ( ! $res['success'] ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => $res['error'], 'message' => $res['error_message'] ), self::map_error_to_http( (string) $res['error'] ) );
		}
		return new WP_REST_Response( array(
			'ok' => true, 'upload_id' => (int) $res['upload_id'], 'blob_id' => (int) $res['blob_id'],
			'blob_was_new' => (bool) $res['blob_was_new'], 'meta' => $res['meta'],
		), 200 );
	}

	private static function riga_pubblica( array $r ): array {
		return array(
			'id' => (int) $r['id'], 'entity_type' => (string) $r['entity_type'], 'entity_id' => (int) $r['entity_id'],
			'scope' => (string) $r['scope'], 'filename_original' => (string) $r['filename_original'],
			'mime_type' => (string) ( $r['mime_type'] ?? '' ), 'size_bytes' => (int) ( $r['size_bytes'] ?? 0 ),
			'sha256_breve' => substr( (string) ( $r['sha256'] ?? '' ), 0, 12 ),
			'uploaded_by' => (int) $r['uploaded_by'], 'uploaded_at' => (string) $r['uploaded_at'],
			'deleted_at' => $r['deleted_at'], 'deleted_by' => $r['deleted_by'] !== null ? (int) $r['deleted_by'] : null,
			'download_url' => rest_url( self::NS . '/' . self::BASE . '/' . (int) $r['id'] . '/download' ),
		);
	}

	public static function rest_list( WP_REST_Request $request ) {
		$tipo  = (string) $request->get_param( 'entity_type' );
		$id    = (int) $request->get_param( 'entity_id' );
		$scope = $request->get_param( 'scope' );
		$scope = ( $scope === null || $scope === '' ) ? null : (string) $scope;
		if ( $tipo === '' || $id <= 0 ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_params', 'message' => 'entity_type ed entity_id sono obbligatori.' ), 400 );
		}
		$u = self::utente();
		if ( ! $u ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'unauthenticated' ), 401 );
		}
		if ( ! Rdacademy_Uploads_Entity::puo( 'vedere', $tipo, $id, $u ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'forbidden' ), 403 );
		}
		$rows = Rdacademy_Uploads_Entity::list_for_entity( $tipo, $id, $scope, (bool) $request->get_param( 'include_deleted' ) );
		if ( Rdacademy_DB::last_sql_error() !== null ) {
			// RP-ERRORE-NON-SI-TRAVESTE-DA-DATO: un elenco non letto non è un elenco vuoto.
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'db_error', 'message' => 'Elenco degli allegati non leggibile.' ), 500 );
		}
		$out = array_map( array( __CLASS__, 'riga_pubblica' ), $rows );
		return new WP_REST_Response( array( 'ok' => true, 'count' => count( $out ), 'uploads' => $out ), 200 );
	}

	/** Streaming binario con Range (copiato: header, buffer, 206/416, exit). */
	public static function rest_download( WP_REST_Request $request ) {
		$upload_id = (int) $request['id'];
		$prep      = Rdacademy_Uploads_Entity::prepare_download( $upload_id );
		if ( ! $prep['ok'] ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => (string) $prep['reason'] ), (int) $prep['http_code'] );
		}
		$meta = $prep['meta'];
		$abs  = $prep['absolute_path'];
		$size = (int) @filesize( $abs );

		@ini_set( 'zlib.output_compression', '0' );
		while ( ob_get_level() > 0 ) {
			@ob_end_clean();
		}
		$nome  = (string) $meta['filename_original'];
		$ascii = preg_replace( '/[^A-Za-z0-9._-]/', '_', $nome );
		if ( $ascii === '' || $ascii === null ) {
			$ascii = 'download.bin';
		}
		$start = 0; $end = $size - 1; $partial = false;
		$range = (string) ( $request->get_header( 'range' ) ?? ( $_SERVER['HTTP_RANGE'] ?? '' ) );
		if ( $range !== '' && preg_match( '/^bytes=(\d+)-(\d*)$/', $range, $m ) ) {
			$start = (int) $m[1];
			if ( $m[2] !== '' ) { $end = (int) $m[2]; }
			if ( $start >= $size || $end >= $size || $start > $end ) {
				status_header( 416 );
				header( 'Content-Range: bytes */' . $size, true );
				header( 'Content-Length: 0', true );
				exit;
			}
			$partial = true;
		}
		$length = $end - $start + 1;
		status_header( $partial ? 206 : 200 );
		$mime   = (string) $meta['mime_type'];
		$is_pdf = ( $mime === 'application/pdf' );
		header( 'Content-Type: ' . ( $mime !== '' ? $mime : 'application/octet-stream' ), true );
		header( 'Content-Disposition: ' . ( $is_pdf ? 'inline' : 'attachment' ) . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode( $nome ), true );
		header( 'X-Content-Type-Options: nosniff', true );
		header( 'Cache-Control: private, no-cache, no-store, must-revalidate', true );
		header( 'Pragma: no-cache', true );
		header( 'Expires: 0', true );
		header( 'X-Frame-Options: ' . ( $is_pdf ? 'SAMEORIGIN' : 'DENY' ), true );
		header( "Content-Security-Policy: default-src 'none'; sandbox", true ); // NUOVO: un allegato aperto non esegue nulla
		header( 'Accept-Ranges: bytes', true );
		header_remove( 'Content-Encoding' );
		if ( $partial ) {
			header( 'Content-Range: bytes ' . $start . '-' . $end . '/' . $size, true );
		}
		header( 'Content-Length: ' . $length, true );
		$fp = @fopen( $abs, 'rb' );
		if ( $fp === false ) {
			status_header( 500 );
			exit;
		}
		if ( $start > 0 ) {
			@fseek( $fp, $start );
		}
		$left = $length;
		while ( $left > 0 && ! feof( $fp ) ) {
			$chunk = fread( $fp, $left > 8192 ? 8192 : $left );
			if ( $chunk === false ) { break; }
			echo $chunk; // binario dell allegato
			@flush();
			$left -= strlen( $chunk );
		}
		fclose( $fp );
		exit;
	}

	public static function rest_delete( WP_REST_Request $request ) {
		$id = (int) $request['id'];
		if ( $id <= 0 ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'invalid_id' ), 400 );
		}
		$r = Rdacademy_Uploads_Entity::delete( $id );
		if ( ! $r['ok'] ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => $r['error'], 'message' => 'Allegato non eliminato.' ), self::map_error_to_http( $r['error'] ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'upload_id' => $id, 'file' => $r['file'] ), 200 );
	}

	public static function rest_meta( WP_REST_Request $request ) {
		$row = Rdacademy_Uploads_Entity::get( (int) $request['id'] );
		if ( $row === null ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'not_found' ), 404 );
		}
		$u = self::utente();
		if ( ! $u ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'unauthenticated' ), 401 );
		}
		if ( ! Rdacademy_Uploads_Entity::puo( 'vedere', (string) $row['entity_type'], (int) $row['entity_id'], $u ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => 'forbidden' ), 403 );
		}
		return new WP_REST_Response( array( 'ok' => true, 'meta' => self::riga_pubblica( $row ) ), 200 );
	}

	private static function map_error_to_http( string $code ): int {
		if ( $code === 'unauthenticated' ) { return 401; }
		if ( $code === 'permission_denied' || $code === 'forbidden' ) { return 403; }
		if ( $code === 'entity_not_found' || $code === 'not_found' ) { return 404; }
		if ( strpos( $code, 'db_error' ) === 0 || $code === 'mkdir_failed' || $code === 'move_failed' || $code === 'sha256_failed' || $code === 'soft_delete_failed' ) { return 500; }
		return 400;
	}
}
