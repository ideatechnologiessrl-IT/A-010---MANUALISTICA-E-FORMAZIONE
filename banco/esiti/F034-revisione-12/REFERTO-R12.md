Prove scritte: 327 · difetti: 3 alti, 4 medi, 4 bassi, 2 dubbi

# Referto R12 — revisione a freddo della Console SQL (RelDoxAcademy Diagnostica 0.1.19A, F033)

Ambiente: MariaDB 10.11.14, PHP 8.3.6 CLI, sostituti `sostituti-wordpress.php`. Palco: `prove/palco.sql` + `prove/palco-aggiunte.sql`
(database `rev12_base` = BASE, `rev12_s3` = Sorgente 3, `rev12_wp` = WordPress e `sorgente_wp`). Libreria di prova: `prove/lib.php`
(chiama il vero `Rdacademy_Console_SQL::esegui()`, cerca nell uscita i valori segreti seminati). Uscite: `prove/outNN.txt`.
Il codice non è stato toccato.

---

## ALTI

### R12-1 · alto — colonne segrete con nomi fuori dall elenco escono in chiaro (anche hash WooCommerce e JSON)
**Che cosa succede.** Una colonna è segreta solo se il suo nome contiene un pezzo di `SEGRETE` (pass, token, api_key, hash…).
`chiave_api`, `private_key`, `pin`, `credenziali` (nomi italiani o comuni), la colonna `consumer_key` di WooCommerce (è l hash HMAC
della chiave del cliente) e `truncated_key` non sono nell elenco: escono con SELECT * e anche citate per nome o con un alias.
Anche un segreto dentro una colonna JSON o serializzata di una tabella normale esce intero.
**Palco minimo.**
```sql
CREATE TABLE chiavi (id INT AUTO_INCREMENT PRIMARY KEY, servizio VARCHAR(50), chiave_api VARCHAR(80), private_key TEXT, pin VARCHAR(10), credenziali VARCHAR(80)) ENGINE=InnoDB;
INSERT INTO chiavi (servizio, chiave_api, private_key, pin, credenziali) VALUES ('claude','sk-ant-COLONNA-CHIAVE-API','-----BEGIN PRIVATE KEY-----XYZ','4829','user:PWCRED');
-- su rev12_wp: wp_woocommerce_api_keys (schema WooCommerce), consumer_key = 'HMACCONSUMERKEYHASH…'
CREATE TABLE rdacademy_config (id INT PRIMARY KEY, dati JSON); INSERT INTO rdacademy_config VALUES (1,'{"smtp_password":"JSON-PW-SEGRETA","api_key":"JSON-APIKEY"}');
```
**Istruzioni (sorgente base / wordpress / sorgente_wp).**
`SELECT * FROM chiavi` · `SELECT chiave_api AS x FROM chiavi` · `SELECT * FROM wp_woocommerce_api_keys` ·
`SELECT key_id, consumer_key, truncated_key FROM wp_woocommerce_api_keys` · `SELECT * FROM rdacademy_config`
**Uscita osservata.**
`[["1","claude","sk-ant-COLONNA-CHIAVE-API","-----BEGIN PRIVATE KEY-----XYZ","4829","user:PWCRED"]]` con «Colonne segrete mascherate: nessuna»;
`[["1","HMACCONSUMERKEYHASH0123…","abc1234"]]`; `[["1","{\"smtp_password\":\"JSON-PW-SEGRETA\",\"api_key\":\"JSON-APIKEY\"}"]]`.
(Curioso: `SELECT JSON_EXTRACT(dati,'$.smtp_password')` invece viene mascherato, solo perché il testo dell espressione contiene «pass».)
**File:** `prove/p01_lettura_base.php` (L13–L15), `prove/p02_kv_wp.php` (K6, K7, K9), `prove/p12_ultime.php` (J1, J2).

### R12-2 · alto — tabelle chiave/valore: valori di chiavi segrete con nomi italiani escono in chiaro
**Che cosa succede.** `segreta_kv()` riconosce la chiave segreta da parole inglesi (key, secret, auth, smtp, licen…) e il valore da
pass/token/secret/segret/api_key… Mancano «chiave», «parola», «pin», «bearer», «sk_» e simili: in `rdacademy_impostazioni`
e in `wp_options` i valori escono in chiaro, anche quelli serializzati o JSON che contengono «chiave»/«parola».
**Palco minimo.**
```sql
INSERT INTO rev12_base.rdacademy_impostazioni (chiave, valore) VALUES ('claude_chiave','sk-ant-api03-QWERTY999'),('ftp_parola','Rondine77'),
 ('posta_accesso','{"utente":"info","parola":"Gabbiano88"}'),('pin_firma','99173'),('bearer_claude','eyJhbGciOiJIUzI1NiJ9.abc');
INSERT INTO rev12_wp.wp_options (option_name, option_value) VALUES ('rdacademy_chiave_api','CHIAVE-API-IN-OPTIONS'),('stripe_live_sk','sk_live_51ABCDEF'),
 ('rdacademy_impostazioni','a:2:{s:6:"chiave";s:12:"CHIAVE-OPZ-1";s:6:"parola";s:9:"Pinguino9";}');
```
**Istruzioni.** base: `SELECT chiave, valore FROM rdacademy_impostazioni`; wordpress: `SELECT option_name, option_value FROM wp_options`,
`SELECT * FROM wp_options`, `SELECT option_value, option_name FROM wp_options WHERE option_name LIKE 'stripe%'`.
**Uscita osservata.** `["claude_chiave","sk-ant-api03-QWERTY999"]`, `["ftp_parola","Rondine77"]`, `["posta_accesso","{\"utente\":\"info\",\"parola\":\"Gabbiano88\"}"]`,
`["pin_firma","99173"]`, `["bearer_claude","eyJhbGciOiJIU…"]`; da WordPress `["rdacademy_chiave_api","CHIAVE-API-IN-OPTIONS"]`,
`["stripe_live_sk","sk_live_51ABCDEF"]`, `["rdacademy_impostazioni","a:2:{…\"chiave\";s:12:\"CHIAVE-OPZ-1\";…\"parola\";s:9:\"Pinguino9\";}"]`.
**File:** `prove/p02_kv_wp.php` (K1, K2, K3, K14, K15, K16).

### R12-3 · alto — scrittura reale nel database di WordPress (e referto falso) con le cascate delle chiavi esterne
**Che cosa succede.** `controlla_scrittura()` guarda solo le tabelle nominate nell istruzione. Una chiave esterna `ON DELETE/ON UPDATE CASCADE`
da una tabella di un altro database (o, sulla sorgente `sorgente_wp`, da una tabella `wp_…` verso una `rdacademy_…`) fa scrivere il server
dove la console non deve: dopo CONFERMO le righe di WordPress sono cancellate/cambiate davvero. Prova a secco, CONFERMO, referto e registro
dichiarano «1 righe» su `rev12_base @ localhost` e non dicono né il database colpito né le righe in cascata.
**Palco minimo.**
```sql
CREATE TABLE rev12_base.padre (id INT PRIMARY KEY, n VARCHAR(20)) ENGINE=InnoDB; INSERT INTO rev12_base.padre VALUES (1,'p1'),(2,'p2');
CREATE TABLE rev12_wp.figlio_fk (id INT AUTO_INCREMENT PRIMARY KEY, padre_id INT,
  FOREIGN KEY (padre_id) REFERENCES rev12_base.padre(id) ON DELETE CASCADE ON UPDATE CASCADE) ENGINE=InnoDB;
INSERT INTO rev12_wp.figlio_fk (padre_id) VALUES (1),(1),(2);
-- variante su sorgente_wp:
CREATE TABLE rev12_wp.rdacademy_padre (id INT PRIMARY KEY) ENGINE=InnoDB; INSERT INTO rev12_wp.rdacademy_padre VALUES (1),(2);
CREATE TABLE rev12_wp.wp_postmeta_fk (id INT AUTO_INCREMENT PRIMARY KEY, rp INT, FOREIGN KEY (rp) REFERENCES rdacademy_padre(id) ON DELETE CASCADE) ENGINE=InnoDB;
INSERT INTO rev12_wp.wp_postmeta_fk (rp) VALUES (1),(1),(2);
```
**Istruzioni** (prova a secco, poi CONFERMO): base `DELETE FROM padre WHERE id = 1`; base `UPDATE padre SET id = 20 WHERE id = 2`;
sorgente_wp `DELETE FROM rdacademy_padre WHERE id = 1`.
**Uscita osservata.** `figli prima: 3` → `VERDE: DELETE eseguita su rev12_base @ localhost: 1 righe scritte` → `figli dopo: 1`;
dopo l UPDATE `rev12_wp.figlio_fk` = `[[3,20]]`; `wp_postmeta_fk` da 3 a 1 righe. Registro: `DELETE FROM padre WHERE id = … ¦ scritta`, db `rev12_base`.
**File:** `prove/p03_scritture.php` (W1, W1b), `prove/p09_varie.php` (P4).

---

## MEDI

### R12-4 · medio — tabella MERGE (MRG_MyISAM) del BASE legge un altro database con il referto del solo BASE (variante di R11-6)
**Palco minimo.** `CREATE TABLE rev12_s3.dati_s3 (id INT, info VARCHAR(50)) ENGINE=MyISAM; INSERT INTO rev12_s3.dati_s3 VALUES (1,'DATO-DI-S3-RISERVATO');`
`CREATE TABLE rev12_base.mrg_s3 (id INT, info VARCHAR(50)) ENGINE=MRG_MyISAM UNION=(rev12_s3.dati_s3);`
**Istruzione** (base): `SELECT * FROM mrg_s3`
**Uscita osservata.** `VERDE: Lettura eseguita su rev12_base @ localhost: 1 righe.` dati `[["1","DATO-DI-S3-RISERVATO"]]`. Il controllo delle viste
(`vista_fuori_db`) non guarda le tabelle MERGE (né altri motori che leggono fuori, come FEDERATED/CONNECT/SPIDER se installati).
Le colonne segrete con nome noto restano mascherate (`mrg_s3k` sopra `myi_s3`).
**File:** `prove/p01_lettura_base.php` (L42–L44, L54), `prove/p12_ultime.php` (E3).

### R12-5 · medio — oracolo d uguaglianza sui segreti nella prova a secco, tramite vincoli FOREIGN KEY e UNIQUE
**Che cosa succede.** La prova a secco riesce o fallisce (o cambia il conteggio) secondo il valore segreto, senza citarlo.
(a) Una tabella senza colonne segrete ha una FK verso una colonna segreta: inserire un valore indovinato riesce, uno sbagliato dà 1452.
(b) Un indice UNIQUE sul valore chiave/valore: 1062 contro riuscita, INSERT IGNORE 0 contro 1 riga, REPLACE 2 contro 1 riga.
**Palco minimo.**
```sql
CREATE TABLE sessioni (id INT PRIMARY KEY, token VARCHAR(40), KEY(token)) ENGINE=InnoDB; INSERT INTO sessioni VALUES (1,'TOKEN-SESSIONE-ABC');
CREATE TABLE accessi (id INT AUTO_INCREMENT PRIMARY KEY, rif_sessione VARCHAR(40), FOREIGN KEY (rif_sessione) REFERENCES sessioni(token)) ENGINE=InnoDB;
CREATE TABLE uniq_val (id INT AUTO_INCREMENT PRIMARY KEY, chiave VARCHAR(50) UNIQUE, valore VARCHAR(100), UNIQUE KEY (valore)) ENGINE=InnoDB;
INSERT INTO uniq_val (chiave, valore) VALUES ('smtp_password','Segretissima1');
```
**Istruzioni** (base, «prova»): `INSERT INTO accessi (rif_sessione) VALUES ('TOKEN-SESSIONE-ABC')` → INFO, prova riuscita;
`INSERT INTO accessi (rif_sessione) VALUES ('TOKEN-SESSIONE-ABD')` → `ROSSO: Prova a secco non riuscita: (1452) Cannot add or update a child row…`;
`INSERT IGNORE INTO uniq_val (chiave, valore) VALUES ('nuova','Segretissima1')` → `righe_prova=0`, con `'Segretissima2'` → `1`;
`REPLACE INTO uniq_val (chiave, valore) VALUES ('nuova','Segretissima1')` → `2`, con `'Segretissima2'` → `1`.
**File:** `prove/p03_scritture.php` (W2a–W2c), `prove/p04_scritture2.php` (U0–U6).

### R12-6 · medio — oracolo per intervallo sul segreto con tabelle partizionate per la colonna segreta
**Palco minimo.** `CREATE TABLE part_tok (id INT, nome VARCHAR(20), token VARCHAR(40)) ENGINE=InnoDB PARTITION BY RANGE COLUMNS(token) (PARTITION p_am VALUES LESS THAN ('n'), PARTITION p_nz VALUES LESS THAN (MAXVALUE)); INSERT INTO part_tok VALUES (1,'uno','abc-segreto'),(2,'due','xyz-segreto');`
**Istruzioni** (base): `SELECT id, nome FROM part_tok PARTITION (p_nz)` → `[["2","due"]]` (quindi il token della riga 2 è ≥ 'n');
`SELECT * FROM part_tok PARTITION (p_am)` → riga 1; `SELECT PARTITION_NAME, TABLE_ROWS FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA='rev12_base' AND TABLE_NAME='part_tok'` → conteggi per intervallo.
Senza chiave primaria la tabella non è fermata dal controllo delle chiavi primarie segrete; anche l ordine delle righe di `SELECT *` segue le partizioni.
**File:** `prove/p01_lettura_base.php` (L35–L37, L45, L46).

### R12-7 · medio — registro con valori in chiaro (variante di R11-5)
**Che cosa succede.** `senza_valori()` toglie stringhe e numeri isolati, ma le parole con cifre fuori dagli apici restano intere, e il campo
«sorgente» di una sorgente sconosciuta è scritto così come arriva dalla richiesta.
**Istruzioni** (base, tutte rifiutate e quindi registrate):
`SELECT * FROM utenti WHERE nome = IT60X0542811101000000123456 INTO OUTFILE '/tmp/x'` → registro: `… WHERE nome = IT60X0542811101000000123456 INTO OUTFILE '…'`;
`SELECT 1 FROM t WHERE x = sk_live_ABCDEF123 INTO @x` → `… x = sk_live_ABCDEF123 …`; `SELECT 1 FROM t WHERE x = Rossi.Mario4829 INTO @x`;
`DROP TABLE x4829`; `SELECT ١٢٣ FROM t` (cifre arabo-indiche in chiaro). Sorgente `sorgente_3 4829 password=Rondine77` con `SELECT 1`
→ riga di registro con `sorgente = 'sorgente_3 4829 password=Rondine77'`.
**File:** `prove/p05_registro.php` (righe REG in `prove/out05.txt`).

---

## BASSI

### R12-8 · basso — oracolo grossolano sulla lunghezza dei segreti (tempo nel referto, statistiche delle tabelle)
Il referto dichiara i millisecondi della lettura, che con MYSQLI_USE_RESULT comprendono il trasferimento dei valori mascherati.
Palco: `CREATE TABLE lung (id INT PRIMARY KEY, nome VARCHAR(10), token LONGTEXT); riga 1 token di 15 MB, riga 2 'corto'`.
`SELECT * FROM lung WHERE id = 1` → «… 42–48 ms», `id = 2` → «0 ms» (stessa uscita mascherata). Si vede solo con valori di MB;
`information_schema.TABLES.AVG_ROW_LENGTH/DATA_LENGTH` e `SHOW TABLE STATUS` danno la stessa indicazione grossolana. Il tetto in byte invece è a posto (R11-8).
**File:** `prove/p10_lunghezza.php` (`prove/out10.txt`), `prove/p01_lettura_base.php` (L47, L50).

### R12-9 · basso — rifiuti a torto e messaggi del server al posto di quelli della console
`EXPLAIN UPDATE innocua SET a = 1`, `EXPLAIN DELETE FROM utenti` → `ROSSO: (1792) Cannot execute statement in a READ ONLY transaction`
(passano i controlli e finiscono contro il server); `EXPLAIN INSERT INTO innocua (a) VALUES (1)` → «INTO in una lettura rifiutato»;
`INSERT INTO utenti PARTITION (p0) (id) VALUES (53)` → rifiutato come «senza elenco delle colonne».
**File:** `prove/p09_varie.php` (X53–X55), `prove/p12_ultime.php` (E1), `prove/p03_scritture.php` (R3-9).

### R12-10 · basso — WITH ROLLUP e nomi di colonna come `update` classificati male
`SELECT nome, COUNT(*) FROM ord GROUP BY nome WITH ROLLUP` → «solo SELECT semplice, senza UNION, WITH né sottoquery» (non c è nessuna
WITH: è ROLLUP); `WITH a AS (SELECT 1 AS \`update\`) SELECT * FROM a` → trattata come scrittura («Scrittura non consentita»).
**File:** `prove/p12_ultime.php` (E2, E5), `prove/p01_lettura_base.php` (L73).

### R12-11 · basso — la maschera chiave/valore dipende dal contenuto del valore
Sotto una chiave innocua il valore è mascherato solo se contiene pass/token/secret/segret/api_key…: il fatto stesso che sia mascherato dice
qualcosa del valore (es. `nome_sito` → in chiaro, una chiave innocua con valore «…SEGRETA» → mascherata). Indizio minimo, nessun valore esce.
**File:** `prove/p01_lettura_base.php` (L16, L17), `prove/p02_kv_wp.php` (K14: `stripe_sk` mascherata, `stripe_live_sk` no).

---

## DUBBI

### R12-12 · dubbio — `sorgente_wp` scrive nel database di WordPress
Con la sorgente «6 · Archivio nel database di WordPress» la console ammette scritture sulle tabelle `rdacademy_…` di `rev12_wp`
(`INSERT INTO rdacademy_wpx (a) VALUES ('due')` → prova a secco riuscita). Il codice lo fa per scelta (0.1.12A); gli estratti delle regole
dicono «database di WordPress sempre in sola lettura». Da decidere con l owner; intanto è la porta usata da R12-3.
**File:** `prove/p09_varie.php` (P1, P2, P3).

### R12-13 · dubbio — MySQL 8 e UDF (non verificabile qui)
(a) In scrittura si chiama solo `SET SESSION max_statement_time = 3` (riga 969), senza ripiego: su MySQL 8 quella variabile non c è e
MAX_EXECUTION_TIME vale solo per le SELECT, quindi la prova a secco non avrebbe il tetto dell istruzione; la console smette di aspettare a 10 s
ma il server continua a scrivere e poi annulla. (b) Le funzioni UDF (`mysql.func`, es. sys_exec) non compaiono in `information_schema.ROUTINES`:
non sarebbero riconosciute come funzioni memorizzate. Qui non ci sono né MySQL 8 né UDF (`SYS_EXEC` → 1305 «does not exist»).

---

## R11-1 … R11-8

| voce | esito | prova |
|---|---|---|
| R11-1 alias sulla chiave | **non più presente** | `SELECT option_name AS n, option_value FROM wp_options` maschera chiave e valori segreti (K4); `SELECT chiave AS k, valore …` uguale (L18). Ma vedi R12-2 per le chiavi con nomi non riconosciuti. |
| R11-2 alias `O`/`o` | **non più presente** | `SELECT O.chiave, o.valore FROM rdacademy_impostazioni O JOIN rdacademy_impostazioni o ON o.id = O.id + 1` → tutti i valori mascherati (L24); `A`/`a` con virgola uguale (L23). |
| R11-3 elenco colonne vuoto | **non più presente** | `INSERT INTO utenti () VALUES ()`, `REPLACE … ()`, `VALUES ()`, `( )`, `(/**/)`, `VALUE ()`, nomi qualificati e fra apici, `rdacademy_impostazioni () …` e senza elenco: tutti rifiutati (R3-0…R3-13, `p03`). |
| R11-4 ordine lungo l indice segreto | **non più presente** | `p06`: `SELECT id FROM ord LIMIT 5` dà 1..5 (senza console l indice coprente dà 168,138,134,27,195); alias, apici, nome qualificato, parentesi, commenti, a capo, alias `limit`/`where`/`as`, JOIN/STRAIGHT_JOIN/virgola: IGNORE INDEX inserito; FORCE/USE INDEX rifiutati; vista sopra l indice rifiutata dal piano; chiave unica segreta senza PK rifiutata. Resta l ordine per partizioni (R12-6). |
| R11-5 numeri nel registro | **non più presente** nelle forme indicate | `4829 SELECT 1` → `… SELECT …`; `4829DROP` → `… DROP`; `$4829` → `$…`; `€4829` → `€…`; `-4829`, `.4829e3`, `0x…`, `0b…` tolti (`out05.txt`). Nuova variante con parole che contengono cifre e campo sorgente: R12-7. |
| R11-6 vista che legge un altro database | **non più presente** per le viste | `vista_s3`, `v_info` (information_schema), `v_sub` (sottoquery verso rev12_s3) rifiutate (L41, V0, V1). Ma la tabella MERGE fa lo stesso: R12-4. |
| R11-7 prova a secco pesante | **non più presente** | `UPDATE big SET x = x + 1` su 2 milioni di righe 9,4 s; `DELETE FROM big WHERE x > 0` 9,5 s; con tre indici in più 5,8 s e 6,3 s; sempre ROSSO entro 10 s, nessun processo né transazione rimasti, lucchetto su `big` libero subito (`p07`, `p11`). Nota: 9,4 s è vicino al tetto di 10 s. |
| R11-8 tetto in byte prima della maschera | **non più presente** | `gros` con 600 segreti da 10 KB (6 MB) e `gros2` con segreti di 1 byte: entrambe 900 righe, nessun troncamento, uscita identica (B1, B2). Resta il tempo nel referto (R12-8, basso). |
