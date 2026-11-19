<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F016 — Manualistica e formazione · sedicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F015, 19/11/2026. Ramo di partenza: `main`. Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0005 — 0.1.03A — F016

PROGRESSIVO K0005   precedente K0004   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0005-0_1_03A-F016.zip · composto il 19/11/2026 15:42
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0005-0_1_03-F016.zip
         reldox-academy-0_1_03.zip  md5 392088fef41c90b535136638e512b4d9
         86 file · 18 cartelle · 0 cartelle vuote
         MD5-0_1_03.txt  86 righe
         APERTURA-F016.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: assente (non ancora scritto, banco G10 rosso)
BUSTA 2  KIT-2-AREA-PROGETTI-K0005-0_1_03-F016.zip  32 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0005-0_1_03-F016.zip  27 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  308 righe
         banco/F013-rifai-0.1.01A.sh  36 righe
         banco/F013-rifai-0.1.02A.sh  39 righe
         banco/F014-rifai-0.1.03A.sh  39 righe
         banco/KIT-PROGRESSIVO.txt  4 righe
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
BUSTA 4  KIT-4-AGGIUNTA-K0005-0_1_03-F016.zip  60 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 6 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (32):
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
  docs/APERTURA-F016.md
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
         registro BUG: REGISTRO BUG · 19/11/2026 F015 · aperti 2 · cornice 2/5 · primo libero BUG-3
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `reldox-academy/includes/class-rdacademy-admin.php` r.78 — quale foglio di stile chiama, ed è nel pacchetto?
2. `docs/BUG-FORMAZIONE.md` r.16 — quale bug si apre e con quante stelle?
3. `grafica/SPEC-GRAFICA-RELDOX-v4.md` del nodo r.44 — quale valore ha il gettone `--nav`?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F015 (19/11/2026), tutto in `main`; pacchetto **invariato**: 0.1.03A, la stessa del kit K0004.
- **Esito sul sito**: 0.1.03A installata su reldoxacademy.it, si entra (BASE connessa, 9 tabelle, 1 accesso). L owner (19/11/2026 14:33) non dà l ok: *«ancora lontano ad essere conforme con lo standard definito dal capo famiglia»*; *«tutta la grafica da usare ... e in caso chiedi al capo famiglia le specifiche»*. BUG-1 resta aperta.
- **BUG-2 ★★★★★** (aperta): cinque fogli chiamati dal codice e assenti dal pacchetto (`admin/css/admin.css`, `assets/docs/docs.css`, `assets/search/rdacademy-search.css`, `public/css/rdacademy-lotto-c.css`, `public/css/rdacademy-lotto-d.css`); grafica non conforme alla SPEC-GRAFICA v4 (blu `#1E3A5F`, circa 700 caratteri sotto i 13 px, emoji nella Stato sistema, manca il logo predefinito Idea Technologies).
- **Specifiche del capo famiglia** (19/11/2026, RelDox CRD S722, sua voce 176) nella voce **F29 ★★★★★**: copiare `rfo-grafica.css` e `rfo_icone()` da RelDoxChecks 1.3.02A (`main` 5b48c92, sola lettura) col nostro prefisso; dove differiscono vince il blocco CSS del § 10 della SPEC; icone dai mockup del `KIT-SPEC-GRAFICA-RELDOX-v3.zip`; banco del § 11 a 1440, 820, 390, rosso sulla 0.1.03A e verde sulla nuova. **Non copiare le pagine operative di nessuno.**
- **Da chiedere all owner, una alla volta, prima del codice**: S-1 (cima della barra laterale senza logo?) e S-2 (loghi verso l esterno); le risposte vanno al capo famiglia.
- **Altri mancanti dello standard** (F29, dopo la grafica): voce wp-admin «Licenza d uso», plugin `reldox-academy-diagnostica` con i banchi standard, `REGOLE-RELDOXACADEMY-v1.md`.
- **Registri**: BUG 2/5 · punti aperti 26, tutti con stelle (nuove F29 ★★★★★ e F30 ★★★, corso di configurazione sicura dalla guida di Sicurezza-Cyber) · violazioni 5 · fallimenti 3.
- **Nodo**: letti e segnati i messaggi del capo famiglia (grafica) e di Sicurezza-Cyber (guida, risposto «ricevuto»); STATO con la 0.1.03A installata; chiusura VERDE.
- **Manifesto K0004**: `banco/KIT-PROGRESSIVO.txt` aveva 4 righe contro le 3 dichiarate (riga scritta dopo il conteggio).

## §01 — CHE COSA FARE, IN ORDINE
0. Manifesto, prova di lettura, apertura del nodo, registri letti in una riga.
1. Domanda S-1 all owner; poi S-2; risposte al capo famiglia.
2. BUG-2: banco del § 11 rosso sulla 0.1.03A → grafica v4 da RelDoxChecks e § 10 → banco verde → 0.1.04A provata su un WordPress in italiano.
3. Poi il pannello dopo l ingresso, nella grafica nuova. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F016
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F016.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia` e fai l apertura; aggancia `RelDoxChecks` in sola lettura al commit 5b48c92.
> Fammi le due domande sulla grafica, una alla volta; poi porta RelDoxAcademy alla grafica standard di famiglia, provata su un WordPress in italiano.
> Consegna come kit a quattro buste, ogni voce nuova con le sue stelle. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
