<?php
/**
 * Rdacademy_Schema_Flag_Guard — UN SEGNAPOSTO NON E' UNA PROVA.  BUG-53, 3.16.101.
 * Copiato da Reldox_Schema_Flag_Guard, RelDox CRD 3.55.09G. TAGLI: la mappa delle
 * classi schema CRM (sostituita dalla sola voce del nucleo) e il ramo FUORI_BASE
 * di Reldox_Table_Owner. Il ramo resta nel codice: il lotto D lo riaccende.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * IL DIFETTO, IN UNA RIGA. Ventuno classi di schema decidono se lavorare
 * guardando un segnaposto: se dice «gia' fatto» escono in testa e non
 * controllano mai che cio' che dichiarano esista davvero. Se la tabella non
 * c'e' — reset parziale, ripristino, una tabella spostata o rinominata da uno
 * Strumento — il prodotto RISPONDE FATTO e non ci torna piu'. Non e' una
 * funzione che manca: e' un'affermazione falsa, e come tale non si scopre mai
 * da sola. E' gia' costata BUG-07 e BUG-12, corretti tutte e due SOLO nella
 * classe dove erano emersi.
 *
 * MISURA SULLA 3.16.100 (letta, non dedotta): 31 file confrontano un flag con
 * la propria versione, 32 punti in tutto. DUE si controllano davvero
 * (`Rdacademy_Schema_Contact_ICP_V32` con `storage_ready()`, che e' il modello, e
 * `Rdacademy_Schema_ObjectState_V32` con `schema_ready()`). Gli altri no.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * PERCHE' UN PUNTO SOLO E NON VENTUNO PEZZE.
 *
 * Riscrivere la guardia dentro ogni classe significa ventuno occasioni di
 * scriverla in ventuno modi, e la prossima classe nascera' senza. Qui la
 * verifica sta DOVE PASSANO TUTTE: `Rdacademy_Provisioning_Guard::provision()`,
 * che e' gia' l'unico cancello del bootstrap ed e' gia' quello che conosce i
 * flag di una classe (`flags()`). Una classe nuova e' coperta il giorno che
 * entra nel provisioning. RP-IL-GATE-SEGUE-IL-DATO-NON-LA-SINTASSI.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * COSA FA, ESATTAMENTE — E COSA NON FA MAI.
 *
 *  1. Legge dal registro le tabelle che la classe DICHIARA di creare.
 *  2. Se il flag e' presente e ANCHE UNA SOLA di quelle tabelle non esiste
 *     sulla connessione che le compete, il flag e' BUGIARDO e viene
 *     CANCELLATO. Nient'altro: la ricostruzione la fa la classe, subito dopo,
 *     con il suo `maybe_rebuild()`, che e' idempotente per costruzione
 *     (`CREATE TABLE IF NOT EXISTS`, guardie `SHOW COLUMNS`, seed solo-se-vuota).
 *  3. NESSUN DROP, nessuna ALTER, nessun dato toccato. Questa classe non ha
 *     SQL di scrittura: sa solo dire «questa tabella non c'e'».
 *
 * ────────────────────────────────────────────────────────────────────────────
 * LA REGOLA DI SICUREZZA CHE VIENE PRIMA DI TUTTE — LEGGERLA PRIMA DI TOCCARE.
 *
 * Se anche UNA delle tabelle dichiarate NON appartiene al BASE, il flag NON si
 * cancella: si riporta e basta. Motivo: le classi di schema costruiscono sulla
 * connessione BASE, e far ripartire una classe che possiede tabelle di modulo
 * significherebbe RICREARLE SUL BASE — cioe' fabbricare esattamente le copie
 * fantasma che la S159 ha passato una sessione a togliere (BUG-55, le 16
 * tabelle SFA parcheggiate `zzex_`). Quel caso appartiene alla VOCE 20 e alla
 * guardia dell'Activator, non a qui. `RP-SFA-NON-RIPIEGA` vale anche al
 * contrario: non si «ripara» scrivendo nel posto sbagliato.
 *
 * ────────────────────────────────────────────────────────────────────────────
 * IL REGISTRO E' GENERATO DAL SORGENTE, NON SCRITTO A MANO.
 *
 * `RP-CIO-CHE-E-CABLATO-NON-SI-AGGIORNA-DA-SOLO`: una mappa compilata a mano
 * diverge al primo `CREATE TABLE` aggiunto. Questa e' stata estratta leggendo
 * i `CREATE TABLE IF NOT EXISTS`, le `ALTER TABLE` e le `Rdacademy_Table_Owner::t()`
 * di ogni classe, risolvendo le variabili locali. Lo **Strumento 91** la
 * riconfronta con il disco vero e dichiara ROSSO se una classe non e' coperta:
 * il registro non si valida da solo (`RP-GATE-CIECO-E-PEGGIO-DI-NESSUN-GATE`).
 *
 * FUORI DAL REGISTRO, DICHIARATO: `Rdacademy_Schema_Masterid_Fix_Multi_V32` opera
 * su un elenco di tabelle costruito a runtime, non leggibile dal sorgente. Non
 * si finge di coprirla: lo Strumento 91 la elenca come NON VERIFICABILE.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_Flag_Guard {

	/** Classe di schema => tabelle (nome corto, senza prefisso) che dichiara. */
	const MAP = array(
		// NUOVO nel guscio: il nucleo nasce dall Activator, che dichiara qui le sue tabelle.
		'Rdacademy_Activator' => array( 'roles', 'users', 'user_roles', 'sessions', 'access_log', 'activity_log', 'impostazioni', 'user_prefs' ),
		// LOTTO-D — cucitura col lotto B: l ancora di licenza ha la sua classe di schema e il suo flag.
		'Rdacademy_Schema_License_V1' => array( 'license_activation' ),
	);

	/**
	 * LOTTO-D — la mappa si estende dal filtro `rdacademy_schema_flag_map` (classe =>
	 * tabelle nude), cosi ogni lotto dichiara le sue classi nel suo file di carico.
	 * Le righe di MAP vincono: un filtro non riscrive le tabelle del nucleo.
	 */
	public static function mappa(): array {
		$extra = (array) apply_filters( 'rdacademy_schema_flag_map', array() );
		$out   = self::MAP;
		foreach ( $extra as $cls => $tabs ) {
			if ( is_string( $cls ) && ! isset( $out[ $cls ] ) && is_array( $tabs ) ) {
				$out[ $cls ] = array_values( array_map( 'strval', $tabs ) );
			}
		}
		return $out;
	}

	/** Classi con flag che il sorgente non permette di mappare. Dichiarate, non nascoste. */
	const NON_VERIFICABILI = array();

	/** @var array<string,string> referto dell'ultimo giro, per lo Strumento 91. */
	private static $log = array();

	/** Tabelle dichiarate da una classe. */
	public static function tables_for( string $cls ): array {
		return self::mappa()[ $cls ] ?? array(); // LOTTO-D: mappa estendibile
	}

	/**
	 * Esiste la tabella sulla connessione che le compete?
	 * Torna null quando non si riesce nemmeno a chiedere: un dubbio non e' un NO.
	 * `RP-ERRORE-NON-SI-TRAVESTE-DA-DATO`.
	 */
	public static function table_exists( string $short ): ?bool {
		$full = RDACADEMY_TABLE_PREFIX . $short;
		try {
			// TAGLIO: in RelDox la connessione veniva da Reldox_Table_Owner::conn_for()
			// (topologia multi-sorgente). Nel guscio il nucleo vive tutto sul BASE.
			$conn = Rdacademy_DB::get_connection();
			if ( ! ( $conn instanceof mysqli ) ) {
				return null;
			}
			$res = $conn->query( "SHOW TABLES LIKE '" . $conn->real_escape_string( $full ) . "'" );
			if ( ! $res ) {
				return null;
			}
			$n = (int) $res->num_rows;
			$res->free();
			return $n > 0;
		} catch ( Throwable $e ) {
			return null; // mysqli_report puo' essere alzato dal provisioning: si regge, non si assume
		}
	}

	/** Tutte le tabelle dichiarate appartengono al BASE? Vedi la regola di sicurezza in testa. */
	public static function tutte_sul_base( string $cls ): bool {
		return true; // senza registro dei proprietari tutto vive sul BASE per definizione (ramo di RelDox)
	}

	/**
	 * Stato di una classe. Nessuna scrittura: si puo' chiamare da un banco.
	 * verdetto: NON_MAPPATA · FLAG_ASSENTE · VERO · BUGIARDO · FUORI_BASE · INCERTO
	 */
	public static function stato( string $cls, array $flags ): array {
		$out = array( 'classe' => $cls, 'flag' => $flags, 'tabelle' => self::tables_for( $cls ),
		              'mancanti' => array(), 'verdetto' => 'NON_MAPPATA' );
		if ( isset( self::NON_VERIFICABILI[ $cls ] ) ) {
			$out['verdetto'] = 'NON_VERIFICABILE';
			$out['nota']     = self::NON_VERIFICABILI[ $cls ];
			return $out;
		}
		if ( ! $out['tabelle'] ) {
			return $out;
		}
		$acceso = false;
		foreach ( $flags as $opt ) {
			if ( (string) Rdacademy_Option::get( $opt, '' ) !== '' ) {
				$acceso = true;
			}
		}
		if ( ! $acceso ) {
			$out['verdetto'] = 'FLAG_ASSENTE'; // la classe lavorera' comunque: niente da fare
			return $out;
		}
		$incerto = false;
		foreach ( $out['tabelle'] as $t ) {
			$e = self::table_exists( $t );
			if ( $e === null ) { $incerto = true; continue; }
			if ( $e === false ) { $out['mancanti'][] = $t; }
		}
		if ( $out['mancanti'] ) {
			$out['verdetto'] = self::tutte_sul_base( $cls ) ? 'BUGIARDO' : 'FUORI_BASE';
		} else {
			$out['verdetto'] = $incerto ? 'INCERTO' : 'VERO';
		}
		return $out;
	}

	/**
	 * IL PUNTO UNICO. Chiamato da Rdacademy_Provisioning_Guard::provision() PRIMA
	 * del maybe_rebuild della classe. Torna true se ha cancellato un flag
	 * bugiardo — cioe' se la classe, subito dopo, ricostruira' invece di uscire
	 * in testa.
	 */
	public static function sweep( string $cls, array $flags ): bool {
		$s = self::stato( $cls, $flags );
		if ( $s['verdetto'] !== 'BUGIARDO' ) {
			if ( $s['verdetto'] === 'FUORI_BASE' ) {
				self::$log[ $cls ] = 'FUORI_BASE: ' . implode( ', ', $s['mancanti'] )
					. ' — flag NON toccato (tabelle di modulo)';
			}
			return false;
		}
		foreach ( $flags as $opt ) {
			if ( (string) Rdacademy_Option::get( $opt, '' ) !== '' ) {
				Rdacademy_Option::del( $opt );
			}
		}
		self::$log[ $cls ] = 'flag cancellato — mancavano: ' . implode( ', ', $s['mancanti'] );
		return true;
	}

	public static function get_log(): array {
		return self::$log;
	}

	/** Referto completo per lo Strumento 91. */
	public static function report(): array {
		$out = array();
		foreach ( array_keys( self::mappa() ) as $cls ) { // LOTTO-D: mappa estendibile
			$flags = class_exists( 'Rdacademy_Provisioning_Guard' )
				? Rdacademy_Provisioning_Guard::flags( $cls ) : array();
			$out[] = self::stato( $cls, $flags );
		}
		foreach ( array_keys( self::NON_VERIFICABILI ) as $cls ) {
			$out[] = self::stato( $cls, array() );
		}
		return $out;
	}
}
