<?php
/**
 * carico-e-190.php — LA VOCE DEL CATALOGO DEI CORSI NEL RAIL (analisi tecnica ed. 12, allegato A: RIFAI).
 *
 * Nel prodotto di partenza questo innesto portava i manuali dei ruoli dentro il prodotto (pagine, PDF, permessi per ruolo).
 * Nell Academy:
 *  - i manuali entrano come MATERIALI (blocco M) e si leggono come lezioni (la lezione è il RIFAI di manuali.php, blocco C · L):
 *    qui non restano né la classe dei manuali né le loro pagine né la consegna dei PDF;
 *  - resta la voce del rail, che diventa «Catalogo»: la pagina /academy/ (il Pannello che diventa «Il mio catalogo», § 9),
 *    con l icona del libro (carico-e.php). Per chi amministra l etichetta resta in italiano, come il resto del suo pannello.
 * ⛔ La voce non cambia chiave (`dashboard`): carico-e-143 le aggiunge l indirizzo della sessione (?_rv=…).
 * ⛔ «Le mie lezioni» e «Registro» arrivano con i loro blocchi (C · L, R).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'rdacademy_nav_items', static function ( array $items, string $app_url ): array {
	if ( ! isset( $items['dashboard'] ) ) return $items;
	$u     = Rdacademy_Auth::get_current_user();
	$admin = $u && Rdacademy_Router::user_can_admin( $u );
	$items['dashboard']['icon']  = 'manuali';
	$items['dashboard']['label'] = Rdacademy_Lingua::t( 'catalogo.voce', $admin ? 'it' : null );
	return $items;
}, 30, 2 );
