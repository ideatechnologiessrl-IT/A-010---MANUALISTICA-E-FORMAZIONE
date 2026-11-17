<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F006 — Manualistica e formazione · sesta sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F005, 17/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F005 (17/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE; nessun messaggio nuovo per la Formazione.
- **Decisioni dell owner** (`docs/DECISIONI-OWNER.md`):
  - D27 (14:51): analisi tecnica ed. 3 approvata;
  - D28 (15:08): nulla si toglie dai prodotti; l Academy è una copia indipendente; la manualistica esce dai plugin solo quando nell Academy tutto funziona;
  - D29 (15:10): **manuale e codice sempre distinti**; il codice dei prodotti si legge e basta;
  - D30 (15:28): **«genera»** del blocco G.
- **Analisi tecnica ed. 4** (`.md` e `.html`): i 24 file di manualistica di RelDoxFood (API comprese) entrano come **MATERIALE**; il motore delle API resta in RelDoxFood.
- **Blocco G iniziato**:
  - banchi `banco/F005-G-banchi-copia.py` (G0, G1, G2, G10), **rossi prima** (`banco/esiti/F005-G-rosso-prima.txt`, 0 verdi, 4 rossi);
  - strumento `strumenti/copia-con-segno.py` (copia UN file dopo la lettura e scrive la riga nel registro);
  - registro `docs/REGISTRO-LETTURA-BLOCCO-G.md`;
  - **letti per intero e copiati 8 file**: `reldox-academy.php` (RIFAI: 0.1.01 Alfa, pacchetto `// 0.1.01A | F005 | copia-base`, indirizzo `/academy/`), `class-rdacademy-db-config.php`, `class-rdacademy-db.php`, `class-rdacademy-option.php`, `helpers.php`, `class-rdacademy-schema-flag-guard.php`, `class-rdacademy-provisioning-guard.php`, `class-rdacademy-roles.php`.

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`.
1. **Domanda 3 ancora aperta**: l indirizzo della mail di servizio dell Academy. Nella copia `RDACADEMY_SERVICE_EMAIL` vale `admin@reldox.it` finché l owner non risponde (D30).
2. Aggancia `ideatechnologiessrl-IT/RelDoxFood` `main` 1.24.11L (commit 5e4a40c) **solo in lettura** e continua il blocco G dal registro, nell ordine del caricamento del file principale:
   `class-rdfood-lingua.php` (TIENI, ridotto: via le chiavi della ristorazione) → `class-rdfood-activator.php` (TIENI, ridotto) → `security/` (security, rest-auth) → `class-rdfood-auth.php` (TIENI, ridotto) → `class-rdfood-user-prefs.php` → `security/` (password-policy, password-token, imap-diretta, casella, password-mail) → `class-rdfood-admin.php` → `class-rdfood-router.php` (TIENI, ridotto) → `carico-b`, `carico-c`, `carico-d` → il resto dell allegato A.
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi `php -l`; per «ridotto» e «rifai» le modifiche a mano dopo la copia.
   - Nei commenti della copia non si scrive il nome del prodotto di base (G1 lo conta come resto): la provenienza sta nel registro.
   - Una differenza dall analisi trovata leggendo → «Differenze» del registro ed **edizione 5** prima di andare avanti.
3. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde**, più la trappola `--trappola` (deve vedere il resto); G3-G7 sul palco.
4. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §11 — PROMPT PER LA SESSIONE F006
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F006.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F006. Leggi i messaggi nuovi e rispondi. Chiedi all owner l indirizzo della mail di servizio dell Academy (domanda 3, ancora aperta). Poi aggancia `ideatechnologiessrl-IT/RelDoxFood`, ramo `main` (1.24.11L), **solo in lettura**, e continua il blocco G dal punto scritto in `docs/REGISTRO-LETTURA-BLOCCO-G.md`: leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copialo con `strumenti/copia-con-segno.py`, togli ciò che dice l allegato A dell analisi ed. 4; manuale e codice sempre distinti (D29). A copia completa i banchi G0, G1, G2, G10 verdi. Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F007.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
