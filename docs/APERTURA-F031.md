<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F031 — Manualistica e formazione · trentunesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F030, 23/11/2026. Ramo di partenza: `sessione/F030` (= 0.1.15A, console SQL corretta dopo la sesta e la settima revisione a freddo, **manca l ottava revisione: BUG-6 aperto**); `main` = 0.1.10A; **sul sito www.reldoxacademy.it è installata la 0.1.12A** (dal 23/11/2026 15:00). Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0030 — 0.1.15A — F031

PROGRESSIVO K0030   precedente K0029   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0030-0_1_15A-F031.zip · composto il 23/11/2026 18:08
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0030-0_1_15-F031.zip
         reldox-academy-0_1_15.zip  md5 1d90731c5bcfb3d1c7b2c4b181af85f8
         103 file · 23 cartelle · 0 cartelle vuote
         MD5-0_1_15.txt  103 righe
         APERTURA-F031.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: reldox-academy-diagnostica-0_1_15.zip  md5 98202697d8387be36dc9d9f514545a8e (F021: secondo pacchetto, stesso numero)
BUSTA 2  KIT-2-AREA-PROGETTI-K0030-0_1_15-F031.zip  48 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0030-0_1_15-F031.zip  102 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  320 righe
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
         banco/F029-banco-console-sql.php  493 righe
         banco/F029-rifai-0.1.14A.sh  69 righe
         banco/F029-sabotaggi-console-sql.py  104 righe
         banco/F030-banco-console-sql.php  548 righe
         banco/F030-rifai-0.1.15A.sh  69 righe
         banco/F030-sabotaggi-console-sql.py  111 righe
         banco/INSTALLATA-SUL-SITO.txt  1 righe
         banco/KIT-PROGRESSIVO.txt  29 righe (nella busta; la riga di questo kit si aggiunge dopo la verifica)
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
         banco/esiti/F029-console-sql-rosso-0.1.13A.txt  133 righe
         banco/esiti/F029-console-sql-rosso-0.1.14A-r5.txt  126 righe
         banco/esiti/F029-console-sql-sabotaggi.txt  75 righe
         banco/esiti/F029-console-sql-trappola.txt  132 righe
         banco/esiti/F029-console-sql-verde-0.1.14A.txt  131 righe
         banco/esiti/F030-console-sql-rosso-0.1.14A.txt  139 righe
         banco/esiti/F030-console-sql-rosso-0.1.15A-r7.txt  151 righe
         banco/esiti/F030-console-sql-sabotaggi.txt  78 righe
         banco/esiti/F030-console-sql-trappola.txt  149 righe
         banco/esiti/F030-console-sql-verde-0.1.15A.txt  148 righe
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
BUSTA 4  KIT-4-AGGIUNTA-K0030-0_1_15-F031.zip  64 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 10 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (48):
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
  docs/APERTURA-F030.md
  docs/APERTURA-F031.md
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
BASELINE 0.1.15 · stadio Alfa · ultima installata sul sito: 0.1.12A (www.reldoxacademy.it, 23/11/2026 15:00)
         registro BUG: REGISTRO BUG · 23/11/2026 F030 · aperti 1 · cornice 1/5 · primo libero BUG-7
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `docs/BUG-FORMAZIONE.md` r.31 (BUG-6) — quanti difetti ha trovato la settima revisione a freddo e di che gravità?
2. `banco/esiti/F030-console-sql-verde-0.1.15A.txt` r.148 — quanti verdi?
3. `docs/ROADMAP.md` r.47 (F46) — che cosa resta aperto?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F030 (23/11/2026).
- Manifesto K0029 confrontato: coincide. Prova di lettura F030 risposta. Nodo `d9ceb3c`: apertura VERDE; 1 messaggio di RelDoxChecks (manuali 1.4.04A/1.4.05A, solo informativo) letto e segnato in `bacheca/formazione/LETTO.md`; classifica 5ª (0,47).
- **Sesta revisione a freddo** (sulla 0.1.14A finale; il primo agente si è fermato con 0 prove e non vale, il secondo ha fatto 95 test): 1 alto (`PROCEDURE ANALYSE()`), 1 basso (AUTO_INCREMENT nella prova a secco), corretti; più F46 (a).
- **Settima revisione a freddo** (sulla prima stesura della 0.1.15A, 135 test): 0 alti, 5 medi, 1 basso, tutti corretti (ON DUPLICATE KEY UPDATE, DISTINCT, NATURAL JOIN/USING, contatori con maiuscole, valori in chiaro nel registro, SHOW PROCEDURE STATUS con più spazi).
- Banco `banco/F030-banco-console-sql.php`: rosso sulla 0.1.14A 95/3, rosso sulla 0.1.15A prima stesura 98/6, **verde 104/104**, trappola rossa. Sabotaggi: 76/76 fino a L3; **i 4 sabotaggi di L4-L8 sono scritti in `banco/F030-sabotaggi-console-sql.py` ma non eseguiti**.
- Pacchetti con `banco/F030-rifai-0.1.15A.sh`. Il registro della console ora conserva l istruzione con i valori fra apici come «…».
- **Dopo il kit K0030** (richiamo dell owner 18:30, cruscotto): violazione **V-8** (riga «Registri» del nodo ferma a 36), fallimento **F030-F1** con `strumenti/riga-registri.py` (nuovo: busta 3 conta un file in più del manifesto), riga del nodo riallineata, messaggio a R-D sul cruscotto che legge solo `main` (FOR-R006); CRD-R045 (decisioni chiuse) preso in carico come voce **F47 (★★★★)**, da fare per prima.
- Banco: MariaDB locale, utente `banco`/`banco` (`apt-get install -y mariadb-server`, `mariadbd --user=mysql`). ⛔ Mai due banchi della console insieme sullo stesso MariaDB; la revisione su database suoi (`rev8_*`). Il primo agente della sesta si è fermato leggendo il banco: all agente di revisione non dare la cartella `banco/`, chiedergli test di conformità scritti da zero.

## §01 — CHE COSA FARE, IN ORDINE
0. Manifesto, prova di lettura, apertura del nodo, registri in una riga; `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md` VERDE (fallimento F030-F1), anche alla chiusura.
1. **F47**: `docs/DECISIONI-CHIUSE.md` e controllo `strumenti/decisioni-chiuse.py` del nodo (CRD-R045).
1 bis. Eseguire `python3 -I banco/F030-sabotaggi-console-sql.py reldox-academy-diagnostica` (80 sabotaggi, tutti rossi).
2. **BUG-6**: ottava revisione a freddo sulla 0.1.15A (agente nuovo, test di conformità scritti da zero, database `rev8_*`); se trova alti o medi: banco rosso, motore, verde, revisione di nuovo fino a 0 e 0. Poi pagina guardata in WordPress e smoke dell owner; con «ok smoke» unione, `novita/`, `prodotti.md`, riga «Sicurezza: tocca query e permessi», BUG-6, F42, F45 chiusi (F46 resta per la parte MySQL 8).
3. F43 nella versione dopo la console; F40 a RelDoxFood; F41; F39 entro l 08/12; F38 alla prossima versione.
4. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F031
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `sessione/F030`: leggi `CLAUDE.md` e `docs/APERTURA-F031.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia` e fai l apertura.
> Esegui i sabotaggi, poi l ottava revisione a freddo della console SQL; se trova difetti alti o medi: banco prima del motore, poi revisione di nuovo, fino a 0 alti e 0 medi.
> Consegna come kit a quattro buste con i due plugin; ogni voce nuova con le sue stelle. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
