<?php
/**
 * class-rdacademy-password-mail.php — LA CASELLA DI SERVIZIO E IL MESSAGGIO
 * =============================================================================
 * Copiato da Reldox_Password_Mail, RelDox CRD 3.55.09G (voce 39, scene 4 e 5).
 *
 * PERCHE SERVE UNA CASELLA DICHIARATA. Un messaggio di recupero parte quando
 * NESSUNO E AUTENTICATO: non esiste un «autore» da cui prendere la casella.
 * Va dichiarata una volta, in Impostazioni › Email (lotto C), e sta li.
 *
 * ⛔ SENZA CASELLA NON SI RIPIEGA. Se la casella di servizio non e
 * configurata il messaggio NON PARTE e lo si dichiara a chi amministra; la
 * scheda utente (lotto C) offre allora la VALVOLA della password provvisoria
 * scritta dall amministratore (decisione 2 della roadmap di RelDox) — e
 * `configurata()` la domanda a cui quella valvola risponde.
 *
 * ⛔ CIO CHE L UTENTE VEDE NON CAMBIA MAI. Chi chiede il recupero riceve sempre
 * la stessa schermata (`RP-LA-PORTA-NON-DICE-CHI-C-E-DENTRO`). Il motivo vero
 * finisce in `activity_log`.
 *
 * DIFFERENZE DA RELDOX, DICHIARATE:
 *  - TAGLIO del motore: in RelDox la casella era quella PREDEFINITA DI UN
 *    UTENTE (`user_email_accounts`, CRM) e spediva `Reldox_Mailer` (PHPMailer
 *    per-invio, posta del CRM). NUOVO nel guscio: la casella di servizio vive
 *    nelle chiavi `mail_servizio_*` di `rdacademy_impostazioni`. 0.6.09A: spedisce
 *    Rdacademy_Casella (PHPMailer per-invio come Reldox_Mailer, 20 secondi per
 *    passo, solo da casella VERIFICATA, copia in «Inviata»).
 *  - TAGLIO nella mail di benvenuto: il riquadro «configura le caselle di
 *    posta e la firma» (CRM).
 *  - Il nome del prodotto viene da RDACADEMY_PRODUCT: niente «RelDOX» scritto a mano.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Rdacademy_Password_Mail' ) ) :

final class Rdacademy_Password_Mail {

	/**
	 * NUOVO — le chiavi della casella di servizio in `rdacademy_impostazioni`.
	 * Un posto solo, nominate una volta. La password si conserva come la
	 * scrivera il lotto C (Impostazioni › Email): qui si legge e basta.
	 */
	const CHIAVI = array(
		'host'      => 'mail_servizio_host',
		'porta'     => 'mail_servizio_porta',
		'sicurezza' => 'mail_servizio_sicurezza',   // ssl | tls | nessuna
		'utente'    => 'mail_servizio_utente',
		'password'  => 'mail_servizio_password',
		'mittente'  => 'mail_servizio_mittente',
		'nome'      => 'mail_servizio_nome',
	);

	/* ===================================================================== *
	 *  LA CASELLA DI SERVIZIO
	 * ===================================================================== */

	/** NUOVO — legge la casella dichiarata. */
	public static function casella(): array {
		$p    = RDACADEMY_TABLE_PREFIX;
		$segn = implode( ',', array_fill( 0, count( self::CHIAVI ), '?' ) );
		$rows = Rdacademy_DB::get_results(
			"SELECT chiave, valore FROM `{$p}impostazioni` WHERE chiave IN ({$segn})",
			array_values( self::CHIAVI ), str_repeat( 's', count( self::CHIAVI ) )
		);
		$per = array();
		foreach ( (array) $rows as $r ) { $per[ (string) $r['chiave'] ] = (string) $r['valore']; }
		$out = array();
		foreach ( self::CHIAVI as $k => $chiave ) { $out[ $k ] = trim( $per[ $chiave ] ?? '' ); }
		return $out;
	}

	/**
	 * La casella dichiarata e utilizzabile? Copiato nella forma da RelDox:
	 * non basta che una chiave sia scritta, servono server e mittente.
	 *
	 * @return array{configurata:bool,riga:?array,motivo:?string}
	 */
	public static function stato(): array {
		// 0.6.09A (RF-BUG-016) — utilizzabile = VERIFICATA da una spedizione vera
		// (Rdacademy_Casella::stato). Compilata e basta non apre piu la porta.
		$c  = self::casella();
		$st = Rdacademy_Casella::stato();
		if ( ! $st['compilata'] || ! $st['verificata'] ) {
			return array( 'configurata' => false, 'riga' => null, 'motivo' => $st['motivo'], 'compilata' => $st['compilata'] );
		}
		unset( $c['password'] ); // ⛔ un segreto non esce dalla SELECT verso chi chiede lo stato (REGOLE § 5)
		return array( 'configurata' => true, 'riga' => $c, 'motivo' => null, 'compilata' => true );
	}

	public static function configurata(): bool {
		$s = self::stato();
		return (bool) $s['configurata'];
	}

	/**
	 * LA SOLA VIA DI SPEDIZIONE. 0.6.09A (RF-BUG-016): non piu wp_mail() con
	 * l hook phpmailer_init, che lasciava a PHPMailer i suoi 300 secondi per
	 * risposta, ma il motore della casella (Rdacademy_Casella::spedisci, copiato
	 * da Reldox_Mailer::smtp_send): casella verificata, 20 secondi per passo,
	 * copia in «Inviata».
	 *
	 * @return array{success:bool,error:?string,avviso?:string}
	 */
	private static function spedisci( string $a, string $oggetto, string $corpo ): array {
		return Rdacademy_Casella::spedisci( $a, '', $oggetto, $corpo );
	}

	/** L ultimo avviso della spedizione (copia in «Inviata» non riuscita), per chi ha chiesto l invio. */
	public static $ultimo_avviso = '';

	/* ===================================================================== *
	 *  SPEDIZIONE (copiata da RelDox; il motore e spedisci())
	 * ===================================================================== */

	/**
	 * Spedisce la mail di reimpostazione (scena 5).
	 *
	 * @return array{success:bool,error:?string}
	 */
	public static function invia_reimpostazione( array $utente, string $token_grezzo ): array {
		$link    = self::link_imposta( $token_grezzo );
		$nome    = trim( (string) ( $utente['first_name'] ?? '' ) );
		// 0.8.2A — nella lingua della persona (la sua scheda), non di chi preme il pulsante.
		// 0.8.7A (RF-BUG-025) — oggetto e testo dalla Tabella Funzionale «Testi delle notifiche» (voce `reimpostazione`).
		$uid     = (int) ( $utente['id'] ?? 0 );
		$lingua  = Rdacademy_Lingua::di_utente( $uid );
		$login   = trim( (string) ( $utente['login'] ?? '' ) );
		$m       = Rdacademy_Testi_Notifiche::modello( 'reimpostazione', $lingua );
		$oggetto = Rdacademy_Lingua::riempi( $m['oggetto'], self::valori_reimpostazione( $nome, $login, $link, $lingua, $uid ), false );
		// 1.4.4B — nel registro delle spedizioni: dalla scheda (chi è collegato) o dalla porta d accesso («password dimenticata»).
		if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::contesto( Rdacademy_Auth::get_current_user() ? 'reimpostazione' : 'reimpostazione_porta', $uid );
		$e = self::spedisci( (string) ( $utente['email'] ?? '' ), $oggetto, self::corpo_reimpostazione( $nome, $link, $lingua, $uid, $login ) );
		self::$ultimo_avviso = (string) ( $e['avviso'] ?? '' );
		return $e;
	}

	/** 0.8.7A — i valori dei segnaposto della reimpostazione: quelli dell invito, con la durata in minuti. */
	private static function valori_reimpostazione( string $nome, string $login, string $link, string $lingua, int $uid ): array {
		$v = self::valori_invito( $nome, $login, $link, $lingua, $uid );
		$v['scadenza'] = Rdacademy_Lingua::fl( $lingua, 'mail.minuti', (int) Rdacademy_Password_Token::MINUTI_REIMPOSTA );
		return $v;
	}

	/**
	 * Spedisce la mail di BENVENUTO (scena 6). ⛔ NESSUNA PASSWORD VIAGGIA QUI:
	 * la mail porta un COLLEGAMENTO e chi lo apre sceglie la propria password
	 * (`RP-LA-PASSWORD-LA-SCEGLIE-CHI-LA-USA`).
	 *
	 * @return array{success:bool,error:?string}
	 */
	public static function invia_invito( array $utente, string $token_grezzo, bool $reinvio = false ): array {
		$link  = self::link_imposta( $token_grezzo );
		$nome  = trim( (string) ( $utente['first_name'] ?? '' ) );
		$login = trim( (string) ( $utente['login'] ?? '' ) );
		if ( $login === '' ) { $login = (string) ( $utente['email'] ?? '' ); }

		// 0.8.2A (owner 07/10 08:48) — la mail parte nella LINGUA DELLA PERSONA.
		// 0.8.7A (RF-BUG-025) — oggetto e testo dalla Tabella Funzionale «Testi delle notifiche»: `invito.persona` o
		// RelDoxAcademy: sempre `invito.persona` (il ramo del ristoratore è tolto); il reinvio prende l oggetto dalla voce `reinvio`.
		$lingua  = Rdacademy_Lingua::di_utente( (int) ( $utente['id'] ?? 0 ) );
		$uid     = (int) ( $utente['id'] ?? 0 );
		$modello = Rdacademy_Testi_Notifiche::modello( 'invito.persona', $lingua );
		$valori  = self::valori_invito( $nome, $login, $link, $lingua, $uid );
		$oggetto = $reinvio
			? Rdacademy_Lingua::riempi( Rdacademy_Testi_Notifiche::modello( 'reinvio', $lingua )['oggetto'], $valori, false )
			: Rdacademy_Lingua::riempi( $modello['oggetto'], $valori, false );

		if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::contesto( $reinvio ? 'invito_reinvio' : 'invito', $uid );   // 1.4.4B
		$e = self::spedisci( (string) ( $utente['email'] ?? '' ), $oggetto, self::corpo_benvenuto( $nome, $login, $link, $reinvio, $lingua, $uid ) );
		self::$ultimo_avviso = (string) ( $e['avviso'] ?? '' );
		return $e;
	}

	/**
	 * 0.8.6A (owner 08/10 08:37, SPEC-CASSA § 0 punto 28) — «INVIO LOGIN»: la mail col solo collegamento alla porta
	 * d accesso e il nome utente. Nessun gettone, nessuna password: quella che la persona ha scelto resta com è.
	 * Nella lingua della scheda, con la testata dei due loghi.
	 * 0.8.7A (RF-BUG-025) — oggetto e testo dalla Tabella Funzionale: `accesso.persona`.
	 *
	 * @return array{success:bool,error:?string}
	 */
	public static function invia_accesso( array $utente ): array {
		$uid     = (int) ( $utente['id'] ?? 0 );
		$lingua  = Rdacademy_Lingua::di_utente( $uid );
		$login   = trim( (string) ( $utente['login'] ?? '' ) );
		if ( $login === '' ) { $login = (string) ( $utente['email'] ?? '' ); }
		$nome    = trim( (string) ( $utente['first_name'] ?? '' ) );
		$m       = Rdacademy_Testi_Notifiche::modello( 'accesso.persona', $lingua );
		$oggetto = Rdacademy_Lingua::riempi( $m['oggetto'], self::valori_invito( $nome, $login, self::link_accesso(), $lingua, $uid ), false );
		$corpo   = self::corpo_accesso( $nome, $login, $lingua, $uid );
		if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::contesto( 'accesso', $uid );   // 1.4.4B
		$e = self::spedisci( (string) ( $utente['email'] ?? '' ), $oggetto, $corpo );
		self::$ultimo_avviso = (string) ( $e['avviso'] ?? '' );
		return $e;
	}

	/** 0.8.6A — l indirizzo della porta d accesso (la stessa per tutti). */
	public static function link_accesso(): string {
		return home_url( '/' . RDACADEMY_APP_SLUG . '/login' );
	}

	/**
	 * 0.8.7A — IL BLOCCO DEL PULSANTE, uno per tutti i messaggi: il pulsante e, sotto, la riga fissa che lo accompagna
	 * (la durata del collegamento). Il testo della tabella lo mette dove scrive {pulsante}.
	 */
	private static function pulsante( string $url, string $etichetta, string $sotto = '' ): string {
		return '<p style="margin:4px 0 10px;text-align:center">'
			. '<a href="' . esc_url( $url ) . '" style="display:inline-block;background:#2E75B6;color:#FFFFFF;text-decoration:none;'
			. 'font-size:14px;font-weight:600;padding:12px 24px;border-radius:8px">' . esc_html( $etichetta ) . '</a></p>'
			. $sotto;
	}

	/** 0.8.7A — l involucro comune: sfondo, scheda di 560 px, testata dei due loghi, corpo, riserva del collegamento, piede. */
	private static function involucro( string $L, string $corpo, string $link_riserva, string $piede ): string {
		$ris = $link_riserva !== ''
			? '<p style="margin:16px 0 0;font-size:12px;color:#6B7280">' . esc_html( Rdacademy_Lingua::t( 'mail.non_funziona', $L ) ) . '<br><span style="word-break:break-all">' . esc_url( $link_riserva ) . '</span></p>'
			: '';
		return '<div lang="' . esc_attr( $L ) . '" style="margin:0;padding:24px 0;background:#F3F4F6;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif">'
			. '<div style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;overflow:hidden">'
			. self::testata()
			. '<div style="padding:18px 28px 26px;font-size:14px;line-height:1.65;color:#111827">'
			. $corpo . $ris
			. '</div>'
			. $piede
			. '</div></div>';
	}

	/** 0.8.6A — corpo HTML di «Invio login». 0.8.7A: il testo viene dalla tabella (`accesso.persona`). */
	public static function corpo_accesso( string $nome, string $login, string $lingua = 'it', int $uid = 0 ): string {
		$L = Rdacademy_Lingua::valida( $lingua );
		$l = self::link_accesso();
		$m = Rdacademy_Testi_Notifiche::modello( 'accesso.persona', $L );
		$v = self::valori_invito( $nome, $login, $l, $L, $uid );
		$v['pulsante_html'] = self::pulsante( $l, Rdacademy_Lingua::fl( $L, 'accesso.btn', RDACADEMY_PRODUCT ) );
		return self::involucro( $L, Rdacademy_Lingua::riempi( $m['testo'], $v, true ), $l, self::piede( $L, false ) );
	}

	/** Il collegamento della mail. Il segreto viaggia QUI e da nessun altra parte. */
	public static function link_imposta( string $token_grezzo ): string {
		return home_url( '/' . RDACADEMY_APP_SLUG . '/imposta-password' ) . '?t=' . rawurlencode( $token_grezzo );
	}

	/* ===================================================================== *
	 *  IL TESTO — contratto grafico, scena 5, replicato alla lettera
	 * ===================================================================== */

	/**
	 * Corpo HTML della mail di reimpostazione.
	 *
	 * ⛔ Stili INLINE e non foglio di stile: i client di posta buttano via i
	 * <style> in testa, ed e esattamente il difetto del CSS in chiaro gia visto
	 * nella voce 37. Larghezza fissa 560, tabella unica, nessuna immagine
	 * remota obbligatoria.
	 */
	/**
	 * BUG-85 (3.16.198, S260) — IL LOGO RELDOX IN TUTTI I MESSAGGI DI POSTA.
	 *
	 * ORDINE DELL OWNER, e vale anche per il marketing: fino alla 3.16.197 la
	 * testata era la parola «RelDOX» scritta a caratteri blu. Un messaggio che
	 * chiede di premere un pulsante e di scegliere una password e' esattamente
	 * il messaggio che va riconosciuto a colpo d occhio: senza il marchio, la
	 * mail dell invito e quella del recupero somigliano a una qualunque.
	 *
	 * ⛔ SI SCRIVE UNA VOLTA SOLA E SI CHIAMA TRE VOLTE. Tre testate copiate in
	 * tre corpi sono tre verita' destinate a divergere alla prima modifica
	 * (`RP-UNA-MANO-SOLA-SUL-COMANDO`): il giorno in cui il logo cambia, ne
	 * cambierebbero due su tre e nessuno se ne accorgerebbe.
	 *
	 * ⛔ PNG E NON SVG, e non e' una preferenza. I client di posta — Outlook in
	 * testa — non disegnano l SVG: `reldox-lockup.png` porta il font incorporato
	 * e lo sfondo trasparente ed e' il file che il kit dichiara per email, firme
	 * e documenti (`public/img/logo/README-LOGO.md`).
	 *
	 * ⛔ IL RIPIEGO RESTA SCRITTO. Molti client bloccano le immagini remote per
	 * impostazione: l attributo `alt` porta la parola «RelDOX», cosi' chi non
	 * vede l immagine legge comunque il marchio dove il marchio deve stare.
	 */
	public static function logo_url(): string {
		// 0.8.7A (RF-BUG-023) — il logo VERO di RelDoxAcademy (lo stesso delle porte), non più il simbolo del kit.
		return rdacademy_loghi( true )['prodotto'];
	}

	/**
	 * 0.8.2A — IL LOGO DEL CLIENTE NELLA MAIL. 0.8.7A (RF-BUG-023): lo sceglie rdacademy_loghi(), la stessa funzione delle
	 * porte (computer → telefono; per la posta un SVG si salta). Nessun logo caricato = nessuna immagine.
	 *
	 * @return array{url:string,alt:string}
	 */
	public static function logo_cliente(): array {
		$L = rdacademy_loghi( true );
		return array( 'url' => (string) $L['cliente'], 'alt' => (string) $L['cliente_alt'] );
	}

	/**
	 * LA TESTATA COMUNE A TUTTI I MESSAGGI — 0.8.7A (RF-BUG-023, owner 08/10 09:17: «se le schermate di login riportano i
	 * due loghi a sinistra e destra anche i messaggi di posta lo devono fare»). Come le porte: a SINISTRA il logo vero di
	 * RelDoxAcademy (lockup PNG, alt «RelDoxAcademy»), un filetto, a DESTRA il logo del cliente alla stessa altezza, sulla stessa
	 * riga. Senza logo del cliente il solo logo RelDoxAcademy, centrato (come la porta). Niente più simbolo + scritta.
	 * Una tabella e non blocchi affiancati: è l unica impaginazione che Outlook rispetta. PNG, mai SVG.
	 * Vale per invito, reinvio, reimpostazione, «Invio login» e avviso di blocco: tutti passano di qui.
	 */
	public static function testata(): string {
		$L   = rdacademy_loghi( true );
		$alt = esc_attr( $L['prodotto_alt'] );
		if ( $L['cliente'] === '' ) {
			return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse"><tr>'
				. '<td class="rdacademy-mail-prodotto" style="padding:22px 28px 8px;text-align:center;vertical-align:middle">'
				. '<img src="' . esc_url( $L['prodotto'] ) . '" alt="' . $alt . '" width="' . (int) round( (int) $L['prodotto_w'] * 56 / max( 1, (int) $L['prodotto_h'] ) ) . '" height="56" '
				. 'style="display:inline-block;width:' . (int) round( (int) $L['prodotto_w'] * 56 / max( 1, (int) $L['prodotto_h'] ) ) . 'px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;vertical-align:middle">'
				. '</td></tr></table>';
		}
		/* 0.13.2A (owner 10/10 18:43: «non sono centrati») — LA COPPIA DEI LOGHI CENTRATA. Prima: due metà fisse del 50 %,
		   RelDoxAcademy appoggiato a destra della metà sinistra e il cliente a sinistra della metà destra; il filetto stava al
		   centro ma la coppia no, perché i due loghi hanno larghezze diverse. Ora: una cella a tutta larghezza centrata e
		   dentro una tabella stretta (align="center", larghezza del contenuto) con logo · filetto · logo, così il centro
		   della coppia è il centro della scheda qualunque sia il logo del cliente. Larghezze in pixel scritte sull'immagine
		   (Outlook) e max-width:100% (sui telefoni stretti la coppia si restringe e non esce dalla scheda). */
		$pw = (int) round( (int) $L['prodotto_w'] * 48 / max( 1, (int) $L['prodotto_h'] ) );   // 657×182 → 173 px a 48 di altezza
		$cw = self::larghezza_cliente( (string) $L['cliente'] );
		$st_cli = $cw > 0
			? 'width:' . $cw . 'px;max-width:100%;height:auto'
			: 'height:48px;width:auto;max-width:190px';
		return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse"><tr>'
			. '<td class="rdacademy-mail-coppia" align="center" style="padding:22px 20px 8px;text-align:center;vertical-align:middle">'
			. '<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;margin:0 auto"><tr>'
			. '<td class="rdacademy-mail-prodotto" style="padding:0 14px 0 0;text-align:right;vertical-align:middle">'
			. '<img src="' . esc_url( $L['prodotto'] ) . '" alt="' . $alt . '" width="' . $pw . '" height="48" '
			. 'style="display:block;width:' . $pw . 'px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none">'
			. '</td>'
			. '<td class="rdacademy-mail-filetto" width="1" style="padding:0;vertical-align:middle">'
			. '<div style="width:1px;height:48px;background:#D1D5DB;line-height:48px;font-size:1px">&nbsp;</div></td>'
			. '<td class="rdacademy-mail-cliente" style="padding:0 0 0 14px;text-align:left;vertical-align:middle">'
			. '<img src="' . esc_url( $L['cliente'] ) . '" alt="' . esc_attr( $L['cliente_alt'] ) . '"' . ( $cw > 0 ? ' width="' . $cw . '"' : '' ) . ' height="48" '
			. 'style="display:block;' . $st_cli . ';border:0;outline:none;text-decoration:none">'
			. '</td></tr></table>'
			. '</td></tr></table>';
	}

	/**
	 * 0.13.2A — la larghezza del logo del cliente alla stessa altezza di RelDoxAcademy (48 px), letta dal file caricato nelle
	 * Impostazioni → Branding (cartella degli upload). Al massimo 190 px. Se il file non è
	 * leggibile (indirizzo esterno, file sparito) torna 0 e la testata usa la larghezza automatica di prima.
	 */
	private static function larghezza_cliente( string $url ): int {
		if ( $url === '' || ! function_exists( 'wp_upload_dir' ) ) return 0;
		$up   = wp_upload_dir( null, false );
		$base = (string) ( $up['baseurl'] ?? '' );
		$u    = (string) preg_replace( '/[?#].*$/', '', $url );
		$rel  = '';
		if ( $base !== '' ) {
			$b = (string) preg_replace( '#^https?:#i', '', $base );
			$v = (string) preg_replace( '#^https?:#i', '', $u );
			if ( strpos( $v, $b . '/' ) === 0 ) $rel = substr( $v, strlen( $b ) + 1 );
		}
		if ( $rel === '' || strpos( $rel, '..' ) !== false ) return 0;
		$f = rtrim( (string) ( $up['basedir'] ?? '' ), '/' ) . '/' . $rel;
		if ( ! is_file( $f ) ) return 0;
		$d = @getimagesize( $f );
		if ( ! is_array( $d ) || (int) $d[0] < 1 || (int) $d[1] < 1 ) return 0;
		return (int) max( 1, min( 190, round( (int) $d[0] * 48 / (int) $d[1] ) ) );
	}

	/**
	 * BUG-83 (3.16.198, S260) — L AVVISO ALL AMMINISTRATORE QUANDO UN ACCOUNT
	 * SI BLOCCA.
	 *
	 * ⛔ IL DESTINATARIO NON E L UTENTE BLOCCATO. Chi ha appena sbagliato cinque
	 * volte lo sa gia' — glielo dice la schermata. Chi NON lo sa e' chi puo'
	 * intervenire, ed e' l unico motivo per cui questo messaggio esiste: cinque
	 * tentativi falliti su un account sono o un utente in difficolta' o
	 * qualcuno che prova le password, e le due cose si distinguono solo
	 * guardando quante volte succede e da dove.
	 *
	 * ⛔ NON PORTA NESSUNA PASSWORD, nemmeno quella sbagliata. E non porta
	 * nessun collegamento di sblocco: un pulsante «sblocca» dentro una mail
	 * sarebbe una porta nuova aperta da un messaggio, cioe' il difetto che si
	 * sta cercando di descrivere.
	 *
	 * @return array{success:bool,error:?string}
	 */
	public static function avvisa_blocco( array $utente, string $ip, string $fino_alle ): array {
		$stato = self::stato();
		if ( empty( $stato['configurata'] ) ) {
			return array( 'success' => false, 'error' => (string) $stato['motivo'] );
		}
		$dest = self::destinatari_admin();
		if ( ! $dest ) {
			return array( 'success' => false, 'error' => 'Nessun amministratore con un indirizzo a cui scrivere.' );
		}

		$chi = trim( (string) ( $utente['first_name'] ?? '' ) . ' ' . (string) ( $utente['last_name'] ?? '' ) );
		if ( $chi === '' ) { $chi = (string) ( $utente['email'] ?? '' ); }
		$corpo = self::corpo_blocco( $chi, (string) ( $utente['email'] ?? '' ), $ip, $fino_alle );
		// 0.8.7A (RF-BUG-025) — oggetto e testo dalla tabella (voce `blocco`, italiano: il pannello è solo in italiano).
		$ogg   = Rdacademy_Lingua::riempi( Rdacademy_Testi_Notifiche::modello( 'blocco', 'it' )['oggetto'], array(), false );

		$ok = false; $err = null;
		foreach ( $dest as $indirizzo ) {
			if ( class_exists( 'Rdacademy_Spedizioni' ) ) Rdacademy_Spedizioni::contesto( 'blocco', 0, 'sistema' );   // 1.4.4B
			$e = self::spedisci( $indirizzo, $ogg, $corpo );
			if ( ! empty( $e['success'] ) ) { $ok = true; } else { $err = $e['error'] ?? 'errore SMTP'; }
		}
		return array( 'success' => $ok, 'error' => $ok ? null : $err );
	}

	/**
	 * Gli indirizzi degli amministratori attivi.
	 *
	 * ⛔ Riconosce l admin come `Rdacademy_Auth::roles_grant_admin()`: un ruolo
	 * `is_backdoor = 1` oppure un `permissions` JSON con «all» valorizzato. Una
	 * seconda definizione di «chi e' amministratore» sarebbe una seconda regola
	 * su un fatto solo, e diverge alla prima modifica dell altra.
	 */
	private static function destinatari_admin(): array {
		return array_values( array_map( static function ( $u ) { return (string) $u['email']; }, self::amministratori() ) );
	}

	/**
	 * 1.19.0L (SV-19, SPEC-BACHECA-RISTORATORI § 4.1, D9) — gli amministratori attivi con un indirizzo, UNO PER INDIRIZZO, con id e nome
	 * (la mail della nota li saluta per nome, nella loro lingua). La regola di «chi è amministratore» resta una sola: quella qui sotto,
	 * che usa anche l avviso di blocco (destinatari_admin).
	 * @return array<int,array{id:int,email:string,first_name:string,last_name:string}>
	 */
	public static function amministratori(): array {
		$p = RDACADEMY_TABLE_PREFIX;
		$righe = Rdacademy_DB::get_results(
			"SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, r.is_backdoor, r.permissions
			   FROM `{$p}users` u
			   JOIN `{$p}user_roles` ur ON ur.user_id = u.id
			   JOIN `{$p}roles`      r  ON r.id       = ur.role_id
			  WHERE u.status = 'active' AND u.email <> ''"
		);
		$fuori = array();
		foreach ( (array) $righe as $r ) {
			$e = trim( (string) ( $r['email'] ?? '' ) );
			if ( $e === '' || isset( $fuori[ $e ] ) ) { continue; }
			$admin = ( (int) ( $r['is_backdoor'] ?? 0 ) === 1 );
			if ( ! $admin ) {
				$perm = json_decode( (string) ( $r['permissions'] ?? '' ), true );
				$all  = is_array( $perm ) ? ( $perm['all'] ?? false ) : false;
				$admin = ( $all === true || $all === 1 || $all === '1' || $all === 'true' );
			}
			if ( $admin ) { $fuori[ $e ] = array( 'id' => (int) $r['id'], 'email' => $e, 'first_name' => (string) $r['first_name'], 'last_name' => (string) $r['last_name'] ); }
		}
		return array_values( $fuori );
	}

	/** Corpo dell avviso di blocco. Stessa griglia degli altri due messaggi. */
	/**
	 * ⛔ 3.16.199 (S260B) — L ENTITA SBAGLIATA, trovata dall owner sullo smoke 9
	 * guardando la mail ARRIVATA e non il codice.
	 *
	 * `e&grave;` non e' «è»: e' la lettera «e» seguita da un accento tipografico
	 * isolato, e a schermo si legge «e`». Lo stesso per `piu&grave;`. La forma
	 * giusta e' `&egrave;` e `&ugrave;` — l entita nomina la LETTERA ACCENTATA,
	 * non l accento da appiccicare alla lettera prima.
	 *
	 * ⛔ NON ERA SOLO MIO. Una delle occorrenze vive nella mail di REINVITO
	 * (`corpo_benvenuto`, ramo `$reinvio`) e risale a S257: e' uscita verso
	 * l esterno per tre sessioni senza che nessuno la vedesse, perche' quel ramo
	 * non era mai stato provato. Una resa sbagliata non alza nessun errore, non
	 * rompe nessuna schermata e nessun gate la vede: la trova solo chi LEGGE il
	 * messaggio arrivato. → `RP-LA-MAIL-SI-PROVA-LEGGENDOLA-NON-COMPILANDOLA`.
	 */
	public static function corpo_blocco( string $chi, string $email, string $ip, string $fino_alle ): string {
		// 0.8.7A (RF-BUG-025) — il testo viene dalla tabella (voce `blocco`); restano fissi il titolo del riquadro rosso e il piede.
		$L = 'it';
		$v = array(
			'chi' => $chi, 'email' => $email, 'soglia' => (string) (int) Rdacademy_Auth::SOGLIA_BLOCCO,
			'ip'  => $ip !== '' ? $ip : Rdacademy_Lingua::t( 'mail.non_reg', $L ),
			'ora' => $fino_alle !== '' ? $fino_alle : Rdacademy_Lingua::t( 'mail.fra_poco', $L ),
			'pulsante_html' => '',
		);
		$corpo = '<div style="background:#FEF3F2;border:1px solid #FECDCA;border-radius:10px;padding:12px 16px;margin:0 0 16px">'
			. '<b style="color:#B42318">' . esc_html( Rdacademy_Lingua::t( 'mail.blocco_tit', $L ) ) . '</b></div>'
			. Rdacademy_Lingua::riempi( Rdacademy_Testi_Notifiche::modello( 'blocco', $L )['testo'], $v, true );
		$piede = '<div style="padding:14px 28px 20px;border-top:1px solid #F3F4F6;font-size:11.5px;line-height:1.6;color:#6B7280;text-align:center">'
			. esc_html( Rdacademy_Lingua::fl( $L, 'mail.automatico', RDACADEMY_PRODUCT ) ) . '</div>';
		return self::involucro( $L, $corpo, '', $piede );
	}

	/** Corpo HTML della reimpostazione. 0.8.7A: il testo viene dalla tabella (voce `reimpostazione`). */
	public static function corpo_reimpostazione( string $nome, string $link, string $lingua = 'it', int $uid = 0, string $login = '' ): string {
		$L = Rdacademy_Lingua::valida( $lingua );
		$m = Rdacademy_Testi_Notifiche::modello( 'reimpostazione', $L );
		$v = self::valori_reimpostazione( $nome, $login, $link, $L, $uid );
		$v['pulsante_html'] = self::pulsante( $link, Rdacademy_Lingua::t( 'mail.reimp_btn', $L ),
			'<p style="margin:0 0 18px;text-align:center;font-size:12.5px;color:#6B7280">' . Rdacademy_Lingua::fl( $L, 'mail.vale_minuti', (int) Rdacademy_Password_Token::MINUTI_REIMPOSTA ) . '</p>' );
		return self::involucro( $L, Rdacademy_Lingua::riempi( $m['testo'], $v, true ), $link, self::piede( $L, false ) );
	}

	/** Il piede comune a invito e recupero, nella lingua della persona. */
	private static function piede( string $L, bool $invito ): string {
		$P = esc_html( RDACADEMY_PRODUCT );
		return '<div style="padding:14px 28px 20px;border-top:1px solid #F3F4F6;font-size:11.5px;line-height:1.6;color:#6B7280;text-align:center">'
			. esc_html( Rdacademy_Lingua::fl( $L, 'mail.automatico', RDACADEMY_PRODUCT ) ) . '<br>'
			. '<b>' . esc_html( Rdacademy_Lingua::fl( $L, 'mail.mai_password', RDACADEMY_PRODUCT ) ) . '</b>'
			. ( $invito ? ' ' . esc_html( Rdacademy_Lingua::t( 'mail.non_aspettavi', $L ) ) : '' )
			. '</div>';
	}

	/** 0.8.3A — il nome del cliente: la ragione sociale di Impostazioni → Azienda. */
	public static function cliente(): string {
		try {
			$r = Rdacademy_DB::get_row( "SELECT valore FROM `" . RDACADEMY_TABLE_PREFIX . "impostazioni` WHERE chiave = 'ragione_sociale' LIMIT 1" );
		} catch ( Throwable $e ) {
			$r = null;
		}
		return trim( (string) ( $r['valore'] ?? '' ) );
	}

	/**
	 * 0.8.2A — i valori dei segnaposto dell invito ({nome} {utente} {prodotto} {scadenza} {collegamento}).
	 * {cliente}: la ragione sociale dell azienda (Impostazioni → Azienda).
	 */
	public static function valori_invito( string $nome, string $login, string $link, string $lingua, int $uid = 0 ): array {
		$giorni = (int) round( Rdacademy_Password_Token::MINUTI_INVITO / 1440 );
		$v = array(
			'nome' => $nome, 'utente' => $login, 'prodotto' => RDACADEMY_PRODUCT, 'collegamento' => $link,
			'scadenza' => Rdacademy_Lingua::fl( $lingua, 'mail.giorni', $giorni ), 'cliente' => self::cliente(),
		);
		return $v;
	}

	/**
	 * Corpo HTML della mail di BENVENUTO — contratto grafico, scena 6,
	 * replicato alla lettera. 3.16.196 (S257).
	 *
	 * ⛔ I DUE PROMEMORIA SONO IL MANDATO, non un ornamento. L owner ha chiesto
	 * che la mail di benvenuto ricordi (1) i parametri di interfacciamento
	 * delle caselle di posta e (2) la personalizzazione della firma personale,
	 * col collegamento alla pagina giusta. Sono il riquadro «Subito dopo, due
	 * cose da fare», e non si comprimono in una riga.
	 *
	 * ⛔ IL RINVIO AL PROFILO E UN IMPEGNO, e il contratto lo dichiara: la card
	 * «Il mio profilo» la costruisce S258 (scena 9). Fino ad allora
	 * `/academy/preferenze` risponde e mostra «In arrivo»: il collegamento non
	 * cade nel vuoto, arriva a una pagina che esiste.
	 *
	 * Stili INLINE, mai un foglio di stile: i client di posta buttano via i
	 * <style> in testa. Stessa larghezza e stessa griglia della scena 5.
	 */
	public static function corpo_benvenuto( string $nome, string $login, string $link, bool $reinvio = false, string $lingua = 'it', int $uid = 0 ): string {
		// 0.8.7A (RF-BUG-025) — IL TESTO E DELL AZIENDA, nella Tabella Funzionale «Testi delle notifiche» (`invito.persona` /
		// nel reinvio la nota in testa viene da `reinvio`), coi segnaposto. Restano fissi il pulsante,
		// la validita del collegamento, l indirizzo di riserva e il piede: sono la sicurezza del
		// messaggio, non il suo tono.
		$L       = Rdacademy_Lingua::valida( $lingua );
		$giorni  = (int) round( Rdacademy_Password_Token::MINUTI_INVITO / 1440 );
		$modello = Rdacademy_Testi_Notifiche::modello( 'invito.persona', $L );
		$v       = self::valori_invito( $nome, $login, $link, $L, $uid );
		$v['pulsante_html'] = self::pulsante( $link, Rdacademy_Lingua::t( 'mail.attiva', $L ),
			'<p style="margin:0 0 18px;text-align:center;font-size:12.5px;color:#6B7280">' . Rdacademy_Lingua::fl( $L, 'mail.vale_giorni', $giorni ) . '</p>' );
		$nota = '';
		if ( $reinvio ) {
			$nt   = Rdacademy_Lingua::riempi( Rdacademy_Testi_Notifiche::modello( 'reinvio', $L )['testo'], $v, false );
			$nota = $nt === '' ? '' : '<p style="margin:0 0 14px;padding:10px 14px;background:#EFF6FF;border:1px solid #BFDBFE;border-radius:8px;font-size:13px;color:#1E3A8A">'
				. nl2br( esc_html( $nt ), false ) . '</p>';
		}
		return self::involucro( $L, $nota . Rdacademy_Lingua::riempi( $modello['testo'], $v, true ), $link, self::piede( $L, true ) );
	}
}

endif;
