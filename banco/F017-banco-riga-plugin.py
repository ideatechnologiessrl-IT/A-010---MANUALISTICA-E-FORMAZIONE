#!/usr/bin/env python3
# F017-banco-riga-plugin.py — RelDoxAcademy · Formazione F017 · 19/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
#
# Il banco della voce F17: regola di famiglia del 16/11/2026 (`bacheca/reldox-crd/messaggi/2026-11-16-a-tutti-copia-configurazione-
# nella-riga-del-plugin.md` del nodo): nell elenco dei plugin, sotto RelDoxAcademy, «Stato e assistenza» e «Copia configurazione»;
# il secondo copia negli appunti senza aprire il plugin, con lo stesso testo della pagina «Stato sistema», e lo dice.
# Clic nel browser vero (modello: banco/S707-13-banco-riga-plugin.py di RelDox CRD). Rosso sulla 0.1.05A, verde sulla 0.1.06A.
#
# uso:  python3 -I banco/F017-banco-riga-plugin.py <url del sito> <utente wp> <password wp>
import re, sys
from playwright.sync_api import sync_playwright

URL, WPU, WPP = sys.argv[1:4]
esiti = []


def esito(ok, cosa):
    esiti.append(bool(ok))
    print(('[VERDE] ' if ok else '[ROSSO] ') + cosa)


with sync_playwright() as pw:
    b = pw.chromium.launch()
    ctx = b.new_context(viewport={'width': 1440, 'height': 900})
    ctx.grant_permissions(['clipboard-read', 'clipboard-write'], origin=URL)
    pg = ctx.new_page()
    pg.goto(URL + '/wp-login.php')
    pg.fill('#user_login', WPU); pg.fill('#user_pass', WPP); pg.click('#wp-submit')
    pg.wait_for_load_state('networkidle')
    pg.goto(URL + '/wp-admin/plugins.php'); pg.wait_for_load_state('networkidle')
    riga = pg.locator('tr[data-plugin^="reldox-academy/"]').first
    esito(riga.count() == 1, 'la riga di RelDoxAcademy è nell elenco dei plugin')
    stato = riga.locator('.row-actions a', has_text='Stato e assistenza')
    copia = riga.locator('.row-actions a', has_text='Copia configurazione')
    esito(stato.count() == 1 and 'page=rdacademy' in (stato.get_attribute('href') or '') if stato.count() else False,
          '«Stato e assistenza» porta alla pagina Stato sistema')
    esito(copia.count() == 1, '«Copia configurazione» è nella riga')
    if copia.count() == 1:
        riga.hover()
        prima = pg.url
        copia.click(); pg.wait_for_timeout(500)
        esito(pg.url == prima, 'il clic non apre il plugin: si resta nell elenco')
        msg = pg.locator('#rdacademy-copiata')
        esito(msg.count() == 1 and 'Configurazione copiata: incollala in chat.' in msg.inner_text(), 'dice «Configurazione copiata: incollala in chat.»')
        testo = pg.evaluate('navigator.clipboard.readText()')
        esito(testo.startswith('=== RELDOXACADEMY — CONFIGURAZIONE INSTALLAZIONE ==='), 'negli appunti il riassunto della configurazione')
        pg.goto(URL + '/wp-admin/admin.php?page=rdacademy'); pg.wait_for_load_state('networkidle')
        pagina = pg.evaluate("document.getElementById('rdacademy-config-text').value")
        # Due righe cambiano da una lettura all altra: l ora in cui si genera e il dettaglio della prova della cache
        # (sul banco il sito non risponde a se stesso e il motivo porta i millisecondi dell attesa).
        togli = lambda s: re.sub(r'^(Generato il:|Cache pagine:)( *[^·\n]*).*$', r'\1\2', s, flags=re.M)
        togli = (lambda f: (lambda s: re.sub(r'^Generato il: .*$', 'Generato il:', f(s), flags=re.M)))(togli)
        diversi = [r for r in zip(togli(testo).splitlines(), togli(pagina).splitlines()) if r[0] != r[1]]
        esito(not diversi and len(testo.splitlines()) == len(pagina.splitlines()),
              'lo stesso testo della pagina Stato sistema (a parte l ora e il dettaglio della prova della cache)' + (' — diverse: ' + str(diversi[:2]) if diversi else ''))
        esito(not re.search(r'(?i)password|pass:|DB_PASS', testo), 'nessuna password nel testo copiato')
    b.close()

n_ok, n = sum(esiti), len(esiti)
print(('VERDE' if n_ok == n else 'ROSSO') + f' — {n_ok} su {n}')
sys.exit(0 if n_ok == n else 1)
