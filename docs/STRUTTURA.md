<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Struttura del repository — v1

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. v1 · 15/11/2026 · Formazione F001 (voce F1 della SPEC § 6). Da approvare dall owner.

## 1 · Le cartelle
| cartella | che cosa contiene | voce |
|---|---|---|
| `docs/` | aperture delle sessioni (`APERTURA-F<nnn>.md`), `ROADMAP.md`, `DECISIONI-OWNER.md`, `REGISTRO-VERSIONI-MATERIALI.md`, questo file | F1 |
| `fonti/` | inventario di ciò che esiste per ogni prodotto (`INVENTARIO.md`) e appunti di lettura delle fonti | F3 |
| `glossario/` | `GLOSSARIO-FAMIGLIA-RELDOX.md`: una parola, una definizione, dove si usa | F4 |
| `manuali/<prodotto>/it/` | manuale d uso, un capitolo per file; una cartella per versione principale quando serve (`v3/`) | F5 |
| `corsi/<percorso>/it/` | un percorso per ruolo; una lezione per file, più verifica finale | F6, F8 |
| `guida-amministratore/it/` | installazione, licenza d uso (testo di Legale), utenti e ruoli, copie, aggiornamenti | F7 |
| `schede/<prodotto>/it/` | schede rapide: una funzione, una pagina | dopo F5 |
| `faq/<prodotto>/it/` | domande frequenti | dopo F5 |
| `modelli/` | modello di lezione, di capitolo, di scheda rapida (intestazione uguale per tutti) | F1 |
| `risorse/` | logo di famiglia (copia da `grafica/loghi/` del nodo), immagini con dati inventati | — |

Prodotti: `reldox-crd`, `reldoxchecks`, `reldoxfood`, `reldoxportal` (gli stessi nomi delle cartelle del nodo). Percorsi: `utente-primi-passi`, `amministratore`, `partner`, `personale-interno`, `igiene-di-sicurezza`.

## 2 · Lingue (D5, owner 15/11/2026, chat Formazione F001: *«Italiano subito, traduzioni dopo»*)
Ogni materiale ha la sottocartella di lingua `it/`. Una traduzione futura è la cartella gemella (`en/`, `de/`) con gli stessi nomi di file, così il registro delle versioni li tiene allineati.

## 3 · Formato dei materiali
Markdown con un intestazione uguale per tutti (vedi `modelli/`): identificativo, titolo, versione del materiale, data, **prodotto e versione a cui vale**, pubblico, obiettivi, durata. I corsi restano in Markdown finché il prodotto dei corsi non c è (D1 bis, voce F9): l intestazione è pensata per essere letta da quel prodotto senza riscrivere le lezioni.

## 4 · Che cosa non entra
Dati di clienti, nomi interni (sigle, BUG, palchi, numeri di voce) nei materiali per l esterno, schermate che non vengano da RelDox.it-testing-LIVE o da dati inventati, funzioni senza smoke verde.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
