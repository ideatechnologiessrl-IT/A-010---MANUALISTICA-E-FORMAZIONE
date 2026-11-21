<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-license-verifica.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Rdacademy_License_Verifica — «VERIFICA IL DOCUMENTO» (3.60.03G, BUG-269, owner 2026-10-13 12:04-12:1x).
 * =============================================================================
 * Il contratto firmato allegato si controlla con un tasto prima di poterlo trasmettere. Se la verifica non passa l allegato si
 * toglie da solo (il file resta nella cartella protetta, lo storico lo nomina) e si può allegare di nuovo.
 *
 * Decisioni dell owner (AskUserQuestion, 12:1x): D-269.1 tasto «Verifica il documento» dopo «Allega», «Trasmetti» acceso solo dopo
 * la verifica · D-269.2 firma digitale: le due firme dello STESSO firmatario · D-269.3 firma autografa: una scansione che il
 * programma non sa giudicare si RIFIUTA col suo motivo · D-269.4 ordine 268+269, poi il lotto 97.2.
 *
 * FIRMA DIGITALE — dal /Contents di ciascuna delle due firme (CMS / PKCS#7) si leggono i certificati con openssl; il certificato
 * del firmatario è quello che non è di una CA. Le due firme devono avere lo stesso firmatario: stesso serialNumber del soggetto
 * (il codice fiscale delle firme qualificate italiane) o, se manca, stesso nome (CN) e organizzazione. NON si verifica la catena
 * né la validità: lo fa Idea Technologies alla ricezione (§ 5 A).
 *
 * FIRMA AUTOGRAFA — l ultima pagina della scansione è un immagine (JPEG, oppure grigio / colori / bianco e nero senza perdita):
 *  1. il file NON è il contratto generato (stessi byte) e non ne è una modifica al computer (stesso /ID senza immagine di pagina);
 *  2. l immagine dell ultima pagina si legge (JPEG con GD; Flate a 1 o 8 bit, grigio / RGB / CMYK / ICC, predittori PNG); i
 *     formati del bianco e nero compresso (CCITT, JBIG2) e il JPEG 2000 non si leggono: si chiede una scansione in grigio o a colori;
 *  3. almeno 110 punti per pollice sulla larghezza della pagina;
 *  4. si cercano le due righe «Firma» del contratto alle posizioni dei due campi firma del generato (dritta, capovolta, girata di
 *     90° da una parte o dall altra) — se non si trovano, la pagina è illeggibile, storta o non è quella del contratto;
 *  5. sopra (e un poco sotto) ciascuna riga ci deve essere un segno di inchiostro abbastanza ampio: altrimenti manca la firma;
 *  6. i due segni si devono somigliare: larghezza, altezza e quantità d inchiostro entro un fattore 2,5, e la forma (griglia
 *     della densità d inchiostro sfumata, correlazione) almeno 0,40. È un controllo di massima: dice se le due firme sono
 *     plausibilmente della stessa mano, non certifica che siano autentiche.
 *
 * FIRMA INSERITA AL COMPUTER COME IMMAGINE (3.60.05G, BUG-269.B — owner 2026-10-13 17:11: «qui abbiamo un problema di troppa fiscalità …
 * dobbiamo essere più flessibili fai in modo che si possa fare»). Se l ultima pagina NON è una scansione (nessuna immagine che copra
 * almeno metà pagina) ma porta immagini appoggiate sopra i due spazi «Firma» — nel contenuto della pagina, dentro un modulo (Form)
 * o in un timbro (annotazione con aspetto) — si controlla:
 *  1. che il file sia il contratto generato da RelDox (stesso /ID, oppure il codice di controllo del testo nel contenuto);
 *  2. che su ciascuno dei due spazi ci sia un immagine con un segno vero (trasparenza /SMask applicata: lo sfondo trasparente è carta);
 *  3. che i due segni si somiglino con le stesse soglie della scansione (la stessa immagine usata due volte va bene).
 * L originale cartaceo resta dovuto (art. 15.3): il riassunto lo dice.
 *
 * @package RelDox CRD
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_License_Verifica {

	const PW = 595.0;               // A4 in punti (Rdacademy_License_Pdf)
	const PH = 842.0;
	const DPI_MIN = 110;            // punti per pollice minimi sulla larghezza della pagina
	const LARGH_MAX = 1700;         // larghezza di lavoro: oltre si riduce (minimo del blocco, le righe sottili restano)
	const SIMILI_FATTORE = 2.5;     // larghezza, altezza e inchiostro delle due firme entro questo fattore
	const SIMILI_FORMA = 0.40;      // correlazione minima della forma (tarata con S616-4: stessa mano 0,49-0,93, mani diverse fino a 0,45)
	const GRIGLIA_W = 48;
	const GRIGLIA_H = 16;

	/** Il motivo per cui il file non passa ('' = passa). $m riceve le misure (per lo storico e la mail). */
	public static function controlla( string $b, string $modalita, array $g, string $gb, ?array &$m = null ): string {
		$m = array( 'modalita' => $modalita );
		return $modalita === 'digitale' ? self::digitale( $b, $m ) : self::autografa( $b, $g, $gb, $m );
	}

	/* ===================================================================== *
	 *  FIRMA DIGITALE — lo stesso firmatario nei due campi (D-269.2)
	 * ===================================================================== */

	public static function digitale( string $b, array &$m ): string {
		$f = self::firmatari( $b );
		$m['firmatari'] = array();
		foreach ( array( Rdacademy_License_Firma::CAMPO_CONTRATTO => 'Firma del contratto', Rdacademy_License_Firma::CAMPO_APPROVAZIONE => 'Approvazione specifica' ) as $c => $etichetta ) {
			if ( empty( $f[ $c ] ) ) return 'Nel campo «' . $etichetta . '» non c\'è una firma digitale leggibile.';
			if ( $f[ $c ]['errore'] !== '' ) return 'Il certificato della firma nel campo «' . $etichetta . '» non si legge: ' . $f[ $c ]['errore'];
			$m['firmatari'][ $c ] = $f[ $c ]['chi'];
		}
		$a = $f[ Rdacademy_License_Firma::CAMPO_CONTRATTO ]; $z = $f[ Rdacademy_License_Firma::CAMPO_APPROVAZIONE ];
		if ( ! hash_equals( $a['chiave'], $z['chiave'] ) )
			return 'Le due firme digitali sono di due persone diverse («' . $a['chi'] . '» e «' . $z['chi'] . '»): le due firme del contratto le appone la stessa persona, il legale rappresentante.';
		$m['firmatario'] = $a['chi'];
		return '';
	}

	/**
	 * I firmatari dei campi firma: campo => ['chi' => nome leggibile, 'chiave' => identità per il confronto, 'errore' => ''].
	 * @return array<string,array{chi:string,chiave:string,errore:string}>
	 */
	public static function firmatari( string $b ): array {
		$out = array();
		$ogg = Rdacademy_License_Firma::oggetti( $b );
		foreach ( $ogg as $corpo ) {
			if ( ! preg_match( '/\/FT\s*\/Sig\b/', $corpo ) || ! preg_match( '/\/T\s*\(((?:\\\\.|[^\\\\)])*)\)/', $corpo, $mt ) ) continue;
			$nome = stripcslashes( $mt[1] );
			$sig = '';
			if ( preg_match( '/\/V\s+(\d+)\s+\d+\s+R/', $corpo, $mv ) ) $sig = (string) ( $ogg[ (int) $mv[1] ] ?? '' );
			elseif ( preg_match( '/\/V\s*<</', $corpo ) ) $sig = $corpo;
			if ( ! preg_match( '/\/Contents\s*<([0-9A-Fa-f\s]*)>/', $sig, $mc ) ) continue;
			$out[ $nome ] = self::firmatario_da_cms( (string) hex2bin( self::pari( (string) preg_replace( '/\s/', '', $mc[1] ) ) ) );
		}
		return $out;
	}

	private static function pari( string $h ): string { return strlen( $h ) % 2 ? $h . '0' : $h; }

	/** Il firmatario di una busta CMS (DER, eventualmente con zeri di riempimento in coda). */
	public static function firmatario_da_cms( string $der ): array {
		$no = array( 'chi' => '', 'chiave' => '', 'errore' => '' );
		if ( ! function_exists( 'openssl_pkcs7_read' ) || ! function_exists( 'openssl_x509_parse' ) ) return array( 'errore' => 'il server non ha l estensione openssl di PHP.' ) + $no;
		if ( strlen( $der ) < 4 || ord( $der[0] ) !== 0x30 ) return array( 'errore' => 'la firma non contiene una busta CMS.' ) + $no;
		// la lunghezza vera dal DER (il /Contents è riempito di zeri fino alla misura riservata)
		$l = ord( $der[1] );
		if ( $l < 0x80 ) $tot = 2 + $l;
		elseif ( $l === 0x80 ) $tot = strlen( rtrim( $der, "\0" ) ) + 2;   // lunghezza indefinita (BER): fino agli zeri finali
		else { $n = $l & 0x7F; $tot = 2 + $n; $len = 0; for ( $i = 0; $i < $n; $i++ ) $len = ( $len << 8 ) | ord( $der[ 2 + $i ] ?? "\0" ); $tot += $len; }
		$der = substr( $der, 0, min( strlen( $der ), $tot ) );
		$pem = "-----BEGIN PKCS7-----\n" . chunk_split( base64_encode( $der ), 64, "\n" ) . "-----END PKCS7-----\n";
		$certs = array();
		if ( ! @openssl_pkcs7_read( $pem, $certs ) || ! $certs ) return array( 'errore' => 'la busta CMS non porta il certificato del firmatario.' ) + $no;
		$info = array();
		foreach ( $certs as $c ) { $x = @openssl_x509_parse( $c ); if ( is_array( $x ) ) $info[] = $x; }
		$emittenti = array();
		foreach ( $info as $x ) $emittenti[] = self::nome_dn( (array) ( $x['issuer'] ?? array() ) );
		$scelto = null;
		foreach ( $info as $x ) {
			$ca = stripos( (string) ( $x['extensions']['basicConstraints'] ?? '' ), 'CA:TRUE' ) !== false;
			$emette = in_array( self::nome_dn( (array) ( $x['subject'] ?? array() ) ), $emittenti, true ) && count( $info ) > 1;
			if ( ! $ca && ! $emette ) { $scelto = $x; break; }
		}
		if ( ! $scelto ) $scelto = $info[0] ?? null;
		if ( ! $scelto ) return array( 'errore' => 'il certificato del firmatario non si legge.' ) + $no;
		$s = (array) ( $scelto['subject'] ?? array() );
		$v = static function ( $k ) use ( $s ) { $x = $s[ $k ] ?? ''; return is_array( $x ) ? (string) reset( $x ) : (string) $x; };
		$cn = $v( 'CN' ); $sn = $v( 'serialNumber' ); $o = $v( 'O' );
		$chi = $cn !== '' ? $cn : ( $sn !== '' ? $sn : self::nome_dn( $s ) );
		$chiave = $sn !== '' ? 'sn:' . mb_strtolower( $sn ) : 'cn:' . mb_strtolower( $cn . '|' . $o );
		return array( 'chi' => $chi . ( $sn !== '' && $cn !== '' ? ' (' . $sn . ')' : '' ), 'chiave' => $chiave, 'errore' => '' );
	}

	private static function nome_dn( array $d ): string {
		$p = array(); foreach ( $d as $k => $x ) $p[] = $k . '=' . ( is_array( $x ) ? implode( '+', $x ) : $x );
		return implode( ',', $p );
	}

	/* ===================================================================== *
	 *  FIRMA AUTOGRAFA — la scansione (D-269.3: ciò che non si sa giudicare si rifiuta)
	 * ===================================================================== */

	const RIFAI_PDF = 'Inserisci l\'immagine della tua firma sopra ciascuno dei due spazi «Firma» dell\'ultima pagina e allega di nuovo il PDF, oppure stampa il contratto, firmalo a mano e allega la scansione.';
	const RIFAI = 'Stampa il contratto, firma a mano nei due spazi «Firma», scansiona tutte le pagine in un solo PDF (in scala di grigi o a colori, almeno 150 dpi) e allegalo di nuovo.';

	public static function autografa( string $b, array $g, string $gb, array &$m ): string {
		if ( $gb !== '' && ( $b === $gb || ( strlen( $b ) >= strlen( $gb ) && strncmp( $b, $gb, strlen( $gb ) ) === 0 ) ) )
			return 'Il file allegato è il contratto generato da RelDoxAcademy, senza le firme. ' . self::RIFAI;
		$riq = self::riquadri( $gb );
		if ( count( $riq ) !== 2 ) return 'Il contratto generato non si legge più (i due spazi di firma non si trovano): genera di nuovo il contratto.';
		// S617_B269B: la firma inserita al computer come immagine — l ultima pagina non è una scansione ma porta immagini appoggiate
		$pos = self::posizionate( $b );
		$scansione = false;
		foreach ( $pos as $p ) if ( $p['area'] >= 0.5 * self::PW * self::PH ) $scansione = true;
		if ( ! $scansione && $pos ) return self::inserita( $b, $g, $riq, $pos, $m );
		$errore = '';
		$img = self::immagine_ultima_pagina( $b, $errore );
		if ( $img === null ) {
			$id = Rdacademy_License_Firma::id_documento( $b );
			if ( $errore === 'nessuna' && $id !== '' && hash_equals( strtolower( (string) ( $g['id'] ?? '' ) ), $id ) )
				return 'Il file allegato è il contratto generato modificato al computer, ma nei due spazi «Firma» non c\'è l\'immagine di una firma. ' . self::RIFAI_PDF;
			return ( $errore === 'nessuna' ? 'L\'ultima pagina del file non è una scansione (non c\'è un\'immagine della pagina). ' : $errore . ' ' ) . self::RIFAI;
		}
		$dpi = (int) round( $img['w0'] / ( self::PW / 72.0 ) );
		$m['dpi'] = $dpi;
		if ( $dpi < self::DPI_MIN ) return 'La scansione ha una risoluzione troppo bassa (' . $dpi . ' dpi): le firme non si distinguono. ' . self::RIFAI;
		// le quattro posizioni della pagina: dritta, capovolta, girata di 90° (se è orizzontale)
		$prove = $img['w'] > $img['h'] ? array( 90, 270 ) : array( 0, 180 );
		$mis = null;
		foreach ( $prove as $giro ) {
			$im = self::gira( $img, $giro );
			$x = self::misura( $im, $riq );
			if ( $x['righe'] === 2 ) { $mis = $x; $m['giro'] = $giro; break; }
		}
		if ( ! $mis ) return 'Nella scansione non trovo le due righe «Firma» dell\'ultima pagina del contratto: la pagina è illeggibile, troppo storta o non è quella del contratto. ' . self::RIFAI;
		$m['firme'] = $mis['firme'];
		$nomi = array( 'la firma del contratto (primo spazio «Firma»)', 'la firma dell\'approvazione specifica (secondo spazio «Firma»)' );
		$mancano = array();
		foreach ( $mis['firme'] as $i => $f ) if ( ! $f['presente'] ) $mancano[] = $nomi[ $i ];
		if ( $mancano ) return 'Nella scansione manca ' . implode( ' e ', $mancano ) . ': ogni spazio «Firma» va firmato a mano per esteso. ' . self::RIFAI;
		list( $a, $z ) = $mis['firme'];
		$sim = self::simili( $a, $z, $mis['griglie'] );
		$m['somiglianza'] = $sim;
		if ( $sim['motivo'] !== '' ) return 'Le due firme sono troppo diverse fra loro (' . $sim['motivo'] . '): le due firme le appone la stessa persona, per esteso, nello stesso modo. ' . self::RIFAI;
		return '';
	}

	/** I due spazi di firma del contratto GENERATO: [ [x0, y0, x1, y1] (punti) ] nell ordine contratto, approvazione. */
	public static function riquadri( string $gb ): array {
		if ( $gb === '' ) return array();
		$r = array();
		foreach ( Rdacademy_License_Firma::oggetti( $gb ) as $corpo ) {
			if ( ! preg_match( '/\/T\s*\((Firma_[A-Za-z_]+)\)/', $corpo, $mt ) ) continue;
			if ( ! preg_match( '/\/Rect\s*\[\s*([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s*\]/', $corpo, $mr ) ) continue;
			$r[ $mt[1] ] = array( (float) $mr[1], (float) $mr[2], (float) $mr[3], (float) $mr[4] );
		}
		$out = array();
		foreach ( array( Rdacademy_License_Firma::CAMPO_CONTRATTO, Rdacademy_License_Firma::CAMPO_APPROVAZIONE ) as $c ) if ( isset( $r[ $c ] ) ) $out[] = $r[ $c ];
		return $out;
	}

	/* ---------- l immagine dell ultima pagina ---------- */

	/**
	 * L immagine più grande dell ultima pagina, in grigio (un byte per punto, 0 = nero), ridotta a LARGH_MAX.
	 * @return array{w:int,h:int,px:string,w0:int,h0:int}|null  $errore = 'nessuna' (nessuna immagine) o il motivo scritto.
	 */
	public static function immagine_ultima_pagina( string $b, string &$errore ): ?array {
		$ogg = Rdacademy_License_Firma::oggetti( $b );
		$pag = self::pagine( $ogg );
		if ( ! $pag ) { $errore = 'Il PDF non si legge (le pagine non si trovano).'; return null; }
		$ultima = $ogg[ end( $pag ) ] ?? '';
		$res = self::risolvi_dict( $ogg, $ultima, 'Resources', $ogg, end( $pag ) );
		$xo = self::risolvi_dict( $ogg, $res, 'XObject' );
		$best = null; $area = 0;
		if ( preg_match_all( '/\/[^\s\/<>\[\]()]+\s+(\d+)\s+\d+\s+R/', $xo, $mx ) ) {
			foreach ( $mx[1] as $n ) {
				$c = (string) ( $ogg[ (int) $n ] ?? '' );
				if ( ! preg_match( '/\/Subtype\s*\/Image\b/', $c ) ) continue;
				$w = self::num( $ogg, $c, 'Width' ); $h = self::num( $ogg, $c, 'Height' );
				if ( $w * $h > $area ) { $area = $w * $h; $best = $c; }
			}
		}
		if ( ! $best ) { $errore = 'nessuna'; return null; }
		return self::decodifica( $ogg, $best, $errore );
	}

	/** Le pagine nell ordine del documento (numeri d oggetto), dall albero /Pages. */
	private static function pagine( array $ogg ): array {
		$radice = null;
		foreach ( $ogg as $n => $c ) if ( preg_match( '/\/Type\s*\/Pages\b/', $c ) && ! preg_match( '/\/Parent\s+\d+/', $c ) ) { $radice = $n; break; }
		if ( $radice === null ) return array();
		$out = array(); $visti = array();
		$giu = static function ( $n ) use ( &$giu, &$out, &$visti, $ogg ) {
			if ( isset( $visti[ $n ] ) || count( $visti ) > 5000 ) return; $visti[ $n ] = 1;
			$c = (string) ( $ogg[ $n ] ?? '' );
			if ( preg_match( '/\/Type\s*\/Page\b(?!s)/', $c ) ) { $out[] = $n; return; }
			if ( preg_match( '/\/Kids\s*\[([^\]]*)\]/', $c, $mk ) && preg_match_all( '/(\d+)\s+\d+\s+R/', $mk[1], $mm ) ) foreach ( $mm[1] as $k ) $giu( (int) $k );
		};
		$giu( $radice );
		return $out;
	}

	/** Il dizionario /Chiave di un oggetto (diretto «<< … >>» o riferimento «n g R»); per /Resources sale ai genitori. */
	private static function risolvi_dict( array $ogg, string $c, string $chiave, ?array $tutti = null, $num = null ): string {
		for ( $giro = 0; $giro < 20; $giro++ ) {
			if ( preg_match( '/\/' . $chiave . '\s+(\d+)\s+\d+\s+R/', $c, $mr ) ) return (string) ( $ogg[ (int) $mr[1] ] ?? '' );
			$p = strpos( $c, '/' . $chiave );
			if ( $p !== false ) { $d = self::dict_da( $c, $p + strlen( $chiave ) + 1 ); if ( $d !== '' ) return $d; }
			if ( $chiave !== 'Resources' || ! preg_match( '/\/Parent\s+(\d+)\s+\d+\s+R/', $c, $mp ) ) return '';
			$c = (string) ( $ogg[ (int) $mp[1] ] ?? '' );
		}
		return '';
	}

	/** Il dizionario «<< … >>» che comincia dopo la posizione $i (con i dizionari annidati). */
	private static function dict_da( string $c, int $i ): string {
		$a = strpos( $c, '<<', $i ); if ( $a === false ) return '';
		if ( trim( substr( $c, $i, $a - $i ) ) !== '' ) return '';
		$d = 0; $n = strlen( $c );
		for ( $k = $a; $k < $n - 1; $k++ ) {
			if ( $c[ $k ] === '<' && $c[ $k + 1 ] === '<' ) { $d++; $k++; }
			elseif ( $c[ $k ] === '>' && $c[ $k + 1 ] === '>' ) { $d--; $k++; if ( $d === 0 ) return substr( $c, $a, $k - $a + 1 ); }
		}
		return '';
	}

	private static function num( array $ogg, string $c, string $k ): int {
		if ( preg_match( '/\/' . $k . '\s+(\d+)\s+\d+\s+R/', $c, $m ) ) return (int) trim( (string) ( $ogg[ (int) $m[1] ] ?? '0' ) );
		return preg_match( '/\/' . $k . '\s+(\d+)/', $c, $m ) ? (int) $m[1] : 0;
	}

	/** I byte grezzi di uno stream (senza filtri): /Length diretta, altrimenti fino a «endstream». */
	private static function grezzo( array $ogg, string $c ): string {
		$s = strpos( $c, 'stream' ); if ( $s === false ) return '';
		$dict = substr( $c, 0, $s ); $s += 6;
		if ( substr( $c, $s, 2 ) === "\r\n" ) $s += 2; elseif ( ( $c[ $s ] ?? '' ) === "\n" || ( $c[ $s ] ?? '' ) === "\r" ) $s += 1;
		$len = self::num( $ogg, $dict, 'Length' );
		if ( $len > 0 && $s + $len <= strlen( $c ) ) return substr( $c, $s, $len );
		$e = strrpos( $c, 'endstream' );
		return $e === false ? '' : rtrim( substr( $c, $s, $e - $s ), "\r\n" );
	}

	private static function inflate( string $x ): ?string {
		$y = @gzuncompress( $x ); if ( $y === false ) $y = @gzinflate( substr( $x, 2 ) ); if ( $y === false ) $y = @gzinflate( $x );
		return $y === false ? null : $y;
	}

	/** L immagine decodificata in grigio. */
	private static function decodifica( array $ogg, string $c, string &$errore ): ?array {
		$s = strpos( $c, 'stream' ); $dict = $s === false ? $c : substr( $c, 0, $s );
		$filtri = array();
		if ( preg_match( '/\/Filter\s*\[([^\]]*)\]/', $dict, $mf ) ) { preg_match_all( '/\/(\w+)/', $mf[1], $mm ); $filtri = $mm[1]; }
		elseif ( preg_match( '/\/Filter\s*\/(\w+)/', $dict, $mf ) ) $filtri = array( $mf[1] );
		$dati = self::grezzo( $ogg, $c );
		$w = self::num( $ogg, $dict, 'Width' ); $h = self::num( $ogg, $dict, 'Height' );
		if ( $w < 1 || $h < 1 || $dati === '' ) { $errore = 'L\'immagine della pagina non si legge.'; return null; }
		while ( $filtri && $filtri[0] === 'FlateDecode' && in_array( 'DCTDecode', $filtri, true ) ) { $dati = self::inflate( $dati ); array_shift( $filtri ); if ( $dati === null ) { $errore = 'L\'immagine della pagina non si legge.'; return null; } }
		$f = $filtri ? end( $filtri ) : '';
		if ( in_array( $f, array( 'CCITTFaxDecode', 'JBIG2Decode', 'JPXDecode', 'LZWDecode', 'RunLengthDecode' ), true ) || count( $filtri ) > 1 ) {
			$errore = 'La scansione è salvata in un formato che RelDoxAcademy non sa leggere (' . ( $f === 'JPXDecode' ? 'JPEG 2000' : ( $f === 'CCITTFaxDecode' || $f === 'JBIG2Decode' ? 'bianco e nero compresso' : $f ) ) . ').';
			return null;
		}
		if ( $f === 'DCTDecode' ) {
			if ( ! function_exists( 'imagecreatefromstring' ) ) { $errore = 'Il server non ha l\'estensione GD di PHP: la scansione JPEG non si legge.'; return null; }
			$gd = @imagecreatefromstring( $dati );
			if ( ! $gd ) { $errore = 'L\'immagine JPEG della pagina non si legge.'; return null; }
			return self::da_gd( $gd, $w, $h );
		}
		// FlateDecode o nessun filtro: i punti grezzi
		if ( $f === 'FlateDecode' ) { $dati = self::inflate( $dati ); if ( $dati === null ) { $errore = 'L\'immagine della pagina non si legge.'; return null; } }
		$bpc = self::num( $ogg, $dict, 'BitsPerComponent' ); $maschera = (bool) preg_match( '/\/ImageMask\s+true/', $dict );
		if ( $maschera ) $bpc = 1;
		$col = $maschera ? 1 : self::colori( $ogg, $dict );
		if ( ! in_array( $bpc, array( 1, 8 ), true ) || $col === 0 ) { $errore = 'La scansione è salvata in un formato che RelDoxAcademy non sa leggere (' . $bpc . ' bit per colore).'; return null; }
		$riga = (int) ceil( $w * $col * $bpc / 8 );
		$pred = preg_match( '/\/Predictor\s+(\d+)/', $dict, $mp ) ? (int) $mp[1] : 1;
		if ( $pred >= 10 ) $dati = self::png_rovescia( $dati, $riga, $h, max( 1, (int) ( $col * $bpc / 8 ) ) );
		elseif ( $pred === 2 ) { $errore = 'La scansione è salvata in un formato che RelDoxAcademy non sa leggere (predittore TIFF).'; return null; }
		if ( strlen( $dati ) < $riga * $h ) { $errore = 'L\'immagine della pagina è incompleta.'; return null; }
		$inverti = (bool) preg_match( '/\/Decode\s*\[\s*1(\.0+)?\s+0(\.0+)?/', $dict );
		if ( $maschera ) $inverti = ! $inverti;   // nella maschera 0 = si dipinge (nero)
		return self::da_grezzo( $dati, $w, $h, $col, $bpc, $riga, $inverti );
	}

	/** Quanti colori ha lo spazio colore (0 = non gestito). */
	private static function colori( array $ogg, string $dict ): int {
		$cs = '';
		if ( preg_match( '/\/ColorSpace\s+(\d+)\s+\d+\s+R/', $dict, $m ) ) $cs = trim( (string) ( $ogg[ (int) $m[1] ] ?? '' ) );
		elseif ( preg_match( '/\/ColorSpace\s*(\[[^\]]*\]|\/\w+)/', $dict, $m ) ) $cs = $m[1];
		if ( preg_match( '/^\/?(DeviceGray|CalGray|G)\b/', ltrim( $cs, '[ ' ) ) ) return 1;
		if ( preg_match( '/^\/?(DeviceRGB|CalRGB|RGB)\b/', ltrim( $cs, '[ ' ) ) ) return 3;
		if ( preg_match( '/^\/?(DeviceCMYK|CMYK)\b/', ltrim( $cs, '[ ' ) ) ) return 4;
		if ( preg_match( '/ICCBased\s+(\d+)\s+\d+\s+R/', $cs, $mi ) ) { $n = self::num( $ogg, (string) ( $ogg[ (int) $mi[1] ] ?? '' ), 'N' ); return in_array( $n, array( 1, 3, 4 ), true ) ? $n : 0; }
		return 0;
	}

	/** Toglie i predittori PNG (righe col byte del filtro davanti). */
	private static function png_rovescia( string $d, int $riga, int $h, int $bpp ): string {
		$out = ''; $prec = str_repeat( "\0", $riga ); $p = 0; $n = strlen( $d );
		for ( $y = 0; $y < $h && $p < $n; $y++ ) {
			$t = ord( $d[ $p ] ); $r = substr( $d, $p + 1, $riga ); $p += $riga + 1;
			if ( strlen( $r ) < $riga ) $r = str_pad( $r, $riga, "\0" );
			if ( $t === 0 ) { $out .= $r; $prec = $r; continue; }
			if ( $t === 2 ) { $x = ''; for ( $i = 0; $i < $riga; $i++ ) $x .= chr( ( ord( $r[ $i ] ) + ord( $prec[ $i ] ) ) & 255 ); $out .= $x; $prec = $x; continue; }
			$x = $r;
			for ( $i = 0; $i < $riga; $i++ ) {
				$a = $i >= $bpp ? ord( $x[ $i - $bpp ] ) : 0; $bb = ord( $prec[ $i ] ); $cc = $i >= $bpp ? ord( $prec[ $i - $bpp ] ) : 0;
				if ( $t === 1 ) $v = $a;
				elseif ( $t === 3 ) $v = ( $a + $bb ) >> 1;
				else { $pp = $a + $bb - $cc; $pa = abs( $pp - $a ); $pb = abs( $pp - $bb ); $pc = abs( $pp - $cc ); $v = ( $pa <= $pb && $pa <= $pc ) ? $a : ( $pb <= $pc ? $bb : $cc ); }
				$x[ $i ] = chr( ( ord( $r[ $i ] ) + $v ) & 255 );
			}
			$out .= $x; $prec = $x;
		}
		return $out;
	}

	/** Dai punti grezzi al grigio di lavoro: riduzione col MINIMO del blocco (le righe sottili non spariscono). */
	private static function da_grezzo( string $d, int $w, int $h, int $col, int $bpc, int $riga, bool $inverti ): array {
		$k = max( 1, (int) ceil( $w / self::LARGH_MAX ) );
		$W = intdiv( $w, $k ); $H = intdiv( $h, $k );
		$px = str_repeat( "\xFF", $W * $H );
		for ( $y = 0; $y < $H * $k; $y++ ) {
			$base = $y * $riga; $Y = intdiv( $y, $k ) * $W;
			for ( $x = 0; $x < $W * $k; $x++ ) {
				if ( $bpc === 1 ) $v = ( ord( $d[ $base + ( $x >> 3 ) ] ) >> ( 7 - ( $x & 7 ) ) & 1 ) ? 255 : 0;
				elseif ( $col === 1 ) $v = ord( $d[ $base + $x ] );
				elseif ( $col === 3 ) { $o = $base + 3 * $x; $v = ( 30 * ord( $d[ $o ] ) + 59 * ord( $d[ $o + 1 ] ) + 11 * ord( $d[ $o + 2 ] ) ) / 100; }
				else { $o = $base + 4 * $x; $v = 255 - min( 255, ( 30 * ord( $d[ $o ] ) + 59 * ord( $d[ $o + 1 ] ) + 11 * ord( $d[ $o + 2 ] ) ) / 100 + ord( $d[ $o + 3 ] ) ); }
				if ( $inverti ) $v = 255 - $v;
				$i = $Y + intdiv( $x, $k );
				if ( $v < ord( $px[ $i ] ) ) $px[ $i ] = chr( (int) $v );
			}
		}
		return array( 'w' => $W, 'h' => $H, 'px' => $px, 'w0' => $w, 'h0' => $h );
	}

	private static function da_gd( $gd, int $w0, int $h0 ): array {
		$w = imagesx( $gd ); $h = imagesy( $gd );
		if ( $w > self::LARGH_MAX ) {
			$nh = (int) round( $h * self::LARGH_MAX / $w );
			$r = imagecreatetruecolor( self::LARGH_MAX, $nh );
			imagecopyresampled( $r, $gd, 0, 0, 0, 0, self::LARGH_MAX, $nh, $w, $h );
			$gd = $r; $w = self::LARGH_MAX; $h = $nh;   // niente imagedestroy(): deprecata da PHP 8.5
		}
		$tc = imageistruecolor( $gd );
		$px = str_repeat( "\xFF", $w * $h );
		for ( $y = 0; $y < $h; $y++ ) {
			$o = $y * $w;
			for ( $x = 0; $x < $w; $x++ ) {
				$c = imagecolorat( $gd, $x, $y );
				if ( ! $tc ) { $q = imagecolorsforindex( $gd, $c ); $px[ $o + $x ] = chr( (int) ( ( 30 * $q['red'] + 59 * $q['green'] + 11 * $q['blue'] ) / 100 ) ); }
				else $px[ $o + $x ] = chr( (int) ( ( 30 * ( ( $c >> 16 ) & 255 ) + 59 * ( ( $c >> 8 ) & 255 ) + 11 * ( $c & 255 ) ) / 100 ) );
			}
		}
		unset( $gd );
		return array( 'w' => $w, 'h' => $h, 'px' => $px, 'w0' => max( $w0, $w ), 'h0' => max( $h0, $h ) );
	}

	/** La pagina girata di 0 · 90 · 180 · 270 gradi (in senso orario). */
	public static function gira( array $im, int $giro ): array {
		if ( $giro === 0 ) return $im;
		if ( $giro === 180 ) return array( 'px' => strrev( $im['px'] ) ) + $im;
		$w = $im['w']; $h = $im['h']; $s = $im['px']; $o = str_repeat( "\xFF", $w * $h );
		// orario: (x, y) → (h-1-y, x) ; antiorario: (x, y) → (y, w-1-x)   — nuova larghezza h
		for ( $y = 0; $y < $h; $y++ ) for ( $x = 0; $x < $w; $x++ ) {
			$v = $s[ $y * $w + $x ];
			if ( $giro === 90 ) $o[ $x * $h + ( $h - 1 - $y ) ] = $v; else $o[ ( $w - 1 - $x ) * $h + $y ] = $v;
		}
		return array( 'w' => $h, 'h' => $w, 'px' => $o ) + $im;
	}

	/* ---------- le righe, l inchiostro, la somiglianza ---------- */

	/**
	 * Le due righe «Firma» e il segno sopra ciascuna. @return array{righe:int,firme:array,griglie:array}
	 */
	public static function misura( array $im, array $riq ): array {
		$px = $im['px'];
		// la carta: il 90° percentile di un campione della pagina; la riga stampata (grigia) è sotto il 72 %, l inchiostro sotto il 58 %
		$cmp = array(); for ( $i = 0, $n = strlen( $px ); $i < $n; $i += 97 ) $cmp[] = ord( $px[ $i ] );
		sort( $cmp ); $carta = max( 120, $cmp[ (int) ( count( $cmp ) * 0.9 ) ] ?? 255 );
		$soglia_riga = (int) ( $carta * 0.72 ); $scuro = (int) ( $carta * 0.58 );
		$out = array( 'righe' => 0, 'firme' => array(), 'griglie' => array() );
		$pend = null;   // la pendenza della pagina (scansione storta): si cerca sulla prima riga, la seconda la riusa
		foreach ( $riq as $r ) {
			$riga = self::trova_riga( $im, $r, $soglia_riga, $pend );
			if ( ! $riga ) return $out;
			$pend = $riga['pendenza'];
			$out['righe']++;
			list( $f, $gr ) = self::inchiostro( $im, $r, $riga, $scuro );
			$out['firme'][] = $f; $out['griglie'][] = $gr;
		}
		return $out;
	}

	/**
	 * La riga «Firma» vicina alla posizione attesa, anche storta (fino a ±2°).
	 * @return array{y:float,x0:int,x1:int,spessore:int,pendenza:float}|null  y = cima della riga in x0; pendenza = punti in giù per punto a destra.
	 */
	private static function trova_riga( array $im, array $r, int $soglia, ?float $pend ): ?array {
		$W = $im['w']; $H = $im['h']; $px = $im['px'];
		$x0 = $r[0] / self::PW * $W; $x1 = $r[2] / self::PW * $W; $y = ( self::PH - ( $r[1] - 1 ) ) / self::PH * $H;
		$lung = $x1 - $x0; $passo = 2;
		$ca = max( 0, (int) ( $x0 - 0.05 * $W ) ); $cz = min( $W - 1, (int) ( $x1 + 0.03 * $W ) );
		$ya = max( 1, (int) ( $y - 0.06 * $H ) ); $yz = min( $H - 2, (int) ( $y + 0.06 * $H ) );
		$pendenze = array();
		if ( $pend === null ) for ( $k = -9; $k <= 9; $k++ ) $pendenze[] = $k * 0.004;
		else $pendenze = array( $pend - 0.002, $pend, $pend + 0.002 );
		$migliore = array();   // riga => [corsa, inizio, pendenza]
		foreach ( $pendenze as $s ) {
			for ( $yy = $ya; $yy <= $yz; $yy += 2 ) {
				$best = 0; $bs = 0; $run = 0; $rs = 0; $buco = 0;
				for ( $xx = $ca; $xx <= $cz; $xx += $passo ) {
					$yr = (int) round( $yy + $s * ( $xx - $x0 ) );
					if ( $yr < 1 || $yr >= $H - 1 ) { $run = 0; continue; }
					$o = $yr * $W + $xx;
					$d = ord( $px[ $o ] ) < $soglia || ord( $px[ $o - $W ] ) < $soglia || ord( $px[ $o + $W ] ) < $soglia;
					if ( $d ) { if ( $run === 0 ) $rs = $xx; $run += $passo * ( 1 + $buco ); $buco = 0; if ( $run > $best ) { $best = $run; $bs = $rs; } }
					elseif ( $run > 0 && $buco < 3 ) $buco++;
					else { $run = 0; $buco = 0; }
				}
				if ( $best >= 0.6 * $lung && abs( $bs - $x0 ) <= 0.035 * $W && $best > ( $migliore[ $yy ][0] ?? 0 ) ) $migliore[ $yy ] = array( $best, $bs, $s );
			}
		}
		if ( ! $migliore ) return null;
		ksort( $migliore );
		// i gruppi di righe vicine: si sceglie quello col centro più vicino all atteso, e nel gruppo la corsa più lunga
		$gruppi = array(); $cur = null;
		foreach ( $migliore as $yy => $e ) {
			if ( $cur && $yy <= $cur['b'] + 4 ) { $cur['b'] = $yy; if ( $e[0] > $cur['e'][0] ) { $cur['e'] = $e; $cur['y'] = $yy; } }
			else { if ( $cur ) $gruppi[] = $cur; $cur = array( 'a' => $yy, 'b' => $yy, 'e' => $e, 'y' => $yy ); }
		}
		$gruppi[] = $cur;
		usort( $gruppi, static function ( $p, $q ) use ( $y ) { return abs( ( $p['a'] + $p['b'] ) / 2 - $y ) <=> abs( ( $q['a'] + $q['b'] ) / 2 - $y ); } );
		$g = $gruppi[0]; $s = $g['e'][2];
		// la cima e il fondo veri della riga lungo la pendenza trovata (punti scuri a metà riga)
		$xm = (int) ( $g['e'][1] + $g['e'][0] / 2 ); $cima = $g['y']; $fondo = $g['y'];
		$yc = (int) round( $g['y'] + $s * ( $xm - $x0 ) );
		for ( $k = 0; $k < 12 && $yc - $k - 1 > 0 && ord( $px[ ( $yc - $k - 1 ) * $W + $xm ] ) < $soglia; $k++ ) $cima--;
		for ( $k = 0; $k < 12 && $yc + $k + 1 < $H && ord( $px[ ( $yc + $k + 1 ) * $W + $xm ] ) < $soglia; $k++ ) $fondo++;
		return array( 'y' => (float) $cima, 'x0' => (int) $g['e'][1], 'x1' => (int) ( $g['e'][1] + $g['e'][0] ), 'spessore' => max( 2, $fondo - $cima + 1 ), 'pendenza' => $s, 'xr' => $x0 );
	}

	/** Il segno sopra la riga (e un poco sotto), seguendo la pendenza: presenza, misure e la griglia della forma. */
	private static function inchiostro( array $im, array $r, array $riga, int $scuro ): array {
		$W = $im['w']; $H = $im['h']; $px = $im['px'];
		$alto = (int) ( ( $r[3] - $r[1] ) / self::PH * $H * 0.92 );
		$sotto = (int) ( $alto * 0.25 );
		$xa = $riga['x0'] + 3; $xz = $riga['x1'] - 3; $sp = $riga['spessore'];
		$tot = 0; $cx = array(); $cy = array(); $punti = array();
		for ( $xx = $xa; $xx <= $xz; $xx++ ) {
			$base = (int) round( $riga['y'] + $riga['pendenza'] * ( $xx - $riga['xr'] ) );
			for ( $d = -$alto; $d <= $sp + $sotto; $d++ ) {
				if ( $d >= -2 && $d <= $sp + 1 ) continue;   // la riga stampata non è inchiostro della firma
				$yy = $base + $d; if ( $yy < 0 || $yy >= $H ) continue;
				if ( ord( $px[ $yy * $W + $xx ] ) >= $scuro ) continue;
				$tot++; $cx[ $xx ] = ( $cx[ $xx ] ?? 0 ) + 1; $cy[ $d ] = ( $cy[ $d ] ?? 0 ) + 1; $punti[] = array( $xx, $d );
			}
		}
		// il riquadro del segno: colonne e righe con almeno 2 punti d inchiostro (il rumore isolato non conta)
		$cols = array_keys( array_filter( $cx, static function ( $v ) { return $v >= 2; } ) );
		$rows = array_keys( array_filter( $cy, static function ( $v ) { return $v >= 2; } ) );
		$area = max( 1, ( $xz - $xa + 1 ) * ( $alto + $sotto ) );
		$lw = max( 1, $riga['x1'] - $riga['x0'] );
		$bw = $cols ? max( $cols ) - min( $cols ) + 1 : 0; $bh = $rows ? max( $rows ) - min( $rows ) + 1 : 0;
		$f = array( 'inchiostro' => round( $tot / $area, 4 ), 'larghezza' => round( $bw / $lw, 3 ), 'altezza' => round( $bh / max( 1, $alto + $sotto ), 3 ), 'punti' => $tot );
		$f['presente'] = $f['inchiostro'] >= 0.004 && $f['larghezza'] >= 0.12 && $f['altezza'] >= 0.12;
		// la griglia della forma (densità nel riquadro del segno, sfumata)
		$g = array_fill( 0, self::GRIGLIA_W * self::GRIGLIA_H, 0.0 );
		if ( $bw > 0 && $bh > 0 ) {
			$x0 = min( $cols ); $y0 = min( $rows );
			foreach ( $punti as $p ) {
				$gx = (int) ( ( $p[0] - $x0 ) * self::GRIGLIA_W / ( $bw + 1 ) ); $gy = (int) ( ( $p[1] - $y0 ) * self::GRIGLIA_H / ( $bh + 1 ) );
				if ( $gx >= 0 && $gx < self::GRIGLIA_W && $gy >= 0 && $gy < self::GRIGLIA_H ) $g[ $gy * self::GRIGLIA_W + $gx ] += 1.0;
			}
			$g = self::sfuma( self::sfuma( $g ) );
		}
		return array( $f, $g );
	}

	private static function sfuma( array $g ): array {
		$o = $g; $W = self::GRIGLIA_W; $H = self::GRIGLIA_H;
		for ( $y = 0; $y < $H; $y++ ) for ( $x = 0; $x < $W; $x++ ) {
			$s = 0.0; $n = 0;
			for ( $dy = -1; $dy <= 1; $dy++ ) for ( $dx = -1; $dx <= 1; $dx++ ) { $X = $x + $dx; $Y = $y + $dy; if ( $X < 0 || $Y < 0 || $X >= $W || $Y >= $H ) continue; $s += $g[ $Y * $W + $X ]; $n++; }
			$o[ $y * $W + $x ] = $s / $n;
		}
		return $o;
	}

	/** Le due firme si somigliano? ['motivo' => '' se sì, 'forma' => correlazione, …]. */
	public static function simili( array $a, array $z, array $griglie ): array {
		$fr = static function ( $p, $q ) { $p = max( 1e-6, (float) $p ); $q = max( 1e-6, (float) $q ); return max( $p / $q, $q / $p ); };
		$r = array( 'larghezza' => round( $fr( $a['larghezza'], $z['larghezza'] ), 2 ), 'altezza' => round( $fr( $a['altezza'], $z['altezza'] ), 2 ),
			'inchiostro' => round( $fr( $a['punti'], $z['punti'] ), 2 ), 'forma' => round( self::correlazione( $griglie[0] ?? array(), $griglie[1] ?? array() ), 2 ), 'motivo' => '' );
		if ( $r['larghezza'] > self::SIMILI_FATTORE ) $r['motivo'] = 'una è ' . self::volte( $r['larghezza'] ) . ' più lunga dell\'altra';
		elseif ( $r['altezza'] > self::SIMILI_FATTORE ) $r['motivo'] = 'una è ' . self::volte( $r['altezza'] ) . ' più alta dell\'altra';
		elseif ( $r['inchiostro'] > self::SIMILI_FATTORE ) $r['motivo'] = 'una ha ' . self::volte( $r['inchiostro'] ) . ' l\'inchiostro dell\'altra';
		elseif ( $r['forma'] < self::SIMILI_FORMA ) $r['motivo'] = 'la forma non si somiglia';
		return $r;
	}

	private static function volte( float $x ): string { return str_replace( '.', ',', (string) round( $x, 1 ) ) . ' volte'; }

	private static function correlazione( array $p, array $q ): float {
		$n = min( count( $p ), count( $q ) ); if ( $n === 0 ) return 0.0;
		$mp = array_sum( $p ) / $n; $mq = array_sum( $q ) / $n; $s = 0.0; $sp = 0.0; $sq = 0.0;
		for ( $i = 0; $i < $n; $i++ ) { $a = $p[ $i ] - $mp; $b = $q[ $i ] - $mq; $s += $a * $b; $sp += $a * $a; $sq += $b * $b; }
		return ( $sp <= 0 || $sq <= 0 ) ? 0.0 : $s / sqrt( $sp * $sq );
	}

	/* ---------- S617_B269B: la firma inserita al computer come immagine ---------- */

	/** La firma inserita: il contratto generato, un immagine con un segno su ciascuno spazio, i due segni simili. */
	private static function inserita( string $b, array $g, array $riq, array $pos, array &$m ): string {
		$m['tipo'] = 'inserita';
		$id = Rdacademy_License_Firma::id_documento( $b );
		$stesso = $id !== '' && hash_equals( strtolower( (string) ( $g['id'] ?? '' ) ), $id );
		if ( ! $stesso && ! self::porta_codice( $b, (string) ( $g['codice_testo'] ?? '' ) ) )
			return 'La firma è inserita in un PDF che non è il contratto generato da RelDoxAcademy (il codice del documento è diverso): inserisci la firma nel contratto scaricato da questa pagina, senza cambiarne il testo, oppure stampalo, firmalo a mano e allega la scansione.';
		$ogg = Rdacademy_License_Firma::oggetti( $b );
		$nomi = array( 'la firma del contratto (primo spazio «Firma»)', 'la firma dell\'approvazione specifica (secondo spazio «Firma»)' );
		$firme = array(); $griglie = array(); $usate = array(); $mancano = array(); $dpi = 0;
		foreach ( $riq as $i => $r ) {
			$z = array( $r[0] - 20, $r[1] - 25, $r[2] + 20, $r[3] + 60 );   // lo spazio e il margine dove si appoggia una firma
			$scelta = null; $meglio = 0.0;
			foreach ( $pos as $k => $p ) {
				if ( isset( $usate[ $k ] ) ) continue;
				$in = self::interseca( $p['bbox'], $z );
				if ( $in > $meglio && $in >= 0.2 * max( 1e-6, $p['area'] ) ) { $meglio = $in; $scelta = $k; }
			}
			if ( $scelta === null ) { $mancano[] = $nomi[ $i ]; $firme[] = array( 'presente' => false ); $griglie[] = array(); continue; }
			$usate[ $scelta ] = 1;
			$p = $pos[ $scelta ];
			$err = '';
			$im = self::con_trasparenza( $ogg, (string) ( $ogg[ $p['n'] ] ?? '' ), $err );
			if ( $im === null ) return 'L\'immagine della firma nel ' . ( $i ? 'secondo' : 'primo' ) . ' spazio non si legge' . ( $err !== '' ? ' (' . rtrim( $err, '.' ) . ')' : '' ) . '. ' . self::RIFAI_PDF;
			$c = $p['ctm'];
			if ( $c[3] < 0 ) $im = self::capovolgi( $im, true );
			if ( $c[0] < 0 ) $im = self::capovolgi( $im, false );
			$wp = $p['bbox'][2] - $p['bbox'][0]; $hp = $p['bbox'][3] - $p['bbox'][1];
			if ( $wp > 0 ) $dpi = max( $dpi, (int) round( $im['w0'] / ( $wp / 72.0 ) ) );
			list( $f, $gr ) = self::segno( $im, $wp, $hp, $r );
			$firme[] = $f; $griglie[] = $gr;
			if ( ! $f['presente'] ) $mancano[] = $nomi[ $i ];
		}
		$m['dpi'] = $dpi; $m['firme'] = $firme;
		if ( $mancano ) return 'Nel PDF manca ' . implode( ' e ', $mancano ) . ': sopra ogni spazio «Firma» va inserita l\'immagine della firma per esteso. ' . self::RIFAI_PDF;
		$sim = self::simili( $firme[0], $firme[1], $griglie );
		$m['somiglianza'] = $sim;
		if ( $sim['motivo'] !== '' ) return 'Le due firme sono troppo diverse fra loro (' . $sim['motivo'] . '): le due firme le appone la stessa persona, per esteso, nello stesso modo. ' . self::RIFAI_PDF;
		return '';
	}

	/** Il codice di controllo del testo (le prime 16 cifre) sta nel contenuto di una pagina? (PDF riscritto da un programma che cambia /ID) */
	private static function porta_codice( string $b, string $codice ): bool {
		if ( strlen( $codice ) < 16 ) return false;
		$ago = strtolower( substr( $codice, 0, 16 ) );
		foreach ( Rdacademy_License_Firma::oggetti( $b ) as $c ) {
			if ( strpos( (string) $c, 'stream' ) === false ) continue;
			$s = self::flusso( array(), (string) $c );
			if ( $s !== null && stripos( $s, $ago ) !== false ) return true;
		}
		return false;
	}

	/** Area dell intersezione di due rettangoli [x0, y0, x1, y1]. */
	private static function interseca( array $a, array $z ): float {
		$w = min( $a[2], $z[2] ) - max( $a[0], $z[0] ); $h = min( $a[3], $z[3] ) - max( $a[1], $z[1] );
		return ( $w > 0 && $h > 0 ) ? $w * $h : 0.0;
	}

	/** Uno stream di contenuto decodificato (nessun filtro o FlateDecode); null se non si sa leggere. */
	private static function flusso( array $ogg, string $c ): ?string {
		$s = strpos( $c, 'stream' ); if ( $s === false ) return null;
		$dict = substr( $c, 0, $s );
		$dati = self::grezzo( $ogg, $c );
		if ( preg_match( '/\/Filter\s*\[\s*\/FlateDecode\s*\]|\/Filter\s*\/FlateDecode\b/', $dict ) ) return self::inflate( $dati );
		if ( preg_match( '/\/Filter\b/', $dict ) ) return null;
		return $dati;
	}

	/** I riferimenti «n g R» di un valore (diretto o in un oggetto-array). */
	private static function riferimenti( array $ogg, string $c, string $chiave ): array {
		$v = '';
		if ( preg_match( '/\/' . $chiave . '\s*\[([^\]]*)\]/', $c, $m ) ) $v = $m[1];
		elseif ( preg_match( '/\/' . $chiave . '\s+(\d+)\s+\d+\s+R/', $c, $m ) ) {
			$o = (string) ( $ogg[ (int) $m[1] ] ?? '' );
			if ( strpos( $o, 'stream' ) !== false || preg_match( '/\/Type\s*\/Annot\b/', $o ) ) return array( (int) $m[1] );
			$v = preg_match( '/\[([^\]]*)\]/', $o, $mm ) ? $mm[1] : '';
		}
		preg_match_all( '/(\d+)\s+\d+\s+R/', $v, $mr );
		return array_map( 'intval', $mr[1] );
	}

	/** Le immagini disegnate sull ultima pagina (contenuto, moduli, timbri): [ ['n' => oggetto, 'bbox' => [x0,y0,x1,y1], 'area', 'ctm'] ]. */
	public static function posizionate( string $b ): array {
		$ogg = Rdacademy_License_Firma::oggetti( $b );
		$pag = self::pagine( $ogg );
		if ( ! $pag ) return array();
		$np = end( $pag ); $c = (string) ( $ogg[ $np ] ?? '' );
		$res = self::risolvi_dict( $ogg, $c, 'Resources', $ogg, $np );
		$out = array(); $testo = '';
		foreach ( self::riferimenti( $ogg, $c, 'Contents' ) as $k ) { $t = self::flusso( $ogg, (string) ( $ogg[ $k ] ?? '' ) ); if ( $t !== null ) $testo .= $t . "\n"; }
		self::disegna( $ogg, $testo, $res, array( 1, 0, 0, 1, 0, 0 ), $out, 0 );
		// i timbri: annotazioni con un aspetto (non i campi, non i collegamenti)
		foreach ( self::riferimenti( $ogg, $c, 'Annots' ) as $k ) {
			$a = (string) ( $ogg[ $k ] ?? '' );
			if ( preg_match( '/\/Subtype\s*\/(Widget|Link|Popup)\b/', $a ) ) continue;
			if ( ! preg_match( '/\/Rect\s*\[\s*([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s*\]/', $a, $mr ) ) continue;
			$ap = self::risolvi_dict( $ogg, $a, 'AP' );
			if ( ! preg_match( '/\/N\s+(\d+)\s+\d+\s+R/', $ap, $mn ) ) continue;
			$f = (string) ( $ogg[ (int) $mn[1] ] ?? '' );
			$fd = substr( $f, 0, (int) strpos( $f, 'stream' ) );
			if ( ! preg_match( '/\/BBox\s*\[\s*([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s*\]/', $fd, $mb ) ) continue;
			$M = self::matrice( $fd );
			$R = array( min( (float) $mr[1], (float) $mr[3] ), min( (float) $mr[2], (float) $mr[4] ), max( (float) $mr[1], (float) $mr[3] ), max( (float) $mr[2], (float) $mr[4] ) );
			$T = self::scatola( array( (float) $mb[1], (float) $mb[2], (float) $mb[3], (float) $mb[4] ), $M );
			$sx = ( $R[2] - $R[0] ) / max( 1e-6, $T[2] - $T[0] ); $sy = ( $R[3] - $R[1] ) / max( 1e-6, $T[3] - $T[1] );
			$A = array( $sx, 0, 0, $sy, $R[0] - $T[0] * $sx, $R[1] - $T[1] * $sy );
			$t = self::flusso( $ogg, $f );
			if ( $t === null ) continue;
			$fr = self::risolvi_dict( $ogg, $fd, 'Resources' );
			self::disegna( $ogg, $t, $fr !== '' ? $fr : $res, self::per( $M, $A ), $out, 1 );
		}
		return $out;
	}

	/** /Matrix di un modulo (identità se manca). */
	private static function matrice( string $d ): array {
		if ( preg_match( '/\/Matrix\s*\[\s*([\d.\-eE+]+)\s+([\d.\-eE+]+)\s+([\d.\-eE+]+)\s+([\d.\-eE+]+)\s+([\d.\-eE+]+)\s+([\d.\-eE+]+)\s*\]/', $d, $m ) )
			return array( (float) $m[1], (float) $m[2], (float) $m[3], (float) $m[4], (float) $m[5], (float) $m[6] );
		return array( 1, 0, 0, 1, 0, 0 );
	}

	/** M × N (matrici PDF [a b c d e f]). */
	private static function per( array $m, array $n ): array {
		return array( $m[0] * $n[0] + $m[1] * $n[2], $m[0] * $n[1] + $m[1] * $n[3], $m[2] * $n[0] + $m[3] * $n[2], $m[2] * $n[1] + $m[3] * $n[3],
			$m[4] * $n[0] + $m[5] * $n[2] + $n[4], $m[4] * $n[1] + $m[5] * $n[3] + $n[5] );
	}

	/** Il rettangolo che contiene il rettangolo r trasformato da m. */
	private static function scatola( array $r, array $m ): array {
		$xs = array(); $ys = array();
		foreach ( array( array( $r[0], $r[1] ), array( $r[2], $r[1] ), array( $r[0], $r[3] ), array( $r[2], $r[3] ) ) as $p ) {
			$xs[] = $m[0] * $p[0] + $m[2] * $p[1] + $m[4]; $ys[] = $m[1] * $p[0] + $m[3] * $p[1] + $m[5];
		}
		return array( min( $xs ), min( $ys ), max( $xs ), max( $ys ) );
	}

	/** Esegue un contenuto e annota dove finiscono le immagini (q Q cm Do; i moduli si seguono fino a 4 livelli). */
	private static function disegna( array $ogg, string $s, string $res, array $ctm, array &$out, int $prof ): void {
		$n = strlen( $s ); $i = 0; $num = array(); $nome = ''; $pila = array();
		$xo = null;
		while ( $i < $n ) {
			$ch = $s[ $i ];
			if ( ctype_space( $ch ) ) { $i++; continue; }
			if ( $ch === '%' ) { while ( $i < $n && $s[ $i ] !== "\n" && $s[ $i ] !== "\r" ) $i++; continue; }
			if ( $ch === '(' ) { $d = 0; for ( ; $i < $n; $i++ ) { if ( $s[ $i ] === '\\' ) { $i++; continue; } if ( $s[ $i ] === '(' ) $d++; elseif ( $s[ $i ] === ')' && --$d === 0 ) { $i++; break; } } continue; }
			if ( $ch === '<' && ( $s[ $i + 1 ] ?? '' ) === '<' ) { $d = 0; for ( ; $i < $n - 1; $i++ ) { if ( $s[ $i ] === '<' && $s[ $i + 1 ] === '<' ) { $d++; $i++; } elseif ( $s[ $i ] === '>' && $s[ $i + 1 ] === '>' ) { $i++; if ( --$d === 0 ) { $i++; break; } } } continue; }
			if ( $ch === '<' ) { $e = strpos( $s, '>', $i ); $i = $e === false ? $n : $e + 1; continue; }
			if ( $ch === '[' || $ch === ']' || $ch === '{' || $ch === '}' ) { $i++; continue; }
			if ( $ch === '/' ) { $j = $i + 1; while ( $j < $n && strpos( " \t\r\n\f\0/[]<>(){}%", $s[ $j ] ) === false ) $j++; $nome = substr( $s, $i + 1, $j - $i - 1 ); $i = $j; continue; }
			if ( preg_match( '/\G[+\-]?(\d+\.?\d*|\.\d+)/', $s, $mm, 0, $i ) ) { $num[] = (float) $mm[0]; $i += strlen( $mm[0] ); continue; }
			$j = $i; while ( $j < $n && strpos( " \t\r\n\f\0/[]<>(){}%", $s[ $j ] ) === false ) $j++;
			if ( $j === $i ) { $i++; continue; }
			$op = substr( $s, $i, $j - $i ); $i = $j;
			if ( $op === 'q' ) $pila[] = $ctm;
			elseif ( $op === 'Q' ) { if ( $pila ) $ctm = array_pop( $pila ); }
			elseif ( $op === 'cm' && count( $num ) >= 6 ) $ctm = self::per( array_slice( $num, -6 ), $ctm );
			elseif ( $op === 'BI' ) { $e = preg_match( '/\sEI(?=[\s]|$)/', $s, $me, PREG_OFFSET_CAPTURE, $i ) ? $me[0][1] + 3 : $n; $i = $e; }
			elseif ( $op === 'Do' && $nome !== '' ) {
				if ( $xo === null ) $xo = self::risolvi_dict( $ogg, $res, 'XObject' );
				if ( preg_match( '/\/' . preg_quote( $nome, '/' ) . '\s+(\d+)\s+\d+\s+R/', $xo, $mr ) ) {
					$k = (int) $mr[1]; $c = (string) ( $ogg[ $k ] ?? '' );
					$d = substr( $c, 0, (int) strpos( $c, 'stream' ) );
					if ( preg_match( '/\/Subtype\s*\/Image\b/', $d ) ) {
						$bb = self::scatola( array( 0, 0, 1, 1 ), $ctm );
						$out[] = array( 'n' => $k, 'bbox' => $bb, 'area' => ( $bb[2] - $bb[0] ) * ( $bb[3] - $bb[1] ), 'ctm' => $ctm );
					} elseif ( preg_match( '/\/Subtype\s*\/Form\b/', $d ) && $prof < 4 ) {
						$t = self::flusso( $ogg, $c );
						$fr = self::risolvi_dict( $ogg, $d, 'Resources' );
						if ( $t !== null ) self::disegna( $ogg, $t, $fr !== '' ? $fr : $res, self::per( self::matrice( $d ), $ctm ), $out, $prof + 1 );
					}
				}
			}
			$num = array(); $nome = '';
		}
	}

	/** L immagine in grigio con la sua trasparenza (/SMask): dove è trasparente è carta. */
	private static function con_trasparenza( array $ogg, string $c, string &$errore ): ?array {
		$im = self::decodifica( $ogg, $c, $errore );
		if ( $im === null ) return null;
		$d = substr( $c, 0, (int) strpos( $c, 'stream' ) );
		if ( ! preg_match( '/\/SMask\s+(\d+)\s+\d+\s+R/', $d, $ms ) ) return $im;
		$e = '';
		$a = self::decodifica( $ogg, (string) ( $ogg[ (int) $ms[1] ] ?? '' ), $e );
		if ( $a === null ) return $im;
		$px = $im['px']; $W = $im['w']; $H = $im['h'];
		for ( $y = 0; $y < $H; $y++ ) {
			$ay = min( $a['h'] - 1, intdiv( $y * $a['h'], max( 1, $H ) ) );
			for ( $x = 0; $x < $W; $x++ ) {
				$ax = min( $a['w'] - 1, intdiv( $x * $a['w'], max( 1, $W ) ) );
				$al = ord( $a['px'][ $ay * $a['w'] + $ax ] );
				if ( $al < 128 ) $px[ $y * $W + $x ] = "\xFF";
				elseif ( $al < 255 ) { $v = ord( $px[ $y * $W + $x ] ); $px[ $y * $W + $x ] = chr( (int) ( 255 - ( 255 - $v ) * $al / 255 ) ); }
			}
		}
		$im['px'] = $px;
		return $im;
	}

	/** Capovolge l immagine (in verticale o in orizzontale). */
	private static function capovolgi( array $im, bool $verticale ): array {
		$W = $im['w']; $H = $im['h']; $s = $im['px']; $o = '';
		if ( $verticale ) { for ( $y = $H - 1; $y >= 0; $y-- ) $o .= substr( $s, $y * $W, $W ); }
		else { for ( $y = 0; $y < $H; $y++ ) $o .= strrev( substr( $s, $y * $W, $W ) ); }
		$im['px'] = $o;
		return $im;
	}

	/** Il segno di un immagine appoggiata: presenza, misure (in proporzione allo spazio di firma) e la griglia della forma. */
	private static function segno( array $im, float $wp, float $hp, array $r ): array {
		$W = $im['w']; $H = $im['h']; $px = $im['px'];
		$cmp = array(); for ( $i = 0, $n = strlen( $px ); $i < $n; $i += 13 ) $cmp[] = ord( $px[ $i ] );
		sort( $cmp ); $carta = max( 120, $cmp[ (int) ( count( $cmp ) * 0.9 ) ] ?? 255 ); $scuro = (int) ( $carta * 0.7 );
		$tot = 0; $cx = array(); $cy = array(); $punti = array();
		for ( $y = 0; $y < $H; $y++ ) for ( $x = 0; $x < $W; $x++ ) {
			if ( ord( $px[ $y * $W + $x ] ) >= $scuro ) continue;
			$tot++; $cx[ $x ] = ( $cx[ $x ] ?? 0 ) + 1; $cy[ $y ] = ( $cy[ $y ] ?? 0 ) + 1; $punti[] = array( $x, $y );
		}
		$cols = array_keys( array_filter( $cx, static function ( $v ) { return $v >= 2; } ) );
		$rows = array_keys( array_filter( $cy, static function ( $v ) { return $v >= 2; } ) );
		$bw = $cols ? max( $cols ) - min( $cols ) + 1 : 0; $bh = $rows ? max( $rows ) - min( $rows ) + 1 : 0;
		$sx = $wp / max( 1, $W ); $sy = $hp / max( 1, $H );
		$rw = max( 1e-6, $r[2] - $r[0] ); $rh = max( 1e-6, ( $r[3] - $r[1] ) * 1.25 );
		$f = array( 'inchiostro' => round( $tot / max( 1, $W * $H ), 4 ), 'larghezza' => round( $bw * $sx / $rw, 3 ), 'altezza' => round( $bh * $sy / $rh, 3 ), 'punti' => round( $tot * $sx * $sy, 2 ) );
		$f['presente'] = $f['inchiostro'] >= 0.004 && $f['larghezza'] >= 0.12 && $f['altezza'] >= 0.12;
		$g = array_fill( 0, self::GRIGLIA_W * self::GRIGLIA_H, 0.0 );
		if ( $bw > 0 && $bh > 0 ) {
			$x0 = min( $cols ); $y0 = min( $rows );
			foreach ( $punti as $p ) {
				$gx = (int) ( ( $p[0] - $x0 ) * self::GRIGLIA_W / ( $bw + 1 ) ); $gy = (int) ( ( $p[1] - $y0 ) * self::GRIGLIA_H / ( $bh + 1 ) );
				if ( $gx >= 0 && $gx < self::GRIGLIA_W && $gy >= 0 && $gy < self::GRIGLIA_H ) $g[ $gy * self::GRIGLIA_W + $gx ] += 1.0;
			}
			$g = self::sfuma( self::sfuma( $g ) );
		}
		return array( $f, $g );
	}

	/** Le parole della verifica superata, per la pagina, lo storico e la mail. */
	public static function riassunto( array $m ): string {
		if ( ( $m['modalita'] ?? '' ) === 'digitale' ) return 'due firme digitali dello stesso firmatario: ' . (string) ( $m['firmatario'] ?? '' );
		$s = (array) ( $m['somiglianza'] ?? array() );
		if ( ( $m['tipo'] ?? '' ) === 'inserita' )
			return 'firma inserita come immagine nel PDF: firme presenti nei due spazi e simili fra loro (forma ' . str_replace( '.', ',', (string) ( $s['forma'] ?? '' ) ) . ') — controllo di massima, non certifica l\'autenticità; resta da spedire l\'originale cartaceo';
		return 'scansione a ' . (int) ( $m['dpi'] ?? 0 ) . ' dpi: firme presenti nei due spazi e simili fra loro (forma ' . str_replace( '.', ',', (string) ( $s['forma'] ?? '' ) ) . ') — controllo di massima, non certifica l\'autenticità';
	}
}
