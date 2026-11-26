<?php
require __DIR__ . '/../sostituti-wordpress.php';
require __DIR__ . '/../reldox-academy-diagnostica/includes/class-rdacademy-console-sql.php';
mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$GLOBALS['NPROVE'] = 0;
function prova( string $sql, string $src = 'base', array $extra = array() ) {
	$GLOBALS['NPROVE']++;
	$t0 = microtime( true );
	$o = Rdacademy_Console_SQL::esegui( array_merge( array( 'sorgente' => $src, 'sql' => $sql, 'utente' => 1, 'login' => 'rev' ), $extra ) );
	$o['_s'] = round( microtime( true ) - $t0, 2 );
	return $o;
}
function mostra( string $sql, string $src = 'base', array $extra = array() ) {
	$o = prova( $sql, $src, $extra );
	$esito = $o['rossi'] ? 'ROSSO' : ( $o['verdi'] ? 'VERDE' : '??' );
	echo "=== [$src] " . substr( str_replace( "\n", ' ', $sql ), 0, 200 ) . "\n  -> $esito ({$o['_s']} s)";
	if ( $o['rossi'] ) { foreach ( $o['referto'] as $l ) if ( $l[0] === 'ROSSO' ) echo ' | ' . mb_substr( $l[1], 0, 180 ); }
	echo "\n";
	if ( $o['colonne'] ) { echo '  COL: ' . implode( ' | ', $o['colonne'] ) . "\n"; foreach ( array_slice( $o['dati'], 0, 6 ) as $r ) echo '  ROW: ' . implode( ' | ', array_map( function ( $v ) { return $v === null ? 'NULL' : mb_substr( (string) $v, 0, 70 ); }, $r ) ) . "\n"; }
	return $o;
}
function fine() { echo "\nPROVE: " . $GLOBALS['NPROVE'] . "\n"; }
