<?php require __DIR__ . '/base.php';
mostra( 'SELECT COUNT(*) AS n FROM seq_1_to_100000000000' );
mostra( 'SELECT * FROM v_idx WHERE id = (SELECT COUNT(*) FROM seq_1_to_100000000000)' );
mostra( 'SELECT * FROM v_idx WHERE id IN (SELECT COUNT(*) FROM seq_1_to_100000000000 a)' );
mostra( 'SELECT seq AS n FROM seq_1_to_100000000000' );
mostra( "SELECT 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa!' REGEXP '^(a+)+$' AS n" );
fine();
