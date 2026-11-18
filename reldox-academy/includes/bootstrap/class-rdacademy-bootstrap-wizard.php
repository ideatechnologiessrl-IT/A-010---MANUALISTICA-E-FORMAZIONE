<?php
/**
 * Rdacademy_Bootstrap_Wizard — pagina di bootstrap dello stato `needs_db` (FASE 0 install).
 *
 * Copiato da Reldox_Bootstrap_Wizard, RelDox CRD 3.55.09G, rinominato. TAGLIO:
 * rewrite_base_defines() (writer programmatico del percorso di reset, escluso
 * dalla SPEC § 0). Il resto e identico: test BASE, sonda «DB gia usato?»,
 * scrittura diretta / FTP / manuale, guardia «BASE ≠ DB di WordPress».
 *
 * COMPITO (patch 2 · 2b): quando mancano le costanti RDACADEMY_DB_* (installazione a
 * zero su ambiente vergine), questa classe rende la PAGINA WIZARD e promuove le
 * Sonde 70 A/B da diagnostica a WRITER VERO di wp-config.php. Scrive UN SOLO set
 * RDACADEMY_DB_* = BASE (_2): la connessione singola che il plugin apre all'avvio.
 * Gli altri DB vivono in data_sources (enabled DEFAULT 0), risolti da
 * Rdacademy_DataSource — cablati in 2b-contorno, a BASE viva.
 *
 * SICUREZZA (paletto owner, S20):
 *  - Il writer si attiva SOLO in stato needs_db (costanti assenti). Un'installazione
 *    di lavoro già configurata NON entra mai qui → wp-config non viene mai toccato.
 *  - Idempotenza «salta + recupera»: se RDACADEMY_DB_HOST è già presente nel file, il
 *    writer NON scrive nulla e segnala «già configurato». Mai sovrascrivere.
 *
 * WIRING (accertato a sorgente, RP-DIAG-NO-ASSUME-WIRING):
 *  - Auto-contenuta: il ramo needs_db in reldox-academy.php ritorna PRIMA dei require
 *    delle classi DB, quindi il test connessione richiede Rdacademy_DB/Rdacademy_DB_Config
 *    on-demand nell'endpoint.
 *  - render_wpconfig_block() legge da self::get() (costanti già definite): NON
 *    utilizzabile in needs_db. Qui il blocco si costruisce dai valori del form,
 *    riusando la sola logica di escape single-quote.
 *  - wp-config in FTP = cartella PADRE di find_folder(WP_CONTENT_DIR) (su Aruba
 *    find_folder(ABSPATH) torna vuoto). Delete file temporaneo forzato tipo 'f'.
 *
 * Contratto grafico: MOCKUP-wizard-fase0-v3.html (RP-MOCKUP-CONTRACT), Step 1+2.
 *
 * @since 3.15.50 (patch 2 · 2b)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rdacademy_Bootstrap_Wizard {

	/** Slug pagina admin. */
	const PAGE_SLUG = 'rdacademy-bootstrap';

	/** Azione nonce. */
	const NONCE = 'rdacademy_boot_wizard';

	/** Marcatori del blocco iniettato (idempotenza + rimozione pulita). */
	const MARK_BEGIN = '/* BEGIN RelDoxAcademy DB */';
	const MARK_END   = '/* END RelDoxAcademy DB */';

	/** Marcatore standard di wp-config: punto d'iniezione preferito. */
	const WP_MARKER = "That's all, stop editing";

	/** Costante-sentinella per l'idempotenza. */
	const SENTINEL = 'RDACADEMY_DB_HOST';

	/**
	 * Registra pagina, avviso e endpoint AJAX. Chiamata SOLO dal ramo needs_db.
	 */
	public static function boot(): void {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}
		add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'wp_ajax_rdacademy_boot_testbase',     array( __CLASS__, 'ajax_testbase' ) );
		add_action( 'wp_ajax_rdacademy_boot_write_direct', array( __CLASS__, 'ajax_write_direct' ) );
		add_action( 'wp_ajax_rdacademy_boot_write_ftp',    array( __CLASS__, 'ajax_write_ftp' ) );
		// §3.5 (3.15.53), ribaltato in avviso a 3.15.62 (decisione owner S32): il probe resta e serve a
		// VERIFICARE che il DB sia vergine; la pulizia non e' piu' offerta (si esegue dal pannello hosting).
		add_action( 'wp_ajax_rdacademy_boot_probe_tables', array( __CLASS__, 'ajax_probe_tables' ) );
	}

	/** Pagina top-level: unica raggiungibile finché la BASE non è in wp-config. */
	public static function register_page(): void {
		add_menu_page(
			RDACADEMY_PRODUCT . ' — Installazione',
			RDACADEMY_PRODUCT . ' — Installazione',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' ),
			'dashicons-database-add',
			3
		);
	}

	/** Avviso admin che rimanda al wizard (sostituisce le istruzioni manuali sparse). */
	public static function notice(): void {
		if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && isset( $screen->id ) && strpos( (string) $screen->id, self::PAGE_SLUG ) !== false ) {
			return; // Sulla pagina wizard l'avviso è ridondante.
		}
		$url = esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		echo '<div class="notice notice-warning"><p><strong>'
			. esc_html( RDACADEMY_PRODUCT . ' — configurazione database mancante.' )
			. '</strong> '
			. wp_kses(
				sprintf(
					/* translators: %s = URL della pagina wizard */
					__( 'Completa l\'installazione dalla <a href="%s">procedura guidata</a>.', 'reldox-academy' ),
					$url
				),
				array( 'a' => array( 'href' => array() ) )
			)
			. '</p></div>';
	}

	/* ============================ HELPERS ============================ */

	/** Gate comune agli endpoint: capability + nonce. */
	private static function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permesso negato.' );
		}
		check_ajax_referer( self::NONCE, 'nonce' );
	}

	/** Escape per stringa PHP single-quoted (solo backslash e apice singolo). */
	private static function esc_php( string $v ): string {
		return str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $v );
	}

	/** Fonde la porta nell'host (host:port), come render_wpconfig_block/produzione. */
	private static function host_with_port( string $host, string $port ): string {
		$host = trim( $host );
		$port = trim( $port );
		if ( $host === '' || $port === '' || strpos( $host, ':' ) !== false ) {
			return $host;
		}
		return $host . ':' . $port;
	}

	/** Raccoglie e normalizza i valori BASE dal POST. */
	private static function collect(): array {
		$host    = isset( $_POST['host'] ) ? trim( (string) wp_unslash( $_POST['host'] ) ) : '';
		$port    = isset( $_POST['port'] ) ? trim( (string) wp_unslash( $_POST['port'] ) ) : '';
		$name    = isset( $_POST['name'] ) ? trim( (string) wp_unslash( $_POST['name'] ) ) : '';
		$user    = isset( $_POST['user'] ) ? trim( (string) wp_unslash( $_POST['user'] ) ) : '';
		$pass    = isset( $_POST['pass'] ) ? (string) wp_unslash( $_POST['pass'] ) : '';
		$charset = isset( $_POST['charset'] ) ? trim( (string) wp_unslash( $_POST['charset'] ) ) : 'utf8mb4';
		if ( $charset === '' ) {
			$charset = 'utf8mb4';
		}
		return array(
			'host'    => self::host_with_port( $host, $port ),
			'name'    => $name,
			'user'    => $user,
			'pass'    => $pass,
			'charset' => $charset,
		);
	}

	/** Costruisce il blocco RDACADEMY_DB_* (BASE) dai valori del form. */
	private static function build_block( array $v ): string {
		$lines = array(
			self::MARK_BEGIN,
			'/* RelDoxAcademy — credenziali database BASE (FASE 0 install) */',
			"define( 'RDACADEMY_DB_HOST',    '" . self::esc_php( $v['host'] )    . "' );",
			"define( 'RDACADEMY_DB_NAME',    '" . self::esc_php( $v['name'] )    . "' );",
			"define( 'RDACADEMY_DB_USER',    '" . self::esc_php( $v['user'] )    . "' );",
			"define( 'RDACADEMY_DB_PASS',    '" . self::esc_php( $v['pass'] )    . "' );",
			"define( 'RDACADEMY_DB_CHARSET', '" . self::esc_php( $v['charset'] ) . "' );",
			self::MARK_END,
		);
		return implode( "\n", $lines );
	}

	/** Individua wp-config.php in scrittura diretta (logica WP). */
	private static function locate_wpconfig_direct(): ?string {
		if ( @file_exists( ABSPATH . 'wp-config.php' ) ) {
			return ABSPATH . 'wp-config.php';
		}
		$up = dirname( ABSPATH ) . '/wp-config.php';
		if ( @file_exists( $up ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			return $up;
		}
		return null;
	}

	/** True se il contenuto contiene già le costanti RDACADEMY_DB_* (idempotenza). */
	private static function already_configured( string $content ): bool {
		return ( strpos( $content, self::SENTINEL ) !== false );
	}

	/**
	 * Inietta il blocco: PRIMA della riga del marcatore «stop editing», altrimenti
	 * append in coda. Ritorna il nuovo contenuto.
	 */
	private static function inject( string $content, string $block ): string {
		$pos = strpos( $content, self::WP_MARKER );
		if ( $pos !== false ) {
			$before   = substr( $content, 0, $pos );
			$nl       = strrpos( $before, "\n" );
			$insertAt = ( $nl === false ) ? 0 : $nl + 1;
			return substr( $content, 0, $insertAt )
				. $block . "\n\n"
				. substr( $content, $insertAt );
		}
		return rtrim( $content, "\n" ) . "\n\n" . $block . "\n";
	}

	/**
	 * M1(b) AP-WIZARD-CORREGGE-BASE — rimuove QUALSIASI configurazione BASE esistente:
	 * prima il blocco marcato MARK_BEGIN…MARK_END (scritto da questo wizard), poi, per
	 * sicurezza, eventuali define RDACADEMY_DB_* residui (config legacy senza marker).
	 * NON tocca altro in wp-config. Ritorna il contenuto ripulito.
	 */
	private static function strip_db_defines( string $content ): string {
		$b = strpos( $content, self::MARK_BEGIN );
		$e = strpos( $content, self::MARK_END );
		if ( $b !== false && $e !== false && $e > $b ) {
			$e2   = $e + strlen( self::MARK_END );
			$tail = ltrim( substr( $content, $e2 ), "\r\n" );
			$content = rtrim( substr( $content, 0, $b ), " \t" ) . $tail;
		}
		// Rete di sicurezza: define RDACADEMY_DB_* non marcati (config scritta a mano).
		$content = preg_replace(
			'/^[ \t]*define\(\s*[\'"]RDACADEMY_DB_(?:HOST|NAME|USER|PASS|CHARSET)[\'"].*?\);[ \t]*\r?\n?/mi',
			'',
			$content
		);
		return is_string( $content ) ? $content : '';
	}

	/**
	 * M1(b) — sostituisce il blocco BASE: ripulisce il vecchio e reinietta quello nuovo
	 * (marcato) col contratto di inject(). Usato dal ramo «rewrite» dei writer per
	 * correggere una BASE gia' scritta e sbagliata (es. finita sul DB del core).
	 */
	private static function replace_block( string $content, string $block ): string {
		return self::inject( self::strip_db_defines( $content ), $block );
	}

	/** True se la richiesta e' una correzione esplicita (mode=rewrite). */
	private static function is_rewrite(): bool {
		return isset( $_POST['mode'] ) && 'rewrite' === (string) wp_unslash( $_POST['mode'] );
	}

	/** True se la conferma esplicita di riscrittura e' presente (confirm=REWRITE). */
	private static function rewrite_confirmed(): bool {
		return isset( $_POST['confirm'] ) && 'REWRITE' === (string) wp_unslash( $_POST['confirm'] );
	}

	/* ============================ AJAX: TEST BASE ============================ */

	/** Prova la connessione BASE riusando Rdacademy_DB::try_connect (override esplicito). */
	public static function ajax_testbase(): void {
		self::guard();

		$v = self::collect();
		if ( $v['host'] === '' || $v['name'] === '' || $v['user'] === '' ) {
			wp_send_json_error( 'Host, nome database e utente sono obbligatori.' );
		}
		// M1(a) AP-WIZARD-BASE-NON-CORE: la BASE non puo' MAI essere il DB del core
		// WP (DB_NAME). Rifiuto gia' al test, prima di connettere: e' la radice del
		// blocco S22 (il wizard aveva accettato Sql..._1 come BASE).
		if ( self::refuse_if_core( $v['name'] ) ) {
			return;
		}

		// Classi DB richieste on-demand: il ramo needs_db ritorna prima dei require globali.
		require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-db-config.php';
		require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-db.php';

		$res = Rdacademy_DB::try_connect( array(
			'host'    => $v['host'],
			'name'    => $v['name'],
			'user'    => $v['user'],
			'pass'    => $v['pass'],
			'charset' => $v['charset'],
		) );

		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( 'Connessione fallita: ' . ( $res['error_message'] ?? 'errore sconosciuto' ) );
		}
		if ( $res['connection'] instanceof mysqli ) {
			@$res['connection']->close();
		}
		wp_send_json_success( array(
			'label'      => 'Connessione BASE',
			'latency_ms' => $res['latency_ms'] ?? null,
		) );
	}

	/* ==================== §3.5 SELETTORE «DB GIÀ USATO?» (BASE _2) ==================== */

	/**
	 * Guardia core condivisa da probe/wipe: il DB bersaglio non può MAI essere il
	 * core WP (`DB_NAME`). Vince sempre (SPEC §2.3, decisione owner S22). Ritorna
	 * true se ha già inviato la risposta d'errore (il chiamante deve fermarsi).
	 */
	private static function refuse_if_core( string $name ): bool {
		if ( defined( 'DB_NAME' ) && $name === DB_NAME ) {
			wp_send_json_error( array(
				'core'    => true,
				'message' => 'Il DB indicato coincide con il database del core WordPress (DB_NAME). '
					. 'La pulizia è vietata: correggi la configurazione e punta la BASE su un DB dedicato (_2).',
			) );
			return true;
		}
		return false;
	}

	/** Apre la connessione BASE dai valori del form. Ritorna mysqli o invia errore JSON. */
	private static function open_base_or_fail( array $v ) {
		require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-db-config.php';
		require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-db.php';
		$res = Rdacademy_DB::try_connect( array(
			'host'    => $v['host'],
			'name'    => $v['name'],
			'user'    => $v['user'],
			'pass'    => $v['pass'],
			'charset' => $v['charset'],
		) );
		if ( empty( $res['ok'] ) || ! ( $res['connection'] instanceof mysqli ) ) {
			wp_send_json_error( array(
				'message' => 'Connessione fallita: ' . ( $res['error_message'] ?? 'errore sconosciuto' ),
			) );
		}
		return $res['connection'];
	}

	/**
	 * Sonda le tabelle del DB BASE indicato. Ritorna conteggio + elenco (cap 500).
	 * NON tocca nulla. Guardia core sovraordinata.
	 */
	public static function ajax_probe_tables(): void {
		self::guard();
		$v = self::collect();
		if ( $v['host'] === '' || $v['name'] === '' || $v['user'] === '' ) {
			wp_send_json_error( array( 'message' => 'Host, nome database e utente sono obbligatori.' ) );
		}
		if ( self::refuse_if_core( $v['name'] ) ) {
			return;
		}

		$conn = self::open_base_or_fail( $v );

		$tables = array();
		$rs = @$conn->query( 'SHOW TABLES' );
		if ( $rs instanceof mysqli_result ) {
			while ( ( $row = $rs->fetch_row() ) !== null ) {
				$tables[] = (string) $row[0];
			}
			$rs->free();
		}
		@$conn->close();

		$count = count( $tables );
		wp_send_json_success( array(
			'db'     => $v['name'],
			'count'  => $count,
			'empty'  => ( $count === 0 ),
			'tables' => array_slice( $tables, 0, 500 ),
		) );
	}

	/*
	 * ajax_wipe_base() RIMOSSA a 3.15.62 (decisione owner S32).
	 * La pulizia di un database gia' usato non e' piu' offerta dal wizard: i database si predispongono
	 * dal pannello di controllo dell'hosting, a cura dell'utente. Il wizard verifica e avvisa soltanto
	 * (ajax_probe_tables + pannello «Database non vuoto»).
	 * NB (RP-VERIFICA-PRIMA-DI-RIMUOVERE): il percorso di reset NON dipendeva da questo metodo — la fase 40
	 * di class-rdacademy-reset-path.php ha il proprio DROP e la propria guardia core (r.409-432). refuse_if_core()
	 * RESTA: e' chiamata da ajax_testbase, ajax_probe_tables, ajax_write_direct e ajax_write_ftp.
	 */

	/* ============================ AJAX: WRITER DIRETTA ============================ */

	/** Promuove Sonda A a writer: scrittura diretta di wp-config.php. */
	public static function ajax_write_direct(): void {
		self::guard();
		require_once ABSPATH . 'wp-admin/includes/file.php'; // get_filesystem_method()

		$v = self::collect();
		if ( $v['host'] === '' || $v['name'] === '' || $v['user'] === '' ) {
			wp_send_json_error( 'Dati BASE incompleti.' );
		}
		// M1(a) AP-WIZARD-BASE-NON-CORE: mai scrivere una BASE == DB_NAME in wp-config.
		if ( self::refuse_if_core( $v['name'] ) ) {
			return;
		}

		$path = self::locate_wpconfig_direct();
		if ( $path === null ) {
			wp_send_json_error( 'wp-config.php non individuato in scrittura diretta. Usa il metodo FTP.' );
		}

		$method   = (string) get_filesystem_method( array(), dirname( $path ), false );
		$writable = @is_writable( $path );
		if ( ! $writable || 'direct' !== $method ) {
			wp_send_json_error( array(
				'message'    => 'Scrittura diretta non disponibile (is_writable=' . ( $writable ? 'sì' : 'no' )
					. ', metodo=' . ( $method !== '' ? $method : 'n/d' ) . '). Usa il metodo FTP o il blocco manuale.',
				'need_ftp'   => true,
			) );
		}

		$content = @file_get_contents( $path );
		if ( ! is_string( $content ) ) {
			wp_send_json_error( 'Impossibile leggere wp-config.php.' );
		}

		// Idempotenza «salta + recupera»: config esistente NON si sovrascrive…
		if ( self::already_configured( $content ) ) {
			// …salvo correzione esplicita (M1(b) AP-WIZARD-CORREGGE-BASE): mode=rewrite.
			if ( ! self::is_rewrite() ) {
				wp_send_json_success( array(
					'status'  => 'exists',
					'label'   => 'wp-config già configurato',
					'message' => 'RDACADEMY_DB_HOST è già presente in wp-config.php: nessuna scrittura effettuata (configurazione recuperata). Ricarica la pagina.',
				) );
			}
			if ( ! self::rewrite_confirmed() ) {
				wp_send_json_error( array(
					'need_confirm' => true,
					'message'      => 'La correzione della BASE riscrive wp-config: richiede conferma esplicita.',
				) );
			}
			$new = self::replace_block( $content, self::build_block( $v ) );
		} else {
			$new = self::inject( $content, self::build_block( $v ) );
		}
		$ok = ( false !== @file_put_contents( $path, $new, LOCK_EX ) );
		if ( ! $ok ) {
			wp_send_json_error( 'Scrittura di wp-config.php fallita (permessi?). Usa il metodo FTP.' );
		}

		// Verifica: rileggo e confermo sentinella + presenza del NUOVO nome BASE.
		self::svuota_cache_config();
		$check   = @file_get_contents( $path );
		$name_ok = is_string( $check ) && ( strpos( $check, "'" . self::esc_php( $v['name'] ) . "'" ) !== false );
		if ( ! is_string( $check ) || ! self::already_configured( $check ) || ! $name_ok ) {
			wp_send_json_error( 'Scrittura eseguita ma verifica fallita: controlla wp-config.php manualmente.' );
		}

		wp_send_json_success( array(
			'status'  => self::is_rewrite() ? 'rewritten' : 'written',
			'label'   => self::is_rewrite() ? 'BASE corretta (diretta)' : 'BASE scritta (diretta)',
			'path'    => $path,
			'message' => 'Credenziali BASE scritte in wp-config.php.',
		) );
	}

	/**
	 * 0.6.03A — dopo la scrittura di wp-config.php la cache del codice (OPcache) può servire ancora
	 * il file vecchio, senza credenziali, finché non lo ricontrolla (sul palco 2 s, sugli hosting fino
	 * a 60 s e oltre): in quella finestra «Vai a Primo accesso» trovava il prodotto spento e WordPress
	 * rispondeva «non hai i permessi». Si toglie dalla cache il file appena scritto.
	 */
	private static function svuota_cache_config(): void {
		if ( ! function_exists( 'opcache_invalidate' ) ) {
			return;
		}
		foreach ( array( ABSPATH . 'wp-config.php', dirname( ABSPATH ) . '/wp-config.php' ) as $f ) {
			if ( @is_file( $f ) ) {
				@opcache_invalidate( $f, true );
			}
		}
	}

	/* ============================ AJAX: WRITER FTP/FTPS ============================ */

	/** Promuove Sonda B a writer: iniezione via WP_Filesystem FTP/FTPS. */
	public static function ajax_write_ftp(): void {
		self::guard();

		$v = self::collect();
		if ( $v['host'] === '' || $v['name'] === '' || $v['user'] === '' ) {
			wp_send_json_error( 'Dati BASE incompleti.' );
		}
		// M1(a) AP-WIZARD-BASE-NON-CORE: mai scrivere una BASE == DB_NAME in wp-config.
		if ( self::refuse_if_core( $v['name'] ) ) {
			return;
		}

		$fhost = isset( $_POST['fhost'] ) ? trim( (string) wp_unslash( $_POST['fhost'] ) ) : '';
		$fport = isset( $_POST['fport'] ) ? (int) $_POST['fport'] : 21;
		$fuser = isset( $_POST['fuser'] ) ? trim( (string) wp_unslash( $_POST['fuser'] ) ) : '';
		$fpass = isset( $_POST['fpass'] ) ? (string) wp_unslash( $_POST['fpass'] ) : '';
		$fssl  = ! empty( $_POST['fssl'] );
		if ( $fhost === '' || $fuser === '' || $fpass === '' ) {
			wp_send_json_error( 'Host, utente e password FTP sono obbligatori.' );
		}
		if ( $fport <= 0 ) {
			$fport = 21;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';

		// Costanti FS_* necessarie istanziando la classe FTP a mano (identiche a WP core).
		if ( ! defined( 'FS_CONNECT_TIMEOUT' ) ) { define( 'FS_CONNECT_TIMEOUT', 30 ); }
		if ( ! defined( 'FS_TIMEOUT' ) )         { define( 'FS_TIMEOUT', 30 ); }
		if ( ! defined( 'FS_CHMOD_DIR' ) )       { define( 'FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) ); }
		if ( ! defined( 'FS_CHMOD_FILE' ) )      { define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) ); }

		try {
			if ( extension_loaded( 'ftp' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpext.php';
				$fs = new WP_Filesystem_FTPext( array(
					'hostname'        => $fhost,
					'port'            => $fport,
					'username'        => $fuser,
					'password'        => $fpass,
					'connection_type' => $fssl ? 'ftps' : 'ftp',
				) );
			} else {
				require_once ABSPATH . 'wp-admin/includes/class-ftp.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpsockets.php';
				$fs = new WP_Filesystem_FTPsockets( array(
					'hostname' => $fhost,
					'port'     => $fport,
					'username' => $fuser,
					'password' => $fpass,
				) );
			}

			if ( is_wp_error( $fs->errors ) && $fs->errors->get_error_code() ) {
				wp_send_json_error( 'Init FTP: ' . $fs->errors->get_error_message() );
			}
			if ( ! $fs->connect() ) {
				$msg = ( is_wp_error( $fs->errors ) && $fs->errors->get_error_message() )
					? $fs->errors->get_error_message()
					: 'connessione o login FTP falliti (host/porta/credenziali?)';
				wp_send_json_error( 'FTP: ' . $msg );
			}

			// wp-config = cartella PADRE di find_folder(WP_CONTENT_DIR); fallback ABSPATH.
			$wpc_remote     = defined( 'WP_CONTENT_DIR' ) ? $fs->find_folder( WP_CONTENT_DIR ) : false;
			$abspath_remote = $fs->find_folder( ABSPATH );
			$cfg_path       = '';
			if ( $wpc_remote ) {
				$cfg_path = trailingslashit( dirname( untrailingslashit( $wpc_remote ) ) ) . 'wp-config.php';
			}
			if ( ( $cfg_path === '' || ! $fs->exists( $cfg_path ) ) && $abspath_remote ) {
				$cfg_path = trailingslashit( $abspath_remote ) . 'wp-config.php';
			}
			if ( $cfg_path === '' || ! $fs->exists( $cfg_path ) ) {
				wp_send_json_error( 'wp-config.php non raggiungibile via FTP. Indica una base remota o usa il blocco manuale.' );
			}

			$cfg_dir = trailingslashit( dirname( untrailingslashit( $cfg_path ) ) );

			// Pre-verifica: prova di scrittura su file TEMPORANEO nella cartella target.
			$probe = $cfg_dir . 'rdacademy-cfgwrite-probe-' . wp_generate_password( 10, false ) . '.txt';
			$token = 'rdacademy cfgwrite ' . time();
			if ( ! $fs->put_contents( $probe, $token, false ) ) {
				wp_send_json_error( 'La cartella di wp-config non è scrivibile via FTP (' . $cfg_dir . ').' );
			}
			$back = $fs->get_contents( $probe );
			$fs->delete( $probe, false, 'f' ); // delete forzato tipo file
			if ( ! is_string( $back ) || $back !== $token ) {
				wp_send_json_error( 'Verifica scrittura FTP fallita (contenuto non coerente).' );
			}

			// Lettura wp-config remoto.
			$content = $fs->get_contents( $cfg_path );
			if ( ! is_string( $content ) ) {
				wp_send_json_error( 'Impossibile leggere wp-config.php via FTP.' );
			}

			// Idempotenza «salta + recupera»… salvo correzione esplicita (M1(b)).
			if ( self::already_configured( $content ) ) {
				if ( ! self::is_rewrite() ) {
					wp_send_json_success( array(
						'status'  => 'exists',
						'label'   => 'wp-config già configurato',
						'message' => 'RDACADEMY_DB_HOST è già presente: nessuna scrittura effettuata. Ricarica la pagina.',
					) );
				}
				if ( ! self::rewrite_confirmed() ) {
					wp_send_json_error( array(
						'need_confirm' => true,
						'message'      => 'La correzione della BASE riscrive wp-config: richiede conferma esplicita.',
					) );
				}
				$new = self::replace_block( $content, self::build_block( $v ) );
			} else {
				$new = self::inject( $content, self::build_block( $v ) );
			}
			if ( ! $fs->put_contents( $cfg_path, $new, false ) ) {
				wp_send_json_error( 'Scrittura di wp-config.php via FTP fallita.' );
			}

			self::svuota_cache_config();
			// Verifica: rilettura remota — sentinella + presenza del NUOVO nome BASE.
			$check   = $fs->get_contents( $cfg_path );
			$name_ok = is_string( $check ) && ( strpos( $check, "'" . self::esc_php( $v['name'] ) . "'" ) !== false );
			if ( ! is_string( $check ) || ! self::already_configured( $check ) || ! $name_ok ) {
				// Tentativo di ripristino dell'originale.
				$fs->put_contents( $cfg_path, $content, false );
				wp_send_json_error( 'Scrittura FTP non verificata: originale ripristinato. Usa il blocco manuale.' );
			}

			wp_send_json_success( array(
				'status'  => self::is_rewrite() ? 'rewritten' : 'written',
				'label'   => self::is_rewrite() ? 'BASE corretta (FTP)' : 'BASE scritta (FTP)',
				'path'    => $cfg_path,
				'message' => 'Credenziali BASE scritte in wp-config.php via FTP.',
			) );

		} catch ( \Throwable $e ) {
			wp_send_json_error( 'FTP (eccezione ' . get_class( $e ) . '): ' . $e->getMessage() );
		}
	}

	// TAGLIO: rewrite_base_defines() — writer programmatico del percorso di reset (RelDox M4).

	/* ============================ RENDER ============================ */

	/** Rende la pagina wizard (Step 1 + Step 2, contratto MOCKUP-wizard-fase0-v3). */
	public static function render(): void {
		$nonce     = wp_create_nonce( self::NONCE );
		$ajax_url  = admin_url( 'admin-ajax.php' );
		// «Avvia il prodotto» porta al login /academy/. Scritto per esteso perche' in needs_db
		// la costante RDACADEMY_APP_SLUG non e' ancora definita (e' oltre il return di reldox-academy.php).
		$after_url = home_url( '/academy/' );
		require RDACADEMY_PLUGIN_DIR . 'includes/bootstrap/view-bootstrap-wizard.php';
	}
}
