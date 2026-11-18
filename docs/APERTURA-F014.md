<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F014 — Manualistica e formazione · quattordicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F013, 18/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F013 (18/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE (8d61501), nessun messaggio nuovo. **Registri letti**: BUG 0/5 · punti aperti 21 · violazioni 1 (V-1) · fallimenti 2 (F011-F1, F012-F1).
- **Prova vera**: un WordPress 6.8.3 con MariaDB montato nella sessione (WordPress da GitHub, wp-cli). Così si è visto che cosa serve davvero all attivazione, invece di dedurlo dai `require`.
- **Copiati**, ognuno letto per intero, banco G0 e G1 verde dopo ognuno: wizard d installazione e la sua vista (la vista non era nell elenco del § 10 di F013, la chiede il wizard), `layout.php` *ridotto* (via menu del ristoratore, casetta della cassa, codice personale), `docs-page.php`, `imposta-password.php`, `rdacademy-pwd-meter.js`, segnaposto del logo azienda; loghi dal kit del nodo (`grafica/loghi/reldoxacademy/`).
- **Non copiato** `docs-content/`: è la documentazione di RelDoxFood (API, codice personale, novità di Food); la pagina Documentazione dice «vuota» senza errori. Il contenuto vero sarà nostro.
- **Pacchetto** `reldox-academy-0_1_01.zip` (0.1.01A, 84 file) da `banco/F013-rifai-0.1.01A.sh` (nome coi trattini bassi, MD5, elenco, data scritta dallo script; REGOLE-FAMIGLIA v4 § 9.2). Provato da zip su sito pulito: si installa e si attiva, il wizard scrive `wp-config`, nascono 22 tabelle, Primo accesso → password → dentro `/academy/`, zero avvisi PHP. Consegnato all owner.
- **Limite noto**: senza `app.css` le pagine dopo l accesso sono senza grafica (icone enormi); tutte le pagine interne dicono «in arrivo».

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE), registri letti in una riga.
1. **Esito della prova dell owner** sul sito: se l ha installata, riga «Installato da collaudare» in testa a `bacheca/formazione/STATO.md` del nodo (regola del nodo «A ogni installazione»). Un errore sul sito = scheda BUG, prima di tutto.
2. **`public/css/app.css`** (438 KB, 7451 righe): letto per intero, a pezzi, togliendo gli stili della ristorazione; poi `rdfood-leggibilita.css` (la chiede `carico-e`). Prova con le schermate (Playwright con `/usr/bin/google-chrome`) prima e dopo.
3. Poi le viste che il sito apre già: `recupera.php` (password dimenticata), `dashboard.php` → catalogo minimo «in arrivo».
4. Ogni consegna nuova: XX sale (0.1.02A), `banco/F0nn-rifai-<ver>.sh`, prova da zip su sito pulito.

## §03 — NOTE PER L OWNER, quando c è qualcosa da provare
Restano quelle di F013 § 03 (colonne delle 7 tabelle dello schema E; controllo delle mail rifiutate solo col pulsante).

## §11 — PROMPT PER LA SESSIONE F014
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F014.md`. Aggancia il nodo `RelDox-Famiglia` e fai l apertura; aggancia `RelDoxFood` in sola lettura al commit 5e4a40c.
> Prima l esito della prova dello zip 0.1.01A sul sito. Poi la grafica: `app.css` letto per intero, a pezzi, e la pagina dopo l accesso leggibile su computer e telefono.
> Consegna 0.1.02A con lo script di ricostruzione, provata da zip su sito pulito. Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
