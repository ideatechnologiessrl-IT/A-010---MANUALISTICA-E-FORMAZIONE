<?php
/**
 * RelDoxAcademy Diagnostica — Pannello Manutenzione (WP-admin)
 * =====================================================================
 * Copiato da Reldox_Maintenance, RelDox CRD 3.55.09G (pkg-manutenzione-panel
 * v3.10.25, voce 84), e ridotto al banco comune a ogni prodotto della
 * famiglia: «Ruoli e recupero accesso» (REGOLE-FAMIGLIA § 3.10).
 *
 * 0.1.02 — TRASLOCATO NEL PLUGIN DI DIAGNOSTICA (SPEC-MANUTENZIONE-E-
 * DIAGNOSTICA-v1, livello 3): stesso percorso relativo che aveva nel prodotto.
 * Il verso e solo plugin → prodotto (R2):
 *  - il «Ripristina accesso admin» e un gesto del cliente (primo accesso di
 *    ogni installazione): la sua logica e la sua azione admin-ajax sono del
 *    PRODOTTO (Rdacademy_Accesso_Emergenza, pagina wp-admin «Primo accesso»). Qui
 *    il pannello lo RIDISEGNA chiamando la STESSA azione (R4), mai una copia;
 *  - nonce e chiave del registro operazioni si leggono dal prodotto (R6) e
 *    questo file carica da se la classe che li dichiara (R5);
 *  - le funzioni di lettura dei ruoli (role_is_admin, admin_roles, …) e il
 *    registro operazioni sono quelle del prodotto: qui restano solo i gesti
 *    di sviluppo (aggiungi / togli un ruolo dal pannello) e la resa.
 *
 * RAZIONALE ARCHITETTURALE — perché WP-admin e non /academy/ (copiato):
 *   WP-admin usa l autenticazione WordPress NATIVA, un piano indipendente
 *   da Rdacademy_Auth (runtime /academy/). Se i ruoli dell app si rompono, WP-admin
 *   resta accessibile.
 *
 * STRUMENTO 1 — Ruoli & recupero accesso (copiato):
 *   - Lista utenti /academy/ + ruoli (chip rimovibili singolarmente).
 *   - Assegna/rimuove UN singolo ruolo in modo ADDITIVO: INSERT IGNORE sul
 *     binomio (user_id, role_id) per aggiungere, DELETE MIRATO sullo stesso
 *     binomio per rimuovere. MAI un DELETE-all incondizionato.
 *   - «Ripristina accesso admin»: azione del prodotto (vedi sopra).
 *   - Safeguard server-side:
 *       (S1) Mai lasciare un utente con ZERO ruoli.
 *       (S2) Mai rimuovere l ULTIMO ruolo amministrativo dell INTERO sistema.
 *   - Registro operazioni (del prodotto: Rdacademy_Accesso_Emergenza::AUDIT_OPT).
 *
 * TAGLI: tutti gli altri banchi (130 riquadri del CRM: SQL, log, OPcache,
 * dead-man, stampa, posta, viste, offerte…), i launcher uploads-smoke e
 * imap-spike, il compositore RxReferto (nessun banco del guscio lo usa).
 *
 * CORRETTO RISPETTO A RELDOX:
 *  - lo scudo di riga chiedeva conferma con window.confirm(): qui apre il
 *    ripristino guidato IN PAGINA con l utente gia scelto (REGOLE § 4);
 *  - gli errori sparivano dopo 3,2 secondi in un toast: qui restano finche li
 *    chiudi e si possono selezionare (REGOLE § 4); gli esiti spariscono;
 *  - lo stato dell utente era in inglese con ucfirst (Suspended): qui in italiano,
 *    con il blocco temporaneo dichiarato;
 *  - gli istanti del registro in UTC da PHP (gmdate), non current_time().
 *
 * NUOVO: i filtri per AREA accanto ai filtri per natura; l azione
 * `rdacademy_manutenzione_banchi` per i banchi di un prodotto.
 *
 * VINCOLI (copiati): mutazioni via admin-ajax con nonce + gate
 * manage_options; Rdacademy_DB con prepared statement; CSS namespaced rx-maint-*.
 *
 * @package    RelDox_Base_Diagnostica
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// R5 — chi legge una costante di un altra classe carica da se il suo file (dal PRODOTTO).
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-accesso-emergenza.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-admin.php';                 // F021, revisione a freddo: Rdacademy_Admin::SLUG
require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-password-token.php'; // F021, revisione a freddo: MINUTI_REIMPOSTA

if ( ! class_exists( 'Rdacademy_Maintenance' ) ) :

final class Rdacademy_Maintenance {

	const PAGE_SLUG  = 'rdacademy-maintenance';
	// R6 — le costanti condivise vivono nel prodotto; qui si leggono.
	const NONCE      = Rdacademy_Accesso_Emergenza::NONCE;
	const AUDIT_OPT  = Rdacademy_Accesso_Emergenza::AUDIT_OPT;
	const AUDIT_MAX  = Rdacademy_Accesso_Emergenza::AUDIT_MAX;
	/** L azione del rientro: quella del PRODOTTO (R4). */
	const AJAX_RIENTRO = Rdacademy_Accesso_Emergenza::AJAX;

	/** Cache di richiesta: numero di utenti con almeno un ruolo admin. */
	private static $admin_user_count = null;

	/* ================================================================= *
	 *  Bootstrap
	 * ================================================================= */

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_submenu' ), 13 );

		// Endpoint AJAX di sviluppo (gate manage_options + nonce dentro ogni handler).
		// ⛔ Il rientro NON si registra qui: e l azione del prodotto (AJAX_RIENTRO).
		add_action( 'wp_ajax_rdacademy_maint_add_role',      array( __CLASS__, 'ajax_add_role' ) );
		add_action( 'wp_ajax_rdacademy_maint_remove_role',   array( __CLASS__, 'ajax_remove_role' ) );
	}

	public static function register_submenu(): void {
		// RelDox 3.55.07 (S591G) voce 84 — due voci di menu: «Manutenzione» (vivi) e «Manutenzione storica» (D-84.1).
		add_submenu_page(
			Rdacademy_Admin::SLUG,
			'Manutenzione',
			'Manutenzione',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
		add_submenu_page(
			Rdacademy_Admin::SLUG,
			'Manutenzione storica',
			'Manutenzione storica',
			'manage_options',
			self::PAGE_SLUG . '-storica',
			array( __CLASS__, 'render_storica' )
		);
	}

	/** La Manutenzione storica: la stessa pagina, con i soli banchi delle schede chiuse. */
	public static function render_storica(): void {
		Rdacademy_Banchi_Registro::$modo = 'storico';
		self::render();
	}

	/* ================================================================= *
	 *  Gate
	 * ================================================================= */

	private static function gate_passes(): bool {
		return current_user_can( 'manage_options' );
	}

	private static function gate_or_die(): void {
		if ( ! self::gate_passes() ) {
			wp_die(
				'<h2>Accesso negato</h2><p>Questa pagina richiede un super-amministratore WordPress (<code>manage_options</code>).</p>',
				esc_html( RDACADEMY_PRODUCT ) . ' — Manutenzione',
				array( 'response' => 403 )
			);
		}
	}

	/* ================================================================= *
	 *  Data helpers
	 * ================================================================= */

	private static function p(): string {
		return RDACADEMY_TABLE_PREFIX;
	}

	/* Lettura dei ruoli: del PRODOTTO (una logica sola, SPEC § 2). */
	private static function role_is_admin( array $role ): bool { return Rdacademy_Accesso_Emergenza::role_is_admin( $role ); }
	private static function all_roles(): array { return Rdacademy_Accesso_Emergenza::all_roles(); }
	private static function admin_roles(): array { return Rdacademy_Accesso_Emergenza::admin_roles(); }
	private static function recommended_backdoor_role(): ?array { return Rdacademy_Accesso_Emergenza::recommended_backdoor_role(); }

	/** Utenti + ruoli annidati. */
	private static function users_with_roles(): array {
		$p = self::p();
		$users = Rdacademy_DB::get_results(
			"SELECT id, first_name, last_name, email, status, tipo, locked_until
			   FROM `{$p}users` ORDER BY id ASC"
		);
		$rolesByUser = array();
		$rows = Rdacademy_DB::get_results(
			"SELECT ur.user_id, r.id AS role_id, r.name, r.color, r.is_backdoor, r.permissions
			   FROM `{$p}user_roles` ur
			   JOIN `{$p}roles` r ON r.id = ur.role_id
			  ORDER BY r.is_backdoor DESC, r.name ASC"
		);
		foreach ( $rows as $row ) {
			$rolesByUser[ (int) $row['user_id'] ][] = $row;
		}
		foreach ( $users as &$u ) {
			$u['roles'] = $rolesByUser[ (int) $u['id'] ] ?? array();
		}
		unset( $u );
		return $users;
	}

	/** N° utenti con almeno un ruolo admin (protezione lockout). */
	private static function admin_user_count(): int {
		if ( self::$admin_user_count !== null ) return self::$admin_user_count;
		$p   = self::p();
		$rows = Rdacademy_DB::get_results(
			"SELECT ur.user_id, r.is_backdoor, r.permissions
			   FROM `{$p}user_roles` ur
			   JOIN `{$p}roles` r ON r.id = ur.role_id"
		);
		$set = array();
		foreach ( $rows as $r ) {
			if ( self::role_is_admin( $r ) ) $set[ (int) $r['user_id'] ] = true;
		}
		self::$admin_user_count = count( $set );
		return self::$admin_user_count;
	}

	/**
	 * La rimozione del ruolo $rid dall utente $uid sarebbe bloccata?
	 * Usato sia per disegnare la chip bloccata (UX) sia, ri-valutato live,
	 * dall handler AJAX (verità lato server).
	 *
	 * @return string  '' se consentito, altrimenti il motivo del blocco.
	 */
	public static function block_reason_remove( int $uid, int $rid ): string {
		$p    = self::p();
		$rows = Rdacademy_DB::get_results(
			"SELECT r.id, r.is_backdoor, r.permissions
			   FROM `{$p}user_roles` ur
			   JOIN `{$p}roles` r ON r.id = ur.role_id
			  WHERE ur.user_id = ?",
			array( $uid ), 'i'
		);
		if ( count( $rows ) <= 1 ) {
			return 'È l\'ultimo ruolo dell\'utente: rimuoverlo lo lascerebbe senza alcun ruolo.';
		}
		$target = null;
		$remaining_admin = 0;
		foreach ( $rows as $r ) {
			if ( (int) $r['id'] === $rid ) { $target = $r; continue; }
			if ( self::role_is_admin( $r ) ) $remaining_admin++;
		}
		if ( $target === null ) return ''; // ruolo non assegnato → no-op consentito
		if ( self::role_is_admin( $target ) && $remaining_admin === 0 ) {
			// L utente perderebbe l ultimo ruolo admin: è anche l ultimo admin del sistema?
			self::$admin_user_count = null; // verità del momento, non della cache di pagina
			if ( self::admin_user_count() <= 1 ) {
				return 'È l\'ultimo amministratore del sistema: rimuoverlo bloccherebbe l\'accesso a tutti.';
			}
		}
		return '';
	}

	/* ================================================================= *
	 *  Registro operazioni e etichette: del PRODOTTO
	 * ================================================================= */

	public static function audit_read(): array { return Rdacademy_Accesso_Emergenza::audit_read(); }
	private static function audit_write( string $kind, string $text ): void { Rdacademy_Accesso_Emergenza::audit_write( $kind, $text ); }
	private static function role_label( int $rid ): string { return Rdacademy_Accesso_Emergenza::role_label( $rid ); }
	private static function user_label( int $uid ): string { return Rdacademy_Accesso_Emergenza::user_label( $uid ); }
	private static function assigned_by(): int { return Rdacademy_Accesso_Emergenza::assigned_by(); }

	/* ================================================================= *
	 *  AJAX handlers
	 * ================================================================= */

	private static function ajax_guard(): void {
		if ( ! self::gate_passes() ) {
			wp_send_json_error( 'Accesso negato (richiesto manage_options).', 403 );
		}
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( 'Token di sicurezza non valido. Ricarica la pagina e riprova.', 403 );
		}
	}

	public static function ajax_add_role(): void {
		self::ajax_guard();
		$p   = self::p();
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		$rid = (int) ( $_POST['role_id'] ?? 0 );
		if ( $uid <= 0 || $rid <= 0 ) wp_send_json_error( 'Parametri non validi.' );

		$u = Rdacademy_DB::get_row( "SELECT id FROM `{$p}users` WHERE id = ?", array( $uid ), 'i' );
		$r = Rdacademy_DB::get_row( "SELECT id FROM `{$p}roles` WHERE id = ?", array( $rid ), 'i' );
		if ( ! $u || ! $r ) wp_send_json_error( 'Utente o ruolo inesistente.' );

		$by = self::assigned_by();
		// ADDITIVO: INSERT IGNORE sul binomio (idempotente, non duplica).
		// BUG-38 di RelDox — RP-ERRORE-NON-SI-TRAVESTE-DA-DATO: il ritorno di execute()
		// e affected_rows. A zero righe NON si scrive il registro e NON si risponde successo.
		Rdacademy_DB::clear_last_error();
		$n = Rdacademy_DB::execute(
			"INSERT IGNORE INTO `{$p}user_roles` (user_id, role_id, assigned_by) VALUES (?, ?, ?)",
			array( $uid, $rid, $by ), 'iii'
		);
		if ( $n < 1 ) {
			$err = Rdacademy_DB::last_error();
			wp_send_json_error(
				'Nessuna riga scritta (righe toccate: ' . (int) $n . '). '
				. ( $err !== '' ? 'Errore del database: ' . $err : 'Il ruolo risultava gia assegnato: nulla da scrivere.' )
			);
		}
		Rdacademy_Security::invalidate_role_cache( $uid );
		self::audit_write( 'add', 'Ruolo "' . self::role_label( $rid ) . '" aggiunto a ' . self::user_label( $uid ) . '.' );
		wp_send_json_success( array( 'message' => 'Ruolo aggiunto.' ) );
	}

	public static function ajax_remove_role(): void {
		self::ajax_guard();
		$p   = self::p();
		$uid = (int) ( $_POST['user_id'] ?? 0 );
		$rid = (int) ( $_POST['role_id'] ?? 0 );
		if ( $uid <= 0 || $rid <= 0 ) wp_send_json_error( 'Parametri non validi.' );

		// Safeguard live (verità lato server).
		$reason = self::block_reason_remove( $uid, $rid );
		if ( $reason !== '' ) {
			wp_send_json_error( 'Operazione bloccata. ' . $reason );
		}

		// MIRATO: DELETE solo del binomio (mai DELETE-all).
		// BUG-38 di RelDox — il registro non deve annotare una rimozione mai avvenuta.
		Rdacademy_DB::clear_last_error();
		$n = Rdacademy_DB::execute(
			"DELETE FROM `{$p}user_roles` WHERE user_id = ? AND role_id = ?",
			array( $uid, $rid ), 'ii'
		);
		if ( $n < 1 ) {
			$err = Rdacademy_DB::last_error();
			wp_send_json_error(
				'Nessuna riga rimossa (righe toccate: ' . (int) $n . '). '
				. ( $err !== '' ? 'Errore del database: ' . $err : 'Il binomio utente-ruolo non risultava presente: nulla da rimuovere.' )
			);
		}
		Rdacademy_Security::invalidate_role_cache( $uid );
		self::audit_write( 'remove', 'Ruolo "' . self::role_label( $rid ) . '" rimosso da ' . self::user_label( $uid ) . '.' );
		wp_send_json_success( array( 'message' => 'Ruolo rimosso.' ) );
	}

	/* ================================================================= *
	 *  Render
	 * ================================================================= */

	public static function render(): void {
		self::gate_or_die();

		$storico      = ( Rdacademy_Banchi_Registro::$modo === 'storico' );
		$users        = self::users_with_roles();
		$all_roles    = self::all_roles();
		$admin_roles  = self::admin_roles();
		$rec_backdoor = self::recommended_backdoor_role();
		$audit        = self::audit_read();
		$nonce        = wp_create_nonce( self::NONCE );
		$ajax_url     = admin_url( 'admin-ajax.php' );
		$aree         = Rdacademy_Banchi_Registro::aree();
		$conta        = Rdacademy_Banchi_Registro::conta();

		echo '<div class="wrap rx-maint">';
		self::print_styles();

		// ── Testata ─────────────────────────────────────────────────
		?>
		<div class="rx-maint-ph">
			<div class="rx-maint-ph-l">
				<h1>
					<span class="rx-maint-h-ic" aria-hidden="true">
						<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2-2 2.5-2.5z"/></svg>
					</span>
					<?php echo esc_html( Rdacademy_Banchi_Registro::titolo() ); ?>
				</h1>
				<p class="rx-maint-sub"><?php echo Rdacademy_Banchi_Registro::sottotitolo(); // HTML gia sicuro ?></p>
			</div>
			<div class="rx-maint-ph-r">
				<span class="rx-maint-gate">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					Accesso: super-admin WP · manage_options
				</span>
				<span class="rx-maint-ver"><?php echo esc_html( strtoupper( RDACADEMY_PRODUCT ) . ' v' . RDACADEMY_VERSION_LABEL ); ?></span>
			</div>
		</div>

		<?php
		echo Rdacademy_Banchi_Registro::css();
		echo Rdacademy_Banchi_Registro::contatore( admin_url( 'admin.php?page=' . self::PAGE_SLUG ), admin_url( 'admin.php?page=' . self::PAGE_SLUG . '-storica' ) );
		?>
		<div class="rx-maint-plane">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
			<span><strong>Perché è qui e non in /<?php echo esc_html( RDACADEMY_APP_SLUG ); ?>/.</strong> WP-admin usa l'autenticazione WordPress nativa, un piano indipendente da quella dell'applicazione. Se i ruoli di <code>/<?php echo esc_html( RDACADEMY_APP_SLUG ); ?>/</code> si rompono, da qui puoi ripristinarli da solo, senza ricorrere a phpMyAdmin.</span>
		</div>

		<div id="rx-maint-toast" class="rx-maint-toast" role="status" aria-live="polite"><span class="rx-maint-toast-t"></span><button type="button" class="rx-maint-toast-x" aria-label="Chiudi" hidden>&times;</button></div>
		<?php

		// ── Barra di ricerca e filtri (mockup MOCKUP-manutenzione-riorg-v1 di RelDox) ──
		echo '<style>.rx-mtb{position:sticky;top:32px;z-index:30;background:#f0f0f1;padding:12px 0 8px;margin:0 0 4px;border-bottom:1px solid #dcdcde}.rx-mtb-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.rx-msearch{flex:1 1 240px;min-width:0;position:relative}.rx-msearch input{width:100%;padding:8px 12px 8px 32px;border:1px solid #8c8f94;border-radius:6px;font-size:13px;box-sizing:border-box}.rx-msearch svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);opacity:.5}.rx-mchips{display:flex;gap:6px;flex-wrap:wrap}.rx-mchip{border:1px solid #c3c4c7;background:#fff;color:#50575e;padding:6px 11px;border-radius:999px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px}.rx-mchip .d{width:8px;height:8px;border-radius:50%}.rx-mchip[aria-pressed=true]{border-color:#0f766e;background:#e6f2f1;color:#0b5a54}.rx-msub{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px;padding-bottom:2px;align-items:center}.rx-msub .lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#8c8f94;margin-right:2px}.rx-cat{margin:26px 0 4px;scroll-margin-top:120px}.rx-cat-h{display:flex;align-items:center;gap:11px;margin:0 0 10px;flex-wrap:wrap}.rx-cat-ic{width:32px;height:32px;border-radius:8px;display:grid;place-items:center;color:#fff;flex:0 0 auto;background:#0f766e}.rx-cat-h h2{font-size:15px;margin:0;padding:0;border:0}.rx-cat-h .c{font:600 11px/1 ui-monospace,monospace;color:#646970;background:#fff;border:1px solid #dcdcde;border-radius:999px;padding:3px 8px}.rx-cat-h p{margin:2px 0 0;font-size:12px;color:#8c8f94;width:100%}.rx-tw{position:relative}.rx-nb{position:absolute;right:14px;top:14px;z-index:2;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:999px;display:inline-flex;align-items:center;gap:5px;pointer-events:none}.rx-nb .d{width:6px;height:6px;border-radius:50%}.b-util{background:#e7f6ec;color:#137a37}.b-util .d{background:#16a34a}.b-gold{background:#fdf1e0;color:#a45c07}.b-gold .d{background:#d97706}.b-oneshot{background:#eef1f5;color:#475569}.b-oneshot .d{background:#64748b}.rx-cat[hidden],.rx-tw[hidden]{display:none}.rx-mempty{display:none;padding:30px;text-align:center;color:#646970;border:1px dashed #c3c4c7;border-radius:8px;background:#fff;line-height:1.6}@media (max-width:600px){.rx-mtb{position:static}.rx-nb{position:static;margin:0 0 6px}}</style>';
		echo '<div class="rx-mtb"><div class="rx-mtb-row"><div class="rx-msearch"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg><input id="rxMSearch" type="search" placeholder="Cerca strumento, numero, scopo..." aria-label="Cerca strumento"></div>'
			. '<div class="rx-mchips" id="rxMChips"><button type="button" class="rx-mchip" data-nat="util" aria-pressed="false"><span class="d" style="background:#16a34a"></span>Utility</button><button type="button" class="rx-mchip" data-nat="gold" aria-pressed="false"><span class="d" style="background:#d97706"></span>Golden</button><button type="button" class="rx-mchip" data-nat="oneshot" aria-pressed="false"><span class="d" style="background:#64748b"></span>One-shot</button></div></div>';
		// NUOVO: le aree sono filtri (in RelDox erano solo ancore di scorrimento).
		echo '<nav class="rx-msub" id="rxMAree" aria-label="Aree"><span class="lbl">Area</span>';
		foreach ( $aree as $cat => $a ) {
			echo '<button type="button" class="rx-mchip" data-area="' . esc_attr( $cat ) . '" aria-pressed="false">' . esc_html( (string) $a[0] ) . '</button>';
		}
		echo '</nav></div>';
		$vuoto = $storico
			? ( $conta['storici'] === 0
				? 'Registro storico vuoto: nessun banco è ancora nato per una scheda chiusa. Quando una scheda passa in PUNTI-CHIUSI, il suo banco compare qui da solo e funziona come prima.'
				: 'Nessun banco storico corrisponde ai filtri.' )
			: 'Nessuno strumento corrisponde ai filtri.';
		echo '<div class="rx-mempty" id="rxMEmpty">' . esc_html( $vuoto ) . '</div>';

		// ── Le aree, ognuna con i suoi riquadri ─────────────────────
		foreach ( $aree as $cat => $a ) {
			echo '<section class="rx-cat" id="' . esc_attr( $cat ) . '" data-cat="' . esc_attr( $cat ) . '"><div class="rx-cat-h"><span class="rx-cat-ic"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/><circle cx="12" cy="12" r="3.2"/></svg></span><h2>' . esc_html( (string) $a[0] ) . '</h2><span class="c">0</span><p>' . esc_html( (string) $a[1] ) . '</p></div>';
			if ( $cat === 'cat-sistema' ) {
				// STRUMENTO 1 — Ruoli & recupero accesso (il banco comune della famiglia).
				echo '<div class="rx-tw" data-nat="util" data-q="1 ruoli recupero accesso utenti amministratore collegamento monouso password"><span class="rx-nb b-util"><span class="d"></span>Utility</span>';
				self::render_tool_roles( $users, $all_roles, $admin_roles, $rec_backdoor, $audit );
				echo '</div>';
			}
			// NUOVO — i banchi di un prodotto: si disegnano dentro la loro area,
			// ognuno nel suo <div class="rx-tw" data-nat data-q> dichiarato nel registro.
			do_action( 'rdacademy_manutenzione_banchi', $cat, $ajax_url, $nonce );
			echo '</section>';
		}

		// Lo script del registro PRIMA di quello della ricerca: segna lo stato di ogni riquadro.
		echo Rdacademy_Banchi_Registro::script();
		echo '<script>(function(){var q=document.getElementById(\'rxMSearch\'),chips=document.getElementById(\'rxMChips\'),aree=document.getElementById(\'rxMAree\'),empty=document.getElementById(\'rxMEmpty\');var nat=new Set(),area=new Set();function apply(){var term=(q.value||\'\').trim().toLowerCase();var any=false;var modo=document.querySelector(\'.rx-modo-storico\')?\'storico\':\'vivo\';document.querySelectorAll(\'.rx-cat\').forEach(function(sec){var shown=0;var okA=!area.size||area.has(sec.dataset.cat);sec.querySelectorAll(\'.rx-tw\').forEach(function(tw){var okN=!nat.size||nat.has(tw.dataset.nat);var okQ=!term||(tw.dataset.q||\'\').indexOf(term)>=0;var okS=(tw.dataset.st||\'vivo\')===modo;var ok=okA&&okN&&okQ&&okS;tw.hidden=!ok;if(ok){shown++;any=true;}});sec.hidden=shown===0;var c=sec.querySelector(\'.rx-cat-h .c\');if(c)c.textContent=shown;});empty.style.display=any?\'none\':\'block\';}function toggle(set,b,key){var n=b.dataset[key];if(set.has(n)){set.delete(n);b.setAttribute(\'aria-pressed\',\'false\');}else{set.add(n);b.setAttribute(\'aria-pressed\',\'true\');}apply();}q.addEventListener(\'input\',apply);chips.addEventListener(\'click\',function(e){var b=e.target.closest(\'.rx-mchip\');if(b)toggle(nat,b,\'nat\');});if(aree)aree.addEventListener(\'click\',function(e){var b=e.target.closest(\'.rx-mchip\');if(b)toggle(area,b,\'area\');});apply();})();</script>';
		// COPIA ESITO (RelDox S93) — un solo ascoltatore delegato per tutta la pagina:
		// ogni banco dichiara data-rx-copia="id del proprio riquadro" e basta.
		echo '<script>(function(){document.addEventListener(\'click\',function(e){var b=e.target.closest&&e.target.closest(\'[data-rx-copia]\');if(!b)return;var el=document.getElementById(b.getAttribute(\'data-rx-copia\'));if(!el)return;var txt=el.innerText||el.textContent||\'\';var eti=b.textContent;function fine(m){b.textContent=m;setTimeout(function(){b.textContent=eti;},1600);}function ok(){fine(\'Copiato\');}function ko(){fine(\'Copia non riuscita\');}if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(txt).then(ok,ko);return;}var ta=document.createElement(\'textarea\');ta.value=txt;ta.setAttribute(\'readonly\',\'\');ta.style.position=\'fixed\';ta.style.top=\'-1000px\';document.body.appendChild(ta);ta.select();try{document.execCommand(\'copy\');ok();}catch(err){ko();}document.body.removeChild(ta);});})();</script>';
		// RelDox 3.18.02 (S280) — COPIA IN TESTA A OGNI STRUMENTO: si mette il comando
		// dove manca, il motore resta quello delegato qui sopra (RP-UNA-MANO-SOLA-SUL-COMANDO).
		echo '<script>(function(){document.querySelectorAll(\'.rx-tw\').forEach(function(tw,i){if(tw.querySelector(\'[data-rx-copia]\'))return;var corpo=tw.querySelector(\'.rx-maint-tool\')||tw;if(!corpo.id)corpo.id=\'rxTwBody\'+i;var b=document.createElement(\'button\');b.type=\'button\';b.className=\'button rx-copia-tutto\';b.setAttribute(\'data-rx-copia\',corpo.id);b.title=\'Copia il contenuto di questo Strumento negli appunti\';b.textContent=\'Copia\';b.style.cssText=\'margin:0 4px 0 0;font-size:12px;line-height:1.9;height:auto;flex:none\';var h=tw.querySelector(\'.rx-maint-th\');if(h)h.insertAdjacentElement(\'afterbegin\',b);else tw.insertAdjacentElement(\'afterbegin\',b);});})();</script>';

		// ── Segnaposto di estensibilità (copiato) ─────────────────────
		?>
		<div class="rx-maint-future">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
			Pannello estensibile — i banchi di un prodotto si aggiungono al registro e compaiono qui come ulteriori riquadri.
		</div>
		<?php

		self::print_script( $ajax_url, $nonce );
		echo '</div>'; // .wrap
	}

	/* ---- Strumento 1: tabella ruoli + recupero ---- */
	private static function render_tool_roles( array $users, array $all_roles, array $admin_roles, ?array $rec_backdoor, array $audit ): void {
		$rec_id = $rec_backdoor ? (int) $rec_backdoor['id'] : 0;
		$app    = '/' . RDACADEMY_APP_SLUG . '/';
		?>
		<section class="rx-maint-tool" id="rx-maint-roles">
			<div class="rx-maint-th">
				<div class="rx-maint-tic t-blue" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				</div>
				<div class="rx-maint-tt">
					<h2>Ruoli &amp; recupero accesso</h2>
					<div class="rx-maint-td">Gestisci i ruoli degli utenti <code><?php echo esc_html( $app ); ?></code> in modo additivo e ripristina l'accesso admin in caso di blocco.</div>
				</div>
			</div>
			<div class="rx-maint-tb">

				<!-- Banner recupero -->
				<div class="rx-maint-rec">
					<div class="rx-maint-rec-ic" aria-hidden="true">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
					</div>
					<div class="rx-maint-rec-txt">
						<div class="t">Hai perso l'accesso admin a <?php echo esc_html( $app ); ?>?</div>
						<div class="s">Riattiva l'account scelto, azzera blocchi e scadenze, chiude le sessioni aperte, gli garantisce il ruolo con privilegi e ti mostra un <strong>collegamento monouso</strong> per scegliere la password nuova (vale <?php echo (int) Rdacademy_Password_Token::MINUTI_REIMPOSTA; ?> minuti, una volta sola). Nessuna password &egrave; scritta nel codice.</div>
					</div>
					<button type="button" class="rx-maint-btn safe" id="rx-maint-rec-toggle" aria-controls="rx-maint-rec-form" aria-expanded="false"<?php disabled( ! $users || ! $admin_roles ); ?> title="<?php echo ( ! $users || ! $admin_roles ) ? esc_attr( 'Spento: ' . ( ! $users ? 'non ci sono utenti.' : 'non esiste un ruolo con privilegi amministrativi.' ) ) : ''; ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12a9 9 0 1 0 9-9 9.7 9.7 0 0 0-6.7 2.7L3 8"/><path d="M3 3v5h5"/></svg>
						Ripristina accesso admin
					</button>
				</div>

				<!-- Ripristino guidato (chiuso di default): e la conferma in pagina -->
				<div class="rx-maint-rec-form" id="rx-maint-rec-form" hidden>
					<div class="rx-maint-sec-head"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg> Ripristino guidato</div>
					<div class="rx-maint-rf-grid">
						<div class="rx-maint-ff">
							<label for="rx-maint-rec-user">Utente a cui ridare accesso</label>
							<select id="rx-maint-rec-user">
								<?php foreach ( $users as $u ) :
									$lbl = trim( ( $u['first_name'] ?? '' ) . ' ' . ( $u['last_name'] ?? '' ) );
									$lbl = $lbl !== '' ? $lbl : ( $u['email'] ?? ( 'utente #' . $u['id'] ) ); ?>
									<option value="<?php echo (int) $u['id']; ?>"><?php echo esc_html( $u['email'] . ' — ' . $lbl . ' (id ' . $u['id'] . ')' ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="rx-maint-ff">
							<label for="rx-maint-rec-role">Ruolo con privilegi da assegnare</label>
							<select id="rx-maint-rec-role">
								<?php foreach ( $admin_roles as $r ) :
									$is_rec = ( (int) $r['id'] === $rec_id );
									$suffix = ! empty( $r['is_backdoor'] ) ? ' · backdoor' : ' · permissions.all'; ?>
									<option value="<?php echo (int) $r['id']; ?>" <?php selected( $is_rec ); ?>><?php echo esc_html( $r['name'] . ' (id ' . $r['id'] . ')' . $suffix . ( $is_rec ? ' · consigliato' : '' ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<p class="rx-maint-rf-note">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
						<span>La password attuale smette di valere, l'account torna attivo, blocchi e scadenze si azzerano, le sessioni aperte si chiudono e compare un <strong>collegamento monouso</strong> (<?php echo (int) Rdacademy_Password_Token::MINUTI_REIMPOSTA; ?> minuti, una volta sola) con cui si sceglie la password nuova. Il ruolo si aggiunge con <code>INSERT IGNORE</code>: <strong>non tocca i ruoli esistenti</strong>. L'operazione viene registrata qui sotto.</span>
					</p>
					<div class="rx-maint-rf-foot">
						<button type="button" class="rx-maint-btn s sm" id="rx-maint-rec-cancel">Annulla</button>
						<button type="button" class="rx-maint-btn safe sm" id="rx-maint-rec-confirm">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
							Ripristina e crea il collegamento
						</button>
					</div>
				</div>

				<!-- Tabella utenti × ruoli -->
				<div class="rx-maint-sec-head">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
					Utenti <?php echo esc_html( $app ); ?> e ruoli
					<span class="count"><?php echo count( $users ); ?> utenti</span>
				</div>

				<?php if ( ! $users ) : ?>
					<p class="rx-maint-empty">Nessun utente nel prodotto: la tabella <code><?php echo esc_html( RDACADEMY_TABLE_PREFIX ); ?>users</code> è vuota.</p>
				<?php else : ?>
				<table class="rx-maint-utable">
					<thead>
						<tr><th>Utente</th><th>Stato</th><th>Ruoli assegnati</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ( $users as $u ) : self::render_user_row( $u, $all_roles, $rec_id ); endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>

				<!-- Safeguard visibili -->
				<div class="rx-maint-safebar">
					<div class="rx-maint-safe">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
						<span><strong>Solo additivo.</strong> Assegna/rimuove un singolo ruolo per volta (<code>INSERT IGNORE</code> / <code>DELETE</code> mirato). Mai un <code>DELETE-all</code> incondizionato.</span>
					</div>
					<div class="rx-maint-safe">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
						<span><strong>Protezione lockout.</strong> Non puoi lasciare un utente senza ruoli, né rimuovere l'ultimo amministratore del sistema (chip bloccata col lucchetto).</span>
					</div>
				</div>

				<!-- Registro operazioni -->
				<div class="rx-maint-audit" id="rx-maint-audit">
					<div class="rx-maint-sec-head">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
						Registro operazioni
						<span class="count"><?php echo count( $audit ) ? ( 'ultime ' . min( count( $audit ), 10 ) . ' di ' . count( $audit ) ) : 'vuoto'; ?></span>
					</div>
					<?php if ( empty( $audit ) ) : ?>
						<p class="rx-maint-empty">Nessuna operazione registrata. Le modifiche ai ruoli e i ripristini compariranno qui.</p>
					<?php else : ?>
						<div class="rx-maint-audit-list">
							<?php foreach ( array_slice( $audit, 0, 10 ) as $a ) :
								$kind = $a['kind'] ?? 'add';
								$icls = $kind === 'remove' ? 'rem' : ( $kind === 'restore' ? 'rec' : 'add' );
								$ico  = $kind === 'remove'
									? '<path d="M5 12h14"/>'
									: ( $kind === 'restore' ? '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>' : '<path d="M12 5v14M5 12h14"/>' );
								?>
								<div class="rx-maint-audit-row">
									<div class="rx-maint-audit-ic <?php echo esc_attr( $icls ); ?>"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><?php echo $ico; ?></svg></div>
									<div class="rx-maint-audit-txt"><?php echo esc_html( $a['text'] ?? '' ); ?><div class="rx-maint-audit-meta"><?php echo esc_html( ( $a['actor'] ?? '—' ) . ' · ' . ( $a['ts'] ?? '' ) ); ?></div></div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>

			</div>
		</section>
		<?php
	}

	/* ---- Riga utente ---- */
	private static function render_user_row( array $u, array $all_roles, int $rec_id ): void {
		$uid    = (int) $u['id'];
		$name   = trim( ( $u['first_name'] ?? '' ) . ' ' . ( $u['last_name'] ?? '' ) );
		if ( $name === '' ) $name = (string) ( $u['email'] ?? ( 'utente #' . $uid ) );
		$init   = self::initials( $u['first_name'] ?? '', $u['last_name'] ?? '', $u['email'] ?? '' );
		$status = (string) ( $u['status'] ?? 'active' );
		$status_active = ( $status === 'active' );
		$stati  = array( 'active' => 'Attivo', 'suspended' => 'Sospeso', 'disabled' => 'Disabilitato' );
		$locked = ( ! empty( $u['locked_until'] ) && strtotime( (string) $u['locked_until'] . ' UTC' ) > time() );
		$assigned_ids = array();
		foreach ( ( $u['roles'] ?? array() ) as $r ) $assigned_ids[] = (int) $r['role_id'];
		?>
		<tr data-uid="<?php echo $uid; ?>" data-email="<?php echo esc_attr( (string) ( $u['email'] ?? '' ) ); ?>">
			<td>
				<div class="rx-maint-u">
					<div class="rx-maint-av<?php echo $status_active ? '' : ' off'; ?>"><?php echo esc_html( $init ); ?></div>
					<div style="min-width:0">
						<div class="rx-maint-u-name"><?php echo esc_html( $name ); ?></div>
						<div class="rx-maint-u-mail"><?php echo esc_html( ( $u['email'] ?? '' ) . ' · id ' . $uid . ( ( $u['tipo'] ?? '' ) === 'esterno' ? ' · esterno' : '' ) ); ?></div>
					</div>
				</div>
			</td>
			<td>
				<span class="rx-maint-status"><span class="dot <?php echo $status_active && ! $locked ? 'on' : 'off'; ?>"></span> <?php echo esc_html( $stati[ $status ] ?? $status ); ?><?php echo $locked ? ' · bloccato' : ''; ?></span>
			</td>
			<td class="rx-maint-roles-cell">
				<div class="rx-maint-chips">
					<?php foreach ( ( $u['roles'] ?? array() ) as $r ) :
						$rid    = (int) $r['role_id'];
						$is_adm = self::role_is_admin( $r );
						$is_bd  = ! empty( $r['is_backdoor'] );
						$cls    = $is_bd ? 'backdoor' : ( $is_adm ? 'admin' : '' );
						$block  = self::block_reason_remove( $uid, $rid );
						if ( $block !== '' ) : ?>
							<span class="rx-maint-chip locked" title="<?php echo esc_attr( $block ); ?>" data-rid="<?php echo $rid; ?>">
								<span class="lk" aria-hidden="true"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
								<?php echo esc_html( $r['name'] ); ?>
								<span class="screen-reader-text"><?php echo esc_html( ' — ' . $block ); ?></span>
							</span>
						<?php else : ?>
							<span class="rx-maint-chip <?php echo esc_attr( $cls ); ?>" data-rid="<?php echo $rid; ?>">
								<?php echo esc_html( $r['name'] ); ?>
								<span class="rx-maint-addwrap">
									<button type="button" class="x rx-maint-remove" data-uid="<?php echo $uid; ?>" data-rid="<?php echo $rid; ?>" aria-haspopup="true" aria-expanded="false" aria-label="Rimuovi ruolo <?php echo esc_attr( $r['name'] ); ?>"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
									<span class="rx-maint-rm-pop" hidden role="dialog" aria-label="Conferma rimozione ruolo">
										<span class="rx-maint-rm-q">Rimuovere «<?php echo esc_html( $r['name'] ); ?>» da <?php echo esc_html( $name ); ?>?</span>
										<button type="button" class="rx-maint-btn dgr sm rx-maint-remove-confirm" data-uid="<?php echo $uid; ?>" data-rid="<?php echo $rid; ?>">Rimuovi</button>
										<button type="button" class="rx-maint-btn s sm rx-maint-remove-cancel">Annulla</button>
									</span>
								</span>
							</span>
						<?php endif;
					endforeach; ?>

					<?php // Selettore «+ Aggiungi»: ruoli non ancora assegnati
					$addable = array();
					foreach ( $all_roles as $r ) {
						if ( ! in_array( (int) $r['id'], $assigned_ids, true ) ) $addable[] = $r;
					}
					if ( ! empty( $addable ) ) : ?>
						<span class="rx-maint-addwrap">
							<button type="button" class="rx-maint-chip-add rx-maint-add-toggle" aria-label="Aggiungi ruolo a <?php echo esc_attr( $name ); ?>"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Aggiungi</button>
							<span class="rx-maint-add-pop" hidden>
								<select class="rx-maint-add-sel" aria-label="Ruolo da aggiungere">
									<?php foreach ( $addable as $r ) : ?>
										<option value="<?php echo (int) $r['id']; ?>"><?php echo esc_html( $r['name'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="button" class="rx-maint-btn safe sm rx-maint-add-confirm" data-uid="<?php echo $uid; ?>">Aggiungi</button>
							</span>
						</span>
					<?php endif; ?>
				</div>
			</td>
			<td>
				<div class="rx-maint-row-acts">
					<?php if ( $rec_id > 0 ) : ?>
						<button type="button" class="rx-maint-icobtn safe rx-maint-shield" data-uid="<?php echo $uid; ?>" data-rid="<?php echo $rec_id; ?>" title="Rientro d'emergenza: apre il ripristino guidato per questo utente" aria-label="Ripristina accesso admin per <?php echo esc_attr( $name ); ?>">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
						</button>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/* ================================================================= *
	 *  Util
	 * ================================================================= */

	private static function initials( string $f, string $l, string $email ): string {
		$f = trim( $f ); $l = trim( $l );
		if ( $f !== '' || $l !== '' ) {
			return strtoupper( mb_substr( $f, 0, 1 ) . mb_substr( $l, 0, 1 ) );
		}
		$email = trim( $email );
		return $email !== '' ? strtoupper( mb_substr( $email, 0, 2 ) ) : '?';
	}

	/* ================================================================= *
	 *  Stili in linea (namespaced rx-maint-*) — copiati da RelDox
	 * ================================================================= */

	public static function print_styles(): void {
		?>
<style>
.rx-maint{--m-blue:#1F4E79;--m-blue-mid:#2E75B6;--m-blue-light:#D6E4F0;--m-teal:#0F766E;--m-amber:#D97706;--m-amber-light:#FEF3C7;--m-red:#DC2626;--m-red-light:#FEE2E2;--m-green:#16A34A;--m-green-light:#DCFCE7;--m-indigo:#4F46E5;--m-indigo-light:#E0E7FF;--m-bg:#F3F4F6;--m-border:#E5E7EB;--m-border-mid:#D1D5DB;--m-text:#111827;--m-text-mid:#374151;--m-muted:#6B7280;--m-faint:#9CA3AF;--m-radius:8px;--m-radius-lg:12px;--m-shadow:0 1px 3px rgba(0,0,0,.08);--m-mono:"SFMono-Regular",Consolas,Menlo,monospace;}
.rx-maint{max-width:1120px;}
.rx-maint code{font-family:var(--m-mono);font-size:.86em;background:var(--m-bg);padding:1px 6px;border-radius:4px;color:var(--m-text-mid);}
.rx-maint h1,.rx-maint h2{margin:0;padding:0;}
.rx-maint button{cursor:pointer;}
.rx-maint button:disabled{cursor:not-allowed;opacity:.55;}
.rx-maint-ph{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin:6px 0 6px;}
.rx-maint-ph h1{font-size:23px;font-weight:700;color:var(--m-text);display:flex;align-items:center;gap:11px;letter-spacing:-.3px;}
.rx-maint-h-ic{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,var(--m-blue),var(--m-teal));display:flex;align-items:center;justify-content:center;color:#fff;flex:none;}
.rx-maint-sub{font-size:13.5px;color:var(--m-muted);margin-top:6px;max-width:78ch;line-height:1.6;}
.rx-maint-ph-r{display:flex;flex-direction:column;align-items:flex-end;gap:8px;}
.rx-maint-gate{display:inline-flex;align-items:center;gap:7px;background:var(--m-indigo-light);color:var(--m-indigo);font-size:11.5px;font-weight:600;padding:6px 12px;border-radius:999px;white-space:nowrap;}
.rx-maint-ver{font-size:11px;font-weight:700;color:var(--m-muted);letter-spacing:.3px;font-family:var(--m-mono);}
.rx-maint-plane{display:flex;gap:10px;align-items:flex-start;background:var(--m-blue-light);border:1px solid #b9d3ec;border-radius:var(--m-radius);padding:11px 14px;margin:12px 0 22px;font-size:12.5px;line-height:1.55;color:var(--m-blue);}
.rx-maint-plane svg{flex:none;margin-top:1px;}
.rx-maint-tool{background:#fff;border:1px solid var(--m-border);border-radius:var(--m-radius-lg);box-shadow:var(--m-shadow);margin-bottom:20px;overflow:hidden;}
.rx-maint-th{display:flex;align-items:center;gap:13px;padding:16px 20px;padding-right:96px;border-bottom:1px solid var(--m-border);background:linear-gradient(180deg,#fff,#fcfdfe);}
@media (max-width:600px){.rx-maint .rx-maint-th{flex-wrap:wrap;padding:14px;}.rx-maint .rx-maint-th .rx-maint-tt{flex:1 1 calc(100% - 60px);}}
.rx-maint-tic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex:none;color:#fff;}
.rx-maint-tic.t-blue{background:linear-gradient(135deg,var(--m-blue),var(--m-blue-mid));}
.rx-maint-tt{flex:1;min-width:0;}
.rx-maint-tt h2{font-size:16px;font-weight:700;color:var(--m-text);letter-spacing:-.2px;}
.rx-maint-td{font-size:12.5px;color:var(--m-muted);margin-top:3px;line-height:1.5;}
.rx-maint-tb{padding:20px;}
.rx-maint-sec-head{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--m-muted);display:flex;align-items:center;gap:8px;padding-bottom:9px;border-bottom:1px solid var(--m-border);margin-bottom:14px;flex-wrap:wrap;}
.rx-maint-sec-head svg{color:var(--m-teal);}
.rx-maint-sec-head .count{margin-left:auto;font-weight:600;color:var(--m-muted);font-size:11px;letter-spacing:0;text-transform:none;}
.rx-maint-btn{padding:8px 15px;border-radius:var(--m-radius);font-size:13px;font-weight:600;border:1px solid transparent;display:inline-flex;align-items:center;gap:7px;white-space:nowrap;text-decoration:none;line-height:1.2;}
.rx-maint-btn.p{background:var(--m-blue);color:#fff;}.rx-maint-btn.p:hover{background:#1a4368;color:#fff;}
.rx-maint-btn.s{background:#fff;color:var(--m-text-mid);border-color:var(--m-border-mid);}.rx-maint-btn.s:hover{background:var(--m-bg);}
.rx-maint-btn.safe{background:#15803D;color:#fff;}.rx-maint-btn.safe:hover{background:#166534;color:#fff;}
.rx-maint-btn.sm{font-size:12px;padding:6px 11px;border-radius:6px;}
.rx-maint-rec{display:flex;gap:14px;align-items:center;background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:1px solid #bbf7d0;border-radius:var(--m-radius);padding:14px 16px;margin-bottom:20px;}
.rx-maint-rec-ic{width:40px;height:40px;border-radius:10px;background:var(--m-green);color:#fff;display:flex;align-items:center;justify-content:center;flex:none;}
.rx-maint-rec-txt{flex:1;min-width:0;}
.rx-maint-rec-txt .t{font-size:13.5px;font-weight:700;color:#14532d;}
.rx-maint-rec-txt .s{font-size:12px;color:#166534;margin-top:2px;line-height:1.5;}
.rx-maint-rec-form{border:1px dashed #86efac;border-radius:var(--m-radius);padding:16px;background:#f7fef9;margin-bottom:20px;}
.rx-maint-rf-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;}
.rx-maint-ff label{display:block;font-size:11px;font-weight:600;color:var(--m-text-mid);margin-bottom:5px;text-transform:uppercase;letter-spacing:.3px;}
.rx-maint-ff select{width:100%;max-width:100%;padding:9px 11px;border:1px solid var(--m-border-mid);border-radius:6px;font-size:13px;background:#fff;color:var(--m-text);}
.rx-maint-rf-note{font-size:11.5px;color:var(--m-muted);line-height:1.5;margin:12px 0 0;display:flex;gap:8px;align-items:flex-start;}
.rx-maint-rf-note svg{flex:none;margin-top:1px;color:var(--m-faint);}
.rx-maint-rf-foot{display:flex;justify-content:flex-end;gap:8px;margin-top:14px;flex-wrap:wrap;}
.rx-maint-utable{width:100%;border-collapse:separate;border-spacing:0;background:#fff;}
.rx-maint-utable thead th{font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--m-muted);text-align:left;padding:0 12px 9px;border-bottom:1px solid var(--m-border);}
.rx-maint-utable tbody td{padding:13px 12px;border-bottom:1px solid var(--m-border);vertical-align:middle;}
.rx-maint-utable tbody tr:last-child td{border-bottom:none;}
.rx-maint-u{display:flex;align-items:center;gap:11px;}
.rx-maint-av{width:36px;height:36px;border-radius:50%;background:var(--m-blue-mid);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12.5px;font-weight:700;flex:none;}
.rx-maint-av.off{background:var(--m-muted);}
.rx-maint-u-name{font-size:13.5px;font-weight:600;color:var(--m-text);}
.rx-maint-u-mail{font-size:12px;color:var(--m-muted);margin-top:1px;overflow-wrap:anywhere;}
.rx-maint-status{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--m-text-mid);}
.rx-maint-status .dot{width:8px;height:8px;border-radius:50%;}
.rx-maint-status .dot.on{background:var(--m-green);}.rx-maint-status .dot.off{background:var(--m-amber);}
.rx-maint-chips{display:flex;flex-wrap:wrap;gap:6px;align-items:center;}
.rx-maint-chip{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;padding:4px 6px 4px 10px;border-radius:999px;border:1px solid var(--m-border);background:#fff;color:var(--m-text-mid);}
.rx-maint-chip .x{display:inline-flex;align-items:center;justify-content:center;width:17px;height:17px;border-radius:50%;color:var(--m-muted);border:none;background:transparent;padding:0;}
.rx-maint-chip .x:hover{background:var(--m-red-light);color:var(--m-red);}
.rx-maint-chip.admin{background:var(--m-blue-light);color:var(--m-blue);border-color:#bcd6ef;}
.rx-maint-chip.backdoor{background:var(--m-indigo-light);color:var(--m-indigo);border-color:#c7d2fe;}
.rx-maint-chip.locked{padding-right:10px;background:var(--m-amber-light);color:#92400e;border-color:#fde68a;cursor:help;}
.rx-maint-chip.locked .lk{display:inline-flex;color:var(--m-amber);}
.rx-maint-chip-add{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;padding:4px 10px;border-radius:999px;border:1px dashed var(--m-border-mid);background:#fff;color:var(--m-blue-mid);}
.rx-maint-chip-add:hover{background:var(--m-blue-light);border-color:var(--m-blue-mid);}
.rx-maint-addwrap{position:relative;display:inline-flex;flex-wrap:wrap;}
.rx-maint-add-pop:not([hidden]){display:inline-flex;align-items:center;gap:6px;margin-left:6px;flex-wrap:wrap;}
.rx-maint-add-pop select{padding:4px 8px;border:1px solid var(--m-border-mid);border-radius:6px;font-size:12px;background:#fff;max-width:100%;}
/* BUG-38 di RelDox — RP-UNA-CONFERMA-VIVE-NELLA-PAGINA: la conferma di rimozione sta nel DOM. */
.rx-maint-rm-pop:not([hidden]){position:absolute;top:calc(100% + 6px);right:0;z-index:30;display:inline-flex;align-items:center;gap:7px;padding:8px 10px;background:#fff;border:1px solid var(--m-border-mid);border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.14);white-space:nowrap;}
.rx-maint-rm-q{font-size:12px;font-weight:600;color:var(--m-text-mid);}
.rx-maint-btn.dgr{background:var(--m-red);color:#fff;}
.rx-maint-btn.dgr:hover{background:#b91c1c;color:#fff;}
.rx-maint-row-acts{display:flex;gap:6px;justify-content:flex-end;}
.rx-maint-icobtn{width:32px;height:32px;border-radius:7px;border:1px solid var(--m-border);background:#fff;display:inline-flex;align-items:center;justify-content:center;color:var(--m-muted);}
.rx-maint-icobtn.safe:hover{background:var(--m-green-light);color:var(--m-green);border-color:#bbf7d0;}
.rx-maint-safebar{display:flex;gap:18px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px dashed var(--m-border);}
.rx-maint-safe{display:flex;gap:9px;align-items:flex-start;font-size:11.5px;color:var(--m-muted);line-height:1.5;max-width:48ch;}
.rx-maint-safe svg{flex:none;margin-top:1px;color:var(--m-green);}
.rx-maint-audit{margin-top:18px;}
.rx-maint-empty{font-size:12.5px;color:var(--m-muted);font-style:italic;}
.rx-maint-audit-row{display:flex;gap:11px;align-items:flex-start;padding:9px 0;border-bottom:1px solid var(--m-border);font-size:12.5px;}
.rx-maint-audit-row:last-child{border-bottom:none;}
.rx-maint-audit-ic{width:26px;height:26px;border-radius:7px;background:var(--m-bg);display:flex;align-items:center;justify-content:center;color:var(--m-muted);flex:none;}
.rx-maint-audit-ic.add{background:var(--m-green-light);color:var(--m-green);}
.rx-maint-audit-ic.rem{background:var(--m-red-light);color:var(--m-red);}
.rx-maint-audit-ic.rec{background:var(--m-indigo-light);color:var(--m-indigo);}
.rx-maint-audit-txt{flex:1;min-width:0;color:var(--m-text-mid);line-height:1.5;overflow-wrap:anywhere;}
.rx-maint-audit-meta{font-size:11px;color:var(--m-muted);margin-top:2px;font-family:var(--m-mono);}
.rx-maint-future{border:1px dashed var(--m-border-mid);border-radius:var(--m-radius-lg);padding:22px;text-align:center;color:var(--m-muted);font-size:12.5px;background:#fafbfc;margin-top:20px;}
.rx-maint-future svg{display:block;margin:0 auto 8px;opacity:.5;}
.rx-maint-toast{position:fixed;right:24px;bottom:24px;z-index:99999;display:none;max-width:360px;padding:12px 16px;border-radius:var(--m-radius);font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.18);gap:10px;align-items:flex-start;}
.rx-maint-toast.ok{display:flex;background:#14532d;color:#fff;}
.rx-maint-toast.err{display:flex;background:#7f1d1d;color:#fff;}
.rx-maint-toast-t{flex:1;user-select:text;}
.rx-maint-toast-x{border:0;background:transparent;color:#fff;font-size:18px;line-height:1;padding:0 2px;}
.rx-maint-rientro{margin:14px 0;padding:14px 16px;border:2px solid #B42318;background:#FEF3F2;border-radius:10px;color:#7A271A;font-size:14px;line-height:1.6;}
.rx-maint-rientro .t{font-weight:700;margin-bottom:6px;}
.rx-maint-rientro .r{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px;}
.rx-maint-rientro a{flex:1 1 260px;min-width:0;word-break:break-all;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;color:#7A271A;}
@media (max-width:820px){
 .rx-maint-utable thead{display:none;}
 .rx-maint-utable tbody td{display:block;border-bottom:none;padding:6px 0;}
 .rx-maint-utable tbody tr{display:block;border:1px solid var(--m-border);border-radius:var(--m-radius);padding:14px;margin-bottom:12px;}
 .rx-maint-row-acts{justify-content:flex-start;margin-top:6px;}
 .rx-maint-roles-cell::before{content:"Ruoli";display:block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--m-muted);margin-bottom:7px;}
 .rx-maint-rm-pop:not([hidden]){left:0;right:auto;white-space:normal;flex-wrap:wrap;max-width:260px;}
}
@media (max-width:640px){.rx-maint-rf-grid{grid-template-columns:minmax(0,1fr);}.rx-maint-rec{flex-wrap:wrap;}.rx-maint-rec .rx-maint-btn{width:100%;justify-content:center;}.rx-maint-tb{padding:14px;}.rx-maint-ph-r{align-items:flex-start;}.rx-maint-toast{left:16px;right:16px;max-width:none;}}
</style>
		<?php
	}

	/* ================================================================= *
	 *  Script in linea (copiato da RelDox, conferme in pagina)
	 * ================================================================= */

	private static function print_script( string $ajax_url, string $nonce ): void {
		?>
<script>
(function(){
 var AJAX=<?php echo wp_json_encode( $ajax_url ); ?>, NONCE=<?php echo wp_json_encode( $nonce ); ?>;
 // R4 — il rientro chiama l azione del PRODOTTO, con il nonce del prodotto (NONCE = Rdacademy_Accesso_Emergenza::NONCE).
 var RIENTRO=<?php echo wp_json_encode( self::AJAX_RIENTRO ); ?>;
 var root=document.querySelector('.rx-maint'); if(!root) return;
 var toast=document.getElementById('rx-maint-toast'), tT=null;
 var toastT=toast?toast.querySelector('.rx-maint-toast-t'):null, toastX=toast?toast.querySelector('.rx-maint-toast-x'):null;
 // REGOLE § 4: gli esiti spariscono, gli errori restano finche li chiudi (in RelDox sparivano dopo 3,2 s).
 function showToast(msg,ok){ if(!toast)return; toastT.textContent=msg; toast.className='rx-maint-toast '+(ok?'ok':'err'); toast.setAttribute('role', ok?'status':'alert'); toastX.hidden=!!ok; clearTimeout(tT); if(ok){ tT=setTimeout(function(){toast.className='rx-maint-toast';},3200); } }
 if(toastX){ toastX.addEventListener('click',function(){ toast.className='rx-maint-toast'; }); }
 function post(action,data){
  var body=new URLSearchParams(); body.set('action',action); body.set('nonce',NONCE);
  Object.keys(data||{}).forEach(function(k){body.set(k,data[k]);});
  return fetch(AJAX,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
   .then(function(r){return r.json();});
 }
 function handle(p,okMsg){
  p.then(function(res){
   if(res&&res.success){ showToast((res.data&&res.data.message)||okMsg,true); setTimeout(function(){location.reload();},700); }
   else{ showToast((res&&res.data)?String(res.data):'Operazione non riuscita.',false); }
  }).catch(function(){ showToast('Errore di rete. Riprova.',false); });
 }
 // BUG-78 di RelDox — L AVVISO DEL RIENTRO NON PUO ESSERE UN TOAST: un messaggio che si
 // ricarica dopo 700 ms sparirebbe prima di essere letto. Il rientro ha un pannello che RESTA.
 function avvisoRientro(d){
  d=d||{};
  var vecchio=document.getElementById('rx-maint-rientro'); if(vecchio) vecchio.remove();
  var box=document.createElement('div'); box.id='rx-maint-rientro'; box.className='rx-maint-rientro';
  box.setAttribute('role','alert');
  var t=document.createElement('div'); t.className='t';
  t.textContent='Accesso ripristinato — collegamento monouso per la password nuova';
  var c=document.createElement('div'); c.textContent=d.avviso||'Accesso ripristinato.';
  box.appendChild(t); box.appendChild(c);
  if(d.link){
   var r=document.createElement('div'); r.className='r';
   var a=document.createElement('a'); a.id='rx-maint-rientro-link'; a.href=d.link; a.target='_blank'; a.rel='noopener'; a.textContent=d.link;
   var b=document.createElement('button'); b.type='button'; b.className='rx-maint-btn s sm'; b.textContent='Copia';
   b.addEventListener('click',function(){ var ok=function(){ b.textContent='Copiato'; };
    if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(d.link).then(ok,function(){ b.textContent='Copia non riuscita'; }); }
    else{ var x=document.createElement('textarea'); x.value=d.link; document.body.appendChild(x); x.select(); try{ document.execCommand('copy'); ok(); }catch(e){} x.remove(); } });
   r.appendChild(a); r.appendChild(b); box.appendChild(r);
  }
  var dove=document.getElementById('rx-maint-rec-form');
  if(dove&&dove.parentNode){ dove.parentNode.insertBefore(box,dove); } else { root.insertBefore(box,root.firstChild); }
  box.scrollIntoView({behavior:'smooth',block:'center'});
 }
 function handleRientro(p,btn){
  if(btn){ btn.disabled=true; }
  p.then(function(res){
   if(btn){ btn.disabled=false; }
   if(res&&res.success){ if(recForm){ recForm.hidden=true; } if(recToggle){ recToggle.setAttribute('aria-expanded','false'); } avvisoRientro(res.data||{}); }
   else{ showToast((res&&res.data)?String(res.data):'Operazione non riuscita.',false); }
  }).catch(function(){ if(btn){ btn.disabled=false; } showToast('Errore di rete. Riprova.',false); });
 }
 // Ripristino guidato: apri / chiudi / conferma
 var recForm=document.getElementById('rx-maint-rec-form');
 var recToggle=document.getElementById('rx-maint-rec-toggle');
 function apriRipristino(uid,rid){
  if(!recForm) return;
  recForm.hidden=false; if(recToggle){ recToggle.setAttribute('aria-expanded','true'); }
  var su=document.getElementById('rx-maint-rec-user'), sr=document.getElementById('rx-maint-rec-role');
  if(uid&&su){ su.value=String(uid); } if(rid&&sr){ sr.value=String(rid); }
  recForm.scrollIntoView({behavior:'smooth',block:'center'});
  if(su){ su.focus(); }
 }
 if(recToggle&&recForm){ recToggle.addEventListener('click',function(){ if(recForm.hidden){ apriRipristino(); } else { recForm.hidden=true; recToggle.setAttribute('aria-expanded','false'); } }); }
 var recCancel=document.getElementById('rx-maint-rec-cancel');
 if(recCancel&&recForm){ recCancel.addEventListener('click',function(){ recForm.hidden=true; if(recToggle){ recToggle.setAttribute('aria-expanded','false'); } }); }
 var recConfirm=document.getElementById('rx-maint-rec-confirm');
 if(recConfirm){ recConfirm.addEventListener('click',function(){
   var uid=document.getElementById('rx-maint-rec-user').value, rid=document.getElementById('rx-maint-rec-role').value;
   handleRientro(post(RIENTRO,{user_id:uid,role_id:rid}),recConfirm);
 }); }
 // Clic delegati
 root.addEventListener('click',function(e){
  // BUG-38 di RelDox — RP-UNA-CONFERMA-VIVE-NELLA-PAGINA. Nessun dialogo del browser.
  var rkc=e.target.closest('.rx-maint-remove-cancel');
  if(rkc){ var pc=rkc.closest('.rx-maint-rm-pop'); if(pc){ pc.hidden=true; var bc=pc.parentNode.querySelector('.rx-maint-remove'); if(bc) bc.setAttribute('aria-expanded','false'); } return; }
  var rko=e.target.closest('.rx-maint-remove-confirm');
  if(rko){ var pk=rko.closest('.rx-maint-rm-pop'); if(pk) pk.hidden=true;
   handle(post('rdacademy_maint_remove_role',{user_id:rko.getAttribute('data-uid'),role_id:rko.getAttribute('data-rid')}),'Ruolo rimosso.');
   return; }
  var rm=e.target.closest('.rx-maint-remove');
  if(rm){ var pop=rm.parentNode.querySelector('.rx-maint-rm-pop');
   Array.prototype.forEach.call(root.querySelectorAll('.rx-maint-rm-pop'),function(p){ if(p!==pop){ p.hidden=true; var ob=p.parentNode.querySelector('.rx-maint-remove'); if(ob) ob.setAttribute('aria-expanded','false'); } });
   if(pop){ pop.hidden=!pop.hidden; rm.setAttribute('aria-expanded', pop.hidden?'false':'true'); }
   return; }
  // ⛔ In RelDox lo scudo chiedeva window.confirm(): qui apre il ripristino guidato,
  // che e la conferma in pagina con utente e ruolo gia scelti.
  var sh=e.target.closest('.rx-maint-shield');
  if(sh){ apriRipristino(sh.getAttribute('data-uid'),sh.getAttribute('data-rid')); return; }
  var at=e.target.closest('.rx-maint-add-toggle');
  if(at){ var ap=at.parentNode.querySelector('.rx-maint-add-pop'); if(ap){ ap.hidden=!ap.hidden; } return; }
  var ac=e.target.closest('.rx-maint-add-confirm');
  if(ac){ var sel=ac.parentNode.querySelector('.rx-maint-add-sel'); if(sel){
    handle(post('rdacademy_maint_add_role',{user_id:ac.getAttribute('data-uid'),role_id:sel.value}),'Ruolo aggiunto.'); }
   return; }
 });
})();
</script>
		<?php
	}
}

endif;
