<?php
/**
 * Rdacademy_Uploads_Entity — IL MOTORE DEGLI ALLEGATI, VALIDO PER QUALUNQUE OGGETTO.
 * -----------------------------------------------------------------
 * Copiato da Reldox_Uploads_Entity (pkg-anagrafica-1b2), RelDox CRD 3.55.09G, e ridotto.
 *
 * Modello blob+attachment content-addressable (copiato):
 *  - 1 file fisico (blob) = 1 SHA-256 = 1 riga in `uploads_blob`
 *  - N allegati logici (`uploads`) riferiscono lo stesso blob
 *  - ref_count conta i riferimenti VIVI
 *
 * Metodi pubblici: attach(), list_for_entity(), get(), delete(),
 * prepare_download(), entita(), unlink_blob_file_guarded().
 *
 * IL REGISTRO DEGLI OGGETTI (NUOVO, al posto di SUPPORTED_ENTITY_TYPES,
 * SCOPE_RULES, ENTITY_TABLE_MAP del CRM — azienda, contatto, documento,
 * offerta, task, progetto, articolo): il filtro `rdacademy_uploads_entita` dichiara
 * per ogni tipo la tabella dove l oggetto vive (nome nudo: la residenza la
 * risolve Rdacademy_Table_Owner::t()), gli scope ammessi e chi lo VEDE e chi lo
 * MODIFICA (funzioni ( int $id, array $utente ): bool). Il guscio dichiara un
 * solo tipo, `utente` (lo vede e lo modifica l amministratore o l utente
 * stesso): è la prova del motore, senza interfacce nuove.
 *
 * ⛔ CORRETTO / NUOVO RISPETTO A RELDOX, DICHIARATO:
 *  - IL MIME SI LEGGE DAL CONTENUTO (REGOLE § 3.9): RelDox verificava il MIME
 *    reale ma poi salvava quello CANONICO DELL ESTENSIONE, e accettava
 *    QUALUNQUE estensione in elenco quando finfo diceva octet-stream
 *    («fallback universale»). Qui: MIME vuoto = rifiuto; octet-stream solo dove
 *    l estensione lo prevede esplicitamente; nel registro va il MIME LETTO.
 *  - html/htm tolti dall elenco (un allegato non diventa una pagina del sito).
 *  - CANCELLARE CANCELLA ANCHE IL FILE (REGOLE § 3.9): RelDox marcava
 *    deleted_at e lasciava ref_count e file intatti. Qui il riferimento cala e,
 *    all ULTIMO, il file e la riga del blob se ne vanno insieme (con la guardia
 *    di percorso realpath copiata da unlink_blob_file_guarded()).
 *  - l identità viene SOLO dalla sessione del prodotto (Rdacademy_Auth), mai
 *    dall utente WordPress (SPEC § 2.1).
 * TAGLI: entity_master_id e la verifica della coppia id↔UUID (vedi lo schema),
 * attach_local() (import IMAP/EML del CRM), copy_all_for_entity() (Duplica del
 * CRM), surface/immagini del corpo e il loro spazzino, storage_used_by_entity(),
 * Reldox_Audit (il registro è Rdacademy_Auth::log_activity), backdoor logging.
 * Restano i ganci `rdacademy_pre_attach` (filtro, può rifiutare con WP_Error) e
 * `rdacademy_post_attach` (azione).
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Uploads_Entity {

	const ALLOWED_SCOPES = array( 'internal', 'external' );

	/** Dimensione massima (copiato: 20 MB). */
	const MAX_SIZE_BYTES = 20 * 1024 * 1024;

	/** Elenco bianco per estensione (copiato da RelDox, tolti html/htm). */
	const ALLOWED_MIME = array(
		'pdf' => 'application/pdf', 'doc' => 'application/msword',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ppt' => 'application/vnd.ms-powerpoint', 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'odt' => 'application/vnd.oasis.opendocument.text', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		'odp' => 'application/vnd.oasis.opendocument.presentation',
		'txt' => 'text/plain', 'csv' => 'text/csv', 'xml' => 'application/xml', 'md' => 'text/markdown', 'log' => 'text/plain',
		'json' => 'application/json', 'rtf' => 'application/rtf',
		'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp',
		'heic' => 'image/heic', 'heif' => 'image/heif', 'bmp' => 'image/bmp', 'tif' => 'image/tiff', 'tiff' => 'image/tiff',
		'zip' => 'application/zip', '7z' => 'application/x-7z-compressed', 'gz' => 'application/gzip',
		'msg' => 'application/vnd.ms-outlook', 'eml' => 'message/rfc822',
		'p7m' => 'application/pkcs7-mime', 'p7s' => 'application/pkcs7-signature',
		'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'mp4' => 'video/mp4', 'mov' => 'video/quicktime',
	);

	/**
	 * Varianti del MIME LETTO accettate per estensione (copiato da
	 * mime_is_acceptable() di RelDox, senza il fallback universale octet-stream).
	 */
	const VARIANTI = array(
		'application/pdf'  => array(),
		'image/jpeg'       => array( 'image/jpg' ),
		'image/webp'       => array(),
		'image/heic'       => array( 'image/heif', 'image/heic-sequence' ),
		'image/heif'       => array( 'image/heic', 'image/heif-sequence' ),
		'image/bmp'        => array( 'image/x-ms-bmp' ),
		'image/tiff'       => array( 'image/x-tiff' ),
		'application/zip'  => array( 'application/x-zip-compressed', 'application/x-zip' ),
		'application/x-7z-compressed' => array( 'application/x-7z' ),
		'application/gzip' => array( 'application/x-gzip' ),
		'text/csv'         => array( 'text/plain', 'application/csv' ),
		'text/plain'       => array(),
		'text/markdown'    => array( 'text/plain' ),
		'application/json' => array( 'text/plain' ),
		'application/rtf'  => array( 'text/rtf' ),
		'application/xml'  => array( 'text/xml' ),
		'message/rfc822'   => array( 'text/plain' ),
		'application/vnd.ms-outlook'    => array( 'application/cdfv2', 'application/x-ole-storage', 'application/octet-stream' ),
		'application/msword'            => array( 'application/cdfv2', 'application/x-ole-storage' ),
		'application/vnd.ms-excel'      => array( 'application/cdfv2', 'application/x-ole-storage' ),
		'application/vnd.ms-powerpoint' => array( 'application/cdfv2', 'application/x-ole-storage' ),
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => array( 'application/zip' ),
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => array( 'application/zip' ),
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => array( 'application/zip' ),
		'application/vnd.oasis.opendocument.text'         => array( 'application/zip' ),
		'application/vnd.oasis.opendocument.spreadsheet'  => array( 'application/zip' ),
		'application/vnd.oasis.opendocument.presentation' => array( 'application/zip' ),
		'application/pkcs7-mime'      => array( 'application/x-pkcs7-mime', 'application/pkcs7-signature', 'application/octet-stream' ),
		'application/pkcs7-signature' => array( 'application/x-pkcs7-signature', 'application/octet-stream' ),
		'audio/mpeg'      => array( 'audio/mp3' ),
		'audio/wav'       => array( 'audio/x-wav', 'audio/wave' ),
		'video/quicktime' => array( 'video/mp4' ),
	);

	/* ══ IL REGISTRO DEGLI OGGETTI ══ */

	/**
	 * @return array<string,array{tabella:string,scope:string[],vedere:callable,modificare:callable}>
	 */
	public static function entita(): array {
		$in  = (array) apply_filters( 'rdacademy_uploads_entita', array() );
		$out = array();
		foreach ( $in as $tipo => $d ) {
			$tipo = (string) $tipo;
			if ( ! preg_match( '/^[a-z][a-z0-9_]{1,31}$/', $tipo ) || ! is_array( $d ) ) { continue; }
			$tab = Rdacademy_Table_Owner::short( (string) ( $d['tabella'] ?? '' ) );
			if ( $tab === '' || ! is_callable( $d['vedere'] ?? null ) || ! is_callable( $d['modificare'] ?? null ) ) { continue; }
			$sc = array_values( array_intersect( (array) ( $d['scope'] ?? array( 'internal' ) ), self::ALLOWED_SCOPES ) );
			$out[ $tipo ] = array( 'tabella' => $tab, 'scope' => $sc ?: array( 'internal' ), 'vedere' => $d['vedere'], 'modificare' => $d['modificare'] );
		}
		return $out;
	}

	/** L utente della SESSIONE del prodotto (mai WordPress). */
	private static function utente(): ?array {
		$u = class_exists( 'Rdacademy_Auth' ) ? Rdacademy_Auth::get_current_user() : null;
		return ( is_array( $u ) && ! empty( $u['id'] ) ) ? $u : null;
	}

	/** L oggetto esiste? (al posto della verifica della coppia id↔UUID). */
	private static function oggetto_esiste( string $tipo, int $id ): bool {
		$e = self::entita()[ $tipo ] ?? null;
		if ( ! $e || $id <= 0 ) {
			return false;
		}
		$row = Rdacademy_DB::get_row( 'SELECT id FROM ' . Rdacademy_Table_Owner::t( $e['tabella'] ) . ' WHERE id = ?', array( $id ), 'i' );
		return is_array( $row );
	}

	public static function puo( string $verbo, string $tipo, int $id, ?array $u ): bool {
		$e = self::entita()[ $tipo ] ?? null;
		if ( ! $e || ! $u ) {
			return false;
		}
		try {
			return (bool) call_user_func( $e[ $verbo === 'modificare' ? 'modificare' : 'vedere' ], $id, $u );
		} catch ( Throwable $ex ) {
			return false;
		}
	}

	/* ══ SEZIONE 1 — ATTACH ══ */

	/**
	 * Carica un file e crea un allegato (deduplicato via SHA-256). Flusso copiato
	 * da RelDox (validazione tipo/id/scope, oggetto esistente, $_FILES, dimensione,
	 * estensione, MIME reale, permesso, gancio pre, SHA-256, blob nuovo o
	 * riusato, riga allegato, registro, gancio post).
	 */
	public static function attach( string $entity_type, int $entity_id, string $scope, array $file, bool $verifica_upload = true ): array {
		$ent = self::entita()[ $entity_type ] ?? null;
		if ( ! $ent ) {
			return self::error_result( 'unsupported_entity_type', sprintf( 'Tipo di oggetto «%s» non dichiarato.', $entity_type ) );
		}
		if ( $entity_id <= 0 ) {
			return self::error_result( 'invalid_entity_id', 'entity_id deve essere > 0.' );
		}
		if ( ! in_array( $scope, $ent['scope'], true ) ) {
			return self::error_result( 'scope_not_allowed', sprintf( 'scope «%s» non ammesso per «%s».', $scope, $entity_type ) );
		}
		$u = self::utente();
		if ( ! $u ) {
			return self::error_result( 'unauthenticated', 'Utente non autenticato.' );
		}
		if ( ! self::oggetto_esiste( $entity_type, $entity_id ) ) {
			return self::error_result( 'entity_not_found', sprintf( 'Oggetto %s id=%d non trovato.', $entity_type, $entity_id ) );
		}
		if ( ! self::puo( 'modificare', $entity_type, $entity_id, $u ) ) {
			return self::error_result( 'permission_denied', 'Permesso di modifica negato su questo oggetto.' );
		}
		if ( ! isset( $file['error'], $file['tmp_name'], $file['name'], $file['size'] ) ) {
			return self::error_result( 'invalid_file_array', 'Struttura del file incompleta.' );
		}
		if ( (int) $file['error'] !== UPLOAD_ERR_OK ) {
			return self::error_result( 'upload_error_' . (int) $file['error'], 'Caricamento non riuscito (codice PHP ' . (int) $file['error'] . ').' );
		}
		// Anti-spoofing (copiato): deve essere un caricamento PHP vero.
		if ( $verifica_upload && ! is_uploaded_file( $file['tmp_name'] ) ) {
			return self::error_result( 'not_uploaded_file', 'Il file non è un caricamento PHP reale.' );
		}
		$size = (int) $file['size'];
		if ( $size <= 0 ) {
			return self::error_result( 'empty_file', 'File vuoto.' );
		}
		if ( $size > self::MAX_SIZE_BYTES ) {
			return self::error_result( 'size_exceeded', sprintf( 'Dimensione %d byte oltre il limite di %d byte.', $size, self::MAX_SIZE_BYTES ) );
		}
		$filename_original = sanitize_file_name( wp_basename( (string) $file['name'] ) );
		$filename_original = mb_substr( $filename_original, 0, 255 );
		$pos = strrpos( $filename_original, '.' );
		if ( $filename_original === '' || $pos === false ) {
			return self::error_result( 'invalid_extension', 'File senza estensione.' );
		}
		$ext = strtolower( substr( $filename_original, $pos + 1 ) );
		if ( ! isset( self::ALLOWED_MIME[ $ext ] ) ) {
			return self::error_result( 'invalid_extension', sprintf( 'Estensione «%s» non ammessa.', $ext ) );
		}
		// ⛔ IL MIME SI LEGGE DAL CONTENUTO.
		$mime_real = self::detect_mime( (string) $file['tmp_name'] );
		$atteso    = self::ALLOWED_MIME[ $ext ];
		if ( $mime_real === '' ) {
			return self::error_result( 'invalid_mime', 'Tipo del contenuto non leggibile: file rifiutato.' );
		}
		if ( $mime_real !== $atteso && ! in_array( $mime_real, self::VARIANTI[ $atteso ] ?? array(), true ) ) {
			return self::error_result( 'invalid_mime', sprintf( 'Il contenuto è «%s», non corrisponde all’estensione «%s» (atteso «%s»).', $mime_real, $ext, $atteso ) );
		}
		$mime = $mime_real;

		$pre = apply_filters( 'rdacademy_pre_attach', null, $entity_type, $entity_id, $size, (int) $u['id'] );
		if ( $pre instanceof WP_Error ) {
			return self::error_result( (string) $pre->get_error_code(), (string) $pre->get_error_message() );
		}

		$sha256 = hash_file( 'sha256', (string) $file['tmp_name'] );
		if ( $sha256 === false || strlen( $sha256 ) !== 64 ) {
			return self::error_result( 'sha256_failed', 'Calcolo SHA-256 non riuscito.' );
		}
		$p = RDACADEMY_TABLE_PREFIX;
		$existing = Rdacademy_DB::get_row( "SELECT id, storage_path FROM `{$p}uploads_blob` WHERE sha256 = ?", array( $sha256 ), 's' );
		if ( Rdacademy_DB::last_sql_error() !== null ) {
			return self::error_result( 'db_error_select_blob', 'Errore del database durante la ricerca del blob.' );
		}
		$base_abs = Rdacademy_Schema_Uploads_Blob_V1::ensure_storage_dir();
		if ( $existing !== null && is_file( $base_abs . '/' . (string) $existing['storage_path'] ) ) {
			// Path A — blob già presente: un riferimento in più.
			$blob_id      = (int) $existing['id'];
			$blob_was_new = false;
			Rdacademy_DB::execute( "UPDATE `{$p}uploads_blob` SET ref_count = ref_count + 1 WHERE id = ?", array( $blob_id ), 'i' );
		} else {
			// Path B — blob nuovo (o riga orfana del suo file): file nello shard + riga.
			$blob_was_new = true;
			$s1 = substr( $sha256, 0, 2 );
			$s2 = substr( $sha256, 2, 2 );
			$storage_path = $s1 . '/' . $s2 . '/' . $sha256 . '.bin';
			$shard_abs    = $base_abs . '/' . $s1 . '/' . $s2;
			$file_abs     = $base_abs . '/' . $storage_path;
			if ( ! file_exists( $shard_abs ) && ! wp_mkdir_p( $shard_abs ) ) {
				return self::error_result( 'mkdir_failed', 'Impossibile creare la cartella dello shard.' );
			}
			Rdacademy_Schema_Uploads_Blob_V1::ensure_shard_htaccess( $base_abs . '/' . $s1 );
			$moved = $verifica_upload ? @move_uploaded_file( $file['tmp_name'], $file_abs ) : @copy( $file['tmp_name'], $file_abs );
			if ( ! $moved ) {
				return self::error_result( 'move_failed', 'Spostamento del file nello storage non riuscito.' );
			}
			@chmod( $file_abs, 0644 );
			if ( $existing !== null ) {
				// riga senza file (file tolto a mano): si rimette il file e si riallinea la riga
				$blob_id = (int) $existing['id'];
				Rdacademy_DB::execute( "UPDATE `{$p}uploads_blob` SET ref_count = ref_count + 1, storage_path = ?, mime_type = ?, size_bytes = ? WHERE id = ?",
					array( $storage_path, $mime, $size, $blob_id ), 'ssii' );
			} else {
				try {
					$blob_id = Rdacademy_DB::insert( "INSERT INTO `{$p}uploads_blob` (sha256, mime_type, size_bytes, storage_path, ref_count, created_by) VALUES (?, ?, ?, ?, 1, ?)",
						array( $sha256, $mime, $size, $storage_path, (int) $u['id'] ), 'ssisi' );
				} catch ( Throwable $e ) {
					$blob_id = 0;
				}
				if ( $blob_id <= 0 ) {
					@unlink( $file_abs ); // nessuna riga per questo file: si toglie (copiato)
					return self::error_result( 'db_error_insert_blob', 'Errore del database durante la scrittura del blob.' );
				}
			}
		}

		$uploaded_at = gmdate( 'Y-m-d H:i:s' ) . '.' . sprintf( '%06d', (int) ( fmod( microtime( true ), 1 ) * 1000000 ) );
		$upload_id   = 0;
		try {
			$upload_id = Rdacademy_DB::insert(
				"INSERT INTO `{$p}uploads` (entity_type, entity_id, scope, blob_id, filename_original, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
				array( $entity_type, $entity_id, $scope, $blob_id, $filename_original, (int) $u['id'], $uploaded_at ), 'sisisis'
			);
		} catch ( Throwable $e ) {
			$upload_id = 0;
		}
		if ( $upload_id <= 0 ) {
			self::rilascia_blob( $blob_id ); // compensazione: il riferimento appena preso si restituisce
			return self::error_result( 'db_error_insert_upload', 'Errore del database durante la scrittura dell’allegato.' );
		}
		Rdacademy_Auth::log_activity( (int) $u['id'], 'allegati', 'attach',
			sprintf( 'Allegato «%s» su %s #%d (%s, %d byte)%s', $filename_original, $entity_type, $entity_id, $mime, $size, $blob_was_new ? '' : ' — contenuto già presente' ) );
		do_action( 'rdacademy_post_attach', $upload_id, $blob_id, $blob_was_new, $entity_type, $entity_id );

		return array(
			'success' => true, 'upload_id' => $upload_id, 'blob_id' => $blob_id, 'blob_was_new' => $blob_was_new,
			'error' => null, 'error_message' => null,
			'meta' => array( 'filename_original' => $filename_original, 'mime_type' => $mime, 'size_bytes' => $size,
				'scope' => $scope, 'uploaded_at' => $uploaded_at, 'sha256' => $sha256 ),
		);
	}

	/* ══ SEZIONE 2 — LISTA E LETTURA ══ */

	public static function list_for_entity( string $entity_type, int $entity_id, ?string $scope = null, bool $include_deleted = false ): array {
		if ( $entity_type === '' || $entity_id <= 0 ) {
			return array();
		}
		$p      = RDACADEMY_TABLE_PREFIX;
		$where  = array( 'u.entity_type = ?', 'u.entity_id = ?' );
		$params = array( $entity_type, $entity_id );
		$types  = 'si';
		if ( ! $include_deleted ) {
			$where[] = 'u.deleted_at IS NULL';
		}
		if ( $scope !== null ) {
			$where[]  = 'u.scope = ?';
			$params[] = $scope;
			$types   .= 's';
		}
		return Rdacademy_DB::get_results(
			"SELECT u.id, u.entity_type, u.entity_id, u.scope, u.blob_id, u.filename_original, u.uploaded_by, u.uploaded_at,
			        u.deleted_at, u.deleted_by, b.mime_type, b.size_bytes, b.sha256
			   FROM `{$p}uploads` u LEFT JOIN `{$p}uploads_blob` b ON b.id = u.blob_id
			  WHERE " . implode( ' AND ', $where ) . ' ORDER BY u.uploaded_at DESC, u.id DESC',
			$params, $types
		);
	}

	public static function get( int $upload_id ): ?array {
		if ( $upload_id <= 0 ) {
			return null;
		}
		$p = RDACADEMY_TABLE_PREFIX;
		return Rdacademy_DB::get_row(
			"SELECT u.id, u.entity_type, u.entity_id, u.scope, u.blob_id, u.filename_original, u.uploaded_by, u.uploaded_at,
			        u.deleted_at, u.deleted_by, b.mime_type, b.size_bytes, b.sha256, b.storage_path, b.ref_count
			   FROM `{$p}uploads` u LEFT JOIN `{$p}uploads_blob` b ON b.id = u.blob_id WHERE u.id = ?",
			array( $upload_id ), 'i'
		);
	}

	/* ══ SEZIONE 3 — CANCELLAZIONE (riga, riferimento e, all ultimo, file) ══ */

	/**
	 * @return array{ok:bool,error:string,file:string} file: deleted|kept_shared|already_gone|''
	 */
	public static function delete( int $upload_id ): array {
		$u   = self::utente();
		if ( ! $u ) {
			return array( 'ok' => false, 'error' => 'unauthenticated', 'file' => '' );
		}
		$row = self::get( $upload_id );
		if ( $row === null ) {
			return array( 'ok' => false, 'error' => 'not_found', 'file' => '' );
		}
		if ( ! empty( $row['deleted_at'] ) ) {
			return array( 'ok' => true, 'error' => '', 'file' => 'already_gone' ); // idempotente (copiato)
		}
		if ( ! self::puo( 'modificare', (string) $row['entity_type'], (int) $row['entity_id'], $u ) ) {
			return array( 'ok' => false, 'error' => 'forbidden', 'file' => '' );
		}
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::clear_last_error();
		$n = Rdacademy_DB::execute( "UPDATE `{$p}uploads` SET deleted_at = ?, deleted_by = ? WHERE id = ? AND deleted_at IS NULL",
			array( gmdate( 'Y-m-d H:i:s' ), (int) $u['id'], $upload_id ), 'sii' );
		if ( $n !== 1 ) {
			return array( 'ok' => false, 'error' => 'soft_delete_failed', 'file' => '' );
		}
		$esito = self::rilascia_blob( (int) $row['blob_id'] );
		Rdacademy_Auth::log_activity( (int) $u['id'], 'allegati', 'delete',
			sprintf( 'Allegato «%s» tolto da %s #%d — file: %s', (string) $row['filename_original'], (string) $row['entity_type'], (int) $row['entity_id'], $esito ) );
		return array( 'ok' => true, 'error' => '', 'file' => $esito );
	}

	/**
	 * Restituisce un riferimento al blob. All ULTIMO, file e riga del blob se ne
	 * vanno insieme (regola copiata da hard_delete_body_image()). Se il file non
	 * si toglie, il DB non si tocca: non si perde il puntatore a un file presente.
	 */
	private static function rilascia_blob( int $blob_id ): string {
		if ( $blob_id <= 0 ) {
			return 'already_gone';
		}
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::execute( "UPDATE `{$p}uploads_blob` SET ref_count = ref_count - 1 WHERE id = ? AND ref_count > 0", array( $blob_id ), 'i' );
		$b = Rdacademy_DB::get_row( "SELECT ref_count, storage_path, sha256 FROM `{$p}uploads_blob` WHERE id = ?", array( $blob_id ), 'i' );
		if ( ! $b ) {
			return 'already_gone';
		}
		if ( (int) $b['ref_count'] > 0 ) {
			return 'kept_shared';
		}
		$del = self::unlink_blob_file_guarded( (string) $b['storage_path'], (string) $b['sha256'] );
		if ( $del === 'rejected_path' || $del === 'unlink_failed' ) {
			error_log( '[RelDoxAcademy Uploads] blob ' . $blob_id . ' non tolto dal disco: ' . $del );
			return $del;
		}
		Rdacademy_DB::execute( "DELETE FROM `{$p}uploads_blob` WHERE id = ? AND ref_count = 0", array( $blob_id ), 'i' );
		Rdacademy_DB::execute( "UPDATE `{$p}uploads` SET blob_id = 0 WHERE blob_id = ? AND deleted_at IS NOT NULL", array( $blob_id ), 'i' );
		self::pota_shard( (string) $b['storage_path'] );
		return $del === 'already_gone' ? 'already_gone' : 'deleted';
	}

	/** Copiato senza modifiche di sostanza: pattern shardato, coerenza col sha256, confinamento realpath. */
	public static function unlink_blob_file_guarded( string $storage_path, string $sha256 ): string {
		if ( ! preg_match( '#^[0-9a-f]{2}/[0-9a-f]{2}/[0-9a-f]{64}\.bin$#', $storage_path ) ) {
			return 'rejected_path';
		}
		if ( $sha256 !== '' && strtolower( substr( $storage_path, -68, 64 ) ) !== strtolower( $sha256 ) ) {
			return 'rejected_path';
		}
		$base_abs = Rdacademy_Schema_Uploads_Blob_V1::base_dir();
		$abs      = $base_abs . '/' . $storage_path;
		if ( ! file_exists( $abs ) ) {
			return 'already_gone';
		}
		$real_base = realpath( $base_abs );
		$real_file = realpath( $abs );
		if ( $real_base === false || $real_file === false || strpos( $real_file, rtrim( $real_base, '/' ) . '/' ) !== 0 ) {
			return 'rejected_path';
		}
		return @unlink( $abs ) ? 'deleted' : 'unlink_failed';
	}

	/** Toglie le due cartelle di shard del blob se sono rimaste vuote (da prune_empty_blob_shards(), ridotto al blob). */
	private static function pota_shard( string $storage_path ): void {
		$base = realpath( Rdacademy_Schema_Uploads_Blob_V1::base_dir() );
		if ( ! $base || ! preg_match( '#^([0-9a-f]{2})/([0-9a-f]{2})/#', $storage_path, $m ) ) {
			return;
		}
		foreach ( array( $base . '/' . $m[1] . '/' . $m[2], $base . '/' . $m[1] ) as $d ) {
			$k = @scandir( $d );
			if ( is_array( $k ) && ! array_diff( $k, array( '.', '..', '.htaccess', 'web.config', 'index.php' ) ) ) {
				foreach ( array( '.htaccess', 'web.config', 'index.php' ) as $f ) { @unlink( $d . '/' . $f ); }
				@rmdir( $d );
			}
		}
	}

	/* ══ SEZIONE 4 — PREPARE DOWNLOAD ══ */

	public static function prepare_download( int $upload_id ): array {
		$fail = static function ( int $h, string $r ): array {
			return array( 'ok' => false, 'http_code' => $h, 'reason' => $r, 'meta' => null, 'absolute_path' => null );
		};
		if ( $upload_id <= 0 ) {
			return $fail( 400, 'invalid_id' );
		}
		$u = self::utente();
		if ( ! $u ) {
			return $fail( 401, 'unauthenticated' );
		}
		$row = self::get( $upload_id );
		if ( $row === null ) {
			return $fail( 404, 'not_found' );
		}
		if ( ! self::puo( 'vedere', (string) $row['entity_type'], (int) $row['entity_id'], $u ) ) {
			return $fail( 403, 'forbidden' ); // prima del 410: chi non vede l oggetto non sa nemmeno che l allegato c era
		}
		if ( ! empty( $row['deleted_at'] ) ) {
			return $fail( 410, 'gone' );
		}
		$sp = (string) ( $row['storage_path'] ?? '' );
		if ( ! preg_match( '#^[0-9a-f]{2}/[0-9a-f]{2}/[0-9a-f]{64}\.bin$#', $sp ) ) {
			return $fail( 500, 'blob_missing' );
		}
		$abs = Rdacademy_Schema_Uploads_Blob_V1::base_dir() . '/' . $sp;
		if ( ! is_file( $abs ) ) {
			error_log( '[RelDoxAcademy Uploads] file fisico mancante per allegato ' . $upload_id );
			return $fail( 404, 'file_missing' );
		}
		return array(
			'ok' => true, 'http_code' => 200, 'reason' => null,
			'meta' => array( 'filename_original' => (string) $row['filename_original'], 'mime_type' => (string) $row['mime_type'],
				'size_bytes' => (int) $row['size_bytes'], 'sha256' => (string) $row['sha256'], 'scope' => (string) $row['scope'] ),
			'absolute_path' => $abs,
		);
	}

	/* ══ SEZIONE 5 — HELPER ══ */

	/** MIME reale dal contenuto (copiato: finfo, poi mime_content_type, poi ''). */
	public static function detect_mime( string $path ): string {
		if ( ! is_file( $path ) ) {
			return '';
		}
		if ( function_exists( 'finfo_open' ) ) {
			$fi = @finfo_open( FILEINFO_MIME_TYPE );
			if ( $fi !== false ) {
				$m = @finfo_file( $fi, $path );
				@finfo_close( $fi );
				if ( is_string( $m ) && $m !== '' ) {
					return strtolower( $m );
				}
			}
		}
		if ( function_exists( 'mime_content_type' ) ) {
			$m = @mime_content_type( $path );
			if ( is_string( $m ) && $m !== '' ) {
				return strtolower( $m );
			}
		}
		return '';
	}

	private static function error_result( string $code, string $message ): array {
		return array( 'success' => false, 'upload_id' => null, 'blob_id' => null, 'blob_was_new' => null,
			'error' => $code, 'error_message' => $message, 'meta' => null );
	}

	/**
	 * NUOVO — per l azzeramento dei dati operativi (Impostazioni › Pericolo):
	 * i percorsi di TUTTI i blob, letti prima che le tabelle si svuotino, e la
	 * loro rimozione dopo. «Un distruttivo che cancella righe cancella anche i file.»
	 */
	public static function percorsi_blob(): array {
		$out = array();
		foreach ( Rdacademy_DB::get_results( 'SELECT storage_path, sha256 FROM `' . RDACADEMY_TABLE_PREFIX . 'uploads_blob`' ) as $r ) {
			$out[] = array( (string) $r['storage_path'], (string) $r['sha256'] );
		}
		return $out;
	}

	public static function togli_file( array $percorsi ): int {
		$n = 0;
		foreach ( $percorsi as $x ) {
			if ( self::unlink_blob_file_guarded( (string) $x[0], (string) $x[1] ) === 'deleted' ) {
				$n++;
				self::pota_shard( (string) $x[0] );
			}
		}
		return $n;
	}
}
