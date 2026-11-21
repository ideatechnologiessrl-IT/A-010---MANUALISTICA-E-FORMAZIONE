<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-license-pdf.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Rdacademy_License_Pdf — IL CONTRATTO DI LICENZA IN PDF.
 * =============================================================================
 * 1.3.0B (PROPOSTA-EULA-v2, owner 11/10 18:39 punto 4): scrittore minimo nel plugin, nessuna libreria esterna, PDF 1.4, A4
 * (595 × 842 pt), Helvetica e Helvetica-Bold in WinAnsiEncoding (il testo si converte in Windows-1252), a capo con le larghezze
 * vere dei caratteri (AFM), pagine automatiche, blocco firma che non si spezza.
 *
 * 1.3.1B (SPEC-FIRMA-LICENZA-RELDOX-v1 §§ 4, 7, 9 — owner 12/10 10:45-11:01):
 *  - TESTATA della prima pagina coi due loghi alti 14 mm: Idea Technologies (licenziante) a sinistra, il prodotto a destra. Il logo
 *    di Idea è un file del pacchetto (public/img/logo/idea-technologies.png o .jpg): se manca il contratto NON si genera (nessun
 *    segnaposto) — Rdacademy_License::genera() lo dice col motivo scritto;
 *  - immagini: PNG (8 bit, colori 0 · 2 · 3 · 4 · 6, non interlacciato) → RGB/grigio FlateDecode + maschera /SMask per la
 *    trasparenza; JPEG → DCTDecode così com è;
 *  - BLOCCO FIRMA a due spazi: (1) sottoscrizione del contratto · (2) approvazione specifica degli articoli 3, 8, 10, 12 e 14;
 *  - DUE CAMPI FIRMA DIGITALE vuoti (AcroForm, /FT /Sig), Firma_Contratto e Firma_Approvazione_Specifica, esattamente sopra le due
 *    righe «Firma»: bordo sottile, nessun testo, non stampati (chi stampa non li vede);
 *  - PIEDE di ogni pagina: «Codice di controllo 843e 4569 f9b5 · <dominio> · Pagina n di N»;
 *  - in calce all ultima pagina la NOTA sul codice di controllo (per esteso, una volta sola) e le ISTRUZIONI DI FIRMA in due righe:
 *    testi `licenza.pdf_nota` e `licenza.pdf_istruzioni` della Tabella «Testi delle notifiche» (R-69);
 *  - trailer /ID = due copie dell identificativo del contratto generato (32 esadecimali): serve a riconoscere il file quando il
 *    programma di firma lo riscrive (§ 5 A);
 *  - nome del file: contratto-licenza-<prodotto>-<versione testo>-<AAAAMMGG>.pdf.
 *
 * @package RelDox CRD
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_License_Pdf {

	const W = 595.28; const H = 841.89; const MX = 56.0; const TOP = 64.0; const BOTTOM = 70.0;
	const MM = 2.834645669;
	const LOGO_ALTO_MM = 14.0;
	const LOGO_IDEA = array( 'public/img/logo/idea-technologies.png', 'public/img/logo/idea-technologies.jpg' );
	const LOGO_PRODOTTO = 'public/img/logo/reldox-academy-lockup.png';

	/** Larghezze AFM (1/1000 em) per i caratteri 32…126; gli altri prendono quella della lettera base o 556. */
	const LARGHE = array(
		'Helvetica' => array( 278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584 ),
		'Helvetica-Bold' => array( 278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584 ),
	);
	const ALTRE = array( 0x80 => 556, 0x85 => 1000, 0x91 => 222, 0x92 => 222, 0x93 => 333, 0x94 => 333, 0x96 => 556, 0x97 => 1000, 0xAB => 556, 0xBB => 556, 0xB0 => 400, 0xA0 => 278 );

	private $pagine = array(); private $cur = ''; private $y = 0.0; private $piede = '';
	private $immagini = array();     // nome => [w, h, colori, dati, alfa|null, filtro]
	private $campi_firma = array();  // [nome, etichetta, pagina (0…), rect]

	/** UTF-8 → Windows-1252 (i caratteri che non ci sono diventano «?»). */
	public static function cp( string $s ): string {
		$s = str_replace( array( "\r\n", "\r", "\t" ), array( "\n", "\n", ' ' ), $s );
		$o = function_exists( 'mb_convert_encoding' ) ? @mb_convert_encoding( $s, 'Windows-1252', 'UTF-8' ) : @iconv( 'UTF-8', 'Windows-1252//TRANSLIT', $s );
		return is_string( $o ) ? $o : (string) preg_replace( '/[^\x20-\x7E\n]/', '?', $s );
	}

	private static function larghezza( string $s, string $font, float $size ): float {
		$t = self::LARGHE[ $font ]; $w = 0;
		$n = strlen( $s );
		for ( $i = 0; $i < $n; $i++ ) {
			$c = ord( $s[ $i ] );
			if ( $c >= 32 && $c <= 126 ) $w += $t[ $c - 32 ];
			elseif ( isset( self::ALTRE[ $c ] ) ) $w += self::ALTRE[ $c ];
			elseif ( $c >= 0xC0 ) $w += ( $c >= 0xE0 ? ( in_array( $c, array( 0xEC, 0xED, 0xEE, 0xEF ), true ) ? 278 : 556 ) : 667 );
			else $w += 556;
		}
		return $w * $size / 1000;
	}

	private static function esc( string $s ): string {
		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $s );
	}

	/** Spezza un paragrafo (già in cp1252) in righe larghe al massimo $max punti. */
	private static function righe( string $s, string $font, float $size, float $max ): array {
		$out = array();
		foreach ( explode( "\n", $s ) as $par ) {
			$riga = '';
			foreach ( preg_split( '/ +/', trim( $par ) ) as $p ) {
				$prova = $riga === '' ? $p : $riga . ' ' . $p;
				if ( $riga !== '' && self::larghezza( $prova, $font, $size ) > $max ) { $out[] = $riga; $riga = $p; }
				else $riga = $prova;
				while ( self::larghezza( $riga, $font, $size ) > $max && strlen( $riga ) > 1 ) {   // parola più lunga della riga (codici)
					$k = strlen( $riga ); while ( $k > 1 && self::larghezza( substr( $riga, 0, $k ), $font, $size ) > $max ) $k--;
					$out[] = substr( $riga, 0, $k ); $riga = substr( $riga, $k );
				}
			}
			$out[] = $riga;
		}
		return $out;
	}

	private function nuova_pagina(): void {
		if ( $this->cur !== '' || $this->pagine !== array() ) $this->pagine[] = $this->cur;
		$this->cur = ''; $this->y = self::H - self::TOP;
	}

	private function serve( float $h ): void {
		if ( $this->y - $h < self::BOTTOM ) $this->nuova_pagina();
	}

	private function testo_a( float $x, float $y, string $s, string $font = 'Helvetica', float $size = 10.5, string $col = '0.07 0.09 0.15' ): void {
		$f = $font === 'Helvetica-Bold' ? 'F2' : 'F1';
		$this->cur .= sprintf( "BT %s rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $col, $f, $size, $x, $y, self::esc( $s ) );
	}

	/** Un paragrafo che va a capo e cambia pagina da solo. $rientro per i punti elenco. */
	public function para( string $utf8, string $font = 'Helvetica', float $size = 10.5, float $dopo = 6.0, float $rientro = 0.0, string $col = '0.07 0.09 0.15' ): void {
		$lh = $size * 1.38;
		foreach ( self::righe( self::cp( $utf8 ), $font, $size, self::W - 2 * self::MX - $rientro ) as $r ) {
			$this->serve( $lh );
			$this->y -= $lh;
			$this->testo_a( self::MX + $rientro, $this->y, $r, $font, $size, $col );
		}
		$this->y -= $dopo;
	}

	public function linea( float $spazio = 8.0 ): void {
		$this->serve( $spazio * 2 );
		$this->y -= $spazio;
		$this->cur .= sprintf( "0.85 0.87 0.9 RG 0.6 w %.2F %.2F m %.2F %.2F l S\n", self::MX, $this->y, self::W - self::MX, $this->y );
		$this->y -= $spazio;
	}

	/**
	 * Una riga da compilare a mano: «Etichetta ________». Con $campo_firma la riga porta sopra di sé il campo firma digitale
	 * di quel nome (AcroForm), largo quanto la riga e alto quanto lo spazio della firma.
	 */
	public function campo( string $etichetta, float $alto = 26.0, string $campo_firma = '', string $descrizione = '' ): void {
		$this->serve( $alto + 4 );
		$this->y -= $alto;
		$e = self::cp( $etichetta );
		$this->testo_a( self::MX, $this->y, $e, 'Helvetica', 10.0, '0.29 0.33 0.39' );
		$x0 = self::MX + self::larghezza( $e, 'Helvetica', 10.0 ) + 8;
		$this->cur .= sprintf( "0.45 0.48 0.52 RG 0.7 w %.2F %.2F m %.2F %.2F l S\n", $x0, $this->y - 2, self::W - self::MX, $this->y - 2 );
		if ( $campo_firma !== '' ) {
			$this->campi_firma[] = array( $campo_firma, $descrizione, count( $this->pagine ), array( $x0, $this->y - 1, self::W - self::MX, $this->y - 1 + $alto - 6 ) );
		}
	}

	public function spazio( float $h ): void { $this->serve( $h ); $this->y -= $h; }

	/** Tiene insieme un blocco alto $h: se non ci sta, pagina nuova (il blocco firma non si spezza). */
	public function insieme( float $h ): void { $this->serve( $h ); }

	/** Un punto d elenco col rientro sospeso: il pallino a sinistra, il testo allineato a capo. */
	public function punto( string $utf8, float $size = 10.0, float $rientro = 16.0 ): void {
		$lh = $size * 1.38; $prima = true;
		foreach ( self::righe( self::cp( $utf8 ), 'Helvetica', $size, self::W - 2 * self::MX - $rientro ) as $r ) {
			$this->serve( $lh ); $this->y -= $lh;
			if ( $prima ) { $this->testo_a( self::MX + 4, $this->y, self::cp( '•' ), 'Helvetica', $size ); $prima = false; }
			$this->testo_a( self::MX + $rientro, $this->y, $r, 'Helvetica', $size );
		}
		$this->y -= 2;
	}

	/* ===================================================================== *
	 *  LE IMMAGINI (§ 9) — PNG e JPEG senza librerie
	 * ===================================================================== */

	/**
	 * Un PNG letto e preparato per il PDF. @return array{w:int,h:int,colori:int,dati:string,alfa:?string}|null
	 * 8 bit per canale, non interlacciato; tipi 0 (grigio), 2 (RGB), 3 (tavolozza, con tRNS), 4 (grigio+alfa), 6 (RGBA).
	 */
	public static function png( string $b ): ?array {
		if ( strncmp( $b, "\x89PNG\r\n\x1A\n", 8 ) !== 0 ) return null;
		$p = 8; $n = strlen( $b ); $idat = ''; $plte = ''; $trns = ''; $w = $h = $prof = $tipo = $intl = 0;
		while ( $p + 8 <= $n ) {
			$len = unpack( 'N', substr( $b, $p, 4 ) )[1]; $t = substr( $b, $p + 4, 4 ); $d = substr( $b, $p + 8, $len );
			if ( $t === 'IHDR' ) { $x = unpack( 'Nw/Nh/Cprof/Ctipo/Ccomp/Cfiltro/Cintl', $d ); $w = $x['w']; $h = $x['h']; $prof = $x['prof']; $tipo = $x['tipo']; $intl = $x['intl']; }
			elseif ( $t === 'PLTE' ) $plte = $d;
			elseif ( $t === 'tRNS' ) $trns = $d;
			elseif ( $t === 'IDAT' ) $idat .= $d;
			elseif ( $t === 'IEND' ) break;
			$p += 12 + $len;
		}
		if ( $w < 1 || $h < 1 || $prof !== 8 || $intl !== 0 || ! in_array( $tipo, array( 0, 2, 3, 4, 6 ), true ) ) return null;
		$raw = @gzuncompress( $idat );
		if ( $raw === false ) return null;
		$bpp = array( 0 => 1, 2 => 3, 3 => 1, 4 => 2, 6 => 4 )[ $tipo ];
		$stride = $w * $bpp;
		if ( strlen( $raw ) < ( $stride + 1 ) * $h ) return null;
		$prec = array_fill( 0, $stride, 0 );
		$col = ''; $alfa = '';
		$pal = $tipo === 3 ? array_values( unpack( 'C*', $plte ) ) : array();
		$tr  = $tipo === 3 && $trns !== '' ? array_values( unpack( 'C*', $trns ) ) : array();
		for ( $r = 0; $r < $h; $r++ ) {
			$off = $r * ( $stride + 1 );
			$f = ord( $raw[ $off ] );
			$cur = array_values( unpack( 'C*', substr( $raw, $off + 1, $stride ) ) );
			for ( $i = 0; $i < $stride; $i++ ) {
				$a = $i >= $bpp ? $cur[ $i - $bpp ] : 0; $up = $prec[ $i ]; $c = $i >= $bpp ? $prec[ $i - $bpp ] : 0;
				switch ( $f ) {
					case 1: $cur[ $i ] = ( $cur[ $i ] + $a ) & 255; break;
					case 2: $cur[ $i ] = ( $cur[ $i ] + $up ) & 255; break;
					case 3: $cur[ $i ] = ( $cur[ $i ] + ( ( $a + $up ) >> 1 ) ) & 255; break;
					case 4: $pp = $a + $up - $c; $pa = abs( $pp - $a ); $pb = abs( $pp - $up ); $pc = abs( $pp - $c );
						$cur[ $i ] = ( $cur[ $i ] + ( ( $pa <= $pb && $pa <= $pc ) ? $a : ( $pb <= $pc ? $up : $c ) ) ) & 255; break;
				}
			}
			$prec = $cur;
			if ( $tipo === 6 || $tipo === 4 ) {
				$k = $tipo === 6 ? 3 : 1;
				for ( $i = 0; $i < $stride; $i += $bpp ) { for ( $j = 0; $j < $k; $j++ ) $col .= chr( $cur[ $i + $j ] ); $alfa .= chr( $cur[ $i + $k ] ); }
			} elseif ( $tipo === 3 ) {
				foreach ( $cur as $ix ) { $col .= chr( $pal[ 3 * $ix ] ?? 0 ) . chr( $pal[ 3 * $ix + 1 ] ?? 0 ) . chr( $pal[ 3 * $ix + 2 ] ?? 0 ); $alfa .= chr( $tr[ $ix ] ?? 255 ); }
			} else {
				$col .= pack( 'C*', ...$cur );
			}
		}
		$colori = in_array( $tipo, array( 0, 4 ), true ) ? 1 : 3;
		if ( $alfa !== '' && strspn( $alfa, "\xFF" ) === strlen( $alfa ) ) $alfa = '';   // tutto opaco: niente maschera
		return array( 'w' => $w, 'h' => $h, 'colori' => $colori, 'dati' => (string) gzcompress( $col, 9 ), 'alfa' => $alfa === '' ? null : (string) gzcompress( $alfa, 9 ), 'filtro' => 'FlateDecode' );
	}

	/** Un JPEG così com è (DCTDecode). @return array{w:int,h:int,colori:int,dati:string,alfa:null,filtro:string}|null */
	public static function jpeg( string $b ): ?array {
		if ( strncmp( $b, "\xFF\xD8", 2 ) !== 0 ) return null;
		$p = 2; $n = strlen( $b );
		while ( $p + 4 < $n ) {
			if ( ord( $b[ $p ] ) !== 0xFF ) return null;
			$m = ord( $b[ $p + 1 ] );
			$len = unpack( 'n', substr( $b, $p + 2, 2 ) )[1];
			if ( in_array( $m, array( 0xC0, 0xC1, 0xC2 ), true ) ) {
				$x = unpack( 'Cprec/nh/nw/Ccomp', substr( $b, $p + 4, 6 ) );
				if ( ! in_array( $x['comp'], array( 1, 3 ), true ) ) return null;
				return array( 'w' => $x['w'], 'h' => $x['h'], 'colori' => $x['comp'], 'dati' => $b, 'alfa' => null, 'filtro' => 'DCTDecode' );
			}
			$p += 2 + $len;
		}
		return null;
	}

	/** Il file del logo di Idea Technologies nel pacchetto ('' se manca). */
	public static function file_logo_idea(): string {
		foreach ( self::LOGO_IDEA as $r ) { $f = RDACADEMY_PLUGIN_DIR . $r; if ( is_file( $f ) && filesize( $f ) > 0 ) return $f; }
		return '';
	}

	/** Un immagine dal file (PNG o JPEG). */
	public static function immagine( string $file ): ?array {
		if ( $file === '' || ! is_file( $file ) ) return null;
		$b = (string) file_get_contents( $file );
		return self::png( $b ) ?? self::jpeg( $b );
	}

	/** Disegna un immagine alta $alto punti con l angolo in basso a sinistra in ($x, $y); $destra = allineata a destra in $x. */
	private function logo( string $nome, array $img, float $x, float $y, float $alto, bool $destra = false ): void {
		$largo = $alto * $img['w'] / $img['h'];
		$max = ( self::W - 2 * self::MX ) / 2 - 12;
		if ( $largo > $max ) { $alto = $alto * $max / $largo; $largo = $max; }
		if ( $destra ) $x -= $largo;
		$this->immagini[ $nome ] = $img;
		$this->cur .= sprintf( "q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q\n", $largo, $alto, $x, $y, $nome );
	}

	/** Una stringa per il dizionario /Info: UTF-16BE con BOM (le lettere accentate e i trattini restano veri). */
	private static function info( string $utf8 ): string {
		return '<FEFF' . strtoupper( bin2hex( (string) mb_convert_encoding( $utf8, 'UTF-16BE', 'UTF-8' ) ) ) . '>';
	}

	/** Il documento finito (byte). $id = l identificativo del contratto (32 esadecimali) che va nel trailer /ID. */
	public function chiudi( string $id ): string {
		if ( $this->cur !== '' ) $this->pagine[] = $this->cur;
		$this->cur = '';
		$n = count( $this->pagine );
		$ogg = array();
		$prossimo = 5;
		$num_img = array();
		foreach ( $this->immagini as $nome => $img ) {
			$smask = 0;
			if ( $img['alfa'] !== null ) { $smask = $prossimo++; }
			$num_img[ $nome ] = $prossimo++;
			if ( $smask ) $ogg[ $smask ] = '<< /Type /XObject /Subtype /Image /Width ' . $img['w'] . ' /Height ' . $img['h'] . ' /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length ' . strlen( $img['alfa'] ) . " >>\nstream\n" . $img['alfa'] . "\nendstream";
			$ogg[ $num_img[ $nome ] ] = '<< /Type /XObject /Subtype /Image /Width ' . $img['w'] . ' /Height ' . $img['h'] . ' /ColorSpace /' . ( $img['colori'] === 1 ? 'DeviceGray' : 'DeviceRGB' ) . ' /BitsPerComponent 8 /Filter /' . $img['filtro']
				. ( $smask ? ' /SMask ' . $smask . ' 0 R' : '' ) . ' /Length ' . strlen( $img['dati'] ) . " >>\nstream\n" . $img['dati'] . "\nendstream";
		}
		$num_pag = array(); $num_cont = array();
		for ( $i = 0; $i < $n; $i++ ) { $num_pag[ $i ] = $prossimo++; $num_cont[ $i ] = $prossimo++; }
		$num_campi = array(); $num_ap = array();
		foreach ( $this->campi_firma as $k => $c ) { $num_campi[ $k ] = $prossimo++; $num_ap[ $k ] = $prossimo++; }
		$num_info = $prossimo++;

		$ogg[1] = '<< /Type /Catalog /Pages 2 0 R' . ( $num_campi ? ' /AcroForm << /Fields [' . implode( ' ', array_map( static function ( $x ) { return $x . ' 0 R'; }, $num_campi ) ) . '] >>' : '' ) . ' >>';
		$ogg[2] = '<< /Type /Pages /Kids [' . implode( ' ', array_map( static function ( $x ) { return $x . ' 0 R'; }, $num_pag ) ) . '] /Count ' . $n . ' >>';
		$ogg[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$ogg[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		$xobj = '';
		foreach ( $num_img as $nome => $k ) $xobj .= '/' . $nome . ' ' . $k . ' 0 R ';
		foreach ( $this->pagine as $i => $c ) {
			$p = self::cp( $this->piede . ' · Pagina ' . ( $i + 1 ) . ' di ' . $n );
			$yy = 40.0;
			foreach ( self::righe( $p, 'Helvetica', 7.5, self::W - 2 * self::MX ) as $r ) { $c .= sprintf( "BT 0.42 0.45 0.5 rg /F1 7.50 Tf %.2F %.2F Td (%s) Tj ET\n", self::MX, $yy, self::esc( $r ) ); $yy -= 10; }
			$c .= sprintf( "0.85 0.87 0.9 RG 0.5 w %.2F 52 m %.2F 52 l S\n", self::MX, self::W - self::MX );
			$annots = array();
			foreach ( $this->campi_firma as $k => $cf ) if ( $cf[2] === $i ) $annots[] = $num_campi[ $k ] . ' 0 R';
			$ogg[ $num_pag[ $i ] ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::W . ' ' . self::H . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >>'
				. ( $xobj !== '' ? ' /XObject << ' . trim( $xobj ) . ' >>' : '' ) . ' >> /Contents ' . $num_cont[ $i ] . ' 0 R' . ( $annots ? ' /Annots [' . implode( ' ', $annots ) . ']' : '' ) . ' >>';
			$ogg[ $num_cont[ $i ] ] = '<< /Length ' . strlen( $c ) . " >>\nstream\n" . $c . "endstream";
		}
		foreach ( $this->campi_firma as $k => $cf ) {
			list( $nome, $descr, $pag, $r ) = $cf;
			$w = $r[2] - $r[0]; $h = $r[3] - $r[1];
			// S617_B269B (owner 2026-10-13 17:14: «togli il riquadro a bordo scuro e lascia solo lo spazio bianco, in questo modo la firma rimane
			// sopra»): l aspetto del campo e vuoto, nessun bordo; il campo resta (serve alla firma digitale) ma non copre la firma inserita.
			$ap = "% campo firma senza bordo\n";
			$ogg[ $num_ap[ $k ] ] = sprintf( '<< /Type /XObject /Subtype /Form /BBox [0 0 %.2F %.2F] /Length %d >>', $w, $h, strlen( $ap ) ) . "\nstream\n" . $ap . "endstream";
			// S617_B269C (owner 17:59): /F 2 = NASCOSTO — il lettore non mostra il campo (niente riquadro azzurro sopra la firma inserita), resta solo lo
			// spazio bianco con la riga «Firma»; il campo resta nel file per nome (Firma_Contratto, Firma_Approvazione_Specifica) e la sua /Rect da la posizione alla verifica
			$ogg[ $num_campi[ $k ] ] = sprintf( '<< /Type /Annot /Subtype /Widget /FT /Sig /T (%s) /TU %s /Rect [%.2F %.2F %.2F %.2F] /P %d 0 R /F 2 /BS << /W 0 >> /AP << /N %d 0 R >> >>',
				self::esc( $nome ), self::info( $descr ), $r[0], $r[1], $r[2], $r[3], $num_pag[ $pag ], $num_ap[ $k ] );
		}
		$ogg[ $num_info ] = '<< /Producer ' . self::info( RDACADEMY_PRODUCT . ' ' . RDACADEMY_VERSION_LABEL ) . ' /Title ' . self::info( 'Contratto di licenza d\'uso ' . RDACADEMY_PRODUCT . ' — EULA v' . Rdacademy_License::EULA_VERSION ) . ' /Author ' . self::info( RDACADEMY_VENDOR ) . ' /CreationDate (D:' . gmdate( 'YmdHis' ) . "Z) >>";
		ksort( $ogg );
		$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $off = array();
		foreach ( $ogg as $k => $o ) { $off[ $k ] = strlen( $pdf ); $pdf .= $k . " 0 obj\n" . $o . "\nendobj\n"; }
		$x = strlen( $pdf ); $tot = max( array_keys( $ogg ) ) + 1;
		$pdf .= "xref\n0 " . $tot . "\n0000000000 65535 f \n";
		for ( $k = 1; $k < $tot; $k++ ) $pdf .= isset( $off[ $k ] ) ? sprintf( "%010d 00000 n \n", $off[ $k ] ) : "0000000000 65535 f \n";
		$id = strtoupper( preg_match( '/^[0-9a-f]{32}$/i', $id ) ? $id : str_repeat( '0', 32 ) );
		$pdf .= "trailer\n<< /Size " . $tot . " /Root 1 0 R /Info " . $num_info . " 0 R /ID [<" . $id . "> <" . $id . ">] >>\nstartxref\n" . $x . "\n%%EOF\n";
		return $pdf;
	}

	/** «012823EA5D11D828»: i primi 16 caratteri del codice di controllo in maiuscolo, come nel kit di firma di Legale (S717_V166, 3.75.05 voce 166:
	 *  l art. 15.1 v4 vuole lo stesso codice sul kit e sul PDF; prima «843e 4569 f9b5», 12 caratteri a gruppi di 4). */
	public static function codice_breve( string $h ): string {
		return strtoupper( substr( $h, 0, 16 ) );
	}

	/** Un testo della Tabella «Testi delle notifiche» (R-69), in italiano, coi segnaposto riempiti. [oggetto, testo] */
	public static function testo( string $codice, array $v ): array {
		$m = Rdacademy_License_Testi::modello( $codice, 'it' );
		return array( Rdacademy_License_Testi::riempi( (string) $m['oggetto'], $v, false ), Rdacademy_License_Testi::riempi( (string) $m['testo'], $v, false ) );
	}

	/**
	 * IL CONTRATTO. $id = identificativo del contratto generato (32 esadecimali).
	 * @return array{byte:string,nome:string,pagine:int,campi:array}|array{errore:string}
	 */
	public static function contratto( string $id ): array {
		$idea = self::immagine( self::file_logo_idea() );
		if ( ! $idea ) return array( 'errore' => 'Manca il logo di Idea Technologies nel pacchetto: il contratto non si genera senza (nessun segnaposto).' );
		$prod = self::immagine( RDACADEMY_PLUGIN_DIR . self::LOGO_PRODOTTO );
		$d = new self();
		$h = Rdacademy_License::licenziatario();
		$foro = Rdacademy_License::foro();
		$dom = class_exists( 'Rdacademy_License_Anchor' ) ? Rdacademy_License_Anchor::current_domain() : '';
		$hash = Rdacademy_License::text_hash();
		$d->piede = 'Codice di controllo ' . self::codice_breve( $hash ) . ' · ' . ( $dom !== '' ? $dom : '—' ) . ' · ' . RDACADEMY_PRODUCT . ' · EULA v' . Rdacademy_License::EULA_VERSION;
		$d->nuova_pagina();
		// testata: i due loghi alti 14 mm, Idea a sinistra, il prodotto a destra
		$alto = self::LOGO_ALTO_MM * self::MM;
		$ytop = self::H - 40 - $alto;
		$d->logo( 'ImIdea', $idea, self::MX, $ytop, $alto );
		if ( $prod ) $d->logo( 'ImProd', $prod, self::W - self::MX, $ytop, $alto, true );
		$d->y = $ytop - 18;
		$d->para( 'Contratto di licenza d\'uso del software «' . RDACADEMY_PRODUCT . '»', 'Helvetica-Bold', 16, 4 );
		$d->para( 'EULA v' . Rdacademy_License::EULA_VERSION . ' · contratto generato il ' . wp_date( 'd/m/Y \a\l\l\e H:i' ) . ' · identificativo ' . strtolower( $id ), 'Helvetica', 8.5, 10, 0, '0.29 0.33 0.39' );
		$d->para( 'Le parti', 'Helvetica-Bold', 12, 4 );
		$L = Rdacademy_License::licenziante();
		$d->para( 'Licenziante: ' . $L['denominazione'] . ( $L['forma'] !== '' ? ', ' . $L['forma'] : '' ) . ( $L['sede'] !== '' ? ' — sede legale: ' . $L['sede'] : '' )
			. ( $L['piva_cf'] !== '' ? ' — P.IVA ' . $L['piva_cf'] : '' ) . ( $L['pec'] !== '' ? ' — PEC ' . $L['pec'] : '' ) . ' — ' . Rdacademy_License::EMAIL_LICENZIANTE . '.', 'Helvetica', 10.5, 3 );
		$v = static function ( string $k ) use ( $h ): string { return $h[ $k ]['missing'] ? '[da compilare]' : $h[ $k ]['value']; };
		$pf = $h['tipo']['value'] === 'persona_fisica';
		$d->para( 'Licenziatario: ' . $v( 'denominazione' ) . ' — ' . ( $pf ? 'C.F. ' : 'P.IVA ' ) . $v( 'piva_cf' ) . ' — ' . ( $pf ? 'residenza' : 'sede legale' ) . ': ' . $v( 'sede' )
			. ( ! $pf ? ' — legale rappresentante: ' . $v( 'legale_rappr' ) : '' ) . ' — email o PEC: ' . $v( 'email' ) . '.', 'Helvetica', 10.5, 3 );
		$d->para( 'Dominio dell\'installazione: ' . ( $dom !== '' ? $dom : '[da compilare]' ) . '.', 'Helvetica', 10.5, 8 );
		$d->linea( 4 );
		foreach ( Rdacademy_License::articoli_resi( $foro ) as $a ) {
			$d->spazio( 4 );
			$d->para( $a['n'] . '. ' . $a['title'], 'Helvetica-Bold', 11, 2 );
			$d->para( $a['body'], 'Helvetica', 10, empty( $a['items'] ) ? 4 : 2 );
			foreach ( (array) ( $a['items'] ?? array() ) as $it ) $d->punto( $it );
		}
		// il blocco firma intero sulla stessa pagina, con la nota e le istruzioni in calce
		$vv = array( 'codice' => self::codice_breve( $hash ), 'licenziante' => rtrim( RDACADEMY_VENDOR, '.' ) . '.', 'indirizzo' => Rdacademy_License::indirizzo_licenziante(), 'prodotto' => RDACADEMY_PRODUCT );
		list( , $nota ) = self::testo( 'licenza.pdf_nota', $vv );
		list( , $istr ) = self::testo( 'licenza.pdf_istruzioni', $vv );
		$d->insieme( 400 );
		$d->linea( 6 );
		$d->para( 'Il Licenziatario — sottoscrizione del contratto', 'Helvetica-Bold', 11, 0 );
		$d->para( 'Licenziatario: ' . $v( 'denominazione' ), 'Helvetica', 10, 0 );
		$d->campo( 'Luogo e data' );
		$d->campo( 'Nome e cognome del firmatario' );
		$d->campo( 'Carica (poteri di rappresentanza)' );
		$d->campo( 'Firma', 44, Rdacademy_License_Firma::CAMPO_CONTRATTO, 'Firma del contratto' );
		$d->spazio( 16 );
		$d->para( 'Il Licenziatario — approvazione specifica, ai sensi degli artt. 1341 e 1342 c.c., degli articoli ' . self::elenco( Rdacademy_License::VEX_ARTICLES ), 'Helvetica-Bold', 11, 2 );
		$d->para( 'Il Licenziatario dichiara di aver letto e di approvare specificamente gli articoli ' . Rdacademy_License::vex_testo() . '.', 'Helvetica', 10, 0 );
		$d->campo( 'Firma', 44, Rdacademy_License_Firma::CAMPO_APPROVAZIONE, 'Approvazione specifica' );
		$d->spazio( 10 );
		$d->linea( 4 );
		$d->para( $nota, 'Helvetica', 8.5, 3, 0, '0.29 0.33 0.39' );
		$d->para( $istr, 'Helvetica', 8.5, 0, 0, '0.29 0.33 0.39' );
		$byte = $d->chiudi( $id );
		$nome = 'contratto-licenza-' . sanitize_title( RDACADEMY_PRODUCT ) . '-eula-v' . Rdacademy_License::EULA_VERSION . '-' . wp_date( 'Ymd' ) . '.pdf';
		return array( 'byte' => $byte, 'nome' => $nome, 'pagine' => count( $d->pagine ), 'campi' => array_map( static function ( $c ) { return $c[0]; }, $d->campi_firma ) );
	}

	/** «3, 8, 10, 12 e 14». */
	public static function elenco( array $x ): string {
		$u = array_pop( $x );
		return $x ? implode( ', ', $x ) . ' e ' . $u : (string) $u;
	}
}
