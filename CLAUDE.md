# Manualistica e formazione — regole di lavoro per Claude

> ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Questo repository è della chat **Manualistica e formazione** della famiglia RelDox: manuali d uso dei prodotti, guida dell amministratore, corsi on line per ruolo, schede rapide, domande frequenti, glossario di famiglia. Compiti e metodo: `spec/SPEC-CHAT-MANUALISTICA-FORMAZIONE-FAMIGLIA-RELDOX-v3.md` del nodo (la tiene il capo famiglia, la chat RelDox CRD). Istituita dall owner il 15/11/2026 08:47 (chat RelDox CRD S705).

**RelDoxAcademy** — il plugin dei corsi on line della famiglia è **nostro** e vive in questo repository (SPEC v3 § 2 bis, manuali dei prodotti § 2 ter; owner 15/11/2026 16:08, chat RelDox CRD S705, e 16:09, chat Formazione F001). Segue le regole dei prodotti: versioni con stadio, banco rosso → verde, mockup prima di ogni interfaccia, smoke dell owner, kit; all unione in `main` scheda in `novita/reldoxacademy.md`, riga in `prodotti.md`, riga di sicurezza nel `STATO.md`. Anagrafiche e stato del canone da RelDox CRD con le API di famiglia (`…-a-reldox-crd-academy-<tema>.md`). Il codice andrà in `reldox-academy/` (cartella da creare con la prima versione, dopo SPEC e mockup approvati).

## Prima di tutto, in ordine
0. ⛔ **Decisioni chiuse, per prime**: leggi `docs/DECISIONI-CHIUSE.md` prima dell APERTURA; appena il nodo è agganciato, dal nodo: `python3 -I strumenti/decisioni-chiuse.py <A-010> --aperti <A-010>/docs/ROADMAP.md <A-010>/docs/BUG-FORMAZIONE.md` (VERDE all apertura e prima di scrivere ogni APERTURA nuova). Una decisione chiusa non si riapre, non si riverifica, non si richiede all owner; la riga «da fare» che la contraddice si toglie. *(REGOLE-FAMIGLIA v9 § 10 ter del nodo; owner 23/11/2026, chat RelDox CRD S745: «Sì, a tutte (consigliata)»; CRD-R045)*
1. Leggi l **APERTURA più recente** in `docs/` (`APERTURA-F<nnn>.md`, il numero più alto): dice da dove si riparte e il prompt (§ 11).
2. Aggancia il nodo `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) e segui il suo `CLAUDE.md`: ⛔ **`python3 strumenti/apertura-nodo.py --prodotto formazione`** (lettura dei messaggi, risposte, `--segna`; alla chiusura `--chiusura` deve essere VERDE).
3. Leggi `prodotti.md`, la mappa `MAPPA-FAMIGLIA-RELDOX.html` e la SPEC della chat in `spec/`.

## ⛔ I quattro registri (SPEC-REGISTRI v2 del nodo, dal 18/11/2026 F009)
| registro | file |
|---|---|
| BUG | `docs/BUG-FORMAZIONE.md` |
| PUNTI APERTI | `docs/ROADMAP.md` (la roadmap stessa, voci F<n>, solo aperte e tutte con stelle); chiuse in `docs/PUNTI-CHIUSI-FORMAZIONE.md` |
| VIOLAZIONI | `docs/VIOLAZIONI-FORMAZIONE.md` |
| FALLIMENTI | `docs/FALLIMENTI-FORMAZIONE.md` |

Si leggono all apertura (regole dei fallimenti, ultime 5 violazioni, bug aperti, voci aperte) e lo si dice all owner in una riga «Registri letti: …»; si scrivono **subito**, nello stesso commit del primo gesto; nessuna scheda si cancella; la riga di testa di ogni registro e la riga «Registri: …» in testa a `bacheca/formazione/STATO.md` del nodo si aggiornano a ogni cambio. *(owner 17/11/2026 18:35, chat RelDox CRD S714)* ⛔ La riga «Registri» del nodo **non si scrive a mano**: si copia dall uscita di `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md`, che deve essere VERDE a ogni consegna e prima di ogni pubblicazione sul nodo; le voci nate sul ramo di sessione si contano dal ramo. *(fallimento F030-F1, 23/11/2026)*

## Dove sta ogni cosa
| cartella | contenuto |
|---|---|
| `docs/` | aperture delle sessioni, `ROADMAP.md`, `DECISIONI-OWNER.md`, `REGISTRO-VERSIONI-MATERIALI.md`, `STRUTTURA.md` |
| `modelli/` | modello di lezione, di capitolo del manuale, di scheda rapida |
| `fonti/`, `glossario/`, `manuali/`, `corsi/`, `guida-amministratore/`, `schede/`, `faq/` | si creano col primo materiale, come dice `docs/STRUTTURA.md` (F001); ogni materiale nella sottocartella di lingua `it/` |
| `risorse/` | logo di famiglia (copia da `grafica/loghi/` del nodo), immagini con dati inventati |

## Regole di sempre
- Solo **italiano**; ogni documento interno con il logo e «Documento riservato · non divulgabile».
- Si scrive **solo di funzioni in produzione** (smoke verde, `novita/` del nodo); niente nomi di clienti né di persone del cliente, niente nomi interni (sigle, BUG, palchi, numeri di voce) nei materiali per l esterno; schermate solo da ambienti di prova o dati inventati.
- Ogni materiale ha versione, data, **versione del prodotto a cui vale** e registro delle modifiche; le scelte dell owner citate con data, ora e chat.
- Nel nodo si scrive **solo in `bacheca/formazione/`**; testi legali a Legale, pubblicazione al Marketing, il codice degli altri prodotti alle loro chat (il nostro solo codice è RelDoxAcademy).
- ⛔ **Prima la funzionalità**: finché RelDoxAcademy non si installa e funziona, niente domande all owner su dettagli; si annotano nell APERTURA e si portano quando c è qualcosa da provare. Rapporti brevi e concreti. *(owner 18/11/2026 10:05, chat Formazione F011: «Serve la funzionalità»; fallimento F011-F1)* Prima di copiare un file ci si chiede se serve a installare il plugin; il prompt per la sessione dopo sta in 5 righe, senza sigle. *(owner 18/11/2026 11:42, chat Formazione F012; fallimento F012-F1)*
- Una cosa alla volta: si chiude un argomento prima di aprire il successivo; prima di un materiale nuovo, il suo indice o mockup all owner.
- ⛔ **Revisione a freddo** (REGOLE-FAMIGLIA v5 § 7 del nodo; owner 20/11/2026 06:55, chat RelDox CRD S724: *«Sì, approvo (consigliata)»*): prima di consegnare all owner o di pubblicare sul nodo, un agente separato che non ha visto il lavoro rivede la consegna quando tocca la sicurezza (una voce della riga di sicurezza) o porta all owner numeri da cui dipende una sua decisione; riceve consegna e fonti, non il ragionamento; si consegna con **0 alti e 0 medi**: i bassi e i dubbi si correggono nello stesso giro se possibile, altrimenti diventano voce con stelle in `docs/ROADMAP.md` per la versione successiva (REGOLE-FAMIGLIA v8 § 7; owner 23/11/2026 09:13, chat RelDox CRD S741: *«Sì, a tutti (consigliata)»*, `CRD-R042`). Nel messaggio di consegna: «Revisione a freddo: n difetti trovati (a alti, m medi, b bassi, d dubbi), n corretti, n in registro (voce …)» oppure «Revisione a freddo: non dovuta».
- ⛔ **Codice delle richieste** (`spec/REGOLE-DIALOGO-FAMIGLIA-RELDOX-v7.md` § 3 e § 3.1 del nodo; owner 21/11/2026 08:12, chat RelDox CRD S731: *«Sì, approvo (consigliata)»*, *«Sì, 36 ore (consigliata)»*): ogni nostra richiesta in bacheca porta `**Codice:** FOR-R<nnn>` (anche nel titolo, `— [FOR-R<nnn>] <tema>`) e `**Ferma il lavoro:** sì — <che cosa> | no`; ogni risposta `**Risponde a:** <codice> · \`bacheca/…\``; chi prende in carico scrive `**Voce:** <voce> (<stelle>)`. Il numero libero lo dice `strumenti/verifica-bacheca.py --solo formazione`.
- ⛔ **Misura e classifica delle sorelle** (`spec/REGOLE-FAMIGLIA-RELDOX-v7.md` § 10 bis del nodo; owner 22/11/2026 19:31, chat RelDox CRD S738: *«Sì, approvo (consigliata)»*): siamo misurati con l efficienza = voci chiuse su voci nuove negli ultimi 7 giorni; la classifica di R-D la mostra `apertura-nodo.py` (sezione «0 bis»). ⛔ **Si sale lavorando meglio, mai mentendo**: nessuna voce chiusa se non è fatta, nessuna voce spezzata, accorpata o tolta per cambiare il conto, ogni chiusura con la sua data e intera in `docs/PUNTI-CHIUSI-FORMAZIONE.md`.
- ⛔ **A ogni kit il prompt completo** (`spec/REGOLE-FAMIGLIA-RELDOX-v10.md` § 10 quater del nodo; owner 24/11/2026 13:40, chat RelDox CRD S748: *«alla generazione del kit deve essere prodotto sempre il prompt completo da allegare in chat»*, *«una nuova regola insindacabile per tutte le chat»*; CRD-R049): a ogni kit, senza che l owner lo chieda, il prompt completo della sessione seguente **per intero nel messaggio di consegna**, in un solo blocco da copiare, e come file `PROMPT-F<nnn>.md` accanto al kit e nella busta 1; il testo è quello del § 11 dell APERTURA, lo scrive `banco/F013-kit.py` (mai a mano) e la verifica del kit è ROSSA se manca, è diverso o non è completo (repository e ramo, versione di `main` e del ramo, installato e da collaudare, file da leggere per primi, nodo e comandi d apertura, lavoro in ordine, regole, file da allegare). Supera «il prompt sta in 5 righe, senza sigle» (F012): resta «si capisce da solo». Un kit senza prompt non è consegnato: violazione.
- Commit col nome di sessione (`Formazione F001: …`).
