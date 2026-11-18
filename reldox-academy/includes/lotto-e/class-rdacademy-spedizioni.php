<?php
/**
 * Rdacademy_Spedizioni — IL REGISTRO DELLE SPEDIZIONI (analisi tecnica ed. 13: TIENI, ridotto).
 * =============================================================================
 * Ogni messaggio che il prodotto manda dalla casella di servizio lascia UNA riga: quando,
 * che cosa, a chi, da chi (chi ha premuto, o «pianificatore» / «porta d accesso» / «sistema»), l esito, il motivo.
 *
 * ⛔ GLI ESITI DICONO IL VERO:
 *  - «spedita»     = il server di posta della casella ha ACCETTATO il messaggio (la sua risposta letterale è salvata).
 *                    Non vuol dire «arrivato»: lo dice solo il server del destinatario.
 *  - «non_spedita» = il messaggio NON è partito (casella non verificata, server che rifiuta, destinatario non valido…), col motivo.
 *  - «respinta»    = è partito, ma il server del destinatario lo ha rifiutato: il rifiuto è tornato nella casella di servizio e
 *                    «Controlla i rifiuti» l ha abbinato alla riga.
 *  - «in_ritardo»  = il server del destinatario ha avvisato che la consegna è in ritardo (avviso temporaneo).
 * Una quarantena antispam del destinatario che non rimanda indietro niente resta «spedita»: la pagina lo scrive.
 *
 * IL PUNTO UNICO. Tutte le mail passano da Rdacademy_Casella::spedisci() (e la prova da prova_spedizione()): lì si scrive la riga.
 * Chi spedisce dice prima «che cosa» con contesto() (Rdacademy_Password_Mail); le partenze fermate prima del motore (casella non
 * verificata, gettone non emesso) si scrivono con non_partita().
 * La colonna `persona_id` resta nella tabella e resta vuota: l Academy non ha schede fuori dagli utenti.
 * Il mittente è SEMPRE la casella di servizio di Impostazioni → Email, chiunque prema il pulsante.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

final class Rdacademy_Schema_Spedizioni_V1 {

	const FLAG    = 'rdacademy_schema_spedizioni_v1';
	const VERSION = 'v1';
	const TABLE   = 'spedizioni';

	public static function maybe_rebuild(): void {
		if ( (string) Rdacademy_Option::get( self::FLAG, '' ) === self::VERSION
			&& Rdacademy_Schema_Flag_Guard::table_exists( self::TABLE ) === true ) {
			return;
		}
		self::create();
		if ( Rdacademy_Schema_Flag_Guard::table_exists( self::TABLE ) === true ) {
			Rdacademy_Option::set( self::FLAG, self::VERSION );
		}
	}

	private static function create(): void {
		$p = RDACADEMY_TABLE_PREFIX;
		Rdacademy_DB::raw(
			"CREATE TABLE IF NOT EXISTS `{$p}spedizioni` (
				`id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`creato`         DATETIME NOT NULL COMMENT 'UTC',
				`tipo`           VARCHAR(40)  NOT NULL COMMENT 'invito · invito_reinvio · accesso · reimpostazione · reimpostazione_porta · blocco · prova · altro',
				`canale`         VARCHAR(10)  NOT NULL DEFAULT 'email' COMMENT 'email',
				`user_id`        BIGINT UNSIGNED NULL COMMENT 'Account del destinatario',
				`persona_id`     BIGINT UNSIGNED NULL COMMENT 'Sempre vuota nell Academy',
				`a_email`        VARCHAR(190) NOT NULL DEFAULT '',
				`a_nome`         VARCHAR(190) NOT NULL DEFAULT '',
				`cc`             VARCHAR(500) NOT NULL DEFAULT '',
				`oggetto`        VARCHAR(255) NOT NULL DEFAULT '',
				`mittente`       VARCHAR(190) NOT NULL DEFAULT '' COMMENT 'La casella di servizio usata',
				`da_user_id`     BIGINT UNSIGNED NULL COMMENT 'Chi ha premuto (NULL = pianificatore, porta d accesso o sistema)',
				`da_tipo`        VARCHAR(20)  NOT NULL DEFAULT 'utente' COMMENT 'utente | pianificatore | porta | sistema',
				`esito`          VARCHAR(20)  NOT NULL COMMENT 'spedita | non_spedita | respinta | in_ritardo',
				`errore`         VARCHAR(1000) NOT NULL DEFAULT '',
				`risposta`       VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Risposta letterale del server di posta',
				`message_id`     VARCHAR(190) NOT NULL DEFAULT '',
				`avviso`         VARCHAR(500) NOT NULL DEFAULT '',
				`rifiuto_il`     DATETIME NULL COMMENT 'UTC: quando è tornato il rifiuto',
				`rifiuto_motivo` VARCHAR(1000) NOT NULL DEFAULT '',
				`nota`           VARCHAR(255) NOT NULL DEFAULT '',
				PRIMARY KEY (`id`),
				KEY `idx_sp_creato` (`creato`),
				KEY `idx_sp_user` (`user_id`, `id`),
				KEY `idx_sp_persona` (`persona_id`, `id`),
				KEY `idx_sp_mid` (`message_id`),
				KEY `idx_sp_email` (`a_email`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro delle spedizioni'"
		);
	}
}

final class Rdacademy_Spedizioni {

	const TABELLA = 'spedizioni';
	/** Tipi di invio che riguardano l accesso di una persona (la pillola in Gestione utenti). */
	const TIPI_ACCESSO = array( 'invito', 'invito_reinvio', 'accesso', 'reimpostazione', 'reimpostazione_porta' );
	const NOMI = array(
		'invito'               => 'Invito (credenziali)',
		'invito_reinvio'       => 'Invito rimandato',
		'accesso'              => 'Invio login (collegamento d’accesso)',
		'reimpostazione'       => 'Reimposta password',
		'reimpostazione_porta' => 'Password dimenticata (dalla porta d’accesso)',
		'blocco'               => 'Avviso di accesso bloccato',
		'prova'                => 'Prova della casella',
		'altro'                => 'Messaggio',
	);
	const ESITI = array(
		'spedita'     => 'Accettata dal server di posta',
		'non_spedita' => 'Non spedita',
		'respinta'    => 'Respinta dal destinatario',
		'in_ritardo'  => 'Consegna in ritardo',
	);

	/** @var array{tipo:string,uid:int,da_tipo:string,nota?:string}|null */
	private static ?array $contesto = null;

	/** Prima di spedire: che cosa, a quale account. $da_tipo vuoto = deciso da solo (utente collegato · pianificatore · porta). */
	public static function contesto( string $tipo, int $uid = 0, string $da_tipo = '', string $nota = '' ): void {
		// $nota: un riferimento che la riga porta con sé.
		self::$contesto = array( 'tipo' => isset( self::NOMI[ $tipo ] ) ? $tipo : 'altro', 'uid' => $uid, 'da_tipo' => $da_tipo, 'nota' => $nota );
	}

	public static function tabella_ok(): bool {
		return class_exists( 'Rdacademy_Schema_Flag_Guard' ) && Rdacademy_Schema_Flag_Guard::table_exists( self::TABELLA ) === true;
	}

	/** Chi spedisce: [da_user_id, da_tipo]. */
	private static function chi( string $forzato ): array {
		if ( $forzato !== '' ) {
			$u = $forzato === 'utente' ? Rdacademy_Auth::get_current_user() : null;
			return array( $u ? (int) $u['id'] : null, $forzato );
		}
		if ( wp_doing_cron() ) return array( null, 'pianificatore' );
		$u = Rdacademy_Auth::get_current_user();
		if ( $u ) return array( (int) $u['id'], 'utente' );
		return array( null, 'porta' );
	}

	private static function uid_di_email( string $email ): int {
		if ( $email === '' ) return 0;
		$r = Rdacademy_DB::get_row( 'SELECT id FROM `' . RDACADEMY_TABLE_PREFIX . 'users` WHERE LOWER(email) = ? ORDER BY id LIMIT 1', array( strtolower( $email ) ), 's' );
		return $r ? (int) $r['id'] : 0;
	}

	private static function corto( string $s, int $n ): string {
		$s = trim( preg_replace( '/\s+/', ' ', $s ) );
		return mb_strlen( $s ) > $n ? mb_substr( $s, 0, $n - 1 ) . '…' : $s;
	}

	private static function scrivi( array $r ): int {
		if ( ! self::tabella_ok() ) return 0;
		$c = array( 'creato', 'tipo', 'canale', 'user_id', 'persona_id', 'a_email', 'a_nome', 'cc', 'oggetto', 'mittente', 'da_user_id', 'da_tipo', 'esito', 'errore', 'risposta', 'message_id', 'avviso', 'nota' );
		$v = array(); $t = '';
		foreach ( $c as $k ) {
			$x = $r[ $k ] ?? ( in_array( $k, array( 'user_id', 'persona_id', 'da_user_id' ), true ) ? null : '' );
			$v[] = $x; $t .= ( in_array( $k, array( 'user_id', 'persona_id', 'da_user_id' ), true ) ? 'i' : 's' );
		}
		$id = Rdacademy_DB::insert( 'INSERT INTO `' . RDACADEMY_TABLE_PREFIX . 'spedizioni` (`' . implode( '`,`', $c ) . '`) VALUES (' . implode( ',', array_fill( 0, count( $c ), '?' ) ) . ')', $v, $t );
		return (int) $id;
	}

	/** Dal motore della casella, dopo ogni tentativo. $esito = quello di Rdacademy_Casella::spedisci. */
	public static function dopo_spedisci( string $a, string $nome_a, array $cc, string $oggetto, array $esito, string $risposta, string $mid, string $mittente ): void {
		$ctx = self::$contesto ?? array( 'tipo' => 'altro', 'uid' => 0, 'da_tipo' => '' );
		self::$contesto = null;
		$uid = (int) $ctx['uid'] ?: self::uid_di_email( $a );
		list( $da, $da_tipo ) = self::chi( (string) $ctx['da_tipo'] );
		$ccs = array();
		foreach ( $cc as $x ) { $e = trim( (string) ( $x[0] ?? '' ) ); if ( $e !== '' ) $ccs[] = $e; }
		self::scrivi( array(
			'creato' => gmdate( 'Y-m-d H:i:s' ), 'tipo' => $ctx['tipo'], 'canale' => 'email', 'user_id' => $uid ?: null, 'persona_id' => null,
			'a_email' => strtolower( trim( $a ) ), 'a_nome' => self::corto( $nome_a, 190 ), 'cc' => self::corto( implode( ', ', $ccs ), 500 ),
			'oggetto' => self::corto( $oggetto, 255 ), 'mittente' => $mittente, 'da_user_id' => $da, 'da_tipo' => $da_tipo,
			'esito' => ! empty( $esito['success'] ) ? 'spedita' : 'non_spedita', 'errore' => self::corto( (string) ( $esito['error'] ?? '' ), 1000 ),
			'risposta' => self::corto( $risposta, 500 ), 'message_id' => $mid, 'avviso' => self::corto( (string) ( $esito['avviso'] ?? '' ), 500 ),
			'nota' => self::corto( (string) ( $ctx['nota'] ?? '' ), 255 ),
		) );
	}

	/** Una partenza fermata prima del motore (casella non verificata, gettone non emesso, …). */
	public static function non_partita( string $tipo, array $utente, string $motivo ): void {
		list( $da, $da_tipo ) = self::chi( '' );
		$uid = (int) ( $utente['id'] ?? 0 );
		self::$contesto = null;
		self::scrivi( array(
			'creato' => gmdate( 'Y-m-d H:i:s' ), 'tipo' => isset( self::NOMI[ $tipo ] ) ? $tipo : 'altro', 'canale' => 'email', 'user_id' => $uid ?: null,
			'persona_id' => null, 'a_email' => strtolower( trim( (string) ( $utente['email'] ?? '' ) ) ),
			'a_nome' => self::corto( trim( (string) ( $utente['first_name'] ?? '' ) . ' ' . (string) ( $utente['last_name'] ?? '' ) ), 190 ),
			'mittente' => (string) ( Rdacademy_Casella::leggi()['mittente'] ?? '' ), 'da_user_id' => $da, 'da_tipo' => $da_tipo,
			'esito' => 'non_spedita', 'errore' => self::corto( $motivo, 1000 ), 'nota' => 'fermata prima della spedizione',
		) );
	}

	/** La prova della casella (Impostazioni → Email → «Prova la spedizione»). */
	public static function prova( string $a, bool $ok, array $passi, string $mittente ): void {
		$ult = $passi ? end( $passi ) : array();
		$ko = '';
		foreach ( $passi as $p ) { if ( ( $p['esito'] ?? '' ) === 'ko' ) { $ko = trim( ( $p['titolo'] ?? '' ) . ': ' . ( $p['consiglio'] ?? '' ) . ' ' . ( $p['server'] ?? '' ) ); break; } }
		list( $da, $da_tipo ) = self::chi( '' );
		self::scrivi( array(
			'creato' => gmdate( 'Y-m-d H:i:s' ), 'tipo' => 'prova', 'canale' => 'email', 'user_id' => null, 'persona_id' => null,
			'a_email' => strtolower( trim( $a ) ), 'oggetto' => 'Prova della casella di servizio', 'mittente' => $mittente,
			'da_user_id' => $da, 'da_tipo' => $da_tipo, 'esito' => $ok ? 'spedita' : 'non_spedita',
			'errore' => $ok ? '' : self::corto( $ko, 1000 ), 'risposta' => $ok ? self::corto( (string) ( $ult['server'] ?? '' ), 500 ) : '',
		) );
	}

	/* ===================================================================== *
	 *  LETTURA
	 * ===================================================================== */

	/** Una riga per la vista: le parole già pronte. */
	public static function riga( array $r, array $nomi_da ): array {
		$q = strtotime( (string) $r['creato'] . ' UTC' );
		$da = '';
		switch ( (string) $r['da_tipo'] ) {
			case 'pianificatore': $da = 'Pianificatore (invio automatico)'; break;
			case 'porta':         $da = 'La persona, dalla porta d’accesso'; break;
			case 'sistema':       $da = 'Sistema'; break;
			default:              $da = $nomi_da[ (int) $r['da_user_id'] ] ?? ( $r['da_user_id'] ? 'utente #' . (int) $r['da_user_id'] : '—' );
		}
		$motivo = '';
		if ( $r['esito'] === 'non_spedita' ) $motivo = (string) $r['errore'];
		elseif ( in_array( $r['esito'], array( 'respinta', 'in_ritardo' ), true ) ) $motivo = (string) $r['rifiuto_motivo'];
		return array(
			'id' => (int) $r['id'], 'quando' => wp_date( 'd/m/Y H:i', $q ), 'iso' => (string) $r['creato'],
			'tipo' => (string) $r['tipo'], 'cosa' => self::NOMI[ $r['tipo'] ] ?? (string) $r['tipo'], 'canale' => (string) $r['canale'],
			'a_email' => (string) $r['a_email'], 'a_nome' => (string) $r['a_nome'], 'cc' => (string) $r['cc'], 'oggetto' => (string) $r['oggetto'],
			'da' => $da, 'mittente' => (string) $r['mittente'], 'esito' => (string) $r['esito'], 'esito_t' => self::ESITI[ $r['esito'] ] ?? (string) $r['esito'],
			'motivo' => $motivo, 'risposta' => (string) $r['risposta'], 'avviso' => (string) $r['avviso'], 'nota' => (string) $r['nota'],
			'rifiuto_il' => $r['rifiuto_il'] ? wp_date( 'd/m/Y H:i', strtotime( (string) $r['rifiuto_il'] . ' UTC' ) ) : '',
			'persona_id' => (int) $r['persona_id'], 'user_id' => (int) $r['user_id'],
		);
	}

	/** @return array<int,string> nome di chi ha premuto, per id. */
	private static function nomi_da( array $righe ): array {
		$ids = array_values( array_unique( array_filter( array_map( static function ( $r ) { return (int) $r['da_user_id']; }, $righe ) ) ) );
		if ( ! $ids ) return array();
		$out = array();
		foreach ( Rdacademy_DB::get_results( 'SELECT id, first_name, last_name, email FROM `' . RDACADEMY_TABLE_PREFIX . 'users` WHERE id IN (' . implode( ',', array_map( 'intval', $ids ) ) . ')' ) ?: array() as $u ) {
			$n = trim( (string) $u['first_name'] . ' ' . (string) $u['last_name'] );
			$out[ (int) $u['id'] ] = $n !== '' ? $n : (string) $u['email'];
		}
		return $out;
	}

	/** @param array{utente?:int,esito?:string,tipo?:string,q?:string,giorni?:int,limite?:int} $f */
	public static function elenco( array $f ): array {
		if ( ! self::tabella_ok() ) return array();
		$p = RDACADEMY_TABLE_PREFIX; $w = array( '1=1' ); $v = array(); $t = '';
		if ( ! empty( $f['utente'] ) ) { $w[] = 'user_id = ?'; $v[] = (int) $f['utente']; $t .= 'i'; }
		if ( ! empty( $f['esito'] ) && isset( self::ESITI[ $f['esito'] ] ) ) { $w[] = 'esito = ?'; $v[] = $f['esito']; $t .= 's'; }
		if ( ( $f['esito'] ?? '' ) === 'problemi' ) $w[] = "esito IN ('non_spedita','respinta','in_ritardo')";
		if ( ! empty( $f['tipo'] ) && isset( self::NOMI[ $f['tipo'] ] ) ) { $w[] = 'tipo = ?'; $v[] = $f['tipo']; $t .= 's'; }
		if ( ! empty( $f['q'] ) ) { $w[] = '(a_email LIKE ? OR a_nome LIKE ? OR oggetto LIKE ?)'; $q = '%' . $f['q'] . '%'; array_push( $v, $q, $q, $q ); $t .= 'sss'; }
		if ( ! empty( $f['giorni'] ) ) { $w[] = 'creato >= ?'; $v[] = gmdate( 'Y-m-d H:i:s', time() - (int) $f['giorni'] * 86400 ); $t .= 's'; }
		$lim = max( 1, min( 1000, (int) ( $f['limite'] ?? 200 ) ) );
		$righe = Rdacademy_DB::get_results( "SELECT * FROM `{$p}spedizioni` WHERE " . implode( ' AND ', $w ) . " ORDER BY id DESC LIMIT {$lim}", $v, $t ) ?: array();
		$n = self::nomi_da( $righe );
		return array_map( static function ( $r ) use ( $n ) { return self::riga( $r, $n ); }, $righe );
	}

	/** L ultima spedizione d accesso con un problema, per account (una query per tutta la lista). @return array<int,array> */
	public static function problemi_per_utente(): array {
		if ( ! self::tabella_ok() ) return array();
		$p = RDACADEMY_TABLE_PREFIX;
		$in = "'" . implode( "','", self::TIPI_ACCESSO ) . "'";
		$righe = Rdacademy_DB::get_results( "SELECT s.* FROM `{$p}spedizioni` s JOIN (SELECT user_id, MAX(id) AS id FROM `{$p}spedizioni` WHERE canale = 'email' AND tipo IN ({$in}) AND user_id IS NOT NULL GROUP BY user_id) u ON u.id = s.id WHERE s.esito IN ('non_spedita','respinta','in_ritardo')" ) ?: array();
		$out = array();
		foreach ( $righe as $r ) $out[ (int) $r['user_id'] ] = self::riga( $r, array() );
		return $out;
	}

	public static function conta_problemi( int $giorni = 30 ): int {
		if ( ! self::tabella_ok() ) return 0;
		$r = Rdacademy_DB::get_row( "SELECT COUNT(*) AS n FROM `" . RDACADEMY_TABLE_PREFIX . "spedizioni` WHERE esito IN ('non_spedita','respinta','in_ritardo') AND creato >= ?", array( gmdate( 'Y-m-d H:i:s', time() - $giorni * 86400 ) ), 's' );
		return (int) ( $r['n'] ?? 0 );
	}

	/* ===================================================================== *
	 *  I RIFIUTI CHE TORNANO NELLA CASELLA
	 * ===================================================================== */

	const ULTIMO_CONTROLLO = 'spedizioni_rifiuti_ultimo';

	/** È un avviso di mancata consegna (DSN o messaggio del server)? */
	public static function e_rifiuto( string $raw ): bool {
		$h = substr( $raw, 0, (int) ( strpos( $raw, "\r\n\r\n" ) ?: strpos( $raw, "\n\n" ) ?: 4000 ) );
		if ( preg_match( '/^Content-Type:\s*multipart\/report;[^\n]*report-type="?delivery-status/im', $h ) ) return true;
		if ( preg_match( '/report-type="?delivery-status/i', $h ) ) return true;
		$from = preg_match( '/^From:\s*(.+)$/im', $h, $m ) ? $m[1] : '';
		$subj = preg_match( '/^Subject:\s*(.+)$/im', $h, $m ) ? self::decodifica( $m[1] ) : '';
		$daemon = (bool) preg_match( '/mailer-daemon|postmaster|mail delivery (sub)?system|microsoftexchange/i', $from );
		$ogg = (bool) preg_match( '/undeliver|delivery (status notification|failure|has failed|failed|delayed)|returned mail|failure notice|mail delivery failed|non recapitabil|mancata consegna|impossibile recapitare|recapito non riuscito|messaggio non consegnato|rejected|bounce/i', $subj );
		return $daemon && $ogg || ( $ogg && (bool) preg_match( '/Final-Recipient:/i', $raw ) );
	}

	private static function decodifica( string $s ): string {
		$d = function_exists( 'iconv_mime_decode' ) ? @iconv_mime_decode( $s, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8' ) : false;
		return $d !== false ? $d : $s;
	}

	/**
	 * Legge un avviso: chi non l ha ricevuto, se è definitivo o un ritardo, il motivo, il Message-ID del nostro messaggio.
	 * @return array{destinatari:array<int,string>,azione:string,motivo:string,mid:string,data:string}
	 */
	public static function leggi_rifiuto( string $raw ): array {
		$dest = array();
		if ( preg_match_all( '/^(?:Final|Original)-Recipient:\s*rfc822;\s*<?([^\s>]+@[^\s>]+)>?/im', $raw, $m ) ) {
			foreach ( $m[1] as $x ) $dest[] = strtolower( trim( $x, " <>\t\r\n;" ) );
		}
		$mid = preg_match( '/<(rdacademy-[0-9a-f]{16}@[^>\s]+)>/i', $raw, $m ) ? '<' . strtolower( $m[1] ) . '>' : '';
		$az  = preg_match( '/^Action:\s*(\w+)/im', $raw, $m ) ? strtolower( $m[1] ) : '';
		$st  = preg_match( '/^Status:\s*([245]\.\d+\.\d+)/im', $raw, $m ) ? $m[1] : '';
		if ( $az === '' ) $az = ( $st !== '' && $st[0] === '4' ) || preg_match( '/delayed|in ritardo|ritardata/i', $raw ) ? 'delayed' : 'failed';
		$diag = '';
		if ( preg_match( '/^Diagnostic-Code:\s*(?:smtp;\s*)?(.+(?:\r?\n[ \t].+)*)/im', $raw, $m ) ) $diag = preg_replace( '/\s+/', ' ', $m[1] );
		if ( $diag === '' && preg_match( '/\b(5\d\d[ -]\d\.\d+\.\d+[^\r\n]{0,200}|55\d [^\r\n]{5,200})/', $raw, $m ) ) $diag = $m[1];
		if ( ! $dest && preg_match( '/<?([A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,})>?\s*(?:\r?\n)?[^\r\n]{0,40}(?:550|554|553|551|User unknown|does not exist|non esiste|rejected)/i', $raw, $m ) ) $dest[] = strtolower( $m[1] );
		$data = preg_match( '/^Date:\s*(.+)$/im', $raw, $m ) ? (string) $m[1] : '';
		$motivo = trim( ( $st !== '' ? $st . ' · ' : '' ) . $diag );
		if ( $motivo === '' ) $motivo = $az === 'delayed' ? 'il server del destinatario non ha ancora consegnato il messaggio' : 'il server del destinatario ha rifiutato il messaggio (motivo non indicato)';
		return array( 'destinatari' => array_values( array_unique( $dest ) ), 'azione' => $az, 'motivo' => self::corto( $motivo, 1000 ), 'mid' => $mid, 'data' => $data );
	}

	/**
	 * «Controlla i rifiuti»: legge la posta in arrivo della casella di servizio dal giorno della spedizione più vecchia ancora
	 * «accettata» (al massimo 30 giorni, al massimo 200 messaggi), riconosce gli avvisi di mancata consegna e li abbina alla riga
	 * (prima per Message-ID, poi per destinatario e data). Non cancella e non segna come letto niente (EXAMINE, BODY.PEEK).
	 * @return array{ok:bool,testo:string,letti:int,rifiuti:int,abbinati:int,non_abbinati:array<int,string>}
	 */
	public static function controlla_rifiuti(): array {
		$vuoto = array( 'ok' => false, 'testo' => '', 'letti' => 0, 'rifiuti' => 0, 'abbinati' => 0, 'non_abbinati' => array() );
		if ( ! self::tabella_ok() ) return array( 'testo' => 'Registro non installato.' ) + $vuoto;
		$c = Rdacademy_Casella::leggi();
		if ( (string) $c['imap_host'] === '' ) return array( 'testo' => 'La casella di servizio non ha il server IMAP (Impostazioni → Email): i rifiuti non si possono leggere.' ) + $vuoto;
		$p = RDACADEMY_TABLE_PREFIX;
		$piu_vecchia = Rdacademy_DB::get_row( "SELECT MIN(creato) AS d FROM `{$p}spedizioni` WHERE canale = 'email' AND esito IN ('spedita','in_ritardo') AND creato >= ?", array( gmdate( 'Y-m-d H:i:s', time() - 30 * 86400 ) ), 's' );
		if ( empty( $piu_vecchia['d'] ) ) { Rdacademy_Option::set( self::ULTIMO_CONTROLLO, gmdate( 'Y-m-d H:i:s' ) ); return array( 'ok' => true, 'testo' => 'Nessuna spedizione degli ultimi 30 giorni da controllare.' ) + $vuoto; }
		$dal = strtotime( (string) $piu_vecchia['d'] . ' UTC' ) - 86400;
		$im = new Rdacademy_Imap_Diretta();
		$col = $im->collega( (string) $c['imap_host'], (int) $c['imap_porta'], (string) $c['imap_sicurezza'] );
		if ( ! $col['ok'] ) return array( 'testo' => 'Server IMAP della casella non raggiungibile: ' . $col['errore'] ) + $vuoto;
		$acc = $im->accedi( Rdacademy_Casella::utente_di( $c ), Rdacademy_Casella::password_di( $c ) );
		if ( ! $acc['ok'] ) { $im->esci(); return array( 'testo' => 'Accesso IMAP rifiutato: ' . $acc['testo'] ) + $vuoto; }
		$ex = $im->esamina( 'INBOX' );
		if ( ! $ex['ok'] ) { $im->esci(); return array( 'testo' => 'Posta in arrivo non apribile: ' . $ex['testo'] ) + $vuoto; }
		$uids = $im->cerca_uid( 'SINCE ' . gmdate( 'd-M-Y', $dal ) );
		$uids = array_slice( $uids, -200 );
		$letti = 0; $rif = 0; $abb = 0; $non = array();
		foreach ( $uids as $u ) {
			$raw = $im->prendi( (int) $u, 200000 );
			if ( $raw === '' ) continue;
			$letti++;
			if ( ! self::e_rifiuto( $raw ) ) continue;
			$rif++;
			$d = self::leggi_rifiuto( $raw );
			$quando = $d['data'] !== '' && strtotime( $d['data'] ) ? gmdate( 'Y-m-d H:i:s', (int) strtotime( $d['data'] ) ) : gmdate( 'Y-m-d H:i:s' );
			$nuovo = $d['azione'] === 'delayed' ? 'in_ritardo' : 'respinta';
			$riga = null;
			if ( $d['mid'] !== '' ) $riga = Rdacademy_DB::get_row( "SELECT id, esito FROM `{$p}spedizioni` WHERE message_id = ? ORDER BY id DESC LIMIT 1", array( $d['mid'] ), 's' );
			if ( ! $riga ) {
				foreach ( $d['destinatari'] as $e ) {
					$riga = Rdacademy_DB::get_row( "SELECT id, esito FROM `{$p}spedizioni` WHERE canale = 'email' AND (a_email = ? OR cc LIKE ?) AND esito IN ('spedita','in_ritardo','respinta') AND creato <= ? ORDER BY id DESC LIMIT 1",
						array( $e, '%' . $e . '%', gmdate( 'Y-m-d H:i:s', strtotime( $quando . ' UTC' ) + 600 ) ), 'sss' );
					if ( $riga ) break;
				}
			}
			if ( ! $riga ) { $non[] = $d['destinatari'] ? implode( ', ', $d['destinatari'] ) : 'destinatario non indicato'; continue; }
			// un rifiuto definitivo vince su un ritardo; un ritardo non torna indietro un rifiuto
			if ( $riga['esito'] === 'respinta' && $nuovo === 'in_ritardo' ) { $abb++; continue; }
			Rdacademy_DB::execute( "UPDATE `{$p}spedizioni` SET esito = ?, rifiuto_il = ?, rifiuto_motivo = ? WHERE id = ?", array( $nuovo, $quando, $d['motivo'], (int) $riga['id'] ), 'sssi' );
			$abb++;
		}
		$im->esci();
		Rdacademy_Option::set( self::ULTIMO_CONTROLLO, gmdate( 'Y-m-d H:i:s' ) );
		$non = array_values( array_unique( $non ) );
		$testo = 'Letti ' . $letti . ' messaggi in arrivo dal ' . wp_date( 'd/m/Y', $dal ) . ': ' . ( $rif ? $rif . ' avvisi di mancata consegna, ' . $abb . ' abbinati alle spedizioni' : 'nessun avviso di mancata consegna' )
			. ( $non ? '; non abbinati: ' . implode( ' · ', array_slice( $non, 0, 5 ) ) : '' ) . '.';
		return array( 'ok' => true, 'testo' => $testo, 'letti' => $letti, 'rifiuti' => $rif, 'abbinati' => $abb, 'non_abbinati' => $non );
	}

	public static function ultimo_controllo_testo(): string {
		$d = (string) Rdacademy_Option::get( self::ULTIMO_CONTROLLO, '' );
		return $d === '' ? 'mai' : wp_date( 'd/m/Y H:i', strtotime( $d . ' UTC' ) );
	}

	/* ===================================================================== *
	 *  AJAX (solo amministratore)
	 * ===================================================================== */

	public static function ajax_elenco( ?array $cur ): void {
		$t = static function ( string $k ): string { return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $k ] ?? '' ) ) ) ); };
		wp_send_json_success( array(
			'righe' => self::elenco( array( 'utente' => (int) $t( 'user_id' ), 'esito' => $t( 'esito' ), 'tipo' => $t( 'tipo' ),
				'q' => $t( 'q' ), 'giorni' => (int) $t( 'giorni' ), 'limite' => (int) $t( 'limite' ) ?: 200 ) ),
			'ultimo_controllo' => self::ultimo_controllo_testo(),
			'mittente' => (string) ( Rdacademy_Casella::leggi()['mittente'] ?? '' ),
		) );
	}

	public static function ajax_rifiuti( ?array $cur ): void {
		$r = self::controlla_rifiuti();
		if ( empty( $r['ok'] ) ) wp_send_json_error( $r['testo'] );
		wp_send_json_success( $r + array( 'ultimo_controllo' => self::ultimo_controllo_testo() ) );
	}
}
