<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Registro della lettura e della copia — blocco G di RelDoxAcademy

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Aperto da Formazione F005, 17/11/2026, al «genera» dell owner (15:28, D30).

Fonte: RelDoxFood `main` 1.24.11L (5e4a40c), letta e mai cambiata. Regola: ogni file si legge **per intero, riga per riga**, prima di copiarlo (`REGOLE-FAMIGLIA-RELDOX-v4` § 2); poi si copia con `strumenti/copia-con-segno.py`, che scrive qui la riga. Le decisioni sono quelle dell allegato A dell analisi tecnica ed. 4; ciò che la lettura trova di diverso va in «Differenze» e in un edizione 5 prima di andare avanti.

Banchi: `banco/F005-G-banchi-copia.py` (G0, G1, G2, G10). Rosso prima: `banco/esiti/F005-G-rosso-prima.txt` (0 verdi, 4 rossi, 17/11/2026).

## Differenze trovate leggendo
- **F006 · 17/11 · `public/views/preferenze.php`** — l allegato A dice TIENI, ma il file ha il ramo «I miei dati» del ristoratore (dietro `class_exists( Rdfood_Miei_Dati )`, classe TOLTA) e usa 29 chiavi `dati.*` del gestore delle lingue. → **TIENI, ridotto** nell analisi **edizione 5**. Le chiavi `dati.*` restano nella copia del gestore delle lingue finché `preferenze.php` non è letto e ridotto.

- **F007 · 17/11 · `includes/security/class-rdfood-password-token.php`** — l allegato A dice TIENI, ma il file ha lo scopo `f` del foglio di accesso stampato (`Rdfood_Foglio_Accesso`, classe TOLTA; chiamata dietro `class_exists`, ripiego a 30 giorni); il banco G1 lo trova. → **TIENI, ridotto** nell analisi **edizione 6**: via lo scopo `f`. Nella fonte `conteggi()` conta i gettoni `f:` come «in chiaro» (difetto di RelDoxFood, da segnalare; da noi sparisce con lo scopo).

## File letti e copiati
| quando | file in RelDoxFood | righe | decisione | MD5 della fonte | nota |
|---|---|---|---|---|---|
| 17/11 15:53 | `reldoxfood/reldoxfood.php` | 525 | RIFAI | `691cd0e1de` | letto per intero; intestazione, costanti, carichi riscritti; storia delle versioni non copiata |
| 17/11 15:54 | `reldoxfood/includes/class-rdfood-db-config.php` | 166 | TIENI | `d9133d7900` | letto per intero; generico, nessun cambiamento oltre il segno |
| 17/11 15:54 | `reldoxfood/includes/class-rdfood-db.php` | 676 | TIENI | `2747420e82` | letto per intero; generico (connessione UTC, dead-man, ultimo errore); solo il segno |
| 17/11 15:54 | `reldoxfood/includes/class-rdfood-option.php` | 567 | TIENI | `27214fe483` | letto per intero; archivio di stato sul BASE, generico; solo il segno |
| 17/11 15:54 | `reldoxfood/includes/helpers.php` | 245 | TIENI | `e3d856647e` | letto per intero; icone, identità, etichetta del pacchetto, freno, loghi e marchio delle porte; il lockup punta a public/img/logo/reldox-academy-lockup.png (marchio Academy, RIFAI con le immagini) |
| 17/11 15:54 | `reldoxfood/includes/class-rdfood-schema-flag-guard.php` | 222 | TIENI | `885a790586` | letto per intero; guardia dei flag, mappa del nucleo e dell ancora di licenza; solo il segno |
| 17/11 15:54 | `reldoxfood/includes/class-rdfood-provisioning-guard.php` | 250 | TIENI | `bba310e48c` | letto per intero; i flag si riconoscono dal prefisso del segno, che cambia insieme ai valori delle costanti; solo il segno |
| 17/11 15:54 | `reldoxfood/includes/class-rdfood-roles.php` | 69 | TIENI | `a47b92e921` | letto per intero; permessi per modulo e livello; solo il segno |
| 17/11 16:01 | `reldoxfood/includes/class-rdfood-lingua.php` | 1572 | TIENI, ridotto | `8bd5b0d277` | letto per intero; tolte le chiavi della ristorazione (TESTI e NOTIFICHE), restano accesso, recupero, password, cornice, icona, mail di sicurezza, porta; restano per ora le dati.* usate da preferenze.php |
| 17/11 16:02 | `reldoxfood/includes/class-rdfood-activator.php` | 365 | TIENI, ridotto | `1cf0137aea` | letto per intero; tolti lo spegnimento degli invii del registro (ristorazione) e l aggancio «versione di prima» delle novità per l integratore; tabelle del nucleo e semi uguali |
| 17/11 16:02 | `reldoxfood/includes/security/class-rdfood-security.php` | 194 | TIENI | `c8fe1e717e` | letto per intero; autorità unica su utente e amministratore, solo sessione del prodotto; solo il segno |
| 17/11 16:02 | `reldoxfood/includes/security/class-rdfood-rest-auth.php` | 74 | TIENI | `c81fe0d5c8` | letto per intero; permesso REST solo sessione del prodotto; resta il 403 all integratore (difesa, la colonna c è nello schema); solo il segno |
| 17/11 16:02 | `reldoxfood/includes/class-rdfood-auth.php` | 732 | TIENI, ridotto | `b0744d4797` | letto per intero; tolto il ramo dell email condivisa (scheda della mensa + amministratore): due utenti con la stessa email = ambiguo; sessione, blocco, impronte e cookie uguali |
| 17/11 17:00 | `reldoxfood/includes/class-rdfood-user-prefs.php` | 161 | TIENI | `b5e88ad624` | letto per intero; porta unica sulle preferenze personali, chiave d esempio tema; il segno e, nel commento, l indirizzo /academy/ |
| 17/11 17:01 | `reldoxfood/includes/security/class-rdfood-password-policy.php` | 279 | TIENI | `519c742430` | letto per intero; regola unica della password (lunghezza, parole personali, banali), nessun database; le chiavi pwd.* ci sono nella copia del gestore delle lingue; solo il segno |
| 17/11 17:01 | `reldoxfood/includes/security/class-rdfood-password-token.php` | 329 | TIENI, ridotto | `4b41684dde` | letto per intero; impronta SHA-256 dei gettoni, consumo che chiude le sessioni; tolto lo scopo f del foglio di accesso stampato (classe TOLTA) — ed. 6; nella fonte conteggi() conta i f: come in chiaro, da segnalare a RelDoxFood |
