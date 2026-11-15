# Manualistica e formazione — regole di lavoro per Claude

> ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Questo repository è della chat **Manualistica e formazione** della famiglia RelDox: manuali d uso dei prodotti, guida dell amministratore, corsi on line per ruolo, schede rapide, domande frequenti, glossario di famiglia. Compiti e metodo: `spec/SPEC-CHAT-MANUALISTICA-FORMAZIONE-FAMIGLIA-RELDOX-v1.md` del nodo (la tiene il capo famiglia, la chat RelDox CRD). Istituita dall owner il 15/11/2026 08:47 (chat RelDox CRD S705).

## Prima di tutto, in ordine
1. Leggi l **APERTURA più recente** in `docs/` (`APERTURA-F<nnn>.md`, il numero più alto): dice da dove si riparte e il prompt (§ 11).
2. Aggancia il nodo `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) e segui il suo `CLAUDE.md`: ⛔ **`python3 strumenti/apertura-nodo.py --prodotto formazione`** (lettura dei messaggi, risposte, `--segna`; alla chiusura `--chiusura` deve essere VERDE).
3. Leggi `prodotti.md`, la mappa `MAPPA-FAMIGLIA-RELDOX.html` e la SPEC della chat in `spec/`.

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
- Nel nodo si scrive **solo in `bacheca/formazione/`**; testi legali a Legale, pubblicazione al Marketing, codice ai prodotti.
- Una cosa alla volta: si chiude un argomento prima di aprire il successivo; prima di un materiale nuovo, il suo indice o mockup all owner.
- Commit col nome di sessione (`Formazione F001: …`).
