<?php
/**
 * Classe Rdacademy_DB
 * Gestisce la connessione al database BASE del prodotto, separato da WordPress.
 * Usa mysqli direttamente per isolare completamente il prodotto da wpdb.
 *
 * Copiato da Reldox_DB, RelDox CRD 3.55.09G. Tagli: reset_keep_tables() ridotto
 * alle tabelle del guscio (senza Reldox_Table_Owner, che e della topologia CRM).
 * NUOVO: il fuso della connessione e fissato a UTC (correzione SPEC § 2.3).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_DB {

    private static ?mysqli $connection = null;

    /** Ultimo errore SQL reale a runtime (insert/execute), per diagnostica visibile. */
    private static string $last_error = '';

    /** Restituisce (e non azzera) l'ultimo errore SQL reale intercettato. */
    public static function last_error(): string {
        return self::$last_error;
    }

    /**
     * 3.16.53 (VIOLAZIONE 87) — Azzera l'ultimo errore SQL.
     *
     * `last_error()` e' PERSISTENTE: sopravvive alle chiamate riuscite. Chi
     * vuole sapere se una singola chiamata ha sbagliato deve poter partire da
     * pulito, altrimenti e' costretto a confrontare stringhe — e due errori
     * identici in due giri diversi sono indistinguibili proprio nel caso che
     * conta, quello di una causa strutturale che si ripete a ogni giro.
     * `RP-LA-GUARDIA-SI-METTE-DOVE-NASCE-IL-RISCHIO-NON-DOVE-PASSA-IL-DATO`:
     * il rischio nasce qui, non nel chiamante.
     *
     * Additivo: non cambia la semantica di nessun metodo esistente e non
     * tocca i due soli lettori di `last_error()` in tutto il prodotto
     * (censimento all'indietro S109).
     */
    public static function clear_last_error(): void {
        self::$last_error = '';
    }

    /**
     * DEAD-MAN (Livello B) — scope di consenso esplicito.
     * Quando true, un DROP intenzionale su tabella rdacademy_ con righe passa
     * comunque (drop consapevole). Si attiva SOLO dentro with_allowed_drop(),
     * mai globalmente. Default false = protezione attiva.
     */
    private static bool $allow_destructive = false;

    /**
     * Restituisce la connessione al DB BASE del prodotto.
     * Crea la connessione solo al primo utilizzo (singleton).
     *
     * Dalla v3.4.0 (pkg6-db-config) le credenziali vengono lette tramite
     * Rdacademy_DB_Config::get(), che applica la logica wp-config → fallback.
     * La firma resta non-nullable per retrocompatibilità con gli 80+ caller
     * esistenti (vedi decisione D-pkg6-01 in CHANGELOG-pkg6).
     */
    public static function get_connection(): mysqli {
        if ( self::$connection === null ) {

            $cfg = Rdacademy_DB_Config::get();

            // PHP 8.1+ default: mysqli_sql_exception per fail di connect.
            // Catturiamo entrambi i percorsi (exception + connect_error)
            // per uniformare la gestione errore prima del wp_die.
            $err = null;

            try {
                self::$connection = @new mysqli(
                    $cfg['host'],
                    $cfg['user'],
                    $cfg['pass'],
                    $cfg['name']
                );

                if ( self::$connection->connect_error ) {
                    $err              = self::$connection->connect_error;
                    self::$connection = null;
                }
            } catch ( mysqli_sql_exception $e ) {
                $err              = $e->getMessage();
                self::$connection = null;
            }

            if ( $err !== null ) {
                // ⛔ In RelDox il collegamento portava a una pagina wp-admin
                // «impostazioni» che non esiste: si rimanda allo Stato sistema.
                $admin_url = function_exists( 'admin_url' )
                    ? admin_url( 'admin.php?page=rdacademy' )
                    : '#';

                wp_die(
                    '<h2>' . esc_html( RDACADEMY_PRODUCT ) . ' — Errore connessione database</h2>' .
                    '<p>Impossibile connettersi al database BASE. ' .
                    'Vai a <a href="' . esc_url( $admin_url ) . '">Stato sistema</a> ' .
                    'e verifica le costanti RDACADEMY_DB_* in wp-config.php.</p>' .
                    '<p><em>Errore tecnico: ' . esc_html( $err ) . '</em></p>',
                    esc_html( RDACADEMY_PRODUCT ) . ' — DB non disponibile',
                    [ 'response' => 503 ]
                );
            }

            self::$connection->set_charset( $cfg['charset'] );

            // NUOVO — UN OROLOGIO SOLO (SPEC § 2.3, REGOLE-FAMIGLIA § 5).
            // In RelDox la sessione scrive `expires_at` con l ora di PHP (UTC sotto
            // WordPress) e la confronta con NOW() di MySQL (ora del server): sul
            // palco italiano la sessione «scade» due ore prima o dopo. Qui la
            // connessione del prodotto parla UTC: le colonne TIMESTAMP si scrivono
            // e si rileggono in UTC e NOW() coincide con gmdate(). Il codice del
            // guscio passa comunque l istante da PHP (gmdate) e non usa NOW() nei
            // confronti: questa riga e la rete sotto, non la regola.
            try {
                self::$connection->query( "SET time_zone = '+00:00'" );
            } catch ( mysqli_sql_exception $e ) {
                error_log( '[RelDoxAcademy DB] time_zone UTC non impostato: ' . $e->getMessage() );
            }
        }

        return self::$connection;
    }

    /**
     * Apre una connessione DB ISOLATA (non singleton) usando i parametri
     * forniti, o quelli correnti se $override è null.
     *
     * Pensata per la diagnostica (endpoint dbconfig_test): apre, misura,
     * chiude. Non tocca il singleton self::$connection in nessun caso.
     *
     * Ritorna null in caso di fallimento — il chiamante è responsabile
     * di interpretare l'errore tramite il campo error_message del result.
     *
     * @param array|null $override [host, name, user, pass, charset] opzionale
     * @return array{
     *   connection: ?mysqli,
     *   ok: bool,
     *   error_message: string,
     *   latency_ms: float
     * }
     * @since 3.4.0
     */
    public static function try_connect( ?array $override = null ): array {

        $cfg = $override ?? Rdacademy_DB_Config::get();

        $start = microtime( true );

        // PHP 8.1+ con MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT (default)
        // lancia mysqli_sql_exception in caso di fail di connect, invece di
        // popolare $conn->connect_error. Gestiamo entrambi i percorsi per
        // robustezza (configurazioni con mysqli_report diverso).
        try {
            $conn = @new mysqli(
                $cfg['host']    ?? '',
                $cfg['user']    ?? '',
                $cfg['pass']    ?? '',
                $cfg['name']    ?? ''
            );

            if ( $conn->connect_error ) {
                $elapsed = ( microtime( true ) - $start ) * 1000.0;
                return [
                    'connection'    => null,
                    'ok'            => false,
                    'error_message' => $conn->connect_error,
                    'latency_ms'    => round( $elapsed, 2 ),
                ];
            }
        } catch ( mysqli_sql_exception $e ) {
            $elapsed = ( microtime( true ) - $start ) * 1000.0;
            return [
                'connection'    => null,
                'ok'            => false,
                'error_message' => $e->getMessage(),
                'latency_ms'    => round( $elapsed, 2 ),
            ];
        }

        if ( ! empty( $cfg['charset'] ) ) {
            try {
                $conn->set_charset( $cfg['charset'] );
            } catch ( mysqli_sql_exception $e ) {
                // Charset fallito non è blocco — la connessione è OK,
                // segnaliamo nel messaggio ma riportiamo ok=true.
                // (Comportamento conservativo per non rompere setup esistenti.)
            }
        }

        $elapsed = ( microtime( true ) - $start ) * 1000.0;

        return [
            'connection'    => $conn,
            'ok'            => true,
            'error_message' => '',
            'latency_ms'    => round( $elapsed, 2 ),
        ];
    }

    /**
     * Esegue una query con prepared statement.
     * Uso: Rdacademy_DB::query("SELECT * FROM rdacademy_users WHERE id = ?", [1], "i")
     *
     * @param string $sql    Query SQL con placeholder ?
     * @param array  $params Parametri da legare
     * @param string $types  Tipi dei parametri (i=intero, s=stringa, d=decimale, b=blob)
     * @return mysqli_result|bool
     */
    /**
     * ULTIMO ERRORE — BUG-27 (S93), RP-ERRORE-NON-SI-TRAVESTE-DA-DATO.
     *
     * Fino alla 3.16.34 un fallimento SQL usciva da qui come `false`, che
     * get_row() traduceva in `null` e get_results() in `[]`. Piu su, chi
     * contava le righe leggeva quel vuoto come «zero righe». Un errore
     * indossava i panni di un dato, e nessuno poteva accorgersene: e' il
     * motivo per cui BUG-27 e' rimasto invisibile per undici versioni.
     *
     * La correzione e' ADDITIVA (lezione S92: quando un valore ha due
     * pubblici, si AGGIUNGE, non si ridefinisce). I valori di ritorno di
     * query(), get_row() e get_results() NON cambiano: nessuno dei
     * chiamanti esistenti deve essere toccato. Chi vuole sapere se quel
     * vuoto era un errore ora lo puo' CHIEDERE.
     *
     * RP-DUE-MODI-DI-MYSQLI: mysqli_report non e' una costante d'ambiente.
     * Se il prodotto ha alzato il riporto per eccezioni, prepare/execute
     * LANCIANO invece di tornare false. Qui l'eccezione viene registrata e
     * poi RILANCIATA identica: chi oggi la cattura continua a catturarla.
     *
     * @var array{errno:int,error:string,sql:string}|null
     */
    private static $last_err = null;

    /**
     * NON sostituisce last_error(): quella torna una STRINGA, ha un lettore
     * gia' in campo (public/views/contatto-form.php r.463) ed e' alimentata
     * da insert(). Questo e' un canale SEPARATO e piu' ricco, aggiunto
     * accanto al vecchio invece che al suo posto (lezione S92).
     *
     * L'ultimo errore SQL, oppure null se l'ultima query e' andata a buon
     * fine. Va letto SUBITO dopo la chiamata: non e' uno storico.
     *
     * @return array{errno:int,error:string,sql:string}|null
     */
    public static function last_sql_error(): ?array {
        return self::$last_err;
    }

    /** Registra l'errore e lo lascia disponibile a chi lo chiede. */
    private static function record_error( int $errno, string $error, string $sql ): void {
        self::$last_err = [
            'errno' => $errno,
            'error' => $error,
            'sql'   => $sql,
        ];
    }

    public static function query( string $sql, array $params = [], string $types = '' ) {
        self::$last_err = null;

        // DEAD-MAN (Livello B): rifiuta un DROP TABLE su tabella rdacademy_ con
        // righe>0 (salvo allow esplicito). Fail-safe: in blocco NON esegue,
        // logga e ritorna false. I non-DROP passano senza alcun costo di DB.
        if ( ! self::deadman_permit( $sql ) ) {
            self::record_error( 0, 'bloccata dal dead-man (DROP su tabella con righe)', $sql );
            return false;
        }
        $conn = self::get_connection();

        try {
            $stmt = $conn->prepare( $sql );

            if ( ! $stmt ) {
                self::record_error( (int) $conn->errno, (string) $conn->error, $sql );
                error_log( 'Reldox DB prepare error: ' . $conn->error . ' | SQL: ' . $sql );
                return false;
            }

            if ( ! empty( $params ) && ! empty( $types ) ) {
                $stmt->bind_param( $types, ...$params );
            }

            $ok = $stmt->execute();
            if ( ! $ok ) {
                self::record_error( (int) $stmt->errno, (string) $stmt->error, $sql );
                error_log( 'Reldox DB execute error: ' . $stmt->error . ' | SQL: ' . $sql );
                $stmt->close();
                return false;
            }
        } catch ( mysqli_sql_exception $e ) {
            // Modo a eccezioni: si registra e si RILANCIA senza alterare nulla.
            self::record_error( (int) $e->getCode(), $e->getMessage(), $sql );
            error_log( 'Reldox DB exception: ' . $e->getMessage() . ' | SQL: ' . $sql );
            throw $e;
        }

        $result = $stmt->get_result();          // false per DML (no result set) ma esecuzione OK
        $stmt->close();
        return ( $result === false ) ? true : $result;  // DML -> true; SELECT -> result set
    }

    /**
     * Esegue una query e restituisce tutti i risultati come array.
     */
    public static function get_results( string $sql, array $params = [], string $types = '' ): array {
        $result = self::query( $sql, $params, $types );

        if ( ! $result || $result === true ) {
            return [];
        }

        $rows = [];
        while ( $row = $result->fetch_assoc() ) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Esegue una query e restituisce una singola riga.
     */
    public static function get_row( string $sql, array $params = [], string $types = '' ): ?array {
        $result = self::query( $sql, $params, $types );

        if ( ! $result || $result === true ) {
            return null;
        }

        return $result->fetch_assoc() ?: null;
    }

    /**
     * Esegue una query di inserimento e restituisce l'ID inserito.
     */
    public static function insert( string $sql, array $params = [], string $types = '' ): int {
        try {
            $conn = self::get_connection();
            $stmt = $conn->prepare( $sql );

            if ( ! $stmt ) {
                error_log( 'Reldox DB insert prepare error: ' . $conn->error );
                return 0;
            }

            if ( ! empty( $params ) && ! empty( $types ) ) {
                $stmt->bind_param( $types, ...$params );
            }

            if ( ! $stmt->execute() ) {
                self::$last_error = (string) $stmt->error;
                error_log( 'Reldox DB insert execute error: ' . $stmt->error . ' | SQL: ' . $sql );
                $stmt->close();
                return 0;
            }
            $insert_id = (int) $conn->insert_id;
            $stmt->close();

            return $insert_id;
        } catch ( mysqli_sql_exception $e ) {
            // LOTTO 5 voce 26 — i due modi di mysqli si reggono entrambi.
            $cod = $e->getCode() ?: ( isset( $conn ) ? ( $conn->errno ?? 0 ) : 0 );
            error_log( '[Reldox DB] insert: mysqli ha lanciato, errno ' . $cod . ': ' . $e->getMessage() );
            self::$last_error = (string) $e->getMessage();
            throw $e;
        }
    }

    /**
     * Esegue una query di aggiornamento/eliminazione e restituisce le righe affette.
     */
    public static function execute( string $sql, array $params = [], string $types = '' ): int {
        try {
            $conn = self::get_connection();
            $stmt = $conn->prepare( $sql );

            if ( ! $stmt ) {
                error_log( 'Reldox DB execute prepare error: ' . $conn->error );
                return 0;
            }

            if ( ! empty( $params ) && ! empty( $types ) ) {
                $stmt->bind_param( $types, ...$params );
            }

            if ( ! $stmt->execute() ) {
                self::$last_error = (string) $stmt->error;
                error_log( 'Reldox DB execute execute error: ' . $stmt->error . ' | SQL: ' . $sql );
                $stmt->close();
                return 0;
            }
            $affected = $stmt->affected_rows;
            $stmt->close();

            return $affected;
        } catch ( mysqli_sql_exception $e ) {
            // LOTTO 5 voce 26 — i due modi di mysqli si reggono entrambi.
            $cod = $e->getCode() ?: ( isset( $conn ) ? ( $conn->errno ?? 0 ) : 0 );
            error_log( '[Reldox DB] execute: mysqli ha lanciato, errno ' . $cod . ': ' . $e->getMessage() );
            self::$last_error = (string) $e->getMessage();
            throw $e;
        }
    }

    /**
     * Esegue una query raw (per CREATE TABLE durante l'installazione).
     * NON usare per query con input utente.
     */
    /**
     * BUG-23 PASSO 7 (3.16.30) — raw() SU UNA CONNESSIONE DICHIARATA.
     *
     * raw() prende la connessione da get_connection(), cablata sul BASE: e' il
     * motivo per cui le sette tabelle `offerte*` dell'Activator nascevano sul
     * _2 anche col _3 configurato. Qui la connessione arriva dal chiamante,
     * che la legge dal registro delle sorgenti (in RelDox Reldox_Table_Owner::conn_for).
     *
     * Il chokepoint DEAD-MAN e' lo STESSO di raw(): cambia dove si esegue, non
     * cosa e' permesso eseguire. Fail-safe: connessione non valida -> false e
     * un error_log, mai un fallback silenzioso su un'altra connessione, che
     * sarebbe esattamente il difetto che questa patch chiude.
     *
     * @param mysqli|null $conn connessione su cui eseguire
     */
    public static function raw_on( $conn, string $sql ): bool {
        try {
            if ( ! self::deadman_permit( $sql ) ) {
                return false;
            }
            if ( ! ( $conn instanceof mysqli ) ) {
                error_log( 'Reldox DB raw_on: connessione non valida | SQL: ' . substr( $sql, 0, 100 ) );
                return false;
            }
            $result = $conn->query( $sql );
            if ( ! $result ) {
                error_log( 'Reldox DB raw_on error: ' . $conn->error . ' | SQL: ' . substr( $sql, 0, 100 ) );
            }
            return (bool) $result;
        } catch ( mysqli_sql_exception $e ) {
            // LOTTO 5 voce 26 — i due modi di mysqli si reggono entrambi.
            $cod = $e->getCode() ?: ( isset( $conn ) ? ( $conn->errno ?? 0 ) : 0 );
            error_log( '[Reldox DB] raw_on: mysqli ha lanciato, errno ' . $cod . ': ' . $e->getMessage() );
            throw $e;
        }
    }

    public static function raw( string $sql ): bool {
        try {
            // DEAD-MAN (Livello B): stesso chokepoint di query() sul percorso raw.
            if ( ! self::deadman_permit( $sql ) ) {
                return false;
            }
            $conn = self::get_connection();
            $result = $conn->query( $sql );

            if ( ! $result ) {
                error_log( 'Reldox DB raw error: ' . $conn->error . ' | SQL: ' . substr( $sql, 0, 100 ) );
            }

            return (bool) $result;
        } catch ( mysqli_sql_exception $e ) {
            // LOTTO 5 voce 26 — i due modi di mysqli si reggono entrambi.
            $cod = $e->getCode() ?: ( isset( $conn ) ? ( $conn->errno ?? 0 ) : 0 );
            error_log( '[Reldox DB] raw: mysqli ha lanciato, errno ' . $cod . ': ' . $e->getMessage() );
            throw $e;
        }
    }

    /**
     * Chiude la connessione.
     */
    public static function close(): void {
        if ( self::$connection !== null ) {
            self::$connection->close();
            self::$connection = null;
        }
    }

    /**
     * Sanitizza un valore per uso sicuro nelle query.
     */
    public static function escape( string $value ): string {
        return self::get_connection()->real_escape_string( $value );
    }

    /**
     * FONTE UNICA DI VERITA' del Reset dati operativi (RP-RESET-ALLOWLIST).
     *
     * Restituisce il set KEEP: i basename (senza prefisso) delle tabelle di
     * CONFIGURAZIONE che l azzeramento dei dati operativi NON deve MAI svuotare.
     * Tutto cio' che e' rdacademy_* e NON in questo set viene svuotato via
     * ALLOWLIST DINAMICA (SHOW TABLES − KEEP): ogni tabella futura e azzerata
     * di default, si mantiene solo questo piccolo elenco (copiato da RelDox).
     *
     * DOVE SI AGGIUNGE UNA TABELLA NUOVA: dal filtro `rdacademy_reset_keep_tables`,
     * con il motivo scritto accanto, nel file del lotto che la crea.
     *
     * @return array<int,string> basename tabella (senza prefisso).
     */
    public static function reset_keep_tables(): array {
        // TAGLIO (lotto A): in RelDox il set nasce da DUE fonti, l elenco
        // proprio e il registro delle tabelle condivise della pagina Tabelle.
        // Il registro dei proprietari e della topologia CRM e non entra nel
        // guscio: resta l elenco PROPRIO, con il motivo accanto a ogni voce.
        // Il lotto C (Impostazioni › Pericolo, Tabelle Funzionali) ci aggiunge
        // le sue tabelle dal filtro, senza ricopiare questo elenco.
        $proprie = array(
            // Sistema / auth — l installazione deve restare accessibile.
            'roles', 'users', 'user_roles', 'sessions',
            // Preferenze dell utente: lavoro dell utente, non dato operativo.
            'user_prefs',
            // Impostazioni e archivio di stato del prodotto (flag di schema compresi).
            'impostazioni', 'options',
        );
        $out = (array) apply_filters( 'rdacademy_reset_keep_tables', $proprie );
        $out = array_values( array_unique( array_map( 'strval', $out ) ) );
        sort( $out );
        return $out;
    }

    /**
     * DEAD-MAN (Livello B) — decisione PURA, parte 1: parsing.
     *
     * Riconosce se una stringa SQL e un DROP TABLE e, in caso, ne estrae
     * il nome NUDO della tabella (senza backtick, senza IF EXISTS, senza
     * qualificatore schema.). Ritorna null se l'SQL non e un DROP TABLE.
     *
     * Funzione PURA: nessun accesso al DB. Cosi e collaudabile a banco
     * (Strumento 47) ed e IDENTICA ovunque venga usata, incluso il futuro
     * cablaggio in query()/raw() (RP-DIAG-NO-ASSUME-WIRING: una sola fonte
     * di verita per la decisione). NON esegue nulla.
     *
     * @param  string      $sql
     * @return string|null nome tabella (senza prefisso schema) oppure null
     */
    public static function parse_drop_target( string $sql ): ?string {
        $s = trim( $sql );
        // Deve iniziare con DROP [TEMPORARY] TABLE [IF EXISTS] <target>...
        if ( ! preg_match(
            '/^\s*DROP\s+(?:TEMPORARY\s+)?TABLE\s+(?:IF\s+EXISTS\s+)?(.+?)\s*;?\s*$/is',
            $s,
            $m
        ) ) {
            return null;
        }
        $target = trim( $m[1] );
        // Primo token (ignora eventuali RESTRICT/CASCADE o liste multiple).
        $parts  = preg_split( '/[\s,]+/', $target );
        $target = isset( $parts[0] ) ? $parts[0] : '';
        // Rimuovi backtick e prefisso schema (`db`.`tbl` oppure db.tbl).
        $target = str_replace( '`', '', $target );
        if ( strpos( $target, '.' ) !== false ) {
            $seg    = explode( '.', $target );
            $target = end( $seg );
        }
        return ( $target !== '' ) ? $target : null;
    }

    /**
     * DEAD-MAN (Livello B) — decisione PURA, parte 2: verdetto.
     *
     * Dato il nome tabella (o null se non e un drop), il numero di righe e
     * il flag allow, decide se BLOCCARE il DROP. Blocca SOLO se TUTTE queste
     * condizioni sono vere:
     *   - e effettivamente un DROP di tabella ($table !== null), E
     *   - la tabella e del prodotto (prefisso RDACADEMY_TABLE_PREFIX), E
     *   - NON e stato concesso l'allow esplicito, E
     *   - la tabella contiene dati ($rowcount > 0).
     * Tutto il resto PASSA: non-drop, tabella non-reldox, tabella vuota,
     * allow esplicito.
     *
     * Funzione PURA: nessun DB. Il conteggio righe lo fornisce il chiamante
     * (che nel cablaggio leggera SELECT COUNT(*) PRIMA di eseguire il DROP),
     * cosi questa resta deterministica e golden-testabile a banco.
     *
     * @param  string|null $table
     * @param  int         $rowcount
     * @param  bool        $allow
     * @return bool        true = BLOCCA (non eseguire), false = PASSA
     */
    public static function should_block_drop( ?string $table, int $rowcount, bool $allow ): bool {
        if ( $table === null ) {
            return false; // non e un DROP TABLE
        }
        if ( strpos( $table, RDACADEMY_TABLE_PREFIX ) !== 0 ) {
            return false; // non e una tabella del prodotto
        }
        if ( $allow ) {
            return false; // drop legittimo concesso esplicitamente
        }
        return $rowcount > 0; // blocca solo se contiene dati
    }

    /**
     * DEAD-MAN (Livello B) — valvola di consenso esplicito.
     *
     * Esegue $fn con lo scope destruttivo attivo: un DROP intenzionale su
     * tabella rdacademy_ con righe passa comunque. Ripristina SEMPRE lo stato
     * precedente nel finally (nesting-safe). E' l'unica via consapevole per
     * far passare un drop-con-righe, l'equivalente a runtime del marcatore
     * // RELDOX-GUARD-ALLOW-DROP usato dal guard al build.
     *
     * Uso: Rdacademy_DB::with_allowed_drop( fn () => Rdacademy_DB::raw( $sql_drop ) );
     *
     * @param  callable $fn
     * @return mixed     il valore ritornato da $fn
     */
    public static function with_allowed_drop( callable $fn ) {
        $prev = self::$allow_destructive;
        self::$allow_destructive = true;
        try {
            return $fn();
        } finally {
            self::$allow_destructive = $prev;
        }
    }

    /**
     * DEAD-MAN (Livello B) — chokepoint: decide se un SQL puo procedere.
     *
     * Chiamato all'ingresso di query() e raw(). Se l'SQL non e un DROP TABLE,
     * passa senza toccare il DB. Se lo e, legge il COUNT(*) reale della tabella
     * e applica should_block_drop: in blocco logga e ritorna false (fail-safe:
     * il DROP NON viene eseguito, nessun dato perso). Il conteggio usa la
     * connessione RAW diretta, MAI self::query, per non ricorsare nel dead-man.
     *
     * @param  string $sql
     * @return bool   true = puoi eseguire, false = BLOCCATO (non eseguire)
     */
    private static function deadman_permit( string $sql ): bool {
        $tbl = self::parse_drop_target( $sql );
        if ( $tbl === null ) {
            return true; // non e un DROP TABLE: nessun controllo, nessun costo
        }
        $rows = self::deadman_rowcount( $tbl );
        if ( self::should_block_drop( $tbl, $rows, self::$allow_destructive ) ) {
            error_log(
                '[Rdacademy_DB DEAD-MAN] DROP RIFIUTATO su `' . $tbl . '` (' . $rows
                . ' righe, nessun allow). Per un drop intenzionale usa '
                . 'Rdacademy_DB::with_allowed_drop(). SQL: ' . substr( $sql, 0, 120 )
            );
            return false;
        }
        return true;
    }

    /**
     * DEAD-MAN (Livello B) — COUNT(*) isolato per il chokepoint.
     *
     * Legge il numero di righe di $table usando la connessione RAW diretta
     * ($conn->query), NON self::query, per evitare ricorsione nel dead-man.
     * Tabella assente o conteggio fallito -> 0: un drop su tabella inesistente
     * o illeggibile e innocuo per i dati; il dead-man protegge le tabelle CON
     * righe. Il nome tabella e gia stato estratto da parse_drop_target dallo
     * stesso SQL in ingresso; ri-escape difensivo prima dell'interpolazione.
     *
     * @param  string $table
     * @return int
     */
    private static function deadman_rowcount( string $table ): int {
        try {
            $conn = self::get_connection();
            $safe = $conn->real_escape_string( $table );
            $res  = $conn->query( "SELECT COUNT(*) AS n FROM `{$safe}`" );
            if ( $res instanceof mysqli_result ) {
                $r = $res->fetch_assoc();
                $res->free();
                return (int) ( $r['n'] ?? 0 );
            }
        } catch ( Throwable $e ) {
            // tabella assente o errore -> 0 (nessun dato da proteggere)
        }
        return 0;
    }
}
