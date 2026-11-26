<?php
/**
 * Plugin Name:       RelDoxAcademy Diagnostica
 * Plugin URI:        https://www.ideatechnologies.com
 * Description:       Strumenti di sviluppo e assistenza di RelDoxAcademy (Manutenzione, Manutenzione storica, registro dei banchi, Console SQL). Solo per Idea Technologies.
 * Version:           0.1.20
 * Author:            Idea Technologies S.r.l.
 * Author URI:        https://www.ideatechnologies.com
 * License:           Proprietary
 * Text Domain:       reldox-academy-diagnostica
 * Requires at least: 6.0
 * Requires PHP:      8.0
 *
 * RelDoxAcademy 0.1.09A (Formazione F021, voce F29): copiato dalla diagnostica della base 1.24.11L
 * (5e4a40c, letto per intero), analisi tecnica § 12. TOLTI gli Strumenti 6 «Azzera il contratto di
 * licenza» e 7 «Licenza obbligatoria per l API» (allegato A: TOGLI). Console SQL: nella prossima versione.
 *
 * Nella base: NUOVO in 0.1.02 — SPEC-MANUTENZIONE-E-DIAGNOSTICA-v1 (RelDox CRD 3.56.01G,
 * standard della famiglia), livello 3: il plugin a parte che il cliente NON
 * riceve. Modello: reldox-diagnostica.php di RelDox CRD 3.56.01G.
 *
 * REGOLE (SPEC § 3, § 6):
 *  - stessi percorsi relativi che i file avevano nel prodotto (includes/…);
 *  - si accende su plugins_loaded priorita 20, DOPO il prodotto (che avvia le
 *    sue classi su plugins_loaded 10);
 *  - prodotto assente (spento, in installazione guidata): UN avviso e nient altro;
 *  - versione diversa da quella del prodotto: un avviso e parte lo stesso
 *    (RP-NO-OPERATION-GATES);
 *  - tutto per manage_options;
 *  - il verso e solo plugin → prodotto (R2): qui si leggono classi e costanti
 *    del prodotto, il prodotto non sa che questo plugin esiste.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RDACADEMY_DIAG_VERSION', '0.1.20' );
define( 'RDACADEMY_DIAG_STAGE',   'Alfa' );
define( 'RDACADEMY_DIAG_PACKAGE', '// 0.1.20A | F034 | console-sql-sola-lettura-r12' );   // lo scrive il compositore, uguale al prodotto (G10)
define( 'RDACADEMY_DIAG_DIR',     plugin_dir_path( __FILE__ ) );
define( 'RDACADEMY_DIAG_URL',     plugin_dir_url( __FILE__ ) );

/** Avviso unico in wp-admin, solo per chi puo agire (manage_options). */
function rdacademy_diag_avviso( string $classe, string $html ): void {
	add_action( 'admin_notices', static function () use ( $classe, $html ) {
		if ( ! current_user_can( 'manage_options' ) ) return;
		echo '<div class="notice ' . esc_attr( $classe ) . ' rdacademy-diag-avviso"><p>' . $html . '</p></div>';
	} );
}

function rdacademy_diag_avvio(): void {
	// Il prodotto c e e si e avviato davvero? (RDACADEMY_VERSION nasce prima del
	// cancello delle credenziali: senza Rdacademy_DB e Rdacademy_Router il prodotto dorme.)
	if ( ! defined( 'RDACADEMY_VERSION' ) || ! class_exists( 'Rdacademy_DB', false ) || ! class_exists( 'Rdacademy_Router', false ) ) {
		rdacademy_diag_avviso( 'notice-warning',
			'<strong>RelDoxAcademy Diagnostica</strong> è spenta: RelDoxAcademy non è attivo oppure non ha completato l’avvio '
			. '(installazione guidata del database in corso). Attiva e configura RelDoxAcademy, poi ricarica questa pagina.' );
		return;
	}

	if ( RDACADEMY_VERSION !== RDACADEMY_DIAG_VERSION ) {
		$prod = defined( 'RDACADEMY_VERSION_LABEL' ) ? RDACADEMY_VERSION_LABEL : RDACADEMY_VERSION;
		rdacademy_diag_avviso( 'notice-warning',
			'<strong>RelDoxAcademy Diagnostica ' . esc_html( RDACADEMY_DIAG_VERSION ) . '</strong> è di un’altra versione rispetto a '
			. '<strong>' . esc_html( RDACADEMY_PRODUCT ) . ' ' . esc_html( $prod ) . '</strong>: gli strumenti partono lo stesso, '
			. 'ma installa la diagnostica con lo stesso numero del prodotto (i due pacchetti escono insieme).' );
	}

	// Nello stesso ordine in cui il prodotto li caricava fino alla 0.1.01 (carico-b.php).
	require_once RDACADEMY_DIAG_DIR . 'includes/class-rdacademy-banchi-registro.php';
	require_once RDACADEMY_DIAG_DIR . 'includes/class-rdacademy-maintenance.php';
	Rdacademy_Maintenance::init();
	// 0.1.11A (F026, voce F29): Console SQL, banco standard n. 2 (mockup MOCKUP-CONSOLE-SQL-v1, D45). Strumenti 6 e 7 della licenza: tolti (analisi § 12).
	require_once RDACADEMY_DIAG_DIR . 'includes/class-rdacademy-console-sql.php';
	require_once RDACADEMY_DIAG_DIR . 'includes/banchi/class-rdacademy-banco-console-sql.php';
	Rdacademy_Banco_Console_SQL::init();
}
add_action( 'plugins_loaded', 'rdacademy_diag_avvio', 20 );
