<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F002 — Manualistica e formazione · seconda sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F001, aggiornata il 17/11/2026 alle 10:35. Ramo di partenza: `main`.

## §00 — DA DOVE SI RIPARTE
F001 (15-17/11/2026) ha fatto, tutto in `main`:
- **Avvio**: struttura (`docs/STRUTTURA.md`), roadmap F1-F21, decisioni dell owner **D1-D24** (`docs/DECISIONI-OWNER.md`), inventario delle fonti (`fonti/INVENTARIO.md`).
- **Stile** approvato (`docs/STILE-DEI-MATERIALI-v1.md` 1.1): «fai da solo», **prima l immagine**, tutti i dispositivi; modello di lezione a otto elementi (`modelli/MODELLO-LEZIONE.md`); lezione d esempio approvata (`mockup/MOCKUP-LEZIONE-ESEMPIO-v1.html`).
- **RelDoxAcademy** (F9): SPEC 1.10 (`docs/SPEC-RELDOXACADEMY-v1.md`); sito su `reldoxacademy.it`, **hosting separato** (WordPress 7.1.4, PHP 8.3); mockup 0.1.01A (S1-S6) approvato per ora; mockup «Chi sei?» con Iris v1 (`mockup/MOCKUP-CHI-SEI-IRIS-v1.html`, all owner); **Iris** approvata (`docs/IRIS-v1.md`; pose in `risorse/assistente/`, versioni web in `risorse/assistente/web/`); logo in `risorse/logo-academy/`.
- **RelDoxFood** (F6): indice **approvato** in quattro percorsi (`corsi/reldoxfood/INDICE.md` 2.1): 1 Configurare (Amministratore) · 2 Rilasciare i QR (Reception) · 3 Mangiare (Operatore) · 4 La cassa (Ristoratore).
- **Glossario** v1 (`glossario/GLOSSARIO-FAMIGLIA-RELDOX.md`).
- **RelDox Chiavi**: pagine 2, 3, 5, 6 della Documentazione alla 1.0.07 (`manuali/reldox-chiavi/`).

**Aperto nel nodo** (`bacheca/formazione/STATO.md`, «Aspetto da»): RelDoxChecks e RelDoxPortal (inventario, dal 15/11); RelDox CRD (elenco delle funzioni di base, voce 156; identificativo dell azienda nella chiave, voce 159, per la proposta «Accesso ai corsi» F18); Sicurezza-Cyber (parere sull accesso firmato e sulla conservazione; verifica del sito dopo i **quattro punti** dell owner: errori PHP nascosti, `DISALLOW_FILE_EDIT`, temi inattivi via, aggiornamenti automatici — alcuni già fatti, 17/11 10:17); Legale (L4, L15).

## §01 — CHE COSA FARE, IN ORDINE
0. Nodo: `git fetch --depth=200 origin main`, `python3 strumenti/apertura-nodo.py --prodotto formazione` (VERDE); regole del dialogo **v6** (riga «Installato da collaudare» nello `STATO.md`); leggi, rispondi, `--segna`.
1. Se l owner non l ha ancora fatto: parere sul mockup «Chi sei?» con Iris.
2. **Lezioni del percorso 1 «Configurare RelDoxFood»** (Amministratore), nello stile approvato, una alla volta: 1.1 La mappa dell applicazione → 1.9. Fonte: manuale Amministratore di RelDoxFood **1.24.11L** (`reldoxfood/manuali/amministratore/01-15.html` del ramo `main`, sola lettura) e `novita/reldoxfood.md`; parole dal glossario. Figure: disegni SVG annotati (come la lezione d esempio) finché RelDoxFood non conferma che le sue immagini sono da dati inventati.
3. Ogni lezione: provata su computer, tablet e telefono; registrata in `docs/REGISTRO-VERSIONI-MATERIALI.md`.

## §11 — PROMPT PER LA SESSIONE F002
> Parti dal repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `main`: leggi `CLAUDE.md` e `docs/APERTURA-F002.md`. Aggancia anche `ideatechnologiessrl-IT/RelDox-Famiglia` (prima `git fetch --depth=200 origin main`) ed esegui `python3 strumenti/apertura-nodo.py --prodotto formazione`. Sei la chat Manualistica e formazione, sessione F002. Leggi i messaggi nuovi, poi riparti dalle lezioni del percorso 1 «Configurare RelDoxFood» (`corsi/reldoxfood/INDICE.md`), nello stile di `docs/STILE-DEI-MATERIALI-v1.md` con Iris (`docs/IRIS-v1.md`). Solo italiano; una cosa alla volta; al 70% del contesto dichiaralo, al 75% chiudi la sessione con l APERTURA F003.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
