<?php
/**
 * RelDoxAcademy — Indurimento dell installazione (voce F32, 0.1.07A · Formazione F018 · 20/11/2026).
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Misura le diciotto regole di spec/SPEC-INDURIMENTO-INSTALLAZIONI-FAMIGLIA-RELDOX-v2.md (nodo RelDox-Famiglia,
 * § A; § D: riga «Indurimento: n su 18», owner 19/11/2026 15:49, chat RelDox CRD S723) e scrive la riga per la
 * «Copia configurazione» e per la Stato sistema.
 *  - Le regole che si vedono solo da fuori (A1-A5, A9, A11, A13, A16) si provano chiedendo le pagine al sito
 *    stesso, senza cookie.
 *  - A12 e A18 non si provano da dentro: «da verificare a mano», MAI «a posto». Così ogni regola che non si
 *    riesce a misurare (il sito non risponde a se stesso, un dato che manca).
 *  - A14 e A16 valgono per le installazioni «solo applicazione» (filtro rdacademy_solo_applicazione, spento di
 *    serie): altrove sono «non richieste» e si contano a posto.
 *  - Sola lettura: la misura non cambia niente sul sito e non ferma nessuno (regola «nessun blocco», 18/11/2026).
 * La fotografia resta in un transitorio di 10 minuti: la pagina e gli appunti leggono la stessa.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Indurimento {

	const TRANSITORIO = 'rdacademy_indurimento_v2';
	const A_POSTO     = 'a posto';
	const MANCA       = 'manca';
	const A_MANO      = 'a mano';

	/** Le diciotto regole, nell ordine della SPEC v2 § A, in parole brevi. */
	public static function regole(): array {
		return array(
			'A1'  => 'XML-RPC spento',
			'A2'  => 'Elenco utenti nascosto agli anonimi',
			'A3'  => 'readme.html, license.txt, wp-config-sample.php non raggiungibili',
			'A4'  => 'Intestazioni di sicurezza (HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy)',
			'A5'  => 'Nessun file sensibile raggiungibile, nessun elenco di cartelle',
			'A6'  => 'Modifica dei file dal pannello spenta, debug spento',
			'A7'  => 'WordPress e plugin aggiornati, scansione di sicurezza attiva',
			'A8'  => 'Nessun plugin di Diagnostica sul sito',
			'A9'  => 'Pagine con dati personali fuori dalla cache (no-store)',
			'A10' => 'Amministratori al minimo, nessun utente di prova o iniziale',
			'A11' => '/.well-known/security.txt col contatto per le segnalazioni',
			'A12' => 'Hosting dimensionato sul picco degli utenti',
			'A13' => 'Nessun contenuto d esempio di WordPress',
			'A14' => 'Solo applicazione: motori di ricerca scoraggiati',
			'A15' => 'Commenti, pingback e trackback chiusi',
			'A16' => 'Solo applicazione: nessuna cache delle pagine',
			'A17' => 'Aggiornamenti automatici solo di manutenzione e sicurezza',
			'A18' => 'Plugin di sicurezza senza trasferimenti fuori UE non dichiarati',
		);
	}

	/** La fotografia per la pagina e per gli appunti: misurata una volta ogni 10 minuti. */
	public static function fotografia(): array {
		$m = get_transient( self::TRANSITORIO );
		if ( is_array( $m ) && isset( $m['esiti'] ) ) return $m;
		$m = self::misura();
		set_transient( self::TRANSITORIO, $m, 10 * MINUTE_IN_SECONDS );
		return $m;
	}

	/**
	 * La misura. $chiedi( metodo, url, corpo ) → array( codice, intestazioni, corpo ) oppure null se il sito non
	 * risponde; $amb le letture dell ambiente (costanti, cartelle di diagnostica, guardia della cache, solo app).
	 * Entrambi si possono passare dal banco; di serie leggono il sito vero.
	 */
	public static function misura( ?callable $chiedi = null, array $amb = array() ): array {
		$chiedi   = $chiedi ?? array( __CLASS__, 'chiedi' );
		$costante = $amb['costante'] ?? static fn( string $c ) => defined( $c ) ? constant( $c ) : null;
		$diag     = $amb['diag'] ?? array( __CLASS__, 'cartelle_diagnostica' );
		$cache    = $amb['cache'] ?? static fn() => class_exists( 'Rdacademy_Cache_Guardia' ) ? Rdacademy_Cache_Guardia::prova() : null;
		$solo_app = (bool) ( isset( $amb['solo_app'] ) ? ( $amb['solo_app'] )() : apply_filters( 'rdacademy_solo_applicazione', false ) );

		$r   = array();
		$get = static fn( string $p ) => $chiedi( 'GET', home_url( $p ), '' );
		$chiusa = static function ( $risp ): ?bool {   // true se la pagina NON si legge, null se non misurabile
			if ( ! is_array( $risp ) ) return null;
			return (int) $risp['codice'] !== 200;
		};
		$int = static function ( $risp, string $nome ): string {
			if ( ! is_array( $risp ) ) return '';
			foreach ( (array) $risp['intestazioni'] as $k => $v ) {
				if ( strtolower( (string) $k ) === $nome ) return is_array( $v ) ? implode( ', ', $v ) : (string) $v;
			}
			return '';
		};

		// A1 — XML-RPC: una POST di system.listMethods deve tornare 403/404/405 o la risposta «servizi spenti».
		$x = $chiedi( 'POST', site_url( 'xmlrpc.php' ), '<?xml version="1.0"?><methodCall><methodName>system.listMethods</methodName></methodCall>' );
		if ( ! is_array( $x ) ) $r['A1'] = self::e( self::A_MANO, 'il sito non risponde a se stesso' );
		else {
			$aperto = (int) $x['codice'] === 200 && str_contains( (string) $x['corpo'], 'system.' ) && ! str_contains( (string) $x['corpo'], 'faultCode' );
			$r['A1'] = $aperto ? self::e( self::MANCA, 'xmlrpc.php risponde all elenco dei metodi' ) : self::e( self::A_POSTO, 'codice ' . (int) $x['codice'] );
		}

		// A2 — elenco utenti agli anonimi.
		$u = $get( 'wp-json/wp/v2/users' );
		if ( ! is_array( $u ) ) $r['A2'] = self::e( self::A_MANO, 'il sito non risponde a se stesso' );
		else {
			$lista = (int) $u['codice'] === 200 ? json_decode( (string) $u['corpo'], true ) : null;
			$r['A2'] = is_array( $lista ) && count( $lista ) > 0
				? self::e( self::MANCA, '/wp-json/wp/v2/users dà ' . count( $lista ) . ' nomi di accesso' )
				: self::e( self::A_POSTO, 'codice ' . (int) $u['codice'] );
		}

		// A3 — i file che dicono la versione di WordPress.
		$r['A3'] = self::elenco_chiuso( $chiusa, $get, array( 'readme.html', 'license.txt', 'wp-config-sample.php' ) );

		// A4 — intestazioni di sicurezza sulla radice.
		$h = $get( '' );
		if ( ! is_array( $h ) ) $r['A4'] = self::e( self::A_MANO, 'il sito non risponde a se stesso' );
		else {
			$manca = array();
			if ( $int( $h, 'strict-transport-security' ) === '' ) $manca[] = 'HSTS';
			if ( stripos( $int( $h, 'x-content-type-options' ), 'nosniff' ) === false ) $manca[] = 'X-Content-Type-Options';
			if ( $int( $h, 'x-frame-options' ) === '' && stripos( $int( $h, 'content-security-policy' ), 'frame-ancestors' ) === false ) $manca[] = 'X-Frame-Options';
			if ( $int( $h, 'referrer-policy' ) === '' ) $manca[] = 'Referrer-Policy';
			$r['A4'] = $manca ? self::e( self::MANCA, 'mancano ' . implode( ', ', $manca ) ) : self::e( self::A_POSTO, 'quattro intestazioni presenti' );
		}

		// A5 — file sensibili ed elenco delle cartelle (il .php eseguibile in uploads/ resta alla verifica esterna).
		$a5 = self::elenco_chiuso( $chiusa, $get, array( 'wp-content/debug.log', '.env', 'wp-config.php.bak', 'wp-config.php~', '.git/HEAD' ) );
		$up = $get( 'wp-content/uploads/' );
		if ( is_array( $up ) && (int) $up['codice'] === 200 && stripos( (string) $up['corpo'], 'Index of' ) !== false ) {
			$a5 = self::e( self::MANCA, trim( ( $a5['stato'] === self::MANCA ? $a5['nota'] . '; ' : '' ) . 'elenco della cartella uploads/ visibile' ) );
		}
		$r['A5'] = $a5;

		// A6 — DISALLOW_FILE_EDIT acceso, WP_DEBUG spento.
		$manca = array();
		if ( true !== $costante( 'DISALLOW_FILE_EDIT' ) ) $manca[] = 'DISALLOW_FILE_EDIT non attivo';
		if ( true === $costante( 'WP_DEBUG' ) ) $manca[] = 'WP_DEBUG acceso';
		$r['A6'] = $manca ? self::e( self::MANCA, implode( '; ', $manca ) ) : self::e( self::A_POSTO, 'modifica dei file spenta, debug spento' );

		// A7 — aggiornamenti in attesa e scansione di sicurezza attiva.
		$core = get_site_transient( 'update_core' );
		$plug = get_site_transient( 'update_plugins' );
		if ( ! is_object( $core ) || ! is_object( $plug ) ) $r['A7'] = self::e( self::A_MANO, 'controllo degli aggiornamenti non ancora fatto da WordPress' );
		else {
			$manca = array();
			foreach ( (array) ( $core->updates ?? array() ) as $up1 ) {
				if ( isset( $up1->response ) && $up1->response === 'upgrade' ) { $manca[] = 'WordPress da aggiornare'; break; }
			}
			$n = count( (array) ( $plug->response ?? array() ) );
			if ( $n > 0 ) $manca[] = $n . ' plugin da aggiornare';
			if ( ! self::scansione_attiva() ) $manca[] = 'nessuna scansione di sicurezza attiva';
			$r['A7'] = $manca ? self::e( self::MANCA, implode( '; ', $manca ) ) : self::e( self::A_POSTO, 'tutto aggiornato, scansione attiva' );
		}

		// A8 — cartelle *-diagnostica nei plugin.
		$d = (array) $diag();
		$r['A8'] = $d ? self::e( self::MANCA, 'presente: ' . implode( ', ', $d ) . ' (ammesso solo sull ambiente di prova)' ) : self::e( self::A_POSTO, 'nessun plugin di diagnostica' );

		// A9 — la guardia della cache del prodotto (due letture senza cookie di una pagina dell app).
		$pc = $cache();
		if ( ! is_array( $pc ) ) $r['A9'] = self::e( self::A_MANO, 'guardia della cache non disponibile' );
		else $r['A9'] = ( $pc['esito'] ?? '' ) === 'verde' ? self::e( self::A_POSTO, (string) ( $pc['motivo'] ?? '' ) ) : self::e( self::MANCA, (string) ( $pc['motivo'] ?? 'pagine dell app memorizzabili' ) );

		// A10 — amministratori: nessun nome di prova o iniziale.
		$admin = (array) get_users( array( 'role' => 'administrator', 'fields' => array( 'user_login' ) ) );
		$sosp  = array();
		foreach ( $admin as $a ) {
			$l = strtolower( (string) ( is_object( $a ) ? $a->user_login : $a ) );
			if ( preg_match( '/^(admin|administrator|amministratore|test|prova|demo|utente)\d*$/', $l ) ) $sosp[] = $l;
		}
		$r['A10'] = $sosp
			? self::e( self::MANCA, 'amministratori da rivedere: ' . implode( ', ', $sosp ) )
			: self::e( self::A_POSTO, count( $admin ) . ' amministratori; il doppio fattore resta a scelta del cliente' );

		// A11 — security.txt col contatto.
		$s = $get( '.well-known/security.txt' );
		if ( ! is_array( $s ) ) $r['A11'] = self::e( self::A_MANO, 'il sito non risponde a se stesso' );
		else $r['A11'] = (int) $s['codice'] === 200 && preg_match( '/^Contact:/mi', (string) $s['corpo'] ) ? self::e( self::A_POSTO, 'contatto presente' ) : self::e( self::MANCA, '/.well-known/security.txt assente o senza «Contact:»' );

		// A12 — non si prova da dentro.
		$r['A12'] = self::e( self::A_MANO, 'scheda dell hosting, prova di carico' );

		// A13 — contenuti d esempio.
		$es = array();
		$p1 = get_page_by_path( 'hello-world', 'OBJECT', 'post' ) ?: get_page_by_path( 'ciao-mondo', 'OBJECT', 'post' );
		if ( $p1 && ( $p1->post_status ?? '' ) === 'publish' ) $es[] = 'articolo «Ciao mondo!»';
		$p2 = get_page_by_path( 'sample-page', 'OBJECT', 'page' ) ?: get_page_by_path( 'pagina-di-esempio', 'OBJECT', 'page' );
		if ( $p2 && ( $p2->post_status ?? '' ) === 'publish' ) $es[] = 'pagina d esempio';
		$c1 = get_comment( 1 );
		if ( $c1 && str_ends_with( (string) ( $c1->comment_author_email ?? '' ), '@wordpress.example' ) ) $es[] = 'commento d esempio';
		$r['A13'] = $es ? self::e( self::MANCA, 'presenti: ' . implode( ', ', $es ) ) : self::e( self::A_POSTO, 'nessun contenuto d esempio' );

		// A14 — solo applicazione: motori di ricerca scoraggiati.
		if ( ! $solo_app ) $r['A14'] = self::e( self::A_POSTO, 'non richiesta: il sito non è «solo applicazione»' );
		else $r['A14'] = (string) get_option( 'blog_public', '1' ) === '0' ? self::e( self::A_POSTO, 'motori scoraggiati' ) : self::e( self::MANCA, 'Impostazioni › Lettura: motori di ricerca non scoraggiati' );

		// A15 — commenti e ping chiusi.
		$manca = array();
		if ( get_option( 'default_comment_status', 'open' ) !== 'closed' ) $manca[] = 'commenti aperti';
		if ( get_option( 'default_ping_status', 'open' ) !== 'closed' ) $manca[] = 'pingback e trackback aperti';
		$r['A15'] = $manca ? self::e( self::MANCA, implode( '; ', $manca ) . ' (Impostazioni › Discussione)' ) : self::e( self::A_POSTO, 'chiusi' );

		// A16 — solo applicazione: nessuna cache delle pagine.
		if ( ! $solo_app ) $r['A16'] = self::e( self::A_POSTO, 'non richiesta: il sito non è «solo applicazione»' );
		else {
			$cp = self::cache_pagine_attiva();
			if ( $cp === '' && is_array( $h ) ) {
				foreach ( array( 'x-cache', 'cf-cache-status', 'x-litespeed-cache', 'x-proxy-cache' ) as $k ) {
					if ( stripos( $int( $h, $k ), 'hit' ) !== false ) { $cp = 'intestazione ' . $k . ': HIT'; break; }
				}
			}
			$r['A16'] = $cp !== '' ? self::e( self::MANCA, 'cache delle pagine attiva: ' . $cp ) : self::e( self::A_POSTO, 'nessuna cache delle pagine' );
		}

		// A17 — aggiornamenti automatici: niente versioni principali da sole.
		$wc = $costante( 'WP_AUTO_UPDATE_CORE' );
		$major = $wc === true || in_array( $wc, array( 'beta', 'rc', 'development', 'branch-development' ), true )
			|| ( $wc === null && get_site_option( 'auto_update_core_major', '' ) === 'enabled' )
			|| true === apply_filters( 'allow_major_auto_core_updates', null );
		$r['A17'] = $major ? self::e( self::MANCA, 'le versioni principali di WordPress si aggiornano da sole' ) : self::e( self::A_POSTO, 'solo manutenzione e sicurezza' );

		// A18 — non si prova da dentro.
		$r['A18'] = self::e( self::A_MANO, 'impostazioni del plugin di sicurezza (Wordfence: rete in tempo reale, Central, reCAPTCHA)' );

		$conta = array( self::A_POSTO => 0, self::MANCA => 0, self::A_MANO => 0 );
		foreach ( $r as $x ) $conta[ $x['stato'] ]++;
		return array(
			'esiti'   => $r,
			'a_posto' => $conta[ self::A_POSTO ],
			'manca'   => array_keys( array_filter( $r, static fn( $x ) => $x['stato'] === self::MANCA ) ),
			'a_mano'  => array_keys( array_filter( $r, static fn( $x ) => $x['stato'] === self::A_MANO ) ),
			'solo_app'=> $solo_app,
		);
	}

	/** «Indurimento: n su 18 · mancano … · da verificare a mano …» — la stessa riga a schermo e negli appunti. */
	public static function riga( ?array $m = null ): string {
		$m = $m ?? self::fotografia();
		$t = 'Indurimento: ' . (int) $m['a_posto'] . ' su ' . count( self::regole() );
		if ( $m['manca'] ) $t .= ' · mancano ' . implode( ', ', $m['manca'] );
		if ( $m['a_mano'] ) $t .= ' · da verificare a mano ' . implode( ', ', $m['a_mano'] );
		return $t;
	}

	/* ---------------- letture del sito vero ---------------- */

	/** Una richiesta al sito stesso, senza cookie, senza seguire i rimandi. */
	public static function chiedi( string $metodo, string $url, string $corpo ): ?array {
		$a = array( 'method' => $metodo, 'timeout' => 4, 'redirection' => 0, 'cookies' => array(), 'sslverify' => false, 'headers' => array( 'User-Agent' => 'RelDoxAcademy-Indurimento' ) );
		if ( $corpo !== '' ) { $a['body'] = $corpo; $a['headers']['Content-Type'] = 'text/xml'; }
		$risp = wp_remote_request( $url, $a );
		if ( is_wp_error( $risp ) ) return null;
		$hd = wp_remote_retrieve_headers( $risp );
		return array(
			'codice'       => (int) wp_remote_retrieve_response_code( $risp ),
			'intestazioni' => is_object( $hd ) && method_exists( $hd, 'getAll' ) ? $hd->getAll() : (array) $hd,
			'corpo'        => substr( (string) wp_remote_retrieve_body( $risp ), 0, 4096 ),
		);
	}

	public static function cartelle_diagnostica(): array {
		$d = glob( trailingslashit( WP_PLUGIN_DIR ) . '*-diagnostica', GLOB_ONLYDIR );
		return array_map( 'basename', is_array( $d ) ? $d : array() );
	}

	private static function scansione_attiva(): bool {
		foreach ( array( 'wordfence/wordfence.php', 'better-wp-security/better-wp-security.php', 'sucuri-scanner/sucuri.php', 'all-in-one-wp-security-and-firewall/wp-security.php', 'ithemes-security-pro/ithemes-security-pro.php', 'wp-cerber/wp-cerber.php' ) as $p ) {
			if ( self::attivo( $p ) ) return true;
		}
		return false;
	}

	private static function cache_pagine_attiva(): string {
		foreach ( array( 'wp-super-cache/wp-cache.php' => 'WP Super Cache', 'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache', 'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache', 'wp-rocket/wp-rocket.php' => 'WP Rocket', 'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache', 'cache-enabler/cache-enabler.php' => 'Cache Enabler', 'wp-optimize/wp-optimize.php' => 'WP-Optimize' ) as $p => $nome ) {
			if ( self::attivo( $p ) ) return $nome;
		}
		return '';
	}

	private static function attivo( string $p ): bool {
		if ( ! function_exists( 'is_plugin_active' ) && defined( 'ABSPATH' ) && is_file( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return function_exists( 'is_plugin_active' ) && is_plugin_active( $p );
	}

	private static function elenco_chiuso( callable $chiusa, callable $get, array $percorsi ): array {
		$aperti = array();
		$ignoti = 0;
		foreach ( $percorsi as $p ) {
			$c = $chiusa( $get( $p ) );
			if ( $c === null ) $ignoti++;
			elseif ( ! $c ) $aperti[] = $p;
		}
		if ( $aperti ) return self::e( self::MANCA, 'raggiungibili: ' . implode( ', ', $aperti ) );
		if ( $ignoti ) return self::e( self::A_MANO, 'il sito non risponde a se stesso' );
		return self::e( self::A_POSTO, 'non raggiungibili' );
	}

	private static function e( string $stato, string $nota ): array {
		return array( 'stato' => $stato, 'nota' => $nota );
	}
}
