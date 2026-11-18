<?php
/**
 * F013-banco-wpconfig.php — RelDoxAcademy · Formazione F013 · 18/11/2026
 * ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
 *
 * Il wizard d installazione deve scrivere le credenziali del database PRIMA della riga che avvia
 * WordPress (`require_once ABSPATH . 'wp-settings.php'`), in qualunque lingua sia il wp-config.
 * Se le scrive dopo, i plugin partono senza credenziali e RelDoxAcademy resta in «solo installazione»
 * (/academy/ inesistente, «Primo accesso» → «Non hai il permesso»). Visto dall owner il 18/11/2026
 * 14:37 sul sito di RelDoxAcademy (WordPress in italiano).
 *
 * uso:  php banco/F013-banco-wpconfig.php <cartella del plugin>
 * esce 0 se VERDE, 1 se ROSSO.
 */
define( 'ABSPATH', sys_get_temp_dir() . '/' );
$dir = rtrim( $argv[1] ?? '', '/' );
require $dir . '/includes/bootstrap/class-rdacademy-bootstrap-wizard.php';

$coda = "/** Path assoluto alla directory di WordPress. */\nif ( ! defined( 'ABSPATH' ) ) {\n\tdefine( 'ABSPATH', __DIR__ . '/' );\n}\n\n/** Imposta le variabili di WordPress ed include i file. */\nrequire_once ABSPATH . 'wp-settings.php';\n";
$casi = array(
	'italiano'          => "<?php\ndefine( 'DB_NAME', 'x' );\n\$table_prefix = 'wp_';\ndefine( 'WP_DEBUG', false );\n\n/* Aggiungere qualsiasi valore personalizzato tra questa riga e la riga \"Finito, interrompere le modifiche\". */\n\n\n\n/* Finito, interrompere le modifiche! Buona pubblicazione. */\n\n" . $coda,
	'inglese'           => "<?php\ndefine( 'DB_NAME', 'x' );\n\$table_prefix = 'wp_';\n\n/* Add any custom values between this line and the \"stop editing\" line. */\n\n\n\n/* That's all, stop editing! Happy publishing. */\n\n" . $coda,
	'senza segnaposto'  => "<?php\ndefine( 'DB_NAME', 'x' );\n\$table_prefix = 'wp_';\nif ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/' );\nrequire_once( ABSPATH . 'wp-settings.php' );\n",
);
$blocco = "/* BEGIN RelDoxAcademy DB */\ndefine( 'RDACADEMY_DB_HOST',    'h' );\ndefine( 'RDACADEMY_DB_NAME',    'n' );\n/* END RelDoxAcademy DB */";

$r   = new ReflectionClass( 'Rdacademy_Bootstrap_Wizard' );
$inj = $r->getMethod( 'inject' );
$inj->setAccessible( true );
$rosso = 0;

function rdacademy_banco_prima( string $c ): bool {
	$b = strpos( $c, 'RDACADEMY_DB_HOST' );
	if ( ! preg_match( '/^[^\n]*wp-settings\.php/m', $c, $m, PREG_OFFSET_CAPTURE ) ) {
		return $b !== false;
	}
	return $b !== false && $b < $m[0][1];
}

// 1. scrittura nuova: il blocco sta prima dell avvio di WordPress, in ogni lingua
foreach ( $casi as $nome => $cfg ) {
	$new = $inj->invoke( null, $cfg, $blocco );
	$ok  = rdacademy_banco_prima( $new ) && substr_count( $new, 'BEGIN RelDoxAcademy DB' ) === 1;
	echo ( $ok ? '[VERDE] ' : '[ROSSO] ' ) . "W1 wp-config $nome: blocco " . ( $ok ? 'prima' : 'NON prima' ) . " di wp-settings.php\n";
	$rosso += $ok ? 0 : 1;
}

// 2. il sito dell owner: blocco già scritto in fondo. Il wizard deve riconoscerlo come fuori posto e spostarlo.
$fuori = rtrim( $casi['italiano'] ) . "\n\n" . $blocco . "\n";
$ha    = $r->hasMethod( 'misplaced' );
if ( ! $ha ) {
	echo "[ROSSO] W2 il wizard non sa riconoscere un blocco scritto dopo wp-settings.php\n";
	$rosso++;
} else {
	$mis = $r->getMethod( 'misplaced' );
	$mis->setAccessible( true );
	$rep = $r->getMethod( 'replace_block' );
	$rep->setAccessible( true );
	$a = $mis->invoke( null, $fuori ) === true;
	$b = $mis->invoke( null, $inj->invoke( null, $casi['italiano'], $blocco ) ) === false;
	$spostato = $rep->invoke( null, $fuori, $blocco );
	$c = rdacademy_banco_prima( $spostato ) && substr_count( $spostato, 'RDACADEMY_DB_HOST' ) === 1;
	$ok = $a && $b && $c;
	echo ( $ok ? '[VERDE] ' : '[ROSSO] ' ) . 'W2 blocco in fondo riconosciuto (' . ( $a ? 'sì' : 'no' ) . '), blocco giusto non toccato (' . ( $b ? 'sì' : 'no' ) . '), spostato prima di wp-settings.php (' . ( $c ? 'sì' : 'no' ) . ")\n";
	$rosso += $ok ? 0 : 1;
}

echo $rosso ? "ESITO: ROSSO ($rosso)\n" : "ESITO: VERDE\n";
exit( $rosso ? 1 : 0 );
