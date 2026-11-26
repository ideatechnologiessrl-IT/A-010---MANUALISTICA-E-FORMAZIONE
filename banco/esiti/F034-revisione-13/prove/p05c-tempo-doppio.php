<?php require __DIR__ . '/base.php';
// sottoquery costante di ~7 s: EXPLAIN (piano_pulito) la esegue una volta, la SELECT un altra
$o = mostra( 'SELECT * FROM v_idx WHERE id = (SELECT MAX(seq % 7) FROM seq_1_to_190000000)' );
echo "  referto: "; foreach ( $o['referto'] as $l ) echo "[{$l[0]}] " . mb_substr( $l[1], 0, 120 ) . ' / '; echo "\n";
fine();
