#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""genera-analisi-tecnica.py — RelDoxAcademy · Formazione F004 · 17/11/2026.
⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Nasce da RelDoxFood 1.24.11L, banco/S67-1-genera-analisi-tecnica.py (stessa grammatica: gettoni di stato,
barra di stato per sezione, indice con il pallino, pagina autonoma senza font esterni, tema chiaro e scuro).
Tolte le figure della ristorazione; marchio Academy (indaco #3E3F9B).

uso:  python3 -I strumenti/genera-analisi-tecnica.py docs/ANALISI-TECNICA-RELDOXACADEMY.md docs/ANALISI-TECNICA-RELDOXACADEMY.html <pacchetto>
⛔ Scrive, rilegge e dichiara: un gettone sconosciuto, o una sezione «§ n» senza stato (tranne § 0 e gli allegati), è ROSSO.
"""
import html, re, sys
from pathlib import Path
import markdown

STATI = {
    'fatto':      ('FATTO', 'verificato sul server'),
    'consegnato': ('CONSEGNATO', 'da verificare sul server'),
    'proposta':   ('PROPOSTA', 'aspetta una decisione'),
    'dafare':     ('DA FARE', 'non ancora sviluppato'),
}


CSS = r"""
:root{--ok-soft:#E6F4EC;--brand:#DC0B15;--brand-soft:#FDECEC;--ink:#161611;--teal:#2A8FAA;--teal-soft:#E8F3F7;--bg:#FAF8F6;--surface:#FFFFFF;--surface-2:#F3EFEC;--line:#E3DCD6;--line-strong:#CFC6BE;--text:#1C1A18;--text-2:#4A443E;--muted:#7A726A;--ok:#136B45;--warn:#9A6209;--warn-soft:#FBF0DC;--ko:#B4121B;--ko-soft:#FDEBEB;--navy:#1E3A5F;
--mono:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;--sans:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif}
@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--ok:#4CC38A;--ok-soft:#10261B;--brand:#FF5A5F;--brand-soft:#3A1A1B;--ink:#EDE8E2;--teal:#5FB9D4;--teal-soft:#10262D;--bg:#131211;--surface:#1C1A18;--surface-2:#252220;--line:#332E2A;--line-strong:#463F39;--text:#EDE8E2;--text-2:#C3BBB2;--muted:#948B82;--warn:#E0A94A;--warn-soft:#2B2113;--ko:#FF6B6B;--ko-soft:#301618;--navy:#2B4F7E}}
:root[data-theme="dark"]{--ok:#4CC38A;--ok-soft:#10261B;--brand:#FF5A5F;--brand-soft:#3A1A1B;--ink:#EDE8E2;--teal:#5FB9D4;--teal-soft:#10262D;--bg:#131211;--surface:#1C1A18;--surface-2:#252220;--line:#332E2A;--line-strong:#463F39;--text:#EDE8E2;--text-2:#C3BBB2;--muted:#948B82;--warn:#E0A94A;--warn-soft:#2B2113;--ko:#FF6B6B;--ko-soft:#301618;--navy:#2B4F7E}
*{box-sizing:border-box}html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--text);font-family:var(--sans);font-size:16px;line-height:1.62;-webkit-font-smoothing:antialiased}
p,li,td,th{overflow-wrap:break-word}code{font-family:var(--mono);font-size:.88em;background:var(--surface-2);padding:1px 5px;border-radius:4px;overflow-wrap:anywhere}
pre{background:var(--surface-2);border:1px solid var(--line);border-radius:10px;padding:14px 16px;overflow-x:auto;font-size:13px;line-height:1.5}pre code{background:none;padding:0}
.mast{background:var(--surface);border-bottom:2px solid var(--ink)}
.mast-in{max-width:1240px;margin:0 auto;padding:24px 20px 20px;display:flex;flex-wrap:wrap;gap:14px 28px;align-items:flex-end;justify-content:space-between}
.mark{font-weight:800;font-size:24px;letter-spacing:-.02em;line-height:1.1;display:flex;align-items:center;gap:8px}.mark svg{flex:0 0 30px}.mark em{font-style:normal;color:#E07A5F}
.sub{font-family:var(--mono);font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-top:6px}
.meta{font-family:var(--mono);font-size:12px;color:var(--text-2);text-align:right}
.wrap{max-width:1240px;margin:0 auto;padding:0 20px}
.layout{display:grid;grid-template-columns:minmax(0,1fr);gap:32px}
@media(min-width:1040px){.layout{grid-template-columns:240px minmax(0,1fr);gap:52px}}
nav.toc{position:sticky;top:0;align-self:start;padding:24px 0;max-height:100vh;overflow:auto;font-size:14px}
@media(max-width:1039px){nav.toc{position:static;max-height:none;padding:16px 0 0;border-bottom:1px solid var(--line)}}
nav.toc b{display:block;font-family:var(--mono);font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);margin-bottom:8px}
nav.toc a{display:block;padding:5px 0 5px 10px;border-left:2px solid var(--line);color:var(--text-2);text-decoration:none;line-height:1.35}
nav.toc a:hover{border-color:var(--brand);color:var(--text)}
main{min-width:0;padding:16px 0 64px}
h1{font-size:clamp(26px,4vw,34px);line-height:1.15;margin:24px 0 12px;letter-spacing:-.015em}
h2{font-size:clamp(20px,2.6vw,24px);line-height:1.2;margin:48px 0 14px;padding-top:14px;border-top:1px solid var(--line);scroll-margin-top:12px}
h3{font-size:18px;margin:28px 0 10px}
p,ul,ol{max-width:72ch}a{color:var(--brand)}
.tw{overflow-x:auto;-webkit-overflow-scrolling:touch;margin:14px 0 20px;border:1px solid var(--line);border-radius:10px;background:var(--surface)}
table{border-collapse:collapse;width:100%;font-size:14.5px}
th,td{text-align:left;padding:9px 12px;border-bottom:1px solid var(--line);vertical-align:top}
th{background:var(--surface-2);font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-2)}
tr:last-child td{border-bottom:0}
blockquote{margin:16px 0;padding:10px 16px;border-left:3px solid var(--brand);background:var(--brand-soft);border-radius:0 8px 8px 0}
figure{margin:22px 0 28px;background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(22,22,17,.05)}
figure svg{display:block;width:100%;height:auto}
figcaption{font-family:var(--mono);font-size:12px;color:var(--muted);margin-top:10px;letter-spacing:.02em}
figure.prop{border-color:var(--warn);background:linear-gradient(var(--warn-soft),var(--surface) 60px)}
.t{fill:var(--text);font:600 14px var(--sans)}.t-h{fill:var(--text);font:700 15px var(--sans)}.t-h2{fill:var(--text);font:700 14px var(--sans)}.t-s{fill:var(--text-2);font:12.5px var(--sans)}.t-ko{fill:var(--ko);font:600 12.5px var(--sans)}
.b-soft{fill:var(--surface-2);stroke:var(--line-strong)}.b-main{fill:var(--surface);stroke:var(--ink);stroke-width:1.5}.b-brand{fill:var(--brand-soft);stroke:var(--brand)}.b-teal{fill:var(--teal-soft);stroke:var(--teal)}.b-ko{fill:var(--ko-soft);stroke:var(--ko)}.b-warn{fill:var(--warn-soft);stroke:var(--warn)}
.l{fill:none;stroke:var(--ink);stroke-width:1.6}.l-teal{stroke:var(--teal)}.l-dash{stroke-dasharray:5 4}.f-ink{fill:var(--ink)}
.m-bg{fill:#F3F4F6}.m-rail{fill:#1E3A5F}.m-logo{fill:#DC0B15}.m-it{fill:transparent}.m-on{fill:#fff}.m-back{fill:rgba(255,255,255,.1);stroke:rgba(255,255,255,.25)}
.m-rt{fill:#C7D6EA;font:600 10px var(--sans)}.m-ont{fill:#1E3A5F;font:700 11px var(--sans)}.m-rh{fill:#7E9AC0;font:700 8.5px var(--sans);letter-spacing:.06em;text-transform:uppercase}
.m-top{fill:#fff;stroke:#E5E7EB}.m-sym{fill:#F2B33D}.m-tb{fill:#111827;font:700 12.5px var(--sans)}.m-tp{fill:#6B7280;font:12px var(--sans)}
.m-chip{fill:#E5EAF1}.m-ct{fill:#1E3A5F;font:600 9.5px var(--sans)}.m-av{fill:#D6E4F0}.m-btn{fill:#fff;stroke:#E2E8F0}.m-gear{fill:#1E3A5F}.m-gt{fill:#fff;font:700 14px var(--sans)}
.m-card{fill:#fff;stroke:#E5E7EB}.m-cht{fill:#111827;font:700 12.5px var(--sans)}.m-sep{stroke:#E5E7EB}.m-tx{fill:#374151;font:12px var(--sans)}.m-s{fill:#6B7280;font:11.5px var(--sans)}
.m-in{fill:#fff;stroke:#D1D5DB}.m-pri{fill:#2E75B6}.m-pri+text,.m-pri~text.m-ont{fill:#fff}.m-tab{fill:#F3F4F6;stroke:#E5E7EB}.m-tabon{fill:#D6E4F0;stroke:#2E75B6}.m-tabd{fill:#FEF3C7;stroke:#D97706;stroke-dasharray:4 3}
.st{display:inline-block;font-family:var(--mono);font-size:11px;font-weight:700;letter-spacing:.06em;padding:2px 9px;border-radius:999px;border:1px solid;white-space:nowrap;vertical-align:1px}
.st::before{content:"";display:inline-block;width:7px;height:7px;border-radius:50%;background:currentColor;margin-right:6px;vertical-align:1px}
.st-fatto{color:var(--ok);background:var(--ok-soft);border-color:var(--ok)}.st-consegnato{color:var(--teal);background:var(--teal-soft);border-color:var(--teal)}
.st-proposta{color:var(--warn);background:var(--warn-soft);border-color:var(--warn);border-style:dashed}.st-dafare{color:var(--muted);background:var(--surface-2);border-color:var(--line-strong)}
.stato{display:flex;flex-wrap:wrap;gap:6px 12px;align-items:center;margin:-4px 0 18px;padding:9px 14px;border-radius:10px;border-left:4px solid;background:var(--surface);font-size:14.5px;color:var(--text-2);max-width:none}
.stato.st-fatto,.stato.st-consegnato,.stato.st-proposta,.stato.st-dafare{font-family:var(--sans);font-weight:400;letter-spacing:0;white-space:normal}
.stato.st-fatto{border-color:var(--ok)}.stato.st-consegnato{border-color:var(--teal)}.stato.st-proposta{border-color:var(--warn)}.stato.st-dafare{border-color:var(--line-strong)}
.stato.st-fatto::before,.stato.st-consegnato::before,.stato.st-proposta::before,.stato.st-dafare::before{display:none}
.stato .st{flex:0 0 auto}.stato span.q{color:var(--muted);font-size:13px}
nav.toc a i{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:8px;vertical-align:1px;background:var(--line-strong)}
nav.toc a i.i-fatto{background:var(--ok)}nav.toc a i.i-consegnato{background:var(--teal)}nav.toc a i.i-proposta{background:var(--warn)}nav.toc a i.i-dafare{background:var(--line-strong)}
.sb-fatto{fill:var(--ok)}.sb-consegnato{fill:var(--teal)}.sb-proposta{fill:var(--warn)}.sb-dafare{fill:var(--line-strong)}.sb-cornice{fill:none;stroke:var(--ink);stroke-width:1}
.sb-t{font:700 11px var(--mono);fill:var(--surface)}.sb-t-dafare{fill:var(--text)}
.tl-fatto{fill:var(--ok-soft);stroke:var(--ok);stroke-width:1.5}.tl-consegnato{fill:var(--teal-soft);stroke:var(--teal);stroke-width:1.5}.tl-proposta{fill:var(--warn-soft);stroke:var(--warn);stroke-width:1.5;stroke-dasharray:5 4}.tl-dafare{fill:var(--surface-2);stroke:var(--line-strong)}
.tl-n{fill:var(--text);font:800 20px var(--sans)}.tl-c{fill:var(--text-2);font:11.5px var(--sans)}
.tl-b-fatto{fill:var(--ok)}.tl-b-consegnato{fill:var(--teal)}.tl-b-proposta{fill:var(--warn)}.tl-b-dafare{fill:var(--line-strong)}
.tl-bt{font:700 10px var(--mono);fill:var(--surface);letter-spacing:.05em}
.solo-stretto{display:none}@media(max-width:640px){.solo-largo{display:none}.solo-stretto{display:block}}.tl-bt-dafare{fill:var(--text)}
.legenda{display:flex;flex-wrap:wrap;gap:8px 16px;margin:8px 0 0}
.badge{display:inline-block;font-family:var(--mono);font-size:11px;padding:2px 8px;border-radius:999px;background:var(--teal-soft);color:var(--teal);vertical-align:middle}
footer{border-top:1px solid var(--line);padding:22px 20px 36px;font-family:var(--mono);font-size:12px;color:var(--muted);max-width:1240px;margin:0 auto}
@media print{nav.toc{display:none}.layout{display:block}h2{break-after:avoid}figure,table{break-inside:avoid}}
"""

# S6 · 2026-10-04 — il simbolo del prodotto approvato dall'owner («Posate nel sole», kit LOGO-RELDOXFOOD-v1), in testata e come favicon.

SIMBOLO = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="30" height="30" role="img" aria-label="RelDoxAcademy">'
    '<rect x="6" y="6" width="88" height="88" rx="22" fill="#3E3F9B"/><path d="M22 44 50 30l28 14-28 14z" fill="#fff"/>'
    '<path d="M34 51v13c8 6 24 6 32 0V51L50 59z" fill="#fff" opacity=".85"/><path d="M78 44v16" stroke="#E8B23A" stroke-width="4" stroke-linecap="round"/></svg>')
FAVICON = 'data:image/svg+xml,' + SIMBOLO.replace('"', "'").replace('#', '%23').replace('<', '%3C').replace('>', '%3E')
SEGN = ('id', 'nome', 'prodotto', 'versione', 'lingua', 'tema', 'percorso', 'nn', 'n')


def main():
    src, out, ver = Path(sys.argv[1]), Path(sys.argv[2]), sys.argv[3]
    md = src.read_text(encoding='utf-8')
    md = re.sub(r'\{(%s)\}' % '|'.join(SEGN), lambda m: '⟨' + m.group(1) + '⟩', md)
    ignoti = sorted(set(re.findall(r'\{([a-z]+)\}', md)) - set(STATI))
    if ignoti:
        print('ROSSO — gettoni di stato sconosciuti:', ', '.join(ignoti)); return 1
    m_ed = re.search(r'\*\*Edizione (\d+) · ([^*]+?)\*\*', md)
    edizione = 'edizione %s · %s' % (m_ed.group(1), m_ed.group(2).split('·')[0].strip()) if m_ed else 'edizione ?'
    md = re.sub(r'^<p><img[^\n]*</p>\n', '', md)
    corpo = markdown.markdown(md, extensions=['tables', 'fenced_code', 'toc'], extension_configs={'toc': {'slugify': lambda v, s: re.sub(r'[^a-z0-9]+', '-', v.lower()).strip('-')}})
    corpo = re.sub(r'<table>', '<div class="tw"><table>', corpo).replace('</table>', '</table></div>')
    stato_sez = {}
    def barra(m):
        stato_sez[m.group(1)] = m.group(3)
        return '%s\n<p class="stato st-%s"><span class="st st-%s">%s</span> <span>%s</span> <span class="q">%s</span></p>' % (m.group(0)[:m.group(0).index('<p>')].rstrip(), m.group(3), m.group(3), STATI[m.group(3)][0], m.group(4), STATI[m.group(3)][1])
    corpo = re.sub(r'<h2 id="([^"]+)">([^<]*)</h2>\s*<p>\{([a-z]+)\} (.*?)</p>', barra, corpo, flags=re.S)
    corpo = re.sub(r'\{([a-z]+)\}', lambda m: '<span class="st st-%s">%s</span>' % (m.group(1), STATI[m.group(1)][0]), corpo)
    senza = [m.group(2) for m in re.finditer(r'<h2 id="([^"]+)">(.*?)</h2>', corpo) if m.group(1) not in stato_sez and re.match(r'§ ?([1-9]|1[0-9]) ', m.group(2))]
    if senza:
        print('ROSSO — sezioni senza stato:', ' | '.join(senza)); return 1
    toc = ''.join('<a href="#%s"><i class="i-%s"></i>%s</a>' % (m.group(1), stato_sez.get(m.group(1), 'nessuno'), re.sub('<[^>]+>', '', m.group(2))) for m in re.finditer(r'<h2 id="([^"]+)">(.*?)</h2>', corpo))
    leg = '<div class="legenda" aria-label="Legenda degli stati">' + ''.join('<span class="st st-%s">%s</span>' % (k, v[0]) for k, v in STATI.items()) + '</div>'
    pagina = ('<!DOCTYPE html>\n<html lang="it">\n<head>\n<meta charset="utf-8">\n<meta name="viewport" content="width=device-width, initial-scale=1">\n'
        '<meta name="robots" content="noindex, nofollow">\n<title>RelDoxAcademy — Analisi tecnica</title>\n<link rel="icon" type="image/svg+xml" href="' + FAVICON + '">\n<style>' + CSS + '\n.tw{overflow-x:auto;max-width:100%}main{min-width:0}code{overflow-wrap:anywhere}</style>\n</head>\n<body>\n'
        '<header class="mast"><div class="mast-in"><div><div class="mark">' + SIMBOLO + '<span><span style="color:#3E3F9B">R</span>el<span style="color:#E8B23A">D</span>ox<em style="color:#3E3F9B">Academy</em> · Analisi tecnica</span></div>'
        '<div class="sub">documento della struttura del prodotto · famiglia RelDox · Idea Technologies · ⛔ riservato, non divulgabile</div></div>'
        '<div class="meta">' + html.escape(ver) + '<br>' + html.escape(edizione) + '</div>' + leg + '</div></header>\n'
        '<div class="wrap"><div class="layout"><nav class="toc" aria-label="Indice"><b>Indice</b>' + toc + '</nav>\n<main>' + corpo + '</main></div></div>\n'
        '<footer>Generato dal gemello .md con strumenti/genera-analisi-tecnica.py · il .md e questo .html si aggiornano insieme · ' + html.escape(ver) + '</footer>\n</body>\n</html>\n')
    pagina = re.sub('⟨([a-z]+)⟩', lambda m: '&#123;' + m.group(1) + '&#125;', pagina)
    out.write_text(pagina, encoding='utf-8')
    letto = out.read_text(encoding='utf-8')
    n_sez, n_bar = letto.count('<h2 id='), letto.count('<p class="stato st-')
    ok = n_sez == md.count('\n## ') and n_bar == len(stato_sez) and re.search(r'\{[a-z]+\}', re.sub(r'<style>.*?</style>', '', letto, flags=re.S)) is None
    print('%s — %s · %d sezioni, %d con la barra di stato · %s · %d byte' % ('VERDE' if ok else 'ROSSO', out.name, n_sez, n_bar, edizione, len(letto.encode('utf-8'))))
    return 0 if ok else 1


if __name__ == '__main__':
    sys.exit(main())
