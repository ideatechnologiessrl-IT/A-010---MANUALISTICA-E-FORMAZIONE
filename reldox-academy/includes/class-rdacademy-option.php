<?php
/**
 * Rdacademy_Option — LO STATO DEL PRODOTTO VIVE SUL BASE, NON SUL DATABASE DI WORDPRESS.
 *
 * Copiato da Reldox_Option (RelDox CRD 3.55.09G, BUG-26 lotti 1 e 2).
 *
 * PERCHE ESISTE — ordine dell owner (S89 di RelDox), verbatim:
 *   «E DA SEMPRE VIETATO SCRIVERE SUL _1. L AMBIENTE WP DEVE ESSERE
 *    INDIPENDENTE DAL MONDO RELDOX.» RP-_1-NON-E-DI-RELDOX.
 *
 * NESSUN UOVO-E-GALLINA: `Rdacademy_DB_Config` non contiene un solo get_option, le
 * credenziali del BASE arrivano solo dalle costanti RDACADEMY_DB_* di wp-config.php.
 *
 * DUE MODI DI MYSQLI, NON UNO (sanatoria di VIOLAZIONE 72 in RelDox): tutte le
 * query passano da q(), p() e x(), che reggono sia mysqli_report OFF sia
 * ERROR|STRICT (alzato dal Provisioning Guard attorno a ogni maybe_rebuild).
 *
 * TAGLI (lotto A, «ponte minimo»): il registro del debito di BUG-36
 * (DA_PORTARE_FUORI_DAL_1, SPECIE_AL_RESET, sweep_ancora_lette), lo SWEEP delle
 * orfane dal _1 e il PARCHEGGIO del percorso di reset. Il guscio nasce senza
 * chiavi sul _1 da migrare: resta il registro delle esenzioni (le poche chiavi
 * di avvio che DEVONO stare sul _1) e il ponte bridge_get/bridge_del, che il
 * Provisioning Guard usa per vedere i flag.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Option {

	/** Nome NUDO della tabella. Vive sul BASE per definizione: e infrastruttura. */
	const TABLE = 'options';

	/**
	 * IL REGISTRO DELLE ESENZIONI — cio' che resta sul `_1` PER DISEGNO.
	 *
	 * QUESTO E' L'UNICO PUNTO IN CUI SI DICHIARA CHE UNA CHIAVE NON PUO' USCIRE
	 * DAL DATABASE DI WORDPRESS. Chi ha bisogno dell'elenco lo chiede a
	 * `keep_per_disegno()`; nessuno lo ricopia. La riga porta con se' il MOTIVO,
	 * perche' un elenco senza motivi diventa il posto dove si nasconde cio' che
	 * non si ha voglia di convertire (copiato da RelDox, BUG-36 passo 1).
	 * TAGLIO: le chiavi del ponte del reset, che nel guscio non esiste.
	 */
	const ESENTI_DAL_1 = array(

		// -- L avvio: si leggono PRIMA che Rdacademy_Option esista
		'rdacademy_db_version'     => 'letta prima di aprire la connessione BASE, per decidere se lo schema va aggiornato',
		'rdacademy_booted_version' => 'avvio del plugin, prima che Rdacademy_Option esista',
		'rdacademy_just_activated' => 'attivazione del plugin: gira quando il database BASE potrebbe non esistere ancora',
		'rdacademy_installed_at'   => 'identita dell installazione, letta dal pannello anche a connessione caduta',
		'rdacademy_version'        => 'identita dell installazione, letta dal pannello anche a connessione caduta',

		// -- Memoria di WordPress, non del prodotto
		'rdacademy_flush_rewrite'  => 'ciclo delle rewrite rules: il destinatario e WordPress, l archivio giusto e il suo',
	);

	/**
	 * Le chiavi che lo sweep non deve toccare, DERIVATE dal registro.
	 *
	 * Esiste perche' `SWEEP_KEEP` era una seconda lista scritta a mano che
	 * ripeteva una voce del registro con un secondo motivo accanto: la stessa
	 * famiglia della VIOLAZIONE 54. Chi legge chiede qui, e qui non c'e' un
	 * elenco: c'e' una lettura del registro.
	 *
	 * @return string[] nomi di chiave, senza motivi.
	 * @since  3.16.56
	 */
	public static function keep_per_disegno(): array {
		return array_keys( self::ESENTI_DAL_1 );
	}

	/**
	 * Il motivo dichiarato per una chiave esente, o stringa vuota.
	 *
	 * @param  string $key nome della chiave.
	 * @return string motivo, vuoto se la chiave non e' esente.
	 * @since  3.16.56
	 */
	public static function motivo_esenzione( string $key ): string {
		return isset( self::ESENTI_DAL_1[ $key ] ) ? (string) self::ESENTI_DAL_1[ $key ] : '';
	}

	/** Cache di richiesta: null = mai caricata. Evita 153 query per richiesta. */
	private static $cache = null;

	/** True quando il CREATE e gia stato tentato in questa richiesta. */
	private static $installed = false;

	/** Ultimo errore MySQL utile alla diagnostica. */
	private static $last_error = '';

	/** Ultimo codice MySQL: 1146 tabella assente, 1050 tabella gia esistente. */
	private static $last_errno = 0;

	/** Esito dell unico tentativo di CREATE fatto in questa richiesta. */
	private static $install_ok = false;

	/** True quando l archivio non e leggibile: get() sta servendo i default. */
	private static $degraded = false;

	/* ===================================================================== *
	 *  INFRASTRUTTURA
	 * ===================================================================== */

	private static function prefix(): string {
		return defined( 'RDACADEMY_TABLE_PREFIX' ) ? RDACADEMY_TABLE_PREFIX : 'rdacademy_';
	}

	/** Nome completo della tabella. Sempre nudo: il BASE non si qualifica mai. */
	public static function table(): string {
		return self::prefix() . self::TABLE;
	}

	/**
	 * Connessione BASE. Sempre e solo il `_2`: questa tabella non e di modulo e
	 * non passa dal risolutore di sorgenti, altrimenti lo stato del prodotto
	 * seguirebbe la sorgente accesa invece di restare uno solo.
	 *
	 * @return mysqli|null
	 */
	private static function conn() {
		if ( ! class_exists( 'Rdacademy_DB' ) ) {
			return null;
		}
		try {
			$c = Rdacademy_DB::get_connection();
			return ( $c instanceof mysqli ) ? $c : null;
		} catch ( Throwable $e ) {
			self::$last_error = $e->getMessage();
			return null;
		}
	}

	/* --------------------------------------------------------------------- *
	 *  ESECUZIONE A PROVA DI ENTRAMBI I MODI DI MYSQLI (3.16.33)
	 *
	 *  Con mysqli_report OFF (cio che WordPress imposta quando wpdb si connette)
	 *  una query fallita torna false e popola $conn->errno. Con mysqli_report
	 *  ERROR|STRICT — che il Provisioning Guard alza attorno al maybe_rebuild di
	 *  OGNI classe schema, e lo Strumento 74 alza a mano — la stessa query
	 *  LANCIA mysqli_sql_exception. Nessun punto di questa classe puo permettersi
	 *  di conoscere uno solo dei due modi: e cio che ha prodotto VIOLAZIONE 72.
	 * --------------------------------------------------------------------- */

	/** Registra codice e messaggio, da un ritorno false o da un eccezione. */
	private static function note_error( $conn, $e = null ): void {
		if ( $e instanceof mysqli_sql_exception ) {
			self::$last_errno = (int) $e->getCode();
			self::$last_error = $e->getMessage();
			if ( 0 === self::$last_errno && $conn instanceof mysqli ) {
				self::$last_errno = (int) $conn->errno;
			}
			return;
		}
		if ( $conn instanceof mysqli ) {
			self::$last_errno = (int) $conn->errno;
			self::$last_error = (string) $conn->error;
		}
	}

	/** query() che non lancia mai: torna false come farebbe con report OFF. */
	private static function q( $conn, string $sql ) {
		self::$last_errno = 0;
		self::$last_error = '';
		try {
			$res = @$conn->query( $sql );
			if ( false === $res ) {
				self::note_error( $conn );
			}
			return $res;
		} catch ( mysqli_sql_exception $e ) {
			self::note_error( $conn, $e );
			return false;
		}
	}

	/** prepare() che non lancia mai: torna false. */
	private static function p( $conn, string $sql ) {
		self::$last_errno = 0;
		self::$last_error = '';
		try {
			$stmt = @$conn->prepare( $sql );
			if ( ! $stmt ) {
				self::note_error( $conn );
				return false;
			}
			return $stmt;
		} catch ( mysqli_sql_exception $e ) {
			self::note_error( $conn, $e );
			return false;
		}
	}

	/** execute() che non lancia mai: torna un booleano. */
	private static function x( $conn, $stmt ): bool {
		try {
			$ok = $stmt->execute();
			if ( ! $ok ) {
				self::$last_errno = (int) $stmt->errno;
				self::$last_error = (string) $stmt->error;
			}
			return (bool) $ok;
		} catch ( mysqli_sql_exception $e ) {
			self::note_error( $conn, $e );
			return false;
		}
	}

	/**
	 * CREATE TABLE IF NOT EXISTS. Idempotente, tentato UNA volta per richiesta e
	 * solo quando serve davvero (cioe alla prima lettura che trova la tabella
	 * mancante): a regime non c e nessun DDL per richiesta.
	 */
	public static function install(): bool {
		if ( self::$installed ) {
			return self::$install_ok;
		}
		self::$installed  = true;
		self::$install_ok = false;

		$conn = self::conn();
		if ( ! $conn ) {
			return false;
		}

		$t   = self::table();
		$sql = "CREATE TABLE IF NOT EXISTS `{$t}` (
				`option_name`  VARCHAR(191) NOT NULL,
				`option_value` LONGTEXT NULL,
				`updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`option_name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

		$ok = ( false !== self::q( $conn, $sql ) );

		// 1050 «tabella gia esistente» non e un guasto: e l idempotenza che urta
		// contro l esistente. Stessa grammatica del Provisioning Guard e dello
		// Strumento 74 (una sola classificazione per tutto il prodotto).
		if ( ! $ok && 1050 === self::$last_errno ) {
			$ok = true;
		}

		self::$install_ok = $ok;
		return $ok;
	}

	/**
	 * Carica l intero archivio in memoria. Le chiavi sono poche decine: una
	 * sola SELECT costa meno di una query per chiave, e rende `get()` gratuito.
	 * Se la tabella non esiste (errno 1146) la crea e riparte da archivio vuoto:
	 * e esattamente il caso della prima installazione dopo BUG-26.
	 */
	private static function load(): void {
		if ( self::$cache !== null ) {
			return;
		}

		$cache = array();

		$conn = self::conn();
		if ( ! $conn ) {
			self::$cache    = $cache;
			self::$degraded = true;
			return;
		}

		$t   = self::table();
		$sql = "SELECT `option_name`, `option_value` FROM `{$t}`";

		$res = self::q( $conn, $sql );

		// 1146 = la tabella non esiste ancora. E il caso della prima richiesta
		// dopo BUG-26: si crea e si rilegge. Il codice arriva da $conn->errno
		// con report OFF e da $e->getCode() con report ERROR|STRICT: q() lo
		// normalizza, quindi questo ramo e raggiungibile in ENTRAMBI i modi.
		if ( false === $res && 1146 === self::$last_errno ) {
			if ( self::install() ) {
				$res = self::q( $conn, $sql );
			}
		}

		if ( ! ( $res instanceof mysqli_result ) ) {
			self::$cache    = $cache;
			self::$degraded = true;
			return;
		}

		while ( $row = $res->fetch_assoc() ) {
			$cache[ (string) $row['option_name'] ] = $row['option_value'];
		}
		$res->free();

		self::$cache    = $cache;
		self::$degraded = false;
	}

	/* ===================================================================== *
	 *  API — stessa forma utile di get_option/update_option/delete_option
	 * ===================================================================== */

	/**
	 * @param  string $key
	 * @param  mixed  $default restituito quando la chiave non esiste
	 * @return mixed
	 */
	public static function get( string $key, $default = '' ) {
		self::load();
		if ( ! array_key_exists( $key, self::$cache ) ) {
			return $default;
		}
		$raw = self::$cache[ $key ];
		return $raw === null ? $default : maybe_unserialize( $raw );
	}

	/** True se la chiave esiste, anche con valore vuoto. */
	public static function has( string $key ): bool {
		self::load();
		return array_key_exists( $key, self::$cache );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param mixed  $autoload accettato e IGNORATO: qui non esiste l autoload di
	 *                         WordPress, l archivio si carica per intero in una
	 *                         SELECT sola. Serve solo a rendere la conversione
	 *                         dei punti di chiamata una sostituzione di nome.
	 */
	public static function set( string $key, $value, $autoload = null ): bool {
		self::load();

		$conn = self::conn();
		if ( ! $conn ) {
			return false;
		}

		$raw  = maybe_serialize( $value );
		$raw  = is_scalar( $raw ) || $raw === null ? $raw : (string) $raw;
		$sval = $raw === null ? null : (string) $raw;

		$ok = self::write( $conn, $key, $sval );

		// Se la tabella non c e (1146) la si crea e si riprova UNA volta sola:
		// install() e idempotente e tentato una volta per richiesta.
		if ( ! $ok && 1146 === self::$last_errno && self::install() ) {
			$ok = self::write( $conn, $key, $sval );
		}

		if ( $ok ) {
			if ( self::$cache === null ) {
				self::$cache = array();
			}
			self::$cache[ $key ] = $sval;
		}
		return $ok;
	}

	/**
	 * La scrittura vera e propria, isolata perche va ritentata dopo un CREATE.
	 *
	 * Forma alias (MySQL 8.0.19+, non deprecata). Tutte e tre le installazioni
	 * sono >= 8.0.32, ma se il prepare non passasse si ripiega su REPLACE INTO,
	 * che e universale e qui ha la stessa semantica: chiave primaria singola,
	 * nessun AUTO_INCREMENT, nessuna foreign key.
	 */
	private static function write( $conn, string $key, $sval ): bool {
		$t = self::table();

		$stmt = self::p(
			$conn,
			"INSERT INTO `{$t}` (`option_name`, `option_value`) VALUES (?, ?)
			 AS nuovo ON DUPLICATE KEY UPDATE `option_value` = nuovo.`option_value`"
		);
		if ( ! $stmt && 1146 !== self::$last_errno ) {
			$stmt = self::p( $conn, "REPLACE INTO `{$t}` (`option_name`, `option_value`) VALUES (?, ?)" );
		}
		if ( ! $stmt ) {
			return false;
		}

		$stmt->bind_param( 'ss', $key, $sval );
		$ok = self::x( $conn, $stmt );
		$stmt->close();

		return $ok;
	}

	/** Cancella una chiave. Non e un DROP e non tocca dati: e uno stato. */
	public static function del( string $key ): bool {
		self::load();

		$conn = self::conn();
		if ( ! $conn ) {
			return false;
		}

		$t    = self::table();
		$stmt = self::p( $conn, "DELETE FROM `{$t}` WHERE `option_name` = ?" );
		if ( ! $stmt ) {
			// Tabella assente: non c e nulla da cancellare, l esito e vero.
			return 1146 === self::$last_errno;
		}
		$stmt->bind_param( 's', $key );
		$ok = self::x( $conn, $stmt );
		$stmt->close();

		unset( self::$cache[ $key ] );
		return $ok;
	}

	/* ===================================================================== *
	 *  PONTE — LETTURA DI RICADUTA E MIGRAZIONE PIGRA (BUG-26 lotto 2)
	 * ===================================================================== *
	 *
	 * PERCHE' ESISTE. La conversione di un punto di chiamata da `get_option()`
	 * a `Rdacademy_Option::get()` non e' una sostituzione innocua: il valore vive
	 * ancora sul `_1`, e sul `_2` la chiave e' assente. Su un'installazione
	 * VIVA il codice guardato da quel flag tornerebbe a girare come se non
	 * fosse mai girato. Per un `CREATE TABLE IF NOT EXISTS` non cambia nulla;
	 * per un `seed_lookups()` seguito da un `dedupe_lookups()` cambia eccome.
	 *
	 * `RP-UNA-CONVERSIONE-NON-PUO-PERDERE-LA-MEMORIA-CHE-CONVERTE`: chi sposta
	 * un archivio porta con se' cio' che c'era dentro, o non ha spostato: ha
	 * ricominciato.
	 *
	 * COME FUNZIONA. Si legge il `_2`. Se la chiave c'e', finisce li' e il `_1`
	 * non viene nemmeno interrogato. Se non c'e', si guarda sul `_1`: se il
	 * valore e' li', lo si **riscrive sul `_2`** e lo si **cancella dal `_1`**.
	 * La migrazione avviene una volta sola, al primo passaggio, e nessuna
	 * installazione viva rifa' un provisioning gia' fatto.
	 *
	 * PERCHE' LA CANCELLAZIONE E' PARTE DEL PONTE E NON UN PASSO SEPARATO.
	 * Due copie della stessa memoria in due archivi diversi sono la premessa
	 * di BUG-26, non la sua cura: finche' il valore resta sul `_1`, un giorno
	 * qualcuno lo leggera' di la'. Ed e' esattamente cio' che e' successo in
	 * S89, dove un flag rimasto sul `_1` ha impedito a nove `CREATE TABLE` di
	 * raggiungere il `_3`.
	 *
	 * PERCHE' QUI DENTRO SI CHIAMANO ANCORA LE OPTION DI WORDPRESS. Sono gli
	 * UNICI due punti del prodotto che devono poterlo fare, ed e' il motivo
	 * per cui esistono: sono la porta d'uscita dal `_1`, non un uso del `_1`.
	 * Portano il marcatore che `gate-option-su-base.py` riconosce, con il
	 * motivo sulla stessa riga — senza motivo il gate li conta come tutti gli
	 * altri, perche' una deroga senza motivo e' una scappatoia.
	 */

	/**
	 * Lettura con ricaduta sul `_1` e migrazione pigra verso il `_2`.
	 *
	 * @param  string $key
	 * @param  mixed  $default restituito quando la chiave non esiste da nessuna parte
	 * @return mixed
	 */
	public static function bridge_get( string $key, $default = '' ) {
		if ( self::has( $key ) ) {
			return self::get( $key, $default );
		}

		$legacy = get_option( $key, null ); // RDACADEMY-OPTION-BRIDGE-ALLOW: unica porta di lettura del _1, esiste per svuotarlo
		if ( $legacy === null || $legacy === false ) {
			return $default;
		}

		// Si scrive PRIMA sul _2 e si cancella DOPO dal _1: se la scrittura
		// fallisse, la memoria resterebbe dov era invece di sparire da entrambi.
		if ( self::set( $key, $legacy ) ) {
			delete_option( $key ); // RDACADEMY-OPTION-BRIDGE-ALLOW: chiude la migrazione, la copia sul _1 non deve sopravvivere
		}
		return maybe_unserialize( $legacy );
	}

	/**
	 * Cancellazione su ENTRAMBI gli archivi.
	 *
	 * Serve ai `force_rebuild()`: cancellare la chiave sul solo `_2` mentre una
	 * copia sopravvive sul `_1` significa che il prossimo `bridge_get()` la
	 * riporterebbe in vita, e un «forza ricostruzione» che non forza nulla e'
	 * peggio di un pulsante assente (`RP-GATE-CIECO-E-PEGGIO-DI-NESSUN-GATE`,
	 * stessa famiglia).
	 */
	public static function bridge_del( string $key ): bool {
		$ok = self::del( $key );
		delete_option( $key ); // RDACADEMY-OPTION-BRIDGE-ALLOW: cancella su entrambi, altrimenti il ponte resusciterebbe la chiave
		return $ok;
	}

	/* ===================================================================== *
	 *  DIAGNOSTICA — serve al banco e alla Console SQL, non al runtime
	 * ===================================================================== */

	/** Svuota la cache di richiesta. Usata dai banchi dopo una scrittura esterna. */
	public static function flush(): void {
		self::$cache      = null;
		self::$installed  = false;
		self::$install_ok = false;
		self::$degraded   = false;
	}

	/** Tutte le chiavi presenti, ordinate. Sola lettura. */
	public static function keys(): array {
		self::load();
		$k = array_keys( self::$cache );
		sort( $k );
		return $k;
	}

	public static function count(): int {
		self::load();
		return count( self::$cache );
	}

	/** True se la tabella esiste davvero sul BASE. */
	public static function table_exists(): bool {
		$conn = self::conn();
		if ( ! $conn ) {
			return false;
		}
		$t   = self::table();
		$res = self::q( $conn, "SHOW TABLES LIKE '" . $conn->real_escape_string( $t ) . "'" );
		$ok  = ( $res instanceof mysqli_result ) && $res->num_rows > 0;
		if ( $res instanceof mysqli_result ) { $res->free(); }
		return (bool) $ok;
	}

	public static function last_error(): string {
		return self::$last_error;
	}

	/** Ultimo codice MySQL: 1146 tabella assente, 1050 gia esistente, 0 nessuno. */
	public static function last_errno(): int {
		return self::$last_errno;
	}

	/**
	 * True quando l archivio NON e stato letto e get() sta servendo i default.
	 * E la differenza fra «nessuna chiave» e «non lo so»: senza questa, un
	 * archivio irraggiungibile e indistinguibile da un archivio vuoto, ed e
	 * esattamente cio che ha reso invisibile VIOLAZIONE 72.
	 */
	public static function is_degraded(): bool {
		self::load();
		return self::$degraded;
	}

	/** Fotografia per il banco (Strumento 79). Sola lettura. */
	public static function diagnostics(): array {
		// L ordine conta: table_exists() esegue una query e quindi riscrive
		// errno/errore. Si fotografano PRIMA, altrimenti il banco riporta
		// l esito della propria sonda invece di quello del prodotto.
		self::load();
		$errno = self::$last_errno;
		$errore = self::$last_error;
		$degradato = self::$degraded;

		return array(
			'tabella'       => self::table(),
			'chiavi'        => self::count(),
			'degradato'     => $degradato,
			'install_fatto' => self::$installed,
			'install_ok'    => self::$install_ok,
			'errno'         => $errno,
			'errore'        => $errore,
			'esiste'        => self::table_exists(),
		);
	}
}
