<?php require __DIR__ . '/base.php';
mostra( 'SELECT * FROM v_idx WHERE id = (SELECT MAX(seq % 7) FROM seq_1_to_10000000000)' );
mostra( 'SELECT * FROM v_idx WHERE id = (SELECT MAX(seq % 7) FROM seq_1_to_10000000000) + 0' );
mostra( 'SELECT MAX(seq % 7) AS n FROM seq_1_to_10000000000' );
mostra( 'SELECT * FROM v_idx, (SELECT MAX(seq % 7) AS n FROM seq_1_to_10000000000) x' );
fine();
