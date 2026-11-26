<?php require __DIR__ . '/base.php';
$q = array(
 "DELETE FROM utenti WHERE iban = IT60X0542811101000000123456",
 "UPDATE utenti SET nome = sk_live_ABCDEF123",
 "DELETE FROM utenti WHERE nome = Mario4829",
 "DELETE FROM utenti WHERE pin = ٤٨٢٩",
 "DELETE FROM utenti WHERE pin = ４８２９",
 "SegretoMarioRossi FROM utenti",
 "Hunterdue",
 "DELETE FROM utenti WHERE password = PasswordSegretaSenzaCifre",
 "DELETE FROM utenti WHERE nome = `Mario Rossi`",
 "DELETE FROM utenti WHERE nome = 'Mario Rossi' AND pin = 4829",
 "SELECT * FROM utenti WHERE codice = x'34383239'",
 "SELECT * FROM utenti WHERE note = _utf8mb4 0x34383239",
 "DELETE FROM utenti WHERE pin = 4829e0",
 "DELETE FROM utenti WHERE pin = -4829",
 "DELETE FROM utenti WHERE pin = quattromilaottocentoventinove",
 "DELETE FROM utenti WHERE pin = ⁴⁸²⁹",
 "DELETE FROM utenti WHERE pin = ½",
 "DELETE FROM utenti WHERE pin = Ⅳ",
 "DELETE FROM utenti WHERE carta = 4111-1111-1111-1111",
 "DELETE FROM utenti WHERE carta = 4111 1111 1111 1111",
 "SELECT password FROM utenti",
 "SELECT * FROM utenti WHERE \$pin = 4829",
 str_repeat( 'a', 20001 ),
);
foreach ( $q as $x ) mostra( $x );
mostra( 'SELECT 1', 'sk_live_SORGENTE_SEGRETA' );
mostra( 'SELECT 1', "pippo\x00\xff" );
prova( 'DELETE FROM utenti', 'base', array( 'login' => "admin\xff\xfe" ) );
fine();
