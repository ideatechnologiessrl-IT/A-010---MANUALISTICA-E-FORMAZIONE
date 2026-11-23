#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
riga-registri.py — RelDoxAcademy · Formazione F030 · 23/11/2026
⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Nasce dal fallimento F030-F1 (terza violazione della classe «regole di famiglia lette e non applicate»: V-5, V-6, V-8).
Conta i quattro registri nei file (mai a mano) e scrive la riga «Registri» del nodo; con lo STATO.md del nodo
dice ROSSO se la riga scritta lì non è quella vera.

uso:
  python3 -I strumenti/riga-registri.py <repository A-010> [<nodo>/bacheca/formazione/STATO.md]
"""
import datetime, os, re, sys

REPO = sys.argv[1] if len(sys.argv) > 1 else '.'


def leggi(rel):
    with open(os.path.join(REPO, rel), encoding='utf-8') as h:
        return h.read()


def riga_vera(oggi=None):
    oggi = oggi or datetime.date.today()
    # BUG: schede «### BUG-n» il cui Stato non è chiusa o respinta
    bug = leggi('docs/BUG-FORMAZIONE.md')
    aperti_bug = 0
    for blocco in re.split(r'^### ', bug, flags=re.M)[1:]:
        if not blocco.startswith('BUG-'):
            continue
        m = re.search(r'^- \*\*Stato\*\*:\s*(.*)$', blocco, re.M)
        if m and not re.match(r'(chius|respint)', m.group(1).strip(), re.I):
            aperti_bug += 1
    # PUNTI APERTI: righe «| Fn |» della roadmap; con stelle = seconda colonna con ★
    righe = [l for l in leggi('docs/ROADMAP.md').split('\n') if re.match(r'^\| F\d+ \|', l)]
    stelle = sum(1 for l in righe if '★' in l.split('|')[2])
    # VIOLAZIONI: schede «### V-n · gg/mm/aaaa»
    viol = re.findall(r'^### V-\d+ · (\d{2})/(\d{2})/(\d{4})', leggi('docs/VIOLAZIONI-FORMAZIONE.md'), re.M)
    recenti = sum(1 for g, m, a in viol if (oggi - datetime.date(int(a), int(m), int(g))).days < 7)
    # FALLIMENTI: schede «### <sessione>-Fn — gg/mm/aaaa»; l ultimo è il più recente per data
    fal = re.findall(r'^### (F\d+-F\d+) — (\d{2})/(\d{2})/(\d{4})', leggi('docs/FALLIMENTI-FORMAZIONE.md'), re.M)
    ultimo = max(fal, key=lambda x: (x[3], x[2], x[1], int(x[0][1:].split('-')[0])))[0] if fal else 'nessuno'
    return ('**Registri:** BUG aperti %d/5 · punti aperti %d (con stelle %d) · violazioni %d (ultimi 7 giorni %d)'
            ' · fallimenti %d (ultimo %s)' % (aperti_bug, len(righe), stelle, len(viol), recenti, len(fal), ultimo))


def main():
    vera = riga_vera()
    print(vera)
    if len(sys.argv) > 2:
        with open(sys.argv[2], encoding='utf-8') as h:
            scritte = [l.strip() for l in h if l.startswith('**Registri:**')]
        if not scritte:
            print('ROSSO: nessuna riga «Registri» in ' + sys.argv[2])
            return 1
        if not scritte[0].startswith(vera):
            print('ROSSO: nel nodo c è\n  ' + scritte[0] + '\nla riga vera è\n  ' + vera)
            return 1
        print('VERDE: la riga «Registri» del nodo è quella dei file')
    return 0


if __name__ == '__main__':
    sys.exit(main())
