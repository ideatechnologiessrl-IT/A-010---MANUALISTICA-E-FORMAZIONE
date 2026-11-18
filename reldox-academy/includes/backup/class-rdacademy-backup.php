<?php
/**
 * Rdacademy_Backup — BACKUP SQL PER SORGENTE e «CORREGGI DA BACKUP» ADDITIVO.
 * =============================================================================
 * Copiato da Reldox_Backup (rete di RECUPERO, 3.14.69+), RelDox CRD 3.55.09G, e ridotto.
 *
 * NON è distruttivo: LEGGE il database e SCRIVE un file. Su hosting condiviso
 * `mysqldump` non c è: il dump è fatto in PHP (SHOW CREATE TABLE + SELECT) e
 * SCRITTO IN STREAMING a file, INSERT a lotti, colonne GENERATED escluse.
 *
 *   wp-content/rdacademy-private/backups/<RDACADEMY_VERSION>/<db>/<db>-<ver>-<ts>.sql.php
 *
 * Sorgenti: base (Rdacademy_DB) e le sorgenti della mappa, SOLO se provisionate con
 * il loro database (altrimenti ricadono sul BASE: marcate, non ri-dumpate).
 *
 * CORREGGI ADDITIVO (copiato, motore + regole): i DROP del dump si SALTANO,
 * CREATE TABLE → CREATE TABLE IF NOT EXISTS, INSERT INTO → INSERT IGNORE INTO;
 * SET/commenti/altro ignorati. Prima della correzione, un backup di sicurezza
 * della stessa sorgente.
 *
 * TAGLI: la sorgente «wp» (il database di WordPress non si tocca, REGOLE § 3.5),
 * cron notturno, fallback «lazy», snapshot pre-migrazione al boot (nessuna
 * migrazione nel guscio), dump_table_to_file() (banco Strumento 49 del CRM).
 *
 * ⛔ CORRETTO / NUOVO RISPETTO A RELDOX, DICHIARATO:
 *  - CARTELLA: in RelDox wp-content/reldox-backups/ con il solo .htaccess. Qui
 *    sotto la cartella privata del prodotto, con .htaccess + web.config +
 *    index.php a ogni livello (REGOLE § 3.9).
 *  - TERZA BARRIERA: il file è `.sql.php` e la sua PRIMA riga è
 *    `<?php http_response_code( 403 ); exit; __halt_compiler();`. Sui server che
 *    ignorano .htaccess e web.config (nginx, il server di PHP) una richiesta
 *    diretta ESEGUE la riga e riceve 403; `__halt_compiler()` impedisce al
 *    motore di analizzare il resto del dump. Il lettore del correggi salta la riga.
 *  - SOLO LE TABELLE DEL PRODOTTO (prefisso RDACADEMY_TABLE_PREFIX): un database di
 *    sorgente può ospitare altro; il correggi rifiuta CREATE/INSERT su tabelle
 *    senza il prefisso.
 *  - IL BACKUP DI SICUREZZA È UN CANCELLO: in RelDox il correggi procedeva anche
 *    se il backup di sicurezza falliva (`$safety['done']` letto e ignorato).
 *  - ANTEPRIMA CHE MISURA CIÒ CHE MANCA: in RelDox l anteprima contava tabelle e
 *    righe del file. Qui dice anche, per ogni tabella, se manca sul database e
 *    quante righe del dump NON ci sono (confronto per chiave primaria, query
 *    preparate, sola lettura). Una tabella senza chiave primaria si dichiara
 *    «non misurabile».
 *  - Le stringhe con la parola DROP sono costruite per concatenazione (copiato).
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Backup {

	const BATCH_ROWS = 400;

	/** La riga di guardia in testa a ogni file. */
	const GUARDIA = '<?php http_response_code( 403 ); exit; __halt_compiler();';

	const ESTENSIONE = '.sql.php';

	/* ── Sorgenti ───────────────────────────────────────────────────── */

	/** @return array<int,array{key:string,label:string,conn:?mysqli,own:bool}> */
	private static function sources(): array {
		$out   = array();
		$out[] = array( 'key' => 'base', 'label' => 'BASE', 'conn' => Rdacademy_DB::get_connection(), 'own' => true );
		foreach ( Rdacademy_Sorgenti::mappa() as $k => $m ) {
			// BUG-19 di RelDox: SOLO se provisionata (i dati vivono davvero lì).
			$own   = Rdacademy_DataSource::is_provisioned( $k );
			$out[] = array( 'key' => $k, 'label' => $m['n'] . ' · ' . $m['nome'], 'conn' => $own ? Rdacademy_DataSource::get( $k ) : null, 'own' => $own );
		}
		return $out;
	}

	public static function source_keys(): array {
		return array_merge( array( 'base' ), Rdacademy_Sorgenti::chiavi(), array( 'all' ) );
	}

	private static function dbname( mysqli $conn ): string {
		try {
			$res = $conn->query( 'SELECT DATABASE() AS d' );
			if ( $res instanceof mysqli_result ) {
				$r = $res->fetch_assoc();
				$res->free();
				return (string) ( $r['d'] ?? '' );
			}
		} catch ( Throwable $e ) {}
		return '';
	}

	/** Le tabelle del PRODOTTO nel database della connessione (preparata). */
	private static function list_tables( mysqli $conn ): array {
		$out = array();
		try {
			$like = str_replace( array( '\\', '_', '%' ), array( '\\\\', '\\_', '\\%' ), RDACADEMY_TABLE_PREFIX ) . '%';
			$st   = $conn->prepare( "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE ? ORDER BY TABLE_NAME" );
			$st->bind_param( 's', $like );
			$st->execute();
			$r = $st->get_result();
			while ( $x = $r->fetch_row() ) { $out[] = (string) $x[0]; }
			$st->close();
		} catch ( Throwable $e ) {}
		return $out;
	}

	/* ── Percorso e protezione ─────────────────────────────────────── */

	public static function private_dir(): string {
		return rtrim( WP_CONTENT_DIR, '/\\' ) . '/' . Rdacademy_Schema_Uploads_Blob_V1::STORAGE_BASEDIR_REL;
	}

	public static function root_dir(): string {
		return self::private_dir() . '/backups';
	}

	public static function backup_dir(): string {
		return self::root_dir() . '/' . preg_replace( '/[^A-Za-z0-9_.\-]/', '_', RDACADEMY_VERSION );
	}

	private static function db_dir( string $dbname ): string {
		return self::backup_dir() . '/' . preg_replace( '/[^A-Za-z0-9_\-]/', '_', $dbname );
	}

	/** Crea la cartella e la protegge (.htaccess + web.config + index.php). Fail-soft. */
	public static function ensure_dir_protected( string $dir ): bool {
		Rdacademy_Schema_Uploads_Blob_V1::ensure_storage_dir(); // la radice privata e le sue barriere
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		if ( ! is_writable( $dir ) ) {
			return false;
		}
		Rdacademy_Schema_Uploads_Blob_V1::barriere( $dir );
		return true;
	}

	/* ── Esecuzione ────────────────────────────────────────────────── */

	public static function run( string $source = 'all', string $trigger = 'manual' ): array {
		if ( ! in_array( $source, self::source_keys(), true ) ) {
			return array( 'ok' => false, 'error' => 'sorgente non valida: ' . $source, 'sources' => array() );
		}
		if ( ! self::ensure_dir_protected( self::root_dir() ) || ! self::ensure_dir_protected( self::backup_dir() ) ) {
			return array( 'ok' => false, 'error' => 'cartella dei backup non creabile o non scrivibile: ' . self::backup_dir(), 'sources' => array() );
		}
		$base_dbname = self::dbname( Rdacademy_DB::get_connection() );
		$results     = array();
		foreach ( self::sources() as $s ) {
			if ( 'all' !== $source && $s['key'] !== $source ) {
				continue;
			}
			if ( ! $s['own'] || ! ( $s['conn'] instanceof mysqli ) ) {
				$results[] = array( 'key' => $s['key'], 'label' => $s['label'], 'skipped' => true,
					'note' => 'NON IN USO o senza tabelle sul suo database: ricade sul BASE, nessun dump dedicato' );
				continue;
			}
			$dbn = self::dbname( $s['conn'] );
			if ( 'base' !== $s['key'] && $dbn === $base_dbname ) {
				$results[] = array( 'key' => $s['key'], 'label' => $s['label'], 'skipped' => true, 'note' => 'ricade sul BASE (' . $dbn . '): coperta dal backup del BASE' );
				continue;
			}
			$results[] = self::dump_source( $s, $dbn, $trigger );
		}
		$done = array_filter( $results, static function ( $r ) { return empty( $r['skipped'] ) && ! empty( $r['ok'] ); } );
		$fail = array_filter( $results, static function ( $r ) { return empty( $r['skipped'] ) && empty( $r['ok'] ); } );
		return array( 'ok' => ! $fail && ( ! empty( $done ) || 'all' !== $source ), 'sources' => $results );
	}

	private static function dump_source( array $s, string $dbn, string $trigger ): array {
		$dir = self::db_dir( $dbn );
		if ( ! self::ensure_dir_protected( $dir ) ) {
			return array( 'key' => $s['key'], 'label' => $s['label'], 'ok' => false, 'error' => 'cartella non scrivibile: ' . $dir );
		}
		$stamp = gmdate( 'Ymd-His' );
		$path  = $dir . '/' . $dbn . '-' . RDACADEMY_VERSION . '-' . $stamp . '-' . substr( md5( uniqid( '', true ) ), 0, 6 ) . self::ESTENSIONE;
		$fh    = @fopen( $path, 'xb' );
		if ( ! $fh ) {
			return array( 'key' => $s['key'], 'label' => $s['label'], 'ok' => false, 'error' => 'apertura del file non riuscita: ' . basename( $path ) );
		}
		$conn = $s['conn'];
		$tables_tot = 0; $rows_tot = 0; $t0 = microtime( true );
		try {
			fwrite( $fh, self::GUARDIA . "\n" );
			fwrite( $fh, '-- ' . RDACADEMY_PRODUCT . " backup — sorgente {$s['label']} (DB {$dbn})\n" );
			fwrite( $fh, '-- versione: ' . RDACADEMY_VERSION_LABEL . ' — origine: ' . $trigger . "\n" );
			fwrite( $fh, '-- UTC: ' . gmdate( 'Y-m-d H:i:s' ) . "\n" );
			fwrite( $fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n" );
			foreach ( self::list_tables( $conn ) as $table ) {
				$res = self::dump_table( $fh, $conn, $table );
				$tables_tot++;
				$rows_tot += $res['rows'];
			}
			fwrite( $fh, "\nSET FOREIGN_KEY_CHECKS=1;\n-- fine backup {$dbn}\n" );
		} catch ( Throwable $e ) {
			fclose( $fh );
			@unlink( $path ); // un dump a metà non è un backup
			return array( 'key' => $s['key'], 'label' => $s['label'], 'ok' => false, 'error' => 'dump: ' . $e->getMessage() );
		}
		fclose( $fh );
		@chmod( $path, 0600 );
		$bytes = (int) @filesize( $path );
		return array(
			'key' => $s['key'], 'label' => $s['label'], 'db' => $dbn, 'ok' => true,
			'file' => basename( $path ), 'rel' => basename( self::backup_dir() ) . '/' . basename( $dir ) . '/' . basename( $path ),
			'tables' => $tables_tot, 'rows' => $rows_tot,
			'mb' => round( $bytes / 1048576, 2 ), 'bytes' => $bytes, 'seconds' => round( microtime( true ) - $t0, 2 ),
		);
	}

	/** Dump di una tabella in streaming (copiato). */
	private static function dump_table( $fh, mysqli $conn, string $table ): array {
		$q = str_replace( '`', '``', $table );
		$drop_kw = 'DROP ' . 'TABLE IF EXISTS'; // stringa scritta nel dump, mai eseguita (il correggi la salta)
		fwrite( $fh, "\n-- tabella {$table}\n" );
		fwrite( $fh, $drop_kw . " `{$q}`;\n" );
		try {
			$res = $conn->query( "SHOW CREATE TABLE `{$q}`" );
			if ( $res instanceof mysqli_result ) {
				$row = $res->fetch_assoc();
				$res->free();
				$create = isset( $row['Create Table'] ) ? (string) $row['Create Table'] : '';
				if ( '' !== $create ) {
					fwrite( $fh, $create . ";\n\n" );
				}
			}
		} catch ( Throwable $e ) {
			fwrite( $fh, '-- struttura non leggibile: ' . $e->getMessage() . "\n" );
			return array( 'rows' => 0 );
		}
		$real_cols = self::cols_non_generated( $conn, $table );
		if ( ! empty( $real_cols ) ) {
			$col_bt     = '`' . implode( '`,`', array_map( static function ( $c ) { return str_replace( '`', '``', $c ); }, $real_cols ) ) . '`';
			$ins_cols   = ' (' . $col_bt . ')';
			$select_sql = "SELECT {$col_bt} FROM `{$q}`";
		} else {
			$ins_cols   = '';
			$select_sql = "SELECT * FROM `{$q}`";
		}
		$rows = 0;
		try {
			$res = $conn->query( $select_sql, MYSQLI_USE_RESULT );
			if ( $res instanceof mysqli_result ) {
				$batch = array();
				while ( $r = $res->fetch_row() ) {
					$vals = array();
					foreach ( $r as $v ) {
						$vals[] = ( null === $v ) ? 'NULL' : "'" . $conn->real_escape_string( (string) $v ) . "'"; // scrittura del dump: è il formato SQL, non una query
					}
					$batch[] = '(' . implode( ',', $vals ) . ')';
					$rows++;
					if ( count( $batch ) >= self::BATCH_ROWS ) {
						fwrite( $fh, "INSERT INTO `{$q}`{$ins_cols} VALUES\n" . implode( ",\n", $batch ) . ";\n" );
						$batch = array();
					}
				}
				if ( ! empty( $batch ) ) {
					fwrite( $fh, "INSERT INTO `{$q}`{$ins_cols} VALUES\n" . implode( ",\n", $batch ) . ";\n" );
				}
				$res->free();
			}
		} catch ( Throwable $e ) {
			fwrite( $fh, '-- dati non leggibili: ' . $e->getMessage() . "\n" );
		}
		fwrite( $fh, "\n" );
		return array( 'rows' => $rows );
	}

	/* ── Elenco e risoluzione ──────────────────────────────────────── */

	public static function list_backups(): array {
		$root = self::root_dir();
		$out  = array();
		if ( ! is_dir( $root ) ) {
			return $out;
		}
		$fonte = array( self::dbname( Rdacademy_DB::get_connection() ) => 'BASE' );
		foreach ( Rdacademy_Sorgenti::mappa() as $k => $m ) {
			$p = Rdacademy_DataSource::connect_params( $k );
			if ( $p && $p['name'] !== '' ) { $fonte[ $p['name'] ] = $m['n'] . ' · ' . $m['nome']; }
		}
		foreach ( (array) glob( $root . '/*', GLOB_ONLYDIR ) as $verdir ) {
			foreach ( (array) glob( $verdir . '/*', GLOB_ONLYDIR ) as $dbdir ) {
				$db = basename( $dbdir );
				foreach ( (array) glob( $dbdir . '/*' . self::ESTENSIONE ) as $file ) {
					$out[] = array(
						'version' => basename( $verdir ), 'db' => $db, 'sorgente' => $fonte[ $db ] ?? '',
						'file' => basename( $file ), 'rel' => basename( $verdir ) . '/' . $db . '/' . basename( $file ),
						'mb' => round( ( (int) @filesize( $file ) ) / 1048576, 2 ), 'bytes' => (int) @filesize( $file ),
						'mtime' => (int) @filemtime( $file ),
					);
				}
			}
		}
		usort( $out, static function ( $a, $b ) { return ( $b['mtime'] <=> $a['mtime'] ) ?: strcmp( $b['file'], $a['file'] ); } );
		return $out;
	}

	/** Percorso relativo → assoluto, ogni uscita dalla radice bloccata (copiato, estensione nuova). */
	public static function resolve_rel( string $rel ): ?string {
		$rel   = str_replace( array( '..', "\0", '\\' ), array( '', '', '/' ), $rel );
		if ( ! preg_match( '#^[A-Za-z0-9_.\-]+/[A-Za-z0-9_\-]+/[A-Za-z0-9_.\-]+\.sql\.php$#', $rel ) ) {
			return null;
		}
		$root  = self::root_dir();
		$real  = realpath( $root . '/' . ltrim( $rel, '/' ) );
		$rroot = realpath( $root );
		if ( ! $real || ! $rroot || strpos( $real, rtrim( $rroot, '/' ) . '/' ) !== 0 || substr( $real, -8 ) !== self::ESTENSIONE ) {
			return null;
		}
		return $real;
	}

	/* ── Lettura del dump a istruzioni ─────────────────────────────── */

	/**
	 * Legge il file a istruzioni (stessa regola dell accumulo di RelDox: fine
	 * istruzione = «;» a fine riga) e chiama $fn( string $stmt ) per ognuna.
	 * Salta la riga di guardia, le righe vuote e i commenti al confine.
	 */
	private static function each_stmt( string $path, callable $fn, array &$res ): bool {
		$fh = @fopen( $path, 'rb' );
		if ( ! $fh ) {
			return false;
		}
		$first = true;
		$buf   = '';
		while ( ( $line = fgets( $fh ) ) !== false ) {
			if ( $first ) {
				$first = false;
				if ( strncmp( $line, '<?php', 5 ) === 0 ) { continue; }
			}
			if ( '' === $buf ) {
				$lt = ltrim( $line );
				if ( '' === $lt || strncmp( $lt, '--', 2 ) === 0 || strncmp( $lt, '/*', 2 ) === 0 ) {
					$res['other_skipped']++;
					continue;
				}
			}
			$buf .= $line;
			$rt   = rtrim( $line );
			if ( $rt === '' || substr( $rt, -1 ) !== ';' ) {
				continue;
			}
			$stmt = trim( $buf );
			$buf  = '';
			if ( $stmt !== '' ) { $fn( $stmt ); }
		}
		if ( trim( $buf ) !== '' ) { $res['other_skipped']++; }
		fclose( $fh );
		return true;
	}

	/** Nome della tabella di un CREATE/INSERT, '' se non si riconosce. */
	private static function tabella_di( string $stmt ): string {
		if ( preg_match( '/^(?:CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|INSERT\s+(?:IGNORE\s+)?INTO)\s+`?([A-Za-z0-9_]+)`?/i', $stmt, $m ) ) {
			return (string) $m[1];
		}
		return '';
	}

	private static function del_prodotto( string $table ): bool {
		return $table !== '' && strpos( $table, RDACADEMY_TABLE_PREFIX ) === 0;
	}

	/* ── Anteprima: che cosa c è nel file, e che cosa MANCA sul database ── */

	public static function preview_file( string $rel ): array {
		$path = self::resolve_rel( $rel );
		if ( ! $path ) {
			return array( 'ok' => false, 'error' => 'file non valido o fuori dalla cartella dei backup' );
		}
		$db  = basename( dirname( $path ) );
		$src = self::connection_for_db( $db );
		$res = array( 'other_skipped' => 0 );
		$tab = array(); // tabella => [presente, righe_file, mancanti|null, nota]
		$conn = $src ? $src['conn'] : null;
		$pk_cache = array();
		$ok = self::each_stmt( $path, function ( string $stmt ) use ( &$tab, $conn, &$pk_cache ) {
			$head = ltrim( $stmt );
			$t    = self::tabella_di( $head );
			if ( ! self::del_prodotto( $t ) ) {
				return;
			}
			if ( ! isset( $tab[ $t ] ) ) {
				$tab[ $t ] = array( 'tabella' => $t, 'presente' => $conn ? ( Rdacademy_DataSource::tabella_su( $conn, $t ) === true ) : null,
					'righe_file' => 0, 'mancanti' => 0, 'nota' => '' );
			}
			if ( stripos( $head, 'INSERT' ) !== 0 ) {
				return;
			}
			$m = null;
			if ( ! preg_match( '/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?[A-Za-z0-9_]+`?\s*(\(([^)]*)\))?\s*VALUES\b/is', $head, $m ) ) {
				$tab[ $t ]['nota'] = 'istruzione non riconosciuta';
				return;
			}
			$tuples = self::parse_values_tuples( substr( $head, strlen( $m[0] ) ) );
			if ( null === $tuples ) {
				$tab[ $t ]['nota'] = 'valori non leggibili';
				return;
			}
			$tab[ $t ]['righe_file'] += count( $tuples );
			if ( ! $conn ) {
				$tab[ $t ]['mancanti'] = null;
				return;
			}
			if ( ! $tab[ $t ]['presente'] ) {
				$tab[ $t ]['mancanti'] += count( $tuples ); // la tabella non c è: mancano tutte
				return;
			}
			if ( $tab[ $t ]['mancanti'] === null ) {
				return; // già dichiarata non misurabile
			}
			$cols = isset( $m[2] ) && $m[2] !== ''
				? array_map( static function ( $c ) { return trim( $c, " `\t\r\n" ); }, explode( ',', $m[2] ) )
				: self::cols_all_and_generated( $conn, $t )['all'];
			if ( ! isset( $pk_cache[ $t ] ) ) {
				$pk_cache[ $t ] = self::primary_key( $conn, $t );
			}
			$pk  = $pk_cache[ $t ];
			$pos = array();
			foreach ( $pk as $c ) {
				$i = array_search( $c, $cols, true );
				if ( $i === false ) { $pos = array(); break; }
				$pos[] = $i;
			}
			if ( ! $pk || ! $pos ) {
				$tab[ $t ]['mancanti'] = null;
				$tab[ $t ]['nota']     = 'senza chiave primaria leggibile: non misurabile';
				return;
			}
			$n = self::conta_mancanti( $conn, $t, $pk, $pos, $tuples );
			if ( $n === null ) {
				$tab[ $t ]['mancanti'] = null;
				$tab[ $t ]['nota']     = 'misura non riuscita';
			} else {
				$tab[ $t ]['mancanti'] += $n;
			}
		}, $res );
		if ( ! $ok ) {
			return array( 'ok' => false, 'error' => 'impossibile leggere il file' );
		}
		$tabelle = array_values( $tab );
		$righe   = array_sum( array_column( $tabelle, 'righe_file' ) );
		$manc    = 0; $cieche = 0; $da_creare = 0;
		foreach ( $tabelle as $x ) {
			if ( $x['mancanti'] === null ) { $cieche++; } else { $manc += (int) $x['mancanti']; }
			if ( $x['presente'] === false ) { $da_creare++; }
		}
		return array(
			'ok' => true, 'db' => $db, 'source' => $src['key'] ?? '', 'source_label' => $src['label'] ?? '',
			'raggiungibile' => (bool) $src,
			'tables' => count( $tabelle ), 'rows_est' => $righe,
			'da_creare' => $da_creare, 'mancanti' => $manc, 'non_misurabili' => $cieche,
			'tabelle' => $tabelle, 'mb' => round( ( (int) @filesize( $path ) ) / 1048576, 2 ),
		);
	}

	/** Le colonne della chiave primaria, in ordine (preparata). */
	private static function primary_key( mysqli $conn, string $table ): array {
		$out = array();
		try {
			$st = $conn->prepare( "SELECT COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = 'PRIMARY' ORDER BY SEQ_IN_INDEX" );
			$st->bind_param( 's', $table );
			$st->execute();
			$r = $st->get_result();
			while ( $x = $r->fetch_row() ) { $out[] = (string) $x[0]; }
			$st->close();
		} catch ( Throwable $e ) {
			return array();
		}
		return $out;
	}

	/** Un letterale SQL del dump → valore PHP (o null per NULL). */
	private static function letterale( string $tok ) {
		$tok = trim( $tok );
		if ( strcasecmp( $tok, 'NULL' ) === 0 ) {
			return null;
		}
		if ( strlen( $tok ) >= 2 && $tok[0] === "'" && substr( $tok, -1 ) === "'" ) {
			$in = substr( $tok, 1, -1 );
			return strtr( str_replace( "''", "'", $in ), array( '\\0' => "\0", "\\'" => "'", '\\"' => '"', '\\b' => "\x08", '\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\Z' => "\x1a", '\\\\' => '\\' ) );
		}
		return $tok;
	}

	/** Quante tuple del dump NON hanno la loro chiave primaria sul database. Sola lettura, preparata. */
	private static function conta_mancanti( mysqli $conn, string $table, array $pk, array $pos, array $tuples ): ?int {
		$q     = '`' . str_replace( '`', '``', $table ) . '`';
		$cols  = implode( ', ', array_map( static function ( $c ) { return '`' . str_replace( '`', '``', $c ) . '`'; }, $pk ) );
		$cond1 = '(' . implode( ' AND ', array_map( static function ( $c ) { return '`' . str_replace( '`', '``', $c ) . '` <=> ?'; }, $pk ) ) . ')';
		$mancanti = 0;
		foreach ( array_chunk( $tuples, 100 ) as $lotto ) {
			$vals = array();
			foreach ( $lotto as $t ) {
				foreach ( $pos as $i ) { $vals[] = self::letterale( (string) ( $t[ $i ] ?? 'NULL' ) ); }
			}
			$sql = "SELECT COUNT(*) FROM {$q} WHERE " . implode( ' OR ', array_fill( 0, count( $lotto ), $cond1 ) );
			try {
				$st = $conn->prepare( $sql );
				if ( ! $st ) { return null; }
				$st->bind_param( str_repeat( 's', count( $vals ) ), ...$vals );
				$st->execute();
				$n = 0;
				$st->bind_result( $n );
				$st->fetch();
				$st->close();
			} catch ( Throwable $e ) {
				return null;
			}
			$mancanti += max( 0, count( $lotto ) - (int) $n );
		}
		return $mancanti;
	}

	/* ── Correggi additivo ─────────────────────────────────────────── */

	public static function apply_correct_file( mysqli $conn, string $path, bool $dry = false ): array {
		$res = array( 'ok' => true, 'dry' => $dry, 'created' => 0, 'insert_stmts' => 0, 'rows' => 0, 'rows_inserted' => 0,
			'dropped_skipped' => 0, 'foreign_skipped' => 0, 'other_skipped' => 0, 'errors' => array() );
		$drop_kw    = 'DROP' . ' TABLE';
		$meta_cache = array();
		$ok = self::each_stmt( $path, function ( string $stmt ) use ( $conn, $dry, $drop_kw, &$meta_cache, &$res ) {
			$head = ltrim( $stmt );
			if ( stripos( $head, $drop_kw ) === 0 ) {
				$res['dropped_skipped']++;
				return;
			}
			$isc = stripos( $head, 'CREATE TABLE' ) === 0;
			$isi = stripos( $head, 'INSERT INTO' ) === 0 || stripos( $head, 'INSERT IGNORE' ) === 0;
			if ( ! $isc && ! $isi ) {
				$res['other_skipped']++;
				return;
			}
			if ( ! self::del_prodotto( self::tabella_di( $head ) ) ) {
				$res['foreign_skipped']++; // NUOVO: solo le tabelle del prodotto
				return;
			}
			if ( $isc ) {
				if ( stripos( $head, 'IF NOT EXISTS' ) === false ) {
					$head = preg_replace( '/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $head, 1 );
				}
				$res['created']++;
				if ( ! $dry ) { self::exec_stmt( $conn, $head, $res ); }
				return;
			}
			$res['insert_stmts']++;
			$res['rows'] += max( 1, (int) preg_match_all( '/^\s*\(/m', $stmt ) );
			if ( $dry ) {
				return;
			}
			$rew = stripos( $head, 'INSERT IGNORE' ) === 0 ? $head : self::insert_ignore_generated_aware( $conn, $stmt, $meta_cache, $res );
			if ( null !== $rew ) {
				self::exec_stmt( $conn, $rew, $res );
				$res['rows_inserted'] += max( 0, (int) $conn->affected_rows );
			}
		}, $res );
		if ( ! $ok ) {
			$res['ok']       = false;
			$res['errors'][] = 'impossibile leggere il file';
			return $res;
		}
		$res['ok'] = empty( $res['errors'] );
		return $res;
	}

	private static function exec_stmt( mysqli $conn, string $sql, array &$res ): void {
		try {
			$ok = $conn->query( $sql ); // istruzione del dump del prodotto, riscritta additiva: il formato è SQL
			if ( false === $ok ) {
				$res['errors'][] = $conn->error !== '' ? $conn->error : 'query fallita';
			}
		} catch ( Throwable $e ) {
			$res['errors'][] = $e->getMessage();
		}
	}

	/* ── Colonne GENERATED (copiato, Meta A/B1) ─────────────────────── */

	private static function cols_non_generated( mysqli $conn, string $table ): array {
		$out = array();
		try {
			$st = $conn->prepare( "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND ( EXTRA IS NULL OR EXTRA NOT LIKE '%GENERATED%' ) ORDER BY ORDINAL_POSITION" );
			$st->bind_param( 's', $table );
			$st->execute();
			$r = $st->get_result();
			while ( $x = $r->fetch_row() ) { $out[] = (string) $x[0]; }
			$st->close();
		} catch ( Throwable $e ) {
			return array();
		}
		return $out;
	}

	private static function cols_all_and_generated( mysqli $conn, string $table ): array {
		$all = array(); $gen = array();
		try {
			$st = $conn->prepare( 'SELECT COLUMN_NAME, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION' );
			$st->bind_param( 's', $table );
			$st->execute();
			$r = $st->get_result();
			while ( $x = $r->fetch_row() ) {
				$all[] = (string) $x[0];
				if ( false !== stripos( (string) $x[1], 'GENERATED' ) ) { $gen[] = (string) $x[0]; }
			}
			$st->close();
		} catch ( Throwable $e ) {
			return array( 'all' => array(), 'gen' => array() );
		}
		return array( 'all' => $all, 'gen' => $gen );
	}

	/** Copiato senza modifiche: tuple di token grezzi, rispetta apici ed escape. */
	private static function parse_values_tuples( string $vc ): ?array {
		$tuples = array();
		$len = strlen( $vc );
		$i = 0;
		while ( $i < $len ) {
			$c = $vc[ $i ];
			if ( $c === ' ' || $c === "\n" || $c === "\r" || $c === "\t" || $c === ',' ) { $i++; continue; }
			if ( $c === ';' ) { break; }
			if ( $c !== '(' ) { return null; }
			$i++;
			$tokens = array(); $cur = ''; $inStr = false; $done = false;
			while ( $i < $len ) {
				$ch = $vc[ $i ];
				if ( $inStr ) {
					if ( $ch === '\\' ) {
						$cur .= $ch; $i++;
						if ( $i < $len ) { $cur .= $vc[ $i ]; $i++; }
						continue;
					}
					if ( $ch === "'" ) {
						if ( $i + 1 < $len && $vc[ $i + 1 ] === "'" ) { $cur .= "''"; $i += 2; continue; }
						$cur .= "'"; $inStr = false; $i++; continue;
					}
					$cur .= $ch; $i++; continue;
				} else {
					if ( $ch === "'" ) { $cur .= "'"; $inStr = true; $i++; continue; }
					if ( $ch === ',' ) { $tokens[] = trim( $cur ); $cur = ''; $i++; continue; }
					if ( $ch === ')' ) { $tokens[] = trim( $cur ); $i++; $done = true; break; }
					$cur .= $ch; $i++; continue;
				}
			}
			if ( ! $done ) { return null; }
			$tuples[] = $tokens;
		}
		return $tuples;
	}

	private static function rewrite_positional_strip_generated( string $table, string $vc, array $all, array $gen ): ?string {
		$tuples = self::parse_values_tuples( $vc );
		if ( null === $tuples || empty( $tuples ) ) { return null; }
		$genSet = array_flip( $gen ); $keepIdx = array(); $keepCols = array();
		foreach ( $all as $idx => $col ) {
			if ( ! isset( $genSet[ $col ] ) ) { $keepIdx[] = $idx; $keepCols[] = $col; }
		}
		$nAll = count( $all ); $out = array();
		foreach ( $tuples as $t ) {
			if ( count( $t ) !== $nAll ) { return null; }
			$vals = array();
			foreach ( $keepIdx as $k ) { $vals[] = $t[ $k ]; }
			$out[] = '(' . implode( ',', $vals ) . ')';
		}
		return "INSERT IGNORE INTO `{$table}` (`" . implode( '`,`', $keepCols ) . "`) VALUES\n" . implode( ",\n", $out );
	}

	private static function insert_ignore_generated_aware( mysqli $conn, string $stmt, array &$cache, array &$res ): ?string {
		if ( ! preg_match( '/^INSERT\s+INTO\s+`?([A-Za-z0-9_]+)`?\s*(\([^)]*\))?\s*VALUES\b/is', $stmt, $m ) ) {
			return preg_replace( '/^INSERT\s+INTO\s+/i', 'INSERT IGNORE INTO ', $stmt, 1 );
		}
		$table = $m[1];
		$has_collist = ( isset( $m[2] ) && '' !== $m[2] );
		if ( ! isset( $cache[ $table ] ) ) {
			$cache[ $table ] = self::cols_all_and_generated( $conn, $table );
		}
		$gen = $cache[ $table ]['gen'];
		$all = $cache[ $table ]['all'];
		if ( empty( $gen ) || $has_collist ) {
			return preg_replace( '/^INSERT\s+INTO\s+/i', 'INSERT IGNORE INTO ', $stmt, 1 );
		}
		if ( empty( $all ) ) {
			$res['errors'][] = "colonne di `{$table}` non leggibili: INSERT posizionale saltato per sicurezza";
			return null;
		}
		$rew = self::rewrite_positional_strip_generated( $table, substr( $stmt, strlen( $m[0] ) ), $all, $gen );
		if ( null === $rew ) {
			$res['errors'][] = "riscrittura INSERT posizionale di `{$table}` rifiutata (conteggio colonne non combacia): riga saltata per sicurezza";
		}
		return $rew;
	}

	/** La sorgente il cui database corrente combacia con $db_name (copiato). */
	private static function connection_for_db( string $db_name ): ?array {
		if ( '' === $db_name ) {
			return null;
		}
		foreach ( self::sources() as $s ) {
			if ( empty( $s['own'] ) || ! ( $s['conn'] instanceof mysqli ) ) {
				continue;
			}
			if ( preg_replace( '/[^A-Za-z0-9_\-]/', '_', self::dbname( $s['conn'] ) ) === $db_name ) {
				return array( 'key' => $s['key'], 'label' => $s['label'], 'conn' => $s['conn'] );
			}
		}
		return null;
	}

	/**
	 * CORREGGI ADDITIVO da un file (copiato). ⛔ Il backup di sicurezza è un
	 * cancello: se non riesce, la correzione non parte.
	 */
	public static function correct_from_file( string $rel, bool $dry = false, bool $safety_backup = true ): array {
		$path = self::resolve_rel( $rel );
		if ( ! $path ) {
			return array( 'ok' => false, 'dry' => $dry, 'error' => 'file non valido o fuori dalla cartella dei backup' );
		}
		$db_name = basename( dirname( $path ) );
		$src     = self::connection_for_db( $db_name );
		if ( null === $src ) {
			return array( 'ok' => false, 'dry' => $dry, 'error' => 'nessuna sorgente IN USO corrisponde al database ' . $db_name );
		}
		$safety = array( 'done' => false );
		try {
			if ( ! $dry && $safety_backup ) {
				$snap = self::run( $src['key'], 'prima-del-correggi' );
				$file = '';
				foreach ( (array) ( $snap['sources'] ?? array() ) as $r ) {
					if ( ( $r['key'] ?? '' ) === $src['key'] && ! empty( $r['file'] ) ) { $file = (string) $r['file']; }
				}
				$safety = array( 'done' => ! empty( $snap['ok'] ) && $file !== '', 'source' => $src['key'], 'file' => $file );
				if ( ! $safety['done'] ) {
					return array( 'ok' => false, 'dry' => $dry, 'db' => $db_name, 'source' => $src['key'], 'safety_backup' => $safety,
						'error' => 'backup di sicurezza non riuscito: la correzione non è partita e nulla è stato toccato' );
				}
			}
			$app = self::apply_correct_file( $src['conn'], $path, $dry );
		} catch ( Throwable $e ) {
			return array( 'ok' => false, 'dry' => $dry, 'db' => $db_name, 'source' => $src['key'], 'safety_backup' => $safety,
				'error' => 'eccezione durante il correggi: ' . $e->getMessage() );
		}
		$app['db']            = $db_name;
		$app['source']        = $src['key'];
		$app['source_label']  = $src['label'];
		$app['safety_backup'] = $safety;
		return $app;
	}
}
