<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require '/home/claude/rev12/sostituti-wordpress.php';
require '/home/claude/rev12/reldox-academy-diagnostica/includes/class-rdacademy-console-sql.php';
const SEGRETI = array('PW-S3','APIKEY-MYI','WP-MAILPASS','WP-AUTHKEY','CHIAVE-API-IN-OPTIONS','STRIPESEGRETO','BHASH','ACTKEY','SESS-TOK','HMACCONSUMER','cs_SEGRETO','HASH-','tok-','SMTP-PW','CHIAVE-CLAUDE','FTP-PAROLA','COLONNA-CHIAVE','PRIVATE KEY','4829','PWCRED','TOKEN-SESSIONE','segreto','Segreto-S3-banco');
$GLOBALS['NPROVE'] = 0;
function db() { static $c; if (!$c) { $c = new mysqli('localhost','banco','banco'); } return $c; }
function sq($sql) { $r = db()->query($sql); if ($r instanceof mysqli_result) { $o = $r->fetch_all(); $r->free(); return $o; } return $r; }
function P($sorg, $sql, $az = 'esegui', $extra = array()) {
	$GLOBALS['NPROVE']++;
	$r = Rdacademy_Console_SQL::esegui(array('sorgente'=>$sorg,'sql'=>$sql,'azione'=>$az,'utente'=>7,'login'=>'rev')+$extra);
	return $r;
}
function riassunto($r) {
	$ref = array_map(function($x){return $x[0].': '.$x[1];}, $r['referto']);
	$s = implode(' | ', $ref);
	$d = json_encode($r['dati'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	return array($s, $d, $r['colonne']);
}
function fuga($txt) { $f = array(); foreach (SEGRETI as $s) if (stripos($txt, $s) !== false) $f[] = $s; return $f; }
function T($nome, $sorg, $sql, $az='esegui', $extra=array(), $breve=true) {
	$t0 = microtime(true);
	$r = P($sorg, $sql, $az, $extra);
	$dt = round(microtime(true)-$t0,2);
	list($s, $d, $c) = riassunto($r);
	$f = fuga($d . ' ' . $s);
	$esito = $r['rossi'] ? 'ROSSO' : ($r['verdi'] ? 'VERDE' : 'INFO');
	if ($breve) $s = mb_substr($s, 0, 260);
	echo "[$nome] {$dt}s $esito".($f?' **FUGA:'.implode(',',$f).'**':'')."\n   SQL: ".str_replace("\n",'\n',mb_substr($sql,0,200))."\n   REF: $s\n   DATI: ".mb_substr($d,0,400)."\n";
	return $r;
}
function conferma($nome, $sorg, $sql, $extra=array()) {
	$a = T($nome.'-prova', $sorg, $sql, 'prova', array('consenti'=>true)+$extra);
	if ($a['rossi']) return $a;
	return T($nome.'-esegui', $sorg, $sql, 'esegui', array('consenti'=>true,'conferma'=>'CONFERMO')+$extra);
}
function registro($n=3) { foreach (array_reverse(sq("SELECT id,sorgente,tipo,esito,istruzione,messaggio FROM rev12_base.rdacademy_console_registro ORDER BY id DESC LIMIT $n")) as $x) echo "   REG: ".implode(' ¦ ',$x)."\n"; }
register_shutdown_function(function(){ echo "\n#PROVE=".$GLOBALS['NPROVE']."\n"; });
