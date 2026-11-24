<p><img src="../risorse/logo/reldox-lockup-color.svg" alt="RelDox" width="240"></p>

# APERTURA F034 — Manualistica e formazione · trentaquattresima sessione

> ⛔ **Documento riservato · non divulgabile** — Idea Technologies srl soc. Uninominale. Scritta da Formazione F033, 24/11/2026. Ramo di partenza: `sessione/F031` (= **0.1.19A**, console SQL corretta dopo l undicesima revisione a freddo, **mancano la controprova e la dodicesima revisione: BUG-6 aperto**); `main` = 0.1.10A; **sul sito www.reldoxacademy.it è installata la 0.1.18A** (24/11/2026 13:35): la console SQL non si usa sul sito e la 0.1.19A non si installa finché la dodicesima revisione non dà 0 alti e 0 medi. Viaggia nella busta 1 del kit.

<!-- MANIFESTO:INIZIO — scritto da banco/F013-kit.py, ricalcolato sulla scatola: non si scrive a mano -->
```
## MANIFESTO — KIT K0037 — 0.1.19A — F034

PROGRESSIVO K0037   precedente K0036   ⛔ mai uguale, mai minore
KIT KIT-RELDOXACADEMY-K0037-0_1_19A-F034.zip · composto il 24/11/2026 16:14
BUSTE DICHIARATE: 4
BUSTA 1  KIT-1-APERTURA-K0037-0_1_19-F034.zip
         reldox-academy-0_1_19.zip  md5 efdf7debf34a847bbe8187c4f53493b3
         103 file · 23 cartelle · 0 cartelle vuote
         MD5-0_1_19.txt  103 righe
         APERTURA-F034.md
         PROMPT-F034.md  (prompt completo della sessione seguente; anche accanto al kit)
         rese: ANALISI-TECNICA-RELDOXACADEMY.html
         pacchetto di diagnostica: reldox-academy-diagnostica-0_1_19.zip  md5 126deb803c8255e2e268c87e5a366940 (F021: secondo pacchetto, stesso numero)
BUSTA 2  KIT-2-AREA-PROGETTI-K0037-0_1_19-F034.zip  52 file — quello che sostituirà l area intera
BUSTA 3  KIT-3-MISURE-K0037-0_1_19-F034.zip  128 script
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
         banco/INSTALLATA-SUL-SITO.txt  1 righe
         banco/KIT-PROGRESSIVO.txt  36 righe (nella busta; la riga di questo kit si aggiunge dopo la verifica)
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
         strumenti/anteprima-lezione.py  208 righe
         strumenti/copia-con-segno.py  40 righe
         strumenti/figure-1-2.py  101 righe
         strumenti/genera-analisi-tecnica.py  144 righe
         strumenti/riga-registri.py  65 righe
BUSTA 4  KIT-4-AGGIUNTA-K0037-0_1_19-F034.zip  64 volumi — corsi 18 · fonti 1 · glossario 1 · manuali 5 · mockup 10 · modelli 3 · risorse 26
ELENCO DELL AREA, DICHIARATO (52):
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
BASELINE 0.1.19 · stadio Alfa · ultima installata sul sito: 0.1.18A (www.reldoxacademy.it, 24/11/2026 13:35)
         registro BUG: REGISTRO BUG · 24/11/2026 F033 · aperti 1 · cornice 1/5 · primo libero BUG-7
```
<!-- MANIFESTO:FINE -->

## PROVA DI LETTURA — si risponde PRIMA di ricevere il mandato
1. `docs/BUG-FORMAZIONE.md` r.31 (BUG-6) — quanti alti e quanti medi ha trovato l undicesima revisione a freddo, e su quante prove?
2. `banco/esiti/F033-console-sql-verde-0.1.19A.txt` r.224 — quanti verdi?
3. `docs/ROADMAP.md` r.51 (F51) — quale sabotaggio è rimasto cieco sulla 0.1.19A?

## §00 — DA DOVE SI RIPARTE (HANDOFF)
F033 (24/11/2026).
- Manifesto K0036 confrontato: coincide. Prova di lettura risposta. Nodo `645447c`: apertura VERDE, 1 messaggio nuovo (CRD-R049); classifica 5ª (0,47). Decisioni chiuse VERDE.
- **CRD-R049, a ogni kit il prompt completo** (owner 24/11/2026 13:40, chat RelDox CRD S748): fatto e chiuso (voce F53): `banco/F013-kit.py` scrive `PROMPT-F<nnn>.md` dal § 11 di questa APERTURA, nella busta 1 e accanto al kit; verifica ROSSA se manca, è diverso o non è completo; riga nel `CLAUDE.md`; C16 nelle decisioni chiuse; risposto «ricevuto» sul nodo. Il prompt va **per intero nel messaggio di consegna**.
- **Undicesima revisione a freddo** (sulla 0.1.18A, agente nuovo, 1.136 prove): **3 alti, 5 medi**, 5 bassi, 5 dubbi (elenco in BUG-6; bassi e dubbi nella voce F54 ★★★).
- **Rimedio nella 0.1.19A** (scritto da un agente di sviluppo su mandato, non riletto riga per riga da F033): banco `banco/F033-banco-console-sql.php` (sezione O, O1-O8) **rosso sulla 0.1.18A 128/8**, **verde sulla 0.1.19A 136/136**, trappola rossa; sabotaggi **122 su 123** (`banco/esiti/F033-console-sql-sabotaggi.txt`): i quattro di F51 riallineati, uno nuovo cieco («F032 vista che chiama una funzione letta»), voce F51.
- **Non fatto** (owner 24/11/2026 16:10, chat Formazione F033: *«componi il kit e fermati lascia alla prossima chat i compiti da finire dammi il prompt»*): la controprova con le prove dell undicesimo revisore sul motore corretto e la dodicesima revisione a freddo. Le prove dell undicesimo revisore non sono nel repository (stavano nella macchina della sessione): la dodicesima le riscrive da zero, com è regola.
- Regole nuove del motore 0.1.19A, da dire all owner allo smoke: sulle tabelle con colonne segrete o chiave/valore niente `USE/FORCE/IGNORE INDEX`, e il motore esclude da sé gli indici sulle colonne segrete (lo dichiara nel referto); le tabelle con una colonna segreta nella chiave primaria non si leggono con SELECT; `INSERT`/`REPLACE … () VALUES` rifiutati su quelle tabelle; non si leggono le viste che leggono un altro database; in scrittura tempo massimo più corto (risposta entro il tempo massimo, annullamento compreso); tetto in byte contato dopo le maschere; registro senza numeri anche nei messaggi di rifiuto. Restano quelle della 0.1.17A e 0.1.18A (APERTURA-F033 § 00).
- Pacchetti con `banco/F033-rifai-0.1.19A.sh`. Banco: MariaDB locale, utente `banco`/`banco` (`apt-get update`, `apt-get install -y mariadb-server php-mysql zip unzip`, `mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld`, `mariadbd --user=mysql`, poi l utente `banco` con tutti i permessi; si spegne fra un giro e l altro: riaccenderlo). ⛔ Mai due banchi della console insieme sullo stesso MariaDB. I sabotaggi durano circa un ora: lanciarli una volta sola, in fondo. All agente di revisione: il plugin di diagnostica, il mockup, gli estratti delle regole e i soli sostituti di WordPress (dal segno «WordPress finto» al segno «il palco» del banco F033, con database `rev12_*`), mai la cartella `banco/` né le revisioni precedenti; la revisione dura circa 50 minuti.
- La riga «Registri» del nodo è stata riscritta da `strumenti/riga-registri.py` e pubblicata (43 punti aperti: nate F53, chiusa in sessione, e F54). I due registri sono riportati in `main` (D46).

## §01 — CHE COSA FARE, IN ORDINE
0. Decisioni chiuse, manifesto, prova di lettura, apertura del nodo, registri in una riga; `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md` VERDE, anche alla chiusura.
1. **BUG-6**: dodicesima revisione a freddo sulla 0.1.19A (agente nuovo, prove scritte da zero, database `rev12_*`; fra le prove, le istruzioni esatte di R11-1 … R11-8 scritte in BUG-6); con alti o medi: banco rosso, motore, verde, revisione di nuovo fino a 0 e 0. Poi pagina guardata in WordPress e smoke dell owner; con «ok smoke» unione, `novita/`, `prodotti.md`, riga «Sicurezza: tocca query e permessi», BUG-6, F42, F45 chiusi.
2. **F51**: l ultimo sabotaggio cieco (123 su 123), una volta sola e in fondo.
3. A ogni kit: i due registri del ramo riportati in `main` (D46), senza codice; il prompt completo nel messaggio di consegna (C16).
4. F48 (a), F49 (g), F50 (e) e F54 (g): portare all owner le scelte sui nomi segreti insieme allo smoke.
5. F43 nella versione dopo la console; F40 a RelDoxFood; F41; F39 entro l 08/12; F38 alla prossima versione; F52.
6. A ogni consegna «Contesto: ~NN% · soglia kit 70%».

## §11 — PROMPT PER LA SESSIONE F034
> Sei la chat «Manualistica e formazione» della famiglia RelDox, sessione F034. Solo italiano, rapporti brevi e concreti.
> Repository `ideatechnologiessrl-IT/A-010---MANUALISTICA-E-FORMAZIONE`, ramo `sessione/F031` (RelDoxAcademy e RelDoxAcademy Diagnostica **0.1.19A**); `main` = 0.1.10A; sul sito www.reldoxacademy.it è installata la **0.1.18A** (dal 24/11/2026 13:35): la console SQL non si usa sul sito e la 0.1.19A non si installa finché la revisione a freddo non dà 0 alti e 0 medi.
> Leggi per primi, in ordine: `docs/DECISIONI-CHIUSE.md`, `CLAUDE.md`, `docs/APERTURA-F034.md`; confronta il manifesto e rispondi alla prova di lettura. Leggi i quattro registri (`docs/BUG-FORMAZIONE.md`, `docs/ROADMAP.md`, `docs/VIOLAZIONI-FORMAZIONE.md`, `docs/FALLIMENTI-FORMAZIONE.md`) e dillo in una riga «Registri letti: …».
> Aggancia il nodo `ideatechnologiessrl-IT/RelDox-Famiglia` (in scrittura: scrivi solo in `bacheca/formazione/`) e fai l apertura: dal nodo `python3 -I strumenti/decisioni-chiuse.py <A-010> --aperti <A-010>/docs/ROADMAP.md <A-010>/docs/BUG-FORMAZIONE.md` (VERDE), poi `python3 strumenti/apertura-nodo.py --prodotto formazione` (leggi i messaggi nuovi, rispondi, `--segna`; alla chiusura `--chiusura` VERDE) e `python3 -I strumenti/riga-registri.py . <nodo>/bacheca/formazione/STATO.md` (VERDE).
> Lavoro, in ordine: 1) prepara il banco (MariaDB locale, utente `banco`/`banco`, come dice l APERTURA § 00) e controlla che `php banco/F033-banco-console-sql.php reldox-academy-diagnostica` dia 136 verdi; 2) fai la **dodicesima revisione a freddo** della console SQL sulla 0.1.19A con un agente nuovo che non ha visto il lavoro (riceve solo il plugin di diagnostica, il mockup della console, gli estratti delle regole e i sostituti di WordPress; database `rev12_*`; deve riprovare anche le istruzioni R11-1 … R11-8 scritte in BUG-6); 3) se trova difetti alti o medi: banco prima del motore (rosso, poi motore, poi verde, trappola), poi revisione di nuovo, fino a 0 alti e 0 medi; bassi e dubbi in una voce nuova con le sue stelle; 4) per ultimo, una volta sola, i sabotaggi (`banco/F033-sabotaggi-console-sql.py`, voce F51: oggi 122 su 123); 5) con 0 alti e 0 medi: guarda la pagina in WordPress e prepara lo smoke dell owner, portandogli insieme le scelte sui nomi segreti (F48 a, F49 g, F50 e, F54 g).
> Regole da ricordare: i due plugin viaggiano sempre insieme, stesso numero, nello stesso kit; kit a quattro buste con `banco/F013-kit.py`, e a ogni kit il prompt completo della sessione dopo per intero nel messaggio di consegna e come file `PROMPT-F035.md`; i registri si scrivono subito, ogni voce nuova con le sue stelle, e a ogni kit i due registri (`docs/ROADMAP.md`, `docs/BUG-FORMAZIONE.md`) si riportano in `main` senza codice; mai due banchi della console insieme sullo stesso MariaDB; le attese lunghe (revisione circa 50 minuti, sabotaggi circa un ora) si dicono all owner prima di cominciarle; riga «Contesto: ~NN% · soglia kit 70%» a ogni consegna, e al 70% ci si ferma e si compone il kit.
> File da allegare: nessuno, tutto è nel repository e nel nodo (il kit K0037 è la copia per l area del progetto).

*⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale*
