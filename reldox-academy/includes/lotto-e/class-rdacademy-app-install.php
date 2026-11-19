<?php
/**
 * Rdacademy_App_Install — L'ICONA SUL TELEFONO (SPEC-ICONA-WEBAPP di famiglia; analisi tecnica, allegato A: TIENI).
 * =============================================================================
 * L'icona è il LOGO DEL CLIENTE. Senza manifesto «Aggiungi a schermata Home» metterebbe un'icona generica e aprirebbe
 * la pagina nel browser, e su Android «Installa app» non comparirebbe. Qui, serviti dal prodotto sotto /academy/, senza file da
 * caricare a mano:
 *  - /academy/manifest.webmanifest — nome, apertura su /academy/ (il catalogo della persona), a tutto
 *    schermo (standalone), colori, icone;
 *  - /academy/app-icona-{180|192|512|512m}.png — le icone quadrate ricavate dal logo del cliente (Impostazioni → Branding: quello
 *    per il telefono, poi quello per tablet e computer; un SVG si salta), su fondo bianco, centrate, mai deformate; «512m» è la
 *    versione maskable con il margine di sicurezza di Android. Senza logo del cliente: il simbolo del prodotto. Le icone si
 *    preparano una volta e si conservano in uploads/rdacademy-branding/ con l'impronta del logo nel nome: un logo nuovo fa
 *    icone nuove da solo;
 *  - /academy/sw.js — un service worker minimo che non tiene niente in memoria (passa ogni richiesta alla rete): serve solo
 *    perché Chrome per Android offra «Installa».
 * Il nome sotto l'icona è il titolo della schermata d'accesso (Impostazioni → Branding) se c'è, altrimenti RelDoxAcademy.
 * Pagine pubbliche: non leggono nulla della persona.
 *
 * @package RelDoxAcademy
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_App_Install {

	const LATI   = array( '180' => 180, '192' => 192, '512' => 512, '512m' => 512 );
	const COLORE = '#0F2A44';   // 0.1.04A: il gettone --nav della famiglia (SPEC-GRAFICA v5 § 1)

	public static function init(): void {
		if ( function_exists( 'add_action' ) ) add_action( 'init', array( __CLASS__, 'servi' ), 1 );
	}

	/** La strada della richiesta sotto la radice del sito, senza query. */
	private static function strada(): string {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = '/' . trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $home !== '/' && strpos( $path, $home ) === 0 ) $path = substr( $path, strlen( $home ) );
		return '/' . ltrim( $path, '/' );
	}

	public static function servi(): void {
		$s = self::strada();
		$b = '/' . RDACADEMY_APP_SLUG . '/';
		if ( $s === $b . 'manifest.webmanifest' ) {
			self::testata( 'application/manifest+json; charset=utf-8', 3600 );
			echo wp_json_encode( self::manifesto(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			exit;
		}
		if ( $s === $b . 'sw.js' ) {
			self::testata( 'application/javascript; charset=utf-8', 0 );
			header( 'Service-Worker-Allowed: ' . $b );
			echo "/* RelDoxAcademy " . RDACADEMY_VERSION_LABEL . " — service worker minimo: niente memoria, ogni richiesta va alla rete. */\n"
				. "self.addEventListener('install', function () { self.skipWaiting(); });\n"
				. "self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });\n"
				. "self.addEventListener('fetch', function () { /* la rete risponde come sempre */ });\n";
			exit;
		}
		if ( preg_match( '#^' . preg_quote( $b, '#' ) . 'app-icona-(180|192|512|512m)\.png$#', $s, $m ) ) {
			$f = self::icona( $m[1] );
			if ( $f === '' || ! is_readable( $f ) ) { status_header( 404 ); exit; }
			self::testata( 'image/png', 86400 );
			header( 'Content-Length: ' . filesize( $f ) );
			readfile( $f );
			exit;
		}
	}

	private static function testata( string $tipo, int $secondi ): void {
		status_header( 200 );
		header( 'Content-Type: ' . $tipo );
		header( 'X-Content-Type-Options: nosniff' );
		header( $secondi > 0 ? 'Cache-Control: public, max-age=' . $secondi : 'Cache-Control: no-cache' );
	}

	/** @return array<string,string> chiave => valore, solo le chiavi chieste */
	private static function impostazioni( array $chiavi ): array {
		$out = array();
		try {
			$rows = Rdacademy_DB::get_results(
				"SELECT chiave, valore FROM `" . RDACADEMY_TABLE_PREFIX . "impostazioni` WHERE chiave IN (" . implode( ',', array_fill( 0, count( $chiavi ), '?' ) ) . ")",
				$chiavi, str_repeat( 's', count( $chiavi ) )
			);
			foreach ( (array) $rows as $r ) $out[ (string) $r['chiave'] ] = trim( (string) $r['valore'] );
		} catch ( \Throwable $e ) { /* nessuna impostazione: valgono i predefiniti */ }
		return $out;
	}

	/** Il nome sotto l'icona: il titolo della schermata d'accesso, altrimenti il nome del prodotto. */
	public static function nome(): string {
		$t = self::impostazioni( array( 'branding_login_title' ) )['branding_login_title'] ?? '';
		$t = trim( wp_strip_all_tags( (string) $t ) );
		return $t !== '' ? mb_substr( $t, 0, 40 ) : RDACADEMY_PRODUCT;
	}

	/**
	 * Il file del logo da cui nascono le icone: il logo del cliente per il telefono, poi quello per tablet e computer (un SVG
	 * si salta: le icone sono PNG), solo se sta nella cartella uploads del sito; altrimenti il simbolo del prodotto.
	 * @return array{file:string,cliente:bool}
	 */
	public static function sorgente(): array {
		$v  = self::impostazioni( array( 'branding_logo_mobile_url', 'branding_logo_url' ) );
		$up = function_exists( 'wp_upload_dir' ) ? wp_upload_dir( null, false ) : array();
		$bu = (string) ( $up['baseurl'] ?? '' );
		$bd = (string) ( $up['basedir'] ?? '' );
		foreach ( array( 'branding_logo_mobile_url', 'branding_logo_url' ) as $k ) {
			$u = (string) ( $v[ $k ] ?? '' );
			if ( $u === '' || $bu === '' || preg_match( '/\.svgz?(\?|$)/i', $u ) ) continue;
			$rel = '';
			foreach ( array( $bu, set_url_scheme( $bu, 'http' ), set_url_scheme( $bu, 'https' ) ) as $base ) {
				if ( strpos( $u, $base . '/' ) === 0 ) { $rel = substr( strtok( $u, '?' ), strlen( $base ) + 1 ); break; }
			}
			if ( $rel === '' || strpos( $rel, '..' ) !== false ) continue;
			$f = trailingslashit( $bd ) . $rel;
			if ( is_readable( $f ) && @getimagesize( $f ) ) return array( 'file' => $f, 'cliente' => true );
		}
		return array( 'file' => RDACADEMY_PLUGIN_DIR . 'public/img/logo/reldox-symbol.png', 'cliente' => false );
	}

	/** L'impronta della sorgente: cambia col logo, così cambiano anche gli indirizzi delle icone. */
	public static function impronta(): string {
		$s = self::sorgente();
		return substr( md5( $s['file'] . '|' . (string) @filemtime( $s['file'] ) . '|' . RDACADEMY_VERSION . '|r3' ), 0, 10 );
	}

	public static function url_icona( string $lato ): string {
		return home_url( '/' . RDACADEMY_APP_SLUG . '/app-icona-' . $lato . '.png' ) . '?v=' . self::impronta();
	}

	public static function manifesto(): array {
		$n = self::nome();
		$icone = array();
		foreach ( array( '192', '512' ) as $l ) {
			$icone[] = array( 'src' => self::url_icona( $l ), 'sizes' => $l . 'x' . $l, 'type' => 'image/png', 'purpose' => 'any' );
		}
		$icone[] = array( 'src' => self::url_icona( '512m' ), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' );
		return array(
			'id'               => '/' . RDACADEMY_APP_SLUG . '/',
			'name'             => $n,
			'short_name'       => mb_substr( $n, 0, 20 ),
			'description'      => RDACADEMY_PRODUCT . ' — ' . RDACADEMY_VENDOR,
			'lang'             => 'it',
			'start_url'        => home_url( '/' . RDACADEMY_APP_SLUG . '/' ),
			'scope'            => home_url( '/' . RDACADEMY_APP_SLUG . '/' ),
			'display'          => 'standalone',
			'orientation'      => 'portrait',
			'background_color' => '#FFFFFF',
			'theme_color'      => self::COLORE,
			'icons'            => $icone,
		);
	}

	/**
	 * L'icona quadrata di quel lato: preparata una volta (uploads/rdacademy-branding/app-icona-<lato>-<impronta>.png), poi
	 * letta dal disco. Logo centrato su bianco, proporzioni intatte; margine 10% (maskable 20%, la zona sicura di Android).
	 * Senza GD o senza cartella scrivibile: il file della sorgente così com'è (il telefono lo rimpicciolisce).
	 */
	public static function icona( string $lato ): string {
		if ( ! isset( self::LATI[ $lato ] ) ) return '';
		$src = self::sorgente();
		$up  = function_exists( 'wp_upload_dir' ) ? wp_upload_dir( null, false ) : array();
		$dir = ! empty( $up['basedir'] ) ? trailingslashit( $up['basedir'] ) . 'rdacademy-branding' : '';
		if ( $dir === '' || ! function_exists( 'imagecreatetruecolor' ) ) return $src['file'];
		$dest = $dir . '/app-icona-' . $lato . '-' . self::impronta() . '.png';
		if ( is_readable( $dest ) ) return $dest;
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) return $src['file'];
		$dati = @file_get_contents( $src['file'] );
		$img  = $dati !== false ? @imagecreatefromstring( $dati ) : false;
		if ( ! $img ) return $src['file'];
		$L   = self::LATI[ $lato ];
		$out = imagecreatetruecolor( $L, $L );
		imagefill( $out, 0, 0, imagecolorallocate( $out, 255, 255, 255 ) );
		// il bordo trasparente del logo non conta: si misura il disegno vero (un logo quadrato con molto margine resta leggibile)
		list( $sx, $sy, $w, $h ) = self::riquadro( $img );
		$area = (int) round( $L * ( $lato === '512m' ? 0.60 : 0.80 ) );
		$k  = min( $area / max( 1, $w ), $area / max( 1, $h ) );
		$nw = max( 1, (int) round( $w * $k ) ); $nh = max( 1, (int) round( $h * $k ) );
		imagealphablending( $out, true );
		imagecopyresampled( $out, $img, (int) ( ( $L - $nw ) / 2 ), (int) ( ( $L - $nh ) / 2 ), $sx, $sy, $nw, $nh, $w, $h );
		$tmp = $dest . '.' . wp_generate_password( 6, false ) . '.tmp';
		$ok  = @imagepng( $out, $tmp, 9 ) && @rename( $tmp, $dest );
		imagedestroy( $out ); imagedestroy( $img );
		if ( ! $ok ) { @unlink( $tmp ); return $src['file']; }
		foreach ( (array) glob( $dir . '/app-icona-' . $lato . '-*.png' ) as $vecchia ) {   // le icone dei loghi di prima
			if ( $vecchia !== $dest ) @unlink( $vecchia );
		}
		return $dest;
	}

	/**
	 * Il riquadro del disegno: il logo senza i bordi trasparenti o bianchi (il fondo dell'icona è bianco: un margine bianco
	 * del file non serve). Un logo senza margini resta intero.
	 * @return array{0:int,1:int,2:int,3:int} x, y, larghezza, altezza
	 */
	private static function riquadro( $img ): array {
		$w = imagesx( $img ); $h = imagesy( $img );
		$passo = max( 1, (int) floor( max( $w, $h ) / 400 ) );   // su un logo grande si guarda un pixel ogni tanto
		$x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;
		for ( $y = 0; $y < $h; $y += $passo ) {
			for ( $x = 0; $x < $w; $x += $passo ) {
				$c = imagecolorat( $img, $x, $y );
				if ( ! imageistruecolor( $img ) ) { $q = imagecolorsforindex( $img, $c ); $c = ( $q['alpha'] << 24 ) | ( $q['red'] << 16 ) | ( $q['green'] << 8 ) | $q['blue']; }
				$bianco = ( ( $c >> 16 ) & 0xFF ) > 245 && ( ( $c >> 8 ) & 0xFF ) > 245 && ( $c & 0xFF ) > 245;
				if ( ( ( $c >> 24 ) & 0x7F ) < 120 && ! $bianco ) {
					if ( $x < $x0 ) $x0 = $x; if ( $x > $x1 ) $x1 = $x; if ( $y < $y0 ) $y0 = $y; if ( $y > $y1 ) $y1 = $y;
				}
			}
		}
		if ( $x1 < 0 ) return array( 0, 0, $w, $h );
		$x0 = max( 0, $x0 - $passo ); $y0 = max( 0, $y0 - $passo );
		$x1 = min( $w - 1, $x1 + $passo ); $y1 = min( $h - 1, $y1 + $passo );
		return array( $x0, $y0, $x1 - $x0 + 1, $y1 - $y0 + 1 );
	}

	/** I tag della testata delle pagine dell'app. */
	public static function testata_html(): string {
		$n = self::nome();
		return '<link rel="manifest" href="' . esc_url( home_url( '/' . RDACADEMY_APP_SLUG . '/manifest.webmanifest' ) ) . '">' . "\n"
			. '    <link rel="apple-touch-icon" href="' . esc_url( self::url_icona( '180' ) ) . '">' . "\n"
			. '    <meta name="theme-color" content="' . esc_attr( self::COLORE ) . '">' . "\n"
			. '    <meta name="mobile-web-app-capable" content="yes">' . "\n"
			. '    <meta name="apple-mobile-web-app-capable" content="yes">' . "\n"
			. '    <meta name="apple-mobile-web-app-title" content="' . esc_attr( $n ) . '">' . "\n"
			. '    <meta name="application-name" content="' . esc_attr( $n ) . '">' . "\n";
	}

	/**
	 * Il pulsante «📲 Metti l'icona sul telefono» col riquadro della spiegazione (catalogo della persona). La riga sparisce se
	 * l'app è già aperta dall'icona (rdacademy-icona.js). $solo_installa = il solo pulsante «Installa»:
	 * compare solo quando il telefono offre l'installazione diretta.
	 */
	public static function html_pulsante( string $lingua, bool $solo_installa = false ): string {
		$t = static function ( string $k ) use ( $lingua ): string { return Rdacademy_Lingua::t( $k, $lingua ); };
		$js = '<script src="' . esc_url( RDACADEMY_PLUGIN_URL . 'public/js/rdacademy-icona.js' ) . '?v=' . esc_attr( RDACADEMY_VERSION ) . '" defer'
			. ' data-sw="' . esc_attr( home_url( '/' . RDACADEMY_APP_SLUG . '/sw.js' ) ) . '" data-scope="' . esc_attr( home_url( '/' . RDACADEMY_APP_SLUG . '/' ) ) . '"></script>';
		$dati = ' data-fatto="' . esc_attr( $t( 'icona.fatto' ) ) . '" data-no="' . esc_attr( $t( 'icona.no' ) ) . '"';
		$ico = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M12 7v7M9 11l3 3 3-3M10 18.5h4"/></svg>';
		if ( $solo_installa ) {
			return '<div class="rdacademy-icona rdacademy-icona-solo" id="rdacademy-icona" data-solo-installa="1"' . $dati . ' hidden>'
				. '<button type="button" class="rdacademy-icona-btn" id="rdacademy-icona-btn">' . $ico . '<span>' . esc_html( $t( 'icona.installa' ) ) . '</span></button>'
				. '<p class="rdacademy-icona-esito" id="rdacademy-icona-esito" role="status" aria-live="polite"></p></div>' . $js;
		}
		return '<div class="rdacademy-icona" id="rdacademy-icona"' . $dati . '>'
			. '<button type="button" class="rdacademy-icona-btn" id="rdacademy-icona-btn" aria-expanded="false" aria-controls="rdacademy-icona-box">' . $ico . '<span>' . esc_html( $t( 'icona.btn' ) ) . '</span></button>'
			. '<div class="rdacademy-icona-box" id="rdacademy-icona-box" hidden>'
			. '<div class="rdacademy-icona-box-testa"><b>' . esc_html( $t( 'icona.btn' ) ) . '</b>'
			. '<button type="button" class="rdacademy-icona-x" id="rdacademy-icona-x" aria-label="' . esc_attr( $t( 'icona.chiudi' ) ) . '">' . rdacademy_icona( 'chiudi' ) . '</button></div>'
			. '<div class="rdacademy-icona-corpo">'
			. '<svg class="rdacademy-icona-fig" viewBox="0 0 120 150" aria-hidden="true" focusable="false"><rect x="22" y="8" width="76" height="134" rx="12" fill="#fff" stroke="#0F2A44" stroke-width="5"/><rect x="50" y="17" width="20" height="4" rx="2" fill="#CBD5E1"/><rect x="36" y="34" width="20" height="20" rx="4" fill="#0C6E86"/><rect x="64" y="34" width="20" height="20" rx="4" fill="#E8B23A"/><rect x="36" y="62" width="20" height="20" rx="4" fill="#CBD5E1"/><rect x="64" y="62" width="20" height="20" rx="4" fill="#CBD5E1"/><path d="M60 94v24M50 108l10 10 10-10" fill="none" stroke="#0C6E86" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			. '<div class="rdacademy-icona-testi">'
			. '<p class="rdacademy-icona-p" data-per="ios">' . esc_html( $t( 'icona.ios' ) ) . '</p>'
			. '<p class="rdacademy-icona-p" data-per="ios-altro">' . esc_html( $t( 'icona.ios_safari' ) ) . '</p>'
			. '<p class="rdacademy-icona-p" data-per="android">' . esc_html( $t( 'icona.and' ) ) . '</p>'
			. '<p class="rdacademy-icona-p" data-per="pc">' . esc_html( $t( 'icona.pc' ) ) . '</p>'
			. '<p class="rdacademy-icona-dopo">' . esc_html( $t( 'icona.dopo' ) ) . '</p>'
			. '</div></div></div>'
			. '<p class="rdacademy-icona-esito" id="rdacademy-icona-esito" role="status" aria-live="polite"></p>'
			. '</div>' . $js;
	}
}
