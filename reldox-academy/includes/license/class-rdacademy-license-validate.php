<?php
// F023_LICENZA — copiato da RelDox CRD 3.76.03G (`class-reldox-license-validate.php`) con banco/F023-porta-licenza.py: non si cambia a mano.
/**
 * Rdacademy_License_Validate — validatori PURI per l'anagrafica intestatario licenza.
 *
 * Nessun DB, nessuna funzione WordPress: logica di formato verificabile in isolamento
 * (banco golden Strumento 31). Restituiscono sempre array ['ok'=>bool,'error'=>string].
 * Campo vuoto = ammesso (non si impone l'obbligo di compilazione); si valida SOLO il
 * formato quando un valore e' presente.
 *
 * Coperto:
 *  - P.IVA italiana: 11 cifre + check-digit (algoritmo Luhn ministeriale).
 *  - Codice Fiscale persona fisica: 16 caratteri, formato (con omocodia) + carattere di controllo.
 *  - CAP italiano: 5 cifre.
 *  - Sigla provincia: 2 lettere contro l'elenco corrente delle targhe automobilistiche.
 *
 * @package Rdacademy_CRD
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'RDACADEMY_LICENSE_VALIDATE_CLI' ) ) {
	exit;
}

if ( ! class_exists( 'Rdacademy_License_Validate' ) ) :

final class Rdacademy_License_Validate {

	/** Sigle province italiane correnti (targhe automobilistiche). */
	const PROVINCE = [
		'AG','AL','AN','AO','AP','AQ','AR','AT','AV','BA','BG','BI','BL','BN','BO',
		'BR','BS','BT','BZ','CA','CB','CE','CH','CL','CN','CO','CR','CS','CT','CZ',
		'EN','FC','FE','FG','FI','FM','FR','GE','GO','GR','IM','IS','KR','LC','LE',
		'LI','LO','LT','LU','MB','MC','ME','MI','MN','MO','MS','MT','NA','NO','NU',
		'OR','PA','PC','PD','PE','PG','PI','PN','PO','PR','PT','PU','PV','PZ','RA',
		'RC','RE','RG','RI','RM','RN','RO','SA','SI','SO','SP','SR','SS','SU','SV',
		'TA','TE','TN','TO','TP','TR','TS','TV','UD','VA','VB','VC','VE','VI','VR',
		'VT','VV',
	];

	/** Tabella valori caratteri in posizione DISPARI per il check-digit del CF. */
	const CF_ODD = [
		'0'=>1,'1'=>0,'2'=>5,'3'=>7,'4'=>9,'5'=>13,'6'=>15,'7'=>17,'8'=>19,'9'=>21,
		'A'=>1,'B'=>0,'C'=>5,'D'=>7,'E'=>9,'F'=>13,'G'=>15,'H'=>17,'I'=>19,'J'=>21,
		'K'=>2,'L'=>4,'M'=>18,'N'=>20,'O'=>11,'P'=>3,'Q'=>6,'R'=>8,'S'=>12,'T'=>14,
		'U'=>16,'V'=>10,'W'=>22,'X'=>25,'Y'=>24,'Z'=>23,
	];

	/** Tabella valori caratteri in posizione PARI per il check-digit del CF. */
	const CF_EVEN = [
		'0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
		'A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,
		'K'=>10,'L'=>11,'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,
		'U'=>20,'V'=>21,'W'=>22,'X'=>23,'Y'=>24,'Z'=>25,
	];

	private static function ok(): array {
		return [ 'ok' => true, 'error' => '' ];
	}

	private static function ko( string $error ): array {
		return [ 'ok' => false, 'error' => $error ];
	}

	/**
	 * P.IVA italiana: 11 cifre + check-digit.
	 * @param string $raw valore grezzo (spazi e prefisso IT tollerati).
	 */
	public static function piva( string $raw ): array {
		$v = strtoupper( trim( $raw ) );
		if ( '' === $v ) {
			return self::ok();
		}
		// Tollera prefisso paese IT e separatori comuni.
		if ( 0 === strpos( $v, 'IT' ) ) {
			$v = substr( $v, 2 );
		}
		$v = preg_replace( '/[\s\.\-]/', '', $v );
		if ( ! preg_match( '/^[0-9]{11}$/', (string) $v ) ) {
			return self::ko( 'La P.IVA deve essere composta da 11 cifre numeriche.' );
		}
		$sum = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$n = (int) $v[ $i ];
			if ( 0 === $i % 2 ) {
				$sum += $n;
			} else {
				$d = $n * 2;
				if ( $d > 9 ) {
					$d -= 9;
				}
				$sum += $d;
			}
		}
		$check = ( 10 - ( $sum % 10 ) ) % 10;
		if ( $check !== (int) $v[10] ) {
			return self::ko( 'La P.IVA non e valida (cifra di controllo errata).' );
		}
		return self::ok();
	}

	/**
	 * Codice Fiscale persona fisica: 16 caratteri, formato + carattere di controllo.
	 * @param string $raw valore grezzo.
	 */
	public static function codice_fiscale( string $raw ): array {
		$v = strtoupper( trim( $raw ) );
		if ( '' === $v ) {
			return self::ok();
		}
		$v = preg_replace( '/\s/', '', $v );
		// Formato con omocodia: le cifre possono essere lettere L,M,N,P,Q,R,S,T,U,V.
		$omo = '[0-9LMNPQRSTUV]';
		$re  = '/^[A-Z]{6}' . $omo . '{2}[ABCDEHLMPRST]' . $omo . '{2}[A-Z]' . $omo . '{3}[A-Z]$/';
		if ( ! preg_match( $re, (string) $v ) ) {
			return self::ko( 'Il Codice Fiscale non e nel formato valido (16 caratteri, persona fisica).' );
		}
		$sum = 0;
		for ( $i = 0; $i < 15; $i++ ) {
			$c = $v[ $i ];
			// Posizione 1-based: dispari quando l'indice 0-based e' pari.
			if ( 0 === $i % 2 ) {
				$sum += self::CF_ODD[ $c ] ?? 0;
			} else {
				$sum += self::CF_EVEN[ $c ] ?? 0;
			}
		}
		$expected = chr( ( $sum % 26 ) + 65 ); // 0 -> A ... 25 -> Z
		if ( $expected !== $v[15] ) {
			return self::ko( 'Il Codice Fiscale non e valido (carattere di controllo errato).' );
		}
		return self::ok();
	}

	/**
	 * CAP italiano: 5 cifre.
	 * @param string $raw valore grezzo.
	 */
	public static function cap( string $raw ): array {
		$v = trim( $raw );
		if ( '' === $v ) {
			return self::ok();
		}
		if ( ! preg_match( '/^[0-9]{5}$/', $v ) ) {
			return self::ko( 'Il CAP italiano deve essere composto da 5 cifre.' );
		}
		return self::ok();
	}

	/**
	 * Sigla provincia italiana: 2 lettere presenti nell'elenco corrente.
	 * @param string $raw valore grezzo.
	 */
	public static function provincia( string $raw ): array {
		$v = strtoupper( trim( $raw ) );
		if ( '' === $v ) {
			return self::ok();
		}
		if ( ! preg_match( '/^[A-Z]{2}$/', $v ) ) {
			return self::ko( 'La provincia deve essere una sigla di 2 lettere (es. MI, RM).' );
		}
		if ( ! in_array( $v, self::PROVINCE, true ) ) {
			return self::ko( 'Sigla provincia non riconosciuta (' . $v . ').' );
		}
		return self::ok();
	}

	/**
	 * Ritorna true se la nazione indicata e' Italia (varie grafie tollerate).
	 * Guida l'applicazione condizionale delle regole CAP/provincia.
	 * @param string $raw valore nazione.
	 */
	public static function is_italia( string $raw ): bool {
		$v = strtolower( trim( $raw ) );
		if ( '' === $v ) {
			return true; // default: intestatario italiano.
		}
		return in_array( $v, [ 'italia', 'italy', 'it', 'ita' ], true );
	}
}

endif;
