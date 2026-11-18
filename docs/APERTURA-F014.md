<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F014 — Manualistica e formazione · quattordicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F013, 18/11/2026. Ramo di partenza: `main`. Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0002 — 0.1.02A — F014

PROGRESSIVO K0002   precedente K0001   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0002-0_1_02A-F014.zip · composto il 18/11/2026 15:24
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0002-0_1_02-F014.zip
         reldox-academy-0_1_02.zip  md5 2ad2483b63344f1ca59a0bf0696f33f1
         84 file · 18 cartelle · 0 cartelle vuote
         MD5-0_1_02.txt  84 righe
         APERTURA-F014.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: assente (non ancora scritto, banco G10 rosso)
BUSTA 2  KIT-2-AREA-PROGETTI-K0002-0_1_02-F014.zip  30 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0002-0_1_02-F014.zip  23 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  308 righe
         banco/F013-rifai-0.1.01A.sh  36 righe
         banco/F013-rifai-0.1.02A.sh  39 righe
         banco/KIT-PROGRESSIVO.txt  1 righe
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
BUSTA 4  KIT-4-AGGIUNTA-K0002-0_1_02-F014.zip  60 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 6 · modelli 3 · risorse 26
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
F013 (18/11/2026), tutto in `main`, chiusa per contesto oltre il 70% (V-3):
- **Registri**: BUG 1/5 (BUG-1, consegnata) · punti aperti 22 · violazioni 4 (V-1…V-4) · fallimenti 3 (ultimo F013-F1).
- **WordPress di prova** montato nella sessione (WordPress 6.8.3 da GitHub, MariaDB, wp-cli, Chrome per le schermate): ogni zip si prova da installazione nuova e sopra la precedente, con `wp-config` **in italiano**. Va rimontato a ogni sessione (non resta).
- **0.1.02A installata** su reldoxacademy.it alle 15:21: il wizard ora parte, Primo accesso crea il collegamento, si arriva alla pagina della password (schermata dell owner). **BUG-1** si chiude solo con l ok dell owner dopo l ingresso.
- **Che cosa manca**: le pagine sono **senza grafica** perché manca `public/css/app.css` di RelDoxFood (28 delle 31 classi del login sono lì). Non serve un altro prodotto come modello: il codice è già quello di RelDoxFood. Le pagine interne dicono «in arrivo»; il plugin di diagnostica non c è (banco G10 rosso).
- **Consegna**: sempre il **kit a quattro buste** (V-2) con `banco/F013-kit.py`; ultimo kit **K0002**.
- **Tre messaggi del nodo non ancora letti** (la chiusura sul nodo è ROSSA per questo): Legale, dati societari in `bacheca/legale/DATI-SOCIETARI-IDEA.md`; capo famiglia, registri chiusi all unione e strumento `voci-fatte-ancora-aperte.py` (chiede una risposta con l esito); RelDoxChecks, testi nuovi per la voce F25.

## §01 — CHE COSA FARE, IN ORDINE
0. Confronta il manifesto col disco, rispondi alla prova di lettura. Nodo: `python3 strumenti/apertura-nodo.py --prodotto formazione`; leggi per intero i tre messaggi, rispondi al capo famiglia, `--segna`. Registri letti in una riga.
1. Esito della 0.1.02A dall owner: se entra, BUG-1 chiusa con la sua citazione.
2. **`app.css`** letto per intero, a pezzi, togliendo gli stili della ristorazione; poi `rdfood-leggibilita.css`. Prima e dopo con le schermate, computer e telefono. Consegna 0.1.03A come kit K0003.
3. A ogni consegna la riga «Contesto: ~NN% · soglia kit 70%»; al 70% ci si ferma e si compone il kit.

## §03 — NOTE PER L OWNER, quando c è qualcosa da provare
Restano quelle di F013 § 03 (colonne delle 7 tabelle dello schema E; controllo delle mail rifiutate solo col pulsante).

## §11 — PROMPT PER LA SESSIONE F014
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F014.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia`, fai l apertura e leggi per intero i tre messaggi nuovi; aggancia `RelDoxFood` in sola lettura al commit 5e4a40c.
> Obiettivo: le pagine di accesso di RelDoxAcademy con la grafica di famiglia. Copia `app.css` leggendolo per intero, a pezzi, e prova su un WordPress in italiano.
> Consegna la 0.1.03A come kit a quattro buste. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
