<?php
/**
 * Rdacademy_Dimensioni — LO SCHEMA DELLE DIMENSIONI: soglie e quote, in un posto solo.
 * ===========================================================================
 * Copiato da Reldox_Dimensioni (BUG-29 punto 6), RelDox CRD 3.55.09G, e ridotto.
 *
 * PERCHÉ ESISTE (copiato): due pannelli che classificano lo stesso numero con
 * due scale diverse mentono entrambi. Le soglie del semaforo e la lettura
 * della quota acquistata stanno qui; il pannello «Capacità database» del BASE
 * e di ogni sorgente le chiede qui.
 *
 * DOVE VIVONO LE QUOTE (copiato): nella tabella `impostazioni` del BASE, chiavi
 * `db_quota_mb` (BASE) e `db_quota_mb_{sorgente}`. Un database irraggiungibile
 * non deve poter far sparire la propria quota.
 *
 * TAGLI: filesystem() (peso dei blob, gancio di dimensionamento del CRM) e
 * misura() (giro di tutte le sorgenti per il banco di carico del CRM).
 * ⛔ CORRETTO: quota_mb() leggeva con real_escape_string concatenato: preparata.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Dimensioni {

	const SOGLIA_AMBRA   = 70.0;
	const SOGLIA_ARANCIO = 85.0;
	const SOGLIA_ROSSO   = 95.0;

	/** Default per singolo database, quando la quota non è configurata (copiato: 1000 MB). */
	const QUOTA_DEFAULT_MB = 1000;

	/** Stato semaforo a partire dalla percentuale di occupazione (copiato). */
	public static function stato( float $pct ): array {
		if ( $pct > self::SOGLIA_ROSSO ) {
			return array( 'state' => 'red',    'label' => 'Saturazione critica' );
		}
		if ( $pct >= self::SOGLIA_ARANCIO ) {
			return array( 'state' => 'orange', 'label' => 'Attenzione' );
		}
		if ( $pct >= self::SOGLIA_AMBRA ) {
			return array( 'state' => 'amber',  'label' => 'Da monitorare' );
		}
		return array( 'state' => 'green', 'label' => 'Uso normale' );
	}

	public static function soglie(): array {
		return array( 'amber' => (int) self::SOGLIA_AMBRA, 'orange' => (int) self::SOGLIA_ARANCIO, 'red' => (int) self::SOGLIA_ROSSO );
	}

	/** Nome della chiave di quota (copiato: il BASE e ogni chiave sconosciuta → db_quota_mb). */
	public static function quota_key( string $source ): string {
		return ( $source === 'base' || ! Rdacademy_Sorgenti::esiste( $source ) ) ? 'db_quota_mb' : 'db_quota_mb_' . $source;
	}

	public static function quota_mb( string $source = 'base' ): int {
		$row = Rdacademy_DB::get_row( 'SELECT valore FROM `' . RDACADEMY_TABLE_PREFIX . 'impostazioni` WHERE chiave = ?', array( self::quota_key( $source ) ), 's' );
		if ( isset( $row['valore'] ) && (int) $row['valore'] > 0 ) {
			return (int) $row['valore'];
		}
		return self::QUOTA_DEFAULT_MB;
	}
}
