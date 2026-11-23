<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F029 — Manualistica e formazione · ventinovesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F028, 23/11/2026. Ramo di partenza: `sessione/F028` (= 0.1.13A, console SQL corretta dopo la terza revisione, **la quarta revisione a freddo ha trovato 3 alti e 2 medi: BUG-6 aperto**); `main` = 0.1.10A; **sul sito www.reldoxacademy.it è installata la 0.1.12A** (dal 23/11/2026 15:00). Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0028 — 0.1.13A — F029

PROGRESSIVO K0028   precedente K0027   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0028-0_1_13A-F029.zip · composto il 23/11/2026 15:43
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0028-0_1_13-F029.zip
         reldox-academy-0_1_13.zip  md5 226d04ac3ce414776f1290d69ebf1d47
         103 file · 23 cartelle · 0 cartelle vuote
         MD5-0_1_13.txt  103 righe
         APERTURA-F029.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: reldox-academy-diagnostica-0_1_13.zip  md5 a3b8bb6e8a333d78b6ae557c7343558c (F021: secondo pacchetto, stesso numero)
BUSTA 2  KIT-2-AREA-PROGETTI-K0028-0_1_13-F029.zip  46 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0028-0_1_13-F029.zip  85 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  317 righe
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
         banco/F028-banco-console-sql.php  413 righe
         banco/F028-rifai-0.1.13A.sh  69 righe
         banco/F028-sabotaggi-console-sql.py  86 righe
         banco/KIT-PROGRESSIVO.txt  27 righe (nella busta; la riga di questo kit si aggiunge dopo la verifica)
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
         banco/esiti/F028-console-sql-rosso-0.1.12A.txt  105 righe
         banco/esiti/F028-console-sql-sabotaggi.txt  60 righe
         banco/esiti/F028-console-sql-trappola.txt  103 righe
         banco/esiti/F028-console-sql-verde-0.1.13A.txt  102 righe
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
BUSTA 4  KIT-4-AGGIUNTA-K0028-0_1_13-F029.zip  64 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 10 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (46):
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
  docs/APERTURA-F029.md
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
BASELINE 0.1.13 · stadio Alfa · ultima installata sul sito: 0.1.03A (reldoxacademy.it, 19/11/2026)
         registro BUG: REGISTRO BUG · 23/11/2026 F028 · aperti 1 · cornice 1/5 · primo libero BUG-7
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `docs/ROADMAP.md` r.46 (F45) — quale lettera riguarda MySQL 8?
2. `docs/BUG-FORMAZIONE.md` r.31 (BUG-6) — quanti difetti alti ha trovato la quarta revisione a freddo?
3. `banco/esiti/F028-console-sql-sabotaggi.txt` r.60 — quanti sabotaggi fanno tornare rosso il banco?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F028 (23/11/2026).
- Manifesto K0027 confrontato (plugin 103 file · 23 cartelle · 0 vuote, area 45, busta 3 78, busta 4 64, diagnostica 6); prova di lettura F028 risposta.
- Nodo: apertura VERDE, 0 messaggi nuovi; riga «Installato da collaudare: 0.1.12A su www.reldoxacademy.it, dal 23/11/2026 15:00 · in main: 0.1.10A» scritta (commit del nodo `b85bd62`). Il sito usa PHP 8.5.13 e MySQL 8.0.44 di Aruba, come tutte le sorelle: va bene; il banco gira su MariaDB 10.11.
- **Terza revisione a freddo** (sulla 0.1.12A): 10 difetti (1 alto, 3 medi, 2 bassi, 4 dubbi). Banco `banco/F028-banco-console-sql.php` (sezione G ter, I1-I11) **rosso sulla 0.1.12A 70/11**, motore corretto, **verde 81/81 sulla 0.1.13A**, trappola rossa, **58 sabotaggi su 58**. Pacchetti con `banco/F028-rifai-0.1.13A.sh`.
- **Quarta revisione a freddo** (agente nuovo, sulla 0.1.13A): 11 difetti, **3 alti** (`--` seguito da un carattere di controllo nasconde il resto dell istruzione; join di wp_options con sé stessa; UPDATE su più tabelle che copia i valori chiave/valore), **2 medi** (rinominare la chiave smaschera il valore; errore durante la lettura dato per VERDE, oracolo con EXP), 3 bassi e 3 dubbi in **F45 (★★★)**. **0 corretti**: mi sono fermato al 70% del contesto. Dettagli nella scheda BUG-6.
- F43 (Condizioni d uso v1) passa alla **0.1.14A**.
- Banco: MariaDB locale con l utente `banco`/`banco` (`apt-get update && apt-get install -y mariadb-server`, `mariadbd --user=mysql` avviato a mano).
- Registri: BUG 1/5 · punti aperti 36 · violazioni 7 · fallimenti 3.

## §01 — CHE COSA FARE, IN ORDINE
0. Manifesto, prova di lettura, apertura del nodo, registri in una riga.
1. **BUG-6**: banco F029 rosso sulla 0.1.13A con i 5 casi alti e medi della quarta revisione (e i bassi di F45 se possibile: caratteri di controllo `\x01`-`\x1f` e `\x7f`, join e virgole sulle tabelle chiave/valore, UPDATE su più tabelle con tabelle segrete, colonna chiave non modificabile, `errno` dopo la lettura); motore 0.1.14A; verde; revisione a freddo di nuovo fino a 0 alti e 0 medi. Poi pagina guardata in WordPress e smoke dell owner; con «ok smoke» unione, `novita/`, `prodotti.md`, riga «Sicurezza: tocca query e permessi», BUG-6, F42, F45 chiusi.
2. F43 nella versione dopo la console; F40 a RelDoxFood; F41; F39 entro l 08/12; F38 alla prossima versione.
3. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F029
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `sessione/F028`: leggi `CLAUDE.md` e `docs/APERTURA-F029.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia` e fai l apertura.
> Correggi la console SQL coi difetti alti e medi della quarta revisione a freddo: banco prima del motore, poi revisione di nuovo, fino a 0 alti e 0 medi.
> Consegna come kit a quattro buste con i due plugin, RelDoxAcademy e RelDoxAcademy Diagnostica; ogni voce nuova con le sue stelle. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
