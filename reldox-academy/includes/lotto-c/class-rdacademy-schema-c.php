<?php
/**
 * Rdacademy_Schema_C_V1 — LO SCHEMA DEL LOTTO C (pagine dell app).
 * =============================================================================
 * NUOVO nella grammatica delle classi schema di RelDox (es.
 * Reldox_Schema_Progetto_V32: CREATE TABLE IF NOT EXISTS, SHOW COLUMNS → ALTER
 * ADD, seme idempotente su chiave univoca, flag sul BASE alzato SUL FATTO).
 * Passa dal Provisioning Guard come il nucleo (includes/carico-c.php).
 *
 * CHE COSA CREA
 *  1. Le colonne della linguetta «Azienda» della scheda utente, che in RelDox
 *     stavano nel CREATE di `reldox_users` (class-reldox-schema-users-v32):
 *     phone, mobile, department, employee_code, job_title, manager_name.
 *     ⛔ SHOW COLUMNS → ALTER ADD, mai ADD COLUMN IF NOT EXISTS (REGOLE § 6:
 *     MySQL 8.0 non lo conosce). Zero MODIFY, zero DROP.
 *     TAGLIO: protocol_prefix (Protocollo, CRM), le colonne delle firme e
 *     delle caselle (Email, CRM).
 *  2. La tabella d esempio delle Tabelle Funzionali `tf_esempio`, nella forma
 *     di `progetto_link_roles` (label UNIQUE, descrizione, enabled,
 *     sort_order) PIU' due colonne che la riga di famiglia pretende
 *     (REGOLE § 3.7): `codice` (scelto alla nascita, non cambia piu, UNIQUE:
 *     e la chiave del seme idempotente) e `colore` (il pallino).
 *  3. `tf_esempio_uso`: chi CITA una voce d esempio. In RelDox la tabella che
 *     cita i ruoli del collegamento e `progetto_links.role_id`; nel guscio non
 *     esiste un oggetto di dominio, quindi la citazione ha una tabella sua,
 *     dichiarata nel registro (Rdacademy_TF_Registry::RIFERIMENTI). E il punto
 *     dove un prodotto aggancia il suo oggetto vero.
 *
 * 0.8.7A (owner 08/10 09:36, «fuorviante») — LA TABELLA D ESEMPIO ESCE DALLA BASE DI PARTENZA: i punti 2 e 3 qui sopra non
 * nascono più (né `tf_esempio` né `tf_esempio_uso`, né il loro seme) e presente() guarda solo le colonne della scheda
 * utente. Dove esistono già restano sul database (zero DROP): nessuna pagina le mostra. Da segnare per il kit di
 * famiglia (R-58): il guscio porta una tabella modello che un prodotto vero deve togliere.
 *
 * ⛔ IL FLAG SI ALZA SUL FATTO: dopo che tabelle e colonne risultano esistere
 * davvero (REGOLE § 6 — «un flag non e una prova»). Senza fatto, il boot
 * successivo ritenta da solo.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_C_V1 {

	const FLAG    = 'rdacademy_schema_lotto_c_v1';
	const VERSION = 'v1';

	/** Le colonne che la scheda utente legge: nascono al boot (REGOLE § 6). */
	const COLONNE_UTENTE = array(
		'phone'         => "VARCHAR(50)  NULL",
		'mobile'        => "VARCHAR(50)  NULL",
		'department'    => "VARCHAR(150) NULL COMMENT 'Ufficio (linguetta Azienda)'",
		'employee_code' => "VARCHAR(50)  NULL COMMENT 'NULL se vuoto: la UNIQUE ammette N NULL e rifiuta N stringhe vuote'",
		'job_title'     => "VARCHAR(150) NULL",
		'manager_name'  => "VARCHAR(150) NULL",
	);

	public static function maybe_rebuild(): void {
		if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION && self::presente() ) {
			return;
		}
		self::colonne_utente();
		if ( self::presente() ) {
			Rdacademy_Option::set( self::FLAG, self::VERSION );
		}
	}

	/** Le tabelle e le colonne ci sono davvero? Sola lettura. */
	public static function presente(): bool {
		$p    = RDACADEMY_TABLE_PREFIX;
		$cols = array_column( Rdacademy_DB::get_results( "SHOW COLUMNS FROM `{$p}users`" ), 'Field' );
		foreach ( array_keys( self::COLONNE_UTENTE ) as $c ) {
			if ( ! in_array( $c, $cols, true ) ) return false;
		}
		return true;
	}

	/** SHOW COLUMNS → ALTER ADD. Nessuna colonna si tocca se c e gia. */
	private static function colonne_utente(): void {
		$p    = RDACADEMY_TABLE_PREFIX;
		$cols = array_column( Rdacademy_DB::get_results( "SHOW COLUMNS FROM `{$p}users`" ), 'Field' );
		foreach ( self::COLONNE_UTENTE as $c => $def ) {
			if ( in_array( $c, $cols, true ) ) continue;
			Rdacademy_DB::raw( "ALTER TABLE `{$p}users` ADD COLUMN `{$c}` {$def}" );
		}
		$idx = array_column( Rdacademy_DB::get_results( "SHOW INDEX FROM `{$p}users`" ), 'Key_name' );
		if ( ! in_array( 'uk_employee_code', $idx, true ) ) {
			Rdacademy_DB::raw( "ALTER TABLE `{$p}users` ADD UNIQUE KEY `uk_employee_code` (`employee_code`)" );
		}
	}
}
