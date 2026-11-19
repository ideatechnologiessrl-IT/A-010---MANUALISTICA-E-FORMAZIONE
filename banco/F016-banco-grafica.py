#!/usr/bin/env python3
# F016-banco-grafica.py — RelDoxAcademy · Formazione F016 · 19/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
#
# Il banco del § 11 della SPEC-GRAFICA-RELDOX-v5 (una schermata si verifica nel browser vero a 1440 × 900,
# 1440 × 600, 820 e 390) più le misure C1-C6 del § 15. Rosso sulla versione di prima, verde sulla nuova.
#
# uso:  python3 -I banco/F016-banco-grafica.py <url del sito> <email> <password> <utente wp> <password wp> \
#           <cartella del plugin> <cartella del nodo> [<cartella delle schermate>]
# esce 0 se VERDE, 1 se ROSSO. Le righe [ROSSO]/[VERDE] sono l esito; nessun numero si scrive a mano.
import json, os, re, subprocess, sys
from playwright.sync_api import sync_playwright

URL, EMAIL, PWD, WPU, WPP, PLUGIN, NODO = sys.argv[1:8]
SCH = sys.argv[8] if len(sys.argv) > 8 else None
MISURE = [('computer', 1440, 900), ('computer-basso', 1440, 600), ('tablet', 820, 1100), ('telefono', 390, 844)]
PAGINE = [('pannello', '/academy/'), ('impostazioni', '/academy/impostazioni')]
ADMIN = [('stato-sistema', '/wp-admin/admin.php?page=rdacademy'), ('documentazione', '/wp-admin/admin.php?page=rdacademy-docs')]
EMOJI = re.compile('[\U0001F300-\U0001FAFF☀-➿⭐✅❌]')
esiti = []


def esito(ok, cosa):
    esiti.append(ok)
    print(('[VERDE] ' if ok else '[ROSSO] ') + cosa)


# ── C1-C6 dallo strumento del nodo (regola 31: il numero esce dallo strumento)
r = subprocess.run([sys.executable, '-I', os.path.join(NODO, 'strumenti', 'conformita-grafica.py'), PLUGIN],
                   capture_output=True, text=True)
riga = [l for l in r.stdout.splitlines() if 'C1' in l][-1]
print('       conformita-grafica: ' + re.sub(r'\s+', ' ', riga.strip()))
esito('C1 sì' in riga and 'C2 sì' in riga, 'C1 e C2: foglio comune copiato identico e gettone --nav definito')
esito(bool(re.search(r'C3 blu\s+0 ', riga)), 'C3 blu vecchio #1E3A5F a zero')
esito(bool(re.search(r'C4 <13px\s+0 ', riga)), 'C4 nessun carattere sotto i 13px')
esito(bool(re.search(r'C5 emoji\s+0 ', riga)), 'C5 nessuna emoji che arriva a schermo')
esito(bool(re.search(r'C6 finestre\s+0', riga)), 'C6 nessuna finestra del browser')

JS_MISURA = r"""() => {
  const css = [...document.querySelectorAll('link[rel=stylesheet]')].map(l => l.href.split('?')[0]);
  const rail = document.querySelector('nav.rail');
  const rs = rail ? getComputedStyle(rail) : null;
  const it = rail ? rail.querySelector('.it .ico, .it svg') : null;
  const att = rail ? rail.querySelector('.it[aria-current=page]') : null;
  const primo = rail ? rail.firstElementChild : null;
  let piccoli = 0, emoji = 0, esempio = '';
  const re = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B50}]/u;
  const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  while (w.nextNode()) {
    const t = w.currentNode, el = t.parentElement;
    if (!t.textContent.trim() || !el || !el.getClientRects().length) continue;
    const s = getComputedStyle(el);
    if (s.visibility === 'hidden' || s.display === 'none') continue;
    if (parseFloat(s.fontSize) < 13) { piccoli++; if (!esempio) esempio = t.textContent.trim().slice(0, 30) + ' (' + s.fontSize + ')'; }
    if (re.test(t.textContent)) emoji++;
  }
  const loghi = [...document.querySelectorAll('.rx-topbar img')].filter(i => i.getClientRects().length && i.naturalWidth > 0).length;
  return {
    css, primoCss: css[0] || '',
    rail: !!rail, railFondo: rs ? rs.backgroundColor : '', railPos: rs ? rs.position : '', railBottom: rail ? Math.round(innerHeight - rail.getBoundingClientRect().bottom) : -1,
    railScorre: rs ? rs.overflowY : '',
    icona: it ? Math.round(it.getBoundingClientRect().width) : 0,
    attiva: att ? getComputedStyle(att).color : '',
    cimaVuota: primo ? (primo.classList.contains('rdacademy-rail-cima') && !primo.querySelector('img,a') && primo.tagName !== 'A' && Math.round(primo.getBoundingClientRect().height) === 48) : false,
    orizzontale: document.documentElement.scrollWidth > innerWidth + 1,
    piccoli, esempio, emoji, loghi,
  };
}"""

with sync_playwright() as p:
    b = p.chromium.launch(executable_path='/usr/bin/google-chrome', args=['--no-sandbox'])
    ctx = b.new_context(locale='it-IT')
    pg = ctx.new_page()
    errori, finestre, mancanti = [], [], set()
    pg.on('pageerror', lambda e: errori.append(str(e)[:120]))
    pg.on('dialog', lambda d: (finestre.append(d.type), d.dismiss()))
    def risposta(r):
        u = r.url.split('?')[0]
        if '/reldox-academy/' in u and u.endswith(('.css', '.js')):
            tipo = (r.headers.get('content-type') or '')
            if r.status >= 300 or 'html' in tipo:   # un 404 o un rinvio a una pagina HTML: il file non c è
                mancanti.add(u)
    pg.on('response', risposta)

    # accesso all Academy
    pg.goto(URL + '/academy/')
    pg.fill('input[name=email]', EMAIL); pg.fill('input[name=password]', PWD)
    pg.click('button[type=submit], input[type=submit]'); pg.wait_for_load_state('networkidle')
    esito('/academy' in pg.url and pg.query_selector('input[name=password]') is None, 'accesso all Academy (' + pg.url.replace(URL, '') + ')')

    for nome, via in PAGINE:
        for mis, lw, lh in MISURE:
            pg.set_viewport_size({'width': lw, 'height': lh})
            pg.goto(URL + via); pg.wait_for_load_state('networkidle')
            m = pg.evaluate(JS_MISURA)
            tag = '%s %s %d' % (nome, mis, lw)
            if SCH:
                os.makedirs(SCH, exist_ok=True); pg.screenshot(path=os.path.join(SCH, '%s-%s.png' % (nome, mis)))
            esito(m['primoCss'].endswith('/reldox-grafica.css'), '%s: il foglio comune si carica per primo (%s)' % (tag, os.path.basename(m['primoCss'])))
            esito(m['rail'] and m['railFondo'] == 'rgb(15, 42, 68)', '%s: barra laterale .rail con fondo --nav #0F2A44 (%s)' % (tag, m['railFondo'] or 'assente'))
            if lw > 700:
                esito(m['icona'] == 34, '%s: icone della barra da 34px (%d)' % (tag, m['icona']))
                esito(m['cimaVuota'], '%s: S-1, in cima alla barra un blocco vuoto di 48px senza collegamento' % tag)
                esito(m['loghi'] >= 2, '%s: due loghi nella barra in alto (%d)' % (tag, m['loghi']))
            else:
                esito(m['railPos'] == 'fixed' and m['railBottom'] == 0, '%s: sul telefono la barra sta in basso (%s, %d)' % (tag, m['railPos'], m['railBottom']))
            if mis == 'computer-basso':
                esito(m['railScorre'] in ('auto', 'scroll'), '%s: la barra scorre se le voci non stanno (%s)' % (tag, m['railScorre']))
            if m['attiva']:
                esito(m['attiva'] == 'rgb(232, 178, 58)', '%s: voce aperta in ambra (%s)' % (tag, m['attiva']))
            esito(not m['orizzontale'], '%s: nessuno scorrimento orizzontale' % tag)
            esito(m['piccoli'] == 0, '%s: nessun testo a schermo sotto i 13px (%d; %s)' % (tag, m['piccoli'], m['esempio']))
            esito(m['emoji'] == 0, '%s: nessuna emoji a schermo (%d)' % (tag, m['emoji']))

    # le pagine del plugin in wp-admin
    pg.set_viewport_size({'width': 1440, 'height': 900})
    pg.goto(URL + '/wp-login.php'); pg.fill('#user_login', WPU); pg.fill('#user_pass', WPP); pg.click('#wp-submit'); pg.wait_for_load_state('networkidle')
    for nome, via in ADMIN:
        for mis, lw, lh in (MISURE[0], MISURE[3]):
            pg.set_viewport_size({'width': lw, 'height': lh})
            pg.goto(URL + via); pg.wait_for_load_state('networkidle')
            m = pg.evaluate("""() => { const w = document.querySelector('#wpbody-content'); let p = 0, e = 0;
              const re = /[\\u{1F300}-\\u{1FAFF}\\u{2600}-\\u{27BF}\\u{2B50}]/u;
              const t = document.createTreeWalker(w, NodeFilter.SHOW_TEXT);
              while (t.nextNode()) { const el = t.currentNode.parentElement; if (!t.currentNode.textContent.trim() || !el.getClientRects().length) continue;
                if (parseFloat(getComputedStyle(el).fontSize) < 13) p++; if (re.test(t.currentNode.textContent)) e++; }
              const css = [...document.querySelectorAll('link[rel=stylesheet]')].map(l => l.href.split('?')[0]);
              const g = css.findIndex(h => h.endsWith('/reldox-grafica.css'));
              const prod = css.findIndex(h => /reldox-academy\\//.test(h) && !h.endsWith('/reldox-grafica.css'));
              return {p, e, g, prod, icone: w.querySelectorAll('svg.ico').length, oriz: document.documentElement.scrollWidth > innerWidth + 1}; }""")
            tag = '%s %s %d' % (nome, mis, lw)
            if SCH:
                pg.screenshot(path=os.path.join(SCH, '%s-%s.png' % (nome, mis)), full_page=True)
            esito(m['g'] >= 0 and (m['prod'] < 0 or m['g'] < m['prod']), '%s: foglio comune caricato prima dei fogli del prodotto' % tag)
            esito(m['p'] == 0, '%s: nessun testo del plugin sotto i 13px (%d)' % (tag, m['p']))
            esito(m['e'] == 0, '%s: nessuna emoji, icone SVG a tratto (%d emoji, %d icone)' % (tag, m['e'], m['icone']))
            esito(not m['oriz'], '%s: nessuno scorrimento orizzontale' % tag)

    esito(not mancanti, 'ogni foglio e script chiamato esiste (%s)' % (', '.join(sorted(u.split('/reldox-academy/')[1] for u in mancanti)) or 'nessuno manca'))
    esito(not errori, 'nessun errore JavaScript (%s)' % ('; '.join(errori[:2]) or '0'))
    esito(not finestre, 'nessuna finestra del browser (%d)' % len(finestre))
    b.close()

n = len(esiti); v = sum(esiti)
print('ESITO: %s — %d verdi su %d' % ('VERDE' if v == n else 'ROSSO', v, n))
sys.exit(0 if v == n else 1)
