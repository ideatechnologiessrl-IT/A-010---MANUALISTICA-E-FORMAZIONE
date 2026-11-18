<?php
/**
 * RelDoxAcademy — REST amministrativa delle Tabelle Funzionali
 * =========================================================================
 * Copiato da Reldox_TF_Admin_REST, RelDox CRD 3.55.09G (S120). Le tre
 * operazioni su OGNI vista di /academy/tabelle/, scritte UNA volta sola:
 *
 *   GET    /rdacademy/v1/tf-admin/registry           — referto completo del registro
 *   GET    /rdacademy/v1/tf-admin/tab/(tab)          — le righe che quel tab amministra
 *   POST   /rdacademy/v1/tf-admin/(tabella)/wipe     — azzera
 *   POST   /rdacademy/v1/tf-admin/(tabella)/generate — rigenera dalla fonte
 *
 * IL PARAMETRO NON E' UNA TABELLA QUALUNQUE (copiato): Rdacademy_TF_Registry
 * accetta solo cio' che dichiara. Un nome che non e' li' dentro viene
 * respinto PRIMA di comporre qualunque SQL.
 *
 * PERMESSI: Rdacademy_C_Rest::permesso_admin() — la sessione del prodotto
 * (Rdacademy_REST_Auth::admin_check, mai l utente WordPress, SPEC § 2.1) PIU' il
 * gettone del canale dell app (vedi Rdacademy_C_Rest, NUOVO).
 * TAGLIO: Reldox_Audit::log → activity_log del guscio.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_TF_Admin_REST {

	const NS = 'rdacademy/v1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		$p = array( 'Rdacademy_C_Rest', 'permesso_admin' );
		register_rest_route( self::NS, '/tf-admin/registry', array(
			'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_registry' ), 'permission_callback' => $p,
		) );
		register_rest_route( self::NS, '/tf-admin/tab/(?P<tab>[a-z0-9\-]+)', array(
			'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_per_tab' ), 'permission_callback' => $p,
		) );
		register_rest_route( self::NS, '/tf-admin/(?P<tabella>[a-z0-9_]+)/wipe', array(
			'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_wipe' ), 'permission_callback' => $p,
		) );
		register_rest_route( self::NS, '/tf-admin/(?P<tabella>[a-z0-9_]+)/generate', array(
			'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_generate' ), 'permission_callback' => $p,
		) );
	}

	public static function rest_registry( WP_REST_Request $req ): WP_REST_Response {
		return new WP_REST_Response( array(
			'ok'        => true,
			'integrita' => Rdacademy_TF_Registry::verifica(),
			'righe'     => Rdacademy_TF_Registry::referto(),
		), 200 );
	}

	public static function rest_per_tab( WP_REST_Request $req ): WP_REST_Response {
		$tab  = (string) $req->get_param( 'tab' );
		$voci = Rdacademy_TF_Registry::per_tab( $tab );
		foreach ( $voci as $i => $v ) {
			$voci[ $i ]['righe'] = Rdacademy_TF_Registry::righe( (string) $v['tabella'] );
		}
		return new WP_REST_Response( array( 'ok' => true, 'tab' => $tab, 'voci' => $voci ), 200 );
	}

	/**
	 * AZZERA (copiato). Il corpo porta `conferma` = il nome della tabella: un
	 * POST partito da solo non deve poter svuotare una lista.
	 */
	public static function rest_wipe( WP_REST_Request $req ): WP_REST_Response {
		$tabella  = (string) $req->get_param( 'tabella' );
		$conferma = (string) ( $req->get_param( 'conferma' ) ?? '' );

		if ( strtolower( trim( $conferma ) ) !== strtolower( trim( $tabella ) ) ) {
			return new WP_REST_Response( array(
				'ok'     => false,
				'errore' => 'conferma mancante o non corrispondente alla tabella richiesta',
			), 400 );
		}

		$r = Rdacademy_TF_Registry::svuota( $tabella );

		if ( $r['ok'] ) {
			// L'azzeramento di una lista di configurazione e' un fatto: si deve
			// poter sapere quando e per mano di chi (copiato; in RelDox Reldox_Audit).
			$u = Rdacademy_Auth::get_current_user();
			Rdacademy_Auth::log_activity( (int) ( $u['id'] ?? 0 ), 'tabelle_funzionali', 'wipe',
				'Azzerata ' . $r['tabella'] . ': ' . (int) $r['rimosse'] . ' voci rimosse' );
		}

		return new WP_REST_Response( $r, $r['ok'] ? 200 : 409 );
	}

	/** RIGENERA dalla fonte dichiarata. Idempotente: non e' distruttivo. */
	public static function rest_generate( WP_REST_Request $req ): WP_REST_Response {
		$r = Rdacademy_TF_Registry::genera( (string) $req->get_param( 'tabella' ) );
		return new WP_REST_Response( $r, $r['ok'] ? 200 : 409 );
	}
}
