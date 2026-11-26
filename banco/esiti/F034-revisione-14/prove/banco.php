<?php
// Carica sostituti e console, esegue le istruzioni di un file (una per riga, «sorgente|istruzione»), stampa l esito.
require '/home/claude/rev14/sostituti-wordpress.php';
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
require '/home/claude/rev14/reldox-academy-diagnostica/includes/class-rdacademy-console-sql.php';
$righe = file( $argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$n = 0;
foreach ( $righe as $ln ) {
	if ( $ln[0] === '#' ) continue;
	list( $src, $sql ) = explode( '|', $ln, 2 ); $n++;
	$t0 = microtime( true );
	$o = Rdacademy_Console_SQL::esegui( array( 'sorgente' => $src, 'sql' => $sql, 'utente' => 1, 'login' => 'admin' ) );
	$dt = round( microtime( true ) - $t0, 2 );
	echo "### [$n] $src | $sql  ({$dt}s)\n";
	if ( $o['colonne'] ) { echo '  COL: ' . implode( ' | ', $o['colonne'] ) . "\n"; foreach ( array_slice( $o['dati'], 0, 4 ) as $r ) echo '  RIGA: ' . implode( ' | ', array_map( fn( $v ) => $v === null ? 'NULL' : mb_substr( (string) $v, 0, 90 ), $r ) ) . "\n"; }
	foreach ( $o['referto'] as $r ) if ( $r[0] !== 'INFO' || str_starts_with( $r[1], 'Colonne segrete' ) ) echo '  [' . $r[0] . '] ' . mb_substr( $r[1], 0, 220 ) . "\n";
}
echo "TOTALE PROVE: $n\n";
