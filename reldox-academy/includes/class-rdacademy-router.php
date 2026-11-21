<?php
/**
 * Classe Rdacademy_Router
 * Intercetta le richieste all URL /base e serve l applicazione del prodotto
 * completamente separata dal tema WordPress.
 *
 * Copiato da Reldox_Router, RelDox CRD 3.55.09G (18.000 righe) e ridotto al
 * nucleo (SPEC § 4, lotto A): init, rewrite + ripiego + autoriparazione, query
 * var, handle_request, serve_app, get_template, handle_ajax con la sua
 * $capability_map (default admin) e il freno, e le azioni login, logout,
 * pwd_forgot, pwd_token_check, pwd_set, get_access_log, me_prefs_get/save.
 *
 * TAGLI: tutte le azioni e le pagine del CRM (documenti, offerte, aziende,
 * contatti, posta, albero, viste, progetti/task/Gantt, forecast, AI, import,
 * protocolli, stampa, cestino, sorgenti, database, area tecnica), i redirect
 * delle liste piane, i gate dei moduli (Reldox_Modules) e della SFA.
 *
 * ⛔ CORREZIONE SPEC § 2.5 — MAPPA DEI PERMESSI DELLE PAGINE. In RelDox il
 * permesso di una pagina viveva SOLO dentro la vista (wp_die in testa al
 * file): una pagina nuova senza quella riga era aperta a tutti. Qui la mappa
 * $page_access e gemella della $capability_map delle azioni: `public`,
 * `auth`, `interno`, `admin`; una pagina non dichiarata vale `admin`. La
 * vista tiene comunque il suo gate.
 *
 * NUOVO — INNESTI PER I LOTTI C e D (marcati «INNESTO LOTTO-C»): filtri
 * `rdacademy_page_map`, `rdacademy_page_access`, `rdacademy_capability_map` e l azione
 * `rdacademy_ajax_<azione>` per le azioni che il lotto aggiunge. Il lotto non
 * riscrive le mappe: aggiunge le sue righe.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Router {

    /**
     * NUOVO — le pagine dell area Impostazioni (rail Setup). Una lista sola,
     * letta dal router e dal layout (in RelDox: $rxw_setup_pages in layout.php).
     */
    const SETUP_PAGES = array( 'preferenze', 'impostazioni', 'utenti', 'tabelle', 'log-accessi', 'area-tecnica' );

    public function init(): void {
        add_action( 'init',              [ $this, 'add_rewrite_rules' ] );
        // AP-FASE0-FLUSH-REWRITE (RelDox 3.15.58) — dead-man: verifica che le regole
        // /academy/ siano DAVVERO salvate; se mancano le rigenera da sola. Priorita 99:
        // dopo add_rewrite_rules(), cosi il flush rigenera includendo le nostre regole.
        add_action( 'init',              [ $this, 'selfheal_rewrite_rules' ], 99 );
        add_filter( 'query_vars',        [ $this, 'add_query_vars' ] );
        // ROUTING INDIPENDENTE DALLE REWRITE RULES: se la regola non e (ancora)
        // salvata, riconosce /academy/... dall URL e imposta le query var a mano.
        add_action( 'parse_request',     [ $this, 'fallback_parse_request' ] );
        add_action( 'template_redirect', [ $this, 'handle_request' ] );
        add_action( 'wp_ajax_rdacademy',        [ $this, 'handle_ajax' ] );
        add_action( 'wp_ajax_nopriv_rdacademy', [ $this, 'handle_ajax' ] );
    }

    /**
     * AP-FASE0-FLUSH-REWRITE (RelDox 3.15.58) — STRATO 1: routing di riserva.
     * Copiato senza modifiche di logica: si riconosce il percorso direttamente
     * da REQUEST_URI e si impostano le query var. Gestisce anche le
     * installazioni in sottocartella.
     *
     * @param WP $wp Istanza WP con le query var della richiesta corrente.
     */
    public function fallback_parse_request( $wp ): void {
        if ( ! is_object( $wp ) || ! isset( $wp->query_vars ) || ! is_array( $wp->query_vars ) ) {
            return;
        }
        if ( ! empty( $wp->query_vars['rdacademy_page'] ) ) {
            return;
        }

        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
        if ( $uri === '' ) {
            return;
        }
        $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
        if ( $path === '' ) {
            return;
        }

        $home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
        $home = '/' . trim( (string) $home, '/' );
        if ( $home !== '/' && strpos( $path, $home ) === 0 ) {
            $path = substr( $path, strlen( $home ) );
        }
        $path = '/' . trim( $path, '/' );

        $slug    = (string) RDACADEMY_APP_SLUG;
        $pattern = '#^/' . preg_quote( $slug, '#' ) . '(?:/([a-z0-9_-]+))?(?:/([0-9]+))?$#i';
        if ( ! preg_match( $pattern, $path, $m ) ) {
            return;
        }

        $page = ( isset( $m[1] ) && $m[1] !== '' ) ? strtolower( $m[1] ) : 'dashboard';
        $wp->query_vars['rdacademy_page'] = $page;
        if ( ! empty( $m[2] ) ) {
            $wp->query_vars['rdacademy_id'] = (int) $m[2];
        }
    }

    /**
     * AP-FASE0-FLUSH-REWRITE (RelDox 3.15.58) — STRATO 2: autoriparazione delle
     * regole. Flush SOFT, idempotente e autolimitato da un transient.
     */
    public function selfheal_rewrite_rules(): void {
        $rules = get_option( 'rewrite_rules' );
        // Permalink «Semplice»: nessuna regola salvata. Ci pensa lo strato 1.
        if ( ! is_array( $rules ) || empty( $rules ) ) {
            return;
        }
        $needle = '^' . RDACADEMY_APP_SLUG . '/?$';
        if ( array_key_exists( $needle, $rules ) ) {
            return; // Regole a posto.
        }
        if ( get_transient( 'rdacademy_rewrite_selfheal' ) ) {
            return; // Tentativo recente: non insistere a ogni richiesta.
        }
        set_transient( 'rdacademy_rewrite_selfheal', time(), 5 * MINUTE_IN_SECONDS );
        flush_rewrite_rules( false );
    }

    /**
     * Registra le rewrite rule per /academy e /academy/*
     */
    public function add_rewrite_rules(): void {
        add_rewrite_rule(
            '^' . RDACADEMY_APP_SLUG . '/?$',
            'index.php?rdacademy_page=dashboard',
            'top'
        );
        add_rewrite_rule(
            '^' . RDACADEMY_APP_SLUG . '/([a-z0-9_-]+)/?$',
            'index.php?rdacademy_page=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^' . RDACADEMY_APP_SLUG . '/([a-z0-9_-]+)/([0-9]+)/?$',
            'index.php?rdacademy_page=$matches[1]&rdacademy_id=$matches[2]',
            'top'
        );
    }

    /**
     * Aggiunge le query vars custom.
     */
    public function add_query_vars( array $vars ): array {
        $vars[] = 'rdacademy_page';
        $vars[] = 'rdacademy_id';
        return $vars;
    }

    /**
     * Intercetta la richiesta e serve la pagina del prodotto.
     */
    public function handle_request(): void {
        $page = get_query_var( 'rdacademy_page' );
        if ( ! $page ) return;

        // Quando la richiesta arriva dallo strato di riserva (rewrite rules
        // assenti) WP l ha gia classificata 404: la si serve con lo stato giusto.
        global $wp_query;
        if ( isset( $wp_query ) && is_object( $wp_query ) ) {
            $wp_query->is_404 = false;
        }
        status_header( 200 );

        // Header no-cache per forzare sempre file freschi.
        // 1.4.3B (RF-BUG-028) — le intestazioni anti-cache sono già partite per prime (template_redirect 0, carico-e-143.php):
        // qui si ripetono le stesse, mai una versione ridotta che ne cancelli una parte.
        if ( class_exists( 'Rdacademy_Cache_Guardia' ) ) {
            Rdacademy_Cache_Guardia::intestazioni();
        } else {
            header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
            header( 'Pragma: no-cache' );
        }

        // Serve l applicazione — bypassa completamente il tema WP
        $this->serve_app( (string) $page );
        exit;
    }

    /* ===================================================================== *
     *  ⛔ SPEC § 2.5 — LA MAPPA DEI PERMESSI DELLE PAGINE (NUOVO)
     * ===================================================================== */

    /**
     * Il livello di una pagina: public | auth | interno | admin.
     *  - public  : chiunque, anche senza sessione (le tre porte dell accesso);
     *  - auth    : qualunque utente collegato, interno o esterno;
     *  - interno : utente collegato di tipo «interno» (o amministratore);
     *  - admin   : solo amministratore (backdoor o permissions.all).
     * ⛔ Una pagina non dichiarata vale `admin`, come un azione non dichiarata.
     */
    /* 1.13.0B (SV-43, SPEC-NOVITA-INTEGRAZIONE-FAMIGLIA § 6.2) — L INTEGRATORE CHIUSO IN TRE PUNTI. Punto 1 (pagine) e punto 2
     * (chiamate) stanno qui, punto 3 (il tipo sconosciuto) in Rdacademy_Auth::tipo_sessione(). Le due liste sono CHIUSE: valgono
     * qualunque sia il livello della pagina o dell azione, e nessun filtro le allarga. */
    const PAGINE_INTEGRATORE = array( 'novita', 'manuale-amministratore', 'preferenze', 'login', 'recupera', 'imposta-password' );
    const AZIONI_INTEGRATORE = array( 'logout', 'me_prefs_get', 'me_prefs_save', 'me_profilo_get', 'me_password_change', 'me_accessi_get' );

    /** È un Integratore (o un tipo sconosciuto, che vale come lui)? */
    public static function e_integratore( ?array $user ): bool {
        return $user && ( $user['tipo'] ?? 'integratore' ) === 'integratore';
    }

    public static function page_access_map(): array {
        $map = array(
            'login'            => 'public',
            'recupera'         => 'public',
            'imposta-password' => 'public',
            'dashboard'        => 'auth',     // «Pannello»: interni ed esterni
            'preferenze'       => 'auth',     // le Preferenze personali sono di tutti (lotto C)
            'log-accessi'      => 'admin',
            'impostazioni'     => 'admin',
            'utenti'           => 'admin',
            'tabelle'          => 'admin',
            'area-tecnica'     => 'admin',
        );
        // INNESTO LOTTO-C: le pagine del lotto dichiarano qui il loro livello.
        return (array) apply_filters( 'rdacademy_page_access', $map );
    }

    public static function page_level( string $page ): string {
        $map = self::page_access_map();
        $lvl = $map[ $page ] ?? 'admin';
        return in_array( $lvl, array( 'public', 'auth', 'interno', 'admin' ), true ) ? $lvl : 'admin';
    }

    /** Il livello ammette questo utente? Stessa autorita del canale AJAX (user_can_admin). */
    public static function user_passes( ?array $user, string $level ): bool {
        if ( $level === 'public' ) return true;
        if ( ! $user ) return false;
        if ( $level === 'auth' ) return true;
        $admin = self::user_can_admin( $user );
        if ( $level === 'admin' ) return $admin;
        if ( $level === 'interno' ) return $admin || ( ( $user['tipo'] ?? 'interno' ) === 'interno' );
        return false;
    }

    private function serve_app( string $page ): void {

        // Pagine pubbliche (non richiedono login): l accesso e le due porte del
        // recupero (RelDox 3.16.195, voce 39). Ora lette dalla mappa § 2.5.
        $level = self::page_level( $page );

        // Controlla autenticazione
        $user = Rdacademy_Auth::get_current_user();

        // ⛔ 1.4.3B (RF-BUG-028) — nessun 30x dal server sulle pagine dell app: una cache li darebbe a tutti. Il rinvio lo fa il
        // browser da una pagina neutra (Rdacademy_Cache_Guardia::rinvio, 200 e no-store). Di norma ci pensa già carico-e-143.php.
        if ( ! $user && $level !== 'public' ) {
            Rdacademy_Cache_Guardia::rinvio( Rdacademy_Cache_Guardia::url_accesso() );
        }

        if ( $user && $page === 'login' ) {
            Rdacademy_Cache_Guardia::rinvio( home_url( '/' . RDACADEMY_APP_SLUG . '/' . ( self::e_integratore( $user ) ? 'novita' : '' ) ) );
        }

        // ⛔ 1.13.0B (SV-43) — punto 1: l Integratore vede solo la lista chiusa; ogni altra pagina (la partenza compresa) rinvia a
        // /academy/novita, qualunque livello abbia. Prima della mappa dei livelli: un livello sbagliato non lo fa entrare.
        // Copia ridotta: nell Academy la pagina «novita» non c è (vale `admin`): l Integratore, tipo senza interfaccia, finisce sul 403.
        if ( self::e_integratore( $user ) && ! in_array( $page, self::PAGINE_INTEGRATORE, true ) ) {
            Rdacademy_Cache_Guardia::rinvio( home_url( '/' . RDACADEMY_APP_SLUG . '/novita' ) );
        }

        // Rimuove TUTTI gli stili e script di WordPress/tema
        // cosi il nostro layout e completamente isolato
        add_action( 'wp_print_styles', function() {
            global $wp_styles;
            if ( $wp_styles ) $wp_styles->queue = [];
        }, 100 );
        add_action( 'wp_print_scripts', function() {
            global $wp_scripts;
            if ( $wp_scripts ) $wp_scripts->queue = [];
        }, 100 );

        // ⛔ § 2.5: il permesso della pagina si decide QUI, prima della vista.
        if ( ! self::user_passes( $user, $level ) ) {
            status_header( 403 );
            $GLOBALS['rdacademy_template'] = 'accesso-riservato.php';
            include RDACADEMY_PLUGIN_DIR . 'public/views/layout.php';
            return;
        }

        // Determina il template da caricare
        $template = $this->get_template( $page );

        // Imposta template globale e carica layout standard
        $GLOBALS['rdacademy_template'] = $template;
        include RDACADEMY_PLUGIN_DIR . 'public/views/layout.php';
    }

    /**
     * Mappa la pagina al file template.
     */
    private function get_template( string $page ): string {

        $map = [
            'login'     => 'login.php',
            // RelDox 3.16.195 (S256, voce 39) — scene 2 e 3 del contratto approvato.
            'recupera'          => 'recupera.php',
            'imposta-password'  => 'imposta-password.php',
            'dashboard' => 'dashboard.php',
            'log-accessi'  => 'log-accessi.php',
            // Le pagine dell area Impostazioni del lotto C (impostazioni, utenti,
            // tabelle, area-tecnica, preferenze) non hanno ancora una vista:
            // ricadono su coming-soon.php, mai un fatale.
        ];
        // INNESTO LOTTO-C: le viste del lotto si dichiarano qui (pagina => file).
        // Il file vive in public/views/ del plugin; un file assente ricade su coming-soon.
        $map = (array) apply_filters( 'rdacademy_page_map', $map );

        $file = $map[ $page ] ?? 'coming-soon.php';
        $path = RDACADEMY_PLUGIN_DIR . 'public/views/' . basename( (string) $file );

        return file_exists( $path ) ? basename( (string) $file ) : 'coming-soon.php';
    }

    /**
     * Gestisce le chiamate AJAX del prodotto.
     */
    public function handle_ajax(): void {
        // ── 1. Verifica nonce (CSRF protection) ───────────────────────────
        if ( ! check_ajax_referer( 'rdacademy_ajax', 'nonce', false ) ) {
            wp_send_json_error( 'Token di sicurezza non valido o scaduto. Ricaricare la pagina.', 403 );
        }

        $action = sanitize_key( $_POST['rdacademy_action'] ?? $_GET['rdacademy_action'] ?? '' );
        if ( ! $action ) wp_send_json_error( 'Azione non specificata', 400 );

        // ── 2. Capability matrix ───────────────────────────────────────────
        // 'public'  = chiunque (es. login)
        // 'auth'    = utente autenticato qualsiasi ruolo
        // 'write'   = utente autenticato + freno 60 al minuto
        // 'admin'   = solo amministratore
        // 'backdoor'= solo backdoor
        $capability_map = [
            // Accesso pubblico
            'login'                    => 'public',
            // RelDox v3.16.195 (S256, voce 39) — LE TRE PORTE PUBBLICHE DEL RECUPERO.
            // Sono `public` per necessita: chi ha perso la password non e
            // autenticato per definizione. ⛔ Il freno del router gira SOLO sul
            // ramo autenticato: su queste tre `check_rate_limit()` si chiama A
            // MANO dentro ciascun handler, per IP e per account.
            'pwd_forgot'               => 'public',
            'pwd_token_check'          => 'public',
            'pwd_set'                  => 'public',
            // Solo autenticazione
            'logout'                   => 'auth',
            'licenza_striscia_chiudi'  => 'admin',   // F023_LICENZA
            // RelDox 3.17.10 (S273) — BUG-91: l identita viene da me_uid() e il modulo non la porta.
            'me_prefs_get'             => 'auth',
            'me_prefs_save'            => 'auth',
            // ⛔ In RelDox era `auth` e il controllo admin stava solo dentro
            // l handler: il permesso vive dove il dato esce, ma si DICHIARA qui.
            'get_access_log'           => 'admin',
        ];
        // INNESTO LOTTO-C: le azioni del lotto aggiungono qui il loro livello.
        // ⛔ Un filtro non puo allargare le azioni del nucleo: le righe sopra vincono.
        $capability_map = array_merge(
            (array) apply_filters( 'rdacademy_capability_map', array() ),
            $capability_map
        );

        $required = $capability_map[ $action ] ?? 'admin'; // default admin se non mappata

        // ── 2b. F023_LICENZA — SOLA CONSULTAZIONE A LICENZA CESSATA (D-150.3, D-150.7, D-150.9, D-150.10), come RelDox CRD ──
        if ( class_exists( 'Rdacademy_Licenza_Stato' ) && ( $required === 'public' || Rdacademy_Auth::get_current_user() ) && Rdacademy_Licenza_Stato::cessata() ) {
            if ( $action === 'save_user' ) {
                $rx_lm = Rdacademy_Licenza_Stato::save_user_a_licenza_cessata( (array) $_POST );
                if ( $rx_lm !== '' ) wp_send_json_error( $rx_lm, 403 );
            } elseif ( ! Rdacademy_Licenza_Stato::azione_ammessa( $action, (string) $required ) ) {
                wp_send_json_error( Rdacademy_Licenza_Stato::MESSAGGIO . '.', 403 );
            }
        }
        // ── 3. Autenticazione e capability check ──────────────────────────
        $user = null;
        if ( $required !== 'public' ) {
            $user = Rdacademy_Auth::get_current_user();

            if ( ! $user ) {
                wp_send_json_error( 'Sessione scaduta. Effettuare nuovamente il login.', 401 );
            }

            // ⛔ 1.13.0B (SV-43) — punto 2: l Integratore chiama solo le azioni personali di un elenco chiuso.
            if ( self::e_integratore( $user ) && ! in_array( $action, self::AZIONI_INTEGRATORE, true ) ) {
                wp_send_json_error( 'Accesso riservato.', 403 );
            }

            if ( $required === 'backdoor' && empty( $user['is_backdoor'] ) ) {
                wp_send_json_error( 'Accesso riservato.', 403 );
            }

            if ( $required === 'admin' && ! self::user_can_admin( $user ) ) {
                wp_send_json_error( 'Permessi insufficienti per questa operazione.', 403 );
            }

            // ── 4. Rate limiting per azioni di scrittura ──────────────────
            if ( $required === 'write' ) {
                if ( ! $this->check_rate_limit( 'write_' . (int)$user['id'], 60, 60 ) ) {
                    wp_send_json_error( 'Troppe richieste. Attendere un momento e riprovare.', 429 );
                }
            }

            // Rate limiting globale per IP su tutte le action autenticate
            $ip = $this->get_client_ip();
            if ( ! $this->check_rate_limit( 'ip_' . md5( $ip ), 200, 60 ) ) {
                wp_send_json_error( 'Troppe richieste da questo IP.', 429 );
            }
        }

        // ── 5. Dispatch ───────────────────────────────────────────────────
        switch ( $action ) {
            case 'licenza_striscia_chiudi': $rx_u = Rdacademy_Auth::get_current_user(); Rdacademy_Licenza_Stato::chiudi_striscia( (int) ( $rx_u['id'] ?? 0 ) ); wp_send_json_success( array( 'chiusa' => true ) ); break;   // F023_LICENZA
            case 'login':
                $this->ajax_login();
                break;
            case 'logout':
                $this->ajax_logout();
                break;
            case 'get_access_log':
                $this->ajax_get_access_log();
                break;
            // RelDox v3.16.195 (S256, voce 39) — recupero credenziali.
            case 'pwd_forgot':
                $this->ajax_pwd_forgot();
                break;
            case 'pwd_token_check':
                $this->ajax_pwd_token_check();
                break;
            case 'pwd_set':
                $this->ajax_pwd_set();
                break;
            case 'me_prefs_get':
                $this->ajax_me_prefs_get();
                break;
            case 'me_prefs_save':
                $this->ajax_me_prefs_save();
                break;
            default:
                // INNESTO LOTTO-C: l azione del lotto risponde da `rdacademy_ajax_<azione>`,
                // DOPO che nonce, permesso e freno sono gia passati qui sopra.
                if ( has_action( 'rdacademy_ajax_' . $action ) ) {
                    do_action( 'rdacademy_ajax_' . $action, $user );
                    wp_send_json_error( 'Azione senza risposta.' ); // un handler deve rispondere lui
                }
                wp_send_json_error( 'Azione non riconosciuta' );
        }
    }

    /**
     * AJAX: login
     */
    private function ajax_login(): void {
        // BUG-81 di RelDox (3.16.198) — l accesso con username: non si sanifica
        // come un indirizzo. Si taglia, si limita la lunghezza e si tolgono i
        // caratteri di controllo; al database il valore arriva legato a un parametro.
        $ident    = trim( (string) ( $_POST['email'] ?? '' ) );
        $ident    = preg_replace( '/[\x00-\x1F\x7F]/u', '', $ident );
        $ident    = mb_substr( (string) $ident, 0, 255 );
        $password = (string) wp_unslash( $_POST['password'] ?? '' );

        // 0.8.2A — la porta parla nella lingua scelta con le bandierine (o in quella del browser).
        $T = static function ( string $k ): string { return Rdacademy_Lingua::t( $k ); };
        if ( $ident === '' || $password === '' ) {
            wp_send_json_error( $T( 'login.obbligatori' ) );
        }

        // Copia ridotta: niente «Entra come …» (era l email condivisa della ristorazione): due righe con la stessa email = «ambiguo».
        $user = Rdacademy_Auth::login( $ident, $password );

        if ( ! $user ) {
            // BUG-83 di RelDox — IL BLOCCO PARLA, IL RESTO NO
            // (RP-LA-PORTA-NON-DICE-CHI-C-E-DENTRO, eccezione per ordine dell owner).
            $motivo = (string) Rdacademy_Auth::$ultimo_motivo;
            $det    = (array) Rdacademy_Auth::$ultimo_dettaglio;

            if ( $motivo === 'bloccato' ) {
                $ora = (string) ( $det['fino_alle'] ?? '' );
                wp_send_json_error(
                    $T( 'login.bloccato' )
                    . ' ' . ( $ora !== '' ? sprintf( $T( 'login.riprova_alle' ), $ora ) : $T( 'login.riprova_poco' ) )
                    . ' ' . $T( 'login.blocco_rec' )
                );
            }
            if ( $motivo === 'ambiguo' ) {
                wp_send_json_error( $T( 'login.ambiguo' ) );
            }
            wp_send_json_error( $T( 'login.non_valide' ) );
        }

        // 0.8.2A — dopo l accesso la porta ricorda la lingua della scheda: la volta dopo si apre già in quella lingua.
        $rx_lin = Rdacademy_Lingua::della_scheda( (int) $user['id'] );
        if ( $rx_lin !== null ) Rdacademy_Lingua::ricorda( $rx_lin );

        // ⛔ DIFFERENZA DA RELDOX: la risposta portava la riga intera dell utente
        // (meno la password): impronta del gettone, tentativi falliti, blocco.
        // Al browser serve sapere dove andare; l identita la rilegge il layout.
        wp_send_json_success( [
            'user'     => [
                'id'   => (int) $user['id'],
                'name' => trim( (string) $user['first_name'] . ' ' . (string) $user['last_name'] ),
            ],
            'redirect' => home_url( '/' . RDACADEMY_APP_SLUG . '/' ),
        ] );
    }

    /**
     * AJAX: logout
     */
    private function ajax_logout(): void {
        Rdacademy_Auth::logout();
        wp_send_json_success( [ 'redirect' => home_url( '/' . RDACADEMY_APP_SLUG . '/login' ) ] );
    }

    /**
     * Rate limiting tramite WordPress Transients — DELEGA al freno unico
     * rdacademy_freno() di helpers.php (BUG-190 di RelDox).
     */
    private function check_rate_limit( string $key, int $max, int $window ): bool {
        return rdacademy_freno( $key, $max, $window );
    }

    /**
     * Recupera l IP reale del client in modo sicuro: solo REMOTE_ADDR.
     * X-Forwarded-For puo essere falsificato.
     */
    private function get_client_ip(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Gate AMMINISTRATIVO per gli AJAX /academy/ — copiato da RelDox
     * (FINDING-ADMIN-AJAX-GATE, Opzione β): delega alla STESSA autorita del
     * gate-pagina (Rdacademy_Security::is_admin), single source of truth. Il puro
     * Backdoor mantiene lo short-circuit in testa.
     * Pubblico (era privato): la usa anche il layout per il rail e l ingranaggio.
     */
    public static function user_can_admin( array $user ): bool {
        if ( ! empty( $user['is_backdoor'] ) ) return true;
        if ( ! empty( $user['id'] ) && class_exists( 'Rdacademy_Security' ) ) {
            return Rdacademy_Security::is_admin( (int) $user['id'] );
        }
        return false;
    }

    /**
     * AJAX: gli ultimi 50 accessi di un utente (scheda utente, lotto C).
     * ⛔ § 2.2: query preparata (in RelDox: user_id interpolato e SHOW TABLES).
     */
    private function ajax_get_access_log(): void {
        $cur = Rdacademy_Auth::get_current_user();
        if ( ! $cur || ! self::user_can_admin( $cur ) ) wp_send_json_error( 'Accesso negato', 403 );
        $p   = RDACADEMY_TABLE_PREFIX;
        $uid = (int) ( $_POST['user_id'] ?? 0 );
        $rows = Rdacademy_DB::get_results(
            "SELECT id, user_id, login_input, ip_address, user_agent, result, reason, created_at
               FROM `{$p}access_log` WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 50",
            [ $uid ], 'i'
        );
        $err = Rdacademy_DB::last_sql_error();
        if ( $err !== null ) {
            // RP-ERRORE-NON-SI-TRAVESTE-DA-DATO: un fallimento non diventa «zero righe».
            wp_send_json_error( 'Registro accessi non leggibile.' );
        }
        foreach ( $rows as &$r ) {
            $r['id']      = (int) $r['id'];
            $r['user_id'] = (int) $r['user_id'];
        }
        unset( $r );
        wp_send_json_success( $rows );
    }

    /* ===================================================================== *
     *  RECUPERO CREDENZIALI — copiato da RelDox v3.16.195 (S256, voce 39)
     *
     *  ⛔ NESSUNA DELLE TRE DISTINGUE GLI ESITI verso l esterno
     *  (RP-LA-PORTA-NON-DICE-CHI-C-E-DENTRO): indirizzo esistente, assente,
     *  sospeso, freno scattato, SMTP guasto o casella di servizio mancante
     *  producono LA STESSA risposta. Il motivo vero finisce in `activity_log`.
     * ===================================================================== */

    private function ajax_pwd_forgot(): void {
        $ip  = $this->get_client_ip();
        $inp = strtolower( trim( (string)( $_POST['login'] ?? '' ) ) );

        // La risposta e UNA SOLA e si prepara PRIMA di guardare qualunque cosa.
        $risposta = [ 'ok' => true ];

        if ( $inp === '' || strlen( $inp ) > 255 ) wp_send_json_success( $risposta );

        // Freno per IP: 10 richieste ogni 15 minuti.
        if ( ! $this->check_rate_limit( 'pwdf_ip_' . md5( $ip ), 10, 900 ) ) {
            wp_send_json_success( $risposta );
        }
        // Freno per account: 3 collegamenti ogni 15 minuti allo stesso indirizzo.
        if ( ! $this->check_rate_limit( 'pwdf_ac_' . md5( $inp ), 3, 900 ) ) {
            wp_send_json_success( $risposta );
        }

        // BUG-79 di RelDox — stessa regola dell accesso: l email ha la
        // precedenza; se restano piu righe SI RIFIUTA (muto verso l esterno).
        $p     = RDACADEMY_TABLE_PREFIX;
        $righe = Rdacademy_DB::get_results(
            "SELECT * FROM `{$p}users` WHERE LOWER(email) = ? AND status = 'active'
              ORDER BY id LIMIT 2",
            [ $inp ], 's'
        );
        if ( count( $righe ) === 0 ) {
            $righe = Rdacademy_DB::get_results(
                "SELECT * FROM `{$p}users` WHERE LOWER(login) = ? AND status = 'active'
                  ORDER BY id LIMIT 2",
                [ $inp ], 's'
            );
        }
        if ( count( $righe ) > 1 ) {
            Rdacademy_Auth::log_activity( (int) $righe[0]['id'], 'auth', 'pwd_forgot_ambiguo',
                'Recupero NON emesso: l identificativo richiesto risolve piu di un utente attivo. '
                . 'Va reso univoco prima che il recupero possa funzionare.' );
            wp_send_json_success( $risposta );
        }
        $ut = $righe ? $righe[0] : null;
        if ( ! $ut || trim( (string)( $ut['email'] ?? '' ) ) === '' ) {
            wp_send_json_success( $risposta );
        }

        $token = Rdacademy_Password_Token::emetti( (int)$ut['id'], Rdacademy_Password_Token::SCOPO_REIMPOSTA );
        if ( $token === null ) wp_send_json_success( $risposta );

        $inv = Rdacademy_Password_Mail::invia_reimpostazione( $ut, $token );
        unset( $token );

        Rdacademy_Auth::log_activity( (int)$ut['id'], 'auth', 'pwd_forgot',
            $inv['success'] ? 'Collegamento di recupero spedito' : ( 'Collegamento NON spedito: ' . ( $inv['error'] ?? 'errore' ) ) );

        wp_send_json_success( $risposta );
    }

    /**
     * Verifica il collegamento PRIMA di disegnare il modulo (scena 3): se il
     * token non vale, il campo password non esiste affatto.
     */
    private function ajax_pwd_token_check(): void {
        $ip = $this->get_client_ip();
        if ( ! $this->check_rate_limit( 'pwdc_ip_' . md5( $ip ), 30, 900 ) ) {
            wp_send_json_success( [ 'valido' => false ] );
        }

        $esito = Rdacademy_Password_Token::verifica( (string)( $_POST['t'] ?? '' ) );
        if ( $esito === null ) wp_send_json_success( [ 'valido' => false ] );

        $ut = $esito['utente'];
        wp_send_json_success( [
            'valido'  => true,
            'scopo'   => $esito['scopo'],
            'nome'    => trim( (string)( $ut['first_name'] ?? '' ) . ' ' . (string)( $ut['last_name'] ?? '' ) ),
            // 1.4.0B — chi non ha l email: sotto il titolo il suo nome utente.
            'email'   => (string)( $ut['email'] ?? '' ) !== '' ? (string) $ut['email'] : (string)( $ut['login'] ?? '' ),
            'regola'  => Rdacademy_Password_Policy::descrittore( $ut, Rdacademy_Lingua::di_utente( (int) ( $ut['id'] ?? 0 ) ) ),
            'lingua'  => Rdacademy_Lingua::di_utente( (int) ( $ut['id'] ?? 0 ) ),
        ] );
    }

    /**
     * Scrive la password nuova e APRE LA SESSIONE (scena 3, stato E).
     */
    private function ajax_pwd_set(): void {
        $ip = $this->get_client_ip();
        if ( ! $this->check_rate_limit( 'pwds_ip_' . md5( $ip ), 20, 900 ) ) {
            wp_send_json_error( Rdacademy_Lingua::t( 'ip.troppi' ), 429 );
        }

        $t    = (string)( $_POST['t'] ?? '' );
        // 0.8.2A — i messaggi nella lingua della persona del collegamento (se il collegamento vale).
        $rx_v = Rdacademy_Password_Token::verifica( $t );
        $rx_l = $rx_v ? Rdacademy_Lingua::di_utente( (int) ( $rx_v['utente']['id'] ?? 0 ) ) : Rdacademy_Lingua::corrente();

        $pwd  = (string) wp_unslash( $_POST['password'] ?? '' );
        $pwd2 = (string) wp_unslash( $_POST['password2'] ?? '' );

        if ( $pwd !== $pwd2 ) wp_send_json_error( Rdacademy_Lingua::t( 'ip.diverse', $rx_l ) );

        $esito = Rdacademy_Password_Token::consuma( $t, $pwd );
        if ( empty( $esito['ok'] ) ) {
            if ( $esito['errore'] === 'collegamento' ) {
                // Stato D: un solo stato per tre cause.
                wp_send_json_error( [ 'collegamento' => false ], 400 );
            }
            wp_send_json_error( (string)$esito['errore'] );
        }

        $uid   = (int)$esito['utente']['id'];
        $entra = Rdacademy_Auth::open_session_for_user( $uid );

        // 0.6.10A (RF-BUG-018, sotto-scheda) — ENTRARE DAL COLLEGAMENTO E UN ACCESSO.
        // Fino alla 0.6.09A la sessione si apriva senza scrivere ne `last_login` ne il registro
        // degli accessi: l ospite entrato dall invito restava «Mai entrato» in elenco e le sue
        // Preferenze dicevano «Nessun accesso registrato» (smoke dell owner, 06/10 07:41).
        if ( $entra ) {
            $p = RDACADEMY_TABLE_PREFIX;
            Rdacademy_DB::execute( "UPDATE `{$p}users` SET last_login = ? WHERE id = ?", [ gmdate( 'Y-m-d H:i:s' ), $uid ], 'si' );
            Rdacademy_Auth::log_access(
                $uid, (string) ( $esito['utente']['email'] ?? '' ), $ip, substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 500 ), 'success',
                ( $esito['scopo'] === Rdacademy_Password_Token::SCOPO_INVITO ) ? 'Primo accesso dal collegamento d\'invito' : 'Accesso dal collegamento di reimpostazione'
            );
        }
        $app = home_url( '/' . RDACADEMY_APP_SLUG );
        // 1.4.5B (RF-BUG-030) — DOPO L ATTIVAZIONE SI ARRIVA A CASA, PER TUTTI E PER OGNI SCOPO (invito, reimpostazione): la porta
        // /academy/ porta ognuno sulla sua schermata principale. Mai sulle Preferenze: la password è appena stata scelta;
        // cambiarla resta nel menu «Il mio profilo».
        $dove = $app . '/';

        wp_send_json_success( [
            'entrato'  => $entra,
            'vai'      => $entra ? $dove : ( $app . '/login' ),
        ] );
    }

    /* ===================================================================== *
     *  SELF-SERVICE — RelDox 3.16.197 (S258) `RP-IL-SELF-SERVICE-NON-TOCCA-UN-ALTRO`
     * ===================================================================== */

    /**
     * L id non arriva dal POST e non ci arrivera mai: si legge da
     * `Rdacademy_Auth::get_current_user()`. Ogni endpoint del self-service passa
     * di qui, cosi la regola sta scritta UNA VOLTA.
     */
    private function me_uid(): int {
        $cur = Rdacademy_Auth::get_current_user();
        if ( ! $cur || empty( $cur['id'] ) ) {
            wp_send_json_error( 'Sessione scaduta. Effettuare nuovamente il login.', 401 );
        }
        return (int) $cur['id'];
    }

    /**
     * Lettura delle proprie preferenze. TAGLIO: in RelDox l endpoint parlava di
     * una sola chiave (aziende dell albero) e risolveva i nomi su `companies`.
     * Nel guscio restituisce tutte le chiavi del vocabolario, con l istante.
     */
    private function ajax_me_prefs_get(): void {
        $uid = $this->me_uid();
        $out = array();
        foreach ( Rdacademy_User_Prefs::tutte( $uid ) as $k => $v ) {
            $out[ $k ] = array( 'valore' => $v, 'updated_at' => Rdacademy_User_Prefs::updated_at( $uid, $k ) );
        }
        wp_send_json_success( array(
            'chiavi' => Rdacademy_User_Prefs::chiavi(),
            'prefs'  => $out,
        ) );
    }

    /**
     * Scrittura delle proprie preferenze, IN REPLACE (decisione D1 dell owner,
     * S273 di RelDox): il valore nuovo SOSTITUISCE il vecchio. Lo svuotamento
     * e un gesto suo (`svuota`).
     */
    private function ajax_me_prefs_save(): void {
        $uid = $this->me_uid();

        $chiave = isset( $_POST['chiave'] ) ? sanitize_key( wp_unslash( (string) $_POST['chiave'] ) ) : '';
        if ( ! Rdacademy_User_Prefs::is_key( $chiave ) ) {
            wp_send_json_error( 'Preferenza non riconosciuta.' );
        }

        if ( ! empty( $_POST['svuota'] ) ) {
            Rdacademy_User_Prefs::del( $uid, $chiave );
            wp_send_json_success( array( 'chiave' => $chiave, 'svuotata' => true ) );
        }

        $raw = isset( $_POST['valore'] ) ? wp_unslash( (string) $_POST['valore'] ) : 'null';
        $val = json_decode( $raw, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( 'Valore non leggibile.' );
        }
        // NUOVO — un lotto puo validare la SUA chiave (es. un elenco chiuso).
        $val = apply_filters( 'rdacademy_user_prefs_valida_' . $chiave, $val );
        if ( is_wp_error( $val ) ) {
            wp_send_json_error( $val->get_error_message() );
        }

        if ( ! Rdacademy_User_Prefs::set( $uid, $chiave, $val ) ) {
            wp_send_json_error( 'Preferenza non salvata.' );
        }

        wp_send_json_success( array(
            'chiave'     => $chiave,
            'valore'     => Rdacademy_User_Prefs::get( $uid, $chiave ),
            'updated_at' => Rdacademy_User_Prefs::updated_at( $uid, $chiave ),
        ) );
    }
}
