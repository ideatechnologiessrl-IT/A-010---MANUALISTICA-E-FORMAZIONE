<?php
/**
 * Rdacademy_Schema_License_V1 — la tabella dell ancora di licenza (`license_activation`).
 *
 * NUOVO come classe; il CREATE e copiato alla lettera da Reldox_Activator::
 * migrate_tables() (RelDox CRD 3.55.09G, v3.15.48 patch 2 FASE 0), dove la
 * tabella nasceva in mezzo alle migrazioni del CRM. Il lotto B non tocca
 * l Activator del nucleo: la tabella prende la sua classe di schema con
 * versione e flag (REGOLE-FAMIGLIA § 6), e passa dal Provisioning Guard.
 *
 * ⛔ IL FLAG SI ALZA SUL FATTO. Lo Schema Flag Guard del nucleo conosce solo
 * le tabelle del nucleo (la sua MAP e una costante del lotto A): finche non
 * vi entra anche questa classe, la verifica fisica la fa maybe_rebuild()
 * da solo — un flag acceso con la tabella assente non fa uscire in testa.
 *
 * CORREZIONE rispetto a RelDox: gli istanti si scrivono in UTC da PHP
 * (gmdate), non con current_time( 'mysql' ) che e l ora locale di WordPress
 * (SPEC § 2.3: un orologio solo).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_License_V1 {

	/** Flag sul BASE (Rdacademy_Option): il Provisioning Guard lo trova per riflessione. */
	const FLAG    = 'rdacademy_schema_licenza_v1';
	const VERSION = 'v1';

	/** Nome corto della tabella (senza prefisso). */
	const TABLE   = 'license_activation';

	public static function maybe_rebuild(): void {
		if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION
			&& Rdacademy_Schema_Flag_Guard::table_exists( self::TABLE ) === true ) {
			return;
		}
		self::create();
		if ( Rdacademy_Schema_Flag_Guard::table_exists( self::TABLE ) === true ) {
			// Seme idempotente dell ancora (registra il dominio corrente se assente;
			// non sovrascrive MAI un ancora gia presente).
			Rdacademy_License_Anchor::ensure_seeded();
			Rdacademy_Option::set( self::FLAG, self::VERSION );
		}
	}

	private static function create(): void {
		$p = RDACADEMY_TABLE_PREFIX;
		// Copiato da RelDox: tabella additiva idempotente (riga singola di
		// attivazione). Storage SOLO BASE, by-value, niente FK. L ancora e SOLO
		// IL DOMINIO (decisione S19 di RelDox): deterrente non-DRM, stato
		// needs_relicense soft, nessun lockout.
		Rdacademy_DB::raw(
			"CREATE TABLE IF NOT EXISTS `{$p}license_activation` (
				`id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`activation_domain` VARCHAR(255) NOT NULL COMMENT 'Ancora: dominio normalizzato alla prima attivazione (home_url)',
				`activated_at`      DATETIME NULL COMMENT 'Istante prima attivazione (UTC)',
				`plugin_version`    VARCHAR(20)  NULL COMMENT 'RDACADEMY_VERSION alla prima attivazione',
				`package`           VARCHAR(64)  NULL COMMENT 'RDACADEMY_PACKAGE alla prima attivazione',
				`license_state`     VARCHAR(32)  NOT NULL DEFAULT 'active' COMMENT 'active | needs_relicense (soft)',
				`last_seen_domain`  VARCHAR(255) NULL COMMENT 'Ultimo dominio osservato (diagnostica)',
				`last_check_at`     DATETIME NULL COMMENT 'Ultimo controllo dominio (UTC)',
				`notes`             VARCHAR(255) NULL,
				PRIMARY KEY (`id`),
				KEY `idx_lic_domain` (`activation_domain`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ancora licenza BASE (deterrente non-DRM)'"
		);
	}
}
