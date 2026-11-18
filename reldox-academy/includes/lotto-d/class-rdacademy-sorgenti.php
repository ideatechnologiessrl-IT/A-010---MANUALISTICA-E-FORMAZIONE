<?php
/**
 * Rdacademy_Sorgenti — LA MAPPA DELLE SORGENTI 3-5, IN UN PUNTO SOLO.
 * =============================================================================
 * NUOVO. In RelDox CRD 3.55.09G le chiavi delle sorgenti erano scritte in
 * QUATTRO posti: Reldox_DataSource::KNOWN_KEYS, Reldox_Router::ds_known_keys()
 * (copia a mano, «VIOLAZIONE 54»), Reldox_Backup::sources() con le etichette
 * ('Anagrafica Offerte', 'Customer Service', 'Riserva') e la vista
 * impostazioni.php con una sezione HTML per sorgente. Ogni posto era del CRM.
 *
 * Il guscio non ha moduli: le chiavi sono GENERICHE e i nomi visibili stanno
 * qui, una volta. Il prodotto le rinomina o le specializza dal filtro
 * `rdacademy_sorgenti` (chiave => n, nome, ruolo) e dichiara le tabelle che nascono
 * su ciascuna dal filtro `rdacademy_sorgenti_tabelle` (chiave => [tabella nuda =>
 * funzione che la crea su una connessione]). Registro, resolver, provisioning,
 * backup, Panoramica e Stato sistema leggono da qui e da nessun altro posto.
 *
 * REGOLE-FAMIGLIA § 3.5: 1 WordPress (non si tocca), 2 BASE (verità), 3-5 sorgenti.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Sorgenti {

	/**
	 * Le tre sorgenti del guscio. `n` è il numero del disegno del server
	 * (3, 4, 5), `nome` la parola che vede l amministratore, `ruolo` la riga
	 * che la Panoramica scrive sotto il nome.
	 */
	const MAPPA = array(
		'sorgente_3' => array( 'n' => 3, 'nome' => 'Sorgente 3', 'ruolo' => 'funzione corposa del prodotto' ),
		'sorgente_4' => array( 'n' => 4, 'nome' => 'Sorgente 4', 'ruolo' => 'funzione corposa del prodotto' ),
		'riserva'    => array( 'n' => 5, 'nome' => 'Riserva',    'ruolo' => 'a disposizione' ),
	);

	/** Una chiave è una parola breve: è anche un nome di colonna e un pezzo di nome tabella. */
	const FORMA_CHIAVE = '/^[a-z][a-z0-9_]{1,23}$/';

	/** @var array<string,array>|null cache di richiesta */
	private static $mappa = null;

	/** @var array<string,array<string,callable>>|null */
	private static $tabelle = null;

	public static function flush(): void {
		self::$mappa   = null;
		self::$tabelle = null;
	}

	/**
	 * La mappa valida, estesa dal prodotto. Una voce malformata si scarta e
	 * si dichiara nel registro degli errori: non diventa una sorgente a metà.
	 *
	 * @return array<string,array{n:int,nome:string,ruolo:string}>
	 */
	public static function mappa(): array {
		if ( self::$mappa !== null ) {
			return self::$mappa;
		}
		$in  = (array) apply_filters( 'rdacademy_sorgenti', self::MAPPA );
		$out = array();
		$num = array();
		foreach ( $in as $k => $v ) {
			$k = (string) $k;
			if ( ! preg_match( self::FORMA_CHIAVE, $k ) || $k === 'base' || ! is_array( $v ) ) {
				error_log( '[RelDoxAcademy] sorgente scartata dalla mappa: chiave non valida «' . $k . '»' );
				continue;
			}
			$n = (int) ( $v['n'] ?? 0 );
			if ( $n < 3 || $n > 5 || isset( $num[ $n ] ) ) {
				error_log( '[RelDoxAcademy] sorgente scartata dalla mappa: numero ' . $n . ' non valido o doppio («' . $k . '»)' );
				continue;
			}
			$num[ $n ] = true;
			$out[ $k ] = array(
				'n'     => $n,
				'nome'  => (string) ( $v['nome'] ?? $k ),
				'ruolo' => (string) ( $v['ruolo'] ?? '' ),
			);
		}
		uasort( $out, static function ( $a, $b ) { return $a['n'] <=> $b['n']; } );
		self::$mappa = $out;
		return $out;
	}

	/** Le chiavi note (al posto di Reldox_DataSource::KNOWN_KEYS). */
	public static function chiavi(): array {
		return array_keys( self::mappa() );
	}

	public static function esiste( string $key ): bool {
		return isset( self::mappa()[ $key ] );
	}

	public static function nome( string $key ): string {
		return (string) ( self::mappa()[ $key ]['nome'] ?? $key );
	}

	public static function numero( string $key ): int {
		return (int) ( self::mappa()[ $key ]['n'] ?? 0 );
	}

	/**
	 * Le tabelle che nascono sul database di ciascuna sorgente quando la si
	 * accende. Nome NUDO (senza prefisso) => funzione( mysqli $conn, string $nome_completo ): bool.
	 * Una tabella dichiarata da due sorgenti appartiene alla prima e si
	 * dichiara: due case per la stessa tabella sono la radice di BUG-23 di RelDox.
	 *
	 * @return array<string,array<string,callable>>
	 */
	public static function tabelle(): array {
		if ( self::$tabelle !== null ) {
			return self::$tabelle;
		}
		$in   = (array) apply_filters( 'rdacademy_sorgenti_tabelle', array() );
		$out  = array();
		$visti = array();
		foreach ( self::chiavi() as $k ) {
			$out[ $k ] = array();
			foreach ( (array) ( $in[ $k ] ?? array() ) as $tab => $fn ) {
				$tab = strtolower( (string) $tab );
				if ( ! preg_match( '/^[a-z0-9_]{1,48}$/', $tab ) || ! is_callable( $fn ) ) {
					error_log( '[RelDoxAcademy] tabella di sorgente scartata: «' . $tab . '» (' . $k . ')' );
					continue;
				}
				if ( isset( $visti[ $tab ] ) ) {
					error_log( '[RelDoxAcademy] tabella «' . $tab . '» dichiarata da due sorgenti: resta a «' . $visti[ $tab ] . '»' );
					continue;
				}
				$visti[ $tab ]     = $k;
				$out[ $k ][ $tab ] = $fn;
			}
		}
		self::$tabelle = $out;
		return $out;
	}

	/** Le tabelle nude di una sorgente, nell ordine dichiarato. */
	public static function tabelle_di( string $key ): array {
		return array_keys( self::tabelle()[ $key ] ?? array() );
	}
}
