#!/usr/bin/env python3
# F017-banco-stato-sistema.py — RelDoxAcademy · Formazione F017 · 19/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
#
# Il banco di BUG-3 (le nove differenze fra la pagina Stato sistema di RelDox CRD 3.75.07G e quella di
# RelDoxAcademy 0.1.04A, viste dall owner il 19/11/2026 16:20) e della voce F33 (via l app installabile,
# owner 19/11/2026 16:35). Legge il codice del plugin; con un indirizzo di WordPress apre anche la pagina
# vera e controlla ciò che arriva a schermo e negli appunti.
# Rosso sulla 0.1.04A, verde sulla 0.1.05A.
#
# uso:  python3 -I banco/F017-banco-stato-sistema.py <cartella del plugin> [<url del sito> <utente wp> <password wp> [<schermata.png>]]
# esce 0 se VERDE, 1 se ROSSO. Nessun numero si scrive a mano: le righe [VERDE]/[ROSSO] sono l esito.
import os, re, sys

P = sys.argv[1]
esiti = []


def esito(ok, cosa):
    esiti.append(bool(ok))
    print(('[VERDE] ' if ok else '[ROSSO] ') + cosa)


def leggi(rel):
    f = os.path.join(P, rel)
    return open(f, encoding='utf-8').read() if os.path.isfile(f) else ''


css = leggi('admin/css/admin.css')
pag = leggi('includes/class-rdacademy-stato-sistema.php')
cad = leggi('includes/carico-d.php')
pri = leggi('reldox-academy.php')


def regola(sel):
    """Il corpo della regola CSS che comincia esattamente con quel selettore."""
    m = re.search(re.escape(sel) + r'\{([^}]*)\}', css)
    return m.group(1) if m else ''


# 1. testata a fascia
t = regola('.rdacademy-stato .rdacademy-testata')
esito('linear-gradient' in t and 'var(--nav)' in t, '1 testata a fascia scura coi gettoni della SPEC (gradiente da --nav)')
esito('color:#fff' in regola('.rdacademy-stato .rdacademy-testata h1').replace(' ', ''), '1 nome del prodotto in bianco sulla fascia')
esito('.rdacademy-stato .reldox-stage' in css and 'var(--nav)' in regola('.rdacademy-stato .reldox-stage'),
      '1 distintivo dello stadio scuro, come il distintivo dell edizione del CRD')
# 2. riquadri numerici
c = regola('.rdacademy-stato .reldox-status-card')
esito('text-align:center' in c, '2 riquadri numerici centrati')
esito('border-top:3px solid var(--ok)' in regola('.rdacademy-stato .reldox-status-card.ok'), '2 bordo superiore verde quando va')
esito('border-top:3px solid var(--bad)' in regola('.rdacademy-stato .reldox-status-card.error'), '2 bordo superiore rosso quando non va')
esito('text-transform:uppercase' in regola('.rdacademy-stato .reldox-status-card .card-title'), '2 etichetta maiuscola')
esito("rdacademy_icona( $d['db_connected'] ? 'spunta'" in pag and "rdacademy_icona( $d['tables_ok'] ? 'spunta'" in pag,
      '2 spunta di stato su Database e Tabelle, come il CRD')
# 3. titoli di sezione con l icona disegnata
esito(re.search(r"<h3>\s*<\?php echo rdacademy_icona\( 'cilindro'[^>]*\?>\s*Database SQL utilizzati", pag),
      '3 «Database SQL utilizzati» con l icona del cilindro (SVG del registro, non emoji)')
esito(re.search(r"<h3>\s*<\?php echo rdacademy_icona\( 'lucchetto'[^>]*\?>\s*Utente di servizio e primo accesso", pag),
      '3 «Utente di servizio e primo accesso» con l icona del lucchetto')
# 4. sezione del primo accesso
esito('Credenziali primo accesso' not in pag, '4 niente più «Credenziali primo accesso»')
esito('Casella dell’utente di servizio' in pag, '4 sottosezione «Casella dell’utente di servizio»')
esito('casella di Idea Technologies (assistenza e manutenzione)' in pag, '4 la riga Email dice di chi è la casella')
# 5. tabella dei database
esito('NON IN USO' not in cad and 'IN USO ·' not in cad, '5 stati delle sorgenti senza maiuscolo («Non configurata», come il CRD)')
esito("'Non configurata'" in cad, '5 stato «Non configurata»')
esito("$pub['n'] . ' · '" not in cad, '5 nome della sorgente senza il numero davanti')
esito('provisionate' not in pag and 'Una sorgente non configurata non è in uso' in pag, '5 nota sotto la tabella in italiano, come il CRD')
# 6. ora locale
esito('get_date_from_gmt' in pag, '6 «Installato il» nell ora del sito')
esito("gmdate( 'Y-m-d H:i:s' ) . ' UTC'" not in pag and "current_time( 'Y-m-d H:i:s' )" in pag, '6 «Generato il» nell ora del sito, senza «UTC»')
# 7. pulsante «Copia configurazione»
esito("rdacademy_icona( 'copia' )" not in pag, '7 pulsante «Copia configurazione» senza icona, come il CRD')
# 8. testo copiato tutto in italiano
esito("'Ancora di dominio:' ) . $d['ancora']" not in pag, '8 «Ancora di dominio» nel testo copiato in italiano (attiva / da rinnovare)')
esito("$pc['esito'] . ' · '" not in pag, '8 «Cache pagine» nel testo copiato con lo stato a parole, non il colore')
# 9. ragione sociale
esito("define( 'RDACADEMY_VENDOR',        'Idea Technologies srl soc. Uninominale' );" in pri, '9 ragione sociale «Idea Technologies srl soc. Uninominale»')
esito('S.r.l.' not in pag, '9 nessun «S.r.l.» nella pagina')
# F33 — via l app installabile
esito(not os.path.exists(os.path.join(P, 'includes/lotto-e/class-rdacademy-app-install.php')), 'F33 classe dell app installabile tolta')
esito(not os.path.exists(os.path.join(P, 'public/js/rdacademy-icona.js')), 'F33 script che registra il service worker tolto')
resti = []
for rad, _, files in os.walk(P):
    for f in files:
        if f.endswith(('.php', '.js')):
            s = open(os.path.join(rad, f), encoding='utf-8', errors='replace').read()
            if re.search(r'App_Install|manifest\.webmanifest|serviceWorker|rdacademy-icona\.js|lotto-e/class-rdacademy-app-install', s):
                resti.append(os.path.relpath(os.path.join(rad, f), P))
esito(not resti, 'F33 nessun richiamo a manifesto o service worker' + (' — restano: ' + ', '.join(resti) if resti else ''))

# ── La pagina vera, se c è un WordPress
if len(sys.argv) > 4:
    URL, WPU, WPP = sys.argv[2:5]
    SCH = sys.argv[5] if len(sys.argv) > 5 else None
    from playwright.sync_api import sync_playwright
    with sync_playwright() as pw:
        b = pw.chromium.launch()
        pg = b.new_page(viewport={'width': 1440, 'height': 900})
        pg.goto(URL + '/wp-login.php')
        pg.fill('#user_login', WPU); pg.fill('#user_pass', WPP); pg.click('#wp-submit')
        pg.wait_for_load_state('networkidle')
        pg.goto(URL + '/wp-admin/admin.php?page=rdacademy'); pg.wait_for_load_state('networkidle')
        m = pg.evaluate("""() => {
          const t = document.querySelector('.rdacademy-testata'), c = document.querySelector('.reldox-status-card');
          const tx = document.getElementById('rdacademy-config-text');
          return { fondo: t ? getComputedStyle(t).backgroundImage : '', centro: c ? getComputedStyle(c).textAlign : '',
                   bordo: c ? getComputedStyle(c).borderTopWidth : '', testo: tx ? tx.value : '',
                   h3svg: [...document.querySelectorAll('.reldox-info-box h3')].filter(h => h.querySelector('svg')).length,
                   corpo: document.body.innerText };
        }""")
        esito('gradient' in m['fondo'], 'a schermo: testata a fascia (' + m['fondo'][:40] + ')')
        esito(m['centro'] == 'center' and m['bordo'] == '3px', 'a schermo: riquadri centrati col bordo di stato')
        esito(m['h3svg'] >= 2, 'a schermo: titoli di sezione con l icona disegnata (' + str(m['h3svg']) + ')')
        esito(m['testo'] and not re.search(r'\bactive\b|arancio|verde ·|rosso ·|UTC|S\.r\.l\.', m['testo']),
              'negli appunti: niente inglese, niente colori, niente UTC, ragione sociale giusta')
        esito('Utente di servizio e primo accesso' in m['corpo'] and 'NON IN USO' not in m['corpo'], 'a schermo: sezioni e stati come il CRD')
        r = pg.request.get(URL + '/academy/manifest.webmanifest')
        esito(r.status != 200 or 'manifest' not in (r.headers.get('content-type') or ''), 'F33 /academy/manifest.webmanifest non si serve più')
        r = pg.request.get(URL + '/academy/sw.js')
        esito(r.status != 200 or 'javascript' not in (r.headers.get('content-type') or ''), 'F33 /academy/sw.js non si serve più')
        if SCH:
            pg.screenshot(path=SCH, full_page=True)
            pg.set_viewport_size({'width': 390, 'height': 844}); pg.wait_for_timeout(300)
            pg.screenshot(path=SCH.replace('.png', '-telefono.png'), full_page=True)
        b.close()

n_ok, n = sum(esiti), len(esiti)
print(('VERDE' if n_ok == n else 'ROSSO') + f' — {n_ok} su {n}')
sys.exit(0 if n_ok == n else 1)
