<?php
/**
 * F026-banco-console-sql.php — RelDoxAcademy · Formazione F026 · 23/11/2026
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Banco della Console SQL (voce F29, banco standard n. 2; mockup MOCKUP-CONSOLE-SQL-v1 approvato D45, 23/11/2026 08:39).
 * Rosso sulla 0.1.10A (la console non c è), verde sulla 0.1.11A. Chiama il motore vero
 * (Rdacademy_Console_SQL) su un MariaDB vero; delle classi del prodotto usa sostituti minimi con la stessa firma
 * (Rdacademy_Sorgenti, Rdacademy_DataSource, Rdacademy_DB_Config), come i banchi F023.
 *
 * Serve un MariaDB/MySQL locale con l utente `banco`/`banco` che può creare i database academy_base, academy_s3, wp_prova.
 *
 * uso:  php banco/F026-banco-console-sql.php <cartella del plugin reldox-academy-diagnostica> [--trappola]
 *   --trappola  copia la diagnostica in una cartella temporanea, toglie il rifiuto di DROP dal motore e rifà il banco:
 *               deve dire ROSSO (il banco non è cieco).
 */
$D = rtrim( $argv[1] ?? '', '/' );
if ( $D === '' || ! is_dir( $D ) ) { fwrite( STDERR, "uso: php banco/F026-banco-console-sql.php <reldox-academy-diagnostica> [--trappola]\n" ); exit( 2 ); }

if ( in_array( '--trappola', $argv, true ) ) {
	$tmp = sys_get_temp_dir() . '/f026-trappola-' . getmypid();
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
prova( 'A5 porta AJAX: manage_options e nonce prima di tutto', (bool) preg_match( "/function ajax\(\)[^{]*\{\s*if \( ! current_user_can\( 'manage_options' \) \)[\s\S]{0,200}check_ajax_referer\(/", $banco ) );
prova( 'A6 riga finale «N verdi, M rossi» e «Copia esito» nel referto', strpos( $banco, ' verdi, ' ) !== false && strpos( $banco, 'Copia esito' ) !== false );
prova( 'A7 interfaccia del mockup: Prova a secco, CONFERMO, spunta «Consenti scrittura»', strpos( $banco, 'Prova a secco' ) !== false && strpos( $banco, 'CONFERMO' ) !== false && strpos( $banco, 'Consenti scrittura' ) !== false );

if ( ! is_file( $fm ) ) { prova( 'B0 motore assente: le prove sul database non si possono fare', false ); fine(); }

// ── WordPress finto e sostituti delle classi del prodotto (stessa firma) ───────
define( 'ABSPATH', '/tmp/' ); define( 'RDACADEMY_TABLE_PREFIX', 'rdacademy_' );
define( 'DB_HOST', 'localhost' ); define( 'DB_NAME', 'wp_prova' ); define( 'DB_USER', 'banco' ); define( 'DB_PASSWORD', 'banco' );
$GLOBALS['table_prefix'] = 'wp_';
const PASS_S3 = 'Segreto-S3-banco';
final class Rdacademy_Sorgenti {
	const MAPPA = array( 'sorgente_3' => array( 'n' => 3, 'nome' => 'Sorgente 3' ), 'sorgente_4' => array( 'n' => 4, 'nome' => 'Sorgente 4' ), 'riserva' => array( 'n' => 5, 'nome' => 'Riserva' ) );
	public static function mappa(): array { return self::MAPPA; }
	public static function nome( string $k ): string { return self::MAPPA[ $k ]['nome'] ?? $k; }
	public static function numero( string $k ): int { return self::MAPPA[ $k ]['n'] ?? 0; }
}
final class Rdacademy_DataSource {
	public static function state( string $k ): string { return array( 'sorgente_3' => 'live', 'sorgente_4' => 'not_configured', 'riserva' => 'disabled' )[ $k ] ?? 'not_configured'; }
	public static function connect_params( string $k ): ?array {
		if ( $k === 'sorgente_3' ) return array( 'host' => 'localhost', 'port' => 3306, 'name' => 'academy_s3', 'user' => 'banco', 'pass' => 'banco', 'charset' => 'utf8mb4', 'pass_finta' => PASS_S3 );
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
$root->select_db( 'academy_s3' );
$root->query( "CREATE TABLE rdacademy_s3_dati (id INT PRIMARY KEY, v INT) ENGINE=InnoDB" );
$root->query( "INSERT INTO rdacademy_s3_dati VALUES (1,1),(2,1),(3,1)" );
$root->select_db( 'wp_prova' );
$root->query( "CREATE TABLE wp_users (ID INT PRIMARY KEY, user_login VARCHAR(60), user_pass VARCHAR(255)) ENGINE=InnoDB" );
$root->query( "INSERT INTO wp_users VALUES (1,'admin','\$P\$Bsegretissimo')" );

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
prova( 'C1 sorgenti: BASE, le tre del prodotto e WordPress; spente e non configurate dichiarate', isset( $S['base'], $S['sorgente_3'], $S['sorgente_4'], $S['riserva'], $S['wordpress'] ) && $S['sorgente_3']['in_uso'] && ! $S['sorgente_4']['in_uso'] && ! $S['riserva']['in_uso'] && ! $S['wordpress']['scrivibile'] && $S['base']['scrivibile'], json_encode( array_map( function ( $s ) { return $s['stato']; }, $S ) ) );
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
$x = chiedi( array( 'sql' => 'SELECT id, email, api_token FROM rdacademy_users ORDER BY id' ) );
$t = testo( $x );
prova( 'D1 lettura sul BASE: 10 righe, «academy_base @ localhost», READ ONLY dichiarato, riga «1 verdi, 0 rossi»', count( $x['dati'] ) === 10 && strpos( $t, 'academy_base @ localhost' ) !== false && strpos( $t, 'READ ONLY' ) !== false && $x['verdi'] === 1 && $x['rossi'] === 0, $t );
prova( 'D2 colonna segreta (api_token) mascherata e dichiarata', ( $x['dati'][0][2] ?? '' ) !== 'tok1' && strpos( $t, 'api_token' ) !== false );
$x = chiedi( array( 'sorgente' => 'wordpress', 'sql' => 'SELECT user_login, user_pass FROM wp_users' ) );
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
prova( 'E13 il registro non si tocca dalla console (append-only)', $x['rossi'] > 0 );
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

fine();
