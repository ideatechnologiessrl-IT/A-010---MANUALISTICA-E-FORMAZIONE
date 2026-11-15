<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# SPEC di RelDoxAcademy — v1

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. v1 · 15/11/2026 · Formazione F001 (voce F9). Indice approvato dall owner (15/11/2026 18:56, chat Formazione F001: *«ok approvo spec .... In caso faremo variazioni in corso sulla base delleesigenze che si andranno a formare»*). ⏳ = parte in attesa di un altra chat, scritta come proposta.

## § 0 · Decisioni dell owner
| n. | parole dell owner | ora | che cosa diventa |
|---|---|---|---|
| D1 · D1 bis | *«Prodotto della famiglia»* · *«Un prodotto RelDox nuovo»* | 14:15-15:33 | RelDoxAcademy è un prodotto della famiglia |
| D2 | *«Utenti dei clienti»* | 14:15-15:33 | § 1, § 16: si comincia da chi usa i prodotti |
| D3 | *«Compresi nel canone»* | 14:15-15:33 | § 13 |
| D4 | attestato *«No»* | 14:15-15:33 | § 6 (eccezione del modulo di igiene da decidere con Sicurezza-Cyber, D10) |
| D5 | *«Italiano subito, traduzioni dopo»* | 14:15-15:33 | § 7 |
| D6 | *«RelDoxAcademy è e sarà un tuo prodotto quindi non si crea nessun nnuovo repository. Lo gestirai tu dentro il tuo repository.»* | 16:09 | § 2: codice in `reldox-academy/` del repository A-010 |
| D7 | *«Nulla deve essere definitivo, vige la regola che uno strumento deve essere utile, produttivo e facile da usare da parte del nostro cliente.»* | 15:51 | criterio di ogni scelta di questa SPEC |
| D8 | *«per il plugin direi di iniziare a usare RelDoxFood come punto di partenza»* | 16:57 | § 2 |
| D9 | *«RelDoxAcademy: B (accesso sul nostro server)»* | 17:02 | § 2, § 3: una sola installazione su ideatechnologies.it |
| D12 | *«l'accesso via web alla parte dei corsi è legata alla presenza della chiave valida sul prodotto che è stato acquistato. Il clinete ede i manuali solo sui prodotti acuistati e non sugli altri»* | 17:06 | § 3, § 4 |
| D13 | *«ok approvo spec … faremo variazioni in corso»* | 18:56 | questa v1; le versioni dopo seguono l uso reale |
| D14 | *«va bene il nome tecnico proposto, `rdacademy`»* · *«il pulsante «Corsi» diventa un pezzo comune, uguale per tutti i prodotti»* · *«parti dai mockup intanto che si definisce la forma della chiave»* | 19:21 | § 2.4, § 10.2, § 8 |

Tutte del 15/11/2026, chat Formazione F001; registro intero in `docs/DECISIONI-OWNER.md`.

## § 1 · Che cos è e per chi
RelDoxAcademy è il posto dove **chi usa i prodotti RelDox impara a usarli**: corsi brevi, manuali e schede rapide, sempre della versione che ha in casa.
| ruolo | chi è | che cosa trova |
|---|---|---|
| **utente** (primo, D2) | chi lavora ogni giorno con il prodotto presso l azienda licenziataria | «primi passi», lezioni per funzione, schede rapide |
| **amministratore del cliente** | chi installa, configura, gestisce utenti e copie | guida dell amministratore, il registro di completamento dei suoi utenti |
| **partner / segnalatore** | chi rivende o segnala i prodotti | percorso partner (più avanti) |
| **personale interno** | chi lavora in Idea Technologies | tutti i corsi, per l assistenza e l avvio dei clienti |

## § 2 · Come è fatta
1. **Una sola installazione** WordPress su ideatechnologies.it (D9), indurita secondo `SPEC-INDURIMENTO-INSTALLAZIONI-FAMIGLIA-RELDOX-v1`. Nessun plugin dal cliente.
2. **Due plugin sulla base di RelDoxFood** (D8), come ogni prodotto della famiglia: `reldox-academy/` (il prodotto) e `reldox-academy-diagnostica/` (la manutenzione, `SPEC-MANUTENZIONE-E-DIAGNOSTICA-v2`). Codice nel repository A-010 (D6).
3. **Database proprio** (regola «un prodotto, un database»): credenziali solo in `wp-config.php`.
4. **Segno e indirizzo dell app**: segno approvato dall owner (D14, 19:21), da scrivere in `REGOLE-FAMIGLIA` dal capo famiglia ⏳ (indirizzo e diagnostica da confermare): segno `rdacademy` (`RDACADEMY_`, `rdacademy_`, `Rdacademy_`), indirizzo `/academy/`, diagnostica `reldox-academy-diagnostica`.
5. **Che cosa si prende da RelDoxFood**: ossatura e caricamento, configurazione del database, accessi e accesso d emergenza, lingue, motore dei manuali in linea (una fonte → capitoli nell app e PDF), diagnostica. **Che cosa si toglie**: tutto ciò che è dei pasti (menu, cassa, codice personale, prenotazioni), il contratto di licenza da firmare (qui non serve: § 13).

## § 3 · Come si entra
1. ⛔ **Si entra solo con una chiave valida di un prodotto acquistato** (D12). La chiave la emette **RelDox Chiavi** del capo famiglia.
2. **Il percorso**: nel prodotto del cliente c è il pulsante **«Corsi»** → apre RelDoxAcademy in una scheda nuova portando un **accesso firmato e a tempo** → RelDoxAcademy controlla firma, scadenza e chiave → apre il catalogo di quella persona. Nessuna password da ricordare, nessuna registrazione.
3. **Che cosa porta l accesso** ⏳ (forma da concordare con RelDox CRD, `bacheca/formazione/messaggi/2026-11-15-a-reldox-crd-academy-chiavi.md`): prodotto, versione, riferimento della chiave, azienda licenziataria (identificativo, non il nome), nome visualizzato e ruolo di chi entra, ora di emissione e di scadenza (pochi minuti), firma. Nessuna password, nessun dato oltre questi.
4. **Regole di famiglia sulle chiavi** (capo famiglia, `bacheca/reldox-crd/messaggi/2026-11-15-a-tutti-scadenza-delle-chiavi.md`; owner 15/11/2026 18:58-19:01, chat RelDox CRD S706): avviso **60 giorni** prima della fine, nessun giorno di tolleranza dopo, rinnovo = vecchia fine + 12 mesi, nessuna revoca tecnica prima della fine. Per RelDoxAcademy: nel catalogo (S2), negli ultimi 60 giorni, la striscia «La manutenzione di <prodotto> finisce il <data>: dopo, i corsi non saranno più disponibili»; dal giorno dopo la fine, la pagina qui sotto.
5. **Chiave scaduta**: RelDoxAcademy mostra una pagina sola: «L accesso ai corsi è compreso nella manutenzione annuale del prodotto. Per riattivarlo scrivi a sales@ideatechnologies.com». Nessun contenuto visibile.
6. **Personale interno**: entra con un account di RelDoxAcademy (accesso WordPress con le regole di indurimento), vede tutto.
7. **Una seduta**: dopo l accesso firmato RelDoxAcademy apre una sua seduta (cookie sicuro, durata breve); a ogni apertura del pulsante «Corsi» la chiave si ricontrolla.

## § 4 · Il catalogo
1. ⛔ **Il cliente vede corsi, manuali e schede solo dei prodotti acquistati, non degli altri** (D12), e della **versione** che ha installato.
2. Più prodotti della stessa azienda ⏳: se le chiavi si riconoscono come della stessa azienda (domanda b) a RelDox CRD), un solo accesso mostra tutti i prodotti acquistati; altrimenti un catalogo per prodotto, dal pulsante di ciascuno.
3. **Il catalogo per ruolo**: l utente vede i percorsi da utente; l amministratore anche la guida dell amministratore e il registro.
4. **Ogni materiale dice per quale versione vale** (intestazione `vale_per`). Se il cliente ha una versione più vecchia di tutti i materiali, vede l ultimo materiale valido per la sua versione e l avviso «la tua versione è precedente: alcune schermate possono essere diverse».
5. **Cerca**: una casella di ricerca su titoli e testo, dentro i soli materiali visibili a quella persona.

## § 5 · La lezione
1. Una lezione = **un gesto, un risultato**, 5-10 minuti (SPEC della chat § 5.4): obiettivi in testa, passi con schermate da ambiente di prova o dati inventati, «che cosa non fa», esercizio, verifica.
2. **Fonte**: il Markdown in `corsi/<percorso>/it/` con l intestazione di `modelli/MODELLO-LEZIONE.md`; manuali e schede da `manuali/` e `schede/`. RelDoxAcademy li legge **così come sono** (una fonte sola: pagina nell app e PDF, come i manuali in linea di RelDoxFood).
3. **Pubblicare** = il contenuto del repository A-010 a una versione, caricato nell installazione dal plugin (§ 9). Nessun testo scritto a mano nel pannello.
4. **Verifica**: domande chiuse (3-5); la risposta giusta sta nel registro del percorso, non nella pagina. Si ripete quante volte si vuole.
5. **Accessibilità**: testo alternativo per ogni immagine, contrasto e misure di lettura di `grafica/SPEC-GRAFICA-RELDOX-v4`, sottotitoli per i video, tutto usabile da tastiera e da telefono.

## § 6 · Il registro di completamento
1. Per ogni persona: lezione, data di apertura, data di completamento, esito della verifica (giuste su totali), tentativi.
2. **L amministratore del cliente** vede il registro dei soli utenti della sua azienda e lo **scarica** (CSV e PDF) come prova della formazione fatta.
3. ⛔ **Nessun attestato** (D4). Il modulo di igiene di sicurezza ⏳: proposta a Sicurezza-Cyber che basti il registro scaricabile (`…-a-sicurezza-cyber-attestato-e-skill.md`).
4. Idea vede il registro di tutte le aziende, solo come numeri per migliorare i corsi (§ 9).

## § 7 · Le lingue
Italiano subito (D5). Struttura pronta: cartelle `it/`, `en/`, `de/` con gli stessi nomi di file (`docs/STRUTTURA.md` § 2); il gestore delle lingue di RelDoxFood mostra la lingua della persona, se il materiale esiste, altrimenti l italiano.

## § 8 · Le schermate
⛔ Ognuna col suo **mockup all owner** prima del codice.
| n. | schermata | per chi |
|---|---|---|
| S1 | Ingresso (accesso firmato in corso / chiave scaduta) | tutti |
| S2 | Il mio catalogo (prodotti acquistati, percorsi per ruolo, avanzamento) | utente, amministratore |
| S3 | La lezione (passi, esercizio, verifica) | utente |
| S4 | Manuale e schede (indice, capitolo, PDF) | utente, amministratore |
| S5 | Cerca | utente, amministratore |
| S6 | Registro della mia azienda (filtri, scarico CSV e PDF) | amministratore |
| S7 | Pannello di Idea (pubblica una versione dei materiali, statistiche) | personale interno |

## § 9 · Il lavoro di Idea
1. **Pubblicare**: dal pannello, «Carica la versione dei materiali» prende il contenuto di A-010 a un etichetta (es. `materiali-2026-11-20`) e mostra che cosa cambia; nulla si pubblica senza conferma.
2. **Aggiornare a una versione nuova di un prodotto**: a ogni unione in `main` di un prodotto la Formazione aggiorna i materiali toccati (SPEC della chat § 5.2) e pubblica; il registro delle versioni (`docs/REGISTRO-VERSIONI-MATERIALI.md`) dice che cosa vale per quale versione.
3. **Statistiche**: lezioni aperte e completate, dove si abbandona, verifiche più sbagliate, ricerche senza risultato — senza nomi di persone.

## § 10 · Che cosa cambia negli altri prodotti
1. **Il pulsante «Corsi»** nel menu di ogni prodotto, visibile a chi ha un ruolo; apre RelDoxAcademy con l accesso firmato (§ 3.3).
2. **Un pezzo comune** (D14, 19:21: *«il pulsante «Corsi» diventa un pezzo comune, uguale per tutti i prodotti»*): «Accesso ai corsi» in `comune/`, copiato in ogni prodotto, che firma l accesso con la chiave del prodotto ⏳ (chi lo scrive: capo famiglia o proposta della Formazione). Forma e firma da concordare con RelDox CRD (API di famiglia, RelDox Chiavi).
3. Nessun dato dei prodotti passa a RelDoxAcademy oltre l accesso firmato.

## § 11 · Dati e privacy
Dalle risposte di Legale (`bacheca/legale/messaggi/2026-11-15-a-formazione-academy-dati-di-chi-studia.md`, chat Legale L001, 15/11/2026):
1. **Dati**: nome visualizzato, ruolo, identificativo dell azienda, registro di completamento. Nessuna mail, nessuna password, nessun dato del lavoro del cliente.
2. **Ruoli**: per il registro dei suoi utenti il **titolare è l azienda licenziataria** e Idea Technologies lo tratta **per suo conto, come responsabile**: capitolo «RelDoxAcademy» nell accordo art. 28 (Legale, voce L4). Per i propri registri tecnici (accessi, sicurezza) Idea è **titolare autonomo**.
3. **Informativa** per chi studia (Legale, voce L15): la rende disponibile Idea, per conto dell azienda, con i dati di contatto di Idea per l accesso tecnico; link in S1 e in fondo a ogni pagina.
4. **Conservazione**: il registro **finché la licenza è attiva**; alla cessazione l amministratore del cliente ha **30 giorni** per scaricarlo, poi Idea lo cancella (licenza v3 art. 10.7). Un tempo più lungo solo con una ragione precisa portata all owner da Legale.

## § 12 · Sicurezza
1. Server esposto e indurito (`SPEC-INDURIMENTO-INSTALLAZIONI-FAMIGLIA-RELDOX-v1`, «n su 11» nella diagnostica).
2. Accesso firmato a tempo breve, usabile una volta; freno ai tentativi; registro degli accessi senza dati personali oltre l identificativo.
3. ⏳ **Parere di Sicurezza-Cyber** su server, accesso firmato e conservazione, prima della prima versione; riga di sicurezza nello `STATO.md` a ogni unione.

## § 13 · Licenza e canone
1. I corsi sono **compresi nel canone** (D3): ⏳ Legale, voce L9 (oggi la licenza v3 art. 9.4 mette «la formazione» fra le cose pagate a parte; Legale aggiunge l autoformazione on line fra quelle comprese).
2. La **chiave valida** del prodotto è l unico controllo: niente contratto da firmare per RelDoxAcademy.

## § 14 · Manutenzione
Il plugin `reldox-academy-diagnostica` come gli altri prodotti: stato del sistema, controlli, «Copia configurazione», indurimento, registro dei banchi.

## § 15 · Versioni, banchi e smoke
1. Numerazione della famiglia: tre segmenti con lo stadio attaccato (`0.1.01A`), dopo `.09` il padre +1; terna intestazione · `RDACADEMY_VERSION` · `RDACADEMY_STAGE`.
2. Banco **rosso** sulla versione prima, **verde** sulla nuova; mockup prima di ogni schermata; dopo due fallimenti ci si ferma.
3. Consegna all owner come kit; al suo **«ok smoke»**: unione in `main`, scheda in `novita/reldoxacademy.md`, riga in `prodotti.md`, `STATO.md` con riga di sicurezza.
4. Prova su un ambiente di prova prima del server vero (`SPEC-AMBIENTE-DI-PROVA-TESTING-LIVE-v3`).

## § 16 · Prima versione (0.1.01A)
**C è**: S1 ingresso con accesso firmato e chiave; S2 catalogo; S3 lezione; S6 registro; un percorso **«primi passi»** per l utente di **un** prodotto (quale, lo sceglie l owner); italiano.
**Non c è**: S4 manuali e schede (arrivano dalla 0.1.02A), cerca, statistiche, pannello di pubblicazione (nella prima i materiali si caricano col kit), altre lingue, partner.

## § 17 · Domande aperte
| a chi | che cosa | dove |
|---|---|---|
| owner | il prodotto del primo percorso «primi passi» | in chat, dopo questa SPEC |
| RelDox CRD | forma e verifica della chiave; chiavi della stessa azienda; chi progetta l accesso firmato; segno e indirizzo | `…-a-reldox-crd-academy-chiavi.md` e messaggio con questa SPEC |
| Legale | L9 canone; L15 informativa; L4 capitolo art. 28 | `…-a-legale-corsi-nel-canone.md`, risposte del 15/11/2026 |
| Sicurezza-Cyber | attestato del modulo di igiene; parere su server e accesso | `…-a-sicurezza-cyber-attestato-e-skill.md` |
| ogni prodotto | il pulsante «Corsi» | dopo l accordo con RelDox CRD |

## § 18 · Registro delle modifiche
| versione | data | che cosa | perché |
|---|---|---|---|
| 1.2 | 15/11/2026 | § 3.4 regole di famiglia sulle chiavi (avviso 60 giorni, nessuna tolleranza); § 11 ruoli privacy e conservazione da Legale | messaggi di RelDox CRD S706 e Legale L001 del 15/11/2026 |
| 1.1 | 15/11/2026 | segno `rdacademy`, pulsante «Corsi» pezzo comune, mockup subito | D14, owner 19:21, chat Formazione F001 |
| 1 | 15/11/2026 | prima stesura sull indice approvato | D13, owner 18:56, chat Formazione F001 |

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
