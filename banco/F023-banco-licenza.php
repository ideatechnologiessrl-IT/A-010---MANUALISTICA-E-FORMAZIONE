<?php
/**
 * F023-banco-licenza.php — RelDoxAcademy · Formazione F023 · 21/11/2026
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Banco della «Licenza d uso» (voce F29, come RelDox CRD 3.76.03G voce 150). Rosso sulla 0.1.09A, verde sulla 0.1.10A.
 * Chiavi firmate a banco (coppia Ed25519 creata qui, RDACADEMY_BANCO_LICENZA), come banco/S702-4-banco-voce-150.php del CRD.
 * Con la trappola: una chiave di RelDox CRD, una chiave alterata, una chiave di un altro sito, un banco che deve dire ROSSO.
 *
 * uso:  php banco/F023-banco-licenza.php <cartella del plugin reldox-academy> [--trappola]
 */
$P = rtrim( $argv[1] ?? '', '/' );
$trappola = in_array( '--trappola', $argv, true );
$esiti = array();
function prova( string $n, bool $ok, string $d = '' ) { global $esiti; $esiti[] = array( $n, $ok, $d ); }
function leggi_f( string $f ): string { return is_file( $f ) ? (string) file_get_contents( $f ) : ''; }

// ── A. i file e la sintassi ─────────────────────────────────────────────────────
$file = array( 'license-admin', 'license-firma', 'license-pdf', 'license-testi', 'license-validate', 'license-verifica', 'license', 'licenza-stato' );
$tutti = true;
foreach ( $file as $f ) {
	$p = "$P/includes/license/class-rdacademy-$f.php";
	if ( ! is_file( $p ) ) { $tutti = false; continue; }
	exec( 'php -l ' . escapeshellarg( $p ) . ' 2>&1', $o, $rc ); if ( $rc !== 0 ) $tutti = false;
}
prova( 'A1 gli otto file della licenza ci sono e si leggono (php -l)', $tutti );
$stato = leggi_f( "$P/includes/license/class-rdacademy-licenza-stato.php" );
$admin = leggi_f( "$P/includes/license/class-rdacademy-license-admin.php" );
prova( 'A2 prodotto della chiave `reldox-academy`', (bool) preg_match( "/const PRODOTTO\s*=\s*'reldox-academy'/", $stato ) );
prova( 'A3 stessa chiave pubblica di Idea Technologies del CRD', strpos( $stato, 'VkmaHbDE54aHCTm5h9tlR0JJIOuNAxG3aps4HWBVHY8=' ) !== false );
prova( 'A4 voce «Licenza d uso» nel menu `rdacademy`', (bool) preg_match( "/add_submenu_page\(\s*'rdacademy',[\s\S]{0,400}?'Licenza d uso'/", $admin ) );
$tutto = ''; foreach ( $file as $f ) $tutto .= leggi_f( "$P/includes/license/class-rdacademy-$f.php" );
prova( 'A5 nessun nome del CRD rimasto negli otto file (Reldox_, RELDOX_, reldox_licen, reldox_license, page=reldox)', $stato !== '' && ! preg_match( '/\bReldox_|\bRELDOX_|reldox_licen|page=reldox/', $tutto ) );
prova( 'A11 nessuna porta su pre_wp_mail: la posta del resto del sito non si ferma', $stato !== '' && ! preg_match( "/add_filter\(\s*'pre_wp_mail'/", $stato ) );
$princ = leggi_f( "$P/reldox-academy.php" );
prova( 'A6 il plugin carica la licenza e la pagina', strpos( $princ, "includes/license/class-rdacademy-licenza-stato.php" ) !== false && strpos( $princ, 'new Rdacademy_License_Admin()' ) !== false );
$router = leggi_f( "$P/includes/class-rdacademy-router.php" );
prova( 'A7 router: sola consultazione prima dei permessi (D-150.3)', (bool) preg_match( '/Licenza_Stato::cessata\(\).*azione_ammessa.*Autenticazione e capability check/s', $router ) );
prova( 'A8 posta: la porta della casella (D-150.9)', strpos( leggi_f( "$P/includes/security/class-rdacademy-casella.php" ), 'Licenza_Stato::porta_posta()' ) !== false );
$pm = leggi_f( "$P/includes/security/class-rdacademy-password-mail.php" );
prova( 'A9 recupero password come posta di accesso; trasmissione del contratto', strpos( $pm, 'come_posta_di_accesso' ) !== false && strpos( $pm, 'function invia_contratto' ) !== false );
prova( 'A10 striscia nell applicazione', strpos( leggi_f( "$P/public/views/layout.php" ), 'Licenza_Stato::striscia(' ) !== false );

// ── B. la chiave, provata davvero (classe caricata con un WordPress finto) ─────────
if ( $stato !== '' && function_exists( 'sodium_crypto_sign_keypair' ) ) {
	$GLOBALS['opz'] = array(); $GLOBALS['sito'] = 'https://corsi.esempio.it';
	function get_option( $k, $d = false ) { return $GLOBALS['opz'][ $k ] ?? $d; }
	function update_option( $k, $v, $a = null ) { $GLOBALS['opz'][ $k ] = $v; return true; }
	function home_url( $p = '' ) { return $GLOBALS['sito'] . $p; }
	function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
	function wp_timezone() { return new DateTimeZone( 'Europe/Rome' ); }
	function apply_filters( $h, $v ) { return $h === 'rdacademy_licenza_chiavi_pubbliche' ? array_merge( (array) $v, $GLOBALS['pk_banco'] ) : $v; }
	function admin_url( $p = '' ) { return 'https://corsi.esempio.it/wp-admin/' . $p; }
	function esc_html( $s ) { return htmlspecialchars( (string) $s ); } function esc_attr( $s ) { return htmlspecialchars( (string) $s ); } function esc_url( $s ) { return (string) $s; }
	function add_filter() {} function add_action() {}
	define( 'ABSPATH', '/tmp/' ); define( 'RDACADEMY_BANCO_LICENZA', true );
	$kp = sodium_crypto_sign_keypair(); $sk = sodium_crypto_sign_secretkey( $kp );
	$GLOBALS['pk_banco'] = array( base64_encode( sodium_crypto_sign_publickey( $kp ) ) );
	$firma = function ( array $d ) use ( $sk ) {
		$b = rtrim( strtr( base64_encode( json_encode( $d ) ), '+/', '-_' ), '=' ); $m = 'RDX1.' . $b;
		return $m . '.' . rtrim( strtr( base64_encode( sodium_crypto_sign_detached( $m, $sk ) ), '+/', '-_' ), '=' );
	};
	require "$P/includes/license/class-rdacademy-licenza-stato.php";
	$L = 'Rdacademy_Licenza_Stato';
	prova( 'B1 senza chiave: «senza_chiave», RelDoxAcademy funziona come oggi (D-150.8)', $L::stato() === 'senza_chiave' && ! $L::cessata() );
	$fine_lontana = ( new DateTimeImmutable( '+300 days' ) )->format( 'Y-m-d' );
	$buona = $firma( array( 'prodotto' => 'reldox-academy', 'sito' => 'corsi.esempio.it', 'fine' => $fine_lontana, 'tipo' => 'canone', 'numero' => '0042' ) );
	$crd   = $firma( array( 'prodotto' => 'reldox-crd', 'sito' => 'corsi.esempio.it', 'fine' => $fine_lontana ) );
	$altro = $firma( array( 'prodotto' => 'reldox-academy', 'sito' => 'altro.esempio.it', 'fine' => $fine_lontana ) );
	$alterata = substr( $buona, 0, 12 ) . ( $buona[12] === 'A' ? 'B' : 'A' ) . substr( $buona, 13 );
	if ( $trappola ) $crd = $buona;   // la trappola: una chiave buona al posto di quella del CRD → B3 deve andare ROSSO
	prova( 'B2 chiave firmata per `reldox-academy`: valida', $L::verifica_chiave( $buona )['valida'] === true );
	prova( 'B3 chiave di RelDox CRD: rifiutata «di un altro prodotto»', strpos( $L::verifica_chiave( $crd )['motivo'], 'altro prodotto' ) !== false );
	prova( 'B4 chiave di un altro sito: rifiutata', strpos( $L::verifica_chiave( $altro )['motivo'], 'altro sito' ) !== false );
	prova( 'B5 chiave alterata: rifiutata', $L::verifica_chiave( $alterata )['valida'] === false );
	$r = $L::salva_chiave( $buona );
	prova( 'B6 chiave applicata: «attiva»', $r['ok'] && $L::stato() === 'attiva' );
	$GLOBALS['opz'] = array(); $L::salva_chiave( $firma( array( 'prodotto' => 'reldox-academy', 'sito' => 'corsi.esempio.it', 'fine' => ( new DateTimeImmutable( '+30 days' ) )->format( 'Y-m-d' ) ) ) );
	$rp = new ReflectionProperty( $L, 'cache' ); $rp->setAccessible( true ); $rp->setValue( null, null );
	prova( 'B7 a 30 giorni dalla fine: «preavviso» (60 giorni, D-150.2)', $L::stato() === 'preavviso' );
	$GLOBALS['opz'] = array(); $rp->setValue( null, null );
	$GLOBALS['opz']['rdacademy_licenza_chiave'] = $firma( array( 'prodotto' => 'reldox-academy', 'sito' => 'corsi.esempio.it', 'fine' => '2026-01-31' ) );
	prova( 'B8 canone finito: «cessata», sola consultazione', $L::stato() === 'cessata' && $L::cessata() );
	// le azioni vere dei carichi: le letture ammesse, le scritture spente
	$mappa = array(); foreach ( glob( "$P/includes/carico-*.php" ) as $c ) { preg_match_all( "/'([a-z_]+)'\s*=>\s*array\(\s*'(admin|write|auth|public)'/", (string) file_get_contents( $c ), $m, PREG_SET_ORDER ); foreach ( $m as $x ) $mappa[ $x[1] ] = $x[2]; }
	$letture = array( 'get_access_log', 'impostazioni_get', 'tech_roles', 'db_panoramica', 'spedizioni_elenco', 'spedizioni_rifiuti', 'backup_list', 'get_user' );
	$ok_l = true; foreach ( $letture as $a ) { if ( ! $L::azione_ammessa( $a, $mappa[ $a ] ?? 'admin' ) ) $ok_l = false; }
	$scritture = array_diff( array_keys( array_filter( $mappa, function ( $l ) { return $l === 'write' || $l === 'admin'; } ) ), $L::AMMESSE_EXTRA );
	$ok_s = count( $scritture ) > 0; foreach ( $scritture as $a ) { if ( $L::azione_ammessa( $a, $mappa[ $a ] ) ) $ok_s = false; }
	prova( 'B9 sola consultazione sulle azioni vere dei carichi: ' . count( $letture ) . ' letture ammesse, ' . count( $scritture ) . ' altre spente; preferenze ammesse', $ok_l && $ok_s && $L::azione_ammessa( 'me_prefs_save', 'auth' ) );
	prova( 'B10 sola consultazione: la posta si spegne, il recupero password esce', $L::porta_posta() !== '' && $L::come_posta_di_accesso( function () use ( $L ) { return $L::porta_posta(); } ) === '' );
	$GLOBALS['opz'] = array( 'rdacademy_licenza_memoria' => '2026-01-31' ); $rp->setValue( null, null );
	prova( 'B11 togliere la chiave dal database non riporta a «senza chiave» (memoria della fine)', $L::stato() !== 'senza_chiave' );
} else {
	prova( 'B1 la classe dello stato della licenza si carica', false, 'manca includes/license/class-rdacademy-licenza-stato.php' );
}

$verdi = count( array_filter( $esiti, function ( $e ) { return $e[1]; } ) );
foreach ( $esiti as $e ) echo ( $e[1] ? '  VERDE  ' : '  ROSSO  ' ) . $e[0] . ( $e[2] !== '' ? ' — ' . $e[2] : '' ) . "\n";
echo ( $verdi === count( $esiti ) ? 'VERDE' : 'ROSSO' ) . " — $verdi su " . count( $esiti ) . ( $trappola ? ' (trappola)' : '' ) . "\n";
exit( $verdi === count( $esiti ) ? 0 : 1 );
