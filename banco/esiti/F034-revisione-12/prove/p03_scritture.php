<?php require __DIR__.'/lib.php';
echo "figli prima: "; print_r(sq("SELECT COUNT(*) FROM rev12_wp.figlio_fk"));
conferma('W1-fk','base',"DELETE FROM padre WHERE id = 1");
echo "figli dopo: "; print_r(sq("SELECT COUNT(*) FROM rev12_wp.figlio_fk")); registro(3);
conferma('W1b-fk-upd','base',"UPDATE padre SET id = 20 WHERE id = 2");
print_r(sq("SELECT * FROM rev12_wp.figlio_fk"));
// oracolo FK su colonna segreta
T('W2a','base',"INSERT INTO accessi (rif_sessione) VALUES ('TOKEN-SESSIONE-ABC')",'prova',array('consenti'=>true));
T('W2b','base',"INSERT INTO accessi (rif_sessione) VALUES ('TOKEN-SESSIONE-ABD')",'prova',array('consenti'=>true));
T('W2c','base',"INSERT INTO accessi (rif_sessione) VALUES ('TOKEN-SESSIONE-ABC') ",'prova',array('consenti'=>true));
// oracolo unico sul valore
T('W3a','base',"INSERT INTO uniq_val (chiave, valore) VALUES ('nuova', 'Segretissima1')",'prova',array('consenti'=>true));
T('W3b','base',"INSERT INTO uniq_val (chiave, valore) VALUES ('nuova', 'Segretissima2')",'prova',array('consenti'=>true));
T('W3c','base',"INSERT IGNORE INTO uniq_val (chiave, valore) VALUES ('nuova', 'Segretissima1')",'prova',array('consenti'=>true));
T('W3d','base',"REPLACE INTO uniq_val (chiave, valore) VALUES ('nuova', 'Segretissima1')",'prova',array('consenti'=>true));
T('W3e','base',"REPLACE INTO uniq_val (chiave, valore) VALUES ('nuova', 'Segretissima2')",'prova',array('consenti'=>true));
T('W3f','base',"UPDATE IGNORE uniq_val SET valore = 'Segretissima1' WHERE chiave = 'nuova0'",'prova',array('consenti'=>true));
// R11-3 varianti
$R3 = array("INSERT INTO utenti () VALUES ()","REPLACE INTO utenti () VALUES ()","INSERT INTO utenti VALUES ()","INSERT INTO utenti ( ) VALUES ( )","INSERT INTO utenti (/**/) VALUES ()","INSERT INTO utenti VALUE ()","INSERT INTO utenti SET id = 50","INSERT INTO utenti (id) VALUES (51)","INSERT INTO utenti(`id`)VALUES(52)","INSERT INTO utenti PARTITION (p0) (id) VALUES (53)","INSERT INTO rev12_base.utenti () VALUES ()","INSERT INTO `utenti` () VALUES ()","INSERT INTO rdacademy_impostazioni () VALUES ()","INSERT INTO rdacademy_impostazioni VALUES (NULL,'x','y')","INSERT INTO utenti (id) VALUES (54) RETURNING *","DELETE FROM utenti WHERE id = 54 RETURNING *","INSERT INTO utenti (id) SELECT 55","INSERT INTO innocua (a) SELECT id FROM utenti","INSERT INTO innocua (b) VALUES ((SELECT nome FROM utenti LIMIT 1))","UPDATE innocua SET b = (SELECT MAX(nome) FROM utenti)","INSERT INTO innocua (b) TABLE utenti","INSERT INTO utenti (nome) VALUES ('x'), ('y')","INSERT INTO utenti (nome) VALUES ROW('x')","INSERT INTO utenti (nome) SELECT b FROM innocua","INSERT DELAYED INTO utenti (nome) VALUES ('d')","INSERT INTO utenti (nome) VALUES ('d') ON DUPLICATE KEY UPDATE nome = 'e'");
foreach ($R3 as $i=>$q) T('R3-'.$i,'base',$q,'prova',array('consenti'=>true));
// motore e oggetti
$O = array("INSERT INTO myi (id) VALUES (9)","INSERT INTO ari (id) VALUES (9)","INSERT INTO mrg_s3 (id, info) VALUES (9,'scritto-da-console')","INSERT INTO v_innocua (a) VALUES (9)","INSERT INTO con_trg (id,a) VALUES (9,9)","INSERT INTO innocua (a) VALUES (f_scrive(9))","INSERT INTO innocua (a) VALUES (`f_scrive`(9))","INSERT INTO innocua (a) VALUES (rev12_base.f_scrive(9))","INSERT INTO rev12_wp.rdacademy_wpx (a) VALUES ('x')","INSERT INTO innocua (b) SELECT a FROM rev12_wp.rdacademy_wpx","UPDATE innocua i JOIN myi m ON m.id=i.id SET m.b='w'","UPDATE innocua i, myi m SET m.b = 'w2' WHERE m.id = i.id","DELETE innocua, myi FROM innocua JOIN myi ON myi.id = innocua.id WHERE innocua.id = 99","INSERT INTO innocua (b) VALUES (LOAD_FILE('/etc/passwd'))","INSERT INTO innocua (b) VALUES (SYS_EXEC('id'))","UPDATE innocua SET a = a + 1 WHERE id = 1");
foreach ($O as $i=>$q) T('O-'.$i,'base',$q,'prova',array('consenti'=>true));
print_r(sq("SELECT * FROM rev12_base.log_trg")); print_r(sq("SELECT * FROM rev12_s3.dati_s3"));
