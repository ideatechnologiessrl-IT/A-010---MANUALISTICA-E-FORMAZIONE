<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F014 — Manualistica e formazione · quattordicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F013, 18/11/2026. Ramo di partenza: `main`. Viaggia nella busta 1 del kit K0001.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0001 — 0.1.02A — F014

PROGRESSIVO K0001   precedente nessuno   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0001-0_1_02A-F014.zip · composto il 18/11/2026 15:09
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0001-0_1_02-F014.zip
         reldox-academy-0_1_02.zip  md5 2ad2483b63344f1ca59a0bf0696f33f1
         84 file · 18 cartelle · 0 cartelle vuote
         MD5-0_1_02.txt  84 righe
         APERTURA-F014.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: assente (non ancora scritto, banco G10 rosso)
BUSTA 2  KIT-2-AREA-PROGETTI-K0001-0_1_02-F014.zip  30 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0001-0_1_02-F014.zip  22 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  308 righe
         banco/F013-rifai-0.1.01A.sh  36 righe
         banco/F013-rifai-0.1.02A.sh  39 righe
         banco/esiti/F005-G-rosso-prima.txt  5 righe
         banco/esiti/F006-G-parziale.txt  7 righe
         banco/esiti/F007-G-parziale.txt  7 righe
         banco/esiti/F008-G-accesso-emergenza.txt  7 righe
         banco/esiti/F008-G-carichi.txt  7 righe
         banco/esiti/F008-G-docs.txt  7 righe
         banco/esiti/F008-G-licenza.txt  7 righe
         banco/esiti/F008-G-parziale.txt  7 righe
         banco/esiti/F008-G-router.txt  7 righe
         banco/esiti/F009-G-parziale.txt  7 righe
         banco/esiti/F010-G-parziale.txt  7 righe
         banco/esiti/F011-G-parziale.txt  7 righe
         banco/esiti/F012-G-parziale.txt  9 righe
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
BUSTA 4  KIT-4-AGGIUNTA-K0001-0_1_02-F014.zip  60 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 6 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (30):
  CLAUDE.md
  README.md
  docs/ANALISI-TECNICA-RELDOXACADEMY.md
  docs/APERTURA-F001.md
  docs/APERTURA-F002.md
  docs/APERTURA-F003.md
  docs/APERTURA-F004.md
  docs/APERTURA-F005.md
  docs/APERTURA-F006.md
  docs/APERTURA-F007.md
  docs/APERTURA-F008.md
  docs/APERTURA-F009.md
  docs/APERTURA-F010.md
  docs/APERTURA-F011.md
  docs/APERTURA-F012.md
  docs/APERTURA-F013.md
  docs/APERTURA-F014.md
  docs/BUG-FORMAZIONE.md
  docs/DECISIONI-OWNER.md
  docs/FALLIMENTI-FORMAZIONE.md
  docs/IRIS-v1.md
  docs/REGISTRO-LETTURA-BLOCCO-G.md
  docs/REGISTRO-VERSIONI-MATERIALI.md
  docs/ROADMAP.md
  docs/SPEC-RELDOXACADEMY-INDICE-v1.md
  docs/SPEC-RELDOXACADEMY-v1.md
  docs/STILE-DEI-MATERIALI-v1.md
  docs/STRUTTURA.md
  docs/VALUTAZIONE-INSTALLAZIONE-RELDOXACADEMY-v1.md
  docs/VIOLAZIONI-FORMAZIONE.md
BASELINE 0.1.02 · stadio Alfa · installata sì (0.1.01A su reldoxacademy.it il 18/11/2026, con BUG-1)
         registro BUG: REGISTRO BUG · 18/11/2026 F013 · aperti 1 · cornice 1/5 · primo libero BUG-2
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `reldox-academy/includes/bootstrap/class-rdacademy-bootstrap-wizard.php` r.55 — quali due righe del `wp-config` cerca il wizard?
2. `reldox-academy/reldox-academy.php` r.28 — quale numero dichiara?
3. `reldox-academy/includes/bootstrap/class-rdacademy-bootstrap-wizard.php` r.233 — che cosa fa il wizard quando questa funzione dice «vero»?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F013 (18/11/2026), tutto in `main`:
- **Apertura** sul nodo VERDE. Registri: BUG 1/5 (BUG-1, consegnata) · punti aperti 22 · violazioni 2 (V-1, V-2) · fallimenti 3 (ultimo F013-F1).
- **WordPress di prova** nella sessione (WordPress 6.8.3 da GitHub, MariaDB, wp-cli, Chrome per le schermate): si prova ogni zip da installazione nuova e sopra la precedente, con `wp-config` **in italiano**.
- **0.1.01A**: copiati e letti per intero il wizard d installazione e la sua vista, `layout.php` ridotto, `docs-page.php`, `imposta-password.php`, il misuratore della password, i loghi dal kit del nodo. `docs-content/` non copiato (è di RelDoxFood).
- **BUG-1** (owner 14:37): sul sito, WordPress in italiano, il plugin dopo il wizard non partiva («Non hai il permesso» su Primo accesso). Causa: il wizard cercava solo la riga inglese del `wp-config` e scriveva il blocco dopo `wp-settings.php`. I permalink non c entrano. **0.1.02A** corregge e sposta da solo un blocco già scritto in fondo; banco `F013-banco-wpconfig.php` ROSSO sulla 0.1.01A e VERDE sulla 0.1.02A; lo script del pacchetto non compone se il banco non è verde.
- **V-2** (owner 15:07): le consegne erano zip sciolti; da qui ogni consegna è il **kit a quattro buste** in un solo ZIP, composto e controllato da `banco/F013-kit.py` (sabotaggi compresi). Questo è il primo, K0001.
- **Limiti noti della 0.1.02A**: senza `app.css` le pagine dopo l accesso sono senza grafica; le pagine interne dicono «in arrivo»; il plugin di diagnostica non c è ancora (banco G10 rosso).

## §01 — CHE COSA FARE, IN ORDINE
0. Kit: confronta il manifesto qui sopra col disco e rispondi alla prova di lettura. Nodo: `git fetch --depth=200 origin main`, `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE), registri letti in una riga.
1. **Esito della 0.1.02A sul sito**: se l owner l ha installata, riga «Installato da collaudare» nel `STATO.md` del nodo; se va, BUG-1 si chiude con la sua citazione.
2. **`public/css/app.css`** (438 KB, 7451 righe): letto per intero, a pezzi, togliendo gli stili della ristorazione; poi `rdfood-leggibilita.css`. Schermate prima e dopo, computer e telefono.
3. Poi `recupera.php` (password dimenticata) e `dashboard.php`. Poi la voce F26 (nessun blocco che fermi il lavoro).
4. Ogni consegna: XX sale (0.1.03A), `banco/F0nn-rifai-<ver>.sh`, prova da zip su WordPress in italiano, kit con `banco/F013-kit.py` e progressivo K che sale di uno.

## §03 — NOTE PER L OWNER, quando c è qualcosa da provare
Restano quelle di F013 § 03 (colonne delle 7 tabelle dello schema E; controllo delle mail rifiutate solo col pulsante).

## §11 — PROMPT PER LA SESSIONE F014
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F014.md`; confronta il manifesto e rispondi alla prova di lettura. Aggancia il nodo `RelDox-Famiglia` e fai l apertura; aggancia `RelDoxFood` in sola lettura al commit 5e4a40c.
> Prima l esito della 0.1.02A sul sito. Poi la grafica: `app.css` letto per intero, a pezzi, e la pagina dopo l accesso leggibile su computer e telefono.
> Consegna 0.1.03A come kit a quattro buste (K0002), provata da zip su WordPress in italiano. Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
