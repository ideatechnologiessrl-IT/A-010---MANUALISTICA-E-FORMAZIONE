<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F028 — Manualistica e formazione · ventottesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F027, 23/11/2026. Ramo di partenza: `sessione/F027` (= 0.1.12A, console SQL corretta, **manca la terza revisione a freddo: BUG-6**); `main` = 0.1.10A. Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0026 — 0.1.12A — F028

PROGRESSIVO K0026   precedente K0025   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0026-0_1_12A-F028.zip · composto il 23/11/2026 14:45
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0026-0_1_12-F028.zip
         reldox-academy-0_1_12.zip  md5 3262681e6f547458dffdb12e41703a4c
         103 file · 23 cartelle · 0 cartelle vuote
         MD5-0_1_12.txt  103 righe
         APERTURA-F028.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: assente (non ancora scritto, banco G10 rosso)
BUSTA 2  KIT-2-AREA-PROGETTI-K0026-0_1_12-F028.zip  45 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0026-0_1_12-F028.zip  78 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  310 righe
         banco/F013-rifai-0.1.01A.sh  36 righe
         banco/F013-rifai-0.1.02A.sh  39 righe
         banco/F014-rifai-0.1.03A.sh  39 righe
         banco/F016-banco-grafica.py  146 righe
         banco/F016-rifai-0.1.04A.sh  39 righe
         banco/F017-banco-riga-plugin.py  62 righe
         banco/F017-banco-stato-sistema.py  130 righe
         banco/F017-rifai-0.1.05A.sh  39 righe
         banco/F017-rifai-0.1.06A.sh  39 righe
         banco/F018-banco-indurimento.php  181 righe
         banco/F018-rifai-0.1.07A.sh  39 righe
         banco/F019-banco-interruttori.php  128 righe
         banco/F019-rifai-0.1.08A.sh  39 righe
         banco/F021-rifai-0.1.09A.sh  66 righe
         banco/F023-banco-licenza.php  101 righe
         banco/F023-porta-licenza.py  189 righe
         banco/F023-rifai-0.1.10A.sh  66 righe
         banco/F026-banco-console-sql.php  222 righe
         banco/F026-rifai-0.1.11A.sh  69 righe
         banco/F026-sabotaggi-console-sql.py  43 righe
         banco/F027-banco-console-sql.php  344 righe
         banco/F027-rifai-0.1.12A.sh  69 righe
         banco/F027-sabotaggi-console-sql.py  68 righe
         banco/KIT-PROGRESSIVO.txt  25 righe (nella busta; la riga di questo kit si aggiunge dopo la verifica)
         banco/esiti/F005-G-rosso-prima.txt  5 righe
         banco/esiti/F006-G-parziale.txt  7 righe
         banco/esiti/F007-G-parziale.txt  7 righe
         banco/esiti/F008-G-accesso-emergenza.txt  7 righe
         banco/esiti/F008-G-carichi.txt  7 righe
         banco/esiti/F008-G-docs.txt  7 righe
         banco/esiti/F008-G-licenza.txt  7 righe
         banco/esiti/F008-G-parziale.txt  7 righe
         banco/esiti/F008-G-router.txt  7 righe
         banco/esiti/F009-G-parziale.txt  7 righe
         banco/esiti/F010-G-parziale.txt  7 righe
         banco/esiti/F011-G-parziale.txt  7 righe
         banco/esiti/F012-G-parziale.txt  9 righe
         banco/esiti/F014-G-parziale.txt  7 righe
         banco/esiti/F014-prima-dopo-computer.png  0 righe
         banco/esiti/F014-prima-dopo-telefono.png  0 righe
         banco/esiti/F016-grafica-rosso-0.1.03A.txt  89 righe
         banco/esiti/F016-grafica-verde-0.1.04A.txt  97 righe
         banco/esiti/F016-prima-dopo-computer.png  0 righe
         banco/esiti/F016-prima-dopo-telefono.png  0 righe
         banco/esiti/F017-riga-plugin-0.1.06A.png  0 righe
         banco/esiti/F017-riga-plugin-rosso-0.1.05A.txt  4 righe
         banco/esiti/F017-riga-plugin-verde-0.1.06A.txt  9 righe
         banco/esiti/F017-stato-sistema-0.1.04A.png  0 righe
         banco/esiti/F017-stato-sistema-0.1.05A-telefono.png  0 righe
         banco/esiti/F017-stato-sistema-0.1.05A.png  0 righe
         banco/esiti/F017-stato-sistema-rosso-0.1.04A.txt  35 righe
         banco/esiti/F017-stato-sistema-verde-0.1.05A.txt  35 righe
         banco/esiti/F018-indurimento-rosso-0.1.06A.txt  4 righe
         banco/esiti/F018-indurimento-trappola.txt  3 righe
         banco/esiti/F018-indurimento-verde-0.1.07A.txt  18 righe
         banco/esiti/F019-interruttori-rosso-0.1.07A.txt  8 righe
         banco/esiti/F019-interruttori-trappola.txt  5 righe
         banco/esiti/F019-interruttori-verde-0.1.08A.txt  29 righe
         banco/esiti/F021-G-diagnostica-rosso-0.1.08A.txt  7 righe
         banco/esiti/F021-G-diagnostica-verde-0.1.09A.txt  12 righe
         banco/esiti/F023-licenza-rosso-0.1.09A.txt  13 righe
         banco/esiti/F023-licenza-trappola.txt  23 righe
         banco/esiti/F023-licenza-verde-0.1.10A.txt  23 righe
         banco/esiti/F026-console-sql-rosso-0.1.10A.txt  10 righe
         banco/esiti/F026-console-sql-sabotaggi.txt  19 righe
         banco/esiti/F026-console-sql-trappola.txt  55 righe
         banco/esiti/F026-console-sql-verde-0.1.11A.txt  54 righe
         banco/esiti/F027-console-sql-rosso-0.1.11A.txt  102 righe
         banco/esiti/F027-console-sql-sabotaggi.txt  43 righe
         banco/esiti/F027-console-sql-trappola.txt  83 righe
         banco/esiti/F027-console-sql-verde-0.1.12A.txt  82 righe
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
BUSTA 4  KIT-4-AGGIUNTA-K0026-0_1_12-F028.zip  64 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 10 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (45):
  CLAUDE.md
  README.md
  docs/ANALISI-TECNICA-RELDOXACADEMY.md
  docs/APERTURA-F001.md
  docs/APERTURA-F002.md
  docs/APERTURA-F003.md
  docs/APERTURA-F004.md
  docs/APERTURA-F005.md
  docs/APERTURA-F006.md
  docs/APERTURA-F007.md
  docs/APERTURA-F008.md
  docs/APERTURA-F009.md
  docs/APERTURA-F010.md
  docs/APERTURA-F011.md
  docs/APERTURA-F012.md
  docs/APERTURA-F013.md
  docs/APERTURA-F014.md
  docs/APERTURA-F015.md
  docs/APERTURA-F016.md
  docs/APERTURA-F017.md
  docs/APERTURA-F018.md
  docs/APERTURA-F019.md
  docs/APERTURA-F020.md
  docs/APERTURA-F021.md
  docs/APERTURA-F022.md
  docs/APERTURA-F023.md
  docs/APERTURA-F024.md
  docs/APERTURA-F025.md
  docs/APERTURA-F026.md
  docs/APERTURA-F027.md
  docs/APERTURA-F028.md
  docs/BUG-FORMAZIONE.md
  docs/DECISIONI-OWNER.md
  docs/FALLIMENTI-FORMAZIONE.md
  docs/IRIS-v1.md
  docs/PUNTI-CHIUSI-FORMAZIONE.md
  docs/REGISTRO-LETTURA-BLOCCO-G.md
  docs/REGISTRO-VERSIONI-MATERIALI.md
  docs/ROADMAP.md
  docs/SPEC-RELDOXACADEMY-INDICE-v1.md
  docs/SPEC-RELDOXACADEMY-v1.md
  docs/STILE-DEI-MATERIALI-v1.md
  docs/STRUTTURA.md
  docs/VALUTAZIONE-INSTALLAZIONE-RELDOXACADEMY-v1.md
  docs/VIOLAZIONI-FORMAZIONE.md
BASELINE 0.1.12 · stadio Alfa · ultima installata sul sito: 0.1.03A (reldoxacademy.it, 19/11/2026)
         registro BUG: REGISTRO BUG · 23/11/2026 F026 · aperti 1 · cornice 1/5 · primo libero BUG-7
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `docs/ROADMAP.md` r.45 (F43) — da quale codice di richiesta nasce e in quale versione entra?
2. `docs/BUG-FORMAZIONE.md` r.31 (BUG-6) — quanti difetti ha trovato la seconda revisione a freddo e quanti ne sono stati corretti?
3. `banco/esiti/F027-console-sql-sabotaggi.txt` r.43 — quanti sabotaggi fanno tornare rosso il banco?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F027 (23/11/2026).
- Manifesto K0025 confrontato (area 44/44, plugin 103 file · 23 cartelle · 0 vuote, 0.1.11, busta 3 identica); prova di lettura F027 risposta.
- Nodo: apertura VERDE, 1 messaggio letto e segnato: **LEG-R019** di Legale (risponde al nostro FOR-R005, ora chiuso): per RelDoxAcademy non vale la v4 ma le «Condizioni d uso di RelDoxAcademy v1», codice `06309F491D8F522E`. Risposta «ricevuto» con la voce nuova **F43 (★★★)**, entra nella **0.1.13A**. Commit del nodo `7989a06`.
- **BUG-6** (console SQL): banco `banco/F027-banco-console-sql.php` (sezioni G, G bis, H: apici inversi in chiaro, `db.tabella`, viste, trigger, funzioni, sequenze, schema mysql, utente, segreti con alias/UNION/WITH, errori ripuliti, gettone atomico, registro con trigger, porta AJAX chiamata davvero, multisite, interfaccia del mockup) **rosso sulla 0.1.11A 47/23**, motore corretto, **verde 70/70 sulla 0.1.12A**, trappola rossa, **41 sabotaggi su 41**. Insieme F42 (a-e). Pacchetto composto con `banco/F027-rifai-0.1.12A.sh` (plugin 103 file, diagnostica 6 file). Il vecchio banco F026 ora dà 3 rossi per la regola nuova sulle colonne segrete: vale il F027.
- **Revisione a freddo**: la seconda (sulla prima stesura della 0.1.12A) ha trovato 9 difetti (3 alti, 4 medi, 2 bassi), **9 corretti**. **La terza non è fatta**: mi sono fermato alla soglia del 70% del contesto. La 0.1.12A NON va allo smoke prima: codice sul ramo `sessione/F027`, `main` resta 0.1.10A.
- Il banco vuole MariaDB locale con l utente `banco`/`banco` (in un ambiente nuovo: `apt-get install mariadb-server`, `mariadbd` avviato a mano).
- Registri: BUG 1/5 · punti aperti 35 · violazioni 6 · fallimenti 3.

## §01 — CHE COSA FARE, IN ORDINE
0. Manifesto, prova di lettura, apertura del nodo, registri in una riga.
1. **BUG-6**: terza revisione a freddo della console della 0.1.12A (agente separato, consegna e fonti); 0 alti e 0 medi, altrimenti banco rosso sul caso e correzione. Poi pagina guardata in WordPress e smoke dell owner; con «ok smoke» unione, `novita/`, `prodotti.md`, riga «Sicurezza: tocca query e permessi», BUG-6 e F42 chiusi.
2. F43 (Condizioni d uso v1) nella 0.1.13A; F40 a RelDoxFood; F41; F39 entro l 08/12; F38 alla prossima versione.
3. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F028
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `sessione/F027`: leggi `CLAUDE.md` e `docs/APERTURA-F028.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia` e fai l apertura.
> Fai la nuova revisione a freddo della console SQL; se trova difetti alti o medi, banco prima del motore e poi revisione di nuovo, fino a 0 alti e 0 medi.
> Consegna come kit a quattro buste, ogni voce nuova con le sue stelle. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
