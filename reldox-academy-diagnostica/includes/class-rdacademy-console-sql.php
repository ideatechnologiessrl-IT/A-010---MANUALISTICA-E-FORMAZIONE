<?php
/**
 * Rdacademy_Console_SQL — IL MOTORE DELLA CONSOLE SQL (banco standard n. 2, area «Database e sorgenti»).
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * RelDoxAcademy 0.1.19A (Formazione F033: BUG-6, undicesima revisione a freddo). 0.1.18A (Formazione F032: BUG-6, decima revisione a freddo). 0.1.17A (Formazione F032: BUG-6, nona revisione a freddo). 0.1.16A (Formazione F031: BUG-6, ottava revisione a freddo). 0.1.14A (Formazione F029: BUG-6, quarta revisione a freddo). 0.1.12A (Formazione F027: BUG-6 e F42, dalla revisione a freddo F026; prima 0.1.11A, F026, voce F29). Mockup MOCKUP-CONSOLE-SQL-v1 approvato (D45, owner
 * 23/11/2026 08:39, chat Formazione F025: «ok approvato»). Nasce dalla console di RelDoxFood 1.24.11L
 * (class-rdfood-console-sql.php, letta in sola lettura in F025) portata allo standard di famiglia: banco
 * prima del motore (banco/F026-banco-console-sql.php).
 *
 * VIVE SOLO NEL PLUGIN DI DIAGNOSTICA (frontiera R1): il cliente non lo riceve; ogni porta chiede manage_options.
 *
 * LE REGOLE (tutte dal server, mai dal browser):
 *  - una istruzione per volta, al massimo MAX_RIGHE righe, tempo massimo TEMPO_MAX secondi;
 *  - lettura: SELECT, SHOW, DESCRIBE/DESC, EXPLAIN, WITH, sempre in transazione READ ONLY (WordPress compreso);
 *  - scrittura SOLO per elenco ammesso: INSERT, UPDATE, DELETE, REPLACE (anche dentro WITH); ogni altra istruzione
 *    è rifiutata anche con la spunta; DROP, TRUNCATE, ALTER, MODIFY, RENAME non si eseguono mai;
 *  - rifiutati sempre: commenti eseguibili /*!…*\/ e /*M!…*\/, INTO (OUTFILE, DUMPFILE, @variabile),
 *    EXPLAIN ANALYZE / ANALYZE, LOAD_FILE, FOR UPDATE / FOR SHARE / LOCK IN SHARE MODE, GET_LOCK e simili, SLEEP, BENCHMARK;
 *  - scrittura in due tempi: prova a secco (transazione annullata, solo tabelle InnoDB), poi CONFERMO legato a
 *    istruzione, sorgente, database, host e utente, valido CONFERMO_TTL secondi e una volta sola; le righe si
 *    ricontano e, se cambiano, si annulla;
 *  - WordPress: solo lettura, sempre; sorgenti non configurate o spente: rifiutate (mai il ripiego sul BASE);
 *  - registro append-only sul BASE (mai su WordPress): prove, scritture e rifiuti; senza registro non si scrive;
 *  - segreti: utente e password della sorgente solo come presenza; colonne segrete mascherate nei risultati;
 *  - database e host dichiarati in ogni riga del referto, anche negli errori.
 *  0.1.12A (BUG-6, F42):
 *  - i nomi fra apici inversi si leggono in chiaro prima dei controlli (`SLEEP`(1) è SLEEP(1));
 *  - mai lo schema mysql, performance_schema, sys; mai SHOW GRANTS / CREATE USER / PROCESSLIST, USER(), CURRENT_USER;
 *  - colonne segrete: non si citano (con SELECT * escono mascherate, anche per nome d origine); errori senza valori né utente;
 *  - in scrittura: niente riferimenti a un altro database, niente viste, sequenze, tabelle con trigger, funzioni memorizzate;
 *    se la sorgente è il database di WordPress, solo tabelle col prefisso di RelDoxAcademy;
 *  - gettone del CONFERMO consumato una volta sola nel database (chiave unica), registro con trigger contro UPDATE e DELETE.
 *  0.1.13A (BUG-6, terza revisione a freddo F028):
 *  - in scrittura nessuna lettura (SELECT, TABLE) da tabelle con colonne segrete o chiave/valore: i segreti non si copiano;
 *  - tabelle chiave/valore (impostazioni, wp_options, *meta): il valore di una chiave segreta si maschera riga per riga, il valore
 *    non si usa in condizioni né in espressioni che ne cambiano il nome; il nome della chiave resta visibile;
 *  - sessione con sql_mode senza ANSI_QUOTES e NO_BACKSLASH_ESCAPES (lo scheletro legge le stringhe come il server), altrimenti niente;
 *  - mai INNODB_TRX, INNODB_LOCKS, SHOW BINLOG/RELAYLOG EVENTS, SHOW EXPLAIN FOR, EXPLAIN FOR CONNECTION, permessi e ruoli, attese GTID, /*+;
 *  - tetto anche in byte (MAX_BYTE), lettura senza copia intera in memoria; utente della sorgente mascherato;
 *  - TABLE, LATERAL, JSON_TABLE come sottoquery; registro: protezione con trigger verificata e dichiarata; attesa dei lucchetti TEMPO_MAX.
 *  0.1.14A (BUG-6, quarta revisione a freddo F028; F45 a-b):
 *  - caratteri di controllo (\x00-\x08, \x0b, \x0c, \x0e-\x1f, \x7f) rifiutati ovunque: «--» seguito da uno di essi è un commento per il server;
 *  - tabelle chiave/valore: il valore si lega alla chiave della SUA tabella nel risultato (stesso alias), si legge solo così com è
 *    (niente espressioni né funzioni: niente oracoli) e in scrittura si può solo assegnare; la chiave non si cambia;
 *  - in scrittura, con tabelle segrete o chiave/valore: una tabella sola (niente UPDATE/DELETE su più tabelle né JOIN), INSERT con le colonne;
 *  - errore a metà lettura: ROSSO e nessun dato parziale; ORDER/GROUP BY per posizione rifiutati sulle tabelle con segreti; ROLE_ solo le viste vere.
 *  - (5ª revisione) chiave immutabile anche con «:=» e nomi a tre parti; col valore chiave/valore nel risultato niente HAVING/ORDER/GROUP BY;
 *    posizioni rifiutate anche con OFFSET; SHOW ALL SLAVES/REPLICAS STATUS rifiutate.
 *  0.1.16A (BUG-6, ottava revisione a freddo F031: 5 alti, 6 medi):
 *  - nomi con caratteri non ASCII rifiutati (il server li confronta senza distinguere accenti, İ e K; i controlli no);
 *  - una funzione chiamata col nome fra apici inversi è una chiamata; in lettura niente funzioni memorizzate né viste che citano colonne segrete;
 *  - chiave/valore: la chiave si lega al valore per database e tabella; il valore mai dentro parentesi (argomento di funzione);
 *  - ORDER/GROUP BY: nessun numero come voce su tabelle con segreti; registro senza numeri né valori (dallo scheletro);
 *  - prova a secco: ALTER del contatore solo se è cambiato; referto: maschere chiave/valore dichiarate, «troncato» nella riga finale.
 *  0.1.17A (BUG-6, nona revisione a freddo F032: 6 alti, 5 medi):
 *  - un nome fra apici inversi nello scheletro sta sempre fra due spazi (`id`UNION è «id UNION» anche per i controlli);
 *  - rifiutati: numeri attaccati a una parola (1.0UNION, 1e1UNION), nomi che cominciano con una cifra, «$» e «\» fuori dalle stringhe,
 *    istruzioni oltre MAX_ISTRUZIONE byte;
 *  - in lettura niente tabelle di un altro database (il referto dichiara un database solo); information_schema resta;
 *  - viste: non si leggono quelle che poggiano su un altra vista, chiamano una funzione memorizzata o leggono una colonna generata da
 *    una segreta; le tabelle con una colonna generata da una segreta non si leggono;
 *  - registro: ogni campo ripulito e accorciato prima di scrivere (byte non UTF-8, sorgente o login lunghi); il rifiuto non registrato si dichiara.
 *  0.1.18A (BUG-6, decima revisione a freddo F032: 1 alto):
 *  - INSERT e REPLACE: subito dopo il nome della tabella fra parentesi va l elenco delle colonne, mai «(VALUES …)» né «(SELECT …)».
 *  0.1.19A (BUG-6, undicesima revisione a freddo F033: 3 alti, 5 medi):
 *  - chiave/valore: il valore si lega alla chiave VERA della riga, letta prima di ogni maschera (un alias sulla colonna della chiave non
 *    lo smaschera); gli alias si confrontano come il server, maiuscole e minuscole distinte (il valore mai alla chiave di un altro alias);
 *  - INSERT e REPLACE su tabelle con colonne segrete o chiave/valore: l elenco delle colonne non può essere vuoto («()» sono tutte le colonne);
 *  - tabelle con colonne segrete o chiave/valore: niente suggerimenti d indice (USE/FORCE/IGNORE INDEX), in lettura e in scrittura; nelle SELECT
 *    il motore esclude da sé (IGNORE INDEX, dichiarato nel referto) ogni indice che contiene una colonna segreta o il valore chiave/valore e
 *    controlla col piano del server (EXPLAIN) che non venga usato: ordine delle righe, LIMIT/OFFSET e tetti non seguono i valori segreti;
 *    le tabelle la cui chiave primaria contiene una colonna segreta non si leggono con SELECT; vale anche attraverso una vista;
 *  - registro senza numeri: nei rifiuti il verbo è solo la prima parola, nessun messaggio cita numeri dell istruzione, i numeri si tolgono
 *    anche dopo «$» o dopo un byte non ASCII;
 *  - viste: non si leggono quelle che leggono tabelle di un altro database (information_schema compreso);
 *  - scrittura: tempo massimo dell istruzione TEMPO_SCRITTURA secondi (meno di TEMPO_MAX) e risposta entro TEMPO_MAX secondi, annullamento
 *    compreso: oltre, la console risponde ROSSO, non conferma nulla e lascia la connessione (il server annulla la transazione);
 *  - tetto in byte contato DOPO le maschere (la risposta non cambia con la lunghezza di un valore segreto).
 *  0.1.20A (BUG-6, dodicesima revisione a freddo F034: 3 alti, 4 medi; owner 26/11/2026 15:17, chat Formazione F034, D47 e D48):
 *  - D47 MASCHERA PER DIFETTO: una colonna si vede in chiaro solo se il suo nome è nell elenco degli innocui (INNOCUE, schemi di nome
 *    innocui) oppure è un numero o una data con un nome senza parole spia (SPIA: pin, chiave, key, codice, token…); tutto il resto è
 *    «segreto»: mascherato con SELECT *, mai citato in espressioni o condizioni. Nelle tabelle chiave/valore si vede in chiaro solo il
 *    valore delle chiavi dell elenco INNOCUE_KV (senza parole spia); il contenuto del valore non decide più nulla;
 *  - D48 SOLA LETTURA: INSERT, UPDATE, DELETE e REPLACE sono rifiutati (niente prova a secco, niente CONFERMO): le scritture tornano
 *    in una versione successiva, con la loro revisione a freddo; il codice delle scritture resta ma non si raggiunge (SOLA_LETTURA);
 *  - in lettura non si leggono le tabelle che leggono altrove (MERGE, FEDERATED, CONNECT, SPIDER) né le tabelle partizionate
 *    (PARTITION, EXPLAIN e information_schema.PARTITIONS direbbero in che intervallo sta un valore);
 *  - registro: le parole con una cifra (anche non ASCII) si tolgono intere, nell istruzione e nel messaggio; una sorgente sconosciuta
 *    si scrive «sconosciuta»; il referto non dichiara più i millisecondi.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rdacademy_Console_SQL {

	const MAX_RIGHE    = 1000;
	const MAX_BYTE     = 5000000;
	const MAX_ISTRUZIONE = 20000;
	const TEMPO_MAX    = 10;
	/** F033 (R11-7): tetto dell istruzione in scrittura, più basso: l annullamento di ciò che è già scritto dura quanto la scrittura e più. */
	const TEMPO_SCRITTURA = 3;
	const CONFERMO_TTL = 300;
	const REGISTRO     = 'console_registro';
	const GETTONI      = 'console_gettoni';

	const LETTURE        = array( 'SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN', 'WITH' );
	const SCRITTURE      = array( 'INSERT', 'UPDATE', 'DELETE', 'REPLACE' );
	const SEMPRE_VIETATE = array( 'DROP', 'TRUNCATE', 'ALTER', 'MODIFY', 'RENAME' );

	/** D48 (owner 26/11/2026 15:17, chat Formazione F034): nella 0.1.20A la console è in sola lettura. */
	const SOLA_LETTURA = true;

	/** D47: parole spia — un nome che ne contiene una è segreto anche se è nell elenco o è un numero. */
	const SPIA = '/pass|pwd|token|gettone|secret|segret|key|chiave|pin|otp|auth|cred|hash|salt|nonce|sess|cookie|cert|privat|parola|codice|iban|carta|card|cvv|firma|sign|bearer|sk_|api|licen|smtp|definer|grantee|seed|recovery|recupero/i';

	/** D47: nomi di colonna innocui (si confrontano in minuscolo, interi). */
	const INNOCUE = array( 'id', 'nome', 'name', 'titolo', 'title', 'stato', 'status', 'tipo', 'type', 'slug', 'ordine', 'lingua', 'ruolo', 'role', 'email', 'descrizione', 'livello', 'versione', 'version', 'attivo', 'active', 'quando', 'data', 'ora', 'n', 'conteggio', 'totale', 'colore',
		'user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status', 'display_name',
		'post_title', 'post_status', 'post_type', 'post_name', 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'post_parent', 'post_author', 'post_mime_type', 'menu_order', 'comment_count', 'comment_status', 'ping_status', 'guid', 'autoload',
		'table_name', 'table_schema', 'table_type', 'table_rows', 'engine', 'row_format', 'table_collation', 'create_time', 'update_time', 'check_time', 'column_name', 'column_type', 'data_type', 'is_nullable', 'column_key', 'extra', 'ordinal_position', 'character_maximum_length', 'numeric_precision', 'numeric_scale', 'character_set_name', 'collation_name',
		'index_name', 'non_unique', 'seq_in_index', 'cardinality', 'index_type', 'nullable', 'sub_part', 'packed', 'constraint_name', 'referenced_table_name', 'referenced_column_name', 'routine_name', 'routine_type', 'trigger_name', 'event_manipulation', 'action_timing', 'event_object_table', 'schema_name', 'default_character_set_name', 'default_collation_name', 'variable_name',
		'select_type', 'table', 'possible_keys', 'key_len', 'ref', 'rows', 'r_rows', 'filtered', 'r_filtered', 'tables_in_', 'field', 'null', 'collation', 'non-unique', 'seq', 'database', 'rows_examined', 'level', 'code', 'message' );

	/** D47: chiavi innocue delle tabelle chiave/valore: solo di queste il valore si vede in chiaro. */
	const INNOCUE_KV = array( 'siteurl', 'home', 'blogname', 'blogdescription', 'admin_email', 'template', 'stylesheet', 'current_theme', 'db_version', 'db_upgraded', 'initial_db_version', 'permalink_structure', 'timezone_string', 'gmt_offset', 'date_format', 'time_format', 'start_of_week', 'blog_charset', 'wplang', 'users_can_register', 'default_role', 'posts_per_page', 'blog_public', 'active_plugins', 'show_on_front', 'page_on_front', 'page_for_posts', 'uploads_use_yearmonth_folders', 'default_category', 'default_comment_status',
		'titolo', 'versione', 'version', 'lingua', 'stato', 'colore_tema', 'nome_sito', 'tema', 'fuso_orario', 'formato_data',
		'nickname', 'first_name', 'last_name', 'description', 'locale', 'rich_editing', 'admin_color', 'show_admin_bar_front', '_edit_last', '_edit_lock', '_wp_page_template', '_thumbnail_id', '_wp_attached_file' );

	/** Vietati in qualunque punto dell istruzione (sullo scheletro, senza stringhe né commenti). */
	const VIETATI_OVUNQUE = array(
		'/\bINTO\s+(OUTFILE|DUMPFILE)\b/i'                     => '«SELECT … INTO OUTFILE/DUMPFILE» rifiutata: la console non scrive file.',
		'/\bANALYZE\b/i'                                       => 'EXPLAIN ANALYZE e ANALYZE rifiutati: eseguono davvero l istruzione.',
		// F030 (6ª revisione a freddo, ALTO): PROCEDURE ANALYSE() mostra Min_value e Max_value di ogni colonna, segrete comprese
		'/\bANALYSE\b|^(?!\s*SHOW\b).*\bPROCEDURE\b/is'   => 'PROCEDURE ANALYSE() rifiutata: mostra i valori delle colonne, segreti compresi.',
		'/\bLOAD_FILE\s*\(/i'                                  => 'LOAD_FILE() rifiutata: la console non legge file del server.',
		'/\bFOR\s+(UPDATE|SHARE)\b|\bLOCK\s+IN\s+SHARE\s+MODE\b/i' => 'FOR UPDATE, FOR SHARE e LOCK IN SHARE MODE rifiutati: la console non prende lucchetti.',
		'/\b(GET_LOCK|RELEASE_LOCK|RELEASE_ALL_LOCKS|MASTER_POS_WAIT|MASTER_GTID_WAIT|SOURCE_POS_WAIT|WAIT_FOR_EXECUTED_GTID_SET|WAIT_UNTIL_SQL_THREAD_AFTER_GTIDS)\s*\(/i' => 'GET_LOCK e funzioni d attesa rifiutate.',
		'/\b(SLEEP|BENCHMARK)\s*\(/i'                          => 'SLEEP e BENCHMARK rifiutati: tengono occupato il server.',
		'/\b(NEXTVAL|SETVAL|LASTVAL)\s*\(|\b(NEXT|PREVIOUS)\s+VALUE\s+FOR\b/i' => 'Sequenze rifiutate: NEXTVAL e simili cambiano il server fuori da ogni transazione.',
		'/\bSHOW\s+(GRANTS|CREATE\s+USER|(FULL\s+)?PROCESSLIST|ENGINES?\b)/i' => 'SHOW GRANTS, SHOW CREATE USER, SHOW PROCESSLIST e SHOW ENGINE rifiutati: utenti e permessi del server non si mostrano.',
		'/\b(USER|CURRENT_USER|SESSION_USER|SYSTEM_USER|CURRENT_ROLE)\s*\(|\bCURRENT_USER\b|\bCURRENT_ROLE\b/i' => 'USER(), CURRENT_USER e simili rifiutati: l utente della sorgente si mostra solo come presenza.',
		'/\b(PROCESSLIST|USER_PRIVILEGES|USER_STATISTICS|CLIENT_STATISTICS|APPLICABLE_ROLES|ENABLED_ROLES|USER_ATTRIBUTES|SCHEMA_PRIVILEGES|TABLE_PRIVILEGES|COLUMN_PRIVILEGES|ADMINISTRABLE_ROLE_AUTHORIZATIONS|ROLE_TABLE_GRANTS|ROLE_COLUMN_GRANTS|ROLE_ROUTINE_GRANTS|ROLE_USAGE_GRANTS)\b/i' => 'Tabelle di sistema con utenti e permessi rifiutate.',
		'/\bINNODB_(TRX|LOCKS|LOCK_WAITS)\b|\bDATA_LOCK(S|_WAITS)\b/i' => 'INNODB_TRX, INNODB_LOCKS e simili rifiutate: mostrano le istruzioni delle altre sessioni.',
		// F034 (R12-6): PARTITION (p), EXPLAIN PARTITIONS e information_schema.PARTITIONS direbbero in che intervallo sta un valore
		'/\bPARTITIONS?\b/i' => 'Partizioni rifiutate (PARTITION, EXPLAIN PARTITIONS, information_schema.PARTITIONS): direbbero in che intervallo sta un valore.',
		'/\bSHOW\s+(ALL\s+)?(BINLOG|RELAYLOG|BINARY|MASTER|SLAVE|SLAVES|REPLICA|REPLICAS)\b|\bSHOW\s+EXPLAIN\b|\bFOR\s+CONNECTION\b/i' => 'SHOW BINLOG/RELAYLOG EVENTS, SHOW EXPLAIN FOR e EXPLAIN FOR CONNECTION rifiutati: istruzioni passate o di altre sessioni.',
	);

	/** Colonne segrete note: si mascherano nei risultati (nome che contiene uno di questi pezzi). */
	const SEGRETE = array( 'pass', 'pwd', 'token', 'gettone', 'secret', 'segret', 'api_key', 'apikey', 'activation_key', 'hash', 'salt', 'nonce', '_enc', 'authentication', 'definer', 'grantee', 'db_user' );

	/** Tabelle chiave/valore (F028): colonna del valore => colonna della chiave. */
	const KV = array( 'valore' => 'chiave', 'option_value' => 'option_name', 'meta_value' => 'meta_key' );

	/** Schemi del server mai toccati dalla console. */
	const SCHEMI_VIETATI = array( 'mysql', 'performance_schema', 'sys' );

	/** Il «adesso» del motore (il banco lo sposta per provare la scadenza del CONFERMO). */
	public static ?int $adesso = null;

	/** F033 (R11-7): il banco può solo ACCORCIARE il tempo di risposta di una scrittura (mai allungarlo oltre TEMPO_MAX). */
	public static ?float $risposta_entro = null;

	/** @var array<int,array> gettoni quando WordPress non c è */
	private static array $gettoni = array();

	private static function ora(): int { return self::$adesso ?? time(); }

	/* ============================================================= *
	 *  Classificazione (pura: nessuna connessione)
	 * ============================================================= */

	/**
	 * Lo scheletro: stringhe, identificatori fra apici inversi e commenti diventano spazi.
	 * Restituisce [scheletro, identificatori fra apici inversi, errore, posizioni (F033)].
	 */
	public static function scheletro( string $sql ): array {
		$out = ''; $ids = array(); $n = strlen( $sql ); $i = 0;
		// F033 (R11-4): $fine[k] = posizione nell istruzione subito dopo ciò che ha dato il byte k dello scheletro (serve a inserire IGNORE INDEX)
		$fine = array();
		while ( $i < $n ) {
			for ( $k = count( $fine ), $kn = strlen( $out ); $k < $kn; $k++ ) $fine[] = $i;
			$c = $sql[ $i ]; $d = $i + 1 < $n ? $sql[ $i + 1 ] : '';
			if ( $c === '/' && $d === '*' ) {
				$dopo = substr( $sql, $i + 2, 2 );
				if ( ( $dopo[0] ?? '' ) === '!' || strtoupper( $dopo ) === 'M!' ) {
					return array( '', $ids, 'Commento eseguibile «/*!…*/» rifiutato.' );
				}
				if ( ( $dopo[0] ?? '' ) === '+' ) {
					return array( '', $ids, 'Suggerimenti «/*+ … */» rifiutati: cambiano tempo massimo e modo del server per l istruzione.' );
				}
				$j = strpos( $sql, '*/', $i + 2 );
				if ( $j === false ) return array( '', $ids, 'Commento non chiuso: istruzione rifiutata.' );
				$out .= ' '; $i = $j + 2; continue;
			}
			if ( $c === '#' || ( $c === '-' && $d === '-' && ( $i + 2 >= $n || ctype_space( $sql[ $i + 2 ] ) ) ) ) {
				$j = strpos( $sql, "\n", $i );
				$out .= ' '; $i = $j === false ? $n : $j + 1; continue;
			}
			if ( $c === "'" || $c === '"' || $c === '`' ) {
				$j = $i + 1; $val = '';
				while ( true ) {
					if ( $j >= $n ) return array( '', $ids, 'Stringa o nome non chiuso: istruzione rifiutata.' );
					if ( $sql[ $j ] === '\\' && $c !== '`' ) { $val .= substr( $sql, $j, 2 ); $j += 2; continue; }
					if ( $sql[ $j ] === $c ) {
						if ( $j + 1 < $n && $sql[ $j + 1 ] === $c ) { $val .= $c; $j += 2; continue; }
						break;
					}
					$val .= $sql[ $j ]; $j++;
				}
				if ( $c === '`' ) {
					$ids[] = $val;
					// in chiaro (BUG-6 ALTO 1): `SLEEP`(1) si giudica come SLEEP(1); un nome con caratteri strani diventa __idN__
					// F032 (9ª revisione, ALTI 1-3 e 5): sempre fra due spazi, altrimenti `id`UNION diventa «idUNION» e UNION sparisce dai controlli
					$out .= preg_match( '/^[A-Za-z0-9_$\x80-\xff]+$/', $val ) ? ' ' . $val . ' ' : ' __id' . ( count( $ids ) - 1 ) . '__ ';
				} else { $out .= " '' "; }
				$i = $j + 1; continue;
			}
			$out .= $c; $i++;
		}
		for ( $k = count( $fine ), $kn = strlen( $out ); $k < $kn; $k++ ) $fine[] = $i;
		return array( $out, $ids, '', $fine );
	}

	/**
	 * @return array{ok:bool,tipo:string,verbo:string,motivo:string,nomi:array}
	 */
	public static function classifica( string $sql ): array {
		$no = static function ( string $m, string $v = '' ): array { return array( 'ok' => false, 'tipo' => 'rifiuto', 'verbo' => $v, 'motivo' => $m, 'nomi' => array() ); };
		// F029 (4ª revisione, ALTO 1): «--» seguito da un carattere di controllo è un commento per il server e non per lo scheletro
		// F032 (9ª revisione, MEDIO 2): oltre il limite di PCRE i controlli tornano NULL e non fermano più nulla
		if ( strlen( $sql ) > self::MAX_ISTRUZIONE ) return $no( 'Istruzione troppo lunga (' . strlen( $sql ) . ' byte, al massimo ' . self::MAX_ISTRUZIONE . '): rifiutata.' );
		if ( preg_match( '/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $sql ) ) return $no( 'Caratteri di controllo nell istruzione rifiutati (ammessi solo spazio, tab e a capo).' );
		list( $sk, $ids, $err ) = self::scheletro( $sql );
		if ( $err !== '' ) return $no( $err );
		$sk = rtrim( $sk );
		if ( substr( $sk, -1 ) === ';' ) $sk = rtrim( substr( $sk, 0, -1 ) );
		if ( trim( $sk ) === '' ) return $no( 'Istruzione vuota.' );
		// F031 (8ª revisione, ALTI 1, 3 e 4): il server confronta i nomi senza distinguere accenti, İ e K; i controlli confrontano in ASCII
		if ( preg_match( '/[\x80-\xff]/', $sk . implode( '', $ids ) ) ) return $no( 'Nomi con caratteri non ASCII (accenti, lettere speciali) rifiutati: il server li confronta in un altro modo e i controlli non li riconoscerebbero. Nelle stringhe fra apici restano ammessi.' );
		// F032 (9ª revisione, ALTO 4 e dubbio 1): il server legge «1.0UNION» come numero e parola, «9tabella» e «$tabella» come nomi; i controlli no
		if ( strpos( $sk, '$' ) !== false || strpos( $sk, '\\' ) !== false ) return $no( '«$» e «\\» fuori dalle stringhe rifiutati: i controlli non leggerebbero l istruzione come il server.' );
		foreach ( $ids as $idn ) { if ( preg_match( '/^[0-9]|\$/', $idn ) ) return $no( 'Nomi che cominciano con una cifra o contengono «$» rifiutati: i controlli non li riconoscerebbero come il server.' ); }
		if ( preg_match_all( '/(?<![A-Za-z0-9_$])(?:0x[0-9a-f]+|0b[01]+|(?:\d+\.?\d*|\.\d+)(?:e[+-]?\d+)?)/i', $sk, $mn, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $mn[0] as $nm ) {
				$dopo = $sk[ $nm[1] + strlen( $nm[0] ) ] ?? '';
				if ( $dopo !== '' && preg_match( '/[A-Za-z0-9_.]/', $dopo ) ) return $no( /* F033 (R11-5): il messaggio va nel registro, quindi non cita il numero */ 'Numero attaccato a una parola o nome che comincia con una cifra: rifiutato, il server lo leggerebbe in un altro modo. Si mette uno spazio dopo il numero; i nomi cominciano con una lettera.' );
			}
		}
		if ( strpos( $sk, ';' ) !== false ) return $no( 'Due istruzioni in una: rifiutata (una per volta).' );
		$testa = ltrim( $sk, " \t\r\n(" );
		// F033 (R11-5): il verbo è solo la prima parola (lettere, al massimo 20); se l istruzione comincia con altro, nessun verbo:
		// il verbo finisce nei messaggi di rifiuto e quindi nel registro, dove non devono arrivare numeri né valori
		$verbo = preg_match( '/^[A-Za-z_]+/', $testa, $mv ) ? strtoupper( substr( $mv[0], 0, 20 ) ) : '';
		if ( in_array( $verbo, self::SEMPRE_VIETATE, true ) ) {
			return $no( '«' . $verbo . '» rifiutata dal server: DROP, TRUNCATE, ALTER, MODIFY, RENAME non si eseguono mai. In scrittura passano solo INSERT, UPDATE, DELETE, REPLACE; ogni altra istruzione (GRANT, CREATE, SET, LOAD DATA, CALL, LOCK…) è rifiutata.', $verbo );
		}
		foreach ( self::VIETATI_OVUNQUE as $re => $m ) {
			if ( preg_match( $re, $sk ) ) return $no( $m, $verbo );
		}
		preg_match_all( '/[A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*/', $sk, $mm0 );
		$tutti = array_map( 'strtolower', array_merge( $mm0[0], $ids ) );
		foreach ( self::SCHEMI_VIETATI as $sv ) {
			if ( ( $sv !== 'sys' && in_array( $sv, $tutti, true ) ) || preg_match( '/\b' . $sv . '\s*\.|\b(FROM|IN)\s+' . $sv . '\b/i', $sk ) || in_array( $sv, array_map( 'strtolower', $ids ), true ) ) {
				return $no( 'Lo schema «' . $sv . '» del server non si tocca dalla console (utenti, password, sessioni).', $verbo );
			}
		}
		$tipo = '';
		if ( in_array( $verbo, self::SCRITTURE, true ) ) {
			$tipo = 'scrittura';
		} elseif ( in_array( $verbo, self::LETTURE, true ) ) {
			$tipo = 'lettura';
			// REPLACE( è la funzione, non l istruzione (F42 b)
			if ( $verbo === 'WITH' && preg_match( '/\b(INSERT|UPDATE|DELETE)\b|\b(REPLACE)\b(?!\s*\()/i', $sk, $m ) ) {
				$tipo = 'scrittura'; $verbo = strtoupper( $m[2] ?? '' ) !== '' ? 'REPLACE' : strtoupper( $m[1] );
			}
		}
		if ( $tipo === '' ) {
			return $no( ( $verbo !== '' ? '«' . $verbo . '» non ammessa' : 'Istruzione non ammessa (non comincia con una parola)' ) . ': in lettura SELECT, SHOW, DESCRIBE, EXPLAIN, WITH; in scrittura solo INSERT, UPDATE, DELETE, REPLACE.', $verbo );
		}
		// F032 (10ª revisione, ALTO 1): «INSERT INTO t (VALUES (…))» e «(SELECT …)» sono la sorgente, non l elenco delle colonne: scriverebbero tutte le colonne
		if ( in_array( $verbo, array( 'INSERT', 'REPLACE' ), true ) && preg_match( '/^\s*(?:INSERT|REPLACE)\s+(?:(?:LOW_PRIORITY|DELAYED|HIGH_PRIORITY|IGNORE)\s+)*(?:INTO\s+)?[A-Za-z0-9_]+(?:\s*\.\s*[A-Za-z0-9_]+)*\s*\(\s*(?:VALUES?\b|SELECT\b|TABLE\b|WITH\b|\()/i', $sk ) ) {
			return $no( '«' . $verbo . ' … (VALUES …)» o «(SELECT …)» subito dopo il nome della tabella rifiutata: fra parentesi lì va l elenco delle colonne (' . $verbo . ' INTO tabella (colonne) VALUES (…)).', $verbo );
		}
		if ( $tipo === 'lettura' && preg_match( '/\bINTO\b/i', $sk ) ) {
			return $no( '«INTO» in una lettura rifiutato: la console non scrive file né variabili.', $verbo );
		}
		preg_match_all( '/[A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*/', $sk, $mm );
		$nomi = array_values( array_unique( array_map( 'strtolower', array_merge( $mm[0], $ids ) ) ) );
		// riferimenti qualificati x.y (il primo nome può essere un database): servono in scrittura
		$qual = array();
		if ( preg_match_all( '/([A-Za-z0-9_$\x80-\xff]+)\s*\.\s*[A-Za-z0-9_$\x80-\xff`]/', $sk, $mq ) ) {
			foreach ( $mq[1] as $q ) { $qual[] = strtolower( preg_match( '/^__id(\d+)__$/', $q, $mi ) ? (string) ( $ids[ (int) $mi[1] ] ?? $q ) : $q ); }
		}
		preg_match_all( '/([A-Za-z_\x80-\xff][A-Za-z0-9_$\x80-\xff]*)\s*\(/', $sk, $mc );
		// F031 (8ª revisione, ALTO 4): `nome-strano`() è una chiamata come le altre
		foreach ( $mc[1] as $ic => $nc ) { if ( preg_match( '/^__id(\d+)__$/', $nc, $mi ) ) $mc[1][ $ic ] = (string) ( $ids[ (int) $mi[1] ] ?? $nc ); }
		return array( 'ok' => true, 'tipo' => $tipo, 'verbo' => $verbo, 'motivo' => '', 'nomi' => $nomi, 'qualificati' => array_values( array_unique( $qual ) ), 'chiamate' => array_values( array_unique( array_map( 'strtolower', $mc[1] ) ) ) );
	}

	/* ============================================================= *
	 *  Sorgenti (mai una password, mai l utente: solo la presenza)
	 * ============================================================= */

	private static function host_porta( string $h ): array {
		if ( preg_match( '/^(.+):(\d+)$/', $h, $m ) ) return array( $m[1], (int) $m[2] );
		return array( $h, 3306 );
	}

	/** Parametri interni di una sorgente (con la password): mai fuori da questa classe. */
	private static function parametri( string $k ): ?array {
		if ( $k === 'base' ) {
			$c = Rdacademy_DB_Config::get();
			list( $h, $p ) = self::host_porta( (string) ( $c['host'] ?? '' ) );
			return array( 'host' => $h, 'port' => $p, 'name' => (string) ( $c['name'] ?? '' ), 'user' => (string) ( $c['user'] ?? '' ), 'pass' => (string) ( $c['pass'] ?? '' ), 'charset' => 'utf8mb4' );
		}
		if ( $k === 'wordpress' ) {
			if ( ! defined( 'DB_HOST' ) ) return null;
			list( $h, $p ) = self::host_porta( (string) DB_HOST );
			return array( 'host' => $h, 'port' => $p, 'name' => (string) DB_NAME, 'user' => (string) DB_USER, 'pass' => (string) DB_PASSWORD, 'charset' => 'utf8mb4' );
		}
		return Rdacademy_DataSource::connect_params( $k );
	}

	/** @return array<string,array{etichetta:string,stato:string,in_uso:bool,scrivibile:bool,db:string,host:string,utente:string,password:string}> */
	public static function sorgenti(): array {
		$voce = static function ( string $et, string $st, bool $uso, bool $scr, ?array $p ): array {
			return array( 'etichetta' => $et, 'stato' => $st, 'in_uso' => $uso, 'scrivibile' => $scr,
				'db' => (string) ( $p['name'] ?? '' ), 'host' => (string) ( $p['host'] ?? '' ),
				'utente' => ( $p['user'] ?? '' ) !== '' ? 'presente' : 'assente', 'password' => ( $p['pass'] ?? '' ) !== '' ? 'presente' : 'assente' );
		};
		$out = array( 'base' => $voce( 'BASE — verità RelDoxAcademy', 'in_uso', true, true, self::parametri( 'base' ) ) );
		foreach ( Rdacademy_Sorgenti::mappa() as $k => $m ) {
			$st  = Rdacademy_DataSource::state( $k );
			$uso = in_array( $st, array( 'live', 'unprovisioned' ), true );
			$et  = Rdacademy_Sorgenti::numero( $k ) . ' · ' . Rdacademy_Sorgenti::nome( $k );
			$out[ $k ] = $voce( $et, $uso ? 'in_uso' : ( $st === 'disabled' ? 'spenta' : 'non_configurata' ), $uso, $uso, $uso ? self::parametri( $k ) : null );
		}
		$out['wordpress'] = $voce( 'WordPress — non è una sorgente di RelDoxAcademy', 'solo_lettura', true, false, self::parametri( 'wordpress' ) );
		return $out;
	}

	/* ============================================================= *
	 *  Esecuzione
	 * ============================================================= */

	private static function q( mysqli $c, string $sql ) {
		try { return $c->query( $sql ); } catch ( mysqli_sql_exception $e ) { return false; }
	}

	private static function err( mysqli $c ): string {
		// BUG-6 MEDIO 1: niente valori dei dati né utente nei messaggi (tutto ciò che è fra apici diventa «…»)
		// dal primo apice in poi si taglia tutto (un apice dentro il valore non lo fa uscire)
		return $c->errno ? '(' . $c->errno . ') ' . (string) preg_replace( array( '/`[^`]*`@`[^`]*`/', '/[\'"].*$/s' ), array( '…', '«…»' ), $c->error ) : 'errore sconosciuto';
	}

	private static function apri( array $p ): ?mysqli {
		if ( ( $p['host'] ?? '' ) === '' || ( $p['name'] ?? '' ) === '' ) return null;   // senza parametri non si apre nulla (mai un ripiego)
		$r = Rdacademy_DataSource::connetti( $p );
		if ( empty( $r['ok'] ) || ! ( $r['connection'] instanceof mysqli ) ) return null;
		$c = $r['connection'];
		// F028: lo scheletro legge stringhe e nomi come un server senza ANSI_QUOTES e NO_BACKSLASH_ESCAPES: la sessione deve essere così
		$m  = self::colonna( $c, 'SELECT @@SESSION.sql_mode' )[0] ?? '';
		$mm = array_filter( explode( ',', strtoupper( $m ) ), static function ( $x ) { return $x !== '' && $x !== 'ANSI_QUOTES' && $x !== 'NO_BACKSLASH_ESCAPES' && $x !== 'ANSI'; } );
		self::q( $c, "SET SESSION sql_mode = '" . $c->real_escape_string( implode( ',', $mm ) ) . "'" );
		$dopo = strtoupper( self::colonna( $c, 'SELECT @@SESSION.sql_mode' )[0] ?? 'ANSI_QUOTES' );
		if ( strpos( $dopo, 'ANSI_QUOTES' ) !== false || strpos( $dopo, 'NO_BACKSLASH_ESCAPES' ) !== false ) { $c->close(); return null; }
		return $c;
	}

	/** D47 (owner 26/11/2026 15:17, chat Formazione F034): una chiave chiave/valore è innocua solo se è nell elenco e non ha parole spia;
	 *  il contenuto del valore non decide più nulla (R12-2, R12-11). */
	private static function segreta_kv( ?string $k, ?string $v ): bool {
		if ( $k === null ) return true;
		$k = strtolower( $k );
		return ! in_array( $k, self::INNOCUE_KV, true ) || (bool) preg_match( self::SPIA, $k );
	}

	/** D47: le colonne delle tabelle chiave/valore le governa la maschera chiave/valore, non questa. */
	private static function e_kv( string $c ): bool { return isset( self::KV[ $c ] ) || in_array( $c, self::KV, true ); }

	/** D47: un nome innocuo per sé (elenco o schema di nome), senza parole spia. */
	private static function innocua( string $col ): bool {
		$c = strtolower( trim( $col ) );
		if ( $c === '' || preg_match( self::SPIA, $c ) ) return false;
		if ( in_array( $c, self::INNOCUE, true ) || strpos( $c, 'tables_in_' ) === 0 ) return true;
		return (bool) preg_match( '/^[a-z][a-z0-9_]*$/', $c ) && (bool) preg_match( '/(^|_)id$|^id_|_(at|il|del|data|date|gmt|ora)$|^(data|ora|creat|aggiorn|modific|scad|inizio|fine)|^(num|numero|conta|totale|n)_/', $c );
	}

	/** D47: un tipo numero o data (nome del tipo di information_schema, o costante del campo di mysqli). */
	private static function tipo_numero( $tipo ): bool {
		if ( is_int( $tipo ) ) return in_array( $tipo, array( 0, 1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 13, 14, 246 ), true );
		return (bool) preg_match( '/^(tinyint|smallint|mediumint|int|integer|bigint|decimal|numeric|float|double|real|date|datetime|timestamp|time|year)$/i', trim( (string) $tipo ) );
	}

	/** D47 MASCHERA PER DIFETTO: segreta = né innocua per nome, né numero o data con un nome senza parole spia. Senza il tipo, solo il nome. */
	private static function segreta( string $col, $tipo = null ): bool {
		$c = strtolower( trim( $col ) );
		if ( self::e_kv( $c ) ) return false;
		if ( self::innocua( $c ) ) return false;
		return ! ( $tipo !== null && self::tipo_numero( $tipo ) && $c !== '' && ! preg_match( self::SPIA, $c ) );
	}

	/** D47: colonne del server col loro tipo; una colonna è segreta se lo è in almeno una tabella (il nome si confronta in minuscolo). */
	private static function mappa_segrete( mysqli $c ): array {
		$res = self::q( $c, 'SELECT LOWER(TABLE_NAME), LOWER(COLUMN_NAME), DATA_TYPE FROM information_schema.COLUMNS' ); $tab = array(); $col = array();
		if ( $res instanceof mysqli_result ) { while ( $r = $res->fetch_row() ) { $s = self::segreta( (string) $r[1], (string) $r[2] ) || self::e_kv( (string) $r[1] ); $col[ (string) $r[1] ] = ( $col[ (string) $r[1] ] ?? false ) || ( $s && ! self::e_kv( (string) $r[1] ) ); if ( $s ) $tab[ (string) $r[0] ] = true; } $res->free(); }
		return array( $tab, $col );
	}

	/**
	 * Una lettura su una connessione: tempo massimo, transazione READ ONLY, al massimo MAX_RIGHE righe.
	 * Il server rifiuta da solo ogni scrittura arrivata fin qui (difesa in profondità).
	 */
	public static function leggi_su( mysqli $c, string $sql, array $nomi = array() ): array {
		if ( ! self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_MAX ) ) {
			self::q( $c, 'SET SESSION MAX_EXECUTION_TIME = ' . ( self::TEMPO_MAX * 1000 ) );
		}
		if ( ! self::q( $c, 'SET SESSION TRANSACTION READ ONLY' ) || ! self::q( $c, 'START TRANSACTION READ ONLY' ) ) {
			return array( 'ok' => false, 'errore' => 'transazione in sola lettura non aperta: ' . self::err( $c ) );
		}
		$t0  = microtime( true );
		// F028: senza copia intera in memoria (MYSQLI_USE_RESULT), tetto in righe e in byte
		try { $res = $c->query( $sql, MYSQLI_USE_RESULT ); } catch ( mysqli_sql_exception $e ) { $res = false; }
		if ( $res === false ) { $e = self::err( $c ); self::q( $c, 'ROLLBACK' ); return array( 'ok' => false, 'errore' => $e ); }
		$col = array(); $org = array(); $tab = array(); $dati = array(); $tronco = false; $byte = 0; $mascherate = array();
		if ( $res instanceof mysqli_result ) {
			// F033 (R11-2): la tabella di ogni colonna si confronta come la confronta il server, maiuscole e minuscole distinte («O» e «o» sono due righe)
			$tipi = array();
			foreach ( $res->fetch_fields() as $f ) { $col[] = (string) $f->name; $org[] = strtolower( (string) ( $f->orgname ?? '' ) ); $tipi[] = (int) $f->type; $tab[] = (string) ( $f->table ?? '' ) === '' ? '' : (string) ( $f->db ?? '' ) . '.' . (string) $f->table; }
			// F028: tabelle chiave/valore — il valore di una chiave segreta si maschera riga per riga; senza la chiave accanto, sempre
			// F033 (R11-8): le maschere si decidono sulle colonne PRIMA di leggere e si mettono riga per riga, così il tetto in byte conta ciò che esce
			$kv_citate = array_intersect( array_keys( self::KV ), $nomi ); $piano = array(); $segr = array();
			foreach ( $col as $i => $nome ) {
				$o = $org[ $i ] ?? '';
				if ( isset( self::KV[ $o ] ) ) {
					// F029 (4ª revisione, ALTO 2): la chiave è quella della stessa tabella del risultato (stesso alias), mai di un altra riga unita
					$j = false;
					foreach ( $org as $jj => $oj ) { if ( $oj === self::KV[ $o ] && ( $tab[ $jj ] ?? '' ) !== '' && ( $tab[ $jj ] ?? '' ) === ( $tab[ $i ] ?? '' ) ) { $j = $jj; break; } }
					$piano[ $i ] = array( 'kv', $j );
				} elseif ( $kv_citate && ( $o === '' || $o !== strtolower( $nome ) ) ) {
					$piano[ $i ] = array( 'tutto', false ); $mascherate[ $nome ] = true;
				}
			}
			foreach ( $col as $i => $nome ) {
				if ( isset( $piano[ $i ] ) ) continue;
				// D47: la colonna col suo nome d origine; un espressione (senza nome d origine) col nome che ha nel risultato. Numeri e date si vedono
				// se il nome non ha parole spia (le colonne non innocue non si possono citare, quindi un espressione numerica non le legge)
				$o = $org[ $i ] ?? '';
				$ok = ! self::segreta( $o !== '' ? $o : $nome, $tipi[ $i ] );
				if ( $ok ) continue;
				$segr[ $i ] = $nome;
			}
			$rotto = '';
			try {
				while ( $r = $res->fetch_row() ) {
					if ( count( $dati ) >= self::MAX_RIGHE ) { $tronco = true; break; }
					// F033 (R11-1): la chiave VERA della riga, com è arrivata dal server, prima di ogni maschera (un alias sulla chiave la maschera)
					$vera = $r;
					foreach ( $piano as $i => $pm ) {
						$j = $pm[1];
						if ( $pm[0] === 'kv' ) {
							if ( $r[ $i ] !== null && self::segreta_kv( $j === false ? null : $vera[ $j ], $vera[ $i ] ) ) { $r[ $i ] = '•••• (mascherata)'; $mascherate[ $col[ $i ] . ' (valori delle chiavi segrete)' ] = true; }
						} elseif ( $r[ $i ] !== null ) { $r[ $i ] = '•••• (mascherata)'; }
					}
					foreach ( $segr as $i => $nome ) { if ( $r[ $i ] !== null ) $r[ $i ] = '•••• (mascherata)'; }
					foreach ( $r as &$v ) { if ( is_string( $v ) && stripos( $v, 'DEFINER' ) !== false ) $v = (string) preg_replace( '/DEFINER\s*=\s*\S+/i', 'DEFINER=…', $v ); }
					unset( $v );
					// F033 (R11-8): i byte si contano DOPO le maschere: la risposta non cambia con la lunghezza di un valore segreto
					foreach ( $r as $v ) { $byte += strlen( (string) $v ); }
					if ( $byte > self::MAX_BYTE ) { $tronco = true; break; }
					$dati[] = $r;
				}
				// F029 (4ª revisione, MEDIO 2): un errore a metà (tempo massimo, EXP…) non è una lettura riuscita con dati parziali
				if ( ! $tronco && $c->errno ) $rotto = self::err( $c );
			} catch ( mysqli_sql_exception $e ) { $rotto = self::err( $c ); if ( $rotto === 'errore sconosciuto' ) $rotto = '(' . (int) $e->getCode() . ') errore durante la lettura'; }
			try { $res->free(); } catch ( mysqli_sql_exception $e ) { /* resto del risultato scartato */ }
			if ( $rotto !== '' ) { self::q( $c, 'ROLLBACK' ); return array( 'ok' => false, 'errore' => 'errore durante la lettura, nessun dato mostrato: ' . $rotto ); }
			$mascherate = array_merge( array_keys( $mascherate ), array_values( $segr ) );
		}
		self::q( $c, 'ROLLBACK' );
		return array( 'ok' => true, 'colonne' => $col, 'dati' => $dati, 'troncato' => $tronco, 'mascherate' => $mascherate, 'ms' => (int) round( ( microtime( true ) - $t0 ) * 1000 ) );
	}

	/** Prima colonna di una lettura di servizio, in minuscolo. */
	/** F030 (F46 a): righe trovate dall istruzione, non solo cambiate («Rows matched» di UPDATE): un valore indovinato giusto
	 *  o sbagliato dà lo stesso numero, quindi il conteggio della prova a secco non è un oracolo d uguaglianza. */
	private static function righe_trovate( mysqli $c ): int {
		$n = (int) $c->affected_rows;
		if ( is_string( $c->info ) && preg_match( '/Rows matched:\s*(\d+)/i', $c->info, $m ) ) $n = (int) $m[1];
		return $n;
	}

	/** F030: contatori AUTO_INCREMENT delle tabelle citate (database corrente), letti da SHOW CREATE TABLE (dato vivo anche su MySQL 8). */
	private static function contatori( mysqli $c, array $nomi ): array {
		$out = array();
		// F030 (7ª revisione, medio B32): il nome vero della tabella (con le sue maiuscole), non quello in minuscolo
		$tab = array(); $res = self::q( $c, 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = \'BASE TABLE\'' );
		if ( $res instanceof mysqli_result ) { while ( $x = $res->fetch_row() ) $tab[] = (string) $x[0]; $res->free(); }
		foreach ( $tab as $t ) {
			if ( ! in_array( strtolower( (string) $t ), array_map( 'strtolower', $nomi ), true ) ) continue;
			$r = $c->query( 'SHOW CREATE TABLE `' . str_replace( '`', '``', $t ) . '`' );
			if ( ! ( $r instanceof mysqli_result ) ) { $out[ $t ] = -1; continue; }
			$d = $r->fetch_row(); $r->free();
			if ( $d && preg_match( '/\bAUTO_INCREMENT\b/i', (string) $d[1] ) ) $out[ $t ] = preg_match( '/\)\s[^)]*\bAUTO_INCREMENT=(\d+)/i', (string) $d[1], $m ) ? (int) $m[1] : 1;
		}
		return $out;
	}

	/** F030: rimette i contatori dopo il ROLLBACK (InnoDB non scende sotto il massimo + 1: le righe altrui restano salve). */
	private static function rimetti_contatori( mysqli $c, array $ai ): bool {
		$ok = true;
		foreach ( $ai as $t => $n ) {
			if ( (int) $n < 0 ) { $ok = false; continue; }
			if ( ! self::q( $c, 'ALTER TABLE `' . str_replace( '`', '``', $t ) . '` AUTO_INCREMENT = ' . max( 1, (int) $n ) ) ) $ok = false;
		}
		return $ok;
	}

	private static function colonna( mysqli $c, string $sql ): array {
		$res = self::q( $c, $sql ); $out = array();
		if ( $res instanceof mysqli_result ) { while ( $r = $res->fetch_row() ) $out[] = strtolower( (string) $r[0] ); $res->free(); }
		return $out;
	}

	/** Tabelle (di ogni schema visibile) che hanno almeno una colonna segreta. */
	private static function tabelle_segrete( mysqli $c ): array {
		return array_keys( self::mappa_segrete( $c )[0] );
	}

	/** F029 (5ª revisione, MEDI 2 e 3): tabelle con una colonna chiave/valore. */
	private static function tabelle_kv( mysqli $c ): array {
		$res = self::q( $c, 'SELECT LOWER(TABLE_NAME), COLUMN_NAME FROM information_schema.COLUMNS' ); $out = array();
		if ( $res instanceof mysqli_result ) { while ( $r = $res->fetch_row() ) { if ( isset( self::KV[ strtolower( (string) $r[1] ) ] ) ) $out[ (string) $r[0] ] = true; } $res->free(); }
		return array_keys( $out );
	}

	/** Colonne segrete citate per nome (BUG-6 MEDIO 1): alias, espressioni e WHERE le farebbero uscire. */
	private static function segrete_citate( mysqli $c, array $nomi ): array {
		$m = self::mappa_segrete( $c )[1];
		return array_values( array_unique( array_filter( $nomi, static function ( $n ) use ( $m ) { return ! empty( $m[ strtolower( (string) $n ) ] ); } ) ) );
	}

	/**
	 * Controlli di una scrittura sul database (BUG-6 ALTI 2 e 3): un motivo di rifiuto, oppure ''.
	 * La prova a secco annulla solo ciò che è InnoDB, nel database corrente, senza effetti fuori dall istruzione.
	 */
	private static function controlla_scrittura( mysqli $c, array $cl ): string {
		$cur = self::colonna( $c, 'SELECT DATABASE()' )[0] ?? '';
		if ( $cur === '' ) return 'Nessun database corrente: scrittura rifiutata.';
		$dbs  = self::colonna( $c, 'SHOW DATABASES' );
		$altri = array_values( array_diff( array_intersect( $cl['qualificati'], array_merge( $dbs, self::SCHEMI_VIETATI ) ), array( $cur ) ) );
		if ( $altri ) return 'Riferimento a un altro database (' . implode( ', ', $altri ) . '): in scrittura solo tabelle del database della sorgente, ' . $cur . '.';
		$res = self::q( $c, 'SELECT LOWER(TABLE_NAME), TABLE_TYPE, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()' );
		$tab = array(); $vie = array();
		if ( $res instanceof mysqli_result ) {
			while ( $r = $res->fetch_row() ) {
				if ( ! in_array( (string) $r[0], $cl['nomi'], true ) ) continue;
				$tab[] = (string) $r[0];
				if ( $r[1] !== 'BASE TABLE' ) $vie[] = $r[0] . ' (' . ( $r[1] === 'VIEW' ? 'vista' : strtolower( (string) $r[1] ) ) . ')';
				elseif ( strcasecmp( (string) $r[2], 'InnoDB' ) !== 0 ) $vie[] = $r[0] . ' (' . $r[2] . ')';
			}
			$res->free();
		}
		if ( $vie ) return 'Viste, sequenze o tabelle non InnoDB (' . implode( ', ', $vie ) . '): la prova a secco non si può annullare, scrittura rifiutata.';
		$trg = array_values( array_intersect( $tab, self::colonna( $c, 'SELECT EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = DATABASE()' ) ) );
		if ( $trg ) return 'Tabelle con trigger (' . implode( ', ', $trg ) . '): scriverebbero altrove anche nella prova a secco, scrittura rifiutata.';
		$seq = array_values( array_intersect( $tab, self::colonna( $c, "SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND ( COLUMN_DEFAULT LIKE '%nextval%' OR COLUMN_DEFAULT LIKE '%next value%' OR GENERATION_EXPRESSION LIKE '%nextval%' )" ) ) );
		if ( $seq ) return 'Tabelle con valori da una sequenza (' . implode( ', ', $seq ) . '): la sequenza avanza anche nella prova a secco, scrittura rifiutata.';
		$fun = array_values( array_intersect( $cl['chiamate'], self::colonna( $c, 'SELECT ROUTINE_NAME FROM information_schema.ROUTINES' ) ) );
		if ( $fun ) return 'Funzioni memorizzate (' . implode( ', ', $fun ) . '): possono scrivere fuori dalla prova a secco, scrittura rifiutata.';
		$wp = self::parametri( 'wordpress' );
		if ( $wp && strtolower( (string) $wp['name'] ) === $cur ) {
			$fuori = array_values( array_filter( $tab, static function ( $t ) { return strpos( $t, strtolower( RDACADEMY_TABLE_PREFIX ) ) !== 0; } ) );
			if ( $fuori ) return 'La sorgente è il database di WordPress: in scrittura solo tabelle di RelDoxAcademy (' . RDACADEMY_TABLE_PREFIX . '…), non ' . implode( ', ', $fuori ) . '.';
		}
		return '';
	}

	/** F028: il valore di una tabella chiave/valore non si usa in condizioni (sarebbe un oracolo): '' oppure il motivo. */
	private static function kv_in_condizione( string $sk, array $nomi ): string {
		$kv = array_values( array_intersect( array_keys( self::KV ), $nomi ) );
		if ( ! $kv ) return '';
		if ( ! preg_match( '/\b(WHERE|HAVING|ORDER\s+BY|GROUP\s+BY)\b|\bON\s+(?!DUPLICATE\b)/i', $sk, $m, PREG_OFFSET_CAPTURE ) ) return '';
		$dopo = substr( $sk, (int) $m[0][1] );
		foreach ( $kv as $v ) {
			if ( preg_match( '/\b' . preg_quote( $v, '/' ) . '\b/i', $dopo ) ) return 'Il valore delle tabelle chiave/valore («' . $v . '») non si usa in condizioni, ordinamenti né unioni: filtra per «' . self::KV[ $v ] . '».';
		}
		return '';
	}

	/** Elementi dello scheletro: nomi, numeri, simboli. */
	private static function gettoni_sql( string $sk ): array {
		preg_match_all( '/[A-Za-z0-9_$\x80-\xff]+|<=>|[<>!:]=|\|\||&&|\S/', $sk, $m );
		return $m[0];
	}

	const OPERATORI = array( 'and', 'or', 'xor', 'not', 'like', 'rlike', 'regexp', 'is', 'in', 'between', 'div', 'mod', 'collate', 'sounds', 'escape', 'member', 'over' );

	/**
	 * F029 (4ª revisione, MEDIO 2 e MEDIO 1): il valore delle tabelle chiave/valore non entra in espressioni.
	 * In lettura solo come voce semplice dell elenco (SELECT valore [AS x], …); in scrittura solo come destinazione di «=» o
	 * nell elenco delle colonne di INSERT. La chiave non si cambia (rinominarla smaschererebbe il valore). '' oppure il motivo.
	 */
	private static function kv_nudo( string $sk, array $nomi, string $tipo, string $verbo ): string {
		$kv = array_values( array_intersect( array_keys( self::KV ), $nomi ) );
		if ( $tipo === 'scrittura' ) {
			// F029 (5ª revisione, ALTO 1): «=» e «:=», con qualunque qualificatore; INSERT … SET è una riga nuova (si guarda solo ON DUPLICATE KEY UPDATE)
			$zona = $sk;
			if ( $verbo !== 'UPDATE' ) { $pd = preg_match( '/\bON\s+DUPLICATE\s+KEY\s+UPDATE\b/i', $sk, $md, PREG_OFFSET_CAPTURE ) ? (int) $md[0][1] : -1; $zona = $pd < 0 ? '' : substr( $sk, $pd ); }
			$tz = self::gettoni_sql( $zona );
			foreach ( $tz as $i => $g ) {
				if ( ! in_array( strtolower( $g ), array_values( self::KV ), true ) || ! in_array( $tz[ $i + 1 ] ?? '', array( '=', ':=' ), true ) ) continue;
				$p = $i - 1; while ( $p >= 1 && $tz[ $p ] === '.' ) $p -= 2;
				if ( in_array( strtolower( $tz[ $p ] ?? '' ), array( 'set', ',', 'update' ), true ) ) return 'La chiave delle tabelle chiave/valore («' . strtolower( $g ) . '») non si cambia: rinominarla smaschererebbe il valore.';
			}
		}
		if ( ! $kv ) return '';
		if ( $tipo === 'scrittura' && in_array( $verbo, array( 'INSERT', 'REPLACE' ), true ) ) {
			$sk = (string) preg_replace( '/^(\s*(?:INSERT|REPLACE)\s+(?:(?:LOW_PRIORITY|DELAYED|HIGH_PRIORITY|IGNORE)\s+)*(?:INTO\s+)?[A-Za-z0-9_$.\x80-\xff]+\s*)\([^()]*\)/i', '$1( )', $sk );
		}
		$t = self::gettoni_sql( $sk ); $prof = 0;
		foreach ( $t as $i => $g ) {
			if ( $g === '(' ) $prof++; elseif ( $g === ')' ) $prof--;
			if ( ! in_array( strtolower( $g ), $kv, true ) ) continue;
			// F031 (8ª revisione, MEDI 1 e 3): dentro una parentesi il valore è l argomento di una funzione, mai una voce né un assegnazione
			if ( $prof > 0 ) return $tipo === 'scrittura' ? 'Il valore delle tabelle chiave/valore («' . strtolower( $g ) . '») in scrittura si può solo assegnare (' . strtolower( $g ) . ' = …), non usare in espressioni.' : 'Il valore delle tabelle chiave/valore («' . strtolower( $g ) . '») si legge solo così com è (SELECT ' . strtolower( $g ) . ' …), non in espressioni, funzioni o condizioni.';
			$p = $i - 1;
			while ( $p >= 1 && $t[ $p ] === '.' ) $p -= 2;
			$prima = strtolower( $t[ $p ] ?? '' ); $dopo = strtolower( $t[ $i + 1 ] ?? '' );
			if ( $tipo === 'scrittura' ) {
				if ( in_array( $dopo, array( '=', ':=' ), true ) && in_array( $prima, array( 'set', ',', 'update' ), true ) ) continue;
				return 'Il valore delle tabelle chiave/valore («' . strtolower( $g ) . '») in scrittura si può solo assegnare (' . strtolower( $g ) . ' = …), non usare in espressioni.';
			}
			$ok_prima = in_array( $prima, array( 'select', ',', 'distinct', 'all' ), true );
			$ok_dopo  = in_array( $dopo, array( ',', 'from', 'as', '' ), true )
				|| ( preg_match( '/^[a-z_\x80-\xff][a-z0-9_$\x80-\xff]*$/', $dopo ) && ! in_array( $dopo, self::OPERATORI, true ) && in_array( strtolower( $t[ $i + 2 ] ?? '' ), array( ',', 'from', '' ), true ) );
			if ( ! $ok_prima || ! $ok_dopo ) return 'Il valore delle tabelle chiave/valore («' . strtolower( $g ) . '») si legge solo così com è (SELECT ' . strtolower( $g ) . ' …), non in espressioni, funzioni o condizioni.';
		}
		return '';
	}

	/**
	 * F033 (R11-6): una vista che legge tabelle di un altro database (information_schema compreso) farebbe uscire i suoi dati col referto
	 * del database della sorgente. Il server scrive la definizione con i nomi fra apici inversi e le tabelle sempre con il loro database.
	 */
	private static function vista_fuori_db( string $def, string $suo, array $tutti_db ): bool {
		$id = '`((?:[^`]|``)*)`';
		// 1) dopo FROM e JOIN: `database`.`tabella`
		if ( preg_match_all( '/\b(?:from|join)\s+[(\s]*' . $id . '\s*\.\s*`/i', $def, $m ) ) { foreach ( $m[1] as $d ) { if ( strtolower( $d ) !== $suo ) return true; } }
		// 2) nomi a tre parti: `database`.`tabella`.`colonna`
		if ( preg_match_all( '/' . $id . '\s*\.\s*' . $id . '\s*\.\s*`/', $def, $m ) ) { foreach ( $m[1] as $d ) { if ( strtolower( $d ) !== $suo ) return true; } }
		// 3) qualunque nome qualificato che comincia con un altro database visibile
		if ( preg_match_all( '/' . $id . '\s*\.\s*`/', $def, $m ) ) { foreach ( $m[1] as $d ) { if ( strtolower( $d ) !== $suo && in_array( strtolower( $d ), $tutti_db, true ) ) return true; } }
		return false;
	}

	/** F029 (F45 b): ORDER BY / GROUP BY per posizione (ordinano per una colonna segreta senza nominarla). */
	private static function per_posizione( string $sk ): bool {
		// F029 (5ª revisione, MEDIO 3): un intero come voce di ORDER/GROUP BY, qualunque cosa venga dopo (OFFSET, ORDER BY NULL…)
		// F031 (8ª revisione, MEDIO 2): si toglie solo il LIMIT vero in coda (numeri), non un alias «limit»; dopo ORDER/GROUP BY nessun numero,
		// nemmeno fra parentesi o col segno
		$sk = preg_replace( '/\bLIMIT\s+\d+(\s*(,|\bOFFSET\b)\s*\d+)?\s*$/i', '', rtrim( $sk, " \t\r\n;" ) );
		if ( $sk === null ) return true; // F032 (9ª revisione, MEDIO 2): se il controllo non si può fare, si rifiuta
		if ( ! preg_match( '/\b(ORDER|GROUP)\s+BY\b(.*)$/is', $sk, $m ) ) return false;
		return (bool) preg_match( '/(?<![A-Za-z0-9_$.\x80-\xff])\d+(?![A-Za-z0-9_$\x80-\xff])/', $m[2] );
	}

	/* ── F033 (R11-4): l ordine delle righe non segue un indice su una colonna segreta ───────────── */

	const SUGGERIMENTI = '/\b(USE|FORCE|IGNORE)\s+(INDEX|KEY)\b/i';

	/** Parole che dopo il nome di una tabella non sono il suo alias. */
	const DOPO_TABELLA = array( 'where', 'group', 'having', 'order', 'limit', 'join', 'inner', 'cross', 'left', 'right', 'natural', 'straight_join', 'on', 'using', 'use', 'force', 'ignore', 'for', 'procedure', 'union', 'intersect', 'except', 'window', 'offset', 'fetch', 'lock', 'into', 'partition', 'set', 'as' );

	/**
	 * Indici del database corrente che contengono una colonna segreta o il valore chiave/valore: il server, leggendo lungo uno di essi,
	 * darebbe le righe nell ordine dei segreti (e LIMIT, GROUP_CONCAT, il tetto delle righe sceglierebbero secondo quell ordine).
	 * @return array{0:?array<string,array<int,string>>,1:array<string,bool>} [tabella => indici da escludere, tabelle la cui chiave primaria è segreta]; [null, …] se non si leggono
	 */
	private static function indici_segreti( mysqli $c ): array {
		$res = self::q( $c, 'SELECT LOWER(s.TABLE_NAME), s.INDEX_NAME, s.COLUMN_NAME, s.NON_UNIQUE, c.DATA_TYPE FROM information_schema.STATISTICS s LEFT JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA = s.TABLE_SCHEMA AND c.TABLE_NAME = s.TABLE_NAME AND c.COLUMN_NAME = s.COLUMN_NAME WHERE s.TABLE_SCHEMA = DATABASE()' );
		if ( ! ( $res instanceof mysqli_result ) ) return array( null, array() );
		$ign = array(); $pk = array(); $unici = array(); $chv = array();
		while ( $x = $res->fetch_row() ) {
			$t = (string) $x[0];
			// senza nome di colonna (indice su un espressione, MySQL 8) non si sa che cosa ordina: si esclude
			$seg = $x[2] === null || self::segreta( (string) $x[2], $x[4] === null ? null : (string) $x[4] ) || isset( self::KV[ strtolower( (string) $x[2] ) ] );
			if ( strtoupper( (string) $x[1] ) === 'PRIMARY' ) { $pk[ $t ] = true; if ( $seg ) $chv[ $t ] = true; continue; }
			if ( $seg ) { $ign[ $t ][ (string) $x[1] ] = true; if ( (int) $x[3] === 0 ) $unici[ $t ] = true; }
		}
		$res->free();
		// senza chiave primaria InnoDB ordina le righe lungo il primo indice unico: se è segreto, è come una chiave primaria segreta
		foreach ( $unici as $t => $v ) { if ( empty( $pk[ $t ] ) ) $chv[ $t ] = true; }
		foreach ( $ign as $t => $ii ) $ign[ $t ] = array_map( 'strval', array_keys( $ii ) );
		return array( $ign, $chv );
	}

	/**
	 * Riscrive la SELECT aggiungendo «IGNORE INDEX (…)» dopo ogni tabella (e il suo alias) che ha indici da escludere.
	 * Se una forma del FROM sfugge, non importa: dopo si controlla il piano del server (piano_pulito) e si rifiuta.
	 * @return array{0:string,1:array<int,string>} [istruzione da eseguire, «indice su tabella» esclusi]
	 */
	private static function senza_indici_segreti( string $sql, array $ign ): array {
		list( $sk, , $err, $fine ) = self::scheletro( $sql );
		if ( $err !== '' || ! preg_match_all( '/[A-Za-z0-9_]+|\S/', $sk, $m, PREG_OFFSET_CAPTURE ) ) return array( $sql, array() );
		$t = $m[0]; $n = count( $t ); $ins = array(); $fatti = array();
		$parola = static function ( int $i ) use ( $t ): string { return isset( $t[ $i ] ) ? strtolower( $t[ $i ][0] ) : ''; };
		$dopo   = static function ( int $i ) use ( $t, $fine ): int { return (int) ( $fine[ $t[ $i ][1] + strlen( $t[ $i ][0] ) - 1 ] ?? 0 ); };
		$apici  = static function ( int $i ) use ( $dopo, $sql ): bool { $d = $dopo( $i ); return $d > 0 && $sql[ $d - 1 ] === '`'; };
		for ( $i = 0; $i < $n; $i++ ) {
			$nome = $parola( $i );
			if ( ! isset( $ign[ $nome ] ) || $parola( $i + 1 ) === '.' ) continue;                 // «tabella.colonna» non è la tabella nel FROM
			$p = $parola( $i - 1 ) === '.' ? $i - 3 : $i - 1;                                       // «database.tabella»
			if ( ! in_array( $parola( $p ), array( 'from', 'join', 'straight_join', ',', '(' ), true ) ) continue;
			$j = $i + 1;
			if ( $parola( $j ) === 'partition' && $parola( $j + 1 ) === '(' ) { while ( $j < $n && $t[ $j ][0] !== ')' ) $j++; $j++; }
			if ( $parola( $j ) === 'as' && ! $apici( $j ) ) $j++;
			if ( $j < $n && preg_match( '/^[A-Za-z0-9_]+$/', $t[ $j ][0] ) && ( $apici( $j ) || ! in_array( $parola( $j ), self::DOPO_TABELLA, true ) ) ) $j++;   // l alias
			$ins[ $dopo( $j - 1 ) ] = ' IGNORE INDEX (' . implode( ', ', array_map( static function ( $x ) { return '`' . str_replace( '`', '``', $x ) . '`'; }, $ign[ $nome ] ) ) . ') ';
			foreach ( $ign[ $nome ] as $ix ) $fatti[ $ix . ' su ' . $nome ] = true;
		}
		krsort( $ins );
		foreach ( $ins as $pos => $txt ) $sql = substr( $sql, 0, $pos ) . $txt . substr( $sql, $pos );
		return array( $sql, array_keys( $fatti ) );
	}

	/**
	 * Il piano del server (EXPLAIN, che non esegue la SELECT): true se non usa nessuno degli indici da escludere, false se ne usa uno,
	 * null se il piano non si può leggere (in $errore il perché): allora non si esegue.
	 */
	private static function piano_pulito( mysqli $c, string $sql, array $ign, string &$errore = '' ): ?bool {
		self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_MAX ) || self::q( $c, 'SET SESSION MAX_EXECUTION_TIME = ' . ( self::TEMPO_MAX * 1000 ) );
		$res = self::q( $c, 'EXPLAIN ' . $sql );
		if ( ! ( $res instanceof mysqli_result ) ) { $errore = self::err( $c ); return null; }
		$vietati = array();
		foreach ( $ign as $ii ) { foreach ( $ii as $ix ) $vietati[ strtolower( (string) $ix ) ] = true; }
		$ok = true; $letto = false;
		while ( $x = $res->fetch_assoc() ) {
			$x = array_change_key_case( $x, CASE_LOWER );
			if ( ! array_key_exists( 'key', $x ) ) { $letto = false; break; }   // piano in un altra forma (albero, JSON): non si sa leggere
			$letto = true;
			foreach ( explode( ',', strtolower( (string) $x['key'] ) ) as $k ) { if ( isset( $vietati[ trim( $k ) ] ) ) $ok = false; }
		}
		$res->free();
		if ( ! $letto ) { $errore = 'piano del server non leggibile'; return null; }
		return $ok;
	}

	/* ── F033 (R11-7): una scrittura risponde entro un tempo dichiarato ───────────────────────── */

	/** Secondi entro cui la console risponde a una scrittura, annullamento compreso: TEMPO_MAX (il banco può solo accorciarli). */
	private static function risposta_entro(): float {
		$t = (float) self::TEMPO_MAX;
		return ( self::$risposta_entro !== null && self::$risposta_entro > 0 && self::$risposta_entro < $t ) ? (float) self::$risposta_entro : $t;
	}

	private static function secondi( float $s ): string { return rtrim( rtrim( number_format( $s, 1, ',', '' ), '0' ), ',' ); }

	/**
	 * Esegue l istruzione in scrittura aspettando la risposta al massimo fino a $entro. Il server ferma l istruzione a TEMPO_SCRITTURA
	 * secondi, ma prima di rispondere annulla ciò che aveva già scritto, e questo non ha tetto: se la risposta non arriva in tempo la
	 * console smette di aspettare. Chi chiama allora lascia la connessione senza COMMIT: il server annulla la transazione.
	 * @return array{0:bool,1:bool} [riuscita, scaduta]
	 */
	private static function scrivi_su( mysqli $c, string $sql, float $entro ): array {
		if ( ! defined( 'MYSQLI_ASYNC' ) || ! function_exists( 'mysqli_poll' ) || ! method_exists( $c, 'reap_async_query' ) ) return array( (bool) self::q( $c, $sql ), false );
		try { $inviata = $c->query( $sql, MYSQLI_ASYNC ); } catch ( mysqli_sql_exception $e ) { $inviata = false; }
		if ( $inviata === false ) return array( false, false );
		while ( true ) {
			$resto = $entro - microtime( true );
			if ( $resto <= 0 ) return array( false, true );
			$le = array( $c ); $er = array( $c ); $ri = array( $c );
			$pronte = @mysqli_poll( $le, $er, $ri, (int) $resto, (int) ( ( $resto - (int) $resto ) * 1000000 ) );
			if ( $pronte === false ) return array( false, true );
			if ( $pronte > 0 ) break;
		}
		try { $res = $c->reap_async_query(); } catch ( mysqli_sql_exception $e ) { $res = false; }
		if ( $res instanceof mysqli_result ) $res->free();
		return array( $res !== false, false );
	}

	private static function lascia( mysqli $c ): void { try { @$c->close(); } catch ( Throwable $e ) { /* connessione già persa */ } }

	private static function tabella_registro(): string { return RDACADEMY_TABLE_PREFIX . self::REGISTRO; }
	private static function tabella_gettoni(): string { return RDACADEMY_TABLE_PREFIX . self::GETTONI; }

	/** Append-only anche nel database (F42 c): trigger che rifiutano UPDATE e DELETE. Senza permesso TRIGGER resta la difesa per nome. */
	public static ?bool $blindato = null;

	private static function blinda( mysqli $c, string $t ): void {
		$nomi = array();
		foreach ( array( 'UPDATE' => 'nu', 'DELETE' => 'nd' ) as $ev => $suf ) {
			$nomi[] = strtolower( "{$t}_{$suf}" );
			if ( in_array( strtolower( "{$t}_{$suf}" ), self::colonna( $c, "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '" . $c->real_escape_string( $t ) . "'" ), true ) ) continue;
			self::q( $c, "CREATE TRIGGER `{$t}_{$suf}` BEFORE $ev ON `$t` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only'" );
		}
		// F028: sull hosting il permesso TRIGGER può mancare (MySQL 8 col registro binario): si verifica e si dichiara
		$ci = self::colonna( $c, "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = '" . $c->real_escape_string( $t ) . "'" );
		if ( $t === self::tabella_registro() ) self::$blindato = ! array_diff( $nomi, $ci );
	}

	/** Consuma il gettone una volta sola (F42 a): chiave unica nel BASE, atomica fra due richieste insieme. */
	private static function consuma_gettone( string $gid ): bool {
		$p = self::parametri( 'base' );
		$c = $p ? self::apri( $p ) : null;
		if ( ! $c || $gid === '' ) return false;
		$t = self::tabella_gettoni();
		$ok = self::q( $c, "CREATE TABLE IF NOT EXISTS `$t` (gid CHAR(32) NOT NULL, quando DATETIME NOT NULL, PRIMARY KEY (gid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
		if ( $ok ) { self::blinda( $c, $t ); $ok = self::q( $c, "INSERT INTO `$t` (gid, quando) VALUES ('" . $c->real_escape_string( $gid ) . "', '" . gmdate( 'Y-m-d H:i:s', self::ora() ) . "')" ); }
		$c->close();
		return (bool) $ok;
	}

	/** Registro append-only sul BASE. false se non si può scrivere (allora non si scrive nemmeno sui dati). */
	/** F030 (7ª revisione, medio A46): nel registro i valori fra apici diventano «…» (lo legge ogni amministratore dalla console). */
	private static function senza_valori( string $sql ): string {
		if ( strlen( $sql ) > self::MAX_ISTRUZIONE ) return '(istruzione di ' . strlen( $sql ) . ' byte, troppo lunga: non trascritta)';
		// F031 (8ª revisione, MEDIO 4): dallo scheletro (stringhe e commenti già tolti come li legge il server); anche numeri, 0x… e 0b…
		list( $sk, $ids, $err ) = self::scheletro( $sql );
		if ( $err !== '' ) return '(istruzione non leggibile: ' . $err . ')';
		// F033 (R11-5): un numero dopo «$» o dopo un byte non ASCII («$4829», «€4829») è un numero anche lui
		$sk = (string) preg_replace( '/(?<![A-Za-z0-9_])(?:0x[0-9a-f]+|0b[01]+|\d+(?:\.\d*)?(?:e[+-]?\d+)?|\.\d+)/i', '… ', str_replace( " '' ", " '…' ", $sk ) );
		$sk = (string) preg_replace_callback( '/__id(…|\d+)__/', static function ( $m ) { return '`…`'; }, $sk );
		return self::senza_cifre( trim( (string) preg_replace( '/\s+/', ' ', $sk ) ) );
	}

	/** F034 (R12-7): ogni parola che contiene una cifra (anche non ASCII) si toglie intera. */
	private static function senza_cifre( string $t ): string {
		$u = preg_replace( '/[\p{L}\p{N}\p{M}_$€£.\-]*\p{N}[\p{L}\p{N}\p{M}_$€£.\-]*/u', '…', $t );
		return $u === null ? (string) preg_replace( '/[^\s]*[0-9\x80-\xff][^\s]*/', '…', $t ) : $u;
	}

	/** F032 (9ª revisione, MEDIO 3): un campo del registro è sempre UTF-8 valido e della misura della colonna, altrimenti la riga non si scrive. */
	private static function pulito( string $t, int $max ): string {
		if ( ! preg_match( '//u', $t ) ) $t = (string) preg_replace( '/[^\x20-\x7e\t\r\n]/', '?', $t );
		$t = (string) preg_replace( '/[\x{10000}-\x{10FFFF}]/u', '?', $t );
		return function_exists( 'mb_substr' ) ? mb_substr( $t, 0, $max, 'UTF-8' ) : (string) preg_replace( '/^(.{0,' . $max . '}).*$/su', '$1', $t );
	}

	private static function registra( array $r, array $s, string $tipo, ?int $righe, string $esito, string $msg ): bool {
		$p = self::parametri( 'base' );
		$c = $p ? self::apri( $p ) : null;
		if ( ! $c ) return false;
		$t = self::tabella_registro();
		$ok = self::q( $c, "CREATE TABLE IF NOT EXISTS `$t` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, quando DATETIME NOT NULL, utente BIGINT UNSIGNED NOT NULL DEFAULT 0, login VARCHAR(60) NOT NULL DEFAULT '', sorgente VARCHAR(40) NOT NULL DEFAULT '', db_nome VARCHAR(64) NOT NULL DEFAULT '', db_host VARCHAR(255) NOT NULL DEFAULT '', istruzione MEDIUMTEXT NOT NULL, tipo VARCHAR(12) NOT NULL, righe INT NULL, esito VARCHAR(20) NOT NULL, messaggio VARCHAR(500) NOT NULL DEFAULT '', PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4" );
		if ( $ok ) {
			self::blinda( $c, $t );
			$st = false; $ok = false;
			try { $st = $c->prepare( "INSERT INTO `$t` (quando, utente, login, sorgente, db_nome, db_host, istruzione, tipo, righe, esito, messaggio) VALUES (?,?,?,?,?,?,?,?,?,?,?)" ); } catch ( mysqli_sql_exception $e ) { $st = false; }
			if ( $st ) {
				$q = gmdate( 'Y-m-d H:i:s', self::ora() ); $u = (int) ( $r['utente'] ?? 0 ); $l = self::pulito( (string) ( $r['login'] ?? '' ), 60 );
				// F034 (R12-7): una sorgente che la console non conosce non si scrive com è arrivata
				$so = (string) ( $r['sorgente'] ?? '' ); $so = $so === 'base' || $so === 'wordpress' || ( class_exists( 'Rdacademy_Sorgenti', false ) && isset( Rdacademy_Sorgenti::mappa()[ $so ] ) ) ? self::pulito( $so, 40 ) : 'sconosciuta'; $db = self::pulito( (string) ( $s['db'] ?? '' ), 64 ); $h = self::pulito( (string) ( $s['host'] ?? '' ), 255 ); $i = self::pulito( self::senza_valori( (string) ( $r['sql'] ?? '' ) ), self::MAX_ISTRUZIONE );
				$m = self::pulito( self::senza_cifre( $msg ), 500 ); $tipo = self::pulito( $tipo, 12 ); $esito = self::pulito( $esito, 20 );
				$st->bind_param( 'sissssssiss', $q, $u, $l, $so, $db, $h, $i, $tipo, $righe, $esito, $m );
				try { $ok = $st->execute(); } catch ( mysqli_sql_exception $e ) { $ok = false; }
				$st->close();
			}
		}
		$c->close();
		return (bool) $ok;
	}

	private static function chiave_gettone( int $u ): string { return 'rdacademy_console_sql_' . $u; }

	private static function gettone_leggi( int $u ): ?array {
		$g = function_exists( 'get_transient' ) ? get_transient( self::chiave_gettone( $u ) ) : ( self::$gettoni[ $u ] ?? false );
		return is_array( $g ) ? $g : null;
	}

	private static function gettone_scrivi( int $u, ?array $g ): void {
		if ( function_exists( 'set_transient' ) ) {
			$g === null ? delete_transient( self::chiave_gettone( $u ) ) : set_transient( self::chiave_gettone( $u ), $g, self::CONFERMO_TTL );
			return;
		}
		if ( $g === null ) unset( self::$gettoni[ $u ] ); else self::$gettoni[ $u ] = $g;
	}

	private static function impronta( array $r, array $s ): string {
		return hash( 'sha256', implode( "\x1f", array( (string) $r['sorgente'], $s['db'], $s['host'], (int) $r['utente'], trim( (string) $r['sql'] ) ) ) );
	}

	/**
	 * La porta unica. $r: sorgente, sql, consenti (bool), azione ('esegui'|'prova'), conferma, utente (id), login.
	 * @return array{referto:array,colonne:array,dati:array,verdi:int,rossi:int,righe_prova?:int,scade?:int}
	 */
	public static function esegui( array $r ): array {
		$r += array( 'sorgente' => 'base', 'sql' => '', 'consenti' => false, 'azione' => 'esegui', 'conferma' => '', 'utente' => 0, 'login' => '' );
		$r['sql'] = (string) $r['sql'];
		$out = array( 'referto' => array(), 'colonne' => array(), 'dati' => array(), 'verdi' => 0, 'rossi' => 0 );
		$riga = static function ( string $l, string $t ) use ( &$out ) {
			$out['referto'][] = array( $l, $t );
			if ( $l === 'VERDE' ) $out['verdi']++;
			if ( $l === 'ROSSO' ) $out['rossi']++;
		};
		$S = self::sorgenti();
		$k = (string) $r['sorgente'];
		if ( ! isset( $S[ $k ] ) ) {
			$riga( 'ROSSO', 'Sorgente «' . self::pulito( $k, 40 ) . '» sconosciuta: rifiutata.' );
			if ( ! self::registra( $r, array(), 'rifiuto', null, 'rifiutata', 'sorgente sconosciuta' ) ) $riga( 'INFO', 'Attenzione: questo rifiuto non è stato scritto nel registro della console (registro non raggiungibile).' );
			return $out;
		}
		$s  = $S[ $k ];
		$at = $s['db'] . ' @ ' . $s['host'];
		$rifiuta = static function ( string $m ) use ( $riga, $r, $s, $at ) {
			$riga( 'ROSSO', $m . ( $s['db'] !== '' ? ' (' . $at . ')' : '' ) );
			if ( ! self::registra( $r, $s, 'rifiuto', null, 'rifiutata', $m ) ) $riga( 'INFO', 'Attenzione: questo rifiuto non è stato scritto nel registro della console (registro non raggiungibile).' );
		};
		if ( ! $s['in_uso'] ) {
			$rifiuta( $s['etichetta'] . ( $s['stato'] === 'spenta' ? ' spenta' : ' non configurata' ) . ': la console la rifiuta (non ricade sul BASE in silenzio).' );
			return $out;
		}
		$cl = self::classifica( $r['sql'] );
		if ( ! $cl['ok'] ) { $rifiuta( $cl['motivo'] ); return $out; }
		// D48 (owner 26/11/2026 15:17, chat Formazione F034): nella 0.1.20A nessuna scrittura, nemmeno la prova a secco
		if ( self::SOLA_LETTURA && $cl['tipo'] !== 'lettura' ) { $rifiuta( 'La console è in sola lettura in questa versione: «' . $cl['verbo'] . '» rifiutata (niente prova a secco né CONFERMO). Le scritture tornano in una versione successiva.' ); return $out; }

		// ── lettura ─────────────────────────────────────────────
		if ( $cl['tipo'] === 'lettura' ) {
			$c = self::apri( (array) self::parametri( $k ) );
			if ( ! $c ) { $riga( 'ROSSO', 'Connessione non riuscita a ' . $at . '.' ); return $out; }
			$sc = self::segrete_citate( $c, $cl['nomi'] );
			if ( $sc ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): non si leggono né si usano in espressioni o condizioni; con SELECT * escono mascherate.' ); return $out; }
			// 2ª revisione F027, ALTO 1: con UNION, WITH o sottoquery il nome della colonna nel risultato non è più il suo
			// F031 (8ª revisione, ALTO 5): una funzione o una vista già nel database può leggere una colonna segreta e darle un altro nome
			// F034 (R12-4, R12-6): tabelle che leggono altrove (MERGE e simili) e tabelle partizionate non si leggono
			if ( $cl['verbo'] !== 'SHOW' && $cl['verbo'] !== 'DESCRIBE' && $cl['verbo'] !== 'DESC' ) {
				$fuori = self::colonna( $c, "SELECT LOWER(TABLE_NAME) FROM information_schema.TABLES WHERE UPPER(ENGINE) IN ('MRG_MYISAM','MERGE','MRG_MYISAM','FEDERATED','FEDERATEDX','CONNECT','SPIDER')" );
				$part  = self::colonna( $c, 'SELECT DISTINCT LOWER(TABLE_NAME) FROM information_schema.PARTITIONS WHERE PARTITION_NAME IS NOT NULL' );
				$tm = array_values( array_intersect( $cl['nomi'], $fuori ) ); $tp = array_values( array_intersect( $cl['nomi'], $part ) );
				if ( $tm ) { $c->close(); $rifiuta( 'Tabelle che leggono i dati da un altra parte (MERGE, FEDERATED, CONNECT, SPIDER: ' . implode( ', ', $tm ) . '): la console non le legge, i dati uscirebbero col nome di questo database.' ); return $out; }
				if ( $tp ) { $c->close(); $rifiuta( 'Tabelle partizionate (' . implode( ', ', $tp ) . '): la console non le legge (la partizione direbbe in che intervallo sta un valore); SHOW e DESCRIBE restano.' ); return $out; }
			}
			$fun = array_values( array_intersect( $cl['chiamate'], self::colonna( $c, 'SELECT ROUTINE_NAME FROM information_schema.ROUTINES' ) ) );
			if ( $fun ) { $c->close(); $rifiuta( 'Funzioni memorizzate (' . implode( ', ', $fun ) . '): la console non le chiama, potrebbero leggere colonne segrete o scrivere.' ); return $out; }
			// F032 (9ª revisione, MEDIO 4): una lettura da un altro database uscirebbe col referto del database della sorgente
			$cur   = self::colonna( $c, 'SELECT DATABASE()' )[0] ?? '';
			// con SHOW il database si nomina senza punto (SHOW TABLES FROM altro, SHOW COLUMNS FROM t IN altro)
			$altri = array_values( array_diff( array_intersect( $cl['verbo'] === 'SHOW' ? $cl['nomi'] : $cl['qualificati'], array_merge( self::colonna( $c, 'SHOW DATABASES' ), self::SCHEMI_VIETATI ) ), array( $cur, 'information_schema' ) ) );
			if ( $altri ) { $c->close(); $rifiuta( 'Riferimento a un altro database (' . implode( ', ', $altri ) . '): la console legge solo il database della sorgente scelta, ' . $cur . ' (e information_schema); per un altro database si sceglie la sua sorgente.' ); return $out; }
			// F032 (9ª revisione, ALTO 6): una colonna generata da una segreta la ripete con un altro nome; una vista sopra un altra vista o
			// sopra una funzione nasconde la colonna segreta dietro due nomi
			// D47: le parole di una definizione sono segrete se sono colonne segrete di qualche tabella (le parole chiave e i nomi di tabella no)
			$msc = self::mappa_segrete( $c )[1];
			$gen = array(); $rg = self::q( $c, "SELECT LOWER(TABLE_NAME), GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE GENERATION_EXPRESSION IS NOT NULL AND GENERATION_EXPRESSION <> ''" );
			if ( $rg instanceof mysqli_result ) {
				while ( $x = $rg->fetch_row() ) {
					preg_match_all( '/[A-Za-z_][A-Za-z0-9_$]*/', (string) $x[1], $pg );
					if ( preg_match( '/[\x80-\xff]/', (string) $x[1] ) || array_filter( $pg[0], static function ( $w ) use ( $msc ) { return ! empty( $msc[ strtolower( $w ) ] ) || isset( self::KV[ strtolower( $w ) ] ); } ) ) $gen[ (string) $x[0] ] = true;
				}
				$rg->free();
			}
			$tg = array_values( array_intersect( $cl['nomi'], array_keys( $gen ) ) );
			if ( $tg ) { $c->close(); $rifiuta( 'Tabelle con una colonna generata da una colonna segreta (' . implode( ', ', $tg ) . '): la console non le legge, la colonna generata ripeterebbe il segreto con un altro nome.' ); return $out; }
			$rout = self::colonna( $c, 'SELECT ROUTINE_NAME FROM information_schema.ROUTINES' );
			$vs = array(); $viste = array(); $rv = self::q( $c, 'SELECT LOWER(TABLE_NAME), VIEW_DEFINITION, LOWER(TABLE_SCHEMA) FROM information_schema.VIEWS' );
			if ( $rv instanceof mysqli_result ) { while ( $x = $rv->fetch_row() ) $viste[] = array( (string) $x[0], (string) $x[1], (string) $x[2] ); $rv->free(); }
			// F033 (R11-6): i database che il server mostra, per riconoscere una vista che legge fuori dal suo
			$tutti_db = array_merge( self::colonna( $c, 'SHOW DATABASES' ), self::SCHEMI_VIETATI, array( 'information_schema' ) );
			$nv = array_column( $viste, 0 );
			foreach ( $viste as $x ) {
				if ( ! in_array( $x[0], $cl['nomi'], true ) ) continue;
				preg_match_all( '/[A-Za-z_][A-Za-z0-9_$]*/', $x[1], $pv );
				$brutte = array_filter( $pv[0], static function ( $w ) use ( $msc ) { return ! empty( $msc[ strtolower( $w ) ] ) || isset( self::KV[ strtolower( $w ) ] ); } );
				$pvl    = array_map( 'strtolower', $pv[0] );
				if ( trim( $x[1] ) === '' || $brutte || preg_match( '/[\x80-\xff]/', $x[1] ) || array_intersect( $pvl, array_diff( $nv, array( $x[0] ) ) ) || array_intersect( $pvl, $rout ) || array_intersect( $pvl, array_keys( $gen ) ) || self::vista_fuori_db( $x[1], $x[2], $tutti_db ) ) $vs[] = $x[0];
			}
			if ( $vs ) { $c->close(); $rifiuta( 'Viste che leggono colonne segrete o chiave/valore, altre viste, funzioni memorizzate, colonne generate o tabelle di un altro database (' . implode( ', ', array_unique( $vs ) ) . '): la console non le legge (le colonne segrete cambierebbero nome; i dati di un altro database uscirebbero col nome di questo).' ); return $out; }
			list( $skl ) = self::scheletro( $r['sql'] );
			$ts = array_values( array_intersect( $cl['nomi'], self::tabelle_segrete( $c ) ) );
			if ( $ts && preg_match( '/\b(UNION|INTERSECT|EXCEPT|WITH|VALUES|LATERAL|JSON_TABLE)\b|\(\s*(SELECT|TABLE)\b/i', $skl ) ) {
				$c->close(); $rifiuta( 'Tabelle con colonne segrete (' . implode( ', ', $ts ) . '): solo SELECT semplice, senza UNION, WITH né sottoquery (le colonne segrete cambierebbero nome).' ); return $out;
			}
			$kvm = self::kv_in_condizione( $skl, $cl['nomi'] );
			if ( $kvm === '' ) $kvm = self::kv_nudo( $skl, $cl['nomi'], 'lettura', $cl['verbo'] );
			if ( $kvm === '' && $ts && preg_match( '/\b(HAVING|ORDER\s+BY|GROUP\s+BY)\b/i', $skl ) && ( array_intersect( array_keys( self::KV ), $cl['nomi'] ) || ( preg_match( '/(?:\bSELECT\b|,)\s*(?:(?:DISTINCT|ALL)\s+)?(?:[A-Za-z0-9_$\x80-\xff]+\s*\.\s*)*\*/i', $skl ) && array_intersect( $cl['nomi'], self::tabelle_kv( $c ) ) ) ) ) {
				$kvm = 'Valore delle tabelle chiave/valore nel risultato: niente HAVING, ORDER BY né GROUP BY (ordinare o filtrare per il valore, anche col suo alias, lo farebbe dedurre).';
			}
			// F030 (7ª revisione a freddo, medi D03 e D04): DISTINCT conta i valori uguali, NATURAL JOIN e USING li confrontano senza nominarli
			if ( $kvm === '' && $ts && preg_match( '/\bNATURAL\b|\bUSING\s*\(/i', $skl ) ) $kvm = 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $ts ) . '): niente NATURAL JOIN né JOIN … USING (confronterebbero i valori senza nominarli), si unisce con ON sulle colonne innocue.';
			if ( $kvm === '' && $ts && preg_match( '/\bDISTINCT(?:ROW)?\b/i', $skl ) ) $kvm = 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $ts ) . '): niente DISTINCT (il numero di righe direbbe quali valori sono uguali).';
			if ( $kvm === '' && $ts && self::per_posizione( $skl ) ) $kvm = 'Tabelle con colonne segrete (' . implode( ', ', $ts ) . '): niente ORDER BY né GROUP BY per posizione, si ordina per nome di colonna.';
			// F033 (R11-4): con un suggerimento d indice l ordine delle righe seguirebbe l indice sulla colonna segreta
			if ( $kvm === '' && $ts && preg_match( self::SUGGERIMENTI, $skl ) ) $kvm = 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $ts ) . '): niente suggerimenti d indice (USE, FORCE, IGNORE INDEX): l ordine delle righe seguirebbe un indice su una colonna segreta. Gli indici sulle colonne segrete li esclude già la console.';
			if ( $kvm !== '' ) { $c->close(); $rifiuta( $kvm ); return $out; }
			// F033 (R11-4): gli indici che contengono una colonna segreta (o il valore chiave/valore) si escludono dalla SELECT, e il piano
			// del server lo conferma: così ordine delle righe, LIMIT/OFFSET, GROUP_CONCAT e tetti non dipendono dai valori segreti
			$sql_letta = $r['sql']; $esclusi = array();
			// anche una vista innocua può poggiare su una tabella con un indice segreto: si guardano le tabelle che la sua definizione nomina
			$larghi = $cl['nomi'];
			foreach ( $viste as $x ) { if ( in_array( $x[0], $cl['nomi'], true ) && preg_match_all( '/[A-Za-z_][A-Za-z0-9_$]*/', $x[1], $pv ) ) $larghi = array_merge( $larghi, array_map( 'strtolower', $pv[0] ) ); }
			if ( $cl['verbo'] === 'SELECT' && ( $ts || array_intersect( $cl['nomi'], $nv ) ) ) {
				list( $ign, $chv ) = self::indici_segreti( $c );
				if ( $ign === null ) { $c->close(); $rifiuta( 'Indici del database non leggibili (information_schema.STATISTICS): senza sapere quali indici stanno sulle colonne segrete la console non legge le tabelle con segreti (' . implode( ', ', $ts ) . ').' ); return $out; }
				$tc = array_values( array_intersect( array_keys( $chv ), $larghi ) );
				if ( $tc ) { $c->close(); $rifiuta( 'Tabelle la cui chiave primaria (o l indice unico che ne fa le veci) contiene una colonna segreta (' . implode( ', ', $tc ) . '): le righe uscirebbero nell ordine dei segreti, la console non le legge con SELECT, nemmeno attraverso una vista (SHOW e DESCRIBE restano).' ); return $out; }
				$ign = array_intersect_key( $ign, array_flip( $larghi ) );
				if ( $ign ) {
					list( $sql_letta, $esclusi ) = self::senza_indici_segreti( $r['sql'], $ign );
					$ep = ''; $pp = self::piano_pulito( $c, $sql_letta, $ign, $ep );
					if ( $pp === null && $sql_letta !== $r['sql'] ) { $sql_letta = $r['sql']; $esclusi = array(); $pp = self::piano_pulito( $c, $sql_letta, $ign, $ep ); }
					if ( $pp === null ) { $c->close(); $riga( 'ROSSO', $cl['verbo'] . ' su ' . $at . ': ' . $ep ); return $out; }
					if ( $pp === false ) { $c->close(); $rifiuta( 'Il server leggerebbe lungo un indice su una colonna segreta (' . implode( ', ', array_map( static function ( $t, $ii ) { return implode( ', ', $ii ) . ' di ' . $t; }, array_keys( $ign ), $ign ) ) . ') e la console non è riuscita a escluderlo: le righe uscirebbero nell ordine dei segreti. Scrivi le tabelle nel FROM in modo semplice (FROM tabella [AS alias] [JOIN tabella …]); una vista sopra queste tabelle non si può correggere da qui: si legge la tabella.' ); return $out; }
				}
			}
			$l = self::leggi_su( $c, $sql_letta, $cl['nomi'] );
			$c->close();
			if ( ! $l['ok'] ) { $riga( 'ROSSO', $cl['verbo'] . ' su ' . $at . ': ' . $l['errore'] ); return $out; }
			$out['colonne'] = $l['colonne']; $out['dati'] = $l['dati'];
			$riga( 'INFO', $cl['verbo'] . ' su ' . $at . ' · lettura in transazione READ ONLY · tempo massimo ' . self::TEMPO_MAX . ' s · ' . count( $l['dati'] ) . ' righe' );
			if ( $l['troncato'] ) $riga( 'INFO', 'Risultato troncato: al massimo ' . self::MAX_RIGHE . ' righe e ' . intdiv( self::MAX_BYTE, 1000000 ) . ' MB.' );
			if ( $esclusi ) $riga( 'INFO', 'Indici su colonne segrete esclusi dalla console (IGNORE INDEX: ' . implode( ', ', $esclusi ) . ') su ' . $at . ': l ordine e la scelta delle righe non seguono i valori segreti.' );
			$riga( 'INFO', 'Colonne segrete mascherate: ' . ( $l['mascherate'] ? implode( ', ', $l['mascherate'] ) : 'nessuna in questo risultato' ) );
			$riga( 'VERDE', 'Lettura eseguita su ' . $at . ': ' . ( $l['troncato'] ? 'solo le prime ' . count( $l['dati'] ) . ' righe, risultato troncato (non è tutto).' : count( $l['dati'] ) . ' righe.' ) );
			return $out;
		}

		// ── scrittura ───────────────────────────────────────────
		if ( ! $s['scrivibile'] ) { $rifiuta( 'Sorgente WordPress: «' . $cl['verbo'] . '» rifiutata. Sul database di WordPress solo lettura, sempre.' ); return $out; }
		if ( empty( $r['consenti'] ) ) { $rifiuta( 'Scrittura non consentita: spunta «Consenti scrittura» (solo BASE e sorgenti IN USO).' ); return $out; }
		if ( array_intersect( array( strtolower( self::tabella_registro() ), strtolower( self::tabella_gettoni() ) ), $cl['nomi'] ) ) { $rifiuta( 'Il registro della console non si tocca dalla console (append-only).' ); return $out; }
		$c = self::apri( (array) self::parametri( $k ) );
		if ( ! $c ) { $riga( 'ROSSO', 'Connessione non riuscita a ' . $at . '.' ); return $out; }
		$sc = self::segrete_citate( $c, $cl['nomi'] );
		if ( $sc ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): la console non le copia né le usa in scrittura.' ); return $out; }
		list( $skw ) = self::scheletro( $r['sql'] );
		$tsw = array_values( array_intersect( $cl['nomi'], self::tabelle_segrete( $c ) ) );
		if ( $tsw && preg_match( '/\b(SELECT|TABLE|LATERAL|JSON_TABLE)\b/i', $skw ) ) { $c->close(); $rifiuta( 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $tsw ) . '): in scrittura non si leggono (niente SELECT né TABLE), i segreti non si copiano.' ); return $out; }
		$kvm = self::kv_in_condizione( $skw, $cl['nomi'] );
		if ( $kvm === '' ) $kvm = self::kv_nudo( $skw, $cl['nomi'], 'scrittura', $cl['verbo'] );
		// F030 (7ª revisione a freddo, medio D02): ON DUPLICATE KEY UPDATE conta 0 o 2 righe secondo il valore che c era
		if ( $kvm === '' && $tsw && preg_match( '/\bON\s+DUPLICATE\b/i', $skw ) ) $kvm = 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $tsw ) . '): niente ON DUPLICATE KEY UPDATE (il conteggio direbbe se il valore era già quello); si usa UPDATE … WHERE chiave = ….';
		if ( $kvm === '' && $tsw ) {
			// F029 (4ª revisione, ALTO 3; F45 b): con tabelle segrete o chiave/valore una tabella sola, INSERT con le colonne, niente posizioni
				// F033 (R11-3): «()» per il server sono TUTTE le colonne: dopo la parentesi deve venire un nome di colonna
			$tcur = array_values( array_intersect( $cl['nomi'], self::colonna( $c, 'SELECT LOWER(TABLE_NAME) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()' ) ) );
			if ( count( $tcur ) > 1 || preg_match( '/\bJOIN\b|\bUSING\b/i', $skw ) ) $kvm = 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $tsw ) . '): in scrittura una tabella sola, niente UPDATE o DELETE su più tabelle (copierebbero i valori).';
			elseif ( in_array( $cl['verbo'], array( 'INSERT', 'REPLACE' ), true ) && ! preg_match( '/^\s*(?:INSERT|REPLACE)\s+(?:(?:LOW_PRIORITY|DELAYED|HIGH_PRIORITY|IGNORE)\s+)*(?:INTO\s+)?[A-Za-z0-9_$.\x80-\xff]+\s*(?:\(\s*[A-Za-z_]|SET\b)/i', $skw ) ) $kvm = 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $tsw ) . '): INSERT e REPLACE con l elenco delle colonne (senza elenco, o con l elenco vuoto «()», si scriverebbero le colonne segrete).';
			elseif ( self::per_posizione( $skw ) ) $kvm = 'Tabelle con colonne segrete (' . implode( ', ', $tsw ) . '): niente ORDER BY per posizione.';
			// F033 (R11-4): con un suggerimento d indice UPDATE e DELETE … LIMIT sceglierebbero le righe nell ordine dei segreti
			elseif ( preg_match( self::SUGGERIMENTI, $skw ) ) $kvm = 'Tabelle con colonne segrete o chiave/valore (' . implode( ', ', $tsw ) . '): niente suggerimenti d indice (USE, FORCE, IGNORE INDEX) in scrittura: le righe si sceglierebbero lungo un indice su una colonna segreta.';
		}
		if ( $kvm !== '' ) { $c->close(); $rifiuta( $kvm ); return $out; }
		$mi = self::controlla_scrittura( $c, $cl );
		if ( $mi !== '' ) { $c->close(); $rifiuta( $mi ); return $out; }
		// F033 (R11-7): tetto più basso per l istruzione in scrittura (il server, fermandola, annulla ciò che ha già scritto: dura altrettanto e più)
		self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_SCRITTURA );
		// F028: anche dove max_statement_time non c è (MySQL 8), l attesa dei lucchetti ha il tetto
		self::q( $c, 'SET SESSION innodb_lock_wait_timeout = ' . self::TEMPO_MAX );
		self::q( $c, 'SET SESSION lock_wait_timeout = ' . self::TEMPO_MAX );
		$u  = (int) $r['utente'];
		$im = self::impronta( $r, $s );

		if ( $r['azione'] === 'prova' ) {
			// F030 (6ª revisione a freddo, basso): la prova non lascia effetti, nemmeno sui contatori AUTO_INCREMENT
			$ai = self::contatori( $c, $cl['nomi'] );
			if ( ! self::q( $c, 'START TRANSACTION' ) ) { $riga( 'ROSSO', 'Transazione non aperta su ' . $at . ': ' . self::err( $c ) ); $c->close(); return $out; }
			// F033 (R11-7): la risposta si aspetta fino a TEMPO_MAX secondi, annullamento compreso; oltre, niente gettone e si lascia la connessione
			$entro = microtime( true ) + self::risposta_entro(); $n = -1; $e = '';
			list( $ok, $scaduta ) = self::scrivi_su( $c, $r['sql'], $entro );
			if ( ! $scaduta ) {
				$n  = $ok ? self::righe_trovate( $c ) : -1; $e = $ok ? '' : self::err( $c ) . ( in_array( (int) $c->errno, array( 1969, 3024, 1317 ), true ) ? ' — in scrittura l istruzione ha al massimo ' . self::TEMPO_SCRITTURA . ' s: restringila con WHERE o dividila in più passi' : '' );
				// anche l annullamento di un istruzione riuscita sta dentro lo stesso tempo
				list( , $scaduta ) = self::scrivi_su( $c, 'ROLLBACK', $entro );
			}
			if ( $scaduta ) {
				self::lascia( $c );
				$rifiuta( 'Prova a secco fermata: il server non ha risposto entro ' . self::secondi( self::risposta_entro() ) . ' s (istruzione troppo pesante, o annullamento ancora in corso sul server). Nessun dato scritto: la transazione non è confermata e il server la annulla; i contatori AUTO_INCREMENT possono essere avanzati. Restringi l istruzione con WHERE o dividila in più passi.' );
				return $out;
			}
			// F031 (8ª revisione, MEDIO 5): ALTER solo sulle tabelle il cui contatore è davvero cambiato
			$ai_dopo = self::contatori( $c, $cl['nomi'] ); $ai_da = array();
			foreach ( $ai as $ta => $na ) { if ( (int) $na < 0 || ( $ai_dopo[ $ta ] ?? null ) !== $na ) $ai_da[ $ta ] = $na; }
			$ai_ok = self::rimetti_contatori( $c, $ai_da ); $c->close();
			if ( ! $ok ) { $rifiuta( 'Prova a secco non riuscita: ' . $e ); return $out; }
			if ( ! self::registra( $r, $s, 'prova', $n, 'annullata', 'prova a secco' ) ) {
				$riga( 'ROSSO', 'Registro sul BASE non scrivibile: senza registro non si scrive (' . $at . ').' ); return $out;
			}
			$scade = self::ora() + self::CONFERMO_TTL;
			self::gettone_scrivi( $u, array( 'impronta' => $im, 'righe' => $n, 'scade' => $scade, 'gid' => bin2hex( random_bytes( 16 ) ) ) );
			$out['righe_prova'] = $n; $out['scade'] = $scade;
			$riga( 'INFO', 'Prova a secco su ' . $at . ': ' . $cl['verbo'] . ' in una transazione annullata (tabelle InnoDB; MyISAM rifiutata) · tempo massimo dell istruzione in scrittura ' . self::TEMPO_SCRITTURA . ' s, risposta entro ' . self::secondi( self::risposta_entro() ) . ' s (annullamento compreso).' );
			$riga( 'INFO', self::$blindato ? 'Registro: protetto da trigger contro UPDATE e DELETE.' : 'Registro: protetto solo per nome (il server non concede TRIGGER): la console non lo tocca, il database sì.' );
			$riga( 'INFO', 'Righe che cambierebbero: ' . $n . ' (trovate dalla condizione, anche se già uguali) · nessun dato scritto' . ( $ai_ok ? ', contatori AUTO_INCREMENT rimessi com erano.' : ' (contatori AUTO_INCREMENT non rimessi: la sorgente non concede ALTER; possono essere avanzati).' ) );
			$riga( 'INFO', 'Per scrivere davvero su ' . $at . ' scrivi CONFERMO e premi «Esegui» entro ' . intdiv( self::CONFERMO_TTL, 60 ) . ' minuti; vale solo per questa istruzione, su questa sorgente, una volta.' );
			return $out;
		}

		// azione «esegui»: CONFERMO + gettone della prova a secco
		if ( (string) $r['conferma'] !== 'CONFERMO' ) { $c->close(); $rifiuta( 'Manca «CONFERMO»: la scrittura non parte.' ); return $out; }
		$g = self::gettone_leggi( $u );
		if ( ! $g ) { $c->close(); $rifiuta( 'Nessuna prova a secco valida per questa istruzione: prima la prova a secco.' ); return $out; }
		if ( (int) $g['scade'] < self::ora() ) { self::gettone_scrivi( $u, null ); $c->close(); $rifiuta( 'CONFERMO scaduto (oltre ' . intdiv( self::CONFERMO_TTL, 60 ) . ' minuti): rifai la prova a secco.' ); return $out; }
		if ( ! hash_equals( (string) $g['impronta'], $im ) ) { $c->close(); $rifiuta( 'Il CONFERMO vale solo per l istruzione e la sorgente della prova a secco: rifai la prova.' ); return $out; }
		if ( ! self::consuma_gettone( (string) ( $g['gid'] ?? '' ) ) ) {
			self::gettone_scrivi( $u, null ); $c->close(); $rifiuta( 'CONFERMO già usato (o gettone non registrabile sul BASE): rifai la prova a secco.' ); return $out;
		}
		if ( ! self::registra( $r, $s, 'avvio', (int) $g['righe'], 'in_corso', 'scrittura confermata' ) ) {
			$c->close(); $riga( 'ROSSO', 'Registro sul BASE non scrivibile: senza registro non si scrive (' . $at . ').' ); return $out;
		}
		self::gettone_scrivi( $u, null );
		if ( ! self::q( $c, 'START TRANSACTION' ) ) { $riga( 'ROSSO', 'Transazione non aperta su ' . $at . ': ' . self::err( $c ) ); $c->close(); return $out; }
		$entro = microtime( true ) + self::risposta_entro();
		list( $ok, $scaduta ) = self::scrivi_su( $c, $r['sql'], $entro );
		if ( $scaduta ) {
			// F033 (R11-7): nessun COMMIT è partito: lasciando la connessione il server annulla la transazione
			self::lascia( $c );
			$m = 'Scrittura fermata: il server non ha risposto entro ' . self::secondi( self::risposta_entro() ) . ' s. Niente scritto: la transazione non ha avuto COMMIT e il server la annulla. Restringi l istruzione o dividila, poi rifai la prova a secco.';
			$riga( 'ROSSO', $m . ' (' . $at . ')' ); self::registra( $r, $s, 'scrittura', null, 'annullata', $m ); return $out;
		}
		$n  = $ok ? self::righe_trovate( $c ) : -1;
		if ( ! $ok ) { $e = self::err( $c ); self::q( $c, 'ROLLBACK' ); $c->close(); $riga( 'ROSSO', 'Scrittura non riuscita su ' . $at . ': ' . $e ); self::registra( $r, $s, 'scrittura', null, 'errore', $e ); return $out; }
		if ( $n !== (int) $g['righe'] ) {
			// F033 (R11-7): anche questo annullamento sta nel tempo di risposta; se non finisce si lascia la connessione (nessun COMMIT: il server annulla)
			list( , $sc ) = self::scrivi_su( $c, 'ROLLBACK', $entro ); if ( $sc ) self::lascia( $c ); else $c->close();
			$m = 'Righe ricontate: ' . $n . ' invece di ' . (int) $g['righe'] . ' della prova a secco → annullata, niente scritto. Rifai la prova.';
			$riga( 'ROSSO', $m . ' (' . $at . ')' ); self::registra( $r, $s, 'scrittura', $n, 'annullata', $m ); return $out;
		}
		$fatto = self::q( $c, 'COMMIT' ); $e = $fatto ? '' : self::err( $c ); $c->close();
		if ( ! $fatto ) { $riga( 'ROSSO', 'COMMIT non riuscito su ' . $at . ': ' . $e ); self::registra( $r, $s, 'scrittura', $n, 'errore', $e ); return $out; }
		self::registra( $r, $s, 'scrittura', $n, 'eseguita', 'scritta' );
		$riga( 'VERDE', $cl['verbo'] . ' eseguita su ' . $at . ': ' . $n . ' righe scritte (trovate dalla condizione, anche se già uguali); nel registro del BASE.' );
		return $out;
	}
}
