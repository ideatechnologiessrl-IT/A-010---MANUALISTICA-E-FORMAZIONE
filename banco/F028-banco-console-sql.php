<?php
/**
 * F028-banco-console-sql.php — RelDoxAcademy · Formazione F028 · 23/11/2026 (nasce dal banco F027)
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
 * uso:  php banco/F027-banco-console-sql.php <cartella del plugin reldox-academy-diagnostica> [--trappola]
 *   --trappola  copia la diagnostica in una cartella temporanea, toglie il rifiuto di DROP dal motore e rifà il banco:
 *               deve dire ROSSO (il banco non è cieco).
 */
$D = rtrim( $argv[1] ?? '', '/' );
if ( $D === '' || ! is_dir( $D ) ) { fwrite( STDERR, "uso: php banco/F028-banco-console-sql.php <reldox-academy-diagnostica> [--trappola]\n" ); exit( 2 ); }

if ( in_array( '--trappola', $argv, true ) ) {
	$tmp = sys_get_temp_dir() . '/f028-trappola-' . getmypid();
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
prova( 'F2 la scrittura di 7 righe è nel registro con le sue righe e la sua istruzione', isset( $scritta[0] ) && (int) $scritta[0]['righe'] === 7 && $scritta[0]['istruzione'] === $U );
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

// ── H. la porta AJAX chiamata davvero (manage_options, nonce, multisite) e l interfaccia ──
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

fine();
