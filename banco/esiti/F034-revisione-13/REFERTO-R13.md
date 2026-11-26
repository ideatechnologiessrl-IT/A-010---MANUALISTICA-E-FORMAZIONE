# Referto della revisione a freddo R13 — Console SQL (RelDoxAcademy Diagnostica 0.1.20A)

> ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.

Prove scritte: 559 · difetti: 4 alti, 3 medi, 3 bassi, 2 dubbi

Ambiente: MariaDB 10.11.14, PHP 8.3 CLI, sostituti di `sostituti-wordpress.php`; palco in `prove/palco.sql` (database `rev13_base`, `rev13_s3`, `rev13_wp`). Le prove chiamano `Rdacademy_Console_SQL::esegui()` vero (`prove/base.php`). Sul server ci sono anche altri database (rev12_*, academy_base): non toccati, ma la console li vede e ne è influenzata (R13-9, R13-10).

---

## ALTI

### R13-1 — alto — colonne di testo con nomi «innocui» generici escono in chiaro
**Che cosa succede.** L elenco `INNOCUE` contiene nomi che in inglese sono contenitori generici: `code`, `message`, `extra`, `data`, `type`, `level`, `ref`, `field` (e `null`, `table`, `database`, `seq`…). Una colonna di testo con uno di questi nomi esce in chiaro, con `SELECT *` e citata per nome, ed è usabile in espressioni (`JSON_EXTRACT`). La parola spia `codice` c è, `code` no.
**Palco minimo.** `CREATE TABLE utenti (id INT PRIMARY KEY, code VARCHAR(20), data TEXT, message TEXT, extra VARCHAR(100)); INSERT INTO utenti VALUES (1,'OTP-777111','{"password":"Dati_segreti"}','Il tuo codice di accesso e 991122','{"api":"sk_extra"}');`
**Istruzioni.** `SELECT * FROM utenti` · `SELECT id, code FROM utenti WHERE id = 1` · `SELECT id, data FROM utenti WHERE id = 1`
**Uscita osservata.** `code | OTP-777111`, `data | {"password":"Dati_segreti"}`, `message | Il tuo codice di accesso e 991122`, `extra | {"api":"sk_extra"}`, `ref | ref_sk_live_X`, `level | lvl_segreto`, `type | tipo_segreto`, `field | field_segreto` — tutte VERDE e in chiaro.
**File di prova.** `prove/p01-maschera-nomi.php`

### R13-2 — alto — gli «schemi di nome innocui» fanno passare in chiaro chiavi private, URL con password, JSON e documenti
**Che cosa succede.** In `innocua()` l espressione `(^|_)id$|^id_|_(at|il|del|data|date|gmt|ora)$|^(data|ora|creat|aggiorn|modific|scad|inizio|fine)|^(num|numero|conta|totale|n)_` vale per colonne di **qualunque tipo**, testo compreso: `^id_` prende `id_rsa`, `^data` prende `database_url`, `_data$` prende `user_data` / `raw_data`, `^ora` prende `oracle_dsn`, `^fine` / `^creat` prendono `fine_config` / `creator_note`, `^numero_` prende `numero_documento`, `_id$` prende `tax_id` (codice fiscale).
**Palco minimo.** `CREATE TABLE utenti (id INT PRIMARY KEY, id_rsa TEXT, database_url VARCHAR(200), user_data JSON, oracle_dsn VARCHAR(200), tax_id VARCHAR(30), numero_documento VARCHAR(30)); INSERT INTO utenti VALUES (1,'-----BEGIN OPENSSH PRIVATE KEY-----AAAA','mysql://root:Pw_Segreta@db/prod','{"token":"tok_SEGRETO"}','oracle://sys:OraPw@h','RSSMRA80A01H501U','AX1234567');`
**Istruzioni.** `SELECT * FROM utenti` · `SELECT id, id_rsa FROM utenti WHERE id = 1` · `SELECT JSON_EXTRACT(user_data, '$.token') AS id FROM utenti`
**Uscita osservata.** `id_rsa | -----BEGIN OPENSSH PRIVATE KEY-----AAAA`, `database_url | mysql://root:Pw_Segreta@db/prod`, `user_data | {"token":"tok_SEGRETO"}`, `oracle_dsn | oracle://sys:OraPw@h`, `tax_id | RSSMRA80A01H501U`, `numero_documento | AX1234567`, `raw_data | raw_SEGRETO`; `JSON_EXTRACT(...) AS id` → `"tok_SEGRETO"`. Tutte VERDE.
**File di prova.** `prove/p01-maschera-nomi.php`

### R13-3 — alto — codici segreti numerici in chiaro: le parole spia non coprono `code`, `cvc`, `puk`
**Che cosa succede.** Un numero con un nome senza parole spia è in chiaro. `SPIA` ha `codice`, `cvv`, `pin` ma non `code`, `cvc`/`csc`, `puk`: `security_code` (CVV), `cvc`, `puk` escono in chiaro.
**Palco minimo.** `CREATE TABLE utenti (id INT PRIMARY KEY, security_code INT, cvc INT, puk BIGINT); INSERT INTO utenti VALUES (1,123,456,12345678);`
**Istruzioni.** `SELECT id, security_code FROM utenti WHERE id = 1` · `SELECT id, cvc FROM utenti WHERE id = 1` · `SELECT * FROM utenti`
**Uscita osservata.** `security_code | 123`, `cvc | 456`, `puk | 12345678` (VERDE).
**File di prova.** `prove/p01-maschera-nomi.php`

### R13-4 — alto — variabili del server in chiaro con un alias innocuo, anche se il loro nome ha una parola spia (SHOW VARIABLES le maschera)
**Che cosa succede.** Un espressione senza nome d origine si giudica dal suo alias: `@@variabile AS id` è in chiaro. Le parole spia nel nome della variabile non contano (`@@report_password` passa), mentre `SHOW VARIABLES` maschera la colonna Value. Escono percorsi del server (`datadir`, `plugin_dir`, `pid_file`, `general_log_file`), `server_uid`, `init_connect` (con la sua lunghezza). Si ferma solo una variabile che per caso ha lo stesso nome di una colonna di qualche tabella (`ssl_key`, `socket`).
**Palco minimo.** `SET GLOBAL init_connect='SET @segreto_init=1'` (poi rimesso vuoto).
**Istruzioni.** `SELECT @@init_connect AS titolo, LENGTH(@@init_connect) AS n` · `SELECT @@report_password AS id` · `SELECT @@datadir AS id` · confronto `SHOW VARIABLES LIKE 'init_connect'`
**Uscita osservata.** `SET @segreto_init=1 | 19` (VERDE); `@@report_password AS id` VERDE (NULL qui, valore in chiaro dove è impostata); `/var/lib/mysql/`, `/usr/lib/mysql/plugin/`, `fXdYp7ZWAMMBm0PUMZFJoAcM0o0=`; `SHOW VARIABLES` → `init_connect | •••• (mascherata)`.
**File di prova.** `prove/p08-variabili.php`

## MEDI

### R13-5 — medio — R12-4 resiste attraverso una vista: dati di un altro database col referto del BASE
**Che cosa succede.** La tabella MERGE è rifiutata se si nomina, ma una vista innocua sopra di essa no: il controllo MERGE guarda solo i nomi dell istruzione, e `vista_fuori_db()` vede solo `rev13_base.unione`.
**Palco minimo.** In `rev13_s3`: `CREATE TABLE altrove (id INT, nome VARCHAR(50), email VARCHAR(80)) ENGINE=MyISAM; INSERT INTO altrove VALUES (1,'ClienteS3','privato@s3.it');` — in `rev13_base`: `CREATE TABLE unione (id INT, nome VARCHAR(50), email VARCHAR(80)) ENGINE=MERGE UNION=(rev13_s3.altrove) INSERT_METHOD=NO; CREATE VIEW v_unione AS SELECT id, nome, email FROM unione;`
**Istruzione.** `SELECT * FROM v_unione` (sorgente BASE)
**Uscita osservata.** `1 | ClienteS3 | privato@s3.it` · `[VERDE] Lettura eseguita su rev13_base @ localhost: 1 righe.` (`SELECT * FROM unione` → ROSSO, corretto).
**File di prova.** `prove/p04-partizioni-merge.php`

### R13-6 — medio — R12-6 resiste: le righe per partizione si leggono da information_schema di InnoDB
**Che cosa succede.** `PARTITION`/`PARTITIONS` sono vietate, ma `INNODB_SYS_TABLESTATS` (NUM_ROWS per `tabella#P#partizione`) e `INNODB_BUFFER_PAGE` (NUMBER_RECORDS, DATA_SIZE per «Partition `p1`») danno quante righe stanno in ogni intervallo della colonna segreta; i nomi delle colonne sono innocui o numeri senza parole spia.
**Palco minimo.** `CREATE TABLE part (id INT, token VARCHAR(20), nome VARCHAR(20)) PARTITION BY RANGE COLUMNS(token) (PARTITION p0 VALUES LESS THAN ('m'), PARTITION p1 VALUES LESS THAN (MAXVALUE)); INSERT INTO part VALUES (1,'zeta','a');`
**Istruzioni.** `SELECT NAME, NUM_ROWS, CLUST_INDEX_SIZE FROM information_schema.INNODB_SYS_TABLESTATS WHERE NAME LIKE 'rev13_base/part%'` · `SELECT TABLE_NAME, NUMBER_RECORDS, DATA_SIZE FROM information_schema.INNODB_BUFFER_PAGE WHERE TABLE_NAME LIKE '%part%'`
**Uscita osservata.** `rev13_base/part#P#p0 | 0 | 1` · `rev13_base/part#P#p1 | 1 | 1`; `` `rev13_base`.`part` /* Partition `p1` */ | 1 | 36 `` e `p0 | 0 | 0` (VERDE): il token è ≥ 'm'. La stessa vista mostra anche le partizioni di altri database del server.
**File di prova.** `prove/p04-partizioni-merge.php`

### R13-7 — medio — oracoli su lunghezza e uguaglianza dei valori segreti da SHOW TABLE STATUS e information_schema.TABLES
**Che cosa succede.** `DATA_LENGTH`, `AVG_ROW_LENGTH` (e `INNODB_BUFFER_PAGE.DATA_SIZE`) sono numeri senza parole spia: in una tabella a righe dinamiche danno la lunghezza del segreto. `CHECKSUM` (MyISAM/Aria con `CHECKSUM=1`) è il checksum vivo del contenuto: rifacendo la tabella altrove con un valore candidato si confronta il numero (oracolo d uguaglianza, a forza bruta su un PIN).
**Palco minimo.** `CREATE TABLE my_segreti (id INT PRIMARY KEY, segreto VARCHAR(100)) ENGINE=MyISAM CHECKSUM=1 ROW_FORMAT=DYNAMIC; INSERT INTO my_segreti VALUES (1,'4829');` e `my_segreti2` uguale con un segreto di 37 caratteri.
**Istruzioni.** `SHOW TABLE STATUS LIKE 'my_segreti%'` · `SELECT TABLE_NAME, DATA_LENGTH, AVG_ROW_LENGTH, CHECKSUM FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'my_%'`
**Uscita osservata.** `my_segreti | 20 | 20 | 2480081823` · `my_segreti2 | 48 | 48 | 1798356088` (VERDE): la differenza di lunghezza è quella del segreto; il checksum identifica il valore.
**File di prova.** `prove/p03-schema-oracoli.php`

## BASSI

### R13-8 — basso — registro: le parole senza cifre fuori dagli apici restano
**Che cosa succede.** `senza_valori()` toglie stringhe e numeri ma lascia le parole: un valore scritto senza apici (per il server un nome) o incollato come prima parola finisce nel registro, anche come «verbo» nel messaggio.
**Istruzioni.** `DELETE FROM utenti WHERE password = PasswordSegretaSenzaCifre` · `SegretoMarioRossi FROM utenti`
**Uscita osservata (registro).** `istruzione: DELETE FROM utenti WHERE password = PasswordSegretaSenzaCifre`; `messaggio: «SEGRETOMARIOROSSI» non ammessa: …`
**File di prova.** `prove/p07-registro.php`

### R13-9 — basso — rifiuti a torto: alias e variabili rifiutati come «colonne segrete» per nomi di colonna di tutto il server
**Che cosa succede.** `mappa_segrete()` legge le colonne di tutti gli schemi (anche `mysql`, `performance_schema`, altri database): un alias di tabella di una lettera (`a`, `b`, `p`, `x`) o una variabile (`@@socket`) è rifiutato se una colonna con quel nome esiste da qualche parte. Anche il letterale esadecimale `x'…'` è letto come nome «x» (messaggio fuorviante).
**Istruzioni.** `SELECT a.id FROM padre a` · `SELECT a.valore, b.chiave FROM rdacademy_impostazioni a JOIN rdacademy_impostazioni b ON a.id = b.id` · `SELECT @@socket AS id` · `SELECT * FROM utenti WHERE codice = x'34383239'`
**Uscita osservata.** `ROSSO | Colonne segrete citate (a)` / `(a, b)` / `(socket)` / `(x)`. Con `u1`/`u2` la stessa unione passa.
**File di prova.** `prove/p09-rifiuti-a-torto.php`, `prove/p02-kv.php`, `prove/p07-registro.php`

### R13-10 — basso — `SELECT VERSION()` rifiutata per la vista `sys.version`
**Che cosa succede.** Le viste di **tutti** gli schemi (anche `sys`, che la console non tocca mai) entrano nel controllo delle viste: ogni parola uguale al nome di una vista di un altro schema fa rifiutare la lettura.
**Istruzione.** `SELECT VERSION() AS id`
**Uscita osservata.** `ROSSO | Viste che leggono colonne segrete … (version)`.
**File di prova.** `prove/p09-rifiuti-a-torto.php`, `prove/p03-schema-oracoli.php`

## DUBBI

### R13-11 — dubbio — funzioni UDF (CREATE FUNCTION … SONAME) non stanno in information_schema.ROUTINES
Il controllo delle funzioni memorizzate guarda solo `ROUTINES`: una UDF installata sul server (per esempio `sys_exec`) non sarebbe riconosciuta e si eseguirebbe in lettura. Qui non c è nessuna UDF (`SELECT sys_exec('id')` → errore 1305 del server, non della console). File: `prove/p06-scritture.php`.

### R13-12 — dubbio — MySQL 8
Non verificabile qui: senza `max_statement_time` resta `MAX_EXECUTION_TIME`, che vale solo per SELECT (SHOW, DESCRIBE, EXPLAIN senza tetto); le tabelle InnoDB di information_schema hanno altri nomi (`INNODB_TABLESTATS`, `INNODB_TABLES`) con gli stessi oracoli di R13-6/R13-7; indici funzionali e `EXPLAIN FORMAT=TREE` non provati.

---

## R12-1 … R12-8

| voce | esito | prova |
|---|---|---|
| R12-1 (alto) | **Corretto per i nomi elencati**: `chiave_api`, `private_key`, `pin`, `credenziali`, `consumer_key`, `truncated_key` mascherate con `SELECT *` e rifiutate se citate; JSON in `chiavi.config` mascherato. **Resiste con varianti** di nome (R13-1, R13-2, R13-3): JSON con segreti in `data`, `user_data`, `extra` in chiaro. | p01 |
| R12-2 (alto) | **Corretto**: `claude_chiave`, `ftp_parola`, `pin_firma`, `bearer_claude`, `stripe_live_sk`, JSON e valori serializzati (`woocommerce_stripe_settings`, `session_tokens`) mascherati; alias sulla chiave, autounione, espressioni, WHERE/ORDER/HAVING sul valore rifiutati. | p02 |
| R12-3 (alto) | **Corretto**: nessuna scrittura (DELETE/UPDATE/INSERT/REPLACE, WITH … DELETE, commenti, parentesi, `consenti`/`prova`/`CONFERMO` forzati, sorgenti BASE, S3 e WordPress): `padre` e `figlio` intatti, sequenza non avanzata, nessun file scritto. `EXPLAIN/DESC DELETE` arriva al server e lo ferma la transazione READ ONLY (errore 1792). | p06 |
| R12-4 (medio) | **Corretto nominando la MERGE**, **resiste attraverso una vista** (R13-5). | p04 |
| R12-5 (medio) | **Non più raggiungibile**: la prova a secco non c è (sola lettura), anche forzando `azione=prova`. | p06 |
| R12-6 (medio) | **Corretto** per `PARTITION (p)`, `information_schema.PARTITIONS` (anche fra apici inversi) e lettura diretta della tabella; **resiste** con `INNODB_SYS_TABLESTATS` e `INNODB_BUFFER_PAGE` (R13-6). | p04 |
| R12-7 (medio) | **Corretto** per le parole con cifre: IBAN, `sk_live_ABCDEF123`, `Mario4829`, cifre arabo-indiche, a tutta larghezza, apici, `½`, carta con trattini e spazi tolti; sorgente sconosciuta scritta «sconosciuta». Resta il residuo delle parole senza cifre (R13-8). | p07 |
| R12-8 (basso) | **Corretto**: il referto non dichiara più i millisecondi. | p01-p09 |
