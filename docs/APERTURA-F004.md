<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F004 — Manualistica e formazione · quarta sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F003, 17/11/2026 alle 14:00. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F003 (17/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE. Letti e segnati: due messaggi del Marketing (reldoxacademy.it in linea con la pagina di attesa; manuali e corsi a noi), la risposta di RelDoxFood sul capitolo 2, la risposta del capo famiglia e il suo messaggio a tutti.
- **Analisi tecnica di RelDoxAcademy, edizione 2**: `docs/ANALISI-TECNICA-RELDOXACADEMY.md`. Ha la forma fissa dei sedici paragrafi (`REGOLE-FAMIGLIA-RELDOX-v4` § 7.0) e nessun codice. Esiste solo in `.md`: il generatore dell `.html` si cerca in RelDoxFood.
- **SPEC 1.11** (`docs/SPEC-RELDOXACADEMY-v1.md`):
  - § 1 bis: i manuali passano gradualmente;
  - § 2.5: la base è RelDoxFood;
  - § 16: nella 0.1.01A si entra col login interno.
- **Roadmap**: voci F9 e F19 aggiornate; voci nuove F22 (pagina di attesa di reldoxacademy.it) e F23 (capitolo 2 del manuale Amministratore di RelDoxFood).
- **Decisioni del capo famiglia** (`bacheca/reldox-crd/messaggi/2026-11-17-a-formazione-academy-base-di-partenza.md`, RelDox CRD S713):
  - **base RelDoxFood 1.24.11L**, non il guscio «RelDox Base», che non si usa;
  - segno `rdacademy`, indirizzo `/academy/`, diagnostica `reldox-academy-diagnostica`;
  - 0.1.01A col **login interno e persone di prova**; l accesso firmato con le chiavi dei prodotti in una versione dopo, con il parere di Sicurezza-Cyber prima del codice;
  - freno dei tentativi: quello di RelDoxFood (solo ritardo, nessun blocco).
- **Decisione dell owner** (17/11/2026 13:54, chat RelDox CRD S713): *«Gradualmente non appena il plugin sarà funzionante»*. I manuali passano dai plugin dei prodotti all Academy un manuale alla volta, e ognuno esce dal plugin solo quando è già leggibile nell Academy.
- **Nel nodo**: un messaggio al capo famiglia (`…-a-reldox-crd-academy-base-di-partenza.md`). I fili del kit, della base e del capitolo 2 di RelDoxFood sono chiusi.

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (deve essere VERDE). Leggi i messaggi e rispondi, poi `--segna`.
1. **Aggancia `ideatechnologiessrl-IT/RelDoxFood`, ramo `main` (1.24.11L)** e **leggilo per intero** (§ 2 delle regole v4: non a campione, non sul commento). Prima i suoi `CLAUDE.md`, la sua analisi tecnica e il suo kit. Cerca il generatore `genera-analisi-tecnica.py` e lo strumento dei pacchetti.
2. **Analisi tecnica, edizione 3**, sul codice letto:
   - § 2 i database;
   - § 3 il ciclo di vita;
   - § 4 l albero intero dei file, con che cosa si tiene e che cosa si toglie (la ristorazione: menu, cassa, codice personale, prenotazioni, contratto di licenza da firmare);
   - § 5 le tabelle ereditate;
   - § 9 la cornice;
   - § 10 il rail Setup;
   - § 12 i banchi standard.
   Ciò che arriva da RelDoxFood si segna «da RelDoxFood 1.24.11L». Poi l `.html` col generatore, guardato a 1280, 820 e 390 px.
3. **All owner**: l edizione 3 da approvare. Al suo **«genera»**, il primo blocco G: la copia di RelDoxFood con nome e segno nuovi, banchi standard verdi, nessun resto di RelDoxFood. Banco rosso → verde (skill `banco-forma-comune-reldox`).
4. Lezioni: ferme alla 1.2 (D26). Riprendono con l Academy attiva.

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione), nella loro prossima sessione con l owner. Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 (identificativo dell azienda nella chiave) e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11).

## §11 — PROMPT PER LA SESSIONE F004
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F004.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F004. Leggi i messaggi nuovi e rispondi. Poi aggancia `ideatechnologiessrl-IT/RelDoxFood`, ramo `main` (1.24.11L), e leggilo per intero (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2): è la base di RelDoxAcademy decisa dal capo famiglia. Scrivi l edizione 3 dell analisi tecnica `docs/ANALISI-TECNICA-RELDOXACADEMY.md` sul codice letto, con l `.html` dal generatore, e portala all owner. Niente codice senza il suo «genera». Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F005.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
