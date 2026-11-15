<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# SPEC di RelDoxAcademy — indice proposto v1

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. v1 · 15/11/2026 · Formazione F001 (voce F9). Indice **approvato** dall owner (D13, 18:56); il testo è in `docs/SPEC-RELDOXACADEMY-v1.md`.

**Scelte dell owner che la SPEC parte da** (chat Formazione F001, 15/11/2026): prodotto della famiglia (D1, 14:15); è della Formazione, nel repository A-010 (D6, 16:09); base RelDoxFood (D8, 16:57); **accesso sul nostro server** (D9, 17:02); primo corso per gli utenti dei clienti (D2); compreso nel canone (D3); nessun attestato (D4, modulo di igiene da definire con Sicurezza-Cyber, D10); italiano subito (D5); niente di definitivo, utile e facile per il cliente (D7); **accesso solo con chiave valida del prodotto acquistato, e solo per i prodotti acquistati** (D12, 17:06).

## Indice
| § | titolo | che cosa dirà, in breve |
|---|---|---|
| 0 | Decisioni dell owner | la tabella delle scelte, con data, ora e chat |
| 1 | Che cos è e per chi | il posto dove chi usa i prodotti RelDox impara a usarli; i ruoli: utente, amministratore, partner, personale interno; chi viene prima (utenti dei clienti) |
| 2 | Come è fatta | **una sola installazione** su ideatechnologies.it; due plugin sulla base di RelDoxFood (`reldox-academy/` e `reldox-academy-diagnostica/`); database proprio; che cosa si riusa e che cosa si toglie di RelDoxFood |
| 3 | Come si entra | ⛔ **l accesso c è solo con una chiave valida del prodotto acquistato** (D12, 17:06), emessa da RelDox Chiavi del capo famiglia; il pulsante «Corsi» nel prodotto del cliente; l accesso firmato e a tempo (nome, ruolo, prodotto e versione, nessuna password); che cosa vede chi ha la chiave scaduta |
| 4 | Il catalogo | percorsi per ruolo e per prodotto; ogni corso dice per quale versione del prodotto vale; ⛔ **il cliente vede manuali e corsi solo dei prodotti acquistati, non degli altri** (D12), e della sua versione |
| 5 | La lezione | obiettivi, passi, schermate con dati inventati, esercizio, verifica; scritta in Markdown con l intestazione di `modelli/` e letta così com è (una fonte, anche il PDF) |
| 6 | Il registro di completamento | chi, quale lezione, quando, esito della verifica; che cosa vede e scarica l amministratore del cliente; nessun attestato (salvo il modulo di igiene) |
| 7 | Le lingue | italiano subito; la struttura `it/`, `en/`, `de/` e le lingue già pronte nella base di RelDoxFood |
| 8 | Le schermate | elenco delle schermate per chi studia, per l amministratore del cliente, per Idea; ⛔ ognuna con il suo mockup all owner prima del codice |
| 9 | Il lavoro di Idea | pubblicare o ritirare un corso, aggiornarlo a una versione nuova di un prodotto, leggere le statistiche (lezioni abbandonate, verifiche sbagliate) |
| 10 | Che cosa cambia negli altri prodotti | il pulsante «Corsi» e l accesso firmato: chi lo scrive (ogni prodotto), con quale API di famiglia (da concordare con RelDox CRD) |
| 11 | Dati e privacy | Idea tiene i dati di chi studia: informativa e ruoli da Legale (L15); che cosa si conserva e per quanto |
| 12 | Sicurezza | server esposto e indurito (SPEC-INDURIMENTO), accesso firmato, freno ai tentativi; revisione di Sicurezza-Cyber; riga di sicurezza nello `STATO.md` |
| 13 | Licenza e canone | corsi compresi nel canone (Legale L9); la chiave come unico controllo |
| 14 | Manutenzione | il plugin di diagnostica: controlli, «Copia configurazione», indurimento «n su 11» |
| 15 | Versioni, banchi e smoke | numerazione con stadio, banco rosso → verde, smoke dell owner, kit, scheda in `novita/reldoxacademy.md` e riga in `prodotti.md` dalla prima versione |
| 16 | Prima versione | che cosa c è nella prima (un percorso «primi passi» di un prodotto, registro, accesso con la chiave) e che cosa no |
| 17 | Domande aperte | quelle che restano per l owner e per le altre chat |
| 18 | Registro delle modifiche | |

## Le chat che servono, quando il testo è scritto
RelDox CRD — capo famiglia e RelDox Chiavi (chiave, accesso firmato, API; messaggio già scritto: `bacheca/formazione/messaggi/2026-11-15-a-reldox-crd-academy-chiavi.md`) · Legale (L9, L15) · Sicurezza-Cyber (server, accesso, parere) · ogni prodotto (il pulsante «Corsi»).

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
