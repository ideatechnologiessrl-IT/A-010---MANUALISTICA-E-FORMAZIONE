<?php
/**
 * Rdacademy_Testi_Notifiche — LA TABELLA FUNZIONALE «TESTI DELLE NOTIFICHE».
 * =============================================================================
 * Il testo di ogni mail che il prodotto spedisce si gestisce da una tabella, per lingua di pubblicazione.
 *
 * UNA VOCE = UN MESSAGGIO IN UNA LINGUA: codice (es. `invito.persona`) + lingua (`it` | `en` | `de`), oggetto, testo coi
 * segnaposto, attiva. Codice e lingua nascono col set di partenza e non cambiano più (sono la chiave che la mail cerca):
 * dalla tabella si cambiano oggetto, testo e «attiva».
 *
 * DA DOVE LEGGE UNA MAIL (modello()): la voce ATTIVA nella lingua della scheda; se manca, la voce attiva italiana; se
 * manca ancora, il set di partenza (Rdacademy_Lingua::NOTIFICHE). Un campo lasciato vuoto vale come mancante.
 *
 * NATURA «sistema» (Rdacademy_TF_Registry): il set di partenza lo conosce il prodotto; «Genera dalla fonte» chiama seed(),
 * che ricrea PER CODICE E LINGUA le voci che mancano e non sovrascrive quelle cambiate o spente.
 *
 * ⛔ REGOLA: ogni testo che arriva a una persona nasce qui; un messaggio nuovo è un codice nuovo in MESSAGGI e nel set di
 * partenza, mai una frase nel codice della mail.
 *
 * @package RelDoxAcademy
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Testi_Notifiche {

	const NS      = 'rdacademy/v1';
	const TABELLA = 'tf_testi_notifiche';

	/**
	 * I messaggi che il prodotto spedisce, nell ordine in cui la tabella li mostra.
	 * codice => [ nome, quando parte, segnaposto che il testo può usare ]
	 */
	const MESSAGGI = array(
		'invito.persona'      => array( 'Invito — persone', 'Parte con «Invia credenziali» e «Reinvia invito»: porta il collegamento per scegliere la password.', '{nome} {utente} {prodotto} {cliente} {giorni} {link} {pulsante}' ),
		'reinvio'             => array( 'Reinvio dell’invito', 'Oggetto del secondo invio e nota in testa al messaggio; sotto segue il testo dell’invito.', '{nome} {utente} {prodotto} {cliente}' ),
		'reimpostazione'      => array( 'Reimpostazione della password', 'Parte con «Password dimenticata?» e con «Reimposta password».', '{nome} {utente} {prodotto} {cliente} {giorni} {link} {pulsante}' ),
		'accesso.persona'     => array( 'Invio login — persone', 'Parte con «Invio login»: solo il collegamento d’accesso, la password non cambia.', '{nome} {utente} {prodotto} {cliente} {link} {pulsante}' ),
		'blocco'              => array( 'Avviso di blocco', 'Agli amministratori, quando un account si blocca per tentativi falliti.', '{chi} {email} {ip} {ora} {soglia} {prodotto}' ),
	);

	public static function init(): void {
		if ( function_exists( 'add_action' ) ) {
			add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		}
	}

	/* ===================================================================== *
	 *  LO SCHEMA (lo chiama Rdacademy_Schema_E_V1)
	 * ===================================================================== */

	public static function create(): void {
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}" . self::TABELLA . "` (
				`id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`codice`      VARCHAR(64)  NOT NULL COMMENT 'Il messaggio (invito.persona, reinvio, …): nasce col set di partenza e non cambia',
				`lingua`      CHAR(2)      NOT NULL DEFAULT 'it' COMMENT 'it | en | de',
				`oggetto`     VARCHAR(255) NOT NULL DEFAULT '',
				`testo`       TEXT         NOT NULL,
				`enabled`     TINYINT(1)   NOT NULL DEFAULT 1,
				`sort_order`  INT          NOT NULL DEFAULT 0,
				`updated_by`  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'users.id di chi ha cambiato il testo',
				`created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
				`updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uk_codice_lingua` (`codice`,`lingua`),
				KEY `idx_sort` (`sort_order`,`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		" );
	}

	public static function presente(): bool {
		$r = Rdacademy_DB::get_row(
			"SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
			array( RDACADEMY_TABLE_PREFIX . self::TABELLA ), 's'
		);
		return $r && (int) $r['n'] === 1;
	}

	/**
	 * Alla nascita (tabella vuota) entra il set di partenza. Su una tabella che ha già righe non fa niente.
	 */
	public static function nascita(): void {
		if ( ! self::presente() ) return;
		$n = Rdacademy_DB::get_row( "SELECT COUNT(*) AS n FROM `" . RDACADEMY_TABLE_PREFIX . self::TABELLA . "`" );
		if ( ! $n || (int) $n['n'] !== 0 ) return;
		self::seed();
	}

	/**
	 * IL GENERATORE di «Genera dalla fonte» (Rdacademy_TF_Registry::genera): ricrea per codice e lingua le voci del set di
	 * partenza che mancano; quelle presenti non si toccano (un testo cambiato resta cambiato, una voce spenta resta
	 * spenta). Idempotente: INSERT IGNORE sulla chiave univoca (codice, lingua).
	 */
	public static function seed(): void {
		foreach ( Rdacademy_Lingua::NOTIFICHE as $codice => $lingue ) {
			foreach ( $lingue as $l => $v ) {
				self::scrivi_nuova( (string) $codice, (string) $l, (string) $v[0], (string) $v[1] );
			}
		}
	}

	private static function ordine( string $codice, string $lingua ): int {
		$i = array_search( $codice, array_keys( self::MESSAGGI ), true );
		$j = array_search( $lingua, Rdacademy_Lingua::LINGUE, true );
		return ( ( $i === false ? 90 : (int) $i ) + 1 ) * 10 + ( $j === false ? 9 : (int) $j );
	}

	private static function scrivi_nuova( string $codice, string $lingua, string $oggetto, string $testo ): bool {
		$p = RDACADEMY_TABLE_PREFIX;
		$c = Rdacademy_DB::get_row( "SELECT id FROM `{$p}" . self::TABELLA . "` WHERE codice = ? AND lingua = ? LIMIT 1", array( $codice, $lingua ), 'ss' );
		if ( $c ) return false;
		Rdacademy_DB::execute(
			"INSERT IGNORE INTO `{$p}" . self::TABELLA . "` (codice, lingua, oggetto, testo, enabled, sort_order) VALUES (?, ?, ?, ?, 1, ?)",
			array( $codice, $lingua, $oggetto, $testo, self::ordine( $codice, $lingua ) ), 'ssssi'
		);
		return true;
	}

	/* ===================================================================== *
	 *  LA LETTURA DELLA MAIL
	 * ===================================================================== */

	/**
	 * Il modello grezzo (coi segnaposto) di un messaggio nella lingua chiesta.
	 * Ordine: voce attiva della lingua → voce attiva italiana → set di partenza della lingua.
	 *
	 * @return array{oggetto:string,testo:string,fonte:string}
	 */
	public static function modello( string $codice, string $lingua ): array {
		$l    = Rdacademy_Lingua::valida( $lingua );
		$part = Rdacademy_Lingua::partenza( $codice, $l );
		$out  = array( 'oggetto' => '', 'testo' => '', 'fonte' => 'partenza' );
		$righe = array();
		try {
			$rows = Rdacademy_DB::get_results(
				"SELECT lingua, oggetto, testo FROM `" . RDACADEMY_TABLE_PREFIX . self::TABELLA . "` WHERE codice = ? AND enabled = 1",
				array( $codice ), 's'
			);
			foreach ( (array) $rows as $r ) { $righe[ (string) $r['lingua'] ] = $r; }
		} catch ( Throwable $e ) { /* tabella non leggibile: resta il set di partenza */ }
		foreach ( array_unique( array( $l, Rdacademy_Lingua::PREDEFINITA ) ) as $cand ) {
			if ( ! isset( $righe[ $cand ] ) ) continue;
			$o = trim( (string) $righe[ $cand ]['oggetto'] );
			$t = trim( (string) $righe[ $cand ]['testo'] );
			if ( $o === '' && $t === '' ) continue;
			$pc = Rdacademy_Lingua::partenza( $codice, $cand );
			return array(
				'oggetto' => $o !== '' ? $o : $pc['oggetto'],
				'testo'   => $t !== '' ? $t : $pc['testo'],
				'fonte'   => 'tabella:' . $cand,
			);
		}
		$out['oggetto'] = $part['oggetto'];
		$out['testo']   = $part['testo'];
		return $out;
	}

	/* ===================================================================== *
	 *  LA TABELLA NELLA PAGINA (/academy/tabelle/?tab=notifiche) — REST, solo amministratore
	 *   GET  /rdacademy/v1/testi-notifiche               — elenco, coi nomi dei messaggi e i segnaposto
	 *   PUT  /rdacademy/v1/testi-notifiche/{id}          — { oggetto, testo, enabled }
	 *   POST /rdacademy/v1/testi-notifiche/{id}/partenza — rimette il testo di partenza di QUELLA voce
	 * ===================================================================== */

	public static function register_routes(): void {
		$p = array( 'Rdacademy_C_Rest', 'permesso_admin' );
		register_rest_route( self::NS, '/testi-notifiche', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_list' ), 'permission_callback' => $p ) );
		register_rest_route( self::NS, '/testi-notifiche/(?P<id>\d+)', array( 'methods' => 'PUT', 'callback' => array( __CLASS__, 'rest_update' ), 'permission_callback' => $p ) );
		register_rest_route( self::NS, '/testi-notifiche/(?P<id>\d+)/partenza', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_partenza' ), 'permission_callback' => $p ) );
	}

	private static function riga( int $id ): ?array {
		return Rdacademy_DB::get_row(
			"SELECT id, codice, lingua, oggetto, testo, enabled, sort_order, updated_at FROM `" . RDACADEMY_TABLE_PREFIX . self::TABELLA . "` WHERE id = ?",
			array( $id ), 'i'
		);
	}

	/** ⛔ I numeri dal database arrivano come stringhe: si convertono qui, alla porta PHP→JS (REGOLE § 6). */
	private static function forma( array $r ): array {
		$c    = (string) $r['codice'];
		$l    = (string) $r['lingua'];
		$m    = self::MESSAGGI[ $c ] ?? array( $c, '', '' );
		$part = Rdacademy_Lingua::partenza( $c, $l );
		return array(
			'id'         => (int) $r['id'],
			'codice'     => $c,
			'lingua'     => $l,
			'lingua_nome'=> Rdacademy_Lingua::NOMI[ $l ] ?? $l,
			'nome'       => (string) $m[0],
			'quando'     => (string) $m[1],
			'segnaposto' => (string) $m[2],
			'oggetto'    => (string) $r['oggetto'],
			'testo'      => (string) $r['testo'],
			'enabled'    => (int) $r['enabled'] === 1,
			'di_partenza'=> ( $part['oggetto'] !== '' && (string) $r['oggetto'] === $part['oggetto'] && (string) $r['testo'] === $part['testo'] ),
			'ha_partenza'=> $part['oggetto'] !== '',
			'aggiornata' => (string) ( $r['updated_at'] ?? '' ),
		);
	}

	public static function elenco(): array {
		$rows = Rdacademy_DB::get_results(
			"SELECT id, codice, lingua, oggetto, testo, enabled, sort_order, updated_at FROM `" . RDACADEMY_TABLE_PREFIX . self::TABELLA . "` ORDER BY sort_order ASC, id ASC"
		) ?: array();
		return array_map( array( __CLASS__, 'forma' ), $rows );
	}

	private static function no( int $stato, string $msg ): array {
		return array( 'ok' => false, 'stato' => $stato, 'errore' => $msg );
	}

	/** $enabled null = non cambia. Codice e lingua non si toccano mai. */
	public static function modifica( int $id, string $oggetto, string $testo, ?bool $enabled, int $chi = 0 ): array {
		$r = self::riga( $id );
		if ( ! $r ) return self::no( 404, 'Voce non trovata.' );
		$oggetto = trim( str_replace( array( "\r", "\n" ), ' ', $oggetto ) );
		$testo   = trim( str_replace( array( "\r\n", "\r" ), "\n", $testo ) );
		if ( $oggetto === '' ) return self::no( 400, 'L’oggetto non può essere vuoto.' );
		if ( mb_strlen( $oggetto ) > 255 ) return self::no( 400, 'L’oggetto supera 255 caratteri.' );
		if ( $testo === '' ) return self::no( 400, 'Il testo non può essere vuoto.' );
		if ( mb_strlen( $testo ) > 8000 ) return self::no( 400, 'Il testo supera 8000 caratteri.' );
		$en = $enabled === null ? (int) $r['enabled'] : ( $enabled ? 1 : 0 );
		Rdacademy_DB::execute(
			"UPDATE `" . RDACADEMY_TABLE_PREFIX . self::TABELLA . "` SET oggetto = ?, testo = ?, enabled = ?, updated_by = ? WHERE id = ?",
			array( $oggetto, $testo, $en, $chi > 0 ? $chi : null, $id ), 'ssiii'
		);
		$v = self::riga( $id );
		if ( ! $v || (string) $v['oggetto'] !== $oggetto || (string) $v['testo'] !== $testo || (int) $v['enabled'] !== $en ) {
			return self::no( 500, 'La modifica non è arrivata sul disco.' );
		}
		return array( 'ok' => true, 'stato' => 200, 'dati' => self::forma( $v ) );
	}

	/** Rimette oggetto e testo di partenza di una voce (la voce resta attiva o spenta com era). */
	public static function a_partenza( int $id, int $chi = 0 ): array {
		$r = self::riga( $id );
		if ( ! $r ) return self::no( 404, 'Voce non trovata.' );
		$part = Rdacademy_Lingua::partenza( (string) $r['codice'], (string) $r['lingua'] );
		if ( $part['oggetto'] === '' ) return self::no( 409, 'Questa voce non ha un testo di partenza.' );
		return self::modifica( $id, $part['oggetto'], $part['testo'], null, $chi );
	}

	private static function risposta( array $r ): WP_REST_Response {
		return $r['ok'] ? new WP_REST_Response( $r['dati'], $r['stato'] ) : new WP_REST_Response( array( 'error' => $r['errore'] ), $r['stato'] );
	}

	private static function chi(): int {
		$u = class_exists( 'Rdacademy_Auth' ) ? Rdacademy_Auth::get_current_user() : null;
		return (int) ( $u['id'] ?? 0 );
	}

	public static function rest_list( WP_REST_Request $req ): WP_REST_Response {
		return new WP_REST_Response( self::elenco(), 200 );
	}

	public static function rest_update( WP_REST_Request $req ): WP_REST_Response {
		$en = $req->get_param( 'enabled' );
		$r  = self::modifica(
			(int) $req->get_param( 'id' ),
			(string) $req->get_param( 'oggetto' ),
			(string) $req->get_param( 'testo' ),
			$en === null ? null : ( (int) $en === 1 ),
			self::chi()
		);
		if ( $r['ok'] ) {
			Rdacademy_Auth::log_activity( self::chi(), 'tabelle_funzionali', 'testo_notifica',
				'Testo della notifica ' . $r['dati']['codice'] . ' (' . $r['dati']['lingua'] . ') aggiornato' . ( $r['dati']['enabled'] ? '' : ', voce spenta' ) );
		}
		return self::risposta( $r );
	}

	public static function rest_partenza( WP_REST_Request $req ): WP_REST_Response {
		$r = self::a_partenza( (int) $req->get_param( 'id' ), self::chi() );
		if ( $r['ok'] ) {
			Rdacademy_Auth::log_activity( self::chi(), 'tabelle_funzionali', 'testo_notifica',
				'Testo della notifica ' . $r['dati']['codice'] . ' (' . $r['dati']['lingua'] . ') rimesso al testo di partenza' );
		}
		return self::risposta( $r );
	}
}
