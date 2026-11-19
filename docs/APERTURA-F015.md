<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F015 — Manualistica e formazione · quindicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F014, 18/11/2026. Ramo di partenza: `main`. Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0003 — 0.1.03A — F015

PROGRESSIVO K0003   precedente K0002   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0003-0_1_03A-F015.zip · composto il 18/11/2026 15:55
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0003-0_1_03-F015.zip
         reldox-academy-0_1_03.zip  md5 70c5877e8815651a66c5c54fe4b50a6c
         86 file · 18 cartelle · 0 cartelle vuote
         MD5-0_1_03.txt  86 righe
         APERTURA-F015.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: assente (non ancora scritto, banco G10 rosso)
BUSTA 2  KIT-2-AREA-PROGETTI-K0003-0_1_03-F015.zip  31 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0003-0_1_03-F015.zip  27 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  308 righe
         banco/F013-rifai-0.1.01A.sh  36 righe
         banco/F013-rifai-0.1.02A.sh  39 righe
         banco/F014-rifai-0.1.03A.sh  39 righe
         banco/KIT-PROGRESSIVO.txt  2 righe
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
         banco/esiti/F014-G-parziale.txt  7 righe
         banco/esiti/F014-prima-dopo-computer.png  0 righe
         banco/esiti/F014-prima-dopo-telefono.png  0 righe
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
BUSTA 4  KIT-4-AGGIUNTA-K0003-0_1_03-F015.zip  60 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 6 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (31):
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
  docs/APERTURA-F015.md
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
BASELINE 0.1.03 · stadio Alfa · installata sì (0.1.01A su reldoxacademy.it il 18/11/2026, con BUG-1)
         registro BUG: REGISTRO BUG · 18/11/2026 F013 · aperti 1 · cornice 1/5 · primo libero BUG-2
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `reldox-academy/public/css/app.css` r.581 — quale classe si apre?
2. `reldox-academy/reldox-academy.php` r.28 — quale numero dichiara?
3. `reldox-academy/includes/bootstrap/class-rdacademy-bootstrap-wizard.php` r.55 — quali due righe del `wp-config` cerca il wizard?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F014 (18/11/2026), tutto in `main`:
- **0.1.03A consegnata come kit K0003**: `app.css` (7451 righe) e `rdacademy-leggibilita.css` (451) copiati **interi** da RelDoxFood al commit 5e4a40c. Lo scorrimento a pezzi dice che `app.css` è quasi tutto lo stile di base di famiglia (tabelle, calendario, schede), non stile da ristorante: niente da togliere per ora. Le classi delle pagine di accesso hanno tutte uno stile, tranne tre classi usate solo dallo script (anche in RelDoxFood).
- **Provata** sul WordPress di prova con `wp-config` in italiano: installazione nuova (wizard → BASE scritta prima di `wp-settings.php` → pronta) e aggiornamento sopra la 0.1.02A (resta attiva, la BASE resta). Prima e dopo in `banco/esiti/F014-prima-dopo-*.png`. Limite: il pacchetto della lingua italiana di WordPress non si scarica da qui (wordpress.org chiuso); la pagina dell Academy è in italiano col browser in italiano.
- **Registri**: BUG 1/5 (BUG-1 ★★★★★, aspetta l ok dell owner dopo l ingresso) · punti aperti 24, tutti con stelle (colonna «Priorità»; nuove F27 e F28) · violazioni 5 (V-5: stelle mancanti e due risposte dovute, rimediata il 19/11) · fallimenti 3. ⛔ Ogni voce nuova nasce con le sue stelle.
- **Nodo**: tre messaggi letti e segnati; risposta al capo famiglia (registri chiusi all unione: VERDE); logo in testa ai 27 messaggi della nostra cartella; testi della 1.3.03A di RelDoxChecks nella voce F25.
- **Da provare dall owner**: installare la 0.1.03A su reldoxacademy.it sopra la 0.1.02A; la pagina d accesso deve avere la grafica. Se entra, BUG-1 si chiude con la sua citazione.

## §01 — CHE COSA FARE, IN ORDINE
0. Manifesto, prova di lettura, apertura del nodo, registri letti in una riga.
1. Esito della 0.1.03A dall owner; se entra, BUG-1 chiusa; riga «Installato da collaudare» nel nodo.
2. Le pagine interne («in arrivo») una alla volta, cominciando dal pannello dopo l ingresso.
3. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F015
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F015.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia` e fai l apertura; aggancia `RelDoxFood` in sola lettura al commit 5e4a40c.
> Chiedimi l esito della 0.1.03A sul sito; poi il pannello dopo l ingresso, provato su un WordPress in italiano.
> Consegna come kit a quattro buste. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
