<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F011 — Manualistica e formazione · undicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F010, 18/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F010 (18/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE; nessun messaggio nuovo; quattro richieste nostre ancora in carico (RelDox CRD, RelDoxFood, RelDoxPortal, Sicurezza-Cyber). **Registri letti**: BUG 0/5 · punti aperti 21 · violazioni 0 · fallimenti 0; nessuna scheda nuova in F010.
- **Blocco G**, `carico-d` completo con tutte le sue dipendenze, 13 file letti per intero e copiati (56 in tutto), **tutti TIENI, solo il segno, nessuna differenza** con l allegato A (l analisi resta all **ed. 10**):
  - `lotto-d/`: sorgenti, schema-datasources-v1, datasource, tabella-senza-casa, table-map, table-owner, schema-sorgente-esempio-v1, sorgente-provisioner, dimensioni, database;
  - `backup/class-rdacademy-backup.php`; `uploads-js/class-rdacademy-uploads-js.php` con `assets/` (css e js).
  - Cercato in ogni file: `persone`, `pasti`, `mensa`, `Servizio`, `Codice`, `Invii`, `Cartella_Dati`, `datiftp`, `fuzzy`, query senza `class_exists` su tabelle della ristorazione: **nessun ramo**. Classi, metodi e costanti chiamati verificati presenti nella copia (`Rdacademy_DB_Config`, `Rdacademy_Cifra::chiudi/apri/forma`, `Router::user_can_admin`, `Auth::log_activity`, `Schema_Uploads_Blob_V1::STORAGE_BASEDIR_REL/barriere/ensure_storage_dir`, `RDACADEMY_PRODUCT/VERSION/VERSION_LABEL`, tabella `impostazioni` nell activator).
- **Banchi** (`banco/esiti/F010-G-parziale.txt` e dopo ogni file): **G0 e G1 verdi** (55 PHP, 57 file); G2 e G10 rossi (attesi: manca la diagnostica e il carico completo).
- Chiusa intorno al 65-70% del contesto, prima di `carico-e` (RIFAI: riscrittura).

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`. **Registri letti** (SPEC-REGISTRI § 5): dillo all owner in una riga.
1. Aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit **5e4a40c** (1.24.11L) **solo in lettura**: `git init`, `git fetch --depth 1 origin 5e4a40c1423aa9bad10affc8d10e580100ab0bfc`, `git checkout FETCH_HEAD`. Continua il blocco G nell ordine del caricamento:
   - `carico-e` (**RIFAI**: si legge per intero la fonte e si riscrive dal carico ciò che l allegato A dice; i `require` di file TOLTI non vanno), poi `carico-e-143`, `-144`, `-190`;
   - `lotto-e/` (con `class-rdfood-testi-notifiche.php` *ridotto*: restano `invito.persona`, `accesso.persona`, `reinvio`, `reimpostazione`, `blocco`), le viste (con `preferenze.php` *ridotto*), gli stili e gli script, la diagnostica.
   - ⛔ **Da ricordare leggendo**:
     - (da F008) `public/js/app.js` ha il ramo «Entra come…» (`scegli`, `profili`): va tolto; `public/views/login.php` (TIENI) va guardato per lo stesso ramo: se c è, è una **differenza** → «Differenze» del registro e nuova edizione dell analisi (la prossima è la **11**) prima di andare avanti;
     - (da F010) il componente allegati legge il gettone da `window.RDACADEMY.nonce` e lo manda in `X-RDACADEMY-Nonce` (il lato PHP, `uploads-rest` e `carico-c`, lo legge già col segno): la **cornice delle viste** deve definire `window.RDACADEMY` con `nonce`; se nella fonte il nome è diverso, va allineato;
     - (da F009) in tre TIENI su sette c erano rami della mensa: cerca sempre le parole della §00 e le query **senza** `class_exists` su tabelle della ristorazione.
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi `php -l`; per «ridotto» le modifiche a mano dopo la copia; poi il banco (G0 e G1 verdi); nomi del cliente nei commenti guardati in lettura; `/food/` nei commenti diventa `/academy/`; il nome del prodotto di partenza nei commenti che scrivi tu non va (G1 lo trova, anche in maiuscolo).
   - ⛔ Le ricerche di parole sugli asset: togli prima il segno (`sed 's/rdfood//gI'`), altrimenti «food» trova ogni nome di classe CSS e l uscita riempie il contesto (F010).
   - Ogni costante d una classe tolta va cercata a mano: G1 vede le chiamate alle classi tolte, non le costanti di classi tenute che nella copia non esistono più.
   - Una differenza = edizione dell analisi (`strumenti/genera-analisi-tecnica.py … "ed. N"`) e riga nelle «Differenze», **commit a parte, prima** della copia del file.
2. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde** (G0, G1, G2, G10), più la trappola `--trappola`; G3-G7 sul palco.
3. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxFood, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §11 — PROMPT PER LA SESSIONE F011
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F011.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F011. Leggi i messaggi nuovi e rispondi; leggi i quattro registri (`CLAUDE.md`) e dillo in una riga. Poi aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit 5e4a40c1423aa9bad10affc8d10e580100ab0bfc (1.24.11L) **solo in lettura** e continua il blocco G dal punto scritto in `docs/REGISTRO-LETTURA-BLOCCO-G.md` (prossimo: `includes/carico-e.php`, RIFAI, poi `carico-e-143`, `-144`, `-190` e `lotto-e/`): leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copialo con `strumenti/copia-con-segno.py`, togli ciò che dice l allegato A dell analisi ed. 10 e i nomi del cliente nei commenti; una differenza con l allegato A vuole la sua edizione (prossima: 11) prima della copia; ricorda i tre punti «Da ricordare leggendo» della § 01 (ramo «Entra come…», `window.RDACADEMY.nonce` nella cornice, rami della mensa nei TIENI); manuale e codice sempre distinti (D29). Dopo ogni file il banco: G0 e G1 restano verdi. A copia completa i banchi G0, G1, G2, G10 verdi. Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F012.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
