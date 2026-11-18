<?php
/**
 * Rdacademy_Schema_Datasources_V1 — IL REGISTRO DELLE SORGENTI, SUL BASE.
 * -----------------------------------------------------------------------------
 * Copiato da Reldox_Schema_DataSources_V32, RelDox CRD 3.55.09G.
 *
 * Crea la tabella `data_sources` sul BASE: il registro delle sorgenti dati
 * (chiavi della mappa Rdacademy_Sorgenti). Ogni riga descrive COME connettersi a un
 * database dedicato; la password è cifrata a riposo (busta `enc1:`,
 * Rdacademy_Cifra) e non lascia mai il backend in chiaro.
 *
 * REGOLA D ORO (copiata): sicurezza e identificativi restano nel BASE. Questa
 * tabella custodisce solo i parametri di connessione, non dati di business.
 *
 * Nota nome colonna (copiata): `KEY` è parola riservata MySQL → `source_key`.
 *
 * CREATE TABLE IF NOT EXISTS — mai DROP+CREATE, mai ALTER: la tabella contiene
 * configurazioni reali.
 *
 * ⛔ CORRETTO RISPETTO A RELDOX: maybe_rebuild() alzava il flag SEMPRE, anche
 * se create() era fallita (il difetto di BUG-07 che il Provisioning Guard
 * insegue). Qui il flag si alza sul FATTO: la tabella esiste davvero.
 * TAGLIO: i due parametri del percorso di reset (connessione, prefisso) e
 * force_rebuild(): il guscio non ha il percorso di reset.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_Datasources_V1 {

	const FLAG    = 'rdacademy_schema_datasources_v1';
	const VERSION = 'v1';
	const TABLE   = 'data_sources';

	public static function maybe_rebuild(): void {
		if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION ) {
			return;
		}
		self::create();
		if ( Rdacademy_Schema_Flag_Guard::table_exists( self::TABLE ) === true ) {
			Rdacademy_Option::set( self::FLAG, self::VERSION );
		}
	}

	public static function create(): bool {
		$p = RDACADEMY_TABLE_PREFIX;
		return Rdacademy_DB::raw( "CREATE TABLE IF NOT EXISTS `{$p}data_sources` (
			id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			source_key   VARCHAR(32)  NOT NULL,
			label        VARCHAR(100) NOT NULL DEFAULT '',
			db_host      VARCHAR(255) NOT NULL DEFAULT '',
			db_port      INT UNSIGNED NOT NULL DEFAULT 3306,
			db_name      VARCHAR(128) NOT NULL DEFAULT '',
			db_user      VARCHAR(128) NOT NULL DEFAULT '',
			db_pass_enc  TEXT         NULL,
			charset      VARCHAR(32)  NOT NULL DEFAULT 'utf8mb4',
			enabled      TINYINT(1)   NOT NULL DEFAULT 0,
			created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_source_key (source_key)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci" );
	}
}
