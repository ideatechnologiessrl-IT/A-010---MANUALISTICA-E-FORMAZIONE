<?php
/**
 * Classe Rdacademy_Admin
 * Registra il menu in WordPress e la pagina «Stato sistema».
 *
 * Copiato da Reldox_Admin, RelDox CRD 3.55.09G, e ridotto alla pagina minima
 * del lotto A (SPEC § 4): intestazione, tre riquadri (database, tabelle,
 * utenti) e le informazioni di installazione. TAGLI: riquadro «Aziende», elenco
 * delle sorgenti _3/_4/_5 (Reldox_DataSource, lotto D), «Copia configurazione»
 * e credenziali del primo accesso (le completa il lotto B), emoji nelle schede.
 *
 * NUOVO — INNESTI PER IL LOTTO B, che non tocca questo file:
 *  - il menu ha slug `rdacademy`: B aggiunge le sue voci con add_submenu_page( 'rdacademy', … );
 *  - se qualcuno aggancia l azione `rdacademy_admin_stato_sistema`, la pagina la
 *    esegue AL POSTO della resa minima (B consegna lo Stato sistema completo).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Admin {

    /** Slug del menu di primo livello (SPEC § 1). */
    const SLUG = 'rdacademy';

    /** Le tabelle del nucleo: una sola lista, quella dello Schema Flag Guard. */
    private function tabelle_nucleo(): array {
        return Rdacademy_Schema_Flag_Guard::tables_for( 'Rdacademy_Activator' );
    }

    public function init(): void {
        add_action( 'admin_menu',    [ $this, 'register_menu' ] );
        add_action( 'admin_notices', [ $this, 'show_welcome_notice' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * S240 di RelDox — L ICONA DEL MENU E IL SOLE DEL PRODOTTO, E RESTA A COLORI.
     * Una data-URI e non un URL: WordPress non ricolora la data-URI, mentre un
     * <img> lo spegne in grigio. Se il file manca si torna alla dashicon.
     */
    private function menu_icon(): string {
        $svg = RDACADEMY_PLUGIN_DIR . 'public/img/logo/reldox-favicon.svg';
        if ( ! is_readable( $svg ) ) return 'dashicons-database';
        $raw = file_get_contents( $svg );
        if ( false === $raw || '' === trim( (string) $raw ) ) return 'dashicons-database';
        return 'data:image/svg+xml;base64,' . base64_encode( $raw );
    }

    public function register_menu(): void {
        add_menu_page(
            RDACADEMY_PRODUCT,
            RDACADEMY_PRODUCT,
            'manage_options',
            self::SLUG,
            [ $this, 'render_status_page' ],
            $this->menu_icon(),
            31
        );

        add_submenu_page(
            self::SLUG,
            'Stato sistema',
            'Stato sistema',
            'manage_options',
            self::SLUG,
            [ $this, 'render_status_page' ]
        );
    }

    /**
     * Carica il CSS per le pagine admin del prodotto.
     */
    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, self::SLUG ) === false ) return;

        // 0.1.04A — SPEC-GRAFICA-RELDOX-v5 § 14: il foglio comune di famiglia PRIMA dei fogli del prodotto (dipendenza).
        wp_register_style( 'reldox-grafica', RDACADEMY_PLUGIN_URL . 'public/css/reldox-grafica.css', [], RDACADEMY_VERSION );
        wp_enqueue_style(
            'rdacademy-admin',
            RDACADEMY_PLUGIN_URL . 'admin/css/admin.css',
            [ 'reldox-grafica' ],
            RDACADEMY_VERSION
        );
    }

    /**
     * Mostra il messaggio di benvenuto dopo l attivazione.
     */
    public function show_welcome_notice(): void {
        if ( ! get_option( 'rdacademy_just_activated' ) ) return;

        delete_option( 'rdacademy_just_activated' );

        $tables_ok = $this->check_tables();
        $class     = $tables_ok ? 'notice-success' : 'notice-error';
        $message   = $tables_ok
            ? '<strong>' . esc_html( RDACADEMY_PRODUCT ) . ' attivato correttamente.</strong> Il database BASE è configurato. Utente di servizio <code>' . esc_html( RDACADEMY_SERVICE_EMAIL ) . '</code> creato <strong>senza password nota</strong>: il primo accesso passa da ' . esc_html( RDACADEMY_PRODUCT ) . ' › <strong>Primo accesso</strong>, che mostra un collegamento monouso.'
            : '<strong>' . esc_html( RDACADEMY_PRODUCT ) . ' — Errore installazione database.</strong> Verifica le costanti RDACADEMY_DB_* in wp-config.php.';

        echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>' . $message . '</p></div>';
    }

    /**
     * Pagina di stato del sistema nel pannello WordPress (resa minima del lotto A).
     */
    public function render_status_page(): void {
        // INNESTO LOTTO B: lo Stato sistema completo prende il posto di questa resa.
        if ( has_action( 'rdacademy_admin_stato_sistema' ) ) {
            do_action( 'rdacademy_admin_stato_sistema' );
            return;
        }

        $tables_ok    = $this->check_tables();
        $db_connected = $this->check_db_connection();
        $version      = get_option( 'rdacademy_version', '—' );
        $installed_at = get_option( 'rdacademy_installed_at', '—' );
        $p            = RDACADEMY_TABLE_PREFIX;

        $user_count    = 0;
        $mysql_version = '—';
        if ( $db_connected && $tables_ok ) {
            $row = Rdacademy_DB::get_row( "SELECT COUNT(*) as n FROM `{$p}users`" );
            $user_count = $row ? (int) $row['n'] : 0;
        }
        if ( $db_connected ) {
            $row = Rdacademy_DB::get_row( 'SELECT VERSION() AS v' );
            $mysql_version = $row && ! empty( $row['v'] ) ? (string) $row['v'] : '—';
        }
        $n_tab   = count( $this->tabelle_nucleo() );
        $app_url = home_url( '/' . RDACADEMY_APP_SLUG );
        $domain  = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        ?>
        <div class="wrap reldox-admin-wrap"><hr class="wp-header-end"><?php /* 0.8.10A (RF-BUG-026): gli avvisi di WordPress sopra la testata, non dentro */ ?>
            <div class="reldox-admin-header">
                <img class="reldox-admin-logo" src="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/img/logo/reldox-symbol-color.svg' ); ?>"
                     alt="" width="56" height="56"
                     style="display:inline-block;vertical-align:middle;margin:0 8px 6px 0;" />
                <h1 style="display:inline-block;vertical-align:middle;"><?php echo esc_html( RDACADEMY_PRODUCT ); ?>
                    <span class="reldox-version">v<?php echo esc_html( RDACADEMY_VERSION_LABEL ); ?></span>
                    <span class="reldox-stage" style="display:inline-block;margin-left:8px;padding:3px 10px;background:#2d4a7c;color:#fff;border-radius:4px;font-size:13px;font-weight:600;letter-spacing:0.3px;vertical-align:middle;">
                        <?php echo esc_html( RDACADEMY_STAGE ); ?>
                    </span>
                </h1>
                <p>Stato sistema — pagina minima del guscio (il lotto B la completa)</p>
            </div>

            <div class="reldox-status-grid">
                <div class="reldox-status-card <?php echo $db_connected ? 'ok' : 'error'; ?>">
                    <div class="card-title">Database BASE</div>
                    <div class="card-value"><?php echo $db_connected ? 'Connesso' : 'Errore connessione'; ?></div>
                    <div class="card-sub"><?php echo esc_html( RDACADEMY_DB_NAME ); ?></div>
                </div>
                <div class="reldox-status-card <?php echo $tables_ok ? 'ok' : 'error'; ?>">
                    <div class="card-title">Tabelle del nucleo</div>
                    <div class="card-value"><?php echo $tables_ok ? 'Installate' : 'Mancanti'; ?></div>
                    <div class="card-sub"><?php echo (int) $n_tab; ?> tabelle richieste</div>
                </div>
                <div class="reldox-status-card ok">
                    <div class="card-title">Utenti</div>
                    <div class="card-value"><?php echo (int) $user_count; ?></div>
                    <div class="card-sub">Registrati nel prodotto</div>
                </div>
            </div>

            <div class="reldox-info-box">
                <h3>Informazioni installazione</h3>
                <table class="reldox-info-table">
                    <tr><td>Dominio installazione</td><td><strong><?php echo esc_html( $domain ); ?></strong></td></tr>
                    <tr><td>Apertura applicazione</td><td><a href="<?php echo esc_url( $app_url ); ?>/" target="_blank" rel="noopener"><strong><?php echo esc_html( $app_url ); ?>/</strong></a></td></tr>
                    <tr><td>Versione plugin</td><td><strong><?php echo esc_html( RDACADEMY_VERSION_LABEL ); ?></strong></td></tr>
                    <?php if ( '—' !== $version && $version !== RDACADEMY_VERSION ) : ?>
                    <tr><td>Versione registrata a DB</td><td><code><?php echo esc_html( $version ); ?></code> — diverge dal codice</td></tr>
                    <?php endif; ?>
                    <tr><td>Pacchetto</td><td><code><?php echo esc_html( rdacademy_pacchetto_etichetta( 120, false ) ); ?></code></td></tr>
                    <tr><td>Build del</td><td><?php echo esc_html( RDACADEMY_PACKAGE_DATE ); ?></td></tr>
                    <tr><td>Installato il</td><td><?php echo esc_html( (string) $installed_at ); ?></td></tr>
                    <tr><td>Prefisso tabelle</td><td><code><?php echo esc_html( RDACADEMY_TABLE_PREFIX ); ?></code></td></tr>
                    <tr><td>PHP</td><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
                    <tr><td>MySQL</td><td><?php echo esc_html( $mysql_version ); ?></td></tr>
                    <tr><td>WordPress</td><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Verifica la connessione al DB BASE.
     * ⛔ In RelDox un errore di connessione finiva in wp_die() dentro Reldox_DB:
     * qui si prova con try_connect(), che non muore, cosi la pagina resta su.
     */
    private function check_db_connection(): bool {
        $r = Rdacademy_DB::try_connect();
        if ( ! empty( $r['connection'] ) && $r['connection'] instanceof mysqli ) {
            @$r['connection']->close();
        }
        return ! empty( $r['ok'] );
    }

    /**
     * Verifica che TUTTE le tabelle del nucleo esistano (in RelDox: la sola `users`).
     */
    private function check_tables(): bool {
        if ( ! $this->check_db_connection() ) {
            return false;
        }
        foreach ( $this->tabelle_nucleo() as $t ) {
            if ( Rdacademy_Schema_Flag_Guard::table_exists( $t ) !== true ) {
                return false;
            }
        }
        return true;
    }
}
