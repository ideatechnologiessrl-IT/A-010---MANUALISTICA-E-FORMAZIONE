<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-license-admin.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Rdacademy_License_Admin — Licenza d uso, contesto BACKEND WordPress.
 *
 * Copiato da Rdacademy_License_Admin, RelDox CRD 3.55.09G.
 *
 * Decisione owner di RelDox (revisione del modello di consenso): l atto di
 * accettazione della licenza e un atto di SETUP compiuto UNA VOLTA
 * dall amministratore WP (titolare o suo delegato), che dichiara di
 * rappresentare la volonta del legale rappresentante. Qui si configurano anche
 * i dati del Licenziatario (intestatario). Contesto admin WP: le funzioni utente
 * WP sono lecite.
 *
 * ZERO ALTER: l intestatario vive come righe additive in rdacademy_impostazioni
 * (chiavi license_holder_*), la registrazione probatoria riusa Rdacademy_License
 * (opzioni del prodotto sul BASE).
 *
 * NUOVO (resa) rispetto a RelDox:
 *  - il riquadro «Ancora di dominio»: in RelDox Rdacademy_License_Anchor::state()
 *    non aveva chiamanti e lo stato «da rinnovare» non compariva da nessuna parte;
 *  - lo «Storico delle accettazioni»: in RelDox il log esisteva ma nessuna pagina
 *    lo mostrava;
 *  - l impronta del testo e la riga «quando cambia il testo serve un nuovo
 *    consenso» sopra il contratto.
 * CORRETTO: gli avvisi di esito/errore portano il pulsante di chiusura e il
 * testo selezionabile (REGOLE § 4: un errore resta finche lo chiudi).
 *
 * 1.3.0B — EULA v2 (owner 11/10 18:39-18:51, PROPOSTA-EULA-v2; «genera» 12/10 08:15):
 *  - il testo in 15 articoli, senza le etichette «clausola vessatoria»; il FORO COMPETENTE nel riquadro dell intestatario
 *    (chiave `license_foro`, vuoto = «[da indicare]» e avviso arancio);
 *  - «Firma e trasmissione»: «Scarica il contratto in PDF» (Rdacademy_License_Pdf), «Allega il contratto firmato» (firma digitale o
 *    autografa scansionata, ≤ 10 MB, cartella protetta, impronta sha256), «Scarica il file allegato», «✉ Trasmetti a Idea
 *    Technologies» (casella di servizio → amministrazione@ideatechnologies.com, esito passo per passo con «Copia esito»; spento col
 *    motivo scritto se manca il file o la casella non è verificata, R-68);
 *  - l accettazione nel pannello resta (prima prova; l approvazione specifica vale con la firma del PDF, art. 15);
 *  - lo storico: accettazioni + eventi del contratto (PDF scaricati, allegati, trasmissioni), tutto append-only;
 *  - lo stile delle Impostazioni 1.1.0B: riquadri bianchi col titolo a 16 px, testi a 15, campi e comandi alti 40-44.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_License_Admin {

	const PAGE_SLUG   = 'rdacademy-licenza';
	const NONCE_HOLD  = 'rdacademy_license_holder';
	const NONCE_ACC   = 'rdacademy_license_accept';
	const ACT_HOLD    = 'rdacademy_license_save_holder';
	const ACT_ACC     = 'rdacademy_license_accept';
	// 1.3.0B — PDF, allegato, file allegato, trasmissione
	const NONCE_PDF   = 'rdacademy_license_pdf';
	const ACT_PDF     = 'rdacademy_license_pdf';
	const NONCE_ALL   = 'rdacademy_license_allega';
	const ACT_ALL     = 'rdacademy_license_allega';
	const NONCE_FILE  = 'rdacademy_license_file';
	const ACT_FILE    = 'rdacademy_license_file';
	const NONCE_TX    = 'rdacademy_license_trasmetti';
	const ACT_TX      = 'rdacademy_license_trasmetti';
	// 1.3.1B — «Rimuovi l allegato» (SPEC-FIRMA-LICENZA-RELDOX § 5)
	const NONCE_RIM   = 'rdacademy_license_rimuovi';
	const ACT_RIM     = 'rdacademy_license_rimuovi';
	// 3.74.04G (S708) voce 150 S708_V150_PAGINA — «Applica la chiave» (D-150.4: la incolla un amministratore WordPress)
	const NONCE_CHIAVE = 'rdacademy_licenza_chiave';
	const ACT_CHIAVE   = 'rdacademy_licenza_chiave';
	// 3.60.03G BUG-269 — «Verifica il documento»
	const NONCE_VER   = 'rdacademy_license_verifica';
	const ACT_VER     = 'rdacademy_license_verifica';
	// 3.60.03G BUG-268 — il segnale «PDF partito» per la pagina (cookie di sessione, 2 minuti, nessun dato: solo il gettone del clic)
	const COOKIE_PDF  = 'rdlic_pdf';
	const OPT_ESITO   = 'rdacademy_license_esito_';
	// 3.60.07G BUG-269.C S618_B269C — gli esiti che si mostrano DENTRO «Firma e trasmissione», accanto ai comandi (non in testa alla pagina)
	const MSG_FIRMA   = [ 'pdf_ok', 'pdf_err', 'all_ok', 'all_err', 'ver_ok', 'ver_err', 'rim_ok', 'tx_ok', 'tx_err' ];   // + id utente: l esito della trasmissione per la pagina dopo il rimando (sul BASE)

	/** Chiavi additive dell intestatario in rdacademy_impostazioni: una sola lista, quella di Rdacademy_License. */
	const HOLDER_KEYS = Rdacademy_License::HOLDER_MAP;

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_submenu' ], 11 );
		add_action( 'admin_post_' . self::ACT_HOLD, [ $this, 'handle_save_holder' ] );
		add_action( 'admin_post_' . self::ACT_ACC,  [ $this, 'handle_accept' ] );
		add_action( 'admin_post_' . self::ACT_PDF,  [ $this, 'handle_pdf' ] );
		add_action( 'admin_post_' . self::ACT_ALL,  [ $this, 'handle_allega' ] );
		add_action( 'admin_post_' . self::ACT_FILE, [ $this, 'handle_file' ] );
		add_action( 'admin_post_' . self::ACT_TX,   [ $this, 'handle_trasmetti' ] );
		add_action( 'admin_post_' . self::ACT_RIM,  [ $this, 'handle_rimuovi' ] );
		add_action( 'admin_post_' . self::ACT_CHIAVE, [ $this, 'handle_chiave' ] );   // 3.74.04G (S708) S708_V150_PAGINA
		// 3.60.07G S618B_VIA_HOOK — «Verifica il documento» tolto (owner 2026-10-14 06:59): nessun aggancio
	}

	private static function utente(): array {
		$u = wp_get_current_user();
		return [ 'id' => (int) get_current_user_id(), 'nome' => $u ? (string) $u->display_name : '' ];
	}

	/* ================================================================= *
	 *  1.3.0B — PDF · ALLEGATO · FILE · TRASMISSIONE
	 * ================================================================= */

	public function handle_pdf(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_PDF );
		// 1.3.1B — «Genera e scarica il contratto» (§ 4): il server rifiuta da sé con l intestazione incompleta, senza il logo di
		// Idea o dopo la trasmissione (stesso controllo della pagina, Rdacademy_License::genera_ferma); nessun file in quei casi.
		$p = Rdacademy_License::genera( self::utente() );
		if ( empty( $p['ok'] ) ) self::torna( [ 'rlic_msg' => 'pdf_err', 'rlic_err' => rawurlencode( (string) $p['error'] ) ] );
		nocache_headers();
		// 3.60.03G BUG-268 S616_B268 — lo scaricamento lascia il browser sulla pagina vecchia (produzione 3.60.02G, 11:56: «quando si scarica il pdf
		// non si attiva il carico»). Col PDF parte un cookie col gettone del clic: la pagina lo vede e si rilegge da sola, già nello stato «generato».
		$tok = isset( $_GET['rlic_t'] ) ? substr( sanitize_key( (string) $_GET['rlic_t'] ), 0, 32 ) : '';
		if ( $tok !== '' && ! headers_sent() ) setcookie( self::COOKIE_PDF, $tok, [ 'expires' => time() + 120, 'path' => '/', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ] );
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $p['nome'] . '"' );
		header( 'Content-Length: ' . strlen( $p['byte'] ) );
		echo $p['byte']; // phpcs:ignore -- PDF binario
		exit;
	}

	/** 3.60.07G BUG-269.C S618_B269C — il limite di caricamento che vale davvero (byte): il minore fra il server e i 10 MB del contratto. */
	private static function limite_byte(): int {
		$srv = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
		return $srv > 0 ? min( $srv, (int) Rdacademy_License::ALLEGATO_MAX ) : (int) Rdacademy_License::ALLEGATO_MAX;
	}

	private static function limite_testo(): string {
		return function_exists( 'size_format' ) ? (string) size_format( self::limite_byte() ) : round( self::limite_byte() / 1048576, 1 ) . ' MB';
	}

	private static function utente_ev(): array {
		$u = self::utente();
		return [ 'user_id' => $u['id'], 'user_name' => $u['nome'] ];
	}

	public function handle_allega(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_ALL );
		$f = $_FILES['contratto'] ?? null;
		// 3.60.07G BUG-269.C S618_B269C — la modalità scelta torna con l esito: dopo un rifiuto la pagina la ripropone (non riparte da «digitale»)
		$firma = isset( $_POST['modalita'] ) ? sanitize_key( (string) $_POST['modalita'] ) : '';
		$mod = isset( Rdacademy_License::FIRME[ $firma ] ) ? [ 'rlic_mod' => $firma ] : [];
		$ferma = Rdacademy_License::allega_ferma();   // 1.3.1B — anche il server rifiuta (trasmesso, già allegato, nessun contratto generato)
		if ( $ferma !== '' ) self::torna( [ 'rlic_msg' => 'all_err', 'rlic_err' => rawurlencode( $ferma ) ] + $mod );
		if ( ! $f || (int) ( $f['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK || empty( $f['tmp_name'] ) || ! is_uploaded_file( (string) $f['tmp_name'] ) ) {
			$e = (int) ( $f['error'] ?? UPLOAD_ERR_NO_FILE );
			$m = [ UPLOAD_ERR_INI_SIZE => 'Il file supera il limite di caricamento del server (' . self::limite_testo() . ').', UPLOAD_ERR_FORM_SIZE => 'Il file supera il limite di caricamento del server (' . self::limite_testo() . ').',
				UPLOAD_ERR_PARTIAL => 'Il file è arrivato solo in parte (connessione interrotta): allegalo di nuovo.', UPLOAD_ERR_NO_FILE => 'Nessun file ricevuto: scegli il file del contratto firmato.',
				UPLOAD_ERR_NO_TMP_DIR => 'Il server non ha la cartella temporanea per i file caricati (configurazione PHP del server).', UPLOAD_ERR_CANT_WRITE => 'Il server non riesce a scrivere il file caricato (spazio o permessi della cartella temporanea).',
				UPLOAD_ERR_EXTENSION => 'Un estensione PHP del server ha fermato il caricamento.' ][ $e ] ?? ( 'Caricamento non riuscito (codice ' . $e . ').' );
			if ( $e === UPLOAD_ERR_OK ) $m = 'Il file ricevuto non risulta caricato da questa pagina: allegalo di nuovo.';
			self::torna( [ 'rlic_msg' => 'all_err', 'rlic_err' => rawurlencode( $m ) ] + $mod );
		}
		$r = Rdacademy_License::allega( (string) $f['tmp_name'], (string) wp_unslash( $f['name'] ?? '' ), $firma, self::utente() );
		if ( empty( $r['ok'] ) ) self::torna( [ 'rlic_msg' => 'all_err', 'rlic_err' => rawurlencode( (string) $r['error'] ) ] + $mod );
		self::torna( [ 'rlic_msg' => 'all_ok' ] );
	}

	public function handle_file(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_FILE );
		$f = Rdacademy_License::get_firmato(); $p = Rdacademy_License::file_firmato( $f );
		if ( $p === '' ) self::torna( [ 'rlic_msg' => 'all_err', 'rlic_err' => rawurlencode( 'Il file allegato non c\'è più o è cambiato: allegalo di nuovo.' ) ] );
		nocache_headers();
		header( 'Content-Type: ' . ( Rdacademy_License::ALLEGATO_TIPI[ (string) ( $f['ext'] ?? 'pdf' ) ] ?? 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . ( (string) $f['nome'] !== '' ? $f['nome'] : $f['file'] ) . '"' );
		header( 'Content-Length: ' . filesize( $p ) );
		readfile( $p );
		exit;
	}

	public function handle_trasmetti(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_TX );
		@set_time_limit( 120 );
		$u = self::utente();
		$r = Rdacademy_License::trasmetti( $u );
		Rdacademy_Option::set( self::OPT_ESITO . $u['id'], $r, false );
		self::torna( [ 'rlic_msg' => $r['ok'] ? 'tx_ok' : 'tx_err' ] );
	}

	/** 3.60.03G BUG-269 S616_B269 — «Verifica il documento»: passa → «Trasmetti» acceso; non passa → allegato tolto, si allega di nuovo. */
	public function handle_verifica(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_VER );
		$r = Rdacademy_License::verifica( self::utente() );
		if ( empty( $r['ok'] ) ) self::torna( [ 'rlic_msg' => 'ver_err', 'rlic_err' => rawurlencode( (string) $r['error'] ) ] );
		self::torna( [ 'rlic_msg' => 'ver_ok' ] );
	}

	/** 3.74.04G (S708) voce 150 S708_V150_PAGINA — «Applica la chiave»: il motore la verifica (firma, prodotto, sito, data) e dice perché la rifiuta. */
	public function handle_chiave(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_CHIAVE );
		$k = isset( $_POST['rlic_chiave'] ) ? (string) wp_unslash( $_POST['rlic_chiave'] ) : '';   // la verifica è il motore: firma Ed25519, nessun testo libero salvato senza firma
		$r = class_exists( 'Rdacademy_Licenza_Stato' ) ? Rdacademy_Licenza_Stato::salva_chiave( $k ) : array( 'ok' => false, 'messaggio' => 'Motore della licenza assente.' );
		if ( trim( $k ) === '' ) $r = array( 'ok' => false, 'messaggio' => 'Incolla la chiave ricevuta da Idea Technologies.' );
		self::torna( [ 'rlic_msg' => ! empty( $r['ok'] ) ? 'chiave_ok' : 'chiave_err', 'rlic_err' => rawurlencode( (string) $r['messaggio'] ) ] );
	}

	/** 3.74.04G (S708) voce 150 S708_V150_PAGINA — la sezione «Chiave di licenza» (MOCKUP-VOCE-150-SOLA-CONSULTAZIONE-v1, vista 3). */
	public static function sezione_chiave( string $msg, string $err ): string {
		if ( ! class_exists( 'Rdacademy_Licenza_Stato' ) ) return '';
		$L = 'Rdacademy_Licenza_Stato';
		$st = $L::stato(); $ci = $L::chiave_installata(); $fine = (string) $ci['fine'];
		$pill = array( 'senza_chiave' => array( 'warn', 'Manca la chiave' ), 'attiva' => array( 'ok', 'Attiva' ),
			'preavviso' => array( 'warn', 'Scade fra ' . max( 0, $L::giorni_alla_fine() ) . ' giorni' ), 'cessata' => array( 'err', 'Sola consultazione' ) )[ $st ];
		$h = '<div class="rlicadm-card" id="rlic-chiave"><h2>Chiave di licenza <span class="rlicadm-pill rlicadm-pill-' . esc_attr( $pill[0] ) . '">' . esc_html( $pill[1] ) . '</span></h2>'
			. '<p class="rlicadm-hint" style="margin:0 0 12px">La rilascia Idea Technologies a ogni canone; la incolla un amministratore WordPress.</p>';
		if ( $msg === 'chiave_ok' ) $h .= '<div class="rlicadm-notice rlicadm-ok" role="status"><span class="rlicadm-notice-t">' . esc_html( $err ) . '</span></div>';
		if ( $msg === 'chiave_err' ) $h .= '<div class="rlicadm-notice rlicadm-err" role="alert"><span class="rlicadm-notice-t"><b>Chiave non applicata.</b> ' . esc_html( $err ) . '</span></div>';
		$h .= '<dl class="rlicadm-kv"><dt>Fine del canone</dt><dd><strong>' . esc_html( $fine !== '' ? $L::data_a_video( $fine ) : '—' ) . '</strong></dd>'
			. '<dt>Stato</dt><dd>' . esc_html( $pill[1] ) . '</dd>'
			. '<dt>Tipo di licenza</dt><dd>' . esc_html( $ci['tipo'] !== '' ? $ci['tipo'] : '—' ) . '</dd>'
			. '<dt>Chiave applicata</dt><dd>' . esc_html( $ci['valida'] ? ( ( $ci['emessa'] !== '' ? $L::data_a_video( $ci['emessa'] ) . ' · ' : '' ) . 'firmata da Idea Technologies' . ( $ci['numero'] !== '' ? ' · n. ' . $ci['numero'] : '' ) ) : 'nessuna' ) . '</dd></dl>'
			. '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">' . wp_nonce_field( self::NONCE_CHIAVE, '_wpnonce', true, false )
			. '<input type="hidden" name="action" value="' . esc_attr( self::ACT_CHIAVE ) . '">'
			. '<div class="rlicadm-field"><label for="rlic-chiave-nuova">Chiave nuova</label><textarea id="rlic-chiave-nuova" name="rlic_chiave" rows="4" spellcheck="false" autocomplete="off" placeholder="Incolla qui la chiave ricevuta da Idea Technologies" style="width:100%;box-sizing:border-box;font:14px/1.5 ui-monospace,monospace;padding:10px;border:1px solid #D1D5DB;border-radius:8px"></textarea>'
			. '<p class="rlicadm-hint" style="margin:4px 0 0">La chiave porta la data di fine del canone e non si può modificare: se è alterata RelDoxAcademy la rifiuta e dice perché, qui sopra.</p></div>'
			. '<button type="submit" class="button button-primary" style="min-height:42px;font-size:15px">Applica la chiave</button></form></div>';
		return $h;
	}

	public function handle_rimuovi(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_RIM );
		$r = Rdacademy_License::rimuovi( self::utente() );
		if ( empty( $r['ok'] ) ) self::torna( [ 'rlic_msg' => 'all_err', 'rlic_err' => rawurlencode( (string) $r['error'] ) ] );
		self::torna( [ 'rlic_msg' => 'rim_ok' ] );
	}

	public function register_submenu(): void {
		add_submenu_page(
			'rdacademy',   // F023_LICENZA: il menu di RelDoxAcademy; S615: RelDoxAcademy non ha Rdacademy_Admin::SLUG, il menu del prodotto e reldox-crd (class-reldox-admin.php r.49)
			'Licenza d uso',
			'Licenza d uso',
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render' ]
		);
	}

	/* ================================================================= *
	 *  Gate (admin WP)
	 * ================================================================= */

	private static function gate_or_die(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				'<h2>Accesso negato</h2><p>Questa pagina richiede un amministratore WordPress (<code>manage_options</code>).</p>',
				esc_html( RDACADEMY_PRODUCT ) . ' — Licenza d uso',
				[ 'response' => 403 ]
			);
		}
	}

	/* ================================================================= *
	 *  Upsert intestatario su rdacademy_impostazioni (ZERO ALTER)
	 * ================================================================= */

	private static function save_holder_value( string $chiave, string $valore ): bool {
		$p   = RDACADEMY_TABLE_PREFIX;
		// `chiave` ha UNIQUE KEY: una riga sola per chiave, il LIMIT e superfluo.
		$row = Rdacademy_DB::get_row(
			"SELECT id FROM `{$p}impostazioni` WHERE chiave = ?",
			[ $chiave ],
			's'
		);
		if ( $row ) {
			Rdacademy_DB::clear_last_error();
			Rdacademy_DB::execute(
				"UPDATE `{$p}impostazioni` SET valore = ? WHERE chiave = ?",
				[ $valore, $chiave ],
				'ss'
			);
			// affected_rows e 0 anche quando il valore era gia quello: l errore si legge da last_error.
			return Rdacademy_DB::last_error() === '';
		}
		return Rdacademy_DB::insert(
			"INSERT INTO `{$p}impostazioni` (chiave, valore, tipo) VALUES (?, ?, 'text')",
			[ $chiave, $valore ],
			'ss'
		) > 0;
	}

	private static function torna( array $args, string $ancora = '' ): void {
		// 3.60.07G BUG-269.C S618_B269C — gli esiti di «Firma e trasmissione» tornano all esito dentro la sezione (#rlic-esito), non in testa alla pagina
		if ( $ancora === '' && in_array( (string) ( $args['rlic_msg'] ?? '' ), self::MSG_FIRMA, true ) ) $ancora = 'rlic-esito';
		wp_safe_redirect( add_query_arg(
			array_merge( [ 'page' => self::PAGE_SLUG ], $args ),
			admin_url( 'admin.php' )
		) . ( $ancora !== '' ? '#' . $ancora : '' ) );
		exit;
	}

	public function handle_save_holder(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_HOLD );
		// 1.3.1B (§ 6) — dopo la trasmissione intestazione e foro sono in lettura: erano nel documento firmato. Rifiuta il server.
		$mt = Rdacademy_License::motivo_trasmesso();
		if ( $mt !== '' ) self::torna( [ 'rlic_msg' => 'holder_err', 'rlic_err' => rawurlencode( 'Intestazione in lettura. ' . $mt ) ] );

		// 1) Raccolta grezza dei campi in ingresso.
		$in = [];
		foreach ( array_keys( self::HOLDER_KEYS ) as $field ) {
			$in[ $field ] = isset( $_POST[ $field ] )
				? sanitize_text_field( wp_unslash( (string) $_POST[ $field ] ) )
				: '';
		}

		// 2) Normalizzazione tipo (default azienda).
		$tipo = ( 'persona_fisica' === $in['tipo'] ) ? 'persona_fisica' : 'azienda';
		$in['tipo'] = $tipo;

		// 3) Validazione di formato (blocco duro, campo vuoto ammesso).
		$errors = [];
		$V = 'Rdacademy_License_Validate';
		if ( 'azienda' === $tipo ) {
			$r = $V::piva( $in['piva'] );
			if ( ! $r['ok'] ) { $errors[] = $r['error']; }
		} else {
			$r = $V::codice_fiscale( $in['cf'] );
			if ( ! $r['ok'] ) { $errors[] = $r['error']; }
		}
		// 1.3.1B — email o PEC (§ 3): vuota ammessa nel salvataggio, ma se c è dev essere un indirizzo
		if ( $in['email'] !== '' && ! is_email( $in['email'] ) ) { $errors[] = 'L\'email o PEC non è un indirizzo valido.'; }
		if ( $V::is_italia( $in['nazione'] ) ) {
			$r = $V::cap( $in['cap'] );
			if ( ! $r['ok'] ) { $errors[] = $r['error']; }
			$r = $V::provincia( $in['provincia'] );
			if ( ! $r['ok'] ) { $errors[] = $r['error']; }
		}

		// 1.3.0B — il foro competente (art. 14): vuoto ammesso, altrimenti il nome della città.
		$foro = isset( $_POST['foro'] ) ? trim( sanitize_text_field( wp_unslash( (string) $_POST['foro'] ) ) ) : Rdacademy_License::foro_salvato();   // 3.75.06 BUG-322: si salva com era
		$mf = Rdacademy_License::foro_valido( $foro );
		if ( $mf !== '' ) { $errors[] = $mf; }

		if ( ! empty( $errors ) ) {
			// Nessuna scrittura: il dato probatorio non deve essere malformato.
			self::torna( [ 'rlic_msg' => 'holder_err', 'rlic_err' => rawurlencode( implode( ' ', $errors ) ) ] );
		}

		// 4) Coerenza per tipo: azzera i campi del tipo opposto (no dati stale).
		if ( 'azienda' === $tipo ) {
			$in['nome'] = '';
			$in['cognome'] = '';
			$in['cf'] = '';
		} else {
			$in['denominazione'] = '';
			$in['piva'] = '';
			$in['legale_rappr'] = '';
		}
		// Normalizza in maiuscolo sigla provincia e codice fiscale; la P.IVA senza separatori.
		if ( '' !== $in['provincia'] ) { $in['provincia'] = strtoupper( $in['provincia'] ); }
		if ( '' !== $in['cf'] )        { $in['cf'] = strtoupper( preg_replace( '/\s/', '', $in['cf'] ) ); }

		// 5) Upsert additivo (ZERO ALTER). ⛔ In RelDox l esito delle scritture
		//    non si leggeva e la pagina diceva «salvati» comunque.
		$falliti = [];
		foreach ( self::HOLDER_KEYS as $field => $chiave ) {
			if ( ! self::save_holder_value( $chiave, $in[ $field ] ) ) {
				$falliti[] = $field;
			}
		}
		$foro_prima = Rdacademy_License::foro_salvato();
		if ( ! self::save_holder_value( Rdacademy_License::K_FORO, $foro ) ) { $falliti[] = 'foro'; }
		elseif ( $foro_prima !== $foro && class_exists( 'Rdacademy_Auth' ) ) {
			Rdacademy_Auth::log_activity( 0, 'licenza', 'foro', 'Licenza d uso (utente WP ' . self::utente()['nome'] . '): foro competente «' . ( $foro_prima !== '' ? $foro_prima : Rdacademy_License::FORO_VUOTO ) . '» → «' . ( $foro !== '' ? $foro : Rdacademy_License::FORO_VUOTO ) . '»' );
		}
		if ( $falliti ) {
			self::torna( [ 'rlic_msg' => 'holder_err', 'rlic_err' => rawurlencode( 'Scrittura non riuscita sul database per: ' . implode( ', ', $falliti ) . '. ' . Rdacademy_DB::last_error() ) ] );
		}

		self::torna( [ 'rlic_msg' => 'holder_ok' ] );
	}

	/* ================================================================= *
	 *  Accettazione (doppia sottoscrizione 1341-1342, atto di setup)
	 * ================================================================= */

	public function handle_accept(): void {
		self::gate_or_die();
		check_admin_referer( self::NONCE_ACC );

		$signer = [
			'nome'           => isset( $_POST['signer_nome'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['signer_nome'] ) ) : '',
			'denominazione'  => isset( $_POST['signer_denom'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['signer_denom'] ) ) : '',
			'rappresentanza' => ! empty( $_POST['acc_rappr'] ),
			'accept_general' => ! empty( $_POST['acc_general'] ),
			'accept_vex'     => ! empty( $_POST['acc_vex'] ),
		];

		$wp_user = wp_get_current_user();
		$user = [
			'id'   => (int) get_current_user_id(),
			'nome' => $wp_user ? (string) $wp_user->display_name : '',
		];

		$res = Rdacademy_License::record_acceptance( $signer, $user );
		$msg = ( isset( $res['ok'] ) && $res['ok'] ) ? 'accept_ok' : 'accept_err';

		$args = [ 'rlic_msg' => $msg ];
		if ( $msg === 'accept_err' && ! empty( $res['error'] ) ) {
			$args['rlic_err'] = rawurlencode( (string) $res['error'] );
		}
		self::torna( $args );
	}

	/* ================================================================= *
	 *  Render pagina backend (grafica semplificata, no neuromarketing)
	 * ================================================================= */

	private static function avviso( string $cls, string $testo ): void {
		echo '<div class="rlicadm-notice ' . esc_attr( $cls ) . '" role="' . ( $cls === 'rlicadm-err' ? 'alert' : 'status' ) . '">'
			. '<span class="rlicadm-notice-t">' . esc_html( $testo ) . '</span>'
			. '<button type="button" class="rlicadm-x" aria-label="Chiudi">&times;</button></div>';
	}

	/** I passi di un esito: ✓ ✕ ! – con titolo, risposta e rimedio (come «Prova la spedizione»). */
	private static function passi( array $r ): void {
		$S = [ 'ok' => '✓', 'ko' => '✕', 'avviso' => '!', 'saltato' => '–' ];
		echo '<ol class="rlicadm-passi" id="rlic-tx-passi">';
		foreach ( (array) ( $r['passi'] ?? [] ) as $p ) {
			echo '<li class="' . esc_attr( (string) $p['esito'] ) . '"><span class="s">' . esc_html( $S[ $p['esito'] ] ?? '?' ) . '</span><span class="c"><b>' . esc_html( (string) $p['titolo'] ) . '</b>'
				. ( (string) $p['server'] !== '' ? '<code>' . esc_html( (string) $p['server'] ) . '</code>' : '' )
				. ( (string) $p['consiglio'] !== '' ? '<em>→ ' . esc_html( (string) $p['consiglio'] ) . '</em>' : '' ) . '</span></li>';
		}
		echo '</ol>';
	}

	private static function quando( string $utc ): string {
		$t = strtotime( str_replace( [ 'T', 'Z' ], [ ' ', '' ], $utc ) . ' UTC' );
		return $t ? wp_date( 'd/m/Y H:i', $t ) : $utc;
	}

	public function render(): void {
		self::gate_or_die();

		// Il controllo del dominio si fa qui, dove lo stato si legge (sola diagnostica, nessun blocco).
		Rdacademy_License_Anchor::touch_seen();
		// 1.3.1B (§ 6) — la riapertura col testo nuovo PRIMA di leggere eventi, accettazione e storico: la pagina mostra subito l evento.
		Rdacademy_License::riapri_se_testo_nuovo();

		$accepted = Rdacademy_License::is_accepted();
		$rec      = Rdacademy_License::get_acceptance();
		$log      = Rdacademy_License::get_log();
		$eventi   = Rdacademy_License::get_eventi();
		$holder   = Rdacademy_License::licenziatario();
		$giver    = Rdacademy_License::licenziante();
		$foro     = Rdacademy_License::foro();
		$articles = Rdacademy_License::articoli_resi( $foro );
		// 1.3.1B — lo stato del percorso (§ 2) si ricava dai record; la riapertura col testo nuovo (§ 6) avviene qui dentro
		$S        = Rdacademy_License::stato();
		$fase     = $S['fase'];
		$bloccato = $fase === 'trasmesso';
		$firmato  = $S['firmato'];
		$file_ok  = Rdacademy_License::file_firmato( $firmato ) !== '';
		$tx_ferma = Rdacademy_License::trasmetti_ferma( $S );
		$gen_ferma = Rdacademy_License::genera_ferma( $S );
		$all_ferma = Rdacademy_License::allega_ferma( $S );
		$storico_c = Rdacademy_License::get_storico_contratti();
		$nota_cod = Rdacademy_License_Pdf::testo( 'licenza.pdf_nota', [ 'codice' => Rdacademy_License_Pdf::codice_breve( Rdacademy_License::text_hash() ) ] )[1];
		$vv_firma = [ 'licenziante' => rtrim( RDACADEMY_VENDOR, '.' ) . '.', 'indirizzo' => Rdacademy_License::indirizzo_licenziante() !== '' ? Rdacademy_License::indirizzo_licenziante() : '[indirizzo da indicare]', 'prodotto' => RDACADEMY_PRODUCT ];
		$post_url = esc_url( admin_url( 'admin-post.php' ) );
		$anc_row  = Rdacademy_License_Anchor::record();
		// 3.59.09G bis (S615B) S615B_ANCORA — «il prossimo caricamento ritenta da solo»: nel kit lo faceva Rdacademy_Schema_License_V1 a ogni caricamento;
		// in RelDox CRD l ancora si seminava solo in Rdacademy_Activator::migrate_tables(). Qui la pagina ritenta (idempotente, non sovrascrive mai un ancora).
		if ( ! $anc_row ) { Rdacademy_License_Anchor::ensure_seeded(); $anc_row = Rdacademy_License_Anchor::record(); }
		$anc_st   = Rdacademy_License_Anchor::state();
		$superata = ( $rec && ! $accepted ); // c era un consenso, ma su un testo diverso
		$uid      = (int) get_current_user_id();

		$msg = isset( $_GET['rlic_msg'] ) ? sanitize_key( (string) $_GET['rlic_msg'] ) : '';
		$err = isset( $_GET['rlic_err'] ) ? sanitize_text_field( wp_unslash( rawurldecode( (string) $_GET['rlic_err'] ) ) ) : '';
		$esito_tx = null;
		if ( in_array( $msg, [ 'tx_ok', 'tx_err' ], true ) ) {
			$esito_tx = Rdacademy_Option::get( self::OPT_ESITO . $uid, null );
			Rdacademy_Option::del( self::OPT_ESITO . $uid );
		}
		$ev_nomi = [ 'pdf' => 'Contratto generato e scaricato', 'allegato' => 'Contratto firmato allegato', 'allegato_rifiutato' => 'Allegato rifiutato', 'allegato_rimosso' => 'Allegato rimosso',
			'allegato_decaduto' => 'Allegato decaduto (contratto generato di nuovo)', 'trasmesso' => 'Trasmesso a Idea Technologies', 'trasmissione_ko' => 'Trasmissione non riuscita', 'riaperto' => 'Riaperto: testo nuovo di Idea Technologies',
			'verificato' => 'Contratto firmato verificato', 'verifica_ko' => 'Verifica non superata: allegato tolto' ];
		?>
		<style>
		.rlicadm{max-width:1000px;font-size:15px;line-height:1.55;color:#111827;}
		.rlicadm h1{font-size:24px;font-weight:700;margin:14px 0 4px;}
		.rlicadm .rlicadm-sub{color:#4B5563;font-size:15px;margin-bottom:18px;}
		.rlicadm-kv{display:grid;grid-template-columns:max-content 1fr;gap:6px 16px;margin:0 0 14px;font-size:15px}.rlicadm-kv dt{color:#4B5563;font-weight:600}.rlicadm-kv dd{margin:0}
		.rlicadm-pill{font-size:13px;font-weight:700;border-radius:999px;padding:3px 10px}.rlicadm-pill-ok{background:#ECFDF5;color:#065F46}.rlicadm-pill-warn{background:#FFFBEB;color:#92400E}.rlicadm-pill-err{background:#FEF2F2;color:#991B1B}
		.rlicadm-card{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:0 20px 18px;margin:0 0 16px;}
		.rlicadm-card h2{display:flex;align-items:center;justify-content:space-between;gap:8px 12px;flex-wrap:wrap;font-size:16px;font-weight:700;color:#111827;margin:0 -20px 14px;padding:14px 20px 12px;border-bottom:1px solid #E5E7EB;}
		.rlicadm-notice{border-radius:10px;padding:12px 14px;margin:0 0 16px;font-size:15px;display:flex;gap:10px;align-items:flex-start;}
		.rlicadm-notice-t{flex:1;user-select:text;}
		.rlicadm-x{border:0;background:transparent;font-size:20px;line-height:1;cursor:pointer;color:inherit;padding:0 2px;min-width:32px;min-height:32px;}
		.rlicadm-ok{background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;}
		.rlicadm-err{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;}
		.rlicadm-warn{background:#FFFBEB;border:1px solid #FCD34D;color:#92400E;}
		.rlicadm-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px;}
		.rlicadm-field{display:flex;flex-direction:column;gap:4px;margin-bottom:14px;min-width:0;}
		.rlicadm-field label{font-size:13.5px;font-weight:600;color:#374151;}
		.rlicadm-field input[type=text],.rlicadm-field select,.rlicadm-field input[type=file]{padding:8px 10px;border:1px solid #D1D5DB;border-radius:8px;font-size:15px;min-height:42px;width:100%;max-width:100%;box-sizing:border-box;background:#fff;}
		.rlicadm-party{border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;background:#F9FAFB;min-width:0;}
		.rlicadm-party .role{font-size:13px;font-weight:700;color:#6B7280;margin-bottom:6px;}
		.rlicadm-party .val{font-size:15px;overflow-wrap:anywhere;}
		.rlicadm-miss{color:#B45309;font-style:italic;}
		.rlicadm-eula{max-height:420px;overflow-y:auto;border:1px solid #E5E7EB;border-radius:10px;padding:16px 18px;background:#FCFDFE;font-size:15px;}
		.rlicadm-art{margin:0 0 14px;}
		.rlicadm-art .t{font-weight:700;font-size:15.5px;}
		.rlicadm-art ul{margin:6px 0 0 20px;list-style:disc;}
		.rlicadm-sub2{background:#F4F7FB;border:1px solid #DBE4EE;border-radius:10px;padding:12px 14px;margin:12px 0;}
		.rlicadm-check{display:flex;gap:10px;align-items:flex-start;margin:10px 0;font-size:15px;}
		.rlicadm-check input{margin-top:4px;width:18px;height:18px;flex:none;}
		.rlicadm-btn{background:#1F4E79;color:#fff;border:none;border-radius:8px;padding:0 18px;min-height:44px;font-size:15px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
		.rlicadm-btn:hover{background:#163A5B;color:#fff;}
		.rlicadm-btn.sec{background:#fff;color:#1F4E79;border:1px solid #93A8BF;}
		.rlicadm-btn[disabled]{background:#C3CBD8;color:#fff;border-color:#C3CBD8;cursor:not-allowed;}
		.rlicadm-btn.is-busy{opacity:.75;cursor:progress;}
		.rlicadm-cmd{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:4px 0 6px;}
		.rlicadm-perche{font-size:14px;color:#B91C1C;margin:6px 0 0;}
		.rlicadm-recgrid{display:grid;grid-template-columns:1fr 1fr;gap:8px 18px;font-size:15px;margin-top:8px;}
		.rlicadm-recgrid span{min-width:0;overflow-wrap:anywhere;}
		.rlicadm-typesel{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 14px;}
		.rlicadm-typeopt{display:flex;align-items:center;gap:8px;border:1px solid #D1D5DB;border-radius:10px;padding:0 16px;min-height:44px;cursor:pointer;font-size:15px;font-weight:600;color:#374151;background:#fff;}
		.rlicadm-typeopt.is-on{border-color:#1F4E79;background:#EEF4FA;color:#1F4E79;}
		.rlicadm-typeopt input{margin:0;}
		.rlicadm-fieldset{border:1px solid #E5E7EB;border-radius:10px;padding:14px 16px 2px;margin:0 0 14px;background:#FBFCFE;min-width:0;}
		.rlicadm-fieldset > legend{font-size:14px;font-weight:700;color:#374151;padding:0 6px;}
		.rlicadm-hidden{display:none !important;}
		.rlicadm-hint{font-size:14px;color:#4B5563;margin:-6px 0 12px;}
		.rlicadm-pill{display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:700;border-radius:999px;padding:4px 12px;}
		.rlicadm-pill .d{width:8px;height:8px;border-radius:50%;}
		.rlicadm-pill.on{background:#DCFCE7;color:#15803D}.rlicadm-pill.on .d{background:#16A34A}
		.rlicadm-pill.warn{background:#FEF3C7;color:#92400E}.rlicadm-pill.warn .d{background:#D97706}
		.rlicadm-pill.off{background:#F3F4F6;color:#4B5563}.rlicadm-pill.off .d{background:#9CA3AF}
		.rlicadm-mono{font-family:"SFMono-Regular",Consolas,Menlo,monospace;font-size:13.5px;overflow-wrap:anywhere;}
		.rlicadm-log{width:100%;border-collapse:collapse;font-size:14px;}
		.rlicadm-log th{text-align:left;font-size:13px;font-weight:600;color:#6B7280;padding:8px;border-bottom:1px solid #E5E7EB;background:#F8FAFC;}
		.rlicadm-log td{padding:9px 8px;border-bottom:1px solid #F1F5F9;vertical-align:top;overflow-wrap:anywhere;}
		.rlicadm-scroll{overflow-x:auto;}
		.rlicadm-h3{font-size:15px;font-weight:700;margin:18px 0 8px;}
		.rlicadm-vie{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:0 0 16px;}
		.rlicadm-via{border:1px solid #E5E7EB;border-radius:10px;padding:12px 14px;background:#F9FAFB;font-size:14.5px;min-width:0;}
		.rlicadm-via b{display:block;font-size:15px;margin-bottom:4px;}
		.rlicadm-via.consigliata{border-color:#86EFAC;background:#F0FDF4;}
		.rlicadm-passi{list-style:none;margin:10px 0;padding:0;border:1px solid #E5E7EB;border-radius:10px;overflow:hidden;}
		.rlicadm-passi li{display:flex;gap:10px;padding:10px 14px;border-bottom:1px solid #F3F4F6;font-size:15px;}
		.rlicadm-passi li:last-child{border-bottom:0;}
		.rlicadm-passi .s{flex:none;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;background:#F3F4F6;color:#6B7280;}
		.rlicadm-passi li.ok .s{background:#DCFCE7;color:#15803D}.rlicadm-passi li.ko .s{background:#FEE2E2;color:#B91C1C}
		.rlicadm-passi .c{min-width:0;flex:1;display:flex;flex-direction:column;gap:3px;}
		.rlicadm-passi code{font-size:13px;background:#F9FAFB;border:1px solid #F3F4F6;border-radius:4px;padding:2px 6px;overflow-wrap:anywhere;white-space:pre-wrap;}
		.rlicadm-passi em{font-style:normal;font-size:14px;color:#92400E;}
		.rlicadm-steps{list-style:none;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px;margin:0 0 16px;padding:0;counter-reset:passo;}
		.rlicadm-steps li{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:10px 12px;min-width:0;display:flex;flex-direction:column;gap:6px;margin:0;}
		.rlicadm-steps li b{font-size:15px;}
		.rlicadm-steps li b::before{counter-increment:passo;content:counter(passo);display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#E5E7EB;color:#374151;font-size:13px;margin-right:6px;}
		.rlicadm-steps li.fatto{border-color:#86EFAC;background:#F0FDF4;} .rlicadm-steps li.fatto b::before{background:#16A34A;color:#fff;}
		.rlicadm-steps li.ora{border-color:#93A8BF;box-shadow:0 0 0 2px #DBE4EE;}
		.rlicadm-steps .rlicadm-pill{align-self:flex-start;font-size:13px;padding:3px 10px;white-space:normal;}
		.rlicadm-modi{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:0 0 14px;}
		.rlicadm-modo{display:block;border:1px solid #D1D5DB;border-radius:12px;padding:12px 14px;background:#fff;cursor:pointer;min-width:0;}
		.rlicadm-modo.is-on{border-color:#1F4E79;background:#EEF4FA;}
.rlicadm-modo.spento{opacity:.6;cursor:not-allowed;}
		.rlicadm-esclusa{display:block;margin:6px 0 0;font-size:14px;color:#B91C1C;}
		.rlicadm-modo .tt{display:flex;align-items:center;gap:8px;font-weight:700;font-size:15.5px;}
		.rlicadm-modo .tt input{width:18px;height:18px;margin:0;}
		.rlicadm-modo ol{margin:8px 0 0 22px;padding:0;font-size:14.5px;}
		.rlicadm-modo ol li{margin:2px 0;}
		.rlicadm-info{display:inline-block;margin-left:6px;cursor:help;}
		.rlicadm-nota{overflow-wrap:anywhere;font-size:14px;color:#4B5563;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:10px 12px;margin:0 0 12px;}
		.rlicadm-perche a{color:#B91C1C;text-decoration:underline;}
		#rlic-esito{scroll-margin-top:48px;}#rlic-esito:empty{display:none;}
		.rlicadm-readonly input,.rlicadm-readonly select{background:#F3F4F6 !important;color:#374151;}
		@media (max-width:1100px){ .rlicadm-steps{grid-template-columns:repeat(3,minmax(0,1fr));} }
		@media (max-width:782px){ .rlicadm-steps{grid-template-columns:1fr 1fr;} .rlicadm-modi{grid-template-columns:1fr;} }
		@media (max-width:420px){ .rlicadm-steps{grid-template-columns:1fr;} }
		@media (max-width:782px){ .rlicadm-grid,.rlicadm-recgrid,.rlicadm-vie{grid-template-columns:1fr;} .rlicadm-card{padding:0 14px 14px;} .rlicadm-card h2{margin:0 -14px 12px;padding:12px 14px 10px;} }
		@media (max-width:600px){ .rlicadm-log thead{display:none} .rlicadm-log tr{display:block;border-bottom:1px solid #E5E7EB;padding:6px 0} .rlicadm-log td{display:block;border:0;padding:2px 4px} }
		</style>

		<div class="wrap rlicadm"><hr class="wp-header-end"><?php /* 0.8.10A (RF-BUG-026): gli avvisi di WordPress sopra la testata, non dentro */ ?>
			<h1>Licenza d uso</h1>
			<div class="rlicadm-sub">Intestatario, contratto (EULA v<?php echo esc_html( Rdacademy_License::EULA_VERSION ); ?>), firma e trasmissione a <?php echo esc_html( RDACADEMY_VENDOR ); ?> &middot; <?php echo esc_html( RDACADEMY_PRODUCT ); ?></div>

			<?php
			if ( $msg === 'holder_ok' ) {
				self::avviso( 'rlicadm-ok', 'Dati intestatario salvati.' );
			} elseif ( $msg === 'holder_err' ) {
				self::avviso( 'rlicadm-err', $err !== '' ? $err : 'Dati intestatario non validi: correggi e riprova.' );
			} elseif ( $msg === 'accept_ok' ) {
				self::avviso( 'rlicadm-ok', 'Accettazione nel pannello registrata. Per l approvazione specifica firma il PDF e allegalo (Firma e trasmissione).' );
			} elseif ( $msg === 'accept_err' ) {
				self::avviso( 'rlicadm-err', $err !== '' ? $err : 'Accettazione non completata.' );
			}
			// 3.60.07G BUG-269.C S618_B269C — gli esiti di «Firma e trasmissione» si disegnano nella sezione (self::esito_firma), non qui in testa
			ob_start();
			if ( false ) {
			} elseif ( $msg === 'pdf_ok' ) {
				self::avviso( 'rlicadm-ok', 'Contratto generato e scaricato: firmalo (digitale o a mano) e allegalo qui sotto.' );
			} elseif ( $msg === 'ver_ok' ) {
				self::avviso( 'rlicadm-ok', 'Verifica superata: le due firme ci sono e sono della stessa persona. Ora puoi trasmettere il contratto a ' . RDACADEMY_VENDOR . '.' );
			} elseif ( $msg === 'ver_err' ) {
				self::avviso( 'rlicadm-err', 'Verifica non superata. ' . ( $err !== '' ? $err : 'Allega di nuovo il contratto firmato.' ) );
			} elseif ( $msg === 'pdf_err' ) {
				self::avviso( 'rlicadm-err', $err !== '' ? $err : 'Contratto non generato.' );
			} elseif ( $msg === 'rim_ok' ) {
				self::avviso( 'rlicadm-ok', 'Allegato rimosso: puoi scegliere di nuovo la modalità di firma e allegare il file.' );
			} elseif ( $msg === 'all_ok' ) {
				self::avviso( 'rlicadm-ok', 'Contratto firmato allegato. Ora puoi trasmetterlo a ' . RDACADEMY_VENDOR . '.' );
			} elseif ( $msg === 'all_err' ) {
				self::avviso( 'rlicadm-err', $err !== '' ? $err : 'Allegato non registrato.' );
			} elseif ( $msg === 'tx_ok' ) {
				self::avviso( 'rlicadm-ok', 'Contratto trasmesso a ' . Rdacademy_License::EMAIL_LICENZIANTE . '.' );
			} elseif ( $msg === 'tx_err' ) {
				self::avviso( 'rlicadm-err', 'Contratto non trasmesso: vedi l esito passo per passo qui sotto.' );
			}
			$esito_firma = (string) ob_get_clean();
			?>

			<?php
			// 1.3.1B — LO STEPPER (§ 10): cinque passi con lo stato in pillola
			$g = $S['generato']; $t_rec = $S['trasmesso'];
			$ric = $S['firmato_ok'] ? ( [ 'byte' => 'Riconosciuto', 'id' => 'Riconosciuto (file riscritto)', 'autografa' => 'Scansione allegata' ][ (string) $firmato['riconosciuto'] ] ?? 'Allegato' ) : 'Da allegare';
			$passi = [
				[ 'Intestazione', $S['mancanti'] ? 'Mancano ' . count( $S['mancanti'] ) . ( count( $S['mancanti'] ) === 1 ? ' campo' : ' campi' ) : 'Completa', $S['mancanti'] ? 'warn' : 'on', ! $S['mancanti'], '#rlic-intestatario' ],
				[ 'Contratto', $S['generato_ok'] ? 'Generato il ' . Rdacademy_License::quando( (string) $g['quando'], 'd/m/Y' ) : 'Da generare', $S['generato_ok'] ? ( $S['intestazione_cambiata'] ? 'warn' : 'on' ) : 'off', $S['generato_ok'] && ! $S['intestazione_cambiata'], '#rlic-genera' ],
				[ 'Firma', $S['firmato_ok'] ? ucfirst( Rdacademy_License::FIRME[ $firmato['modalita'] ] ) : 'Da scegliere', $S['firmato_ok'] ? 'on' : 'off', $S['firmato_ok'], '#rlic-firma' ],
				// 3.60.07G S618B_VIA_PASSI — senza verifica: l allegato è fatto quando c è
				[ 'Allegato', $S['firmato_ok'] ? 'Allegato' : 'Da allegare', $S['firmato_ok'] ? 'on' : 'off', $S['firmato_ok'], '#rlic-allega-box' ],
				[ 'Trasmissione', $bloccato ? 'Trasmesso il ' . Rdacademy_License::quando( (string) $t_rec['quando'], 'd/m/Y' ) : 'Da trasmettere', $bloccato ? 'on' : 'off', $bloccato, '#rlic-trasmetti-box' ],
			];
			$ora = -1; foreach ( $passi as $i => $x ) { if ( ! $x[3] ) { $ora = $i; break; } }
			?>
			<?php echo self::sezione_chiave( $msg, $err ); // phpcs:ignore — 3.74.04G (S708) S708_V150_PAGINA: la chiave sta in cima, prima del contratto (vista 3 del mockup) ?>
			<ol class="rlicadm-steps" id="rlic-passi" aria-label="Percorso del contratto">
				<?php foreach ( $passi as $i => $x ) : ?>
					<li class="<?php echo $x[3] ? 'fatto' : ( $i === $ora ? 'ora' : '' ); ?>" data-passo="<?php echo (int) $i + 1; ?>"><b><a href="<?php echo esc_attr( $x[4] ); ?>" style="color:inherit;text-decoration:none"><?php echo esc_html( $x[0] ); ?></a></b><span class="rlicadm-pill <?php echo esc_attr( $x[2] ); ?>"><span class="d"></span><?php echo esc_html( $x[1] ); ?></span></li>
				<?php endforeach; ?>
			</ol>
			<?php if ( $bloccato ) : ?>
				<div class="rlicadm-notice rlicadm-ok" id="rlic-bloccato"><span class="rlicadm-notice-t"><b><?php echo esc_html( Rdacademy_License::motivo_trasmesso( $S ) ); ?></b><?php if ( ! empty( $t_rec['originale_da_ricevere'] ) ) : ?> <?php echo esc_html( Rdacademy_License_Testi::riempi( (string) Rdacademy_License_Testi::modello( 'licenza.originale', 'it' )['oggetto'], $vv_firma, false ) ); ?>.<?php endif; ?></span></div>
			<?php endif; ?>

			<!-- SEZIONE A: INTESTATARIO E FORO -->
			<div class="rlicadm-card" id="rlic-intestatario">
				<h2>Dati del Licenziatario (intestatario)</h2>
				<?php
				$tipo_val = ( 'persona_fisica' === $holder['tipo']['value'] ) ? 'persona_fisica' : 'azienda';
				$is_az    = ( 'azienda' === $tipo_val );
				$rag_soc  = $is_az ? $holder['denominazione']['value'] : '';
				$naz_val  = ( '' !== $holder['nazione']['value'] ) ? $holder['nazione']['value'] : 'Italia';
				?>
				<?php if ( $bloccato ) : ?><p class="rlicadm-perche" id="rlic-intestazione-lettura" style="margin:0 0 12px">Intestazione in lettura: era nel contratto trasmesso.</p><?php endif; ?>
				<form method="post" action="<?php echo $post_url; ?>" id="rlic-form-holder"<?php echo $bloccato ? ' class="rlicadm-readonly"' : ''; ?>>
					<?php if ( $bloccato ) : ?><fieldset disabled style="border:0;padding:0;margin:0;min-width:0"><?php endif; ?>
					<?php wp_nonce_field( self::NONCE_HOLD ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACT_HOLD ); ?>">

					<div class="rlicadm-typesel" role="radiogroup" aria-label="Tipo soggetto">
						<label class="rlicadm-typeopt<?php echo $is_az ? ' is-on' : ''; ?>" data-type="azienda">
							<input type="radio" name="tipo" value="azienda"<?php checked( $is_az ); ?>>
							<span>Azienda / Ente</span>
						</label>
						<label class="rlicadm-typeopt<?php echo ! $is_az ? ' is-on' : ''; ?>" data-type="persona_fisica">
							<input type="radio" name="tipo" value="persona_fisica"<?php checked( ! $is_az ); ?>>
							<span>Persona fisica</span>
						</label>
					</div>

					<fieldset class="rlicadm-fieldset rlicadm-grp-azienda<?php echo $is_az ? '' : ' rlicadm-hidden'; ?>">
						<legend>Dati azienda</legend>
						<div class="rlicadm-grid">
							<div class="rlicadm-field"><label for="rl_denom">Ragione sociale</label><input type="text" id="rl_denom" name="denominazione" value="<?php echo esc_attr( $rag_soc ); ?>"></div>
							<div class="rlicadm-field"><label for="rl_piva">P.IVA</label><input type="text" id="rl_piva" name="piva" value="<?php echo esc_attr( $holder['piva']['value'] ); ?>" inputmode="numeric" placeholder="11 cifre"></div>
							<div class="rlicadm-field"><label for="rl_legr">Legale rappresentante</label><input type="text" id="rl_legr" name="legale_rappr" value="<?php echo esc_attr( $holder['legale_rappr']['value'] ); ?>"></div>
						</div>
					</fieldset>

					<fieldset class="rlicadm-fieldset rlicadm-grp-pf<?php echo ! $is_az ? '' : ' rlicadm-hidden'; ?>">
						<legend>Dati persona fisica</legend>
						<div class="rlicadm-grid">
							<div class="rlicadm-field"><label for="rl_nome">Nome</label><input type="text" id="rl_nome" name="nome" value="<?php echo esc_attr( $holder['nome']['value'] ); ?>"></div>
							<div class="rlicadm-field"><label for="rl_cognome">Cognome</label><input type="text" id="rl_cognome" name="cognome" value="<?php echo esc_attr( $holder['cognome']['value'] ); ?>"></div>
							<div class="rlicadm-field"><label for="rl_cf">Codice Fiscale</label><input type="text" id="rl_cf" name="cf" value="<?php echo esc_attr( $holder['cf']['value'] ); ?>" maxlength="16" style="text-transform:uppercase" placeholder="16 caratteri"></div>
						</div>
					</fieldset>

					<fieldset class="rlicadm-fieldset">
						<legend>Indirizzo (sede legale / residenza)</legend>
						<div class="rlicadm-grid">
							<div class="rlicadm-field"><label for="rl_via">Via e civico</label><input type="text" id="rl_via" name="via" value="<?php echo esc_attr( $holder['via']['value'] ); ?>"></div>
							<div class="rlicadm-field"><label for="rl_citta">Citta</label><input type="text" id="rl_citta" name="citta" value="<?php echo esc_attr( $holder['citta']['value'] ); ?>"></div>
							<div class="rlicadm-field"><label for="rl_cap">CAP</label><input type="text" id="rl_cap" name="cap" value="<?php echo esc_attr( $holder['cap']['value'] ); ?>" inputmode="numeric" maxlength="5" placeholder="5 cifre"></div>
							<div class="rlicadm-field"><label for="rl_prov">Provincia</label><input type="text" id="rl_prov" name="provincia" value="<?php echo esc_attr( $holder['provincia']['value'] ); ?>" maxlength="2" style="text-transform:uppercase" placeholder="Sigla (es. MI)"></div>
							<div class="rlicadm-field"><label for="rl_naz">Nazione</label><input type="text" id="rl_naz" name="nazione" value="<?php echo esc_attr( $naz_val ); ?>"></div>
						</div>
						<p class="rlicadm-hint" style="margin-top:-4px">CAP e sigla provincia sono validati solo per nazione Italia.</p>
					</fieldset>

					<?php /* S717_V166 — 3.75.05 voce 166: il foro è nel testo della licenza v4 (Milano), il campo non c è più. */ ?>
					<fieldset class="rlicadm-fieldset">
						<legend>Contratto</legend>
						<div class="rlicadm-grid">
							<div class="rlicadm-field"><label for="rl_email">Email o PEC del Licenziatario</label><input type="text" id="rl_email" name="email" value="<?php echo esc_attr( $holder['email']['value'] ); ?>" inputmode="email" placeholder="nome@pec.azienda.it"></div>
						</div>
					</fieldset>

					<button type="submit" class="rlicadm-btn" id="rlic-salva">Salva intestatario</button>
					<?php if ( $bloccato ) : ?></fieldset><?php endif; ?>
				</form>
			</div>

			<!-- SEZIONE B: ANCORA DI DOMINIO -->
			<div class="rlicadm-card" id="rlic-ancora">
				<h2>Ancora di dominio
					<?php if ( $anc_row ) : ?><span class="rlicadm-pill <?php echo $anc_st === Rdacademy_License_Anchor::STATE_ACTIVE ? 'on' : 'warn'; ?>" id="rlic-ancora-stato"><span class="d"></span><?php echo esc_html( ucfirst( Rdacademy_License_Anchor::parola( $anc_st ) ) ); ?></span><?php endif; ?>
				</h2>
				<?php if ( ! $anc_row ) : ?>
					<div class="rlicadm-notice rlicadm-warn"><span class="rlicadm-notice-t">Ancora non registrata: la tabella <code><?php echo esc_html( RDACADEMY_TABLE_PREFIX . 'license_activation'   /* 3.59.09G bis (S615B): RelDoxAcademy non ha Rdacademy_Schema_License_V1, la tabella la crea l Activator */ ); ?></code> non risponde. Il prossimo caricamento ritenta da solo.</span></div>
				<?php else : ?>
					<div class="rlicadm-recgrid">
						<span><b>Dominio registrato:</b> <span class="rlicadm-mono"><?php echo esc_html( (string) $anc_row['activation_domain'] ); ?></span></span>
						<span><b>Dominio corrente:</b> <span class="rlicadm-mono"><?php echo esc_html( Rdacademy_License_Anchor::current_domain() ); ?></span></span>
						<span><b>Registrata il (UTC):</b> <?php echo esc_html( (string) $anc_row['activated_at'] ); ?></span>
						<span><b>Versione alla registrazione:</b> <?php echo esc_html( (string) $anc_row['plugin_version'] ); ?></span>
					</div>
					<p class="rlicadm-hint" style="margin:10px 0 0">
						<?php if ( $anc_st === Rdacademy_License_Anchor::STATE_ACTIVE ) : ?>
							Il sito gira sul dominio con cui e stato attivato.
						<?php else : ?>
							Il sito e stato spostato su un altro dominio: la licenza va rinnovata. Il prodotto continua a funzionare (nessun blocco).
						<?php endif; ?>
						L ancora e la base della futura chiave di funzionamento per gli aggiornamenti.
					</p>
				<?php endif; ?>
			</div>

			<!-- SEZIONE C: IL CONTRATTO -->
			<div class="rlicadm-card" id="rlic-contratto">
				<h2>Contratto di licenza (EULA v<?php echo esc_html( Rdacademy_License::EULA_VERSION ); ?>)
					<span class="rlicadm-pill <?php echo $accepted ? 'on' : 'warn'; ?>" id="rlic-stato"><span class="d"></span><?php echo $accepted ? 'Accettato nel pannello' : 'Da accettare'; ?></span>
				</h2>
				<p class="rlicadm-hint" style="margin:0 0 12px">Codice di controllo del testo: <span class="rlicadm-mono" id="rlic-impronta"><?php echo esc_html( Rdacademy_License_Pdf::codice_breve( Rdacademy_License::text_hash() ) ); ?></span><span class="rlicadm-info" title="<?php echo esc_attr( $nota_cod ); ?>" tabindex="0" aria-label="Che cos è il codice di controllo">ⓘ</span> — è lo stesso del kit di firma di Idea Technologies; quando cambia il testo serve un nuovo consenso, non quando cambia la versione del plugin. L intestazione non entra nel codice.</p>
				<p class="rlicadm-nota" id="rlic-nota-codice"><?php echo esc_html( $nota_cod ); ?></p>

				<div class="rlicadm-grid" style="margin-bottom:16px;gap:12px 16px">
					<div class="rlicadm-party">
						<div class="role">Licenziante</div>
						<div class="val" id="rlic-licenziante"><b><?php echo esc_html( $giver['denominazione'] ); ?></b><?php echo $giver['forma'] !== '' ? ', ' . esc_html( $giver['forma'] ) : ''; ?><br><?php echo esc_html( $giver['sede'] ); ?><br>P.IVA <?php echo esc_html( $giver['piva_cf'] ); ?> · PEC <span class="rlicadm-mono"><?php echo esc_html( $giver['pec'] ); ?></span><br><span class="rlicadm-mono"><?php echo esc_html( Rdacademy_License::EMAIL_LICENZIANTE ); ?></span></div>
					</div>
					<div class="rlicadm-party">
						<div class="role">Licenziatario &middot; intestatario</div>
						<div class="val">
							<?php if ( $holder['denominazione']['missing'] ) : ?>
								<span class="rlicadm-miss">Denominazione non impostata (compila la sezione sopra)</span>
							<?php else : ?>
								<b><?php echo esc_html( $holder['denominazione']['value'] ); ?></b>
								<?php if ( ! $holder['piva_cf']['missing'] ) : ?><br><span class="rlicadm-mono"><?php echo esc_html( $holder['piva_cf']['value'] ); ?></span><?php endif; ?>
								<?php if ( ! $holder['sede']['missing'] ) : ?><br><?php echo esc_html( $holder['sede']['value'] ); ?><?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="rlicadm-eula" tabindex="0" aria-label="Testo del contratto" id="rlic-testo">
					<?php foreach ( $articles as $a ) : ?>
						<div class="rlicadm-art" data-art="<?php echo esc_attr( $a['n'] ); ?>">
							<div class="t"><?php echo esc_html( $a['n'] . '. ' . $a['title'] ); ?></div>
							<div><?php echo nl2br( esc_html( $a['body'] ) ); ?></div>
							<?php if ( ! empty( $a['items'] ) ) : ?>
								<ul><?php foreach ( $a['items'] as $it ) : ?><li><?php echo esc_html( $it ); ?></li><?php endforeach; ?></ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- SEZIONE D: CONTRATTO, FIRMA E TRASMISSIONE (1.3.1B — SPEC-FIRMA-LICENZA-RELDOX §§ 4-6) -->
			<div class="rlicadm-card" id="rlic-firma">
				<h2>Firma e trasmissione
					<span class="rlicadm-pill <?php echo $bloccato || $S['firmato_ok'] ? 'on' : 'off'; /* S618B_VIA_PILL */ ?>" id="rlic-firma-stato"><span class="d"></span><?php echo $bloccato ? 'Trasmesso' : ( $S['firmato_ok'] ? 'Contratto firmato allegato' : 'Nessun contratto firmato' ); ?></span>
				</h2>
				<?php $esito_sotto = ( $msg === 'all_ok' && $firmato && $S['firmato_ok'] ); // S618_B269C_SOTTO — l esito di «Allega» riuscito va accanto all allegato ?>
				<?php if ( ! $esito_sotto ) : ?><div id="rlic-esito"><?php echo $esito_firma; // phpcs:ignore -- self::avviso() passa tutto da esc_html / esc_attr ?></div><?php endif; ?>
				<p class="rlicadm-hint" style="margin:0 0 12px">L approvazione specifica degli articoli <?php echo esc_html( Rdacademy_License_Pdf::elenco( Rdacademy_License::VEX_ARTICLES ) ); ?> (artt. 1341-1342 c.c.) vale con una <b>seconda firma sul documento</b>, non con una casella spuntata: genera il contratto, firmalo due volte, allegalo e trasmettilo a <?php echo esc_html( RDACADEMY_VENDOR ); ?>.</p>

				<div class="rlicadm-h3" id="rlic-genera" style="margin-top:0">Il contratto</div>
				<div class="rlicadm-cmd">
					<?php if ( $gen_ferma === '' ) : ?>
						<a class="rlicadm-btn" id="rlic-pdf" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACT_PDF ), self::NONCE_PDF ) ); ?>">⤓ <?php echo $S['generato_ok'] ? 'Genera di nuovo e scarica' : 'Genera e scarica il contratto'; ?></a>
					<?php else : ?>
						<button type="button" class="rlicadm-btn" id="rlic-pdf" disabled>⤓ Genera e scarica il contratto</button>
					<?php endif; ?>
				</div>
				<?php if ( $gen_ferma !== '' ) : ?>
					<p class="rlicadm-perche" id="rlic-pdf-perche"><?php
						if ( ! $bloccato && $S['mancanti'] ) {
							echo esc_html( count( $S['mancanti'] ) === 1 ? 'Per generare il contratto manca: ' : 'Per generare il contratto mancano: ' );
							$l = array(); foreach ( $S['mancanti'] as $mm ) $l[] = '<a href="#' . esc_attr( $mm[0] ) . '" data-campo="' . esc_attr( $mm[0] ) . '">' . esc_html( $mm[1] ) . '</a>';
							echo implode( ', ', $l ) . '.'; // phpcs:ignore -- ogni parte è già passata da esc_html / esc_attr
						} else echo esc_html( $gen_ferma );
					?></p>
				<?php elseif ( $S['generato_ok'] ) : ?>
					<p class="rlicadm-hint" style="margin:8px 0 0" id="rlic-generato">Generato il <?php echo esc_html( Rdacademy_License::quando( (string) $S['generato']['quando'] ) ); ?> · <?php echo (int) $S['generato']['pagine']; ?> pagine · identificativo <span class="rlicadm-mono"><?php echo esc_html( (string) $S['generato']['id'] ); ?></span>. Generarlo di nuovo fa decadere un allegato firmato su quello precedente: vale l ultimo.</p>
					<?php if ( $S['intestazione_cambiata'] ) : ?><p class="rlicadm-perche" id="rlic-intestazione-cambiata">L intestazione è cambiata dopo la generazione: genera di nuovo il contratto prima di firmarlo.</p><?php endif; ?>
				<?php endif; ?>

				<div class="rlicadm-h3">Come firmi — una modalità esclude l altra</div>
				<form method="post" action="<?php echo $post_url; ?>" enctype="multipart/form-data" id="rlic-form-allega">
					<?php wp_nonce_field( self::NONCE_ALL ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACT_ALL ); ?>">
					<?php
					// 1.3.2B (RF-BUG-027, owner 12/10 16:12): la scelta della modalità è libera finché non c è un allegato; si chiude
					// («una esclude l altra») solo con l allegato e dopo la trasmissione. Prima della generazione si spengono solo il file e «Allega».
					$scelta = $firmato && $S['firmato_ok'] ? $firmato['modalita'] : 'digitale';
					// 3.60.07G BUG-269.C S618_B269C — dopo un «Allega» rifiutato si ripropone la modalità che l utente aveva scelto
					$mod_q = isset( $_GET['rlic_mod'] ) ? sanitize_key( (string) $_GET['rlic_mod'] ) : '';
					if ( ! ( $firmato && $S['firmato_ok'] ) && isset( Rdacademy_License::FIRME[ $mod_q ] ) ) $scelta = $mod_q;
					$modi_bloccati = in_array( $fase, array( 'firmato', 'trasmesso' ), true );
					$modi_spenti = $all_ferma !== '';
					$motivo_modi = $bloccato ? Rdacademy_License::motivo_trasmesso( $S ) : ( $modi_bloccati ? $all_ferma : '' );
					?>
					<div class="rlicadm-modi" role="radiogroup" aria-label="Modalità di firma">
						<?php foreach ( array( 'digitale', 'autografa' ) as $mo ) :
							$tx = Rdacademy_License_Pdf::testo( 'licenza.firma_' . $mo, $vv_firma );
							$esclusa = $modi_bloccati && $scelta !== $mo; ?>
							<label class="rlicadm-modo<?php echo $scelta === $mo ? ' is-on' : ''; ?><?php echo $esclusa ? ' spento' : ''; ?>" data-modo="<?php echo esc_attr( $mo ); ?>" id="rlic-modo-<?php echo esc_attr( $mo ); ?>">
								<span class="tt"><input type="radio" name="modalita" value="<?php echo esc_attr( $mo ); ?>"<?php checked( $scelta === $mo ); ?><?php disabled( $modi_bloccati ); ?>><?php echo esc_html( $tx[0] ); ?></span>
								<?php if ( $esclusa ) : ?><span class="rlicadm-esclusa">Esclusa: <?php echo esc_html( $motivo_modi ); ?></span><?php endif; ?>
								<ol><?php foreach ( preg_split( '/\R/u', trim( $tx[1] ) ) as $riga ) : if ( trim( $riga ) === '' ) continue; ?><li><?php echo esc_html( $riga ); ?></li><?php endforeach; ?></ol>
							</label>
						<?php endforeach; ?>
					</div>
					<?php if ( $modi_bloccati ) : ?><p class="rlicadm-perche" id="rlic-modi-perche" style="margin:-4px 0 10px">Modalità bloccata: <?php echo esc_html( $motivo_modi ); ?></p><?php endif; ?>
					<?php if ( $modi_spenti && ! $modi_bloccati ) : ?><p class="rlicadm-perche" id="rlic-allega-perche" style="margin:-4px 0 10px">«Allega» spento: <?php echo esc_html( $all_ferma ); ?> La modalità la puoi scegliere già adesso.</p><?php endif; ?>
					<p class="rlicadm-hint" style="margin:0 0 12px">La validità dei certificati di firma la verifica Idea Technologies alla ricezione.</p>
					<div id="rlic-allega-box">
						<div class="rlicadm-field"><label for="rl_file">Il contratto firmato (PDF, al massimo 10 MB)</label>
							<input type="file" id="rl_file" name="contratto" accept=".pdf,application/pdf"<?php disabled( $modi_spenti ); ?>>
							<?php if ( $msg === 'all_err' && ! $modi_spenti ) : ?><span class="rlicadm-perche" id="rlic-file-di-nuovo" style="margin:2px 0 0">Il browser non tiene il file dopo un rifiuto: sceglilo di nuovo.</span><?php endif; ?></div>
						<div class="rlicadm-cmd">
							<button type="submit" class="rlicadm-btn sec" id="rlic-allega"<?php disabled( $modi_spenti ); ?>>Allega il contratto firmato</button>
						</div>
						<?php if ( $modi_bloccati ) : ?><p class="rlicadm-perche" id="rlic-allega-perche">«Allega» spento: <?php echo esc_html( $all_ferma ); ?></p><?php endif; ?>
					</div>
				</form>

				<?php if ( $firmato && $S['firmato_ok'] ) : ?>
					<?php if ( $esito_sotto ) : ?><div id="rlic-esito" style="margin-top:14px"><?php echo $esito_firma; // phpcs:ignore -- self::avviso() passa tutto da esc_html / esc_attr ?></div><?php endif; ?>
					<div class="rlicadm-recgrid" id="rlic-allegato" style="margin-top:14px">
						<span><b>File:</b> <?php echo esc_html( (string) $firmato['nome'] ); ?> (<?php echo esc_html( size_format( (int) $firmato['byte'] ) ); ?>)</span>
						<span><b>Modalità:</b> <?php echo esc_html( Rdacademy_License::FIRME[ $firmato['modalita'] ] ?? (string) $firmato['modalita'] ); ?></span>
						<span><b>Riconoscimento:</b> <span id="rlic-riconosciuto"><?php echo esc_html( Rdacademy_License::riconoscimento( (string) $firmato['riconosciuto'] ) ); ?></span></span>
						<span><b><?php echo $firmato['modalita'] === 'digitale' ? 'Firme:' : 'Pagine:'; ?></b> <?php echo esc_html( $firmato['modalita'] === 'digitale' ? (int) $firmato['firme'] . ' (contratto e approvazione specifica)' : (int) $firmato['pagine'] . ' su ' . (int) $S['generato']['pagine'] ); ?></span>
						<span><b>Allegato il:</b> <?php echo esc_html( Rdacademy_License::quando( (string) $firmato['quando'] ) ); ?> da <?php echo esc_html( (string) ( $firmato['user_name'] ?? '' ) ); ?></span>
						<span><b>Impronta del file:</b> <span class="rlicadm-mono">sha256:<?php echo esc_html( substr( (string) $firmato['sha256'], 0, 16 ) ); ?>…</span></span>
						<?php if ( false /* S618B_VIA_RIGA */ ) : ?><span style="grid-column:1/-1" id="rlic-verificato"><b>Verifica superata il <?php echo esc_html( Rdacademy_License::quando( (string) $firmato['verifica']['quando'] ) ); ?>:</b> <?php echo esc_html( (string) $firmato['verifica']['riassunto'] ); ?></span><?php endif; ?>
						<?php if ( $firmato['modalita'] === 'autografa' ) : ?><span style="grid-column:1/-1" id="rlic-originale"><b><?php echo esc_html( Rdacademy_License_Testi::riempi( (string) Rdacademy_License_Testi::modello( 'licenza.originale', 'it' )['oggetto'], $vv_firma, false ) ); ?></b></span><?php endif; ?>
					</div>
					<div class="rlicadm-cmd" style="margin-top:10px">
						<?php /* 3.60.07G S618B_VIA_TASTO — «Verifica il documento» tolto (owner 2026-10-14 06:59) */ ?>
						<?php if ( $file_ok ) : ?><a class="rlicadm-btn sec" id="rlic-file" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACT_FILE ), self::NONCE_FILE ) ); ?>">Scarica il file allegato</a><?php endif; ?>
						<?php if ( ! $bloccato ) : ?>
							<form method="post" action="<?php echo $post_url; ?>" id="rlic-form-rimuovi" style="margin:0">
								<?php wp_nonce_field( self::NONCE_RIM ); ?><input type="hidden" name="action" value="<?php echo esc_attr( self::ACT_RIM ); ?>">
								<button type="submit" class="rlicadm-btn sec" id="rlic-rimuovi">Rimuovi l allegato</button>
							</form>
						<?php else : ?>
							<button type="button" class="rlicadm-btn sec" id="rlic-rimuovi" disabled>Rimuovi l allegato</button>
						<?php endif; ?>
					</div>
					<?php if ( $bloccato ) : ?><p class="rlicadm-perche" style="margin:4px 0 0" id="rlic-rimuovi-perche">«Rimuovi l allegato» spento: <?php echo esc_html( Rdacademy_License::motivo_trasmesso( $S ) ); ?></p><?php endif; ?>
				<?php endif; ?>

				<div class="rlicadm-h3" id="rlic-trasmetti-box">Trasmetti a <?php echo esc_html( RDACADEMY_VENDOR ); ?></div>
				<form method="post" action="<?php echo $post_url; ?>" id="rlic-form-trasmetti">
					<?php wp_nonce_field( self::NONCE_TX ); ?>
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACT_TX ); ?>">
					<button type="submit" class="rlicadm-btn" id="rlic-trasmetti"<?php disabled( $tx_ferma !== '' ); ?>>✉ Trasmetti a <?php echo esc_html( RDACADEMY_VENDOR ); ?></button>
					<?php if ( $tx_ferma !== '' ) : ?><p class="rlicadm-perche" id="rlic-trasmetti-perche">«Trasmetti» spento: <?php echo esc_html( $tx_ferma ); ?></p>
					<?php else : ?><p class="rlicadm-hint" style="margin:8px 0 0">Una mail dalla casella di servizio a <?php echo esc_html( Rdacademy_License::EMAIL_LICENZIANTE ); ?> col file firmato, la modalità di firma, il riconoscimento, l identificativo del contratto e il codice di controllo.</p><?php endif; ?>
				</form>
				<?php if ( is_array( $esito_tx ) ) : ?>
					<div id="rlic-tx-esito" style="margin-top:12px">
						<?php self::passi( $esito_tx ); ?>
						<button type="button" class="rlicadm-btn sec" id="rlic-tx-copia" style="min-height:40px" data-testo="<?php echo esc_attr( (string) $esito_tx['testo'] ); ?>">📋 Copia esito</button>
					</div>
				<?php endif; ?>
			</div>

			<!-- SEZIONE E: ACCETTAZIONE NEL PANNELLO -->
			<div class="rlicadm-card" id="rlic-accettazione">
				<h2>Accettazione nel pannello</h2>
				<?php if ( $accepted && $rec ) : ?>
					<div class="rlicadm-notice rlicadm-ok" id="rlic-accettata">
						<span class="rlicadm-notice-t"><b>Licenza accettata nel pannello.</b> Registrazione probatoria vigente. Per l approvazione specifica vale la firma del PDF.</span>
					</div>
					<div class="rlicadm-recgrid">
						<span><b>Firmatario:</b> <?php echo esc_html( $rec['signer_name'] ?? '' ); ?></span>
						<span><b>Per conto di:</b> <?php echo esc_html( $rec['denominazione'] ?? '' ); ?></span>
						<span><b>Rappresentanza:</b> dichiarata</span>
						<span><b>Utente WP:</b> <?php echo esc_html( ( $rec['user_name'] ?? '' ) . ' (id ' . (int) ( $rec['user_id'] ?? 0 ) . ')' ); ?></span>
						<span><b>Data/ora (UTC):</b> <?php echo esc_html( $rec['accepted_at_utc'] ?? '' ); ?></span>
						<span><b>Versione:</b> <?php echo esc_html( trim( ( $rec['plugin_version'] ?? '' ) . ' ' . ( $rec['plugin_stage'] ?? '' ) ) ); ?></span>
						<span><b>Articoli approvati:</b> <?php echo esc_html( implode( ', ', $rec['vex_articles'] ?? [] ) ); ?></span>
						<span><b>Impronta testo:</b> <span class="rlicadm-mono">sha256:<?php echo esc_html( substr( (string) ( $rec['text_hash'] ?? '' ), 0, 12 ) ); ?>&hellip;</span></span>
					</div>
				<?php else : ?>
					<?php if ( $superata ) : ?>
						<div class="rlicadm-notice rlicadm-warn" id="rlic-superata"><span class="rlicadm-notice-t">Il testo del contratto e cambiato dopo l ultima accettazione (<?php echo esc_html( (string) ( $rec['accepted_at_utc'] ?? '' ) ); ?>, EULA v<?php echo esc_html( (string) ( $rec['eula_version'] ?? '' ) ); ?>): serve un nuovo consenso.</span></div>
					<?php endif; ?>
					<form method="post" action="<?php echo $post_url; ?>" id="rlic-form-accetta">
						<?php wp_nonce_field( self::NONCE_ACC ); ?>
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACT_ACC ); ?>">
						<div class="rlicadm-grid">
							<div class="rlicadm-field"><label for="rl_signer">Nome e cognome del firmatario</label><input type="text" id="rl_signer" name="signer_nome" value=""></div>
							<div class="rlicadm-field"><label for="rl_sdenom">Denominazione rappresentata</label><input type="text" id="rl_sdenom" name="signer_denom" value="<?php echo esc_attr( $holder['denominazione']['value'] ); ?>"></div>
						</div>
						<div class="rlicadm-sub2">
							<label class="rlicadm-check"><input type="checkbox" name="acc_rappr" value="1" id="rl_acc_rappr"><span>Dichiaro di sottoscrivere in nome e per conto del soggetto sopra indicato e di averne i poteri di rappresentanza, assumendone la responsabilita.</span></label>
							<label class="rlicadm-check"><input type="checkbox" name="acc_general" value="1" id="rl_acc_general"><span>Accetto integralmente il contratto di licenza d uso (accettazione generale).</span></label>
							<label class="rlicadm-check"><input type="checkbox" name="acc_vex" value="1" id="rl_acc_vex"><span>Ai sensi degli artt. 1341-1342 c.c. approvo specificamente gli articoli <?php echo esc_html( Rdacademy_License::vex_testo() ); ?>.</span></label>
						</div>
						<button type="submit" class="rlicadm-btn" id="rlic-accetta">Accetta e registra</button>
					</form>
				<?php endif; ?>
			</div>

			<!-- SEZIONE F: STORICO -->
			<div class="rlicadm-card" id="rlic-storico">
				<h2>Storico <span class="rlicadm-hint" style="font-weight:400;margin:0">si aggiunge e non si cancella</span></h2>
				<div class="rlicadm-h3" style="margin-top:0">Firma e trasmissione (<?php echo count( $eventi ); ?>)</div>
				<?php if ( ! $eventi ) : ?>
					<p class="rlicadm-miss" style="margin:0" id="rlic-eventi-vuoto">Nessun contratto generato, nessun allegato finora.</p>
				<?php else : ?>
					<div class="rlicadm-scroll"><table class="rlicadm-log" id="rlic-eventi">
						<thead><tr><th>Quando</th><th>Che cosa</th><th>Chi</th><th>Dettaglio</th><th>Testo</th></tr></thead>
						<tbody>
						<?php foreach ( $eventi as $e ) : $t = (string) ( $e['tipo'] ?? '' ); ?>
							<tr data-tipo="<?php echo esc_attr( $t ); ?>">
								<td><?php echo esc_html( self::quando( (string) ( $e['quando_utc'] ?? '' ) ) ); ?></td>
								<td><b<?php echo $t === 'trasmissione_ko' ? ' style="color:#B91C1C"' : ''; ?>><?php echo esc_html( $ev_nomi[ $t ] ?? $t ); ?></b></td>
								<td><?php echo esc_html( (string) ( $e['user_name'] ?? '' ) ); ?></td>
								<td><?php
									if ( $t === 'pdf' ) echo esc_html( (int) ( $e['pagine'] ?? 0 ) . ' pagine · foro ' . ( (string) ( $e['foro'] ?? '' ) !== '' ? $e['foro'] : Rdacademy_License::FORO_VUOTO ) . ( ! empty( $e['id_generato'] ) ? ' · ' . $e['id_generato'] : '' ) );
									elseif ( $t === 'allegato' ) echo esc_html( (string) ( $e['nome'] ?? $e['nome_originale'] ?? '' ) . ' · ' . ( Rdacademy_License::FIRME[ $e['modalita'] ?? $e['firma'] ?? '' ] ?? '' ) . ( ! empty( $e['riconosciuto'] ) ? ' · ' . Rdacademy_License::riconoscimento( (string) $e['riconosciuto'] ) : '' ) . ' · sha256 ' . substr( (string) ( $e['sha256'] ?? '' ), 0, 12 ) . '…' );
									elseif ( in_array( $t, array( 'allegato_rifiutato', 'allegato_decaduto' ), true ) ) echo esc_html( (string) ( $e['nome'] ?? '' ) . ' — ' . (string) ( $e['motivo'] ?? '' ) );
									elseif ( $t === 'allegato_rimosso' ) echo esc_html( (string) ( $e['nome'] ?? '' ) );
									elseif ( $t === 'riaperto' ) echo esc_html( 'codice ' . Rdacademy_License_Pdf::codice_breve( (string) ( $e['codice_vecchio'] ?? '' ) ) . ' → ' . Rdacademy_License_Pdf::codice_breve( (string) ( $e['codice_nuovo'] ?? '' ) ) );
									elseif ( $t === 'trasmesso' ) echo esc_html( 'a ' . ( $e['a'] ?? '' ) . ' · file sha256 ' . substr( (string) ( $e['sha256'] ?? '' ), 0, 12 ) . '…' );
									else echo esc_html( trim( (string) ( $e['motivo'] ?? '' ) ) );
								?></td>
								<td>EULA v<?php echo esc_html( (string) ( $e['eula_version'] ?? '' ) ); ?><?php echo hash_equals( Rdacademy_License::text_hash(), (string) ( $e['text_hash'] ?? '' ) ) ? '' : ' (precedente)'; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table></div>
				<?php endif; ?>
				<?php if ( $storico_c ) : ?>
					<div class="rlicadm-h3">Contratti trasmessi superati da un testo nuovo (<?php echo count( $storico_c ); ?>)</div>
					<div class="rlicadm-scroll"><table class="rlicadm-log" id="rlic-storico-contratti">
						<thead><tr><th>Trasmesso il</th><th>Modalità</th><th>Contratto</th><th>Riaperto il</th></tr></thead><tbody>
						<?php foreach ( $storico_c as $sc ) : ?>
							<tr><td><?php echo esc_html( Rdacademy_License::quando( (string) ( $sc['trasmesso']['quando'] ?? '' ) ) ); ?></td>
								<td><?php echo esc_html( Rdacademy_License::FIRME[ $sc['trasmesso']['modalita'] ?? '' ] ?? '' ); ?></td>
								<td class="rlicadm-mono"><?php echo esc_html( (string) ( $sc['trasmesso']['id_generato'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( Rdacademy_License::quando( (string) ( $sc['riaperto_utc'] ?? '' ) ) ); ?></td></tr>
						<?php endforeach; ?>
					</tbody></table></div>
				<?php endif; ?>
				<div class="rlicadm-h3">Accettazioni nel pannello (<?php echo count( $log ); ?>)</div>
				<?php if ( ! $log ) : ?>
					<p class="rlicadm-miss" style="margin:0">Nessuna accettazione registrata finora.</p>
				<?php else : ?>
					<div class="rlicadm-scroll">
					<table class="rlicadm-log">
						<thead><tr><th>Data/ora (UTC)</th><th>Firmatario</th><th>Per conto di</th><th>Utente WP</th><th>Versione</th><th>Impronta</th></tr></thead>
						<tbody>
						<?php foreach ( $log as $r ) : ?>
							<tr>
								<td class="rlicadm-mono"><?php echo esc_html( (string) ( $r['accepted_at_utc'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $r['signer_name'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $r['denominazione'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $r['user_name'] ?? '' ) . ' (id ' . (int) ( $r['user_id'] ?? 0 ) . ')' ); ?></td>
								<td><?php echo esc_html( trim( (string) ( $r['plugin_version'] ?? '' ) . ' ' . (string) ( $r['plugin_stage'] ?? '' ) ) ); ?> · EULA v<?php echo esc_html( (string) ( $r['eula_version'] ?? '' ) ); ?></td>
								<td class="rlicadm-mono"><?php echo esc_html( substr( (string) ( $r['text_hash'] ?? '' ), 0, 12 ) ); ?>…<?php echo hash_equals( Rdacademy_License::text_hash(), (string) ( $r['text_hash'] ?? '' ) ) ? '' : ' <b>(testo precedente)</b>'; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<script>
		(function(){
			var form = document.getElementById('rlic-form-holder');
			if(form){
				var opts = form.querySelectorAll('.rlicadm-typeopt');
				var grpA = form.querySelector('.rlicadm-grp-azienda');
				var grpP = form.querySelector('.rlicadm-grp-pf');
				var apply = function(t){
					if(grpA) grpA.classList.toggle('rlicadm-hidden', t !== 'azienda');
					if(grpP) grpP.classList.toggle('rlicadm-hidden', t !== 'persona_fisica');
					opts.forEach(function(o){ o.classList.toggle('is-on', o.getAttribute('data-type') === t); });
				};
				opts.forEach(function(o){
					var r = o.querySelector('input[type=radio]');
					if(!r) return;
					r.addEventListener('change', function(){ if(r.checked) apply(o.getAttribute('data-type')); });
				});
			}
			document.addEventListener('click', function(e){
				var x = e.target.closest ? e.target.closest('.rlicadm-x') : null;
				if(x && x.parentNode){ x.parentNode.remove(); }
				var c = e.target.closest ? e.target.closest('#rlic-tx-copia') : null;
				if(c){ var t = c.getAttribute('data-testo') || ''; var ok = function(){ c.textContent = '✓ Esito copiato'; };
					if(navigator.clipboard){ navigator.clipboard.writeText(t).then(ok, function(){ c.textContent = 'Copia non riuscita'; }); }
					else { var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); try { document.execCommand('copy'); ok(); } catch(err) {} a.remove(); } }
			});
			/* 1.3.1B — le due modalità: una sola scelta (radio), la scheda scelta evidenziata. */
			document.querySelectorAll('.rlicadm-modo input[type=radio]').forEach(function(r){ r.addEventListener('change', function(){
				document.querySelectorAll('.rlicadm-modo').forEach(function(m){ m.classList.toggle('is-on', m.getAttribute('data-modo') === r.value && r.checked); }); }); });
			/* I campi che mancano, cliccabili: portano al campo (§ 3). */
			document.querySelectorAll('#rlic-pdf-perche a[data-campo]').forEach(function(a){ a.addEventListener('click', function(e){ var c = document.getElementById(a.getAttribute('data-campo')); if(c){ e.preventDefault(); c.scrollIntoView({block:'center'}); try{ c.focus({preventScroll:true}); }catch(err){ c.focus(); } } }); });
			/* 3.60.03G BUG-268 S616_B268 — «Genera e scarica»: il PDF si scarica e la pagina, quando il server ha risposto (cookie col gettone del clic), si rilegge da sola. */
			var gp = document.querySelector('a#rlic-pdf');
			if(gp){ gp.addEventListener('click', function(){
				var t = Date.now().toString(36) + Math.random().toString(36).slice(2, 8), u = gp.getAttribute('href'), n = 0;
				gp.setAttribute('href', u.replace(/([?&])rlic_t=[^&]*&?/, '$1') + (u.indexOf('?') < 0 ? '?' : '&') + 'rlic_t=' + t);
				gp.classList.add('is-busy'); gp.setAttribute('aria-busy', 'true');
				var giro = setInterval(function(){
					n++;
					if(document.cookie.indexOf('<?php echo esc_js( self::COOKIE_PDF ); ?>=' + t) > -1 || n > 150){
						clearInterval(giro);
						document.cookie = '<?php echo esc_js( self::COOKIE_PDF ); ?>=; Max-Age=0; path=/; SameSite=Lax';
						setTimeout(function(){ location.replace('<?php echo esc_js( add_query_arg( [ 'page' => self::PAGE_SLUG, 'rlic_msg' => 'pdf_ok' ], admin_url( 'admin.php' ) ) ); ?>' + '#rlic-esito'); }, 500);
					}
				}, 200);
			}); }
			/* 3.60.07G S618B_VIA_JS — «Verifica il documento» tolto */
			/* «Allega»: il controllo del file prima di spedirlo (il server ricontrolla tutto). */
			var fa = document.getElementById('rlic-form-allega');
			if(fa){ fa.addEventListener('submit', function(e){ var f = document.getElementById('rl_file'); if(!f.files || !f.files.length){ e.preventDefault(); alertBox('Scegli il file del contratto firmato.'); return; }
				if(f.files[0].size > <?php echo (int) self::limite_byte(); ?>){ e.preventDefault(); alertBox('Il file supera il limite di caricamento (<?php echo esc_js( self::limite_testo() ); ?>).'); } }); }
			function alertBox(t){ var n = document.createElement('div'); n.className = 'rlicadm-notice rlicadm-err'; n.setAttribute('role','alert'); n.innerHTML = '<span class="rlicadm-notice-t"></span><button type="button" class="rlicadm-x" aria-label="Chiudi">&times;</button>'; n.firstChild.textContent = t; fa.parentNode.insertBefore(n, fa); }
		})();
		</script>
		<?php
	}
}
