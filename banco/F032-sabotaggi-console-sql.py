#!/usr/bin/env python3
# F032-sabotaggi-console-sql.py — RelDoxAcademy · Formazione F032 · 24/11/2026 (nasce da F031: + le regole della nona revisione a freddo, 0.1.17A)
# (da F031) F031-sabotaggi-console-sql.py — RelDoxAcademy · Formazione F031 · 23/11/2026 (nasce da F030: il sabotaggio di PROCEDURE ANALYSE non si applicava più dopo la settima revisione, «NON APPLICATO» = 79/80; con un terzo argomento si esegue solo il sabotaggio che lo contiene nel nome)
# (da F030) (nasce da F029: + le due regole della sesta revisione a freddo di BUG-6)
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
# Toglie dal motore della console una regola alla volta (su una copia) e rifà il banco: ognuno deve dire ROSSO.
# uso:  python3 -I banco/F032-sabotaggi-console-sql.py <reldox-academy-diagnostica> [parte del nome]
import os, shutil, subprocess, sys, tempfile
D = sys.argv[1]; BANCO = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'F032-banco-console-sql.php')
SOLO = sys.argv[2] if len(sys.argv) > 2 else ''
M = 'includes/class-rdacademy-console-sql.php'; MB = 'includes/banchi/class-rdacademy-banco-console-sql.php'
SAB = [
 ('lettura senza READ ONLY (sessione e transazione)', "'SET SESSION TRANSACTION READ ONLY'", "'SET @x = 1'"),
 ('WordPress scrivibile', "'solo_lettura', true, false,", "'solo_lettura', true, true,"),
 ('spunta ignorata', "if ( empty( $r['consenti'] ) )", "if ( false )"),
 ('righe non ricontate', "if ( $n !== (int) $g['righe'] )", "if ( false )"),
 ('CONFERMO senza scadenza', "if ( (int) $g['scade'] < self::ora() )", "if ( false )"),
 ('CONFERMO per qualunque istruzione', "if ( ! hash_equals( (string) $g['impronta'], $im ) )", "if ( false )"),
 ('conferma non controllata', "if ( (string) $r['conferma'] !== 'CONFERMO' )", "if ( false )"),
 ('INTO OUTFILE ammesso', "'/\\bINTO\\s+(OUTFILE|DUMPFILE)\\b/i'", "'/NIENTE_OUTFILE/'"),
 ('SLEEP ammesso', "'/\\b(SLEEP|BENCHMARK)\\s*\\(/i'", "'/NIENTE_SLEEP/'"),
 ('commento eseguibile ammesso', "return array( '', $ids, 'Commento eseguibile", "if(0)return array( '', $ids, 'Commento eseguibile"),
 ('due istruzioni ammesse', "if ( strpos( $sk, ';' ) !== false )", "if ( false )"),
 ('colonne segrete in chiaro', "if ( ! self::segreta( $nome ) && ! self::segreta( $org[ $i ] ?? '' ) ) continue;", "continue;"),
 ('ripiego su sorgente spenta', "if ( ! $s['in_uso'] ) {", "if ( false ) {"),
 ('registro toccabile', "if ( array_intersect( array( strtolower( self::tabella_registro() ), strtolower( self::tabella_gettoni() ) ), $cl['nomi'] ) )", "if ( false )"),
 ('MyISAM ammessa', "elseif ( strcasecmp( (string) $r[2], 'InnoDB' ) !== 0 ) $vie[]", "elseif ( false ) $vie[]"),
 ('scrittura senza registro', "self::registra( $r, $s, 'scrittura', $n, 'eseguita', 'scritta' );", ""),
 ('F027 apici inversi non in chiaro', "$out .= preg_match( '/^[A-Za-z0-9_$\\x80-\\xff]+$/', $val ) ? ' ' . $val . ' ' :", "$out .= false ? $val :"),
 ('F027 sequenze ammesse', "'/\\b(NEXTVAL|SETVAL|LASTVAL)", "'/NIENTE_SEQ|\\b(NEXTVAL_X)"),
 ('F027 SHOW GRANTS ammesso', "'/\\bSHOW\\s+(GRANTS|", "'/\\bSHOW\\s+(NIENTE|"),
 ('F027 USER() ammesso', "'/\\b(USER|CURRENT_USER|SESSION_USER|SYSTEM_USER|CURRENT_ROLE)\\s*\\(|\\bCURRENT_USER\\b|\\bCURRENT_ROLE\\b/i'", "'/NIENTE_USER/'"),
 ('F027 schema mysql ammesso', "return $no( 'Lo schema «'", "if(0)return $no( 'Lo schema «'"),
 ('F027 REPLACE() preso per scrittura', "\\b(REPLACE)\\b(?!\\s*\\()", "\\b(REPLACE)\\b"),
 ('F027 colonne segrete citabili in lettura', "if ( $sc ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): non si", "if ( false ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): non si"),
 ('F027 errori con i valori', "'/[\\'\"].*$/s' ), array( '…', '«…»' )", "'/NIENTE/' ), array( '…', '«…»' )"),
 ('F027 UNION e WITH ammessi su tabelle con segreti', "if ( $ts && preg_match(", "if ( false && preg_match("),
 ('F027 nomi accentati non letti', "\\x80-\\xff", ""),
 ('F027 sequenze di DEFAULT ammesse', "if ( $seq ) return", "if ( false ) return"),
 ('F027 SHOW ENGINE ammesso', "|ENGINES?\\b)/i'", ")/i'"),
 ('F027 prefisso nel database di WordPress ignorato', "if ( $fuori ) return", "if ( false ) return"),
 ('F027 tempo massimo solo in lettura', "self::q( $c, 'SET SESSION max_statement_time = ' . self::TEMPO_MAX );\n\t\t// F028", "// F028"),
 ('F027 DEFINER in chiaro', "$v = (string) preg_replace( '/DEFINER", "$v = $v . (string) preg_replace( '/NIENTE"),
 ('F027 altro database ammesso', "if ( $altri ) return", "if ( false ) return"),
 ('F027 viste ammesse', "if ( $r[1] !== 'BASE TABLE' ) $vie[]", "if ( $r[1] === 'VIEW' ) continue; if ( $r[1] !== 'BASE TABLE' ) $vie[]"),
 ('F027 trigger ammessi', "if ( $trg ) return", "if ( false ) return"),
 ('F027 funzioni memorizzate ammesse', "if ( $fun ) return", "if ( false ) return"),
 ('F027 gettone riusabile', "if ( ! self::consuma_gettone(", "if ( false && ! self::consuma_gettone("),
 ('F027 registro senza trigger', "self::q( $c, \"CREATE TRIGGER `", "if(0)self::q( $c, \"CREATE TRIGGER `"),
 ('F027 registro «avvio» ignorato', "if ( ! self::registra( $r, $s, 'avvio'", "if ( false && ! self::registra( $r, $s, 'avvio'"),
 ('F027 multisite senza super amministratore', "&& ( ! is_multisite() || is_super_admin() )", "", 'B'),
 ('F027 interfaccia: Incolla in scrittura', 'data-f="incolla" data-scrittura="no"', 'data-f="incolla"', 'B'),
 ('F028 INSERT … SELECT dalle tabelle con segreti (con le regole F029 che la doppiano)', "if ( $tsw && preg_match(", "if ( false && preg_match("),
 ('F028 chiave/valore senza maschera per riga', "if ( $r[ $i ] !== null && self::segreta_kv(", "if ( false && self::segreta_kv("),
 ('F028 chiave segreta riconosciuta solo per nome di colonna', "|| preg_match( '/key|smtp|licen|credenz|credential|privat|auth|secret|segret|session|cookie|cert/i', $k )", ""),
 ('F028 valore serializzato con segreti non guardato', "return $v !== null && (bool) preg_match(", "return false && (bool) preg_match("),
 ('F028 espressioni sul valore in chiaro (con la regola F029 che la doppia)', "} elseif ( $kv_citate && (", "} elseif ( false && ("),
 ('F028 valore usato nelle condizioni', "if ( $kvm !== '' ) { $c->close(); $rifiuta( $kvm ); return $out; }\n\t\t\t$l", "\n\t\t\t$l"),
 ('F028 sql_mode del server lasciato (né ripulito né controllato)', "self::q( $c, \"SET SESSION sql_mode = '\"", "if(0)self::q( $c, \"SET SESSION sql_mode = '\""),
 ('F028 INNODB_TRX ammessa', "'/\\bINNODB_(TRX|LOCKS|LOCK_WAITS)\\b|\\bDATA_LOCK(S|_WAITS)\\b/i'", "'/NIENTE_TRX/'"),
 ('F028 SHOW BINLOG ed EXPLAIN FOR ammessi', "'/\\bSHOW\\s+(ALL\\s+)?(BINLOG|RELAYLOG", "'/NIENTE_BINLOG|\\bSHOW\\s+(ALL\\s+)?(BINLOGX|RELAYLOGX"),
 ('F028 suggerimenti /*+ ammessi', "if ( ( $dopo[0] ?? '' ) === '+' ) {", "if ( false ) {"),
 ('F028 attese GTID ammesse', "|WAIT_FOR_EXECUTED_GTID_SET|WAIT_UNTIL_SQL_THREAD_AFTER_GTIDS", ""),
 ('F028 permessi e ruoli ammessi', "|USER_ATTRIBUTES|SCHEMA_PRIVILEGES|TABLE_PRIVILEGES|COLUMN_PRIVILEGES|ADMINISTRABLE_ROLE_AUTHORIZATIONS|ROLE_TABLE_GRANTS|ROLE_COLUMN_GRANTS|ROLE_ROUTINE_GRANTS|ROLE_USAGE_GRANTS", ""),
 ('F028 senza tetto in byte', "if ( $byte > self::MAX_BYTE )", "if ( false )"),
 ('F028 utente della sorgente in chiaro', "'grantee', 'db_user' );", "'grantee' );"),
 ('F028 TABLE nelle sottoquery ammesso', "\\(\\s*(SELECT|TABLE)\\b/i', $skl", "\\(\\s*(SELECT)\\b/i', $skl"),
 ('F028 registro protetto non dichiarato', "$riga( 'INFO', self::$blindato ?", "if(0)$riga( 'INFO', self::$blindato ?"),
 ('F028 attesa dei lucchetti senza tetto', "self::q( $c, 'SET SESSION innodb_lock_wait_timeout = ' . self::TEMPO_MAX );", ""),
 ('F029 caratteri di controllo ammessi', "if ( preg_match( '/[\\x00-\\x08", "if ( false && preg_match( '/[\\x00-\\x08"),
 ('F029 chiave/valore legati a una chiave di un altra tabella unita', "&& ( $tab[ $jj ] ?? '' ) === ( $tab[ $i ] ?? '' )", ""),
 ('F029 UPDATE/DELETE su più tabelle ammessi', "if ( count( $tcur ) > 1 || preg_match( '/\\bJOIN\\b|\\bUSING\\b/i', $skw ) )", "if ( false )"),
 ('F029 chiave rinominabile', "return 'La chiave delle tabelle chiave/valore", "if(0)return 'La chiave delle tabelle chiave/valore"),
 ('F029 errore a metà lettura dato per VERDE', "if ( ! $tronco && $c->errno ) $rotto", "if ( false ) $rotto"),
 ('F029 valore chiave/valore in espressioni (lettura)', "if ( ! $ok_prima || ! $ok_dopo ) return", "if ( false ) return"),
 ('F029 valore chiave/valore in espressioni (scrittura)', "return 'Il valore delle tabelle chiave/valore («' . strtolower( $g ) . '») in scrittura", "continue; return 'Il valore delle tabelle chiave/valore («' . strtolower( $g ) . '») in scrittura"),
 ('F029 ORDER BY per posizione ammesso', "if ( $kvm === '' && $ts && self::per_posizione( $skl ) )", "if ( false )"),
 ('F029 INSERT senza colonne ammesso', "elseif ( in_array( $cl['verbo'], array( 'INSERT', 'REPLACE' ), true ) && ! preg_match(", "elseif ( false && ! preg_match("),
 ('F029 ROLE_ troppo largo', "|ROLE_TABLE_GRANTS|ROLE_COLUMN_GRANTS|ROLE_ROUTINE_GRANTS|ROLE_USAGE_GRANTS)", "|ROLE_[A-Z_]+)"),
 ('F029-r5 chiave cambiabile con «:=»', "|| ! in_array( $tz[ $i + 1 ] ?? '', array( '=', ':=' ), true ) ) continue;", "|| ( $tz[ $i + 1 ] ?? '' ) !== '=' ) continue;"),
 ('F029-r5 HAVING/ORDER BY sul valore chiave/valore', "if ( $kvm === '' && $ts && preg_match( '/\\b(HAVING|", "if ( false && preg_match( '/\\b(HAVING|"),
 ('F029-r5 posizioni: taglio a ogni parola LIMIT (dal motore F031 la regola è «nessun numero dopo ORDER/GROUP BY»)', "'/\\bLIMIT\\s+\\d+(\\s*(,|\\bOFFSET\\b)\\s*\\d+)?\\s*$/i'", "'/\\bLIMIT\\b.*$/is'"),
 ('F029-r5 INSERT … SET preso per cambio di chiave', "if ( $verbo !== 'UPDATE' ) {", 'if ( false ) {'),
 ('F029-r5 SHOW ALL SLAVES ammesso', '\\bSHOW\\s+(ALL\\s+)?(BINLOG', '\\bSHOW\\s+(BINLOG'),
 ('F030 PROCEDURE ANALYSE ammessa', "'/\\bANALYSE\\b|^(?!\\s*SHOW\\b).*\\bPROCEDURE\\b/is'", "'/NIENTE_ANALYSE/'"),
 ('F030 contatori AUTO_INCREMENT non rimessi', "$ai_ok = self::rimetti_contatori( $c, $ai_da );", "$ai_ok = true;"),
 ('F030 F46(a) conteggio delle righe cambiate', "if ( is_string( $c->info ) && preg_match(", "if ( false && preg_match("),
 ('F030-r7 ON DUPLICATE KEY UPDATE ammesso', "if ( $kvm === '' && $tsw && preg_match( '/\\bON\\s+DUPLICATE\\b/i', $skw ) )", "if ( false )"),
 ('F030-r7 NATURAL JOIN e USING ammessi', "if ( $kvm === '' && $ts && preg_match( '/\\bNATURAL\\b|", "if ( false && preg_match( '/\\bNATURAL\\b|"),
 ('F030-r7 DISTINCT ammesso', "if ( $kvm === '' && $ts && preg_match( '/\\bDISTINCT(?:ROW)?\\b/i', $skl ) )", "if ( false )"),
 ('F030-r7 valori in chiaro nel registro', "$i = self::senza_valori( (string) ( $r['sql'] ?? '' ) );", "$i = (string) ( $r['sql'] ?? '' );"),
 ('oltre 1000 righe', "if ( count( $dati ) >= self::MAX_RIGHE )", "if ( false )"),
 # F032 — nona revisione a freddo (0.1.17A)
 ('F032 nome fra apici inversi senza spazi attorno', "? ' ' . $val . ' ' : ' __id'", "? $val : ' __id'"),
 ('F032 numero attaccato a una parola ammesso', "if ( $dopo !== '' && preg_match( '/[A-Za-z0-9_.]/', $dopo ) ) return", "if ( false ) return"),
 ('F032 «$» e «\\» fuori dalle stringhe ammessi', "if ( strpos( $sk, '$' ) !== false || strpos( $sk, '\\\\' ) !== false ) return", "if ( false ) return"),
 ('F032 nomi che cominciano con una cifra ammessi', "foreach ( $ids as $idn ) { if ( preg_match( '/^[0-9]|\\$/', $idn ) ) return", "foreach ( $ids as $idn ) { if ( false ) return"),
 ('F032 istruzione lunghissima ammessa', "if ( strlen( $sql ) > self::MAX_ISTRUZIONE ) return $no(", "if ( false ) return $no("),
 ('F032 lettura da un altro database ammessa', "if ( $altri ) { $c->close(); $rifiuta( 'Riferimento a un altro database (' . implode( ', ', $altri ) . '): la console legge", "if ( false ) { $c->close(); $rifiuta( 'Riferimento a un altro database (' . implode( ', ', $altri ) . '): la console legge"),
 ('F032 SHOW … FROM un altro database ammesso', "$cl['verbo'] === 'SHOW' ? $cl['nomi'] : $cl['qualificati']", "$cl['qualificati']"),
 ('F032 tabelle con colonna generata da una segreta lette', "if ( $tg ) { $c->close();", "if ( false ) { $c->close();"),
 ('F032 vista sopra un altra vista letta', "array_intersect( $pvl, array_diff( $nv, array( $x[0] ) ) ) || ", ""),
 ('F032 vista che chiama una funzione letta', "array_intersect( $pvl, $rout ) || ", ""),
 ('F032 vista sopra una colonna generata letta', " || array_intersect( $pvl, array_keys( $gen ) )", ""),
 ('F032 registro: campi non ripuliti', "if ( ! preg_match( '//u', $t ) ) $t = (string) preg_replace( '/[^\\x20-\\x7e\\t\\r\\n]/', '?', $t );", ""),
 ('F032 registro: campi non accorciati', "mb_substr( $t, 0, $max, 'UTF-8' )", "$t"),
 ('F032 registro: numeri incollati in chiaro', "|\\.\\d+)/i', '… '", "|\\.\\d+)(?![A-Za-z0-9_$])/i', '…'"),
]
SAB += [
 ('F031 nomi non ASCII ammessi', r"if ( preg_match( '/[\x80-\xff]/', $sk . implode( '', $ids ) ) ) return", "if ( false ) return"),
 ('F031 chiamata fra apici inversi non vista', r"if ( preg_match( '/^__id(\d+)__$/', $nc, $mi ) ) $mc[1][ $ic ]", "if ( false ) $mc[1][ $ic ]"),
 ('F031 chiave legata solo per alias', "strtolower( (string) ( $f->db ?? '' ) . '.' . (string) $f->table )", "strtolower( (string) $f->table )"),
 ('F031 funzioni memorizzate chiamate in lettura', "if ( $fun ) { $c->close(); $rifiuta( 'Funzioni memorizzate (' . implode( ', ', $fun ) . '): la console non le chiama", "if ( false ) { $c->close(); $rifiuta( 'Funzioni memorizzate (' . implode( ', ', $fun ) . '): la console non le chiama"),
 ('F031 viste con colonne segrete lette', "if ( $vs ) {", "if ( false ) {"),
 ('F031 valore chiave/valore fra parentesi', "if ( $prof > 0 ) return", "if ( false ) return"),
 ('F031 numeri dopo ORDER BY ammessi', "return (bool) preg_match( '/(?<![A-Za-z0-9_$.\\x80-\\xff])\\d+(?![A-Za-z0-9_$\\x80-\\xff])/', $m[2] );", "return false;"),
 ('F031 numeri in chiaro nel registro', "'…', str_replace(", "'$0', str_replace("),
 ('F031 ALTER del contatore sempre', "if ( (int) $na < 0 || ( $ai_dopo[ $ta ] ?? null ) !== $na )", "if ( true )"),
 ('F031 maschere chiave/valore non dichiarate', "$mascherate[ $nome . ' (valori delle chiavi segrete)' ] = true;", ""),
 ('F031 troncato taciuto nella riga finale', "( $l['troncato'] ? 'solo le prime '", "( false ? 'solo le prime '"),
]
rossi = 0
if SOLO: SAB = [v for v in SAB if SOLO in v[0]]
for voce in SAB:
    nome, da, a = voce[:3]; f = MB if len(voce) > 3 else M
    t = tempfile.mkdtemp(prefix='f032-sab-'); c = os.path.join(t, 'd'); shutil.copytree(D, c)
    p = os.path.join(c, f); s = open(p, encoding='utf-8').read()
    if da not in s: print('  NON APPLICATO  ' + nome); shutil.rmtree(t); continue
    s = s.replace(da, a) if 'accentati' in nome else s.replace(da, a, 1)
    if nome.startswith('F028 sql_mode'): s = s.replace("if ( strpos( $dopo, 'ANSI_QUOTES' ) !== false || strpos( $dopo, 'NO_BACKSLASH_ESCAPES' ) !== false )", "if ( false )", 1)
    # F029: difese doppie — la regola F028 e quella F029 che la copre si tolgono insieme
    if nome.startswith('F028 INSERT … SELECT'): s = s.replace("if ( count( $tcur ) > 1 || preg_match( '/\\bJOIN\\b|\\bUSING\\b/i', $skw ) )", "if ( false )", 1).replace("elseif ( in_array( $cl['verbo'], array( 'INSERT', 'REPLACE' ), true ) && ! preg_match(", "elseif ( false && ! preg_match(", 1).replace("if ( $kvm === '' ) $kvm = self::kv_nudo( $skw,", "if ( false ) $kvm = self::kv_nudo( $skw,", 1)
    if nome.startswith('F029 valore chiave/valore in espressioni (scrittura)'): s = s.replace("if ( $prof > 0 ) return", "if ( false ) return", 1)  # F031: la regola delle parentesi la doppia
    if nome.startswith('F028 espressioni sul valore'): s = s.replace("if ( ! $ok_prima || ! $ok_dopo ) return", "if ( false ) return", 1)
    if nome.startswith('F032 nomi che cominciano'): s = s.replace("if ( $dopo !== '' && preg_match( '/[A-Za-z0-9_.]/', $dopo ) ) return", "if ( false ) return", 1)  # F032: la regola dei numeri attaccati la doppia
    if nome.startswith('F032 registro: campi non ripuliti'): s = s.replace("$t = (string) preg_replace( '/[\\x{10000}-\\x{10FFFF}]/u', '?', $t );", "", 1).replace("mb_substr( $t, 0, $max, 'UTF-8' )", "substr( $t, 0, $max )", 1)  # F032: difesa tripla (la seconda pulizia svuota il campo, mb_substr sostituisce i byte non validi)
    if nome.startswith('F032 istruzione lunghissima'): s = s.replace("if ( $sk === null ) return true;", "$sk = (string) $sk;", 1)  # F032: per_posizione() la doppia
    if nome.startswith('lettura senza READ ONLY'): s = s.replace("'START TRANSACTION READ ONLY'", "'START TRANSACTION'", 1)
    open(p, 'w', encoding='utf-8').write(s)
    r = subprocess.run(['php', BANCO, c], capture_output=True, text=True)
    fin = r.stdout.strip().splitlines()[-1] if r.stdout.strip() else '?'
    ok = r.returncode != 0
    rossi += ok
    print(('  ROSSO (giusto)  ' if ok else '  VERDE (CIECO)   ') + nome + ' — ' + fin)
    shutil.rmtree(t)
print('\n%d sabotaggi su %d fanno tornare rosso il banco' % (rossi, len(SAB)))
sys.exit(0 if rossi == len(SAB) else 1)
