<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F003 — Manualistica e formazione · terza sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F002, 17/11/2026 alle 12:15. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F002 (17/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE; letto il messaggio del Marketing sul logo di idea.comunicando (informativo).
- **RelDoxFood, percorso 1**: lezioni **1.1** «La mappa dell applicazione» e **1.2** «La prima configurazione» in `corsi/reldoxfood/amministratore/it/`. Ognuna ha il Markdown, l anteprima `.html` e le figure SVG in `img/`. Sono provate a 1366, 820 e 390 px e scritte nel registro delle versioni.
- **Strumenti**: `strumenti/anteprima-lezione.py` crea l anteprima HTML dal Markdown della lezione. `strumenti/figure-1-2.py` crea le figure della 1.2.
- **Decisioni dell owner** (`docs/DECISIONI-OWNER.md`):
  - **D25** (12:01): le lezioni non aspettano l approvazione una per una; l owner le controlla con l Academy attiva.
  - **D26** (12:10): *«ferma la lezione e crea il plugin secondo lo starda di quelli esistenti, chiedi al capo famiglia in caso»*.
- **Nel nodo** (`bacheca/formazione/messaggi/`):
  - a RelDoxFood, `…-a-reldoxfood-manuale-amministratore-impostazioni.md`: alla 1.24.11 le Impostazioni hanno il pannello di dieci pulsanti e il comando «Modifica», non le linguette del capitolo 2 del manuale; c è anche il «Diritto alla mensa». La lezione 1.2 segue lo schermo.
  - al capo famiglia, `…-a-reldox-crd-academy-kit-di-partenza.md`: kit di partenza e guscio, segno `rdacademy`, accesso della 0.1.01A.

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (deve essere VERDE). Leggi i messaggi e rispondi, poi `--segna`. Guarda prima la risposta del capo famiglia sul kit di partenza.
1. **RelDoxAcademy 0.1.01A, secondo lo standard dei prodotti** (D26). Le regole sono in `spec/REGOLE-FAMIGLIA-RELDOX-v4.md` del nodo:
   - § 2: ciò che RelDox fa già si **copia**, dopo averlo letto per intero;
   - § 7.0: prima di tutto l **analisi tecnica, edizione 1** (`docs/ANALISI-TECNICA-RELDOXACADEMY.md`), dal modello del kit;
   - § 14: il guscio «RelDox Base» con `nuovo-prodotto.py`, il segno proposto `rdacademy` e il codice in `reldox-academy/`;
   - § 9: banchi rosso → verde, kit, «genera» dell owner prima del codice.
   Fonte di ciò che serve: la SPEC 1.10 (§ 16, prima versione) e il mockup 0.1.01A. Le due lezioni fatte sono il contenuto di prova.
2. Se il capo famiglia non ha ancora risposto: nessun codice. Prepara l analisi tecnica edizione 1 sulla SPEC e sul mockup, segnando PROPOSTA ciò che dipende dal guscio, e chiedi all owner il kit di partenza.
3. Lezioni: ferme alla 1.2 (D26). Riprendono con l Academy attiva.

## §11 — PROMPT PER LA SESSIONE F003
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F003.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F003. Leggi i messaggi nuovi, prima di tutto la risposta del capo famiglia sul kit di partenza. Poi parti dal plugin **RelDoxAcademy 0.1.01A** secondo lo standard dei prodotti (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2, § 7.0, § 14): prima l analisi tecnica edizione 1, niente codice senza «genera». Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F004.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
