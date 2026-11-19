# Manualistica e formazione — regole di lavoro per Claude

> ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Questo repository è della chat **Manualistica e formazione** della famiglia RelDox: manuali d uso dei prodotti, guida dell amministratore, corsi on line per ruolo, schede rapide, domande frequenti, glossario di famiglia. Compiti e metodo: `spec/SPEC-CHAT-MANUALISTICA-FORMAZIONE-FAMIGLIA-RELDOX-v3.md` del nodo (la tiene il capo famiglia, la chat RelDox CRD). Istituita dall owner il 15/11/2026 08:47 (chat RelDox CRD S705).

**RelDoxAcademy** — il plugin dei corsi on line della famiglia è **nostro** e vive in questo repository (SPEC v3 § 2 bis, manuali dei prodotti § 2 ter; owner 15/11/2026 16:08, chat RelDox CRD S705, e 16:09, chat Formazione F001). Segue le regole dei prodotti: versioni con stadio, banco rosso → verde, mockup prima di ogni interfaccia, smoke dell owner, kit; all unione in `main` scheda in `novita/reldoxacademy.md`, riga in `prodotti.md`, riga di sicurezza nel `STATO.md`. Anagrafiche e stato del canone da RelDox CRD con le API di famiglia (`…-a-reldox-crd-academy-<tema>.md`). Il codice andrà in `reldox-academy/` (cartella da creare con la prima versione, dopo SPEC e mockup approvati).

## Prima di tutto, in ordine
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

Si leggono all apertura (regole dei fallimenti, ultime 5 violazioni, bug aperti, voci aperte) e lo si dice all owner in una riga «Registri letti: …»; si scrivono **subito**, nello stesso commit del primo gesto; nessuna scheda si cancella; la riga di testa di ogni registro e la riga «Registri: …» in testa a `bacheca/formazione/STATO.md` del nodo si aggiornano a ogni cambio. *(owner 17/11/2026 18:35, chat RelDox CRD S714)*

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
- Commit col nome di sessione (`Formazione F001: …`).
