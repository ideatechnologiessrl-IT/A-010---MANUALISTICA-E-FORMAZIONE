<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F007 — Manualistica e formazione · settima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F006, 17/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F006 (17/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE; nessun messaggio nuovo per la Formazione.
- **D31** (15:58): mail di servizio dell Academy **`academy@reldox.it`** → `RDACADEMY_SERVICE_EMAIL`; domanda 3 del § 15 chiusa. ⛔ La casella va creata sul server dell Academy prima dello smoke.
- **Analisi tecnica ed. 5** (`.md` e `.html`): `public/views/preferenze.php` diventa **TIENI, ridotto** (ha il ramo «I miei dati» del ristoratore); scritto nelle «Differenze» del registro.
- **Blocco G**, altri 5 file letti per intero e copiati (13 in tutto, `docs/REGISTRO-LETTURA-BLOCCO-G.md`):
  - `class-rdfood-lingua.php` → **ridotto**: 685 chiavi della ristorazione tolte; restano le notifiche dell account (invito, reinvio, reimpostazione, accesso, blocco) e i testi di accesso, recupero, password, cornice, icona, mail di sicurezza, porta; ⛔ restano per ora le 29 chiavi `dati.*` di `preferenze.php`: escono con la sua riduzione;
  - `class-rdfood-activator.php` → **ridotto** (via lo spegnimento degli invii e l aggancio delle novità per l integratore);
  - `security/class-rdfood-security.php`, `security/class-rdfood-rest-auth.php` → TIENI;
  - `class-rdfood-auth.php` → **ridotto** (via l email condivisa: due utenti con la stessa email = «ambiguo»).
- **Banchi sulla copia parziale** (`banco/esiti/F006-G-parziale.txt`): **G0 e G1 verdi**; G2 e G10 rossi (manca la diagnostica e il carico completo, atteso).

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`.
1. Aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit **5e4a40c** (1.24.11L) **solo in lettura** (se `main` è andato avanti: `git fetch --depth=300 origin main` e `git checkout 5e4a40c`) e continua il blocco G dal registro, nell ordine del caricamento:
   `class-rdfood-user-prefs.php` → `security/` (password-policy, password-token, imap-diretta, casella *ridotto*, password-mail *ridotto*, cifra, uploads…) → `class-rdfood-admin.php` → `class-rdfood-router.php` (*ridotto*; ⛔ il parametro `$profilo` del login non serve più: si toglie qui insieme a «Entra come…») → `carico-b`, `carico-c`, `carico-d` → il resto dell allegato A (con `preferenze.php` *ridotto*: allora via anche le `dati.*` dal gestore delle lingue).
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi `php -l`; per «ridotto» e «rifai» le modifiche a mano dopo la copia; poi il banco (G0 e G1 devono restare verdi).
   - ⛔ Lezione di F006: i file grandi (il gestore delle lingue, 1572 righe) consumano molto contesto; uno per volta, commit dopo ognuno.
   - Una differenza dall analisi trovata leggendo → «Differenze» del registro ed **edizione 6** prima di andare avanti.
2. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde**, più la trappola `--trappola`; G3-G7 sul palco.
3. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §11 — PROMPT PER LA SESSIONE F007
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F007.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F007. Leggi i messaggi nuovi e rispondi. Poi aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit 5e4a40c (1.24.11L) **solo in lettura** e continua il blocco G dal punto scritto in `docs/REGISTRO-LETTURA-BLOCCO-G.md` (prossimo: `class-rdfood-user-prefs.php`): leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copialo con `strumenti/copia-con-segno.py`, togli ciò che dice l allegato A dell analisi ed. 5; manuale e codice sempre distinti (D29). Dopo ogni file il banco: G0 e G1 restano verdi. A copia completa i banchi G0, G1, G2, G10 verdi. Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F008.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
