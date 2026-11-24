<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Decisioni dell owner per la Manualistica e formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Registro tenuto dalla chat Manualistica e formazione; la più recente in testa. Ogni riga: testo della scelta, data, ora, chat.

## F022 · 20-21/11/2026
| n. | domanda | scelta dell owner | ora | effetti |
|---|---|---|---|---|
| D46 | I registri dei punti aperti e dei bug in `main` anche prima dell unione del codice (richiamo: lo stato dichiarava 38 punti e 1 bug, in `main` se ne leggevano 33 e 0) | *«si procedi ma procedi nel kit e nella generazione del nuov propmpt x nuova chat»* | 24/11/2026 13:41, chat Formazione F032 | `docs/ROADMAP.md` e `docs/BUG-FORMAZIONE.md` del ramo di sessione portati in `main` senza codice (42 punti, 1 bug); da ora i due registri si riportano in `main` a ogni kit |
| D45 | Mockup v1 della «Console SQL» nella diagnostica di RelDoxAcademy (`mockup/MOCKUP-CONSOLE-SQL-v1.html`: lettura in sola lettura protetta, scrittura solo INSERT/UPDATE/DELETE/REPLACE con prova a secco, CONFERMO e registro, rifiuti lato server, segreti mascherati) | *«ok approvato»* | 23/11/2026 08:39, chat Formazione F025 | mockup approvato; prossimo passo: banco CLI con trappola rosso sulla 0.1.10A, codice, verde, revisione a freddo, 0.1.11A, smoke (resto di F29) |
| D44 | Smoke della 0.1.10A («Licenza d uso») su www.reldoxacademy.it: passo 4, chiave valida sul sito (A: costante di prova nel wp-config e pulizia) o solo a banco (B) | 23/11 06:19 *«smok ok»* (passo 2: *«smoke ok c'èe la scritta»*); passi 3a e 3b con schermate (rifiuto «non è firmata da Idea Technologies», rifiuto «non è nel formato»); passo 4: *«B — solo a banco, chiudo qui»* | 23/11/2026 06:31, chat Formazione F024 | smoke verde della 0.1.10A con i passi 1-3 sul sito (installazione, pagina e striscia «Manca la chiave», due chiavi sbagliate rifiutate); chiave valida, preavviso, sola consultazione provati solo a banco (verde 22/22); unione in `main`; F29 resta aperta (console SQL, `REGOLE-RELDOXACADEMY-v1.md`); passi del contratto (allegato, firma, trasmissione) non provati sul sito |
| D43 | Mockup v2 della «Licenza d uso» (copia della pagina di RelDox CRD 3.76.03G, tre viste) | *«approvato mockup genera»* | 21/11/2026 19:05, chat Formazione F023 | 0.1.10A: licenza portata dal CRD con `banco/F023-porta-licenza.py`; banco rosso 0/12 → verde 22/22 (corretto il 23/11/2026 F024: era scritto 0/11 e 21/21, l esito `banco/esiti/F023-licenza-verde-0.1.10A.txt` dice 22/22) |
| D42 | 0.1.09A su reldoxacademy.it: A1 «manca» (7 su 18), come procedere | 20/11 16:53 *«Salvo A1, poi smoke ok»*; 21/11 14:31 schermata (interruttore spento), 14:33 configurazione *«Indurimento: 8 su 18 · mancano A3, A4, A6, A8, A11, A13, A15, A17 · da verificare a mano A12, A18»*, 14:34 *«fatto»* | 21/11 14:34 | smoke verde della 0.1.09A (prodotto e diagnostica); BUG-5 respinta; unione in `main`: `novita/reldoxacademy.md`, `prodotti.md`, riga di sicurezza nello `STATO.md`; F29 resta aperta (console SQL, licenza d uso, `REGOLE-RELDOXACADEMY-v1.md`) |

## F021 · 20/11/2026
| n. | domanda | scelta dell owner | ora | effetti |
|---|---|---|---|---|
| D40 | Prossima voce a cinque stelle dopo la 0.1.08A: F26 (nessun blocco) o F29 (standard di famiglia) | *«F29 genera»* (la chat consigliava F26: blocco di 15 minuti dopo 5 password errate) | 14:07 | F29 in lavoro, una cosa alla volta: prima la voce wp-admin «Licenza d uso», mockup v1 `mockup/MOCKUP-LICENZA-D-USO-v1.html`; poi plugin di diagnostica e regole di RelDoxAcademy. F26 resta aperta a ★★★★★ |
| D41 | Mockup v1 della «Licenza d uso» (installazione propria, senza chiave) | *«importante che mantieni la stessa logica, funzionalità ed impostazione di quella che già esiste in merito alla licenza su gli altri prodotti. In caso di domande chiedi al capo famiglia se vuoi specifiche appropriate»* | 14:25 | mockup v1 superato; licenza come RelDox CRD 3.76.03G (accettazione, contratto PDF, chiave firmata, sola consultazione); specifiche chieste al capo famiglia (`bacheca/formazione/messaggi/2026-11-20-a-reldox-crd-licenza-d-uso-academy.md`); niente codice della licenza fino alla risposta |

## F020 · 20/11/2026
| n. | domanda | scelta dell owner | ora | effetti |
|---|---|---|---|---|
| D39 | Esito della 0.1.08A su reldoxacademy.it | configurazioni incollate: 09:13 interruttori spenti *«Indurimento: 8 su 18 · mancano A1, A3, A4, A6, A11, A13, A15, A17 · da verificare a mano A12, A18»*; 09:14 A1 acceso *«Indurimento: 9 su 18 · mancano A3, A4, A6, A11, A13, A15, A17 · da verificare a mano A12, A18»*; 09:15 *«smoke ok»*, dato dopo l indicazione della chat che A2 non serviva accenderlo (elenco utenti già nascosto, A2 a posto in entrambe le misure) | 09:15 | 0.1.08A in `main`: F34 (A1 provato sul sito; A2 provato solo al banco, accettato dall owner con lo smoke) e BUG-4 chiusi e passati nei registri dei chiusi; `prodotti.md`, `novita/reldoxacademy.md`, riga di sicurezza nello `STATO.md` |

## F019 · 20/11/2026
| n. | domanda | scelta dell owner | ora | effetti |
|---|---|---|---|---|
| D38 | Mockup degli interruttori A1 e A2 (`mockup/MOCKUP-INTERRUTTORI-A1-A2-v1.html`): nel riquadro dell indurimento della Stato sistema, sotto la regola, pulsante «Salva», niente pagina nuova | *«ok mockup approvato»* | 07:49 | 0.1.08A: interruttori a schermo, salvataggio solo `manage_options` con nonce, la misura si rifà subito |
| D37 | Esito della 0.1.07A su reldoxacademy.it | configurazione incollata dalla 0.1.07A: *«Indurimento: 7 su 18 · mancano A1, A3, A4, A6, A9, A11, A13, A15, A17 · da verificare a mano A12, A18»* | 07:09 | 0.1.07A in `main`: F32 chiusa e passata in `PUNTI-CHIUSI-FORMAZIONE.md`; `prodotti.md`, `novita/reldoxacademy.md`, riga di sicurezza nello `STATO.md`; BUG-4 (A9 segnata «manca» mentre la riga della cache dice «nessuna azione richiesta») aperto per la 0.1.08A |

## F017 · 19/11/2026
| n. | domanda | scelta dell owner | ora | effetti |
|---|---|---|---|---|
| D36 | Esito della 0.1.06A su reldoxacademy.it | configurazione incollata e *«riga plugin»* | 18:34 | 0.1.06A in `main`: F17 chiusa e passata in `PUNTI-CHIUSI-FORMAZIONE.md`; `prodotti.md` e `novita/reldoxacademy.md` aggiornati |
| D35 | Esito della 0.1.05A su reldoxacademy.it | *«smoke ok ma manca il copia configurazione fuori nella lista dei plugin. adeguati sullo stato sistema sulla base delle specifiche che dice il capo famiglia»* | 17:28 | prima unione in `main` di RelDoxAcademy (0.1.05A): BUG-1, BUG-2, BUG-3 e F33 chiusi; riga in `prodotti.md`, schede in `novita/reldoxacademy.md`; la voce F17 (regola di famiglia del 16/11: «Stato e assistenza» e «Copia configurazione» nella riga del plugin) entra nella 0.1.06A |
| D34 | Pulsante «Installa» nella barra del browser su reldoxacademy.it (app installabile ereditata da RelDoxFood: manifesto e service worker) | *«togliamola in questo caso non ci serve averla sul phone mi sembra eccessivo»* (chat Formazione F017) | 16:35 | voce F33: al prossimo pacchetto si tolgono manifesto, `sw.js`, la registrazione in `rdacademy-icona.js` e le righe in `layout.php`; niente `sw.js` di passaggio: l unico browser che l ha registrato è quello dell owner (16:40, chat Formazione F017: *«solo io ... uindi nessun problema»*), che lo toglie a mano una volta |

## F016 · 19/11/2026

| n. | domanda | scelta dell owner | ora | effetti |
|---|---|---|---|---|
| D33 | S-2 della SPEC-GRAFICA v5 § 13 (loghi verso l esterno: logo del prodotto in firma, in testa logo dell azienda o intestazione scritta, mai il logo Idea predefinito né le iniziali) | *«Sì, come la SPEC»* | 15:46 | voce F31: nel codice con le prime email e PDF verso l esterno; al capo famiglia per la tabella del § 13 |
| D32 | S-1 della SPEC-GRAFICA v5 § 13 (cima della barra laterale senza logo, blocco vuoto di 48px) | *«Sì, cima vuota»* | 15:46 | 0.1.04A: `.rdacademy-rail-cima` in `public/views/layout.php`; al capo famiglia per la tabella del § 13 |

## F006 · 17/11/2026
| n. | scelta dell owner | ora | effetti |
|---|---|---|---|
| D31 | domanda 3 del § 15 (mail di servizio dell Academy): *«academy@reldox.it»* | 15:58 | `RDACADEMY_SERVICE_EMAIL` = `academy@reldox.it` in `reldox-academy/reldox-academy.php`; domanda 3 chiusa (la casella va creata se non c è) |

## F005 · 17/11/2026
| n. | scelta dell owner | ora | effetti |
|---|---|---|---|
| D30 | *«genera»* | 15:28 | parte il blocco G (copia di RelDoxFood 1.24.11L con nome e segno `rdacademy`, edizione 4); la domanda 3 (mail di servizio) resta aperta: nella copia `RDACADEMY_SERVICE_EMAIL` tiene il valore di RelDoxFood finché l owner non dà l indirizzo |
| D29 | edizione 4 dell analisi (manuali di RelDoxFood, API comprese, nell Academy come materiali; motore delle API solo in RelDoxFood): *«ok ma tieni sempre presente ciò che è manuale e ciò che è codice . Tu non entri mai nel codice»* | 15:10 | `docs/ANALISI-TECNICA-RELDOXACADEMY.md` ed. 4 e `.html`: decisione MATERIALE nell allegato A (24 file), § 4, § 7.1, blocco M; regola «manuale e codice distinti»: il codice dei prodotti si legge e basta, mai si cambia; il solo codice nostro è RelDoxAcademy (CLAUDE.md di A-010) |
| D28 | *«ho paura che c istiamo infilando in un tunel pericoloso. Facciamo così 1) tu duplichi tutto in reldoxaccademy e nulla si toglie dai prodotti. 2) quando siamo sicuri che tutto è in linea e tutto funziona su accademy allora e solo allora le chat toglieranno la parte di manualistica. E' più sicuro così che andare a pezzi a togliere. Quindi tu devi fare una nuova copia di tutto funzionante in modo indipendente su academy»* | 15:08 | nessun prodotto perde nulla finché l Academy non funziona con tutto dentro; la manualistica esce dai plugin solo dopo, in un colpo, su conferma; RelDoxAcademy è una copia indipendente (nessun legame di codice con RelDoxFood). Conferma e rafforza la regola di famiglia del 17/11 13:54 (chat RelDox CRD S713). Domanda 2 del § 15 in chiarimento |
| D27 | analisi tecnica edizione 3: *«approvo»: l edizione 3 vale così comè · Eventuali modifiche le faremo dopo. · Se non vedo il risultato dinale non posso modificare»* | 14:51 | blocco K chiuso: `docs/ANALISI-TECNICA-RELDOXACADEMY.md` ed. 3 approvata con l allegato A così com è; le modifiche si faranno vedendo il plugin attivo, come versioni nuove (come D25); restano le domande 2 e 3 del § 15 |

## F002 · 17/11/2026
| n. | scelta dell owner | ora | effetti |
|---|---|---|---|
| D26 | *«ferma la lezione e crea il plugin secondo lo starda di quelli esistenti, chiedi al capo famiglia in caso»* | 12:10 | lezioni ferme alla 1.2; F003 parte da RelDoxAcademy 0.1.01A secondo REGOLE-FAMIGLIA v4 (§ 2 si copia, § 7.0 analisi tecnica ed. 1, § 14 guscio «RelDox Base» dal kit di partenza); al capo famiglia il kit e il segno del prodotto |
| D25 | *«riesco a darti un check solo dopo che è attiva eventuali modifiche le vediamo dopo come in un qualsiasi corso»* | 12:01 | le lezioni non aspettano l approvazione una per una: si scrivono nello stile approvato, si provano sui tre schermi e si registrano; il controllo dell owner è con RelDoxAcademy attiva, le correzioni come versioni nuove delle lezioni |

## F001 · 17/11/2026
| n. | scelta dell owner | ora | effetti |
|---|---|---|---|
| D24 | scheda di Iris: *«approvo»* | 10:28 | `docs/IRIS-v1.md` approvata; si passa al glossario (F4) e alle lezioni del percorso 1 |
| D23 | *«Chi è Iris ? :-) devi anche presentarla e dire cosa farà e cosa dirà un domani potrebbe anche essere integrata con la AI (tu) come abbiamo fatto con relDox (vai a vedere come)»* | 10:23 | `docs/IRIS-v1.md` (chi è, che cosa fa, catalogo delle battute, «Chiedi a Iris» col modello della voce 71 di RelDox); SPEC 1.10 § 8 e § 17; roadmap F21 |
| D22 | indice di RelDoxFood in quattro percorsi con il «Chi sei?» di Iris: *«si ok»*; sui quattro punti del sito: *«Alcuen di queste attività sono già state completate»* | 10:17 | indice v2 approvato; mockup del «Chi sei?», poi glossario e prime lezioni |

## F001 · 16/11/2026
| n. | scelta dell owner | ora | effetti |
|---|---|---|---|
| D21 | *«i passi per reldoxfood .... 1) primo punto configurazione da parte degli amministratori del sistema del prodotto. 2) formazione di rilascio QR code alle persone e ai ristoratori da parte del personale aziendale 3) formazione utenti aziendale utilizzatori del sistema 4) formazione ristoratore. Io me lo imaginerei con un CHI SEI guidato ..... deve essere come un assistente chiedi al marketing anche la risorsa disegnata del assistente umano che abbiamo fatto»* | 17:09 | indice v2 `corsi/reldoxfood/INDICE.md` (quattro percorsi, «Chi sei?»); messaggio al Marketing; SPEC 1.7 § 8 |
| D20 | *«ok avrai il tuo sito indipendente con un'infrastruttura totalmente dedicata a te ... magari più avanti ci faremo un pensiero per aprirlo a dei corsi fuori famiglia .... vedremo l'evoluzione»* | 16:56 | **supera D18**: RelDoxAcademy su un sito e un infrastruttura dedicati; SPEC 1.6 § 2.1 e § 17; messaggio a Sicurezza-Cyber aggiornato |
| D19 | *«Si stile perfetto ricorda che la parte visiva deve avere sempre la priorità gli occhi imparano prima delle parole. Inoltre il tutto deve essere utilizzabile su tutti i dispositivi»* | 16:14 | `docs/STILE-DEI-MATERIALI-v1.md` 1.1 § 0; lezione d esempio v1.1 con la figura prima del testo |
| D18 | *«Il dominio RelDoxAcademy ha un redirect che porterà ad una pagina sotto ideatechnologies.it se però preferisci che compro una infrastruttura a parte con un wp separato l ofacciamo. Per ottimizzazione vorrei mntenere tutto nel sito istituzionale. Sentiamo anche il parere di cyber»* | 16:11 | SPEC § 2.1: nel sito istituzionale, salvo parere di Sicurezza-Cyber (`…-a-sicurezza-cyber-academy-dove-installarla.md`) |
| D17 | *«le guide non devono esere sviluppate a livello sol odi testo, ma devono avere delle caratteristiche tipiche ForDummies. L'utente deve essere in grado di fare da se e solo se proprio non capisce deve chiamarci»* | 09:34 | `docs/STILE-DEI-MATERIALI-v1.md`, `modelli/MODELLO-LEZIONE.md` a otto elementi, lezione d esempio `mockup/MOCKUP-LEZIONE-ESEMPIO-v1.html` |
| D16 | *«reldoxfood che è quello attualmente in corso d'offerta sul secondo cliente il primo lo sta già usando»* | 09:32 | primo percorso «primi passi»: RelDoxFood, chi mangia; indice `corsi/reldoxfood/utente-primi-passi/INDICE.md` |
| D15 | *«leggi news · per ora mockup approvato, devo provare a lavoraci prima di confermarlo definitivamente»* | 09:16 | mockup 0.1.01A v1 approvato per ora; SPEC 1.3 § 8 |

## F001 · 15/11/2026 — dopo le domande di avvio
| n. | scelta dell owner | ora | effetti |
|---|---|---|---|
| D14 | *«va bene il nome tecnico proposto, `rdacademy` · il pulsante «Corsi» diventa un pezzo comune, uguale per tutti i prodotti · parti dai mockup intanto che si definisce la forma della chiave»* | 19:21 | SPEC § 2.4 e § 10.2; segno e pezzo comune li scrive il capo famiglia; mockup S1, S2, S3, S6 |
| D13 | *«ok approvo spec .... In caso faremo variazioni in corso sulla base delleesigenze che si andranno a formare»* | 18:56 | indice approvato; testo `docs/SPEC-RELDOXACADEMY-v1.md`; messaggi a RelDox CRD, Sicurezza-Cyber, Legale |
| D12 | *«devi fasarti anche con la chat capofamiglia che sta sviluppando il plugin delle chiavi. E' chiaro che l'accesso via web alla parte dei corsi è legata alla presenza della chiave valida sul prodotto che è stato acquistato. Il clinete ede i manuali solo sui prodotti acuistati e non sugli altri»* | 17:06 | indice SPEC § 3 e § 4; messaggio a RelDox CRD (`…-a-reldox-crd-academy-chiavi.md`) |
| D9 | *«RelDoxAcademy: B (accesso sul nostro server)»* | 17:02 | una sola RelDoxAcademy su ideatechnologies.it, accesso con la chiave; voce F9 |
| D10 | attestato del modulo di igiene e skill `accessibility`: *«Per questo devi confontrarti con la chat cyber»* | 17:02 | messaggio a Sicurezza-Cyber (`…-a-sicurezza-cyber-attestato-e-skill.md`) |
| D11 | indice della SPEC di RelDoxAcademy prima del testo: *«0k il punto come hai detto»* | 17:02 | `docs/SPEC-RELDOXACADEMY-INDICE-v1.md` all owner |
| D8 | *«ok per il plugin direi di iniziare a usare RelDoxFood come punto di partenza. Di fatti chi compra il prodotto installerà 1) il core del prodotto (plugin) 2) manutenzione 3) reldoxAccadamy da valutare se si oppure fare solo accesso su nostro server ideatechnologie.it ma protetto con la gestioni delle chiavi»* | 16:57 | base del plugin = RelDoxFood; dove gira da decidere (`docs/VALUTAZIONE-INSTALLAZIONE-RELDOXACADEMY-v1.md`); voce F9 |
| D6 | *«RelDoxAcademy è e sarà un tuo prodotto quindi non si crea nessun nnuovo repository. Lo gestirai tu dentro il tuo repository.»* | 16:09 | supera D1 bis per la parte «repository e chat sua»; voce F9; messaggio al capo famiglia aggiornato (registro, mappa, SPEC v2) |
| D7 | *«ok approvo per ora poi in cao apportiamo delle modifiche in base alla reale operatività. Nulla deve essere definitivo, vige la regola che uno strumento deve essere utile, produttivo e facile da usare da parte del nostro cliente. procedi con inventario su tutti i prodotti e dividi per prodotto. In caso devi chiedere alle singole chat di progetto»* | 15:51 | struttura F1 approvata «per ora»; principio di ogni materiale; voce F3 |

## F001 · 15/11/2026 — domande di avvio (SPEC § 6, voce F2)
Domande chiuse fatte una alla volta nella chat Formazione F001. La prima risposta è delle 14:15; le altre fra le 14:15 e le 15:33 (ora esatta non registrata dallo strumento delle domande).

| n. | domanda | scelta dell owner | ora | effetti |
|---|---|---|---|---|
| D1 | Su quale piattaforma girano i corsi on line? | *«Prodotto della famiglia»* | 14:15 | voce F9 |
| D1 bis | Quale prodotto? | *«Un prodotto RelDox nuovo»* (un plugin a sé, con repository e chat sua) | 14:15-15:33 | voce F9: proposta al capo famiglia, nuovo prodotto da istituire |
| D2 | A chi è rivolto il primo corso? | *«Utenti dei clienti»* | 14:15-15:33 | voce F6 «primi passi» per l utente |
| D3 | Come si pagano i corsi per i clienti? | *«Compresi nel canone»* | 14:15-15:33 | voce F10 a Legale (che cosa comprende il canone) |
| D4 | Attestato a fine corso? | *«No»* | 14:15-15:33 | resta solo il registro di completamento; nessun testo d attestato a Legale |
| D5 | Lingue | *«Italiano subito, traduzioni dopo»* | 14:15-15:33 | struttura con cartella di lingua `it/` in ogni materiale (`docs/STRUTTURA.md`) |

⛔ D1 bis tocca la famiglia (un prodotto nuovo): per REGOLE-DIALOGO v5 § 5 e § 11.2 la valuta il capo famiglia; fino alla sua risposta per le altre chat è un informazione.

## Istituzione · 15/11/2026 08:47
*«A-010 - MANUALISTICA E FORMAZIONE … Ho creato questa nuova chat di progetto. Servirà per creare tutta la parte di formazione corsi on line Inizializza la chat come da procedere definite dal CF»* (owner, chat RelDox CRD S705).

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
