<?php
/**
 * RelDoxAcademy — dominio `Rdacademy_User_Prefs`
 * ---------------------------------------------------------------------
 * Copiato da Reldox_User_Prefs, RelDox CRD 3.55.09G (BUG-91).
 * LA PORTA UNICA sulle preferenze personali. Chi legge o scrive una
 * preferenza passa di qui e non tocca `rdacademy_user_prefs` con una query
 * propria (`RP-UNA-MANO-SOLA-SUL-COMANDO`).
 *
 * ⛔ L'IDENTITA' NON ARRIVA MAI DAL CHIAMANTE ESTERNO. Ogni metodo pretende
 * un `$uid` gia' risolto, e l'unico che lo risolve al runtime `/academy/` e'
 * `Rdacademy_Router::me_uid()`, che legge da `Rdacademy_Auth`
 * (`RP-IL-SELF-SERVICE-NON-TOCCA-UN-ALTRO`).
 *
 * TAGLIO: la chiave `albero_aziende` e i suoi aiutanti (aziende_ids,
 * normalizza_ids, aziende_risolte su `companies`) — sono del CRM.
 * NUOVO: il vocabolario delle chiavi si estende dal filtro
 * `rdacademy_user_prefs_chiavi` (ogni lotto dichiara le SUE chiavi, una volta).
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_User_Prefs {

	/**
	 * VOCABOLARIO DELLE CHIAVI — dichiarato qui e in nessun altro posto.
	 * Una chiave scritta a mano dal chiamante e' una chiave che domani
	 * qualcuno scrive con un carattere diverso e la preferenza sparisce in
	 * silenzio. `is_key()` e' la guardia.
	 */
	const K_TEMA = 'tema';   // NUOVO: chiave d esempio del guscio (chiaro | scuro | sistema)

	/** @return string[] */
	public static function chiavi(): array {
		$k = (array) apply_filters( 'rdacademy_user_prefs_chiavi', array( self::K_TEMA ) );
		return array_values( array_unique( array_map( 'strval', $k ) ) );
	}

	/** NUOVO — tutte le preferenze dell utente, chiave => valore decodificato. */
	public static function tutte( int $uid ): array {
		if ( $uid <= 0 ) {
			return array();
		}
		$p    = RDACADEMY_TABLE_PREFIX;
		$rows = Rdacademy_DB::get_results(
			"SELECT pref_key, pref_json FROM `{$p}user_prefs` WHERE user_id = ? ORDER BY pref_key",
			array( $uid ), 'i'
		);
		$out = array();
		foreach ( (array) $rows as $r ) {
			$k = (string) $r['pref_key'];
			if ( ! self::is_key( $k ) ) { continue; }
			$v = json_decode( (string) $r['pref_json'], true );
			if ( json_last_error() === JSON_ERROR_NONE ) { $out[ $k ] = $v; }
		}
		return $out;
	}

	public static function is_key( string $k ): bool {
		return in_array( $k, self::chiavi(), true );
	}

	/**
	 * Legge una preferenza. Ritorna il valore decodificato, oppure `$default`
	 * se la riga non c'e' o il JSON e' illeggibile.
	 *
	 * ⛔ JSON rotto NON e' un errore da propagare: e' una preferenza che non
	 * c'e'. L'alternativa — una eccezione — bloccherebbe l'apertura
	 * dell'albero per un dato di comodita'.
	 *
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get( int $uid, string $key, $default = null ) {
		if ( $uid <= 0 || ! self::is_key( $key ) ) {
			return $default;
		}
		$p   = RDACADEMY_TABLE_PREFIX;
		$row = Rdacademy_DB::get_row(
			"SELECT pref_json, updated_at FROM `{$p}user_prefs`
			  WHERE user_id = ? AND pref_key = ? LIMIT 1",
			array( $uid, $key ), 'is'
		);
		if ( ! $row || ! isset( $row['pref_json'] ) ) {
			return $default;
		}
		$v = json_decode( (string) $row['pref_json'], true );
		return ( json_last_error() === JSON_ERROR_NONE ) ? $v : $default;
	}

	/** Quando e' cambiata l'ultima volta. Stringa vuota se non c'e'. */
	public static function updated_at( int $uid, string $key ): string {
		if ( $uid <= 0 || ! self::is_key( $key ) ) {
			return '';
		}
		$p   = RDACADEMY_TABLE_PREFIX;
		$row = Rdacademy_DB::get_row(
			"SELECT updated_at FROM `{$p}user_prefs`
			  WHERE user_id = ? AND pref_key = ? LIMIT 1",
			array( $uid, $key ), 'is'
		);
		return $row ? (string) ( $row['updated_at'] ?? '' ) : '';
	}

	/**
	 * Scrive una preferenza in REPLACE — decisione D1 dell'owner, S273:
	 * «va in replace», il valore nuovo SOSTITUISCE quello vecchio e non ci si
	 * fonde.
	 *
	 * ⛔ `INSERT ... ON DUPLICATE KEY UPDATE` e non «leggi poi decidi»: due
	 * salvataggi vicini dallo stesso utente su due schede del browser
	 * lascerebbero due righe, e la lettura sceglierebbe a caso. L'unicita' la
	 * fa il database con `uniq_user_key`, non il PHP.
	 *
	 * @param mixed $value
	 */
	public static function set( int $uid, string $key, $value ): bool {
		if ( $uid <= 0 || ! self::is_key( $key ) ) {
			return false;
		}
		$json = wp_json_encode( $value );
		if ( ! is_string( $json ) ) {
			return false;
		}
		$p   = RDACADEMY_TABLE_PREFIX;
		$now = gmdate( 'Y-m-d H:i:s' ); // § 2.3: un orologio solo (in RelDox: current_time('mysql'), ora locale)
		Rdacademy_DB::execute(
			"INSERT INTO `{$p}user_prefs` (user_id, pref_key, pref_json, updated_at)
			      VALUES (?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE pref_json = VALUES(pref_json), updated_at = VALUES(updated_at)",
			array( $uid, $key, $json, $now ), 'isss'
		);
		return true;
	}

	/** Toglie la preferenza. La ✕ della card, quella della D2 dell'owner. */
	public static function del( int $uid, string $key ): bool {
		if ( $uid <= 0 || ! self::is_key( $key ) ) {
			return false;
		}
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::execute(
			"DELETE FROM `{$p}user_prefs` WHERE user_id = ? AND pref_key = ?",
			array( $uid, $key ), 'is'
		);
		return true;
	}

}

/*
 * NUOVO — validatore della chiave d esempio `tema`: elenco chiuso. Ogni lotto
 * che aggiunge una chiave puo agganciare `rdacademy_user_prefs_valida_<chiave>`.
 */
add_filter( 'rdacademy_user_prefs_valida_' . Rdacademy_User_Prefs::K_TEMA, static function ( $v ) {
	return in_array( $v, array( 'chiaro', 'scuro', 'sistema' ), true )
		? $v
		: new WP_Error( 'rdacademy_pref', 'Tema non riconosciuto: chiaro, scuro o sistema.' );
} );
