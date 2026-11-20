<?php
/**
 * Classe Rdacademy_Stato_Sistema — la pagina wp-admin «Stato sistema» completa.
 *
 * Copiato da Reldox_Admin::render_status_page() (e dai suoi check_tables /
 * check_db_connection), RelDox CRD 3.55.09G. Il lotto A tiene la voce di menu
 * e una resa minima; questa classe aggancia l azione `rdacademy_admin_stato_sistema`
 * che Rdacademy_Admin::render_status_page() esegue AL POSTO della resa minima.
 *
 * TAGLI rispetto a RelDox:
 *  - il riquadro «Aziende» (companies, CRM): il conteggio di dominio del guscio
 *    e quello degli accessi registrati nel Log accessi;
 *  - le sorgenti _3/_4/_5 lette da Reldox_DataSource (lotto D): qui il filtro
 *    `rdacademy_stato_sistema_sorgenti` (NUOVO) le lascia aggiungere a chi le porta;
 *  - il lockup «RelDox — illumina il tuo lavoro» (nome del CRM): il Sole del kit
 *    con il nome che viene da RDACADEMY_PRODUCT;
 *  - le emoji nelle schede e nei titoli (REGOLE § 4: icone SVG a tratto).
 *
 * CORRETTO RISPETTO A RELDOX:
 *  - il riquadro delle tabelle diceva «16 tabelle richieste» scritto a mano e
 *    controllava la sola `users`: qui il numero e la verifica vengono dalla
 *    stessa lista (Schema Flag Guard del nucleo + ancora di licenza);
 *  - check_db_connection() in RelDox passava da Reldox_DB::get_row(), che a
 *    connessione caduta finisce in wp_die(): la pagina che deve funzionare
 *    «anche quando l app non funziona» moriva. Qui si prova con try_connect().
 *
 * RelDoxAcademy (Formazione F009, 18/11/2026) — copiata dalla base di partenza (1.24.11L), RIDOTTA
 * (analisi tecnica ed. 7, allegato A): tolti i rami della ristorazione (invii del
 * registro, cartella dati, integrazione API, novità per l integratore: classi tolte)
 * e lo stato della licenza da firmare con il collegamento alla sua pagina; della
 * licenza resta solo l ancora di dominio. Tenuta la guardia della cache (carico-e-143).
 *
 * ⛔ Il blocco «Copia configurazione» si costruisce LATO SERVER (BUG-11 di
 * RelDox: niente lettura del DOM) e non contiene MAI password o utente del DB:
 * delle credenziali si dicono solo nome del database e host (REGOLE § 5).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// R5 (SPEC-MANUTENZIONE-E-DIAGNOSTICA § 6): la pagina legge Rdacademy_Accesso_Emergenza::PAGE_SLUG.
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-accesso-emergenza.php';

final class Rdacademy_Stato_Sistema {

	public static function init(): void {
		add_action( 'rdacademy_admin_stato_sistema', array( __CLASS__, 'render' ) );
	}

	/* ================================================================= *
	 *  Misure (copiate da Reldox_Admin, corrette)
	 * ================================================================= */

	/** Le tabelle richieste: il nucleo dichiarato dallo Schema Flag Guard + l ancora di licenza. */
	public static function tabelle_richieste(): array {
		$t = Rdacademy_Schema_Flag_Guard::tables_for( 'Rdacademy_Activator' );
		$t[] = Rdacademy_Schema_License_V1::TABLE;
		// Chi porta tabelle sue le aggiunge con il filtro.
		$t = function_exists( 'apply_filters' ) ? (array) apply_filters( 'rdacademy_stato_tabelle_richieste', $t ) : $t;
		return array_values( array_unique( array_map( 'strval', $t ) ) );
	}

	private static function check_db_connection(): bool {
		$r = Rdacademy_DB::try_connect();
		if ( ! empty( $r['connection'] ) && $r['connection'] instanceof mysqli ) {
			@$r['connection']->close();
		}
		return ! empty( $r['ok'] );
	}

	/** @return array{ok:bool,mancanti:string[]} */
	private static function check_tables( bool $db_connected ): array {
		if ( ! $db_connected ) {
			return array( 'ok' => false, 'mancanti' => self::tabelle_richieste() );
		}
		$mancanti = array();
		foreach ( self::tabelle_richieste() as $t ) {
			if ( Rdacademy_Schema_Flag_Guard::table_exists( $t ) !== true ) {
				$mancanti[] = $t;
			}
		}
		// La verifica dello schema conta anche le COLONNE nuove («tabella.colonna»), portate dal filtro da chi le aggiunge.
		foreach ( (array) apply_filters( 'rdacademy_stato_schema_mancanti', array() ) as $c ) {
			if ( is_string( $c ) && strpos( $c, '.' ) !== false && ! in_array( $c, $mancanti, true ) ) $mancanti[] = $c;
		}
		return array( 'ok' => ! $mancanti, 'mancanti' => $mancanti );
	}

	/** Un COUNT(*) che non si traveste da zero quando fallisce (REGOLE § 6). */
	private static function conta( string $tabella_corta ): ?int {
		$p = RDACADEMY_TABLE_PREFIX;
		try {
			$row = Rdacademy_DB::get_row( "SELECT COUNT(*) AS n FROM `{$p}{$tabella_corta}`" );
		} catch ( Throwable $e ) {
			return null;
		}
		return $row ? (int) $row['n'] : null;
	}

	/**
	 * Tutti i dati della pagina, misurati UNA volta: la pagina e il blocco di
	 * copia leggono la stessa fotografia (BUG-11 di RelDox: una verita sola).
	 */
	public static function dati(): array {
		$db_connected  = self::check_db_connection();
		$tables        = self::check_tables( $db_connected );
		$mysql_version = '—';
		$user_count    = null;
		$access_count  = null;
		if ( $db_connected ) {
			$row = Rdacademy_DB::get_row( 'SELECT VERSION() AS v' );
			$mysql_version = $row && ! empty( $row['v'] ) ? (string) $row['v'] : '—';
			$user_count    = self::conta( 'users' );
			$access_count  = self::conta( 'access_log' );
		}

		// RP-VERSIONE-PANNELLO-MINIMO: elenco dei DB SQL realmente in uso.
		$db_sources   = array();
		$db_sources[] = array(
			'label'   => 'WordPress',
			'db_name' => defined( 'DB_NAME' ) ? (string) DB_NAME : '—',
			'db_host' => defined( 'DB_HOST' ) ? (string) DB_HOST : '—',
			'stato'   => 'DB proprio',
		);
		$db_sources[] = array(
			'label'   => 'BASE (verità)',
			'db_name' => defined( 'RDACADEMY_DB_NAME' ) ? (string) RDACADEMY_DB_NAME : '—',
			'db_host' => defined( 'RDACADEMY_DB_HOST' ) ? (string) RDACADEMY_DB_HOST : '—',
			'stato'   => $db_connected ? 'Connesso' : 'Errore connessione',
		);
		// NUOVO — le sorgenti 3-5 le porta chi le ha (lotto D). Si accettano solo
		// le quattro chiavi di sola lettura: una password non ha dove passare.
		foreach ( (array) apply_filters( 'rdacademy_stato_sistema_sorgenti', array() ) as $s ) {
			if ( ! is_array( $s ) ) continue;
			$db_sources[] = array(
				'label'   => (string) ( $s['label'] ?? '—' ),
				'db_name' => (string) ( $s['db_name'] ?? '' ),
				'db_host' => (string) ( $s['db_host'] ?? '' ),
				'stato'   => (string) ( $s['stato'] ?? 'Ricade sul BASE' ),
			);
		}

		// BUG-11 (RelDox 3.16.1): il dominio d installazione, il dato che mancando
		// faceva confondere copia/incolla fra installazioni diverse.
		$home   = home_url();
		$domain = wp_parse_url( $home, PHP_URL_HOST );
		if ( ! $domain ) {
			$domain = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '—';
		}

		return array(
			'db_connected'  => $db_connected,
			'tables_ok'     => $tables['ok'],
			'tables_miss'   => $tables['mancanti'],
			'tables_n'      => count( self::tabelle_richieste() ),
			'user_count'    => $user_count,
			'access_count'  => $access_count,
			'mysql_version' => $mysql_version,
			'version'       => (string) get_option( 'rdacademy_version', '—' ),
			'installed_at'  => self::ora_sito( (string) get_option( 'rdacademy_installed_at', '—' ) ),
			'home'          => $home,
			'app_url'       => home_url( '/' . RDACADEMY_APP_SLUG . '/' ),
			'domain'        => (string) $domain,
			'db_sources'    => $db_sources,
			'ancora'        => class_exists( 'Rdacademy_License_Anchor' ) ? Rdacademy_License_Anchor::state() : '—',
		);
	}

	/**
	 * 0.1.05A (BUG-3) — l ora d installazione si salva in UTC («2026-11-18 14:19:34 UTC»): a schermo e negli
	 * appunti va nell ora del sito, come in RelDox CRD. Un valore che non è una data resta com è.
	 */
	private static function ora_sito( string $utc ): string {
		$v = trim( preg_replace( '/\s*UTC$/', '', $utc ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v ) ) return $utc;
		return (string) get_date_from_gmt( $v, 'Y-m-d H:i:s' );
	}

	/** 0.1.05A (BUG-3) — l esito della guardia della cache a parole, uguale a schermo e negli appunti. */
	private static function cache_a_parole( string $esito ): string {
		$m = array( 'verde' => 'sì', 'rosso' => 'no', 'arancio' => 'sì, con un avviso' );
		return $m[ $esito ] ?? 'non misurabile';
	}

	/** 0.1.05A (BUG-3) — l ancora di dominio a parole, uguale a schermo e negli appunti. */
	private static function ancora_a_parole( string $stato ): string {
		if ( $stato === '—' ) return 'non leggibile';
		return $stato === Rdacademy_License_Anchor::STATE_NEEDS_RELICENSE ? 'da rinnovare' : 'attiva';
	}

	/**
	 * BUG-11 di RelDox — il blocco da INCOLLARE in chat, costruito lato server.
	 * Il pulsante «Copia configurazione» copia ESATTAMENTE questo testo.
	 * ⛔ Nessuna password e nessun utente del database: solo nome e host.
	 */
	public static function testo_configurazione( ?array $d = null ): string {
		$d   = $d ?? self::dati();
		$pad = static function ( string $k ): string {
			return str_pad( $k, 20, ' ', STR_PAD_RIGHT );
		};
		$n   = static function ( $v ): string {
			return $v === null ? 'non misurabile' : (string) (int) $v;
		};
		$L   = array();
		$L[] = '=== ' . strtoupper( RDACADEMY_PRODUCT ) . ' — CONFIGURAZIONE INSTALLAZIONE ===';
		$L[] = $pad( 'Dominio:' ) . $d['domain'];
		$L[] = $pad( 'URL:' ) . $d['home'];
		// S240 di RelDox — l indirizzo operativo si CALCOLA dallo slug, non si cabla.
		$L[] = $pad( 'Apertura app:' ) . $d['app_url'];
		$L[] = $pad( 'Prodotto:' ) . RDACADEMY_PRODUCT . ' — ' . RDACADEMY_VENDOR;
		$L[] = $pad( 'Versione plugin:' ) . RDACADEMY_VERSION_LABEL . ' (' . RDACADEMY_STAGE . ')';
		if ( '—' !== $d['version'] && $d['version'] !== RDACADEMY_VERSION ) {
			$L[] = $pad( 'Versione a DB:' ) . $d['version'] . ' (DIVERGE dal codice)';
		}
		$L[] = $pad( 'Pacchetto:' ) . rdacademy_pacchetto_etichetta(); // BUG-98 di RelDox: il copia-testo non porta la cronaca
		$L[] = $pad( 'Build del:' ) . RDACADEMY_PACKAGE_DATE;
		$L[] = $pad( 'Installato il:' ) . $d['installed_at'];
		$L[] = $pad( 'Prefisso tabelle:' ) . RDACADEMY_TABLE_PREFIX;
		$L[] = $pad( 'PHP:' ) . PHP_VERSION;
		$L[] = $pad( 'MySQL:' ) . $d['mysql_version'];
		$L[] = $pad( 'WordPress:' ) . get_bloginfo( 'version' );
		$L[] = $pad( 'Database BASE:' ) . ( $d['db_connected'] ? 'Connesso' : 'Errore connessione' )
			. ' (' . ( defined( 'RDACADEMY_DB_NAME' ) ? RDACADEMY_DB_NAME : '—' ) . ')';
		$L[] = $pad( 'Tabelle DB:' ) . ( $d['tables_ok'] ? 'Installate' : 'Mancanti: ' . implode( ', ', $d['tables_miss'] ) )
			. ' (' . (int) $d['tables_n'] . ' richieste)';
		$L[] = $pad( 'Utenti:' ) . $n( $d['user_count'] );
		$L[] = $pad( 'Accessi registrati:' ) . $n( $d['access_count'] );
		$L[] = $pad( 'Ancora di dominio:' ) . self::ancora_a_parole( (string) $d['ancora'] );
		if ( class_exists( 'Rdacademy_Cache_Guardia' ) ) {   // 1.4.3B — RF-BUG-028: le pagine dell app non memorizzate dalla cache
			$pc = Rdacademy_Cache_Guardia::prova();
			$L[] = $pad( 'Cache pagine:' ) . self::cache_a_parole( (string) $pc['esito'] ) . ' · ' . $pc['motivo'];
		}
		if ( class_exists( 'Rdacademy_Indurimento' ) ) {   // 0.1.07A — F32: SPEC-INDURIMENTO v2 § D, la stessa riga della pagina
			$L[] = $pad( 'Indurimento:' ) . substr( Rdacademy_Indurimento::riga(), strlen( 'Indurimento: ' ) );
		}
		$L[] = '';
		$L[] = 'Database SQL utilizzati:';
		foreach ( $d['db_sources'] as $s ) {
			$L[] = '  - ' . $s['label'] . ': '
				. ( '' !== $s['db_name'] ? $s['db_name'] : '—' )
				. ' @ ' . ( '' !== $s['db_host'] ? $s['db_host'] : '—' )
				. ' [' . $s['stato'] . ']';
		}
		$L[] = '';
		$L[] = 'Generato il: ' . current_time( 'Y-m-d H:i:s' );   // 0.1.05A (BUG-3): l ora del sito, come RelDox CRD
		return implode( "\n", $L );
	}

	/* ================================================================= *
	 *  Resa (copiata da Reldox_Admin::render_status_page)
	 * ================================================================= */

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Accesso negato: questa pagina richiede manage_options.', RDACADEMY_PRODUCT, array( 'response' => 403 ) );
		}
		$d    = self::dati();
		$txt  = self::testo_configurazione( $d );
		$n    = static function ( $v ): string {
			return $v === null ? '—' : (string) (int) $v;
		};
		$primo = admin_url( 'admin.php?page=' . Rdacademy_Accesso_Emergenza::PAGE_SLUG );
		?>
		<div class="wrap reldox-admin-wrap rdacademy-stato">
			<?php /* 0.1.04A (BUG-2) — l aspetto sta in admin/css/admin.css, dopo il foglio comune (SPEC-GRAFICA-RELDOX-v5 § 14).
			         0.1.05A (BUG-3) — la pagina come la Stato di RelDox CRD 3.75.07G (owner 19/11/2026 16:20, chat Formazione F017):
			         testata a fascia, riquadri centrati col bordo di stato, titoli con l icona; le icone sono quelle disegnate del
			         registro comune, mai emoji (SPEC-GRAFICA v5 G6). */ ?>
			<?php /* 0.8.10A (RF-BUG-026, owner 08/10 18:58) — LA TESTATA COME RELDOX: gli avvisi di WordPress vanno SOPRA (wp-header-end:
			         senza, WordPress li infila dopo il primo h1, cioè dentro la testata blu); in testa il LOGO VERO del prodotto, sotto il
			         nome con le pillole della versione e dello stadio, poi il sottotitolo. Riquadri con l'icona sopra la scritta. */ ?>
			<hr class="wp-header-end">
			<div class="reldox-admin-header rdacademy-testata">
				<span class="rdacademy-testata-targa"><img class="rdacademy-testata-logo" src="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/img/logo/reldox-academy-lockup.png' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>" alt="<?php echo esc_attr( RDACADEMY_PRODUCT ); ?>" width="720" height="182"></span>
				<h1><?php echo esc_html( RDACADEMY_PRODUCT ); ?>
					<span class="reldox-version">v<?php echo esc_html( RDACADEMY_VERSION_LABEL ); ?></span>
					<?php
					// RP-VERSIONE-PANNELLO-MINIMO (RelDox 3.15.7): nella testata va SOLO lo stadio.
					if ( defined( 'RDACADEMY_STAGE' ) && '' !== RDACADEMY_STAGE ) : ?>
						<span class="reldox-stage"><?php echo esc_html( RDACADEMY_STAGE ); ?></span>
					<?php endif; ?>
				</h1>
				<p>Corsi on line della famiglia RelDox — Pannello di controllo</p>
			</div>

			<div class="reldox-status-grid">
				<div class="reldox-status-card <?php echo $d['db_connected'] ? 'ok' : 'error'; ?>">
					<div class="card-icon card-icon-ok" aria-hidden="true"><?php echo rdacademy_icona( $d['db_connected'] ? 'spunta' : 'triangolo' ); ?></div>
					<div class="card-title">Database BASE</div>
					<div class="card-value"><?php echo $d['db_connected'] ? 'Connesso' : 'Errore connessione'; ?></div>
					<div class="card-sub"><?php echo esc_html( defined( 'RDACADEMY_DB_NAME' ) ? RDACADEMY_DB_NAME : '—' ); ?></div>
				</div>
				<div class="reldox-status-card <?php echo $d['tables_ok'] ? 'ok' : 'error'; ?>">
					<div class="card-icon card-icon-ok" aria-hidden="true"><?php echo rdacademy_icona( $d['tables_ok'] ? 'spunta' : 'triangolo' ); ?></div>
					<div class="card-title">Tabelle DB</div>
					<div class="card-value"><?php echo $d['tables_ok'] ? 'Installate' : 'Mancanti'; ?></div>
					<div class="card-sub"><?php echo (int) $d['tables_n']; ?> tabelle richieste<?php echo $d['tables_miss'] ? ' — mancano: ' . esc_html( implode( ', ', $d['tables_miss'] ) ) : ''; ?></div>
				</div>
				<div class="reldox-status-card <?php echo $d['user_count'] === null ? 'error' : 'ok'; ?>">
					<div class="card-icon" aria-hidden="true"><?php echo rdacademy_icona( $d['user_count'] !== null ? 'persone' : 'triangolo' ); ?></div>
					<div class="card-title">Utenti</div>
					<div class="card-value"><?php echo esc_html( $n( $d['user_count'] ) ); ?></div>
					<div class="card-sub">Registrati nel prodotto</div>
				</div>
				<div class="reldox-status-card <?php echo $d['access_count'] === null ? 'error' : 'ok'; ?>">
					<div class="card-icon" aria-hidden="true"><?php echo rdacademy_icona( $d['access_count'] !== null ? 'lucchetto' : 'triangolo' ); ?></div>
					<div class="card-title">Accessi</div>
					<div class="card-value"><?php echo esc_html( $n( $d['access_count'] ) ); ?></div>
					<div class="card-sub">Registrati nel Log accessi</div>
				</div>
			</div>

			<div class="reldox-info-box">
				<h3 class="rdacademy-copia-h">
					<span>Informazioni installazione</span>
					<span style="display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap">
						<button type="button" id="rdacademy-copy-config" class="button button-primary rdacademy-copia-btn">
							Copia configurazione
						</button>
						<span id="rdacademy-copy-config-done" class="rdacademy-copia-ok" role="status" aria-live="polite">Copiato</span>
					</span>
				</h3>
				<?php // Il testo che si copia: costruito qui sopra, lato server. Nascosto alla vista, non agli appunti. ?>
				<textarea id="rdacademy-config-text" readonly aria-hidden="true" tabindex="-1"
					style="position:absolute;left:-9999px;top:0;width:1px;height:1px;opacity:0;"><?php echo esc_textarea( $txt ); ?></textarea>
				<div id="rdacademy-copy-config-err" class="rdacademy-copia-ko" role="alert" hidden></div>
				<table class="reldox-info-table">
					<tr><td>Dominio installazione</td><td><strong><?php echo esc_html( $d['domain'] ); ?></strong> <span class="rdacademy-muted"><?php echo esc_html( $d['home'] ); ?></span></td></tr>
					<tr><td>Apertura applicazione</td><td><a href="<?php echo esc_url( $d['app_url'] ); ?>" target="_blank" rel="noopener"><strong><?php echo esc_html( $d['app_url'] ); ?></strong></a> <span class="rdacademy-muted">pagina funzionale d’apertura</span></td></tr>
					<tr><td>Versione plugin</td><td><strong><?php echo esc_html( RDACADEMY_VERSION_LABEL ); ?></strong> <span class="rdacademy-muted"><?php echo esc_html( RDACADEMY_STAGE ); ?></span></td></tr>
					<?php
					// Diagnostica OPcache/aggiornamento (RelDox): la riga compare SOLO se la
					// versione registrata a DB diverge dal codice caricato.
					if ( '—' !== $d['version'] && $d['version'] !== RDACADEMY_VERSION ) : ?>
						<tr><td>Versione registrata a DB</td><td><code><?php echo esc_html( $d['version'] ); ?></code> diverge dal codice</td></tr>
					<?php endif; ?>
					<?php
					// RP-PACCHETTO-ETICHETTA-BREVE-NON-INCREMENTALE (RelDox S240): qui vive il NOME
					// del pacchetto; la guardia e a valle, il troncamento e il segnale.
					$pkg  = rdacademy_pacchetto_etichetta( 120, false );
					$lung = ( mb_strlen( (string) RDACADEMY_PACKAGE ) > 120 );
					?>
					<tr><td>Pacchetto</td><td><code><?php echo esc_html( $pkg . ( $lung ? '…' : '' ) ); ?></code>
						<?php if ( $lung ) : ?><span class="rdacademy-avviso">etichetta troppo lunga: il pacchetto e un NOME, la cronaca sta nel CHANGELOG</span><?php endif; ?>
					</td></tr>
					<tr><td>Build del</td><td><?php echo esc_html( RDACADEMY_PACKAGE_DATE ); ?></td></tr>
					<tr><td>Installato il</td><td><?php echo esc_html( $d['installed_at'] ); ?></td></tr>
					<tr><td>Prefisso tabelle</td><td><code><?php echo esc_html( RDACADEMY_TABLE_PREFIX ); ?></code></td></tr>
					<tr><td>PHP</td><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
					<tr><td>MySQL</td><td><?php echo esc_html( $d['mysql_version'] ); ?></td></tr>
					<tr><td>WordPress</td><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
					<?php if ( class_exists( 'Rdacademy_Cache_Guardia' ) ) : $rx_pc = Rdacademy_Cache_Guardia::prova(); /* 1.4.3B — RF-BUG-028 */ ?>
					<tr class="rdacademy-cache-pagine" data-esito="<?php echo esc_attr( $rx_pc['esito'] ); ?>"><td>Pagine dell app non memorizzate dalla cache</td><td><span class="stato<?php echo $rx_pc['esito'] === 'verde' ? '' : ' mod'; ?>"><?php echo esc_html( self::cache_a_parole( (string) $rx_pc['esito'] ) ); /* 1.8.0B — RF-BUG-035; 0.1.04A pastiglia di stato (SPEC-GRAFICA § 7) */ ?></span>
						<span class="rdacademy-muted"><?php echo esc_html( $rx_pc['motivo'] ); ?></span></td></tr>
					<?php endif; ?>
					<tr><td>Ancora di dominio</td><td><?php echo esc_html( self::ancora_a_parole( (string) $d['ancora'] ) ); ?></td></tr>
				</table>
			</div>

			<?php if ( class_exists( 'Rdacademy_Indurimento' ) ) : $rx_in = Rdacademy_Indurimento::fotografia(); /* 0.1.07A — F32, SPEC-INDURIMENTO v2 */ ?>
			<div class="reldox-info-box rdacademy-indurimento">
				<h3><?php echo rdacademy_icona( 'lucchetto' ); ?> Indurimento dell installazione</h3>
				<p><strong><?php echo esc_html( Rdacademy_Indurimento::riga( $rx_in ) ); ?></strong></p>
				<?php if ( isset( $_GET['interruttori'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification -- solo il messaggio dopo il salvataggio ?>
				<p class="rdacademy-interruttori-esito"><strong><?php echo $_GET['interruttori'] === 'salvati' ? 'Interruttori salvati: la misura è stata rifatta.' : 'Interruttori non salvati: riprovare.'; // phpcs:ignore ?></strong></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="rdacademy-indurimento">
				<input type="hidden" name="action" value="rdacademy_interruttori">
				<?php wp_nonce_field( 'rdacademy_interruttori' ); ?>
				<div class="rdacademy-scroll">
				<table class="reldox-info-table">
					<?php foreach ( Rdacademy_Indurimento::regole() as $rx_id => $rx_titolo ) : $rx_e = $rx_in['esiti'][ $rx_id ]; ?>
					<tr data-regola="<?php echo esc_attr( $rx_id ); ?>"><td><?php echo esc_html( $rx_id . ' · ' . $rx_titolo ); ?></td>
						<td><span class="stato<?php echo $rx_e['stato'] === Rdacademy_Indurimento::A_POSTO ? '' : ' mod'; ?>"><?php echo esc_html( $rx_e['stato'] === Rdacademy_Indurimento::A_MANO ? 'da verificare a mano' : $rx_e['stato'] ); ?></span>
						<span class="rdacademy-muted"><?php echo esc_html( $rx_e['nota'] ); ?></span></td></tr>
					<?php if ( isset( Rdacademy_Interruttori::TESTI[ $rx_id ] ) ) : // 0.1.08A — F34, mockup MOCKUP-INTERRUTTORI-A1-A2-v1 approvato (owner 20/11/2026 07:49, chat Formazione F019) ?>
					<tr class="rdacademy-interruttore" data-interruttore="<?php echo esc_attr( $rx_id ); ?>"><td colspan="2"><label class="rdacademy-tog"><input type="checkbox" name="<?php echo esc_attr( strtolower( $rx_id ) ); ?>" value="1"<?php checked( Rdacademy_Interruttori::acceso( $rx_id ) ); ?>><span aria-hidden="true"></span><strong><?php echo esc_html( Rdacademy_Interruttori::TESTI[ $rx_id ][0] ); ?></strong></label> <span class="rdacademy-muted"><?php echo esc_html( Rdacademy_Interruttori::TESTI[ $rx_id ][1] ); ?></span></td></tr>
					<?php endif; ?>
					<?php endforeach; ?>
				</table>
				</div>
				<p class="rdacademy-interruttori-piede"><span class="rdacademy-muted">Le regole si leggono e basta; solo gli interruttori A1 e A2 cambiano il sito, e di serie sono spenti. Dopo il salvataggio la misura si rifà subito; poi ogni 10 minuti.</span> <button type="submit" class="button button-primary">Salva gli interruttori</button></p>
				</form>
			</div>
			<?php endif; ?>

			<?php // RP-VERSIONE-PANNELLO-MINIMO: i DB SQL in uso, in chiaro e in un colpo d occhio. ?>
			<div class="reldox-info-box">
				<h3><?php echo rdacademy_icona( 'cilindro' ); ?> Database SQL utilizzati</h3>
				<div class="rdacademy-scroll">
				<table class="reldox-info-table rdacademy-db-usati">
					<tr><td><strong>Sorgente</strong></td><td><strong>Database</strong></td><td><strong>Host</strong></td><td><strong>Stato</strong></td></tr>
					<?php foreach ( $d['db_sources'] as $s ) : ?>
						<tr>
							<td><?php echo esc_html( $s['label'] ); ?></td>
							<td><code><?php echo esc_html( '' !== $s['db_name'] ? $s['db_name'] : '—' ); ?></code></td>
							<td><?php echo esc_html( '' !== $s['db_host'] ? $s['db_host'] : '—' ); ?></td>
							<td><?php echo esc_html( $s['stato'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
				</div>
				<p class="rdacademy-muted" style="margin:10px 0 0">Una sorgente non configurata non è in uso: i suoi dati stanno sul <strong>BASE</strong>. È il comportamento previsto, non un errore.</p>
			</div>

			<div class="reldox-info-box">
				<h3><?php echo rdacademy_icona( 'lucchetto' ); ?> Utente di servizio e primo accesso</h3>
				<p>L’utente di servizio nasce <strong>senza password nota</strong>: per entrare la prima volta usa
					<a href="<?php echo esc_url( $primo ); ?>"><?php echo esc_html( RDACADEMY_PRODUCT ); ?> › Primo accesso</a>, scegli <code><?php echo esc_html( RDACADEMY_SERVICE_EMAIL ); ?></code>, premi <strong>Crea il collegamento di accesso</strong> e apri il collegamento monouso che compare.</p>
				<table class="reldox-info-table">
					<tr><td>Email</td><td><code><?php echo esc_html( RDACADEMY_SERVICE_EMAIL ); ?></code> · casella di Idea Technologies (assistenza e manutenzione)</td></tr>
					<tr><td>Password</td><td>nessuna: la scegli dal collegamento monouso (vale <?php echo (int) Rdacademy_Password_Token::MINUTI_REIMPOSTA; ?> minuti, una volta sola)</td></tr>
					<tr><td>Indirizzo d’accesso</td><td><a href="<?php echo esc_url( home_url( '/' . RDACADEMY_APP_SLUG . '/login' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( '/' . RDACADEMY_APP_SLUG . '/login' ) ); ?></a></td></tr>
				</table>
				<h4 class="rdacademy-sotto">Casella dell’utente di servizio</h4>
				<p style="margin:0">È la casella di Idea Technologies per l’assistenza e la manutenzione. L’utente di servizio opera solo in <?php echo esc_html( RDACADEMY_PRODUCT ); ?>: non dà accesso a WordPress né alle sue credenziali.</p>
			</div>
		</div>
		<script>
		(function () {
			var btn = document.getElementById('rdacademy-copy-config');
			var src = document.getElementById('rdacademy-config-text');
			var done = document.getElementById('rdacademy-copy-config-done');
			var err = document.getElementById('rdacademy-copy-config-err');
			if (!btn || !src) { return; }
			function flash() {
				if (err) { err.hidden = true; }
				if (!done) { return; }
				done.style.visibility = 'visible';
				window.setTimeout(function () { done.style.visibility = 'hidden'; }, 2200);
			}
			// Un errore resta finche non si ricarica e si puo copiare (REGOLE § 4).
			function fail() {
				if (!err) { return; }
				err.textContent = 'Copia non riuscita: il browser non permette di scrivere negli appunti. Seleziona e copia il testo qui sotto.';
				err.hidden = false;
				src.style.cssText = 'display:block;width:100%;height:220px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:13px;margin:8px 0';
				src.removeAttribute('aria-hidden');
			}
			function legacyCopy() {
				src.removeAttribute('aria-hidden');
				src.focus();
				src.select();
				src.setSelectionRange(0, src.value.length);
				var ok = false;
				try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
				src.setAttribute('aria-hidden', 'true');
				if (window.getSelection) { window.getSelection().removeAllRanges(); }
				return ok;
			}
			btn.addEventListener('click', function () {
				var text = src.value;
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(flash, function () {
						if (legacyCopy()) { flash(); } else { fail(); }
					});
				} else if (legacyCopy()) {
					flash();
				} else {
					fail();
				}
			});
		})();
		</script>
		<?php
	}
}
