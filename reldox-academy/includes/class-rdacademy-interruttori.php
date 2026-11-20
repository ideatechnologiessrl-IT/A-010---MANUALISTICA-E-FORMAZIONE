<?php
/**
 * Rdacademy_Interruttori — 0.1.08A, voce F34 · Formazione F019 · 20/11/2026
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Gli interruttori A1 e A2 della SPEC-INDURIMENTO-INSTALLAZIONI v2 § D.2 del nodo:
 *   A1 — XML-RPC chiuso: filtro `xmlrpc_enabled` a false, via l intestazione X-Pingback e xmlrpc.php risponde 403
 *        (il filtro da solo lascia `system.listMethods` e `system.multicall`, che la regola vuole chiusi);
 *   A2 — elenco utenti nascosto agli anonimi: filtro `rest_endpoints`, via `/wp/v2/users…` per chi non è collegato.
 * Spenti di serie, li accende l amministratore (owner 13/11/2026 19:19, chat RelDox CRD S703, (c) «Spento di serie»).
 * Chi è collegato lavora come prima: nessun blocco del lavoro (CLAUDE.md del nodo, owner 18/11/2026 13:08, S717).
 * Lo stato vive sul BASE (Rdacademy_Option), mai sul database di WordPress; se il BASE non risponde restano spenti.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Interruttori {

	const CHIAVI = array(
		'A1' => 'indurimento_a1_xmlrpc_chiuso',
		'A2' => 'indurimento_a2_utenti_nascosti',
	);

	/** Le scritte sotto la regola, come nel mockup approvato (owner 20/11/2026 07:49, chat Formazione F019). */
	const TESTI = array(
		'A1' => array( 'Chiudi XML-RPC', 'serve solo a vecchie app e a pubblicazioni da fuori; i corsi non lo usano.' ),
		'A2' => array( 'Nascondi l elenco utenti a chi non è collegato', 'chi è collegato lavora come prima.' ),
	);

	/** Acceso solo se l amministratore l ha acceso; ogni dubbio vale «spento». */
	public static function acceso( string $regola ): bool {
		if ( ! isset( self::CHIAVI[ $regola ] ) ) return false;
		try {
			return (string) Rdacademy_Option::get( self::CHIAVI[ $regola ], '0' ) === '1';
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	public static function salva( bool $a1, bool $a2 ): bool {
		$ok1 = Rdacademy_Option::set( self::CHIAVI['A1'], $a1 ? '1' : '0' );
		$ok2 = Rdacademy_Option::set( self::CHIAVI['A2'], $a2 ? '1' : '0' );
		return $ok1 && $ok2;
	}

	/** Vero se questa richiesta va chiusa: A1 acceso e richiesta a xmlrpc.php. */
	public static function chiude_xmlrpc( bool $richiesta_xmlrpc ): bool {
		return $richiesta_xmlrpc && self::acceso( 'A1' );
	}

	public static function init(): void {
		add_action( 'admin_post_rdacademy_interruttori', array( __CLASS__, 'salva_dal_modulo' ) );
		if ( self::acceso( 'A1' ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
			add_filter( 'wp_headers', array( __CLASS__, 'senza_pingback' ) );
			// revisione a freddo F019: X-Pingback WordPress la manda con header() in WP::handle_404(), fuori da wp_headers
			add_action( 'wp', array( __CLASS__, 'togli_pingback' ), 99 );
			// e le pagine non indicano più xmlrpc.php (EditURI e pingback nel <head>)
			remove_action( 'wp_head', 'rsd_link' );
			remove_action( 'wp_head', 'pingback_header' );
			if ( self::chiude_xmlrpc( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
				if ( function_exists( 'status_header' ) ) status_header( 403 );
				if ( ! headers_sent() ) header( 'Content-Type: text/plain; charset=utf-8' );
				echo 'XML-RPC chiuso su questo sito.';
				exit;
			}
		}
		if ( self::acceso( 'A2' ) ) {
			add_filter( 'rest_endpoints', array( __CLASS__, 'senza_utenti' ) );
		}
	}

	/** Il modulo della Stato sistema: solo chi gestisce il sito, con il nonce; poi la misura si rifà. */
	public static function salva_dal_modulo(): void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permesso negato.', '', array( 'response' => 403 ) );
		check_admin_referer( 'rdacademy_interruttori' );
		$ok = self::salva( ! empty( $_POST['a1'] ), ! empty( $_POST['a2'] ) );
		if ( class_exists( 'Rdacademy_Indurimento' ) ) delete_transient( Rdacademy_Indurimento::TRANSITORIO );
		wp_safe_redirect( admin_url( 'admin.php?page=rdacademy&interruttori=' . ( $ok ? 'salvati' : 'errore' ) . '#rdacademy-indurimento' ) );
		exit;
	}

	public static function togli_pingback(): void {
		if ( ! headers_sent() ) header_remove( 'X-Pingback' );
	}

	public static function senza_pingback( $h ) {
		if ( is_array( $h ) ) unset( $h['X-Pingback'] );
		return $h;
	}

	public static function senza_utenti( $rotte ) {
		if ( ! is_array( $rotte ) || is_user_logged_in() ) return $rotte;
		foreach ( array_keys( $rotte ) as $r ) {
			if ( str_starts_with( (string) $r, '/wp/v2/users' ) ) unset( $rotte[ $r ] );
		}
		return $rotte;
	}
}
