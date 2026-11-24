<?php
/**
 * F032-banco-console-sql.php — RelDoxAcademy · Formazione F032 · 24/11/2026 (nasce dal banco F031)
 * Nona revisione a freddo (F032, sulla 0.1.16A: 6 alti, 5 medi): sezione N (N1-N12). Rosso sulla 0.1.16A, verde sulla 0.1.17A.
 *
 * (dal banco F031)
 * F031-banco-console-sql.php — RelDoxAcademy · Formazione F031 · 23/11/2026 (nasce dal banco F030)
 * Ottava revisione a freddo (F031, sulla 0.1.15A: 5 alti, 6 medi): sezione M (M1-M11). Rosso sulla 0.1.15A, verde sulla 0.1.16A.
 *
 * (dal banco F030)
 * F030-banco-console-sql.php — (nasce dal banco F029)
 * Sesta revisione a freddo (F030, sulla 0.1.14A finale): sezione G sexies (L1-L2: PROCEDURE ANALYSE() che mostra i valori
 * segreti nelle colonne Min_value/Max_value; AUTO_INCREMENT avanzato dalla prova a secco) e L3 (F46 a: il conteggio della prova a
 * secco è delle righe trovate, non di quelle cambiate, e non dice più se il valore indovinato è giusto). Rosso sulla 0.1.14A, verde sulla 0.1.15A.
 * Settima revisione a freddo (F030, sulla 0.1.15A prima stesura): L4-L9 (ON DUPLICATE KEY UPDATE sul valore, DISTINCT e NATURAL JOIN
 * come oracoli, contatori di tabelle con maiuscole, valori in chiaro nel registro, SHOW  PROCEDURE STATUS rifiutata a torto).
 *
 * (dal banco F029)
 * F029-banco-console-sql.php — RelDoxAcademy · Formazione F029 · 23/11/2026 (nasce dal banco F028)
 * BUG-6, quarta revisione a freddo (F028, sulla 0.1.13A): sezione G quater (J1-J9: «--» seguito da un carattere di controllo,
 * tabelle chiave/valore unite con sé stesse, UPDATE/DELETE su più tabelle, chiave rinominata, errore a metà lettura dato per
 * VERDE e oracolo con EXP, F45 a-b: ROLE_ troppo largo, ORDER BY per posizione e INSERT senza colonne). Rosso sulla 0.1.13A.
 * Quinta revisione a freddo (F029, sulla 0.1.14A): sezione G quinquies (K1-K4). Rosso sulla 0.1.14A del commit a2a151b, verde sulla 0.1.14A finale.
 *
 * (dal banco F028)
 * F028-banco-console-sql.php — (nasce dal banco F027)
 * BUG-6, terza revisione a freddo (F028): sezione G ter (INSERT … SELECT dalle tabelle con segreti, tabelle chiave/valore,
 * sql_mode ANSI_QUOTES e NO_BACKSLASH_ESCAPES, INNODB_TRX e simili, tetto in byte, utente della sorgente, TABLE nelle
 * sottoquery e suggerimenti /*+, registro protetto dichiarato, attesa dei lucchetti in scrittura). Rosso sulla 0.1.12A, verde sulla 0.1.13A.
 *
 * (dal banco F027)
 * BUG-6 e F42: sezione G (i casi della revisione a freddo F026: apici inversi, db.tabella, viste, trigger, funzioni,
 * schema mysql, utente, alias e segreti, messaggi d errore, gettone atomico, registro «avvio») e sezione H (porta AJAX
 * chiamata davvero, multisite, interfaccia del mockup). D2 e D3: le colonne segrete si vedono solo con SELECT *, mascherate.
 * Rosso sulla 0.1.11A, verde sulla 0.1.12A.
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Banco della Console SQL (voce F29, banco standard n. 2; mockup MOCKUP-CONSOLE-SQL-v1 approvato D45, 23/11/2026 08:39).
 * Rosso sulla 0.1.10A (la console non c è), verde sulla 0.1.11A. Chiama il motore vero
 * (Rdacademy_Console_SQL) su un MariaDB vero; delle classi del prodotto usa sostituti minimi con la stessa firma
 * (Rdacademy_Sorgenti, Rdacademy_DataSource, Rdacademy_DB_Config), come i banchi F023.
 *
 * Serve un MariaDB/MySQL locale con l utente `banco`/`banco` che può creare i database academy_base, academy_s3, wp_prova.
 *
 * uso:  php banco/F029-banco-console-sql.php <cartella del plugin reldox-academy-diagnostica> [--trappola]
 *   --trappola  copia la diagnostica in una cartella temporanea, toglie il rifiuto di DROP dal motore e rifà il banco:
 *               deve dire ROSSO (il banco non è cieco).
 */
$D = rtrim( $argv[1] ?? '', '/' );
if ( $D === '' || ! is_dir( $D ) ) { fwrite( STDERR, "uso: php banco/F032-banco-console-sql.php <reldox-academy-diagnostica> [--trappola]\n" ); exit( 2 ); }

if ( in_array( '--trappola', $argv, true ) ) {
	$tmp = sys_get_temp_dir() . '/f032-trappola-' . getmypid();
	exec( 'rm -rf ' . escapeshellarg( $tmp ) . ' && cp -r ' . escapeshellarg( $D ) . ' ' . escapeshellarg( $tmp ) );
	$m = "$tmp/includes/class-rdacademy-console-sql.php";
	if ( is_file( $m ) ) { file_put_contents( $m, str_replace( "'DROP', ", '', (string) file_get_contents( $m ) ) ); }
	passthru( 'php ' . escapeshellarg( __FILE__ ) . ' ' . escapeshellarg( $tmp ), $rc );
	exec( 'rm -rf ' . escapeshellarg( $tmp ) );
	echo $rc !== 0 ? "TRAPPOLA: il banco ha detto ROSSO sul motore sabotato (DROP tolto) — come deve.\n" : "TRAPPOLA FALLITA: il banco è verde su un motore sabotato.\n";
	exit( $rc !== 0 ? 0 : 1 );
}

$esiti = array();
function prova( string $n, bool $ok, string $d = '' ) { global $esiti; $esiti[] = array( $n, $ok, $d ); }
function leggi_f( string $f ): string { return is_file( $f ) ? (string) file_get_contents( $f ) : ''; }
function fine(): void {
	global $esiti; $v = 0; $r = 0;
	foreach ( $esiti as $e ) { echo ( $e[1] ? '  VERDE  ' : '  ROSSO  ' ) . $e[0] . ( $e[2] !== '' ? ' — ' . $e[2] : '' ) . "\n"; $e[1] ? $v++ : $r++; }
	echo "\n$v verdi, $r rossi\n"; exit( $r === 0 ? 0 : 1 );
}

// ── A. file, carico, registro, porta AJAX ────────────────────────────────────────
$fm = "$D/includes/class-rdacademy-console-sql.php";
$fb = "$D/includes/banchi/class-rdacademy-banco-console-sql.php";
$sint = true;
foreach ( array( $fm, $fb ) as $f ) { if ( ! is_file( $f ) ) { $sint = false; continue; } exec( 'php -l ' . escapeshellarg( $f ) . ' 2>&1', $o, $rc ); if ( $rc !== 0 ) $sint = false; }
prova( 'A1 motore e banco della console ci sono e si leggono (php -l)', $sint );
$princ = leggi_f( "$D/reldox-academy-diagnostica.php" );
prova( 'A2 la diagnostica carica motore e banco e avvia il banco', strpos( $princ, "includes/class-rdacademy-console-sql.php" ) !== false && strpos( $princ, 'Rdacademy_Banco_Console_SQL::init()' ) !== false );
$reg = leggi_f( "$D/includes/class-rdacademy-banchi-registro.php" );
prova( 'A3 registro: area «Database e sorgenti» e voce n. 2 utility col suo file', (bool) preg_match( "/'cat-database'\s*=>\s*array\(\s*'Database e sorgenti'/", $reg ) && (bool) preg_match( "/'n'\s*=>\s*2,\s*'nat'\s*=>\s*'util',\s*'cat'\s*=>\s*'cat-database',\s*'file'\s*=>\s*'includes\/banchi\/class-rdacademy-banco-console-sql\.php'/", $reg ) );
$banco = leggi_f( $fb );
prova( 'A4 forma comune: costanti N, NAT, AREA, SCHEDA, Q, TITOLO, SCOPO e metodi init/render/esegui/ajax', $banco !== '' && ! array_diff( array( 'N', 'NAT', 'AREA', 'SCHEDA', 'Q', 'TITOLO', 'SCOPO' ), array_filter( array( 'N', 'NAT', 'AREA', 'SCHEDA', 'Q', 'TITOLO', 'SCOPO' ), function ( $c ) use ( $banco ) { return (bool) preg_match( "/const $c\s*=/", $banco ); } ) ) && (bool) preg_match( '/function init\(.*function render\(.*function esegui\(.*function ajax\(/s', $banco ) );
prova( 'A5 porta AJAX: permesso (manage_options; su multisite super amministratore) e nonce prima di tutto (provati davvero in H1-H3)', (bool) preg_match( "/function ajax\(\)[^{]*\{\s*if \( ! self::puo\(\) \)[\s\S]{0,250}check_ajax_referer\(/", $banco ) && (bool) preg_match( "/function puo\(\)[^{]*\{\s*return current_user_can\( 'manage_options' \) && \( ! is_multisite\(\) \|\| is_super_admin\(\) \);/", $banco ) );
prova( 'A6 riga finale «N verdi, M rossi» e «Copia esito» nel referto', strpos( $banco, ' verdi, ' ) !== false && strpos( $banco, 'Copia esito' ) !== false );
prova( 'A7 interfaccia del mockup: Prova a secco, CONFERMO, spunta «Consenti scrittura»', strpos( $banco, 'Prova a secco' ) !== false && strpos( $banco, 'CONFERMO' ) !== false && strpos( $banco, 'Consenti scrittura' ) !== false );

if ( ! is_file( $fm ) ) { prova( 'B0 motore assente: le prove sul database non si possono fare', false ); fine(); }

// ── WordPress finto e sostituti delle classi del prodotto (stessa firma) ───────
define( 'ABSPATH', '/tmp/' ); define( 'RDACADEMY_TABLE_PREFIX', 'rdacademy_' );
define( 'DB_HOST', 'localhost' ); define( 'DB_NAME', 'wp_prova' ); define( 'DB_USER', 'banco' ); define( 'DB_PASSWORD', 'banco' );
$GLOBALS['table_prefix'] = 'wp_';
const PASS_S3 = 'Segreto-S3-banco';
final class Rdacademy_Sorgenti {
	const MAPPA = array( 'sorgente_3' => array( 'n' => 3, 'nome' => 'Sorgente 3' ), 'sorgente_4' => array( 'n' => 4, 'nome' => 'Sorgente 4' ), 'riserva' => array( 'n' => 5, 'nome' => 'Riserva' ), 'sorgente_wp' => array( 'n' => 6, 'nome' => 'Archivio nel database di WordPress' ) );
	public static function mappa(): array { return self::MAPPA; }
	public static function nome( string $k ): string { return self::MAPPA[ $k ]['nome'] ?? $k; }
	public static function numero( string $k ): int { return self::MAPPA[ $k ]['n'] ?? 0; }
}
final class Rdacademy_DataSource {
	public static function state( string $k ): string { return array( 'sorgente_3' => 'live', 'sorgente_4' => 'not_configured', 'riserva' => 'disabled', 'sorgente_wp' => 'live' )[ $k ] ?? 'not_configured'; }
	public static function connect_params( string $k ): ?array {
		if ( $k === 'sorgente_3' ) return array( 'host' => 'localhost', 'port' => 3306, 'name' => 'academy_s3', 'user' => 'banco', 'pass' => 'banco', 'charset' => 'utf8mb4', 'pass_finta' => PASS_S3 );
		if ( $k === 'sorgente_wp' ) return array( 'host' => 'localhost', 'port' => 3306, 'name' => 'wp_prova', 'user' => 'banco', 'pass' => 'banco', 'charset' => 'utf8mb4' );
		if ( $k === 'riserva' ) return array( 'host' => 'localhost', 'port' => 3306, 'name' => 'academy_base', 'user' => 'banco', 'pass' => 'banco', 'charset' => 'utf8mb4' );
		return null;
	}
	public static function connetti( array $c ): array {
		try { $conn = @new mysqli( $c['host'], $c['user'], $c['pass'], $c['name'], (int) ( $c['port'] ?? 3306 ) ); }
		catch ( mysqli_sql_exception $e ) { return array( 'connection' => null, 'ok' => false, 'error_message' => $e->getMessage() ); }
		$conn->set_charset( 'utf8mb4' );
		return array( 'connection' => $conn, 'ok' => true, 'error_message' => '' );
	}
}
final class Rdacademy_DB_Config { public static function get(): array { return array( 'host' => 'localhost', 'name' => 'academy_base', 'user' => 'banco', 'pass' => 'banco' ); } }
function get_transient( $k ) { return $GLOBALS['__tr'][ $k ] ?? false; }
function set_transient( $k, $v, $t ) { $GLOBALS['__tr'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['__tr'][ $k ] ); return true; }

// ── il palco: tre database veri ─────────────────────────────────────────────────
mysqli_report( MYSQLI_REPORT_OFF );
$root = @new mysqli( 'localhost', 'banco', 'banco' );
if ( $root->connect_error ) { prova( 'B0 MariaDB del banco raggiungibile (utente banco)', false, $root->connect_error ); fine(); }
foreach ( array( 'academy_base', 'academy_s3', 'wp_prova' ) as $db ) { $root->query( "DROP DATABASE IF EXISTS `$db`" ); $root->query( "CREATE DATABASE `$db`" ); }
$root->select_db( 'academy_base' );
$root->query( "CREATE TABLE rdacademy_users (id INT PRIMARY KEY AUTO_INCREMENT, email VARCHAR(80), status VARCHAR(20), api_token VARCHAR(64)) ENGINE=InnoDB" );
for ( $i = 1; $i <= 10; $i++ ) { $dom = $i <= 7 ? 'esempio.it' : 'altro.it'; $root->query( "INSERT INTO rdacademy_users (email,status,api_token) VALUES ('u$i@$dom','active','tok$i')" ); }
$root->query( "CREATE TABLE rdacademy_molte (id INT PRIMARY KEY) ENGINE=InnoDB" );
$root->query( "INSERT INTO rdacademy_molte (id) SELECT seq FROM seq_1_to_1200" );
$root->query( "CREATE TABLE rdacademy_vecchia (id INT PRIMARY KEY, v INT) ENGINE=MyISAM" );
$root->query( "INSERT INTO rdacademy_vecchia VALUES (1,1),(2,2)" );
$root->query( "CREATE TABLE rdacademy_contr (id INT PRIMARY KEY, v INT) ENGINE=InnoDB" );
$root->query( "INSERT INTO rdacademy_contr VALUES (1,1)" );
$root->query( "CREATE TRIGGER rdacademy_contr_au AFTER UPDATE ON rdacademy_contr FOR EACH ROW INSERT INTO rdacademy_vecchia VALUES (NEW.id + 100, 1)" );
$root->query( "CREATE VIEW rdacademy_vista AS SELECT id, v FROM rdacademy_vecchia" );
$root->query( "CREATE TABLE `vecchìa` (id INT PRIMARY KEY, v INT) ENGINE=MyISAM" ); $root->query( "INSERT INTO `vecchìa` VALUES (1,1)" );
$root->query( "CREATE SEQUENCE rdacademy_seq" ); $root->query( "CREATE TABLE rdacademy_cs (id INT NOT NULL DEFAULT NEXT VALUE FOR rdacademy_seq, v INT, PRIMARY KEY (id)) ENGINE=InnoDB" );
$root->query( "CREATE TABLE rdacademy_tm (id INT PRIMARY KEY AUTO_INCREMENT, v DOUBLE) ENGINE=InnoDB" );
$root->query( "CREATE FUNCTION rdacademy_f() RETURNS INT MODIFIES SQL DATA BEGIN INSERT INTO rdacademy_vecchia VALUES (99,99); RETURN 1; END" );
// G ter (F028)
$root->query( "CREATE TABLE rdacademy_note (a VARCHAR(80), b VARCHAR(80), c VARCHAR(80), d VARCHAR(80)) ENGINE=InnoDB" );
$root->query( "CREATE TABLE rdacademy_impostazioni (id INT PRIMARY KEY AUTO_INCREMENT, chiave VARCHAR(100), valore LONGTEXT) ENGINE=InnoDB" );
$root->query( "INSERT INTO rdacademy_impostazioni (chiave, valore) VALUES ('mail_servizio_password','pw-riservata-1'),('ai_api_key','sk-RISERVATO-IN-CHIARO'),('titolo','Academy-visibile')" );
$root->query( "CREATE TABLE rdacademy_data_sources (id INT PRIMARY KEY, db_user VARCHAR(60), db_pass_enc VARCHAR(200)) ENGINE=InnoDB" );
$root->query( "INSERT INTO rdacademy_data_sources VALUES (3,'utente_della_sorgente','cifrata')" );
$root->query( "CREATE TABLE rdacademy_ruoli (role_id INT PRIMARY KEY, role_name VARCHAR(40)) ENGINE=InnoDB" );
$root->query( "CREATE TABLE rdacademy_tl (id INT PRIMARY KEY AUTO_INCREMENT, v DOUBLE) ENGINE=InnoDB" );
$root->select_db( 'academy_s3' );
$root->query( "CREATE TABLE mi (id INT PRIMARY KEY, v INT) ENGINE=MyISAM" );
$root->query( "DROP DATABASE IF EXISTS `archivìo`" ); $root->query( "CREATE DATABASE `archivìo`" ); $root->query( "CREATE TABLE `archivìo`.mi (id INT PRIMARY KEY, v INT) ENGINE=MyISAM" ); $root->query( "INSERT INTO `archivìo`.mi VALUES (1,1)" );
$root->query( "CREATE TABLE wp_prova.rdacademy_wpx (id INT PRIMARY KEY, v INT) ENGINE=InnoDB" );
$root->query( "INSERT INTO mi VALUES (1,1)" );
$root->query( "CREATE TABLE rdacademy_s3_dati (id INT PRIMARY KEY, v INT) ENGINE=InnoDB" );
$root->query( "INSERT INTO rdacademy_s3_dati VALUES (1,1),(2,1),(3,1)" );
$root->select_db( 'wp_prova' );
$root->query( "CREATE TABLE wp_users (ID INT PRIMARY KEY, user_login VARCHAR(60), user_pass VARCHAR(255)) ENGINE=InnoDB" );
$root->query( "INSERT INTO wp_users VALUES (1,'admin','\$P\$Bsegretissimo')" );
$root->query( "CREATE TABLE wp_options (option_id INT PRIMARY KEY AUTO_INCREMENT, option_name VARCHAR(191), option_value LONGTEXT) ENGINE=InnoDB" );
$root->query( "INSERT INTO wp_options (option_name, option_value) VALUES ('blogname','Sito-visibile'),('mailserver_pass','smtp-riservata'),('nonce_salt','sale-riservato'),('wp_mail_smtp','a:1:{s:4:\"pass\";s:9:\"smtp-zzz9\";}'),('opzione_innocua','a:1:{s:8:\"password\";s:6:\"pw-zz8\";}'),('auth_key','chiave-auth-riservata')" );

require $fm;
$C = 'Rdacademy_Console_SQL';
prova( 'B1 il motore si carica (classe Rdacademy_Console_SQL)', class_exists( $C ) );
if ( ! class_exists( $C ) ) fine();

// ── B. classificazione: lettura, scrittura, rifiuti (dal server, prima di ogni connessione) ──
$leggi = array( 'SELECT 1', 'select * from rdacademy_users;', 'SHOW TABLES', 'DESCRIBE rdacademy_users', 'EXPLAIN SELECT 1', 'WITH x AS (SELECT 1 AS a) SELECT a FROM x', "SELECT ';DROP TABLE x' AS a", "SELECT 1 -- ; DROP TABLE x", '(SELECT 1)' );
$ok = true; $no = array();
foreach ( $leggi as $s ) { $c = $C::classifica( $s ); if ( ! $c['ok'] || $c['tipo'] !== 'lettura' ) { $ok = false; $no[] = $s; } }
prova( 'B2 letture riconosciute (' . count( $leggi ) . ' casi, anche con «;» in una stringa o in un commento)', $ok, implode( ' | ', $no ) );
$scr = array( 'INSERT INTO t (a) VALUES (1)', "UPDATE rdacademy_users SET status = 'x' WHERE id = 1", 'DELETE FROM t WHERE id = 1', 'REPLACE INTO t VALUES (1)', 'WITH x AS (SELECT 1) UPDATE t SET a = 1' );
$ok = true; $no = array();
foreach ( $scr as $s ) { $c = $C::classifica( $s ); if ( ! $c['ok'] || $c['tipo'] !== 'scrittura' ) { $ok = false; $no[] = $s; } }
prova( 'B3 le quattro scritture ammesse riconosciute, anche dentro WITH', $ok, implode( ' | ', $no ) );
$rif = array( 'DROP TABLE rdacademy_users', 'drop table x', 'TRUNCATE rdacademy_users', 'ALTER TABLE t ADD c INT', 'ALTER TABLE t MODIFY c INT', 'RENAME TABLE a TO b', 'GRANT ALL ON *.* TO x', 'CREATE TABLE t (a INT)', 'SET GLOBAL general_log = 1', "LOAD DATA INFILE '/etc/passwd' INTO TABLE t", 'CALL p()', 'LOCK TABLES t WRITE', 'DO SLEEP(1)', 'HANDLER t OPEN', 'FLUSH TABLES', 'KILL 1', 'USE mysql', 'PREPARE s FROM "SELECT 1"',
	"SELECT * FROM rdacademy_users INTO OUTFILE '/tmp/x'", "SELECT 1 INTO DUMPFILE '/tmp/x'", 'SELECT 1 INTO @x', '/*! DROP TABLE t */ SELECT 1', 'SELECT /*!50000 1 */', 'SELECT /*M!100000 1 */', 'EXPLAIN ANALYZE SELECT 1', 'ANALYZE SELECT 1', 'ANALYZE TABLE t',
	"SELECT LOAD_FILE('/etc/passwd')", 'SELECT * FROM t FOR UPDATE', 'SELECT * FROM t LOCK IN SHARE MODE', 'SELECT * FROM t FOR SHARE', "SELECT GET_LOCK('a', 1)", 'SELECT SLEEP(5)', 'SELECT sleep (5)', 'SELECT BENCHMARK(1000000, MD5(1))', 'UPDATE t SET a = SLEEP(5)', "INSERT INTO t SELECT LOAD_FILE('/etc/passwd')",
	'SELECT 1; DROP TABLE t', 'SELECT 1; SELECT 2', 'SEL/**/ECT 1', '', '   ', 'DELETE FROM t; DELETE FROM u', "SELECT MASTER_POS_WAIT('a', 1)", "SELECT RELEASE_LOCK('a')" );
$ok = true; $no = array();
foreach ( $rif as $s ) { $c = $C::classifica( $s ); if ( $c['ok'] ) { $ok = false; $no[] = $s === '' ? '(vuota)' : $s; } }
prova( 'B4 rifiutate dal server, con o senza spunta (' . count( $rif ) . ' casi: DROP…RENAME, GRANT/CREATE/SET/LOAD/CALL/LOCK, INTO, /*!…*/, EXPLAIN ANALYZE, LOAD_FILE, FOR UPDATE, GET_LOCK, SLEEP, BENCHMARK, due istruzioni)', $ok, implode( ' | ', $no ) );
$c = $C::classifica( 'DROP TABLE rdacademy_users' );
prova( 'B5 il rifiuto di DROP dice perché (mai DROP, TRUNCATE, ALTER, MODIFY, RENAME; solo INSERT, UPDATE, DELETE, REPLACE)', strpos( $c['motivo'], 'non si eseguono mai' ) !== false && strpos( $c['motivo'], 'INSERT, UPDATE, DELETE, REPLACE' ) !== false, $c['motivo'] );
$c = $C::classifica( "INSERT INTO t SELECT * FROM u INTO OUTFILE '/tmp/x'" ); $c2 = $C::classifica( "SELECT * FROM t INTO OUTFILE '/tmp/x'" );
prova( 'B6 INTO OUTFILE rifiutato anche dentro una scrittura, col suo motivo (la console non scrive file)', ! $c['ok'] && strpos( $c['motivo'], 'non scrive file' ) !== false && strpos( $c2['motivo'], 'non scrive file' ) !== false, $c['motivo'] );

// ── C. sorgenti ────────────────────────────────────────────────────────────────
$S = $C::sorgenti();
prova( 'C1 sorgenti: BASE, quelle del prodotto e WordPress; spente e non configurate dichiarate', isset( $S['base'], $S['sorgente_3'], $S['sorgente_4'], $S['riserva'], $S['wordpress'] ) && $S['sorgente_3']['in_uso'] && ! $S['sorgente_4']['in_uso'] && ! $S['riserva']['in_uso'] && ! $S['wordpress']['scrivibile'] && $S['base']['scrivibile'], json_encode( array_map( function ( $s ) { return $s['stato']; }, $S ) ) );
$tutto_s = json_encode( $S );
prova( 'C2 mai una password nell elenco delle sorgenti (solo «presente»)', strpos( $tutto_s, 'banco' ) === false && strpos( $tutto_s, PASS_S3 ) === false && strpos( $tutto_s, 'presente' ) !== false );
function chiedi( array $r ): array { return Rdacademy_Console_SQL::esegui( $r + array( 'sorgente' => 'base', 'consenti' => false, 'azione' => 'esegui', 'conferma' => '', 'utente' => 7, 'login' => 'idea-tecnico' ) ); }
function testo( array $x ): string { return implode( "\n", array_map( function ( $l ) { return '[' . $l[0] . '] ' . $l[1]; }, $x['referto'] ) ); }
$x = chiedi( array( 'sorgente' => 'sorgente_4', 'sql' => 'SELECT 1' ) );
prova( 'C3 sorgente 4 non configurata: rifiutata, non ricade sul BASE', $x['rossi'] > 0 && empty( $x['dati'] ) && stripos( testo( $x ), 'non configurata' ) !== false, testo( $x ) );
$x = chiedi( array( 'sorgente' => 'riserva', 'sql' => 'SELECT 1' ) );
prova( 'C4 riserva spenta: rifiutata anche se ha parametri', $x['rossi'] > 0 && empty( $x['dati'] ), testo( $x ) );
$x = chiedi( array( 'sorgente' => 'inventata', 'sql' => 'SELECT 1' ) );
prova( 'C5 sorgente sconosciuta: rifiutata', $x['rossi'] > 0 && empty( $x['dati'] ) );

// ── D. letture ─────────────────────────────────────────────────────────────────
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_users ORDER BY id' ) );
$t = testo( $x );
prova( 'D1 lettura sul BASE: 10 righe, «academy_base @ localhost», READ ONLY dichiarato, riga «1 verdi, 0 rossi»', count( $x['dati'] ) === 10 && strpos( $t, 'academy_base @ localhost' ) !== false && strpos( $t, 'READ ONLY' ) !== false && $x['verdi'] === 1 && $x['rossi'] === 0, $t );
prova( 'D2 colonna segreta (api_token) mascherata e dichiarata', ( $x['dati'][0][3] ?? '' ) !== 'tok1' && strpos( json_encode( $x ), 'tok1' ) === false && strpos( $t, 'api_token' ) !== false );
$x = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT * FROM wp_users' ) );
prova( 'D3 WordPress in lettura: user_pass mascherata', count( $x['dati'] ) === 1 && strpos( json_encode( $x ), 'segretissimo' ) === false, testo( $x ) );
$x = chiedi( array( 'sql' => 'SELECT id FROM rdacademy_molte' ) );
prova( 'D4 al massimo 1000 righe, troncamento dichiarato', count( $x['dati'] ) === 1000 && stripos( testo( $x ), '1000' ) !== false );
$x = chiedi( array( 'sql' => 'SELECT @@max_statement_time AS t, @@tx_read_only AS ro' ) );
prova( 'D5 tempo massimo 10 s e transazione in sola lettura, misurati dal server', isset( $x['dati'][0] ) && (float) $x['dati'][0][0] === 10.0 && (string) $x['dati'][0][1] === '1', json_encode( $x['dati'] ) );
$r = $C::leggi_su( Rdacademy_DataSource::connetti( Rdacademy_DB_Config::get() + array( 'port' => 3306 ) )['connection'], "UPDATE rdacademy_users SET status = 'X'" );
$root->select_db( 'academy_base' );
prova( 'D6 difesa in profondità: anche un UPDATE passato a leggi_su() lo rifiuta il server (READ ONLY)', ! $r['ok'] && (int) $root->query( "SELECT COUNT(*) FROM rdacademy_users WHERE status = 'X'" )->fetch_row()[0] === 0, $r['errore'] ?? '' );
$x = chiedi( array( 'sorgente' => 'sorgente_3', 'sql' => 'SELECT COUNT(*) FROM rdacademy_s3_dati' ) );
prova( 'D7 lettura sulla sorgente 3 IN USO: dichiara academy_s3 @ localhost', ( $x['dati'][0][0] ?? '' ) == 3 && strpos( testo( $x ), 'academy_s3 @ localhost' ) !== false, testo( $x ) );

// ── E. scritture in due tempi ─────────────────────────────────────────────────
$U = "UPDATE rdacademy_users SET status = 'suspended' WHERE email LIKE '%@esempio.it'";
function sospesi(): int { global $root; $root->select_db( 'academy_base' ); return (int) $root->query( "SELECT COUNT(*) FROM rdacademy_users WHERE status = 'suspended'" )->fetch_row()[0]; }
$x = chiedi( array( 'sql' => $U, 'azione' => 'prova' ) );
prova( 'E1 senza la spunta una scrittura è rifiutata', $x['rossi'] > 0 && sospesi() === 0, testo( $x ) );
$x = chiedi( array( 'sql' => $U, 'consenti' => true, 'sorgente' => 'wordpress', 'azione' => 'prova' ) );
prova( 'E2 sorgente WordPress: DELETE/UPDATE rifiutati anche con la spunta', $x['rossi'] > 0 && stripos( testo( $x ), 'WordPress' ) !== false );
$x = chiedi( array( 'sql' => $U, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'E3 senza prova a secco non si scrive, nemmeno con CONFERMO', $x['rossi'] > 0 && sospesi() === 0, testo( $x ) );
$x = chiedi( array( 'sql' => $U, 'consenti' => true, 'azione' => 'prova' ) );
prova( 'E4 prova a secco: «righe che cambierebbero: 7», nessun dato scritto', ( $x['righe_prova'] ?? -1 ) === 7 && sospesi() === 0 && stripos( testo( $x ), 'nessun dato scritto' ) !== false, testo( $x ) );
$x = chiedi( array( 'sql' => $U, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'confermo' ) );
prova( 'E5 conferma diversa da «CONFERMO» (anche minuscola): rifiutata', $x['rossi'] > 0 && sospesi() === 0 );
$x = chiedi( array( 'sql' => $U . ' AND id > 0', 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'E6 il CONFERMO vale solo per l istruzione provata', $x['rossi'] > 0 && sospesi() === 0 );
$x = chiedi( array( 'sql' => $U, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO', 'utente' => 8 ) );
prova( 'E7 il CONFERMO vale solo per chi ha fatto la prova', $x['rossi'] > 0 && sospesi() === 0 );
$x = chiedi( array( 'sql' => $U, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'E8 CONFERMO giusto: 7 righe scritte davvero, «academy_base @ localhost»', $x['rossi'] === 0 && sospesi() === 7 && strpos( testo( $x ), 'academy_base @ localhost' ) !== false, testo( $x ) );
$x = chiedi( array( 'sql' => $U, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'E9 il CONFERMO si usa una volta sola', $x['rossi'] > 0 );
// scadenza
$D2 = "DELETE FROM rdacademy_users WHERE id = 10";
chiedi( array( 'sql' => $D2, 'consenti' => true, 'azione' => 'prova' ) );
$C::$adesso = time() + 301;
$x = chiedi( array( 'sql' => $D2, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
$C::$adesso = null;
$root->select_db( 'academy_base' );
prova( 'E10 CONFERMO scaduto dopo 5 minuti: rifiutato, riga ancora lì', $x['rossi'] > 0 && (int) $root->query( 'SELECT COUNT(*) FROM rdacademy_users WHERE id = 10' )->fetch_row()[0] === 1, testo( $x ) );
// righe ricontate
$U2 = "UPDATE rdacademy_users SET status = 'riattivato' WHERE status = 'suspended'";
chiedi( array( 'sql' => $U2, 'consenti' => true, 'azione' => 'prova' ) );
$root->query( "UPDATE rdacademy_users SET status = 'suspended' WHERE id = 9" );
$x = chiedi( array( 'sql' => $U2, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'E11 righe ricontate: 8 invece di 7 → annullata, niente scritto', $x['rossi'] > 0 && (int) $root->query( "SELECT COUNT(*) FROM rdacademy_users WHERE status = 'riattivato'" )->fetch_row()[0] === 0, testo( $x ) );
$x = chiedi( array( 'sql' => 'UPDATE rdacademy_vecchia SET v = 9', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'E12 tabella MyISAM: la prova a secco la rifiuta (non si annulla), niente scritto', $x['rossi'] > 0 && (int) $root->query( 'SELECT SUM(v) FROM rdacademy_vecchia' )->fetch_row()[0] === 3, testo( $x ) );
$x = chiedi( array( 'sql' => 'DELETE FROM rdacademy_console_registro', 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => "INSERT INTO `rdacademy_console_gettoni` (gid, quando) VALUES ('x', NOW())", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'E13 il registro e i gettoni non si toccano dalla console (append-only, per nome: vale anche senza permesso TRIGGER)', $x['rossi'] > 0 && stripos( testo( $x ), 'append-only' ) !== false && stripos( testo( $x2 ), 'append-only' ) !== false, testo( $x ) . ' / ' . testo( $x2 ) );
$S3 = 'UPDATE rdacademy_s3_dati SET v = 2 WHERE id = 1';
chiedi( array( 'sorgente' => 'sorgente_3', 'sql' => $S3, 'consenti' => true, 'azione' => 'prova' ) );
$x = chiedi( array( 'sorgente' => 'sorgente_3', 'sql' => $S3, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'E14 il CONFERMO del BASE non vale su un altra sorgente, quello della sorgente 3 sì', $x['rossi'] === 0 && strpos( testo( $x ), 'academy_s3 @ localhost' ) !== false, testo( $x ) );
$x = chiedi( array( 'sql' => 'DROP TABLE rdacademy_users', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'E15 DROP con la spunta: rifiutato, tabella ancora lì', $x['rossi'] > 0 && $root->query( 'SELECT 1 FROM rdacademy_users LIMIT 1' ) !== false );

// ── F. registro sul BASE ───────────────────────────────────────────────────────
$root->select_db( 'academy_base' );
$q = $root->query( 'SELECT utente, login, sorgente, db_nome, db_host, istruzione, tipo, righe, esito FROM rdacademy_console_registro ORDER BY id' );
$righe = $q ? $q->fetch_all( MYSQLI_ASSOC ) : array();
$tipi = array_count_values( array_column( $righe, 'tipo' ) );
prova( 'F1 registro sul BASE: prove, scritture e rifiuti, con chi, sorgente, database, host, istruzione, righe, esito', ! empty( $tipi['prova'] ) && ! empty( $tipi['scrittura'] ) && ! empty( $tipi['rifiuto'] ) && in_array( 'idea-tecnico', array_column( $righe, 'login' ), true ) && in_array( 'academy_s3', array_column( $righe, 'db_nome' ), true ), json_encode( $tipi ) );
$scritta = array_values( array_filter( $righe, function ( $r ) { return $r['tipo'] === 'scrittura' && $r['esito'] === 'eseguita' && $r['db_nome'] === 'academy_base'; } ) );
prova( 'F2 la scrittura di 7 righe è nel registro con le sue righe e la sua istruzione (F030: i valori fra apici come «…»)', isset( $scritta[0] ) && (int) $scritta[0]['righe'] === 7 && $scritta[0]['istruzione'] === preg_replace( "/'[^']*'/", "'…'", $U ) && $scritta[0]['istruzione'] !== $U );
$root->select_db( 'wp_prova' );
prova( 'F3 mai un registro sul database di WordPress', $root->query( "SHOW TABLES LIKE '%console%'" )->num_rows === 0 );
prova( 'F4 nessuna password nel registro', strpos( json_encode( $righe ), PASS_S3 ) === false );

// ── G. BUG-6 e F42: i casi della revisione a freddo F026 ───────────────────────
function uno( string $sql, string $db = 'academy_base' ) { global $root; $root->select_db( $db ); $q = $root->query( $sql ); return $q instanceof mysqli_result ? $q->fetch_row()[0] : null; }
$ap = array( "SELECT `LOAD_FILE`('/etc/hostname')", 'SELECT `SLEEP`(1)', "SELECT `get_lock`('a', 1)", 'SELECT `BENCHMARK`(1, 1)', 'UPDATE t SET a = `SLEEP`(1)', "SELECT `NEXTVAL`(s)", 'SELECT NEXT VALUE FOR s' );
$no = array(); foreach ( $ap as $q ) { if ( $C::classifica( $q )['ok'] ) $no[] = $q; }
prova( 'G1 nomi di funzione fra apici inversi rifiutati come in chiaro (LOAD_FILE, SLEEP, GET_LOCK, BENCHMARK, NEXTVAL)', ! $no, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => 'UPDATE academy_s3.mi SET v = 9', 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => 'UPDATE `academy_s3`.`rdacademy_s3_dati` SET v = 9', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'G2 dal BASE la prova a secco non tocca un altro database (MyISAM di academy_s3, anche fra apici inversi)', $x['rossi'] > 0 && $x2['rossi'] > 0 && (int) uno( 'SELECT v FROM mi', 'academy_s3' ) === 1 && (int) uno( 'SELECT SUM(v) FROM rdacademy_s3_dati', 'academy_s3' ) !== 27 && stripos( testo( $x ), 'altro database' ) !== false, testo( $x ) . ' / ' . testo( $x2 ) );
$W = "UPDATE wp_prova . wp_users SET user_login = 'preso' WHERE ID = 1";
$x = chiedi( array( 'sql' => $W, 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => $W, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
$x3 = chiedi( array( 'sql' => 'DELETE FROM mysql.nessuna_tabella', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'G3 dal BASE niente scrittura su WordPress né su mysql.* con db.tabella, nemmeno con CONFERMO', $x['rossi'] > 0 && $x2['rossi'] > 0 && uno( 'SELECT user_login FROM wp_users', 'wp_prova' ) === 'admin' && stripos( testo( $x ), 'altro database' ) !== false && $x3['rossi'] > 0 && stripos( testo( $x3 ), 'mysql' ) !== false, testo( $x ) . ' / ' . testo( $x3 ) );
$x = chiedi( array( 'sql' => 'UPDATE rdacademy_vista SET v = 7', 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => 'UPDATE rdacademy_contr SET v = 2', 'consenti' => true, 'azione' => 'prova' ) );
$x3 = chiedi( array( 'sql' => "UPDATE rdacademy_users SET status = 'z' WHERE id = rdacademy_f()", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'G4 in scrittura rifiutate viste, tabelle con trigger e funzioni memorizzate (scriverebbero fuori dalla prova a secco)', $x['rossi'] > 0 && $x2['rossi'] > 0 && $x3['rossi'] > 0 && (int) uno( 'SELECT COUNT(*) FROM rdacademy_vecchia' ) === 2 && (int) uno( 'SELECT SUM(v) FROM rdacademy_vecchia' ) === 3, testo( $x ) . ' / ' . testo( $x2 ) . ' / ' . testo( $x3 ) );
$vl = array( 'SELECT * FROM mysql.user', 'SELECT authentication_string FROM mysql.user', 'SELECT * FROM `mysql`.`user`', 'SHOW GRANTS', 'SHOW CREATE USER banco', 'SELECT USER()', 'SELECT CURRENT_USER', 'SELECT SESSION_USER()', 'SELECT * FROM performance_schema.threads', 'SHOW PROCESSLIST' );
$no = array(); foreach ( $vl as $q ) { $x = chiedi( array( 'sql' => $q ) ); if ( $x['rossi'] === 0 || $x['dati'] ) $no[] = $q; }
prova( 'G5 in lettura rifiutati lo schema mysql, performance_schema, SHOW GRANTS/CREATE USER/PROCESSLIST e USER()/CURRENT_USER', ! $no, implode( ' | ', $no ) );
$sg = array( array( 'base', 'SELECT api_token AS x FROM rdacademy_users' ), array( 'base', 'SELECT CONCAT(api_token) FROM rdacademy_users' ), array( 'base', "SELECT id FROM rdacademy_users WHERE api_token = 'tok1'" ), array( 'wordpress', 'SELECT user_pass x FROM wp_users' ), array( 'wordpress', 'SELECT EXTRACTVALUE(1, CONCAT(0x7e, (SELECT user_pass FROM wp_users LIMIT 1)))' ), array( 'wordpress', 'SELECT * FROM (SELECT user_pass AS a FROM wp_users) t' ) );
$no = array(); foreach ( $sg as $q ) { $x = chiedi( array( 'sorgente' => $q[0], 'sql' => $q[1] ) ); $j = json_encode( $x ); if ( strpos( $j, 'tok1' ) !== false || strpos( $j, 'segretissimo' ) !== false || $x['dati'] ) $no[] = $q[1]; }
prova( 'G6 segreti non aggirabili: alias, espressioni, WHERE sulla colonna, errore di EXTRACTVALUE, sottoquery', ! $no, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => 'SELECT EXTRACTVALUE(1, CONCAT(0x7e, (SELECT email FROM rdacademy_users WHERE id = 1)))' ) );
$x2 = chiedi( array( 'sorgente' => 'sorgente_3', 'sql' => 'SELECT * FROM academy_base.rdacademy_tabella_assente' ) );
prova( 'G7 messaggi d errore ripuliti: niente valori dei dati, niente nome utente', $x['rossi'] > 0 && strpos( json_encode( $x ), 'u1@esempio.it' ) === false && strpos( json_encode( $x2 ), "'banco'" ) === false, testo( $x ) . ' / ' . testo( $x2 ) );
prova( 'G8 «authentication» fra le colonne segrete', in_array( 'authentication', $C::SEGRETE, true ) );
$A = "UPDATE rdacademy_users SET status = 'g9' WHERE id = 1";
chiedi( array( 'sql' => $A, 'consenti' => true, 'azione' => 'prova' ) );
$copia = $GLOBALS['__tr'];
chiedi( array( 'sql' => $A, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
uno( "UPDATE rdacademy_users SET status = 'active' WHERE id = 1" );
$GLOBALS['__tr'] = $copia;
$x = chiedi( array( 'sql' => $A, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'G9 F42(a) il gettone si consuma una volta sola anche se due richieste l hanno letto insieme', $x['rossi'] > 0 && uno( 'SELECT status FROM rdacademy_users WHERE id = 1' ) === 'active', testo( $x ) );
$c = $C::classifica( "WITH x AS (SELECT 1) SELECT REPLACE('a', 'b', 'c')" );
prova( 'G10 F42(b) WITH … SELECT REPLACE(…) è una lettura', $c['ok'] && $c['tipo'] === 'lettura', json_encode( $c ) );
$root->select_db( 'academy_base' );
$tr = (int) uno( "SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = 'academy_base' AND EVENT_OBJECT_TABLE = 'rdacademy_console_registro' AND EVENT_MANIPULATION IN ('UPDATE','DELETE')" );
$prima = (int) uno( 'SELECT COUNT(*) FROM rdacademy_console_registro' ); $root->query( 'DELETE FROM rdacademy_console_registro' ); $dopo = (int) uno( 'SELECT COUNT(*) FROM rdacademy_console_registro' );
prova( 'G11 F42(c) registro append-only anche nel database: trigger contro UPDATE e DELETE', $tr === 2 && $prima > 0 && $dopo === $prima, "trigger $tr, righe $prima → $dopo" );
$B = "UPDATE rdacademy_users SET status = 'g12' WHERE id = 2"; $st2 = uno( 'SELECT status FROM rdacademy_users WHERE id = 2' );
chiedi( array( 'sql' => $B, 'consenti' => true, 'azione' => 'prova' ) );
$root->select_db( 'academy_base' ); $root->query( 'RENAME TABLE rdacademy_console_registro TO rdacademy_console_registro_v' ); $root->query( 'CREATE VIEW rdacademy_console_registro AS SELECT 1 AS id' );
$x = chiedi( array( 'sql' => $B, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
$root->query( 'DROP VIEW rdacademy_console_registro' ); $root->query( 'RENAME TABLE rdacademy_console_registro_v TO rdacademy_console_registro' );
prova( 'G12 registro «avvio» non scrivibile: la scrittura confermata non parte', $x['rossi'] > 0 && uno( 'SELECT status FROM rdacademy_users WHERE id = 2' ) === $st2 && $st2 !== 'g12', testo( $x ) );

$x = chiedi( array( 'sql' => 'SHOW CREATE VIEW rdacademy_vista' ) );
prova( 'G13 SHOW CREATE VIEW: il DEFINER (utente@host) non si mostra', count( $x['dati'] ) === 1 && strpos( json_encode( $x['dati'] ), 'banco' ) === false, json_encode( $x['dati'] ) );


// ── G bis. revisione a freddo F027 (2ª): UNION/WITH, apici negli errori, nomi accentati, sequenze, SHOW ENGINE, WordPress, tempo ──
$no = array();
foreach ( array( array( 'wordpress', 'SELECT 1,2,3 UNION SELECT * FROM wp_users' ), array( 'base', 'SELECT 1,2,3,4 UNION SELECT * FROM rdacademy_users' ), array( 'wordpress', 'WITH t(a,b,c) AS (SELECT * FROM wp_users) SELECT * FROM t' ), array( 'base', "WITH t(a,b,c,d,e,f,g,h,i,j,k) AS (SELECT * FROM information_schema.VIEWS) SELECT c,g FROM t WHERE b = 'academy_base'" ), array( 'wordpress', 'SELECT EXTRACTVALUE(1, CONCAT(0x7e, 0x27, (SELECT c FROM (SELECT 1 a,2 b,3 c UNION SELECT * FROM wp_users LIMIT 1 OFFSET 1) t)))' ) ) as $q ) {
	$x = chiedi( array( 'sorgente' => $q[0], 'sql' => $q[1] ) ); $j = json_encode( $x ); if ( strpos( $j, 'tok1' ) !== false || strpos( $j, 'segretissimo' ) !== false || strpos( $j, 'banco@' ) !== false || $x['dati'] ) $no[] = $q[1];
}
prova( 'G14 segreti non escono con UNION, colonne del WITH, information_schema.VIEWS (DEFINER) e sottoquery', ! $no, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => "SELECT EXTRACTVALUE(1, CONCAT(0x7e, 0x27, 'valore-riservato'))" ) );
prova( 'G15 errore con un apice nel valore: il valore non esce', $x['rossi'] > 0 && strpos( json_encode( $x ), 'valore-riservato' ) === false, testo( $x ) );
$x = chiedi( array( 'sql' => 'UPDATE archivìo.mi SET v = 9', 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => 'UPDATE vecchìa SET v = 9', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'G16 nomi accentati senza apici inversi: altro database e MyISAM rifiutati, niente scritto', $x['rossi'] > 0 && $x2['rossi'] > 0 && (int) uno( 'SELECT v FROM mi', 'archivìo' ) === 1 && (int) uno( 'SELECT v FROM `vecchìa`' ) === 1, testo( $x ) . ' / ' . testo( $x2 ) );
$x = chiedi( array( 'sql' => 'INSERT INTO rdacademy_cs (v) VALUES (1)', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'G17 tabella con DEFAULT NEXT VALUE FOR: rifiutata (la sequenza non si annulla)', $x['rossi'] > 0 && (int) uno( 'SELECT NEXT VALUE FOR rdacademy_seq' ) === 1, testo( $x ) );
$x = chiedi( array( 'sql' => 'SHOW ENGINE INNODB STATUS' ) );
prova( 'G18 SHOW ENGINE … STATUS rifiutata (utenti e istruzioni delle altre sessioni)', $x['rossi'] > 0 && ! $x['dati'], testo( $x ) );
$x = chiedi( array( 'sorgente' => 'sorgente_wp', 'sql' => "UPDATE wp_users SET user_login = 'preso'", 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sorgente' => 'sorgente_wp', 'sql' => 'UPDATE rdacademy_wpx SET v = 1', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'G19 sorgente nel database di WordPress: wp_users rifiutata, tabelle rdacademy_ ammesse', $x['rossi'] > 0 && stripos( testo( $x ), 'WordPress' ) !== false && $x2['rossi'] === 0 && uno( 'SELECT user_login FROM wp_users', 'wp_prova' ) === 'admin', testo( $x ) . ' / ' . testo( $x2 ) );
$T = 'INSERT INTO rdacademy_tm (v) SELECT @@max_statement_time';
chiedi( array( 'sql' => $T, 'consenti' => true, 'azione' => 'prova' ) ); chiedi( array( 'sql' => $T, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'G20 tempo massimo 10 s anche in scrittura, misurato dal server', (float) uno( 'SELECT v FROM rdacademy_tm' ) === 10.0, (string) uno( 'SELECT v FROM rdacademy_tm' ) );

// ── G ter. revisione a freddo F028 (3ª): INSERT…SELECT, chiave/valore, sql_mode, INNODB_TRX, byte, utente, TABLE, /*+, registro, lucchetti ──
$no = array();
foreach ( array( 'INSERT INTO rdacademy_note (a,b,c,d) SELECT * FROM rdacademy_users', 'REPLACE INTO rdacademy_note SELECT * FROM rdacademy_users', 'INSERT INTO rdacademy_note SELECT 1,2,3,4 UNION SELECT * FROM rdacademy_users', 'INSERT INTO rdacademy_note (a) SELECT t.email FROM (SELECT * FROM rdacademy_users) t' ) as $q ) {
	$x = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'prova' ) );
	$x2 = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
	if ( $x['rossi'] === 0 || $x2['rossi'] === 0 ) $no[] = $q;
}
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_note' ) );
prova( 'I1 i segreti non si copiano: INSERT/REPLACE … SELECT da tabelle con colonne segrete rifiutate, niente scritto', ! $no && (int) uno( 'SELECT COUNT(*) FROM rdacademy_note' ) === 0 && strpos( json_encode( $x ), 'tok1' ) === false, implode( ' | ', $no ) );
$no = array();
foreach ( array( array( 'base', 'SELECT * FROM rdacademy_impostazioni' ), array( 'base', "SELECT valore FROM rdacademy_impostazioni WHERE valore LIKE 'sk-%'" ), array( 'base', 'SELECT chiave, CONCAT(valore) AS v FROM rdacademy_impostazioni' ), array( 'base', 'SELECT UPPER(valore) FROM rdacademy_impostazioni' ), array( 'wordpress', 'SELECT option_name, option_value FROM wp_options' ), array( 'wordpress', 'SELECT * FROM wp_options' ), array( 'wordpress', 'SELECT option_value FROM wp_options' ) ) as $q ) {
	$j = json_encode( chiedi( array( 'sorgente' => $q[0], 'sql' => $q[1] ) ) );
	foreach ( array( 'pw-riservata-1', 'RISERVATO-IN-CHIARO', 'smtp-riservata', 'sale-riservato', 'smtp-zzz9', 'pw-zz8', 'chiave-auth-riservata' ) as $seg ) { if ( strpos( $j, $seg ) !== false ) { $no[] = $q[1] . ' → ' . $seg; } }
}
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_impostazioni' ) ); $j = json_encode( $x );
$x2 = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT option_name, option_value FROM wp_options' ) ); $j2 = json_encode( $x2 );
prova( 'I2 tabelle chiave/valore: il valore di una chiave segreta mascherato riga per riga, il nome della chiave e i valori innocui visibili', ! $no && strpos( $j, 'ai_api_key' ) !== false && strpos( $j, 'Academy-visibile' ) !== false && strpos( $j2, 'Sito-visibile' ) !== false && strpos( $j2, 'mailserver_pass' ) !== false, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => "SELECT chiave FROM rdacademy_impostazioni WHERE valore LIKE 'sk-%'" ) );
$x2 = chiedi( array( 'sorgente' => 'wordpress', 'sql' => "SELECT COUNT(*) FROM wp_options WHERE option_value LIKE 'smtp-r%'" ) );
prova( 'I3 tabelle chiave/valore: il valore non si usa nelle condizioni (non diventa un oracolo)', $x['rossi'] > 0 && ! $x['dati'] && $x2['rossi'] > 0 && ! $x2['dati'], testo( $x ) . ' / ' . testo( $x2 ) );
$mo = (string) uno( 'SELECT @@GLOBAL.sql_mode' );
$root->query( "SET GLOBAL sql_mode = 'ANSI_QUOTES,NO_BACKSLASH_ESCAPES'" );
$x = chiedi( array( 'sql' => "SELECT '\\', LOAD_FILE('/etc/hostname') AS f -- '" ) );
$x2 = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT CONCAT("user_pass") AS x FROM wp_users' ) );
$x3 = chiedi( array( 'sql' => "SELECT '\\', 1 AS a -- '" ) );
$root->query( "SET GLOBAL sql_mode = '" . $root->real_escape_string( $mo ) . "'" );
$h = trim( (string) @file_get_contents( '/etc/hostname' ) );
prova( 'I4 server con ANSI_QUOTES e NO_BACKSLASH_ESCAPES: la sessione della console li toglie, niente file né password', ( $h === '' || strpos( json_encode( $x ), $h ) === false ) && strpos( json_encode( $x2 ), 'segretissimo' ) === false && strpos( json_encode( $x3 ), '"a"' ) === false, testo( $x ) . ' / ' . testo( $x2 ) );
$o = @new mysqli( 'localhost', 'banco', 'banco', 'academy_base' );
$o->query( 'START TRANSACTION' ); $o->query( 'SELECT * FROM rdacademy_users' );
$o->query( "SELECT 'istruzione-riservata-trx', SLEEP(3) FROM rdacademy_users LIMIT 1", MYSQLI_ASYNC );
usleep( 300000 );
$x = chiedi( array( 'sql' => 'SELECT trx_query FROM information_schema.INNODB_TRX' ) );
$x2 = chiedi( array( 'sql' => 'SELECT * FROM information_schema.innodb_trx' ) );
$o->reap_async_query(); $o->query( 'ROLLBACK' ); $o->close();
prova( 'I5 information_schema.INNODB_TRX rifiutata (istruzioni delle altre sessioni)', $x['rossi'] > 0 && $x2['rossi'] > 0 && strpos( json_encode( array( $x, $x2 ) ), 'istruzione-riservata-trx' ) === false, testo( $x ) );
$no = array();
foreach ( array( 'SELECT * FROM information_schema.INNODB_LOCKS', 'SELECT * FROM information_schema.INNODB_LOCK_WAITS', 'SELECT * FROM performance_schema.data_locks', 'SHOW BINLOG EVENTS', "SHOW RELAYLOG EVENTS", 'SHOW EXPLAIN FOR 1', 'SHOW ANALYZE FOR 1', 'EXPLAIN FOR CONNECTION 1', 'SELECT * FROM information_schema.USER_ATTRIBUTES', 'SELECT GRANTOR FROM information_schema.ROLE_TABLE_GRANTS', 'SELECT * FROM information_schema.TABLE_PRIVILEGES', "SELECT WAIT_FOR_EXECUTED_GTID_SET('x', 5)", 'SELECT /*+ MAX_EXECUTION_TIME(3600000) */ 1', 'UPDATE /*+ SET_VAR(sql_mode=\'ANSI_QUOTES\') */ rdacademy_s3_dati SET v = 1' ) as $q ) {
	if ( $C::classifica( $q )['ok'] ) $no[] = $q;
}
prova( 'I6 rifiutati dal server: INNODB_LOCKS e simili, SHOW BINLOG/RELAYLOG EVENTS, SHOW EXPLAIN/ANALYZE FOR, EXPLAIN FOR CONNECTION, permessi e ruoli, attese GTID, suggerimenti /*+', ! $no, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => "SELECT REPEAT('x', 100000) AS r FROM seq_1_to_200" ) );
$by = strlen( json_encode( $x['dati'] ) );
prova( 'I7 tetto in byte: un risultato di 20 MB si tronca sotto i 6 MB e lo dichiara', $by < 6000000 && stripos( testo( $x ), 'troncat' ) !== false, $by . ' byte' );
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_data_sources' ) );
prova( 'I8 utente della sorgente: solo come presenza (db_user mascherato)', strpos( json_encode( $x ), 'utente_della_sorgente' ) === false && $x['dati'], testo( $x ) );
$x = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT CONCAT(c) FROM (TABLE wp_users) AS t(a,b,c)' ) );
$x2 = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT * FROM wp_users, LATERAL (SELECT 1) AS z' ) );
prova( 'I9 MySQL 8: TABLE e LATERAL nelle sottoquery su tabelle con segreti rifiutati dalla console (non dal server)', stripos( testo( $x ), 'colonne segrete' ) !== false && stripos( testo( $x2 ), 'colonne segrete' ) !== false, testo( $x ) . ' / ' . testo( $x2 ) );
$x = chiedi( array( 'sql' => "UPDATE rdacademy_users SET status = 'active' WHERE id = 0", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'I10 registro: la prova a secco dichiara se è protetto da trigger o solo per nome', (bool) preg_match( '/Registro: (protetto da trigger|protetto solo per nome)/', testo( $x ) ), testo( $x ) );
$T = 'INSERT INTO rdacademy_tl (v) SELECT @@innodb_lock_wait_timeout';
chiedi( array( 'sql' => $T, 'consenti' => true, 'azione' => 'prova' ) ); chiedi( array( 'sql' => $T, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
prova( 'I11 in scrittura anche l attesa dei lucchetti ha il tetto di 10 s (vale anche su MySQL 8, dove max_statement_time non c è)', (float) uno( 'SELECT v FROM rdacademy_tl' ) === 10.0, (string) uno( 'SELECT v FROM rdacademy_tl' ) );

// ── G quater. revisione a freddo F028 (4ª, sulla 0.1.13A): caratteri di controllo, join e UPDATE su più tabelle chiave/valore, chiave rinominata, errore in lettura ──
$h = trim( (string) @file_get_contents( '/etc/hostname' ) );
$no = array();
foreach ( array( "\x01", "\x02", "\x1f", "\x7f" ) as $cc ) {
	$x = chiedi( array( 'sorgente' => 'wordpress', 'sql' => "SELECT 1 --{$cc} '\n, CONCAT(user_pass) AS x FROM wp_users -- '" ) );
	$x2 = chiedi( array( 'sql' => "SELECT 1 --{$cc} '\n, LOAD_FILE('/etc/hostname') AS f -- '" ) );
	$j = json_encode( array( $x, $x2 ) );
	if ( strpos( $j, 'segretissimo' ) !== false || ( $h !== '' && strpos( $j, $h ) !== false ) || $x['rossi'] === 0 || $x2['rossi'] === 0 ) $no[] = bin2hex( $cc );
}
prova( 'J1 «--» seguito da un carattere di controllo (\x01-\x1f, \x7f): istruzione rifiutata, niente password né file', ! $no, 'passano: ' . implode( ', ', $no ) );
$W2 = "UPDATE rdacademy_note SET a = 'j1' --\x01 '\n, b = (SELECT COUNT(*) FROM wp_prova.wp_users) -- '";
$x = chiedi( array( 'sql' => $W2, 'consenti' => true, 'azione' => 'prova' ) );
$c = $C::classifica( "SELECT 1 -- commento con tab\t e spazio" ); $c2 = $C::classifica( "SELECT 1\t,\r\n 2" );
prova( 'J2 caratteri di controllo rifiutati anche in scrittura e dal classificatore; tab, a capo e spazio restano ammessi', $x['rossi'] > 0 && stripos( testo( $x ), 'controllo' ) !== false && $c['ok'] && $c2['ok'], testo( $x ) . ' / ' . json_encode( array( $c['ok'], $c2['ok'] ) ) );
$no = array();
foreach ( array( array( 'wordpress', "SELECT a.option_name, b.option_value FROM wp_options a, wp_options b WHERE a.option_name = 'blogname'" ), array( 'wordpress', "SELECT a.option_name, b.option_value FROM wp_options a JOIN wp_options b ON a.option_id = 1" ), array( 'base', 'SELECT a.chiave, b.valore FROM rdacademy_impostazioni a CROSS JOIN rdacademy_impostazioni b WHERE a.id = 3' ), array( 'base', 'SELECT b.chiave AS k, a.valore FROM rdacademy_impostazioni a STRAIGHT_JOIN rdacademy_impostazioni b' ) ) as $q ) {
	$j = json_encode( chiedi( array( 'sorgente' => $q[0], 'sql' => $q[1] ) ) );
	foreach ( array( 'pw-riservata-1', 'RISERVATO-IN-CHIARO', 'smtp-riservata', 'sale-riservato', 'smtp-zzz9', 'pw-zz8', 'chiave-auth-riservata' ) as $seg ) { if ( strpos( $j, $seg ) !== false ) { $no[] = $q[1] . ' → ' . $seg; } }
}
$x = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT o.option_name, o.option_value FROM wp_options o' ) );
prova( 'J3 tabelle chiave/valore unite con sé stesse: ogni valore resta legato alla chiave della sua riga (mascherato se segreto), i valori innocui visibili', ! $no && strpos( json_encode( $x ), 'Sito-visibile' ) !== false && strpos( json_encode( $x ), 'smtp-riservata' ) === false, implode( ' | ', $no ) );
uno( "INSERT INTO rdacademy_note (a, b, c, d) VALUES ('n1', 'n1', 'n1', 'n1')" );
$no = array();
foreach ( array( 'UPDATE rdacademy_note n, rdacademy_impostazioni i SET n.a = i.valore', 'UPDATE rdacademy_note n JOIN rdacademy_impostazioni i SET n.b = i.valore', 'UPDATE rdacademy_note n, rdacademy_users u SET n.c = u.email', 'DELETE n FROM rdacademy_note n, rdacademy_impostazioni i' ) as $q ) {
	$x = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'prova' ) );
	$x2 = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
	if ( $x['rossi'] === 0 || $x2['rossi'] === 0 ) $no[] = $q;
}
$j = json_encode( $root->query( 'SELECT * FROM academy_base.rdacademy_note' )->fetch_all() );
prova( 'J4 UPDATE e DELETE su più tabelle con tabelle segrete o chiave/valore: rifiutati, niente copiato', ! $no && strpos( $j, 'RISERVATO' ) === false && strpos( $j, 'pw-riservata' ) === false && strpos( $j, 'n1' ) !== false, implode( ' | ', $no ) . ' / ' . $j );
$no = array();
foreach ( array( "UPDATE rdacademy_impostazioni SET chiave = 'innocua' WHERE chiave = 'ai_api_key'", "UPDATE rdacademy_impostazioni SET `chiave` = CONCAT('x', id) WHERE id = 2", "UPDATE rdacademy_impostazioni i SET i.chiave = 'innocua2' WHERE i.id = 1", "INSERT INTO rdacademy_impostazioni (id, chiave, valore) VALUES (2, 'z', 'z') ON DUPLICATE KEY UPDATE chiave = 'innocua3'" ) as $q ) {
	$x = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'prova' ) );
	$x2 = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) );
	if ( $x['rossi'] === 0 || $x2['rossi'] === 0 ) $no[] = $q;
}
$x = chiedi( array( 'sql' => 'SELECT chiave, valore FROM rdacademy_impostazioni' ) );
prova( 'J5 la chiave di una tabella chiave/valore non si cambia (rinominarla smaschererebbe il valore)', ! $no && strpos( json_encode( $x ), 'RISERVATO-IN-CHIARO' ) === false && strpos( json_encode( $x ), 'pw-riservata-1' ) === false && (int) uno( "SELECT COUNT(*) FROM rdacademy_impostazioni WHERE chiave IN ('ai_api_key','mail_servizio_password')" ) === 2, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => 'SELECT id, EXP(IF(id = 3, 1000, 0)) AS e FROM rdacademy_users ORDER BY id' ) );
prova( 'J6 errore durante la lettura (a metà dei dati): ROSSO e nessun dato parziale', $x['rossi'] > 0 && $x['verdi'] === 0 && ! $x['dati'], testo( $x ) . ' / ' . count( $x['dati'] ) . ' righe' );
$no = array();
foreach ( array( "SELECT chiave, EXP(IF(valore LIKE 'sk-%', 1000, 0)) FROM rdacademy_impostazioni", 'SELECT chiave, LENGTH(valore) FROM rdacademy_impostazioni', "SELECT chiave, valore + 0 AS v FROM rdacademy_impostazioni", "SELECT chiave, CASE WHEN valore LIKE 'sk%' THEN 1 END FROM rdacademy_impostazioni" ) as $q ) { $x = chiedi( array( 'sql' => $q ) ); if ( $x['rossi'] === 0 || $x['dati'] ) $no[] = $q; }
$x = chiedi( array( 'sql' => 'SELECT chiave, valore AS v, id FROM rdacademy_impostazioni' ) ); $x2 = chiedi( array( 'sql' => 'SELECT i.chiave, i.valore FROM rdacademy_impostazioni i' ) );
prova( 'J7 il valore delle tabelle chiave/valore si legge solo così com è (niente espressioni: non diventa un oracolo), con o senza alias', ! $no && $x['rossi'] === 0 && strpos( json_encode( $x ), 'Academy-visibile' ) !== false && $x2['rossi'] === 0, implode( ' | ', $no ) . ' / ' . testo( $x ) );
$c = $C::classifica( 'SELECT role_name, role_id FROM rdacademy_ruoli' ); $c2 = $C::classifica( 'SELECT * FROM information_schema.ROLE_TABLE_GRANTS' );
prova( 'J8 F45(a) «role_name» è una colonna qualunque; ROLE_TABLE_GRANTS resta rifiutata', $c['ok'] && ! $c2['ok'], json_encode( array( $c['motivo'], $c2['motivo'] ) ) );
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_users ORDER BY 4' ) ); $x2 = chiedi( array( 'sql' => 'SELECT status, COUNT(*) FROM rdacademy_users GROUP BY 1' ) );
$x3 = chiedi( array( 'sql' => "INSERT INTO rdacademy_users VALUES (50, 'j9@esempio.it', 'active', 'tok-noto')", 'consenti' => true, 'azione' => 'prova' ) );
$x4 = chiedi( array( 'sql' => "INSERT INTO rdacademy_users (email, status) VALUES ('j9@esempio.it', 'active')", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'J9 F45(b) tabelle con segreti: niente ORDER/GROUP BY per posizione né INSERT senza elenco di colonne; con le colonne innocue sì', $x['rossi'] > 0 && ! $x['dati'] && $x2['rossi'] > 0 && $x3['rossi'] > 0 && $x4['rossi'] === 0, testo( $x ) . ' / ' . testo( $x3 ) . ' / ' . testo( $x4 ) );

$x = chiedi( array( 'sql' => "UPDATE rdacademy_impostazioni SET valore = IF(valore LIKE 'sk%', valore, 'x') WHERE chiave = 'ai_api_key'", 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => "UPDATE rdacademy_impostazioni SET valore = 'Academy-visibile' WHERE chiave = 'titolo'", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'J10 in scrittura il valore chiave/valore si assegna e basta: «righe che cambierebbero» non diventa un oracolo', $x['rossi'] > 0 && ! isset( $x['righe_prova'] ) && $x2['rossi'] === 0, testo( $x ) . ' / ' . testo( $x2 ) );

// ── G quinquies. revisione a freddo F029 (5ª, sulla 0.1.14A): chiave con := e nome a tre parti, HAVING/ORDER BY sull alias del valore, posizioni con OFFSET, INSERT … SET, SHOW ALL SLAVES ──
$no = array();
foreach ( array( "UPDATE rdacademy_impostazioni SET chiave := 'innocua' WHERE id = 1", "UPDATE rdacademy_impostazioni SET academy_base.rdacademy_impostazioni.chiave = 'innocua2' WHERE id = 2", "INSERT INTO rdacademy_impostazioni (id, chiave, valore) VALUES (2, 'z', 'z') ON DUPLICATE KEY UPDATE chiave := 'innocua1'", "UPDATE rdacademy_impostazioni SET valore := 'k1' WHERE chiave = 'titolo'" ) as $i => $q ) {
	$x = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'prova' ) );
	if ( $i < 3 ? $x['rossi'] === 0 : $x['rossi'] > 0 ) $no[] = $q;
	if ( $i < 3 ) { $x2 = chiedi( array( 'sql' => $q, 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) ); if ( $x2['rossi'] === 0 ) $no[] = $q; }
}
prova( 'K1 la chiave non si cambia nemmeno con «:=» o col nome a tre parti; il valore si assegna anche con «:=»', ! $no && (int) uno( "SELECT COUNT(*) FROM rdacademy_impostazioni WHERE chiave IN ('ai_api_key','mail_servizio_password')" ) === 2, implode( ' | ', $no ) );
$no = array();
foreach ( array( array( 'base', "SELECT chiave, valore AS x FROM rdacademy_impostazioni HAVING x LIKE 'pw-r%'" ), array( 'wordpress', "SELECT option_name, option_value x FROM wp_options HAVING x LIKE 'smtp%'" ), array( 'base', 'SELECT chiave, valore AS x FROM rdacademy_impostazioni ORDER BY x' ), array( 'base', 'SELECT * FROM rdacademy_impostazioni ORDER BY valore' ), array( 'base', 'SELECT * FROM rdacademy_impostazioni ORDER BY 3 OFFSET 0 ROWS' ), array( 'base', 'SELECT * FROM rdacademy_users ORDER BY 4 DESC OFFSET 0 ROWS' ), array( 'base', 'SELECT * FROM rdacademy_users GROUP BY 4 ORDER BY NULL' ), array( 'base', 'SELECT i.* FROM rdacademy_impostazioni i ORDER BY i.id' ) ) as $q ) { $x = chiedi( array( 'sorgente' => $q[0], 'sql' => $q[1] ) ); if ( $x['rossi'] === 0 || $x['dati'] ) $no[] = $q[1]; }
$x = chiedi( array( 'sql' => 'SELECT chiave FROM rdacademy_impostazioni ORDER BY chiave' ) ); $x2 = chiedi( array( 'sql' => 'SELECT id, email FROM rdacademy_users ORDER BY email LIMIT 10, 5' ) );
prova( 'K2 col valore chiave/valore nel risultato niente HAVING, ORDER BY né GROUP BY; posizioni rifiutate anche con OFFSET; ordinare per chiave o colonne innocue sì', ! $no && $x['rossi'] === 0 && $x2['rossi'] === 0, implode( ' | ', $no ) . ' / ' . testo( $x ) . ' / ' . testo( $x2 ) );
$x = chiedi( array( 'sql' => "INSERT INTO rdacademy_impostazioni SET chiave = 'nuova_k3', valore = 'v'", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'K3 INSERT … SET su una tabella chiave/valore: è una riga nuova, non un cambio di chiave', $x['rossi'] === 0, testo( $x ) );
$c = $C::classifica( 'SHOW ALL SLAVES STATUS' ); $c2 = $C::classifica( 'SHOW ALL REPLICAS STATUS' );
prova( 'K4 SHOW ALL SLAVES/REPLICAS STATUS rifiutate (utente e host della replica)', ! $c['ok'] && ! $c2['ok'], json_encode( array( $c['ok'], $c2['ok'] ) ) );

// ── H. la porta AJAX chiamata davvero (manage_options, nonce, multisite) e l interfaccia ──

// ── G sexies. revisione a freddo F030 (6ª, sulla 0.1.14A finale): PROCEDURE ANALYSE e AUTO_INCREMENT della prova a secco ──
$no = array();
foreach ( array( array( 'base', 'SELECT * FROM rdacademy_impostazioni PROCEDURE ANALYSE()' ), array( 'base', 'SELECT valore FROM rdacademy_impostazioni PROCEDURE ANALYSE(10, 2000)' ), array( 'base', 'SELECT * FROM rdacademy_users PROCEDURE  ANALYSE ( )' ), array( 'wordpress', 'SELECT * FROM wp_users PROCEDURE ANALYSE()' ), array( 'wordpress', 'SELECT * FROM wp_options /* x */ PROCEDURE ANALYSE()' ), array( 'wordpress', 'SELECT user_login FROM wp_users PROCEDURE ANALYSE()' ), array( 'base', 'SELECT * FROM rdacademy_users PROCEDURE `ANALYSE`()' ) ) as $q ) {
	$x = chiedi( array( 'sorgente' => $q[0], 'sql' => $q[1] ) ); $j = json_encode( $x );
	if ( $x['rossi'] === 0 || $x['dati'] || preg_match( '/pw-riservata|sk-RISERVATO|tok[0-9]|segretissimo|smtp-riservata|sale-riservato|smtp-zzz9/', $j ) ) $no[] = $q[1];
}
$x = chiedi( array( 'sql' => 'SELECT chiave FROM rdacademy_impostazioni ORDER BY chiave' ) );
prova( 'L1 PROCEDURE ANALYSE() rifiutata ovunque: Min_value e Max_value mostrerebbero i valori segreti; le letture ordinarie restano', ! $no && $x['rossi'] === 0, implode( ' | ', $no ) . ' / ' . testo( $x ) );
function prossimo_ai( string $t ): int { global $root; $root->select_db( 'academy_base' ); $r = $root->query( "SHOW CREATE TABLE `$t`" )->fetch_row()[1]; return preg_match( '/AUTO_INCREMENT=(\d+)/', $r, $m ) ? (int) $m[1] : 1; }
$a0 = prossimo_ai( 'rdacademy_tm' ); $u0 = prossimo_ai( 'rdacademy_users' );
$x = chiedi( array( 'sql' => 'INSERT INTO rdacademy_tm (v) VALUES (1), (2), (3)', 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => "INSERT INTO rdacademy_users SET email = 'l2@esempio.it', status = 'active'", 'consenti' => true, 'azione' => 'prova' ) );
$a1 = prossimo_ai( 'rdacademy_tm' ); $u1 = prossimo_ai( 'rdacademy_users' );
prova( 'L2 la prova a secco non lascia effetti: AUTO_INCREMENT di nuovo com era dopo l annullamento', $x['rossi'] === 0 && $x2['rossi'] === 0 && $a1 === $a0 && $u1 === $u0 && ( $x['righe_prova'] ?? -1 ) === 3, "tm $a0 → $a1 · users $u0 → $u1 · " . testo( $x ) );

$x = chiedi( array( 'sql' => "UPDATE rdacademy_impostazioni SET valore = 'pw-riservata-1' WHERE chiave = 'mail_servizio_password'", 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => "UPDATE rdacademy_impostazioni SET valore = 'indovinato-male' WHERE chiave = 'mail_servizio_password'", 'consenti' => true, 'azione' => 'prova' ) );
$x3 = chiedi( array( 'sql' => "UPDATE rdacademy_impostazioni SET valore = 'pw-riservata-1' WHERE id = 1", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'L3 F46(a) la prova a secco conta le righe trovate: valore indovinato giusto o sbagliato danno lo stesso numero (niente oracolo d uguaglianza)', $x['rossi'] === 0 && $x2['rossi'] === 0 && $x3['rossi'] === 0 && ( $x['righe_prova'] ?? -1 ) === 1 && ( $x2['righe_prova'] ?? -2 ) === 1 && ( $x3['righe_prova'] ?? -3 ) === 1 && (int) uno( "SELECT COUNT(*) FROM rdacademy_impostazioni WHERE valore = 'indovinato-male'" ) === 0, json_encode( array( $x['righe_prova'] ?? null, $x2['righe_prova'] ?? null, $x3['righe_prova'] ?? null ) ) );

// ── G septies. revisione a freddo F030 (7ª, sulla 0.1.15A prima stesura) ──
$x = chiedi( array( 'sql' => "INSERT INTO rdacademy_impostazioni (id, chiave) VALUES (1, 'mail_servizio_password') ON DUPLICATE KEY UPDATE valore = 'pw-riservata-1'", 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => "INSERT INTO rdacademy_impostazioni (id, chiave) VALUES (1, 'mail_servizio_password') ON DUPLICATE KEY UPDATE valore := 'x'", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'L4 ON DUPLICATE KEY UPDATE su una tabella chiave/valore rifiutato (il conteggio 0/2 direbbe se il valore è giusto)', $x['rossi'] > 0 && ! isset( $x['righe_prova'] ) && $x2['rossi'] > 0, testo( $x ) );
$no = array();
foreach ( array( "SELECT DISTINCT valore FROM rdacademy_impostazioni WHERE chiave IN ('mail_servizio_password','titolo')", "SELECT DISTINCTROW valore FROM rdacademy_impostazioni", "SELECT DISTINCT * FROM rdacademy_impostazioni" ) as $q ) { $x = chiedi( array( 'sql' => $q ) ); if ( $x['rossi'] === 0 || $x['dati'] ) $no[] = $q; }
$x = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT DISTINCT option_value FROM wp_options' ) ); if ( $x['rossi'] === 0 || $x['dati'] ) $no[] = 'wp_options DISTINCT';
prova( 'L5 DISTINCT e DISTINCTROW rifiutati sulle tabelle chiave/valore (il numero di righe direbbe se due valori sono uguali)', ! $no, implode( ' | ', $no ) );
$root->select_db( 'academy_base' ); $root->query( 'DROP TABLE IF EXISTS rdacademy_pref' );
$root->query( 'CREATE TABLE rdacademy_pref (id INT PRIMARY KEY, chiave VARCHAR(100), valore LONGTEXT) ENGINE=InnoDB' );
$root->query( "INSERT INTO rdacademy_pref VALUES (1, 'mail_servizio_password', 'pw-riservata-1')" );
$no = array();
foreach ( array( 'SELECT chiave FROM rdacademy_impostazioni NATURAL JOIN rdacademy_pref', 'SELECT i.chiave FROM rdacademy_impostazioni i JOIN rdacademy_pref p USING (valore)', 'SELECT i.chiave FROM rdacademy_impostazioni i NATURAL LEFT JOIN rdacademy_pref p', 'SELECT u.email FROM rdacademy_users u JOIN rdacademy_users v USING (api_token)' ) as $q ) { $x = chiedi( array( 'sql' => $q ) ); if ( $x['rossi'] === 0 || $x['dati'] ) $no[] = $q; }
prova( 'L6 NATURAL JOIN e JOIN … USING rifiutati sulle tabelle chiave/valore o con segreti (confronterebbero i valori senza nominarli)', ! $no, implode( ' | ', $no ) );
$root->query( 'DROP TABLE IF EXISTS rdacademy_Lezioni' ); $root->query( 'CREATE TABLE rdacademy_Lezioni (id INT PRIMARY KEY AUTO_INCREMENT, titolo VARCHAR(40)) ENGINE=InnoDB' ); $root->query( "INSERT INTO rdacademy_Lezioni (titolo) VALUES ('a'), ('b')" );
$a0 = prossimo_ai( 'rdacademy_Lezioni' );
$x = chiedi( array( 'sql' => "INSERT INTO rdacademy_Lezioni (titolo) VALUES ('L7')", 'consenti' => true, 'azione' => 'prova' ) );
$a1 = prossimo_ai( 'rdacademy_Lezioni' );
prova( 'L7 contatori rimessi anche per le tabelle con maiuscole nel nome, e il referto dice il vero', $x['rossi'] === 0 && $a1 === $a0 && stripos( testo( $x ), 'rimessi com erano' ) !== false, "$a0 → $a1 · " . testo( $x ) );
$x = chiedi( array( 'sql' => "UPDATE rdacademy_impostazioni SET valore = 'NUOVO-SEGRETO-L8' WHERE id = 1", 'consenti' => true, 'azione' => 'prova' ) );
$nr = (int) uno( "SELECT COUNT(*) FROM rdacademy_console_registro WHERE istruzione LIKE '%NUOVO-SEGRETO-L8%'" );
$nt = (int) uno( "SELECT COUNT(*) FROM rdacademy_console_registro WHERE istruzione LIKE '%rdacademy_impostazioni%'" );
prova( 'L8 il registro non conserva in chiaro i valori scritti fra apici (ogni amministratore lo legge): resta l istruzione, il valore diventa «…»', $x['rossi'] === 0 && $nr === 0 && $nt > 0, "in chiaro $nr · istruzioni registrate $nt" );
$c = $C::classifica( 'SHOW  PROCEDURE STATUS' ); $c2 = $C::classifica( "SHOW\n\tPROCEDURE STATUS" ); $c3 = $C::classifica( 'SHOW CREATE  PROCEDURE p1' ); $c4 = $C::classifica( 'SELECT 1 FROM rdacademy_users PROCEDURE ANALYSE()' );
prova( 'L9 SHOW PROCEDURE STATUS e SHOW CREATE PROCEDURE ammessi con qualunque spaziatura; PROCEDURE ANALYSE resta rifiutata', $c['ok'] && $c2['ok'] && $c3['ok'] && ! $c4['ok'], json_encode( array( $c['ok'], $c2['ok'], $c3['ok'], $c4['ok'] ) ) );

final class Fine_Json extends Exception { public $dati; public $ok; public $cod; function __construct( $ok, $d, $c ) { $this->ok = $ok; $this->dati = $d; $this->cod = $c; parent::__construct( 'json' ); } }
$GLOBALS['wp_cap'] = true; $GLOBALS['wp_multi'] = false; $GLOBALS['wp_super'] = false;
function current_user_can( $c ) { return $c === 'manage_options' && $GLOBALS['wp_cap']; }
function is_multisite() { return $GLOBALS['wp_multi']; }
function is_super_admin( $u = null ) { return $GLOBALS['wp_super']; }
function check_ajax_referer( $a, $k ) { if ( ( $_POST[ $k ] ?? '' ) !== 'nonce-buono-' . $a ) throw new Fine_Json( false, 'nonce', 403 ); return 1; }
function wp_send_json_error( $d = null, $c = null ) { throw new Fine_Json( false, $d, $c ); }
function wp_send_json_success( $d = null, $c = null ) { throw new Fine_Json( true, $d, $c ); }
function wp_get_current_user() { return (object) array( 'ID' => 7, 'user_login' => 'idea-tecnico' ); }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function wp_unslash( $v ) { return $v; }
function add_action() {} function wp_create_nonce( $a ) { return 'nonce-buono-' . $a; } function admin_url( $p = '' ) { return '/wp-admin/' . $p; }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } function esc_url( $s ) { return (string) $s; }
require $fb;
function porta( array $p ) { $_POST = $p + array( 'action' => 'rdacademy_console_sql', 'nonce' => 'nonce-buono-rdacademy_console_sql', 'sorgente' => 'base', 'consenti' => '0', 'azione' => 'esegui', 'conferma' => '' ); try { Rdacademy_Banco_Console_SQL::ajax(); } catch ( Fine_Json $e ) { return $e; } return null; }
$n0 = (int) uno( 'SELECT COUNT(*) FROM rdacademy_console_registro' );
$GLOBALS['wp_cap'] = false; $e = porta( array( 'sql' => 'SELECT 1' ) ); $GLOBALS['wp_cap'] = true;
prova( 'H1 porta AJAX vera: senza manage_options risposta 403 e nessuna istruzione arriva al motore', $e && ! $e->ok && $e->cod === 403 && (int) uno( 'SELECT COUNT(*) FROM rdacademy_console_registro' ) === $n0 );
$e = porta( array( 'sql' => 'SELECT 1', 'nonce' => 'falso' ) );
prova( 'H2 porta AJAX vera: nonce sbagliato rifiutato prima del motore', $e && ! $e->ok && (int) uno( 'SELECT COUNT(*) FROM rdacademy_console_registro' ) === $n0 );
$GLOBALS['wp_multi'] = true; $e = porta( array( 'sql' => 'SELECT 1' ) ); $GLOBALS['wp_multi'] = false;
prova( 'H3 F42(e) multisite: l amministratore di un sito che non è super amministratore non interroga il server', $e && ! $e->ok && $e->cod === 403 );
$e = porta( array( 'sql' => 'SELECT 41 + 1 AS r' ) );
prova( 'H4 porta AJAX vera: una lettura arriva al motore e torna coi dati', $e && $e->ok && ( $e->dati['dati'][0][0] ?? '' ) == 42 );
$e = porta( array( 'sql' => $W, 'consenti' => '1', 'azione' => 'prova' ) ); $e2 = porta( array( 'sql' => $W, 'consenti' => '1', 'conferma' => 'CONFERMO' ) );
prova( 'H5 porta AJAX vera: UPDATE wp_prova.wp_users dal BASE rifiutato anche con CONFERMO', $e && ( $e->dati['rossi'] ?? 0 ) > 0 && $e2 && ( $e2->dati['rossi'] ?? 0 ) > 0 && uno( 'SELECT user_login FROM wp_users', 'wp_prova' ) === 'admin' );
ob_start(); Rdacademy_Banco_Console_SQL::render( 'cat-database' ); $html = ob_get_clean();
prova( 'H6 F42(d) interfaccia del mockup: «Esegui · N righe» al CONFERMO; in scrittura solo «Prova a secco» e «Pulisci»', strpos( $html, 'Esegui · ' ) !== false && strpos( $html, 'data-scrittura="no"' ) !== false && substr_count( $html, 'data-scrittura="no"' ) === 3 );

// ── M. ottava revisione a freddo (F031, sulla 0.1.15A: 5 alti, 6 medi) ─────────────────────────────────────────
$root->select_db( 'academy_s3' );
$root->query( "CREATE TABLE rdacademy_impostazioni (id INT PRIMARY KEY AUTO_INCREMENT, chiave VARCHAR(100), valore LONGTEXT) ENGINE=InnoDB" );
$root->query( "INSERT INTO rdacademy_impostazioni (chiave, valore) VALUES ('colore_tema','blu')" );
$root->select_db( 'academy_base' );
$root->query( "CREATE TABLE `rdacademy_UTENTI_È` (id INT PRIMARY KEY, login VARCHAR(40), api_token VARCHAR(64)) ENGINE=InnoDB" );
$root->query( "INSERT INTO `rdacademy_UTENTI_È` VALUES (1,'elsa','TOKEN-ELSA-ACCENTO')" );
$root->query( "CREATE TABLE `rdacademy_CITTÀ_my` (id INT PRIMARY KEY AUTO_INCREMENT, nome VARCHAR(60)) ENGINE=MyISAM" );
$root->query( "CREATE FUNCTION `rdacademy-g`() RETURNS INT MODIFIES SQL DATA BEGIN INSERT INTO rdacademy_vecchia VALUES (77,77); RETURN 1; END" );
$root->query( "CREATE FUNCTION rdacademy_scrivi() RETURNS INT MODIFIES SQL DATA BEGIN INSERT INTO rdacademy_vecchia VALUES (78,78); RETURN 1; END" );
$root->query( "CREATE FUNCTION rdacademy_fleggi() RETURNS VARCHAR(64) READS SQL DATA RETURN (SELECT api_token FROM rdacademy_users WHERE id = 1)" );
$root->query( "CREATE VIEW rdacademy_v_ut AS SELECT id, email, api_token AS t FROM rdacademy_users" );
$no = array();
foreach ( array( 'SELECT ID, USER, HOST, INFO FROM information_schema.PROCESSLİST', 'SELECT trx_query FROM information_schema.İNNODB_TRX', "SELECT * FROM information_schema.INNODB_LOC\u{212A}S", 'SELECT * FROM information_schema.USER_PRİVİLEGES' ) as $s ) { $x = chiedi( array( 'sql' => $s ) ); if ( $x['rossi'] === 0 || $x['dati'] ) $no[] = $s; }
prova( 'M1 (alto) tabelle di sistema vietate scritte con İ o K: rifiutate come col nome in ASCII', ! $no, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => "SELECT academy_s3.rdacademy_impostazioni.chiave, academy_base.rdacademy_impostazioni.valore FROM academy_base.rdacademy_impostazioni, academy_s3.rdacademy_impostazioni WHERE academy_base.rdacademy_impostazioni.chiave = 'mail_servizio_password'" ) );
$x2 = chiedi( array( 'sql' => "SELECT academy_s3.x.chiave, academy_base.x.valore FROM academy_base.rdacademy_impostazioni x, academy_s3.rdacademy_impostazioni x WHERE academy_base.x.chiave = 'ai_api_key'" ) );
prova( 'M2 (alto) chiave/valore: la tabella gemella di un altro database (stesso nome o stesso alias) non smaschera il valore', strpos( json_encode( $x ), 'pw-riservata-1' ) === false && strpos( json_encode( $x2 ), 'sk-RISERVATO' ) === false, testo( $x ) . ' / ' . testo( $x2 ) );
$x = chiedi( array( 'sql' => 'SELECT 1,2,3 UNION SELECT * FROM `rdacademy_UTENTI_È`' ) );
$x2 = chiedi( array( 'sql' => "INSERT INTO `rdacademy_CITTÀ_my` (nome) VALUES ('rimasta')", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'M3 (alto) tabelle con maiuscole accentate: niente segreti con UNION, niente prova a secco su MyISAM', strpos( json_encode( $x ), 'TOKEN-ELSA' ) === false && $x2['rossi'] > 0 && (int) uno( 'SELECT COUNT(*) FROM `rdacademy_CITTÀ_my`' ) === 0, testo( $x ) . ' / ' . testo( $x2 ) );
$x = chiedi( array( 'sql' => 'INSERT INTO rdacademy_tm (v) VALUES (`rdacademy-g`())', 'consenti' => true, 'azione' => 'prova' ) );
$x2 = chiedi( array( 'sql' => 'INSERT INTO rdacademy_tm (v) VALUES (rdacademy_scrİvi())', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'M4 (alto) funzioni memorizzate col nome fra apici inversi o con İ: la prova a secco le rifiuta, niente scritto', $x['rossi'] > 0 && $x2['rossi'] > 0 && (int) uno( 'SELECT COUNT(*) FROM rdacademy_vecchia WHERE id IN (77,78)' ) === 0, testo( $x ) . ' / ' . testo( $x2 ) );
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_v_ut' ) ); $x2 = chiedi( array( 'sql' => 'SELECT rdacademy_fleggi()' ) ); $x3 = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_vista' ) );
prova( 'M5 (alto) una vista o una funzione che rinomina una colonna segreta non la mostra; la vista innocua si legge', strpos( json_encode( $x ), 'tok1' ) === false && strpos( json_encode( $x2 ), 'tok1' ) === false && $x3['rossi'] === 0, testo( $x ) . ' / ' . testo( $x2 ) . ' / ' . testo( $x3 ) );
$no = array();
foreach ( array( "SELECT chiave, ELT(LOCATE('pw-ris', valore, 1), 'x') AS o FROM rdacademy_impostazioni WHERE chiave = 'mail_servizio_password'", "SELECT chiave, EXP(710 * LOCATE('pw-ris', valore, 1)) AS o FROM rdacademy_impostazioni WHERE chiave = 'mail_servizio_password'", "SELECT chiave, ELT(FIELD('pw-riservata-1', valore, ''), 1) FROM rdacademy_impostazioni WHERE chiave = 'mail_servizio_password'" ) as $s ) { $x = chiedi( array( 'sql' => $s ) ); if ( $x['rossi'] === 0 || strpos( testo( $x ), 'così com è' ) === false ) $no[] = $s; }
prova( 'M6 (medio) chiave/valore in lettura: il valore non entra come argomento di una funzione (oracolo NULL, errore, tempo)', ! $no, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => "UPDATE rdacademy_impostazioni SET id = id + 0 * IF(1, valore = 'pw-riservata-1', 0) WHERE chiave = 'mail_servizio_password'", 'consenti' => true, 'azione' => 'prova' ) );
prova( 'M7 (medio) prova a secco: «, valore =» dentro una funzione non è un assegnazione (oracolo d uguaglianza)', $x['rossi'] > 0 && strpos( testo( $x ), 'solo assegnare' ) !== false, testo( $x ) );
$no = array();
foreach ( array( 'SELECT * FROM rdacademy_users ORDER BY (4)', 'SELECT * FROM rdacademy_users ORDER BY ((4)) DESC', 'SELECT * FROM rdacademy_users ORDER BY +4', 'SELECT * FROM rdacademy_users `limit` ORDER BY 4', 'SELECT *, COUNT(*) FROM rdacademy_users GROUP BY (4)' ) as $s ) { $x = chiedi( array( 'sql' => $s ) ); if ( $x['rossi'] === 0 ) $no[] = $s; }
$x = chiedi( array( 'sql' => 'SELECT id, email FROM rdacademy_users ORDER BY id LIMIT 5' ) );
prova( 'M8 (medio) ORDER BY e GROUP BY per posizione rifiutati anche con parentesi, segno o alias «limit»; ORDER BY id LIMIT 5 resta', ! $no && $x['rossi'] === 0 && count( $x['dati'] ) === 5, implode( ' | ', $no ) . ' / ' . testo( $x ) );
foreach ( array( "UPDATE rdacademy_impostazioni SET valore = 915273 WHERE chiave = 'titolo'", "UPDATE rdacademy_impostazioni SET valore = -77812.25 WHERE chiave = 'titolo'", "UPDATE rdacademy_impostazioni SET valore = 0b0100111001010111 WHERE chiave = 'titolo'", "UPDATE rdacademy_impostazioni /* l'apostrofo */ SET valore = 'NuovaPasswordSMTP-3' WHERE chiave = 'titolo'" ) as $s ) chiedi( array( 'sql' => $s, 'consenti' => true, 'azione' => 'prova' ) );
$n = (int) uno( "SELECT COUNT(*) FROM rdacademy_console_registro WHERE istruzione LIKE '%915273%' OR istruzione LIKE '%77812%' OR istruzione LIKE '%0b0100%' OR istruzione LIKE '%NuovaPasswordSMTP%'" );
prova( 'M9 (medio) registro: nessun valore digitato in chiaro (numeri, 0b…, apostrofo in un commento)', $n === 0 && (int) uno( "SELECT COUNT(*) FROM rdacademy_console_registro WHERE istruzione LIKE 'UPDATE rdacademy_impostazioni%SET valore = %WHERE chiave = %'" ) >= 3, $n . ' righe coi valori' );
$ct = uno( "SELECT CREATE_TIME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'academy_base' AND TABLE_NAME = 'rdacademy_tl'" ); sleep( 2 );
$x = chiedi( array( 'sql' => 'UPDATE rdacademy_tl SET v = 1 WHERE id = 1', 'consenti' => true, 'azione' => 'prova' ) );
prova( 'M10 (medio) prova a secco senza INSERT: nessun ALTER TABLE sulla tabella (CREATE_TIME fermo)', $x['rossi'] === 0 && $ct === uno( "SELECT CREATE_TIME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'academy_base' AND TABLE_NAME = 'rdacademy_tl'" ), testo( $x ) );
$x = chiedi( array( 'sql' => 'SELECT chiave, valore FROM rdacademy_impostazioni' ) ); $x2 = chiedi( array( 'sql' => 'SELECT id FROM rdacademy_molte' ) ); $ul = end( $x2['referto'] );
prova( 'M11 (medio) referto vero: valori chiave/valore mascherati dichiarati; riga finale di una lettura troncata dice «troncato»', strpos( testo( $x ), 'nessuna in questo risultato' ) === false && strpos( json_encode( $x['dati'] ), 'pw-riservata-1' ) === false && strpos( (string) $ul[1], 'troncato' ) !== false, testo( $x ) . ' / ' . $ul[1] );

// ── N. nona revisione a freddo (F032, sulla 0.1.16A: 6 alti, 5 medi) ─────────────────────────────────────────
$root->query( 'DROP DATABASE IF EXISTS academy_estraneo' ); $root->query( 'CREATE DATABASE academy_estraneo' );
$root->query( "CREATE TABLE academy_estraneo.diario (id INT PRIMARY KEY AUTO_INCREMENT, txt VARCHAR(40)) ENGINE=MyISAM" );
$root->query( "CREATE TABLE academy_estraneo.clienti (id INT PRIMARY KEY, nome VARCHAR(40)) ENGINE=InnoDB" ); $root->query( "INSERT INTO academy_estraneo.clienti VALUES (1,'cliente-estraneo')" );
$root->select_db( 'wp_prova' );
$root->query( "CREATE TABLE wp_posts (ID INT PRIMARY KEY, post_status VARCHAR(20), post_title VARCHAR(40)) ENGINE=InnoDB" ); $root->query( "INSERT INTO wp_posts VALUES (1,'draft','bozza'),(2,'publish','pubblico')" );
$root->select_db( 'academy_base' );
$root->query( "INSERT INTO rdacademy_note (a,b,c,d) VALUES ('nota-a','nota-b','nota-c','nota-d')" );
$root->query( "CREATE TABLE `9riservati` (id INT PRIMARY KEY, password VARCHAR(40)) ENGINE=InnoDB" ); $root->query( "INSERT INTO `9riservati` VALUES (1,'pw-nove')" );
$root->query( "CREATE TABLE `\$dollari` (id INT PRIMARY KEY, api_token VARCHAR(40)) ENGINE=InnoDB" ); $root->query( "INSERT INTO `\$dollari` VALUES (1,'tok-dollaro')" );
$root->query( "CREATE TABLE `9antica` (id INT PRIMARY KEY AUTO_INCREMENT, txt VARCHAR(40)) ENGINE=MyISAM" );
$root->query( "CREATE TABLE rdacademy_riservati (id INT PRIMARY KEY, password VARCHAR(40)) ENGINE=InnoDB" ); $root->query( "INSERT INTO rdacademy_riservati VALUES (1,'pw-vista')" );
$root->query( "CREATE VIEW rdacademy_v_uno AS SELECT id, password AS p FROM rdacademy_riservati" );
$root->query( "CREATE VIEW rdacademy_v_due AS SELECT id, p AS q FROM rdacademy_v_uno" );
$root->query( "CREATE FUNCTION rdacademy_fpw() RETURNS VARCHAR(64) READS SQL DATA RETURN (SELECT password FROM rdacademy_riservati WHERE id = 1)" );
$root->query( "CREATE VIEW rdacademy_v_fun AS SELECT rdacademy_fpw() AS x" );
$root->query( "CREATE TABLE rdacademy_gen (id INT PRIMARY KEY, password VARCHAR(40), copia VARCHAR(40) AS (password) VIRTUAL) ENGINE=InnoDB" ); $root->query( "INSERT INTO rdacademy_gen (id, password) VALUES (1,'pw-generata')" );
$root->query( "CREATE VIEW rdacademy_v_gen AS SELECT id, copia AS c FROM rdacademy_gen" );
function due_tempi( array $r ): array { $a = chiedi( $r + array( 'consenti' => true, 'azione' => 'prova' ) ); $b = chiedi( $r + array( 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' ) ); return array( $a, $b ); }
function nreg(): int { return (int) uno( 'SELECT COUNT(*) FROM rdacademy_console_registro' ); }

$x = chiedi( array( 'sql' => 'SELECT id,id,id,id FROM rdacademy_molte WHERE id=`id`UNION SELECT * FROM rdacademy_users' ) );
$x2 = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT 1,2,3 UNION SELECT * FROM wp_users`u`' ) );
$x3 = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT 1,2,3 FROM wp_posts WHERE ID=`ID`UNION SELECT * FROM wp_options' ) );
prova( 'N1 (alto) nome fra apici inversi attaccato a UNION o alla tabella: nessun segreto in chiaro (BASE, wp_users, wp_options)', $x['rossi'] > 0 && $x2['rossi'] > 0 && $x3['rossi'] > 0 && strpos( json_encode( $x ), 'tok1' ) === false && strpos( json_encode( $x2 ), 'segretissimo' ) === false && strpos( json_encode( $x3 ), 'riservat' ) === false, testo( $x ) . ' / ' . testo( $x2 ) . ' / ' . testo( $x3 ) );
due_tempi( array( 'sorgente' => 'sorgente_wp', 'sql' => "UPDATE wp_users`u` SET user_login='preso' WHERE ID=1" ) );
due_tempi( array( 'sql' => "DELETE FROM`wp_prova`.wp_posts WHERE post_status='draft'" ) );
chiedi( array( 'sql' => "INSERT INTO`academy_estraneo`.diario (txt) VALUES ('scritta')", 'consenti' => true, 'azione' => 'prova' ) );
due_tempi( array( 'sql' => 'INSERT INTO rdacademy_note (a) SELECT api_token`p` FROM rdacademy_users`s` WHERE id=1' ) );
list( $x ) = due_tempi( array( 'sql' => 'UPDATE rdacademy_contr`c` SET v = 5 WHERE id = 1' ) );
prova( 'N2 (alto) scritture con l apice inverso attaccato: niente su WordPress, su un altro database, su MyISAM, da tabelle con segreti, con trigger', uno( 'SELECT user_login FROM wp_users', 'wp_prova' ) === 'admin' && (int) uno( 'SELECT COUNT(*) FROM wp_posts', 'wp_prova' ) === 2 && (int) uno( 'SELECT COUNT(*) FROM academy_estraneo.diario' ) === 0 && (int) uno( "SELECT COUNT(*) FROM rdacademy_note WHERE a LIKE 'tok%'" ) === 0 && $x['rossi'] > 0 && (int) uno( 'SELECT v FROM rdacademy_contr WHERE id = 1' ) !== 5, json_encode( array( uno( 'SELECT user_login FROM wp_users', 'wp_prova' ), uno( 'SELECT COUNT(*) FROM wp_posts', 'wp_prova' ), uno( 'SELECT COUNT(*) FROM academy_estraneo.diario' ), uno( "SELECT COUNT(*) FROM rdacademy_note WHERE a LIKE 'tok%'" ) ) ) . ' / ' . testo( $x ) );
$fo = '/tmp/f032-n3-' . getmypid() . '.txt'; $no = array();
foreach ( array( "SELECT a FROM rdacademy_note WHERE a=`a`INTO OUTFILE '$fo'", 'SELECT a FROM rdacademy_note WHERE a=`a`LOCK IN SHARE MODE', 'SELECT a FROM rdacademy_note WHERE a=`a`INTO @v', 'SELECT a FROM rdacademy_note WHERE a=`a`FOR UPDATE', 'SELECT`SLEEP`(0)' ) as $s ) { $x = chiedi( array( 'sql' => $s ) ); if ( $x['rossi'] === 0 ) $no[] = $s; }
prova( 'N3 (alto) INTO OUTFILE, INTO @v, lucchetti e SLEEP attaccati a un nome fra apici inversi: rifiutati, nessun file scritto', ! $no && ! file_exists( $fo ), implode( ' | ', $no ) . ( file_exists( $fo ) ? ' · file scritto' : '' ) );
$x = chiedi( array( 'sql' => 'SELECT 1,2 UNION SELECT * FROM 9riservati' ) ); $x2 = chiedi( array( 'sql' => 'SELECT 1,2 UNION SELECT * FROM $dollari' ) ); $x3 = chiedi( array( 'sql' => 'SELECT 1,2 UNION SELECT * FROM `9riservati`' ) );
chiedi( array( 'sql' => "INSERT INTO 9antica (txt) VALUES ('scritta')", 'consenti' => true, 'azione' => 'prova' ) ); chiedi( array( 'sql' => "INSERT INTO `9antica` (txt) VALUES ('scritta')", 'consenti' => true, 'azione' => 'prova' ) );
$x4 = chiedi( array( 'sql' => 'SELECT a FROM rdacademy_note WHERE 1=1.0UNION SELECT password FROM rdacademy_riservati' ) ); $x5 = chiedi( array( 'sql' => 'SELECT a FROM rdacademy_note WHERE a IS NOT \NUNION SELECT api_token FROM rdacademy_users' ) );
prova( 'N4 (alto) nomi che cominciano con una cifra o con $ e numeri attaccati a una parola: rifiutati, nessun segreto, nessuna scrittura su MyISAM', $x['rossi'] > 0 && $x2['rossi'] > 0 && $x3['rossi'] > 0 && $x4['rossi'] > 0 && $x5['rossi'] > 0 && strpos( json_encode( array( $x, $x2, $x3, $x4, $x5 ) ), 'pw-nove' ) === false && strpos( json_encode( array( $x2, $x5 ) ), 'tok' ) === false && (int) uno( 'SELECT COUNT(*) FROM `9antica`' ) === 0, testo( $x ) . ' / ' . testo( $x2 ) . ' / ' . testo( $x4 ) . ' / righe in 9antica ' . uno( 'SELECT COUNT(*) FROM `9antica`' ) );
$x = chiedi( array( 'sql' => 'SELECT * FROM information_schema.PROCESSLIST`p`' ) ); $x2 = chiedi( array( 'sql' => 'SELECT * FROM`information_schema`.`PROCESSLIST`' ) );
prova( 'N5 (alto) information_schema.PROCESSLIST con un alias attaccato: rifiutata', $x['rossi'] > 0 && $x2['rossi'] > 0 && ! $x['dati'] && ! $x2['dati'], testo( $x ) . ' / ' . testo( $x2 ) );
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_v_due' ) ); $x2 = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_v_fun' ) ); $x3 = chiedi( array( 'sql' => 'SELECT copia FROM rdacademy_gen' ) ); $x4 = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_v_gen' ) ); $x5 = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_vista' ) );
prova( 'N6 (alto) vista sopra una vista, vista che chiama una funzione, colonna generata da una segreta: il segreto non esce; la vista innocua si legge', strpos( json_encode( array( $x, $x2 ) ), 'pw-vista' ) === false && strpos( json_encode( array( $x3, $x4 ) ), 'pw-generata' ) === false && $x5['rossi'] === 0, testo( $x ) . ' / ' . testo( $x2 ) . ' / ' . testo( $x3 ) . ' / ' . testo( $x4 ) . ' / ' . testo( $x5 ) );
$no = array();
foreach ( array( array( 'wordpress', "SELECT option_name, option_value v FROM wp_options HAVING`v`LIKE 'smtp%'" ), array( 'base', 'SELECT * FROM rdacademy_users ORDER BY`id`*0,4' ), array( 'base', 'SELECT * FROM rdacademy_users WHERE id>0.0ORDER BY 4' ), array( 'base', "SELECT chiave, valore v FROM rdacademy_impostazioni ORDER BY`v`" ) ) as $s ) { $x = chiedi( array( 'sorgente' => $s[0], 'sql' => $s[1] ) ); if ( $x['rossi'] === 0 ) $no[] = $s[1]; }
prova( 'N7 (medio) oracoli per incollatura: HAVING e ORDER BY attaccati a un nome fra apici inversi o a un numero rifiutati', ! $no, implode( ' | ', $no ) );
$x = chiedi( array( 'sql' => 'SELECT * FROM rdacademy_users ORDER BY 4 LIMIT 3' . str_repeat( ' ', 3000000 ) . 'ROWS EXAMINED 100000' ) );
prova( 'N8 (medio) istruzione lunghissima: rifiutata (i controlli non si spengono)', $x['rossi'] > 0 && ! $x['dati'], substr( testo( $x ), 0, 300 ) );
$n0 = nreg(); chiedi( array( 'sql' => "SELECT \xff FROM rdacademy_note" ) ); $n1 = nreg(); chiedi( array( 'sorgente' => str_repeat( 's', 60 ), 'sql' => 'SELECT 1' ) ); $n2 = nreg(); chiedi( array( 'sql' => 'DROP TABLE rdacademy_note', 'login' => "tecn\xff\xfeico" ) ); $n3 = nreg();
prova( 'N9 (medio) ogni rifiuto è nel registro: byte non UTF-8 nell istruzione, sorgente lunghissima, login non UTF-8', $n1 === $n0 + 1 && $n2 === $n1 + 1 && $n3 === $n2 + 1, "registro: $n0 → $n1 → $n2 → $n3" );
$x = chiedi( array( 'sql' => 'SELECT * FROM academy_estraneo.clienti' ) ); $x2 = chiedi( array( 'sql' => 'SELECT * FROM `academy_s3`.rdacademy_s3_dati' ) ); $x3 = chiedi( array( 'sql' => 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()' ) ); $x4 = chiedi( array( 'sql' => 'SELECT n.a FROM academy_base.rdacademy_note n' ) );
$x5 = chiedi( array( 'sql' => 'SHOW TABLES FROM academy_estraneo' ) ); $x6 = chiedi( array( 'sql' => 'SHOW COLUMNS FROM clienti IN `academy_estraneo`' ) ); $x7 = chiedi( array( 'sql' => 'SHOW TABLES' ) ); $x8 = chiedi( array( 'sql' => 'SHOW COLUMNS FROM rdacademy_note FROM academy_base' ) );
prova( 'N10 (medio) lettura da un altro database rifiutata, anche con SHOW … FROM (il referto dichiarerebbe il database sbagliato); information_schema e il proprio database restano', $x5['rossi'] > 0 && $x6['rossi'] > 0 && $x7['rossi'] === 0 && $x8['rossi'] === 0 && $x['rossi'] > 0 && $x2['rossi'] > 0 && strpos( json_encode( $x ), 'cliente-estraneo' ) === false && $x3['rossi'] === 0 && $x4['rossi'] === 0, testo( $x ) . ' / ' . testo( $x2 ) . ' / ' . testo( $x3 ) . ' / ' . testo( $x4 ) . ' / ' . testo( $x5 ) . ' / ' . testo( $x8 ) );
chiedi( array( 'sql' => 'UPDATE rdacademy_tl SET v = 7 WHERE v=0.424242AND id = 1', 'consenti' => true, 'azione' => 'prova' ) ); chiedi( array( 'sql' => 'UPDATE rdacademy_vecchia`v` SET v = 1', 'consenti' => true, 'azione' => 'prova' ) );
$n = (int) uno( "SELECT COUNT(*) FROM rdacademy_console_registro WHERE istruzione LIKE '%424242%' OR istruzione LIKE '%rdacademy_vecchiav%'" );
prova( 'N11 (medio) registro: nessun numero incollato in chiaro e nessun nome fuso col suo alias', $n === 0, $n . ' righe' );
$x = chiedi( array( 'sql' => 'SELECT `id`, `email` FROM `rdacademy_users` WHERE `id` = 1' ) ); $x2 = chiedi( array( 'sql' => 'SELECT `n`.`a` FROM `academy_base`.`rdacademy_note` `n` WHERE 1.5e3 > 1 AND 0x41 = 0x41 AND .5 < 1 LIMIT 1' ) ); $x3 = chiedi( array( 'sql' => 'SELECT id, email FROM rdacademy_users WHERE id IN (1,2) ORDER BY id DESC LIMIT 2' ) );
prova( 'N12 istruzioni innocue con apici inversi, numeri con esponente ed esadecimali restano ammesse', $x['rossi'] === 0 && count( $x['dati'] ) === 1 && $x2['rossi'] === 0 && count( $x2['dati'] ) === 1 && $x3['rossi'] === 0 && count( $x3['dati'] ) === 2, testo( $x ) . ' / ' . testo( $x2 ) . ' / ' . testo( $x3 ) );


fine();
