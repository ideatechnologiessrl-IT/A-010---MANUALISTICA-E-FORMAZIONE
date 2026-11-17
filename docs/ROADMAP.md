<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# Roadmap della Manualistica e formazione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Aggiornata 15/11/2026, Formazione F001. Le voci nuove vanno in coda; la priorità la decide l owner. Una cosa alla volta: prima di un materiale nuovo, il suo indice all owner.

| n. | voce | da dove nasce | stato |
|---|---|---|---|
| F1 | Struttura del repository (`docs/STRUTTURA.md`), modelli, `ROADMAP.md`, registro delle versioni | SPEC § 6, istituzione 15/11/2026 | fatta in F001, approvata «per ora» (D7, 15:51) |
| F2 | Domande all owner: piattaforma, pubblico, prezzo, attestato, lingue | owner 15/11/2026 08:47 | chiusa in F001 (`DECISIONI-OWNER.md` D1-D5) |
| F3 | Inventario di ciò che esiste per ogni prodotto (`novita/`, SPEC, «Documentazione» dei plugin, testi del sito) | SPEC § 6, D7 | v1 fatta in F001 (`fonti/INVENTARIO.md`); domande aperte alle quattro chat di prodotto |
| F4 | Glossario di famiglia, prima versione | SPEC § 6 | da fare |
| F5 | Manuale d uso di RelDox CRD: indice all owner, poi i primi capitoli sulle funzioni in produzione | SPEC § 6, `novita/reldox-crd.md` | da fare |
| F6 | **RelDoxFood in RelDoxAcademy**: «Chi sei?» con l assistente e quattro percorsi nell ordine dell avvio (configurare · rilasciare i QR · mangiare · la cassa del ristorante) | SPEC § 6, D2, D16, D21 | indice v2 all owner (`corsi/reldoxfood/INDICE.md`); assistente chiesto al Marketing |
| F7 | Guida dell amministratore: installazione, licenza d uso (testo di Legale), copie, aggiornamenti | SPEC § 6 | da fare |
| F8 | Con Sicurezza-Cyber: modulo di igiene di sicurezza (sei lezioni, primi destinatari gli amministratori dei siti in produzione; contenuto di Sicurezza-Cyber) | SPEC § 6; owner 15/11/2026 14:22, chat Sicurezza-Cyber S3 | approvato, in carico a Sicurezza-Cyber per il contenuto |
| F9 | **RelDoxAcademy**, il prodotto dei corsi on line, tenuto dalla Formazione **dentro questo repository** (D6): registro e SPEC v2 dal capo famiglia; poi requisiti, mockup all owner prima di ogni interfaccia, e le regole dei prodotti (banco, smoke, riga di sicurezza) | D1, D1 bis, D6, D8 | base RelDoxFood (D8, 16:57); **accesso sul nostro server** (D9, 17:02); indice approvato (D13, 18:56); **SPEC v1** `docs/SPEC-RELDOXACADEMY-v1.md` portata a RelDox CRD, Sicurezza-Cyber, Legale; mockup S1, S2, S3, S6 v1 approvato per ora (D15, 16/11 09:16); SPEC 1.3; codice dopo il parere di Sicurezza-Cyber e la forma della chiave |
| F10 | A **Legale**: corsi compresi nel canone (D3) rispetto all art. 9.2 della licenza v3; privacy di chi studia; nessun attestato (D4) | D3, D4 | messaggio a Legale in F001 |
| F11 | **Presa in carico dei manuali esistenti** di RelDoxChecks (8 manuali, 1.3.02A) e RelDoxFood (3 manuali per ruolo in it/en/de, 1.24.11L): fonte unica nel mio repository, i prodotti rigenerano capitoli e PDF alla versione indicata | F3, passaggio del capo famiglia (owner 15/11/2026 14:07, chat RelDox CRD S705) | dopo le risposte di RelDoxChecks e RelDoxFood |
| F12 | Intestazione unica dei materiali di famiglia sul formato già usato da RelDoxChecks (`title`, `slug`, `order`, `audience`, `versione`, `pdf`), da allineare in `modelli/` | F3 | da proporre all owner |
| F13 | Skill per la chat: `accessibility` 🟢 di Sicurezza-Cyber (`docx`, `pdf`, `pptx` già presenti nell app) | `bacheca/sicurezza-cyber/messaggi/2026-11-15-a-formazione-skill-candidate.md`; D10 | 🟢 di Sicurezza-Cyber (16/11): `accessibility` (commit `afa8da9`) e le tre skill WordPress (commit `7374e6f`); da installare con l owner |
| F14 | Pagine dei manuali di RelDoxFood per la **1.25.0L** (gestione avanzata dei pasti: fasce, Colazione · Pranzo · Cena, cassa con le voci), da `novita/reldoxfood.md` dopo il collaudo; testo nelle fonti di RelDoxFood | risposta di RelDoxFood S114, 15/11/2026 | dopo il collaudo della 1.25.0L |
| F15 | Testi dell aiuto della posta e dell Area tecnica di RelDox CRD, da tenere e da indicare alla versione | risposta di RelDox CRD S706, 15/11/2026; schede della posta 3.74.09G del 17/11/2026 | dopo F5 (indice del manuale) |
| F16 | Documentazione di «RelDox Chiavi» 1.0.06: pagine 2, 3 aggiornate e 6 nuova (`manuali/reldox-chiavi/`) | richiesta di RelDox CRD S707, 16/11/2026 | fatte il 16/11; pagina 5 e pagina 6 v2 alla 1.0.07 (16/11); RelDox CRD le copia nella 1.0.08 |
| F17 | RelDoxAcademy: «Stato e assistenza» e «Copia configurazione» nella riga dei due plugin, dalla 0.1.01A | regola dell owner 16/11/2026 08:57, chat RelDox CRD S707 | nella SPEC 1.3 § 14 |
| F18 | Proposta del pezzo comune **«Accesso ai corsi»** al capo famiglia (`…-a-reldox-crd-proposta-accesso-ai-corsi.md`): firma del prodotto, chiave di licenza dentro, destinazione e lingua, pochi minuti, monouso | owner 16/11/2026, chat RelDox CRD S708: «Proposta dalla Formazione» | dopo il formato della chiave con l identificativo dell azienda (voce 159 di RelDox CRD) |
| F19 | **Manuali dei prodotti in RelDoxAcademy** (SPEC della chat v3 § 2 ter): dalla 0.1.02A; si parte da RelDoxFood (fonte unica `banco/S91-42-…`), poi RelDoxChecks | owner 16/11/2026 09:22, chat RelDoxFood S122 | dopo la 0.1.01A in produzione |
| F20 | Testo corretto della pagina «API e collegamenti» di RelDoxFood (capitolo per titolo, non per numero) | RelDoxFood 16/11/2026 | fatto il 16/11 |

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
