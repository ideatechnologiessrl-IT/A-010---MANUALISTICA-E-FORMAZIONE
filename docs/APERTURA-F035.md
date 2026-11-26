<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F035 — Manualistica e formazione · trentacinquesima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F034, 26/11/2026. Ramo di partenza: `sessione/F031` (= **0.1.20A**: console SQL in **sola lettura** con la **maschera per difetto**, D47 e D48; **mancano la correzione dei tre alti della quattordicesima revisione a freddo e la quindicesima: BUG-6 aperto**); `main` = 0.1.10A; **sul sito www.reldoxacademy.it è installata la 0.1.18A** (24/11/2026 13:35): la console SQL non si usa sul sito. ⛔ **Chat senza consegna: 2 di 2** (F033, F034): **la F035 consegna** (CRD-R057). Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0038 — 0.1.20A — F035

PROGRESSIVO K0038   precedente K0037   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0038-0_1_20A-F035.zip · composto il 26/11/2026 16:56
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0038-0_1_20-F035.zip
         reldox-academy-0_1_20.zip  md5 fd27506ed5b3e9bd09a1d78ac6411679
         103 file · 23 cartelle · 0 cartelle vuote
         MD5-0_1_20.txt  103 righe
         APERTURA-F035.md
         PROMPT-F035.md  (prompt completo della sessione seguente; anche accanto al kit)
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: reldox-academy-diagnostica-0_1_20.zip  md5 9ef183f453c44bfc2f44730d28ba4be9 (F021: secondo pacchetto, stesso numero)
BUSTA 2  KIT-2-AREA-PROGETTI-K0038-0_1_20-F035.zip  53 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0038-0_1_20-F035.zip  187 script
         banco/F005-G-banchi-copia.py  130 righe
         banco/F013-banco-wpconfig.php  69 righe
         banco/F013-kit.py  376 righe
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
         banco/F032-banco-console-sql.php  664 righe
         banco/F032-rifai-0.1.17A.sh  69 righe
         banco/F032-rifai-0.1.18A.sh  69 righe
         banco/F032-sabotaggi-console-sql.py  148 righe
         banco/F033-banco-console-sql.php  772 righe
         banco/F033-rifai-0.1.19A.sh  69 righe
         banco/F033-sabotaggi-console-sql.py  174 righe
         banco/F034-banco-console-sql.php  953 righe
         banco/F034-rifai-0.1.20A.sh  69 righe
         banco/INSTALLATA-SUL-SITO.txt  1 righe
         banco/KIT-PROGRESSIVO.txt  37 righe (nella busta; la riga di questo kit si aggiunge dopo la verifica)
         banco/__pycache__/F013-kit.cpython-313.pyc  0 righe
         banco/__pycache__/F033-sabotaggi-console-sql.cpython-313.pyc  0 righe
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
         banco/esiti/F032-console-sql-rosso-0.1.16A.txt  226 righe
         banco/esiti/F032-console-sql-rosso-0.1.17A.txt  198 righe
         banco/esiti/F032-console-sql-sabotaggi.txt  108 righe
         banco/esiti/F032-console-sql-trappola.txt  199 righe
         banco/esiti/F032-console-sql-verde-0.1.18A.txt  198 righe
         banco/esiti/F033-console-sql-rosso-0.1.18A.txt  226 righe
         banco/esiti/F033-console-sql-sabotaggi.txt  125 righe
         banco/esiti/F033-console-sql-trappola.txt  225 righe
         banco/esiti/F033-console-sql-verde-0.1.19A.txt  224 righe
         banco/esiti/F034-console-sql-rosso-0.1.19A.txt  211 righe
         banco/esiti/F034-console-sql-rosso-0.1.20A-r13.txt  223 righe
         banco/esiti/F034-console-sql-trappola.txt  228 righe
         banco/esiti/F034-console-sql-verde-0.1.20A.txt  219 righe
         banco/esiti/F034-revisione-12/REFERTO-R12.md  178 righe
         banco/esiti/F034-revisione-12/prove/lib.php  38 righe
         banco/esiti/F034-revisione-12/prove/out01.txt  310 righe
         banco/esiti/F034-revisione-12/prove/out02.txt  66 righe
         banco/esiti/F034-revisione-12/prove/out03.txt  262 righe
         banco/esiti/F034-revisione-12/prove/out04.txt  84 righe
         banco/esiti/F034-revisione-12/prove/out05.txt  177 righe
         banco/esiti/F034-revisione-12/prove/out06.txt  130 righe
         banco/esiti/F034-revisione-12/prove/out07.txt  42 righe
         banco/esiti/F034-revisione-12/prove/out08.txt  18 righe
         banco/esiti/F034-revisione-12/prove/out09.txt  274 righe
         banco/esiti/F034-revisione-12/prove/out10.txt  8 righe
         banco/esiti/F034-revisione-12/prove/out11.txt  16 righe
         banco/esiti/F034-revisione-12/prove/out12.txt  31 righe
         banco/esiti/F034-revisione-12/prove/p01_lettura_base.php  32 righe
         banco/esiti/F034-revisione-12/prove/p02_kv_wp.php  17 righe
         banco/esiti/F034-revisione-12/prove/p03_scritture.php  24 righe
         banco/esiti/F034-revisione-12/prove/p04_scritture2.php  5 righe
         banco/esiti/F034-revisione-12/prove/p05_registro.php  6 righe
         banco/esiti/F034-revisione-12/prove/p06_ordine.php  3 righe
         banco/esiti/F034-revisione-12/prove/p07_tempi.php  16 righe
         banco/esiti/F034-revisione-12/prove/p08_lucchetti.php  11 righe
         banco/esiti/F034-revisione-12/prove/p09_varie.php  13 righe
         banco/esiti/F034-revisione-12/prove/p10_lunghezza.php  3 righe
         banco/esiti/F034-revisione-12/prove/p11_tempi2.php  13 righe
         banco/esiti/F034-revisione-12/prove/p12_ultime.php  9 righe
         banco/esiti/F034-revisione-12/prove/palco-aggiunte.sql  26 righe
         banco/esiti/F034-revisione-12/prove/palco.sql  44 righe
         banco/esiti/F034-revisione-13/REFERTO-R13.md  105 righe
         banco/esiti/F034-revisione-13/prove/base.php  22 righe
         banco/esiti/F034-revisione-13/prove/p01-maschera-nomi.php  7 righe
         banco/esiti/F034-revisione-13/prove/p02-kv.php  34 righe
         banco/esiti/F034-revisione-13/prove/p03-schema-oracoli.php  28 righe
         banco/esiti/F034-revisione-13/prove/p04-partizioni-merge.php  20 righe
         banco/esiti/F034-revisione-13/prove/p05-tempo.php  7 righe
         banco/esiti/F034-revisione-13/prove/p05b-tempo.php  6 righe
         banco/esiti/F034-revisione-13/prove/p05c-tempo-doppio.php  5 righe
         banco/esiti/F034-revisione-13/prove/p06-scritture.php  20 righe
         banco/esiti/F034-revisione-13/prove/p06.out  714 righe
         banco/esiti/F034-revisione-13/prove/p07-registro.php  31 righe
         banco/esiti/F034-revisione-13/prove/p07.out  0 righe
         banco/esiti/F034-revisione-13/prove/p08-variabili.php  8 righe
         banco/esiti/F034-revisione-13/prove/p09-rifiuti-a-torto.php  13 righe
         banco/esiti/F034-revisione-13/prove/palco.sql  45 righe
         banco/esiti/F034-revisione-14/REFERTO-R14.md  78 righe
         banco/esiti/F034-revisione-14/prove/banco.php  18 righe
         banco/esiti/F034-revisione-14/prove/p01-variabili.txt  34 righe
         banco/esiti/F034-revisione-14/prove/p02-colonne.txt  71 righe
         banco/esiti/F034-revisione-14/prove/p03-show-is.txt  100 righe
         banco/esiti/F034-revisione-14/prove/p04-scritture-tempi.txt  36 righe
         banco/esiti/F034-revisione-14/prove/p05-tempi.txt  3 righe
         banco/esiti/F034-revisione-14/prove/p06-varie.txt  39 righe
         banco/esiti/F034-revisione-14/prove/palco.sql  36 righe
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
         strumenti/riga-registri.py  65 righe
BUSTA 4  KIT-4-AGGIUNTA-K0038-0_1_20-F035.zip  64 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 10 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (53):
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
  docs/APERTURA-F033.md
  docs/APERTURA-F034.md
  docs/APERTURA-F035.md
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
BASELINE 0.1.20 · stadio Alfa · ultima installata sul sito: 0.1.18A (www.reldoxacademy.it, 24/11/2026 13:35)
         registro BUG: REGISTRO BUG · 26/11/2026 F034 · aperti 1 · cornice 1/5 · primo libero BUG-7
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `docs/BUG-FORMAZIONE.md` r.31 (BUG-6, quattordicesima revisione) — quanti alti e quanti medi, e quali sono i tre alti?
2. `banco/esiti/F034-console-sql-verde-0.1.20A.txt` r.219 — quanti verdi?
3. `docs/DECISIONI-CHIUSE.md` r.29-30 — che cosa dicono C17 e C18?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F034 (26/11/2026).
- Apertura: manifesto K0037 coincide; nodo VERDE; 14 messaggi letti; risposte a CRD-R051 (F55), CRD-R055 (F56), CRD-R057 (F57), SEC-R035 (F58); FOR-R006 chiuso.
- **Dodicesima revisione** sulla 0.1.19A: 3 alti, 4 medi (referto `banco/esiti/F034-revisione-12/`). **Decisioni dell owner** 26/11/2026 15:17, chat Formazione F034: **D47 «Maschera per difetto»** e **D48 «Sola lettura ora»** (C17, C18).
- **0.1.20A**: console in sola lettura (costante `SOLA_LETTURA`, interfaccia senza spunta né CONFERMO); in chiaro solo i nomi dell elenco stretto `INNOCUE`, i numeri e le date con nomi senza parole spia (`SPIA`), i nomi dei risultati di SHOW/DESCRIBE/EXPLAIN (`ESPLICATIVE`); chiave/valore in chiaro solo per le chiavi di `INNOCUE_KV`; MERGE (anche dietro una vista), partizioni e `INNODB_*` rifiutati; registro senza parole con cifre e senza sorgente sconosciuta; referto senza millisecondi.
- **Tredicesima revisione** sulla prima stesura: 4 alti, 3 medi (`banco/esiti/F034-revisione-13/`), tutti corretti. **Quattordicesima** sulla 0.1.20A di `40cd74b`: **3 alti, 0 medi** (`banco/esiti/F034-revisione-14/`): R14-1 variabili e funzioni del server convertite in numero o data (`ASCII(@@datadir)`, `CONV(HEX(…))`, `MAKEDATE`); R14-2 vista con `CURRENT_USER() AS nome`; R14-3 numeri segreti con nomi senza parola spia (`csc`, `ccv`, `cvn`, `nip`, `ssn`, `pan`). Bassi e dubbio: F61; della tredicesima: F60.
- Banco `banco/F034-banco-console-sql.php`: sezioni P e Q; **rosso sulla 0.1.19A 108/38**, **rosso sulla 0.1.20A di `29100e0` 147/6**, **verde sulla 0.1.20A di `40cd74b` 153/153**; trappola rossa; due sabotaggi delle regole nuove rossi (`banco/esiti/F034-console-sql-trappola.txt`). Le 26 prove vecchie che chiedevano una scrittura sono «SUPERATE (D48)»: controllano che ogni scrittura sia rifiutata. Pacchetti con `banco/F034-rifai-0.1.20A.sh`.
- ⛔ Il banco legge le colonne di **tutti** i database del server: prima di lanciarlo, togliere i database `rev*` lasciati dai revisori (`SET FOREIGN_KEY_CHECKS=0; DROP DATABASE …`), altrimenti va rosso a torto (R13-9, R14-4).
- Banco: MariaDB locale, utente `banco`/`banco` (`apt-get update`, `apt-get install -y mariadb-server php-mysql zip unzip`, `mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld`, `mariadbd --user=mysql`, poi l utente `banco` con tutti i permessi). Mai due banchi della console insieme sullo stesso MariaDB. Le revisioni a freddo della F034 sono durate 10-13 minuti ciascuna. All agente: plugin di diagnostica, mockup, estratti delle regole con D47 e D48, sostituti di WordPress (righe 99-129 del banco F033, database `rev15_*`), istruzioni R14-1 … R14-3; mai la cartella `banco/`.
- Proposta per i tre alti (da decidere col banco, prima del motore): R14-1 — in lettura rifiutare `@@` e le funzioni d informazione del server e di sessione (DATABASE, VERSION, CONNECTION_ID, CURRENT_USER e simili), oppure mostrare le espressioni solo se sono aggregati (COUNT, SUM, MIN, MAX, AVG) di colonne citabili; R14-2 — vista rifiutata se la definizione chiama una funzione di sessione o d informazione; R14-3 — anche i numeri solo per elenco o schema stretto (id, `_id`, conteggi, date): è la regola di C17 («in chiaro solo ciò che è in un elenco di innocui») applicata anche ai numeri; l owner ne è informato alla consegna della F034, non si richiede.

## §01 — CHE COSA FARE, IN ORDINE
0. Decisioni chiuse, manifesto, prova di lettura, apertura del nodo, registri in una riga; `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md` VERDE, anche alla chiusura.
1. **BUG-6**: banco rosso per R14-1 … R14-3 (sezione R del banco F035), motore, verde, trappola; R14-3 dentro C17 (numeri solo per elenco). Poi quindicesima revisione a freddo; con 0 alti e 0 medi pagina guardata in WordPress e **smoke dell owner della 0.1.20A** (o 0.1.21A). Con «ok smoke»: unione, `novita/`, `prodotti.md`, riga «Sicurezza: tocca query e permessi», BUG-6, F42, F45, F46, F48, F49, F50, F54 (nella parte corretta) chiusi.
2. ⛔ Regola CRD-R057: se una revisione trova ancora alti, si spezza di nuovo e si consegna la parte verde (per esempio il prodotto con la console spenta), dicendolo all owner: la F035 consegna.
3. **F51**: i sabotaggi della console, una volta sola e in fondo (lo script va riallineato al banco F034).
4. A ogni kit i due registri riportati in `main` (D46) e il prompt completo nel messaggio di consegna (C16).
5. F55 e F56 (indurimento) dopo la console; F43; F40; F41; F39 entro l 08/12; F38; F52; F58.
6. A ogni consegna «Contesto: ~NN% · soglia kit 70%» e «Chat senza consegna: n di 2».

## §11 — PROMPT PER LA SESSIONE F035
> Sei la chat «Manualistica e formazione» della famiglia RelDox, sessione F035. Solo italiano, rapporti brevi e concreti.
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `sessione/F031` (RelDoxAcademy e RelDoxAcademy Diagnostica **0.1.20A**: console SQL in sola lettura con la maschera per difetto); `main` = 0.1.10A; sul sito www.reldoxacademy.it è installata la **0.1.18A** (dal 24/11/2026 13:35): la console SQL non si usa sul sito. ⛔ **Chat senza consegna: 2 di 2 — questa chat consegna** (regola CRD-R057).
> Leggi per primi, in ordine: `docs/DECISIONI-CHIUSE.md`, `CLAUDE.md`, `docs/APERTURA-F035.md`; confronta il manifesto e rispondi alla prova di lettura. Leggi i quattro registri (`docs/BUG-FORMAZIONE.md`, `docs/ROADMAP.md`, `docs/VIOLAZIONI-FORMAZIONE.md`, `docs/FALLIMENTI-FORMAZIONE.md`) e dillo in una riga «Registri letti: …».
> Aggancia il nodo `ideatechnologiessrl-IT/RelDox-Famiglia` (in scrittura: scrivi solo in `bacheca/formazione/`) e fai l apertura: dal nodo `python3 -I strumenti/decisioni-chiuse.py <A-010> --aperti <A-010>/docs/ROADMAP.md <A-010>/docs/BUG-FORMAZIONE.md` (VERDE), poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (leggi i messaggi nuovi, rispondi, `--segna`; alla chiusura `--chiusura` VERDE) e `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md` (VERDE).
> Lavoro, in ordine: 1) prepara il banco (MariaDB locale, utente `banco`/`banco`, come dice l APERTURA § 00; togli prima i database `rev*`) e controlla che `php banco/F034-banco-console-sql.php reldox-academy-diagnostica` dia 153 verdi; 2) correggi i tre alti della quattordicesima revisione (R14-1 variabili e funzioni del server convertite in numero o data, R14-2 vista con una funzione di sessione, R14-3 numeri segreti con nomi senza parola spia): banco prima del motore (rosso, motore, verde, trappola); per R14-3 anche i numeri solo per elenco o schema stretto, come dice C17; 3) quindicesima revisione a freddo con un agente nuovo (database `rev15_*`, riprova R14-1 … R14-3; circa 15 minuti, da dire all owner prima); se trova ancora alti o medi, spezza e consegna la parte verde; 4) con 0 alti e 0 medi: pagina guardata in WordPress, kit e smoke dell owner della 0.1.20A (o 0.1.21A); 5) per ultimo, una volta sola, i sabotaggi (voce F51).
> Regole da ricordare: i due plugin viaggiano sempre insieme, stesso numero, nello stesso kit; kit a quattro buste con `banco/F013-kit.py`, e a ogni kit il prompt completo della sessione dopo per intero nel messaggio di consegna e come file `PROMPT-F036.md`; i registri si scrivono subito, ogni voce nuova con le sue stelle, e a ogni kit i due registri (`docs/ROADMAP.md`, `docs/BUG-FORMAZIONE.md`) si riportano in `main` senza codice; mai due banchi della console insieme sullo stesso MariaDB; le attese lunghe (revisione circa 15 minuti, sabotaggi circa un ora) si dicono all owner prima di cominciarle; riga «Contesto: ~NN% · soglia kit 70%» e «Chat senza consegna: n di 2» a ogni consegna, e al 70% ci si ferma e si compone il kit.
> File da allegare: nessuno, tutto è nel repository e nel nodo (il kit K0038 è la copia per l area del progetto).

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
