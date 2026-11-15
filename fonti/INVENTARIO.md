<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Inventario delle fonti per manuali e corsi — v1

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. v1 · 15/11/2026 · Formazione F001 (voce F3). Letto in sola lettura dai repository dei prodotti (`main` del 15/11/2026) e dal nodo. Le domande aperte sono scritte alle chat di prodotto in bacheca (§ 7).

**Principio dell owner** (15/11/2026 15:51, chat Formazione F001): *«Nulla deve essere definitivo, vige la regola che uno strumento deve essere utile, produttivo e facile da usare da parte del nostro cliente.»*

## 0 · Il quadro in una tabella
| prodotto | versione in `main` | schede `novita/` | manuali per l utente già esistenti | dove | lingue | stato per la formazione |
|---|---|---|---|---|---|---|
| RelDox CRD | 3.74.03G | 18 (dal 06/11/2026) | **nessuno**; un aiuto «Posta» dentro il plugin, l Area tecnica | `RelDox` | it | da scrivere quasi da zero |
| RelDoxChecks | 1.3.02A | 17 (dal 02/11/2026) | **8 manuali per ruolo** dentro il plugin, con PDF e 242 schermate | `RelDoxChecks` | it (questionari in 4 lingue) | ricchi e allineati alla 1.3.02A: da prendere in carico |
| RelDoxFood | 1.24.11L | 16 (dal 07/11/2026) | **3 manuali per ruolo** (amministratore, chi mangia, ristoratore) in capitoli HTML e PDF, più 4 pagine «Documentazione» | `RelDoxFood` | it, en, de | ricchi e allineati alla 1.24.11L: da prendere in carico |
| RelDoxPortal | 0.3.09 Beta | 0 | **nessuno** | `RelDoxPortal` | it | da scrivere da zero, dopo l elenco delle funzioni in produzione |

⛔ **Il limite comune**: `novita/` nasce il 02-07/11/2026 e racconta solo le funzioni **cambiate da allora**. Le funzioni di base, in produzione da prima, non hanno scheda: per loro la fonte sono i manuali già scritti dai prodotti (RelDoxChecks, RelDoxFood) o la conferma della chat di prodotto (RelDox CRD, RelDoxPortal). È la prima domanda a ogni chat (§ 7).

## 1 · RelDox CRD — 3.74.03G Gold
- **Nodo**: `novita/reldox-crd.md`, 18 schede dal 06/11 al 13/11/2026 (sicurezza degli accessi, mappe della sede, richieste demo dal sito, norme e certificazioni nell anagrafica, registro delle mail spedite, albero dei documenti).
- **Repository** (`docs/`, per chi sviluppa): 28 `SPEC-*.md`, con stato misto (lotti chiusi, consegnati, alcuni «nessuna riga costruita», es. `SPEC-TASK-ACCETTAZIONE-A-DUE-LIVELLI-v1`). Due sono già scritte per sito e comunicazione: `SPEC-CATALOGO-FUNZIONI-CLAUDE-v1`, `SPEC-POSTA-E-ARCHIVIAZIONE-MAIL-SITO-E-MARKETING-v1`. `docs/CHANGELOG.md` (17 960 righe) è la storia delle versioni.
- **Dentro il plugin**: `public/views/partials/aiuto-posta.php` (aiuto della posta), `public/views/area-tecnica.php` (Area tecnica e Manutenzione, controlli e «Copia esito»); plugin gemello RelDox Diagnostica.
- **RelDox Chiavi 1.0.04** (`reldox-chiavi/docs/*.md`, cinque pagine): **non è in `main`** del repository RelDox; strumento interno, fuori dai materiali per i clienti. Da localizzare (ramo).
- **Manca**: manuale d uso per ruolo, guida dell amministratore, elenco delle funzioni di base in produzione.

## 2 · RelDoxChecks — 1.3.02A Alfa
- **Nodo**: `novita/reldoxchecks.md`, 17 schede dal 02/11 all 11/11/2026.
- **Dentro il plugin** (`reldoxchecks/docs-content/`), tutti alla **1.3.02A, 11/11/2026**, ciascuno col suo PDF in `docs-content/manuali/`:

| file | per chi | righe |
|---|---|---|
| `10-configurazione-database.md` | amministratore | 119 |
| `20-integrazione-sistemi-aziendali.md` | amministratore / chi integra | 3 670 |
| `30-manuale-master.md` | chi dà il visto ai rilasci | 2 025 |
| `40-manuale-certificatore.md` | chi approva domande e decide gli esiti | 1 270 |
| `50-manuale-redattore.md` | chi prepara i questionari e manda gli inviti | 1 081 |
| `60-manuale-sola-lettura.md` | chi legge e compila | 503 |
| `70-manuale-integratori.md` | software house, sviluppatori | 3 035 |
| `80-manuale-google-form.md` | chi importa un Google Form | 300 |

- Più: 242 schermate (`manuali/img/`), 8 schemi SVG, note di versione in `docs-content/novita/` (1.1.06A-1.3.02A), `docs/MODELLO-MANUALE-INTEGRATORI-v2.md`, `docs/NOVITA-RELDOXCHECKS-1_2_06A-PER-IL-SITO.md`.
- **Da notare**: l intestazione dei file (`title`, `slug`, `order`, `audience`, `versione`, `pdf`) è già un formato che la Formazione può adottare per tutta la famiglia.

## 3 · RelDoxFood — 1.24.11L Live
- **Nodo**: `novita/reldoxfood.md`, 16 schede dal 07/11 al 14/11/2026.
- **Dentro il plugin** (`reldoxfood/manuali/`), alla **1.24.11L**: Amministratore (15 capitoli, solo it), Chi mangia (9 capitoli × it, en, de), Ristoratore (11 capitoli × it, en, de), ognuno con il PDF; `Specifica-integrazione` ed. 8, 9, 10 in PDF; `Guida-sviluppatore-gestionale` **1.13.0B** (più vecchia del prodotto).
- **Documentazione** (`reldoxfood/docs-content/`): configurazione database, API e collegamenti (1.18.0L), i manuali dei ruoli (1.9.0B), il codice personale e i suoi invii (1.10.0B); guida della cassa (`public/views/guida-cassa.php`).
- **Regola già scritta dal prodotto**: `docs/SPEC-MANUALI-IN-LINEA-RELDOXFOOD-v1.md` — una sola fonte, due uscite (capitoli nell app e PDF), manuali rigenerati a ogni pacchetto con la sua versione.

## 4 · RelDoxPortal — 0.3.09 Beta
- **Nodo**: `novita/reldoxportal.md` senza schede.
- **Repository**: `docs/SPEC-RELDOXPORTAL-v18.md` (per chi sviluppa), `docs/CHANGELOG-RELDOXPORTAL.md`, `reldox-portal/LICENZA.txt` (licenza gratuita 1.0, testo di Legale).
- **Manca**: ogni materiale per l utente; l elenco delle funzioni in produzione.

## 5 · Sito e Marketing (`Mkrt-Idea-Technologies`, `main`)
- Pagine prodotto: `sito/01-pagine/04-reldox/`, `06-reldoxfood/`, `09-reldoxportal/`, `10-scarica-reldoxportal/`, `12-reldoxchecks/`, `07-demoreldox/` (HTML e schede).
- `prodotto/FUNZIONI-RACCONTABILI.md`: le funzioni raccontabili (stessa forma di `novita/`).
- Flyer di RelDoxFood e RelDoxChecks: ramo `sito/reldox-S15a`, `sito/03-marketing/flyer/` (messaggio del Marketing del 12/11/2026).
- Nessun tutorial o «come si fa» trovato in `main`.

## 6 · Dagli altri
- **Sicurezza-Cyber**: modulo di igiene di sicurezza approvato dall owner (15/11/2026 14:22, chat Sicurezza-Cyber S3), sei lezioni, primi destinatari gli amministratori dei siti in produzione; proposta in `proposte/2026-11-15-modulo-igiene-sicurezza-utenti.md` del suo repository (voce F8). Skill candidate per la Formazione: `audit/skill/2026-11-15-formazione-candidate.md`.
- **Legale**: nulla da passare (`bacheca/legale/messaggi/2026-11-15-a-reldox-crd-manuali-e-formazione-alla-chat-formazione.md`).

## 7 · Domande alle chat di prodotto (livello 2, in bacheca)
| chat | messaggio | stato |
|---|---|---|
| RelDox CRD | `bacheca/formazione/messaggi/2026-11-15-a-reldox-crd-inventario-manuali.md` | in carico: elenco delle funzioni di base = voce 156 di RelDox CRD; RelDox Chiavi nel ramo `sessione/S706` (1.0.04, la 1.0.05 in lavorazione); aiuto della posta e testi dell Area tecnica passano a noi |
| RelDoxChecks | `…-a-reldoxchecks-inventario-manuali.md` | aperto |
| RelDoxFood | `…-a-reldoxfood-inventario-manuali.md` | in carico: fonte unica `banco/S91-42-manuali-sorgenti-1_24_1L.zip` (una fonte Python per manuale e lingua), composizione con lo script del banco della versione; en e de allineati; Guida sviluppatore 1.13.0B ancora valida; ⚠ la 1.25.0L (gestione avanzata dei pasti) non ha pagine nei manuali: le scriviamo noi dopo il collaudo; passaggio completo = loro voce SV-60 |
| RelDoxPortal | `…-a-reldoxportal-inventario-manuali.md` | aperto |

## Registro delle modifiche
| versione | data | che cosa | perché |
|---|---|---|---|
| 1.1 | 15/11/2026 | risposte di RelDox CRD e RelDoxFood (§ 7) | messaggi in bacheca del 15/11/2026 |
| 1 | 15/11/2026 | prima stesura | voce F3; owner 15/11/2026 15:51, chat Formazione F001: *«procedi con inventario su tutti i prodotti e dividi per prodotto»* |

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
