#!/usr/bin/env python3
# F029-sabotaggi-console-sql.py — RelDoxAcademy · Formazione F029 · 23/11/2026 (nasce da F028: + le regole della quarta revisione a freddo di BUG-6 e F45 a-b)
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
# Toglie dal motore della console una regola alla volta (su una copia) e rifà il banco: ognuno deve dire ROSSO.
# uso:  python3 -I banco/F029-sabotaggi-console-sql.py <reldox-academy-diagnostica>
import os, shutil, subprocess, sys, tempfile
D = sys.argv[1]; BANCO = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'F029-banco-console-sql.php')
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
 ('F027 apici inversi non in chiaro', "$out .= preg_match( '/^[A-Za-z0-9_$\\x80-\\xff]+$/', $val ) ? $val :", "$out .= false ? $val :"),
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
 ('F029-r5 posizioni con OFFSET ammesse', "(?:[^;]*?,\\s*)?\\d+(?![A-Za-z0-9_$.\\x80-\\xff])/i'", "(?:[^;]*?,\\s*)?\\d+\\s*(?:ASC\\b|DESC\\b)?\\s*(?:,|\\)|$|\\bLIMIT\\b)/i'"),
 ('F029-r5 INSERT … SET preso per cambio di chiave', "if ( $verbo !== 'UPDATE' ) {", 'if ( false ) {'),
 ('F029-r5 SHOW ALL SLAVES ammesso', '\\bSHOW\\s+(ALL\\s+)?(BINLOG', '\\bSHOW\\s+(BINLOG'),
 ('oltre 1000 righe', "if ( count( $dati ) >= self::MAX_RIGHE )", "if ( false )"),
]
rossi = 0
for voce in SAB:
    nome, da, a = voce[:3]; f = MB if len(voce) > 3 else M
    t = tempfile.mkdtemp(prefix='f029-sab-'); c = os.path.join(t, 'd'); shutil.copytree(D, c)
    p = os.path.join(c, f); s = open(p, encoding='utf-8').read()
    if da not in s: print('  NON APPLICATO  ' + nome); shutil.rmtree(t); continue
    s = s.replace(da, a) if 'accentati' in nome else s.replace(da, a, 1)
    if nome.startswith('F028 sql_mode'): s = s.replace("if ( strpos( $dopo, 'ANSI_QUOTES' ) !== false || strpos( $dopo, 'NO_BACKSLASH_ESCAPES' ) !== false )", "if ( false )", 1)
    # F029: difese doppie — la regola F028 e quella F029 che la copre si tolgono insieme
    if nome.startswith('F028 INSERT … SELECT'): s = s.replace("if ( count( $tcur ) > 1 || preg_match( '/\\bJOIN\\b|\\bUSING\\b/i', $skw ) )", "if ( false )", 1).replace("elseif ( in_array( $cl['verbo'], array( 'INSERT', 'REPLACE' ), true ) && ! preg_match(", "elseif ( false && ! preg_match(", 1).replace("if ( $kvm === '' ) $kvm = self::kv_nudo( $skw,", "if ( false ) $kvm = self::kv_nudo( $skw,", 1)
    if nome.startswith('F028 espressioni sul valore'): s = s.replace("if ( ! $ok_prima || ! $ok_dopo ) return", "if ( false ) return", 1)
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
