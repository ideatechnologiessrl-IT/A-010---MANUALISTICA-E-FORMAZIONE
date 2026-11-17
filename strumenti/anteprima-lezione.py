"""Anteprima HTML di una lezione dal suo Markdown (modello `modelli/MODELLO-LEZIONE.md`, stile v1.1, Iris v1).
Formazione F002 · 17/11/2026. Una sola fonte: il Markdown; l HTML serve a provare la lezione su computer, tablet e telefono.
Uso: python3 -I strumenti/anteprima-lezione.py <lezione.md> [...]   → scrive <lezione>.html accanto.
Il foglio di stile è quello della lezione d esempio approvata (mockup/MOCKUP-LEZIONE-ESEMPIO-v1.html)."""
import html, os, re, sys

RADICE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ESEMPIO = os.path.join(RADICE, 'mockup', 'MOCKUP-LEZIONE-ESEMPIO-v1.html')

CSS_IN_PIU = """
.trenta b{font-size:14px}
.scala{grid-template-columns:minmax(0,1fr)}.scala li span{min-width:0;overflow-wrap:anywhere}
.passo{grid-template-columns:320px 1fr}
.passo img.fig{width:100%;height:auto;max-width:320px;justify-self:center;order:-1}
.iris{display:flex;gap:14px;align-items:center;background:var(--vio-bg);border-radius:14px;padding:10px 16px;margin:0 0 18px}
.iris img{width:76px;height:auto;flex-shrink:0}
.iris p{margin:0;font-size:17px;color:#2E2470}.iris b{color:var(--vio)}
.tappe{display:flex;gap:6px;margin:0 0 14px;flex-wrap:wrap}.tappe span{width:28px;height:8px;border-radius:4px;background:var(--line)}.tappe span.fatta{background:var(--brand)}.tappe span.qui{background:var(--gold)}
@media (min-width:761px) and (max-width:1024px){.passo{grid-template-columns:260px 1fr}}
@media (max-width:760px){.passo{grid-template-columns:1fr}.passo img.fig{max-width:300px}.trenta b{font-size:13px;min-width:92px}.iris img{width:60px}.iris p{font-size:16px}}
"""

RIQ = {'Suggerimento': 'r-sug', 'Ricorda': 'r-ric', 'Attenzione': 'r-att', 'Se non funziona': 'r-fix'}


def inl(s):
    s = html.escape(s.strip(), quote=False)
    s = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', s)
    s = re.sub(r'(?<![\w*])\*(.+?)\*(?![\w*])', r'<em>\1</em>', s)
    s = re.sub(r'`(.+?)`', r'<code>\1</code>', s)
    return s


def att(s):
    return html.escape(re.sub(r'[*`]', '', s), quote=True)


def intestazione(t):
    m = re.match(r'---\n(.*?)\n---\n', t, re.S)
    d, chiave = {}, None
    for r in m.group(1).splitlines():
        if re.match(r'^\S[^:]*:\s*$', r):
            chiave = r[:-1].strip(); d[chiave] = {}
        elif re.match(r'^\s+\S[^:]*:\s', r) and isinstance(d.get(chiave), dict):
            k, v = r.strip().split(':', 1); d[chiave][k.strip()] = v.strip()
        elif re.match(r'^\S[^:]*:\s', r):
            k, v = r.split(':', 1); d[k.strip()] = v.strip(); chiave = None
    return d, t[m.end():]


def sezione(corpo, n):
    m = re.search(r'^## %d · .*?\n(.*?)(?=^## |\Z)' % n, corpo, re.S | re.M)
    return m.group(1).strip() if m else ''


def costruisci(percorso_md):
    t = open(percorso_md, encoding='utf-8').read()
    d, corpo = intestazione(t)
    dove = os.path.dirname(os.path.abspath(percorso_md))
    risorse = os.path.relpath(os.path.join(RADICE, 'risorse'), dove).replace(os.sep, '/')
    es = open(ESEMPIO, encoding='utf-8').read()
    css = re.search(r'<style>(.*?)</style>', es, re.S).group(1) + CSS_IN_PIU
    icone = re.findall(r'<div class="riq [^"]+">(<svg.*?</svg>)', es)
    script = re.search(r'<script>.*?</script>', es, re.S).group(0)
    iris = d.get('iris', {})
    titolo = d['titolo']
    tappa, tot = (int(x) for x in d.get('tappa', '1/1').split('/'))

    # 1 · In 30 secondi
    s1 = sezione(corpo, 1)
    trenta = re.findall(r'\*\*(Saprai|Ci vogliono|Ti serve):\*\*\s*(.*?)(?=\s*\*\*(?:Saprai|Ci vogliono|Ti serve):|\Z)', s1, re.S)
    trenta_html = ''.join(f'<div><b>{k}</b><span>{inl(v.rstrip("."))}</span></div>' for k, v in trenta)

    # 2 · Passo per passo
    passi, fig_n = [], 0
    for blocco in re.split(r'\n(?=\d+\. )', sezione(corpo, 2)):
        m = re.match(r'(\d+)\. (.*?)→ \*vedi:\*\s*(.*?)\n\s*!\[(.*?)\]\((.*?)\)', blocco, re.S)
        if not m:
            continue
        n, gesto, vedi, alt, src = m.groups()
        fig_n += 1
        w, h = 320, 220
        try:
            vb = re.search(r'viewBox="0 0 (\d+) (\d+)"', open(os.path.join(dove, src), encoding='utf-8').read())
            w, h = 320, round(320 * int(vb.group(2)) / int(vb.group(1)))
        except (OSError, AttributeError):
            pass
        car = 'eager' if fig_n < 3 else 'lazy'
        passi.append(f'''
 <div class="passo">
  <div>
   <div class="n">Passo {n}</div>
   <div class="gesto">{inl(gesto)}</div>
   <div class="vedi"><b>Vedi:</b> {inl(vedi)}</div>
  </div>
  <img class="fig" src="{src}" alt="{att(alt)}" width="{w}" height="{h}" loading="{car}" decoding="async">
 </div>''')

    # 3 · Riquadri
    riq = []
    for tipo, testo in re.findall(r'^> \*\*\S+ (Suggerimento|Ricorda|Attenzione|Se non funziona)\*\* — (.*)$', sezione(corpo, 3), re.M):
        i = list(RIQ).index(tipo)
        riq.append(f' <div class="riq {RIQ[tipo]}">{icone[i]}<div><b>{tipo}</b>{inl(testo)}</div></div>')

    # 4 · Prova tu
    s4 = sezione(corpo, 4)
    compito, atteso = re.split(r'→\s*\*\*ce l hai fatta se\*\*', s4)
    prova = f'{inl(compito)}<br><span class="ok">Ce l hai fatta se</span> {inl(atteso.rstrip("."))}.'

    # 5 · Hai capito?  (ogni domanda: «N. testo — a) … b) … c) …» e sotto «   - giusta: x · sì: … · no: …»)
    dom = []
    for q in re.findall(r'^(\d+\. .*?)\n\s+- giusta: (\w) · sì: (.*?) · no: (.*?)$', sezione(corpo, 5), re.M):
        testa, giusta, si, no = q
        domanda, opz = testa.split(' — ', 1)
        voci = re.findall(r'(\w)\) (.*?)(?= \w\) |$)', opz)
        n = domanda.split('.')[0]
        lab = ''.join(f'<label class="sc"><input type="radio" name="q{n}" value="{l}">{inl(o)}</label>' for l, o in voci)
        dom.append(f'''
 <fieldset data-giusta="{giusta}" data-perche-si="{att(si)}" data-perche-no="{att(no)}">
  <legend>{inl(domanda)}</legend>
  {lab}
  <div class="esito" role="status"></div>
 </fieldset>''')

    # 6 · In una riga
    riga = inl(sezione(corpo, 6).replace('**', ''))

    # 7 · Se ancora non va
    scala = re.findall(r'\d\. (.*?)(?=\s\d\. |\Z)', sezione(corpo, 7), re.S)
    scala_html = ''.join(f'<li><span class="g">{i}</span><span>{inl(s)}</span></li>' for i, s in enumerate(scala, 1))

    tappe = ''.join(f'<span class="{"fatta" if i < tappa else ("qui" if i == tappa else "")}"></span>' for i in range(1, tot + 1))
    prec, succ = d.get('nav_prec', '← La strada del percorso'), d.get('nav_succ', 'Avanti →')

    pagina = f'''<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>{html.escape(titolo)} — {html.escape(d.get('percorso', ''))} · RelDoxAcademy</title>
<style>{css}</style>
</head>
<body>
<div class="nota-mockup" role="note"><b>Anteprima</b> Lezione {html.escape(d.get('numero', ''))} · v{d['versione']} · {d['data']} · generata dal Markdown · {html.escape(d['vale_per'])}, dati inventati · ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale</div>

<header class="testata"><img src="{risorse}/logo-academy/reldoxacademy-lockup-color.svg" alt="RelDoxAcademy" width="160" height="30"><span class="ut">Laura B. · amministratore</span></header>

<main>
<p class="briciole"><a href="#">Il mio catalogo</a> › {html.escape(d.get('prodotto', ''))} › {html.escape(d.get('percorso', ''))}</p>
<div class="tappe" aria-label="Tappa {tappa} di {tot}">{tappe}</div>
<h1>{html.escape(titolo)}</h1>
<p class="meta">Lezione {html.escape(d.get('numero', ''))} · vale per {html.escape(d['vale_per'])}</p>

<div class="iris"><img src="{risorse}/assistente/web/iris-indica-foglio.webp" alt="Iris indica la strada a tappe" width="76" height="76"><p><b>Iris:</b> {inl(iris.get('strada', '').strip('«»'))} {inl(iris.get('perche_conta', '').strip('«»'))}</p></div>

<section class="blocco" aria-labelledby="h1s">
 <h2 id="h1s"><span class="num">1</span>In 30 secondi</h2>
 <div class="trenta">{trenta_html}</div>
</section>

<section class="blocco" aria-labelledby="h2s">
 <h2 id="h2s"><span class="num">2</span>Passo per passo</h2>{''.join(passi)}
</section>

<section class="blocco" aria-labelledby="h3s">
 <h2 id="h3s"><span class="num">3</span>Da sapere</h2>
{chr(10).join(riq)}
</section>

<section class="blocco" aria-labelledby="h4s">
 <h2 id="h4s"><span class="num">4</span>Prova tu</h2>
 <div class="prova">{prova}</div>
</section>

<section class="blocco" aria-labelledby="h5s">
 <h2 id="h5s"><span class="num">5</span>Hai capito?</h2>{''.join(dom)}
</section>

<div class="riga" style="margin-bottom:18px"><svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="#E8B23A" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg><div><small>In una riga</small>{riga}</div></div>

<section class="blocco" aria-labelledby="h7s">
 <h2 id="h7s"><span class="num">7</span>Se ancora non va</h2>
 <ol class="scala">{scala_html}</ol>
</section>

<div class="iris"><img src="{risorse}/assistente/web/iris-pollice.webp" alt="Iris alza il pollice" width="76" height="76" loading="lazy"><p><b>Iris:</b> {inl(iris.get('fine', '').strip('«»'))}</p></div>

<div class="navlez"><a class="btn" href="#">{html.escape(prec)}</a><a class="btn prim" href="#">{html.escape(succ)}</a></div>

<p class="legenda">Le figure sono disegni (SVG) costruiti sul manuale e sulle scritte del prodotto alla versione indicata; dati inventati. Quando il prodotto confermerà immagini da dati inventati potranno diventare schermate dell ambiente di prova, sempre con i numeri sopra.</p>
</main>

{script}
</body>
</html>
'''
    controlli = {'passi': len(passi), 'riquadri': len(riq), 'domande': len(dom), 'scala': len(scala), 'in 30 secondi': len(trenta)}
    vuoti = [k for k, v in controlli.items() if not v]
    out = percorso_md[:-3] + '.html'
    open(out, 'w', encoding='utf-8').write(pagina)
    print(out, controlli, 'ROSSO: vuoti ' + ', '.join(vuoti) if vuoti else 'VERDE')
    return not vuoti


if __name__ == '__main__':
    esiti = [costruisci(p) for p in sys.argv[1:]]
    sys.exit(0 if all(esiti) else 1)
