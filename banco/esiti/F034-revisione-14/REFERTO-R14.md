# Referto della revisione a freddo R14 — Console SQL (RelDoxAcademy 0.1.20A, sola lettura, maschera per difetto)

> Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Prove scritte: 273 · difetti: 3 alti, 0 medi, 5 bassi, 1 dubbi

Palco: `prove/palco.sql` (rev14_base, rev14_s3, rev14_wp: colonne innocue, ambigue e segrete, JSON, chiave/valore, MyISAM, MERGE anche su un altro database, partizionata, viste anche su altro database, colonna generata, trigger, funzione, sequenza, indici su colonne segrete).
Banco: `prove/banco.php <file>` carica `sostituti-wordpress.php` e il motore vero e chiama `Rdacademy_Console_SQL::esegui()` come la porta AJAX (consenti=false, azione=esegui).
File di prove: `prove/p01-variabili.txt` (34), `p02-colonne.txt` (63), `p03-show-is.txt` (98), `p04-scritture-tempi.txt` (36), `p05-tempi.txt` (3), `p06-varie.txt` (39).

---

## R14-1 · ALTO — variabili e funzioni del server escono in chiaro come numeri o date (variante di R13-4)
**Che cosa succede.** Un espressione senza nome d origine si vede se il suo tipo è numero o data e l alias non ha parole spia (`leggi_su`, riga 471). Basta trasformare il testo segreto in numero: l alias non conta più, il valore esce intero.
**Istruzioni esatte (sorgente base, nessun palco):**
- `SELECT CAST(CONV(HEX(SUBSTR(@@datadir,1,7)),16,10) AS UNSIGNED) AS n1, CAST(CONV(HEX(SUBSTR(@@datadir,8,7)),16,10) AS UNSIGNED) AS n2, CAST(CONV(HEX(SUBSTR(@@datadir,15,7)),16,10) AS UNSIGNED) AS n3`
  → `13359484805016681 | 27636694953193836 | 47`, cioè in esadecimale `2F7661722F6C69` `622F6D7973716C` `2F` = «/var/lib/mysql/» intero.
- `SELECT ASCII(@@datadir) AS n` → 47; `SELECT ORD(SUBSTR(@@datadir,2,1)) AS n` → 118; `SELECT ASCII(SUBSTR(@@general_log_file,1,1)) AS n` → 118; `SELECT ASCII(@@version_comment) AS n` → 85; `SELECT ASCII(SUBSTR(DATABASE(),1,1)) AS n` → 114.
- Lunghezze e impronte: `LENGTH(@@datadir) AS n` → 15 (la parola spia «length» è solo nel nome della funzione, non nell alias), `BIT_LENGTH` → 120, `OCTET_LENGTH` → 15, `CRC32(@@datadir) AS n` → 2313934553, `UNCOMPRESSED_LENGTH(@@datadir) AS n`, `STRCMP(@@datadir,'/var/lib/mysql/') AS n` → 0, `INSTR(...)`, `@@datadir LIKE '/var%' AS n` → 1.
- Date: `SELECT FROM_UNIXTIME(CONV(HEX(SUBSTR(@@datadir,1,3)),16,10)) AS quando` → 1970-02-06 01:01:37 (= 3 byte); `SELECT MAKEDATE(2000, ASCII(@@datadir)) AS quando` → 2000-02-16.
- Mescolata a una colonna innocua: `SELECT importo * ASCII(@@datadir) AS importo FROM innocua` → 470, 940.
Vale per ogni variabile (`@@init_connect`, `@@report_password`, `@@log_error`… con alias non presenti nella mappa) e per `DATABASE()`. Le stesse istruzioni come condizione (`WHERE ASCII(@@datadir) = 47`, `HAVING`, `ORDER BY ASCII(@@datadir)*id`) sono oracoli, ma servono a poco perché il valore esce già.
**Uscita osservata:** VERDE, «Colonne segrete mascherate: nessuna in questo risultato». **File:** `prove/p01-variabili.txt` (prove 3-7, 9, 12-14, 17-18, 25, 28-32, 34), `prove/p06-varie.txt` (prove 1-4, 6, 10-12, 14, 24, 27).

## R14-2 · ALTO — una vista con CURRENT_USER() mostra l utente della sorgente in una colonna innocua
**Che cosa succede.** Il controllo delle viste (righe 986-991) cerca solo parole che siano colonne segrete, altre viste, funzioni memorizzate, colonne generate o database diversi. `CURRENT_USER()` è vietato nell istruzione (VIETATI_OVUNQUE) ma non nella definizione di una vista; la colonna della vista si chiama `nome` (innocua), quindi esce in chiaro.
**Palco minimo:** `CREATE VIEW v_user AS SELECT CURRENT_USER() AS nome, DATABASE() AS titolo;` (in rev14_base).
**Istruzione:** `SELECT * FROM v_user` (sorgente base).
**Uscita osservata:** `nome | titolo` → `banco@localhost | rev14_base`, VERDE, nessuna maschera. L utente della sorgente deve uscire solo come «presente». (`SESSION_USER()` in una vista invece è rifiutata per caso, perché la parola `session_user` è anche il nome di una colonna segreta di qualche tabella.) **File:** `prove/p02-colonne.txt` prova 29, `prove/p06-varie.txt` prove 7 e 9.

## R14-3 · ALTO — numeri segreti con nomi senza parola spia (R13-3 ancora presente con varianti)
**Che cosa succede.** Le parole spia aggiunte (code, cvc, puk, secur) coprono i nomi della R13, ma altri nomi comuni di numeri segreti non hanno una parola spia e, essendo numeri, escono in chiaro anche con `SELECT *`.
**Palco minimo:** `CREATE TABLE cliente (id INT PRIMARY KEY, csc INT, ccv INT, nip INT, ssn BIGINT, pan BIGINT, cvn INT);` con valori.
**Istruzione:** `SELECT csc, ccv, nip, ssn, pan, cvn FROM cliente` (e `SELECT * FROM cliente`).
**Uscita osservata:** `111 | 222 | 333 | 123456789 | 4111111111111111 | 444`, VERDE. Il numero di carta (`pan`) e il codice di sicurezza (`csc`/`ccv`/`cvn`) escono interi.
Nota: la regola dell owner, presa alla lettera, ammette i numeri senza parola spia; il difetto è che l elenco delle parole spia è aperto («pin, chiave, key, codice, token…») e lascia fuori nomi diffusi di segreti numerici. Classe come R13-3. **File:** `prove/p02-colonne.txt` prove 2 e 12, `prove/p06-varie.txt` prova 36.

## R14-4 · BASSO — rifiuti a torto per alias o parole che sono colonne o viste altrove
Un alias o una parola che coincide col nome di una colonna non innocua di qualunque tabella del server (anche di `sys` o information_schema) fa rifiutare l istruzione come «colonne segrete citate»; una funzione che si chiama come una vista di `sys` è presa per una vista.
Istruzioni: `SELECT ... AS a, ... AS b, ... AS c` → «Colonne segrete citate (a, b, c)»; `SELECT c.nome FROM cliente c`, `WITH x AS (SELECT id FROM cliente) SELECT * FROM x` → «(x)»; `SELECT ASCII(VERSION()) AS n` → «Viste … (version)»; `SHOW FUNCTION STATUS`, `SHOW CREATE FUNCTION f_seg` → «(function)»; `SHOW EVENTS` → «(events)»; `SHOW PRIVILEGES` → «(privileges)»; `SELECT * FROM information_schema.GLOBAL_VARIABLES WHERE VARIABLE_NAME='DATADIR'` → «(variable_name)». Il messaggio è sbagliato (non sono colonne segrete) e non dice come rimediare. **File:** `p01` prove 8, 10, 11, 16, 19, 20, 33; `p02` prove 6, 26, 27, 44; `p03` prove 9, 12, 39, 40, 56; `p04` prove 31, 33.

## R14-5 · BASSO — DESC SELECT salta i controlli su MERGE e partizioni
I controlli su tabelle MERGE/FEDERATED e partizionate si fanno solo se il verbo non è SHOW, DESCRIBE o DESC (riga 949); ma `DESC SELECT …` è un EXPLAIN. Istruzioni: `DESC SELECT * FROM part_t WHERE id = 1` e `DESC SELECT * FROM arch_all` → VERDE col piano (righe stimate della MERGE e della partizionata), mentre `EXPLAIN SELECT * FROM arch_all` e `EXPLAIN SELECT * FROM part_t WHERE num < 100` sono rifiutati. Nessun valore esce (il piano MariaDB non mostra le partizioni), quindi solo incoerenza. **File:** `p03` prove 85-88.

## R14-6 · BASSO — SHOW OPEN TABLES e information_schema mostrano nomi di altri database sotto il referto della sorgente
`SHOW OPEN TABLES` (sorgente base) → 293 righe con `Database | Table` di tutti i database del server (sys, altri database non della sorgente), referto «Lettura eseguita su rev14_base @ localhost». Lo stesso con `SELECT * FROM information_schema.SCHEMATA`, `TABLE_CONSTRAINTS`, `TABLES` (nomi, righe, AUTO_INCREMENT, date di modifica degli altri database). information_schema è un eccezione dichiarata («e information_schema»), SHOW OPEN TABLES no: il referto dichiara un database solo ma i dati sono di tutto il server. Sono nomi, non valori. **File:** `p03` prove 18, 49, 64, 79.

## R14-7 · BASSO — EXPLAIN UPDATE / DELETE: messaggio impreciso
`EXPLAIN UPDATE innocua SET nome='z'` e `EXPLAIN DELETE FROM innocua` passano la classificazione come letture e arrivano al server, che risponde «(1792) Cannot execute statement in a READ ONLY transaction»: nessuna scrittura (la difesa in profondità tiene), ma il messaggio non dice che la console è in sola lettura; `EXPLAIN INSERT … VALUES` invece è rifiutato con «INTO in una lettura… non scrive file né variabili», che è impreciso. **File:** `p03` prove 95-97.

## R14-8 · BASSO — riga finale VERDE «troncato» anche quando il server ha interrotto l istruzione per tempo
`SELECT * FROM seq_1_to_100000000000` (motore Sequence): la console prende 1000 righe, poi `free()` scarica il resto finché il server la ferma a 10 s; la risposta arriva dopo 10,1 s ed è VERDE «solo le prime 1000 righe, risultato troncato», senza dire che l istruzione è stata interrotta per tempo massimo. Il tempo massimo tiene (nessuna attesa oltre 10 s); solo il referto è incompleto. **File:** `p05` prova 1 (anche `p04` prova 34: 7,7 s per scaricare 100 milioni di righe dopo le prime 1000).

## R14-9 · DUBBIO — host senza porta nel referto
Il referto dichiara «<database> @ <host>» senza la porta (`$at`, riga 926; `host_porta` la separa e la scarta). Due sorgenti sullo stesso host con porte diverse e database con lo stesso nome avrebbero lo stesso referto. Qui tutte le sorgenti sono su localhost:3306: non verificabile.

---

## Che cosa ha tenuto (nessun difetto)
- Sola lettura: INSERT, REPLACE, UPDATE, DELETE, WITH … UPDATE, «(UPDATE …)», NEXTVAL/SETVAL/NEXT VALUE FOR anche fra apici inversi, GET_LOCK anche fra apici inversi, FOR UPDATE, LOCK IN SHARE MODE, INTO OUTFILE, INTO @a, CALL, SET, DO, HANDLER, LOAD DATA, CREATE TEMPORARY: tutti rifiutati; dopo le prove `innocua` e la sequenza `seq1` sono invariate (verificato da fuori). EXPLAIN UPDATE/DELETE fermati dal server (READ ONLY).
- Tempo massimo: SLEEP e BENCHMARK rifiutati (anche fra apici inversi); prodotti cartesiani e sottoquery pesanti fermati a 10 s (errore 1969), anche dietro una vista.
- Colonne citate per nome, alias, maiuscole, apici inversi, UNION, sottoquery, WITH, tabelle derivate: rifiutate; `SELECT *` maschera.
- Chiave/valore: valore mascherato per chiave non innocua, anche con alias, senza la chiave accanto, con WHERE sulla chiave; LENGTH(valore), ORDER BY valore, WHERE valore, «valore valore», JOIN su sé stessa: rifiutati.
- Viste su altro database, viste sopra MERGE (anche MERGE che legge un altro database), colonne generate da segrete, funzioni memorizzate: rifiutate.
- Registro: 120 righe lette dopo le prove: nessuna cifra né valore fra apici, né nell istruzione né nel messaggio; sorgente sconosciuta scritta «sconosciuta»; letture non registrate (come previsto).
- Errori: il messaggio del server è tagliato dal primo apice («(1690) DOUBLE value is out of range in «…»»).
- Sorgenti spenta (riserva), non configurata (sorgente_4) e sconosciuta: rifiutate senza ripiego sul BASE.

---

## R13-1 … R13-7
- **R13-1 (colonne di testo con nomi generici):** CORRETTO. `SELECT code, data, message, extra, type, level, ref, field FROM cliente` → rifiutata «Colonne segrete citate»; varianti `code AS id`, `` `code` ``, `c.\`DATA\``, `CODE` rifiutate; con `SELECT *` tutte mascherate. (`p02` prove 1-7)
- **R13-2 (schemi di nome innocui sul testo):** CORRETTO. `id_rsa, database_url, user_data, oracle_dsn, tax_id` rifiutate; `JSON_EXTRACT(user_data,'$.token') AS id` e `JSON_VALUE(user_data,'$.token') AS titolo` rifiutate; con `SELECT *` mascherate. (`p02` prove 8-10)
- **R13-3 (numeri segreti senza parola spia):** CORRETTO per `security_code`, `cvc`, `puk` (rifiutati e mascherati); ANCORA PRESENTE con varianti `csc`, `ccv`, `cvn`, `nip`, `ssn`, `pan` → vedi R14-3. (`p02` prove 11-12)
- **R13-4 (variabili del server con alias innocuo):** CORRETTO nella forma diretta (`SELECT @@datadir AS id`, `AS titolo`, `@@hostname AS titolo`, `LOWER(@@datadir) AS nome`, `AS Tables_in_x`: mascherate); ANCORA PRESENTE con una conversione in numero o data → vedi R14-1. (`p01` prove 1-2, `p06` prove 30-34)
- **R13-5 (vista sopra MERGE su altro database):** CORRETTO. Sorgente 3: `SELECT * FROM v_m` (vista su `m_altro`, MERGE su `rev14_base.arch_a`) e `SELECT * FROM m_altro` rifiutate; `v_altro` (vista su `rev14_base.cliente`) rifiutata. (`p02` prove 56-58)
- **R13-6 (INNODB_SYS_TABLESTATS, INNODB_BUFFER_PAGE):** CORRETTO. Rifiutate anche fra apici inversi e in minuscolo; `information_schema.PARTITIONS` rifiutata anche fra apici inversi. (`p03` prove 65-70)
- **R13-7 (DATA_LENGTH, AVG_ROW_LENGTH, CHECKSUM):** CORRETTO. In `SHOW TABLE STATUS` e `information_schema.TABLES` con `SELECT *` sono mascherate (anche Index_length, Data_free, Max_*); citate per nome (anche `DATA_LENGTH DIV 1`) rifiutate; anche `Cardinality` di SHOW INDEX e information_schema.STATISTICS è mascherata o rifiutata. (`p03` prove 1-3, 47-51)
