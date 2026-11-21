<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F024 — Manualistica e formazione · ventiquattresima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F023, 21/11/2026. Ramo di partenza: `sessione/F023` (0.1.10A da collaudare; `main` = 0.1.09A). Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `docs/DECISIONI-OWNER.md` D43 — che cosa ha approvato l owner e a che ora?
2. `banco/F023-porta-licenza.py` — da quale versione di RelDox CRD viene la licenza e che prodotto porta la chiave?
3. `banco/esiti/F023-licenza-trappola.txt` — quale prova va rossa nella trappola e perché?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F023 (21/11/2026).
- Mockup v2 della «Licenza d uso» approvato (D43, 19:05: *«approvato mockup genera»*).
- **0.1.10A sul ramo `sessione/F023`**: la licenza di RelDox CRD 3.76.03G (voce 150) portata con `banco/F023-porta-licenza.py` (ripetibile, non si cambia a mano): pagina wp-admin «Licenza d uso» (chiave in cima, cinque passi, intestatario, ancora, contratto, firma e trasmissione, accettazione, storico), chiave `reldox-academy` con la stessa chiave pubblica, striscia nell applicazione, sola consultazione D-150 (router, posta salvo recupero password, attività automatiche). Pagine pubbliche dei corsi accese. Diagnostica alzata a 0.1.10 senza altri cambi.
- Banco `banco/F023-banco-licenza.php`: rosso 0/11 sulla 0.1.09A, verde 22/22 sulla 0.1.10A, trappola rossa.
- Da fare: **smoke dell owner** su reldoxacademy.it (installare, aprire «Licenza d uso», incollare una chiave firmata a banco: servono la chiave di prova e il filtro del palco dei banchi, come `banco/S702-4-banco-voce-150.php` del CRD). Allo «smoke ok»: unione in `main`, `novita/`, `prodotti.md`, STATO con «Sicurezza: tocca accesso / permessi / nonce / posta / file».
- Testo del contratto: oggi l EULA v4 copiata dal CRD; Legale deve dire quale testo vale per RelDoxAcademy → richiesta `FOR-R005` a Legale (non ancora scritta).
- Registri: BUG 0/5 · punti aperti 29 · violazioni 6 · fallimenti 3.

## §01 — CHE COSA FARE, IN ORDINE
0. Manifesto, prova di lettura, apertura del nodo, registri letti in una riga.
1. Smoke della 0.1.10A con l owner, un passo alla volta; correzioni sul ramo.
2. Richiesta FOR-R005 a Legale (testo della licenza per RelDoxAcademy).
3. Poi: console SQL nella diagnostica, `REGOLE-RELDOXACADEMY-v1.md` (resto di F29).
4. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F024
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `sessione/F023`: leggi `CLAUDE.md` e `docs/APERTURA-F024.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia` e fai l apertura.
> La 0.1.10A (Licenza d uso) è da provare: guidami nello smoke un passo alla volta.
> Consegna come kit a quattro buste, ogni voce nuova con le sue stelle. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
