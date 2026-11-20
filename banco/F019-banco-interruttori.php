<?php
/**
 * F019-banco-interruttori.php — RelDoxAcademy · Formazione F019 · 20/11/2026
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Banco della voce F34 e di BUG-4 (0.1.08A).
 *   F34 — interruttori A1 (XML-RPC, filtro `xmlrpc_enabled`) e A2 (elenco utenti agli anonimi, filtro
 *         `rest_endpoints`), SPEC-INDURIMENTO v2 § D.2 del nodo (owner 13/11/2026 19:19, chat RelDox CRD S703,
 *         (c) «Spento di serie»): spenti di serie, li accende l amministratore.
 *   BUG-4 — A9: la guardia della cache con esito «arancio» (nessuna copia, Vary: Cookie tolto dal server) è
 *         «a posto» (SPEC A9: «o pagine escluse dalla cache»); «non misurabile» è «da verificare a mano»;
 *         solo «rosso» (una copia trovata) è «manca».
 * Chiama le classi VERE del plugin. Il WordPress finto è quello di banco/F018-banco-indurimento.php (la parte
 * prima di «la classe vera»), letto da lì e non ricopiato.
 * Trappola (reversibile): RDX_TRAPPOLA=1 fa passare l anonimo per un utente collegato → A2 deve andare ROSSO.
 *
 * uso:  php banco/F019-banco-interruttori.php <cartella del plugin>
 * esce 0 se VERDE, 1 se ROSSO.
 */

$P = rtrim( $argv[1] ?? '', '/' );

/* ---- il WordPress finto del banco F018 ---- */
$f18 = (string) file_get_contents( __DIR__ . '/F018-banco-indurimento.php' );
$taglio = strpos( $f18, '/* ---- la classe vera ---- */' );
$tmp = sys_get_temp_dir() . '/rdx-f019-finto-' . getmypid() . '.php';
file_put_contents( $tmp, substr( $f18, 0, $taglio ) );
$argv_salvato = $argv;
require $tmp;
unlink( $tmp );
$P = rtrim( $argv_salvato[1] ?? '', '/' );

/* ---- quel che serve in più agli interruttori ---- */
$GLOBALS['FILTRI'] = array();
function add_filter( $h, $cb, $p = 10, $n = 1 ) { $GLOBALS['FILTRI'][ $h ][] = $cb; return true; }
function add_action( $h, $cb, $p = 10, $n = 1 ) { return add_filter( $h, $cb, $p, $n ); }
function is_user_logged_in() { return getenv( 'RDX_TRAPPOLA' ) === '1' ? true : ! empty( $GLOBALS['COLLEGATO'] ); }
function __return_false() { return false; }
$GLOBALS['TOLTE'] = array();
function remove_action( $h, $cb, $p = 10 ) { $GLOBALS['TOLTE'][] = $h . ':' . $cb; return true; }
final class Rdacademy_Option {
	public static array $v = array();
	public static function get( string $k, $d = '' ) { return self::$v[ $k ] ?? $d; }
	public static function set( string $k, $v, $a = null ): bool { self::$v[ $k ] = $v; return true; }
}
function applica( string $h, $v ) {
	foreach ( $GLOBALS['FILTRI'][ $h ] ?? array() as $cb ) $v = call_user_func( $cb, $v );
	return $v;
}

/* ---- 1. BUG-4: A9 secondo l esito della guardia della cache ---- */
$f = $P . '/includes/class-rdacademy-indurimento.php';
require $f;
$chiedi = 'rdacademy_banco_risposta';
$amb = static function ( array $s ): array {
	return array(
		'costante' => static fn( string $c ) => $s['costanti'][ $c ] ?? null,
		'diag'     => static fn() => $s['diag'],
		'cache'    => static fn() => $s['cache'],
	);
};
$attese = array( 'verde' => 'a posto', 'arancio' => 'a posto', 'non misurabile' => 'a mano', 'rosso' => 'manca' );
foreach ( $attese as $esito_cache => $stato ) {
	$GLOBALS['SITO'] = sito_indurito();
	$GLOBALS['SITO']['cache'] = array( 'esito' => $esito_cache, 'motivo' => 'prova ' . $esito_cache );
	$m = Rdacademy_Indurimento::misura( $chiedi, $amb( $GLOBALS['SITO'] ) );
	esito( $m['esiti']['A9']['stato'] === $stato, 'A9 con la cache «' . $esito_cache . '» → «' . $stato . '» (misurato «' . $m['esiti']['A9']['stato'] . '»)' );
}

/* ---- 2. F34: il motore degli interruttori ---- */
$fi = $P . '/includes/class-rdacademy-interruttori.php';
esito( is_file( $fi ), 'il motore esiste: includes/class-rdacademy-interruttori.php' );
if ( ! is_file( $fi ) ) fine();
require $fi;
esito( class_exists( 'Rdacademy_Interruttori' ), 'classe Rdacademy_Interruttori caricata' );
if ( ! class_exists( 'Rdacademy_Interruttori' ) ) fine();

// 2a. spenti di serie: nessun filtro, WordPress come prima
Rdacademy_Option::$v = array(); $GLOBALS['FILTRI'] = array();
Rdacademy_Interruttori::init();
esito( ! Rdacademy_Interruttori::acceso( 'A1' ) && ! Rdacademy_Interruttori::acceso( 'A2' ), 'di serie A1 e A2 spenti (owner 13/11/2026 19:19: «Spento di serie»)' );
esito( empty( $GLOBALS['FILTRI']['xmlrpc_enabled'] ) && empty( $GLOBALS['FILTRI']['rest_endpoints'] ), 'spenti: nessun filtro aggiunto' );
esito( ! Rdacademy_Interruttori::chiude_xmlrpc( true ), 'spenti: xmlrpc.php resta aperto' );

// 2b. A1 acceso
Rdacademy_Option::$v = array(); $GLOBALS['FILTRI'] = array();
Rdacademy_Interruttori::salva( true, false );
Rdacademy_Interruttori::init();
esito( Rdacademy_Interruttori::acceso( 'A1' ) && ! Rdacademy_Interruttori::acceso( 'A2' ), 'salva(A1 sì, A2 no) si rilegge così' );
esito( applica( 'xmlrpc_enabled', true ) === false, 'A1 acceso: filtro xmlrpc_enabled → false' );
esito( Rdacademy_Interruttori::chiude_xmlrpc( true ) && ! Rdacademy_Interruttori::chiude_xmlrpc( false ), 'A1 acceso: xmlrpc.php chiuso (403) e solo lui' );
$h = applica( 'wp_headers', array( 'X-Pingback' => 'https://banco.example/xmlrpc.php', 'X-Altro' => '1' ) );
esito( ! isset( $h['X-Pingback'] ) && isset( $h['X-Altro'] ), 'A1 acceso: tolta l intestazione X-Pingback, le altre restano' );
esito( empty( $GLOBALS['FILTRI']['rest_endpoints'] ), 'A1 acceso non tocca l elenco utenti' );
esito( ! empty( $GLOBALS['FILTRI']['wp'] ) && str_contains( (string) file_get_contents( $fi ), "header_remove( 'X-Pingback' )" ), 'A1 acceso: X-Pingback tolta anche quando WordPress la manda con header() (revisione a freddo)' );
esito( in_array( 'wp_head:rsd_link', $GLOBALS['TOLTE'], true ) && in_array( 'wp_head:pingback_header', $GLOBALS['TOLTE'], true ), 'A1 acceso: le pagine non indicano più xmlrpc.php (EditURI e pingback)' );

// 2c. A2 acceso
Rdacademy_Option::$v = array(); $GLOBALS['FILTRI'] = array();
Rdacademy_Interruttori::salva( false, true );
Rdacademy_Interruttori::init();
$rotte = array( '/wp/v2/users' => 1, '/wp/v2/users/(?P<id>[\d]+)' => 1, '/wp/v2/users/me' => 1, '/wp/v2/posts' => 1, '/rdacademy/v1/stato' => 1 );
$GLOBALS['COLLEGATO'] = false;
$anon = applica( 'rest_endpoints', $rotte );
esito( ! isset( $anon['/wp/v2/users'] ) && ! isset( $anon['/wp/v2/users/(?P<id>[\d]+)'] ) && ! isset( $anon['/wp/v2/users/me'] ), 'A2 acceso: all anonimo niente /wp/v2/users' );
esito( isset( $anon['/wp/v2/posts'] ) && isset( $anon['/rdacademy/v1/stato'] ), 'A2 acceso: le altre rotte restano' );
$GLOBALS['COLLEGATO'] = true;
esito( applica( 'rest_endpoints', $rotte ) === $rotte, 'A2 acceso: chi è collegato vede tutto (l editor dei contenuti lavora come prima)' );
$GLOBALS['COLLEGATO'] = false;
esito( ! Rdacademy_Interruttori::chiude_xmlrpc( true ), 'A2 acceso non tocca XML-RPC' );

/* ---- 3. il file principale carica il motore e lo avvia; la riga del motore dice dove si chiude ---- */
$pri = (string) @file_get_contents( $P . '/reldox-academy.php' );
esito( str_contains( $pri, "includes/class-rdacademy-interruttori.php" ) && str_contains( $pri, 'Rdacademy_Interruttori::init' ), 'il file principale carica e avvia gli interruttori' );
$GLOBALS['SITO'] = sito_nudo();
Rdacademy_Option::$v = array();
$m = Rdacademy_Indurimento::misura( $chiedi, $amb( $GLOBALS['SITO'] ) );
esito( str_contains( $m['esiti']['A1']['nota'], 'interruttore' ) && str_contains( $m['esiti']['A2']['nota'], 'interruttore' ), 'A1 e A2 «manca»: la nota dice che si chiudono con l interruttore' );

/* ---- 4. a schermo (mockup approvato 20/11/2026 07:49): modulo nel riquadro, salvataggio protetto ---- */
$pag = (string) @file_get_contents( $P . '/includes/class-rdacademy-stato-sistema.php' );
$mot = (string) @file_get_contents( $fi );
esito( str_contains( $pag, "wp_nonce_field( 'rdacademy_interruttori' )" ) && str_contains( $pag, 'value="rdacademy_interruttori"' ) && str_contains( $pag, 'Rdacademy_Interruttori::TESTI[ $rx_id ]' ), 'Stato sistema: modulo con nonce e un interruttore sotto A1 e sotto A2' );
esito( str_contains( $mot, "admin_post_rdacademy_interruttori" ) && str_contains( $mot, "current_user_can( 'manage_options' )" ) && str_contains( $mot, "check_admin_referer( 'rdacademy_interruttori' )" ), 'salvataggio: solo manage_options e nonce verificato' );
esito( str_contains( $mot, 'delete_transient( Rdacademy_Indurimento::TRANSITORIO )' ), 'dopo il salvataggio la misura si rifà subito' );
esito( array_keys( Rdacademy_Interruttori::TESTI ) === array( 'A1', 'A2' ), 'interruttori solo per A1 e A2 (SPEC § D.2)' );

fine();
