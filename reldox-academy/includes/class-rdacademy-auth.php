<?php
/**
 * Classe Rdacademy_Auth
 * Gestisce autenticazione e sessioni del prodotto, separate da WordPress.
 *
 * Copiato da Reldox_Auth, RelDox CRD 3.55.09G. Correzioni obbligatorie (SPEC § 2):
 *  - § 2.2 LOGIN CON QUERY PREPARATE: in RelDox login() e log_access()
 *    concatenavano real_escape_string() dentro SQL scritto a mano (anche gli
 *    UPDATE di failed_logins/locked_until/last_login). Qui ogni valore viaggia
 *    legato a un parametro, compresa la pagina di accesso (REGOLE § 5).
 *  - § 2.3 UN OROLOGIO SOLO: expires_at, locked_until e last_login si scrivono
 *    con gmdate() e si confrontano con un istante UTC passato da PHP. In RelDox
 *    la sessione si scriveva con date() di PHP e si confrontava con NOW() di MySQL.
 * NUOVO: `tipo` (interno | esterno) e `login` nella struttura dell utente corrente.
 * TAGLIO: nessuno sulle funzioni; nome_visibile() resta (serve al lotto C).
 * Copia ridotta: via il ramo dell email condivisa (scheda della mensa + amministratore).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Auth {

    const SESSION_DURATION = 28800; // 8 ore DI INATTIVITA in secondi (0.6.09A: si rinnova mentre si lavora)
    const RINNOVO_OGNI     = 300;   // 0.6.09A — la scadenza si sposta al massimo ogni 5 minuti
    const COOKIE_SCADUTA   = 'rdacademy_scaduta'; // 0.6.09A — «la sessione e finita»: lo legge la pagina d accesso
    const SESSION_COOKIE   = 'rdacademy_session';
    const TOKEN_LENGTH     = 64;
    /* S590B_SESSIONE — 3.55.03G voce 83: nel database l impronta del gettone, mai il gettone */
    const IMPRONTA_SESSIONE = 's:';
    /* 1.4.3B (RF-BUG-028) — i due cookie che accompagnano la sessione, mai il gettone:
       COOKIE_VISTA     il «segno» della sessione (impronta non reversibile), leggibile dal browser: la guardia della pagina lo
                        confronta col segno con cui la pagina è stata disegnata; «-» = nessuno collegato;
       COOKIE_COLLEGATO il nome che le cache per WordPress (Aruba, Varnish, LiteSpeed, nginx) riconoscono come «utente collegato»:
                        in sua presenza saltano la cache. Valore casuale, non apre niente. */
    const COOKIE_VISTA     = 'rdacademy_vista';
    const COOKIE_COLLEGATO = 'wordpress_logged_in_rdacademy';
    private static ?array $current_user = null;
    /** 1.4.3B — la richiesta portava un gettone, ma la sessione è finita (la pagina d accesso lo dice). */
    private static bool $sessione_finita = false;

    /**
     * Tenta il login con email O username + password.
     * Gestisce log accessi, blocco tentativi, scadenza password.
     */
    /**
     * BUG-83 (3.16.198, S260) — IL MOTIVO DEL RIFIUTO, PER CHI HA DIRITTO DI
     * SAPERLO.
     *
     * `login()` esce a vuoto per CINQUE cause diverse e il router le comprimeva
     * tutte in «Credenziali non valide» (RP-LA-PORTA-NON-DICE-CHI-C-E-DENTRO).
     * Corretto verso l esterno, ma il 16/08/2026 e' costato una diagnosi in
     * Console SQL: senza una via interna, ogni incidente futuro la ripete.
     * Questa proprieta' NON e' la risposta: e' cio' che il chiamante puo'
     * leggere per decidere che cosa dire. Chi la stampa se ne assume la scelta.
     *
     * Valori: 'assente' | 'ambiguo' | 'inattivo' | 'bloccato' | 'errata' | 'scaduta'
     */
    public static $ultimo_motivo = '';
    /** Dati d appoggio del motivo (es. l ora di sblocco gia' resa in ora locale). */
    public static $ultimo_dettaglio = array();

    /** BUG-83 — la soglia e la durata del blocco: dichiarate, non sparse. */
    const SOGLIA_BLOCCO  = 5;
    const MINUTI_BLOCCO  = 15;

    /**
     * BUG-83 (3.16.198) — L AVVISO ALL AMMINISTRATORE QUANDO LA PORTA SI CHIUDE.
     *
     * ⛔ NON DEVE POTER ROMPERE L ACCESSO. Se la casella di servizio non e'
     * configurata, se l SMTP e' guasto, se non esiste nessun amministratore con
     * un indirizzo, questa funzione ESCE IN SILENZIO: un avviso mancato e' un
     * fastidio, un login che va in errore per colpa dell avviso e' un secondo
     * incidente dentro il primo.
     *
     * ⛔ NON DICE MAI CHE COSA E STATO DIGITATO. Nel messaggio vanno l utente,
     * l ora e la rete di provenienza: la password tentata non si registra e non
     * si spedisce, nemmeno quando e' sbagliata — spessissimo e' quella giusta
     * di un altro servizio.
     */
    private static function avvisa_admin_del_blocco( array $user, string $ip, string $lock_until ): void {
        try {
            if ( ! class_exists( 'Rdacademy_Password_Mail' )
                || ! method_exists( 'Rdacademy_Password_Mail', 'avvisa_blocco' )
                || ! Rdacademy_Password_Mail::configurata() ) {
                return;
            }
            Rdacademy_Password_Mail::avvisa_blocco( $user, $ip, self::ora_locale( $lock_until ) );
        } catch ( \Throwable $e ) {
            error_log( '[Reldox Auth] avviso di blocco non spedito: ' . $e->getMessage() );
        }
    }

    /**
     * BUG-83 (3.16.198) — L ORA DEL BLOCCO IN ORA LOCALE, NON IN UTC.
     *
     * `locked_until` si scrive col fuso di PHP, che sotto WordPress e' UTC. Il
     * messaggio diceva «bloccato fino alle 12:15» a un utente che sull orologio
     * leggeva le 14:07: chiunque avrebbe pensato che il blocco fosse finito.
     * Si dichiara che il valore in colonna e' UTC e lo si rende col fuso del
     * sito, che e' la sola ora che l utente riconosce.
     */
    private static function ora_locale( string $utc ): string {
        $ts = strtotime( $utc . ' UTC' );
        if ( ! $ts ) return '';
        return function_exists( 'wp_date' ) ? (string) wp_date( 'H:i', $ts ) : date_i18n( 'H:i', $ts );
    }

    /**
     * 0.11.1A — la password sbagliata su un account: conta il tentativo, al quinto blocca (il contatore riparte da zero,
     * come in RelDox), scrive il registro accessi. L avviso all amministratore resta in login().
     * @return array{fails:int,scattato:bool,lock_until:string}
     */
    private static function password_errata( array $user, string $login_input, string $ip, string $ua ): array {
        $p        = RDACADEMY_TABLE_PREFIX;
        $fails    = (int)($user['failed_logins'] ?? 0) + 1;
        $scattato = false; $lock_until = '';
        if ( $fails >= self::SOGLIA_BLOCCO ) {
            $lock_until = gmdate('Y-m-d H:i:s', time() + self::MINUTI_BLOCCO * 60);
            Rdacademy_DB::execute(
                "UPDATE `{$p}users` SET locked_until = ?, failed_logins = 0 WHERE id = ?",
                [ $lock_until, (int) $user['id'] ], 'si'
            );
            $scattato = true;
        } else {
            Rdacademy_DB::execute(
                "UPDATE `{$p}users` SET failed_logins = ? WHERE id = ?",
                [ $fails, (int) $user['id'] ], 'ii'
            );
        }
        self::log_access( (int)$user['id'], $login_input, $ip, $ua, 'failed', "Password errata (tentativo {$fails})" );
        return array( 'fails' => $fails, 'scattato' => $scattato, 'lock_until' => $lock_until );
    }

    public static function login( string $login_input, string $password ): ?array {
        $p    = RDACADEMY_TABLE_PREFIX;
        $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua   = substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500 );
        $inp  = strtolower( trim( $login_input ) );
        self::$ultimo_motivo    = '';
        self::$ultimo_dettaglio = array();

        // ═══════════════════════════════════════════════════════════════════
        // BUG-79 di RelDox (3.16.198, S260) — UN IDENTIFICATIVO VALE UN UTENTE.
        //   1. LA CORRISPONDENZA ESATTA SULL EMAIL HA LA PRECEDENZA.
        //   2. ALTRIMENTI SI CONTA: se il valore risolve piu di una riga si
        //      RIFIUTA — non si sceglie.
        // ⛔ `LIMIT 2` e non `LIMIT 1`: serve sapere se ce n e una seconda.
        // ⛔ CORREZIONE § 2.2: query preparate (in RelDox: '{$inp}' concatenato).
        // ═══════════════════════════════════════════════════════════════════
        $per_email = Rdacademy_DB::get_results(
            "SELECT * FROM `{$p}users` WHERE LOWER(email) = ? ORDER BY id LIMIT 3",
            [ $inp ], 's'
        );

        // Copia ridotta: via il ramo dell email condivisa e il parametro $profilo («Entra come …», F008):
        // due righe con la stessa email sono «ambiguo» (BUG-79).
        if ( count( $per_email ) === 1 ) {
            $user = $per_email[0];
        } elseif ( count( $per_email ) > 1 ) {
            self::$ultimo_motivo = 'ambiguo';
            self::log_access( null, $login_input, $ip, $ua, 'failed',
                'Identificativo ambiguo: piu utenti con questa email' );
            return null;
        } else {
            $per_login = Rdacademy_DB::get_results(
                "SELECT * FROM `{$p}users` WHERE LOWER(login) = ? ORDER BY id LIMIT 2",
                [ $inp ], 's'
            );
            if ( count( $per_login ) > 1 ) {
                self::$ultimo_motivo = 'ambiguo';
                self::log_access( null, $login_input, $ip, $ua, 'failed',
                    'Identificativo ambiguo: piu utenti con questo username' );
                return null;
            }
            $user = $per_login ? $per_login[0] : null;
        }

        // Utente non trovato
        if ( ! $user ) {
            self::$ultimo_motivo = 'assente';
            self::log_access( null, $login_input, $ip, $ua, 'failed', 'Utente non trovato' );
            return null;
        }

        // Account non attivo
        if ( $user['status'] !== 'active' ) {
            self::$ultimo_motivo = 'inattivo';
            self::log_access( (int)$user['id'], $login_input, $ip, $ua, 'failed', 'Account '.$user['status'] );
            return null;
        }

        // Blocco temporaneo (max 5 tentativi falliti → blocco 15 min).
        // ⛔ § 2.3: la colonna e UTC (connessione a +00:00, scritta con gmdate()).
        if ( ! empty($user['locked_until']) && strtotime($user['locked_until'] . ' UTC') > time() ) {
            $until = self::ora_locale( (string) $user['locked_until'] );
            self::$ultimo_motivo    = 'bloccato';
            self::$ultimo_dettaglio = array( 'fino_alle' => $until );
            self::log_access( (int)$user['id'], $login_input, $ip, $ua, 'locked', "Bloccato fino alle {$until}" );
            return null;
        }

        // Verifica password
        if ( ! password_verify( $password, $user['password'] ) ) {
            $e          = self::password_errata( $user, $login_input, $ip, $ua );
            $fails      = $e['fails'];
            $scattato   = $e['scattato'];
            $lock_until = $e['lock_until'];

            if ( $scattato ) {
                // ⛔ BUG-83 di RelDox — IL BLOCCO NON PUO PIU ESSERE MUTO:
                // ORDINE DELL OWNER, dirlo a video E all admin.
                self::$ultimo_motivo    = 'bloccato';
                self::$ultimo_dettaglio = array(
                    'fino_alle' => self::ora_locale( $lock_until ),
                    'appena'    => true,
                );
                self::avvisa_admin_del_blocco( $user, $ip, $lock_until );
            } else {
                self::$ultimo_motivo    = 'errata';
                self::$ultimo_dettaglio = array(
                    'restano' => max( 0, self::SOGLIA_BLOCCO - $fails ),
                );
            }
            return null;
        }

        // Verifica scadenza password (la mette solo l amministratore su un utente).
        if ( ! empty($user['password_expires_at']) && strtotime($user['password_expires_at'] . ' 00:00:00 UTC') < time() ) {
            self::$ultimo_motivo = 'scaduta';
            self::log_access( (int)$user['id'], $login_input, $ip, $ua, 'expired', 'Password scaduta' );
            return null;
        }

        // Login OK — reset failed_logins, aggiorna last_login (UTC da PHP, § 2.3).
        Rdacademy_DB::execute(
            "UPDATE `{$p}users` SET last_login = ?, failed_logins = 0, locked_until = NULL WHERE id = ?",
            [ gmdate( 'Y-m-d H:i:s' ), (int) $user['id'] ], 'si'
        );

        // Crea sessione
        $token = self::create_session( (int)$user['id'] );
        if ( ! $token ) return null;
        self::set_cookie( $token );
        // 0.6.09A — rientrati: l avviso di sessione scaduta non serve piu.
        if ( ! headers_sent() && ! empty( $_COOKIE[ self::COOKIE_SCADUTA ] ) ) {
            setcookie( self::COOKIE_SCADUTA, '', time() - 3600, '/' );
        }

        // Log successo
        self::log_access( (int)$user['id'], $login_input, $ip, $ua, 'success', 'Login OK' );
        self::log_activity( (int)$user['id'], 'auth', 'login', 'Login effettuato' );

        unset( $user['password'], $user['reset_token'], $user['reset_expiry'] );
        return $user;
    }

    /**
     * Registra un tentativo di accesso nel log.
     * ⛔ § 2.2: insert preparato (in RelDox: real_escape_string concatenato).
     */
    public static function log_access( ?int $user_id, string $login_input, string $ip, string $ua, string $result, string $reason = '' ): void {
        $p = RDACADEMY_TABLE_PREFIX;
        try {
            Rdacademy_DB::insert(
                "INSERT INTO `{$p}access_log` (user_id, login_input, ip_address, user_agent, result, reason, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $user_id ?: null,
                    substr( $login_input, 0, 255 ),
                    substr( $ip, 0, 45 ),
                    substr( $ua, 0, 500 ),
                    $result,
                    substr( $reason, 0, 255 ),
                    gmdate( 'Y-m-d H:i:s' ),
                ],
                'issssss'
            );
        } catch ( mysqli_sql_exception $e ) {
            // Il registro non deve poter rompere l accesso (in RelDox: SHOW TABLES a ogni tentativo).
            error_log( '[RelDoxAcademy Auth] access_log non scritto: ' . $e->getMessage() );
        }
    }


    /**
     * Logout — elimina sessione e cookie.
     */
    public static function logout(): void {
        $token = self::get_token_from_cookie();

        if ( $token ) {
            $p = RDACADEMY_TABLE_PREFIX;
            $user = self::get_current_user();

            Rdacademy_DB::execute(
                "DELETE FROM `{$p}sessions` WHERE token = ?",
                [ self::impronta_sessione( (string) $token ) ], 's'
            );

            if ( $user ) {
                self::log_activity( (int) $user['id'], 'auth', 'logout', 'Logout effettuato' );
            }
        }

        self::clear_cookie();
        self::$current_user = null;
    }

    /**
     * Restituisce l'utente attualmente loggato, null se non autenticato.
     */
    /** 1.13.0B (SV-43) — il tipo dell utente in sessione: interno | esterno | integratore; tutto il resto = integratore (il più chiuso). */
    public static function tipo_sessione( $t ): string {
        $t = is_string( $t ) ? $t : '';
        return in_array( $t, array( 'interno', 'esterno' ), true ) ? $t : 'integratore';
    }

    public static function get_current_user(): ?array {
        if ( self::$current_user !== null ) {
            return self::$current_user;
        }

        $token = self::get_token_from_cookie();
        if ( ! $token ) return null;
        if ( self::$sessione_finita ) return null;   // 1.4.3B — già vista finita in questa richiesta: niente seconda pulizia

        $p = RDACADEMY_TABLE_PREFIX;

        // Verifica sessione valida
        $session = Rdacademy_DB::get_row(
            // 1C D7 fix avatar header: includere avatar_path (sorgente reale)
            // PRIMA di avatar_url legacy. La colonna avatar_url resta nullable
            // ed è popolata solo da installazioni pre-pkg-anagrafica-1a.
            // ⛔ § 2.3: si confronta con l istante UTC di PHP, non con NOW() di MySQL.
            "SELECT s.*, u.id as user_id, u.first_name, u.last_name, u.email, u.login, u.tipo,
                    u.avatar_path, u.avatar_url, u.status
             FROM `{$p}sessions` s
             JOIN `{$p}users` u ON u.id = s.user_id
             WHERE s.token = ? AND s.expires_at > ? AND u.status = 'active'
             LIMIT 1",
            [ self::impronta_sessione( (string) $token ), gmdate( 'Y-m-d H:i:s' ) ], 'ss'
        );

        if ( ! $session ) {
            self::$sessione_finita = true;
            // ⛔ 1.4.3B (RF-BUG-028) — UNA PAGINA GET NON SCRIVE MAI COOKIE: una cache che la memorizza girerebbe il cookie a tutti.
            // Su una GET la pagina d accesso lo sa da sessione_finita() (rinvio con ?scaduta=1); i cookie li pulisce la prima POST.
            if ( self::puo_scrivere_cookie() ) {
                self::clear_cookie();
                // 0.6.09A (RF-BUG-016) — un gettone c era ma la sessione e finita: la pagina
                // d accesso lo DICE, invece di comparire muta come se nulla fosse successo.
                if ( ! headers_sent() ) {
                    setcookie( self::COOKIE_SCADUTA, '1', [ 'expires' => time() + 600, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ] );
                }
            }
            return null;
        }

        // 0.6.09A (RF-BUG-016) — LA SESSIONE SI RINNOVA MENTRE SI LAVORA. Fino alla
        // 0.6.08A durava 8 ore fisse dal login: l owner, dentro dalle 10:13, e stato
        // messo fuori alle 18:24 mentre spediva credenziali. Ora scade dopo 8 ore di
        // INATTIVITA. Si riscrive al massimo una volta ogni 5 minuti (non a ogni richiesta).
        $resta = strtotime( (string) $session['expires_at'] . ' UTC' ) - time();
        // 1.4.3B (RF-BUG-028) — il rinnovo scrive il cookie: si fa solo su una POST (ogni pagina ne manda una appena aperta).
        if ( $resta < self::SESSION_DURATION - self::RINNOVO_OGNI && self::puo_scrivere_cookie() ) {
            Rdacademy_DB::execute(
                "UPDATE `{$p}sessions` SET expires_at = ? WHERE token = ?",
                [ gmdate( 'Y-m-d H:i:s', time() + self::SESSION_DURATION ), self::impronta_sessione( (string) $token ) ], 'ss'
            );
            if ( ! headers_sent() ) {
                self::set_cookie( (string) $token );
            }
        }

        // Carica ruoli
        $roles = Rdacademy_DB::get_results(
            "SELECT r.id, r.name, r.color, r.is_backdoor, r.permissions
             FROM `{$p}roles` r
             JOIN `{$p}user_roles` ur ON ur.role_id = r.id
             WHERE ur.user_id = ?",
            [ (int) $session['user_id'] ], 'i'
        );

        self::$current_user = [
            'id'         => (int) $session['user_id'],
            'first_name' => $session['first_name'],
            'last_name'  => $session['last_name'],
            'email'      => $session['email'],
            'login'      => (string) ( $session['login'] ?? '' ),
            // NUOVO — il tipo di utente decide rail e pagine (REGOLE-FAMIGLIA § 3.3).
            // 1.13.0B (SV-43, SPEC-NOVITA-INTEGRAZIONE-FAMIGLIA § 6.2 punto 3) — tre valori; un valore sconosciuto diventa il PIÙ CHIUSO
            // («integratore»), mai «interno». La colonna è NOT NULL con un valore della lista: il ramo sconosciuto è la difesa.
            'tipo'       => self::tipo_sessione( $session['tipo'] ?? null ),
            'avatar_path'=> $session['avatar_path'] ?? null,
            'avatar_url' => $session['avatar_url']  ?? null,
            'status'     => $session['status'],
            'roles'      => $roles,
            'is_backdoor'=> ! empty( array_filter( $roles, fn($r) => $r['is_backdoor'] ) ),
            // v3.10.38: nozione UNICA di admin per il runtime. true se l'utente ha
            // un ruolo backdoor OPPURE un ruolo con permissions.all truthy
            // (data-driven, allineato a Rdacademy_Security::is_admin()). Calcolato in
            // PHP dai ruoli gia' caricati: nessuna query aggiuntiva.
            'is_admin'   => self::roles_grant_admin( $roles ),
        ];

        return self::$current_user;
    }

    /**
     * RX533D3 — IL NOME CON CUI L UTENTE SI PRESENTA FUORI DAL PRODOTTO.
     *
     * ⛔ PERCHE ESISTE, E PERCHE E UNA SOLA. Il cartiglio degli export scriveva
     * `$u['username'] ?? $u['email']` in DUE posti — il Calendario e l export
     * delle viste. ⛔ La chiave `username` NON esiste in `get_current_user()` e
     * la tabella utenti non ha quella colonna: quel `??` non era un ripiego che
     * ogni tanto scattava, era un dato MORTO che cadeva sull email SEMPRE, per
     * ogni utente e in ogni installazione. Lo smoke del 2026-09-22 lo ha visto
     * sul campo: `Autore: <email dell owner>` dentro un file che si manda ai
     * clienti. `RP-DATO-MORTO-SI-TOGLIE`.
     *
     * ⛔ E LA REGOLA STA QUI, NON NEI DUE CHIAMANTI. Due righe uguali in due
     * moduli sono due verità da tenere allineate a mano, e il giorno che ne
     * nasce una terza si allineano da sole solo nei desideri
     * (`RP-UX-CONFORMITY-CROSS-MODULE`, regola (m) dell owner).
     *
     * ⛔ L EMAIL RESTA COME ULTIMA SPIAGGIA, e non per pigrizia: `first_name` e
     * `last_name` sono NOT NULL ma possono essere stringhe vuote, e un cartiglio
     * con l Autore in bianco non dice a chi chiedere conto del file. Meglio un
     * indirizzo che il vuoto — ma solo quando un nome davvero non c e.
     *
     * @param array|null $u L utente da descrivere. ⛔ Con `null` si prende la
     *                      SESSIONE: l identità non si fa dichiarare da chi la
     *                      usa. L argomento esiste perché la funzione sia
     *                      misurabile senza sessione e senza banca dati
     *                      (Strumento 131, M16).
     */
    public static function nome_visibile( ?array $u = null ): string {
        if ( null === $u ) {
            $u = self::get_current_user();
        }
        if ( ! is_array( $u ) ) {
            return '';
        }
        $nome = trim(
            trim( (string) ( $u['first_name'] ?? '' ) ) . ' ' .
            trim( (string) ( $u['last_name'] ?? '' ) )
        );
        if ( '' !== $nome ) {
            return $nome;
        }
        return trim( (string) ( $u['email'] ?? '' ) );
    }

    /**
     * Verifica se l'utente corrente è autenticato.
     */
    public static function is_logged_in(): bool {
        return self::get_current_user() !== null;
    }

    /**
     * Verifica se l'utente corrente è backdoor.
     */
    public static function is_backdoor(): bool {
        $user = self::get_current_user();
        return $user && $user['is_backdoor'];
    }

    /**
     * v3.10.38 — Un set di ruoli conferisce privilegi di amministrazione?
     *
     * Vero se ALMENO UN ruolo e' backdoor (scialuppa di emergenza) OPPURE
     * ha permissions.all truthy (admin "data-driven", es. "Admin Reldox").
     * Allineato a Rdacademy_Security::is_admin(): la colonna permissions e'
     * una stringa JSON; .all puo' essere salvato come boolean true,
     * stringa "true" o numero 1 — confronto type-flexible.
     *
     * @param array $roles  Righe ruolo come caricate in get_current_user()
     *                       (campi: is_backdoor, permissions).
     * @return bool
     */
    private static function roles_grant_admin( array $roles ): bool {
        foreach ( $roles as $r ) {
            if ( ! empty( $r['is_backdoor'] ) ) {
                return true;
            }
            $raw = $r['permissions'] ?? '';
            if ( $raw === '' || $raw === null ) {
                continue;
            }
            $perm = json_decode( (string) $raw, true );
            if ( ! is_array( $perm ) || ! array_key_exists( 'all', $perm ) ) {
                continue;
            }
            $all = $perm['all'];
            if ( $all === true || $all === 1 || $all === '1'
                 || ( is_string( $all ) && strtolower( $all ) === 'true' ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * v3.10.38 — Comodita': l'utente corrente e' admin (backdoor o permissions.all)?
     */
    public static function is_admin(): bool {
        $user = self::get_current_user();
        return $user && ! empty( $user['is_admin'] );
    }

    /**
     * Crea un token di sessione nel database.
     */
    private static function create_session( int $user_id ): ?string {
        $p = RDACADEMY_TABLE_PREFIX;

        // Pulisce sessioni scadute di questo utente (§ 2.3: istante UTC da PHP)
        Rdacademy_DB::execute(
            "DELETE FROM `{$p}sessions` WHERE user_id = ? AND expires_at < ?",
            [ $user_id, gmdate( 'Y-m-d H:i:s' ) ], 'is'
        );

        $token      = bin2hex( random_bytes( self::TOKEN_LENGTH / 2 ) );
        // ⛔ § 2.3: in RelDox `date()` (fuso di PHP) confrontato poi con NOW() di MySQL.
        $expires_at = gmdate( 'Y-m-d H:i:s', time() + self::SESSION_DURATION );
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua         = substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500 );

        $id = Rdacademy_DB::insert(
            "INSERT INTO `{$p}sessions` (user_id, token, ip_address, user_agent, expires_at)
             VALUES (?, ?, ?, ?, ?)",
            [ $user_id, self::impronta_sessione( $token ), $ip, $ua, $expires_at ],
            'issss'
        );

        return $id ? $token : null;
    }

    /**
     * Imposta il cookie di sessione.
     */
    private static function set_cookie( string $token ): void {
        if ( headers_sent() ) return;
        // 1.4.3B (RF-BUG-028) — con la sessione, il segno per la guardia e il cookie che le cache WordPress riconoscono.
        $o = [ 'expires' => time() + self::SESSION_DURATION, 'path' => '/', 'secure' => is_ssl(), 'samesite' => 'Lax' ];
        setcookie( self::COOKIE_VISTA, self::vista_di( $token ), $o + [ 'httponly' => false ] );
        $gia = (string) ( $_COOKIE[ self::COOKIE_COLLEGATO ] ?? '' );
        setcookie( self::COOKIE_COLLEGATO, preg_match( '/^[a-f0-9]{32}$/', $gia ) ? $gia : bin2hex( random_bytes( 16 ) ), $o + [ 'httponly' => true ] );
        // SameSite=Lax: necessario perché le chiamate fetch() verso admin-ajax.php
        // partono da /academy/ — con Strict il browser non invia il cookie.
        // secure: is_ssl() — sul palco http resta false, in produzione https e vero.
        setcookie(
            self::SESSION_COOKIE,
            $token,
            [
                'expires'  => time() + self::SESSION_DURATION,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Elimina il cookie di sessione.
     */
    private static function clear_cookie(): void {
        if ( headers_sent() ) return;
        setcookie( self::SESSION_COOKIE, '', time() - 3600, '/' );
        setcookie( self::COOKIE_COLLEGATO, '', time() - 3600, '/' );   // 1.4.3B
        setcookie( self::COOKIE_VISTA, '-', [ 'expires' => time() + YEAR_IN_SECONDS, 'path' => '/', 'secure' => is_ssl(), 'httponly' => false, 'samesite' => 'Lax' ] );
    }

    /**
     * 1.4.3B (RF-BUG-028) — SI SCRIVONO COOKIE SOLO SU UNA POST. Una risposta a una GET può finire in una cache che ignora le
     * intestazioni e la rigira a chiunque apra lo stesso indirizzo, Set-Cookie compreso: con dentro il gettone di sessione di
     * un altro. Le POST (accesso, uscita, AJAX, il rinnovo che ogni pagina chiede appena aperta) nessuna cache le memorizza.
     */
    public static function puo_scrivere_cookie(): bool {
        return strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) === 'POST';
    }

    /** 1.4.3B — il segno della sessione: impronta del gettone, non lo apre e non si torna indietro. */
    public static function vista_di( string $token ): string {
        return substr( hash_hmac( 'sha256', 'vista|' . $token, wp_salt( 'nonce' ) ), 0, 20 );
    }

    /** 1.4.3B — il segno della sessione di questa richiesta, «-» se nessuno è collegato. */
    public static function vista(): string {
        $t = self::get_token_from_cookie();
        return ( $t && self::get_current_user() ) ? self::vista_di( (string) $t ) : '-';
    }

    /** 1.4.3B — la richiesta portava un gettone di una sessione finita. */
    public static function sessione_finita(): bool {
        self::get_current_user();
        return self::$sessione_finita;
    }

    /**
     * 1.4.3B — LA POST CHE OGNI PAGINA MANDA APPENA APERTA (e la porta di /academy/): rinnova la sessione se tocca, rimette il segno
     * per la guardia e il cookie delle cache, pulisce i cookie di una sessione finita. Fuori da una POST non fa niente.
     */
    public static function aggiorna_cookie(): void {
        if ( ! self::puo_scrivere_cookie() || headers_sent() ) return;
        $t = self::get_token_from_cookie();
        if ( ! $t ) return;                                // nessun gettone: niente da scrivere (anche una POST da un altro sito)
        if ( self::get_current_user() ) {
            if ( ( $_COOKIE[ self::COOKIE_VISTA ] ?? '' ) !== self::vista_di( (string) $t ) || empty( $_COOKIE[ self::COOKIE_COLLEGATO ] ) ) {
                self::set_cookie( (string) $t );
            }
        }
    }

    /**
     * Legge il token dal cookie.
     */
    private static function get_token_from_cookie(): ?string {
        return $_COOKIE[ self::SESSION_COOKIE ] ?? null;
    }

    /**
     * v3.16.195 (S256, voce 39) — APRE UNA SESSIONE SENZA PASSARE DALLA PASSWORD.
     *
     * Serve a UN caso solo, quello disegnato dalla scena 3 stato E del contratto:
     * l utente ha appena scritto la password nuova consumando un collegamento a
     * uso singolo, e non gli si chiede di ridigitare cio' che ha appena scritto.
     *
     * ⛔ PERCHE STA QUI E NON NEL CHIAMANTE. `create_session()` e `set_cookie()`
     * sono private, e devono restarlo: sono l autorita' unica sulla sessione.
     * Una sessione scritta a mano da un altra classe sarebbe una SECONDA regola
     * sulla durata, sul cookie e sulla pulizia delle scadute — la stessa
     * asimmetria che RP-UNA-MANO-SOLA-SUL-COMANDO esiste per impedire.
     *
     * ⛔ QUESTO METODO NON VERIFICA NIENTE. Non e' una scorciatoia di login: chi
     * lo chiama ha gia' provato l identita' consumando il token
     * (`Rdacademy_Password_Token::consuma()`), che e' l unico chiamante ammesso.
     */
    public static function open_session_for_user( int $user_id ): bool {
        if ( $user_id <= 0 ) return false;
        $token = self::create_session( $user_id );
        if ( ! $token ) return false;
        self::set_cookie( $token );
        self::$current_user = null; // la prossima lettura risolve dal cookie nuovo
        return true;
    }

    /**
     * S590B_SESSIONE — 3.55.03G voce 83 (sicurezza ★★★★★). IL PUNTO UNICO DELL IMPRONTA.
     *
     * Il gettone viaggia nel cookie e basta; nella colonna `sessions.token` c e
     * `s:` + sha256 del gettone (66 caratteri, dentro i 128). Chi legge il
     * database (backup, dump, phpMyAdmin) trova una fotografia che non apre
     * niente. Il prefisso distingue le righe gia convertite da quelle scritte
     * dalle versioni prima (64 esadecimali): la conversione e idempotente.
     */
    public static function impronta_sessione( string $gettone ): string {
        return self::IMPRONTA_SESSIONE . hash( 'sha256', $gettone );
    }

    // TAGLIO: converti_sessioni_in_chiaro() — serviva alle installazioni di RelDox
    // nate prima della 3.55.03G. Il guscio nasce con le impronte: nessuna riga da convertire.

    /**
     * Registra un'attività nel log.
     */
    public static function log_activity( int $user_id, string $entity_type, string $action, string $description = '' ): void {
        $p = RDACADEMY_TABLE_PREFIX;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        Rdacademy_DB::insert(
            "INSERT INTO `{$p}activity_log` (user_id, entity_type, action, description, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?)",
            [ $user_id, $entity_type, $action, $description, $ip, gmdate( 'Y-m-d H:i:s' ) ],
            'isssss'
        );
    }
}
