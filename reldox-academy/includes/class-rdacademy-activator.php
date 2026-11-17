<?php
/**
 * Classe Rdacademy_Activator
 * Eseguita all attivazione del plugin e a ogni cambio di versione (plugins_loaded).
 * Crea le tabelle del NUCLEO del prodotto sul database BASE.
 *
 * Copiato da Reldox_Activator, RelDox CRD 3.55.09G, e ridotto (SPEC § 4, lotto A).
 * TAGLI: migrate_tables() (tutte le ALTER del CRM: offerte, documenti, aziende,
 * caselle email, protocollo), migrate_company_vat_unify(), seed_company_tipo(),
 * le tabelle lookup/custom field/aziende/contatti/documenti/task/offerte/ateco,
 * i ruoli di dominio (SalesData, Cestino, Costi Offerta), la consolidazione del
 * ruolo legacy «Amministratore», l anagrafica aziendale di Idea Technologies nei
 * semi delle impostazioni.
 * Le colonne che in RelDox nascevano per ALTER (login, failed_logins,
 * locked_until, password_expires_at) qui stanno GIA nel CREATE di `users`,
 * con `tipo` (interno | esterno) — SPEC § 4.
 * NUOVO: `user_prefs` nasce qui (in RelDox la creava la classe
 * Reldox_Schema_User_Prefs_V32); maybe_rebuild()/FLAG per il Provisioning Guard.
 * Copia ridotta: via lo spegnimento degli invii del registro e l aggancio delle novità per l integratore.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Activator {

    /**
     * NUOVO — il flag del nucleo, sul BASE (Rdacademy_Option). Il Provisioning Guard
     * lo trova per riflessione (costante che inizia per «rdacademy_»), lo Schema
     * Flag Guard lo confronta con le tabelle vere. Si alza SUL FATTO: dopo che
     * tutte le tabelle del nucleo risultano esistere (REGOLE-FAMIGLIA § 6).
     */
    const FLAG    = 'rdacademy_schema_nucleo_v1';
    const VERSION = 'v1';

    /**
     * Eseguita al click su «Attiva» in WordPress e al cambio di versione.
     */
    public static function activate(): void {
        self::create_tables();
        self::insert_default_data();
        self::set_version();
        if ( self::nucleo_presente() ) {
            Rdacademy_Option::set( self::FLAG, self::VERSION );
        }

        update_option( 'rdacademy_flush_rewrite', true );
        update_option( 'rdacademy_just_activated', true );
    }

    /**
     * Eseguita al click su «Disattiva». Non elimina i dati.
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * NUOVO — il primitivo che il Provisioning Guard esegue a ogni boot.
     * Flag allineato: esce subito, zero DDL. Flag assente (o cancellato dallo
     * Schema Flag Guard perche una tabella manca): ricrea e risemina, tutto
     * idempotente (CREATE TABLE IF NOT EXISTS, INSERT IGNORE).
     */
    public static function maybe_rebuild(): void {
        if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION ) {
            return;
        }
        self::create_tables();
        self::insert_default_data();
        if ( self::nucleo_presente() ) {
            Rdacademy_Option::set( self::FLAG, self::VERSION );
        }
    }

    /** Il flag si alza sul fatto: tutte le tabelle del nucleo esistono davvero. */
    private static function nucleo_presente(): bool {
        foreach ( Rdacademy_Schema_Flag_Guard::tables_for( __CLASS__ ) as $t ) {
            if ( Rdacademy_Schema_Flag_Guard::table_exists( $t ) !== true ) {
                return false;
            }
        }
        return true;
    }

    private static function create_tables(): void {
        $p = RDACADEMY_TABLE_PREFIX;

        // =============================================
        // TABELLA: Ruoli utente (copiata da RelDox)
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}roles` (
                `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name`        VARCHAR(100)    NOT NULL,
                `description` TEXT,
                `color`       VARCHAR(7)      DEFAULT '#6B7280',
                `is_backdoor` TINYINT(1)      DEFAULT 0,
                `is_system`   TINYINT(1)      DEFAULT 0,
                `permissions` JSON,
                `created_at`  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_role_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // =============================================
        // TABELLA: Utenti del prodotto (separati da wp_users)
        // Copiata da RelDox; le colonne delle ALTER storiche sono gia qui
        // (REGOLE-FAMIGLIA § 3.3). `login` facoltativo e mai un email: l univocita
        // la difende Rdacademy_Auth::login() (due righe uguali = rifiuto, BUG-79).
        // 0.11.1A: l email non è univoca nell indice (idx_user_email); due utenti con la stessa email li rifiuta
        // Rdacademy_Auth::login() come «ambiguo».
        // ⛔ Tutte le istanti sono scritti in UTC da PHP (SPEC § 2.3).
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}users` (
                `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `first_name`          VARCHAR(100)    NOT NULL,
                `last_name`           VARCHAR(100)    NOT NULL,
                `email`               VARCHAR(255)    NOT NULL,
                `login`               VARCHAR(100)    NULL,
                `password`            VARCHAR(255)    NOT NULL,
                `tipo`                ENUM('interno','esterno','integratore') NOT NULL DEFAULT 'interno' COMMENT 'Tipo di utente: decide rail e pagine (REGOLE-FAMIGLIA 3.3); integratore = 1.13.0B',
                `avatar_path`         VARCHAR(500)    NULL,
                `avatar_url`          VARCHAR(500)    NULL,
                `status`              ENUM('active','suspended','disabled') DEFAULT 'active',
                `last_login`          TIMESTAMP       NULL,
                `failed_logins`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `locked_until`        TIMESTAMP       NULL COMMENT 'Blocco temporaneo (UTC)',
                `password_expires_at` DATE            NULL COMMENT 'Scadenza messa dall amministratore su un utente',
                `reset_token`         VARCHAR(100)    NULL COMMENT 'Impronta <scopo>:sha256, mai il gettone',
                `reset_expiry`        TIMESTAMP       NULL COMMENT 'Scadenza del gettone (UTC)',
                `created_at`          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                `updated_at`          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_email` (`email`),
                KEY `idx_login` (`login`),
                KEY `idx_status` (`status`),
                KEY `idx_tipo` (`tipo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // =============================================
        // TABELLA: Associazione utenti-ruoli (copiata da RelDox)
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}user_roles` (
                `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    BIGINT UNSIGNED NOT NULL,
                `role_id`    BIGINT UNSIGNED NOT NULL,
                `assigned_at` TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
                `assigned_by` BIGINT UNSIGNED,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_role_id` (`role_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // =============================================
        // TABELLA: Sessioni del prodotto (separate da WP) — copiata da RelDox.
        // `token` = impronta «s:» + sha256 del gettone (66 caratteri), mai il gettone.
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}sessions` (
                `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    BIGINT UNSIGNED NOT NULL,
                `token`      VARCHAR(128)    NOT NULL,
                `ip_address` VARCHAR(45),
                `user_agent` VARCHAR(500),
                `expires_at` TIMESTAMP       NOT NULL,
                `created_at` TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_token` (`token`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_expires` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // =============================================
        // TABELLA: Log accessi (in RelDox nasceva in migrate_tables, v2.9.9)
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}access_log` (
                `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`     BIGINT UNSIGNED NULL,
                `login_input` VARCHAR(255) COMMENT 'Email o username inserito',
                `ip_address`  VARCHAR(45),
                `user_agent`  VARCHAR(500),
                `result`      ENUM('success','failed','locked','expired') NOT NULL,
                `reason`      VARCHAR(255),
                `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user` (`user_id`),
                KEY `idx_result` (`result`),
                KEY `idx_created` (`created_at`),
                KEY `idx_ip` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // =============================================
        // TABELLA: Log attivita generale (copiata da RelDox)
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}activity_log` (
                `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`     BIGINT UNSIGNED,
                `entity_type` VARCHAR(50),
                `entity_id`   BIGINT UNSIGNED,
                `action`      VARCHAR(100)    NOT NULL,
                `description` TEXT,
                `old_value`   TEXT,
                `new_value`   TEXT,
                `ip_address`  VARCHAR(45),
                `created_at`  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_entity` (`entity_type`, `entity_id`),
                KEY `idx_user` (`user_id`),
                KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // =============================================
        // TABELLA: Impostazioni del prodotto (copiata da RelDox)
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}impostazioni` (
                `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `chiave`      VARCHAR(100)    NOT NULL,
                `valore`      LONGTEXT        NULL,
                `tipo`        VARCHAR(20)     NOT NULL DEFAULT 'text' COMMENT 'text, json, bool, image',
                `updated_at`  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_chiave` (`chiave`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // =============================================
        // TABELLA: Preferenze personali — copiata da Reldox_Schema_User_Prefs_V32
        // (BUG-91 di RelDox: un deposito, molte chiavi; una persona, una chiave, UN valore).
        // =============================================
        Rdacademy_DB::raw("
            CREATE TABLE IF NOT EXISTS `{$p}user_prefs` (
                `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    BIGINT UNSIGNED NOT NULL COMMENT 'Proprietario (users.id del prodotto) — scope personale',
                `pref_key`   VARCHAR(64)     NOT NULL COMMENT 'Nome della preferenza — vocabolario in Rdacademy_User_Prefs',
                `pref_json`  TEXT            NULL     COMMENT 'Valore, SEMPRE JSON (anche uno scalare)',
                `updated_at` DATETIME        NOT NULL COMMENT 'Ultimo cambio (UTC)',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_user_key` (`user_id`,`pref_key`),
                KEY `idx_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Inserisce i dati di default. Idempotente (INSERT IGNORE su chiavi uniche).
     */
    private static function insert_default_data(): void {
        $p = RDACADEMY_TABLE_PREFIX;

        // Impostazioni di default — TAGLIO: in RelDox qui c era l anagrafica di
        // Idea Technologies scritta a mano (ragione sociale, indirizzo, Arcore) e i
        // contatori offerte. L azienda la scrive l amministratore in Impostazioni
        // › Azienda (lotto C): le chiavi nascono VUOTE.
        $defaults = [
            [ 'ragione_sociale',      '', 'text' ],
            [ 'branding_logo_url',    '', 'image' ],
            [ 'branding_login_title', '', 'text' ],
        ];
        foreach ( $defaults as $d ) {
            Rdacademy_DB::execute(
                "INSERT IGNORE INTO `{$p}impostazioni` (chiave, valore, tipo) VALUES (?, ?, ?)",
                $d, 'sss'
            );
        }

        // ─────────────────────────────────────────────────────────
        // Ruoli speciali (copiato da RelDox 1C.2.e P1.5)
        // ─────────────────────────────────────────────────────────
        // Service Account (ex-«Backdoor»): fisso di sistema, nascosto da OGNI UI
        // (il CRUD ruoli filtra is_backdoor = 0).
        Rdacademy_DB::raw("
            INSERT IGNORE INTO `{$p}roles`
                (`name`, `description`, `color`, `is_backdoor`, `is_system`, `permissions`)
            VALUES
                ('Service Account', 'Accesso totale di emergenza — riservato sviluppatori', '#DC2626', 1, 1, '{\"all\": true}')
        ");

        // Admin: super-utente del CLIENTE (livello 1). permissions.all = true →
        // Rdacademy_Security::is_admin() lo riconosce data-driven. is_system = 1 →
        // protetto dall eliminazione nel CRUD.
        Rdacademy_DB::raw("
            INSERT IGNORE INTO `{$p}roles`
                (`name`, `description`, `color`, `is_backdoor`, `is_system`, `permissions`)
            VALUES
                ('Admin', 'Amministratore — accesso completo a tutte le funzioni e ai dati', '#2563EB', 0, 1, '{\"all\": true, \"settings\": true, \"users\": true}')
        ");

        // Utente di servizio — S590C_NASCITA di RelDox (voce 82): nasce SENZA
        // password nota. Primo accesso da wp-admin › RelDoxAcademy › Primo accesso
        // (Rdacademy_Accesso_Emergenza, 0.1.02), che mostra un collegamento monouso.
        // ⛔ Nessuna password scritta nel sorgente (D-82).
        $existing = Rdacademy_DB::get_row(
            "SELECT id FROM `{$p}users` WHERE email = ?",
            [ RDACADEMY_SERVICE_EMAIL ], 's'
        );

        if ( ! $existing ) {
            $password_hash = password_hash( bin2hex( random_bytes( 32 ) ), PASSWORD_BCRYPT );
            $user_id = Rdacademy_DB::insert(
                "INSERT INTO `{$p}users` (first_name, last_name, email, password, tipo, status)
                 VALUES (?, ?, ?, ?, 'interno', 'active')",
                [ 'Admin', 'Servizio', RDACADEMY_SERVICE_EMAIL, $password_hash ],
                'ssss'
            );
            if ( $user_id ) {
                Rdacademy_Auth_Seed::assegna_ruoli_di_servizio( (int) $user_id );
            }
        } else {
            // AUTO-ASSIGN (RelDox P1.5.6): l utente di servizio ha ENTRAMBI i ruoli.
            Rdacademy_Auth_Seed::assegna_ruoli_di_servizio( (int) $existing['id'] );
        }
    }

    /**
     * Salva la versione del plugin nelle opzioni WordPress (chiavi di avvio,
     * ESENTI_DAL_1 di Rdacademy_Option).
     *
     * ⛔ CORREZIONE SPEC § 2.4: in RelDox `reldox_db_version` si scriveva col
     * valore fisso '3.1.46' e la closure di plugins_loaded la riscriveva subito
     * dopo con la versione vera — una seconda verita nascosta dietro la prima.
     * Qui si scrive UNA volta, la versione del codice.
     */
    private static function set_version(): void {
        update_option( 'rdacademy_version', RDACADEMY_VERSION );
        update_option( 'rdacademy_db_version', RDACADEMY_VERSION );
        if ( ! get_option( 'rdacademy_installed_at' ) ) {
            update_option( 'rdacademy_installed_at', gmdate( 'Y-m-d H:i:s' ) . ' UTC' );
        }
    }
}

/**
 * NUOVO — l assegnazione dei due ruoli dell utente di servizio, scritta UNA volta
 * (in RelDox era sparsa in due blocchi di insert_default_data, con una query per
 * nome di ruolo e un LIMIT 1 sull «is_backdoor = 1»).
 */
final class Rdacademy_Auth_Seed {
    public static function assegna_ruoli_di_servizio( int $user_id ): void {
        if ( $user_id <= 0 ) return;
        $p = RDACADEMY_TABLE_PREFIX;
        Rdacademy_DB::execute(
            "INSERT IGNORE INTO `{$p}user_roles` (user_id, role_id, assigned_by)
             SELECT ?, r.id, NULL FROM `{$p}roles` r
              WHERE r.is_system = 1 AND r.name IN ('Service Account', 'Admin')",
            [ $user_id ], 'i'
        );
    }
}
