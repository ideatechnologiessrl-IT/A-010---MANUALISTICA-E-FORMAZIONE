<?php
/**
 * F018-banco-indurimento.php — RelDoxAcademy · Formazione F018 · 20/11/2026
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Banco della voce F32: riga «Indurimento: n su 18» (spec/SPEC-INDURIMENTO-INSTALLAZIONI-FAMIGLIA-RELDOX-v2.md
 * del nodo, § A e § D, owner 19/11/2026 15:49, chat RelDox CRD S723) nella «Copia configurazione» e nella
 * Stato sistema. Chiama la classe VERA del plugin (Rdacademy_Indurimento) su due siti finti:
 *   1. «nudo»: WordPress appena installato, nessuna difesa → la riga dice quali regole mancano;
 *   2. «indurito»: tutte le difese al loro posto → 16 su 18, A12 e A18 «da verificare a mano», mai «a posto».
 * Trappola (reversibile): RDX_TRAPPOLA=1 lascia readme.html raggiungibile sul sito indurito → deve andare ROSSO.
 *
 * uso:  php banco/F018-banco-indurimento.php <cartella del plugin>
 * esce 0 se VERDE, 1 se ROSSO. Nessun numero si scrive a mano: le righe [VERDE]/[ROSSO] sono l esito.
 */

$P = rtrim( $argv[1] ?? '', '/' );
$esiti = array();
function esito( bool $ok, string $cosa ): void {
	global $esiti;
	$esiti[] = $ok;
	echo ( $ok ? '[VERDE] ' : '[ROSSO] ' ) . $cosa . "\n";
}
function fine(): void {
	global $esiti;
	$v = count( array_filter( $esiti ) );
	$r = count( $esiti ) - $v;
	echo "\n" . $v . ' verdi, ' . $r . ' rossi' . "\n" . ( $r === 0 ? 'ESITO: VERDE' : 'ESITO: ROSSO' ) . "\n";
	exit( $r === 0 ? 0 : 1 );
}

/* ---- WordPress finto: solo le funzioni che la classe usa ---- */
define( 'ABSPATH', '/tmp/rdx-banco-wp/' );
define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );
$GLOBALS['SITO'] = array();
function get_option( $k, $def = false ) { return $GLOBALS['SITO']['opzioni'][ $k ] ?? $def; }
function get_site_option( $k, $def = false ) { return get_option( $k, $def ); }
function get_site_transient( $k ) { return $GLOBALS['SITO']['transitori'][ $k ] ?? false; }
function get_transient( $k ) { return false; }
function set_transient( $k, $v, $t ) { return true; }
function delete_transient( $k ) { return true; }
function home_url( $p = '' ) { return 'https://banco.example' . ( $p === '' ? '' : '/' . ltrim( $p, '/' ) ); }
function site_url( $p = '' ) { return home_url( $p ); }
function content_url( $p = '' ) { return home_url( 'wp-content/' . ltrim( $p, '/' ) ); }
function includes_url( $p = '' ) { return home_url( 'wp-includes/' . ltrim( $p, '/' ) ); }
function apply_filters( $h, $v ) { return $GLOBALS['SITO']['filtri'][ $h ] ?? $v; }
function is_plugin_active( $p ) { return in_array( $p, $GLOBALS['SITO']['attivi'], true ); }
function get_users( $a ) { return $GLOBALS['SITO']['admin']; }
function get_page_by_path( $s, $o = 'OBJECT', $t = 'page' ) { return in_array( $t . ':' . $s, $GLOBALS['SITO']['esempi'], true ) ? (object) array( 'post_status' => 'publish' ) : null; }
function get_comment( $id ) { return in_array( 'commento:' . $id, $GLOBALS['SITO']['esempi'], true ) ? (object) array( 'comment_author_email' => 'wapuu@wordpress.example' ) : null; }
function is_ssl() { return true; }
function wp_parse_url( $u, $c = -1 ) { return parse_url( $u, $c ); }
function rdacademy_banco_risposta( $metodo, $url ) {
	$s = $GLOBALS['SITO'];
	$pth = (string) parse_url( $url, PHP_URL_PATH );
	if ( $pth === '' ) $pth = '/';
	if ( isset( $s['pagine'][ $pth ] ) ) return $s['pagine'][ $pth ];
	return array( 'codice' => 404, 'intestazioni' => $s['intestazioni_base'], 'corpo' => '' );
}
$GLOBALS['SITO_CARTELLE_DIAG'] = array();

function sito_nudo(): array {
	return array(
		'opzioni'     => array( 'default_comment_status' => 'open', 'default_ping_status' => 'open', 'blog_public' => '1', 'auto_update_core_major' => 'enabled' ),
		'transitori'  => array( 'update_core' => (object) array( 'updates' => array( (object) array( 'response' => 'upgrade' ) ) ), 'update_plugins' => (object) array( 'response' => array( 'x/x.php' => 1 ) ) ),
		'filtri'      => array(),
		'attivi'      => array(),
		'admin'       => array( (object) array( 'user_login' => 'admin' ) ),
		'esempi'      => array( 'post:hello-world', 'page:sample-page', 'commento:1' ),
		'costanti'    => array(),
		'diag'        => array( 'reldox-academy-diagnostica' ),
		'intestazioni_base' => array(),
		'pagine'      => array(
			'/xmlrpc.php'           => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => '<methodResponse><params><param><value><array><data><value><string>system.multicall</string></value></data></array></value></param></params></methodResponse>' ),
			'/wp-json/wp/v2/users'  => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => '[{"id":1,"slug":"admin"}]' ),
			'/readme.html'          => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => 'WordPress' ),
			'/license.txt'          => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => 'GPL' ),
			'/wp-config-sample.php' => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => '' ),
			'/'                     => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => '<html>Ciao mondo!</html>' ),
			'/wp-content/debug.log' => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => 'PHP Notice' ),
			'/wp-content/uploads/'  => array( 'codice' => 200, 'intestazioni' => array(), 'corpo' => '<title>Index of /wp-content/uploads</title>' ),
		),
		'cache'       => array( 'esito' => 'rosso', 'motivo' => 'senza no-store' ),
	);
}
function sito_indurito(): array {
	$base = array( 'strict-transport-security' => 'max-age=31536000', 'x-content-type-options' => 'nosniff', 'x-frame-options' => 'SAMEORIGIN', 'referrer-policy' => 'strict-origin-when-cross-origin' );
	$s = array(
		'opzioni'     => array( 'default_comment_status' => 'closed', 'default_ping_status' => 'closed', 'blog_public' => '1', 'auto_update_core_major' => 'disabled' ),
		'transitori'  => array( 'update_core' => (object) array( 'updates' => array( (object) array( 'response' => 'latest' ) ) ), 'update_plugins' => (object) array( 'response' => array() ) ),
		'filtri'      => array(),
		'attivi'      => array( 'wordfence/wordfence.php' ),
		'admin'       => array( (object) array( 'user_login' => 'g.rossi' ) ),
		'esempi'      => array(),
		'costanti'    => array( 'DISALLOW_FILE_EDIT' => true ),
		'diag'        => array(),
		'intestazioni_base' => $base,
		'pagine'      => array(
			'/xmlrpc.php'                 => array( 'codice' => 403, 'intestazioni' => $base, 'corpo' => '' ),
			'/wp-json/wp/v2/users'        => array( 'codice' => 401, 'intestazioni' => $base, 'corpo' => '' ),
			'/'                           => array( 'codice' => 200, 'intestazioni' => $base, 'corpo' => '<html>Corsi</html>' ),
			'/wp-content/uploads/'        => array( 'codice' => 403, 'intestazioni' => $base, 'corpo' => '' ),
			'/.well-known/security.txt'   => array( 'codice' => 200, 'intestazioni' => $base, 'corpo' => "Contact: mailto:support@ideatechnologies.com\nExpires: 2027-11-20T00:00:00Z\n" ),
		),
		'cache'       => array( 'esito' => 'verde', 'motivo' => 'no-store, Vary: Cookie' ),
	);
	if ( getenv( 'RDX_TRAPPOLA' ) === '1' ) {
		$s['pagine']['/readme.html'] = array( 'codice' => 200, 'intestazioni' => $base, 'corpo' => 'WordPress' );
	}
	return $s;
}

/* ---- la classe vera ---- */
$f = $P . '/includes/class-rdacademy-indurimento.php';
esito( is_file( $f ), 'il motore esiste: includes/class-rdacademy-indurimento.php' );
if ( ! is_file( $f ) ) fine();
require $f;
esito( class_exists( 'Rdacademy_Indurimento' ), 'classe Rdacademy_Indurimento caricata' );
if ( ! class_exists( 'Rdacademy_Indurimento' ) ) fine();

$regole = Rdacademy_Indurimento::regole();
esito( count( $regole ) === 18 && array_keys( $regole ) === array_map( fn( $i ) => 'A' . $i, range( 1, 18 ) ), 'diciotto regole, da A1 ad A18, nell ordine della SPEC v2' );

$chiedi = 'rdacademy_banco_risposta';
$amb = static function ( array $s ): array {
	return array(
		'costante' => static fn( string $c ) => $s['costanti'][ $c ] ?? null,
		'diag'     => static fn() => $s['diag'],
		'cache'    => static fn() => $s['cache'],
	);
};

/* 1. sito nudo */
$GLOBALS['SITO'] = sito_nudo();
$m = Rdacademy_Indurimento::misura( $chiedi, $amb( $GLOBALS['SITO'] ) );
$e = $m['esiti'];
$attese_mancanti = array( 'A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'A9', 'A10', 'A11', 'A13', 'A15', 'A17' );
$mancanti = array_keys( array_filter( $e, fn( $x ) => $x['stato'] === 'manca' ) );
esito( $mancanti === $attese_mancanti, 'sito nudo: mancano ' . implode( ', ', $attese_mancanti ) . ' (misurate: ' . implode( ', ', $mancanti ) . ')' );
esito( $e['A12']['stato'] === 'a mano' && $e['A18']['stato'] === 'a mano', 'sito nudo: A12 e A18 «da verificare a mano», mai «a posto»' );
esito( $m['a_posto'] === 2, 'sito nudo: a posto solo A14 e A16 (non richieste fuori da «solo applicazione»): ' . $m['a_posto'] );
$riga = Rdacademy_Indurimento::riga( $m );
esito( str_starts_with( $riga, 'Indurimento: 2 su 18' ) && str_contains( $riga, 'mancano A1, A2' ) && str_contains( $riga, 'da verificare a mano A12, A18' ), 'sito nudo: la riga «' . $riga . '»' );

/* 2. sito indurito */
$GLOBALS['SITO'] = sito_indurito();
$m = Rdacademy_Indurimento::misura( $chiedi, $amb( $GLOBALS['SITO'] ) );
$riga = Rdacademy_Indurimento::riga( $m );
esito( $m['a_posto'] === 16, 'sito indurito: 16 su 18 a posto (misurate ' . $m['a_posto'] . ')' );
esito( $riga === 'Indurimento: 16 su 18 · da verificare a mano A12, A18', 'sito indurito: la riga «' . $riga . '»' );

/* 3. «solo applicazione»: A14 e A16 diventano regole vere */
$GLOBALS['SITO'] = sito_indurito();
$a = $amb( $GLOBALS['SITO'] );
$a['solo_app'] = static fn() => true;
$GLOBALS['SITO']['attivi'][] = 'wp-super-cache/wp-cache.php';
$m = Rdacademy_Indurimento::misura( $chiedi, $a );
esito( $m['esiti']['A14']['stato'] === 'manca' && $m['esiti']['A16']['stato'] === 'manca', 'solo applicazione: motori non scoraggiati e cache delle pagine attiva → A14 e A16 mancano' );

/* 4. una regola che non si riesce a misurare non è mai «a posto» */
$GLOBALS['SITO'] = sito_indurito();
$m = Rdacademy_Indurimento::misura( static fn() => null, $amb( $GLOBALS['SITO'] ) );
esito( $m['esiti']['A3']['stato'] !== 'a posto' && $m['esiti']['A11']['stato'] !== 'a posto', 'sito che non risponde a se stesso: A3 e A11 non si dichiarano «a posto»' );

/* 5. la riga sta negli appunti e nella pagina */
$pag = (string) @file_get_contents( $P . '/includes/class-rdacademy-stato-sistema.php' );
$pri = (string) @file_get_contents( $P . '/reldox-academy.php' );
esito( (bool) preg_match( "/\\\$pad\\( 'Indurimento:' \\)[^\\n]*Rdacademy_Indurimento::/", $pag ), 'Copia configurazione: riga «Indurimento:» costruita dal motore (vale anche per la riga del plugin, stesso testo)' );
esito( str_contains( $pag, 'Indurimento dell installazione' ) && str_contains( $pag, "Rdacademy_Indurimento::regole()" ), 'Stato sistema: riquadro «Indurimento dell installazione» con le 18 regole' );
esito( str_contains( $pri, "includes/class-rdacademy-indurimento.php" ), 'il file principale carica il motore' );
$codice = '';
foreach ( token_get_all( (string) file_get_contents( $f ) ) as $t ) {   // il ⛔ dell intestazione obbligatoria sta nei commenti: si guarda il resto
	if ( is_array( $t ) && in_array( $t[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) continue;
	$codice .= is_array( $t ) ? $t[1] : $t;
}
esito( ! preg_match( '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $codice ), 'nessuna emoji nei testi del motore (icone del registro comune)' );

fine();
