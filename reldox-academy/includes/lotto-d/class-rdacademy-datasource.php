<?php
/**
 * Rdacademy_DataSource — RESOLVER DELLE CONNESSIONI ALLE SORGENTI 3-5.
 * --------------------------------------------------------------------------
 * Copiato da Reldox_DataSource, RelDox CRD 3.55.09G, e ridotto.
 *
 * Risolve una sorgente dati (chiave della mappa Rdacademy_Sorgenti) in una
 * connessione mysqli verso il suo database dedicato, decifrando la password
 * salvata nel BASE.
 *
 * REGOLE (copiate):
 *  - Le credenziali delle sorgenti vivono cifrate nel BASE (`data_sources`).
 *  - RIPIEGO SUL BASE: se la sorgente non è configurata, è spenta o la
 *    connessione fallisce → get() ritorna la connessione BASE. Mai solleva.
 *  - Niente FK/transazioni tra database: qui si apre SOLO la connessione;
 *    l ordine di scrittura BASE → sorgente è del chiamante.
 *
 * TAGLI: KNOWN_KEYS (le chiavi vengono da Rdacademy_Sorgenti, un punto solo),
 * encrypt_pass/decrypt_pass (vivono in Rdacademy_Cifra con la busta `enc1:`),
 * SENTINEL_TABLE cablata su 'offerte' (la sentinella è la prima tabella che la
 * sorgente dichiara in `rdacademy_sorgenti_tabelle`).
 *
 * ⛔ CORRETTO RISPETTO A RELDOX, DICHIARATO:
 *  - get_row() e reset() costruivano l SQL con real_escape_string concatenato:
 *    qui query preparate (REGOLE § 5).
 *  - db_port era salvata ma IGNORATA a runtime (connect_params() non la
 *    passava e Reldox_DB::try_connect() non la conosce): qui la connessione
 *    della sorgente si apre CON la porta salvata.
 *  - occupancy() contava le tabelle; il passaggio a NON IN USO era negato anche
 *    con tabelle VUOTE (azzerare le credenziali di tabelle vuote non toglie la
 *    via a nessun dato). Qui conta anche le RIGHE, con COUNT(*) esatto, e il
 *    divieto vale quando c è almeno una riga.
 *  - NUOVO: rifiuto_database() — una sorgente non può puntare al database di
 *    WordPress, al BASE o al database di un altra sorgente (REGOLE § 3.5).
 *  - NUOVO: la connessione della sorgente parla UTC come il BASE (SPEC § 2.3).
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_DataSource {

	/** Connessioni aperte per chiave (mini-pool per richiesta). */
	private static array $pool = array();

	/** @var array<string,bool> cache di processo di is_provisioned() */
	private static array $prov_cache = array();

	private static function tab(): string {
		return '`' . RDACADEMY_TABLE_PREFIX . Rdacademy_Schema_Datasources_V1::TABLE . '`';
	}

	/* ============================================================= *
	 *  Lettura configurazione sorgente
	 * ============================================================= */

	/** Riga completa di una sorgente (con password cifrata). USO SOLO BACKEND. */
	public static function get_row( string $key ): ?array {
		if ( ! Rdacademy_Sorgenti::esiste( $key ) ) {
			return null;
		}
		return Rdacademy_DB::get_row( 'SELECT * FROM ' . self::tab() . ' WHERE source_key = ?', array( $key ), 's' );
	}

	/**
	 * Config «pubblica» per la UI: niente password, solo has_pass (presenza e
	 * forma, REGOLE § 5). Lo stato ha UNA fonte: state() (BUG-50 di RelDox).
	 */
	public static function get_public( string $key ): array {
		$row = self::get_row( $key );
		$st  = self::state( $key );
		$out = array(
			'source_key'  => $key,
			'n'           => Rdacademy_Sorgenti::numero( $key ),
			'label'       => Rdacademy_Sorgenti::nome( $key ),
			'db_host'     => '',
			'db_port'     => 3306,
			'db_name'     => '',
			'db_user'     => '',
			'charset'     => 'utf8mb4',
			'enabled'     => 0,
			'has_pass'    => false,
			'pass_forma'  => Rdacademy_Cifra::forma( '' ),
			'configured'  => false,
			'in_uso'      => false,
			'provisioned' => false,
			'state'       => $st,
			'tabelle'     => Rdacademy_Sorgenti::tabelle_di( $key ),
		);
		if ( ! $row ) {
			return $out;
		}
		$enc = (string) ( $row['db_pass_enc'] ?? '' );
		return array_merge( $out, array(
			'db_host'     => (string) ( $row['db_host'] ?? '' ),
			'db_port'     => (int) ( $row['db_port'] ?? 3306 ),
			'db_name'     => (string) ( $row['db_name'] ?? '' ),
			'db_user'     => (string) ( $row['db_user'] ?? '' ),
			'charset'     => (string) ( $row['charset'] ?? 'utf8mb4' ),
			'enabled'     => (int) ( $row['enabled'] ?? 0 ),
			'has_pass'    => $enc !== '',
			'pass_forma'  => Rdacademy_Cifra::forma( $enc ),
			'configured'  => ( $st !== 'not_configured' ),
			'in_uso'      => in_array( $st, array( 'live', 'unprovisioned' ), true ),
			'provisioned' => ( $st === 'live' ),
		) );
	}

	/**
	 * Parametri di connessione decifrati: [host, port, name, user, pass, charset].
	 * null se la sorgente non è configurata.
	 */
	public static function connect_params( string $key ): ?array {
		$row = self::get_row( $key );
		if ( ! $row || (string) ( $row['db_host'] ?? '' ) === '' ) {
			return null;
		}
		return array(
			'host'    => (string) $row['db_host'],
			'port'    => (int) ( $row['db_port'] ?? 3306 ),
			'name'    => (string) ( $row['db_name'] ?? '' ),
			'user'    => (string) ( $row['db_user'] ?? '' ),
			'pass'    => Rdacademy_Cifra::apri( (string) ( $row['db_pass_enc'] ?? '' ) ),
			'charset' => (string) ( $row['charset'] ?? 'utf8mb4' ),
		);
	}

	/**
	 * NUOVO — connessione ISOLATA coi parametri dati, CON la porta.
	 * Stessa forma di Rdacademy_DB::try_connect() (che non conosce la porta):
	 * regge i due modi di mysqli e non tocca il singleton del BASE.
	 *
	 * @return array{connection:?mysqli,ok:bool,error_message:string,latency_ms:float}
	 */
	public static function connetti( array $c ): array {
		$t0 = microtime( true );
		try {
			$conn = @new mysqli( (string) ( $c['host'] ?? '' ), (string) ( $c['user'] ?? '' ), (string) ( $c['pass'] ?? '' ),
				(string) ( $c['name'] ?? '' ), (int) ( $c['port'] ?? 3306 ) );
			if ( $conn->connect_error ) {
				return array( 'connection' => null, 'ok' => false, 'error_message' => (string) $conn->connect_error,
					'latency_ms' => round( ( microtime( true ) - $t0 ) * 1000, 2 ) );
			}
		} catch ( mysqli_sql_exception $e ) {
			return array( 'connection' => null, 'ok' => false, 'error_message' => $e->getMessage(),
				'latency_ms' => round( ( microtime( true ) - $t0 ) * 1000, 2 ) );
		}
		try {
			$conn->set_charset( (string) ( $c['charset'] ?? 'utf8mb4' ) ?: 'utf8mb4' );
			$conn->query( "SET time_zone = '+00:00'" );
		} catch ( mysqli_sql_exception $e ) {
			// charset/fuso non bloccano: la connessione c e (comportamento di try_connect).
		}
		return array( 'connection' => $conn, 'ok' => true, 'error_message' => '',
			'latency_ms' => round( ( microtime( true ) - $t0 ) * 1000, 2 ) );
	}

	/* ============================================================= *
	 *  Resolver connessione (con RIPIEGO sul BASE)
	 * ============================================================= */

	public static function get( string $key ): mysqli {
		if ( isset( self::$pool[ $key ] ) ) {
			return self::$pool[ $key ];
		}
		$row = self::get_row( $key );
		// Non configurata o spenta → BASE.
		if ( ! $row || (int) ( $row['enabled'] ?? 0 ) !== 1 || empty( $row['db_host'] ) ) {
			return Rdacademy_DB::get_connection();
		}
		$res = self::connetti( (array) self::connect_params( $key ) );
		if ( empty( $res['ok'] ) || ! ( $res['connection'] instanceof mysqli ) ) {
			error_log( "[RelDoxAcademy DataSource] '{$key}' non raggiungibile, ripiego sul BASE: " . ( $res['error_message'] ?? '' ) );
			return Rdacademy_DB::get_connection();
		}
		self::$pool[ $key ] = $res['connection'];
		return self::$pool[ $key ];
	}

	/** La connessione è configurata e accesa (NON «i dati vivono lì»: vedi is_provisioned()). */
	public static function is_active( string $key ): bool {
		$row = self::get_row( $key );
		return $row && (int) ( $row['enabled'] ?? 0 ) === 1 && ! empty( $row['db_host'] );
	}

	/** Svuota la cache del provisioning (e il pool). Da chiamare DOPO aver creato o azzerato. */
	public static function flush_provisioning( string $key = '' ): void {
		if ( '' === $key ) {
			self::$prov_cache = array();
			foreach ( self::$pool as $c ) { try { $c->close(); } catch ( Throwable $e ) {} }
			self::$pool = array();
		} else {
			unset( self::$prov_cache[ $key ] );
			if ( isset( self::$pool[ $key ] ) ) {
				try { self::$pool[ $key ]->close(); } catch ( Throwable $e ) {}
				unset( self::$pool[ $key ] );
			}
		}
	}

	/** La sentinella: la PRIMA tabella che la sorgente dichiara. '' se non ne dichiara. */
	public static function sentinella( string $key ): string {
		$t = Rdacademy_Sorgenti::tabelle_di( $key );
		return $t ? (string) $t[0] : '';
	}

	/**
	 * BUG-19 di RelDox — I DATI VIVONO DAVVERO SUL DATABASE DELLA SORGENTE?
	 * Tre condizioni, tutte necessarie (copiate): attiva; la connessione
	 * risolta NON è ricaduta sul BASE (verificato con SELECT DATABASE()); la
	 * sentinella esiste su quello storage. Ogni incertezza → false.
	 */
	public static function is_provisioned( string $key ): bool {
		if ( isset( self::$prov_cache[ $key ] ) ) {
			return self::$prov_cache[ $key ];
		}
		self::$prov_cache[ $key ] = false;
		if ( ! self::is_active( $key ) ) {
			return false;
		}
		$sentinel = self::sentinella( $key );
		if ( '' === $sentinel ) {
			return false;
		}
		$conn = self::get( $key );
		if ( $conn === Rdacademy_DB::get_connection() ) {
			return false;
		}
		$expected = (string) ( self::connect_params( $key )['name'] ?? '' );
		try {
			$res    = $conn->query( 'SELECT DATABASE() AS d' );
			$dbname = ( $res instanceof mysqli_result ) ? (string) ( $res->fetch_assoc()['d'] ?? '' ) : '';
			if ( $res instanceof mysqli_result ) { $res->free(); }
		} catch ( mysqli_sql_exception $e ) {
			return false;
		}
		if ( '' === $dbname || $dbname !== $expected ) {
			return false;
		}
		$ok = self::tabella_su( $conn, RDACADEMY_TABLE_PREFIX . $sentinel ) === true;
		self::$prov_cache[ $key ] = $ok;
		return $ok;
	}

	/**
	 * NUOVO — «questa tabella esiste su questa connessione?» con statement
	 * preparato su information_schema (al posto di SHOW TABLES LIKE '{$tbl}').
	 * null = non si è potuto chiedere (un dubbio non è un NO).
	 */
	public static function tabella_su( mysqli $conn, string $full ): ?bool {
		try {
			$st = $conn->prepare( 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?' );
			if ( ! $st ) { return null; }
			$st->bind_param( 's', $full );
			if ( ! $st->execute() ) { $st->close(); return null; }
			$n = 0;
			$st->bind_result( $n );
			$st->fetch();
			$st->close();
			return (int) $n > 0;
		} catch ( Throwable $e ) {
			return null;
		}
	}

	/**
	 * SVILUPPO 33 di RelDox — QUANTO C È DENTRO, A PRESCINDERE DA `enabled`.
	 * Connessione ISOLATA coi parametri salvati; elenca le tabelle col
	 * prefisso del prodotto e (CORRETTO) ne conta le righe con COUNT(*).
	 * Sola lettura.
	 *
	 * @return array{reachable:bool,tables:int,rows:int,popolate:array<string,int>,error:string}
	 */
	public static function occupancy( string $key ): array {
		$out = array( 'reachable' => false, 'tables' => 0, 'rows' => 0, 'popolate' => array(), 'error' => '' );
		$par = self::connect_params( $key );
		if ( ! $par || '' === trim( $par['name'] ) ) {
			$out['reachable'] = true; // niente da raggiungere: la sorgente è già vuota
			return $out;
		}
		$res = self::connetti( $par );
		if ( empty( $res['ok'] ) ) {
			$out['error'] = (string) $res['error_message'];
			return $out;
		}
		$conn = $res['connection'];
		try {
			$like = str_replace( array( '\\', '_', '%' ), array( '\\\\', '\\_', '\\%' ), RDACADEMY_TABLE_PREFIX ) . '%';
			$st   = $conn->prepare( "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' AND table_name LIKE ?" );
			$st->bind_param( 's', $like );
			$st->execute();
			$r    = $st->get_result();
			$tabs = array();
			while ( $x = $r->fetch_row() ) { $tabs[] = (string) $x[0]; }
			$st->close();
			$out['reachable'] = true;
			$out['tables']    = count( $tabs );
			foreach ( $tabs as $t ) {
				$c = $conn->query( 'SELECT COUNT(*) FROM `' . str_replace( '`', '``', $t ) . '`' );
				$n = ( $c instanceof mysqli_result ) ? (int) $c->fetch_row()[0] : -1;
				if ( $c instanceof mysqli_result ) { $c->free(); }
				if ( $n < 0 ) {
					$out['error'] = 'conteggio non riuscito su ' . $t; // un dubbio non è uno zero
					$out['reachable'] = false;
				} elseif ( $n > 0 ) {
					$out['popolate'][ $t ] = $n;
					$out['rows'] += $n;
				}
			}
		} catch ( Throwable $e ) {
			$out['reachable'] = false;
			$out['error']     = $e->getMessage();
		}
		$conn->close();
		return $out;
	}

	/**
	 * SVILUPPO 33 di RelDox — NON IN USO: la riga torna vuota.
	 * UPDATE, non DELETE: la riga di registro resta (source_key + label), i
	 * DATI di connessione se ne vanno. Nessun DDL. ⛔ Preparata.
	 */
	public static function reset( string $key ): bool {
		Rdacademy_DB::clear_last_error();
		Rdacademy_DB::execute(
			'UPDATE ' . self::tab() . " SET db_host = '', db_port = 3306, db_name = '', db_user = '', db_pass_enc = NULL, enabled = 0 WHERE source_key = ?",
			array( $key ), 's'
		);
		self::flush_provisioning( $key );
		if ( class_exists( 'Rdacademy_Table_Owner' ) ) { Rdacademy_Table_Owner::flush(); }
		return Rdacademy_DB::last_error() === '';
	}

	/** Lo spegnimento dopo un provisioning fallito: la configurazione resta, per correggerla. */
	public static function spegni( string $key ): bool {
		Rdacademy_DB::clear_last_error();
		Rdacademy_DB::execute( 'UPDATE ' . self::tab() . ' SET enabled = 0 WHERE source_key = ?', array( $key ), 's' );
		self::flush_provisioning( $key );
		if ( class_exists( 'Rdacademy_Table_Owner' ) ) { Rdacademy_Table_Owner::flush(); }
		return Rdacademy_DB::last_error() === '';
	}

	/**
	 * Copiato (BUG-19 di RelDox): quattro valori.
	 *   not_configured · disabled (configurata ma spenta) · unprovisioned (accesa,
	 *   senza tabelle sul suo database) · live (accesa e provisionata).
	 * La UI ne mostra due: IN USO (live|unprovisioned) / NON IN USO.
	 */
	public static function state( string $key ): string {
		$row = self::get_row( $key );
		if ( ! $row || empty( $row['db_host'] ) ) {
			return 'not_configured';
		}
		if ( (int) ( $row['enabled'] ?? 0 ) !== 1 ) {
			return 'disabled';
		}
		return self::is_provisioned( $key ) ? 'live' : 'unprovisioned';
	}

	/* ============================================================= *
	 *  NUOVO — il database di una sorgente non è di nessun altro
	 * ============================================================= */

	/** localhost / 127.0.0.1 / ::1 sono lo stesso host; la porta non conta (copiato da Reldox_Table_Owner::norm_host). */
	public static function norm_host( string $host ): string {
		$h = strtolower( trim( $host ) );
		if ( $h === '' ) { return ''; }
		$h = (string) preg_replace( '/:\d+$/', '', $h );
		if ( $h === 'localhost' || $h === '127.0.0.1' || $h === '::1' ) { return 'localhost'; }
		return $h;
	}

	/**
	 * Messaggio di rifiuto, o '' se il database è libero. Stesso server e
	 * stesso nome = stesso database. REGOLE § 3.5: il database di WordPress non
	 * si tocca, il BASE non è una sorgente, una sorgente non ne condivide il
	 * database con un altra.
	 */
	public static function rifiuto_database( string $key, string $host, string $name ): string {
		$h = self::norm_host( $host );
		$n = strtolower( trim( $name ) );
		if ( $h === '' || $n === '' ) {
			return '';
		}
		$wp_host = defined( 'DB_HOST' ) ? self::norm_host( (string) DB_HOST ) : '';
		$wp_name = defined( 'DB_NAME' ) ? strtolower( (string) DB_NAME ) : '';
		if ( $h === $wp_host && $n === $wp_name ) {
			return 'Rifiutato: «' . $name . '» è il database di WordPress, che non si tocca mai e non contiene tabelle del prodotto.';
		}
		$base = Rdacademy_DB_Config::get();
		if ( $h === self::norm_host( (string) $base['host'] ) && $n === strtolower( (string) $base['name'] ) ) {
			return 'Rifiutato: «' . $name . '» è il BASE del prodotto. Una sorgente vive su un database dedicato; se non serve, resta NON IN USO e ricade sul BASE da sola.';
		}
		foreach ( Rdacademy_Sorgenti::chiavi() as $altra ) {
			if ( $altra === $key ) { continue; }
			$p = self::connect_params( $altra );
			if ( $p && self::norm_host( $p['host'] ) === $h && strtolower( $p['name'] ) === $n ) {
				return 'Rifiutato: «' . $name . '» è già il database della sorgente «' . Rdacademy_Sorgenti::nome( $altra ) . '».';
			}
		}
		return '';
	}
}
