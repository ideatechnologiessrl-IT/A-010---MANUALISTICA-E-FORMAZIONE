<?php
/**
 * class-rdacademy-imap-diretta.php — IMAP PARLATO DIRETTAMENTE, SENZA ESTENSIONE
 * =============================================================================
 * 0.6.09A (S9, RF-BUG-016). NUOVO.
 *
 * PERCHE NON `imap_open()`. Il motore di RelDox CRD (Reldox_Imap) usa
 * l estensione IMAP di PHP. Da PHP 8.4 l estensione non fa piu parte di PHP
 * (spostata in PECL) e il server di produzione gira con PHP 8.5: senza estensione il
 * «Testa» del CRD ripiega sulla sola raggiungibilita e il salvataggio in
 * «Inviata» non parte. Alla casella di servizio servono QUATTRO comandi del
 * protocollo (RFC 3501): LOGIN, LIST, APPEND, LOGOUT, piu STARTTLS. Si parlano
 * qui, su un socket, con tempi massimi su ogni lettura. Una via sola, uguale
 * con o senza estensione.
 *
 * ⛔ OGNI PASSO TORNA LA RISPOSTA LETTERALE DEL SERVER. Chi legge l esito vede
 * che cosa ha detto il server, non una nostra interpretazione al suo posto.
 * ⛔ LA PASSWORD NON FINISCE MAI NEL REGISTRO DEI PASSI: la riga del LOGIN si
 * scrive con la password coperta.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'Rdacademy_Imap_Diretta' ) ) :

final class Rdacademy_Imap_Diretta {

	/** Secondi massimi per collegarsi e per ogni risposta. */
	const ATTESA = 10;

	/** @var resource|null */
	private $fp = null;
	private int $n = 0;
	private string $errore = '';
	/** Il registro: ogni riga scambiata col server, la password coperta. */
	private array $registro = array();

	public function errore(): string { return $this->errore; }
	public function registro(): array { return $this->registro; }

	/**
	 * Apre il collegamento. $sicurezza: ssl | tls (STARTTLS) | nessuna.
	 * @return array{ok:bool, saluto:string, errore:string}
	 */
	public function collega( string $host, int $porta, string $sicurezza ): array {
		$this->chiudi();
		$prefisso = ( $sicurezza === 'ssl' ) ? 'ssl://' : 'tcp://';
		$ctx = stream_context_create( array( 'ssl' => array(
			'verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true, 'peer_name' => $host,
		) ) );
		$errno = 0; $errstr = '';
		$this->registro[] = '… collegamento a ' . $prefisso . $host . ':' . $porta;
		$fp = @stream_socket_client( $prefisso . $host . ':' . $porta, $errno, $errstr, self::ATTESA, STREAM_CLIENT_CONNECT, $ctx );
		if ( ! $fp ) {
			$this->errore = trim( $errstr ) !== '' ? trim( $errstr ) . ' (' . $errno . ')' : 'collegamento non riuscito (' . $errno . ')';
			$this->registro[] = '✕ ' . $this->errore;
			return array( 'ok' => false, 'saluto' => '', 'errore' => $this->errore );
		}
		$this->fp = $fp;
		stream_set_timeout( $fp, self::ATTESA );
		$saluto = $this->riga();
		if ( $saluto === null ) {
			$this->errore = 'il server non ha salutato entro ' . self::ATTESA . ' secondi';
			$this->chiudi();
			return array( 'ok' => false, 'saluto' => '', 'errore' => $this->errore );
		}
		if ( strpos( $saluto, '* OK' ) !== 0 && strpos( $saluto, '* PREAUTH' ) !== 0 ) {
			$this->errore = 'risposta che non è di un server IMAP: ' . $saluto;
			$this->chiudi();
			return array( 'ok' => false, 'saluto' => $saluto, 'errore' => $this->errore );
		}
		if ( $sicurezza === 'tls' ) {
			$r = $this->comando( 'STARTTLS' );
			if ( ! $r['ok'] ) {
				$this->errore = 'STARTTLS rifiutato: ' . $r['testo'];
				$this->chiudi();
				return array( 'ok' => false, 'saluto' => $saluto, 'errore' => $this->errore );
			}
			$cifra = @stream_socket_enable_crypto( $this->fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT );
			if ( $cifra !== true ) {
				$this->errore = 'la cifratura STARTTLS non è partita';
				$this->registro[] = '✕ ' . $this->errore;
				$this->chiudi();
				return array( 'ok' => false, 'saluto' => $saluto, 'errore' => $this->errore );
			}
			$this->registro[] = '… cifratura STARTTLS attiva';
		}
		return array( 'ok' => true, 'saluto' => $saluto, 'errore' => '' );
	}

	/** LOGIN con utente e password. @return array{ok:bool, testo:string} */
	public function accedi( string $utente, string $password ): array {
		return $this->comando( 'LOGIN ' . self::stringa( $utente ) . ' ' . self::stringa( $password ), 'LOGIN ' . self::stringa( $utente ) . ' "••••••••"' );
	}

	/**
	 * LIST "" "*": le cartelle, con gli attributi (\Sent, \Noselect…).
	 * @return array{ok:bool, testo:string, cartelle:array<int,array{nome:string,attributi:string}>}
	 */
	public function cartelle(): array {
		$r = $this->comando( 'LIST "" "*"' );
		$out = array();
		foreach ( $r['righe'] as $riga ) {
			if ( ! preg_match( '/^\* LIST \(([^)]*)\) (?:"[^"]*"|NIL) (.+)$/i', $riga, $m ) ) continue;
			$nome = trim( $m[2] );
			if ( strlen( $nome ) >= 2 && $nome[0] === '"' ) {
				$nome = stripcslashes( substr( $nome, 1, -1 ) );
			}
			$out[] = array( 'nome' => self::da_utf7( $nome ), 'grezzo' => $nome, 'attributi' => $m[1] );
		}
		return array( 'ok' => $r['ok'], 'testo' => $r['testo'], 'cartelle' => $out );
	}

	/** APPEND del messaggio intero nella cartella (grezzo = nome come lo dà il server). */
	public function aggiungi( string $cartella_grezzo, string $messaggio ): array {
		$messaggio = preg_replace( "/\r?\n/", "\r\n", $messaggio );
		$tag = $this->tag();
		$intesta = $tag . ' APPEND ' . self::stringa( $cartella_grezzo ) . ' (\\Seen) {' . strlen( $messaggio ) . '}';
		if ( ! $this->scrivi( $intesta ) ) return array( 'ok' => false, 'testo' => $this->errore );
		$cont = $this->riga();
		if ( $cont === null || strpos( $cont, '+' ) !== 0 ) {
			return array( 'ok' => false, 'testo' => $cont === null ? 'nessuna risposta all\'APPEND' : $cont );
		}
		if ( ! $this->scrivi( $messaggio, '(messaggio di ' . strlen( $messaggio ) . ' byte)' ) ) return array( 'ok' => false, 'testo' => $this->errore );
		return $this->finale( $tag );
	}

	/* 1.4.4B — LA LETTURA DEI RIFIUTI (Rdacademy_Spedizioni::controlla_rifiuti): tre comandi in più, tutti in sola lettura.
	   EXAMINE apre la cartella senza cambiarla; BODY.PEEK non segna i messaggi come letti. */

	/** EXAMINE della cartella (sola lettura). @return array{ok:bool, testo:string} */
	public function esamina( string $cartella_grezzo ): array {
		$r = $this->comando( 'EXAMINE ' . self::stringa( $cartella_grezzo ) );
		return array( 'ok' => $r['ok'], 'testo' => $r['testo'] );
	}

	/** UID SEARCH: gli UID dei messaggi che rispondono ai criteri (es. «SINCE 12-Oct-2026»). @return int[] */
	public function cerca_uid( string $criteri ): array {
		$r = $this->comando( 'UID SEARCH ' . $criteri );
		$out = array();
		foreach ( $r['righe'] as $riga ) {
			if ( preg_match( '/^\* SEARCH\b(.*)$/i', $riga, $m ) ) {
				foreach ( preg_split( '/\s+/', trim( $m[1] ) ) as $n ) if ( ctype_digit( $n ) ) $out[] = (int) $n;
			}
		}
		sort( $out );
		return $out;
	}

	/** UID FETCH del messaggio (al massimo $max byte), senza segnarlo come letto. '' se non arriva. */
	public function prendi( int $uid, int $max = 200000 ): string {
		$tag = $this->tag();
		if ( ! $this->scrivi( $tag . ' UID FETCH ' . $uid . ' (BODY.PEEK[]<0.' . max( 1, $max ) . '>)' ) ) return '';
		$dati = '';
		while ( true ) {
			$r = $this->riga();
			if ( $r === null ) return $dati;
			if ( preg_match( '/\{(\d+)\}$/', $r, $m ) ) {
				$b = $this->byte( (int) $m[1] );
				if ( $b === null ) return $dati;
				if ( $dati === '' ) $dati = $b;
				continue;
			}
			if ( strpos( $r, $tag . ' ' ) === 0 ) return $dati;
		}
	}

	public function esci(): void {
		if ( $this->fp ) { $this->comando( 'LOGOUT' ); }
		$this->chiudi();
	}

	public function chiudi(): void {
		if ( $this->fp ) { @fclose( $this->fp ); }
		$this->fp = null;
	}

	/* ------------------------------------------------------------------ */

	/** @return array{ok:bool, testo:string, righe:array<int,string>} */
	private function comando( string $cmd, string $da_registrare = '' ): array {
		$tag = $this->tag();
		if ( ! $this->scrivi( $tag . ' ' . $cmd, $da_registrare !== '' ? $tag . ' ' . $da_registrare : '' ) ) {
			return array( 'ok' => false, 'testo' => $this->errore, 'righe' => array() );
		}
		return $this->finale( $tag );
	}

	private function finale( string $tag ): array {
		$righe = array();
		while ( true ) {
			$r = $this->riga();
			if ( $r === null ) {
				$this->errore = 'il server non ha risposto entro ' . self::ATTESA . ' secondi';
				return array( 'ok' => false, 'testo' => $this->errore, 'righe' => $righe );
			}
			// Letterale {n}: il resto della voce arriva come n byte grezzi.
			while ( preg_match( '/\{(\d+)\}$/', $r, $m ) ) {
				$dati = $this->byte( (int) $m[1] );
				if ( $dati === null ) { return array( 'ok' => false, 'testo' => 'risposta interrotta', 'righe' => $righe ); }
				$segue = $this->riga();
				$r = substr( $r, 0, -strlen( $m[0] ) ) . '"' . addcslashes( $dati, '"\\' ) . '"' . ( $segue ?? '' );
			}
			if ( strpos( $r, $tag . ' ' ) === 0 ) {
				$resto = substr( $r, strlen( $tag ) + 1 );
				$ok = stripos( $resto, 'OK' ) === 0;
				if ( ! $ok ) $this->errore = $resto;
				return array( 'ok' => $ok, 'testo' => $resto, 'righe' => $righe );
			}
			$righe[] = $r;
		}
	}

	private function tag(): string { return 'F' . str_pad( (string) ( ++$this->n ), 3, '0', STR_PAD_LEFT ); }

	private function scrivi( string $t, string $da_registrare = '' ): bool {
		if ( ! $this->fp ) { $this->errore = 'collegamento chiuso'; return false; }
		$this->registro[] = 'C: ' . ( $da_registrare !== '' ? $da_registrare : $t );
		$t .= "\r\n";
		$tot = strlen( $t ); $fatti = 0;
		while ( $fatti < $tot ) {
			$w = @fwrite( $this->fp, substr( $t, $fatti ) );
			if ( $w === false || $w === 0 ) { $this->errore = 'scrittura interrotta'; return false; }
			$fatti += $w;
		}
		return true;
	}

	private function riga(): ?string {
		if ( ! $this->fp ) return null;
		$r = @fgets( $this->fp, 65536 );
		if ( $r === false ) {
			$meta = @stream_get_meta_data( $this->fp );
			if ( ! empty( $meta['timed_out'] ) ) $this->registro[] = '✕ tempo scaduto (' . self::ATTESA . ' s)';
			return null;
		}
		$r = rtrim( $r, "\r\n" );
		$this->registro[] = 'S: ' . ( strlen( $r ) > 300 ? substr( $r, 0, 300 ) . '…' : $r );
		return $r;
	}

	private function byte( int $n ): ?string {
		$out = '';
		while ( strlen( $out ) < $n ) {
			$b = @fread( $this->fp, $n - strlen( $out ) );
			if ( $b === false || $b === '' ) return null;
			$out .= $b;
		}
		return $out;
	}

	/** Stringa IMAP fra virgolette, con \ e " protetti. */
	public static function stringa( string $s ): string {
		return '"' . addcslashes( $s, '"\\' ) . '"';
	}

	/** Nome di cartella in UTF-7 modificato (RFC 3501 § 5.1.3) → UTF-8, per mostrarlo. */
	public static function da_utf7( string $s ): string {
		return preg_replace_callback( '/&([A-Za-z0-9+,]*)-/', static function ( $m ) {
			if ( $m[1] === '' ) return '&';
			$b = base64_decode( str_replace( ',', '/', $m[1] ) . str_repeat( '=', ( 4 - strlen( $m[1] ) % 4 ) % 4 ) );
			$u = @mb_convert_encoding( (string) $b, 'UTF-8', 'UTF-16BE' );
			return $u !== false ? $u : $m[0];
		}, $s );
	}

	/** Nomi comuni della cartella «Inviata» (copiati da Reldox_Imap::sent_candidates). */
	public static function nomi_inviata(): array {
		return array(
			'sent', 'inbox.sent', 'sent messages', 'sent items', 'sent-mail',
			'posta inviata', 'inbox.posta inviata', 'inviata', 'inviate', 'oggetti inviati',
		);
	}

	/**
	 * Sceglie la cartella «Inviata»: prima l attributo \Sent (RFC 6154), poi i
	 * nomi comuni, poi un nome che contiene sent/inviat (logica di
	 * Reldox_Imap::pick_sent_mailbox, con l attributo davanti).
	 */
	public static function scegli_inviata( array $cartelle ): ?array {
		foreach ( $cartelle as $c ) {
			if ( stripos( (string) $c['attributi'], '\\Sent' ) !== false ) return $c;
		}
		$nomi = self::nomi_inviata();
		foreach ( $cartelle as $c ) {
			if ( in_array( strtolower( (string) $c['nome'] ), $nomi, true ) ) return $c;
		}
		foreach ( $cartelle as $c ) {
			$n = strtolower( (string) $c['nome'] );
			if ( strpos( $n, 'sent' ) !== false || strpos( $n, 'inviat' ) !== false ) return $c;
		}
		return null;
	}
}

endif;
