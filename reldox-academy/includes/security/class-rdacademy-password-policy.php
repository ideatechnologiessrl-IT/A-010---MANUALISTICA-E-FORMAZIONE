<?php
/**
 * class-rdacademy-password-policy.php — LA REGOLA DELLA PASSWORD, SCRITTA UNA VOLTA
 * Copiato da Reldox_Password_Policy, RelDox CRD 3.55.09G, senza tagli.
 * =============================================================================
 * 3.16.195 (S256) — voce 39 `SV-CREDENZIALI-E-RECUPERO`, chat 1 di 4.
 *
 * PERCHE ESISTE COME CLASSE E NON COME TRE CONTROLLI SPARSI.
 * `RP-UNA-REGOLA-DI-PASSWORD-SOLA`. Il contratto grafico (scena 3, stato C)
 * disegna un misuratore nel browser CHE DICE LE STESSE TRE FRASI che il server
 * userebbe per rifiutare. Se le frasi vivessero in due posti — una in
 * JavaScript e una in PHP — divergerebbero alla prima modifica, ed e
 * esattamente il difetto che GATE 26 sorveglia altrove: un fatto dichiarato
 * due volte diverge da solo.
 *
 * Qui la regola sta in UN posto. Il server chiama `verifica()`. Il browser
 * riceve `descrittore()`, che e la STESSA regola in forma di dati, e la applica
 * per accendere o spegnere il pulsante. La parola definitiva resta del server:
 * il misuratore e una cortesia, non un controllo.
 *
 * LE TRE CONDIZIONI, verbatim dal contratto:
 *   - almeno 12 caratteri
 *   - non contiene il tuo nome o la tua email
 *   - non e fra le password piu diffuse
 *
 * ⛔ NESSUN OBBLIGO DI COMPOSIZIONE. Niente maiuscola obbligatoria, niente
 * simbolo obbligatorio: e una decisione di prodotto scritta nella roadmap e
 * allineata alle linee guida vigenti (NIST SP 800-63B), che raccomandano
 * lunghezza e denylist invece delle regole di composizione, le quali producono
 * password prevedibili («Password1!») e appunti sotto la tastiera.
 *
 * ZERO SCHEMA, ZERO DATABASE: questa classe non legge e non scrive nulla.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Rdacademy_Password_Policy' ) ) :

final class Rdacademy_Password_Policy {

	/** Lunghezza minima. Unico numero, letto da entrambe le sponde. */
	const LUNGHEZZA_MINIMA = 12;

	/**
	 * 3.44.03 (S514, BUG-179) — sotto questa lunghezza una parola personale non
	 * conta. Era un 3 scritto dentro `parole_personali()`: ora ha un nome perche
	 * lo legge anche il browser (la scheda utente ricava le parole dai campi
	 * mentre si scrivono), e un numero scritto due volte diverge da solo.
	 * `RP-UNA-REGOLA-DI-PASSWORD-SOLA`.
	 */
	const PERSONALE_MINIMA = 3;

	/**
	 * Le password piu diffuse. Non e un dizionario completo — non deve esserlo:
	 * un elenco lungo si porta dietro un file e una manutenzione, e il grosso
	 * del rischio sta nelle prime decine. Confronto in minuscolo, per uguaglianza
	 * e non per contenimento: «password» va rifiutata, «passwordista» no.
	 */
	public static function elenco_banali(): array {
		// NUOVO nel guscio: le parole del nome di prodotto si ricavano da RDACADEMY_PRODUCT
		// invece di stare scritte a mano (lo script di rinomina cambia la costante,
		// l elenco segue da solo). Confronto per uguaglianza, come le altre voci.
		$prodotto = defined( 'RDACADEMY_PRODUCT' ) ? strtolower( (string) RDACADEMY_PRODUCT ) : '';
		$parole   = array_filter( array_merge( preg_split( '/\s+/', $prodotto ), array( str_replace( ' ', '', $prodotto ) ) ) );
		return array_merge( array_values( $parole ), array(
			'password', 'password1', 'password123', 'passw0rd', 'p@ssword',
			'123456', '1234567', '12345678', '123456789', '1234567890',
			'qwerty', 'qwertyuiop', 'qwerty123', 'asdfghjkl', 'zxcvbnm',
			'abc123', 'abcd1234', 'iloveyou', 'admin', 'administrator',
			'letmein', 'welcome', 'monkey', 'dragon', 'sunshine', 'princess',
			'football', 'baseball', 'superman', 'trustno1', 'starwars',
			'ciao', 'ciaociao', 'benvenuto', 'password!', 'cambiami',
			'segreta', 'segreto', 'juventus', 'milano', 'napoli', 'roma',
			'reldox123', 'ideatechnologies', 'azienda123',
			'11111111', '00000000', '87654321', 'qazwsxedc', 'q1w2e3r4',
		) );
	}

	/**
	 * Le parole personali che una password non deve contenere: nome, cognome,
	 * login e la parte locale dell email. Sotto i 3 caratteri si scartano,
	 * altrimenti un cognome «Re» renderebbe irrifiutabile mezza lingua.
	 */
	public static function parole_personali( array $utente ): array {
		$fuori = array();
		$campi = array(
			(string) ( $utente['first_name'] ?? '' ),
			(string) ( $utente['last_name']  ?? '' ),
			(string) ( $utente['login']      ?? '' ),
		);
		$email = (string) ( $utente['email'] ?? '' );
		if ( $email !== '' ) {
			$campi[] = substr( $email, 0, strpos( $email . '@', '@' ) );
		}
		foreach ( $campi as $c ) {
			// 3.44.03 (S514, BUG-179): mb_, come il browser. Con strtolower «Élise»
			// restava «Élise» e non si trovava dentro «xxélise…»; con strlen «Lù»
			// contava 3 (byte) contro i 2 caratteri del browser.
			$c = mb_strtolower( trim( $c ), 'UTF-8' );
			if ( mb_strlen( $c, 'UTF-8' ) >= self::PERSONALE_MINIMA ) { $fuori[] = $c; }
		}
		return array_values( array_unique( $fuori ) );
	}

	/**
	 * Le tre condizioni, ognuna col proprio esito. L ORDINE E QUELLO DEL
	 * CONTRATTO e non si cambia: la schermata le elenca in quest ordine.
	 *
	 * @return array<int,array{id:string,testo:string,ok:bool}>
	 */
	public static function regole( string $password, array $utente ): array {
		$pwd  = (string) $password;
		$low  = strtolower( $pwd );

		// 3.44.03 (S514, BUG-179): la ricerca e UNA, in `parola_trovata()`, che
		// serve anche al motivo del rifiuto.
		$contiene = ( self::parola_trovata( $pwd, $utente ) !== '' );

		return array(
			array(
				'id'    => 'lunghezza',
				'testo' => 'almeno ' . self::LUNGHEZZA_MINIMA . ' caratteri',
				'ok'    => ( mb_strlen( $pwd ) >= self::LUNGHEZZA_MINIMA ),
			),
			array(
				'id'    => 'personale',
				'testo' => 'non contiene il tuo nome o la tua email',
				'ok'    => ( $pwd !== '' && ! $contiene ),
			),
			array(
				'id'    => 'banale',
				'testo' => 'non è fra le password più diffuse', // LOTTO-D: accenti corretti
				'ok'    => ( $pwd !== '' && ! in_array( $low, self::elenco_banali(), true ) ),
			),
		);
	}

	/**
	 * 3.44.03 (S514, BUG-179) — QUALE parola personale sta dentro la password.
	 * Stringa vuota se nessuna. Serve al rifiuto: dire «contiene "domenico"» fa
	 * correggere al primo colpo, ripetere la regola no.
	 */
	public static function parola_trovata( string $password, array $utente ): string {
		$low = mb_strtolower( (string) $password, 'UTF-8' );
		if ( $low === '' ) { return ''; }
		foreach ( self::parole_personali( $utente ) as $parola ) {
			if ( strpos( $low, $parola ) !== false ) { return $parola; }
		}
		return '';
	}

	/**
	 * 3.44.03 (S514, BUG-179) — IL MOTIVO DEL RIFIUTO, UNO PER REGOLA.
	 *
	 * ⛔ PERCHE NON SI RIUSA `testo`. `testo` e la regola scritta in positivo
	 * («non contiene il tuo nome o la tua email»): giusta in una lista con la
	 * spunta, ma messa dopo «non rispetta la regola:» si legge al contrario —
	 * sembra che la password DEBBA contenere il nome. Cosi l owner si e fermato
	 * sul primo utente di un installazione nuova (S514). Il rifiuto dice che
	 * cosa non va e, quando puo, con quale parola.
	 *
	 * ⛔ In terza persona («dell utente»): lo stesso motivo lo legge chi sceglie
	 * la propria password e l amministratore che la scrive per un altro.
	 */
	public static function motivo( string $id, string $password, array $utente ): string {
		$password = (string) $password;
		if ( $password === '' ) { return 'è vuota'; }
		switch ( $id ) {
			case 'lunghezza':
				return 'è troppo corta: servono almeno ' . self::LUNGHEZZA_MINIMA
					. ' caratteri e ne ha ' . mb_strlen( $password );
			case 'personale':
				$w = self::parola_trovata( $password, $utente );
				return 'contiene «' . $w . '», che fa parte del nome, del cognome, del login'
					. ' o dell’email dell’utente: va tolta';
			case 'banale':
				return 'è fra le password più diffuse';
		}
		return 'non è accettata';
	}

	/**
	 * Verdetto del SERVER. E il punto unico di scrittura: chi salva una password
	 * passa di qui, sempre.
	 *
	 * @return array{ok:bool,errore:?string,regole:array}
	 */
	public static function verifica( string $password, array $utente ): array {
		$regole = self::regole( $password, $utente );
		$rotte  = array();
		foreach ( $regole as $r ) {
			if ( empty( $r['ok'] ) ) {
				// Una password vuota rompe tutte e tre: il motivo si dice una volta.
				$m = self::motivo( (string) $r['id'], (string) $password, $utente );
				if ( ! in_array( $m, $rotte, true ) ) { $rotte[] = $m; }
			}
		}
		return array(
			'ok'     => empty( $rotte ),
			// 3.44.03 (S514, BUG-179): il MOTIVO, non la regola. Vedi `motivo()`.
			'errore' => empty( $rotte ) ? null : ( 'Password non accettata: ' . implode( '; ', $rotte ) . '.' ),
			'regole' => $regole,
		);
	}

	/**
	 * La STESSA regola in forma di dati, per il browser. Il misuratore della
	 * scena 3 legge questo e nient altro: nessun numero e nessuna frase
	 * riscritta a mano dentro il JavaScript.
	 *
	 * ⛔ Le parole personali viaggiano gia in chiaro nella schermata (la scena 3
	 * mostra nome ed email dell utente sotto il titolo), quindi mandarle qui non
	 * espone niente che non sia gia sott occhio a chi ha aperto quel link.
	 * L elenco delle banali invece NON viaggia: al browser basta l esito, e
	 * spedire un dizionario a ogni apertura sarebbe peso inutile.
	 */
	public static function descrittore( array $utente, string $lingua = 'it' ): array {
		// 0.8.2A — le stesse regole, le frasi nella lingua della persona (la pagina «scegli la password» in inglese).
		// I numeri non cambiano con la lingua: soglie e minimi restano una cosa sola.
		$L = class_exists( 'Rdacademy_Lingua' ) ? Rdacademy_Lingua::valida( $lingua ) : 'it';
		$T = static function ( string $k ) use ( $L ): string { return class_exists( 'Rdacademy_Lingua' ) ? Rdacademy_Lingua::t( $k, $L ) : $k; };
		// 1.17.0L — un blocco solo per tutte le lingue: le frasi stanno in TESTI (it / en / de), i numeri restano una cosa sola.
		return array(
			'minimo'         => self::LUNGHEZZA_MINIMA,
			'personali'      => self::parole_personali( $utente ),
			// 3.44.03 (S514, BUG-179) — la scheda di un utente NUOVO non ha ancora un utente da passare qui: ricava le parole dai
			// campi mentre l amministratore li scrive, e le serve LA STESSA soglia.
			'personali_min'  => self::PERSONALE_MINIMA,
			// 3.44.03 (S514, BUG-179) — la regola personale detta a chi scrive la password PER UN ALTRO (scheda utente).
			'testo_altrui'   => $T( 'pwd.altrui' ),
			// 3.44.03 (S514, BUG-179) — la parola della robustezza quando una regola è rotta.
			'parola_rifiuto' => $T( 'pwd.rifiuto' ),
			'testi'          => array(
				'lunghezza' => sprintf( $T( 'pwd.lunghezza' ), self::LUNGHEZZA_MINIMA ),
				'personale' => $T( 'pwd.personale' ),
				'banale'    => $T( 'pwd.banale' ),
			),
			// 3.16.196 (S257) — LA SCALA VIAGGIA COME DATO: le stesse quattro parole di `robustezza()` alle stesse soglie
			// (`RP-UNA-REGOLA-DI-PASSWORD-SOLA`).
			'scala'          => array(
				'soglie'      => array( 12, 16, 20 ),
				'varieta_min' => 3,
				'parole'      => explode( '|', $T( 'pwd.scala' ) ),
			),
		);
	}

	/**
	 * Robustezza: quattro tacche, come le quattro barrette del contratto.
	 * Non e una regola di ammissione — una password puo essere ammessa e
	 * comunque non «ottima» — ed e per questo che vive separata da `verifica()`.
	 *
	 * @return array{livello:int,parola:string}
	 */
	public static function robustezza( string $password ): array {
		$n = mb_strlen( (string) $password );
		if ( $n < self::LUNGHEZZA_MINIMA ) {
			return array( 'livello' => 1, 'parola' => 'insufficiente' );
		}
		$varieta = 0;
		if ( preg_match( '/[a-z]/', $password ) ) { $varieta++; }
		if ( preg_match( '/[A-Z]/', $password ) ) { $varieta++; }
		if ( preg_match( '/[0-9]/', $password ) ) { $varieta++; }
		if ( preg_match( '/[^A-Za-z0-9]/', $password ) ) { $varieta++; }

		$punti = 0;
		if ( $n >= 12 ) { $punti++; }
		if ( $n >= 16 ) { $punti++; }
		if ( $n >= 20 ) { $punti++; }
		if ( $varieta >= 3 ) { $punti++; }

		if ( $punti >= 4 ) { return array( 'livello' => 4, 'parola' => 'ottima' ); }
		if ( $punti === 3 ) { return array( 'livello' => 3, 'parola' => 'buona' ); }
		if ( $punti === 2 ) { return array( 'livello' => 2, 'parola' => 'sufficiente' ); }
		return array( 'livello' => 2, 'parola' => 'sufficiente' );
	}
}

endif;
