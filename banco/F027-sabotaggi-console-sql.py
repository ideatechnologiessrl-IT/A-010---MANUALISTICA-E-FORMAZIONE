#!/usr/bin/env python3
# F027-sabotaggi-console-sql.py — RelDoxAcademy · Formazione F027 · 23/11/2026 (nasce da F026: + una regola nuova per volta di BUG-6 e F42)
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
# Toglie dal motore della console una regola alla volta (su una copia) e rifà il banco: ognuno deve dire ROSSO.
# uso:  python3 -I banco/F027-sabotaggi-console-sql.py <reldox-academy-diagnostica>
import os, shutil, subprocess, sys, tempfile
D = sys.argv[1]; BANCO = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'F027-banco-console-sql.php')
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
 ('F027 apici inversi non in chiaro', "$out .= preg_match( '/^[A-Za-z0-9_$]+$/', $val ) ? $val :", "$out .= false ? $val :"),
 ('F027 sequenze ammesse', "'/\\b(NEXTVAL|SETVAL|LASTVAL)", "'/NIENTE_SEQ|\\b(NEXTVAL_X)"),
 ('F027 SHOW GRANTS ammesso', "'/\\bSHOW\\s+(GRANTS|", "'/\\bSHOW\\s+(NIENTE|"),
 ('F027 USER() ammesso', "'/\\b(USER|CURRENT_USER|SESSION_USER|SYSTEM_USER|CURRENT_ROLE)\\s*\\(|\\bCURRENT_USER\\b|\\bCURRENT_ROLE\\b/i'", "'/NIENTE_USER/'"),
 ('F027 schema mysql ammesso', "return $no( 'Lo schema «'", "if(0)return $no( 'Lo schema «'"),
 ('F027 REPLACE() preso per scrittura', "\\b(REPLACE)\\b(?!\\s*\\()", "\\b(REPLACE)\\b"),
 ('F027 colonne segrete citabili in lettura', "if ( $sc ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): non si", "if ( false ) { $c->close(); $rifiuta( 'Colonne segrete citate (' . implode( ', ', $sc ) . '): non si"),
 ('F027 errori con i valori', "array( \"'…'\", '\"…\"', '…' )", "array( '$0', '$0', '$0' )"),
 ('F027 DEFINER in chiaro', "$v = (string) preg_replace( '/DEFINER", "$v = $v . (string) preg_replace( '/NIENTE"),
 ('F027 altro database ammesso', "if ( $altri ) return", "if ( false ) return"),
 ('F027 viste ammesse', "if ( $r[1] !== 'BASE TABLE' ) $vie[]", "if ( $r[1] === 'VIEW' ) continue; if ( $r[1] !== 'BASE TABLE' ) $vie[]"),
 ('F027 trigger ammessi', "if ( $trg ) return", "if ( false ) return"),
 ('F027 funzioni memorizzate ammesse', "if ( $fun ) return", "if ( false ) return"),
 ('F027 gettone riusabile', "if ( ! self::consuma_gettone(", "if ( false && ! self::consuma_gettone("),
 ('F027 registro senza trigger', "self::q( $c, \"CREATE TRIGGER IF NOT EXISTS", "if(0)self::q( $c, \"CREATE TRIGGER IF NOT EXISTS"),
 ('F027 registro «avvio» ignorato', "if ( ! self::registra( $r, $s, 'avvio'", "if ( false && ! self::registra( $r, $s, 'avvio'"),
 ('F027 multisite senza super amministratore', "&& ( ! is_multisite() || is_super_admin() )", "", 'B'),
 ('F027 interfaccia: Incolla in scrittura', 'data-f="incolla" data-scrittura="no"', 'data-f="incolla"', 'B'),
 ('oltre 1000 righe', "if ( count( $dati ) >= self::MAX_RIGHE )", "if ( false )"),
]
rossi = 0
for voce in SAB:
    nome, da, a = voce[:3]; f = MB if len(voce) > 3 else M
    t = tempfile.mkdtemp(prefix='f027-sab-'); c = os.path.join(t, 'd'); shutil.copytree(D, c)
    p = os.path.join(c, f); s = open(p, encoding='utf-8').read()
    if da not in s: print('  NON APPLICATO  ' + nome); shutil.rmtree(t); continue
    s = s.replace(da, a, 1)
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
