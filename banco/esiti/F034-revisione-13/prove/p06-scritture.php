<?php require __DIR__ . '/base.php';
$forza = array( 'consenti' => true, 'azione' => 'prova', 'conferma' => 'CONFERMO' );
$forza2 = array( 'consenti' => true, 'azione' => 'esegui', 'conferma' => 'CONFERMO' );
$w = array(
 'DELETE FROM padre WHERE id = 1', 'delete from padre', ' DELETE FROM padre', '(DELETE FROM padre)', '/* x */ DELETE FROM padre', "-- c\nDELETE FROM padre", "# c\nDELETE FROM padre",
 'UPDATE padre SET nome = \'x\'', 'INSERT INTO padre (id, nome) VALUES (9,\'z\')', 'REPLACE INTO padre (id, nome) VALUES (1,\'z\')',
 'WITH x AS (SELECT 1) DELETE FROM padre', 'WITH x AS (SELECT 1) UPDATE padre SET nome = \'y\'', 'WITH x AS (SELECT 1) INSERT INTO padre (id,nome) VALUES (8,\'q\')',
 'SELECT NEXTVAL(sq)', 'SELECT `NEXTVAL`(sq)', 'SELECT NEXT VALUE FOR sq', 'SELECT NEXT/**/VALUE FOR sq', 'SELECT SETVAL(sq, 100)', 'SELECT LASTVAL(sq)', 'SELECT PREVIOUS VALUE FOR sq',
 'SELECT f_seg()', 'SELECT `f_seg`()', 'SELECT rev13_base.f_seg()', 'SELECT * FROM padre FOR UPDATE', 'SELECT * FROM padre LOCK IN SHARE MODE', 'SELECT GET_LOCK(\'a\', 1)',
 'SELECT * FROM padre INTO OUTFILE \'/tmp/rev13.txt\'', 'SELECT * INTO @x FROM padre', 'SELECT LOAD_FILE(\'/etc/passwd\') AS id', 'SELECT load_file (\'/etc/passwd\') AS id',
 'EXPLAIN DELETE FROM padre', 'DESC DELETE FROM padre', 'EXPLAIN UPDATE padre SET nome = \'k\'', 'ANALYZE DELETE FROM padre', 'EXPLAIN ANALYZE DELETE FROM padre',
 'SET @a = 1', 'DO 1', 'CALL p()', 'HANDLER padre OPEN', 'LOCK TABLES padre WRITE', 'CREATE TABLE rev13_base.x (a INT)', 'DROP TABLE figlio', 'TRUNCATE figlio', 'GRANT ALL ON *.* TO banco', 'KILL 1', 'FLUSH TABLES', 'LOAD DATA INFILE \'/etc/passwd\' INTO TABLE padre', 'INSTALL SONAME \'x\'', 'XA START \'x\'', 'PREPARE s FROM \'DELETE FROM padre\'', 'EXECUTE IMMEDIATE \'DELETE FROM padre\'', 'BEGIN NOT ATOMIC DELETE FROM padre; END', 'SELECT 1; DELETE FROM padre', 'SHOW TABLES; DELETE FROM padre',
 'SELECT * FROM padre WHERE id = 1 /*!99999 OR 1 */', 'SELECT 1 /*M!100000 , 2 */', 'SELECT /*+ MAX_EXECUTION_TIME(1) */ 1', 'SELECT SLEEP(1)', 'SELECT `SLEEP`(1)', 'SELECT BENCHMARK(1000000, MD5(1))',
 'SELECT sys_exec(\'id\')', 'SELECT * FROM mysql.user', 'SELECT * FROM `mysql`.`user`', 'SHOW GRANTS', 'SELECT USER()', 'SELECT CURRENT_USER', 'SELECT SESSION_USER()',
 'VALUES (1)', 'TABLE padre', 'SELECT * FROM padre WHERE id IN (SELECT id FROM padre) FOR UPDATE SKIP LOCKED',
);
foreach ( $w as $q ) { mostra( $q ); mostra( $q, 'base', $forza ); mostra( $q, 'base', $forza2 ); mostra( $q, 'wordpress', $forza ); mostra( $q, 'sorgente_3', $forza2 ); }
// sorgenti non in uso e sconosciute
foreach ( array( 'riserva', 'sorgente_4', 'pippo', '../base', 'BASE', 'Base' ) as $s ) mostra( 'SELECT 1 AS n', $s );
fine();
