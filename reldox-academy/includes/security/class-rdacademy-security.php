<?php
/**
 * RelDoxAcademy — Foundation Security API (ridotta)
 * -----------------------------------------------------------------
 * Copiato da Reldox_Security, RelDox CRD 3.55.09G (pkg-anagrafica-1a) e ridotto
 * alla sola AUTORITA UNICA su chi e l utente e chi e amministratore
 * (REGOLE-FAMIGLIA § 3.4).
 *
 * ⛔ CORREZIONE SPEC § 2.1 — NESSUN RIPIEGO SULL UTENTE WORDPRESS.
 * In RelDox resolve_current_user_id() e resolve_current_user() avevano un
 * «Track 2»: senza sessione del prodotto restituivano l id dell utente
 * WordPress collegato. L utente WordPress numero N ereditava cosi i ruoli
 * dell utente numero N del prodotto (REGOLE § 3.4, ultimo punto). Qui l unica
 * fonte e la sessione del prodotto: senza sessione l utente e 0 / null.
 *
 * TAGLI: Permission API per entita (user_can_see / user_can_edit /
 * list_filter_where, privacy public/role/named), log_backdoor_access
 * (security_log_backdoor), cache effective_security_users delle aziende,
 * TCF, mappa entity_type → tabella. Sono il dominio documentale del CRM.
 *
 * @package RelDox_Base
 * @subpackage Security
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Security {

	/**
	 * Nome del ruolo Admin «legacy» (case-insensitive lookup): RelDox lo
	 * riconosceva per nome oltre che per permissions.all. Nel guscio il ruolo
	 * si chiama «Admin»: il riconoscimento per nome resta come rete.
	 */
	const ROLE_ADMIN_NAME = 'admin';

	/** Cache in-memory dei role_id per utente, per la durata della richiesta. */
	private static array $user_roles_cache = array();

	// ═════════════════════════════════════════════════════════════════
	// SEZIONE 1 — RESOLVER (SOLO SESSIONE DEL PRODOTTO)
	// ═════════════════════════════════════════════════════════════════

	/**
	 * Risolve l ID dell utente corrente dalla sola sessione del prodotto.
	 *
	 * @return int User ID, o 0 se non autenticato.
	 */
	public static function resolve_current_user_id(): int {
		if ( class_exists( 'Rdacademy_Auth' ) ) {
			$rx_user = Rdacademy_Auth::get_current_user();
			if ( $rx_user && ! empty( $rx_user['id'] ) ) {
				return (int) $rx_user['id'];
			}
		}
		// ⛔ § 2.1: qui RelDox ripiegava su get_current_user_id() di WordPress. Tolto.
		return 0;
	}

	/**
	 * Risolve l utente corrente come array, dalla sola sessione del prodotto.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function resolve_current_user(): ?array {
		if ( class_exists( 'Rdacademy_Auth' ) ) {
			$rx_user = Rdacademy_Auth::get_current_user();
			if ( $rx_user && ! empty( $rx_user['id'] ) ) {
				return $rx_user;
			}
		}
		// ⛔ § 2.1: qui RelDox normalizzava un WP_User in struttura compatibile. Tolto.
		return null;
	}

	/**
	 * Verifica se l utente e Backdoor (Service Account).
	 *
	 * @param int $user_id  Se 0, usa l utente corrente.
	 */
	public static function is_backdoor( int $user_id = 0 ): bool {
		if ( $user_id === 0 ) {
			$current = self::resolve_current_user();
			if ( ! $current ) return false;
			return ! empty( $current['is_backdoor'] );
		}

		$p = RDACADEMY_TABLE_PREFIX;
		$row = Rdacademy_DB::get_row(
			"SELECT r.id FROM `{$p}roles` r
			 JOIN `{$p}user_roles` ur ON ur.role_id = r.id
			 WHERE ur.user_id = ? AND r.is_backdoor = 1
			 LIMIT 1",
			array( $user_id ),
			'i'
		);
		return ! empty( $row );
	}

	/**
	 * Verifica se l utente e Admin del prodotto (super-utente del cliente).
	 *
	 * Copiato da RelDox P1.5 (1C.2.e): l Admin e identificato in modo
	 * data-driven via permissions.all = true sui suoi ruoli. JSON_EXTRACT puo
	 * ritornare boolean true, stringa "true" o numero 1: confronto type-flexible.
	 * ⛔ DIFETTO DI RELDOX CORRETTO: la condizione `= CAST('true' AS JSON)` e un
	 * errore di sintassi su MariaDB (il palco): l intera SELECT falliva, il
	 * riconoscimento data-driven non funzionava MAI e restava solo il ripiego
	 * per nome. JSON_UNQUOTE(...) IN ('true','1') copre i tre casi su MySQL 8 e
	 * su MariaDB (true → 'true', "true" → 'true', 1 → '1').
	 *
	 * @param int $user_id  Se 0, usa l utente corrente.
	 */
	public static function is_admin( int $user_id = 0 ): bool {
		if ( $user_id === 0 ) {
			$user_id = self::resolve_current_user_id();
			if ( $user_id === 0 ) return false;
		}

		$p = RDACADEMY_TABLE_PREFIX;
		$row = Rdacademy_DB::get_row(
			"SELECT 1
			 FROM `{$p}user_roles` ur
			 INNER JOIN `{$p}roles` r ON r.id = ur.role_id
			 WHERE ur.user_id = ?
			   AND r.is_backdoor = 0
			   AND r.permissions IS NOT NULL
			   AND JSON_UNQUOTE(JSON_EXTRACT(r.permissions, '$.all')) IN ('true','1')
			 LIMIT 1",
			array( $user_id ), 'i'
		);
		if ( $row ) return true;

		// Ripiego per nome (in RelDox: 'amministratore' e 'admin reldox').
		if ( self::user_has_role( $user_id, self::ROLE_ADMIN_NAME ) ) return true;

		return false;
	}

	// ═════════════════════════════════════════════════════════════════
	// SEZIONE 4 — HELPER (copiati)
	// ═════════════════════════════════════════════════════════════════

	private static function user_has_role( int $user_id, string $role_name ): bool {
		$p = RDACADEMY_TABLE_PREFIX;
		$row = Rdacademy_DB::get_row(
			"SELECT r.id FROM `{$p}roles` r
			 JOIN `{$p}user_roles` ur ON ur.role_id = r.id
			 WHERE ur.user_id = ? AND LOWER(r.name) = LOWER(?)
			 LIMIT 1",
			array( $user_id, $role_name ),
			'is'
		);
		return ! empty( $row );
	}

	/**
	 * Carica i role_id di un utente con cache in-memory per richiesta.
	 *
	 * @return int[]
	 */
	public static function get_user_role_ids( int $user_id ): array {
		if ( isset( self::$user_roles_cache[ $user_id ] ) ) {
			return self::$user_roles_cache[ $user_id ];
		}

		$p = RDACADEMY_TABLE_PREFIX;
		$rows = Rdacademy_DB::get_results(
			"SELECT role_id FROM `{$p}user_roles` WHERE user_id = ?",
			array( $user_id ),
			'i'
		);

		$ids = array();
		foreach ( (array) $rows as $r ) {
			$ids[] = (int) $r['role_id'];
		}

		self::$user_roles_cache[ $user_id ] = $ids;
		return $ids;
	}

	/**
	 * Invalida la cache statica dei ruoli (dopo assign/unassign a runtime).
	 *
	 * @param int $user_id  Se 0, invalida tutta la cache.
	 */
	public static function invalidate_role_cache( int $user_id = 0 ): void {
		if ( $user_id === 0 ) {
			self::$user_roles_cache = array();
		} else {
			unset( self::$user_roles_cache[ $user_id ] );
		}
	}
}
