<?php
/**
 * RelDoxAcademy — Registro delle FONTI delle Tabelle Funzionali
 * =========================================================================
 * Copiato da Reldox_TF_Registry, RelDox CRD 3.55.09G (S120: BUG-39/40/41/42).
 * Le viste di /academy/tabelle/ rispondono a tre domande:
 *
 *   1. «da dove viene questo elenco?»          -> la FONTE
 *   2. «come lo rifaccio se lo rovino?»        -> il GENERATORE
 *   3. «come lo azzero per ricominciare?»      -> l'AZZERAMENTO
 *
 * TRE NATURE DICHIARATE, non dedotte da un nome (REGOLE-FAMIGLIA § 3.7):
 *   - `normativa` — l'elenco discende da una fonte esterna pubblicata. Rigenerabile.
 *   - `sistema`   — elenco curato dentro il prodotto, lo conosce il motore. Rigenerabile.
 *   - `cliente`   — («azienda» a schermo) non esiste un set di partenza: il
 *                   contenuto e' dato dell'azienda. NON rigenerabile, e un
 *                   generatore qui sarebbe contenuto INVENTATO.
 *
 * TAGLI, DICHIARATI:
 *  - le ventuno voci del CRM (aliquote, ATECO, comuni, valute, forme
 *    giuridiche, stati offerta, trasporti, commesse, pratiche, TCF, cicli…);
 *  - il tab NON si legge piu da Reldox_Table_Owner::FUNZIONALI (topologia del
 *    CRM): nel guscio il tab sta nella voce stessa (`tab`), insieme alla
 *    `famiglia` del rail. ⛔ Resta UNA verita: questa costante. La vista e il
 *    banco la LEGGONO, non la ricopiano.
 *  - `verifica()` non sottrae piu da Table_Owner::funzionali(): sottrae dal
 *    DISCO (ogni voce deve nominare una tabella che esiste davvero) e dalle
 *    nature ammesse. Una natura fuori dalle tre fa ROSSO (REGOLE § 3.7).
 *  - Reldox_Audit::log dell'azzeramento: diventa una riga di activity_log.
 *
 * L'AZZERAMENTO E' DICHIARATO, NON DEDOTTO (copiato): `svuota()` esegue un
 * `DELETE FROM` sulla singola tabella nominata dal registro. Mai `TRUNCATE`,
 * mai `DROP`, mai uno `SHOW TABLES` per scegliere che cosa cancellare.
 *
 * RelDoxAcademy (Formazione F009, 18/11/2026, analisi ed. 10) — RIDOTTA: tolta la fonte degli
 * invii del registro dei pasti; resta la tabella dei testi delle notifiche.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_TF_Registry {

	/** Nature ammesse. Chi ne inventa una quarta fa ROSSO in verifica(). */
	const NATURA_NORMATIVA = 'normativa';
	const NATURA_SISTEMA   = 'sistema';
	const NATURA_CLIENTE   = 'cliente';

	/** NUOVO — come si dice la natura a schermo (REGOLE § 3.7: «azienda», in codice `cliente`). */
	const NATURA_ETICHETTA = array(
		self::NATURA_NORMATIVA => 'normativa',
		self::NATURA_SISTEMA   => 'sistema',
		self::NATURA_CLIENTE   => 'azienda',
	);

	/**
	 * LA FONTE DI OGNI FUNZIONALE DEL GUSCIO.
	 *
	 * Chiave = nome BREVE della tabella (senza prefisso).
	 * Campi copiati: natura, fonte, classe, metodo, nota.
	 * NUOVO: tab (la voce del rail), famiglia (il gruppo del rail), etichetta.
	 *
	 * @var array<string,array<string,string>>
	 */
	const FONTI = array(
		// 0.8.7A (RF-BUG-025) — la prima tabella vera di RelDoxAcademy. «Voci d esempio» (tf_esempio, la tabella modello del
		// guscio) è uscita dal registro: l owner l ha giudicata fuorviante (08/10 09:36). La tabella resta sul database
		// (zero DROP, REGOLE § 6): nessuno la mostra e nessuno la legge.
		'tf_testi_notifiche' => array(
			'tab'       => 'notifiche',
			'famiglia'  => 'Comunicazione',
			'etichetta' => 'Testi delle notifiche',
			'natura'    => self::NATURA_SISTEMA,
			'fonte'     => 'Il set di partenza dei messaggi del prodotto (Rdacademy_Lingua::NOTIFICHE): un oggetto e un testo per messaggio e lingua',
			'classe'    => 'Rdacademy_Testi_Notifiche',
			'metodo'    => 'seed',
			'nota'      => 'Rigenerare ricrea per messaggio e lingua le voci che mancano. Non sovrascrive i testi cambiati e non riaccende le voci spente.',
		),
	);

	/**
	 * CHI PUNTA A UNA FUNZIONALE — copiato (3.47.06, D-R3 dell owner).
	 * Tabella => array( tabella che la cita, colonna ). «Cancella tutto» si
	 * RIFIUTA finche almeno una riga la cita. I testi delle notifiche non sono
	 * citati da nessuna riga: la mail li cerca per codice e, se mancano, usa il
	 * set di partenza.
	 *
	 * @var array<string,array{0:string,1:string}>
	 */
	const RIFERIMENTI = array();

	/** Come si dice il rifiuto di «Cancella tutto», per tabella (copiato). */
	const PAROLE_RIFIUTO = array();

	/** La frase del rifiuto di «Cancella tutto» su una tabella citata $n volte. */
	public static function frase_rifiuto( string $table, int $n ): string {
		$t = self::short( $table );
		$w = self::PAROLE_RIFIUTO[ $t ] ?? array( 'riga', 'righe', 'una voce', 'le voci' );
		return ( 1 === $n ? '1 ' . $w[0] . ' usa' : $n . ' ' . $w[1] . ' usano' )
			. ' ' . $w[2] . ' di questa tabella: si azzera solo quando nessuno ne usa una. Puoi disattivare ' . $w[3] . ' una per una.';
	}

	/** Quante righe citano la tabella (RIFERIMENTI). 0 se non e in mappa, -1 se non leggibile. */
	public static function citata( string $table ): int {
		$t = self::short( $table );
		if ( ! isset( self::RIFERIMENTI[ $t ] ) ) {
			return 0;
		}
		list( $da, $col ) = self::RIFERIMENTI[ $t ];
		$p   = RDACADEMY_TABLE_PREFIX;
		$row = Rdacademy_DB::get_row( "SELECT COUNT(*) AS n FROM `{$p}{$da}` WHERE `{$col}` IS NOT NULL" );
		return is_array( $row ) ? (int) ( $row['n'] ?? 0 ) : -1;
	}

	// ──────────────────────────────────────────────────────────────────
	// LETTURA
	// ──────────────────────────────────────────────────────────────────

	/** Nome breve, senza prefisso, minuscolo. */
	private static function short( string $table ): string {
		$t = strtolower( trim( $table ) );
		$p = strtolower( (string) ( defined( 'RDACADEMY_TABLE_PREFIX' ) ? RDACADEMY_TABLE_PREFIX : '' ) );
		if ( '' !== $p && 0 === strpos( $t, $p ) ) {
			$t = substr( $t, strlen( $p ) );
		}
		return $t;
	}

	/** @return array<string,mixed>|null */
	public static function fonte( string $table ): ?array {
		$t = self::short( $table );
		if ( ! isset( self::FONTI[ $t ] ) ) {
			return null;
		}
		$v = self::FONTI[ $t ];
		return array(
			'tabella'      => $t,
			'tab'          => (string) $v['tab'],
			'famiglia'     => (string) $v['famiglia'],
			'etichetta'    => (string) $v['etichetta'],
			'amministrata' => ( '' !== (string) $v['tab'] ),
			'natura'       => (string) $v['natura'],
			'natura_label' => self::NATURA_ETICHETTA[ (string) $v['natura'] ] ?? (string) $v['natura'],
			'fonte'        => (string) $v['fonte'],
			'classe'       => (string) $v['classe'],
			'metodo'       => (string) $v['metodo'],
			'nota'         => (string) $v['nota'],
			'rigenerabile' => self::rigenerabile( $t ),
		);
	}

	/** Le voci di un tab (copiato: un tab puo amministrare piu tabelle). */
	public static function per_tab( string $tab ): array {
		$tab = strtolower( trim( $tab ) );
		$out = array();
		if ( '' === $tab ) {
			return $out;
		}
		foreach ( array_keys( self::FONTI ) as $t ) {
			if ( (string) self::FONTI[ $t ]['tab'] === $tab ) {
				$v = self::fonte( $t );
				if ( null !== $v ) {
					$out[] = $v;
				}
			}
		}
		return $out;
	}

	/** NUOVO — il rail: famiglia => [voci], nell ordine del registro. */
	public static function famiglie(): array {
		$out = array();
		foreach ( array_keys( self::FONTI ) as $t ) {
			$v = self::fonte( $t );
			if ( null === $v || ! $v['amministrata'] ) continue;
			$v['righe'] = self::righe( $t );
			$out[ $v['famiglia'] ][] = $v;
		}
		return $out;
	}

	/**
	 * Rigenerabile = la classe esiste DAVVERO e il metodo e' richiamabile
	 * (copiato): un nome scritto qui e rinominato altrove darebbe un bottone
	 * che promette e non mantiene.
	 */
	public static function rigenerabile( string $table ): bool {
		$t = self::short( $table );
		if ( ! isset( self::FONTI[ $t ] ) ) {
			return false;
		}
		if ( self::NATURA_CLIENTE === (string) self::FONTI[ $t ]['natura'] ) {
			return false; // NUOVO: una tabella dell azienda non ha generatore, anche se qualcuno lo scrivesse
		}
		$c = (string) self::FONTI[ $t ]['classe'];
		$m = (string) self::FONTI[ $t ]['metodo'];
		if ( '' === $c || '' === $m ) {
			return false;
		}
		return class_exists( $c ) && is_callable( array( $c, $m ) );
	}

	/** Righe presenti adesso. -1 se la tabella non e' leggibile. */
	public static function righe( string $table ): int {
		$t = self::short( $table );
		if ( ! isset( self::FONTI[ $t ] ) ) {
			return -1;
		}
		$p   = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::clear_last_error();
		$row = Rdacademy_DB::get_row( "SELECT COUNT(*) AS n FROM `{$p}{$t}`" );
		return is_array( $row ) ? (int) ( $row['n'] ?? 0 ) : -1;
	}

	// ──────────────────────────────────────────────────────────────────
	// INTEGRITA'
	// ──────────────────────────────────────────────────────────────────

	/**
	 * Il registro e' coerente? Sottrae, non elenca (copiato nella forma).
	 * NUOVO: la sottrazione si fa contro il disco (ogni voce nomina una tabella
	 * che esiste) invece che contro Table_Owner, che nel guscio non c e.
	 *
	 * @return array{verde:bool,mancanti:string[],natura_ignota:string[],totale:int}
	 */
	public static function verifica(): array {
		$ammesse  = array( self::NATURA_NORMATIVA, self::NATURA_SISTEMA, self::NATURA_CLIENTE );
		$ignota   = array();
		$mancanti = array();
		foreach ( self::FONTI as $t => $v ) {
			if ( ! in_array( (string) $v['natura'], $ammesse, true ) ) {
				$ignota[] = $t;
			}
			if ( self::righe( $t ) < 0 ) {
				$mancanti[] = $t;
			}
		}
		sort( $mancanti );
		sort( $ignota );
		return array(
			'verde'         => ( array() === $mancanti && array() === $ignota ),
			'mancanti'      => $mancanti,
			'natura_ignota' => $ignota,
			'totale'        => count( self::FONTI ),
		);
	}

	/** Il referto completo: una misura, due consumatori (REST e banco). */
	public static function referto(): array {
		$out = array();
		foreach ( array_keys( self::FONTI ) as $t ) {
			$v = self::fonte( $t );
			if ( null === $v ) {
				continue;
			}
			$v['righe']   = self::righe( $t );
			$v['citata']  = self::citata( $t );
			$out[] = $v;
		}
		usort( $out, static function ( array $a, array $b ): int {
			return strcmp( (string) $a['tabella'], (string) $b['tabella'] );
		} );
		return $out;
	}

	// ──────────────────────────────────────────────────────────────────
	// SCRITTURA — le due sole operazioni, entrambe dichiarate (copiate)
	// ──────────────────────────────────────────────────────────────────

	/** @return array{ok:bool,tabella:string,prima:int,dopo:int,rimosse:int,errore:string} */
	public static function svuota( string $table ): array {
		$t   = self::short( $table );
		$out = array( 'ok' => false, 'tabella' => $t, 'prima' => -1, 'dopo' => -1, 'rimosse' => 0, 'errore' => '' );

		if ( ! isset( self::FONTI[ $t ] ) ) {
			$out['errore'] = 'tabella non dichiarata nel registro delle funzionali';
			return $out;
		}
		$v = self::fonte( $t );
		if ( null === $v || ! $v['amministrata'] ) {
			$out['errore'] = 'tabella amministrata fuori da /' . RDACADEMY_APP_SLUG . '/tabelle/: si azzera dal suo pannello';
			return $out;
		}

		// D-R3 (copiato): una tabella citata non si azzera. Il controllo sta
		// PRIMA della conta e della DELETE, e dice il numero.
		$cit = self::citata( $t );
		if ( 0 !== $cit ) {
			$out['prima']  = self::righe( $t );
			$out['errore'] = $cit < 0
				? 'non riesco a leggere chi usa questa tabella: non la azzero'
				: self::frase_rifiuto( $t, $cit );
			return $out;
		}

		$prima        = self::righe( $t );
		$out['prima'] = $prima;
		if ( $prima < 0 ) {
			$out['errore'] = 'tabella non leggibile su questa installazione';
			return $out;
		}
		if ( 0 === $prima ) {
			$out['ok']   = true;
			$out['dopo'] = 0;
			return $out;
		}

		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::clear_last_error();
		Rdacademy_DB::execute( "DELETE FROM `{$p}{$t}`" );
		$err = Rdacademy_DB::last_error();

		$dopo           = self::righe( $t );
		$out['dopo']    = $dopo;
		$out['rimosse'] = ( $prima >= 0 && $dopo >= 0 ) ? max( 0, $prima - $dopo ) : 0;

		if ( '' !== $err ) {
			$out['errore'] = $err;
			return $out;
		}
		// La prova non e' «la query non ha protestato»: e' che la tabella e vuota.
		$out['ok'] = ( 0 === $dopo );
		if ( ! $out['ok'] && '' === $out['errore'] ) {
			$out['errore'] = 'restano ' . $dopo . ' righe: cancellazione non completa';
		}
		return $out;
	}

	/** @return array{ok:bool,tabella:string,prima:int,dopo:int,aggiunte:int,fonte:string,errore:string} */
	public static function genera( string $table ): array {
		$t   = self::short( $table );
		$out = array( 'ok' => false, 'tabella' => $t, 'prima' => -1, 'dopo' => -1, 'aggiunte' => 0, 'fonte' => '', 'errore' => '' );

		$v = self::fonte( $t );
		if ( null === $v ) {
			$out['errore'] = 'tabella non dichiarata nel registro delle funzionali';
			return $out;
		}
		$out['fonte'] = (string) $v['fonte'];

		if ( self::NATURA_CLIENTE === $v['natura'] ) {
			$out['errore'] = 'nessun set di partenza: il contenuto è un dato dell’azienda';
			return $out;
		}
		if ( ! self::rigenerabile( $t ) ) {
			$out['errore'] = ( '' !== $v['nota'] ) ? (string) $v['nota'] : 'generatore non esposto per questa tabella';
			return $out;
		}

		$prima        = self::righe( $t );
		$out['prima'] = $prima;

		$c = (string) $v['classe'];
		$m = (string) $v['metodo'];
		Rdacademy_DB::clear_last_error();
		try {
			call_user_func( array( $c, $m ) );
		} catch ( Throwable $e ) {
			$out['errore'] = $c . '::' . $m . '() ha sollevato: ' . $e->getMessage();
			$out['dopo']   = self::righe( $t );
			return $out;
		}

		$dopo            = self::righe( $t );
		$out['dopo']     = $dopo;
		$out['aggiunte'] = ( $prima >= 0 && $dopo >= 0 ) ? max( 0, $dopo - $prima ) : 0;
		$err             = Rdacademy_DB::last_error();
		if ( '' !== $err ) {
			$out['errore'] = $err;
			return $out;
		}
		$out['ok'] = ( $dopo > 0 );
		if ( ! $out['ok'] ) {
			$out['errore'] = 'il generatore non ha lasciato nessuna riga: fonte vuota o seme non applicabile';
		}
		return $out;
	}
}
