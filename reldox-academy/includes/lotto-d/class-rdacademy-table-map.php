<?php
/**
 * Rdacademy_Table_Map — MAPPA DEI NOMI TABELLA, per le query già scritte.
 * ==========================================================================
 * Copiato da Reldox_Table_Map (BUG-23 passo 3), RelDox CRD 3.55.09G, senza tagli.
 *
 * PERCHÉ ESISTE, in una riga (copiato): PHP non sa interpolare una chiamata
 * statica dentro una stringa. Questo oggetto ArrayAccess di sola lettura
 * interroga `Rdacademy_Table_Owner::t()` a ogni accesso:
 *
 *     $T   = Rdacademy_Table_Owner::map();
 *     $sql = "SELECT … FROM {$T['sorgente_3_esempio']} e JOIN {$T['users']} u …";
 *
 * Nessuna chiave «mancante» (il registro decide la RESIDENZA, non
 * l esistenza), sola lettura per costruzione, nessuno stato.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Table_Map implements ArrayAccess {

	private static ?Rdacademy_Table_Map $instance = null;

	private function __construct() {}

	public static function instance(): Rdacademy_Table_Map {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function offsetExists( mixed $offset ): bool {
		return is_string( $offset ) && '' !== Rdacademy_Table_Owner::short( $offset );
	}

	/** Nome pronto per la query, già fra backtick. */
	public function offsetGet( mixed $offset ): string {
		return Rdacademy_Table_Owner::t( (string) $offset );
	}

	/** @throws LogicException Sempre: la mappa è di sola lettura. */
	public function offsetSet( mixed $offset, mixed $value ): void {
		throw new LogicException( 'Rdacademy_Table_Map è di sola lettura: il nome di una tabella si dichiara nella mappa delle sorgenti, non si riscrive nel punto d uso.' );
	}

	/** @throws LogicException Sempre: la mappa è di sola lettura. */
	public function offsetUnset( mixed $offset ): void {
		throw new LogicException( 'Rdacademy_Table_Map è di sola lettura: non si rimuove una tabella dalla mappa.' );
	}
}
