<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F010 — Manualistica e formazione · decima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F009, 18/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F009 (18/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE; nessun messaggio nuovo. Le letture automatiche del mattino (Cruscotto) avevano lasciato due voci «alla prossima sessione con l owner»: chiuse qui.
- **I quattro registri** (SPEC-REGISTRI v2 § 6, prima del lavoro): `docs/BUG-FORMAZIONE.md`, `docs/VIOLAZIONI-FORMAZIONE.md`, `docs/FALLIMENTI-FORMAZIONE.md` (nessuna scheda: in F001-F008 non ne risultano) e `docs/ROADMAP.md` come PUNTI APERTI (riga di testa, 21 voci aperte, primo libero F26); tabella nel `CLAUDE.md`; riga «Registri» nello `STATO.md` del nodo; seguito chiuso al capo famiglia (`bacheca/formazione/messaggi/2026-11-18-a-reldox-crd-registri.md`).
- **Voce F25**: RelDoxChecks, voce 219 (precompilazione nei manuali della 1.3.03A), candidata; seguito a RelDoxChecks.
- **Blocco G**, altri 8 file letti per intero e copiati (43 in tutto):
  - `class-rdfood-stato-sistema.php` → **ridotto**: via invii del registro, cartella dati, integrazione API, novità, lo stato della licenza da firmare e il collegamento a `License_Admin` (fatale); resta l ancora di dominio; tenuta la guardia della cache;
  - `lotto-c/` completo: `schema-c`, `me`, `tf-admin-rest`, `area-tecnica` → TIENI; tre **differenze** con l allegato A, ognuna con la sua edizione dell analisi e la riga nelle «Differenze» del registro:
    - **ed. 8** `utenti` → ridotto: `login_libero()` interrogava **senza condizione** la tabella `persone` della mensa (ogni salvataggio con username = «Errore DB»); via anche email condivisa e nome utente della scheda;
    - **ed. 9** `impostazioni` → ridotto: via Santo Patrono e festività (`Servizio`), recapiti (`Codice`), porta propria `datiftp_*`;
    - **ed. 10** `tf-registry` → ridotto: via la fonte `tf_invii_registro` (senza la tabella `verifica()` sarebbe stata ROSSA); resta `tf_testi_notifiche`.
- **Banchi** sulla copia parziale (`banco/esiti/F009-G-parziale.txt` e a ogni file): **G0 e G1 verdi**; G2 e G10 rossi (attesi: manca la diagnostica e il carico completo).
- Chiusa intorno al 65% del contesto, prima di `lotto-d/` (~2.700 righe che non ci stavano).

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`. **Registri letti** (SPEC-REGISTRI § 5): dillo all owner in una riga.
1. Aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit **5e4a40c** (1.24.11L) **solo in lettura**. Lo SHA corto non si scarica: usa quello intero `5e4a40c1423aa9bad10affc8d10e580100ab0bfc` (`git init`, `git fetch --depth 1 origin <sha>`, `git checkout FETCH_HEAD`). Continua il blocco G nell ordine del caricamento:
   - le dipendenze di `carico-d`, nell ordine dei `require`: `lotto-d/` (sorgenti 144, schema-datasources-v1 66, datasource 404, tabella-senza-casa 46, table-map 53, table-owner 266, schema-sorgente-esempio-v1 41, sorgente-provisioner 208, dimensioni 64, database 523), `backup/class-rdfood-backup.php` (747), `uploads-js/class-rdfood-uploads-js.php` (99) con i suoi `assets/` (css 904, js 905). Tutti TIENI nell allegato A: ⛔ in F009 tre TIENI su sette avevano rami della mensa — leggi cercando `persone`, `pasti`, `mensa`, `Servizio`, `Codice`, `Invii`, `Cartella_Dati`, `datiftp`, `fuzzy` e le query **senza** `class_exists` su tabelle della ristorazione (quelle sono fatali o errori DB che `php -l` e G1 non vedono);
   - poi `carico-e` (RIFAI), `carico-e-143`, `-144`, `-190`, `lotto-e/` (con `class-rdfood-testi-notifiche.php` *ridotto*: restano `invito.persona`, `accesso.persona`, `reinvio`, `reimpostazione`, `blocco`), le viste (con `preferenze.php` *ridotto*), gli stili e gli script, la diagnostica.
   - ⛔ **Da ricordare leggendo** (da F008): `public/js/app.js` ha il ramo «Entra come…» (`scegli`, `profili`): va tolto; `public/views/login.php` (TIENI) va guardato per lo stesso ramo: se c è, è una **differenza** → «Differenze» del registro e nuova edizione dell analisi (la prossima è la **11**) prima di andare avanti.
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi `php -l`; per «ridotto» le modifiche a mano dopo la copia (se il testo non combacia per le tabulazioni, togli per numero di riga dopo averle stampate e controllate); poi il banco (G0 e G1 verdi); nomi del cliente nei commenti guardati in lettura; `/food/` nei commenti diventa `/academy/`; il nome del prodotto di partenza nei commenti che scrivi tu non va (G1 lo trova, anche in maiuscolo).
   - Ogni costante d una classe tolta va cercata a mano: G1 vede le chiamate alle classi tolte, non le costanti di classi tenute che nella copia non esistono più.
   - Una differenza = edizione dell analisi (`strumenti/genera-analisi-tecnica.py … "ed. N"`) e riga nelle «Differenze», **commit a parte, prima** della copia del file.
2. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde** (G0, G1, G2, G10), più la trappola `--trappola`; G3-G7 sul palco.
3. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §11 — PROMPT PER LA SESSIONE F010
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F010.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F010. Leggi i messaggi nuovi e rispondi; leggi i quattro registri (`CLAUDE.md`) e dillo in una riga. Poi aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit 5e4a40c1423aa9bad10affc8d10e580100ab0bfc (1.24.11L) **solo in lettura** e continua il blocco G dal punto scritto in `docs/REGISTRO-LETTURA-BLOCCO-G.md` (prossimo: `lotto-d/class-rdfood-sorgenti.php`, poi il resto di `carico-d`): leggi riga per riga ogni file prima di copiarlo (`spec/REGOLE-FAMIGLIA-RELDOX-v4.md` § 2), copialo con `strumenti/copia-con-segno.py`, togli ciò che dice l allegato A dell analisi ed. 10 e i nomi del cliente nei commenti; una differenza con l allegato A vuole la sua edizione (prossima: 11) prima della copia; manuale e codice sempre distinti (D29). Dopo ogni file il banco: G0 e G1 restano verdi. A copia completa i banchi G0, G1, G2, G10 verdi. Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F011.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
