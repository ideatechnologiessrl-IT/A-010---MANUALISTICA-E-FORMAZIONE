<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-license-firma.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Rdacademy_License_Firma — LA LETTURA DEL CONTRATTO FIRMATO (1.3.1B, SPEC-FIRMA-LICENZA-RELDOX-v1 § 5).
 * =============================================================================
 * Un lettore minimo di PDF, senza librerie: quanto serve per dire se il file allegato è il contratto generato da questa
 * installazione e se porta le due firme nei due campi predisposti. NON verifica i certificati (catena, validità,
 * qualificazione): lo fa Idea Technologies alla ricezione col suo programma di verifica (§ 5 A).
 *
 * CHE COSA LEGGE
 *  - gli oggetti indiretti «n g obj … endobj», nell ordine del file: un oggetto ridefinito da un aggiornamento incrementale
 *    (le firme PAdES si aggiungono in coda) vale nella sua ultima definizione;
 *  - gli oggetti dentro gli stream di oggetti (/Type /ObjStm, FlateDecode), che i programmi di firma usano spesso;
 *  - il primo elemento di /ID dell ultimo trailer (o dell ultimo dizionario di xref a stream);
 *  - i campi firma (/FT /Sig) col loro nome /T e il valore /V; i dizionari di firma (/ByteRange e /Contents);
 *  - le pagine (/Type /Page).
 *
 * I CONTROLLI (in quest ordine, il primo che fallisce dà il motivo — § 5 A):
 *  1. tipo dai primi byte (%PDF-); una busta CMS (.p7m) è rifiutata col suo motivo;
 *  2. almeno due firme vere, una nel campo Firma_Contratto e una in Firma_Approvazione_Specifica;
 *  3. è il nostro contratto: per byte (il file comincia coi byte del generato) o per /ID (file riscritto);
 *  4. l ultima firma copre il file fino in fondo: niente aggiunto dopo.
 *
 * @package RelDox CRD
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_License_Firma {

	const CAMPO_CONTRATTO     = 'Firma_Contratto';
	const CAMPO_APPROVAZIONE  = 'Firma_Approvazione_Specifica';

	/** Una busta CMS / PKCS#7 (il .p7m): DER (SEQUENCE) o base64 o PEM. */
	public static function e_busta_cms( string $testa ): bool {
		if ( $testa === '' ) return false;
		if ( ord( $testa[0] ) === 0x30 && in_array( ord( $testa[1] ?? "\0" ), array( 0x80, 0x81, 0x82, 0x83, 0x84 ), true ) ) return true;
		return strncmp( $testa, '-----BEGIN', 10 ) === 0 || strncmp( $testa, 'MII', 3 ) === 0 || strncmp( $testa, 'MIA', 3 ) === 0;
	}

	/**
	 * Gli oggetti del file: numero => corpo (senza «n g obj» e «endobj»); l ultima definizione vince.
	 * @return array<int,string>
	 */
	public static function oggetti( string $b ): array {
		$out = array();
		$n = strlen( $b );
		if ( ! preg_match_all( '/(?<![0-9])(\d{1,10})\s+(\d{1,5})\s+obj\b/', $b, $m, PREG_OFFSET_CAPTURE ) ) return $out;
		$pos = array();
		$limite = 0;   // un «n g obj» che cade dentro l oggetto precedente (byte di uno stream) non è un oggetto
		foreach ( $m[0] as $i => $tutto ) {
			if ( $tutto[1] < $limite ) continue;
			$num   = (int) $m[1][ $i ][0];
			$da    = $tutto[1] + strlen( $tutto[0] );
			$fine  = strpos( $b, 'endobj', $da );
			if ( $fine === false ) $fine = $n;
			$st = strpos( $b, 'stream', $da );
			if ( $st !== false && $st < $fine ) {
				// lo stream può contenere «endobj» fra i suoi byte: si salta fino a «endstream»
				$es = strpos( $b, 'endstream', $st + 6 );
				if ( $es !== false ) { $f2 = strpos( $b, 'endobj', $es ); $fine = $f2 === false ? $n : $f2; }
			}
			$out[ $num ] = substr( $b, $da, $fine - $da );
			$pos[ $num ] = $tutto[1];
			$limite = $fine;
		}
		// gli stream di oggetti: i loro oggetti valgono se il file non li ridefinisce come oggetti diretti dopo
		foreach ( $out as $num => $corpo ) {
			if ( ! preg_match( '/\/Type\s*\/ObjStm\b/', $corpo ) ) continue;
			$dati = self::stream( $corpo );
			if ( $dati === null ) continue;
			$first = preg_match( '/\/First\s+(\d+)/', $corpo, $mf ) ? (int) $mf[1] : 0;
			$quanti = preg_match( '/\/N\s+(\d+)/', $corpo, $mn ) ? (int) $mn[1] : 0;
			$testa = preg_split( '/\s+/', trim( substr( $dati, 0, $first ) ) );
			$coppie = array();
			for ( $k = 0; $k + 1 < count( $testa ) && count( $coppie ) < $quanti; $k += 2 ) $coppie[] = array( (int) $testa[ $k ], (int) $testa[ $k + 1 ] );
			foreach ( $coppie as $j => $c ) {
				$a = $first + $c[1];
				$z = isset( $coppie[ $j + 1 ] ) ? $first + $coppie[ $j + 1 ][1] : strlen( $dati );
				if ( ! isset( $pos[ $c[0] ] ) || $pos[ $c[0] ] < $pos[ $num ] ) { $out[ $c[0] ] = substr( $dati, $a, max( 0, $z - $a ) ); $pos[ $c[0] ] = $pos[ $num ]; }
			}
		}
		return $out;
	}

	/** I byte di uno stream (FlateDecode o senza filtro); null se il filtro non si sa leggere. */
	public static function stream( string $corpo ): ?string {
		$s = strpos( $corpo, 'stream' );
		if ( $s === false ) return null;
		$s += 6;
		if ( substr( $corpo, $s, 2 ) === "\r\n" ) $s += 2; elseif ( ( $corpo[ $s ] ?? '' ) === "\n" || ( $corpo[ $s ] ?? '' ) === "\r" ) $s += 1;
		$e = strrpos( $corpo, 'endstream' );
		if ( $e === false || $e < $s ) return null;
		$dati = rtrim( substr( $corpo, $s, $e - $s ), "\r\n" );
		$dict = substr( $corpo, 0, $s );
		if ( preg_match( '/\/Filter\s*\[?\s*\/(\w+)/', $dict, $mf ) ) {
			if ( $mf[1] !== 'FlateDecode' ) return null;
			$x = @gzuncompress( $dati );
			if ( $x === false ) $x = @gzinflate( substr( $dati, 2 ) );
			return $x === false ? null : $x;
		}
		return $dati;
	}

	/** Il primo elemento di /ID dell ultimo trailer, in esadecimale minuscolo ('' se manca). */
	public static function id_documento( string $b ): string {
		if ( ! preg_match_all( '/\/ID\s*\[\s*(<[0-9A-Fa-f\s]*>|\((?:\\\\.|[^\\\\)])*\))/', $b, $m ) ) return '';
		$ultimo = end( $m[1] );
		if ( $ultimo[0] === '<' ) return strtolower( (string) preg_replace( '/[^0-9A-Fa-f]/', '', $ultimo ) );
		return bin2hex( stripcslashes( substr( $ultimo, 1, -1 ) ) );
	}

	/** Una stringa PDF (letterale o esadecimale) in testo; i nomi UTF-16 con BOM si convertono. */
	private static function stringa( string $s ): string {
		if ( $s === '' ) return '';
		if ( $s[0] === '<' ) $v = (string) hex2bin( (string) preg_replace( '/[^0-9A-Fa-f]/', '', substr( $s, 1, -1 ) ) . ( strlen( preg_replace( '/[^0-9A-Fa-f]/', '', $s ) ) % 2 ? '0' : '' ) );
		else $v = stripcslashes( substr( $s, 1, -1 ) );
		if ( strncmp( $v, "\xFE\xFF", 2 ) === 0 ) $v = (string) mb_convert_encoding( substr( $v, 2 ), 'UTF-8', 'UTF-16BE' );
		return $v;
	}

	/** Un dizionario di firma: /ByteRange [a b c d] e /Contents non vuoto. null se non lo è. */
	private static function firma_da( string $corpo, string $b ): ?array {
		if ( ! preg_match( '/\/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/', $corpo, $br ) ) return null;
		if ( ! preg_match( '/\/Contents\s*<([0-9A-Fa-f\s]*)>/', $corpo, $ct ) ) return null;
		$hex = rtrim( (string) preg_replace( '/\s/', '', $ct[1] ), '0' );
		$r = array( (int) $br[1], (int) $br[2], (int) $br[3], (int) $br[4] );
		$n = strlen( $b );
		// il buco del ByteRange è esattamente il valore di /Contents <…> nel file
		$buco_ok = $r[0] === 0 && $r[1] > 0 && $r[2] > $r[1] && ( $b[ $r[1] ] ?? '' ) === '<' && ( $b[ $r[2] - 1 ] ?? '' ) === '>' && $r[2] + $r[3] <= $n;
		return array( 'byterange' => $r, 'firmata' => strlen( $hex ) >= 64, 'buco_ok' => $buco_ok, 'fine' => $r[2] + $r[3],
			'tipo' => preg_match( '/\/Type\s*\/DocTimeStamp\b/', $corpo ) ? 'marca' : 'firma' );
	}

	/**
	 * L analisi del file. @return array{pdf:bool,cms:bool,id:string,pagine:int,firme:array,campi:array,fine_ok:bool,ultima_fine:int,byte:int}
	 *  firme = i dizionari di firma veri (con contenuto); campi = nome del campo => ['firmato' => bool].
	 */
	public static function analizza( string $b ): array {
		$out = array( 'pdf' => strncmp( $b, '%PDF-', 5 ) === 0, 'cms' => self::e_busta_cms( substr( $b, 0, 16 ) ), 'id' => '', 'pagine' => 0,
			'firme' => array(), 'campi' => array(), 'fine_ok' => false, 'ultima_fine' => 0, 'byte' => strlen( $b ) );
		if ( ! $out['pdf'] ) return $out;
		$out['id'] = self::id_documento( $b );
		$ogg = self::oggetti( $b );
		$firme = array();
		foreach ( $ogg as $num => $corpo ) {
			if ( preg_match( '/\/Type\s*\/Page\b(?!s)/', $corpo ) ) $out['pagine']++;
			if ( preg_match( '/\/ByteRange\s*\[/', $corpo ) && preg_match( '/\/Contents\s*</', $corpo ) ) {
				$f = self::firma_da( $corpo, $b );
				if ( $f && $f['tipo'] === 'firma' ) $firme[ $num ] = $f;
			}
		}
		foreach ( $ogg as $num => $corpo ) {
			if ( ! preg_match( '/\/FT\s*\/Sig\b/', $corpo ) ) continue;
			if ( ! preg_match( '/\/T\s*(\((?:\\\\.|[^\\\\)])*\)|<[0-9A-Fa-f\s]*>)/', $corpo, $mt ) ) continue;
			$nome = self::stringa( $mt[1] );
			$val = null;
			if ( preg_match( '/\/V\s+(\d+)\s+(\d+)\s+R/', $corpo, $mv ) ) $val = $firme[ (int) $mv[1] ] ?? null;
			elseif ( preg_match( '/\/V\s*<</', $corpo ) ) $val = self::firma_da( $corpo, $b );
			$out['campi'][ $nome ] = array( 'firmato' => $val !== null && $val['firmata'] && $val['buco_ok'] );
		}
		foreach ( $firme as $f ) {
			if ( $f['firmata'] && $f['buco_ok'] ) $out['firme'][] = $f;
			$out['ultima_fine'] = max( $out['ultima_fine'], $f['fine'] );
		}
		// niente dopo l ultima firma: al più un a capo finale
		$out['fine_ok'] = $out['firme'] !== array() && trim( substr( $b, $out['ultima_fine'] ), "\r\n" ) === '';
		return $out;
	}
}
