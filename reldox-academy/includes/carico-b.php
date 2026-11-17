<?php
/**
 * RelDoxAcademy — CARICO DEL LOTTO B (i menu di WordPress).
 *
 * NUOVO (SPEC-GUSCIO-BASE § 4, lotto B). Il file principale lo richiede SOLO SE
 * ESISTE, dopo tutte le classi del nucleo: qui si caricano e si avviano i file
 * del lotto B senza toccare il file principale, il router o il layout.
 *
 * In RelDox CRD 3.55.09G le stesse classi si caricavano dal file principale
 * (require_once sparsi) e si avviavano dentro reldox_init(): Reldox_Docs con
 * init() statico.
 *
 * Copia ridotta (analisi tecnica ed. 7): via la licenza da firmare (`license/`,
 * classe, PDF, firma, pagina di WordPress); resta l ancora di licenza (solo il
 * dominio). Si tiene la stessa grammatica:
 * ogni classe si registra da sola sui suoi hook di admin.
 *
 * I menu del PRODOTTO (REGOLE-FAMIGLIA § 3.10), sotto la voce di primo
 * livello `rdacademy` del lotto A:
 *   10  Stato sistema          (Rdacademy_Admin, lotto A — la resa completa e qui)
 *   12  Documentazione         (Rdacademy_Docs)
 *   12  Primo accesso          (Rdacademy_Accesso_Emergenza — NUOVO 0.1.02)
 *
 * 0.1.02 — SPEC-MANUTENZIONE-E-DIAGNOSTICA-v1 (standard della famiglia): gli
 * strumenti di sviluppo non stanno piu nel prodotto, che non li carica, non li
 * nomina e non ne dipende (R1, R2, R3). Resta qui cio che serve al cliente
 * (R4): il primo accesso dell utente di servizio e il rientro d emergenza,
 * con la loro azione admin-ajax e la loro pagina.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-stato-sistema.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-schema-license-v1.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-license-anchor.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/docs/class-rdacademy-markdown.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/docs/class-rdacademy-docs-loader.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/docs/class-rdacademy-docs.php';
require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-accesso-emergenza.php';

/*
 * Lo schema dell ancora di licenza. In RelDox la tabella `license_activation`
 * nasceva dentro Reldox_Activator::migrate_tables(), cioe in un file che il
 * lotto B non puo toccare: qui ha la SUA classe di schema con versione e flag
 * (REGOLE-FAMIGLIA § 6) e passa dal Provisioning Guard come il nucleo.
 * Priorita 6: subito DOPO il provisioning del nucleo (priorita 5 del file
 * principale), cosi Rdacademy_Option e le tabelle del nucleo ci sono gia.
 */
add_action( 'plugins_loaded', static function () {
	Rdacademy_Provisioning_Guard::provision( 'Rdacademy_Schema_License_V1' );
}, 6 );

/*
 * L ancora di licenza e CONFIGURAZIONE dell installazione: l azzeramento dei
 * dati operativi (Impostazioni › Pericolo, lotto C) non la deve svuotare.
 * L accettazione della licenza vive in `options`, che il nucleo tiene gia.
 */
add_filter( 'rdacademy_reset_keep_tables', static function ( $keep ) {
	$keep   = (array) $keep;
	$keep[] = Rdacademy_Schema_License_V1::TABLE; // ancora di dominio: prova dell installazione, non dato operativo
	return $keep;
} );

Rdacademy_Stato_Sistema::init();
Rdacademy_Docs::init();
Rdacademy_Accesso_Emergenza::init();
