<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# RelDoxAcademy — dove gira: dal cliente o sul nostro server? Valutazione v1

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. v1 · 15/11/2026 · Formazione F001 (voce F9). **Decisa: B** (owner 15/11/2026 17:02, chat Formazione F001: *«RelDoxAcademy: B (accesso sul nostro server)»*).

**Scelta dell owner** (15/11/2026 16:57, chat Formazione F001): *«per il plugin direi di iniziare a usare RelDoxFood come punto di partenza. Di fatti chi compra il prodotto installerà 1) il core del prodotto (plugin) 2) manutenzione 3) reldoxAccadamy da valutare se si oppure fare solo accesso su nostro server ideatechnologie.it ma protetto con la gestioni delle chiavi»*.
**Principio** (15:51): *«uno strumento deve essere utile, produttivo e facile da usare da parte del nostro cliente»*.

## 1 · Che cosa prendiamo da RelDoxFood (1.24.11L), in entrambe le strade
- **Ossatura a due plugin**: `reldoxfood/` (il prodotto) + `reldoxfood-diagnostica/` (la manutenzione: licenza, azzeramento, registro dei banchi). RelDoxAcademy nasce uguale: `reldox-academy/` + `reldox-academy-diagnostica/`.
- **Database proprio** (`class-rdfood-db-config.php`, regola «un prodotto, un database»), accessi e accesso d emergenza, lingue (`class-rdfood-lingua.php`: it, en, de — utile per D5).
- **Licenza** (`includes/license/`): anagrafica dell intestatario, contratto in PDF, firma (`SPEC-FIRMA-LICENZA-RELDOX-v1`).
- **Manuali in linea** (`SPEC-MANUALI-IN-LINEA-RELDOXFOOD-v1`, `docs-content/`): una sola fonte, capitoli nell app e PDF — è già il motore di cui le lezioni hanno bisogno.

## 2 · Le due strade
| | **A · Plugin dal cliente** (terzo plugin, accanto a core e manutenzione) | **B · Accesso sul nostro server** (una sola RelDoxAcademy su ideatechnologies.it, protetta dalle chiavi) |
|---|---|---|
| Che cosa installa il cliente | core + manutenzione + RelDoxAcademy, e un database in più | core + manutenzione; nel core un pulsante «Corsi» che apre l Academy con la sua chiave |
| Corsi aggiornati | a ogni pacchetto del plugin, cliente per cliente | **una volta, per tutti, subito** |
| Allineamento corso ↔ versione del prodotto | dipende da quando il cliente aggiorna | l Academy sa dalla chiave quale prodotto e versione ha il cliente e mostra i corsi giusti |
| Canone in corso (D3) | da verificare in ogni installazione | **la chiave del prodotto acquistato** (RelDox Chiavi) apre o chiude l accesso |
| Facilità per il cliente | un plugin in più da installare, aggiornare, copiare | **niente da installare**; un clic |
| Lavoro per Idea Technologies | assistenza su N installazioni | una installazione da tenere, indurita (SPEC-INDURIMENTO) |
| Dati di chi studia (registro di completamento) | restano dal cliente, titolare il cliente | **li tiene Idea**: informativa e ruoli privacy da Legale (L15), misure da Sicurezza-Cyber |
| Rete | funziona anche senza raggiungere il nostro server | serve il collegamento al nostro server |
| Statistiche per migliorare i corsi | sparse, una per cliente | in un posto solo (quali lezioni si abbandonano, quali verifiche falliscono) |
| Chi è l utente | utenti WordPress del cliente | il core passa all Academy un accesso firmato e a tempo (nome e ruolo, nessuna password); da definire con RelDox CRD e Sicurezza-Cyber |

## 3 · La mia proposta: **B**
È la strada più facile per il cliente e la più produttiva per noi: nessun plugin in più, corsi sempre allineati, canone verificato con la chiave che esiste già. RelDoxFood resta il punto di partenza **del plugin**, che si installa una volta sola sul nostro server; nei prodotti dei clienti basta il pulsante «Corsi».
**Che cosa costa**: Idea diventa responsabile dei dati di chi studia (Legale) e di un server esposto (Sicurezza-Cyber); l accesso firmato dal core va progettato con il capo famiglia (API di famiglia) e cambia di poco ogni prodotto (il pulsante).
**Niente di definitivo** (15:51): se la realtà lo chiede, la stessa base può diventare anche un plugin dal cliente (A) per chi non può uscire in rete.

## 4 · Dopo la scelta
1. SPEC di RelDoxAcademy v1 (indice all owner prima del testo), poi mockup delle schermate.
2. Con B: messaggi uno a uno a RelDox CRD (accesso firmato, chiavi), Legale (L15), Sicurezza-Cyber (server e accesso).

## Registro delle modifiche
| versione | data | che cosa | perché |
|---|---|---|---|
| 1.1 | 15/11/2026 | tolto il richiamo sbagliato alla licenza v3 art. 9.5 (parla del ritardato pagamento, non della chiave) | correzione della Formazione |
| 1 | 15/11/2026 | prima stesura | scelta dell owner 16:57, chat Formazione F001 |

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
