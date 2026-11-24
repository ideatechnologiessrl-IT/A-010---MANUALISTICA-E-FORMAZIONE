<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F032 — Manualistica e formazione · trentaduesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F031, 23/11/2026. Ramo di partenza: `sessione/F031` (= 0.1.16A, console SQL corretta dopo l ottava revisione a freddo, **manca la nona revisione: BUG-6 aperto**); `main` = 0.1.10A; **sul sito www.reldoxacademy.it è installata la 0.1.16A** (24/11/2026 11:23, scheda di configurazione dell owner: MySQL 8.0.44-35, PHP 8.5.13, WordPress 7.1.5, BASE connesso, 9 tabelle; prima la 0.1.15A dal 23/11/2026 18:39): la console SQL non si usa sul sito finché una revisione non dà 0 alti e 0 medi. Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0031 — 0.1.16A — F032

PROGRESSIVO K0031   precedente K0030   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0031-0_1_16A-F032.zip · composto il 23/11/2026 19:55
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0031-0_1_16-F032.zip
         reldox-academy-0_1_16.zip  md5 dd13f532191b0d5e5660c585ad91bcff
         103 file · 23 cartelle · 0 cartelle vuote
         MD5-0_1_16.txt  103 righe
         APERTURA-F032.md
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: reldox-academy-diagnostica-0_1_16.zip  md5 8eec845312aec0b98d8a077ea36549c1 (F021: secondo pacchetto, stesso numero)
BUSTA 2  KIT-2-AREA-PROGETTI-K0031-0_1_16-F032.zip  50 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0031-0_1_16-F032.zip  110 script
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
         banco/F031-banco-console-sql.php  597 righe
         banco/F031-rifai-0.1.16A.sh  69 righe
         banco/F031-sabotaggi-console-sql.py  128 righe
         banco/INSTALLATA-SUL-SITO.txt  1 righe
         banco/KIT-PROGRESSIVO.txt  30 righe (nella busta; la riga di questo kit si aggiunge dopo la verifica)
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
         banco/esiti/F031-console-sql-rosso-0.1.15A.txt  190 righe
         banco/esiti/F031-console-sql-sabotaggi.txt  192 righe
         banco/esiti/F031-console-sql-trappola.txt  173 righe
         banco/esiti/F031-console-sql-verde-0.1.16A.txt  172 righe
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
         strumenti/riga-registri.py  65 righe
BUSTA 4  KIT-4-AGGIUNTA-K0031-0_1_16-F032.zip  64 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 10 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (50):
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
  docs/APERTURA-F032.md
  docs/BUG-FORMAZIONE.md
  docs/DECISIONI-CHIUSE.md
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
BASELINE 0.1.16 · stadio Alfa · ultima installata sul sito: 0.1.15A (www.reldoxacademy.it, 23/11/2026 18:39)
         registro BUG: REGISTRO BUG · 23/11/2026 F031 · aperti 1 · cornice 1/5 · primo libero BUG-7
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `docs/BUG-FORMAZIONE.md` r.31 (BUG-6) — quanti alti e quanti medi ha trovato l ottava revisione a freddo, e su quante prove?
2. `banco/esiti/F031-console-sql-verde-0.1.16A.txt` r.172 — quanti verdi?
3. `docs/ROADMAP.md` r.48 (F48, punto a) — quale scelta è aperta sulle tabelle chiave/valore?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F031 (23/11/2026).
- Manifesto K0030 confrontato: coincide, salvo `strumenti/riga-registri.py` e la riga K0030 del progressivo, nati dopo il kit. Prova di lettura F031 risposta. Nodo `5ee7cf4`: apertura VERDE, 0 messaggi nuovi; classifica 5ª (0,47).
- **F47 chiusa**: `docs/DECISIONI-CHIUSE.md` (15 decisioni), controllo del nodo VERDE, passo 0 nel `CLAUDE.md`.
- **Sabotaggi sulla 0.1.15A**: 79 su 80 al primo giro (quello di PROCEDURE ANALYSE cercava un testo vecchio del motore), 80 su 80 col motivo corretto (`banco/F031-sabotaggi-console-sql.py`).
- **Ottava revisione a freddo** (sulla 0.1.15A, 551 prove): 5 alti, 6 medi, 3 bassi, 4 dubbi. Gli 11 alti e medi corretti nella **0.1.16A**; bassi e dubbi nella voce F48 (★★★★).
- Banco `banco/F031-banco-console-sql.php` (sezione M, M1-M11): rosso sulla 0.1.15A 104/11, **verde sulla 0.1.16A 115/115**, trappola rossa; sabotaggi in `banco/esiti/F031-console-sql-sabotaggi.txt`.
- Regole nuove del motore, da dire all owner allo smoke: i nomi con accenti o lettere speciali sono rifiutati (nelle stringhe fra apici no); la console non chiama funzioni memorizzate e non legge viste che citano colonne segrete.
- Pacchetti con `banco/F031-rifai-0.1.16A.sh`. Banco: MariaDB locale, utente `banco`/`banco` (`apt-get update`, `apt-get install -y mariadb-server`, `mariadbd --user=mysql`). ⛔ Mai due banchi della console insieme sullo stesso MariaDB. All agente di revisione: il plugin di diagnostica, il mockup, gli estratti delle regole e i soli sostituti di WordPress (righe 82-111 del banco, con database `rev9_*`), mai la cartella `banco/`.
- **Richiamo dal cruscotto del 24/11/2026 11:21** («Aree grigie»: 39 dichiarati, 33 nei file): riga e file coincidono (ramo `sessione/F031` 38 punti + BUG-6; `main` 33 e 0), il cruscotto legge `main`. Nessuna violazione; scritto a R-D il seguito di FOR-R006 (`2026-11-24-a-r-d-cruscotto-registri-ramo-di-sessione.md`), che resta aperta. Letti i messaggi di RelDoxChecks sui manuali 1.4.06A e 1.4.07A (informativi: materia della voce F24; la riga «15 minuti» non è nei nostri materiali).
- La riga «Registri» del nodo va riscritta da `strumenti/riga-registri.py` prima di pubblicare (38 punti aperti: chiusa F47, nata F48).

## §01 — CHE COSA FARE, IN ORDINE
0. Decisioni chiuse, manifesto, prova di lettura, apertura del nodo, registri in una riga; `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md` VERDE, anche alla chiusura.
1. **BUG-6**: nona revisione a freddo sulla 0.1.16A (agente nuovo, prove scritte da zero, database `rev9_*`); con alti o medi: banco rosso, motore, verde, sabotaggi, revisione di nuovo fino a 0 e 0. Poi pagina guardata in WordPress e smoke dell owner; con «ok smoke» unione, `novita/`, `prodotti.md`, riga «Sicurezza: tocca query e permessi», BUG-6, F42, F45 chiusi.
2. F48 punto (a): portare all owner la scelta sulle chiavi segrete insieme allo smoke.
3. F43 nella versione dopo la console; F40 a RelDoxFood; F41; F39 entro l 08/12; F38 alla prossima versione.
4. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F032
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `sessione/F031`: leggi `CLAUDE.md`, `docs/DECISIONI-CHIUSE.md` e `docs/APERTURA-F032.md`, confronta il manifesto e rispondi alla prova di lettura.
> Aggancia il nodo `RelDox-Famiglia` e fai l apertura.
> Fai la nona revisione a freddo della console SQL; se trova difetti alti o medi: banco prima del motore, poi revisione di nuovo, fino a 0 alti e 0 medi.
> Consegna come kit a quattro buste con i due plugin; ogni voce nuova con le sue stelle. Riga del contesto a ogni consegna; al 70% fermati e componi il kit.
> Solo italiano, rapporti brevi.

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
