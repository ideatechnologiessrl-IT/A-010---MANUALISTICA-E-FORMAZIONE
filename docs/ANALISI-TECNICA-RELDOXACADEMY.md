<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# ANALISI TECNICA DEL PRODOTTO — RELDOXACADEMY

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale.

**Edizione 2 · F003 · 17/11/2026 · nessun pacchetto (base: RelDoxFood 1.24.11L)**

| edizione | sessione · data | che cosa cambia |
|---|---|---|
| 2 | Formazione F003 · 17/11/2026 | base **RelDoxFood 1.24.11L**, non il guscio; segno, indirizzo, diagnostica e ingresso della 0.1.01A confermati dal capo famiglia (`bacheca/reldox-crd/messaggi/2026-11-17-a-formazione-academy-base-di-partenza.md`, RelDox CRD S713) |
| 1 | Formazione F003 · 17/11/2026 | prima stesura, sulla SPEC 1.10 e sul mockup 0.1.01A v1, senza il kit di partenza: tutto ciò che arriva dal guscio è PROPOSTA finché il kit non c è |

> ⛔ **È il documento della struttura del prodotto** (`REGOLE-FAMIGLIA-RELDOX-v4` § 7.0): non si scrive codice su una struttura che non è qui.
> ⛔ **Due forme, un testo**: questo `.md` è il canonico; l `.html` si genera con `strumenti/genera-analisi-tecnica.py`. *Il generatore si cerca in RelDoxFood, che è la base (§ 2): finché non c è, questa edizione esiste solo in `.md`; l `.html` nasce prima di ogni codice.*
> ⛔ **I contratti si richiamano, non si ricopiano**: la SPEC di RelDoxAcademy (`docs/SPEC-RELDOXACADEMY-v1.md`, 1.10), le regole di famiglia v4, la SPEC della manutenzione v2, la SPEC dell indurimento v1, la SPEC delle API v8.

**Decisione dell owner che apre questo lavoro** — D26 (17/11/2026 12:10, chat Formazione F002): *«ferma la lezione e crea il plugin secondo lo starda di quelli esistenti, chiedi al capo famiglia in caso»*.

---

## § 0 — Stato dello sviluppo
**Stato: PROPOSTA** — edizione 2, nessun codice · *il codice parte solo dopo la lettura intera di RelDoxFood 1.24.11L (edizione 3 di questa analisi), l approvazione e il «genera» dell owner*

Legenda: **FATTO** verificato sul server dall owner · **CONSEGNATO** provato qui (banco e browser) · **PROPOSTA** aspetta una decisione o un mockup approvato · **DA FARE** non ancora sviluppato.

| voce | che cosa | stato | dove si legge |
|---|---|---|---|
| A0 | Analisi tecnica, edizione 1 | PROPOSTA | questo documento |
| A1 | Copia di RelDoxFood 1.24.11L con nome e segno nuovi: `reldox-academy/` e `reldox-academy-diagnostica/` | DA FARE | § 2, § 4 |
| A2 | Caricamento dei materiali dal repository (Markdown → tabelle) | DA FARE | § 7.1 |
| A3 | Ingresso 0.1.01A: login interno e persone di prova | DA FARE | § 8 |
| A3 bis | Ingresso con accesso firmato e controllo della chiave (S1), versione successiva | PROPOSTA | § 8 |
| A4 | «Chi sei?» con Iris (S1 bis) | DA FARE | § 7.4, § 9 |
| A5 | Il mio catalogo (S2) | DA FARE | § 7.2 |
| A6 | La lezione con la verifica (S3) | DA FARE | § 7.3 |
| A7 | Registro dell azienda, CSV e PDF (S6) | DA FARE | § 7.5 |
| A8 | «Stato e assistenza» e «Copia configurazione» nella riga dei due plugin | DA FARE | § 11 |
| A9 | Banchi del prodotto | DA FARE | § 14 |

---

## § 1 — Il prodotto e l installazione sono due cose
**Stato: PROPOSTA** — segno, indirizzo e diagnostica confermati (D14; regole v4, riga di RelDoxAcademy, RelDox CRD S708; RelDox CRD S713); gli altri nomi seguono la forma di RelDoxFood · *da riscontrare alla lettura del codice*

- **Il prodotto** è RelDoxAcademy: un plugin della famiglia, rivendibile come gli altri. **L installazione** oggi è una sola, su `reldoxacademy.it`, tenuta da Idea (D9; SPEC § 2.1). Niente nel codice dipende dal dominio: il dominio è un dato dell installazione.
- **Il marchio** (logo «Academy», indaco `#3E3F9B`, frase «il sapere, la tua crescita») è un dato, da `risorse/logo-academy/`, non scritto nel codice delle viste.

**Scheda di prodotto** (i nomi del § 3.6 delle regole):

| nome | valore | stato |
|---|---|---|
| nome del prodotto | RelDoxAcademy | FATTO (D1) |
| segno | `rdacademy` | approvato dall owner (D14, 15/11/2026 19:21); registrato nelle regole v4 |
| classi · funzioni · costanti | `Rdacademy_` · `rdacademy_` · `RDACADEMY_` | PROPOSTA |
| tabelle | `<prefisso>rdacademy_<nome>` nel database proprio | PROPOSTA |
| cookie della seduta | `rdacademy_seduta` | PROPOSTA |
| spazio REST | `rdacademy/v1` | PROPOSTA |
| opzioni WordPress | `rdacademy_<nome>` (solo avvio, § 13) | PROPOSTA |
| cartella del plugin | `reldox-academy/` | approvata (SPEC § 2.2) |
| plugin di diagnostica | `reldox-academy-diagnostica/` | confermato (RelDox CRD S713) |
| indirizzo dell applicazione | `/academy/` | registrato nelle regole v4 · *la radice del sito resta alla pagina di attesa del Marketing finché l Academy non è in produzione (roadmap F22)* |
| versione · stadio | `RDACADEMY_VERSION` = `0.1.01` · `RDACADEMY_STAGE` = `Alfa` · etichetta `0.1.01A` | PROPOSTA |

---

## § 2 — Architettura: un prodotto, il suo BASE
**Stato: PROPOSTA** — base decisa: RelDoxFood 1.24.11L · *capo famiglia, RelDox CRD S713*

- **Una installazione, un database proprio** (SPEC § 2.3; § 12.3): credenziali solo in `wp-config.php`, utente del database con permessi minimi. Sul WordPress dell Academy non ci sono altri prodotti RelDox (SPEC § 2.1): la convivenza del § 3.6 delle regole resta comunque garantita dai nomi del § 1.
- **I database** (1 · 2 · 3-5 delle regole) si richiamano da RelDoxFood 1.24.11L: si scrivono nell edizione 3, dopo la lettura del codice.
- **Il plugin di diagnostica** è separato, come da SPEC della manutenzione v2 (§ 12).
- ⛔ **Da quale base si parte: RelDoxFood** (`ideatechnologiessrl-IT/RelDoxFood`, `main` = 1.24.11L Live), come dice la D8 (owner 15/11/2026 16:57, chat Formazione F001: *«per il plugin direi di iniziare a usare RelDoxFood come punto di partenza»*). Lo ha confermato il capo famiglia (RelDox CRD S713, 17/11/2026). La scelta tecnica è sua: owner 17/11/2026 11:48, chat RelDox CRD S713, *«la decidione tecnica è del programmatore»*. Il guscio «RelDox Base» 0.1.03A è fermo a RelDox CRD 3.56.02 e non si usa.
- **Come**: si copia da `main` di RelDoxFood, **letto per intero** (§ 2 delle regole). Si cambiano nome e segno e si toglie tutto ciò che è della ristorazione (menu, cassa, codice personale, prenotazioni, contratto di licenza da firmare: SPEC § 2.5). Si tengono l ossatura (accessi, utenti, impostazioni, diagnostica separata, pacchetti, banchi standard), il **motore dei manuali in linea** e il **gestore delle lingue**.
---

## § 3 — Ciclo di vita
**Stato: PROPOSTA** — si richiama da RelDoxFood 1.24.11L · *da confermare alla lettura del codice*

| momento | che cosa fa | da dove |
|---|---|---|
| installazione guidata | come RelDoxFood: database proprio, prima configurazione | RelDoxFood |
| schema al boot | come RelDoxFood: crea e allinea le tabelle del § 5 con la loro versione | RelDoxFood + tabelle proprie |
| primo accesso | il primo amministratore interno; poi «Chiavi pubbliche» (§ 10) e primo caricamento dei materiali (§ 7.1) | RelDoxFood + proprio |
| aggiornamento | per rimozione e reinstallazione (§ 9.2 delle regole): i dati restano nel database proprio | RelDoxFood |
| disattivazione | nessun dato toccato | RelDoxFood |
| rimozione | come RelDoxFood; il registro di completamento segue la conservazione della SPEC § 11.4 (30 giorni dopo la cessazione, poi si cancella), non la rimozione del plugin | RelDoxFood + proprio |

---

## § 4 — L albero dei file
**Stato: PROPOSTA** — l ossatura arriva dalla copia di RelDoxFood; qui solo i file propri · *l albero intero si scrive nell edizione 3, sul codice di RelDoxFood letto*

```
reldox-academy/                      ← nel repository A-010 (D6)
  reldox-academy.php                 intestazione Version:, RDACADEMY_VERSION, RDACADEMY_STAGE, RDACADEMY_PACKAGE
  … ossatura di RelDoxFood (caricamento, database, accessi, impostazioni, viste comuni)
  includes/
    class-rdacademy-materiali.php    caricamento dei materiali, § 7.1
    class-rdacademy-catalogo.php     chi vede che cosa, § 7.2
    class-rdacademy-lezione.php      lezione e verifica, § 7.3
    class-rdacademy-iris.php         battute di Iris dal file di testo, § 7.4
    class-rdacademy-registro.php     registro, CSV e PDF, § 7.5
    class-rdacademy-accesso.php      accesso firmato, chiave, seduta, § 8
    … motore dei manuali in linea e gestore delle lingue di RelDoxFood, con nomi propri
  views/
    ingresso.php · pausa.php · chi-sei.php · catalogo.php · lezione.php · registro.php
  lang/it/iris.txt                   catalogo delle battute (docs/IRIS-v1.md)
  assets/                            CSS e JS dell app, logo Academy, pose di Iris
  banchi/                            banchi del prodotto, § 14
reldox-academy-diagnostica/
  … da RelDoxFood (stato, controlli, «Copia configurazione», indurimento, registro dei banchi)
```

---

## § 5 — Lo schema del BASE
**Stato: PROPOSTA** — tabelle ereditate da RelDoxFood + tabelle proprie · *nomi senza prefisso*

Ereditate da RelDoxFood (si richiamano, edizione 3): utenti propri, ruoli e permessi, impostazioni, registro tecnico. Proprie del prodotto:

| tabella | che cosa contiene | voce |
|---|---|---|
| `rdacademy_carichi` | ogni caricamento dei materiali: etichetta, commit del repository A-010, data, chi | A2 |
| `rdacademy_percorsi` | prodotto, ruolo (pubblico), titolo, ordine, lingua | A2 |
| `rdacademy_lezioni` | `id` della lezione (`<prodotto>-<percorso>-L<nn>`), percorso, titolo, `vale_per` (prodotto e versione minima), durata, HTML già pronto, lingua, versione del materiale | A2 |
| `rdacademy_domande` | le domande della verifica e la risposta giusta: **mai mandate al browser** | A2, A6 |
| `rdacademy_aziende` | identificativo dell azienda (dalla chiave, voce 159 di RelDox CRD); fino ad allora la coppia prodotto + dominio | A3 |
| `rdacademy_persone` | azienda, identificativo della persona nel prodotto, nome visualizzato, ruolo, lingua — nessuna mail, nessuna password (SPEC § 11.1) | A3 |
| `rdacademy_registro` | persona, lezione, aperta il, completata il, esito (giuste su totali), tentativi | A6, A7 |
| `rdacademy_accessi_usati` | impronta dell accesso firmato già usato e sua scadenza (monouso, § 8); si svuota da solo | A3 |
| `rdacademy_registro_accessi` | ora, esito, prodotto, dominio, identificativo della persona — mai la chiave (SPEC § 12.4) | A3 |

Scelta tecnica dichiarata: il Markdown si converte in HTML **al caricamento** (A2), non a ogni pagina: meno lavoro sul server, una sola conversione da provare col banco.

---

## § 6 — Sicurezza, segreti e chiavi
**Stato: PROPOSTA** — aspetta il parere di Sicurezza-Cyber sulla SPEC · *risposta del 16/11/2026: il parere arriva nella loro prossima sessione con l owner; il codice della prima versione lo aspetta*

| che cosa | come si tiene |
|---|---|
| chiave di licenza del prodotto | **non si salva** in nessuna tabella né registro (SPEC § 12.4): si verifica con la chiave pubblica di RelDox Chiavi e se ne tengono al massimo prodotto, dominio, ultimo giorno |
| chiavi pubbliche di RelDox Chiavi | impostazione «Chiavi pubbliche» (§ 10), solo personale interno |
| accesso firmato | a tempo breve (pochi minuti) e **monouso**: se ne salva solo l impronta fino alla scadenza (§ 5) |
| seduta | cookie `rdacademy_seduta`, `Secure`, `HttpOnly`, `SameSite=Lax`, durata breve; a ogni ingresso dal pulsante «Corsi» la chiave si ricontrolla (SPEC § 3.8) |
| credenziali del database | solo `wp-config.php` |
| risposte giuste della verifica | solo sul server; il browser manda le scelte, il server dice l esito |
| personale interno | utenti dell applicazione (da RelDoxFood) con doppio fattore (SPEC § 12.2) |

---

## § 7 — Materiali e percorsi (il cuore del prodotto)
**Stato: PROPOSTA** — dalla SPEC § 4-§ 7 e dal mockup 0.1.01A v1 approvato «per ora» · *owner 16/11/2026 09:16, chat Formazione F001*

### 7.1 Il caricamento dei materiali
- **Una fonte sola**: i Markdown del repository A-010 (`corsi/<prodotto>/<percorso>/it/`) con l intestazione di `modelli/MODELLO-LEZIONE.md` (`id`, `vale_per`, `pubblico`, `lingua`, `durata_minuti`, `obiettivi`).
- Nella 0.1.01A i materiali **viaggiano col kit** (SPEC § 16): una cartella `materiali/` nel pacchetto, con la sua etichetta e il commit. Il pannello «Carica la versione dei materiali» arriva dopo (SPEC § 9.1).
- Il caricamento è **tutto o niente**: si legge tutto, si controlla (intestazioni complete, `id` unici, immagini presenti, domande con una sola risposta giusta), poi si scrive in una transazione. Un errore = nessun cambiamento e il motivo per riga.
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

## § 8 — Accessi
**Stato: PROPOSTA** — tre porte; nella 0.1.01A solo la seconda; la prima aspetta il parere di Sicurezza-Cyber e la forma del pezzo comune «Accesso ai corsi» · *voce F18, voce 159 di RelDox CRD*

| porta | chi | verifica |
|---|---|---|
| 1 · accesso firmato dal prodotto | utente e amministratore del cliente | firma del prodotto, scadenza, monouso, chiave di licenza valida con la chiave pubblica, prodotto acquistato; freno ai tentativi |
| 2 · login dell applicazione | personale interno | utenti dell applicazione (da RelDoxFood), doppio fattore |
| 3 · wp-admin | amministratore del sito | WordPress indurito (SPEC § 12) |

Chiave scaduta → pagina «I corsi sono in pausa» (mockup S1), nessun contenuto. Ultimi 60 giorni → striscia nel catalogo (SPEC § 3.5).

⛔ **Ingresso della 0.1.01A: solo la porta 2**, con persone di prova create dal personale interno, per provare catalogo, lezione e registro. La porta 1 (accesso firmato con le chiavi dei prodotti, voce 159 di RelDox CRD) arriva in una versione dopo, con il parere di Sicurezza-Cyber prima del codice. Proposta della Formazione, confermata dal capo famiglia (RelDox CRD S713, 17/11/2026); SPEC 1.11 § 16.
**Freno ai tentativi**: quello di RelDoxFood, già allineato al parere di Sicurezza-Cyber: solo ritardo, nessun blocco (owner 17/11/2026 11:50, chat Sicurezza-Cyber S3).

Manca nella SPEC § 3.4: l **identificativo della persona nel prodotto** (un numero, non un dato personale), senza il quale il registro non riconosce la stessa persona tra due ingressi. Lo porto nella proposta del pezzo comune.

---

## § 9 — L applicazione: cornice, rail, navigazione
**Stato: PROPOSTA** — cornice di RelDoxFood; rotte dal mockup · *indirizzo base da confermare (§ 1)*

| rotta | schermata | permesso |
|---|---|---|
| `/academy/entra` | S1 · ingresso (riceve l accesso firmato) | pubblica |
| `/academy/pausa` | S1 · chiave scaduta | pubblica |
| `/academy/chi-sei` | S1 bis · «Chi sei?» con Iris | seduta |
| `/academy/` | S2 · il mio catalogo | seduta |
| `/academy/lezione/<id>` | S3 · la lezione | seduta, lezione visibile (§ 7.2) |
| `/academy/registro` | S6 · registro dell azienda | seduta con ruolo amministratore |
| `/academy/setup/…` | impostazioni (§ 10) | personale interno |

Rail: **utente** Catalogo · Le mie lezioni; **amministratore** + Registro; **personale interno** + Setup.

---

## § 10 — Impostazioni dell applicativo
**Stato: PROPOSTA** — rail Setup di RelDoxFood · *nessun mockup ancora per le linguette proprie*

- Da RelDoxFood: Impostazioni, Gestione utenti (solo personale interno), Preferenze, Area tecnica.
- Proprie: **Materiali** (versione caricata, data, commit; nella 0.1.01A solo lettura); **Chiavi pubbliche** di RelDox Chiavi.
- Tabella Funzionale: **Prodotti della famiglia** (nome, segno, versioni note), per il catalogo e per i corsi fuori famiglia più avanti.
- ⛔ Le linguette proprie hanno bisogno del loro mockup prima del codice (SPEC § 8).

---

## § 11 — Il pannello WordPress
**Stato: PROPOSTA** — come RelDoxFood · *regola dell owner 16/11/2026 08:57, chat RelDox CRD S707*

- Un menu «RelDoxAcademy» che porta all applicazione; il plugin di diagnostica col suo menu.
- Nella riga di **tutti e due** i plugin: «Stato e assistenza» e «Copia configurazione» (solo `manage_options`), mai chiavi, segreti, password, nomi o host dei database, prefisso, utenti, indirizzi IP (SPEC § 14).

---

## § 12 — Manutenzione e diagnostica
**Stato: PROPOSTA** — i tre livelli della SPEC della manutenzione v2, da RelDoxFood · *controlli propri qui sotto*

Controlli propri: materiali caricati e coerenti (numero di lezioni, domande senza risposta giusta = rosso); chiavi pubbliche presenti; accessi monouso scaduti ripuliti; registri delle aziende cessate oltre i 30 giorni = rosso; nessuna chiave di licenza in tabelle e registri (ricerca del formato della chiave = rosso).

---

## § 13 — Opzioni nel database di WordPress
**Stato: PROPOSTA** — solo chiavi di avvio

| opzione | che cosa | alla rimozione |
|---|---|---|
| `rdacademy_versione_schema` | versione dello schema del database proprio | si toglie |
| quelle di RelDoxFood, col segno nuovo | come RelDoxFood | come RelDoxFood |

---

## § 14 — Come si misura
**Stato: DA FARE** — banchi nella forma comune (skill `banco-forma-comune-reldox`), rossi prima e verdi dopo

| banco | che cosa prova | rosso se |
|---|---|---|
| B1 accesso | firma, scadenza, monouso, chiave valida e scaduta | entra un accesso falso, scaduto, già usato o con chiave scaduta |
| B2 catalogo | prodotti acquistati, ruolo, versione | si vede un prodotto non acquistato o un percorso di un altro ruolo |
| B3 verifica | esito calcolato sul server | la risposta giusta arriva al browser |
| B4 registro | sola propria azienda; CSV e PDF | un amministratore vede persone di un altra azienda |
| B5 segreti | nessuna chiave di licenza in tabelle e registri | il formato della chiave si trova nel database o nei log |
| B6 caricamento | tutto o niente | un materiale rotto lascia il catalogo a metà |
| + i banchi standard di RelDoxFood | | |

Palco e testing-live secondo `SPEC-AMBIENTE-DI-PROVA-TESTING-LIVE-v3`; browser a 1280, 820 e 390 px, nessuno scorrimento orizzontale, nessun errore JS; due pacchetti con MD5 (§ 9.2 delle regole).

---

## § 15 — Riferimenti di famiglia e ordine dei blocchi
**Stato: PROPOSTA** — ordine da approvare con l analisi

Riferimenti: `docs/SPEC-RELDOXACADEMY-v1.md` 1.10 · mockup `mockup/MOCKUP-RELDOXACADEMY-0.1.01A-v1.html` e `mockup/MOCKUP-CHI-SEI-IRIS-v1.html` · `docs/IRIS-v1.md` · `REGOLE-FAMIGLIA-RELDOX-v4` · `SPEC-MANUTENZIONE-E-DIAGNOSTICA-v2` · `SPEC-INDURIMENTO-INSTALLAZIONI-FAMIGLIA-RELDOX-v1` · `SPEC-API-FAMIGLIA-RELDOX-v8`.

| blocco | che cosa | dipende da | «fatto» quando |
|---|---|---|---|
| K | RelDoxFood 1.24.11L letto per intero; edizione 3 di questa analisi sul codice vero | — | analisi ed. 3 in `.md` e `.html` |
| G | copia di RelDoxFood con nome e segno nuovi, ristorazione tolta → i due plugin installabili | K | banchi standard verdi col segno `rdacademy`; nessun resto di RelDoxFood (ricerca di nomi e segno = zero) |
| M | caricamento dei materiali | G | B6 verde, le due lezioni di prova caricate |
| P | porta 2 e persone di prova | G | login interno con doppio fattore |
| C · L | catalogo e lezione con verifica | M, P | B2 e B3 verdi, browser a tre misure |
| I | «Chi sei?» con Iris | C | battute da `iris.txt` |
| R | registro, CSV e PDF | L | B4 verde |
| D | riga dei plugin e controlli propri | G | «Copia configurazione» senza segreti |
| A | porta 1, accesso firmato (dopo la 0.1.01A) | parere di Sicurezza-Cyber, pezzo comune, voce 159 | B1 e B5 verdi |
| Z | pacchetto 0.1.01A, kit, smoke dell owner | K-D | «ok smoke» |

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
