<!-- REGISTRO BUG · 19/11/2026 F015 · aperti 2 · cornice 2/5 · primo libero BUG-3 -->
<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Registro BUG della Formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Creato in Formazione F009, 18/11/2026, secondo `spec/SPEC-REGISTRI-DI-CHAT-FAMIGLIA-RELDOX-v2.md` del nodo (ordine dell owner 17/11/2026 18:35, chat RelDox CRD S714).

Ci va ciò che **è rotto** nei materiali, negli strumenti, nei banchi o nel codice di RelDoxAcademy (SPEC-REGISTRI v2 § 1). Stati: aperta → in lavorazione → consegnata (versione) → chiusa | respinta | diventata voce n. Si chiude solo con l esito verde sul vero e la citazione dell owner. Cornice: 5 bug aperti.

Si legge all apertura di ogni sessione (§ 5), si scrive subito, nello stesso commit del primo gesto; nessuna scheda si cancella.

## Schede

Nessuna scheda al 18/11/2026: nelle sessioni F001-F008 non risultano scritti difetti né errori di metodo (verificato sulle aperture e sui registri di `docs/`). Niente schede inventate per riempire (§ 6.1).

### BUG-2 — ★★★★★ — 19/11/2026 — pagine senza grafica: cinque fogli di stile chiamati dal codice e assenti dal pacchetto; grafica non conforme alla SPEC-GRAFICA v4
- **Stato**: aperta.
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
