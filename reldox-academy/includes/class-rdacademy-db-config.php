<?php
/**
 * Classe Rdacademy_DB_Config
 *
 * Copiato da Reldox_DB_Config, RelDox CRD 3.55.09G, senza tagli: e generico.
 *
 * Lettore credenziali DB con logica di lookup wp-config.php → fallback hardcoded.
 *
 * Introdotta in pkg6-db-config (v3.4.0) per chiudere il debito tecnico §12.4
 * dell'analisi funzionale: spostamento delle credenziali DB dal sorgente del
 * plugin a wp-config.php, con strategia di migrazione in tre stadi (§18.3).
 *
 * Per ogni costante RDACADEMY_DB_* la logica è:
 *   1. se definita PRIMA del caricamento del plugin (= in wp-config.php),
 *      vince la definizione di wp-config;
 *   2. altrimenti viene usato il fallback hardcoded di reldox-academy.php.
 *
 * Il flag 'source' nel risultato indica l'origine complessiva:
 *   - 'wp-config' : tutte le costanti vengono da wp-config.php
 *   - 'mixed'     : alcune da wp-config, altre dal fallback
 *   - 'fallback'  : tutte dal fallback (stato pre-migrazione)
 *
 * Lo stato 'mixed' NON è un errore ma una migrazione parziale.
 *
 * ARCHITETTURA — per rilevare l'origine di ogni singola costante NON basta
 * leggere il valore (le costanti sono già definite a questo punto, sia che
 * vengano da wp-config sia dal fallback). Per discriminare l'origine ci
 * affidiamo al fatto che `reldox-academy.php` salva la lista delle costanti che
 * ha effettivamente *dovuto* definire lui stesso (cioè non erano già definite
 * da wp-config) in `$GLOBALS['rdacademy_db_defaults_applied']`.
 *
 * @since 3.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_DB_Config {

	/**
	 * Mappatura nome-campo → nome-costante.
	 * L'ordine determina l'ordine di iterazione nel detection di 'source'.
	 */
	private const FIELD_MAP = [
		'host'    => 'RDACADEMY_DB_HOST',
		'name'    => 'RDACADEMY_DB_NAME',
		'user'    => 'RDACADEMY_DB_USER',
		'pass'    => 'RDACADEMY_DB_PASS',
		'charset' => 'RDACADEMY_DB_CHARSET',
	];

	/**
	 * Ritorna la configurazione DB corrente con indicazione dell'origine.
	 *
	 * @return array{
	 *   host: string,
	 *   name: string,
	 *   user: string,
	 *   pass: string,
	 *   charset: string,
	 *   source: 'wp-config'|'mixed'|'fallback',
	 *   per_field_source: array<string, 'wp-config'|'fallback'>
	 * }
	 */
	public static function get(): array {

		$config           = [];
		$per_field_source = [];
		$from_wpconfig    = 0;
		$from_fallback    = 0;

		// Lista delle costanti che reldox-academy.php ha dovuto definire come
		// fallback (perché non erano già definite da wp-config.php).
		// Vedi reldox-academy.php — sezione fallback DB.
		$fallback_applied = isset( $GLOBALS['rdacademy_db_defaults_applied'] ) && is_array( $GLOBALS['rdacademy_db_defaults_applied'] )
			? $GLOBALS['rdacademy_db_defaults_applied']
			: [];

		foreach ( self::FIELD_MAP as $field => $constant ) {

			$value = defined( $constant ) ? constant( $constant ) : '';

			// Se la costante NON è nella lista dei fallback applicati, allora
			// è stata definita da wp-config.php (o da chi ha caricato prima
			// del plugin); altrimenti viene dal fallback hardcoded.
			$origin = in_array( $constant, $fallback_applied, true )
				? 'fallback'
				: 'wp-config';

			$config[ $field ]           = (string) $value;
			$per_field_source[ $field ] = $origin;

			if ( $origin === 'wp-config' ) {
				$from_wpconfig++;
			} else {
				$from_fallback++;
			}
		}

		// Sintesi 'source':
		// - tutti wp-config → wp-config
		// - tutti fallback  → fallback
		// - misto           → mixed
		if ( $from_fallback === 0 ) {
			$source = 'wp-config';
		} elseif ( $from_wpconfig === 0 ) {
			$source = 'fallback';
		} else {
			$source = 'mixed';
		}

		$config['source']           = $source;
		$config['per_field_source'] = $per_field_source;

		return $config;
	}

	/**
	 * Versione "pubblica" della config: rimuove il campo `pass` per uso
	 * nel response degli endpoint AJAX di diagnostica.
	 *
	 * La password non transita MAI nel response di dbconfig_status (test D5).
	 *
	 * @return array Stessa struttura di get() senza il campo `pass`.
	 */
	public static function get_public(): array {
		$config = self::get();
		unset( $config['pass'] );
		// Rimuoviamo anche `pass` da per_field_source per coerenza
		if ( isset( $config['per_field_source']['pass'] ) ) {
			unset( $config['per_field_source']['pass'] );
		}
		return $config;
	}

	/**
	 * Helper per il rendering del blocco copia/incolla da inserire in
	 * wp-config.php. Include la password REALE (non placeholder).
	 *
	 * Va usato SOLO lato server (la pagina admin chiama questo per generare
	 * il payload del bottone "Copia negli appunti"). La password NON viene
	 * mai mandata al DOM: è il server che gestisce direttamente la copia
	 * via response dedicato.
	 *
	 * @return string Blocco PHP da copiare in wp-config.php
	 */
	public static function render_wpconfig_block(): string {

		$config = self::get();

		// Escape per stringa PHP single-quoted: solo backslash e apice singolo.
		$esc = static function ( string $v ): string {
			return str_replace( [ '\\', "'" ], [ '\\\\', "\\'" ], $v );
		};

		$lines = [
			"/* RelDoxAcademy — credenziali database */",
			"define( 'RDACADEMY_DB_HOST',    '" . $esc( $config['host'] )    . "' );",
			"define( 'RDACADEMY_DB_NAME',    '" . $esc( $config['name'] )    . "' );",
			"define( 'RDACADEMY_DB_USER',    '" . $esc( $config['user'] )    . "' );",
			"define( 'RDACADEMY_DB_PASS',    '" . $esc( $config['pass'] )    . "' );",
			"define( 'RDACADEMY_DB_CHARSET', '" . $esc( $config['charset'] ) . "' );",
		];

		return implode( "\n", $lines );
	}
}
