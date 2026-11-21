<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-licenza-stato.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Reldox CRD — VOCE 150 «Sola consultazione a licenza cessata» — LO STATO DELLA LICENZA E LE PORTE CHIUSE (3.74.04G, S702, lotto 1) — S702_V150
 * -----------------------------------------------------------------
 * Dominio dell owner (13/11/2026, chat RelDox CRD S700-S702):
 *   D-150.1 chiave firmata da Idea Technologies con la data di fine del canone; alla data RelDox passa da solo in sola consultazione;
 *           il rinnovo è una chiave nuova; il cliente non può modificarla.
 *   D-150.2 dai 60 giorni prima della fine (S708: era 30; owner 15/11/2026 18:58), una striscia solo agli amministratori, nessuna tolleranza.
 *   D-150.3 a licenza cessata i comandi che scrivono restano visibili ma spenti: «Non disponibile: licenza scaduta».
 *   D-150.5 delle attività automatiche restano accese solo copia di sicurezza e sorveglianza delle falle (+ controllo dell utente di servizio).
 *   D-150.7 restano accese le impostazioni personali di consultazione (viste, ricerche, preferenze, barra, «segna come letto»).
 *   D-150.8 senza chiave RelDox funziona come oggi.  D-150.9 la mail di recupero della password esce.  D-150.10 utenti: solo disattivare.
 *
 * SCELTE DI PROGRAMMAZIONE (regola 2):
 *   - chiave = «RDX1.» + base64url(JSON) + «.» + base64url(firma Ed25519 di «RDX1.<json64>»), JSON {prodotto, sito, fine, emessa, numero};
 *     `sito` è l host del sito senza «www.»; `fine` è l ULTIMO giorno valido nel fuso di WordPress.
 *   - le chiavi pubbliche di Idea Technologies stanno in CHIAVI_PUBBLICHE; il filtro `rdacademy_licenza_chiavi_pubbliche` ne aggiunge
 *     (solo sul palco dei banchi, costante RDACADEMY_BANCO_LICENZA — S708). S708, lotto 2: c è la chiave pubblica vera di Idea Technologies;
 *     un installazione senza chiave incollata resta «senza chiave» (D-150.8) e funziona come prima.
 *   - la memoria (OPZIONE_MEMORIA) tiene la fine più lontana mai installata: togliere la chiave dal database non riporta a «senza chiave».
 *   - le porte: router AJAX (elenco delle azioni ammesse), REST (`rest_pre_dispatch`), posta (`Rdacademy_Mailer::consegna`, `pre_wp_mail`),
 *     IA (`Rdacademy_AI::chiama`), pagina pubblica (`Rdacademy_Vista_Singola::serve`), attività automatiche (init 999).
 * Banco: banco/S702-4-banco-voce-150.php (ROSSO 20/69 sulla 3.74.03G).
 *
 * @package Rdacademy_CRD
 * @since 3.74.04
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Licenza_Stato {

	const OPZIONE          = 'rdacademy_licenza_chiave';
	const OPZIONE_MEMORIA  = 'rdacademy_licenza_memoria';
	const PREFISSO         = 'RDX1';
	const PRODOTTO         = 'reldox-academy';   // F023_LICENZA: capo famiglia 20/11/2026 § 2
	const PREAVVISO_GIORNI = 60;   // S708 · D-150.2 da 30 a 60 giorni (owner 15/11/2026 18:58, chat RelDoxAcademy S706); nessuna tolleranza
	const TIPI             = array( 'canone' => 'Canone', 'not4sale' => 'NOT4SALE', 'segnalatore' => 'Segnalatore', 'partner' => 'Partner' );   // D-150.13 (come «RelDox Chiavi»)
	const OPZIONE_STRISCIA = 'rdacademy_licenza_striscia_chiusa_';   // + id utente: giorno in cui l amministratore ha chiuso la striscia (torna il giorno dopo)
	const MESSAGGIO        = 'Non disponibile: licenza scaduta';
	const CODICE_REST      = 'rdacademy_licenza_scaduta';

	/** Chiavi pubbliche Ed25519 di Idea Technologies (base64). S708: la chiave di firma di «RelDox Chiavi» sul sito di Idea Technologies,
	 *  incollata dall owner il 16/11/2026 09:42 (chat RelDox CRD S708) da «Chiavi emesse › Chiave di firma». */
	const CHIAVI_PUBBLICHE = array( 'VkmaHbDE54aHCTm5h9tlR0JJIOuNAxG3aps4HWBVHY8=' );

	/** Attività automatiche che restano accese a licenza cessata (D-150.5). */
	const ATTIVITA_ACCESE = array( 'rdacademy_backup_daily', 'rdacademy_sorveglianza_sicurezza' );

	/** Attività automatiche note che si spengono (più ogni altra `reldox_*` in calendario). */
	const ATTIVITA_SPENTE = array();   // F023_LICENZA: più ogni altra `rdacademy_*` in calendario

	/** Azioni di livello «auth» che scrivono o escono: a licenza cessata si spengono anche loro. */
	const AUTH_SPENTE = array();   // F023_LICENZA: le azioni personali di RelDoxAcademy (preferenze, uscita) restano accese (D-150.7)

	/** Azioni di livello «admin» / «write» che restano accese (letture, esportazioni, copia di sicurezza). */
	const AMMESSE_EXTRA = array( 'get_access_log', 'get_user', 'esporta_tutto', 'backup_list', 'backup_run', 'restore_preview', 'tech_check',
		'db_health', 'db_capacity', 'dbconfig_status', 'spedizioni_csv', 'datasource_get', 'save_user', 'licenza_striscia_chiudi' );   // F023_LICENZA

	/** @var bool la spedizione in corso è una mail di accesso (recupero della password, D-150.9) */
	private static $posta_di_accesso = false;
	/** @var array|null cache della richiesta */
	private static $cache = null;

	public static function init(): void {
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'porta_rest' ), 1, 3 );
		add_filter( 'pre_wp_mail', array( __CLASS__, 'porta_wp_mail' ), 1, 2 );
		add_action( 'init', array( __CLASS__, 'spegni_attivita' ), 999 );
	}

	// ───────────────────────────────────────────────────────────── la chiave ──

	public static function chiavi_pubbliche(): array {
		// S708 · sicurezza (★★★★★): il filtro si ascolta SOLO sul palco dei banchi (costante RDACADEMY_BANCO_LICENZA nel wp-config di prova).
		// In produzione valgono solo le chiavi pubbliche scritte qui: un aggiunta di codice nel sito non può firmarsi una licenza.
		$k = ( defined( 'RDACADEMY_BANCO_LICENZA' ) && RDACADEMY_BANCO_LICENZA && function_exists( 'apply_filters' ) )
			? (array) apply_filters( 'rdacademy_licenza_chiavi_pubbliche', self::CHIAVI_PUBBLICHE ) : self::CHIAVI_PUBBLICHE;
		return array_values( array_filter( array_map( 'strval', $k ) ) );
	}

	private static function b64u_dec( string $s ): string {
		$d = base64_decode( strtr( $s, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $s ) % 4 ) % 4 ), true );
		return $d === false ? '' : $d;
	}

	public static function sito(): string {
		$h = function_exists( 'home_url' ) ? (string) wp_parse_url( home_url(), PHP_URL_HOST ) : '';
		return strtolower( (string) preg_replace( '/^www\./i', '', $h ) );
	}

	private static function oggi(): string {
		return ( new DateTimeImmutable( 'now', wp_timezone() ) )->format( 'Y-m-d' );
	}

	/** @return array {valida:bool, motivo:string, fine:string, dati:array} */
	public static function verifica_chiave( string $chiave ): array {
		$no = function ( string $m ) { return array( 'valida' => false, 'motivo' => $m, 'fine' => '', 'dati' => array() ); };
		$chiave = trim( preg_replace( '/\s+/', '', $chiave ) );
		$p = explode( '.', $chiave );
		if ( count( $p ) !== 3 || $p[0] !== self::PREFISSO ) return $no( 'La chiave non è nel formato di RelDoxAcademy.' );
		$json = self::b64u_dec( $p[1] ); $firma = self::b64u_dec( $p[2] );
		if ( $json === '' || strlen( $firma ) !== SODIUM_CRYPTO_SIGN_BYTES ) return $no( 'La chiave è incompleta.' );
		$buona = false;
		foreach ( self::chiavi_pubbliche() as $pk ) {
			$pkb = base64_decode( $pk, true );
			if ( $pkb !== false && strlen( $pkb ) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
				&& sodium_crypto_sign_verify_detached( $firma, $p[0] . '.' . $p[1], $pkb ) ) { $buona = true; break; }
		}
		if ( ! $buona ) return $no( 'La chiave non è firmata da Idea Technologies oppure è stata modificata.' );
		$d = json_decode( $json, true );
		if ( ! is_array( $d ) ) return $no( 'La chiave è illeggibile.' );
		if ( (string) ( $d['prodotto'] ?? '' ) !== self::PRODOTTO ) return $no( 'La chiave è di un altro prodotto.' );
		$sito = strtolower( (string) preg_replace( '/^www\./i', '', (string) ( $d['sito'] ?? '' ) ) );
		if ( $sito === '' || $sito !== self::sito() ) return $no( 'La chiave è di un altro sito (' . $sito . ').' );
		$fine = (string) ( $d['fine'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fine ) ) return $no( 'La chiave non ha una data di fine valida.' );
		return array( 'valida' => true, 'motivo' => '', 'fine' => $fine, 'dati' => $d );
	}

	/** @return array {ok:bool, messaggio:string, fine?:string} */
	public static function salva_chiave( string $chiave ): array {
		$v = self::verifica_chiave( $chiave );
		if ( ! $v['valida'] ) return array( 'ok' => false, 'messaggio' => $v['motivo'] );
		$mem = (string) get_option( self::OPZIONE_MEMORIA, '' );
		if ( $mem !== '' && $v['fine'] < $mem ) {
			return array( 'ok' => false, 'messaggio' => 'La chiave scade il ' . self::data_a_video( $v['fine'] ) . ', prima di quella già installata (' . self::data_a_video( $mem ) . ').' );
		}
		update_option( self::OPZIONE, trim( preg_replace( '/\s+/', '', $chiave ) ), false );
		if ( $mem === '' || $v['fine'] > $mem ) update_option( self::OPZIONE_MEMORIA, $v['fine'], false );
		self::$cache = null;
		return array( 'ok' => true, 'messaggio' => 'Chiave installata: il canone vale fino al ' . self::data_a_video( $v['fine'] ) . '.', 'fine' => $v['fine'] );
	}

	public static function data_a_video( string $ymd ): string {
		$p = explode( '-', $ymd );
		return count( $p ) === 3 ? $p[2] . '/' . $p[1] . '/' . $p[0] : $ymd;
	}

	// ───────────────────────────────────────────────────────────── lo stato ──

	/** La fine valida: dalla chiave installata; se la chiave manca o non vale più, dalla memoria (mai «senza chiave» dopo la prima). */
	public static function fine(): string {
		if ( self::$cache !== null ) return (string) self::$cache['fine'];
		$fine = '';
		$k = (string) get_option( self::OPZIONE, '' );
		if ( $k !== '' ) { $v = self::verifica_chiave( $k ); if ( $v['valida'] ) $fine = $v['fine']; }
		$mem = (string) get_option( self::OPZIONE_MEMORIA, '' );
		if ( $fine === '' && $mem !== '' ) $fine = $mem;
		if ( $fine !== '' && $mem !== '' && $mem < $fine ) { /* la chiave valida è più lontana: vince lei */ }
		self::$cache = array( 'fine' => $fine );
		return $fine;
	}

	/** senza_chiave | attiva | preavviso | cessata */
	public static function stato(): string {
		$fine = self::fine();
		if ( $fine === '' ) return 'senza_chiave';
		$oggi = self::oggi();
		if ( $oggi > $fine ) return 'cessata';
		$giorni = (int) ( ( strtotime( $fine . ' 00:00:00 UTC' ) - strtotime( $oggi . ' 00:00:00 UTC' ) ) / 86400 );
		return $giorni <= self::PREAVVISO_GIORNI ? 'preavviso' : 'attiva';
	}

	public static function cessata(): bool {
		return self::stato() === 'cessata';
	}

	/** S708 · i dati della chiave installata (per la pagina «Licenza d uso»): {fine, tipo, emessa, numero, valida, motivo}. */
	public static function chiave_installata(): array {
		$k = (string) get_option( self::OPZIONE, '' );
		if ( $k === '' ) return array( 'fine' => self::fine(), 'tipo' => '', 'emessa' => '', 'numero' => '', 'valida' => false, 'motivo' => '' );
		$v = self::verifica_chiave( $k ); $d = (array) $v['dati'];
		$tipo = (string) ( $d['tipo'] ?? '' );
		return array( 'fine' => self::fine(), 'tipo' => $tipo === '' ? '' : ( self::TIPI[ $tipo ] ?? $tipo ), 'emessa' => (string) ( $d['emessa'] ?? '' ),
			'numero' => (string) ( $d['numero'] ?? '' ), 'valida' => (bool) $v['valida'], 'motivo' => (string) $v['motivo'] );
	}

	/** S708 · giorni che mancano alla fine (0 = ultimo giorno), -1 se non c è una fine. */
	public static function giorni_alla_fine(): int {
		$fine = self::fine();
		if ( $fine === '' ) return -1;
		return (int) ( ( strtotime( $fine . ' 00:00:00 UTC' ) - strtotime( self::oggi() . ' 00:00:00 UTC' ) ) / 86400 );
	}

	/** S708 · l indirizzo della pagina «Licenza d uso» di WordPress (solo per gli amministratori WordPress). */
	public static function url_pagina(): string {
		return function_exists( 'admin_url' ) ? admin_url( 'admin.php?page=rdacademy-licenza' ) : '';
	}

	/**
	 * S708 · LA STRISCIA in cima a ogni schermata di /crd/ (MOCKUP-VOCE-150-SOLA-CONSULTAZIONE-v1, viste 1 e 2):
	 *   cessata     → a tutti, «Licenza scaduta — sola consultazione», non si chiude (D-150.3);
	 *   preavviso   → solo agli amministratori, ambra, dai 60 giorni prima, si chiude con la X e torna il giorno dopo (D-150.2);
	 *   senza_chiave→ solo agli amministratori, «manca la chiave», si chiude con la X e torna il giorno dopo (D-150.8).
	 */
	public static function striscia( bool $admin, int $uid = 0 ): string {
		$st = self::stato();
		$ico_x = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5v.5"/></svg>';
		if ( $st === 'cessata' ) {
			return '<div class="rx-lic rx-lic-scad" role="status" data-rx-lic="cessata">' . $ico_x . '<b>Licenza scaduta — sola consultazione</b>'
				. '<span class="rx-lic-dx">Potete cercare, leggere ed esportare tutto. Per tornare a scrivere serve il rinnovo del canone.</span></div>';
		}
		if ( ! $admin || ( $st !== 'preavviso' && $st !== 'senza_chiave' ) ) return '';
		if ( $uid > 0 && (string) get_option( self::OPZIONE_STRISCIA . $uid, '' ) === self::oggi() ) return '';
		$testo = $st === 'preavviso'
			? 'Il canone scade il ' . self::data_a_video( self::fine() ) . ': rinnova per non passare in sola consultazione'
			: 'Manca la chiave di licenza: incollala nella pagina «Licenza d uso»';
		$link = '<span class="rx-lic-dx"><a href="' . esc_url( self::url_pagina() ) . '">Licenza d uso</a></span>';   // la pagina la apre solo un amministratore WordPress
		return '<div class="rx-lic rx-lic-pre" role="status" data-rx-lic="' . esc_attr( $st ) . '">' . $ico_x . '<b>' . esc_html( $testo ) . '</b>' . $link
			. '<button class="rx-lic-x" type="button" aria-label="Chiudi" data-rx-lic-chiudi>×</button></div>';
	}

	/** S708 · la X della striscia: chiusa per oggi, per quell amministratore (torna il giorno dopo). */
	public static function chiudi_striscia( int $uid ): void {
		if ( $uid > 0 ) update_option( self::OPZIONE_STRISCIA . $uid, self::oggi(), false );
	}

	// ─────────────────────────────────────────────────────────────── le porte ──

	/** Router AJAX: l azione è ammessa a licenza cessata? ($livello = public|auth|write|admin|backdoor dalla mappa del router) */
	public static function azione_ammessa( string $azione, string $livello ): bool {
		if ( in_array( $azione, self::AMMESSE_EXTRA, true ) ) return true;
		if ( $livello === 'public' ) return true;
		if ( $livello === 'auth' ) return ! in_array( $azione, self::AUTH_SPENTE, true );
		return false;
	}

	/**
	 * Gestione utenti a licenza cessata (D-150.10): si può solo disattivare un utente attivo. Ritorna '' se la richiesta va al gestore
	 * (mai, a licenza cessata: la disattivazione si fa qui) oppure il messaggio di rifiuto; in caso di disattivazione risponde e termina.
	 */
	public static function save_user_a_licenza_cessata( array $post ): string {
		$cur = Rdacademy_Auth::get_current_user();
		$adm = $cur && ( ! empty( $cur['is_backdoor'] ) || ( class_exists( 'Rdacademy_Security' ) && Rdacademy_Security::is_admin( (int) $cur['id'] ) ) );
		if ( ! $adm ) return 'Permessi insufficienti per questa operazione.';   // il cancello sta prima del controllo dei permessi del router
		$uid = (int) ( $post['user_id'] ?? 0 );
		$st  = sanitize_key( (string) ( $post['status'] ?? '' ) );
		if ( $uid <= 0 || ! in_array( $st, array( 'suspended', 'disabled' ), true ) ) return self::MESSAGGIO . ': si può solo disattivare un utente.';
		$p = RDACADEMY_TABLE_PREFIX;
		$riga = Rdacademy_DB::get_row( "SELECT id, status FROM `{$p}users` WHERE id = ? LIMIT 1", array( $uid ), 'i' );
		if ( ! $riga ) return 'Utente non trovato.';
		if ( (int) $cur['id'] === $uid ) return 'Non puoi disattivare te stesso.';
		$c = Rdacademy_DB::get_connection();
		$c->query( "UPDATE `{$p}users` SET status='" . $st . "', updated_at=NOW() WHERE id=" . $uid );   // Sospeso o Disabilitato (gestione-utenti.php)
		$c->query( "DELETE FROM `{$p}sessions` WHERE user_id=" . $uid );
		if ( class_exists( 'Rdacademy_Auth' ) && method_exists( 'Rdacademy_Auth', 'log_activity' ) && $cur ) {
			Rdacademy_Auth::log_activity( (int) $cur['id'], 'users', 'disattiva', 'Utente #' . $uid . ' ' . ( $st === 'disabled' ? 'disabilitato' : 'sospeso' ) . ' a licenza cessata' );
		}
		wp_send_json_success( array( 'user_id' => $uid, 'status' => $st, 'licenza' => 'cessata' ) );
		return '';
	}

	/** REST: a licenza cessata si spengono le scritture e l API di famiglia (anche in lettura). */
	public static function porta_rest( $risultato, $server, $richiesta ) {
		if ( $risultato !== null || ! ( $richiesta instanceof WP_REST_Request ) ) return $risultato;
		$rotta = (string) $richiesta->get_route();
		if ( strpos( $rotta, '/rdacademy/v1' ) !== 0 ) return $risultato;
		if ( ! self::cessata() ) return $risultato;
		$metodo = strtoupper( (string) $richiesta->get_method() );
		if ( strpos( $rotta, '/rdacademy/v1/ext' ) === 0 || ! in_array( $metodo, array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
			return new WP_Error( self::CODICE_REST, self::MESSAGGIO, array( 'status' => 403 ) );
		}
		return $risultato;
	}

	/** wp_mail: a licenza cessata non esce nulla (le mail di RelDox passano da Rdacademy_Mailer::consegna). */
	public static function porta_wp_mail( $ritorno, $atts ) {
		if ( $ritorno !== null || self::$posta_di_accesso ) return $ritorno;
		return self::cessata() ? false : $ritorno;
	}

	/** Rdacademy_Mailer::consegna: '' se la spedizione può partire, altrimenti il messaggio. */
	public static function porta_posta(): string {
		if ( self::$posta_di_accesso || ! self::cessata() ) return '';
		return self::MESSAGGIO . ': la posta in uscita è spenta.';
	}

	/** La mail di recupero della password esce anche a licenza cessata (D-150.9). */
	public static function come_posta_di_accesso( callable $f ) {
		$prima = self::$posta_di_accesso; self::$posta_di_accesso = true;
		try { return $f(); } finally { self::$posta_di_accesso = $prima; }
	}

	/** Attività automatiche (D-150.5): a licenza cessata si sganciano tutte quelle di RelDox tranne le tre che proteggono. */
	public static function spegni_attivita(): void {
		if ( ! self::cessata() ) return;
		$ganci = self::ATTIVITA_SPENTE;
		if ( function_exists( '_get_cron_array' ) ) {
			foreach ( (array) _get_cron_array() as $ev ) {
				foreach ( array_keys( (array) $ev ) as $h ) { if ( strpos( (string) $h, 'rdacademy_' ) === 0 ) $ganci[] = (string) $h; }
			}
		}
		foreach ( array_unique( $ganci ) as $h ) {
			if ( ! in_array( $h, self::ATTIVITA_ACCESE, true ) ) remove_all_actions( $h );
		}
	}
}
