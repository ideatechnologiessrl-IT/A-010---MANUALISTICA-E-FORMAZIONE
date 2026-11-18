#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""copia-con-segno.py — RelDoxAcademy · Formazione F005 · 17/11/2026.
⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Copia UN file di RelDoxFood nella copia dell Academy con nome e segno nuovi (analisi tecnica ed. 4, § 1).
RelDoxFood si legge e basta. ⛔ Si usa solo DOPO aver letto il file per intero (REGOLE-FAMIGLIA v4 § 2):
il comando lo scrive nel registro `docs/REGISTRO-LETTURA-BLOCCO-G.md` con le righe e l impronta della fonte.

uso:  python3 -I strumenti/copia-con-segno.py <repo RelDoxFood> <percorso nel repo RelDoxFood> <decisione> "<nota>"
"""
import hashlib, sys, datetime
from pathlib import Path

SEGNI = [('reldoxfood-diagnostica', 'reldox-academy-diagnostica'), ('reldoxfood', 'reldox-academy'),
         ('RelDoxFood', 'RelDoxAcademy'), ('RELDOXFOOD', 'RELDOXACADEMY'), ('Rdfood', 'Rdacademy'), ('RDFOOD', 'RDACADEMY'), ('rdfood', 'rdacademy')]

def segna(s):
    for a, b in SEGNI: s = s.replace(a, b)
    return s

def main():
    if len(sys.argv) < 5: sys.exit(__doc__)
    food, rel, dec, nota = Path(sys.argv[1]), sys.argv[2], sys.argv[3], sys.argv[4]
    rep = Path(__file__).resolve().parent.parent
    src = food / rel
    dati = src.read_bytes()
    dst = rep / segna(rel)
    dst.parent.mkdir(parents=True, exist_ok=True)
    try:
        testo = dati.decode('utf-8'); dst.write_text(segna(testo), encoding='utf-8'); n = testo.count('\n') + (0 if testo.endswith('\n') else 1)
    except UnicodeDecodeError:
        dst.write_bytes(dati); n = 0
    reg = rep / 'docs/REGISTRO-LETTURA-BLOCCO-G.md'
    riga = '| %s | `%s` | %s | %s | `%s` | %s |\n' % (datetime.datetime.now().strftime('%d/%m %H:%M'), rel, n or '—', dec, hashlib.md5(dati).hexdigest()[:10], nota)
    with reg.open('a', encoding='utf-8') as f: f.write(riga)
    print('copiato %s → %s (%s righe)' % (rel, dst.relative_to(rep), n or '—'))

if __name__ == '__main__':
    main()
