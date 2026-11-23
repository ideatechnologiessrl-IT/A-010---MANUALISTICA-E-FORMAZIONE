<?php
/**
 * Rdacademy_Console_SQL — IL MOTORE DELLA CONSOLE SQL (banco standard n. 2, area «Database e sorgenti»).
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * RelDoxAcademy 0.1.12A (Formazione F027: BUG-6 e F42, dalla revisione a freddo F026; prima 0.1.11A, F026, voce F29). Mockup MOCKUP-CONSOLE-SQL-v1 approvato (D45, owner
 * 23/11/2026 08:39, chat Formazione F025: «ok approvato»). Nasce dalla console di RelDoxFood 1.24.11L
 * (class-rdfood-console-sql.php, letta in sola lettura in F025) portata allo standard di famiglia: banco
 * prima del motore (banco/F026-banco-console-sql.php).
 *
 * VIVE SOLO NEL PLUGIN DI DIAGNOSTICA (frontiera R1): il cliente non lo riceve; ogni porta chiede manage_options.
 *
 * LE REGOLE (tutte dal server, mai dal browser):
 *  - una istruzione per volta, al massimo MAX_RIGHE righe, tempo massimo TEMPO_MAX secondi;
 *  - lettura: SELECT, SHOW, DESCRIBE/DESC, EXPLAIN, WITH, sempre in transazione READ ONLY (WordPress compreso);
 *  - scrittura SOLO per elenco ammesso: INSERT, UPDATE, DELETE, REPLACE (anche dentro WITH); ogni altra istruzione
 *    è rifiutata anche con la spunta; DROP, TRUNCATE, ALTER, MODIFY, RENAME non si eseguono mai;
 *  - rifiutati sempre: commenti eseguibili /*!…*\/ e /*M!…*\/, INTO (OUTFILE, DUMPFILE, @variabile),
 *    EXPLAIN ANALYZE / ANALYZE, LOAD_FILE, FOR UPDATE / FOR SHARE / LOCK IN SHARE MODE, GET_LOCK e simili, SLEEP, BENCHMARK;
 *  - scrittura in due tempi: prova a secco (transazione annullata, solo tabelle InnoDB), poi CONFERMO legato a
 *    istruzione, sorgente, database, host e utente, valido CONFERMO_TTL secondi e una volta sola; le righe si
 *    ricontano e, se cambiano, si annulla;
 *  - WordPress: solo lettura, sempre; sorgenti non configurate o spente: rifiutate (mai il ripiego sul BASE);
 *  - registro append-only sul BASE (mai su WordPress): prove, scritture e rifiuti; senza registro non si scrive;
 *  - segreti: utente e password della sorgente solo come presenza; colonne segrete mascherate nei risultati;
 *  - database e host dichiarati in ogni riga del referto, anche negli errori.
 *  0.1.12A (BUG-6, F42):
 *  - i nomi fra apici inversi si leggono in chiaro prima dei controlli (`SLEEP`(1) è SLEEP(1));
 *  - mai lo schema mysql, performance_schema, sys; mai SHOW GRANTS / CREATE USER / PROCESSLIST, USER(), CURRENT_USER;
 *  - colonne segrete: non si citano (con SELECT * escono mascherate, anche per nome d origine); errori senza valori né utente;
 *  - in scrittura: niente riferimenti a un altro database, niente viste, sequenze, tabelle con trigger, funzioni memorizzate;
 *    se la sorgente è il database di WordPress, solo tabelle col prefisso di RelDoxAcademy;
 *  - gettone del CONFERMO consumato una volta sola nel database (chiave unica), registro con trigger contro UPDATE e DELETE.
 *  0.1.13A (BUG-6, terza revisione a freddo F028):
 *  - in scrittura nessuna lettura (SELECT, TABLE) da tabelle con colonne segrete o chiave/valore: i segreti non si copiano;
 *  - tabelle chiave/valore (impostazioni, wp_options, *meta): il valore di una chiave segreta si maschera riga per riga, il valore
 *    non si usa in condizioni né in espressioni che ne cambiano il nome; il nome della chiave resta visibile;
 *  - sessione con sql_mode senza ANSI_QUOTES e NO_BACKSLASH_ESCAPES (lo scheletro legge le stringhe come il server), altrimenti niente;
 *  - mai INNODB_TRX, INNODB_LOCKS, SHOW BINLOG/RELAYLOG EVENTS, SHOW EXPLAIN FOR, EXPLAIN FOR CONNECTION, permessi e ruoli, attese GTID, /*+;
 *  - tetto anche in byte (MAX_BYTE), lettura senza copia intera in memoria; utente della sorgente mascherato;
 *  - TABLE, LATERAL, JSON_TABLE come sottoquery; registro: protezione con trigger verificata e dichiarata; attesa dei lucchetti TEMPO_MAX.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rdacademy_Console_SQL {

	const MAX_RIGHE    = 1000;
	const MAX_BYTE     = 5000000;
	const TEMPO_MAX    = 10;
	const CONFERMO_TTL = 300;
	const REGISTRO     = 'console_registro';
	const GETTONI      = 'console_gettoni';

	const LETTURE        = array( 'SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'WITH' );
	const SCRITTURE      = array( 'INSERT', 'UPDATE', 'DELETE', 'REPLACE' );
	const SEMPRE_VIETATE = array( 'DROP', 'TRUNCATE', 'ALTER', 'MODIFY', 'RENAME' );

	/** Vietati in qualunque punto dell istruzione (sullo scheletro, senza stringhe né commenti). */
	const VIETATI_OVUNQUE = array(
		'/\bINTO\s+(OUTFILE|DUMPFILE)\b/i'                     => '«SELECT … INTO OUTFILE/DUMPFILE» rifiutata: la console non scrive file.',
		'/\bANALYZE\b/i'                                       => 'EXPLAIN ANALYZE e ANALYZE rifiutati: eseguono davvero l istruzione.',
		'/\bLOAD_FILE\s*\(/i'                                  => 'LOAD_FILE() rifiutata: la console non legge file del server.',
		'/\bFOR\s+(UPDATE|SHARE)\b|\bLOCK\s+IN\s+SHARE\s+MODE\b/i' => 'FOR UPDATE, FOR SHARE e LOCK IN SHARE MODE rifiutati: la console non prende lucchetti.',
		'/\b(GET_LOCK|RELEASE_LOCK|RELEASE_ALL_LOCKS|MASTER_POS_WAIT|MASTER_GTID_WAIT|SOURCE_POS_WAIT|WAIT_FOR_EXECUTED_GTID_SET|WAIT_UNTIL_SQL_THREAD_AFTER_GTIDS)\s*\(/i' => 'GET_LOCK e funzioni d attesa rifiutate.',
		'/\b(SLEEP|BENCHMARK)\s*\(/i'                          => 'SLEEP e BENCHMARK rifiutati: tengono occupato il server.',
		'/\b(NEXTVAL|SETVAL|LASTVAL)\s*\(|\b(NEXT|PREVIOUS)\s+VALUE\s+FOR\b/i' => 'Sequenze rifiutate: NEXTVAL e simili cambiano il server fuori da ogni transazione.',
		'/\bSHOW\s+(GRANTS|CREATE\s+USER|(FULL\s+)?PROCESSLIST|ENGINES?\b)/i' => 'SHOW GRANTS, SHOW CREATE USER, SHOW PROCESSLIST e SHOW ENGINE rifiutati: utenti e permessi del server non si mostrano.',
		'/\b(USER|CURRENT_USER|SESSION_USER|SYSTEM_USER|CURRENT_ROLE)\s*\(|\bCURRENT_USER\b|\bCURRENT_ROLE\b/i' => 'USER(), CURRENT_USER e simili rifiutati: l utente della sorgente si mostra solo come presenza.',
		'/\b(PROCESSLIST|USER_PRIVILEGES|USER_STATISTICS|CLIENT_STATISTICS|APPLICABLE_ROLES|ENABLED_ROLES|USER_ATTRIBUTES|SCHEMA_PRIVILEGES|TABLE_PRIVILEGES|COLUMN_PRIVILEGES|ADMINISTRABLE_ROLE_AUTHORIZATIONS|ROLE_[A-Z_]+)\b/i' => 'Tabelle di sistema con utenti e permessi rifiutate.',
		'/\bINNODB_(TRX|LOCKS|LOCK_WAITS)\b|\bDATA_LOCK(S|_WAITS)\b/i' => 'INNODB_TRX, INNODB_LOCKS e simili rifiutate: mostrano le istruzioni delle altre sessioni.',
		'/\bSHOW\s+(BINLOG|RELAYLOG|BINARY|MASTER|SLAVE|REPLICA|REPLICAS)\b|\bSHOW\s+EXPLAIN\b|\bFOR\s+CONNECTION\b/i' => 'SHOW BINLOG/RELAYLOG EVENTS, SHOW EXPLAIN FOR e EXPLAIN FOR CONNECTION rifiutati: istruzioni passate o di altre sessioni.',
	);

	/** Colonne segrete note: si mascherano nei risultati (nome che contiene uno di questi pezzi). */
	const SEGRETE = array( 'pass', 'pwd', 'token', 'gettone', 'secret', 'segret', 'api_key', 'apikey', 'activation_key', 'hash', 'salt', 'nonce', '_enc', 'authentication', 'definer', 'grantee', 'db_user' );

	/** Tabelle chiave/valore (F028): colonna del valore => colonna della chiave. */
	const KV = array( 'valore' => 'chiave', 'option_value' => 'option_name', 'meta_value' => 'meta_key' );

	/** Schemi del server mai toccati dalla console. */
	const SCHEMI_VIETATI = array( 'mysql', 'performance_schema', 'sys' );

	/** Il «adesso» del motore (il banco lo sposta per provare la scadenza del CONFERMO). */
	public static ?int $adesso = null;

	/** @var array<int,array> gettoni quando WordPress non c è */
	private static array $gettoni = array();

	private static function ora(): int { return self::$adesso ?? time(); }

	/* ============================================================= *
	 *  Classificazione (pura: nessuna connessione)
	 * ============================================================= */

	/**
	 * Lo scheletro: stringhe, identificatori fra apici inversi e commenti diventano spazi.
	 * Restituisce [scheletro, identificatori fra apici inversi, errore].
	 */
	public static function scheletro( string $sql ): array {
		$out = ''; $ids = array(); $n = strlen( $sql ); $i = 0;
		while ( $i < $n ) {
			$c = $sql[ $i ]; $d = $i + 1 < $n ? $sql[ $i + 1 ] : '';
			if ( $c === '/' && $d === '*' ) {
				$dopo = substr( $sql, $i + 2, 2 );
				if ( ( $dopo[0] ?? '' ) === '!' || strtoupper( $dopo ) === 'M!' ) {
					return array( '', $ids, 'Commento eseguibile «/*!…*/» rifiutato.' );
				}
				if ( ( $dopo[0] ?? '' ) === '+' ) {
					return array( '', $ids, 'Suggerimenti «/*+ … */» rifiutati: cambiano tempo massimo e modo del server per l istruzione.' );
				}
				$j = strpos( $sql, '*/', $i + 2 );
				if ( $j === false ) return array( '', $ids, 'Commento non chiuso: istruzione rifiutata.' );
				$out .= ' '; $i = $j + 2; continue;
			}
			if ( $c === '#' || ( $c === '-' && $d === '-' && ( $i + 2 >= $n || ctype_space( $sql[ $i + 2 ] ) ) ) ) {
				$j = strpos( $sql, "\n", $i );
				$out .= ' '; $i = $j === false ? $n : $j + 1; continue;
			}
			if ( $c === "'" || $c === '"' || $c === '`' ) {
				$j = $i + 1; $val = '';
				while ( true ) {
					if ( $j >= $n ) return array( '', $ids, 'Stringa o nome non chiuso: istruzione rifiutata.' );
					if ( $sql[ $j ] === '\\' && $c !== '`' ) { $val .= substr( $sql, $j, 2 ); $j += 2; continue; }
					if ( $sql[ $j ] === $c ) {
						if ( $j + 1 < $n && $sql[ $j + 1 ] === $c ) { $val .= $c; $j += 2; continue; }
						break;
					}
					$val .= $sql[ $j ]; $j++;
				}
				if ( $c === '`' ) {
					$ids[] = $val;
					// in chiaro (BUG-6 ALTO 1): `SLEEP`(1) si giudica come SLEEP(1); un nome con caratteri strani diventa __idN__
					$out .= preg_match( '/^[A-Za-z0-9_$\x80-\xff]+$/', $val ) ? $val : ' __id' . ( count( $ids ) - 1 ) . '__ ';
				} else { $out .= " '' "; }
				$i = $j + 1; continue;
			}
			$out .= $c; $i++;
		}
		return array( $out, $ids, '' );
	}

	/**
	 * @return array{ok:bool,tipo:string,verbo:string,motivo:string,nomi:array}
	 */
	public static function classifica( string $sql ): array {
		$no = static function ( string $m, string $v = '' ): array { return array( 'ok' => false, 'tipo' => 'rifiuto', 'verbo' => $v, 'motivo' => $m, 'nomi' => array() ); };
		list( $sk, $ids, $err ) = self::scheletro( $sql );
		if ( $err !== '' ) return $no( $err );
		$sk = rtrim( $sk );
		if ( substr( $sk, -1 ) === ';' ) $sk = rtrim( substr( $sk, 0, -1 ) );
		if ( trim( $sk ) === '' ) return $no( 'Istruzione vuota.' );
		if ( strpos( $sk, ';' ) !== false ) return $no( 'Due istruzioni in una: rifiutata (una per volta).' );
		$testa = ltrim( $sk, " \t\r\n(" );
		$verbo = strtoupper( (string) preg_replace( '/^([A-Za-z_]+).*$/s', '$1', $testa ) );
		if ( in_array( $verbo, self::SEMPRE_VIETATE, true ) ) {
			return $no( '«' . $verbo . '» rifiutata dal server: DROP, TRUNCATE, ALTER, MODIFY, RENAME non si eseguono mai. In scrittura passano solo INSERT, UPDATE, DELETE, REPLACE; ogni altra istruzione (GRANT, CREATE, SET, LOAD DATA, CALL, LOCK…) è rifiutata.', $verbo );
		}
		foreach ( self::VIETATI_OVUNQUE as $re => $m ) {
			if ( preg_match( $re, $sk ) ) return $no( $m, $verbo );
		}
		preg_match_all( '/[A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*/', $sk, $mm0 );
		$tutti = array_map( 'strtolower', array_merge( $mm0[0], $ids ) );
		foreach ( self::SCHEMI_VIETATI as $sv ) {
			if ( ( $sv !== 'sys' && in_array( $sv, $tutti, true ) ) || preg_match( '/\b' . $sv . '\s*\.|\b(FROM|IN)\s+' . $sv . '\b/i', $sk ) || in_array( $sv, array_map( 'strtolower', $ids ), true ) ) {
				return $no( 'Lo schema «' . $sv . '» del server non si tocca dalla console (utenti, password, sessioni).', $verbo );
			}
		}
		$tipo = '';
		if ( in_array( $verbo, self::SCRITTURE, true ) ) {
			$tipo = 'scrittura';
		} elseif ( in_array( $verbo, self::LETTURE, true ) ) {
			$tipo = 'lettura';
			// REPLACE( è la funzione, non l istruzione (F42 b)
			if ( $verbo === 'WITH' && preg_match( '/\b(INSERT|UPDATE|DELETE)\b|\b(REPLACE)\b(?!\s*\()/i', $sk, $m ) ) {
				$tipo = 'scrittura'; $verbo = strtoupper( $m[2] ?? '' ) !== '' ? 'REPLACE' : strtoupper( $m[1] );
			}
		}
		if ( $tipo === '' ) {
			return $no( '«' . ( $verbo !== '' ? $verbo : substr( $testa, 0, 12 ) ) . '» non ammessa: in lettura SELECT, SHOW, DESCRIBE, EXPLAIN, WITH; in scrittura solo INSERT, UPDATE, DELETE, REPLACE.', $verbo );
		}
		if ( $tipo === 'lettura' && preg_match( '/\bINTO\b/i', $sk ) ) {
			return $no( '«INTO» in una lettura rifiutato: la console non scrive file né variabili.', $verbo );
		}
		preg_match_all( '/[A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*/', $sk, $mm );
		$nomi = array_values( array_unique( array_map( 'strtolower', array_merge( $mm[0], $ids ) ) ) );
		// riferimenti qualificati x.y (il primo nome può essere un database): servono in scrittura
		$qual = array();
		if ( preg_match_all( '/([A-Za-z0-9_$\x80-\xff]+)\s*\.\s*[A-Za-z0-9_$\x80-\xff`]/', $sk, $mq ) ) {
			foreach ( $mq[1] as $q ) { $qual[] = strtolower( preg_match( '/^__id(\d+)__$/', $q, $mi ) ? (string) ( $ids[ (int) $mi[1] ] ?? $q ) : $q ); }
		}
		preg_match_all( '/([A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*)\s*\(/', $sk, $mc );
		return array( 'ok' => true, 'tipo' => $tipo, 'verbo' => $verbo, 'motivo' => '', 'nomi' => $nomi, 'qualificati' => array_values( array_unique( $qual ) ), 'chiamate' => array_values( array_unique( array_map( 'strtolower', $mc[1] ) ) ) );
	}

	/* ============================================================= *
	 *  Sorgenti (mai una password, mai l utente: solo la presenza)
	 * ============================================================= */

	private static function host_porta( string $h ): array {
		if ( preg_match( '/^(.+):(\d+)$/', $h, $m ) ) return array( $m[1], (int) $m[2] );
		return array( $h, 3306 );
	}

	/** Parametri interni di una sorgente (con la password): mai fuori da questa classe. */
	private static function parametri( string $k ): ?array {
		if ( $k === 'base' ) {
			$c = Rdacademy_DB_Config::get();
			list( $h, $p ) = self::host_porta( (string) ( $c['host'] ?? '' ) );
			return array( 'host' => $h, 'port' => $p, 'name' => (string) ( $c['name'] ?? '' ), 'user' => (string) ( $c['user'] ?? '' ), 'pass' => (string) ( $c['pass'] ?? '' ), 'charset' => 'utf8mb4' );
		}
		if ( $k === 'wordpress' ) {
			if ( ! defined( 'DB_HOST' ) ) return null;
			list( $h, $p ) = self::host_porta( (string) DB_HOST );
			return array( 'host' => $h, 'port' => $p, 'name' => (string) DB_NAME, 'user' => (string) DB_USER, 'pass' => (string) DB_PASSWORD, 'charset' => 'utf8mb4' );
		}
		return Rdacademy_DataSource::connect_params( $k );
	}

	/** @return array<string,array{etichetta:string,stato:string,in_uso:bool,scrivibile:bool,db:string,host:string,utente:string,password:string}> */
	public static function sorgenti(): array {
		$voce = static function ( string $et, string $st, bool $uso, bool $scr, ?array $p ): array {
			return array( 'etichetta' => $et, 'stato' => $st, 'in_uso' => $uso, 'scrivibile' => $scr,
				'db' => (string) ( $p['name'] ?? '' ), 'host' => (string) ( $p['host'] ?? '' ),
				'utente' => ( $p['user'] ?? '' ) !== '' ? 'presente' : 'assente', 'password' => ( $p['pass'] ?? '' ) !== '' ? 'presente' : 'assente' );
		};
		$out = array( 'base' => $voce( 'BASE — verità RelDoxAcademy', 'in_uso', true, true, self::parametri( 'base' ) ) );
		foreach ( Rdacademy_Sorgenti::mappa() as $k => $m ) {
			$st  = Rdacademy_DataSource::state( $k );
			$uso = in_array( $st, array( 'live', 'unprovisioned' ), true );
			$et  = Rdacademy_Sorgenti::numero( $k ) . ' · ' . Rdacademy_Sorgenti::nome( $k );
			$out[ $k ] = $voce( $et, $uso ? 'in_uso' : ( $st === 'disabled' ? 'spenta' : 'non_configurata' ), $uso, $uso, $uso ? self::parametri( $k ) : null );
		}
		$out['wordpress'] = $voce( 'WordPress — non è una sorgente di RelDoxAcademy', 'solo_lettura', true, false, self::parametri( 'wordpress' ) );
		return $out;
	}

	/* ============================================================= *
	 *  Esecuzione
	 * ============================================================= */

	private static function q( mysqli $c, string $sql ) {
		try { return $c->query( $sql ); } catch ( mysqli_sql_exception $e ) { return false; }
	}

	private static function err( mysqli $c ): string {
		// BUG-6 MEDIO 1: niente valori dei dati né utente nei messaggi (tutto ciò che è fra apici diventa «…»)
		// dal primo apice in poi si taglia tutto (un apice dentro il valore non lo fa uscire)
		return $c->errno ? '(' . $c->errno . ') ' . (string) preg_replace( array( '/`[^`]*`@`[^`]*`/', '/[\'"].*$/s' ), array( '…', '«…»' ), $c->error ) : 'errore sconosciuto';
	}

	private static function apri( array $p ): ?mysqli {
		if ( ( $p['host'] ?? '' ) === '' || ( $p['name'] ?? '' ) === '' ) return null;   // senza parametri non si apre nulla (mai un ripiego)
		$r = Rdacademy_DataSource::connetti( $p );
		if ( empty( $r['ok'] ) || ! ( $r['connection'] instanceof mysqli ) ) return null;
		$c = $r['connection'];
		// F028: lo scheletro legge stringhe e nomi come un server senza ANSI_QUOTES e NO_BACKSLASH_ESCAPES: la sessione deve essere così
		$m  = self::colonna( $c, 'SELECT @@SESSION.sql_mode' )[0] ?? '';
		$mm = array_filter( explode( ',', strtoupper( $m ) ), static function ( $x ) { return $x !== '' && $x !== 'ANSI_QUOTES' && $x !== 'NO_BACKSLASH_ESCAPES' && $x !== 'ANSI'; } );
		self::q( $c, "SET SESSION sql_mode = '" . $c->real_escape_string( implode( ',', $mm ) ) . "'" );
		$dopo = strtoupper( self::colonna( $c, 'SELECT @@SESSION.sql_mode' )[0] ?? 'ANSI_QUOTES' );
		if ( strpos( $dopo, 'ANSI_QUOTES' ) !== false || strpos( $dopo, 'NO_BACKSLASH_ESCAPES' ) !== false ) { $c->close(); return null; }
		return $c;
	}

	/** F028: la chiave di una riga chiave/valore è segreta, o il valore ne contiene una (array serializzati, JSON). */
	private static function segreta_kv( ?string $k, ?string $v ): bool {
		if ( $k === null ) return true;
		if ( self::segreta( $k ) || preg_match( '/key|smtp|licen|credenz|credential|privat|auth|secret|segret|session|cookie|cert/i', $k ) ) return true;
		return $v !== null && (bool) preg_match( '/pass|pwd|secret|segret|token|gettone|api_?key|apikey|privat|BEGIN [A-Z ]*KEY/i', $v );
	}

	private static function segreta( string $col ): bool {
		$c = strtolower( $col );
		foreach ( self::SEGRETE as $s ) { if ( strpos( $c, $s ) !== false ) return true; }
		return false;
	}

	/**
	 * Una lettura su una connessione: tempo massimo, transazione READ ONLY, al massimo MAX_RIGHE righe.
	 * Il server rifiuta da solo ogni scrittura arrivata fin qui (difesa in profondità).
	 */
	public static function leggi_su( mysqli $c, string $sql, array $nomi = array() ): array {
		if ( ! self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_MAX ) ) {
			self::q( $c, 'SET SESSION MAX_EXECUTION_TIME = ' . ( self::TEMPO_MAX * 1000 ) );
		}
		if ( ! self::q( $c, 'SET SESSION TRANSACTION READ ONLY' ) || ! self::q( $c, 'START TRANSACTION READ ONLY' ) ) {
			return array( 'ok' => false, 'errore' => 'transazione in sola lettura non aperta: ' . self::err( $c ) );
		}
		$t0  = microtime( true );
		// F028: senza copia intera in memoria (MYSQLI_USE_RESULT), tetto in righe e in byte
		try { $res = $c->query( $sql, MYSQLI_USE_RESULT ); } catch ( mysqli_sql_exception $e ) { $res = false; }
		if ( $res === false ) { $e = self::err( $c ); self::q( $c, 'ROLLBACK' ); return array( 'ok' => false, 'errore' => $e ); }
		$col = array(); $org = array(); $dati = array(); $tronco = false; $byte = 0;
		if ( $res instanceof mysqli_result ) {
			foreach ( $res->fetch_fields() as $f ) { $col[] = (string) $f->name; $org[] = strtolower( (string) ( $f->orgname ?? '' ) ); }
			while ( $row = $res->fetch_row() ) {
				if ( count( $dati ) >= self::MAX_RIGHE ) { $tronco = true; break; }
				foreach ( $row as $v ) { $byte += strlen( (string) $v ); }
				if ( $byte > self::MAX_BYTE ) { $tronco = true; break; }
				$dati[] = $row;
			}
			$res->free();
		}
		self::q( $c, 'ROLLBACK' );
		// F028: tabelle chiave/valore — il valore di una chiave segreta si maschera riga per riga; senza la chiave accanto, sempre
		$kv_citate = array_intersect( array_keys( self::KV ), $nomi );
		foreach ( $col as $i => $nome ) {
			$o = $org[ $i ] ?? '';
			if ( isset( self::KV[ $o ] ) ) {
				$j = array_search( self::KV[ $o ], $org, true );
				foreach ( $dati as &$r ) { if ( $r[ $i ] !== null && self::segreta_kv( $j === false ? null : $r[ $j ], $r[ $i ] ) ) $r[ $i ] = '•••• (mascherata)'; }
				unset( $r );
			} elseif ( $kv_citate && ( $o === '' || $o !== strtolower( $nome ) ) ) {
				foreach ( $dati as &$r ) { if ( $r[ $i ] !== null ) $r[ $i ] = '•••• (mascherata)'; }
				unset( $r );
			}
		}
		$mascherate = array();
		foreach ( $col as $i => $nome ) {
			if ( ! self::segreta( $nome ) && ! self::segreta( $org[ $i ] ?? '' ) ) continue;
			$mascherate[] = $nome;
			foreach ( $dati as &$r ) { if ( $r[ $i ] !== null ) $r[ $i ] = '•••• (mascherata)'; }
			unset( $r );
		}
		foreach ( $dati as &$r ) {
			foreach ( $r as &$v ) { if ( is_string( $v ) && stripos( $v, 'DEFINER' ) !== false ) $v = (string) preg_replace( '/DEFINER\s*=\s*\S+/i', 'DEFINER=…', $v ); }
			unset( $v );
		}
		unset( $r );
		return array( 'ok' => true, 'colonne' => $col, 'dati' => $dati, 'troncato' => $tronco, 'mascherate' => $mascherate, 'ms' => (int) round( ( microtime( true ) - $t0 ) * 1000 ) );
	}

	/** Prima colonna di una lettura di servizio, in minuscolo. */
	private static function colonna( mysqli $c, string $sql ): array {
		$res = self::q( $c, $sql ); $out = array();
		if ( $res instanceof mysqli_result ) { while ( $r = $res->fetch_row() ) $out[] = strtolower( (string) $r[0] ); $res->free(); }
		return $out;
	}

	/** Tabelle (di ogni schema visibile) che hanno almeno una colonna segreta. */
	private static function tabelle_segrete( mysqli $c ): array {
		$res = self::q( $c, 'SELECT LOWER(TABLE_NAME), COLUMN_NAME FROM information_schema.COLUMNS' ); $out = array();
		if ( $res instanceof mysqli_result ) { while ( $r = $res->fetch_row() ) { if ( self::segreta( (string) $r[1] ) || isset( self::KV[ strtolower( (string) $r[1] ) ] ) ) $out[ (string) $r[0] ] = true; } $res->free(); }
		return array_keys( $out );
	}

	/** Colonne segrete citate per nome (BUG-6 MEDIO 1): alias, espressioni e WHERE le farebbero uscire. */
	private static function segrete_citate( mysqli $c, array $nomi ): array {
		$col = self::colonna( $c, 'SELECT DISTINCT COLUMN_NAME FROM information_schema.COLUMNS' );
		return array_values( array_filter( array_intersect( $nomi, $col ), array( __CLASS__, 'segreta' ) ) );
	}

	/**
	 * Controlli di una scrittura sul database (BUG-6 ALTI 2 e 3): un motivo di rifiuto, oppure ''.
	 * La prova a secco annulla solo ciò che è InnoDB, nel database corrente, senza effetti fuori dall istruzione.
	 */
	private static function controlla_scrittura( mysqli $c, array $cl ): string {
		$cur = self::colonna( $c, 'SELECT DATABASE()' )[0] ?? '';
		if ( $cur === '' ) return 'Nessun database corrente: scrittura rifiutata.';
		$dbs  = self::colonna( $c, 'SHOW DATABASES' );
		$altri = array_values( array_diff( array_intersect( $cl['qualificati'], array_merge( $dbs, self::SCHEMI_VIETATI ) ), array( $cur ) ) );
		if ( $altri ) return 'Riferimento a un altro database (' . implode( ', ', $altri ) . '): in scrittura solo tabelle del database della sorgente, ' . $cur . '.';
		$res = self::q( $c, 'SELECT LOWER(TABLE_NAME), TABLE_TYPE, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()' );
		$tab = array(); $vie = array();
		if ( $res instanceof mysqli_result ) {
			while ( $r = $res->fetch_row() ) {
				if ( ! in_array( (string) $r[0], $cl['nomi'], true ) ) continue;
				$tab[] = (string) $r[0];
				if ( $r[1] !== 'BASE TABLE' ) $vie[] = $r[0] . ' (' . ( $r[1] === 'VIEW' ? 'vista' : strtolower( (string) $r[1] ) ) . ')';
				elseif ( strcasecmp( (string) $r[2], 'InnoDB' ) !== 0 ) $vie[] = $r[0] . ' (' . $r[2] . ')';
			}
			$res->free();
		}
		if ( $vie ) return 'Viste, sequenze o tabelle non InnoDB (' . implode( ', ', $vie ) . '): la prova a secco non si può annullare, scrittura rifiutata.';
		$trg = array_values( array_intersect( $tab, self::colonna( $c, 'SELECT EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = DATABASE()' ) ) );
		if ( $trg ) return 'Tabelle con trigger (' . implode( ', ', $trg ) . '): scriverebbero altrove anche nella prova a secco, scrittura rifiutata.';
		$seq = array_values( array_intersect( $tab, self::colonna( $c, "SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND ( COLUMN_DEFAULT LIKE '%nextval%' OR COLUMN_DEFAULT LIKE '%next value%' OR GENERATION_EXPRESSION LIKE '%nextval%' )" ) ) );
		if ( $seq ) return 'Tabelle con valori da una sequenza (' . implode( ', ', $seq ) . '): la sequenza avanza anche nella prova a secco, scrittura rifiutata.';
		$fun = array_values( array_intersect( $cl['chiamate'], self::colonna( $c, 'SELECT ROUTINE_NAME FROM information_schema.ROUTINES' ) ) );
		if ( $fun ) return 'Funzioni memorizzate (' . implode( ', ', $fun ) . '): possono scrivere fuori dalla prova a secco, scrittura rifiutata.';
		$wp = self::parametri( 'wordpress' );
		if ( $wp && strtolower( (string) $wp['name'] ) === $cur ) {
			$fuori = array_values( array_filter( $tab, static function ( $t ) { return strpos( $t, strtolower( RDACADEMY_TABLE_PREFIX ) ) !== 0; } ) );
			if ( $fuori ) return 'La sorgente è il database di WordPress: in scrittura solo tabelle di RelDoxAcademy (' . RDACADEMY_TABLE_PREFIX . '…), non ' . implode( ', ', $fuori ) . '.';
		}
		return '';
	}

	/** F028: il valore di una tabella chiave/valore non si usa in condizioni (sarebbe un oracolo): '' oppure il motivo. */
	private static function kv_in_condizione( string $sk, array $nomi ): string {
		$kv = array_values( array_intersect( array_keys( self::KV ), $nomi ) );
		if ( ! $kv ) return '';
		if ( ! preg_match( '/\b(WHERE|HAVING|ORDER\s+BY|GROUP\s+BY)\b|\bON\s+(?!DUPLICATE\b)/i', $sk, $m, PREG_OFFSET_CAPTURE ) ) return '';
		$dopo = substr( $sk, (int) $m[0][1] );
		foreach ( $kv as $v ) {
			if ( preg_match( '/\b' . preg_quote( $v, '/' ) . '\b/i', $dopo ) ) return 'Il valore delle tabelle chiave/valore («' . $v . '») non si usa in condizioni, ordinamenti né unioni: filtra per «' . self::KV[ $v ] . '».';
		}
		return '';
	}

	private static function tabella_registro(): string { return RDACADEMY_TABLE_PREFIX . self::REGISTRO; }
	private static function tabella_gettoni(): string { return RDACADEMY_TABLE_PREFIX . self::GETTONI; }

	/** Append-only anche nel database (F42 c): trigger che rifiutano UPDATE e DELETE. Senza permesso TRIGGER resta la difesa per nome. */
	public static ?bool $blindato = null;

	private static function blinda( mysqli $c, string $t ): void {
		$nomi = array();
		foreach ( array( 'UPDATE' => 'nu', 'DELETE' => 'nd' ) as $ev => $suf ) {
			$nomi[] = strtolower( "{$t}_{$suf}" );
			if ( in_array( strtolower( "{$t}_{$suf}" ), self::colonna( $c, "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '" . $c->real_escape_string( $t ) . "'" ), true ) ) continue;
			self::q( $c, "CREATE TRIGGER `{$t}_{$suf}` BEFORE $ev ON `$t` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only'" );
		}
		// F028: sull hosting il permesso TRIGGER può mancare (MySQL 8 col registro binario): si verifica e si dichiara
		$ci = self::colonna( $c, "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '" . $c->real_escape_string( $t ) . "'" );
		if ( $t === self::tabella_registro() ) self::$blindato = ! array_diff( $nomi, $ci );
	}

	/** Consuma il gettone una volta sola (F42 a): chiave unica nel BASE, atomica fra due richieste insieme. */
	private static function consuma_gettone( string $gid ): bool {
		$p = self::parametri( 'base' );
		$c = $p ? self::apri( $p ) : null;
		if ( ! $c || $gid === '' ) return false;
		$t = self::tabella_gettoni();
		$ok = self::q( $c, "CREATE TABLE IF NOT EXISTS `$t` (gid CHAR(32) NOT NULL, quando DATETIME NOT NULL, PRIMARY KEY (gid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
		if ( $ok ) { self::blinda( $c, $t ); $ok = self::q( $c, "INSERT INTO `$t` (gid, quando) VALUES ('" . $c->real_escape_string( $gid ) . "', '" . gmdate( 'Y-m-d H:i:s', self::ora() ) . "')" ); }
		$c->close();
		return (bool) $ok;
	}

	/** Registro append-only sul BASE. false se non si può scrivere (allora non si scrive nemmeno sui dati). */
	private static function registra( array $r, array $s, string $tipo, ?int $righe, string $esito, string $msg ): bool {
		$p = self::parametri( 'base' );
		$c = $p ? self::apri( $p ) : null;
		if ( ! $c ) return false;
		$t = self::tabella_registro();
		$ok = self::q( $c, "CREATE TABLE IF NOT EXISTS `$t` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, quando DATETIME NOT NULL, utente BIGINT UNSIGNED NOT NULL DEFAULT 0, login VARCHAR(60) NOT NULL DEFAULT '', sorgente VARCHAR(40) NOT NULL DEFAULT '', db_nome VARCHAR(64) NOT NULL DEFAULT '', db_host VARCHAR(255) NOT NULL DEFAULT '', istruzione MEDIUMTEXT NOT NULL, tipo VARCHAR(12) NOT NULL, righe INT NULL, esito VARCHAR(20) NOT NULL, messaggio VARCHAR(500) NOT NULL DEFAULT '', PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
		if ( $ok ) {
			self::blinda( $c, $t );
			$st = $c->prepare( "INSERT INTO `$t` (quando, utente, login, sorgente, db_nome, db_host, istruzione, tipo, righe, esito, messaggio) VALUES (?,?,?,?,?,?,?,?,?,?,?)" );
			$ok = false;
			if ( $st ) {
				$q = gmdate( 'Y-m-d H:i:s', self::ora() ); $u = (int) ( $r['utente'] ?? 0 ); $l = substr( (string) ( $r['login'] ?? '' ), 0, 60 );
				$so = (string) ( $r['sorgente'] ?? '' ); $db = (string) ( $s['db'] ?? '' ); $h = (string) ( $s['host'] ?? '' ); $i = (string) ( $r['sql'] ?? '' );
				$m = substr( $msg, 0, 500 );
				$st->bind_param( 'sissssssiss', $q, $u, $l, $so, $db, $h, $i, $tipo, $righe, $esito, $m );
				try { $ok = $st->execute(); } catch ( mysqli_sql_exception $e ) { $ok = false; }
				$st->close();
			}
		}
		$c->close();
		return (bool) $ok;
	}

	private static function chiave_gettone( int $u ): string { return 'rdacademy_console_sql_' . $u; }

	private static function gettone_leggi( int $u ): ?array {
		$g = function_exists( 'get_transient' ) ? get_transient( self::chiave_gettone( $u ) ) : ( self::$gettoni[ $u ] ?? false );
		return is_array( $g ) ? $g : null;
	}

	private static function gettone_scrivi( int $u, ?array $g ): void {
		if ( function_exists( 'set_transient' ) ) {
			$g === null ? delete_transient( self::chiave_gettone( $u ) ) : set_transient( self::chiave_gettone( $u ), $g, self::CONFERMO_TTL );
			return;
		}
		if ( $g === null ) unset( self::$gettoni[ $u ] ); else self::$gettoni[ $u ] = $g;
	}

	private static function impronta( array $r, array $s ): string {
		return hash( 'sha256', implode( "\x1f", array( (string) $r['sorgente'], $s['db'], $s['host'], (int) $r['utente'], trim( (string) $r['sql'] ) ) ) );
	}

	/**
	 * La porta unica. $r: sorgente, sql, consenti (bool), azione ('esegui'|'prova'), conferma, utente (id), login.
	 * @return array{referto:array,colonne:array,dati:array,verdi:int,rossi:int,righe_prova?:int,scade?:int}
	 */
	public static function esegui( array $r ): array {
		$r += array( 'sorgente' => 'base', 'sql' => '', 'consenti' => false, 'azione' => 'esegui', 'conferma' => '', 'utente' => 0, 'login' => '' );
		$r['sql'] = (string) $r['sql'];
		$out = array( 'referto' => array(), 'colonne' => array(), 'dati' => array(), 'verdi' => 0, 'rossi' => 0 );
		$riga = static function ( string $l, string $t ) use ( &$out ) {
			$out['referto'][] = array( $l, $t );
			if ( $l === 'VERDE' ) $out['verdi']++;
			if ( $l === 'ROSSO' ) $out['rossi']++;
		};
		$S = self::sorgenti();
		$k = (string) $r['sorgente'];
		if ( ! isset( $S[ $k ] ) ) {
			$riga( 'ROSSO', 'Sorgente «' . $k . '» sconosciuta: rifiutata.' );
			self::registra( $r, array(), 'rifiuto', null, 'rifiutata', 'sorgente sconosciuta' );
			return $out;
		}
		$s  = $S[ $k ];
		$at = $s['db'] . ' @ ' . $s['host'];
		$rifiuta = static function ( string $m ) use ( $riga, $r, $s, $at ) {
			$riga( 'ROSSO', $m . ( $s['db'] !== '' ? ' (' . $at . ')' : '' ) );
			self::registra( $r, $s, 'rifiuto', null, 'rifiutata', $m );
		};
		if ( ! $s['in_uso'] ) {
			$rifiuta( $s['etichetta'] . ( $s['stato'] === 'spenta' ? ' spenta' : ' non configurata' ) . ': la console la rifiuta (non ricade sul BASE in silenzio).' );
			return $out;
		}
		$cl = self::classifica( $r['sql'] );
		if ( ! $cl['ok'] ) { $rifiuta( $cl['motivo'] ); return $out; }

		// ── lettura ─────────────────────────────────────────────
		if ( $cl['tipo'] === 'lettura' ) {
			$c = self::apri( (array) self::parametri( $k ) );
			if ( ! $c ) { $riga( 'ROSSO', 'Connessione non riuscita a ' . $at . '.' ); return $out; }
			$sc = self::segrete_citate( $c, $cl['nomi'] );
			if ( $sc ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): non si leggono né si usano in espressioni o condizioni; con SELECT * escono mascherate.' ); return $out; }
			// 2ª revisione F027, ALTO 1: con UNION, WITH o sottoquery il nome della colonna nel risultato non è più il suo
			list( $skl ) = self::scheletro( $r['sql'] );
			$ts = array_values( array_intersect( $cl['nomi'], self::tabelle_segrete( $c ) ) );
			if ( $ts && preg_match( '/\b(UNION|INTERSECT|EXCEPT|WITH|VALUES|LATERAL|JSON_TABLE)\b|\(\s*(SELECT|TABLE)\b/i', $skl ) ) {
				$c->close(); $rifiuta( 'Tabelle con colonne segrete (' . implode( ', ', $ts ) . '): solo SELECT semplice, senza UNION, WITH né sottoquery (le colonne segrete cambierebbero nome).' ); return $out;
			}
			$kvm = self::kv_in_condizione( $skl, $cl['nomi'] );
			if ( $kvm !== '' ) { $c->close(); $rifiuta( $kvm ); return $out; }
			$l = self::leggi_su( $c, $r['sql'], $cl['nomi'] );
			$c->close();
			if ( ! $l['ok'] ) { $riga( 'ROSSO', $cl['verbo'] . ' su ' . $at . ': ' . $l['errore'] ); return $out; }
			$out['colonne'] = $l['colonne']; $out['dati'] = $l['dati'];
			$riga( 'INFO', $cl['verbo'] . ' su ' . $at . ' · lettura in transazione READ ONLY · tempo massimo ' . self::TEMPO_MAX . ' s · ' . count( $l['dati'] ) . ' righe · ' . $l['ms'] . ' ms' );
			if ( $l['troncato'] ) $riga( 'INFO', 'Risultato troncato: al massimo ' . self::MAX_RIGHE . ' righe e ' . intdiv( self::MAX_BYTE, 1000000 ) . ' MB.' );
			$riga( 'INFO', 'Colonne segrete mascherate: ' . ( $l['mascherate'] ? implode( ', ', $l['mascherate'] ) : 'nessuna in questo risultato' ) );
			$riga( 'VERDE', 'Lettura eseguita su ' . $at . ': ' . count( $l['dati'] ) . ' righe.' );
			return $out;
		}

		// ── scrittura ───────────────────────────────────────────
		if ( ! $s['scrivibile'] ) { $rifiuta( 'Sorgente WordPress: «' . $cl['verbo'] . '» rifiutata. Sul database di WordPress solo lettura, sempre.' ); return $out; }
		if ( empty( $r['consenti'] ) ) { $rifiuta( 'Scrittura non consentita: spunta «Consenti scrittura» (solo BASE e sorgenti IN USO).' ); return $out; }
		if ( array_intersect( array( strtolower( self::tabella_registro() ), strtolower( self::tabella_gettoni() ) ), $cl['nomi'] ) ) { $rifiuta( 'Il registro della console non si tocca dalla console (append-only).' ); return $out; }
		$c = self::apri( (array) self::parametri( $k ) );
		if ( ! $c ) { $riga( 'ROSSO', 'Connessione non riuscita a ' . $at . '.' ); return $out; }
		$sc = self::segrete_citate( $c, $cl['nomi'] );
		if ( $sc ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): la console non le copia né le usa in scrittura.' ); return $out; }
		list( $skw ) = self::scheletro( $r['sql'] );
		$tsw = array_values( array_intersect( $cl['nomi'], self::tabelle_segrete( $c ) ) );
		if ( $tsw && preg_match( '/\b(SELECT|TABLE|LATERAL|JSON_TABLE)\b/i', $skw ) ) { $c->close(); $rifiuta( 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $tsw ) . '): in scrittura non si leggono (niente SELECT né TABLE), i segreti non si copiano.' ); return $out; }
		$kvm = self::kv_in_condizione( $skw, $cl['nomi'] );
		if ( $kvm !== '' ) { $c->close(); $rifiuta( $kvm ); return $out; }
		$mi = self::controlla_scrittura( $c, $cl );
		if ( $mi !== '' ) { $c->close(); $rifiuta( $mi ); return $out; }
		self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_MAX );
		// F028: anche dove max_statement_time non c è (MySQL 8), l attesa dei lucchetti ha il tetto
		self::q( $c, 'SET SESSION innodb_lock_wait_timeout = ' . self::TEMPO_MAX );
		self::q( $c, 'SET SESSION lock_wait_timeout = ' . self::TEMPO_MAX );
		$u  = (int) $r['utente'];
		$im = self::impronta( $r, $s );

		if ( $r['azione'] === 'prova' ) {
			if ( ! self::q( $c, 'START TRANSACTION' ) ) { $riga( 'ROSSO', 'Transazione non aperta su ' . $at . ': ' . self::err( $c ) ); $c->close(); return $out; }
			$ok = self::q( $c, $r['sql'] );
			$n  = $ok ? (int) $c->affected_rows : -1; $e = $ok ? '' : self::err( $c );
			self::q( $c, 'ROLLBACK' ); $c->close();
			if ( ! $ok ) { $rifiuta( 'Prova a secco non riuscita: ' . $e ); return $out; }
			if ( ! self::registra( $r, $s, 'prova', $n, 'annullata', 'prova a secco' ) ) {
				$riga( 'ROSSO', 'Registro sul BASE non scrivibile: senza registro non si scrive (' . $at . ').' ); return $out;
			}
			$scade = self::ora() + self::CONFERMO_TTL;
			self::gettone_scrivi( $u, array( 'impronta' => $im, 'righe' => $n, 'scade' => $scade, 'gid' => bin2hex( random_bytes( 16 ) ) ) );
			$out['righe_prova'] = $n; $out['scade'] = $scade;
			$riga( 'INFO', 'Prova a secco su ' . $at . ': ' . $cl['verbo'] . ' in una transazione annullata (tabelle InnoDB; MyISAM rifiutata).' );
			$riga( 'INFO', self::$blindato ? 'Registro: protetto da trigger contro UPDATE e DELETE.' : 'Registro: protetto solo per nome (il server non concede TRIGGER): la console non lo tocca, il database sì.' );
			$riga( 'INFO', 'Righe che cambierebbero: ' . $n . ' · nessun dato scritto (i contatori AUTO_INCREMENT possono avanzare: è InnoDB).' );
			$riga( 'INFO', 'Per scrivere davvero su ' . $at . ' scrivi CONFERMO e premi «Esegui» entro ' . intdiv( self::CONFERMO_TTL, 60 ) . ' minuti; vale solo per questa istruzione, su questa sorgente, una volta.' );
			return $out;
		}

		// azione «esegui»: CONFERMO + gettone della prova a secco
		if ( (string) $r['conferma'] !== 'CONFERMO' ) { $c->close(); $rifiuta( 'Manca «CONFERMO»: la scrittura non parte.' ); return $out; }
		$g = self::gettone_leggi( $u );
		if ( ! $g ) { $c->close(); $rifiuta( 'Nessuna prova a secco valida per questa istruzione: prima la prova a secco.' ); return $out; }
		if ( (int) $g['scade'] < self::ora() ) { self::gettone_scrivi( $u, null ); $c->close(); $rifiuta( 'CONFERMO scaduto (oltre ' . intdiv( self::CONFERMO_TTL, 60 ) . ' minuti): rifai la prova a secco.' ); return $out; }
		if ( ! hash_equals( (string) $g['impronta'], $im ) ) { $c->close(); $rifiuta( 'Il CONFERMO vale solo per l istruzione e la sorgente della prova a secco: rifai la prova.' ); return $out; }
		if ( ! self::consuma_gettone( (string) ( $g['gid'] ?? '' ) ) ) {
			self::gettone_scrivi( $u, null ); $c->close(); $rifiuta( 'CONFERMO già usato (o gettone non registrabile sul BASE): rifai la prova a secco.' ); return $out;
		}
		if ( ! self::registra( $r, $s, 'avvio', (int) $g['righe'], 'in_corso', 'scrittura confermata' ) ) {
			$c->close(); $riga( 'ROSSO', 'Registro sul BASE non scrivibile: senza registro non si scrive (' . $at . ').' ); return $out;
		}
		self::gettone_scrivi( $u, null );
		if ( ! self::q( $c, 'START TRANSACTION' ) ) { $riga( 'ROSSO', 'Transazione non aperta su ' . $at . ': ' . self::err( $c ) ); $c->close(); return $out; }
		$ok = self::q( $c, $r['sql'] );
		$n  = $ok ? (int) $c->affected_rows : -1;
		if ( ! $ok ) { $e = self::err( $c ); self::q( $c, 'ROLLBACK' ); $c->close(); $riga( 'ROSSO', 'Scrittura non riuscita su ' . $at . ': ' . $e ); self::registra( $r, $s, 'scrittura', null, 'errore', $e ); return $out; }
		if ( $n !== (int) $g['righe'] ) {
			self::q( $c, 'ROLLBACK' ); $c->close();
			$m = 'Righe ricontate: ' . $n . ' invece di ' . (int) $g['righe'] . ' della prova a secco → annullata, niente scritto. Rifai la prova.';
			$riga( 'ROSSO', $m . ' (' . $at . ')' ); self::registra( $r, $s, 'scrittura', $n, 'annullata', $m ); return $out;
		}
		$fatto = self::q( $c, 'COMMIT' ); $e = $fatto ? '' : self::err( $c ); $c->close();
		if ( ! $fatto ) { $riga( 'ROSSO', 'COMMIT non riuscito su ' . $at . ': ' . $e ); self::registra( $r, $s, 'scrittura', $n, 'errore', $e ); return $out; }
		self::registra( $r, $s, 'scrittura', $n, 'eseguita', 'scritta' );
		$riga( 'VERDE', $cl['verbo'] . ' eseguita su ' . $at . ': ' . $n . ' righe scritte; nel registro del BASE.' );
		return $out;
	}
}
