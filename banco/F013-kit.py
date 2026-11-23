#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
F013-kit.py — RelDoxAcademy · Formazione F013 · 18/11/2026
⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Compone il kit di consegna a quattro buste in un solo ZIP (REGOLE-FAMIGLIA v4 § 10; SPEC-KIT-DI-CONSEGNA v1
§ 2-§ 9) e lo controlla. Nasce dalla violazione V-2 (owner 18/11/2026 15:07, chat Formazione F013: «La regola
imposta è sempre delle 4 buste … le sorelle che generano codice devono rispettare questa regola»).

  busta 1 APERTURA   il pacchetto del plugin, MD5 di ogni file, APERTURA della sessione seguente (manifesto,
                     prova di lettura, handoff, prompt), le rese .html rigenerate
  busta 2 AREA       docs/*.md, CLAUDE.md, README.md: l area intera, sostituzione totale
  busta 3 MISURE     banco/ e strumenti/: script, banchi, esiti
  busta 4 AGGIUNTA   mockup, materiali (corsi, fonti, glossario, manuali, modelli), risorse

Il manifesto si ricalcola sulla scatola e si scrive nell APERTURA fra i due segni MANIFESTO; il progressivo K sta in
banco/KIT-PROGRESSIVO.txt e cresce di uno a ogni consegna, mai riusato.

uso:
  python3 -I banco/F013-kit.py componi <uscita> <pacchetto.zip> <versione con lettera> <sessione seguente> <diagnostica.zip>
      (F027, violazione V-7: il prodotto e RelDoxAcademy Diagnostica viaggiano sempre insieme; senza diagnostica la verifica è ROSSA)
  python3 -I banco/F013-kit.py verifica <kit.zip> <progressivo precedente, es. K0000>
  python3 -I banco/F013-kit.py sabotaggi <kit.zip> <progressivo precedente>   (tutti e cinque devono dare ROSSO)
"""
import hashlib, io, os, re, sys, zipfile, subprocess, tempfile, datetime

REPO = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PRODOTTO = 'RELDOXACADEMY'
REGISTRO_K = os.path.join(REPO, 'banco', 'KIT-PROGRESSIVO.txt')
SEGNO_A, SEGNO_B = '<!-- MANIFESTO:INIZIO', '<!-- MANIFESTO:FINE -->'


def md5(b):
    return hashlib.md5(b).hexdigest()


def tutti(cartelle):
    out = []
    for c in cartelle:
        base = os.path.join(REPO, c)
        if os.path.isfile(base):
            out.append(c)
            continue
        for d, _, fs in os.walk(base):
            for f in fs:
                if f == '.DS_Store':
                    continue
                out.append(os.path.relpath(os.path.join(d, f), REPO))
    return sorted(out)


def zip_da(file_e_dati):
    buf = io.BytesIO()
    with zipfile.ZipFile(buf, 'w', zipfile.ZIP_DEFLATED) as z:
        for nome, dati in file_e_dati:
            z.writestr(zipfile.ZipInfo(nome, (2026, 1, 1, 0, 0, 0)), dati)
    return buf.getvalue()


def ultimo_k():
    if not os.path.exists(REGISTRO_K):
        return 0
    ks = [int(m) for m in re.findall(r'^K(\d{4})', open(REGISTRO_K, encoding='utf-8').read(), re.M)]
    return max(ks) if ks else 0


# ───────────────────────────────────────────────────────────── componi
def componi(uscita, pacchetto, ver_l, sess, diag=None):
    ver, lettera = ver_l[:-1], ver_l[-1]
    vs = ver.replace('.', '_')
    prec = ultimo_k()
    k = 'K%04d' % (prec + 1)
    suff = '%s-%s-%s' % (k, vs, sess)
    nomi = {1: 'KIT-1-APERTURA-%s.zip' % suff, 2: 'KIT-2-AREA-PROGETTI-%s.zip' % suff,
            3: 'KIT-3-MISURE-%s.zip' % suff, 4: 'KIT-4-AGGIUNTA-%s.zip' % suff}
    kit_nome = 'KIT-%s-%s-%s%s-%s.zip' % (PRODOTTO, k, vs, lettera, sess)

    # busta 1: pacchetto, MD5 di ogni file, rese
    pz = open(pacchetto, 'rb').read()
    with zipfile.ZipFile(io.BytesIO(pz)) as z:
        voci = [i for i in z.infolist() if not i.is_dir()]
        righe = ['%s  %s' % (md5(z.read(i)), i.filename) for i in voci]
        cartelle = sorted({os.path.dirname(i.filename) for i in voci})
    md5_txt = ('# MD5 di ogni file del pacchetto %s (%s%s) — zip %s\n' % (os.path.basename(pacchetto), ver, lettera, md5(pz))
               + '\n'.join(righe) + '\n')
    md5_nome = 'MD5-%s.txt' % vs
    tmp = tempfile.mkdtemp()
    resa = os.path.join(tmp, 'ANALISI-TECNICA-RELDOXACADEMY.html')
    r = subprocess.run([sys.executable, '-I', os.path.join(REPO, 'strumenti', 'genera-analisi-tecnica.py'),
                        os.path.join(REPO, 'docs', 'ANALISI-TECNICA-RELDOXACADEMY.md'), resa, pacchetto],
                       capture_output=True, text=True)
    if r.returncode != 0 or not os.path.exists(resa):
        sys.exit('ROSSO: resa dell analisi tecnica non rigenerata: ' + (r.stderr or r.stdout)[-300:])
    rese = [('ANALISI-TECNICA-RELDOXACADEMY.html', open(resa, 'rb').read())]

    area = tutti(['CLAUDE.md', 'README.md']) + sorted(
        os.path.join('docs', f) for f in os.listdir(os.path.join(REPO, 'docs')) if f.endswith('.md'))
    misure = tutti(['banco', 'strumenti'])
    aggiunta = tutti(['mockup', 'corsi', 'fonti', 'glossario', 'manuali', 'modelli', 'risorse'])
    apertura_rel = os.path.join('docs', 'APERTURA-%s.md' % sess)
    if apertura_rel not in area:
        sys.exit('ROSSO: manca %s' % apertura_rel)

    oggi = datetime.datetime.now().strftime('%d/%m/%Y %H:%M')
    m = [SEGNO_A + ' — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->',
         '```',
         '## MANIFESTO — KIT %s — %s%s — %s' % (k, ver, lettera, sess),
         '',
         'PROGRESSIVO %s   precedente %s   ⛔ mai uguale, mai minore' % (k, 'K%04d' % prec if prec else 'nessuno'),
         'KIT %s · composto il %s' % (kit_nome, oggi),
         'BUSTE DICHIARATE: 4',
         'BUSTA 1  %s' % nomi[1],
         '         %s  md5 %s' % (os.path.basename(pacchetto), md5(pz)),
         '         %d file · %d cartelle · 0 cartelle vuote' % (len(voci), len(cartelle)),
         '         %s  %d righe' % (md5_nome, len(righe)),
         '         APERTURA-%s.md' % sess,
         '         rese: %s' % ', '.join(n for n, _ in rese),
         ('         pacchetto di diagnostica: %s  md5 %s (F021: secondo pacchetto, stesso numero)' % (os.path.basename(diag), md5(open(diag, 'rb').read()))
          if diag else '         pacchetto di diagnostica: assente (non ancora scritto, banco G10 rosso)'),
         'BUSTA 2  %s  %d file — quello che sostituirà l area intera' % (nomi[2], len(area)),
         'BUSTA 3  %s  %d script' % (nomi[3], len(misure))]
    for f in misure:
        p = os.path.join(REPO, f)
        try:
            n = sum(1 for _ in open(p, encoding='utf-8'))
        except (UnicodeDecodeError, OSError):
            n = 0
        m.append('         %s  %d righe%s' % (f, n, ' (nella busta; la riga di questo kit si aggiunge dopo la verifica)'
                                                   if f.endswith('KIT-PROGRESSIVO.txt') else ''))
    gruppi = {}
    for f in aggiunta:
        gruppi[f.split(os.sep)[0]] = gruppi.get(f.split(os.sep)[0], 0) + 1
    m.append('BUSTA 4  %s  %d volumi — %s' % (nomi[4], len(aggiunta),
             ' · '.join('%s %d' % (g, n) for g, n in sorted(gruppi.items()))))
    m.append('ELENCO DELL AREA, DICHIARATO (%d):' % len(area))
    m += ['  ' + f for f in area]
    # F029: l ultima installata si legge da banco/INSTALLATA-SUL-SITO.txt (prima era scritta fissa: 0.1.03A)
    fi = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'INSTALLATA-SUL-SITO.txt')
    inst = open(fi, encoding='utf-8').read().strip() if os.path.isfile(fi) else 'non dichiarata (manca banco/INSTALLATA-SUL-SITO.txt)'
    m.append('BASELINE %s · stadio %s · ultima installata sul sito: %s' % (ver, {'A': 'Alfa', 'B': 'Beta', 'G': 'Gold', 'L': 'Live'}[lettera], inst))
    bug = open(os.path.join(REPO, 'docs', 'BUG-FORMAZIONE.md'), encoding='utf-8').readline()
    m.append('         registro BUG: %s' % re.sub(r'^<!--\s*|\s*-->\s*$', '', bug.strip()))
    m += ['```', SEGNO_B]
    manifesto = '\n'.join(m)

    ap_path = os.path.join(REPO, apertura_rel)
    ap = open(ap_path, encoding='utf-8').read()
    a, b = ap.find(SEGNO_A), ap.find(SEGNO_B)
    if a < 0 or b < 0:
        sys.exit('ROSSO: nell APERTURA mancano i segni del manifesto')
    ap = ap[:a] + manifesto + ap[b + len(SEGNO_B):]
    open(ap_path, 'w', encoding='utf-8').write(ap)

    leggi = lambda f: open(os.path.join(REPO, f), 'rb').read()
    b1 = zip_da(([(os.path.basename(diag), open(diag, 'rb').read())] if diag else []) + [(os.path.basename(pacchetto), pz), (md5_nome, md5_txt.encode()),
                 (os.path.basename(apertura_rel), ap.encode())] + rese)
    b2 = zip_da([(f, leggi(f)) for f in area])
    b3 = zip_da([(f, leggi(f)) for f in misure])
    b4 = zip_da([(f, leggi(f)) for f in aggiunta])
    kit = zip_da([(nomi[1], b1), (nomi[2], b2), (nomi[3], b3), (nomi[4], b4)])
    os.makedirs(uscita, exist_ok=True)
    out = os.path.join(uscita, kit_nome)
    open(out, 'wb').write(kit)
    return out, 'K%04d' % prec, k, ver + lettera, sess


# ───────────────────────────────────────────────────────────── verifica
def verifica(kit_path, prec, dati=None):
    """Ritorna la lista dei rossi (vuota = VERDE). Ogni esito nasce dalla rilettura della scatola."""
    rossi = []
    kz = zipfile.ZipFile(io.BytesIO(dati) if dati else kit_path)
    membri = kz.namelist()
    b1n = [n for n in membri if n.startswith('KIT-1-APERTURA-')]
    if not b1n:
        return ['1: busta 1 assente, il manifesto non si legge']
    b1 = zipfile.ZipFile(io.BytesIO(kz.read(b1n[0])))
    ap_n = [n for n in b1.namelist() if n.startswith('APERTURA-')]
    if not ap_n:
        return ['4: APERTURA assente nella busta 1']
    ap = b1.read(ap_n[0]).decode('utf-8')
    man = ap[ap.find(SEGNO_A):ap.find(SEGNO_B)]
    mk = re.search(r'^## MANIFESTO — KIT (K\d{4}) — (\S+) — (\S+)', man, re.M)
    nb = re.search(r'^BUSTE DICHIARATE: (\d+)', man, re.M)
    if not mk or not nb:
        return ['4: manifesto assente o senza intestazione']
    k, ver_l, sess = mk.groups()
    dich = {int(i): n for i, n in re.findall(r'^BUSTA (\d)\s+(KIT-\S+\.zip)', man, re.M)}
    # 1. buste dichiarate, con versione e sessione nel nome
    if len(dich) != int(nb.group(1)):
        rossi.append('1: buste dichiarate %s, righe di busta %d' % (nb.group(1), len(dich)))
    vs = ver_l[:-1].replace('.', '_')
    for i, n in dich.items():
        if n not in membri:
            rossi.append('1: busta %d dichiarata e assente (%s)' % (i, n))
        if vs not in n or sess not in n or k not in n:
            rossi.append('1: busta %d senza versione, sessione o progressivo nel nome' % i)
    # 10. un solo zip, esattamente le buste dichiarate
    if sorted(membri) != sorted(dich.values()):
        rossi.append('10: nel kit %d voci, dichiarate %d (sciolti o buste in più)' % (len(membri), len(dich)))
    # 2-3. busta 2 = elenco dichiarato, niente .py/.html/.zip
    el = re.search(r'^ELENCO DELL AREA, DICHIARATO \((\d+)\):\n((?:  \S.*\n?)*)', man, re.M)
    if not el:
        rossi.append('2: elenco dell area non dichiarato nel manifesto')
    elif 2 in dich and dich[2] in membri:
        atteso = sorted(l.strip() for l in el.group(2).splitlines() if l.strip())
        b2 = sorted(zipfile.ZipFile(io.BytesIO(kz.read(dich[2]))).namelist())
        if atteso != b2:
            rossi.append('2: busta 2 diversa dall elenco: mancano %s, in più %s'
                         % (sorted(set(atteso) - set(b2))[:5], sorted(set(b2) - set(atteso))[:5]))
        if len(atteso) != int(el.group(1)):
            rossi.append('2: elenco di %d nomi, dichiarato (%s)' % (len(atteso), el.group(1)))
        cattivi = [f for f in b2 if f.endswith(('.py', '.html', '.zip'))]
        if cattivi:
            rossi.append('3: in busta 2 %s' % cattivi[:5])
    # 4 bis. F027 (V-7): RelDoxAcademy Diagnostica viaggia col prodotto, stesso numero
    dg = re.search(r'pacchetto di diagnostica: (\S+\.zip)\s+md5 ([0-9a-f]{32})', man)
    if not dg or dg.group(1) not in b1.namelist():
        rossi.append('4: RelDoxAcademy Diagnostica assente in busta 1 (i due plugin viaggiano insieme)')
    elif hashlib.md5(b1.read(dg.group(1))).hexdigest() != dg.group(2):
        rossi.append('4: md5 della diagnostica non coincide')
    # 4. md5 e conteggi ricalcolati
    pk = re.search(r'^\s+(\S+\.zip)\s+md5 ([0-9a-f]{32})\n\s+(\d+) file', man, re.M)
    if not pk or pk.group(1) not in b1.namelist():
        rossi.append('4: pacchetto dichiarato assente in busta 1')
    else:
        pz = b1.read(pk.group(1))
        if md5(pz) != pk.group(2):
            rossi.append('4: md5 del pacchetto non coincide')
        nf = len([i for i in zipfile.ZipFile(io.BytesIO(pz)).infolist() if not i.is_dir()])
        if nf != int(pk.group(3)):
            rossi.append('4: file del pacchetto %d, dichiarati %s' % (nf, pk.group(3)))
    for i, rx in ((2, r'^BUSTA 2\s+\S+\s+(\d+) file'), (3, r'^BUSTA 3\s+\S+\s+(\d+) script'), (4, r'^BUSTA 4\s+\S+\s+(\d+) volumi')):
        mm = re.search(rx, man, re.M)
        if not mm:
            rossi.append('4: conteggio della busta %d non dichiarato' % i)
        elif i in dich and dich[i] in membri:
            n = len(zipfile.ZipFile(io.BytesIO(kz.read(dich[i]))).namelist())
            if n != int(mm.group(1)):
                rossi.append('4/8: busta %d con %d file, dichiarati %s (conteggio non ricalcolato)' % (i, n, mm.group(1)))
    # 5. prova di lettura: tre domande su riga
    pl = ap[ap.find('## PROVA DI LETTURA'):]
    if ap.find('## PROVA DI LETTURA') < 0 or len(re.findall(r'^\d\. .*r\.\d+', pl, re.M)) < 3:
        rossi.append('5: prova di lettura assente o con meno di tre domande su riga')
    # 9. progressivo che cresce
    if int(k[1:]) <= int(prec[1:]):
        rossi.append('9: progressivo %s non maggiore del precedente %s' % (k, prec))
    return rossi


def sabota(kit_path, modo):
    """Rompe una copia del kit in uno dei cinque modi noti; ritorna (dati, precedente)."""
    kz = zipfile.ZipFile(kit_path)
    m = {n: kz.read(n) for n in kz.namelist()}
    n1 = [n for n in m if n.startswith('KIT-1-')][0]
    k = re.search(r'K\d{4}', n1).group(0)
    prec = 'K%04d' % (int(k[1:]) - 1)
    if modo == 'busta':
        del m[[n for n in m if n.startswith('KIT-4-')][0]]
    elif modo == 'canonico':
        n2 = [n for n in m if n.startswith('KIT-2-')][0]
        b2 = zipfile.ZipFile(io.BytesIO(m[n2]))
        m[n2] = zip_da([(f, b2.read(f)) for f in b2.namelist() if f != 'docs/ROADMAP.md'])
    elif modo in ('conteggio', 'elenco'):
        b1 = zipfile.ZipFile(io.BytesIO(m[n1]))
        voci = []
        for f in b1.namelist():
            d = b1.read(f)
            if f.startswith('APERTURA-'):
                t = d.decode('utf-8')
                if modo == 'conteggio':
                    t = re.sub(r'^(BUSTA 3\s+\S+\s+)(\d+)', lambda x: x.group(1) + str(int(x.group(2)) + 1), t, flags=re.M)
                else:
                    t = t.replace('ELENCO DELL AREA, DICHIARATO', 'ELENCO TOLTO')
                d = t.encode('utf-8')
            voci.append((f, d))
        m[n1] = zip_da(voci)
    elif modo == 'progressivo':
        prec = k
    return zip_da(list(m.items())), prec


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    cmd = sys.argv[1]
    if cmd == 'componi':
        out, prec, k, ver_l, sess = componi(*sys.argv[2:8])
        rossi = verifica(out, prec)
        if rossi:
            print('ROSSO: ' + ' | '.join(rossi))
            sys.exit(1)
        for modo in ('busta', 'canonico', 'conteggio', 'progressivo', 'elenco'):
            dati, p = sabota(out, modo)
            if not verifica(None, p, dati):
                print('ROSSO: il controllo resta verde col sabotaggio «%s»' % modo)
                sys.exit(1)
        with open(REGISTRO_K, 'a', encoding='utf-8') as f:
            f.write('%s  %s  %s  %s  %s\n' % (k, ver_l, sess, os.path.basename(out),
                                              datetime.datetime.now().strftime('%d/%m/%Y %H:%M')))
        print('VERDE: %s · %s byte · md5 %s · 5 sabotaggi tutti rossi · progressivo %s registrato'
              % (out, os.path.getsize(out), md5(open(out, 'rb').read()), k))
    elif cmd == 'verifica':
        rossi = verifica(sys.argv[2], sys.argv[3])
        print('ROSSO: ' + ' | '.join(rossi) if rossi else 'VERDE')
        sys.exit(1 if rossi else 0)
    elif cmd == 'sabotaggi':
        tutti_rossi = True
        for modo in ('busta', 'canonico', 'conteggio', 'progressivo', 'elenco'):
            dati, p = sabota(sys.argv[2], modo)
            r = verifica(None, p, dati)
            print('[%s] sabotaggio %-11s → %s' % ('ROSSO' if r else 'VERDE?', modo, r[0] if r else 'nessun rosso'))
            tutti_rossi &= bool(r)
        sys.exit(0 if tutti_rossi else 1)
    else:
        sys.exit(__doc__)


if __name__ == '__main__':
    main()
