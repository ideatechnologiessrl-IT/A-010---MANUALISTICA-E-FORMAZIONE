<?php
/**
 * Rdacademy_Schema_Uploads_V1 — IL REGISTRO DEGLI ALLEGATI, VALIDO PER QUALUNQUE OGGETTO.
 * -----------------------------------------------------------------
 * Copiato da Reldox_Schema_Uploads_V32 (v32c, modello blob+attachment), RelDox CRD 3.55.09G.
 *
 * Tabella `uploads` entity-agnostic: ogni oggetto del prodotto (entity_type +
 * entity_id) può avere allegati con scope 'internal' / 'external'. Il blob
 * fisico vive in `uploads_blob` (Rdacademy_Schema_Uploads_Blob_V1), riferito per
 * valore da `blob_id` (nessuna FK fisica, pattern di famiglia).
 *
 * TAGLI (CRM): entity_master_id (UUID dei record del CRM: le tabelle del guscio
 * non lo hanno; l esistenza dell oggetto si verifica per id nel registro degli
 * oggetti, Rdacademy_Uploads_Entity::entita()), surface (immagini del corpo dei
 * documenti), template_id (modelli di stampa) e i loro backfill, la cartella
 * 1A in wp-content/uploads/reldox/ (storica, mai usata dal guscio).
 * ⛔ Il flag si alza SUL FATTO. Zero DROP, zero ALTER.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_Uploads_V1 {

	const FLAG    = 'rdacademy_schema_uploads_v1';
	const VERSION = 'v1';
	const TABLE   = 'uploads';

	public static function maybe_rebuild(): void {
		if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION ) {
			return;
		}
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::raw( "
			CREATE TABLE IF NOT EXISTS `{$p}uploads` (
				`id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`entity_type`       VARCHAR(32)     NOT NULL COMMENT 'tipo di oggetto dal registro rdacademy_uploads_entita',
				`entity_id`         BIGINT UNSIGNED NOT NULL,
				`scope`             ENUM('internal','external') NOT NULL DEFAULT 'internal',
				`blob_id`           BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'FK soft a uploads_blob; 0 = blob tolto con l ultimo riferimento',
				`filename_original` VARCHAR(255)    NOT NULL COMMENT 'Etichetta utente: può ripetersi su oggetti diversi',
				`uploaded_by`       BIGINT UNSIGNED NOT NULL,
				`uploaded_at`       DATETIME(6)     NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
				`deleted_at`        DATETIME(6)     NULL,
				`deleted_by`        BIGINT UNSIGNED NULL,
				PRIMARY KEY (`id`),
				KEY `idx_entity_scope` (`entity_type`, `entity_id`, `scope`),
				KEY `idx_uploader`     (`uploaded_by`, `uploaded_at`),
				KEY `idx_active`       (`deleted_at`, `entity_type`, `entity_id`),
				KEY `idx_blob`         (`blob_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
			  COMMENT='Allegati entity-agnostic — modello blob+attachment (FK soft blob_id)'
		" );
		if ( Rdacademy_Schema_Flag_Guard::table_exists( self::TABLE ) === true ) {
			Rdacademy_Option::set( self::FLAG, self::VERSION );
		}
	}
}
