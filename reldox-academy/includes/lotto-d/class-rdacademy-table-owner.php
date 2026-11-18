<?php
/**
 * Rdacademy_Table_Owner — DI CHI È QUESTA TABELLA, E DOVE VIVE ADESSO.
 * ==========================================================================
 * Copiato da Reldox_Table_Owner (BUG-23 passo 2), RelDox CRD 3.55.09G, e
 * ridotto a ciò che serve al guscio: la RESIDENZA di una tabella di sorgente
 * alla NASCITA (conn_for in modo dichiarato) e il NOME QUALIFICATO a runtime (t).
 *
 * DUE RISPOSTE, NON UNA (copiato):
 *  1) PROPRIETÀ (statica, di progetto) — owner(): 'base' oppure la chiave della
 *     sorgente. Nel guscio la dichiara la mappa `rdacademy_sorgenti_tabelle`.
 *  2) RESIDENZA (dinamica, di installazione) — schema_of(): su QUALE database la
 *     tabella vive DAVVERO, adesso. Una tabella di sorgente è raggiungibile solo
 *     quando la sorgente è attiva, provisionata, sullo stesso host del BASE.
 *
 * MODO QUALIFICATO tutto-o-niente (copiato): se almeno una sorgente è
 * instradabile, t() qualifica TUTTO, tabelle del BASE comprese:
 * `rdacademy_s3`.`rdacademy_x` × `rdacademy_base`.`rdacademy_users`. Altrimenti nomi NUDI, byte per byte.
 *
 * STESSO HOST (copiato): `db`.`tab` è una sintassi, non un protocollo di rete;
 * una sorgente su un altro host non si qualifica e non si instrada.
 *
 * MODO DICHIARATO (BUG-23 passo 7 di RelDox): alla nascita il nome resta NUDO e
 * cambia la CONNESSIONE (conn_for); a regime la connessione resta il BASE e si
 * QUALIFICA il nome. Alla nascita il ripiego sul BASE è vietato: si solleva
 * Rdacademy_Tabella_Senza_Casa (BUG-182 lotto 2).
 *
 * TAGLI: il SEED delle tabelle di modulo (offerte, articoli, listini — CRM),
 * le TABELLE FUNZIONALI condivise (registro della pagina /crd/tabelle/),
 * register(), index_sorgenti()/owner_of_file() (residenza dei file sorgente
 * per i gate di consegna), ripiego()/ripieghi()/incompleti() (diagnostica del
 * ripiego cieco SFA), column_exists(), report(), conflitti_di_dichiarazione().
 * NUOVO: exists() con statement preparato su information_schema.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Table_Owner {

	const BASE = 'base';

	private static ?bool $qualifying = null;
	private static array $routable_cache = array();
	private static int $declared = 0;

	public static function flush(): void {
		self::$qualifying     = null;
		self::$routable_cache = array();
		Rdacademy_Sorgenti::flush();
	}

	/* ── MODO DICHIARATO (nascita) — un contatore: le aperture si annidano ── */

	public static function begin_declared(): void {
		self::$declared++;
		self::flush();
	}

	public static function end_declared(): void {
		self::$declared = max( 0, self::$declared - 1 );
		self::flush();
	}

	public static function is_declared(): bool {
		return self::$declared > 0;
	}

	/* ── 1) PROPRIETÀ ─────────────────────────────────────────────────── */

	/** Le tabelle dichiarate di una sorgente (nome nudo). */
	public static function module_tables( string $key ): array {
		return Rdacademy_Sorgenti::tabelle_di( $key );
	}

	/** Di chi è questa tabella: 'base' per tutto ciò che non è dichiarato di sorgente. */
	public static function owner( string $table ): string {
		$t = self::short( $table );
		foreach ( Rdacademy_Sorgenti::chiavi() as $k ) {
			if ( in_array( $t, Rdacademy_Sorgenti::tabelle_di( $k ), true ) ) {
				return $k;
			}
		}
		return self::BASE;
	}

	/* ── 2) RESIDENZA ─────────────────────────────────────────────────── */

	public static function base_db(): string {
		try {
			return (string) ( Rdacademy_DB_Config::get()['name'] ?? '' );
		} catch ( Throwable $e ) {
			return '';
		}
	}

	private static function base_host(): string {
		try {
			return Rdacademy_DataSource::norm_host( (string) ( Rdacademy_DB_Config::get()['host'] ?? '' ) );
		} catch ( Throwable $e ) {
			return '';
		}
	}

	/** La sorgente vive sullo STESSO server MySQL del BASE? Nel dubbio: false. */
	public static function same_host( string $key ): bool {
		$p = Rdacademy_DataSource::connect_params( $key );
		if ( ! $p ) {
			return false;
		}
		$b = self::base_host();
		$s = Rdacademy_DataSource::norm_host( (string) $p['host'] );
		return ( $b !== '' && $s !== '' && $b === $s );
	}

	public static function module_db( string $key ): string {
		$p = Rdacademy_DataSource::connect_params( $key );
		return $p ? (string) $p['name'] : '';
	}

	/**
	 * INSTRADABILE: attiva, con tabelle proprie, nome di database noto, stesso
	 * host — e a runtime anche davvero provisionata. Alla nascita (modo
	 * dichiarato) la sentinella non si può pretendere: è proprio la tabella che
	 * si sta per creare (copiato, BUG-23 passo 7).
	 */
	public static function routable( string $key ): bool {
		$key = strtolower( trim( $key ) );
		if ( isset( self::$routable_cache[ $key ] ) ) {
			return self::$routable_cache[ $key ];
		}
		self::$routable_cache[ $key ] = false;
		if ( ! Rdacademy_Sorgenti::esiste( $key ) || self::module_tables( $key ) === array() ) {
			return false;
		}
		if ( ! Rdacademy_DataSource::is_active( $key ) || self::module_db( $key ) === '' || ! self::same_host( $key ) ) {
			return false;
		}
		if ( ! self::is_declared() && ! Rdacademy_DataSource::is_provisioned( $key ) ) {
			return false;
		}
		self::$routable_cache[ $key ] = true;
		return true;
	}

	/**
	 * DOVE SI MANDA LA QUERY (copiato). A runtime sempre il BASE (t() qualifica
	 * il nome); alla nascita la connessione della sorgente, oppure
	 * Rdacademy_Tabella_Senza_Casa — mai il BASE per cortesia.
	 */
	public static function conn_for( string $table ): mysqli {
		$base = Rdacademy_DB::get_connection();
		if ( ! self::is_declared() ) {
			return $base;
		}
		$owner = self::owner( $table );
		if ( $owner === self::BASE ) {
			return $base;
		}
		if ( ! self::routable( $owner ) ) {
			throw new Rdacademy_Tabella_Senza_Casa(
				self::short( $table ), $owner,
				'la sorgente non è instradabile (non configurata, spenta o su un altro host).'
			);
		}
		$c = Rdacademy_DataSource::get( $owner );
		if ( $c === $base ) {
			throw new Rdacademy_Tabella_Senza_Casa( self::short( $table ), $owner, 'la connessione della sorgente non risponde e ricadrebbe sul BASE.' );
		}
		return $c;
	}

	/** MODO QUALIFICATO attivo? Vero se almeno una sorgente è instradabile. */
	public static function qualifying(): bool {
		if ( self::$qualifying !== null ) {
			return self::$qualifying;
		}
		self::$qualifying = false;
		if ( self::base_db() === '' ) {
			return false;
		}
		foreach ( Rdacademy_Sorgenti::chiavi() as $key ) {
			if ( self::routable( $key ) ) {
				self::$qualifying = true;
				break;
			}
		}
		return self::$qualifying;
	}

	/** Su quale database vive DAVVERO la tabella, adesso ('' = nudo). */
	public static function schema_of( string $table ): string {
		if ( self::is_declared() || ! self::qualifying() ) {
			return '';
		}
		$owner = self::owner( $table );
		if ( $owner === self::BASE ) {
			return self::base_db();
		}
		return self::routable( $owner ) ? self::module_db( $owner ) : self::base_db();
	}

	/* ── L UNICA COSA CHE LE QUERY DEVONO CHIAMARE ───────────────────── */

	/** Nome pronto da incollare in una query, già fra backtick (copiato). */
	public static function t( string $table ): string {
		$short  = self::short( $table );
		$full   = self::prefix() . $short;
		$schema = self::schema_of( $short );
		return ( $schema === '' )
			? '`' . $full . '`'
			: '`' . str_replace( '`', '``', $schema ) . '`.`' . $full . '`';
	}

	public static function map(): Rdacademy_Table_Map {
		return Rdacademy_Table_Map::instance();
	}

	public static function prefix(): string {
		return RDACADEMY_TABLE_PREFIX;
	}

	/** Toglie il prefisso se c è e normalizza (copiato). */
	public static function short( string $table ): string {
		$t = strtolower( trim( $table ) );
		$t = trim( $t, "`\t\n\r " );
		$p = strtolower( self::prefix() );
		if ( $p !== '' && strpos( $t, $p ) === 0 ) {
			$t = substr( $t, strlen( $p ) );
		}
		return (string) preg_replace( '/[^a-z0-9_]/', '', $t );
	}

	/**
	 * «Questa tabella esiste?» sulla connessione data, nello schema in cui vive
	 * adesso. ⛔ Preparata (in RelDox SHOW TABLES LIKE con real_escape_string).
	 */
	public static function exists( $conn, string $table ): bool {
		if ( ! ( $conn instanceof mysqli ) ) {
			return false;
		}
		$short = self::short( $table );
		if ( $short === '' ) {
			return false;
		}
		$full   = self::prefix() . $short;
		$schema = self::schema_of( $short );
		try {
			if ( $schema === '' ) {
				return Rdacademy_DataSource::tabella_su( $conn, $full ) === true;
			}
			$st = $conn->prepare( 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?' );
			$st->bind_param( 'ss', $schema, $full );
			$st->execute();
			$n = 0;
			$st->bind_result( $n );
			$st->fetch();
			$st->close();
			return (int) $n > 0;
		} catch ( Throwable $e ) {
			error_log( '[RelDoxAcademy Table Owner] exists: ' . $e->getMessage() );
			return false;
		}
	}
}
