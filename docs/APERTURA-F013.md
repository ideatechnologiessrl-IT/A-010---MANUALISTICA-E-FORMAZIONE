<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F013 — Manualistica e formazione · tredicesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F012, 18/11/2026. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F012 (18/11/2026) ha fatto, tutto in `main`:
- **Apertura** sul nodo VERDE (e26d0a3); un messaggio nuovo, solo informativo (capo famiglia: kit del logo di RelDoxAcademy in `grafica/loghi/reldoxacademy/`), segnato letto; quattro richieste nostre ancora in carico (RelDox CRD, RelDoxFood, RelDoxPortal, Sicurezza-Cyber). **Registri letti**: BUG 0/5 · punti aperti 21 · violazioni 0 · fallimenti 1 (F011-F1).
- **Blocco G**, 10 file letti per intero e copiati (72 in tutto):
  - `lotto-e/class-rdfood-testi-notifiche.php` TIENI, ridotto: restano i cinque messaggi dell account, lo schema, la nascita col set di partenza, il modello della mail, la REST della tabella; tolti trasferimento da Messaggi, voci tedesche all aggiornamento, `aggiungi_se_manca`, testi della pagina rossa e della cassa (`riempi`, `motivo`, `contatti`);
  - `lotto-e/class-rdfood-schema-e.php` **RIFAI**: grammatica uguale (versione `v1`, flag sul fatto, solo `CREATE TABLE IF NOT EXISTS`), le 7 tabelle proprie del § 5 e la nascita dei testi delle mail;
  - `public/views/login.php` TIENI, ridotto (**ed. 14**): tolti «Entra come…» (`rx-login-profili`) e «Come si usa» verso `/manuali`;
  - `public/js/app.js` TIENI, ridotto (**ed. 15**): tolto il ramo «Entra come…»; `window.RDACADEMY.nonce` resta;
  - TIENI, solo il segno e i commenti: `accesso-riservato.php`, `coming-soon.php`, `tf-barra.php`, `rdacademy-144.css`, `rdacademy-icona.js`.
- **Analisi** all **ed. 15**; «Differenze» del registro di lettura con le due voci F012.
- **Banchi** (`banco/esiti/F012-G-parziale.txt`): G0 e G1 verdi (67 PHP, 72 file), trappola G1 verde; G2 e G10 rossi (attesi).
- **Violazione V-1** (`docs/VIOLAZIONI-FORMAZIONE.md`): un commit con G1 rosso (`b0684b5`, due `/food/` nei commenti dell icona), corretto subito in `178d2e7`. ⛔ Il banco si lancia **da solo**, si legge l esito, **poi** si pubblica: mai nella stessa catena del push.
- Chiusa intorno al 70% del contesto, prima di `public/views/spedizioni.php` (95 righe, *ridotto*).

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE). Messaggi, risposte, `--segna`. **Registri letti** (SPEC-REGISTRI § 5): dillo all owner in una riga.
1. Aggancia `ideatechnologiessrl-IT/RelDoxFood` al commit **5e4a40c** (1.24.11L) **solo in lettura**: prima `add_repo` del repository in sola lettura (senza, il proxy chiede le credenziali), poi `mkdir /home/claude/reldoxfood && cd` lì, `git init`, `git remote add origin https://github.com/ideatechnologiessrl-IT/reldoxfood`, `git fetch --depth 1 origin 5e4a40c1423aa9bad10affc8d10e580100ab0bfc`, `git checkout FETCH_HEAD`. Continua le viste, gli stili, gli script e la diagnostica che restano (l elenco: allegato A, decisioni diverse da TOGLI, meno le righe già nel registro di lettura):
   - **viste**: `spedizioni.php` (*ridotto*: il nome della persona dagli utenti, non da `persone`), `recupera.php`, `imposta-password.php`, `preferenze.php` (*ridotto*), `layout.php` (*ridotto*), poi le grandi (`gestione-utenti.php`, `impostazioni.php`, `area-tecnica.php`, `log-accessi.php`, …) e le due RIFAI (`dashboard.php` → catalogo, `manuali.php` → lezione: per la 0.1.01A bastano pagine minime che dicono «in arrivo», niente interfaccia nuova senza mockup);
   - **stili**: `rdfood-leggibilita.css` (la chiede già `carico-e`), `rdfood-lotto-c.css`, `rdfood-lotto-d.css`, `app.css` (7451 righe: una sessione da sola, a pezzi);
   - **script**: `rdfood-pagine.js`, `rdfood-pwd-meter.js`, `rdfood-tf.js`;
   - **diagnostica** e i **file principali** dei due plugin (G2, G10).
   - ⛔ **Da ricordare leggendo**:
     - (da F010) la **cornice delle viste** deve definire `window.RDACADEMY` con `nonce`: `app.js` e il componente allegati lo leggono (`X-RDACADEMY-Nonce`);
     - (da F011) `public/views/layout.php` chiama `rdacademy_icona_tratto( 'guida-cassa' )` e `rdacademy_e_ristoratore()` nel ramo del ristoratore (dietro `function_exists`/`class_exists`): ramo della mensa da togliere; `rdacademy_icona_tratto( 'manuali' )` resta in `carico-e`;
     - (da F012) `accesso-riservato.php` e `coming-soon.php` dicono «← Torna al Pannello»: quando la voce del rail diventa «Catalogo» (`carico-e-190`) va allineato il testo, con la vista del catalogo;
     - (da F012) la chiave `login.come_si_usa` resta nel gestore delle lingue senza chi la usa: si toglie o si riusa quando la porta avrà il collegamento ai corsi;
     - (da F009) cerca sempre `persone`, `pasti`, `mensa`, `Servizio`, `Codice`, `Invii`, `Cartella_Dati`, `datiftp`, `fuzzy`, `ristorat`, `cassa` e le query **senza** `class_exists` su tabelle della ristorazione, anche nei TIENI.
   - Ogni file: **letto per intero**, poi `python3 -I strumenti/copia-con-segno.py /home/claude/reldoxfood <percorso> <decisione> "<nota>"`, poi le modifiche con uno script fuori dal repository (`/home/claude/script/`), `php -l` o `node --check`, il banco **da solo** (G0 e G1 verdi), **poi** commit e push. Nei commenti `/food/` diventa `/academy/` (il segno non lo fa: G1 lo vede); via sigle, versioni e citazioni dell owner della fonte.
   - ⛔ Negli script di sostituzione conta l indentazione a **tab** della fonte: `cat -A` prima di scrivere il blocco da cercare.
   - Una differenza = edizione dell analisi (prossima: **16**) e riga nelle «Differenze», **commit a parte, prima** della copia del file.
2. A copia completa: `python3 -I banco/F005-G-banchi-copia.py . /home/claude/reldoxfood` **verde** (G0, G1, G2, G10), più `--trappola`; G3-G7 sul palco.
3. Lezioni: ferme alla 1.2 (D26).

## §02 — CHE COSA ASPETTO DALLE ALTRE CHAT
- **Sicurezza-Cyber**: il parere sulla SPEC (accesso firmato, conservazione). Serve prima della porta 1, non per la 0.1.01A.
- **RelDox CRD**: la voce 159 e la proposta del pezzo comune «Accesso ai corsi» (roadmap F18).
- **RelDoxChecks, RelDoxFood, RelDoxPortal**: le risposte all inventario dei manuali (dal 15/11; il sollecito tocca al capo famiglia).

## §03 — NOTE PER L OWNER, quando c è qualcosa da provare (non domande ora: F011-F1)
- **Colonne delle 7 tabelle dello schema E**: il § 5 le descrive a parole; F012 le ha scritte così, da portare all owner con la prima installazione: `domande` ha `risposte` (JSON, l elenco della domanda chiusa, § 7.1 «una sola risposta giusta») e `giusta` (indice); `percorsi` ha `codice` (il `<percorso>` dell id delle lezioni) con chiave unica prodotto + codice + lingua; `lezioni` ha chiave id + lingua e `percorso_id`; `aziende` ha `identificativo` NULL finché la chiave non lo porta, e la coppia prodotto + dominio unica; `persone_azienda` ha una riga per utente, azienda e prodotto; `registro` una riga per utente e lezione. Ogni cambio dopo sarà solo `ALTER ADD` (versione `v2`).
- Il controllo delle mail rifiutate nell Academy è solo col pulsante: si riprende quando il plugin funziona. ⛔ Regola F011-F1: prima un plugin installabile, poi i dettagli.

## §10 — IL LAVORO DI F013 (sostituisce il § 01 punto 1: fallimento F012-F1)
Solo il **pacchetto installabile 0.1.01A**. Per attivarsi il plugin chiede ancora (controllo dei `require` del 18/11/2026):
- `public/views/layout.php` (670 righe, *ridotto*: via il ramo del ristoratore e della cassa; definisce `window.RDACADEMY` col `nonce`);
- `includes/bootstrap/class-rdfood-bootstrap-wizard.php`, `includes/docs/views/docs-page.php`, la cartella `docs-content/`;
- `public/img/logo/reldox-favicon.svg` e `reldox-symbol.png` → dal kit del logo di RelDoxAcademy nel nodo (`grafica/loghi/reldoxacademy/`);
- le viste `contatto-form.php` e `setup-personale.php`, poi quelle che `layout.php` apre (le grandi si portano ridotte all essenziale; `dashboard.php` e `manuali.php` come pagine minime «in arrivo»);
- `app.css` subito dopo, se la pagina è illeggibile senza.
Poi: banco G0 e G1 verdi, `reldox-academy-0.1.01A.zip` mandato all owner con tre righe su come installarlo e che cosa guardare.

## §11 — PROMPT PER LA SESSIONE F013
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F013.md` (§ 10). Aggancia il nodo `RelDox-Famiglia` e fai l apertura (`apertura-nodo.py --prodotto formazione`). Aggancia `RelDoxFood` in sola lettura al commit 5e4a40c.
> **Obiettivo unico: lo zip di RelDoxAcademy 0.1.01A che si installa su WordPress.** Copia solo i file che mancano perché il plugin si attivi (elenco nel § 10), leggendoli per intero, e banco verde dopo ognuno.
> Consegna lo zip all owner per la prova sul sito. Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
