<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F005 — Manualistica e formazione · quinta sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F004, 17/11/2026 alle 14:45. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F004 (17/11/2026) ha fatto, tutto in `main`:
- **Apertura e chiusura** sul nodo VERDI; nessun messaggio nuovo per la Formazione.
- **RelDoxFood agganciato** (`main` 1.24.11L, commit 5e4a40c) e letto in due passate: inventario di tutti i 201 file dei due plugin con uno strumento, lettura diretta del nucleo (file principale, instradatore, attivatore, opzioni sul BASE, database e sorgenti, cornice, rail Setup, Tabelle Funzionali, schemi C ed E, diagnostica). ⛔ La lettura **riga per riga** di ogni file si fa nel blocco G, file per file, prima di copiarlo.
- **Analisi tecnica, edizione 3**: `docs/ANALISI-TECNICA-RELDOXACADEMY.md` e `.html`, scritta sul codice. Contiene:
  - i nomi presi dal codice (§ 1); il cookie diventa `rdacademy_session`;
  - i database BASE e sorgenti 3-5, senza sorgenti accese (§ 2);
  - le API esterne di RelDoxFood si tolgono (§ 2);
  - il ciclo di vita (§ 3): `carico-e` diventa il carico dell Academy;
  - l albero intero, file per file, nell allegato A: 91 file da tenere, 13 da ridurre, 18 da rifare, 121 da togliere;
  - 15 tabelle ereditate e 7 proprie (§ 5);
  - cornice (§ 9), rail Setup e Impostazioni (§ 10);
  - undici banchi standard G0-G10 (§ 14).
- **Generatore** `strumenti/genera-analisi-tecnica.py`, nato da quello di RelDoxFood (`banco/S67-1-genera-analisi-tecnica.py`). Pagina guardata a 1280, 820 e 390 px: nessuno scorrimento orizzontale, nessun errore.
- **Portata all owner**: l edizione 3 aspetta la sua approvazione. Tre domande, una alla volta, in fondo al § 15: (1) approvi l edizione 3? (2) togliere le API esterne? (3) l indirizzo della mail di servizio dell Academy.

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (deve essere VERDE). Leggi i messaggi e rispondi, poi `--segna`.
1. **Riprendi le tre domande del § 15** dell analisi, una alla volta. Le decisioni dell owner vanno in `docs/DECISIONI-OWNER.md` con data, ora e chat; se cambiano l analisi, edizione 4 e `.html` rigenerato.
2. Al **«genera»** dell owner: aggancia `ideatechnologiessrl-IT/RelDoxFood` `main` 1.24.11L e fai il **blocco G**. Leggi riga per riga ogni file prima di copiarlo; copialo con nome e segno `rdacademy`; togli ciò che l allegato A dice di togliere.
   - Banchi G0, G1, G2 e G10 rossi prima e verdi dopo (skill `banco-forma-comune-reldox`); G3-G7 sul palco.
   - Ciò che la lettura trova di diverso dall analisi va in un edizione 4, prima di andare avanti.
3. Lezioni: ferme alla 1.2 (D26). Riprendono con l Academy attiva.

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 (identificativo dell azienda nella chiave) e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; oltre il secondo giorno le sollecita il capo famiglia).

## §11 — PROMPT PER LA SESSIONE F005
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F005.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F005. Leggi i messaggi nuovi e rispondi. Poi riprendi con l owner, una alla volta, le tre domande del § 15 dell analisi tecnica `docs/ANALISI-TECNICA-RELDOXACADEMY.md` (edizione 3). Al suo «genera», aggancia `ideatechnologiessrl-IT/RelDoxFood`, ramo `main` (1.24.11L), e fai il blocco G: leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copia con nome e segno `rdacademy`, togli ciò che dice l allegato A. Prova i banchi rossi prima e verdi dopo. Niente codice senza il suo «genera». Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F006.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
