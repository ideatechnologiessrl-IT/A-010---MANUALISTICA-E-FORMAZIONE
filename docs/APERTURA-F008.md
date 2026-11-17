<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F008 — Manualistica e formazione · ottava sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F007, 17/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F007 (17/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE; nessun messaggio nuovo per la Formazione.
- **Analisi tecnica ed. 6** (`.md` e `.html`): `includes/security/class-rdfood-password-token.php` diventa **TIENI, ridotto** (via lo scopo `f` del foglio di accesso stampato, classe TOLTA); scritto nelle «Differenze» del registro.
- **Blocco G**, altri 12 file letti per intero e copiati (25 in tutto, `docs/REGISTRO-LETTURA-BLOCCO-G.md`):
  - `class-rdfood-user-prefs.php` → TIENI;
  - `security/`, ora **completa**: password-policy, imap-diretta, cifra, i due schemi degli allegati, uploads-entity, uploads-rest → TIENI; password-token → **ridotto** (ed. 6); casella → **ridotto** (solo commenti della ristorazione: il codice non ne aveva); password-mail → **ridotto** (restano invito, reinvio, reimpostazione, «Invio login», avviso di blocco, testata a due loghi, `amministratori()`; tolti registro dei pasti, contratto da firmare, sentinella delle API, FUZZYCode, codice personale, note della bacheca, email condivisa, scheda della mensa e ramo «ristoratore», tutti chiamati solo da file TOLTI);
  - `class-rdfood-admin.php` → TIENI.
- Nei commenti copiati sono stati tolti **nomi del cliente** (server e persona) che la fonte conserva: va guardato in ogni file che segue (`grep` sui nomi prima del commit).
- **Banchi sulla copia parziale** (`banco/esiti/F007-G-parziale.txt`): **G0 e G1 verdi**; G2 e G10 rossi (manca la diagnostica e il carico completo, atteso).

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`.
1. **Messaggio a RelDoxFood** (skill `messaggio-bacheca-reldox`, `bacheca/formazione/messaggi/…-a-reldoxfood-conteggi-gettoni.md`, informativo): nella 1.24.11L `Rdfood_Password_Token::conteggi()` conta i gettoni `f:` del foglio di accesso stampato fra quelli «in chiaro» (manca `AND reset_token NOT LIKE 'f:%'`); da noi lo scopo non c è più, da loro il banco dei gettoni può segnare un falso allarme.
2. Aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit **5e4a40c** (1.24.11L) **solo in lettura** e continua il blocco G, nell ordine del caricamento:
   `class-rdfood-router.php` (820 righe, *ridotto*: tolti i rami della ristorazione; ⛔ il parametro `$profilo` del login non serve più: si toglie insieme a «Entra come…») → `carico-b`, `carico-c`, `carico-d` → il resto dell allegato A (con `preferenze.php` *ridotto*: allora via anche le 29 `dati.*` dal gestore delle lingue; con `lotto-e/class-rdfood-testi-notifiche.php` *ridotto*: restano `invito.persona`, `accesso.persona`, `reinvio`, `reimpostazione`, `blocco`).
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi `php -l`; per «ridotto» e «rifai» le modifiche a mano dopo la copia; poi il banco (G0 e G1 devono restare verdi).
   - ⛔ L instradatore è grande: aprirlo **a inizio sessione**, da solo, commit subito dopo.
   - Una differenza dall analisi trovata leggendo → «Differenze» del registro ed **edizione 7** prima di andare avanti.
3. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde**, più la trappola `--trappola`; G3-G7 sul palco.
4. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §11 — PROMPT PER LA SESSIONE F008
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F008.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F008. Leggi i messaggi nuovi e rispondi, poi scrivi a RelDoxFood il messaggio informativo del § 01 punto 1. Poi aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit 5e4a40c (1.24.11L) **solo in lettura** e continua il blocco G dal punto scritto in `docs/REGISTRO-LETTURA-BLOCCO-G.md` (prossimo: `class-rdfood-router.php`, ridotto): leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copialo con `strumenti/copia-con-segno.py`, togli ciò che dice l allegato A dell analisi ed. 6 e i nomi del cliente nei commenti; manuale e codice sempre distinti (D29). Dopo ogni file il banco: G0 e G1 restano verdi. A copia completa i banchi G0, G1, G2, G10 verdi. Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F009.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
