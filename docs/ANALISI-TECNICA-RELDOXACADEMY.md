<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# ANALISI TECNICA DEL PRODOTTO — RELDOXACADEMY

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale.

**Edizione 9 · F009 · 18/11/2026 · nessun pacchetto (base: RelDoxFood 1.24.11L, `main` 5e4a40c)**

| edizione | sessione · data | che cosa cambia |
|---|---|---|
| 9 | Formazione F009 · 18/11/2026 | differenza trovata nella lettura riga per riga del blocco G: `includes/lotto-c/class-rdfood-impostazioni.php` (TIENI nell allegato A) ha nel salvataggio i rami della ristorazione dietro `class_exists` — Santo Patrono e festività del servizio (`Rdfood_Servizio`, TOLTA), recapiti della pagina personale (`Rdfood_Codice`, TOLTA) — e la porta propria della cartella dati (`datiftp_*`, TOLTA) → **TIENI, ridotto** nell allegato A, come per `preferenze.php` (ed. 5) |
| 8 | Formazione F009 · 18/11/2026 | differenza trovata nella lettura riga per riga del blocco G: `includes/lotto-c/class-rdfood-utenti.php` (TIENI nell allegato A) in `login_libero()` interroga **senza condizione** la tabella `persone` della mensa (username già usato come nome utente di una scheda): nell Academy la tabella non c è e ogni salvataggio di un utente con username finirebbe in «Errore DB»; in più due rami della ristorazione dietro `class_exists` (email condivisa, classe TOLTA; nome utente della scheda della mensa) → **TIENI, ridotto** nell allegato A |
| 7 | Formazione F008 · 17/11/2026 | differenza trovata nella lettura riga per riga del blocco G: `includes/carico-b.php` (TIENI nell allegato A) richiede in modo fisso i cinque file della licenza da firmare (`license/`, TOLTI) e avvia `Rdfood_License_Admin` → **TIENI, ridotto** nell allegato A, come già diceva il § dei carichi («senza la licenza da firmare»): restano Stato sistema, ancora di licenza (solo il dominio), Documentazione, Primo accesso |
| 6 | Formazione F007 · 17/11/2026 | differenza trovata nella lettura riga per riga del blocco G: `includes/security/class-rdfood-password-token.php` ha lo scopo `f` del foglio di accesso stampato (classe `Rdfood_Foglio_Accesso`, TOLTA; chiamata dietro `class_exists`) → **TIENI, ridotto** nell allegato A: restano gli scopi invito e reimpostazione |
| 5 | Formazione F006 · 17/11/2026 | differenza trovata nella lettura riga per riga del blocco G: `public/views/preferenze.php` ha il ramo «I miei dati» del ristoratore (dietro `class_exists`) → **TIENI, ridotto** nell allegato A; domanda 3 del § 15 chiusa (D31: mail di servizio `academy@reldox.it`) |
| 4 | Formazione F005 · 17/11/2026 | edizione 3 approvata (D27); **manuale e codice tenuti distinti** (D28, D29): ogni manuale, guida, specifica e novità di RelDoxFood, API comprese, non si toglie più ma entra nell Academy **come materiale** (decisione nuova MATERIALE nell allegato A, § 4, § 7.1, blocco M); il motore delle API resta solo in RelDoxFood; nulla si toglie dai prodotti |
| 3 | Formazione F004 · 17/11/2026 | scritta **sul codice di RelDoxFood 1.24.11L**: database (§ 2), ciclo di vita (§ 3), albero intero con tieni/togli (§ 4 e allegato A), tabelle ereditate (§ 5), nomi presi dal codice (§ 1), cornice (§ 9), rail Setup (§ 10), banchi standard (§ 14); prima forma `.html` col generatore |
| 2 | Formazione F003 · 17/11/2026 | base **RelDoxFood 1.24.11L**, non il guscio; segno, indirizzo, diagnostica e ingresso della 0.1.01A confermati dal capo famiglia (RelDox CRD S713) |
| 1 | Formazione F003 · 17/11/2026 | prima stesura, sulla SPEC 1.10 e sul mockup 0.1.01A v1 |

> ⛔ **È il documento della struttura del prodotto** (`REGOLE-FAMIGLIA-RELDOX-v4` § 7.0): non si scrive codice su una struttura che non è qui.
> ⛔ **Due forme, un testo**: questo `.md` è il canonico; l `.html` si genera con `python3 -I strumenti/genera-analisi-tecnica.py` (nato dal generatore di RelDoxFood, `banco/S67-1-genera-analisi-tecnica.py`).
> ⛔ **I contratti si richiamano, non si ricopiano**: SPEC di RelDoxAcademy 1.11, regole di famiglia v4, SPEC della manutenzione v2, SPEC dell indurimento v1, SPEC delle API v8.
> Ciò che arriva dal codice si segna **«da RelDoxFood 1.24.11L»**.

**Decisioni che aprono questo lavoro** — D26 (owner 17/11/2026 12:10, chat Formazione F002): *«ferma la lezione e crea il plugin secondo lo starda di quelli esistenti, chiedi al capo famiglia in caso»*; base RelDoxFood 1.24.11L (capo famiglia, RelDox CRD S713, 17/11/2026; owner 17/11/2026 11:48, chat RelDox CRD S713: *«la decidione tecnica è del programmatore»*).

**Come è stato letto il codice** (§ 2 delle regole). RelDoxFood `main` 1.24.11L: 194 file di codice del prodotto (69.291 righe, compresi i 75 manuali HTML) e 7 della diagnostica (2.005 righe). Due passate:
1. **inventario di ogni file**, con uno strumento che li apre tutti: classi, funzioni, carichi, mappe delle pagine e delle azioni, tabelle create, opzioni di WordPress, rotte REST, dipendenze fra le parti da tenere e quelle da togliere;
2. **lettura diretta del nucleo**: file principale, instradatore, attivatore, opzioni sul BASE, configurazione e sorgenti del database, cornice, registro delle Tabelle Funzionali, schemi C ed E, accesso d emergenza, diagnostica.
⛔ Non è ancora la lettura **riga per riga** di ogni file. Si fa nel blocco G, **file per file, prima di copiarlo**: ciò che quella lettura trova di diverso da qui entra in un edizione 4 prima di andare avanti.

---

## § 0 — Stato dello sviluppo
{proposta} edizione 4 (la 3 approvata, D27), nessun codice · *il codice parte solo dopo l approvazione e il «genera» dell owner*

| voce | che cosa | stato | dove si legge |
|---|---|---|---|
| A0 | Analisi tecnica, edizione 4 sul codice di RelDoxFood (la 3 approvata, D27) | {proposta} | questo documento |
| A1 | Copia di RelDoxFood 1.24.11L con nome e segno nuovi, ristorazione tolta: `reldox-academy/` e `reldox-academy-diagnostica/` | {dafare} | § 1, § 4, allegato A |
| A2 | Caricamento dei materiali dal repository (Markdown → tabelle) | {dafare} | § 7.1 |
| A3 | Ingresso 0.1.01A: login interno e persone di prova | {dafare} | § 8 |
| A3 bis | Ingresso con accesso firmato e controllo della chiave, versione successiva | {proposta} | § 8 |
| A4 | «Chi sei?» con Iris | {dafare} | § 7.4, § 9 |
| A5 | Il mio catalogo | {dafare} | § 7.2 |
| A6 | La lezione con la verifica | {dafare} | § 7.3 |
| A7 | Registro dell azienda, CSV e PDF | {dafare} | § 7.5 |
| A8 | «Stato e assistenza» e «Copia configurazione» nella riga dei due plugin | {dafare} | § 11 |
| A9 | Banchi del prodotto e banchi standard | {dafare} | § 14 |

---

## § 1 — Il prodotto e l installazione sono due cose
{proposta} nomi presi dal codice di RelDoxFood 1.24.11L · *segno, indirizzo e diagnostica già confermati (D14; RelDox CRD S708 e S713)*

- **Il prodotto** è RelDoxAcademy, rivendibile come gli altri. **L installazione** oggi è una sola, su `reldoxacademy.it`, tenuta da Idea (D9; SPEC § 2.1). Niente nel codice dipende dal dominio.
- **Il marchio** (logo «Academy», indaco `#3E3F9B`) è un dato, da `risorse/logo-academy/`, non scritto nelle viste.

**Scheda dei nomi**: a sinistra com è in RelDoxFood, a destra come diventa. Ogni nome si cambia in un punto solo, come fa già RelDoxFood (`reldoxfood.php`, righe 313-389).

| che cosa | in RelDoxFood 1.24.11L | in RelDoxAcademy | stato |
|---|---|---|---|
| file principale | `reldoxfood/reldoxfood.php` | `reldox-academy/reldox-academy.php` | approvato (SPEC § 2.2) |
| classi · funzioni · costanti | `Rdfood_` · `rdfood_` · `RDFOOD_` | `Rdacademy_` · `rdacademy_` · `RDACADEMY_` | {proposta} |
| versione · stadio · etichetta | `RDFOOD_VERSION` · `RDFOOD_STAGE` · `RDFOOD_VERSION_LABEL` | `0.1.01` · `Alfa` · `0.1.01A` | {proposta} |
| pacchetto | `RDFOOD_PACKAGE` `// 1.24.11L \| S108 \| log-accessi-standard` | `// 0.1.01A \| F00n \| <tema>` | {proposta} |
| credenziali del BASE in `wp-config.php` | `RDFOOD_DB_HOST/NAME/USER/PASS/CHARSET` | `RDACADEMY_DB_HOST/NAME/USER/PASS/CHARSET` | {proposta} |
| prefisso delle tabelle | `RDFOOD_TABLE_PREFIX` = `rdfood_` | `rdacademy_` | {proposta} |
| indirizzo dell applicazione | `RDFOOD_APP_SLUG` = `food` (`/food/`) | `academy` (`/academy/`) | registrato nelle regole v4 |
| variabili d indirizzo | `rdfood_page`, `rdfood_id`, `rdfood_codice` | `rdacademy_page`, `rdacademy_id` (il codice non serve) | {proposta} |
| canale delle chiamate | azione AJAX `rdfood`, nonce `rdfood_ajax`, campo `rdfood_action` | `rdacademy`, `rdacademy_ajax`, `rdacademy_action` | {proposta} |
| innesti (filtri e azioni) | `rdfood_page_map`, `rdfood_page_access`, `rdfood_capability_map`, `rdfood_nav_items`, `rdfood_setup_pages`, `rdfood_setup_sistema_items`, `rdfood_ajax_<azione>`… | stessi nomi col segno `rdacademy_` | {proposta} |
| cookie della sessione | `rdfood_session`, `rdfood_scaduta`, `rdfood_vista` | `rdacademy_session`, `rdacademy_scaduta`, `rdacademy_vista` · *l ed. 2 diceva `rdacademy_seduta`: vale il nome del codice* | {proposta} |
| spazio REST | `rdfood/v1` (Tabelle Funzionali, allegati, testi delle notifiche) | `rdacademy/v1` | {proposta} |
| plugin di diagnostica | `reldoxfood-diagnostica/`, `RDFOOD_DIAG_*` | `reldox-academy-diagnostica/`, `RDACADEMY_DIAG_*` | confermato (RelDox CRD S713) |
| mail di servizio | `RDFOOD_SERVICE_EMAIL` = `admin@reldox.it` | stessa costante col segno nuovo; indirizzo da decidere con l owner | {proposta} |

*Nota trovata leggendo*: in RelDoxFood 1.24.11L la diagnostica dichiara ancora `RDFOOD_DIAG_PACKAGE = '// 1.20.0L | S76 | menu-del-giorno'`, mentre la versione è 1.24.11. Nell Academy il pacchetto della diagnostica si scrive a ogni versione e lo controlla un banco (§ 14).

---

## § 2 — Architettura: un prodotto, i suoi database
{proposta} da RelDoxFood 1.24.11L, senza cambiamenti di forma

I tre livelli di database del § 3.5 delle regole, come li fa RelDoxFood:

| n. | database | come lo fa RelDoxFood 1.24.11L | nell Academy |
|---|---|---|---|
| **1** | WordPress | non contiene tabelle del prodotto; solo **sei opzioni di avvio** (§ 13) | uguale |
| **2** | BASE del prodotto | database proprio; credenziali solo in `wp-config.php` (`Rdfood_DB_Config`); se ne manca una il prodotto non parte e si apre l **installazione guidata** (`Rdfood_Bootstrap_Wizard`, che rifiuta un BASE uguale a quello di WordPress e scrive il blocco delle costanti); lo stato del prodotto vive sul BASE nella tabella `options` (`Rdfood_Option`, sempre la connessione del BASE) | uguale: qui stanno tutte le tabelle del § 5 |
| **3-5** | sorgenti | registro `data_sources` sul BASE con password cifrata (`Rdfood_Cifra`, busta `enc1:`); tre posti: `sorgente_3`, `sorgente_4`, `riserva` (`Rdfood_Sorgenti::MAPPA`); due stati IN USO / NON IN USO; se una sorgente non risponde si ricade sul BASE (`Rdfood_DataSource`); le tabelle nascono quando la si accende (`Rdfood_Sorgente_Provisioner`); backup per sorgente e «Correggi da backup» (`Rdfood_Backup`) | **si tiene il meccanismo, nessuna sorgente accesa**: i materiali e il registro dell Academy sono piccoli e stanno sul BASE. Una sorgente si accende solo se il registro cresce (decisione dell owner, più avanti) |

- **La convivenza** con altri prodotti sullo stesso WordPress è data dai nomi del § 1; sul WordPress dell Academy non ci sono altri prodotti RelDox (SPEC § 2.1).
- **L autoriparazione dello schema** si copia così com è: `Rdfood_Provisioning_Guard` a ogni avvio, `Rdfood_Schema_Flag_Guard` (un flag non basta, la tabella deve esistere davvero), schemi che fanno solo `CREATE TABLE IF NOT EXISTS` e `ALTER … ADD`.
- **Il motore delle API esterne** (`includes/ext/`, dieci tabelle `ext_*` ed `eliminazioni`) **si tolgono**: sono le API che RelDoxFood offre ai gestionali dei clienti. L Academy, nella 0.1.01A, non offre API; quando chiederà anagrafiche e stato del canone a RelDox CRD sarà lei a chiamare, con le API di famiglia (CLAUDE.md di A-010). Il motore delle API si ricopierà, dalla versione in vigore, solo se un giorno l Academy dovrà rispondere a qualcuno.

---

## § 3 — Ciclo di vita
{proposta} da RelDoxFood 1.24.11L

| momento | che cosa fa RelDoxFood 1.24.11L | nell Academy |
|---|---|---|
| caricamento del file principale | se manca una delle cinque costanti del BASE: solo l installazione guidata, nessun altro file, il sito resta in piedi | uguale |
| attivazione (`register_activation_hook`) | `Rdfood_Activator::activate()`: crea le tabelle del nucleo, semina ruoli («Service Account», «Admin») e l utente di servizio con una password che nessuno conosce, scrive la versione, alza il flag dello schema solo se le tabelle esistono davvero, chiede il rinnovo delle regole d indirizzo | uguale; i ruoli seminati sono quelli del § 6 |
| ogni avvio (`plugins_loaded`, priorità 5) | se `rdfood_db_version` < versione: riattiva; poi `Rdfood_Provisioning_Guard::provision()` (ricrea ciò che manca, a flag allineati non fa nulla) | uguale |
| carichi | `carico-b` (menu di WordPress, licenza, documentazione, stato del sistema, accesso d emergenza), `carico-c` (Gestione utenti, Preferenze, Impostazioni, Tabelle Funzionali, Area tecnica), `carico-d` (sorgenti, Database, allegati, backup), `carico-e` (la mensa, con i carichi a catena `carico-e-1xx`); ognuno si carica solo se il file c è (`is_readable`) | `carico-b`, `carico-c`, `carico-d` si tengono (senza la licenza da firmare); **`carico-e` diventa il carico dell Academy** (materiali, catalogo, lezione, registro, Iris) e tiene solo due pezzi generici di oggi: l indipendenza dalla cache (`carico-e-143`) e il registro delle spedizioni (`carico-e-144`) |
| aggiornamento | per rimozione e reinstallazione; `upgrader_process_complete` svuota l OPcache | uguale |
| disattivazione | rinnova le regole d indirizzo e spegne il pianificatore degli invii del registro (`Rdfood_Invii::spegni`, ogni 15 minuti) | rinnova le regole; il pianificatore non c è più |
| rimozione | **non c è `uninstall.php`**: il BASE resta intero, restano le opzioni di avvio | uguale per il BASE; il registro di completamento segue la conservazione della SPEC § 11.4 (30 giorni dopo la cessazione, poi si cancella), con un controllo della diagnostica (§ 12) |

---

## § 4 — L albero dei file
{proposta} l albero intero, file per file, è nell **allegato A**; qui il riassunto

⛔ **Manuale e codice sono due cose distinte** *(owner 17/11/2026 15:10, chat Formazione F005: «tieni sempre presente ciò che è manuale e ciò che è codice . Tu non entri mai nel codice»)*. Il **codice** di RelDoxFood si legge e basta: non si cambia un file e non si scrive nel suo repository; nel codice di RelDoxAcademy entra solo la copia del motore che serve ai corsi. I **manuali** (guide, manuali dei ruoli, manuale e guida delle API, specifiche d integrazione, novità, pagine di documentazione) non si tolgono mai: entrano nell Academy **come materiali** (decisione MATERIALE). ⛔ **Nulla si toglie dai prodotti** *(owner 17/11/2026 15:08, D28)*: i manuali restano anche nei plugin; solo quando nell Academy tutto è in linea e funziona le chat dei prodotti tolgono la loro manualistica.

Sul codice (PHP, JS, CSS) di RelDoxFood 1.24.11L, 83.964 righe:

| decisione | file | righe | che cosa vuol dire |
|---|---|---|---|
| TIENI | 91 | 32.475 | si copia con nome e segno nuovi, senza cambiare la logica |
| TIENI, ridotto | 13 | 9.171 | si copia e si tolgono i rami della ristorazione; oggi quei rami sono quasi tutti dietro `class_exists(…)`, perciò togliere le classi non rompe il resto; il banco G lo prova |
| RIFAI | 18 | 2.388 | resta il posto e la forma, cambia il contenuto (file principale, carico dell Academy, schema, catalogo, lezione, marchio) |
| TOGLI | 97 | 39.479 | non entra nella copia (in RelDoxFood resta tutto): ristorazione (menu, cassa, codice personale e QR, pasti, ospiti e FUZZYCode, statistiche, invii del registro, cartella dati, ristoratori), motore delle API esterne, contratto di licenza da firmare |
| MATERIALE | 24 | — | manualistica, non codice: 6 pagine Markdown (API e collegamenti, manuali dei ruoli, codice personale, tre novità per l integratore), 11 PDF (manuali dei ruoli in tre lingue, manuale dell amministratore, guida dello sviluppatore del gestionale, specifiche d integrazione ed. 8-10), 7 cartelle con le 75 pagine HTML dei manuali; si caricano nell Academy come materiali (§ 7.1, blocco M) e restano anche in RelDoxFood (D28) |

Per cartella:

| cartella di RelDoxFood | tieni | rifai | togli | materiale |
|---|---|---|---|---|
| `reldoxfood.php` | | 1 | | |
| `includes/` (nucleo: database, opzioni, accesso, ruoli, instradatore, lingua, stato del sistema) | 23 | 2 | 13 (carichi `carico-e-1xx` della mensa) | |
| `includes/bootstrap/`, `backup/`, `docs/`, `lotto-c/`, `lotto-d/`, `uploads-js/` | 27 | | | |
| `includes/security/` (password, gettoni, casella di servizio, cifratura, allegati) | 12 | | | |
| `includes/lotto-e/` | 3 (testi delle notifiche, spedizioni, icona sul telefono) | 1 (lo schema) | 24 | |
| `includes/ext/`, `includes/license/`, `includes/novita/`, `includes/vendor/` | | | 19 | |
| `public/views/` | 16 | 2 (Pannello → catalogo; manuali → lezione) | 18 | |
| `public/js/`, `public/css/`, `public/img/` | 11 | 8 (marchio) | 19 | |
| `assets/`, `admin/`, `docs-content/` | 6 | 1 | 2 | 6 |
| `manuali/` | | 3 | | 18 |
| `reldoxfood-diagnostica/` | 5 | | 2 (azzera il contratto; licenza per l API) | |

**Che cosa si aggiunge**, accanto ai file tenuti (nomi da confermare alla scrittura, uno per responsabilità come in RelDoxFood):

```
reldox-academy/
  includes/academy/
    class-rdacademy-schema-academy.php   tabelle proprie del § 5 (grammatica di Rdfood_Schema_E_V1)
    class-rdacademy-materiali.php        caricamento tutto o niente, § 7.1 (usa Rdfood_Markdown → Rdacademy_Markdown)
    class-rdacademy-catalogo.php         chi vede che cosa, § 7.2
    class-rdacademy-lezione.php          lezione e verifica, § 7.3
    class-rdacademy-iris.php             battute dal file di testo, § 7.4
    class-rdacademy-registro.php         registro, CSV e PDF, § 7.5
  public/views/  catalogo.php (dal Pannello) · lezione.php (dai manuali) · chi-sei.php · registro.php · pausa.php
  lang/it/iris.txt                       catalogo delle battute (docs/IRIS-v1.md)
  materiali/                             i materiali della 0.1.01A, con etichetta e commit (SPEC § 16)
reldox-academy-diagnostica/              i cinque file tenuti, col segno nuovo, più i controlli propri del § 12
```

---

## § 5 — Lo schema del BASE
{proposta} 15 tabelle ereditate da RelDoxFood 1.24.11L + 7 proprie · *nomi senza prefisso*

**Ereditate**, da RelDoxFood 1.24.11L (colonne lette dal codice):

| tabella | colonne | da dove | nell Academy |
|---|---|---|---|
| `options` | chiave e valore dello stato del prodotto | `Rdfood_Option` | uguale |
| `users` | nome, cognome, email, login, password, `tipo` (interno · esterno · integratore), avatar, stato, ultimo accesso, tentativi falliti, bloccato fino a; il lotto C aggiunge telefono, cellulare, ufficio, matricola, mansione, responsabile | `Rdfood_Activator`, `Rdfood_Schema_C_V1` | uguale; il tipo `integratore` non serve (era delle API) e resta solo come valore ammesso, nessuna interfaccia |
| `roles` | nome, descrizione, colore, `is_backdoor`, `is_system`, permessi in JSON | `Rdfood_Activator` | uguale; ruoli del § 6 |
| `user_roles` | utente, ruolo, assegnato il, da | `Rdfood_Activator` | uguale |
| `sessions` | utente, impronta del gettone, IP, browser, scadenza | `Rdfood_Activator` | uguale |
| `access_log` | utente, cosa è stato scritto, IP, browser, esito, motivo | `Rdfood_Activator` | uguale |
| `activity_log` | utente, oggetto, azione, descrizione, prima, dopo, IP | `Rdfood_Activator` | uguale |
| `impostazioni` | chiave, valore, tipo | `Rdfood_Activator` | uguale, chiavi della ristorazione tolte |
| `user_prefs` | utente, chiave, valore JSON | `Rdfood_Activator` | uguale |
| `license_activation` | il dominio d attivazione | `Rdfood_Schema_License_V1` | uguale |
| `data_sources` | registro delle sorgenti 3-5, password cifrata | `Rdfood_Schema_Datasources_V1` | uguale, nessuna accesa (§ 2) |
| `uploads` · `uploads_blob` | allegati per qualunque oggetto e i loro file | `Rdfood_Schema_Uploads_V1`, `…_Blob_V1` | uguale: servono per le immagini delle lezioni |
| `tf_testi_notifiche` | testi delle mail per lingua | `Rdfood_Testi_Notifiche` | ridotta alle mail dell account |
| `spedizioni` | registro delle mail spedite | `Rdfood_Spedizioni` | uguale |

**Non si copiano** (ristorazione e API): `persone`, `pasti`, `fuzzy`, `fuzzy_ditte`, `inviti`, `riservati`, `giorni_persona`, `ristoranti_importi`, `ristoranti_valori`, `ristoranti_menu`, `pasti_voci`, `menu_righe`, `note_ristoratori`, `campi_mano`, `tf_invii_registro`, `invii_registro_log`, `tf_nomi_file`, le dieci `ext_*` ed `eliminazioni`.

**Proprie del prodotto** (dalla SPEC; la persona del registro è un utente di `users`, perciò l ed. 2 si semplifica: niente tabella `persone` a parte nella 0.1.01A):

| tabella | che cosa contiene | voce |
|---|---|---|
| `carichi` | ogni caricamento dei materiali: etichetta, commit del repository A-010, data, chi | A2 |
| `percorsi` | prodotto, ruolo (pubblico), titolo, ordine, lingua | A2 |
| `lezioni` | `id` (`<prodotto>-<percorso>-L<nn>`), percorso, titolo, `vale_per`, durata, HTML già pronto, lingua, versione del materiale | A2 |
| `domande` | domande della verifica e risposta giusta: **mai mandate al browser** | A2, A6 |
| `aziende` | identificativo dell azienda (dalla chiave, voce 159 di RelDox CRD); fino ad allora la coppia prodotto + dominio | A3 |
| `persone_azienda` | utente (`users.id`), azienda, prodotto e versione, ruolo nel prodotto · per la porta 1 anche l identificativo della persona nel prodotto (SPEC § 11.1: nessuna mail né password in più) | A3 |
| `registro` | utente, lezione, aperta il, completata il, esito (giuste su totali), tentativi | A6, A7 |

Con la porta 1 (dopo la 0.1.01A) si aggiungono `accessi_usati` (impronta dell accesso firmato e scadenza) e le righe d accesso nel `access_log` esistente: niente tabella `registro_accessi` a parte, perché il registro degli accessi c è già.

Scelta tecnica dichiarata: il Markdown si converte in HTML **al caricamento**, col convertitore che RelDoxFood ha già (`Rdfood_Markdown`, 281 righe, oggi per la Documentazione in wp-admin); se non basta per le lezioni lo dice il banco B6.

---

## § 6 — Utenti, ruoli e sicurezza
{proposta} accesso da RelDoxFood 1.24.11L; segreti della SPEC · *parere di Sicurezza-Cyber atteso prima della porta 1*

- **Accesso**, da RelDoxFood 1.24.11L: utenti propri (mai utenti WordPress), login con email o nome utente e risposta sempre uguale verso l esterno (`Rdfood_Router::ajax_login`, `Rdfood_Auth`), sessione da 8 ore di inattività che si rinnova lavorando, nel database solo l impronta del gettone; recupero e imposta password (`Rdfood_Password_Token`, `…_Policy`, `…_Mail`); casella di servizio verificata da una spedizione vera (`Rdfood_Casella`); freno: 60 scritture al minuto per utente e 200 chiamate al minuto per indirizzo; ritardo sui tentativi falliti senza blocco (owner 17/11/2026 11:50, chat Sicurezza-Cyber S3); **primo accesso e rientro d emergenza** da wp-admin con collegamento monouso (`Rdfood_Accesso_Emergenza`).
- **Permessi**, da RelDoxFood: ogni pagina e ogni azione dichiara il suo livello (`public`, `auth`, `interno`, `write`, `admin`, `backdoor`); **ciò che non è dichiarato vale `admin`** (`Rdfood_Router::page_level`, `handle_ajax`).
- **Ruoli seminati** nell Academy: «Service Account» e «Admin» come in RelDoxFood (personale interno, `tipo` = interno); in più «Studente» e «Amministratore del cliente» (`tipo` = esterno), che vedono catalogo e lezioni, e il secondo anche il registro della sua azienda. Il ruolo scelto in «Chi sei?» cambia solo la vista, **mai i permessi**.
- **Persone di prova** della 0.1.01A: utenti `esterno` creati dal personale interno in Gestione utenti, con dati inventati (`@esempio.it`).
- **Segreti**: chiave di licenza mai salvata; chiavi pubbliche di RelDox Chiavi in Impostazioni; credenziali solo in `wp-config.php`; risposte giuste solo sul server; personale interno con doppio fattore quando il pezzo di famiglia c è (oggi non è in RelDoxFood: regole v4 § 3.3, mockup da approvare).

## § 7 — Materiali e percorsi (il cuore del prodotto)
{proposta} dalla SPEC § 4-§ 7 e dal mockup 0.1.01A v1 approvato «per ora» · *owner 16/11/2026 09:16, chat Formazione F001*

### 7.1 Il caricamento dei materiali
- **Una fonte sola**: i Markdown del repository A-010 (`corsi/<prodotto>/<percorso>/it/`) con l intestazione di `modelli/MODELLO-LEZIONE.md` (`id`, `vale_per`, `pubblico`, `lingua`, `durata_minuti`, `obiettivi`).
- Nella 0.1.01A i materiali **viaggiano col kit** (SPEC § 16): una cartella `materiali/` nel pacchetto, con la sua etichetta e il commit. Il pannello «Carica la versione dei materiali» arriva dopo (SPEC § 9.1).
- Il caricamento è **tutto o niente**: si legge tutto, si controlla (intestazioni complete, `id` unici, immagini presenti, domande con una sola risposta giusta), poi si scrive in una transazione. Un errore = nessun cambiamento e il motivo per riga.
- **I manuali dei prodotti** *(D28, D29)*: ogni file MATERIALE dell allegato A (manuali dei ruoli, manuale dell amministratore, manuale e guida delle API, specifiche d integrazione, novità) si porta nell Academy **senza cambiarne il senso**, con la sua versione e la versione del prodotto a cui vale; i PDF come allegati scaricabili, le pagine HTML e Markdown come materiali leggibili. In RelDoxFood restano come sono finché l owner non dà l ok.
- Le domande della verifica stanno in un file del percorso (`verifica.md` accanto alle lezioni), **non nella pagina** (SPEC § 5.4). *Oggi le due lezioni di prova hanno la verifica nel testo: si sposta nel file del percorso prima del primo caricamento.*

### 7.2 Il catalogo: chi vede che cosa
Regola, nell ordine, dentro la seduta:
1. solo i **prodotti acquistati** dall azienda della seduta (D12);
2. solo i percorsi del **ruolo** della persona (l amministratore vede anche i suoi);
3. per ogni lezione, l ultima versione con `vale_per` ≤ alla versione del prodotto della persona; se la sua versione è più vecchia di tutte, l ultima con l avviso «la tua versione è precedente» (SPEC § 4.4);
4. italiano; le altre lingue quando ci sono (SPEC § 7).
Il **prodotto di un corso è un campo**, non una regola fissa: la strada ai corsi fuori dalla famiglia resta aperta (D20).

### 7.3 La lezione e la verifica
Una lezione si apre → riga `aperta il` nel registro. Si chiude la verifica → esito e tentativi; la lezione è completata quando la verifica è fatta (anche se non tutta giusta: si può ripetere quante volte si vuole, SPEC § 5.4). «Riprendi da dove eri» = ultima lezione aperta e non completata.

### 7.4 Iris
Battute dal file di testo per lingua (`docs/IRIS-v1.md`), **nessuna IA** nella prima versione. «Chi sei?» propone il ruolo che porta l accesso; la scelta cambia solo la vista del catalogo, **mai i permessi** (un utente non diventa amministratore scegliendolo).

### 7.5 Il registro dell azienda
Solo l amministratore del cliente e solo la sua azienda; filtri per prodotto, corso, periodo; scarico CSV e PDF con la data di scarico e il periodo (SPEC § 6). Nessun attestato (D4).

---

---

## § 8 — Accessi
{proposta} tre porte; nella 0.1.01A solo la seconda

| porta | chi | verifica |
|---|---|---|
| 1 · accesso firmato dal prodotto | utente e amministratore del cliente | firma del prodotto, scadenza, monouso, chiave valida con la chiave pubblica, prodotto acquistato; freno ai tentativi |
| 2 · login dell applicazione | personale interno e persone di prova | il login di RelDoxFood 1.24.11L (§ 6) |
| 3 · wp-admin | amministratore del sito | WordPress indurito; da qui «Primo accesso» come in RelDoxFood |

⛔ **Ingresso della 0.1.01A: solo la porta 2** (proposta della Formazione, confermata dal capo famiglia, RelDox CRD S713; SPEC 1.11 § 16). La porta 1 arriva dopo, col parere di Sicurezza-Cyber prima del codice, la voce 159 di RelDox CRD e il pezzo comune «Accesso ai corsi» (roadmap F18). Chiave scaduta → pagina «I corsi sono in pausa»; ultimi 60 giorni → striscia nel catalogo.

---

## § 9 — L applicazione: la cornice
{proposta} la cornice è quella di RelDoxFood 1.24.11L (`public/views/layout.php`, 670 righe)

**Come funziona in RelDoxFood**: l instradatore (`Rdfood_Router`) riceve `/food/<pagina>` con quattro regole d indirizzo più un ripiego che legge l indirizzo anche senza regole salvate e un autoriparazione; sceglie la vista dalla mappa delle pagine, toglie dalla coda **tutti** gli stili e gli script del tema e monta la vista dentro la cornice unica. Pagina negata = 403 con «Accesso riservato» e il rail operativo. La cornice ha: barra in alto (marchio del prodotto, contesto, ingranaggio solo per l amministratore, utente col ruolo, Esci), **rail operativo** (ogni voce dichiara pagina e tipi di utente; si vede solo ciò che si può aprire), **rail Setup** (§ 10), logo dell azienda in testa al rail con segnaposto neutro, piede con prodotto e produttore, tre larghezze (al telefono menu ☰ e fogli dal basso), indipendenza dalla cache dell hosting (`carico-e-143`).

**Nell Academy** (rotte dal mockup 0.1.01A v1):

| rotta | schermata | livello | da |
|---|---|---|---|
| `/academy/login`, `/recupera`, `/imposta-password` | accesso | public | RelDoxFood, uguale |
| `/academy/` | il mio catalogo | auth | RIFAI del Pannello (`dashboard.php`) |
| `/academy/chi-sei` | «Chi sei?» con Iris | auth | nuova (mockup approvato) |
| `/academy/lezione/<n>` | la lezione | auth, lezione visibile (§ 7.2) | RIFAI di `manuali.php` |
| `/academy/registro` | registro dell azienda | Amministratore del cliente | nuova (mockup approvato) |
| `/academy/pausa` | chiave scaduta | public | nuova, porta 1 |
| `/academy/preferenze` | preferenze personali | auth | RelDoxFood, uguale |
| `/academy/impostazioni`, `/utenti`, `/tabelle`, `/log-accessi`, `/area-tecnica` | rail Setup | admin | RelDoxFood (§ 10) |

Rail operativo: **studente** Catalogo · Le mie lezioni; **amministratore del cliente** + Registro; **personale interno** + ingranaggio. Le voci della mensa (Ospiti, Registro dei pasti, Invii, Il mio codice, FUZZYCode, Menu del giorno, Comunica, Statistiche, Guida alla cassa, I miei pasti, Novità per l integrazione) non si copiano.

---

## § 10 — Impostazioni e rail Setup
{proposta} rail Setup di RelDoxFood 1.24.11L · *le linguette proprie hanno bisogno del loro mockup prima del codice (SPEC § 8)*

**Rail Setup in RelDoxFood** (`Rdfood_Router::SETUP_PAGES` e `layout.php`): «← Torna» (ritorna esattamente dov eri), poi il gruppo SISTEMA, solo amministratore: **Utenti · Tabelle · Log accessi · Impostazioni · Area tecnica**; le Preferenze si aprono dal proprio nome. La mensa vi aggiunge Registro dei pasti e Invii del registro: **non si copiano**.

**Impostazioni** (pannello a dieci riquadri, 1.24.7L):

| riquadro di RelDoxFood | nell Academy |
|---|---|
| Azienda | tieni |
| Festività | togli (serviva ai giorni della mensa) |
| Branding (due loghi) | tieni: logo Academy |
| Email (casella di servizio) | tieni |
| Dati FTP | togli |
| Integrazioni (API) | togli |
| Ristoratori | togli |
| Sistema | tieni |
| Database (sorgenti, capacità, backup) | tieni |
| Zona pericolosa | tieni |
| — | **Materiali** (versione caricata, data, commit; nella 0.1.01A solo lettura) · **nuovo, mockup prima** |
| — | **Chiavi pubbliche** di RelDox Chiavi · nuovo, con la porta 1 |

**Tabelle Funzionali** (`Rdfood_TF_Registry`, tre nature: normativa, sistema, azienda): resta «Testi delle notifiche» (sistema); «Invii del registro» si toglie; si aggiunge **Prodotti della famiglia** (nome, segno, versioni note; natura sistema).

**Area tecnica, Gestione utenti, Log accessi**: allo standard grafico di famiglia già in RelDoxFood (1.24.3L-1.24.11L); si copiano così, senza le linguette della scheda della mensa.

---

## § 11 — Il pannello WordPress
{proposta} come RelDoxFood 1.24.11L

- Menu «RelDoxAcademy» (`Rdfood_Admin`, `add_menu_page`) che porta all applicazione, con «Primo accesso», «Stato sistema» (`Rdfood_Stato_Sistema`) e «Documentazione» (`Rdfood_Docs`, solo le pagine sulla configurazione del database).
- Il plugin di diagnostica col suo menu: Manutenzione, Manutenzione storica, registro dei banchi, console SQL fra le sorgenti.
- Nella riga di **tutti e due** i plugin: «Stato e assistenza» e «Copia configurazione» (solo `manage_options`), mai chiavi, segreti, password, nomi o host dei database, prefisso, utenti, indirizzi IP (regola dell owner 16/11/2026 08:57, chat RelDox CRD S707).

---

## § 12 — Manutenzione e diagnostica
{proposta} i tre livelli della SPEC della manutenzione v2, da RelDoxFood 1.24.11L

- **Si tengono**, col segno nuovo: `class-rdfood-maintenance.php` (ruoli e rientro, il banco comune «Ruoli e recupero accesso»), `class-rdfood-banchi-registro.php`, `class-rdfood-console-sql.php`, `manifest-file.php`, il file principale.
- **Si tolgono**: Strumento 6 «Azzera il contratto di licenza» e Strumento 7 «Licenza obbligatoria per l API».
- **Controlli propri** dello Stato del sistema: materiali caricati e coerenti (lezioni contate, domande senza risposta giusta = rosso); chiavi pubbliche presenti (con la porta 1); accessi monouso scaduti ripuliti; registri delle aziende cessate oltre i 30 giorni = rosso; nessuna chiave di licenza in tabelle e registri; pacchetto della diagnostica uguale a quello del prodotto.

---

## § 13 — Opzioni nel database di WordPress
{proposta} solo le sei chiavi di avvio di RelDoxFood 1.24.11L, col segno nuovo

| opzione in RelDoxFood | nell Academy | a che cosa serve |
|---|---|---|
| `rdfood_db_version` | `rdacademy_db_version` | versione dello schema: se è più vecchia, a ogni avvio si riattiva |
| `rdfood_booted_version` | `rdacademy_booted_version` | primo avvio di una versione: rinnova le regole d indirizzo |
| `rdfood_flush_rewrite` | `rdacademy_flush_rewrite` | rinnovo delle regole d indirizzo in attesa |
| `rdfood_installed_at` | `rdacademy_installed_at` | data d installazione |
| `rdfood_just_activated` | `rdacademy_just_activated` | appena attivato |
| `rdfood_version` | `rdacademy_version` | versione del plugin |

Tutto il resto vive sul BASE (`options`, `impostazioni`). Alla rimozione restano, come in RelDoxFood.

---

## § 14 — Come si misura
{dafare} banchi nella forma comune (skill `banco-forma-comune-reldox`), rossi prima e verdi dopo

**Banchi standard**, da RelDoxFood 1.24.11L:

| banco | che cosa prova | in RelDoxFood |
|---|---|---|
| G0 sintassi e lingue | `php -l` su ogni file; stesse chiavi in ogni lingua del gestore | verifica prima di ogni consegna (CLAUDE.md di RelDoxFood) |
| G1 nessun resto | zero occorrenze di `rdfood`, `RDFOOD`, `Rdfood`, `RelDoxFood`, `/food/` e delle classi tolte; nessuna chiamata a una classe tolta fuori da `class_exists` | nuovo per la copia (regole v4 § 14) |
| G2 ruoli e recupero accesso | il banco comune della Manutenzione | `class-rdfood-maintenance.php` |
| G3 Gestione utenti allo standard | | `banco/S91-76-banco-gestione-utenti-standard.py` |
| G4 Impostazioni allo standard | | `banco/S91-84-banco-impostazioni-standard.py` |
| G5 Area tecnica allo standard | | `banco/S91-92-banco-area-tecnica-standard.py` |
| G6 Tabelle Funzionali allo standard | | `banco/S91-109-banco-tabelle-funzionali-standard.py` |
| G7 Log accessi allo standard | | `banco/S91-118-banco-log-accessi-standard.py` |
| G8 sabotaggi | si rompe di proposito una cosa per banco e il banco deve dire rosso | `banco/S91-93-sabotaggi-1_24_8L.py` |
| G9 installato uguale al pacchetto | i file sul server hanno lo stesso MD5 del pacchetto | `banco/S91-95-installato-uguale-pacchetto-1_24_8L.sh` |
| G10 diagnostica allineata | stesso numero e stesso pacchetto del prodotto | nuovo (§ 1, nota) |

I banchi G3-G7 di RelDoxFood girano a 2560, 1920, 1440, 820 e 390 px, senza scorrimento orizzontale e con la console pulita; nell Academy si copiano cambiando indirizzo e persone di prova. Il banco della scheda della persona e quelli della mensa non si copiano.

**Banchi del prodotto**:

| banco | che cosa prova | rosso se |
|---|---|---|
| B1 accesso (porta 1) | firma, scadenza, monouso, chiave valida e scaduta | entra un accesso falso, scaduto, già usato o con chiave scaduta |
| B2 catalogo | prodotti acquistati, ruolo, versione | si vede un prodotto non acquistato o un percorso di un altro ruolo |
| B3 verifica | esito calcolato sul server | la risposta giusta arriva al browser |
| B4 registro | sola propria azienda; CSV e PDF | un amministratore vede persone di un altra azienda |
| B5 segreti | nessuna chiave di licenza in tabelle e registri | il formato della chiave si trova nel database o nei log |
| B6 caricamento | tutto o niente | un materiale rotto lascia il catalogo a metà |

Palco e testing-live secondo `SPEC-AMBIENTE-DI-PROVA-TESTING-LIVE-v3`; due pacchetti con MD5 (regole v4 § 9.2).

---

## § 15 — Riferimenti di famiglia e ordine dei blocchi
{proposta} ordine da approvare con l analisi

Riferimenti: RelDoxFood `main` 1.24.11L (5e4a40c) · `docs/SPEC-RELDOXACADEMY-v1.md` 1.11 · mockup `MOCKUP-RELDOXACADEMY-0.1.01A-v1` e `MOCKUP-CHI-SEI-IRIS-v1` · `docs/IRIS-v1.md` · `REGOLE-FAMIGLIA-RELDOX-v4` · `SPEC-MANUTENZIONE-E-DIAGNOSTICA-v2` · `SPEC-INDURIMENTO-INSTALLAZIONI-FAMIGLIA-RELDOX-v1` · `SPEC-API-FAMIGLIA-RELDOX-v8`.

| blocco | che cosa | dipende da | «fatto» quando |
|---|---|---|---|
| K | RelDoxFood letto; edizione 3 approvata (D27), edizione 4 | — | questa edizione approvata |
| G | copia con nome e segno nuovi, ristorazione tolta (allegato A), lettura riga per riga di ogni file copiato → i due plugin installabili | K | G0-G2 e G10 verdi; G3-G7 verdi sul palco; una differenza trovata leggendo = edizione 4 prima di andare avanti |
| M | caricamento dei materiali, compresi i manuali di RelDoxFood (MATERIALE, allegato A) | G | B6 verde, le due lezioni di prova e i manuali di RelDoxFood caricati e leggibili |
| P | persone di prova (porta 2) | G | uno studente e un amministratore del cliente entrano |
| C · L | catalogo e lezione con verifica | M, P | B2 e B3 verdi, browser a tre misure |
| I | «Chi sei?» con Iris | C | battute da `iris.txt` |
| R | registro, CSV e PDF | L | B4 verde |
| D | riga dei plugin e controlli propri | G | «Copia configurazione» senza segreti |
| A | porta 1, accesso firmato (dopo la 0.1.01A) | parere di Sicurezza-Cyber, pezzo comune, voce 159 | B1 e B5 verdi |
| Z | pacchetto 0.1.01A, kit, smoke dell owner | K-D | «ok smoke» |

**Domande per l owner** con questa edizione (una alla volta, nell ordine):
1. ~~Approvi l edizione 3?~~ *«approvo»* (17/11/2026 14:51, D27).
2. ~~Le API esterne si tolgono?~~ Il motore resta solo in RelDoxFood; i manuali delle API entrano come materiali (15:08-15:10, D28, D29); edizione 4 approvata con il suo «ok» (15:10, D29).
3. ~~Indirizzo della mail di servizio dell Academy (§ 1).~~ *«academy@reldox.it»* (17/11/2026 15:58, chat Formazione F006, D31).

---

## Allegato A — L albero intero di RelDoxFood 1.24.11L, file per file

*Righe contate sul file; «—» per immagini e PDF. I 75 manuali HTML sono raggruppati per cartella.*

| file | righe | decisione | perché |
|---|---|---|---|
| `reldoxfood-diagnostica/includes/class-rdfood-azzera-licenza.php` | 175 | TOGLI | azzera il contratto di licenza |
| `reldoxfood-diagnostica/includes/class-rdfood-banchi-registro.php` | 158 | TIENI |  |
| `reldoxfood-diagnostica/includes/class-rdfood-console-sql.php` | 598 | TIENI |  |
| `reldoxfood-diagnostica/includes/class-rdfood-licenza-api.php` | 93 | TOGLI | licenza per l API |
| `reldoxfood-diagnostica/includes/class-rdfood-maintenance.php` | 890 | TIENI |  |
| `reldoxfood-diagnostica/includes/manifest-file.php` | 16 | TIENI |  |
| `reldoxfood-diagnostica/reldoxfood-diagnostica.php` | 75 | TIENI |  |
| `reldoxfood/admin/css/admin.css` | 111 | TIENI |  |
| `reldoxfood/assets/docs/docs.css` | 325 | TIENI |  |
| `reldoxfood/assets/docs/docs.js` | 130 | TIENI |  |
| `reldoxfood/assets/manuali/manuale.css` | 309 | RIFAI | foglio di stile della lezione |
| `reldoxfood/assets/search/rdfood-search.css` | 225 | TIENI | ricerca dell app |
| `reldoxfood/assets/search/rdfood-search.js` | 566 | TIENI | ricerca dell app |
| `reldoxfood/docs-content/10-configurazione-database.md` | 107 | TIENI, ridotto | Documentazione interna in wp-admin |
| `reldoxfood/docs-content/20-api-e-collegamenti.md` | 309 | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/docs-content/30-manuali-dei-ruoli.md` | 39 | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/docs-content/40-codice-personale.md` | 56 | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/docs-content/assets/flusso-credenziali-db.svg` | 93 | TIENI, ridotto | Documentazione interna in wp-admin |
| `reldoxfood/docs-content/novita/.htaccess` | — | TOGLI | pagina della ristorazione o delle API |
| `reldoxfood/docs-content/novita/1_13_0B.md` | 14 | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/docs-content/novita/1_17_0L.md` | 16 | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/docs-content/novita/1_18_0L.md` | 17 | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/docs-content/novita/index.php` | 2 | TOGLI | novità per l integratore delle API |
| `reldoxfood/includes/backup/class-rdfood-backup.php` | 747 | TIENI |  |
| `reldoxfood/includes/bootstrap/class-rdfood-bootstrap-wizard.php` | 628 | TIENI |  |
| `reldoxfood/includes/bootstrap/view-bootstrap-wizard.php` | 474 | TIENI |  |
| `reldoxfood/includes/carico-b.php` | 69 | TIENI, ridotto | tolti i cinque `require` della licenza da firmare (`license/`, TOLTI) e l avvio di `Rdfood_License_Admin` — ed. 7 |
| `reldoxfood/includes/carico-c.php` | 149 | TIENI |  |
| `reldoxfood/includes/carico-d.php` | 159 | TIENI |  |
| `reldoxfood/includes/carico-e-1100.php` | 488 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1120.php` | 16 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1130.php` | 83 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1160.php` | 18 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1190.php` | 122 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1200.php` | 109 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1210.php` | 21 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1220.php` | 35 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-1230.php` | 44 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-140.php` | 216 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-143.php` | 274 | TIENI | indipendenza dalla cache dell hosting (generico); passa nel carico dell Academy |
| `reldoxfood/includes/carico-e-144.php` | 48 | TIENI | pagina del registro delle spedizioni |
| `reldoxfood/includes/carico-e-150.php` | 99 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-160.php` | 81 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-183.php` | 31 | TOGLI | ristorazione |
| `reldoxfood/includes/carico-e-190.php` | 232 | RIFAI | i manuali dei ruoli: la voce del rail diventa il catalogo dei corsi |
| `reldoxfood/includes/carico-e.php` | 361 | RIFAI | resta il punto d innesto del carico; dentro solo le voci dell Academy |
| `reldoxfood/includes/class-rdfood-accesso-emergenza.php` | 388 | TIENI |  |
| `reldoxfood/includes/class-rdfood-activator.php` | 365 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/includes/class-rdfood-admin.php` | 212 | TIENI |  |
| `reldoxfood/includes/class-rdfood-auth.php` | 732 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/includes/class-rdfood-db-config.php` | 166 | TIENI |  |
| `reldoxfood/includes/class-rdfood-db.php` | 676 | TIENI |  |
| `reldoxfood/includes/class-rdfood-license-anchor.php` | 178 | TIENI | ancora di licenza: solo il dominio |
| `reldoxfood/includes/class-rdfood-lingua.php` | 1572 | TIENI, ridotto | gestore delle lingue; si tolgono le chiavi della ristorazione |
| `reldoxfood/includes/class-rdfood-option.php` | 567 | TIENI |  |
| `reldoxfood/includes/class-rdfood-provisioning-guard.php` | 250 | TIENI |  |
| `reldoxfood/includes/class-rdfood-roles.php` | 69 | TIENI |  |
| `reldoxfood/includes/class-rdfood-router.php` | 820 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/includes/class-rdfood-schema-flag-guard.php` | 222 | TIENI |  |
| `reldoxfood/includes/class-rdfood-schema-license-v1.php` | 68 | TIENI | ancora di licenza: solo il dominio |
| `reldoxfood/includes/class-rdfood-stato-sistema.php` | 441 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/includes/class-rdfood-user-prefs.php` | 161 | TIENI |  |
| `reldoxfood/includes/docs/class-rdfood-docs-loader.php` | 84 | TIENI |  |
| `reldoxfood/includes/docs/class-rdfood-docs.php` | 93 | TIENI |  |
| `reldoxfood/includes/docs/class-rdfood-markdown.php` | 281 | TIENI |  |
| `reldoxfood/includes/docs/views/docs-page.php` | 99 | TIENI |  |
| `reldoxfood/includes/ext/class-rdfood-ext-admin.php` | 209 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-esporta.php` | 263 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-flusso.php` | 336 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-fonte.php` | 77 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-oggetti.php` | 292 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-profilo.php` | 265 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-ristoratori.php` | 432 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-schema.php` | 124 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-scrivi.php` | 373 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext-sentinella.php` | 229 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/ext/class-rdfood-ext.php` | 440 | TOGLI | API esterne della ristorazione: l Academy nella 0.1.01A non espone API (§ 2) |
| `reldoxfood/includes/helpers.php` | 245 | TIENI |  |
| `reldoxfood/includes/license/class-rdfood-license-admin.php` | 928 | TOGLI | contratto di licenza da firmare e sua pagina (SPEC § 2.5) |
| `reldoxfood/includes/license/class-rdfood-license-firma.php` | 169 | TOGLI | contratto di licenza da firmare e sua pagina (SPEC § 2.5) |
| `reldoxfood/includes/license/class-rdfood-license-pdf.php` | 401 | TOGLI | contratto di licenza da firmare e sua pagina (SPEC § 2.5) |
| `reldoxfood/includes/license/class-rdfood-license-validate.php` | 183 | TOGLI | contratto di licenza da firmare e sua pagina (SPEC § 2.5) |
| `reldoxfood/includes/license/class-rdfood-license.php` | 814 | TOGLI | contratto di licenza da firmare e sua pagina (SPEC § 2.5) |
| `reldoxfood/includes/lotto-c/class-rdfood-area-tecnica.php` | 468 | TIENI |  |
| `reldoxfood/includes/lotto-c/class-rdfood-impostazioni.php` | 572 | TIENI, ridotto | tolti i rami di `Rdfood_Servizio` (Santo Patrono, festività) e di `Rdfood_Codice` (recapiti) e la porta propria `datiftp_*` della cartella dati — ed. 9 |
| `reldoxfood/includes/lotto-c/class-rdfood-me.php` | 175 | TIENI |  |
| `reldoxfood/includes/lotto-c/class-rdfood-schema-c.php` | 91 | TIENI |  |
| `reldoxfood/includes/lotto-c/class-rdfood-tf-admin-rest.php` | 101 | TIENI |  |
| `reldoxfood/includes/lotto-c/class-rdfood-tf-registry.php` | 390 | TIENI |  |
| `reldoxfood/includes/lotto-c/class-rdfood-utenti.php` | 601 | TIENI, ridotto | tolti il controllo dello username sulla tabella `persone` della mensa (senza condizione: errore DB nell Academy), il ramo dell email condivisa e quello del nome utente della scheda — ed. 8 |
| `reldoxfood/includes/lotto-d/class-rdfood-database.php` | 523 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-datasource.php` | 404 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-dimensioni.php` | 64 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-schema-datasources-v1.php` | 66 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-schema-sorgente-esempio-v1.php` | 41 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-sorgente-provisioner.php` | 208 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-sorgenti.php` | 144 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-tabella-senza-casa.php` | 46 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-table-map.php` | 53 | TIENI |  |
| `reldoxfood/includes/lotto-d/class-rdfood-table-owner.php` | 266 | TIENI |  |
| `reldoxfood/includes/lotto-e/class-rdfood-accesso-stampato.php` | 1112 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-app-install.php` | 264 | TIENI | icona sul telefono (SPEC-ICONA-WEBAPP di famiglia) |
| `reldoxfood/includes/lotto-e/class-rdfood-cartella-dati.php` | 972 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-cassa.php` | 868 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-codice.php` | 413 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-conti.php` | 530 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-email-condivisa.php` | 119 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-export.php` | 489 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-fuzzy.php` | 1636 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-invii.php` | 877 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-inviti.php` | 111 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-menu-giorno.php` | 344 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-miei-dati.php` | 227 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-note.php` | 414 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-obbligatori.php` | 202 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-pass.php` | 257 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-pasti-avanzata.php` | 445 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-persone.php` | 1324 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-profili.php` | 113 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-quadro-pasti.php` | 121 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-report-ristoratore.php` | 427 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-riservati.php` | 121 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-schema-e.php` | 467 | RIFAI | resta la grammatica dello schema (versione, flag, solo CREATE IF NOT EXISTS e ALTER ADD); le tabelle diventano quelle del § 5 |
| `reldoxfood/includes/lotto-e/class-rdfood-servizio.php` | 532 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-spedizioni.php` | 467 | TIENI | registro delle mail spedite dalla casella di servizio |
| `reldoxfood/includes/lotto-e/class-rdfood-statistiche.php` | 616 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-storno.php` | 183 | TOGLI | ristorazione |
| `reldoxfood/includes/lotto-e/class-rdfood-testi-notifiche.php` | 510 | TIENI, ridotto | Tabella Funzionale dei testi delle mail: restano invito, recupero, avvisi dell account |
| `reldoxfood/includes/manifest-file.php` | 313 | TIENI |  |
| `reldoxfood/includes/novita/class-rdfood-giro.php` | 40 | TOGLI | novità per l integratore delle API |
| `reldoxfood/includes/novita/class-rdfood-novita.php` | 629 | TOGLI | novità per l integratore delle API |
| `reldoxfood/includes/security/class-rdfood-casella.php` | 706 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/includes/security/class-rdfood-cifra.php` | 102 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-imap-diretta.php` | 296 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-password-mail.php` | 729 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/includes/security/class-rdfood-password-policy.php` | 279 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-password-token.php` | 329 | TIENI, ridotto | tolto lo scopo `f` del foglio di accesso stampato (classe TOLTA, oggi dietro class_exists) — ed. 6 |
| `reldoxfood/includes/security/class-rdfood-rest-auth.php` | 74 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-schema-uploads-blob-v1.php` | 145 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-schema-uploads-v1.php` | 59 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-security.php` | 194 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-uploads-entity.php` | 541 | TIENI |  |
| `reldoxfood/includes/security/class-rdfood-uploads-rest.php` | 234 | TIENI |  |
| `reldoxfood/includes/uploads-js/assets/rdfood-uploads.css` | 904 | TIENI |  |
| `reldoxfood/includes/uploads-js/assets/rdfood-uploads.js` | 905 | TIENI |  |
| `reldoxfood/includes/uploads-js/class-rdfood-uploads-js.php` | 99 | TIENI |  |
| `reldoxfood/includes/vendor/class-rdfood-qr.php` | 270 | TOGLI | lettore e generatore di QR: servono alla cassa e al codice personale |
| `reldoxfood/manuali/.htaccess` | — | RIFAI | indice e protezione della cartella: diventano quelli dei materiali caricati (§ 7.1) |
| `reldoxfood/manuali/Guida-sviluppatore-gestionale-RelDoxFood-1_13_0B.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Manuale-RelDoxFood-Amministratore-1_24_11L.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Manuale-RelDoxFood-Chi-mangia-1_24_11L-DE.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Manuale-RelDoxFood-Chi-mangia-1_24_11L-EN.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Manuale-RelDoxFood-Chi-mangia-1_24_11L.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Manuale-RelDoxFood-Ristoratore-1_24_11L-DE.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Manuale-RelDoxFood-Ristoratore-1_24_11L-EN.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Manuale-RelDoxFood-Ristoratore-1_24_11L.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Specifica-integrazione-RelDoxFood-ed10.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Specifica-integrazione-RelDoxFood-ed8.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/Specifica-integrazione-RelDoxFood-ed9.pdf` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/amministratore/ (15 pagine)` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/chi-mangia-de/ (9 pagine)` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/chi-mangia-en/ (9 pagine)` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/chi-mangia/ (9 pagine)` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/index.php` | 2 | RIFAI | indice e protezione della cartella: diventano quelli dei materiali caricati (§ 7.1) |
| `reldoxfood/manuali/indice.php` | 104 | RIFAI | indice e protezione della cartella: diventano quelli dei materiali caricati (§ 7.1) |
| `reldoxfood/manuali/ristoratore-de/ (11 pagine)` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/ristoratore-en/ (11 pagine)` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/manuali/ristoratore/ (11 pagine)` | — | MATERIALE | manualistica: si carica nell Academy come materiale (§ 7.1); resta anche in RelDoxFood (D28) |
| `reldoxfood/public/css/app.css` | 7451 | TIENI |  |
| `reldoxfood/public/css/rdfood-1100.css` | 18 | TOGLI | ristorazione |
| `reldoxfood/public/css/rdfood-1190.css` | 81 | TOGLI | ristorazione |
| `reldoxfood/public/css/rdfood-1200.css` | 57 | TOGLI | ristorazione |
| `reldoxfood/public/css/rdfood-1210.css` | 45 | TOGLI | ristorazione |
| `reldoxfood/public/css/rdfood-140.css` | 118 | TOGLI | ristorazione |
| `reldoxfood/public/css/rdfood-144.css` | 49 | TIENI |  |
| `reldoxfood/public/css/rdfood-150.css` | 389 | TOGLI | ristorazione |
| `reldoxfood/public/css/rdfood-leggibilita.css` | 451 | TIENI |  |
| `reldoxfood/public/css/rdfood-lotto-c.css` | 1298 | TIENI |  |
| `reldoxfood/public/css/rdfood-lotto-d.css` | 122 | TIENI |  |
| `reldoxfood/public/img/icona-qr.png` | — | TOGLI | codice personale |
| `reldoxfood/public/img/logo-azienda-segnaposto.svg` | 7 | TIENI |  |
| `reldoxfood/public/img/logo/README-LOGO.md` | 31 | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/img/logo/idea-technologies.png` | — | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/img/logo/mail/reldoxfood-lockup.png` | — | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/img/logo/reldox-favicon.svg` | 21 | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/img/logo/reldox-symbol-color.svg` | 24 | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/img/logo/reldox-symbol-mono.svg` | 18 | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/img/logo/reldox-symbol.png` | — | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/img/logo/reldoxfood-lockup.png` | — | RIFAI | marchio Academy da risorse/logo-academy/ |
| `reldoxfood/public/js/app.js` | 169 | TIENI |  |
| `reldoxfood/public/js/rdfood-cassa.js` | 260 | TOGLI | ristorazione |
| `reldoxfood/public/js/rdfood-fuzzy.js` | 755 | TOGLI | ristorazione |
| `reldoxfood/public/js/rdfood-galleria.js` | 158 | TOGLI | ristorazione |
| `reldoxfood/public/js/rdfood-guida.js` | 108 | TOGLI | guida alla cassa |
| `reldoxfood/public/js/rdfood-icona.js` | 68 | TIENI |  |
| `reldoxfood/public/js/rdfood-integrazioni.js` | 181 | TOGLI | Impostazioni › Integrazioni (API) |
| `reldoxfood/public/js/rdfood-invio-codice.js` | 114 | TOGLI | ristorazione |
| `reldoxfood/public/js/rdfood-menu-giorno.js` | 162 | TOGLI | ristorazione |
| `reldoxfood/public/js/rdfood-note.js` | 114 | TOGLI | ristorazione |
| `reldoxfood/public/js/rdfood-novita.js` | 129 | TOGLI | novità per l integratore |
| `reldoxfood/public/js/rdfood-pagine.js` | 135 | TIENI |  |
| `reldoxfood/public/js/rdfood-pwd-meter.js` | 188 | TIENI |  |
| `reldoxfood/public/js/rdfood-tf.js` | 401 | TIENI |  |
| `reldoxfood/public/js/vendor/jsQR-1.4.0-LICENSE.txt` | 202 | TOGLI | lettore e generatore di QR: servono alla cassa e al codice personale |
| `reldoxfood/public/js/vendor/jsQR-1.4.0.js` | 10101 | TOGLI | lettore e generatore di QR: servono alla cassa e al codice personale |
| `reldoxfood/public/js/vendor/qrcode-generator-1.4.4.js` | 2297 | TOGLI | lettore e generatore di QR: servono alla cassa e al codice personale |
| `reldoxfood/public/views/accesso-riservato.php` | 17 | TIENI |  |
| `reldoxfood/public/views/area-tecnica.php` | 390 | TIENI |  |
| `reldoxfood/public/views/attiva.php` | 70 | TOGLI | ristorazione |
| `reldoxfood/public/views/codice.php` | 48 | TOGLI | ristorazione |
| `reldoxfood/public/views/coming-soon.php` | 26 | TIENI |  |
| `reldoxfood/public/views/comunica.php` | 59 | TOGLI | ristorazione |
| `reldoxfood/public/views/dashboard.php` | 279 | RIFAI | il Pannello diventa «Il mio catalogo» (S2 del mockup) |
| `reldoxfood/public/views/fuzzycode.php` | 54 | TOGLI | ristorazione |
| `reldoxfood/public/views/gestione-utenti.php` | 1445 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/public/views/guida-cassa.php` | 112 | TOGLI | ristorazione |
| `reldoxfood/public/views/hanno-mangiato.php` | 44 | TOGLI | ristorazione |
| `reldoxfood/public/views/imposta-password.php` | 288 | TIENI |  |
| `reldoxfood/public/views/impostazioni-database.php` | 517 | TIENI |  |
| `reldoxfood/public/views/impostazioni-integrazioni.php` | 209 | TOGLI | ristorazione |
| `reldoxfood/public/views/impostazioni-ristoratori.php` | 123 | TOGLI | ristorazione |
| `reldoxfood/public/views/impostazioni-standard-1247.php` | 369 | TIENI |  |
| `reldoxfood/public/views/impostazioni.php` | 1181 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/public/views/invii-registro.php` | 581 | TOGLI | ristorazione |
| `reldoxfood/public/views/layout.php` | 670 | TIENI, ridotto | tolti i rami della ristorazione (oggi dietro class_exists) |
| `reldoxfood/public/views/log-accessi.php` | 382 | TIENI |  |
| `reldoxfood/public/views/login.php` | 102 | TIENI |  |
| `reldoxfood/public/views/manuali.php` | 109 | RIFAI | diventa la lezione (S3 del mockup) |
| `reldoxfood/public/views/menu-del-giorno.php` | 139 | TOGLI | ristorazione |
| `reldoxfood/public/views/miei-pasti.php` | 86 | TOGLI | ristorazione |
| `reldoxfood/public/views/novita.php` | 81 | TOGLI | novità per l integratore |
| `reldoxfood/public/views/preferenze.php` | 379 | TIENI, ridotto | tolto il ramo «I miei dati» del ristoratore (oggi dietro class_exists) — ed. 5 |
| `reldoxfood/public/views/recupera.php` | 120 | TIENI |  |
| `reldoxfood/public/views/registro-pasti.php` | 230 | TOGLI | ristorazione |
| `reldoxfood/public/views/scheda-persona-valori.php` | 335 | TOGLI | ristorazione |
| `reldoxfood/public/views/scheda-persona.php` | 1421 | TOGLI | ristorazione |
| `reldoxfood/public/views/scheda-ristoratore-1241.php` | 268 | TOGLI | ristorazione |
| `reldoxfood/public/views/scheda-utente-1243.php` | 363 | TIENI |  |
| `reldoxfood/public/views/spedizioni.php` | 95 | TOGLI | ristorazione |
| `reldoxfood/public/views/statistiche.php` | 328 | TOGLI | ristorazione |
| `reldoxfood/public/views/tabelle-funzionali.php` | 327 | TIENI |  |
| `reldoxfood/public/views/tf-barra.php` | 44 | TIENI |  |
| `reldoxfood/reldoxfood.php` | 525 | RIFAI | reldox-academy.php: intestazione, costanti RDACADEMY_*, elenco dei carichi; la storia delle versioni non si copia |

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
