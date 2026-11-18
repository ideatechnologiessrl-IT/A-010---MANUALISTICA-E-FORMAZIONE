<?php
/**
 * Rdacademy_Database — LE AZIONI DI IMPOSTAZIONI › DATABASE (canale dell app, livello `admin`).
 * =============================================================================
 * Copiate da Reldox_Router, RelDox CRD 3.55.09G: db_source_param(),
 * db_conn_for_source(), ajax_datasource_get/save/test/reset (r.3746-4078),
 * capacity_aggregate() + stats_expiry_zero/restore() + q_due_modi() +
 * frag_pct() + fmt_bytes_short() (r.15962-16497), ajax_db_capacity (r.16505),
 * ajax_db_quota_save, ajax_db_optimize (r.16669), ajax_backup_run/list,
 * ajax_restore_preview/apply (r.16904-16985).
 *
 * CORREZIONI E DIFFERENZE, DICHIARATE:
 *  - ⛔ datasource_save/quota_save PREPARATE (in RelDox real_escape_string
 *    concatenato in un UPSERT costruito a mano).
 *  - ⛔ datasource_test: in RelDox, a password vuota, si usava la password
 *    SALVATA anche con host e utente CAMBIATI nel modulo: un amministratore (o
 *    chi ne ruba la sessione) poteva far recapitare la password del database a
 *    un server qualsiasi. Qui la password salvata si usa SOLO verso lo stesso
 *    host, porta e utente salvati.
 *  - NUOVO: rifiuto del database di WordPress, del BASE e di un altra sorgente
 *    (Rdacademy_DataSource::rifiuto_database), in test e in salvataggio.
 *  - Salvare = IN USO (SVILUPPO 33 di RelDox: due stati soli); la transizione
 *    verso IN USO provisiona; se non riesce la sorgente torna spenta.
 *  - db_capacity / db_optimize su una sorgente NON IN USO rispondono «ricade sul
 *    BASE» invece di misurare il BASE sotto il nome della sorgente (in RelDox
 *    db_conn_for_source() ripiegava in silenzio e il pannello mostrava i numeri
 *    del BASE etichettati come sorgente).
 *  - TAGLI: db_health, run_migrations, dbconfig_copy_block (la password del BASE
 *    non lascia mai il server, nemmeno negli appunti), il fondo di allocazione
 *    misurato (alloc_floor_*: magazzino del CRM sul BASE), la modale della quota
 *    (qui un campo nella scheda).
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Database {

	private static function guardia( ?array $cur ): array {
		if ( ! $cur || ! Rdacademy_Router::user_can_admin( $cur ) ) {
			wp_send_json_error( 'Accesso negato', 403 );
		}
		return $cur;
	}

	/** Copiato: 'base' di default, ogni valore sconosciuto ricade su 'base' (mai una sorgente arbitraria). */
	private static function source_param(): string {
		$s = strtolower( trim( (string) wp_unslash( $_POST['source'] ?? 'base' ) ) );
		return ( $s !== 'base' && ! Rdacademy_Sorgenti::esiste( $s ) ) ? 'base' : $s;
	}

	private static function key_param(): string {
		$k = strtolower( trim( (string) wp_unslash( $_POST['source_key'] ?? '' ) ) );
		if ( ! Rdacademy_Sorgenti::esiste( $k ) ) {
			wp_send_json_error( 'Sorgente non valida.' );
		}
		return $k;
	}

	/**
	 * La connessione della sorgente richiesta, oppure null se la sorgente NON
	 * è in uso col suo database (CORRETTO: niente ripiego muto per le misure).
	 */
	private static function conn_misura( string $source ): ?mysqli {
		if ( $source === 'base' ) {
			return Rdacademy_DB::get_connection();
		}
		if ( ! Rdacademy_DataSource::is_active( $source ) ) {
			return null;
		}
		$c = Rdacademy_DataSource::get( $source );
		return ( $c === Rdacademy_DB::get_connection() ) ? null : $c;
	}

	/* ══ PANORAMICA (NUOVO: lo stato di tutte le sorgenti in una risposta) ══ */

	public static function panoramica(): array {
		$cfg = Rdacademy_DB_Config::get_public();
		$out = array(
			'wordpress' => array( 'n' => 1, 'db_name' => defined( 'DB_NAME' ) ? (string) DB_NAME : '', 'db_host' => defined( 'DB_HOST' ) ? (string) DB_HOST : '' ),
			'base'      => array( 'n' => 2, 'db_name' => (string) $cfg['name'], 'db_host' => (string) $cfg['host'] ),
			'sorgenti'  => array(),
		);
		foreach ( Rdacademy_Sorgenti::chiavi() as $k ) {
			$out['sorgenti'][] = Rdacademy_DataSource::get_public( $k );
		}
		return $out;
	}

	public static function ajax_db_panoramica( ?array $cur ): void {
		self::guardia( $cur );
		wp_send_json_success( self::panoramica() );
	}

	/* ══ SORGENTI: get / test / save / reset ══ */

	public static function ajax_datasource_get( ?array $cur ): void {
		self::guardia( $cur );
		wp_send_json_success( Rdacademy_DataSource::get_public( self::key_param() ) );
	}

	/** I campi del modulo, normalizzati come in RelDox. */
	private static function campi(): array {
		$in = static function ( string $k ): string { return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $k ] ?? '' ) ) ) ); };
		$port = (int) ( $_POST['db_port'] ?? 3306 );
		if ( $port < 1 || $port > 65535 ) { $port = 3306; }
		$cs = preg_replace( '/[^a-z0-9_]/', '', strtolower( $in( 'charset' ) ) );
		return array(
			'host' => $in( 'db_host' ), 'port' => $port, 'name' => $in( 'db_name' ), 'user' => $in( 'db_user' ),
			'charset' => $cs !== '' ? $cs : 'utf8mb4',
			'pass' => (string) wp_unslash( $_POST['db_pass'] ?? '' ), // vuota = non cambiare
		);
	}

	public static function ajax_datasource_test( ?array $cur ): void {
		self::guardia( $cur );
		$key = self::key_param();
		$f   = self::campi();
		if ( $f['host'] === '' || $f['name'] === '' ) {
			wp_send_json_error( array( 'message' => 'Hostname e Nome database sono obbligatori per il test.' ), 200 );
		}
		$rif = Rdacademy_DataSource::rifiuto_database( $key, $f['host'], $f['name'] );
		if ( $rif !== '' ) {
			wp_send_json_error( array( 'message' => $rif, 'rifiuto' => true ), 200 );
		}
		if ( $f['pass'] === '' ) {
			// ⛔ La password salvata viaggia solo verso il server e l utente per cui è stata salvata.
			$s = Rdacademy_DataSource::connect_params( $key );
			if ( $s && $s['pass'] !== '' && Rdacademy_DataSource::norm_host( $s['host'] ) === Rdacademy_DataSource::norm_host( $f['host'] )
				&& (int) $s['port'] === (int) $f['port'] && $s['user'] === $f['user'] ) {
				$f['pass'] = $s['pass'];
			}
		}
		$res   = Rdacademy_DataSource::connetti( $f );
		$scrub = static function ( string $m ) use ( $f ): string { return $f['pass'] !== '' ? str_replace( $f['pass'], '[REDACTED]', $m ) : $m; };
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'message' => $scrub( (string) ( $res['error_message'] ?: 'Connessione non riuscita.' ) ), 'latency_ms' => $res['latency_ms'] ), 200 );
		}
		$conn = $res['connection'];
		$t0 = microtime( true ); $ok = false;
		try {
			$r = @$conn->query( 'SELECT 1' );
			if ( $r !== false ) { $ok = true; if ( $r instanceof mysqli_result ) { $r->free(); } }
		} catch ( mysqli_sql_exception $e ) {
			$ok = false;
		}
		$q_ms = round( ( microtime( true ) - $t0 ) * 1000, 2 );
		$srv  = (string) ( $conn->server_info ?? '' );
		$conn->close();
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => 'Connessione aperta ma SELECT 1 fallita. Verifica i permessi dell\'utente DB.', 'latency_ms' => $res['latency_ms'], 'query_ms' => $q_ms ), 200 );
		}
		wp_send_json_success( array( 'message' => 'Connessione OK', 'latency_ms' => $res['latency_ms'], 'query_ms' => $q_ms, 'server' => $srv ) );
	}

	/**
	 * Salva la sorgente e la porta IN USO. Password cifrata solo se fornita;
	 * vuota = resta quella salvata (copiato). La TRANSIZIONE verso IN USO
	 * provisiona; se non riesce la sorgente torna spenta e lo si dice (copiato,
	 * BUG-182 lotto 1).
	 */
	public static function ajax_datasource_save( ?array $cur ): void {
		$cur = self::guardia( $cur );
		$key = self::key_param();
		$f   = self::campi();
		if ( $f['host'] === '' || $f['name'] === '' ) {
			wp_send_json_error( 'Per mettere la sorgente IN USO servono almeno Hostname e Nome database.' );
		}
		$rif = Rdacademy_DataSource::rifiuto_database( $key, $f['host'], $f['name'] );
		if ( $rif !== '' ) {
			wp_send_json_error( array( 'message' => $rif, 'rifiuto' => true ) );
		}
		$prima      = Rdacademy_DataSource::get_row( $key );
		$era_in_uso = $prima && (int) ( $prima['enabled'] ?? 0 ) === 1;
		$cambia_db  = $prima && ( Rdacademy_DataSource::norm_host( (string) $prima['db_host'] ) !== Rdacademy_DataSource::norm_host( $f['host'] )
			|| (string) $prima['db_name'] !== $f['name'] || (int) $prima['db_port'] !== $f['port'] || (string) $prima['db_user'] !== $f['user'] );
		$enc = '';
		if ( $f['pass'] !== '' ) {
			$enc = Rdacademy_Cifra::chiudi( $f['pass'] );
			if ( $enc === '' ) {
				wp_send_json_error( 'Cifratura non riuscita: la sorgente non è stata salvata.' );
			}
		} elseif ( $cambia_db && $f['user'] !== '' ) {
			// ⛔ server o utente cambiati: la password vecchia non vale per il nuovo indirizzo.
			wp_send_json_error( 'Hai cambiato server, porta o utente: scrivi anche la password del nuovo database.' );
		}
		$t     = '`' . RDACADEMY_TABLE_PREFIX . Rdacademy_Schema_Datasources_V1::TABLE . '`';
		$label = Rdacademy_Sorgenti::nome( $key );
		Rdacademy_DB::clear_last_error();
		if ( $enc !== '' ) {
			Rdacademy_DB::execute(
				"INSERT INTO {$t} (source_key, label, db_host, db_port, db_name, db_user, db_pass_enc, charset, enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
				 ON DUPLICATE KEY UPDATE label = VALUES(label), db_host = VALUES(db_host), db_port = VALUES(db_port), db_name = VALUES(db_name),
				 db_user = VALUES(db_user), db_pass_enc = VALUES(db_pass_enc), charset = VALUES(charset), enabled = 1",
				array( $key, $label, $f['host'], $f['port'], $f['name'], $f['user'], $enc, $f['charset'] ), 'sssissss'
			);
		} else {
			Rdacademy_DB::execute(
				"INSERT INTO {$t} (source_key, label, db_host, db_port, db_name, db_user, db_pass_enc, charset, enabled) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, 1)
				 ON DUPLICATE KEY UPDATE label = VALUES(label), db_host = VALUES(db_host), db_port = VALUES(db_port), db_name = VALUES(db_name),
				 db_user = VALUES(db_user), charset = VALUES(charset), enabled = 1",
				array( $key, $label, $f['host'], $f['port'], $f['name'], $f['user'], $f['charset'] ), 'sssisss'
			);
		}
		if ( Rdacademy_DB::last_error() !== '' ) {
			wp_send_json_error( 'Salvataggio non riuscito: ' . Rdacademy_DB::last_error() );
		}
		Rdacademy_DataSource::flush_provisioning( $key );
		Rdacademy_Table_Owner::flush();

		$msg  = 'Sorgente salvata e IN USO.';
		$prov = null;
		if ( ! $era_in_uso || $cambia_db ) {
			$prov = Rdacademy_Sorgente_Provisioner::porta_in_uso( $key );
			if ( in_array( (string) $prov['esito'], Rdacademy_Sorgente_Provisioner::ESITI_KO, true ) ) {
				Rdacademy_DataSource::spegni( $key );
				Rdacademy_Auth::log_activity( (int) $cur['id'], 'database', 'datasource_spenta', $label . ': ' . (string) $prov['messaggio'] );
				wp_send_json_error( array(
					'message' => (string) $prov['messaggio'] . ' La sorgente è tornata NON IN USO.',
					'esito'   => (string) $prov['esito'],
					'config'  => Rdacademy_DataSource::get_public( $key ),
				) );
			}
			$msg = 'Sorgente salvata e IN USO. ' . (string) $prov['messaggio'];
		}
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'database', 'datasource_save',
			$label . ' IN USO su ' . $f['host'] . '/' . $f['name'] . ( $enc !== '' ? ' (password cambiata)' : '' ) );
		wp_send_json_success( array( 'message' => $msg, 'config' => Rdacademy_DataSource::get_public( $key ), 'esito' => $prov['esito'] ?? '' ) );
	}

	/**
	 * NON IN USO (copiato, SVILUPPO 33). Due guardie, in quest ordine:
	 *  1) RIGHE DENTRO → NEGATO, sempre (CORRETTO: righe, non tabelle vuote).
	 *  2) NON RAGGIUNGIBILE → negato alla prima chiamata con `confirm`; il
	 *     secondo giro arriva con force=1 dalla conferma in pagina.
	 */
	public static function ajax_datasource_reset( ?array $cur ): void {
		$cur   = self::guardia( $cur );
		$key   = self::key_param();
		$force = ( (int) ( $_POST['force'] ?? 0 ) === 1 );
		$occ   = Rdacademy_DataSource::occupancy( $key );
		if ( $occ['rows'] > 0 ) {
			$righe = array();
			foreach ( $occ['popolate'] as $t => $n ) { $righe[] = $t . ' (' . $n . ')'; }
			wp_send_json_error( array(
				'message' => 'Negato: il database della sorgente contiene dati del prodotto — ' . implode( ', ', $righe )
					. '. Azzerare la connessione toglierebbe l\'unica via per raggiungerli. Prima si spostano i dati.',
				'tables' => (int) $occ['tables'], 'rows' => (int) $occ['rows'],
			) );
		}
		if ( ! $occ['reachable'] && ! $force ) {
			wp_send_json_error( array( 'message' => 'Database non raggiungibile: non posso provare che sia vuoto.', 'confirm' => 'unreachable', 'detail' => (string) $occ['error'] ) );
		}
		if ( ! Rdacademy_DataSource::reset( $key ) ) {
			wp_send_json_error( 'Azzeramento non riuscito: ' . Rdacademy_DB::last_error() );
		}
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'database', 'datasource_reset', Rdacademy_Sorgenti::nome( $key ) . ' NON IN USO: configurazione azzerata' );
		wp_send_json_success( array( 'message' => 'Sorgente NON IN USO: configurazione azzerata, ricade sul BASE.', 'config' => Rdacademy_DataSource::get_public( $key ) ) );
	}

	/* ══ CAPACITÀ ══ */

	/** Copiato: la query che fallisce non lancia, dice perché. */
	private static function q_due_modi( mysqli $conn, string $sql, string &$err = '' ) {
		$err = '';
		try {
			$r = @$conn->query( $sql );
			if ( false === $r ) {
				$err = ( '' !== $conn->error ) ? $conn->error : 'interrogazione fallita';
			}
			return $r;
		} catch ( Throwable $e ) {
			$err = $e->getMessage() !== '' ? $e->getMessage() : 'interrogazione fallita';
			return false;
		}
	}

	/** Copiato (BUG-43 di RelDox): statistiche InnoDB fresche, sulla STESSA connessione. */
	public static function stats_expiry_zero( mysqli $conn ) {
		$err = '';
		$rv  = self::q_due_modi( $conn, 'SELECT @@SESSION.information_schema_stats_expiry', $err );
		if ( ! ( $rv instanceof mysqli_result ) ) { return null; }
		$rr = $rv->fetch_row();
		$rv->free();
		if ( ! isset( $rr[0] ) ) { return null; }
		$prev = (int) $rr[0];
		$ok   = self::q_due_modi( $conn, 'SET SESSION information_schema_stats_expiry = 0', $err );
		if ( false === $ok ) { return null; }
		return $prev;
	}

	public static function stats_expiry_restore( mysqli $conn, $prev ): void {
		if ( null === $prev ) { return; }
		$err = '';
		self::q_due_modi( $conn, 'SET SESSION information_schema_stats_expiry = ' . (int) $prev, $err );
	}

	public static function frag_pct( int $spreco, int $occupato ): float {
		$lordo = $occupato + $spreco;
		return ( $lordo > 0 ) ? round( $spreco * 100 / $lordo, 2 ) : 0.0;
	}

	private static function fmt_bytes_short( int $b ): string {
		if ( $b >= 1073741824 ) { return number_format( $b / 1073741824, 2, ',', '.' ) . ' GB'; }
		if ( $b >= 1048576 )    { return number_format( $b / 1048576, 2, ',', '.' ) . ' MB'; }
		if ( $b >= 1024 )       { return number_format( $b / 1024, 0, ',', '.' ) . ' KB'; }
		return $b . ' B';
	}

	/** Copiato (3.16.13 di RelDox): la misura in un posto, preparata (CORRETTO). */
	public static function capacity_aggregate( mysqli $conn, bool $fresh ): array {
		$out = array( 'ok' => false, 'data_size' => 0, 'index_size' => 0, 'free_size' => 0, 'tables_total' => 0, 'dbname' => '', 'error' => '', 'fresh' => false );
		try {
			$r  = @$conn->query( 'SELECT DATABASE()' );
			$db = ( $r instanceof mysqli_result ) ? (string) ( $r->fetch_row()[0] ?? '' ) : '';
			if ( $r instanceof mysqli_result ) { $r->free(); }
			if ( '' === $db ) {
				$out['error'] = 'Impossibile determinare il database corrente.';
				return $out;
			}
			$out['dbname'] = $db;
			$prev = $fresh ? self::stats_expiry_zero( $conn ) : null;
			$like = str_replace( array( '\\', '_', '%' ), array( '\\\\', '\\_', '\\%' ), RDACADEMY_TABLE_PREFIX ) . '%';
			$st   = $conn->prepare( "SELECT COALESCE(SUM(DATA_LENGTH),0), COALESCE(SUM(INDEX_LENGTH),0), COALESCE(SUM(DATA_FREE),0), COUNT(*)
				FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ? AND TABLE_TYPE = 'BASE TABLE'" );
			$ok = $st && $st->bind_param( 'ss', $db, $like ) && $st->execute();
			self::stats_expiry_restore( $conn, $prev );
			if ( ! $ok ) {
				$out['error'] = 'Query INFORMATION_SCHEMA fallita: ' . $conn->error;
				return $out;
			}
			$a = $b = $c = $d = 0;
			$st->bind_result( $a, $b, $c, $d );
			$st->fetch();
			$st->close();
			$out = array_merge( $out, array( 'ok' => true, 'data_size' => (int) $a, 'index_size' => (int) $b, 'free_size' => (int) $c, 'tables_total' => (int) $d, 'fresh' => ( null !== $prev ) ) );
			return $out;
		} catch ( Throwable $e ) {
			$out['error'] = 'mysqli ha lanciato: ' . $e->getMessage();
			return $out;
		}
	}

	public static function ajax_db_capacity( ?array $cur ): void {
		self::guardia( $cur );
		$source = self::source_param();
		$conn   = self::conn_misura( $source );
		if ( ! $conn ) {
			wp_send_json_error( array( 'message' => 'Sorgente NON IN USO: ricade sul BASE, non c’è un database suo da misurare.', 'non_in_uso' => true ) );
		}
		$agg = self::capacity_aggregate( $conn, true );
		if ( empty( $agg['ok'] ) ) {
			wp_send_json_error( (string) $agg['error'] );
		}
		$used  = (int) $agg['data_size'] + (int) $agg['index_size'];
		$quota = Rdacademy_Dimensioni::quota_mb( $source );
		$qb    = $quota * 1024 * 1024;
		$pct   = $qb > 0 ? round( $used / $qb * 100, 1 ) : 0.0;
		$st    = Rdacademy_Dimensioni::stato( (float) $pct );
		wp_send_json_success( array(
			'source' => $source, 'db' => (string) $agg['dbname'],
			'used_bytes' => $used, 'data_size' => (int) $agg['data_size'], 'index_size' => (int) $agg['index_size'], 'free_size' => (int) $agg['free_size'],
			'quota_mb' => $quota, 'quota_bytes' => $qb, 'pct' => (float) $pct, 'state' => $st['state'], 'state_label' => $st['label'],
			'tables_total' => (int) $agg['tables_total'], 'thresholds' => Rdacademy_Dimensioni::soglie(), 'fresh' => ! empty( $agg['fresh'] ),
		) );
	}

	/** Copiato (Patch I di RelDox): 10 MB – 1.000.000 MB, una quota per database. ⛔ Preparata. */
	public static function ajax_db_quota_save( ?array $cur ): void {
		$cur    = self::guardia( $cur );
		$source = self::source_param();
		$q      = (int) ( $_POST['quota_mb'] ?? 0 );
		if ( $q < 10 || $q > 1000000 ) {
			wp_send_json_error( 'Valore della quota non valido (consentito 10 – 1.000.000 MB).' );
		}
		Rdacademy_DB::clear_last_error();
		Rdacademy_DB::execute( 'INSERT INTO `' . RDACADEMY_TABLE_PREFIX . 'impostazioni` (chiave, valore) VALUES (?, ?) ON DUPLICATE KEY UPDATE valore = VALUES(valore)',
			array( Rdacademy_Dimensioni::quota_key( $source ), (string) $q ), 'ss' );
		if ( Rdacademy_DB::last_error() !== '' ) {
			wp_send_json_error( 'Salvataggio della quota non riuscito: ' . Rdacademy_DB::last_error() );
		}
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'database', 'quota', 'Capacità acquistata di ' . $source . ': ' . $q . ' MB' );
		wp_send_json_success( array( 'quota_mb' => $q, 'source' => $source ) );
	}

	/* ══ OTTIMIZZAZIONE (copiato: pre-check assoluto O relativo, esito per riga) ══ */

	public static function ajax_db_optimize( ?array $cur ): void {
		$cur    = self::guardia( $cur );
		@set_time_limit( 0 );
		$force  = isset( $_POST['force'] ) && $_POST['force'] === '1';
		$source = self::source_param();
		$conn   = self::conn_misura( $source );
		if ( ! $conn ) {
			wp_send_json_error( array( 'message' => 'Sorgente NON IN USO: non c’è un database suo da ottimizzare.', 'non_in_uso' => true ) );
		}
		$r  = @$conn->query( 'SELECT DATABASE()' );
		$db = ( $r instanceof mysqli_result ) ? (string) ( $r->fetch_row()[0] ?? '' ) : '';
		if ( $db === '' ) {
			wp_send_json_error( 'Impossibile determinare il database corrente.' );
		}
		$ABS = 1 * 1024 * 1024; // 1 MB — ramo assoluto (copiato)
		$REL = 0.20;            // 20%  — ramo relativo (copiato, BUG-47)
		$prev = self::stats_expiry_zero( $conn );
		$like = str_replace( array( '\\', '_', '%' ), array( '\\\\', '\\_', '\\%' ), RDACADEMY_TABLE_PREFIX ) . '%';
		$st   = $conn->prepare( "SELECT TABLE_NAME, ENGINE, DATA_FREE, DATA_LENGTH, INDEX_LENGTH FROM INFORMATION_SCHEMA.TABLES
			WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ? AND TABLE_TYPE = 'BASE TABLE' AND ENGINE IN ('InnoDB','MyISAM') ORDER BY DATA_FREE DESC" );
		if ( ! $st || ! $st->bind_param( 'ss', $db, $like ) || ! $st->execute() ) {
			self::stats_expiry_restore( $conn, $prev );
			wp_send_json_error( 'Query INFORMATION_SCHEMA fallita: ' . $conn->error );
		}
		$righe = $st->get_result()->fetch_all( MYSQLI_ASSOC );
		$st->close();
		$optimized = array(); $skipped = array(); $errors = array(); $freed_tot = 0;
		foreach ( $righe as $row ) {
			$t   = (string) $row['TABLE_NAME'];
			$eng = (string) ( $row['ENGINE'] ?? 'InnoDB' );
			$df  = (int) $row['DATA_FREE'];
			$tot = (int) $row['DATA_LENGTH'] + (int) $row['INDEX_LENGTH'];
			$pa  = $df >= $ABS;
			$pr  = $tot > 0 && $df >= (int) ceil( $tot * $REL );
			if ( ! $force && ! $pa && ! $pr ) {
				$skipped[] = array( 'table' => $t, 'engine' => $eng, 'data_free' => $df, 'total_size' => $tot, 'pct' => self::frag_pct( $df, $tot ),
					'reason' => 'sotto soglia: ' . self::fmt_bytes_short( $df ) . ' liberi su ' . self::fmt_bytes_short( $tot ) . ' (serve 1 MB assoluti oppure il 20%)' );
				continue;
			}
			$err = '';
			$rq  = self::q_due_modi( $conn, 'OPTIMIZE TABLE `' . str_replace( '`', '``', $t ) . '`', $err );
			if ( ! ( $rq instanceof mysqli_result ) ) {
				$errors[] = array( 'table' => $t, 'engine' => $eng, 'error' => $err !== '' ? $err : $conn->error );
				continue;
			}
			// Copiato (BUG-43): l esito di OPTIMIZE sta nelle RIGHE, non nel ritorno.
			$msg = '';
			while ( $m = $rq->fetch_assoc() ) {
				if ( 'error' === strtolower( (string) ( $m['Msg_type'] ?? '' ) ) ) { $msg = (string) ( $m['Msg_text'] ?? 'errore senza testo' ); }
			}
			$rq->free();
			if ( $msg !== '' ) {
				$errors[] = array( 'table' => $t, 'engine' => $eng, 'error' => $msg );
				continue;
			}
			$s2 = $conn->prepare( 'SELECT DATA_FREE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?' );
			$after = 0;
			if ( $s2 && $s2->bind_param( 'ss', $db, $t ) && $s2->execute() ) { $s2->bind_result( $after ); $s2->fetch(); $s2->close(); }
			$freed      = max( 0, $df - (int) $after );
			$freed_tot += $freed;
			$optimized[] = array( 'table' => $t, 'engine' => $eng, 'data_free_before' => $df, 'data_free_after' => (int) $after, 'bytes_freed' => $freed,
				'total_size' => $tot, 'pct_before' => self::frag_pct( $df, $tot ), 'forced' => ( $force && ! $pa && ! $pr ) );
		}
		self::stats_expiry_restore( $conn, $prev );
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'database', 'optimize', $db . ': ' . count( $optimized ) . ' ottimizzate, ' . count( $skipped ) . ' saltate, ' . count( $errors ) . ' errori' );
		wp_send_json_success( array(
			'fresh' => ( null !== $prev ), 'optimized_count' => count( $optimized ), 'skipped_count' => count( $skipped ), 'errors_count' => count( $errors ),
			'bytes_freed_total' => $freed_tot, 'optimized' => $optimized, 'skipped' => $skipped, 'errors' => $errors,
			'forced' => $force, 'source' => $source, 'db' => $db, 'threshold' => array( 'abs_bytes' => $ABS, 'rel_pct' => (int) round( $REL * 100 ) ),
		) );
	}

	/* ══ BACKUP E CORREGGI ══ */

	public static function ajax_backup_run( ?array $cur ): void {
		$cur    = self::guardia( $cur );
		$source = sanitize_key( wp_unslash( (string) ( $_POST['source'] ?? 'all' ) ) );
		if ( ! in_array( $source, Rdacademy_Backup::source_keys(), true ) ) {
			$source = 'all';
		}
		$res = Rdacademy_Backup::run( $source, 'manuale' );
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'database', 'backup', 'Backup ' . $source . ': ' . ( ! empty( $res['ok'] ) ? 'riuscito' : 'NON riuscito' ) );
		if ( empty( $res['ok'] ) ) {
			$err = (string) ( $res['error'] ?? '' );
			foreach ( (array) ( $res['sources'] ?? array() ) as $s ) { if ( ! empty( $s['error'] ) ) { $err .= ( $err !== '' ? ' · ' : '' ) . $s['label'] . ': ' . $s['error']; } }
			if ( $err === '' ) { $err = 'nessuna sorgente con un database suo da salvare'; }
			wp_send_json_error( array( 'message' => 'Backup non riuscito: ' . $err, 'sources' => $res['sources'] ?? array() ) );
		}
		wp_send_json_success( array( 'source' => $source, 'sources' => $res['sources'] ) );
	}

	public static function ajax_backup_list( ?array $cur ): void {
		self::guardia( $cur );
		wp_send_json_success( array( 'backups' => Rdacademy_Backup::list_backups() ) );
	}

	public static function ajax_restore_preview( ?array $cur ): void {
		self::guardia( $cur );
		$res = Rdacademy_Backup::preview_file( (string) wp_unslash( $_POST['rel'] ?? '' ) );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( (string) ( $res['error'] ?? 'anteprima non riuscita' ) );
		}
		wp_send_json_success( $res );
	}

	/** La parola che la conferma in pagina chiede (NUOVO, al posto della doppia window.confirm di RelDox). */
	const PAROLA_CORREGGI = 'CORREGGI';

	public static function ajax_restore_apply( ?array $cur ): void {
		$cur = self::guardia( $cur );
		if ( strtoupper( trim( (string) ( $_POST['conferma'] ?? '' ) ) ) !== self::PAROLA_CORREGGI ) {
			wp_send_json_error( 'Conferma mancante: scrivi ' . self::PAROLA_CORREGGI . ' per applicare la correzione.', 400 );
		}
		$rel = (string) wp_unslash( $_POST['rel'] ?? '' );
		if ( '' === $rel ) {
			wp_send_json_error( 'Nessun file di backup selezionato.' );
		}
		try {
			$res = Rdacademy_Backup::correct_from_file( $rel, false, true );
		} catch ( Throwable $e ) {
			wp_send_json_error( 'Eccezione: ' . $e->getMessage() );
		}
		Rdacademy_Auth::log_activity( (int) $cur['id'], 'database', 'restore_apply', basename( $rel ) . ': '
			. ( ! empty( $res['ok'] ) ? ( (int) ( $res['rows_inserted'] ?? 0 ) . ' righe reinserite' ) : 'NON riuscito' ) );
		if ( empty( $res['ok'] ) ) {
			if ( ! empty( $res['error'] ) ) {
				wp_send_json_error( (string) $res['error'] );
			}
			$errs = array_slice( (array) ( $res['errors'] ?? array() ), 0, 3 );
			wp_send_json_error( 'Correzione con errori SQL: ' . implode( ' | ', array_map( 'strval', $errs ) )
				. sprintf( ' — (create=%d, insert=%d, righe=%d, drop saltati=%d)', (int) ( $res['created'] ?? 0 ), (int) ( $res['insert_stmts'] ?? 0 ), (int) ( $res['rows'] ?? 0 ), (int) ( $res['dropped_skipped'] ?? 0 ) ) );
		}
		wp_send_json_success( $res );
	}
}
