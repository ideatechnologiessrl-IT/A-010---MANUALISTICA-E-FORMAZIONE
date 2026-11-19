<?php
/**
 * NUOVO — IL CARICO DEL LOTTO C (pagine dell app: Gestione utenti, Preferenze,
 * Impostazioni, Tabelle Funzionali, Area tecnica). SPEC-GUSCIO-BASE § 4.
 *
 * Il file principale lo richiede SOLO SE ESISTE (reldox-academy.php, «I carichi
 * dei lotti B, C, D»). Qui il lotto aggiunge i suoi file e le SUE righe negli
 * innesti che il lotto A ha predisposto, senza riscrivere le mappe:
 *   - rdacademy_page_map           pagina => vista
 *   - rdacademy_capability_map     azione => livello (⛔ le righe del nucleo vincono)
 *   - rdacademy_ajax_<azione>      l handler, chiamato DOPO nonce, permesso e freno
 *   - rdacademy_reset_keep_tables  cio che l azzeramento non svuota (con il motivo)
 *   - rdacademy_nav_icons          le icone delle viste del lotto
 * Le pagine (utenti, preferenze, impostazioni, tabelle, area-tecnica) hanno
 * gia il loro livello in Rdacademy_Router::page_access_map(): non si ridichiarano.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once RDACADEMY_PLUGIN_DIR . 'includes/security/class-rdacademy-cifra.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-c/class-rdacademy-schema-c.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-c/class-rdacademy-utenti.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-c/class-rdacademy-me.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-c/class-rdacademy-impostazioni.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-c/class-rdacademy-tf-registry.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-c/class-rdacademy-tf-admin-rest.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/lotto-c/class-rdacademy-area-tecnica.php';

/**
 * NUOVO — IL CANCELLO DEGLI ENDPOINT REST DEL LOTTO.
 *
 * In RelDox i controller REST delle Tabelle funzionali chiedevano solo la
 * sessione (permission_admin) e il browser mandava X-WP-Nonce `wp_rest`, che
 * protegge l utente WORDPRESS e non la sessione del prodotto: con il cookie
 * del prodotto in SameSite=Lax il rischio e piccolo, ma un POST JSON da un
 * altra scheda dello stesso sito passava. Qui si chiede ANCHE il gettone del
 * canale dell app (lo stesso `rdacademy_ajax` del router: «una credenziale per
 * pagina», REGOLE § 5) nell intestazione X-RDACADEMY-Nonce.
 */
final class Rdacademy_C_Rest {
	public static function permesso_admin( $request = null ) {
		$ok = Rdacademy_REST_Auth::admin_check( $request );
		if ( $ok !== true ) {
			return $ok;
		}
		$n = ( $request instanceof WP_REST_Request ) ? (string) $request->get_header( 'x_rdacademy_nonce' ) : '';
		if ( $n === '' || ! wp_verify_nonce( $n, 'rdacademy_ajax' ) ) {
			return new WP_Error( 'rdacademy_rest_nonce', 'Token di sicurezza non valido o scaduto. Ricaricare la pagina.', array( 'status' => 403 ) );
		}
		return true;
	}
}

Rdacademy_TF_Admin_REST::init();

/* ── Provisioning dello schema del lotto, flag-verificato (BUG-07 di RelDox) ── */
add_action( 'plugins_loaded', static function () {
	Rdacademy_Provisioning_Guard::provision( 'Rdacademy_Schema_C_V1' );
}, 6 );

/* ── Le viste del lotto (pagina => file in public/views/) ── */
add_filter( 'rdacademy_page_map', static function ( array $map ): array {
	$map['utenti']       = 'gestione-utenti.php';
	$map['preferenze']   = 'preferenze.php';
	$map['impostazioni'] = 'impostazioni.php';
	$map['tabelle']      = 'tabelle-funzionali.php';
	$map['area-tecnica'] = 'area-tecnica.php';
	return $map;
} );

/**
 * Le azioni del lotto, ognuna col suo livello e il suo handler. UNA tabella:
 * la mappa delle capacita e le registrazioni si leggono da qui, cosi un azione
 * non puo avere un handler senza livello (varrebbe `admin`) ne un livello
 * senza handler («Azione non riconosciuta»).
 */
function rdacademy_lotto_c_azioni(): array {
	return array(
		// Gestione utenti (copiate da RelDox: tutte `admin`)
		'get_user'          => array( 'admin', array( 'Rdacademy_Utenti', 'ajax_get_user' ) ),
		'save_user'         => array( 'admin', array( 'Rdacademy_Utenti', 'ajax_save_user' ) ),
		'delete_user'       => array( 'admin', array( 'Rdacademy_Utenti', 'ajax_delete_user' ) ),
		'reset_user_pwd'    => array( 'admin', array( 'Rdacademy_Utenti', 'ajax_reset_user_pwd' ) ),
		'user_invite'       => array( 'admin', array( 'Rdacademy_Utenti', 'ajax_user_invite' ) ),
		'user_login_link'   => array( 'admin', array( 'Rdacademy_Utenti', 'ajax_user_login_link' ) ),   // 0.8.6A — «✉ Invio login»
		// Preferenze: il self-service e di tutti, l identita viene dalla sessione
		'me_profilo_get'     => array( 'auth', array( 'Rdacademy_Me', 'ajax_me_profilo_get' ) ),
		'me_password_change' => array( 'auth', array( 'Rdacademy_Me', 'ajax_me_password_change' ) ),
		'me_accessi_get'     => array( 'auth', array( 'Rdacademy_Me', 'ajax_me_accessi_get' ) ),
		// Impostazioni (⛔ impostazioni_get era `auth` in RelDox: qui `admin`)
		'impostazioni_get'     => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_impostazioni_get' ) ),
		'impostazioni_save'    => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_impostazioni_save' ) ),
		'mail_servizio_save'   => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_mail_servizio_save' ) ),
		// 0.6.09A (RF-BUG-016): la prova a secco esce; entrano «Testa», «Prova la spedizione», «Rileva», «Come si configura».
		'mail_servizio_prova_imap'  => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_mail_servizio_prova_imap' ) ),
		'mail_servizio_prova_invio' => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_mail_servizio_prova_invio' ) ),
		'mail_servizio_rileva'      => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_mail_servizio_rileva' ) ),
		'mail_servizio_aiuto'       => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_mail_servizio_aiuto' ) ),
		'branding_upload_logo' => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_branding_upload_logo' ) ),
		'dbconfig_status'      => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_dbconfig_status' ) ),
		'dbconfig_test'        => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_dbconfig_test' ) ),
		'db_reset'             => array( 'admin', array( 'Rdacademy_Impostazioni', 'ajax_db_reset' ) ),
		// Area tecnica
		'tech_check'        => array( 'admin', array( 'Rdacademy_Area_Tecnica', 'ajax_tech_check' ) ),
		'tech_roles'        => array( 'admin', array( 'Rdacademy_Area_Tecnica', 'ajax_tech_roles' ) ),
		'tech_role_add'     => array( 'admin', array( 'Rdacademy_Area_Tecnica', 'ajax_tech_role_add' ) ),
		'tech_role_remove'  => array( 'admin', array( 'Rdacademy_Area_Tecnica', 'ajax_tech_role_remove' ) ),
		'tech_role_restore' => array( 'admin', array( 'Rdacademy_Area_Tecnica', 'ajax_tech_role_restore' ) ),
		'tech_opcache'      => array( 'admin', array( 'Rdacademy_Area_Tecnica', 'ajax_tech_opcache' ) ),
	);
}

add_filter( 'rdacademy_capability_map', static function ( array $map ): array {
	foreach ( rdacademy_lotto_c_azioni() as $azione => $def ) {
		$map[ $azione ] = $def[0];
	}
	return $map;
} );

foreach ( rdacademy_lotto_c_azioni() as $rdacademy_c_azione => $rdacademy_c_def ) {
	add_action( 'rdacademy_ajax_' . $rdacademy_c_azione, $rdacademy_c_def[1], 10, 1 );
}
unset( $rdacademy_c_azione, $rdacademy_c_def );

/* ── Cio che l azzeramento dei dati operativi NON svuota ──
 * 0.8.7A: la tabella d esempio del guscio è uscita (owner 08/10 09:36, «fuorviante»); la Tabella Funzionale dei testi
 * delle notifiche la protegge carico-e.php, accanto alla sua classe. */

/*
 * Il foglio e gli attrezzi comuni delle pagine del lotto, dal <head> della
 * cornice (hook `rdacademy_enqueue_frontend_assets`, come il misuratore della
 * password). Solo per chi e collegato: le tre porte pubbliche non li usano.
 * RDACADEMYC_REST porta la radice REST e il gettone `wp_rest` per i controller
 * delle Tabelle funzionali (in RelDox: window.wpApiSettings dalla vista).
 */
add_action( 'rdacademy_enqueue_frontend_assets', static function () {
	if ( ! Rdacademy_Auth::is_logged_in() ) return;
	/* 0.1.04A (BUG-2): il foglio e lo script delle pagine del lotto non sono nel pacchetto e le pagine sono ancora
	   «in sviluppo»: non si chiamano. Entreranno con le pagine, scritte sul foglio comune (SPEC-GRAFICA v5 § 14). */
	echo '<script>window.RDACADEMYC_REST = ' . wp_json_encode( array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) ) . ';</script>' . "\n";
}, 5 );

/* ── Le icone delle viste del lotto: dal registro di famiglia (rdacademy_icona(), 0.1.04A) ── */
