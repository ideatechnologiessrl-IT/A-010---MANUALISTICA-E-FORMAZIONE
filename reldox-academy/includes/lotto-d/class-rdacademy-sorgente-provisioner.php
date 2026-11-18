<?php
/**
 * Rdacademy_Sorgente_Provisioner — LE TABELLE DI UNA SORGENTE NASCONO QUANDO LA SI ACCENDE.
 * =============================================================================
 * Copiato da Reldox_Sfa_Provisioner (BUG-182 lotto 1), RelDox CRD 3.55.09G, e
 * reso generico: in RelDox era cablato sulla SFA («offerte»); qui ogni cosa si
 * chiede alla mappa per CHIAVE (Rdacademy_Sorgenti::tabelle()).
 *
 * LE DECISIONI COPIATE:
 *  1. Si scatta sulla TRANSIZIONE verso IN USO, non a ogni salvataggio (lo
 *     decide il chiamante leggendo `enabled` prima dell UPSERT).
 *  2. Una chiave senza tabelle proprie esce con NON_APPLICABILE: non è un guasto.
 *  3. Il cancello conta con COUNT(*), mai con information_schema.TABLE_ROWS
 *     (una stima di InnoDB ha già mentito a RelDox).
 *  4. Copie sul BASE popolate → si RIFIUTA e si nominano tabelle e righe:
 *     «prima si spostano i dati: è una migrazione, non un interruttore».
 *  5. Tutto dentro il MODO DICHIARATO, e lo si chiude SEMPRE in finally.
 *  6. Verifica per CONVERGENZA sul campo, non sul diario.
 *  7. Se non riesce, la sorgente torna spenta (lo fa il chiamante): una
 *     sorgente accesa a cui mancano tabelle darebbe errori SQL a schermo.
 *
 * TAGLI: la riesecuzione per riflessione di tutte le classi `Reldox_Schema_*` e
 * di Reldox_Activator::activate() (in RelDox le tabelle SFA nascevano dentro le
 * classi del prodotto): nel guscio ogni tabella di sorgente ha il SUO creatore
 * dichiarato nella mappa, e si chiama quello. Reldox_Sfa_Gate, Reldox_Offerte.
 *
 * ZERO DROP, ZERO ALTER, nessuna option nuova, nessun dato spostato.
 *
 * @package RelDox_Base
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Sorgente_Provisioner {

	const NON_APPLICABILE   = 'non_applicabile';
	const RIFIUTO           = 'rifiuto';
	const NON_RAGGIUNGIBILE = 'non_raggiungibile';
	const GIA_A_POSTO       = 'gia_a_posto';
	const PROVISIONATA      = 'provisionata';
	const INCOMPLETA        = 'incompleta';

	/** Gli esiti che riportano la sorgente a spenta. */
	const ESITI_KO = array( self::RIFIUTO, self::NON_RAGGIUNGIBILE, self::INCOMPLETA );

	/**
	 * CENSIMENTO — SOLA LETTURA ASSOLUTA (copiato).
	 */
	public static function censimento( string $key ): array {
		$key = strtolower( trim( $key ) );
		$out = array(
			'sorgente' => $key, 'tabelle' => array(), 'configurata' => false, 'attiva' => false,
			'stesso_host' => false, 'db' => '', 'raggiungibile' => false, 'provisionata' => false,
			'base_presenti' => array(), 'base_popolate' => array(), 'base_vuote' => array(),
			'src_presenti' => array(), 'src_mancanti' => array(), 'incerto' => '',
		);
		$out['tabelle'] = Rdacademy_Table_Owner::module_tables( $key );
		if ( $out['tabelle'] === array() ) {
			return $out;
		}
		$out['db']          = Rdacademy_Table_Owner::module_db( $key );
		$out['configurata'] = ( $out['db'] !== '' );
		$out['attiva']      = Rdacademy_DataSource::is_active( $key );
		$out['stesso_host'] = Rdacademy_Table_Owner::same_host( $key );

		// ── Le copie sul BASE, e QUANTE righe hanno davvero (nome composto a mano: deliberato) ──
		$p    = Rdacademy_Table_Owner::prefix();
		$base = null;
		try { $base = Rdacademy_DB::get_connection(); } catch ( Throwable $e ) { $base = null; }
		if ( ! ( $base instanceof mysqli ) ) {
			$out['incerto'] = 'la connessione BASE non risponde: le copie sul BASE non sono interrogabili.';
		} else {
			foreach ( $out['tabelle'] as $t ) {
				$full = $p . $t;
				$c    = Rdacademy_DataSource::tabella_su( $base, $full );
				if ( $c === null ) { $out['incerto'] .= ' sonda non riuscita su «' . $full . '» del BASE.'; continue; }
				if ( $c === false ) { continue; }
				$out['base_presenti'][] = $t;
				try {
					$r = $base->query( 'SELECT COUNT(*) AS n FROM `' . str_replace( '`', '``', $full ) . '`' );
					if ( ! ( $r instanceof mysqli_result ) ) { $out['incerto'] .= ' conteggio non riuscito su «' . $full . '».'; continue; }
					$n = (int) ( $r->fetch_assoc()['n'] ?? 0 );
					$r->free();
					if ( $n > 0 ) { $out['base_popolate'][ $t ] = $n; } else { $out['base_vuote'][] = $t; }
				} catch ( Throwable $e ) {
					$out['incerto'] .= ' interrogazione fallita su «' . $full . '»: ' . $e->getMessage();
				}
			}
		}
		$out['incerto'] = trim( $out['incerto'] );

		if ( ! $out['attiva'] || ! $out['configurata'] || ! $out['stesso_host'] ) {
			return $out;
		}
		$conn = Rdacademy_DataSource::get( $key );
		if ( $base instanceof mysqli && $conn === $base ) {
			return $out; // ricaduta sul BASE: non raggiungibile
		}
		$out['raggiungibile'] = true;
		$out['provisionata']  = Rdacademy_DataSource::is_provisioned( $key );
		foreach ( $out['tabelle'] as $t ) {
			$c = Rdacademy_DataSource::tabella_su( $conn, $p . $t );
			if ( $c === true ) {
				$out['src_presenti'][] = $t;
			} else {
				$out['src_mancanti'][] = $t;
				if ( $c === null ) { $out['incerto'] = trim( $out['incerto'] . ' sonda fallita su «' . $p . $t . '» della sorgente.' ); }
			}
		}
		return $out;
	}

	/** IL CANCELLO — si può provisionare? (copiato) */
	public static function cancello( string $key ): array {
		$c    = self::censimento( $key );
		$nome = Rdacademy_Sorgenti::nome( $key );
		$no   = static function ( string $esito, string $msg ) use ( $c ): array {
			return array( 'esito' => $esito, 'messaggio' => $msg, 'censimento' => $c );
		};
		if ( $c['tabelle'] === array() ) {
			return $no( self::NON_APPLICABILE, 'La sorgente «' . $nome . '» non dichiara tabelle proprie: non c’è nulla da far nascere, e non è un guasto.' );
		}
		if ( ! $c['configurata'] || ! $c['attiva'] ) {
			return $no( self::NON_RAGGIUNGIBILE, 'La sorgente «' . $nome . '» non è configurata o non è accesa: il provisioning non parte.' );
		}
		if ( ! $c['stesso_host'] ) {
			return $no( self::NON_RAGGIUNGIBILE, 'Il database «' . $c['db'] . '» della sorgente «' . $nome . '» non è sullo stesso server del BASE: la qualificazione degli schemi è una sintassi, non un protocollo di rete.' );
		}
		if ( ! $c['raggiungibile'] ) {
			return $no( self::NON_RAGGIUNGIBILE, 'La sorgente «' . $nome . '» è accesa ma la connessione ricade sul BASE: credenziali, nome del database o permessi non rispondono. Usa «Testa connessione» e correggi.' );
		}
		if ( $c['base_popolate'] !== array() ) {
			$righe = array();
			foreach ( $c['base_popolate'] as $t => $n ) { $righe[] = $t . ' (' . $n . ')'; }
			return $no( self::RIFIUTO, 'PROVISIONING RIFIUTATO sulla sorgente «' . $nome . '». Sul BASE ci sono ancora dati: '
				. count( $c['base_popolate'] ) . ' tabelle su ' . count( $c['tabelle'] ) . ' hanno righe dentro — ' . implode( ', ', $righe ) . '.'
				. ' Creare le copie vuote sul database dedicato renderebbe quei dati irraggiungibili senza cancellarli.'
				. ' Prima si spostano i dati: è una migrazione, non un interruttore.' );
		}
		if ( $c['incerto'] !== '' ) {
			return $no( self::NON_RAGGIUNGIBILE, 'PROVISIONING NON ESEGUITO sulla sorgente «' . $nome . '»: la misura non è stata completa e non si costruisce su un dubbio — ' . $c['incerto'] );
		}
		if ( $c['src_mancanti'] === array() ) {
			return $no( self::GIA_A_POSTO, 'La sorgente «' . $nome . '» ha già tutte le sue ' . count( $c['tabelle'] ) . ' tabelle: nulla da creare.' );
		}
		return $no( '', 'Provisioning eseguibile: ' . count( $c['src_mancanti'] ) . ' tabelle su ' . count( $c['tabelle'] ) . ' mancano sulla sorgente.' );
	}

	/**
	 * IL GESTO — crea le tabelle della sorgente sul suo database. Non lancia
	 * mai: torna sempre un verdetto leggibile (copiato).
	 */
	public static function porta_in_uso( string $key ): array {
		$key = strtolower( trim( $key ) );
		$v   = self::cancello( $key );
		if ( $v['esito'] !== '' ) {
			$v['creato'] = array();
			$v['log']    = array();
			return $v;
		}
		$prima = $v['censimento']['src_presenti'];
		$log   = array();
		$crea  = Rdacademy_Sorgenti::tabelle()[ $key ] ?? array();

		Rdacademy_Table_Owner::begin_declared();
		try {
			Rdacademy_DataSource::flush_provisioning( $key );
			foreach ( $crea as $tab => $fn ) {
				if ( in_array( $tab, $prima, true ) ) { $log[ $tab ] = 'già presente: non si tocca'; continue; }
				try {
					$conn = Rdacademy_Table_Owner::conn_for( $tab );
					$ok   = (bool) call_user_func( $fn, $conn, Rdacademy_Table_Owner::prefix() . $tab );
					$log[ $tab ] = $ok ? 'ok' : 'FALLITA: ' . (string) $conn->error;
				} catch ( Rdacademy_Tabella_Senza_Casa $e ) {
					$log[ $tab ] = 'RINVIATA: ' . $e->getMessage();
				} catch ( Throwable $e ) {
					$log[ $tab ] = 'FALLITA: ' . get_class( $e ) . ': ' . $e->getMessage();
				}
			}
		} finally {
			Rdacademy_Table_Owner::end_declared();
			Rdacademy_DataSource::flush_provisioning( $key );
		}

		$dopo   = self::censimento( $key );
		$creato = array_values( array_diff( $dopo['src_presenti'], $prima ) );
		$nome   = Rdacademy_Sorgenti::nome( $key );
		if ( $dopo['src_mancanti'] === array() ) {
			return array(
				'esito'      => self::PROVISIONATA,
				'messaggio'  => 'Tabelle della sorgente «' . $nome . '» nate su «' . $dopo['db'] . '»: ' . count( $creato ) . ' create, '
					. count( $dopo['src_presenti'] ) . ' su ' . count( $dopo['tabelle'] ) . ' presenti.'
					. ( $dopo['provisionata'] ? ' La sentinella risponde: la sorgente è instradabile.' : '' ),
				'censimento' => $dopo, 'creato' => $creato, 'log' => $log,
			);
		}
		$motivi = array();
		foreach ( $log as $t => $l ) { if ( strpos( $l, 'ok' ) !== 0 && strpos( $l, 'già' ) !== 0 ) { $motivi[] = $t . ': ' . $l; } }
		return array(
			'esito'      => self::INCOMPLETA,
			'messaggio'  => 'PROVISIONING INCOMPLETO su «' . $dopo['db'] . '»: mancano ancora ' . count( $dopo['src_mancanti'] ) . ' tabelle su '
				. count( $dopo['tabelle'] ) . ' — ' . implode( ', ', $dopo['src_mancanti'] ) . '.'
				. ( $motivi ? ' Motivo: ' . implode( ' · ', $motivi ) . '.' : '' )
				. ' La sorgente è stata riportata a NON IN USO: l’installazione lavora come prima e i dati non sono stati toccati.',
			'censimento' => $dopo, 'creato' => $creato, 'log' => $log,
		);
	}
}
