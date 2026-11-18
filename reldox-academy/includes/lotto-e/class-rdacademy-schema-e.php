<?php
/**
 * Rdacademy_Schema_E_V1 — LO SCHEMA DEL LOTTO E: LE TABELLE PROPRIE DELL ACADEMY.
 * =============================================================================
 * Stessa grammatica di Rdacademy_Schema_C_V1: solo CREATE TABLE IF NOT EXISTS (e, per le versioni che verranno,
 * SHOW COLUMNS → ALTER ADD; mai MODIFY, mai DROP), flag sul BASE alzato SUL FATTO, passa dal Provisioning Guard
 * (includes/carico-e.php).
 *
 * CHE COSA CREA (analisi tecnica § 5, «Proprie del prodotto»; nomi senza prefisso, il prefisso lo mette il BASE)
 *  1. `carichi`         — ogni caricamento dei materiali: etichetta, commit del repository dei materiali, quando, chi.
 *  2. `percorsi`        — prodotto, ruolo (pubblico), titolo, ordine, lingua.
 *  3. `lezioni`         — `id` nella forma <prodotto>-<percorso>-L<nn>, percorso, titolo, vale per, durata, HTML già pronto
 *                         (il Markdown si converte al caricamento), lingua, versione del materiale.
 *  4. `domande`         — le domande chiuse della verifica e la risposta giusta. ⛔ Non vanno mai al browser: l esito si
 *                         calcola sul server (§ 7.3).
 *  5. `aziende`         — l identificativo dell azienda (dalla chiave); fino ad allora la coppia prodotto + dominio.
 *  6. `persone_azienda` — l utente (`users.id`), la sua azienda, prodotto e versione, ruolo nel prodotto e, per l accesso
 *                         dal prodotto, l identificativo della persona nel prodotto (nessuna mail né password in più).
 *  7. `registro`        — utente, lezione, aperta il, completata il, esito (giuste su totali), tentativi.
 *  E la Tabella Funzionale dei testi delle mail (Rdacademy_Testi_Notifiche::create), col set di partenza alla nascita.
 *
 * Tutte nascono vuote: le riempiono il caricamento dei materiali (blocco M) e l uso.
 *
 * ⛔ IL FLAG SI ALZA SUL FATTO: dopo che le tabelle risultano esistere davvero. Senza fatto, il boot successivo ritenta.
 *
 * @package RelDoxAcademy
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_E_V1 {

	const FLAG    = 'rdacademy_schema_lotto_e_v1';
	const VERSION = 'v1';

	/** Le tabelle proprie, nell ordine in cui nascono. */
	const TABELLE = array( 'carichi', 'percorsi', 'lezioni', 'domande', 'aziende', 'persone_azienda', 'registro' );

	public static function maybe_rebuild(): void {
		if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION && self::presente() ) {
			return;
		}
		self::create();
		if ( class_exists( 'Rdacademy_Testi_Notifiche' ) ) {
			Rdacademy_Testi_Notifiche::create();
			Rdacademy_Testi_Notifiche::nascita();
		}
		if ( self::presente() ) {
			Rdacademy_Option::set( self::FLAG, self::VERSION );
		}
	}

	public static function presente(): bool {
		$nomi = array();
		foreach ( self::TABELLE as $t ) {
			$nomi[] = RDACADEMY_TABLE_PREFIX . $t;
		}
		$r = Rdacademy_DB::get_row(
			"SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (" . implode( ',', array_fill( 0, count( $nomi ), '?' ) ) . ")",
			$nomi, str_repeat( 's', count( $nomi ) )
		);
		if ( ! $r || (int) $r['n'] !== count( self::TABELLE ) ) return false;
		if ( class_exists( 'Rdacademy_Testi_Notifiche' ) && ! Rdacademy_Testi_Notifiche::presente() ) return false;
		return true;
	}

	private static function create(): void {
		$p = RDACADEMY_TABLE_PREFIX;
		$c = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}carichi` (
				`id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`etichetta`    VARCHAR(190) NOT NULL DEFAULT '',
				`commit_fonte` VARCHAR(40)  NOT NULL DEFAULT '' COMMENT 'Commit del repository dei materiali da cui viene il carico',
				`caricato_il`  DATETIME     NULL DEFAULT NULL COMMENT 'UTC',
				`caricato_da`  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'users.id',
				PRIMARY KEY (`id`),
				KEY `idx_caricato_il` (`caricato_il`)
			) {$c}
		" );

		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}percorsi` (
				`id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`prodotto`  VARCHAR(40)  NOT NULL DEFAULT '',
				`codice`    VARCHAR(60)  NOT NULL DEFAULT '' COMMENT 'Il <percorso> dell id delle lezioni',
				`ruolo`     VARCHAR(60)  NOT NULL DEFAULT '' COMMENT 'Il pubblico del percorso',
				`titolo`    VARCHAR(190) NOT NULL DEFAULT '',
				`ordine`    INT          NOT NULL DEFAULT 0,
				`lingua`    CHAR(2)      NOT NULL DEFAULT 'it',
				PRIMARY KEY (`id`),
				UNIQUE KEY `uk_percorso` (`prodotto`,`codice`,`lingua`),
				KEY `idx_prodotto_ruolo` (`prodotto`,`ruolo`)
			) {$c}
		" );

		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}lezioni` (
				`id`          VARCHAR(100) NOT NULL COMMENT '<prodotto>-<percorso>-L<nn>',
				`lingua`      CHAR(2)      NOT NULL DEFAULT 'it',
				`percorso_id` BIGINT UNSIGNED NOT NULL COMMENT 'percorsi.id',
				`titolo`      VARCHAR(190) NOT NULL DEFAULT '',
				`vale_per`    VARCHAR(40)  NOT NULL DEFAULT '' COMMENT 'Versione del prodotto a cui vale',
				`durata`      SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minuti',
				`html`        MEDIUMTEXT   NOT NULL COMMENT 'Già convertito dal Markdown al caricamento',
				`versione`    VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'Versione del materiale',
				PRIMARY KEY (`id`,`lingua`),
				KEY `idx_percorso` (`percorso_id`)
			) {$c}
		" );

		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}domande` (
				`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`lezione_id` VARCHAR(100) NOT NULL COMMENT 'lezioni.id',
				`lingua`     CHAR(2)      NOT NULL DEFAULT 'it',
				`ordine`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
				`testo`      TEXT         NOT NULL,
				`risposte`   TEXT         NOT NULL COMMENT 'JSON: le risposte della domanda chiusa, nell ordine',
				`giusta`     TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Indice della risposta giusta: mai al browser',
				PRIMARY KEY (`id`),
				KEY `idx_lezione` (`lezione_id`,`lingua`,`ordine`)
			) {$c}
		" );

		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}aziende` (
				`id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`identificativo`  VARCHAR(64)  NULL DEFAULT NULL COMMENT 'Dalla chiave di licenza; NULL finché la chiave non lo porta',
				`prodotto`        VARCHAR(40)  NOT NULL DEFAULT '',
				`dominio`         VARCHAR(190) NOT NULL DEFAULT '',
				`created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uk_identificativo` (`identificativo`),
				UNIQUE KEY `uk_prodotto_dominio` (`prodotto`,`dominio`)
			) {$c}
		" );

		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}persone_azienda` (
				`id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id`          BIGINT UNSIGNED NOT NULL COMMENT 'users.id',
				`azienda_id`       BIGINT UNSIGNED NOT NULL COMMENT 'aziende.id',
				`prodotto`         VARCHAR(40)  NOT NULL DEFAULT '',
				`versione`         VARCHAR(20)  NOT NULL DEFAULT '' COMMENT 'Versione del prodotto in uso',
				`ruolo`            VARCHAR(60)  NOT NULL DEFAULT '' COMMENT 'Ruolo nel prodotto',
				`id_nel_prodotto`  VARCHAR(64)  NULL DEFAULT NULL COMMENT 'Identificativo della persona nel prodotto (accesso dal prodotto)',
				PRIMARY KEY (`id`),
				UNIQUE KEY `uk_persona` (`user_id`,`azienda_id`,`prodotto`),
				UNIQUE KEY `uk_nel_prodotto` (`azienda_id`,`prodotto`,`id_nel_prodotto`),
				KEY `idx_azienda` (`azienda_id`)
			) {$c}
		" );

		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}registro` (
				`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id`        BIGINT UNSIGNED NOT NULL COMMENT 'users.id',
				`lezione_id`     VARCHAR(100) NOT NULL COMMENT 'lezioni.id',
				`aperta_il`      DATETIME     NULL DEFAULT NULL COMMENT 'UTC',
				`completata_il`  DATETIME     NULL DEFAULT NULL COMMENT 'UTC: quando la verifica è fatta',
				`giuste`         TINYINT UNSIGNED NULL DEFAULT NULL,
				`totali`         TINYINT UNSIGNED NULL DEFAULT NULL,
				`tentativi`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uk_persona_lezione` (`user_id`,`lezione_id`),
				KEY `idx_lezione` (`lezione_id`)
			) {$c}
		" );
	}
}
