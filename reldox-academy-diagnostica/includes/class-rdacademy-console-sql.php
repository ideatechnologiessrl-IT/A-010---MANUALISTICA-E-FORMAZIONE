<?php
/**
 * Rdacademy_Console_SQL — IL MOTORE DELLA CONSOLE SQL (banco standard n. 2, area «Database e sorgenti»).
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * RelDoxAcademy 0.1.11A (Formazione F026, voce F29). Mockup MOCKUP-CONSOLE-SQL-v1 approvato (D45, owner
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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rdacademy_Console_SQL {

	const MAX_RIGHE    = 1000;
	const TEMPO_MAX    = 10;
	const CONFERMO_TTL = 300;
	const REGISTRO     = 'console_registro';

	const LETTURE        = array( 'SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'WITH' );
	const SCRITTURE      = array( 'INSERT', 'UPDATE', 'DELETE', 'REPLACE' );
	const SEMPRE_VIETATE = array( 'DROP', 'TRUNCATE', 'ALTER', 'MODIFY', 'RENAME' );

	/** Vietati in qualunque punto dell istruzione (sullo scheletro, senza stringhe né commenti). */
	const VIETATI_OVUNQUE = array(
		'/\bINTO\s+(OUTFILE|DUMPFILE)\b/i'                     => '«SELECT … INTO OUTFILE/DUMPFILE» rifiutata: la console non scrive file.',
		'/\bANALYZE\b/i'                                       => 'EXPLAIN ANALYZE e ANALYZE rifiutati: eseguono davvero l istruzione.',
		'/\bLOAD_FILE\s*\(/i'                                  => 'LOAD_FILE() rifiutata: la console non legge file del server.',
		'/\bFOR\s+(UPDATE|SHARE)\b|\bLOCK\s+IN\s+SHARE\s+MODE\b/i' => 'FOR UPDATE, FOR SHARE e LOCK IN SHARE MODE rifiutati: la console non prende lucchetti.',
		'/\b(GET_LOCK|RELEASE_LOCK|RELEASE_ALL_LOCKS|MASTER_POS_WAIT|MASTER_GTID_WAIT|SOURCE_POS_WAIT)\s*\(/i' => 'GET_LOCK e funzioni d attesa rifiutate.',
		'/\b(SLEEP|BENCHMARK)\s*\(/i'                          => 'SLEEP e BENCHMARK rifiutati: tengono occupato il server.',
	);

	/** Colonne segrete note: si mascherano nei risultati (nome che contiene uno di questi pezzi). */
	const SEGRETE = array( 'pass', 'pwd', 'token', 'gettone', 'secret', 'segret', 'api_key', 'apikey', 'activation_key', 'hash', 'salt', 'nonce', '_enc', 'chiave' );

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
				if ( $c === '`' ) { $ids[] = $val; $out .= ' `id` '; } else { $out .= " '' "; }
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
		$tipo = '';
		if ( in_array( $verbo, self::SCRITTURE, true ) ) {
			$tipo = 'scrittura';
		} elseif ( in_array( $verbo, self::LETTURE, true ) ) {
			$tipo = 'lettura';
			if ( $verbo === 'WITH' && preg_match( '/\b(INSERT|UPDATE|DELETE|REPLACE)\b/i', $sk, $m ) ) {
				$tipo = 'scrittura'; $verbo = strtoupper( $m[1] );
			}
		}
		if ( $tipo === '' ) {
			return $no( '«' . ( $verbo !== '' ? $verbo : substr( $testa, 0, 12 ) ) . '» non ammessa: in lettura SELECT, SHOW, DESCRIBE, EXPLAIN, WITH; in scrittura solo INSERT, UPDATE, DELETE, REPLACE.', $verbo );
		}
		if ( $tipo === 'lettura' && preg_match( '/\bINTO\b/i', $sk ) ) {
			return $no( '«INTO» in una lettura rifiutato: la console non scrive file né variabili.', $verbo );
		}
		preg_match_all( '/[A-Za-z_][A-Za-z0-9_$]*/', $sk, $mm );
		$nomi = array_values( array_unique( array_map( 'strtolower', array_merge( $mm[0], $ids ) ) ) );
		return array( 'ok' => true, 'tipo' => $tipo, 'verbo' => $verbo, 'motivo' => '', 'nomi' => $nomi );
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
		return $c->errno ? '(' . $c->errno . ') ' . $c->error : 'errore sconosciuto';
	}

	private static function apri( array $p ): ?mysqli {
		$r = Rdacademy_DataSource::connetti( $p );
		return ( ! empty( $r['ok'] ) && $r['connection'] instanceof mysqli ) ? $r['connection'] : null;
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
	public static function leggi_su( mysqli $c, string $sql ): array {
		if ( ! self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_MAX ) ) {
			self::q( $c, 'SET SESSION MAX_EXECUTION_TIME = ' . ( self::TEMPO_MAX * 1000 ) );
		}
		if ( ! self::q( $c, 'SET SESSION TRANSACTION READ ONLY' ) || ! self::q( $c, 'START TRANSACTION READ ONLY' ) ) {
			return array( 'ok' => false, 'errore' => 'transazione in sola lettura non aperta: ' . self::err( $c ) );
		}
		$t0  = microtime( true );
		$res = self::q( $c, $sql );
		if ( $res === false ) { $e = self::err( $c ); self::q( $c, 'ROLLBACK' ); return array( 'ok' => false, 'errore' => $e ); }
		$col = array(); $dati = array(); $tronco = false;
		if ( $res instanceof mysqli_result ) {
			foreach ( $res->fetch_fields() as $f ) $col[] = (string) $f->name;
			while ( $row = $res->fetch_row() ) {
				if ( count( $dati ) >= self::MAX_RIGHE ) { $tronco = true; break; }
				$dati[] = $row;
			}
			$res->free();
		}
		self::q( $c, 'ROLLBACK' );
		$mascherate = array();
		foreach ( $col as $i => $nome ) {
			if ( ! self::segreta( $nome ) ) continue;
			$mascherate[] = $nome;
			foreach ( $dati as &$r ) { if ( $r[ $i ] !== null ) $r[ $i ] = '•••• (mascherata)'; }
			unset( $r );
		}
		return array( 'ok' => true, 'colonne' => $col, 'dati' => $dati, 'troncato' => $tronco, 'mascherate' => $mascherate, 'ms' => (int) round( ( microtime( true ) - $t0 ) * 1000 ) );
	}

	/** Tabelle non InnoDB del database corrente toccate dall istruzione (la prova a secco non le può annullare). */
	private static function non_innodb( mysqli $c, array $nomi ): array {
		$res = self::q( $c, "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND ENGINE <> 'InnoDB'" );
		$out = array();
		if ( $res instanceof mysqli_result ) {
			while ( $r = $res->fetch_row() ) { if ( in_array( strtolower( (string) $r[0] ), $nomi, true ) ) $out[] = $r[0] . ' (' . $r[1] . ')'; }
		}
		return $out;
	}

	private static function tabella_registro(): string { return RDACADEMY_TABLE_PREFIX . self::REGISTRO; }

	/** Registro append-only sul BASE. false se non si può scrivere (allora non si scrive nemmeno sui dati). */
	private static function registra( array $r, array $s, string $tipo, ?int $righe, string $esito, string $msg ): bool {
		$p = self::parametri( 'base' );
		$c = $p ? self::apri( $p ) : null;
		if ( ! $c ) return false;
		$t = self::tabella_registro();
		$ok = self::q( $c, "CREATE TABLE IF NOT EXISTS `$t` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, quando DATETIME NOT NULL, utente BIGINT UNSIGNED NOT NULL DEFAULT 0, login VARCHAR(60) NOT NULL DEFAULT '', sorgente VARCHAR(40) NOT NULL DEFAULT '', db_nome VARCHAR(64) NOT NULL DEFAULT '', db_host VARCHAR(255) NOT NULL DEFAULT '', istruzione MEDIUMTEXT NOT NULL, tipo VARCHAR(12) NOT NULL, righe INT NULL, esito VARCHAR(20) NOT NULL, messaggio VARCHAR(500) NOT NULL DEFAULT '', PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
		if ( $ok ) {
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
			$l = self::leggi_su( $c, $r['sql'] );
			$c->close();
			if ( ! $l['ok'] ) { $riga( 'ROSSO', $cl['verbo'] . ' su ' . $at . ': ' . $l['errore'] ); return $out; }
			$out['colonne'] = $l['colonne']; $out['dati'] = $l['dati'];
			$riga( 'INFO', $cl['verbo'] . ' su ' . $at . ' · lettura in transazione READ ONLY · tempo massimo ' . self::TEMPO_MAX . ' s · ' . count( $l['dati'] ) . ' righe · ' . $l['ms'] . ' ms' );
			if ( $l['troncato'] ) $riga( 'INFO', 'Risultato troncato a ' . self::MAX_RIGHE . ' righe.' );
			$riga( 'INFO', 'Colonne segrete mascherate: ' . ( $l['mascherate'] ? implode( ', ', $l['mascherate'] ) : 'nessuna in questo risultato' ) );
			$riga( 'VERDE', 'Lettura eseguita su ' . $at . ': ' . count( $l['dati'] ) . ' righe.' );
			return $out;
		}

		// ── scrittura ───────────────────────────────────────────
		if ( ! $s['scrivibile'] ) { $rifiuta( 'Sorgente WordPress: «' . $cl['verbo'] . '» rifiutata. Sul database di WordPress solo lettura, sempre.' ); return $out; }
		if ( empty( $r['consenti'] ) ) { $rifiuta( 'Scrittura non consentita: spunta «Consenti scrittura» (solo BASE e sorgenti IN USO).' ); return $out; }
		if ( in_array( strtolower( self::tabella_registro() ), $cl['nomi'], true ) ) { $rifiuta( 'Il registro della console non si tocca dalla console (append-only).' ); return $out; }
		$c = self::apri( (array) self::parametri( $k ) );
		if ( ! $c ) { $riga( 'ROSSO', 'Connessione non riuscita a ' . $at . '.' ); return $out; }
		$mi = self::non_innodb( $c, $cl['nomi'] );
		if ( $mi ) { $c->close(); $rifiuta( 'Tabelle non InnoDB (' . implode( ', ', $mi ) . '): la prova a secco non si può annullare, scrittura rifiutata.' ); return $out; }
		self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_MAX );
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
			self::gettone_scrivi( $u, array( 'impronta' => $im, 'righe' => $n, 'scade' => $scade ) );
			$out['righe_prova'] = $n; $out['scade'] = $scade;
			$riga( 'INFO', 'Prova a secco su ' . $at . ': ' . $cl['verbo'] . ' in una transazione annullata (tabelle InnoDB; MyISAM rifiutata).' );
			$riga( 'INFO', 'Righe che cambierebbero: ' . $n . ' · nessun dato scritto.' );
			$riga( 'INFO', 'Per scrivere davvero su ' . $at . ' scrivi CONFERMO e premi «Esegui» entro ' . intdiv( self::CONFERMO_TTL, 60 ) . ' minuti; vale solo per questa istruzione, su questa sorgente, una volta.' );
			return $out;
		}

		// azione «esegui»: CONFERMO + gettone della prova a secco
		if ( (string) $r['conferma'] !== 'CONFERMO' ) { $c->close(); $rifiuta( 'Manca «CONFERMO»: la scrittura non parte.' ); return $out; }
		$g = self::gettone_leggi( $u );
		if ( ! $g ) { $c->close(); $rifiuta( 'Nessuna prova a secco valida per questa istruzione: prima la prova a secco.' ); return $out; }
		if ( (int) $g['scade'] < self::ora() ) { self::gettone_scrivi( $u, null ); $c->close(); $rifiuta( 'CONFERMO scaduto (oltre ' . intdiv( self::CONFERMO_TTL, 60 ) . ' minuti): rifai la prova a secco.' ); return $out; }
		if ( ! hash_equals( (string) $g['impronta'], $im ) ) { $c->close(); $rifiuta( 'Il CONFERMO vale solo per l istruzione e la sorgente della prova a secco: rifai la prova.' ); return $out; }
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
