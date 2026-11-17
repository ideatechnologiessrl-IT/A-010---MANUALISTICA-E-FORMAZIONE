<?php
/**
 * RelDoxAcademy — PROVISIONING GUARD (Patch 2 di BUG-07 — AP-PROVISIONING-AUTORIPARANTE)
 * Copiato da Reldox_Provisioning_Guard, RelDox CRD 3.55.09G. TAGLIO: il ramo
 * «rinvio» di Reldox_Tabella_Senza_Casa (BUG-182, tabelle SFA di modulo).
 * -----------------------------------------------------------------------------
 * IL VIZIO CHE QUESTA CLASSE CHIUDE (misurato in S61/S62 su sorgente):
 * il pattern one-shot di 59 delle 60 classi schema e', in sostanza:
 *
 *     if ( flag combacia ) return;
 *     create(); seed();
 *     update_option( FLAG );   <-- SEMPRE, anche se create() e' FALLITA
 *
 * L'esito di $conn->query() non e' controllato e in produzione mysqli_report
 * e' OFF: l'errore e' MUTO e il flag MENTE. Da quel momento il provisioning
 * non viene mai piu' ritentato. E' la radice di BUG-07 (nuova installazione
 * con tabelle/colonne mancanti e nessuna via di ritorno).
 *
 * LA CORREZIONE, IN UN PUNTO SOLO:
 * i 60 punti di chiamata del bootstrap sono tutti identici, quindi NON si
 * riscrivono le 60 classi (che restano intatte: zero rischio di regressione
 * sulle installazioni sane). Si intercetta la chiamata:
 *
 *     Rdacademy_Provisioning_Guard::provision( 'Rdacademy_Activator' );
 *
 * Il guard, per ogni classe:
 *   1. fotografa i flag della classe (costanti 'rdacademy_*', per riflessione)
 *      registrando quali erano ASSENTI prima della chiamata;
 *   2. alza mysqli_report(ERROR|STRICT) TEMPORANEO, cosi' un DDL fallito
 *      diventa un'eccezione invece di un guasto silenzioso (stesso
 *      meccanismo gia' collaudato dallo Strumento 74 in 3.15.93);
 *   3. esegue maybe_rebuild() della classe;
 *   4. RIPRISTINA sempre lo stato di mysqli_report precedente (finally);
 *   5. se e' arrivato un errore VERO, CANCELLA i soli flag che erano assenti
 *      prima e che la classe ha stampato durante questo tentativo fallito:
 *      il flag torna a dire la verita' e IL BOOT SUCCESSIVO RITENTA DA SOLO.
 *
 * ERRORI BENIGNI (1050 tabella esiste, 1060 colonna duplicata, 1061 indice
 * duplicato, 1062 riga duplicata): sono l'idempotenza che urta contro
 * l'esistente, NON guasti. Non annullano il flag. Stessa classificazione
 * dello Strumento 74 (una sola grammatica per tutto il prodotto).
 *
 * COSTO SU INSTALLAZIONE SANA: zero DDL in piu'. Le classi restituiscono
 * subito sul controllo del proprio flag, come hanno sempre fatto. Il guard
 * aggiunge solo una riflessione sulle costanti e due get_option per classe.
 * Nessuna migrazione dei flag esistenti: le installazioni sane non si
 * accorgono di nulla.
 *
 * ZERO DROP. ZERO SQL PROPRIO: il guard non possiede DDL, esegue i primitivi
 * delle classi (RP-ENGINE-NOT-BENCH).
 *
 * @package Rdacademy_CRD
 * NOME DELLA CLASSE (3.15.96, correzione di un errore MIO in 3.15.95): questa
 * classe si chiamava Rdacademy_Schema_Guard. NON e' una classe schema: non ha
 * maybe_rebuild(), non possiede tabelle. Lo Strumento 74 ha una rete
 * anti-deriva che raccoglie per riflessione OGNI classe dichiarata con
 * prefisso 'Rdacademy_Schema_' e le esegue il provisioning: al primo boot utile
 * ha giustamente accodato il guard e ha riportato NON CONVERGE con un
 * TypeError. La rete ha funzionato; era sbagliato il nome. Rinominata in
 * Rdacademy_Provisioning_Guard: fuori dallo spazio dei nomi delle classi schema.
 * Il banco NON e' stato modificato (nessuna eccezione cablata a mano).
 *
 * @since   3.15.95 (rinominata in 3.15.96)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Rdacademy_Provisioning_Guard {

	/** Codici MySQL che l'idempotenza produce urtando l'esistente: non sono guasti. */
	const BENIGN = array( 1050, 1060, 1061, 1062 );

	/** Diario dell'ultimo boot: classe => esito. Letto dallo Strumento 74. */
	private static $log = array();

	/**
	 * Nomi delle option-flag dichiarati dalla classe: costanti di tipo
	 * stringa il cui valore inizia per 'rdacademy_'. Il prefisso CON underscore
	 * esclude le costanti nome-directory ('rdacademy-private', 'rdacademy') delle
	 * classi Uploads, che non sono option (correzione 3.15.94, stessa
	 * grammatica dello Strumento 74).
	 */
	/**
	 * IL FLAG NON VIVE PIU SUL `_1` (3.16.33 — coda di BUG-26 lotto 1).
	 *
	 * Dalla 3.16.32 le 48 classi schema convertite scrivono il proprio flag con
	 * `Rdacademy_Option` sul BASE `_2`. Questo guard continuava a fotografarlo con
	 * `get_option()` sul `_1`: avrebbe visto OGNI flag come assente e, in caso di
	 * fallimento, non avrebbe cancellato nulla — cioe il rollback che e il senso
	 * stesso della classe sarebbe stato CIECO. Un gate cieco e peggio di nessun
	 * gate (RP-GATE-CIECO-E-PEGGIO-DI-NESSUN-GATE), e vale anche per una rete di
	 * sicurezza.
	 *
	 * 3.16.58 — IL LOTTO 2 E FINITO, E QUESTI DUE CORPI CAMBIANO DI CONSEGUENZA.
	 * Fino alla 3.16.57 guardavano ENTRAMBI gli archivi con un `get_option()`
	 * nudo, perche le classi non ancora convertite scrivevano ancora sul `_1`.
	 * Adesso nessuna lo fa piu, ma su un installazione VIVA un flag scritto
	 * prima della conversione puo essere rimasto fisicamente la: guardare solo
	 * il `_2` renderebbe di nuovo cieca la rete di sicurezza.
	 *
	 * La risposta e il PONTE, non un secondo sguardo sul `_1`. `bridge_get()`
	 * guarda il `_2`, e solo se non trova ricade sul `_1`, riscrive il valore
	 * sul `_2` e lo cancella di la: la rete resta vedente E il `_1` si svuota
	 * invece di essere consultato in eterno. `bridge_del()` cancella su
	 * entrambi per la stessa ragione — un rollback che lascia la copia sul `_1`
	 * la vedrebbe resuscitare al primo `bridge_get()` successivo.
	 *
	 * La trappola false/default e riletta a mano: qui il confronto e con `null`
	 * e `bridge_get( $opt, null )` torna `null` sia a chiave assente sia a
	 * valore `false`, esattamente come faceva `get_option( $opt, null )`.
	 */
	private static function flag_presente( string $opt ): bool {
		if ( ! class_exists( 'Rdacademy_Option' ) ) {
			return false;
		}
		return Rdacademy_Option::bridge_get( $opt, null ) !== null;
	}

	/** Cancella il flag ovunque si trovi. Non e un DROP: e uno stato. */
	private static function flag_cancella( string $opt ): void {
		if ( ! class_exists( 'Rdacademy_Option' ) ) {
			return;
		}
		Rdacademy_Option::bridge_del( $opt );
	}

	public static function flags( string $cls ): array {
		$out = array();
		try {
			$rc = new ReflectionClass( $cls );
			foreach ( $rc->getConstants() as $val ) {
				if ( is_string( $val ) && 0 === strpos( $val, 'rdacademy_' ) ) {
					$out[] = $val;
				}
			}
		} catch ( Throwable $e ) {
			// fail-soft: classe senza riflessione -> nessun rollback possibile,
			// ma la chiamata avviene lo stesso (comportamento pre-3.15.95).
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Esegue il provisioning di UNA classe con flag verificato.
	 * Non lancia mai: il bootstrap non deve morire per una classe schema.
	 *
	 * @param string $cls Nome della classe schema.
	 * @return bool true se il provisioning e' andato a buon fine (o benigno).
	 */
	public static function provision( string $cls ): bool {
		if ( ! class_exists( $cls ) || ! method_exists( $cls, 'maybe_rebuild' ) ) {
			return false; // stessa semantica del vecchio if ( class_exists(...) )
		}

		// (0) BUG-53 (3.16.101) — UN SEGNAPOSTO NON E' UNA PROVA.
		//     Prima di lasciar decidere la classe, si guarda se cio' che il suo
		//     flag DICHIARA esiste davvero. Se il flag dice «fatto» e una tabella
		//     dichiarata non c'e', il flag viene CANCELLATO: la classe qui sotto
		//     ricostruisce invece di uscire in testa. Nessun DROP, nessuna ALTER,
		//     nessun dato toccato — e mai su tabelle che non sono del BASE
		//     (vedi la regola di sicurezza in testa a Rdacademy_Schema_Flag_Guard).
		//     Sta QUI e non in ventuno classi perche' questo e' il punto da cui
		//     passano tutte, comprese quelle che nasceranno.
		if ( class_exists( 'Rdacademy_Schema_Flag_Guard' ) ) {
			try {
				Rdacademy_Schema_Flag_Guard::sweep( $cls, self::flags( $cls ) );
			} catch ( Throwable $e ) {
				self::$log[ $cls ] = 'flag-guard non eseguito: ' . $e->getMessage();
			}
		}

		// (1) Quali flag erano ASSENTI prima di questo tentativo: sono i soli
		//     che ha senso cancellare in caso di fallimento. I flag gia'
		//     presenti provengono da esecuzioni precedenti andate a buon fine
		//     e NON si toccano (nessuna migrazione dei flag esistenti).
		$absent_before = array();
		foreach ( self::flags( $cls ) as $opt ) {
			if ( ! self::flag_presente( $opt ) ) {
				$absent_before[] = $opt;
			}
		}

		// (2) Errori SQL VISIBILI, temporaneamente.
		$prev_report = null;
		if ( function_exists( 'mysqli_report' ) ) {
			try {
				$prev_report = mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
			} catch ( Throwable $e ) {
				$prev_report = null;
			}
		}

		$ok    = true;
		$fatal = '';

		try {
			// (3) Il guard non ha SQL proprio: esegue il primitivo della classe.
			call_user_func( array( $cls, 'maybe_rebuild' ) );
		} catch ( mysqli_sql_exception $e ) {
			$code = (int) $e->getCode();
			if ( in_array( $code, self::BENIGN, true ) ) {
				// Idempotenza contro l'esistente: non e' un guasto.
				self::$log[ $cls ] = 'benigno [' . $code . ']';
			} else {
				$ok    = false;
				$fatal = '[' . $code . '] ' . $e->getMessage();
			}
		} catch ( Throwable $e ) {
			$ok    = false;
			$fatal = get_class( $e ) . ': ' . $e->getMessage();
		} finally {
			// (4) Ripristino SEMPRE: nessuna deriva dello stato globale.
			if ( function_exists( 'mysqli_report' ) && $prev_report !== null ) {
				try {
					mysqli_report( MYSQLI_REPORT_OFF );
				} catch ( Throwable $e ) { /* fail-soft */ }
			}
		}

		if ( $ok ) {
			if ( ! isset( self::$log[ $cls ] ) ) {
				self::$log[ $cls ] = 'ok';
			}
			return true;
		}

		// (5) IL PUNTO DI TUTTA LA PATCH: il tentativo e' fallito davvero,
		//     quindi i flag che questo tentativo ha stampato sono BUGIE.
		//     Si cancellano: al prossimo caricamento si ritenta da soli.
		$rolled = array();
		foreach ( $absent_before as $opt ) {
			if ( self::flag_presente( $opt ) ) {
				self::flag_cancella( $opt );
				$rolled[] = $opt;
			}
		}

		self::$log[ $cls ] = 'FALLITA: ' . $fatal
			. ( $rolled ? ' | flag annullati: ' . implode( ', ', $rolled ) : ' | nessun flag stampato' );

		error_log( '[Reldox Provisioning Guard] ' . $cls . ' — ' . self::$log[ $cls ] );

		return false;
	}

	/** Diario del boot corrente (classe => esito). Sola lettura. */
	public static function get_log(): array {
		return self::$log;
	}
}
