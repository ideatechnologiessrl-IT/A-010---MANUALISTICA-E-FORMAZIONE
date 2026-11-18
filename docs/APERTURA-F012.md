<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F012 — Manualistica e formazione · dodicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F011, 18/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F011 (18/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE (372b855); nessun messaggio nuovo; quattro richieste nostre ancora in carico (RelDox CRD, RelDoxFood, RelDoxPortal, Sicurezza-Cyber). **Registri letti**: BUG 0/5 · punti aperti 21 · violazioni 0 · fallimenti 0; nessuna scheda nuova in F011.
- **Blocco G**, 6 file letti per intero e copiati (62 in tutto):
  - `carico-e` **RIFAI**: punto d innesto dell Academy; azioni vuote finché i blocchi M, C · L, R, I non portano le loro; schema E, testi delle notifiche, icona sul telefono; i carichi `-143`, `-144`, `-190` si chiedono **da qui** (nella fonte li chiedeva `carico-e-140`, TOLTO);
  - `carico-e-143` TIENI, ridotto (**ed. 11**): tolti l approdo dell amministratore su `pasti`/`mio-codice` e il rinvio di `?tab=invii`; la prova dello Stato del sistema legge `preferenze` invece di `pasti`;
  - `carico-e-144` TIENI, ridotto (**ed. 12**): tolto l aggancio a `Rdfood_Invii::EVENTO` (fatale); i rifiuti delle mail si controllano solo col pulsante. Nella stessa edizione `public/views/spedizioni.php` passa da TOGLI a **TIENI, ridotto** (il nome della persona dagli utenti, non da `persone`);
  - `carico-e-190` **RIFAI**: la voce `dashboard` del rail diventa «Catalogo» (icona del libro, chiave nuova `catalogo.voce` nel gestore delle lingue); la classe dei manuali dei ruoli, le loro pagine e i PDF non si copiano;
  - `lotto-e/class-rdfood-spedizioni.php` TIENI, ridotto (**ed. 13**): `persona_di()` leggeva `persone` senza condizione a ogni mail; tolti anche importazione dal registro attività, `stampa()`, esito «stampato», tipi della mensa, `dal_pianificatore()`; tabella uguale;
  - `lotto-e/class-rdfood-app-install.php` TIENI, solo il segno e i commenti.
- **Analisi** all **ed. 13**; «Differenze» del registro di lettura con le tre voci F011.
- **Banchi** (`banco/esiti/F011-G-parziale.txt`): **G0 e G1 verdi** (61 PHP, 63 file), trappola G1 verde; G2 e G10 rossi (attesi: manca la diagnostica e il carico completo).
- Chiusa intorno al 65% del contesto, prima di `lotto-e/class-rdfood-testi-notifiche.php` (510 righe, *ridotto*).

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`. **Registri letti** (SPEC-REGISTRI § 5): dillo all owner in una riga.
1. Aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit **5e4a40c** (1.24.11L) **solo in lettura**: `mkdir /home/claude/reldoxfood && cd` lì, `git init`, `git remote add origin https://github.com/ideatechnologiessrl-IT/reldoxfood` (nome in minuscolo: con le maiuscole il proxy chiede le credenziali), `git fetch --depth 1 origin 5e4a40c1423aa9bad10affc8d10e580100ab0bfc`, `git checkout FETCH_HEAD`. Continua `lotto-e/`:
   - `class-rdfood-testi-notifiche.php` (*ridotto*: restano `invito.persona`, `accesso.persona`, `reinvio`, `reimpostazione`, `blocco`; `carico-e` usa la sua costante `TABELLA`);
   - `class-rdfood-schema-e.php` (**RIFAI**: resta la grammatica — versione, flag, solo `CREATE IF NOT EXISTS` e `ALTER ADD` — le tabelle diventano le 7 proprie del § 5: `carichi`, `percorsi`, `lezioni`, `domande`, `aziende`, `persone_azienda`, `registro`). La classe resta `Rdacademy_Schema_E_V1`: `carico-e` la passa già alla guardia di provisioning. Se le colonne del § 5 non bastano per scriverle, è una domanda all owner, non una scelta tua;
   - poi le viste (con `preferenze.php` e **`spedizioni.php`** *ridotti*), gli stili (`rdfood-144.css`, `rdfood-leggibilita.css` li chiede già `carico-e`), gli script (`rdfood-icona.js`), la diagnostica.
   - ⛔ **Da ricordare leggendo**:
     - (da F008) `public/js/app.js` ha il ramo «Entra come…» (`scegli`, `profili`): va tolto; `public/views/login.php` (TIENI) va guardato per lo stesso ramo e per il collegamento «Come si usa» verso `/manuali` (riga 99 della fonte: nell Academy quelle pagine non ci sono più): se c è, è una **differenza** → «Differenze» e nuova edizione (la prossima è la **14**) prima di andare avanti;
     - (da F010) la **cornice delle viste** deve definire `window.RDACADEMY` con `nonce` (il componente allegati lo manda in `X-RDACADEMY-Nonce`);
     - (da F011) `public/views/layout.php` chiama `rdacademy_icona_tratto( 'guida-cassa' )` e `rdacademy_e_ristoratore()` nel ramo del ristoratore (dietro `function_exists`/`class_exists`): ramo della mensa da togliere; `rdacademy_icona_tratto( 'manuali' )` resta in `carico-e`;
     - (da F009) cerca sempre `persone`, `pasti`, `mensa`, `Servizio`, `Codice`, `Invii`, `Cartella_Dati`, `datiftp`, `fuzzy` e le query **senza** `class_exists` su tabelle della ristorazione, anche nei TIENI (in F011 tre TIENI su quattro li avevano).
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi le modifiche a mano con uno script fuori dal repository (`/home/claude/script/`), `php -l`, il banco (G0 e G1 verdi). Nei commenti `/food/` diventa `/academy/`; via sigle, versioni e citazioni dell owner della fonte.
   - ⛔ Negli script di sostituzione conta l indentazione a **tab** della fonte: guarda le righe con `cat -A` prima di scrivere il blocco da cercare (F011: due giri persi).
   - Una differenza = edizione dell analisi (`strumenti/genera-analisi-tecnica.py … "ed. N"`) e riga nelle «Differenze», **commit a parte, prima** della copia del file.
2. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde** (G0, G1, G2, G10), più `--trappola`; G3-G7 sul palco.
3. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxFood, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §03 — DA DIRE ALL OWNER QUANDO SERVE
- Nell Academy il controllo dei rifiuti delle mail non gira da solo (nella fonte lo faceva il pianificatore degli invii del registro dei pasti, che non c è): resta il pulsante «Controlla i rifiuti». Se lo vuole automatico serve un pianificatore proprio: voce nuova, solo con il suo sì.

## §11 — PROMPT PER LA SESSIONE F012
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F012.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F012. Leggi i messaggi nuovi e rispondi; leggi i quattro registri (`CLAUDE.md`) e dillo in una riga. Poi aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit 5e4a40c1423aa9bad10affc8d10e580100ab0bfc (1.24.11L) **solo in lettura** (indirizzo in minuscolo, § 01) e continua il blocco G dal punto scritto in `docs/REGISTRO-LETTURA-BLOCCO-G.md` (prossimo: `includes/lotto-e/class-rdfood-testi-notifiche.php`, ridotto, poi `class-rdfood-schema-e.php`, RIFAI con le tabelle del § 5, poi viste, stili, script e diagnostica): leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copialo con `strumenti/copia-con-segno.py`, togli ciò che dice l allegato A dell analisi ed. 13 e i nomi del cliente nei commenti; una differenza con l allegato A vuole la sua edizione (prossima: 14) prima della copia; ricorda i punti «Da ricordare leggendo» della § 01 (ramo «Entra come…» e «Come si usa» in `login.php`, `window.RDACADEMY.nonce` nella cornice, ramo del ristoratore in `layout.php`, rami della mensa nei TIENI); manuale e codice sempre distinti (D29). Dopo ogni file il banco: G0 e G1 restano verdi. A copia completa i banchi G0, G1, G2, G10 verdi. Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F013.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
