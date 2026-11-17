<?php
/**
 * Rdacademy_Cifra — IL SEGRETO A RIPOSO, CON IL SUO PREFISSO.
 * =============================================================================
 * Copiato da Reldox_DataSource::encrypt_pass() / decrypt_pass() (RelDox CRD
 * 3.55.09G, «Cifratura credenziali, opzione A»: AES-256-CBC con AUTH_KEY e i
 * primi 16 caratteri di AUTH_SALT, risultato in base64) e dalla busta `enc1:`
 * con cui Reldox_AI::chiave() conserva in `impostazioni` la chiave cifrata
 * (const CIFRA = 'enc1:'): il prefisso distingue un valore cifrato da uno
 * rimasto in chiaro, e una riga in chiaro si riconosce invece di essere
 * decifrata a vuoto.
 *
 * DICHIARATO (lotto C): e il metodo che RelDox usa per le sorgenti dati e la
 * busta che RelDox usa nelle impostazioni. Il lotto C lo usa per la password
 * della casella di servizio (`mail_servizio_password`); il lotto D lo riusa
 * per il registro delle sorgenti. Un posto solo, nominato una volta.
 *
 * DIFFERENZE DA RELDOX, DICHIARATE:
 *  - il vettore d inizializzazione si porta a 16 byte con str_pad: in RelDox
 *    `substr( AUTH_SALT, 0, 16 )` su un sale corto (palco: 2 caratteri) fa
 *    emettere a openssl un avviso «IV passed is only N bytes long» a ogni
 *    cifratura. Il valore cifrato resta identico a quello di RelDox (openssl
 *    completa con NUL, str_pad con NUL fa lo stesso).
 *  - NUOVO: `forma()` dice di un valore SOLO presenza e forma, mai il segreto
 *    (REGOLE § 5: «delle credenziali si mostrano solo presenza e forma»).
 *
 * ⛔ DIFETTO DI RELDOX CONSERVATO E DICHIARATO NEL REFERTO: il vettore e fisso
 * (deriva dal sale), quindi due password uguali danno lo stesso cifrato. Si
 * copia per restare leggibili con le righe di RelDox; la correzione (IV
 * casuale per valore, prefisso `enc2:`) e una decisione di famiglia.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Cifra {

	/** La busta. Copiata da Reldox_AI::CIFRA. */
	const PREFISSO = 'enc1:';

	private static function iv(): string {
		$sale = defined( 'AUTH_SALT' ) ? (string) AUTH_SALT : '';
		return str_pad( substr( $sale, 0, 16 ), 16, "\0" );
	}

	private static function chiave(): string {
		return defined( 'AUTH_KEY' ) ? (string) AUTH_KEY : '';
	}

	/** Copiato da Reldox_DataSource::encrypt_pass(): stringa vuota se l ingresso e vuoto. */
	public static function cifra( string $plain ): string {
		if ( $plain === '' || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}
		$enc = openssl_encrypt( $plain, 'AES-256-CBC', self::chiave(), 0, self::iv() );
		return ( $enc === false ) ? '' : base64_encode( $enc );
	}

	/** Copiato da Reldox_DataSource::decrypt_pass() (ripiego difensivo, lo stesso schema). */
	public static function decifra( string $enc ): string {
		if ( $enc === '' || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$raw = base64_decode( $enc, true );
		if ( $raw === false ) {
			return '';
		}
		$dec = openssl_decrypt( $raw, 'AES-256-CBC', self::chiave(), 0, self::iv() );
		return ( $dec === false ) ? '' : $dec;
	}

	/** Il valore da scrivere sul BASE: busta + cifrato. Vuoto se non si e potuto cifrare. */
	public static function chiudi( string $plain ): string {
		$c = self::cifra( $plain );
		return $c === '' ? '' : self::PREFISSO . $c;
	}

	/**
	 * Il valore in chiaro, SOLO per l uso (es. l invio SMTP). Una riga senza
	 * busta e un valore rimasto in chiaro: si restituisce com e, come fa
	 * Reldox_AI::chiave() con le chiavi anteriori alla cifratura.
	 */
	public static function apri( string $stored ): string {
		if ( strpos( $stored, self::PREFISSO ) === 0 ) {
			return self::decifra( substr( $stored, strlen( self::PREFISSO ) ) );
		}
		return $stored;
	}

	public static function e_cifrato( string $stored ): bool {
		return strpos( $stored, self::PREFISSO ) === 0;
	}

	/** NUOVO — presenza e forma, mai il segreto. */
	public static function forma( string $stored ): array {
		return array(
			'presente' => ( $stored !== '' ),
			'cifrata'  => self::e_cifrato( $stored ),
		);
	}
}
