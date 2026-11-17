<p><img src="../../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# RelDoxFood in RelDoxAcademy — «Chi sei?» e i quattro percorsi · indice proposto v2

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. v2 · 16/11/2026 · Formazione F001 (voce F6). Indice **approvato** dall owner (17/11/2026 10:17, chat Formazione F001: *«si ok»*). Stile: `docs/STILE-DEI-MATERIALI-v1.md` (fai da solo, prima l immagine, tutti i dispositivi).

**Scelte dell owner** (chat Formazione F001):
- 16/11/2026 09:32: *«reldoxfood che è quello attualmente in corso d'offerta sul secondo cliente il primo lo sta già usando»*;
- **16/11/2026 17:09**: *«i passi per reldoxfood .... 1) primo punto configurazione da parte degli amministratori del sistema del prodotto. 2) formazione di rilascio QR code alle persone e ai ristoratori da parte del personale aziendale 3) formazione utenti aziendale utilizzatori del sistema 4) formazione ristoratore. Io me lo imaginerei con un CHI SEI guidato ..... deve essere come un assistente chiedi al marketing anche la risorsa disegnata del assistente umano che abbiamo fatto»*.

**Vale per**: RelDoxFood **1.24.11L** (in produzione). La 1.25-1.26 (pasti con più fasce, cassa e pagine in veste nuova) è consegnata ma non in produzione: le lezioni toccate si aggiornano col collaudo.
**Fonti**: i tre manuali di RelDoxFood 1.24.11L — Amministratore (15 capitoli), Chi mangia (9), Ristoratore (11) — e le schede di `novita/reldoxfood.md`. Nessuna funzione nuova; dati inventati.

## 0 · L ingresso: «Chi sei?» con l assistente
Chi entra in RelDoxAcademy dai «Corsi» di RelDoxFood trova **Iris**, l assistente — il personaggio disegnato del Marketing (nome: owner 16/11/2026 18:51, chat Marketing S144; pose in `risorse/assistente/`, regole in `risorse/assistente/FONTE.md`) — che fa **una domanda sola**: **«Chi sei?»**, con quattro risposte grandi, ognuna con la sua figura.

| risposta | chi è | percorso |
|---|---|---|
| **«Preparo RelDoxFood per la mia azienda»** | l amministratore del sistema | **1 · Configurare** |
| **«Do gli accessi ai colleghi e ai ristoranti»** | il personale aziendale (ufficio del personale, reception) | **2 · Rilasciare i QR** |
| **«Vado a mangiare con il QR»** | chi usa il servizio | **3 · Mangiare con RelDoxFood** |
| **«Ho un ristorante convenzionato»** | il ristoratore | **4 · La cassa del ristorante** |

- **L assistente sa già molto**: l accesso dal prodotto porta il ruolo (SPEC di RelDoxAcademy § 3.4); se il ruolo è chiaro l assistente propone la risposta («Ciao, sono Iris. Laura, sei amministratore: comincia da qui») e si può cambiarla.
- Dopo la scelta l assistente **accompagna**: mostra il percorso come una strada a tappe, dice «sei qui», alla fine di ogni lezione propone la successiva, riprende da dove eri. Battute di una riga, una posa diversa a ogni momento (come nella serie del Marketing).
- L ordine 1 → 4 è **l ordine dell avvio** presso un cliente: prima si configura, poi si danno gli accessi, poi si mangia e si incassa. L assistente lo ricorda all amministratore («prima di invitare i colleghi, finisci il percorso 1»).

## 1 · Configurare — per l amministratore del sistema (circa 60 minuti)
| lezione | un gesto, un risultato | dal capitolo |
|---|---|---|
| 1.1 La mappa dell applicazione | sai chi fa che cosa e dove lo trova | Amm. 1 |
| 1.2 La prima configurazione | impostazioni una linguetta alla volta, nell ordine giusto | Amm. 2 |
| 1.3 Caricare le anagrafiche | porti dipendenti e ristoratori dal file del gestionale, con la prova a secco | Amm. 3 |
| 1.4 I ristoranti convenzionati | apri, regoli e chiudi una convenzione: orari, giorni, importi | Amm. 8 |
| 1.5 Come decide la cassa | sai spiegare qualsiasi «NON AUTORIZZATO» | Amm. 9 |
| 1.6 Il registro e le statistiche | trovi un pasto in pochi secondi e rispondi a direzione e contabilità | Amm. 10-11 |
| 1.7 I report automatici | imposti una volta gli invii e non ci pensi più | Amm. 12 |
| 1.8 Controllo e sicurezza | tieni sotto controllo il servizio senza cercare | Amm. 14 |
| *per chi ce l ha* 1.9 Integrazione col gestionale | dai all ERP file completi e controllabili | Amm. 13 |
| *verifica e riassunto da tasca* | | |

## 2 · Rilasciare i QR — per il personale aziendale (circa 40 minuti)
| lezione | un gesto, un risultato | dal capitolo |
|---|---|---|
| 2.1 La scheda della persona | abiliti, sospendi o chiudi il servizio a un collega | Amm. 4 |
| 2.2 Consegnare gli accessi | invito per email o foglio stampato, e sai se è arrivato | Amm. 5 |
| 2.3 Mandare «Il mio codice» | il collega riceve il suo QR per email o WhatsApp | Chi mangia 2 |
| 2.4 Dare l accesso al ristoratore | il ristorante attiva la sua cassa | Amm. 8 · Rist. 1 |
| 2.5 Gli ospiti accreditati | accrediti un ospite in un minuto, al banco della reception | Amm. 6 |
| 2.6 Il codice cumulativo per gli ospiti | un gruppo di ospiti mangia con un codice solo | Amm. 7 |
| 2.7 Quando un collega chiama | codice rosso, pasto che non torna: dove guardi e che cosa rispondi | Amm. 9-10, 15 |
| *verifica e riassunto da tasca* | | |

## 3 · Mangiare con RelDoxFood — per chi usa il servizio (circa 45 minuti)
| corso | lezioni | dal capitolo |
|---|---|---|
| 3A Entrare la prima volta | primo accesso con l invito per email · col foglio personale · l icona sul telefono | Chi mangia 1-2 |
| 3B A pranzo, ogni giorno | dove mangiare oggi · alla cassa: il QR verde (lezione d esempio approvata) · le regole del pasto · il menu del giorno | Chi mangia 3-4, 6 |
| 3C Quando qualcosa non va | il codice è rosso · la cassa dice «NON AUTORIZZATO» · password dimenticata e sicurezza | Chi mangia 5, 7 |
| 3D *solo per chi porta ospiti* | il codice cumulativo per te e i tuoi ospiti | Chi mangia 8 |
| *verifica e riassunto da tasca «Apri · Mostra · Se è rosso»* | | |

## 4 · La cassa del ristorante — per il ristoratore (circa 40 minuti)
| lezione | un gesto, un risultato | dal capitolo |
|---|---|---|
| 4.1 L attivazione | entri nella cassa con la tua password | Rist. 1 |
| 4.2 La cassa sul telefono | la apri con un tocco, ogni giorno | Rist. 2 |
| 4.3 Registrare un pasto | cinque passi, meno di cinque secondi | Rist. 3 |
| 4.4 Gli esiti della cassa | sai che cosa fare davanti al rosso | Rist. 4 |
| 4.5 Il codice cumulativo | registri in un colpo il referente e i suoi ospiti | Rist. 5 |
| 4.6 «Hanno mangiato» e i report | i conti tornano, ricevi i dati per la fattura | Rist. 6-7 |
| 4.7 Il menu del giorno | pubblichi il menu partendo da quello di ieri | Rist. 10 |
| 4.8 I miei dati e i messaggi all azienda | tieni giusti i recapiti e avvisi l azienda senza telefonare | Rist. 8-9 |
| *verifica e riassunto da tasca* | | |

## Che cosa serve prima di scrivere
1. ~~Marketing: l assistente~~ fatto: **Iris**, nove pose in `risorse/assistente/`; logo di RelDoxAcademy in `risorse/logo-academy/`.
2. **Glossario** (F4): QR, fascia, trattenuta, convenzione, codice cumulativo (FUZZYCode), referente, ospite accreditato, registro dei pasti.
3. **RelDoxFood**: immagini da dati inventati (domanda c dell inventario) o schermate dall ambiente di prova.
4. **Mockup** del «Chi sei?» e della strada a tappe (SPEC di RelDoxAcademy § 8), all owner prima del codice.

## Registro delle modifiche
| versione | data | che cosa | perché |
|---|---|---|---|
| 2 | 16/11/2026 | quattro percorsi nell ordine dell avvio, ingresso «Chi sei?» con l assistente | owner 16/11/2026 17:09, chat Formazione F001 |
| 1 | 16/11/2026 | indice del solo percorso di chi mangia | owner 16/11/2026 09:32, chat Formazione F001 |

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
