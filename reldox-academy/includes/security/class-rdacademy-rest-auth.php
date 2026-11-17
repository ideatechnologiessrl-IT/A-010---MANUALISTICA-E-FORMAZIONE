<?php
/**
 * RelDoxAcademy — REST Auth Helper
 * -----------------------------------------------------------------
 * Copiato da Reldox_REST_Auth, RelDox CRD 3.55.09G (pkg-anagrafica-1b2):
 * helper centralizzato per il permesso degli endpoint REST del prodotto
 * (namespace rdacademy/v1), single point of evolution per la policy dei
 * controller REST futuri (lotti C e D).
 *
 * ⛔ CORREZIONE SPEC § 2.1 — dual_auth_check() riconosce SOLO la sessione
 * del prodotto. In RelDox il «Track 1» accettava qualunque utente WordPress
 * collegato (is_user_logged_in()): un abbonato di WordPress apriva gli
 * endpoint REST del CRM. Il nome del metodo resta (lo chiamano i controller
 * copiati da RelDox), la seconda porta no.
 *
 * @package RelDox_Base
 * @subpackage Security
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_REST_Auth {

	/**
	 * Permission callback per endpoint REST.
	 *
	 * @param WP_REST_Request|null $request  Non usato: la decisione si basa
	 *                                       solo sulla sessione corrente.
	 * @return true|WP_Error true se autenticato, WP_Error 401 altrimenti.
	 */
	public static function dual_auth_check( $request = null ) {
		// ⛔ § 2.1: qui RelDox accettava is_user_logged_in() di WordPress. Tolto.
		if ( class_exists( 'Rdacademy_Auth' ) && Rdacademy_Auth::is_logged_in() ) {
			// ⛔ 1.13.0B (SV-43, SPEC-NOVITA-INTEGRAZIONE-FAMIGLIA § 6.2 punto 2) — l Integratore non usa nessuna rotta REST interna
			// (Tabelle Funzionali, testi delle notifiche, caricamenti): 403. L API esterna rdacademy/v1/ext usa le chiavi, non questa porta.
			if ( class_exists( 'Rdacademy_Router' ) && Rdacademy_Router::e_integratore( Rdacademy_Auth::get_current_user() ) ) {
				return class_exists( 'WP_Error' ) ? new WP_Error( 'rdacademy_rest_forbidden', 'Accesso riservato.', array( 'status' => 403 ) ) : false;
			}
			return true;
		}

		if ( class_exists( 'WP_Error' ) ) {
			return new WP_Error(
				'rdacademy_rest_unauthorized',
				__( 'Autenticazione richiesta.', 'reldox-academy' ),
				array( 'status' => 401 )
			);
		}
		return false;
	}

	/**
	 * NUOVO — permission callback per gli endpoint REST riservati
	 * all amministratore (gemello di dual_auth_check, stessa autorita del
	 * router: backdoor oppure Rdacademy_Security::is_admin()).
	 *
	 * @return true|WP_Error
	 */
	public static function admin_check( $request = null ) {
		$ok = self::dual_auth_check( $request );
		if ( $ok !== true ) {
			return $ok;
		}
		$u = Rdacademy_Auth::get_current_user();
		if ( ! empty( $u['is_backdoor'] ) || ( ! empty( $u['id'] ) && Rdacademy_Security::is_admin( (int) $u['id'] ) ) ) {
			return true;
		}
		return new WP_Error(
			'rdacademy_rest_forbidden',
			__( 'Permessi insufficienti.', 'reldox-academy' ),
			array( 'status' => 403 )
		);
	}
}
