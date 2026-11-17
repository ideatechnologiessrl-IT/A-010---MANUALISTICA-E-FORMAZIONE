<?php
/**
 * class-rdacademy-password-token.php — IL TOKEN NON SI CONSERVA IN CHIARO
 * Copiato da Reldox_Password_Token, RelDox CRD 3.55.09G. Unica modifica (correzione
 * § 2.3 della SPEC, fuso orario): il confronto della scadenza usa un valore UTC
 * passato da PHP, non UTC_TIMESTAMP() del server — un orologio solo per scrivere
 * e per confrontare.
 * =============================================================================
 * 3.16.195 (S256) — voce 39 `SV-CREDENZIALI-E-RECUPERO`, chat 1 di 4.
 *
 * IL DIFETTO CHE QUESTA CLASSE CHIUDE, misurato a sorgente e non dedotto.
 * Fino alla 3.16.194 `ajax_reset_user_pwd()` (`class-rdacademy-router.php`
 * r.4242-4254) generava 32 byte casuali, li scriveva IN CHIARO in
 * `rdacademy_users.reset_token` e li RESTITUIVA nel corpo della risposta JSON.
 * Il pulsante «Reset pwd» che la chiama esiste ed e vivo
 * (`gestione-utenti.php` r.369, mostrato su ogni utente esistente r.1162,
 * chiamata r.1478): non era un moncone dormiente, era un moncone RAGGIUNGIBILE.
 * Chiunque avesse letto quella riga di database avrebbe potuto entrare come
 * quell utente per 24 ore.
 *
 * COME LO CHIUDE — `RP-IL-TOKEN-NON-SI-CONSERVA-IN-CHIARO`, sorvegliata da
 * GATE 28. Il segreto viaggia UNA VOLTA, nel link della mail, e sul database
 * resta solo la sua IMPRONTA SHA-256. Un archivio rubato non contiene nessuna
 * chiave: contiene la fotografia di una chiave, che non apre niente.
 *
 * ⛔ ZERO SCHEMA, ZERO ALTER. Le due colonne esistono gia dalla v32
 * (`class-rdacademy-schema-users-v32.php` r.291-292): `reset_token VARCHAR(100)` e
 * `reset_expiry TIMESTAMP NULL`. Lo SCOPO del token — invito o reimpostazione —
 * non prende una colonna sua: sta come PREFISSO DI DUE CARATTERI davanti
 * all impronta. 2 + 64 = 66 caratteri, dentro i 100 disponibili. E la decisione
 * 5 della roadmap, adottata proprio per non toccare lo schema.
 *
 * UN SOLO TOKEN VIVO PER UTENTE. Emettere sovrascrive: chi chiede due volte il
 * recupero puo usare solo l ultimo collegamento ricevuto. Un utente con tre
 * link vivi e un utente con tre porte aperte.
 *
 * IL CONSUMO CHIUDE TUTTO — `RP-CAMBIO-PASSWORD-CHIUDE-LE-SESSIONI`. Cambiare
 * password mentre una sessione rubata resta aperta non ripara niente: e per
 * questo che al consumo si cancellano TUTTE le sessioni dell utente, si azzera
 * il contatore dei tentativi falliti e si toglie l eventuale blocco.
 *
 * ⛔ `password_expires_at` VIENE AZZERATA. Non e una funzione nuova (la scadenza
 * periodica forzata resta fuori dalla voce 39, §3 della roadmap): e la
 * conseguenza necessaria del fatto che la password e stata appena scritta.
 * `Rdacademy_Auth::login()` r.70-73 respinge chi ha una scadenza nel passato:
 * senza questo azzeramento l utente cambierebbe la password e non riuscirebbe
 * comunque a entrare, che e il difetto travestito da riparazione.
 *
 * REGOLE SORVEGLIATE
 *   RP-IL-TOKEN-NON-SI-CONSERVA-IN-CHIARO (GATE 28)
 *   RP-CAMBIO-PASSWORD-CHIUDE-LE-SESSIONI
 *   RP-LA-PORTA-NON-DICE-CHI-C-E-DENTRO — qui in forma di ritorni indistinti
 *   RP-UNA-REGOLA-DI-PASSWORD-SOLA — la password passa da Rdacademy_Password_Policy
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Rdacademy_Password_Token' ) ) :

final class Rdacademy_Password_Token {

	/** Prefissi di scopo. DUE caratteri contando i due punti. */
	const SCOPO_INVITO     = 'w';
	const SCOPO_REIMPOSTA  = 'r';
	// RelDoxAcademy 0.1.01A — tolto lo scopo del foglio di accesso stampato (ristorazione): restano invito e reimpostazione.

	/** Durate, decisione 4 della roadmap. */
	const MINUTI_INVITO    = 10080;   // 7 giorni
	const MINUTI_REIMPOSTA = 60;      // 60 minuti

	/* ===================================================================== *
	 *  IMPRONTA
	 * ===================================================================== */

	/**
	 * Cio che finisce sul database: `<scopo>:<sha256 del token grezzo>`.
	 * 66 caratteri esatti.
	 */
	private static function impronta( string $scopo, string $grezzo ): string {
		return $scopo . ':' . hash( 'sha256', $grezzo );
	}

	private static function scopi_ammessi(): array {
		return array( self::SCOPO_INVITO, self::SCOPO_REIMPOSTA );
	}

	public static function minuti_di( string $scopo ): int {
		return ( $scopo === self::SCOPO_INVITO ) ? self::MINUTI_INVITO : self::MINUTI_REIMPOSTA;
	}

	/* ===================================================================== *
	 *  EMISSIONE
	 * ===================================================================== */

	/**
	 * Emette un token per l utente e restituisce il SEGRETO IN CHIARO, che e
	 * l unico momento in cui esiste in chiaro in tutto il prodotto: il
	 * chiamante lo mette nel link della mail e non lo scrive da nessuna parte.
	 *
	 * @return string|null 64 caratteri esadecimali, oppure null se non scritto.
	 */
	public static function emetti( int $user_id, string $scopo ): ?string {
		if ( $user_id <= 0 || ! in_array( $scopo, self::scopi_ammessi(), true ) ) { return null; }

		$p       = RDACADEMY_TABLE_PREFIX;
		$grezzo  = bin2hex( random_bytes( 32 ) );
		$scadenza = gmdate( 'Y-m-d H:i:s', time() + ( self::minuti_di( $scopo ) * 60 ) );

		$fatto = Rdacademy_DB::execute(
			"UPDATE `{$p}users` SET reset_token = ?, reset_expiry = ? WHERE id = ?",
			array( self::impronta( $scopo, $grezzo ), $scadenza, $user_id ),
			'ssi'
		);
		// `execute()` torna le righe CAMBIATE, non un booleano: zero significa che
		// l utente non esiste (l impronta e sempre diversa dalla precedente).
		if ( $fatto <= 0 ) { return null; }

		return $grezzo;
	}

	/* ===================================================================== *
	 *  VERIFICA
	 * ===================================================================== */

	/**
	 * Verifica un token grezzo. NON dice mai perche fallisce: scaduto,
	 * consumato o inventato tornano tutti e tre `null`, che e cio che la scena 3
	 * stato D disegna — un solo stato per tre cause.
	 *
	 * Lo scopo NON arriva dal chiamante: si scopre dal prefisso trovato sul
	 * database. E la ragione per cui il prefisso sta dentro l impronta.
	 *
	 * @return array{utente:array,scopo:string}|null
	 */
	public static function verifica( string $grezzo ): ?array {
		$grezzo = trim( (string) $grezzo );
		if ( ! preg_match( '/^[0-9a-f]{64}$/', $grezzo ) ) { return null; }

		$p    = RDACADEMY_TABLE_PREFIX;
		$impr = array();
		foreach ( self::scopi_ammessi() as $s ) { $impr[ $s ] = self::impronta( $s, $grezzo ); }

		$riga = Rdacademy_DB::get_row(
			"SELECT * FROM `{$p}users`
			  WHERE reset_token IN (" . implode( ', ', array_fill( 0, count( $impr ), '?' ) ) . ")
			    AND reset_expiry IS NOT NULL
			    AND reset_expiry > ?
			  LIMIT 1",
			array_merge( array_values( $impr ), array( gmdate( 'Y-m-d H:i:s' ) ) ),
			str_repeat( 's', count( $impr ) + 1 )   // un segnaposto per scopo, più la scadenza
		);
		if ( ! $riga ) { return null; }

		// Un account sospeso o disabilitato non si riapre da un link: sarebbe
		// una porta laterale attorno alla decisione dell amministratore.
		if ( (string) ( $riga['status'] ?? '' ) !== 'active' ) { return null; }

		$scopo = substr( (string) $riga['reset_token'], 0, 1 );
		if ( ! in_array( $scopo, self::scopi_ammessi(), true ) ) { return null; }

		return array( 'utente' => $riga, 'scopo' => $scopo );
	}

	/* ===================================================================== *
	 *  CONSUMO
	 * ===================================================================== */

	/**
	 * Consuma il token e scrive la password nuova. E il PUNTO UNICO DI
	 * SCRITTURA della password da collegamento: la regola si applica qui, una
	 * volta sola, e chi chiama non la ripete.
	 *
	 * @return array{ok:bool,errore:?string,utente:?array,scopo:?string}
	 */
	public static function consuma( string $grezzo, string $password_nuova ): array {
		$esito = self::verifica( $grezzo );
		if ( $esito === null ) {
			return array( 'ok' => false, 'errore' => 'collegamento', 'utente' => null, 'scopo' => null );
		}

		$utente = $esito['utente'];
		$regola = Rdacademy_Password_Policy::verifica( (string) $password_nuova, $utente );
		if ( empty( $regola['ok'] ) ) {
			return array( 'ok' => false, 'errore' => (string) $regola['errore'], 'utente' => null, 'scopo' => null );
		}

		$p    = RDACADEMY_TABLE_PREFIX;
		$uid  = (int) $utente['id'];
		$hash = password_hash( (string) $password_nuova, PASSWORD_DEFAULT );

		$scritto = Rdacademy_DB::execute(
			"UPDATE `{$p}users`
			    SET password = ?, reset_token = NULL, reset_expiry = NULL,
			        failed_logins = 0, locked_until = NULL, password_expires_at = NULL
			  WHERE id = ? AND reset_token = ?",
			array( $hash, $uid, (string) $utente['reset_token'] ),
			'sis'
		);
		// Zero righe cambiate = qualcuno ha consumato lo stesso token nel frattempo:
		// la clausola `AND reset_token = ?` e la corsa vinta da uno solo.
		if ( $scritto <= 0 ) {
			return array( 'ok' => false, 'errore' => 'collegamento', 'utente' => null, 'scopo' => null );
		}

		// RP-CAMBIO-PASSWORD-CHIUDE-LE-SESSIONI. Tutte, comprese quelle su
		// altri dispositivi: e il senso della fascia blu della scena 3 stato B.
		Rdacademy_DB::execute(
			"DELETE FROM `{$p}sessions` WHERE user_id = ?",
			array( $uid ),
			'i'
		);

		if ( class_exists( 'Rdacademy_Auth' ) ) {
			Rdacademy_Auth::log_activity(
				$uid, 'auth', 'password_set',
				( ( $esito['scopo'] === self::SCOPO_INVITO )
					? 'Password impostata al primo accesso (invito)'
					: 'Password reimpostata da collegamento di recupero' )
			);
		}

		return array( 'ok' => true, 'errore' => null, 'utente' => $utente, 'scopo' => $esito['scopo'] );
	}

	/* ===================================================================== *
	 *  MISURA (sola lettura, per il banco 105)
	 * ===================================================================== */

	/**
	 * Conteggi sui token, senza mostrarne nessuno. Il banco misura QUANTI, mai
	 * QUALI: stampare un impronta non serve a nessuno e insegna a stamparla.
	 *
	 * @return array{vivi:int,scaduti:int,invito:int,reimposta:int,in_chiaro:int}
	 */
	/**
	 * 3.16.196 (S257) — LO STATO DEL COLLEGAMENTO, LETTO E BASTA.
	 *
	 * Dice se su quella riga utente c e un collegamento ancora vivo e di che
	 * scopo, SENZA toccare il database e SENZA mostrare niente del segreto:
	 * riceve i due campi che la lista ha gia in mano (`SELECT u.*`) e ne
	 * ricava tre parole. Il prefisso di scopo si legge dall impronta, che e
	 * esattamente la ragione per cui il prefisso sta dentro l impronta.
	 *
	 * ⛔ E UNA FUNZIONE PURA. Non interroga, non scrive, non registra: la lista
	 * utenti ha 100 righe e una funzione che tocca il database qui vorrebbe
	 * dire 100 query per una pastiglia. Chi la chiama ha gia la riga.
	 *
	 * @return array{tipo:string,vivo:bool,resta:int,parola:string}
	 *         tipo: 'invito' | 'reimposta' | 'ignoto' | 'nessuno'
	 *         resta: minuti mancanti alla scadenza (0 se non vivo)
	 */
	/**
	 * 0.6.10A (RF-BUG-018) — LA RIGA DICE QUANDO L INVITO E PARTITO. Non serve una
	 * colonna: la scadenza e scritta all emissione, e la durata e una costante.
	 * «Invito spedito il 06/10 alle 07:38 · scade fra 7g».
	 */
	public static function pillola_invito( $reset_token, $reset_expiry ): string {
		$st = self::stato_di( $reset_token, $reset_expiry );
		if ( ! $st['vivo'] || $st['tipo'] !== 'invito' ) { return ''; }
		$t = strtotime( trim( (string) $reset_expiry ) . ' UTC' ) - self::MINUTI_INVITO * 60;
		return 'Invito spedito il ' . wp_date( 'd/m', $t ) . ' alle ' . wp_date( 'H:i', $t ) . ' · scade fra ' . $st['parola'];
	}

	/** 0.6.10A (RF-BUG-018) — una spedizione fallita ritira il collegamento appena emesso. */
	public static function ritira( int $user_id ): void {
		if ( $user_id <= 0 ) { return; }
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::execute( "UPDATE `{$p}users` SET reset_token = NULL, reset_expiry = NULL WHERE id = ?", array( $user_id ), 'i' );
	}

	public static function stato_di( $reset_token, $reset_expiry ): array {
		$impr = trim( (string) $reset_token );
		$vuoto = array( 'tipo' => 'nessuno', 'vivo' => false, 'resta' => 0, 'parola' => '' );
		if ( $impr === '' ) { return $vuoto; }

		if ( strpos( $impr, self::SCOPO_INVITO . ':' ) === 0 ) {
			$tipo = 'invito';
		} elseif ( strpos( $impr, self::SCOPO_REIMPOSTA . ':' ) === 0 ) {
			$tipo = 'reimposta';
		} else {
			// Residuo anteriore alla 3.16.195: c e qualcosa, ma non e roba
			// nostra. Non si finge di saperne lo scopo (BKL-TOKEN-RESIDUI-IN-CHIARO).
			$tipo = 'ignoto';
		}

		$scad = trim( (string) $reset_expiry );
		if ( $scad === '' ) { return array( 'tipo' => $tipo, 'vivo' => false, 'resta' => 0, 'parola' => '' ); }

		$resta = (int) floor( ( strtotime( $scad . ' UTC' ) - time() ) / 60 );
		if ( $resta <= 0 ) { return array( 'tipo' => $tipo, 'vivo' => false, 'resta' => 0, 'parola' => '' ); }

		if ( $resta >= 1440 ) {
			$parola = (int) ceil( $resta / 1440 ) . 'g';
		} elseif ( $resta >= 60 ) {
			$parola = (int) ceil( $resta / 60 ) . 'h';
		} else {
			$parola = $resta . 'm';
		}
		return array( 'tipo' => $tipo, 'vivo' => true, 'resta' => $resta, 'parola' => $parola );
	}

	public static function conteggi(): array {
		$p   = RDACADEMY_TABLE_PREFIX;
		$ora = gmdate( 'Y-m-d H:i:s' );
		$r = Rdacademy_DB::get_row(
			"SELECT
			   SUM( reset_token IS NOT NULL AND reset_expiry > ? ) AS vivi,
			   SUM( reset_token IS NOT NULL AND ( reset_expiry IS NULL OR reset_expiry <= ? ) ) AS scaduti,
			   SUM( reset_token LIKE 'w:%' ) AS invito,
			   SUM( reset_token LIKE 'r:%' ) AS reimposta,
			   SUM( reset_token IS NOT NULL AND reset_token NOT LIKE 'w:%' AND reset_token NOT LIKE 'r:%' ) AS in_chiaro
			 FROM `{$p}users`",
			array( $ora, $ora ), 'ss'
		);
		return array(
			'vivi'      => (int) ( $r['vivi']      ?? 0 ),
			'scaduti'   => (int) ( $r['scaduti']   ?? 0 ),
			'invito'    => (int) ( $r['invito']    ?? 0 ),
			'reimposta' => (int) ( $r['reimposta'] ?? 0 ),
			'in_chiaro' => (int) ( $r['in_chiaro'] ?? 0 ),
		);
	}
}

endif;
