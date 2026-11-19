<!-- REGISTRO BUG · 19/11/2026 F017 · aperti 3 · cornice 3/5 · primo libero BUG-4 -->
<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Registro BUG della Formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Creato in Formazione F009, 18/11/2026, secondo `spec/SPEC-REGISTRI-DI-CHAT-FAMIGLIA-RELDOX-v2.md` del nodo (ordine dell owner 17/11/2026 18:35, chat RelDox CRD S714).

Ci va ciò che **è rotto** nei materiali, negli strumenti, nei banchi o nel codice di RelDoxAcademy (SPEC-REGISTRI v2 § 1). Stati: aperta → in lavorazione → consegnata (versione) → chiusa | respinta | diventata voce n. Si chiude solo con l esito verde sul vero e la citazione dell owner. Cornice: 5 bug aperti.

Si legge all apertura di ogni sessione (§ 5), si scrive subito, nello stesso commit del primo gesto; nessuna scheda si cancella.

## Schede

Nessuna scheda al 18/11/2026: nelle sessioni F001-F008 non risultano scritti difetti né errori di metodo (verificato sulle aperture e sui registri di `docs/`). Niente schede inventate per riempire (§ 6.1).

### BUG-3 — ★★★★★ — 19/11/2026 — Stato sistema diversa da quella di RelDox CRD: la misura dice «conforme» ma l occhio vede un altra pagina
- **Stato**: aperta.
- **Visto**: owner 19/11/2026 16:20, chat Formazione F017, schermate affiancate di wp-admin › Stato sistema: RelDox CRD 3.75.07G (www.ideatechnologies.cloud) e RelDoxAcademy 0.1.04A (www.reldoxacademy.it), più il testo di «Copia configurazione». Owner: *«ancora diversità prendi nota per le prossime modifiche e apri il bug»*.
- **Differenze viste (CRD → Academy)**:
  1. **Testata**: nel CRD una fascia blu scuro a tutta larghezza, con il logo in bianco, il nome, il distintivo della versione scuro, il distintivo dell edizione («Gold») e il sottotitolo. Da noi c è un riquadro bianco con il logo a colori e un distintivo «Alfa» chiaro.
  2. **Riquadri numerici**: nel CRD hanno il bordo superiore verde, il contenuto centrato, l icona colorata di stato, l etichetta maiuscola piccola, il valore grande e una riga sotto. Da noi sono allineati a sinistra, con le icone grigie a tratto e nessun bordo di stato.
  3. **Titoli di sezione**: nel CRD c è un icona davanti al titolo («Database SQL utilizzati», «Utente di servizio…»), da noi no. Nel CRD sembrano **emoji**, che la SPEC-GRAFICA v5 vieta (G6, C5): da noi va l icona SVG del registro comune.
  4. **Sezione del primo accesso**: nel CRD si chiama «Utente di servizio e primo accesso», ha un testo che spiega e la sottosezione «Casella dell utente di servizio». Da noi si chiama «Credenziali primo accesso».
  5. **Tabella dei database**: il CRD dà un nome alle sorgenti («Customer Service», «Riserva»), scrive lo stato «Non configurata» e chiude con la nota «Una sorgente non configurata non è in uso e non scrive sul BASE: è il comportamento previsto, non un errore.». Da noi c è «3 · Sorgente 3», «NON IN USO · ricade sul BASE» in maiuscolo e una nota con la parola inglese «provisionate» (`includes/carico-d.php` r.138-139, `class-rdacademy-stato-sistema.php` r.348).
  6. **Data d installazione**: il CRD dà l ora locale, da noi c è l ora UTC con la scritta «UTC».
  7. **Pulsante «Copia configurazione»**: da noi ha l icona, nel CRD no (differenza minore, da allineare).
  8. **Testo copiato**: scrive «Ancora di dominio: active», in inglese, mentre a schermo dice «attiva» (`class-rdacademy-stato-sistema.php` r.205); scrive anche «Cache pagine: arancio», con la parola del semaforo al posto di uno stato.
  9. **Ragione sociale**: nel sottotitolo e nel testo copiato c è «Idea Technologies S.r.l.» invece di «Idea Technologies srl soc. Uninominale».
- **Causa**: il banco `banco/F016-banco-grafica.py` e `conformita-grafica.py` misurano solo le regole C1-C6 della SPEC (foglio comune, blu vecchio, testo sotto i 13 px, emoji, finestre del browser). Non confrontano la pagina con quella di riferimento. Nel nodo non c è un modello comune della pagina Stato sistema (`spec/`, `comune/`), quindi «CONFORME» non vuol dire «uguale al CRD».
- **Rimedio (alle prossime modifiche, dopo l esito di BUG-2)**:
  - Rifare Stato sistema sul modello del CRD 3.75.07G: testata a fascia, riquadri con bordo di stato, titoli con icona SVG e nessuna emoji, sezione del primo accesso, nomi e note della tabella dei database, ora locale, testo copiato tutto in italiano con la ragione sociale.
  - Prima, chiedere al capo famiglia se la Stato sistema del CRD è il modello di famiglia e far presente le icone che sembrano emoji.
  - Banco con un controllo per ognuna delle nove differenze: ROSSO sulla 0.1.04A, VERDE sulla nuova.

### BUG-2 — ★★★★★ — 19/11/2026 — pagine senza grafica: cinque fogli di stile chiamati dal codice e assenti dal pacchetto; grafica non conforme alla SPEC-GRAFICA v4
- **Stato**: consegnata (0.1.04A, kit K0007), da chiudere con l esito dell owner sul sito.
- **Consegna (F016, 19/11/2026)**: SPEC-GRAFICA **v5** (in vigore dalle 15:35, supera la v4 e la copia da RelDoxChecks): `reldox-grafica.css` e `reldox-icone.json` copiati identici dal nodo e caricati per primi (app e wp-admin); barra laterale comune con la cima vuota (S-1); due loghi in testata col predefinito Idea Technologies; Stato sistema con icone del registro; `admin.css`, `docs.css`, `docs.js`, `rdacademy-search.css` e `.js` scritti coi gettoni; i fogli dei lotti C e D e `rdacademy-pagine.js` non più chiamati (pagine ancora «in sviluppo»). Il banco ha trovato anche **tre script assenti** (`docs.js`, `rdacademy-search.js`, `rdacademy-pagine.js`), sistemati insieme. Misura: `C1 no · C2 no · 32 · 607 · 27 · 0` → `C1 sì · C2 sì · 0 · 0 · 0 · 0` (CONFORME). Banco `banco/F016-banco-grafica.py`: ROSSO sulla 0.1.03A (27/87), VERDE sulla 0.1.04A (95/95) su WordPress con `wp-config` italiano, aggiornamento e installazione nuova.
- **Visto**: owner 19/11/2026 14:33, chat Formazione F015, wp-admin › RelDoxAcademy › Stato sistema sulla 0.1.03A: riquadri uno sotto l altro, senza stile, icone emoji. Owner: *«ancora lontano ad essere conforme con lo standard definito dal capo famiglia, vai a rileggere bene tutte le regole di impostazione»*; 14:33: *«tutta la grafica da usare ... e in caso chiedi al capo famiglia le specifiche»*.
- **Causa (misurata sul codice)**: (1) il codice chiama cinque fogli che nel pacchetto non ci sono: `admin/css/admin.css` (Stato sistema), `assets/docs/docs.css` e `assets/search/rdacademy-search.css` (Documentazione), `public/css/rdacademy-lotto-c.css` (Utenti, Impostazioni, Area tecnica), `public/css/rdacademy-lotto-d.css` (Database); in RelDoxFood 5e4a40c esistono (admin.css 111 righe, lotto-c 1298, lotto-d 122, docs 325, search 225). (2) Anche copiati, non bastano: la SPEC-GRAFICA v4 del nodo chiede gettoni `--nav #0F2A44`, voce aperta ambra, testo 16 px e niente sotto i 13 px (G5), icone SVG a tratto e mai emoji (G6), due loghi con il predefinito Idea Technologies (G16, § 3.2-3.3); `app.css` copiato da RelDoxFood usa il blu `#1E3A5F` (41 volte), ha circa 700 dichiarazioni sotto i 13 px, la Stato sistema usa emoji, manca `public/img/logo/idea-technologies.png`. (3) Manca la voce «Licenza d uso» fra i menu wp-admin (REGOLE v4 § 3.10) e il pacchetto di diagnostica (§ 9.2, R7).
- **Rimedio**: in attesa delle specifiche chieste al capo famiglia (`bacheca/formazione/messaggi/2026-11-19-a-reldox-crd-grafica-standard-academy.md`): quale prodotto vivo applica intera la SPEC-GRAFICA v4 e da quale si copia. Poi banco rosso → verde sulle misure del § 11 della SPEC (1440, 820, 390).

### BUG-1 — ★★★★★ — 18/11/2026 — dopo il wizard il plugin non parte su un WordPress in italiano
- **Stato**: consegnata (0.1.02A), da chiudere con l esito dell owner sul sito.
- **Esito sul sito (19/11/2026 14:33, chat Formazione F015)**: 0.1.03A installata su reldoxacademy.it, BASE connessa, 9 tabelle, 1 accesso registrato (schermata e «Copia configurazione» dell owner). Resta aperta: l owner non ha dato l ok, ha scritto *«fino ad ora questo plugin è ancora lontano ad essere conforme con lo standard definito dal capo famiglia»* (vedi BUG-2).
- **Visto**: owner 18/11/2026 14:37, chat Formazione F013, sul sito di RelDoxAcademy con la 0.1.01A: dopo «Scrivi» del wizard, «Avvia il prodotto» porta a una pagina inesistente e «Primo accesso» dice «Non hai il permesso di accedere a questa pagina».
- **Causa (misurata)**: il wizard cerca in `wp-config.php` la riga inglese «That's all, stop editing»; nel `wp-config` italiano c è «Finito, interrompere le modifiche!», quindi il blocco del database finisce in coda, **dopo** `require_once ABSPATH . 'wp-settings.php'`. WordPress avvia i plugin prima di leggere le credenziali e RelDoxAcademy resta in «solo installazione»: niente `/academy/`, niente «Primo accesso». Rifacendo il wizard, il blocco c è già e la risposta è «già configurato»: il guasto resta. I permalink non c entrano: con la correzione funziona con «Semplice», «Data e nome» e «Nome articolo», senza toccarli.
- **Rimedio (0.1.02A)**: il blocco va prima della riga italiana o inglese, o comunque prima della riga di `wp-settings.php`; un blocco scritto dopo si riconosce e si sposta da solo; la verifica dopo la scrittura controlla anche la posizione; il testo del metodo manuale dice dove incollare in italiano.
- **Banco**: `banco/F013-banco-wpconfig.php`: ROSSO sulla 0.1.01A (3), VERDE sulla 0.1.02A. Prova dallo zip sul WordPress di prova con `wp-config` italiano: 0.1.01A → `/academy/` 404 e «Primo accesso» 403, come sul sito; 0.1.02A → tutto 200, password scelta, dentro.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
