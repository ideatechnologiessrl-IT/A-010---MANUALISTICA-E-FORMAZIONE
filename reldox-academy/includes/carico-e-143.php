<?php
/**
 * 1.4.3B — RF-BUG-028 (owner 16/10 11:17-11:30): «dobbiamo essere indipendenti dalla cache», «la soluzione più sicura».
 *
 * IL DIFETTO (nel prodotto di partenza). I rinvii d approdo partivano in template_redirect PRIMA del router, l unico che
 * mandava no-store: il 302 usciva senza intestazioni anti-cache, la cache dell hosting (per lei le sessioni dell app sono
 * visitatori anonimi) lo teneva e lo dava a tutti → «Pagina riservata». Con una cache che ignora anche le intestazioni,
 * peggio: la pagina di un altro.
 *
 * LE DIFESE, indipendenti l una dall altra:
 *  1. NESSUN 30x DAL SERVER sulle pagine dell app. /academy/ è una PORTA NEUTRA, uguale per tutti e senza dati di nessuno: il
 *     browser chiede con una POST (che nessuna cache tiene) dove deve andare quel profilo e ci va. Il pannello di ogni
 *     persona sta su /academy/?_rv=<segno della sessione>: un indirizzo per sessione, che nessuna cache può dare a un
 *     altro. Ogni altro rinvio è una pagina 200 neutra che il browser segue.
 *  2. ANTI-CACHE PER PRIMO: send_headers e template_redirect priorità 0, su ogni pagina e ogni AJAX dell app (e su ogni rinvio).
 *  3. IL COOKIE CHE LE CACHE WORDPRESS RICONOSCONO: wordpress_logged_in_rdacademy, con la sessione (Rdacademy_Auth::set_cookie).
 *  4. Vary: Cookie su ogni risposta dell app.
 *  5. LA GUARDIA DELLA PAGINA: ogni pagina porta il segno della sessione con cui è stata disegnata; se il cookie del browser
 *     dice un altra sessione (una copia rigirata da una cache), la pagina si nasconde e si ricarica con un indirizzo suo.
 *     E nessuna pagina GET scrive cookie (Rdacademy_Auth::puo_scrivere_cookie): il rinnovo lo fa la POST che ogni pagina manda.
 *  6. IL CONTROLLO NELLO STATO DEL SISTEMA: due letture di /academy/ e di una pagina dell app, senza cookie: se tornano uguali
 *     (X-Rdacademy-Resa) o senza no-store, rosso col motivo. 1.8.0B (RF-BUG-035): la sola Vary: Cookie mancante è arancio.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Cache_Guardia {

	private static bool $fatte = false;
	private static ?string $resa = null;

	/** È una richiesta dell app? Pagina /academy/… o AJAX del prodotto. */
	public static function e_app(): bool {
		if ( wp_doing_ajax() ) {
			$a = (string) ( $_REQUEST['action'] ?? '' );
			return $a === 'rdacademy' || $a === 'rdacademy_approdo';
		}
		return (string) get_query_var( 'rdacademy_page' ) !== '';
	}

	/** Difese 2 e 4: le intestazioni anti-cache, una volta per richiesta e prima di ogni altra cosa. */
	public static function intestazioni(): void {
		foreach ( array( 'DONOTCACHEPAGE', 'DONOTCACHEOBJECT', 'DONOTCACHEDB' ) as $c ) {
			if ( ! defined( $c ) ) define( $c, true );
		}
		if ( headers_sent() ) return;
		if ( ! self::$fatte ) nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		header( 'X-Accel-Expires: 0' );
		header( 'Surrogate-Control: no-store' );
		if ( ! self::$fatte ) header( 'Vary: Cookie', false );   // 1.8.0B — RF-BUG-035: si aggiunge alle Vary del server, non le sostituisce
		header( 'X-Rdacademy-Resa: ' . self::resa() );   // unica per risposta: due letture uguali = una copia (difesa 6)
		self::$fatte = true;
	}

	public static function resa(): string {
		return self::$resa ?? ( self::$resa = bin2hex( random_bytes( 8 ) ) );
	}

	/** L indirizzo della pagina d accesso (con «la sessione è scaduta» se la richiesta portava un gettone finito). */
	public static function url_accesso(): string {
		$u = home_url( '/' . RDACADEMY_APP_SLUG . '/login/' );
		return Rdacademy_Auth::sessione_finita() ? add_query_arg( 'scaduta', '1', $u ) : $u;
	}

	/** Dove approda chi è collegato (decisa qui e non con un 30x). */
	public static function approdo( array $u ): string {
		$app = '/' . RDACADEMY_APP_SLUG . '/';
		// 1.13.0B (SV-43) — l Integratore parte dalla pagina Novità (è la sua pagina di partenza).
		if ( class_exists( 'Rdacademy_Router' ) && Rdacademy_Router::e_integratore( $u ) ) return home_url( $app . 'novita/' );
		return add_query_arg( '_rv', Rdacademy_Auth::vista(), home_url( $app ) );
	}

	/** Il segno con cui si disegna questa pagina: la sessione, «-» per nessuno, «x» se il server ha visto un gettone finito. */
	public static function segno_pagina(): string {
		if ( Rdacademy_Auth::get_current_user() ) return Rdacademy_Auth::vista();
		return Rdacademy_Auth::sessione_finita() ? 'x' : '-';
	}

	/**
	 * Difesa 5 — LA GUARDIA, il primo script del <head>. Se il segno del cookie non è quello della pagina, la pagina è la copia
	 * di un altra sessione rigirata da una cache: si nasconde e si ricarica con un indirizzo suo (_rv = segno, _rz = istante),
	 * al massimo due volte. Se coincide, toglie _rv/_rz dalla barra degli indirizzi.
	 */
	public static function guardia_js( string $segno ): string {
		$m = wp_json_encode( $segno );
		$c = Rdacademy_Auth::COOKIE_VISTA;
		return '(function(){try{var m=' . $m . ',c=(document.cookie.match(/(?:^|; )' . $c . '=([^;]*)/)||[])[1]||"-",u=new URL(location.href),k="rdacademy_rv",n=0;'
			. 'try{n=+(sessionStorage.getItem(k)||0)}catch(e){}'
			. 'if(m!=="x"&&c!==m&&n<2){try{sessionStorage.setItem(k,n+1)}catch(e){}document.documentElement.style.visibility="hidden";'
			. 'u.searchParams.set("_rv",c);u.searchParams.set("_rz",Date.now().toString(36));window.RDACADEMY_RICARICA=1;location.replace(u.toString());return;}'
			. 'try{sessionStorage.removeItem(k)}catch(e){}'
			. 'if(u.searchParams.has("_rv")||u.searchParams.has("_rz")){u.searchParams.delete("_rv");u.searchParams.delete("_rz");history.replaceState(history.state,"",u.pathname+u.search+u.hash);}'
			. '}catch(e){}})();';
	}

	/** La guardia e la POST di rinnovo, per il <head> del layout. */
	public static function testa_html(): string {
		$aj = wp_json_encode( admin_url( 'admin-ajax.php' ) );
		return '<script>' . self::guardia_js( self::segno_pagina() ) . '</script>' . "\n"
			. '<script>window.addEventListener("load",function(){if(window.RDACADEMY_RICARICA)return;try{fetch(' . $aj . ',{method:"POST",credentials:"same-origin",cache:"no-store",'
			. 'headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=rdacademy_approdo&rinnova=1"}).then(function(r){return r.text();}).catch(function(){});}catch(e){}});</script>' . "\n";
	}

	/** Una pagina 200 minima, neutra (nessun dato di nessuno), con le intestazioni anti-cache. */
	private static function pagina( string $testa, string $corpo ): void {
		self::intestazioni();
		status_header( 200 );
		header( 'Content-Type: text/html; charset=UTF-8' );
		$l = class_exists( 'Rdacademy_Lingua' ) ? Rdacademy_Lingua::corrente() : 'it';
		echo '<!DOCTYPE html><html lang="' . esc_attr( $l ) . '"><head><meta charset="UTF-8">'
			. '<meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="robots" content="noindex, nofollow">'
			. '<title>' . esc_html( RDACADEMY_PRODUCT ) . '</title>' . $testa
			. '<style>html,body{margin:0;min-height:100%;background:#f5f6f8;color:#1f2937;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
			. 'main{min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;padding:24px 16px;box-sizing:border-box;text-align:center}'
			. '.rx-giro{width:34px;height:34px;border-radius:50%;border:3px solid #d5dae1;border-top-color:#2d4a7c;animation:rxg .9s linear infinite}'
			. '@keyframes rxg{to{transform:rotate(360deg)}}@media (prefers-reduced-motion:reduce){.rx-giro{animation:none}}'
			. 'p{margin:0;font-size:17px}a,button{font:inherit;font-size:16px;min-height:44px;padding:10px 20px;border-radius:8px;border:0;background:#2d4a7c;color:#fff;text-decoration:none;cursor:pointer;display:inline-flex;align-items:center}'
			. '[hidden]{display:none!important}</style></head><body><main>' . $corpo . '</main></body></html>';
		exit;
	}

	/** Un rinvio fatto dal browser: 200, neutro, con la guardia (una copia rigirata si ricarica col suo indirizzo). */
	public static function rinvio( string $url ): void {
		$t  = wp_json_encode( $url );
		$tx = static function ( string $k ): string { return class_exists( 'Rdacademy_Lingua' ) ? Rdacademy_Lingua::t( $k ) : $k; };
		self::pagina(
			'<script>' . self::guardia_js( self::segno_pagina() ) . 'if(!window.RDACADEMY_RICARICA){var t=new URL(' . $t . ',location.href);if(!t.hash)t.hash=location.hash;location.replace(t.toString());}</script>'
				. '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $url ) . '"></noscript>',
			'<div class="rx-giro" aria-hidden="true"></div><p role="status">' . esc_html( $tx( 'porta.apertura' ) ) . '</p>'
				. '<a href="' . esc_url( $url ) . '">' . esc_html( $tx( 'porta.continua' ) ) . '</a>'
		);
	}

	/** Difesa 1 — LA PORTA di /academy/: uguale per tutti; chiede con una POST dove andare e ci va. */
	public static function porta(): void {
		$aj  = wp_json_encode( admin_url( 'admin-ajax.php' ) );
		$acc = home_url( '/' . RDACADEMY_APP_SLUG . '/login/' );
		$tx  = static function ( string $k ): string { return class_exists( 'Rdacademy_Lingua' ) ? Rdacademy_Lingua::t( $k ) : $k; };
		self::pagina(
			'<script>(function(){function vai(){var e=document.getElementById("rx-porta-errore");if(e)e.hidden=true;'
				. 'fetch(' . $aj . ',{method:"POST",credentials:"same-origin",cache:"no-store",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=rdacademy_approdo"})'
				. '.then(function(r){return r.json();}).then(function(j){if(!(j&&j.success&&j.data&&j.data.url))throw 0;'
				// come un rinvio del server: il frammento (#…) resta; sulla stessa pagina restano anche gli altri parametri
				. 'var t=new URL(j.data.url,location.href),c=new URL(location.href);if(t.pathname===c.pathname){c.searchParams.forEach(function(v,k){if(k!=="_rv"&&k!=="_rz"&&!t.searchParams.has(k))t.searchParams.set(k,v);});}'
				. 'if(!t.hash)t.hash=c.hash;location.replace(t.toString());})'
				. '.catch(function(){var e=document.getElementById("rx-porta-errore");if(e)e.hidden=false;});}'
				. 'window.rdacademyPorta=vai;vai();})();</script>'
				. '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $acc ) . '"></noscript>',
			'<div class="rx-giro" aria-hidden="true"></div><p role="status">' . esc_html( $tx( 'porta.apertura' ) ) . '</p>'
				. '<div id="rx-porta-errore" hidden><p>' . esc_html( $tx( 'porta.errore' ) ) . '</p><p style="margin-top:12px">'
				. '<button type="button" onclick="window.rdacademyPorta()">' . esc_html( $tx( 'porta.riprova' ) ) . '</button></p></div>'
		);
	}

	/** La POST della porta e del rinnovo: dove va questo profilo; rimette i cookie (mai su una GET). */
	public static function ajax_approdo(): void {
		self::intestazioni();
		if ( ! Rdacademy_Auth::puo_scrivere_cookie() ) wp_send_json_error( 'POST', 405 );
		$o = (string) ( $_SERVER['HTTP_ORIGIN'] ?? '' );
		if ( $o !== '' && wp_parse_url( $o, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) wp_send_json_error( 'origine', 403 );
		$u = Rdacademy_Auth::get_current_user();   // in POST: rinnova la sessione se tocca, pulisce i cookie di una sessione finita
		Rdacademy_Auth::aggiorna_cookie();
		wp_send_json_success( array( 'url' => $u ? self::approdo( $u ) : self::url_accesso() ) );
	}

	/** Le decisioni d approdo, PRIMA del router (priorità 1). */
	public static function instrada(): void {
		$page = (string) get_query_var( 'rdacademy_page' );
		if ( $page === '' ) return;
		self::intestazioni();
		$u = Rdacademy_Auth::get_current_user();
		if ( $page === 'dashboard' ) {
			$rv = (string) ( $_GET['_rv'] ?? '' );
			if ( $u && $rv !== '' && hash_equals( Rdacademy_Auth::vista(), $rv ) ) {
				return;   // il pannello della persona, sul SUO indirizzo
			}
			self::porta();
		}
		$livello = Rdacademy_Router::page_level( $page );
		if ( ! $u && $livello !== 'public' ) self::rinvio( self::url_accesso() );
		if ( $u && $page === 'login' ) self::rinvio( home_url( '/' . RDACADEMY_APP_SLUG . '/' ) );
		// 1.13.0B (SV-43) — punto 1 anche qui, prima di ogni altra cosa della pagina: fuori dalla lista chiusa, la pagina Novità.
		if ( $u && class_exists( 'Rdacademy_Router' ) && Rdacademy_Router::e_integratore( $u ) && ! in_array( $page, Rdacademy_Router::PAGINE_INTEGRATORE, true ) && $page !== 'manuale-pdf' ) {
			self::rinvio( home_url( '/' . RDACADEMY_APP_SLUG . '/novita/' ) );
		}
	}

	/**
	 * Difesa 6 — IL CONTROLLO DELLO STATO DEL SISTEMA. Due letture senza cookie di /academy/ e di una pagina dell app (che per chi
	 * non è collegato è un rinvio): verde se ogni risposta è 200 (nessun 30x), porta no-store e Vary: Cookie e le due letture
	 * sono diverse (X-Rdacademy-Resa); rosso col motivo altrimenti; «non misurabile» se il sito non si raggiunge da sé.
	 * @return array{esito:string,motivo:string}
	 */
	public static function prova(): array {
		static $r = null;
		if ( $r !== null ) return $r;
		$base = (string) apply_filters( 'rdacademy_cache_prova_base', home_url( '/' . RDACADEMY_APP_SLUG . '/' ) );
		$prob = array();
		foreach ( array( $base, $base . 'preferenze/' ) as $url ) {   // una pagina auth del Setup: per chi non è collegato, un rinvio
			$rese = array();
			for ( $i = 0; $i < 2; $i++ ) {
				$x = wp_remote_get( $url, array( 'timeout' => 6, 'redirection' => 0, 'cookies' => array(), 'headers' => array( 'Cache-Control' => '' ) ) );
				if ( is_wp_error( $x ) ) {
					return $r = array( 'esito' => 'non misurabile', 'motivo' => 'il sito non risponde a se stesso (' . $x->get_error_message() . ')' );
				}
				$cod = (int) wp_remote_retrieve_response_code( $x );
				$cc  = strtolower( (string) wp_remote_retrieve_header( $x, 'cache-control' ) );
				$va  = strtolower( (string) wp_remote_retrieve_header( $x, 'vary' ) );
				$hit = strtolower( (string) wp_remote_retrieve_header( $x, 'x-cache' ) . ' ' . (string) wp_remote_retrieve_header( $x, 'x-litespeed-cache' ) . ' ' . (string) wp_remote_retrieve_header( $x, 'cf-cache-status' ) );
				$age = (int) wp_remote_retrieve_header( $x, 'age' );
				$rese[] = (string) wp_remote_retrieve_header( $x, 'x-rdacademy-resa' );
				$nome = ( $url === $base ) ? '/' . RDACADEMY_APP_SLUG . '/' : '/' . RDACADEMY_APP_SLUG . '/preferenze/';
				if ( $cod >= 300 && $cod < 400 ) $prob[] = $nome . ' risponde con un rinvio ' . $cod;
				elseif ( $cod !== 200 ) $prob[] = $nome . ' risponde ' . $cod;
				if ( strpos( $cc, 'no-store' ) === false ) $prob[] = $nome . ' arriva senza no-store';
				if ( strpos( $va, 'cookie' ) === false ) $prob[] = $nome . ' arriva senza Vary: Cookie';
				if ( strpos( $hit, 'hit' ) !== false || $age > 0 ) $prob[] = $nome . ' arriva dalla cache (HIT o Age)';
			}
			if ( $rese[0] === '' || $rese[0] === $rese[1] ) $prob[] = ( $url === $base ? '/' . RDACADEMY_APP_SLUG . '/' : '/' . RDACADEMY_APP_SLUG . '/preferenze/' ) . ' letta due volte torna uguale: è una copia della cache';
		}
		$prob = array_values( array_unique( $prob ) );
		/* La sola Vary
		   mancante non espone nessuno: la toglie il server davanti al sito, le difese 1, 2, 5 restano. Arancio col motivo, mai
		   «svuotare la cache» (l app non dipende dalla cache dell hosting). Rosso solo se una copia arriva davvero. */
		$senza_vary = array_values( array_filter( $prob, static fn( $x ) => strpos( $x, 'senza Vary: Cookie' ) !== false ) );
		$gravi      = array_values( array_diff( $prob, $senza_vary ) );
		if ( $gravi ) {
			return $r = array( 'esito' => 'rosso', 'motivo' => implode( ' · ', $prob ) . ' — una copia delle pagine dell app può arrivare a un altra persona: mandare questa riga a Idea Technologies' );
		}
		if ( $senza_vary ) {
			return $r = array( 'esito' => 'arancio', 'motivo' => 'nessuna copia: due letture diverse, no-store, nessun rinvio · il server davanti al sito toglie Vary: Cookie (' . implode( ', ', array_map( static fn( $x ) => strtok( $x, ' ' ), $senza_vary ) ) . '): le altre difese restano attive, nessuna azione richiesta' );
		}
		return $r = array( 'esito' => 'verde', 'motivo' => 'due letture diverse, no-store e Vary: Cookie, nessun rinvio dal server' );
	}
}

/* Difese 2 e 4 per prime: prima del corpo e prima di ogni rinvio. */
add_action( 'send_headers', static function ( $wp ): void {
	if ( is_object( $wp ) && ! empty( $wp->query_vars['rdacademy_page'] ) ) Rdacademy_Cache_Guardia::intestazioni();
}, 0 );
add_action( 'template_redirect', static function (): void {
	if ( Rdacademy_Cache_Guardia::e_app() ) Rdacademy_Cache_Guardia::intestazioni();
}, 0 );
add_action( 'template_redirect', array( 'Rdacademy_Cache_Guardia', 'instrada' ), 1 );
add_filter( 'wp_redirect', static function ( $loc ) {
	if ( Rdacademy_Cache_Guardia::e_app() ) Rdacademy_Cache_Guardia::intestazioni();
	return $loc;
}, 0 );
foreach ( array( 'wp_ajax_rdacademy', 'wp_ajax_nopriv_rdacademy' ) as $rdacademy_h ) {
	add_action( $rdacademy_h, array( 'Rdacademy_Cache_Guardia', 'intestazioni' ), 0 );
}
foreach ( array( 'wp_ajax_rdacademy_approdo', 'wp_ajax_nopriv_rdacademy_approdo' ) as $rdacademy_h ) {
	add_action( $rdacademy_h, array( 'Rdacademy_Cache_Guardia', 'ajax_approdo' ) );
}
unset( $rdacademy_h );

/* Il pannello nel menu porta il suo indirizzo (/academy/?_rv=…): un passaggio in meno dalla porta. */
add_filter( 'rdacademy_nav_items', static function ( array $items ): array {
	if ( isset( $items['dashboard']['url'] ) && Rdacademy_Auth::get_current_user() ) {
		$items['dashboard']['url'] = add_query_arg( '_rv', Rdacademy_Auth::vista(), trailingslashit( (string) $items['dashboard']['url'] ) );
	}
	return $items;
}, 99 );
