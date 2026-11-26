<?php require __DIR__ . '/base.php';
// R12-6: tabella partizionata per RANGE COLUMNS(token): p0 < 'm' <= p1; la riga ha token 'zeta'
mostra( 'SELECT * FROM part' );
mostra( 'SELECT * FROM part PARTITION (p1)' );
mostra( 'SELECT * FROM v_part' );
mostra( 'EXPLAIN SELECT * FROM v_part' );
mostra( 'SELECT NAME, NUM_ROWS, CLUST_INDEX_SIZE FROM information_schema.INNODB_SYS_TABLESTATS WHERE NAME LIKE \'rev13_base/part%\'' );
mostra( 'SELECT TABLE_NAME, NUMBER_RECORDS, DATA_SIZE FROM information_schema.INNODB_BUFFER_PAGE WHERE TABLE_NAME LIKE \'%part%\'' );
mostra( 'SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.`PARTITIONS` WHERE TABLE_NAME = \'part\'' );
mostra( 'SELECT * FROM information_schema.INNODB_SYS_TABLES WHERE NAME LIKE \'rev13_base/part%\'' );
// R12-4: MERGE che legge rev13_s3.altrove
mostra( 'SELECT * FROM unione' );
mostra( 'SELECT * FROM v_unione' );
mostra( 'SELECT * FROM `unione`' );
mostra( 'SELECT id, nome, email FROM v_unione WHERE id = 1' );
mostra( 'SELECT COUNT(*) AS n FROM v_unione' );
mostra( 'DESCRIBE unione' );
mostra( 'SHOW CREATE TABLE unione' );
mostra( 'SHOW TABLE STATUS LIKE \'unione\'' );
fine();
