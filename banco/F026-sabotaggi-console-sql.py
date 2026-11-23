#!/usr/bin/env python3
# F026-sabotaggi-console-sql.py — RelDoxAcademy · Formazione F026 · 23/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
# Toglie dal motore della console una regola alla volta (su una copia) e rifà il banco: ognuno deve dire ROSSO.
# uso:  python3 -I banco/F026-sabotaggi-console-sql.py <reldox-academy-diagnostica>
import os, shutil, subprocess, sys, tempfile
D = sys.argv[1]; BANCO = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'F026-banco-console-sql.php')
M = 'includes/class-rdacademy-console-sql.php'
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
 ('colonne segrete in chiaro', "if ( ! self::segreta( $nome ) ) continue;", "continue;"),
 ('ripiego su sorgente spenta', "if ( ! $s['in_uso'] ) {", "if ( false ) {"),
 ('registro toccabile', "if ( in_array( strtolower( self::tabella_registro() ), $cl['nomi'], true ) )", "if ( false )"),
 ('MyISAM ammessa', "if ( $mi ) {", "if ( false ) {"),
 ('scrittura senza registro', "self::registra( $r, $s, 'scrittura', $n, 'eseguita', 'scritta' );", ""),
 ('oltre 1000 righe', "if ( count( $dati ) >= self::MAX_RIGHE )", "if ( false )"),
]
rossi = 0
for nome, da, a in SAB:
    t = tempfile.mkdtemp(prefix='f026-sab-'); c = os.path.join(t, 'd'); shutil.copytree(D, c)
    p = os.path.join(c, M); s = open(p, encoding='utf-8').read()
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
