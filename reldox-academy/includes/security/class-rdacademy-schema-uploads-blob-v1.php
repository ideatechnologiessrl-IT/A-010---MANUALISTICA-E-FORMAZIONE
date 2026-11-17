<?php
/**
 * Rdacademy_Schema_Uploads_Blob_V1 — I BLOB FISICI DEGLI ALLEGATI E LA CARTELLA PRIVATA.
 * -----------------------------------------------------------------
 * Copiato da Reldox_Schema_Uploads_Blob_V32 (pkg-anagrafica-1b2), RelDox CRD 3.55.09G.
 *
 * Modello content-addressable (copiato):
 *  - 1 file fisico (blob) = 1 SHA-256 = 1 riga in `uploads_blob`
 *  - N allegati logici (`uploads`) possono riferire lo stesso blob
 *  - ref_count conta i riferimenti VIVI: a zero, file e riga se ne vanno insieme
 *
 * Storage FUORI da wp-content/uploads/ (copiato):
 *   wp-content/rdacademy-private/{hex2}/{hex2}/{sha256}.bin
 *
 * Doppia barriera (copiato): .htaccess (Require all denied + Deny + -Indexes)
 * e web.config (IIS) alla radice; .htaccess «sentinella» a ogni shard; controllo
 * di open_basedir con registro diagnostico.
 *
 * DIFFERENZE, DICHIARATE:
 *  - il flag si alza SUL FATTO (in RelDox sempre, dopo il tentativo);
 *  - NUOVO barriere(): la stessa coppia .htaccess + web.config + index.php per
 *    ogni sottocartella che la chiede (backup, shard), in un posto solo;
 *  - TAGLIO force_rebuild() e diagnose() (pannello di diagnostica del CRM):
 *    lo stato lo legge l Area tecnica dal registro e dal disco.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_Uploads_Blob_V1 {

	const FLAG    = 'rdacademy_schema_uploads_blob_v1';
	const VERSION = 'v1';
	const TABLE   = 'uploads_blob';

	/** Cartella privata relativa a wp-content/ (NON wp-content/uploads/). SPEC § 1. */
	const STORAGE_BASEDIR_REL = 'rdacademy-private';

	public static function maybe_rebuild(): void {
		if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION ) {
			self::ensure_storage_dir(); // costa una file_exists: protegge da un rm -rf a mano (copiato)
			return;
		}
		self::rebuild();
		self::ensure_storage_dir();
		if ( Rdacademy_Schema_Flag_Guard::table_exists( self::TABLE ) === true ) {
			Rdacademy_Option::set( self::FLAG, self::VERSION );
		}
	}

	private static function rebuild(): void {
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}uploads_blob` (
				`id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`sha256`       CHAR(64)        NOT NULL COMMENT 'Hash SHA-256 del contenuto, chiave content-addressable',
				`mime_type`    VARCHAR(127)    NOT NULL COMMENT 'Letto dal CONTENUTO, mai dal nome (REGOLE 3.9)',
				`size_bytes`   BIGINT UNSIGNED NOT NULL,
				`storage_path` VARCHAR(500)    NOT NULL COMMENT 'Relativo a wp-content/rdacademy-private/, pattern sha256-sharded',
				`ref_count`    INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Allegati VIVI che riferiscono questo blob',
				`created_at`   DATETIME(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
				`created_by`   BIGINT UNSIGNED NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `uniq_sha256` (`sha256`),
				KEY `idx_ref` (`ref_count`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			  COMMENT='Blob fisici content-addressable — un sha256 = un file = una riga'
		" );
	}

	/** Contenuto della barriera Apache (copiato). */
	private static function htaccess(): string {
		return '# ' . RDACADEMY_PRODUCT . " — cartella privata, accesso solo dagli endpoint del prodotto\n"
			. "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
			. "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n"
			. "Options -Indexes\n";
	}

	/** Contenuto della barriera IIS (copiato). */
	private static function webconfig(): string {
		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <authorization>\n      <deny users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n";
	}

	/** NUOVO — .htaccess + web.config + index.php in una cartella. Idempotente, non sovrascrive. */
	public static function barriere( string $dir ): bool {
		if ( $dir === '' || ! is_dir( $dir ) ) {
			return false;
		}
		$dir = rtrim( $dir, '/\\' );
		$ok  = true;
		foreach ( array( '.htaccess' => self::htaccess(), 'web.config' => self::webconfig(), 'index.php' => "<?php // Silence is golden.\n" ) as $f => $c ) {
			if ( ! file_exists( $dir . '/' . $f ) ) {
				if ( @file_put_contents( $dir . '/' . $f, $c ) === false ) {
					$ok = false;
					error_log( '[RelDoxAcademy Uploads Blob] barriera ' . $f . ' NON scritta in ' . $dir );
				} else {
					@chmod( $dir . '/' . $f, 0644 );
				}
			}
		}
		return $ok;
	}

	public static function base_dir(): string {
		return trailingslashit( WP_CONTENT_DIR ) . self::STORAGE_BASEDIR_REL;
	}

	/** open_basedir lascia lavorare in wp-content? (copiato) */
	public static function open_basedir_ok(): bool {
		$ob = ini_get( 'open_basedir' );
		if ( empty( $ob ) ) {
			return true;
		}
		$wp_content = trailingslashit( WP_CONTENT_DIR );
		foreach ( explode( PATH_SEPARATOR, $ob ) as $allowed ) {
			$allowed = rtrim( trim( $allowed ), DIRECTORY_SEPARATOR );
			if ( $allowed !== '' && strpos( $wp_content, $allowed ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/** Crea (se manca) la cartella privata e ne rimette le barriere (copiato, auto-guarigione). */
	public static function ensure_storage_dir(): string {
		$base = self::base_dir();
		if ( ! self::open_basedir_ok() ) {
			error_log( '[RelDoxAcademy Uploads Blob] WARNING: open_basedir (' . ini_get( 'open_basedir' ) . ') potrebbe non includere WP_CONTENT_DIR. Lo storage proverà comunque.' );
		}
		if ( ! file_exists( $base ) && ! wp_mkdir_p( $base ) ) {
			error_log( '[RelDoxAcademy Uploads Blob] wp_mkdir_p fallita su ' . $base );
		}
		if ( is_dir( $base ) ) {
			@chmod( $base, 0755 );
			self::barriere( $base );
		}
		return $base;
	}

	/** Sentinella .htaccess al livello shard {hex2}/ (copiato, ora con la coppia intera). */
	public static function ensure_shard_htaccess( string $hex2_path ): void {
		self::barriere( $hex2_path );
	}
}
