<?php
/**
 * Rdacademy_Schema_Sorgente_Esempio_V1 — LA TABELLA D ESEMPIO DI UNA SORGENTE.
 * -----------------------------------------------------------------------------
 * NUOVO. Il guscio non ha moduli, quindi nessuna sorgente ha tabelle proprie:
 * senza una tabella dichiarata il provisioning all accensione non avrebbe nulla
 * da far nascere e la regola «le tabelle di una sorgente nascono sul suo
 * database» non sarebbe verificabile. Questa classe è la tabella dimostrativa
 * che `includes/carico-d.php` dichiara dal filtro `rdacademy_sorgenti_tabelle`, una
 * per sorgente (`<chiave>_esempio`). Il prodotto toglie la dichiarazione e
 * mette le sue.
 *
 * Grammatica delle classi schema di RelDox: CREATE TABLE IF NOT EXISTS, mai
 * DROP, mai ALTER. ⛔ Nasce SULLA CONNESSIONE CHE LE PASSA IL PROVISIONING (la
 * connessione della sorgente, da Rdacademy_Table_Owner::conn_for in modo
 * dichiarato): nessun ripiego sul BASE alla nascita (BUG-182 di RelDox).
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_Sorgente_Esempio_V1 {

	/**
	 * Il creatore che il provisioning chiama: ( mysqli $conn, string $full ): bool.
	 * Il nome completo arriva dal registro, non si compone qui.
	 */
	public static function create( mysqli $conn, string $full ): bool {
		$t = str_replace( '`', '``', $full );
		return Rdacademy_DB::raw_on( $conn, "CREATE TABLE IF NOT EXISTS `{$t}` (
			`id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`codice`     VARCHAR(40)     NOT NULL,
			`nota`       VARCHAR(255)    NOT NULL DEFAULT '',
			`created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			UNIQUE KEY `uk_codice` (`codice`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		  COMMENT='Tabella di esempio di una sorgente del guscio (nasce sul database della sorgente)'" );
	}
}
