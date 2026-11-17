#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""F005-G-banchi-copia.py — RelDoxAcademy · Formazione F005 · 17/11/2026.
⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Banchi standard della copia (analisi tecnica ed. 4, § 14): G0 sintassi e lingue, G1 nessun resto,
G2 ruoli e recupero accesso (controllo sui file; la prova vera sul palco), G10 diagnostica allineata.
Leggono il codice vero della copia; RelDoxFood si legge e basta.

uso:  python3 -I banco/F005-G-banchi-copia.py <repo A-010> <repo RelDoxFood> [--trappola]
      --trappola: mette un resto «rdfood» in una copia temporanea; G1 deve andare ROSSO.
Esito: righe [VERDE]/[ROSSO]/[INFO] e la riga finale «N verdi, M rossi». Uscita 0 solo se tutto verde.
"""
import json, re, shutil, subprocess, sys, tempfile
from pathlib import Path

PROD, DIAG = 'reldox-academy', 'reldox-academy-diagnostica'
RESTI = re.compile(r'rdfood|reldoxfood|/food/', re.I)
righe = []
def esito(ok, banco, testo):
    righe.append(('[VERDE]' if ok else '[ROSSO]', banco, testo))
def info(banco, testo):
    righe.append(('[INFO]', banco, testo))

def file_di(base, est):
    return sorted(p for p in base.rglob('*') if p.is_file() and p.suffix in est) if base.is_dir() else []

def tolte(food, analisi):
    """Classi dei file PHP che l allegato A dice TOGLI: nella copia non devono esistere né essere chiamate."""
    a = analisi.read_text(encoding='utf-8'); a = a[a.index('## Allegato A'):]
    nomi = set()
    for f in re.findall(r'^\| `([^`]+\.php)` \| [^|]+ \| TOGLI \|', a, re.M):
        p = food / f
        if p.is_file():
            nomi |= set(re.findall(r'^\s*(?:final\s+|abstract\s+)?class\s+Rdfood_(\w+)', p.read_text(encoding='utf-8', errors='replace'), re.M))
    return {'Rdacademy_' + n for n in nomi}

def g0(rep):
    php = file_di(rep / PROD, {'.php'}) + file_di(rep / DIAG, {'.php'})
    if not php:
        esito(False, 'G0', 'nessun file PHP nella copia'); return
    rotti = []
    for p in php:
        r = subprocess.run(['php', '-l', str(p)], capture_output=True, text=True)
        if r.returncode: rotti.append(p.relative_to(rep).as_posix())
    esito(not rotti, 'G0', 'php -l su %d file: %s' % (len(php), 'nessun errore' if not rotti else '%d errori: %s' % (len(rotti), ', '.join(rotti[:5]))))
    ling = rep / PROD / 'includes' / 'class-rdacademy-lingua.php'
    if not ling.is_file():
        esito(False, 'G0', 'manca includes/class-rdacademy-lingua.php'); return
    codice = r'''define('ABSPATH','/');require $argv[1];$c=new ReflectionClass('Rdacademy_Lingua');$L=$c->getConstant('LINGUE');$err=[];
foreach($c->getConstants() as $n=>$v){ if(!is_array($v)) continue;
  foreach($v as $k=>$per){ if(!is_array($per)) continue; $ks=array_keys($per);
    if(array_intersect($ks,$L)===$ks && $ks){ $m=array_diff($L,$ks); if($m) $err[]="$n.$k manca ".implode(',',$m);
      $sh=null; foreach($per as $lg=>$t){ $s=is_array($t)?count($t):-1; if($sh===null)$sh=$s; elseif($s!==$sh)$err[]="$n.$k forma diversa in $lg"; } } } }
echo json_encode(['lingue'=>$L,'err'=>$err]);'''
    r = subprocess.run(['php', '-r', codice, str(ling)], capture_output=True, text=True)
    try:
        j = json.loads(r.stdout)
        esito(not j['err'], 'G0', 'lingue %s: %s' % ('/'.join(j['lingue']), 'stesse chiavi in ogni lingua' if not j['err'] else '; '.join(j['err'][:5])))
    except Exception:
        esito(False, 'G0', 'gestore delle lingue non caricabile: ' + (r.stderr or r.stdout)[:200])

def g1(rep, food, analisi):
    tutti = file_di(rep / PROD, {'.php', '.js', '.css', '.md', '.txt', '.svg', '.html'}) + file_di(rep / DIAG, {'.php', '.js', '.css'})
    if not tutti:
        esito(False, 'G1', 'copia vuota'); return
    resti = []
    for p in tutti:
        for i, l in enumerate(p.read_text(encoding='utf-8', errors='replace').splitlines(), 1):
            if RESTI.search(l): resti.append('%s:%d' % (p.relative_to(rep).as_posix(), i))
    esito(not resti, 'G1', '%d file, resti di RelDoxFood: %s' % (len(tutti), 'zero' if not resti else '%d (%s)' % (len(resti), ', '.join(resti[:5]))))
    t = tolte(food, analisi); chiamate = []
    for p in tutti:
        testo = p.read_text(encoding='utf-8', errors='replace')
        for n in t:
            for m in re.finditer(r'\b%s\b' % n, testo):
                prima = testo[max(0, m.start() - 20):m.start()]
                if 'class_exists' not in prima: chiamate.append('%s → %s' % (p.relative_to(rep).as_posix(), n))
    esito(not chiamate, 'G1', '%d classi tolte, chiamate fuori da class_exists: %s' % (len(t), 'zero' if not chiamate else '; '.join(sorted(set(chiamate))[:5])))

def metodi(p):
    return set(re.findall(r'function\s+(\w+)\s*\(', p.read_text(encoding='utf-8', errors='replace'))) if p.is_file() else set()

def g2(rep, food):
    orig = food / 'reldoxfood-diagnostica/includes/class-rdfood-maintenance.php'
    copia = rep / DIAG / 'includes/class-rdacademy-maintenance.php'
    if not copia.is_file():
        esito(False, 'G2', 'manca %s/includes/class-rdacademy-maintenance.php' % DIAG); return
    mo, mc = metodi(orig), metodi(copia)
    esito(mo == mc, 'G2', 'metodi della Manutenzione: %d in RelDoxFood, %d nella copia%s' % (len(mo), len(mc), '' if mo == mc else '; differenze: ' + ', '.join(sorted(mo ^ mc)[:6])))
    testo = copia.read_text(encoding='utf-8')
    esito('Ruoli &amp; recupero accesso' in testo and 'class Rdacademy_Maintenance' in testo, 'G2', 'strumento «Ruoli & recupero accesso» presente in Rdacademy_Maintenance')
    info('G2', 'la prova vera (ruoli, collegamento monouso) gira sul palco')

def costante(p, nome):
    m = re.search(r"define\(\s*'%s',\s*'([^']*)'" % nome, p.read_text(encoding='utf-8')) if p.is_file() else None
    return m.group(1) if m else None

def g10(rep):
    pp, pd = rep / PROD / 'reldox-academy.php', rep / DIAG / 'reldox-academy-diagnostica.php'
    if not (pp.is_file() and pd.is_file()):
        esito(False, 'G10', 'mancano i file principali dei due plugin'); return
    for a, b in (('RDACADEMY_VERSION', 'RDACADEMY_DIAG_VERSION'), ('RDACADEMY_STAGE', 'RDACADEMY_DIAG_STAGE'), ('RDACADEMY_PACKAGE', 'RDACADEMY_DIAG_PACKAGE')):
        va, vb = costante(pp, a), costante(pd, b)
        esito(va is not None and va == vb, 'G10', '%s = %s · %s = %s' % (a, va, b, vb))
    hv = lambda p: (re.search(r'^\s*\*\s*Version:\s*(\S+)', p.read_text(encoding='utf-8'), re.M) or [None, None])[1]
    esito(hv(pp) is not None and hv(pp) == hv(pd), 'G10', 'intestazione Version: %s · %s' % (hv(pp), hv(pd)))

def main():
    if len(sys.argv) < 3: sys.exit(__doc__)
    rep, food = Path(sys.argv[1]).resolve(), Path(sys.argv[2]).resolve()
    analisi = rep / 'docs/ANALISI-TECNICA-RELDOXACADEMY.md'
    if '--trappola' in sys.argv:
        tmp = Path(tempfile.mkdtemp()); 
        for d in (PROD, DIAG, 'docs'):
            if (rep / d).is_dir(): shutil.copytree(rep / d, tmp / d)
        bersaglio = next(iter(file_di(tmp / PROD, {'.php'})), None)
        if bersaglio is None: sys.exit('trappola: copia vuota, niente da sabotare')
        bersaglio.write_text(bersaglio.read_text(encoding='utf-8') + '\n// rdfood\n', encoding='utf-8')
        g1(tmp, food, analisi); shutil.rmtree(tmp)
        rossi = sum(1 for r in righe if r[0] == '[ROSSO]')
        print('TRAPPOLA G1: %s — %d righe rosse' % ('VERDE (il banco ha visto il resto)' if rossi else 'ROSSO (il banco è cieco)', rossi))
        sys.exit(0 if rossi else 1)
    g0(rep); g1(rep, food, analisi); g2(rep, food); g10(rep)
    for s, b, t in righe: print('%s %-4s %s' % (s, b, t))
    v = sum(1 for r in righe if r[0] == '[VERDE]'); r = sum(1 for r in righe if r[0] == '[ROSSO]')
    print('%d verdi, %d rossi' % (v, r)); sys.exit(0 if r == 0 else 1)

if __name__ == '__main__':
    main()
