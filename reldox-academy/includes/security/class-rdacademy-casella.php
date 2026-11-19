<?php
/**
 * class-rdacademy-casella.php — LA CASELLA DI SERVIZIO: DATI, PROVE, SPEDIZIONE
 * =============================================================================
 * 0.6.09A (S9, RF-BUG-016). NUOVO, sul motore di RelDox CRD 3.56.06.
 *
 * IL DIFETTO CHE CHIUDE. Fino alla 0.6.08A la casella era «Configurata»
 * appena compilati server e mittente: nessuno provava a parlarle. Su un server
 * di produzione il campo SMTP portava il server di RICEZIONE (imaps.aruba.it): ogni
 * invito restava appeso al collegamento, con il pulsante grigio e nessun
 * segnale, fino al tempo massimo di PHPMailer (300 secondi). Il «Prova d invio
 * a secco» ripeteva i dati salvati e non provava niente.
 *
 * DA QUI:
 *  - la casella e VERIFICATA solo dopo una spedizione vera riuscita, e
 *    l attestato vale per i dati con cui e stata fatta (impronta): cambiare
 *    server, porta, sicurezza, utente, mittente o password lo spegne;
 *  - gli inviti partono solo da una casella verificata
 *    (Rdacademy_Password_Mail::stato() chiede qui);
 *  - ogni spedizione ha un tempo massimo di 20 secondi per passo
 *    (Timeout E Timelimit di PHPMailer: il secondo e quello che restava a 300);
 *  - la prova dice passo per passo che cosa e successo, con la risposta
 *    letterale del server e il rimedio, e si copia per intero.
 *
 * COPIATO DA RELDOX CRD 3.56.06: il motore d invio (Reldox_Mailer::smtp_send,
 * PHPMailer di WordPress per-invio, Timeout 20), la copia in «Inviati» dopo
 * l invio riuscito e mai al posto suo (fail-soft), i nomi delle cartelle
 * «Inviati». NUOVO: l IMAP si parla senza estensione (Rdacademy_Imap_Diretta).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Rdacademy_Casella' ) ) :

final class Rdacademy_Casella {

	/** Secondi massimi per ogni passo di una spedizione vera. */
	const ATTESA_INVIO = 20;
	/** Secondi massimi per ogni passo della prova. */
	const ATTESA_PROVA = 10;

	/** Le chiavi nuove della 0.6.09A, accanto a Rdacademy_Password_Mail::CHIAVI. */
	const CHIAVI_IMAP = array(
		'imap_host'      => 'mail_servizio_imap_host',
		'imap_porta'     => 'mail_servizio_imap_porta',
		'imap_sicurezza' => 'mail_servizio_imap_sicurezza',
		'salva_inviati'  => 'mail_servizio_salva_inviati',
		'cartella'       => 'mail_servizio_cartella_inviati',
	);
	const CHIAVE_VERIFICA      = 'mail_servizio_verifica';
	const CHIAVE_VERIFICA_IMAP = 'mail_servizio_verifica_imap';

	/* ===================================================================== *
	 *  I DATI
	 * ===================================================================== */

	/** Tutte le chiavi della casella, la password come sta a riposo (cifrata). */
	public static function leggi(): array {
		$K = Rdacademy_Password_Mail::CHIAVI + self::CHIAVI_IMAP;
		$K['verifica'] = self::CHIAVE_VERIFICA;
		$K['verifica_imap'] = self::CHIAVE_VERIFICA_IMAP;
		$p = RDACADEMY_TABLE_PREFIX;
		$segn = implode( ',', array_fill( 0, count( $K ), '?' ) );
		$rows = Rdacademy_DB::get_results(
			"SELECT chiave, valore FROM `{$p}impostazioni` WHERE chiave IN ({$segn})",
			array_values( $K ), str_repeat( 's', count( $K ) )
		);
		$per = array();
		foreach ( (array) $rows as $r ) { $per[ (string) $r['chiave'] ] = (string) $r['valore']; }
		$c = array();
		foreach ( $K as $k => $chiave ) { $c[ $k ] = trim( $per[ $chiave ] ?? '' ); }
		if ( $c['porta'] === '' ) $c['porta'] = '587';
		if ( $c['sicurezza'] === '' ) $c['sicurezza'] = 'tls';
		if ( $c['imap_porta'] === '' ) $c['imap_porta'] = '993';
		if ( $c['imap_sicurezza'] === '' ) $c['imap_sicurezza'] = 'ssl';
		if ( $c['salva_inviati'] === '' ) $c['salva_inviati'] = '1';
		return $c;
	}

	/** L utente con cui si entra: lo Username se c e, altrimenti l indirizzo. */
	public static function utente_di( array $c ): string {
		return $c['utente'] !== '' ? $c['utente'] : $c['mittente'];
	}

	public static function password_di( array $c ): string {
		return $c['password'] === '' ? '' : ( class_exists( 'Rdacademy_Cifra' ) ? Rdacademy_Cifra::apri( $c['password'] ) : $c['password'] );
	}

	/** L impronta dei dati della spedizione: se cambia, l attestato non vale piu. */
	public static function impronta_smtp( array $c ): string {
		return hash( 'sha256', implode( "\n", array( 'smtp', strtolower( $c['host'] ), $c['porta'], $c['sicurezza'], $c['utente'], strtolower( $c['mittente'] ), $c['password'] ) ) );
	}

	public static function impronta_imap( array $c ): string {
		return hash( 'sha256', implode( "\n", array( 'imap', strtolower( $c['imap_host'] ), $c['imap_porta'], $c['imap_sicurezza'], $c['utente'], strtolower( $c['mittente'] ), $c['password'] ) ) );
	}

	/**
	 * Lo stato della casella, letto da UN posto.
	 * @return array{compilata:bool, verificata:bool, quando:string, a:string, imap_verificata:bool, imap_quando:string, motivo:string}
	 */
	public static function stato( ?array $c = null ): array {
		$c = $c ?? self::leggi();
		$out = array( 'compilata' => false, 'verificata' => false, 'quando' => '', 'a' => '', 'imap_verificata' => false, 'imap_quando' => '', 'motivo' => '' );
		if ( $c['host'] === '' && $c['mittente'] === '' ) {
			$out['motivo'] = 'Nessuna casella di servizio dichiarata.';
			return $out;
		}
		if ( $c['host'] === '' || ! is_email( $c['mittente'] ) ) {
			$out['motivo'] = 'La casella di servizio è incompleta: servono il server SMTP e un indirizzo email valido.';
			return $out;
		}
		$out['compilata'] = true;
		$v = json_decode( $c['verifica'], true );
		if ( is_array( $v ) && hash_equals( (string) ( $v['impronta'] ?? '' ), self::impronta_smtp( $c ) ) ) {
			$out['verificata'] = true;
			$out['quando'] = (string) ( $v['quando'] ?? '' );
			$out['a'] = (string) ( $v['a'] ?? '' );
		} else {
			$out['motivo'] = is_array( $v )
				? 'I dati della casella sono cambiati dopo l\'ultima prova: va provata di nuovo (Impostazioni → «Modifica» → Email → «Prova la spedizione»).'
				: 'La casella di servizio non è ancora verificata: Impostazioni → «Modifica» → Email → «Prova la spedizione».';
		}
		$vi = json_decode( $c['verifica_imap'], true );
		if ( $c['imap_host'] !== '' && is_array( $vi ) && hash_equals( (string) ( $vi['impronta'] ?? '' ), self::impronta_imap( $c ) ) ) {
			$out['imap_verificata'] = true;
			$out['imap_quando'] = (string) ( $vi['quando'] ?? '' );
		}
		return $out;
	}

	/** Istante UTC in parole, nel fuso del sito. */
	public static function in_parole( string $utc ): string {
		if ( $utc === '' ) return '';
		$t = strtotime( $utc . ' UTC' );
		return $t ? wp_date( 'd/m/Y \a\l\l\e H:i', $t ) : '';
	}

	/* ===================================================================== *
	 *  I CONSIGLI: dati che si vedono sbagliati prima ancora di provare
	 * ===================================================================== */

	/** @return array<int,string> */
	public static function avvisi_smtp( array $c ): array {
		$a = array();
		$h = strtolower( $c['host'] );
		if ( preg_match( '/^(imap|imaps|pop|pop3|pops|mx)\./', $h ) ) {
			$a[] = '«' . $c['host'] . '» è un server di RICEZIONE della posta: per spedire serve il server SMTP' . ( strpos( $h, 'aruba.it' ) !== false ? ' (per Aruba: smtps.aruba.it)' : '' ) . '.';
		}
		$porta = (int) $c['porta'];
		if ( $porta === 465 && $c['sicurezza'] !== 'ssl' ) $a[] = 'La porta 465 vuole la sicurezza SSL/TLS.';
		if ( $porta === 587 && $c['sicurezza'] === 'ssl' ) $a[] = 'La porta 587 vuole la sicurezza STARTTLS, non SSL/TLS.';
		if ( in_array( $porta, array( 993, 143, 110, 995 ), true ) ) $a[] = 'La porta ' . $porta . ' è una porta di RICEZIONE: per spedire si usa 465 (SSL/TLS) o 587 (STARTTLS).';
		return $a;
	}

	/** @return array<int,string> */
	public static function avvisi_imap( array $c ): array {
		$a = array();
		$h = strtolower( $c['imap_host'] );
		if ( preg_match( '/^(smtp|smtps|mail-out|out)\./', $h ) ) {
			$a[] = '«' . $c['imap_host'] . '» è un server di SPEDIZIONE: per leggere le cartelle serve il server IMAP' . ( strpos( $h, 'aruba.it' ) !== false ? ' (per Aruba: imaps.aruba.it)' : '' ) . '.';
		}
		$porta = (int) $c['imap_porta'];
		if ( $porta === 993 && $c['imap_sicurezza'] !== 'ssl' ) $a[] = 'La porta 993 vuole la sicurezza SSL/TLS.';
		if ( $porta === 143 && $c['imap_sicurezza'] === 'ssl' ) $a[] = 'La porta 143 vuole STARTTLS o nessuna cifratura, non SSL/TLS.';
		return $a;
	}

	/** «Come si configura»: la scheda d aiuto (forma di Reldox_Imap::provider_help, solo IMAP). */
	public static function aiuto( string $imap_host = '', string $smtp_host = '' ): array {
		$aruba = ( stripos( $imap_host . $smtp_host, 'aruba' ) !== false );
		if ( $aruba ) {
			return array(
				'title'  => 'Configurazione Aruba',
				'params' => 'IMAP imaps.aruba.it · porta 993 · SSL/TLS — SMTP smtps.aruba.it · porta 465 · SSL/TLS (oppure 587 · STARTTLS) — utenza: indirizzo email completo',
				'steps'  => array(
					'Server IMAP (ricezione): imaps.aruba.it, porta 993, SSL/TLS.',
					'Server SMTP (spedizione): smtps.aruba.it, porta 465 con SSL/TLS, oppure 587 con STARTTLS.',
					'Username: l\'indirizzo email completo della casella; Password: quella della casella (la stessa della webmail).',
					'Salva, poi «Testa» per l\'IMAP e «Prova la spedizione» per l\'SMTP.',
				),
				'note'   => 'Attenzione: imaps.aruba.it riceve e basta: nel campo «Server SMTP» va smtps.aruba.it.',
				'url'    => 'https://guide.hosting.aruba.it/email/configurazione-caselle-email/',
			);
		}
		return array(
			'title'  => 'Configurazione IMAP generico',
			'params' => 'Usa host, porta e SSL indicati dal tuo provider — utenza: di norma l\'indirizzo email completo',
			'steps'  => array(
				'Verifica sul sito del tuo provider host IMAP, porta e modalità SSL corretti.',
				'Assicurati che l\'accesso IMAP sia abilitato per la casella (alcuni provider lo disattivano di default).',
				'Usa come utenza l\'indirizzo email completo e la password della casella.',
				'Il server SMTP è un altro indirizzo: di solito 465 con SSL/TLS oppure 587 con STARTTLS.',
				'Salva, poi «Testa» per l\'IMAP e «Prova la spedizione» per l\'SMTP.',
			),
			'note'   => 'Se non conosci i parametri, cercali come «impostazioni IMAP e SMTP» sul sito del tuo provider di posta.',
			'url'    => '',
		);
	}

	/* ===================================================================== *
	 *  LA PROVA DELL IMAP — «Testa»
	 * ===================================================================== */

	/**
	 * @return array{ok:bool, passi:array, cartelle:array<int,string>, proposta:string, registro:array}
	 */
	public static function prova_imap( array $c ): array {
		$passi = array();
		$fine = static function ( bool $ok, array $passi, array $extra = array() ) {
			return array_merge( array( 'ok' => $ok, 'passi' => $passi, 'cartelle' => array(), 'proposta' => '', 'registro' => array() ), $extra );
		};
		foreach ( self::avvisi_imap( $c ) as $av ) {
			$passi[] = self::passo( 'Dati della casella', 'avviso', '', $av );
		}
		if ( $c['imap_host'] === '' ) {
			$passi[] = self::passo( 'Server IMAP', 'ko', '', 'Scrivi il server IMAP (per Aruba: imaps.aruba.it).' );
			return $fine( false, $passi );
		}
		$pwd = self::password_di( $c );
		if ( $pwd === '' ) {
			$passi[] = self::passo( 'Password', 'ko', '', 'Scrivi la password della casella e salva.' );
			return $fine( false, $passi );
		}
		// 1. Il nome del server
		$ip = self::risolvi( $c['imap_host'] );
		if ( $ip === null ) {
			$passi[] = self::passo( 'Il nome del server esiste', 'ko', 'nessun indirizzo per «' . $c['imap_host'] . '»', 'Il nome del server non esiste: controlla che sia scritto giusto.' );
			return $fine( false, $passi );
		}
		$passi[] = self::passo( 'Il nome del server esiste', 'ok', $c['imap_host'] . ' → ' . $ip );
		// 2. Collegamento (e cifratura)
		$im = new Rdacademy_Imap_Diretta();
		$col = $im->collega( $c['imap_host'], (int) $c['imap_porta'], $c['imap_sicurezza'] );
		if ( ! $col['ok'] ) {
			$passi[] = self::passo( 'Il server risponde su quella porta' . ( $c['imap_sicurezza'] !== 'nessuna' ? ' (cifrato)' : '' ), 'ko', $col['errore'], self::rimedio_collegamento( $col['errore'], (int) $c['imap_porta'], $c['imap_sicurezza'], 'imap' ) );
			return $fine( false, $passi, array( 'registro' => $im->registro() ) );
		}
		$passi[] = self::passo( 'Il server risponde su quella porta' . ( $c['imap_sicurezza'] === 'ssl' ? ' (SSL/TLS)' : ( $c['imap_sicurezza'] === 'tls' ? ' (STARTTLS)' : '' ) ), 'ok', $col['saluto'] );
		// 3. Accesso
		$acc = $im->accedi( self::utente_di( $c ), $pwd );
		if ( ! $acc['ok'] ) {
			$passi[] = self::passo( 'Utente e password accettati', 'ko', $acc['testo'], 'Il server ha rifiutato l\'accesso: controlla che lo Username sia l\'indirizzo email completo e che la password sia quella della casella.' );
			$im->esci();
			return $fine( false, $passi, array( 'registro' => $im->registro() ) );
		}
		$passi[] = self::passo( 'Utente e password accettati', 'ok', $acc['testo'] );
		// 4. Cartelle
		$ls = $im->cartelle();
		$nomi = array_map( static function ( $x ) { return $x['nome']; }, $ls['cartelle'] );
		$scelta = Rdacademy_Imap_Diretta::scegli_inviata( $ls['cartelle'] );
		$passi[] = self::passo( 'Le cartelle si leggono', $ls['ok'] ? 'ok' : 'avviso', $ls['ok'] ? count( $nomi ) . ' cartelle' . ( $scelta ? ' · «Inviata» = ' . $scelta['nome'] : ' · nessuna cartella «Inviata» riconosciuta' ) : $ls['testo'] );
		$im->esci();
		return $fine( true, $passi, array( 'cartelle' => $nomi, 'proposta' => $scelta ? $scelta['nome'] : '', 'registro' => $im->registro() ) );
	}

	/* ===================================================================== *
	 *  LA PROVA DELLA SPEDIZIONE — passo per passo, con la risposta del server
	 * ===================================================================== */

	/**
	 * Spedisce DAVVERO un messaggio di prova a $a, un passo alla volta, con il
	 * motore SMTP di PHPMailer (lo stesso di wp_mail e del CRD). Ogni passo
	 * porta la risposta letterale del server.
	 *
	 * @return array{ok:bool, passi:array, registro:array}
	 */
	public static function prova_spedizione( array $c, string $a ): array {
		$passi = array();
		$registro = array();
		foreach ( self::avvisi_smtp( $c ) as $av ) {
			$passi[] = self::passo( 'Dati della casella', 'avviso', '', $av );
		}
		$fine = static function ( bool $ok ) use ( &$passi, &$registro, $a, $c ) {
			if ( class_exists( 'Rdacademy_Spedizioni' ) ) {   // 1.4.4B — anche la prova nel registro delle spedizioni
				try { Rdacademy_Spedizioni::prova( $a, $ok, $passi, (string) ( $c['mittente'] ?? '' ) ); } catch ( \Throwable $x ) {}
			}
			return array( 'ok' => $ok, 'passi' => $passi, 'registro' => $registro );
		};
		if ( ! is_email( $a ) ) {
			$passi[] = self::passo( 'Destinatario della prova', 'ko', '', 'Scrivi un indirizzo valido in «Spedisci la prova a».' );
			return $fine( false );
		}
		if ( ! self::carica_phpmailer() ) {
			$passi[] = self::passo( 'Motore di spedizione', 'ko', '', 'PHPMailer non è disponibile in questa installazione di WordPress.' );
			return $fine( false );
		}
		$host = $c['host']; $porta = (int) $c['porta']; $sic = $c['sicurezza'];

		// 1. Il nome del server esiste
		$ip = self::risolvi( $host );
		if ( $ip === null ) {
			$passi[] = self::passo( 'Il nome del server esiste', 'ko', 'nessun indirizzo per «' . $host . '»', 'Il nome del server non esiste: controlla che sia scritto giusto (per Aruba: smtps.aruba.it).' );
			return $fine( false );
		}
		$passi[] = self::passo( 'Il nome del server esiste', 'ok', $host . ' → ' . $ip );

		$smtp = new \PHPMailer\PHPMailer\SMTP();
		$smtp->Timeout   = self::ATTESA_PROVA;
		$smtp->Timelimit = self::ATTESA_PROVA;
		$smtp->do_debug  = \PHPMailer\PHPMailer\SMTP::DEBUG_CONNECTION;
		$smtp->Debugoutput = self::registratore( $registro );

		// 2. Collegamento (con SSL/TLS se la porta lo vuole subito)
		$ok = $smtp->connect( ( $sic === 'ssl' ? 'ssl://' : '' ) . $host, $porta, self::ATTESA_PROVA, array( 'ssl' => array( 'verify_peer' => true, 'verify_peer_name' => true ) ) );
		$saluto = trim( (string) $smtp->getLastReply() );
		$err_c  = (array) $smtp->getError();
		// ⛔ PHPMailer risponde «false» sia quando il collegamento non si apre, sia quando
		// si apre e il saluto non e 220 (un server IMAP, un server muto): sono due passi
		// diversi e si dicono diversi. Solo «Failed to connect» e il collegamento mancato.
		$non_aperto = ! $ok && $saluto === '' && stripos( (string) ( $err_c['error'] ?? '' ), 'connect' ) !== false;
		if ( $non_aperto ) {
			$err = self::errore_di( $smtp );
			$passi[] = self::passo( 'Il server risponde su quella porta' . ( $sic === 'ssl' ? ' (SSL/TLS)' : '' ), 'ko', $err, self::rimedio_collegamento( $err, $porta, $sic, 'smtp' ) );
			return $fine( false );
		}
		$passi[] = self::passo( 'Il server risponde su quella porta' . ( $sic === 'ssl' ? ' (SSL/TLS)' : '' ), 'ok', 'collegato a ' . $host . ':' . $porta );

		// 3. Il server si presenta come server di posta in uscita
		if ( strpos( $saluto, '220' ) !== 0 ) {
			$rim = ( $saluto === '' )
				? 'Il server non ha salutato entro ' . self::ATTESA_PROVA . ' secondi: non è un server di spedizione su questa porta, oppure la porta vuole SSL/TLS (465) e la sicurezza scelta è un\'altra.'
				: ( strpos( $saluto, '* OK' ) === 0 ? 'Ha risposto un server IMAP (ricezione), non un server di spedizione: nel campo «Server SMTP» va il server SMTP (per Aruba: smtps.aruba.it).' : 'Questa non è la risposta di un server di spedizione.' );
			$passi[] = self::passo( 'Si presenta come server di posta in uscita', 'ko', $saluto === '' ? '(nessuna risposta)' : $saluto, $rim );
			$smtp->close();
			return $fine( false );
		}
		$passi[] = self::passo( 'Si presenta come server di posta in uscita', 'ok', $saluto );

		$nome_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! $smtp->hello( $nome_host !== '' ? $nome_host : 'localhost' ) ) {
			$err = self::errore_di( $smtp );
			$passi[] = self::passo( 'Presentazione (EHLO)', 'ko', $err, 'Il server non ha accettato la presentazione.' );
			$smtp->close();
			return $fine( false );
		}

		// 4. Cifratura
		if ( $sic === 'tls' ) {
			if ( ! $smtp->startTLS() ) {
				$err = self::errore_di( $smtp );
				$passi[] = self::passo( 'La connessione cifrata parte (STARTTLS)', 'ko', $err, $porta === 465 ? 'La porta 465 vuole SSL/TLS dal primo istante: scegli «SSL/TLS».' : 'La cifratura STARTTLS non è partita: prova la porta 465 con SSL/TLS.' );
				$smtp->close();
				return $fine( false );
			}
			$smtp->hello( $nome_host !== '' ? $nome_host : 'localhost' );
			$passi[] = self::passo( 'La connessione cifrata parte (STARTTLS)', 'ok', trim( (string) $smtp->getLastReply() ) );
		} elseif ( $sic === 'ssl' ) {
			$passi[] = self::passo( 'La connessione è cifrata', 'ok', 'SSL/TLS dal collegamento' );
		} else {
			$passi[] = self::passo( 'La connessione è cifrata', 'avviso', 'nessuna cifratura', 'Senza cifratura la password viaggia in chiaro: usala solo in una rete interna.' );
		}

		// 5. Accesso
		$utente = self::utente_di( $c ); $pwd = self::password_di( $c );
		if ( $c['utente'] === '' && $pwd === '' ) {
			$passi[] = self::passo( 'Utente e password accettati', 'saltato', 'server senza autenticazione' );
		} elseif ( ! $smtp->authenticate( $utente, $pwd ) ) {
			$err = self::errore_di( $smtp );
			$passi[] = self::passo( 'Utente e password accettati', 'ko', $err, 'Il server ha rifiutato l\'accesso: lo Username è di norma l\'indirizzo email completo e la password è quella della casella.' );
			$smtp->close();
			return $fine( false );
		} else {
			$passi[] = self::passo( 'Utente e password accettati', 'ok', trim( (string) $smtp->getLastReply() ) );
		}

		// 6. Mittente
		if ( ! $smtp->mail( $c['mittente'] ) ) {
			$err = self::errore_di( $smtp );
			$passi[] = self::passo( 'Il mittente viene accettato', 'ko', $err, 'Il server non accetta «' . $c['mittente'] . '» come mittente: di norma deve essere l\'indirizzo della casella con cui si entra.' );
			$smtp->close();
			return $fine( false );
		}
		$passi[] = self::passo( 'Il mittente viene accettato', 'ok', trim( (string) $smtp->getLastReply() ) );

		// 7. Destinatario
		if ( ! $smtp->recipient( $a ) ) {
			$err = self::errore_di( $smtp );
			$passi[] = self::passo( 'Il destinatario viene accettato', 'ko', $err, 'Il server ha rifiutato il destinatario «' . $a . '».' );
			$smtp->close();
			return $fine( false );
		}
		$passi[] = self::passo( 'Il destinatario viene accettato', 'ok', trim( (string) $smtp->getLastReply() ) );

		// 8. Consegna del messaggio
		$mime = self::messaggio_di_prova( $c, $a );
		if ( $mime === '' || ! $smtp->data( $mime ) ) {
			$err = $mime === '' ? 'messaggio non composto' : self::errore_di( $smtp );
			$passi[] = self::passo( 'Il messaggio parte', 'ko', $err, 'Il server ha rifiutato il messaggio: leggi qui accanto il motivo che dà.' );
			$smtp->close();
			return $fine( false );
		}
		$passi[] = self::passo( 'Il messaggio parte', 'ok', trim( (string) $smtp->getLastReply() ) );
		$smtp->quit();
		$smtp->close();

		// 9. Copia in «Inviata» (non decide la verifica: l invio e gia riuscito)
		if ( $c['salva_inviati'] === '1' ) {
			$w = array();
			$ok_c = self::in_inviata( $c, $mime, $w );
			$passi[] = self::passo( 'Copia nella cartella «Inviata»', $ok_c ? 'ok' : 'avviso', implode( ' · ', $w ), $ok_c ? '' : 'Il messaggio è partito, ma la copia non è stata salvata: prova «Testa» nella parte IMAP.' );
		}
		return $fine( true );
	}

	/* ===================================================================== *
	 *  LA SPEDIZIONE VERA — inviti e recuperi
	 * ===================================================================== */

	/**
	 * Spedisce con la casella di servizio VERIFICATA. Tempo massimo 20 secondi
	 * per passo. Dopo l invio riuscito, copia in «Inviata» (fail-soft).
	 * @return array{success:bool, error:?string, avviso:string}
	 */
	/** 1.4.4B — la risposta letterale del server all ultimo invio e il Message-ID dato al messaggio (per il registro). */
	private static string $ultima_risposta = '';
	private static string $ultimo_mid = '';

	/**
	 * 1.4.4B — IL PUNTO UNICO DI OGNI MAIL, con la sua riga nel registro delle spedizioni (Rdacademy_Spedizioni): partita o no, col
	 * motivo e la risposta del server. Il mittente è sempre la casella di servizio, chiunque prema il pulsante.
	 */
	/**
	 * 1.13.0B (SV-43, SPEC-NOVITA-INTEGRAZIONE-FAMIGLIA § 5.1) — $bcc: la copia nascosta ([[email, nome], …]), solo per la mail delle
	 * novità. Le altre chiamate non la passano e non cambiano. Se il server rifiuta SOLO la copia nascosta la mail al destinatario è
	 * consegnata e vale come spedita: l esito porta 'bcc' => 'ko' (altrimenti 'ok', o '' senza copia).
	 */
	public static function spedisci( string $a, string $nome_a, string $oggetto, string $corpo_html, array $allegati = array(), array $cc = array(), array $bcc = array() ): array {
		self::$ultima_risposta = ''; self::$ultimo_mid = '';
		$e = self::spedisci_motore( $a, $nome_a, $oggetto, $corpo_html, $allegati, $cc, $bcc );
		if ( class_exists( 'Rdacademy_Spedizioni' ) ) {
			try {
				Rdacademy_Spedizioni::dopo_spedisci( $a, $nome_a, $cc, $oggetto, $e, self::$ultima_risposta, self::$ultimo_mid, (string) ( self::leggi()['mittente'] ?? '' ) );
			} catch ( \Throwable $x ) { /* il registro non ferma mai una spedizione */ }
		}
		return $e;
	}

	private static function spedisci_motore( string $a, string $nome_a, string $oggetto, string $corpo_html, array $allegati = array(), array $cc = array(), array $bcc = array() ): array {
		$c = self::leggi();
		$st = self::stato( $c );
		if ( ! $st['verificata'] ) {
			return array( 'success' => false, 'error' => $st['motivo'], 'avviso' => '' );
		}
		if ( ! is_email( $a ) ) return array( 'success' => false, 'error' => 'Destinatario non valido.', 'avviso' => '' );
		if ( ! self::carica_phpmailer() ) return array( 'success' => false, 'error' => 'PHPMailer non disponibile.', 'avviso' => '' );
		$pwd = self::password_di( $c );
		try {
			$m = self::posta_pronta( $c, $pwd, self::ATTESA_INVIO );
			$m->addAddress( $a, $nome_a );
			// La copia (cc) sulla stessa mail: [[email, nome], …].
			foreach ( $cc as $x ) {
				$e = trim( (string) ( $x[0] ?? '' ) );
				if ( $e !== '' && is_email( $e ) && strcasecmp( $e, $a ) !== 0 ) $m->addCC( $e, (string) ( $x[1] ?? '' ) );
			}
			// 1.13.0B (SV-43) — la copia nascosta: nella stessa mail, una spedizione sola, un esito solo.
			$nascoste = array();
			foreach ( $bcc as $x ) {
				$e = strtolower( trim( (string) ( $x[0] ?? '' ) ) );
				if ( $e !== '' && is_email( $e ) && strcasecmp( $e, $a ) !== 0 ) { $m->addBCC( $e, (string) ( $x[1] ?? '' ) ); $nascoste[] = $e; }
			}
			$m->Subject = $oggetto;
			$m->isHTML( true );
			$m->Body    = $corpo_html;
			$m->AltBody = self::testo_da_html( $corpo_html );
			// Gli allegati in memoria: [nome, byte, tipo]. Nessun file su disco.
			foreach ( $allegati as $al ) {
				// 1.5.0B — con il quarto elemento (cid) l immagine sta DENTRO il messaggio, non fra gli allegati.
				if ( ! empty( $al[3] ) ) { $m->addStringEmbeddedImage( (string) $al[1], (string) $al[3], (string) $al[0], 'base64', (string) ( $al[2] ?? 'image/png' ) ); continue; }
				$m->addStringAttachment( (string) $al[1], (string) $al[0], 'base64', (string) ( $al[2] ?? 'application/octet-stream' ) );
			}
			// 1.4.4B — un Message-ID nostro: un rifiuto che torna nella casella si abbina alla sua riga del registro.
			$dom = substr( strrchr( (string) $c['mittente'], '@' ) ?: '@reldox-academy.local', 1 );
			self::$ultimo_mid = '<rdacademy-' . bin2hex( random_bytes( 8 ) ) . '@' . strtolower( preg_replace( '/[^A-Za-z0-9.\-]/', '', $dom ) ?: 'reldox-academy.local' ) . '>';
			$m->MessageID = self::$ultimo_mid;
			// 1.4.4B — il collegamento resta aperto fino a dopo l invio, per leggere la risposta del server alla consegna («250 … queued
			// as …») e non quella del commiato («221 Bye»); poi si chiude.
			$m->SMTPKeepAlive = true;
			$m->send();
			self::$ultima_risposta = trim( (string) $m->getSMTPInstance()->getLastReply() );
			$m->smtpClose();
		} catch ( \Throwable $e ) {
			if ( isset( $m ) ) { self::$ultima_risposta = trim( (string) $m->getSMTPInstance()->getLastReply() ); try { $m->smtpClose(); } catch ( \Throwable $z ) {} }
			$msg = trim( $e->getMessage() );
			// 1.13.0B (SV-43) — rifiutata solo la copia nascosta: i dati sono partiti (PHPMailer manda DATA se resta un destinatario
			// valido e poi solleva l elenco dei rifiutati). Il destinatario vero non è fra i rifiutati: la mail vale come spedita.
			if ( ! empty( $nascoste ) && (int) $e->getCode() === \PHPMailer\PHPMailer\PHPMailer::STOP_CONTINUE && stripos( $msg, $a ) === false ) {
				$solo = true; foreach ( $nascoste as $n ) if ( stripos( $msg, $n ) === false ) $solo = false;
				if ( $solo ) return array( 'success' => true, 'error' => null, 'avviso' => '', 'bcc' => 'ko', 'bcc_errore' => $msg );
			}
			if ( stripos( $msg, 'connect' ) !== false || stripos( $msg, 'timed out' ) !== false ) {
				$msg = 'Il server di posta non risponde (' . $msg . '). Il messaggio NON è partito: controlla la casella in Impostazioni → Email.';
			}
			return array( 'success' => false, 'error' => $msg, 'avviso' => '' );
		}
		$avviso = '';
		if ( $c['salva_inviati'] === '1' ) {
			$w = array();
			if ( ! self::in_inviata( $c, $m->getSentMIMEMessage(), $w ) ) {
				$avviso = 'Messaggio partito, copia in «Inviata» non salvata: ' . implode( ' · ', $w );
			}
		}
		return array( 'success' => true, 'error' => null, 'avviso' => $avviso, 'bcc' => empty( $nascoste ) ? '' : 'ok' );
	}

	/* ===================================================================== *
	 *  COPIA IN «INVIATA»
	 * ===================================================================== */

	/** APPEND del messaggio nella cartella «Inviata». $note: che cosa e successo. */
	public static function in_inviata( array $c, string $mime, array &$note ): bool {
		if ( $c['imap_host'] === '' ) { $note[] = 'server IMAP non indicato'; return false; }
		$pwd = self::password_di( $c );
		$im = new Rdacademy_Imap_Diretta();
		$col = $im->collega( $c['imap_host'], (int) $c['imap_porta'], $c['imap_sicurezza'] );
		if ( ! $col['ok'] ) { $note[] = 'IMAP: ' . $col['errore']; return false; }
		$acc = $im->accedi( self::utente_di( $c ), $pwd );
		if ( ! $acc['ok'] ) { $note[] = 'IMAP, accesso: ' . $acc['testo']; $im->esci(); return false; }
		$ls = $im->cartelle();
		$scelta = null;
		if ( $c['cartella'] !== '' ) {
			foreach ( $ls['cartelle'] as $x ) {
				if ( strcasecmp( $x['nome'], $c['cartella'] ) === 0 ) { $scelta = $x; break; }
			}
			if ( ! $scelta ) { $note[] = 'la cartella «' . $c['cartella'] . '» non esiste su questa casella'; $im->esci(); return false; }
		} else {
			$scelta = Rdacademy_Imap_Diretta::scegli_inviata( $ls['cartelle'] );
			if ( ! $scelta ) { $note[] = 'nessuna cartella «Inviata» riconosciuta: indicala nel campo'; $im->esci(); return false; }
		}
		$r = $im->aggiungi( $scelta['grezzo'], $mime );
		$im->esci();
		if ( ! $r['ok'] ) { $note[] = 'APPEND in «' . $scelta['nome'] . '»: ' . $r['testo']; return false; }
		$note[] = 'salvata in «' . $scelta['nome'] . '»';
		return true;
	}

	/* ===================================================================== *
	 *  ATTREZZI
	 * ===================================================================== */

	public static function passo( string $titolo, string $esito, string $server = '', string $consiglio = '' ): array {
		return array( 'titolo' => $titolo, 'esito' => $esito, 'server' => $server, 'consiglio' => $consiglio );
	}

	/** Il testo da copiare: completo, leggibile, senza segreti. */
	public static function testo_esito( string $titolo, array $c, array $esito ): string {
		$seg = array( 'ok' => 'OK', 'ko' => '×', 'avviso' => '!', 'saltato' => '–' );
		$r = array();
		$r[] = RDACADEMY_PRODUCT . ' ' . RDACADEMY_VERSION . ' — ' . $titolo . ' — ' . wp_date( 'd/m/Y H:i:s' );
		$r[] = 'SMTP ' . $c['host'] . ':' . $c['porta'] . ' · ' . $c['sicurezza'] . ' · IMAP ' . ( $c['imap_host'] !== '' ? $c['imap_host'] . ':' . $c['imap_porta'] . ' · ' . $c['imap_sicurezza'] : '—' );
		$r[] = 'utente ' . self::utente_di( $c ) . ' · mittente ' . $c['mittente'] . ' · password ' . ( $c['password'] !== '' ? 'salvata' : 'assente' );
		$r[] = 'via: IMAP parlato direttamente (estensione PHP IMAP ' . ( function_exists( 'imap_open' ) ? 'presente' : 'assente' ) . ', non usata) · SMTP con PHPMailer di WordPress · PHP ' . PHP_VERSION;
		$r[] = 'ESITO: ' . ( ! empty( $esito['ok'] ) ? 'RIUSCITA' : 'NON RIUSCITA' );
		$i = 0;
		foreach ( (array) ( $esito['passi'] ?? array() ) as $p ) {
			$r[] = sprintf( '%2d. %s %s', ++$i, $seg[ $p['esito'] ] ?? '?', $p['titolo'] )
				. ( $p['server'] !== '' ? "\n      server: " . $p['server'] : '' )
				. ( $p['consiglio'] !== '' ? "\n      → " . $p['consiglio'] : '' );
		}
		if ( ! empty( $esito['registro'] ) ) {
			$r[] = '— conversazione col server —';
			foreach ( $esito['registro'] as $l ) { $r[] = '  ' . $l; }
		}
		return implode( "\n", $r );
	}

	private static function risolvi( string $host ): ?string {
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) return $host;
		$ip = @gethostbyname( $host );
		return ( $ip === $host || $ip === '' ) ? null : $ip;
	}

	private static function rimedio_collegamento( string $err, int $porta, string $sic, string $tipo ): string {
		$e = strtolower( $err );
		if ( strpos( $e, 'refused' ) !== false ) {
			return 'Il server rifiuta il collegamento su questa porta: la porta è sbagliata o chiusa' . ( $tipo === 'smtp' ? ' (spedizione: 465 SSL/TLS o 587 STARTTLS).' : ' (IMAP: 993 SSL/TLS).' );
		}
		if ( strpos( $e, 'timed out' ) !== false || strpos( $e, 'timeout' ) !== false || strpos( $e, '110' ) !== false ) {
			return 'Nessuna risposta in tempo: porta chiusa da un filtro, oppure server sbagliato' . ( $tipo === 'smtp' ? ' (per esempio imaps invece di smtps).' : '.' );
		}
		if ( strpos( $e, 'ssl' ) !== false || strpos( $e, 'crypto' ) !== false || strpos( $e, 'certificate' ) !== false || strpos( $e, 'tls' ) !== false ) {
			return 'La cifratura non corrisponde alla porta: ' . ( $tipo === 'smtp' ? '465 = SSL/TLS, 587 = STARTTLS.' : '993 = SSL/TLS, 143 = STARTTLS.' ) . ' Se il certificato non è del server indicato, usa il nome esatto del server.';
		}
		return 'Collegamento non riuscito: controlla server, porta e sicurezza.';
	}

	private static function errore_di( \PHPMailer\PHPMailer\SMTP $smtp ): string {
		$e = (array) $smtp->getError();
		$parti = array_filter( array(
			trim( (string) ( $e['smtp_code'] ?? '' ) . ' ' . (string) ( $e['smtp_code_ex'] ?? '' ) ),
			trim( (string) ( $e['detail'] ?? '' ) ),
			trim( (string) ( $e['error'] ?? '' ) ),
		) );
		$t = trim( implode( ' — ', array_unique( $parti ) ) );
		return $t !== '' ? $t : trim( (string) $smtp->getLastReply() );
	}

	/** Registra la conversazione SMTP senza credenziali e senza il corpo. */
	private static function registratore( array &$registro ): callable {
		$nel_corpo = false;
		return static function ( $riga ) use ( &$registro, &$nel_corpo ) {
			$riga = rtrim( (string) $riga );
			if ( $riga === '' ) return;
			// 0.6.10A — anche la riga VUOTA del client (le righe bianche del messaggio) e del client:
			// prima finiva fra le righe di servizio come «… CLIENT -> SERVER:».
			if ( preg_match( '/^CLIENT -> SERVER:\s?(.*)$/s', $riga, $m ) ) {
				$cmd = $m[1];
				if ( $nel_corpo ) { return; }
				if ( preg_match( '/^(EHLO|HELO|STARTTLS|MAIL FROM|RCPT TO|QUIT|RSET|NOOP)\b/i', $cmd ) ) { $registro[] = 'C: ' . $cmd; }
				elseif ( preg_match( '/^DATA\b/i', $cmd ) ) { $registro[] = 'C: DATA'; $nel_corpo = true; }
				elseif ( preg_match( '/^AUTH (\S+)/i', $cmd, $a ) ) { $registro[] = 'C: AUTH ' . $a[1] . ' ••••'; }
				else { $registro[] = 'C: ••••'; }
				return;
			}
			if ( preg_match( '/^SERVER -> CLIENT: (.*)$/s', $riga, $m ) ) {
				if ( $nel_corpo && preg_match( '/^(250|4\d\d|5\d\d)/', $m[1] ) ) { $nel_corpo = false; $registro[] = 'C: (messaggio)'; }
				foreach ( preg_split( "/\r?\n/", trim( $m[1] ) ) as $l ) { $registro[] = 'S: ' . $l; }
				return;
			}
			if ( stripos( $riga, 'password' ) === false ) { $registro[] = '… ' . $riga; }
		};
	}

	/**
	 * 0.6.10A (RF-BUG-019) — LA VERSIONE «SOLO TESTO» SI LEGGE. Fino alla 0.6.09A era
	 * wp_strip_all_tags() del corpo HTML: paragrafi incollati («RelDoxAcademyCiao Mario,ti
	 * rimandiamo…») e lettere accentate in codice («non &egrave; pi&ugrave; valido»). La
	 * leggono i programmi senza grafica e i filtri antispam. Ora: un paragrafo per riga,
	 * entita sciolte, il collegamento scritto per intero sulla sua riga.
	 */
	public static function testo_da_html( string $html ): string {
		$t = preg_replace( '#<(script|style|head)[^>]*>.*?</\1>#is', '', $html );
		// un collegamento: il testo e poi l indirizzo, se sono diversi
		$t = preg_replace_callback( '#<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', static function ( $m ) {
			$testo = trim( wp_strip_all_tags( $m[2] ) );
			$url   = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return ( $testo === '' || $testo === $url ) ? "\n" . $url . "\n" : $testo . ":\n" . $url . "\n";
		}, $t );
		$t = preg_replace( '#<br\s*/?>#i', "\n", $t );
		$t = preg_replace( '#</(p|div|h[1-6]|li|tr|table|ul|ol)>#i', "\n\n", $t );
		$t = wp_strip_all_tags( $t );
		$t = html_entity_decode( $t, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$righe = array_map( static function ( $r ) { return trim( preg_replace( '/[ \t\x{00A0}]+/u', ' ', $r ) ); }, explode( "\n", str_replace( "\r", '', $t ) ) );
		$t = preg_replace( "/\n{3,}/", "\n\n", implode( "\n", $righe ) );
		return trim( $t );
	}

	private static function carica_phpmailer(): bool {
		if ( ! class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) ) {
			$b = ABSPATH . WPINC . '/PHPMailer/';
			if ( is_file( $b . 'PHPMailer.php' ) ) {
				require_once $b . 'PHPMailer.php';
				require_once $b . 'SMTP.php';
				require_once $b . 'Exception.php';
			}
		}
		return class_exists( '\\PHPMailer\\PHPMailer\\PHPMailer' ) && class_exists( '\\PHPMailer\\PHPMailer\\SMTP' );
	}

	/** Un PHPMailer pronto per la casella (copiato da Reldox_Mailer::smtp_send). */
	private static function posta_pronta( array $c, string $pwd, int $attesa ): \PHPMailer\PHPMailer\PHPMailer {
		$m = new \PHPMailer\PHPMailer\PHPMailer( true );
		$m->isSMTP();
		$m->Host     = $c['host'];
		$m->Port     = (int) $c['porta'];
		$m->SMTPAuth = ( $c['utente'] !== '' || $pwd !== '' );
		$m->Username = self::utente_di( $c );
		$m->Password = $pwd;
		$m->CharSet  = 'UTF-8';
		$m->Encoding = 'base64';
		$m->Timeout  = $attesa;
		if ( $c['sicurezza'] === 'ssl' ) {
			$m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
		} elseif ( $c['sicurezza'] === 'tls' ) {
			$m->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
		} else {
			$m->SMTPSecure = '';
			$m->SMTPAutoTLS = false;
		}
		// ⛔ Timelimit e il tempo massimo di OGNI risposta: PHPMailer lo tiene a 300 secondi.
		$m->getSMTPInstance()->Timelimit = $attesa;
		$m->setFrom( $c['mittente'], $c['nome'] !== '' ? $c['nome'] : RDACADEMY_PRODUCT, false );
		return $m;
	}

	private static function messaggio_di_prova( array $c, string $a ): string {
		try {
			$m = self::posta_pronta( $c, '', self::ATTESA_PROVA );
			$m->addAddress( $a );
			$m->Subject = RDACADEMY_PRODUCT . ' — prova della casella di servizio';
			$m->isHTML( true );
			$m->Body = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#111827;line-height:1.6">'
				. '<p>Questa è una <strong>prova della casella di servizio</strong> di ' . esc_html( RDACADEMY_PRODUCT ) . '.</p>'
				. '<p>Se la leggi, la casella <strong>' . esc_html( $c['mittente'] ) . '</strong> spedisce correttamente: da qui partiranno inviti e recuperi delle credenziali.</p>'
				. '<p style="color:#6B7280;font-size:13px">Spedita il ' . esc_html( wp_date( 'd/m/Y H:i' ) ) . ' da ' . esc_html( home_url() ) . '</p></div>';
			$m->AltBody = self::testo_da_html( $m->Body );
			$m->preSend();
			return $m->getSentMIMEMessage();
		} catch ( \Throwable $e ) {
			return '';
		}
	}
}

endif;
