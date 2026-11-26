<?php require __DIR__.'/lib.php';
$a = new mysqli('localhost','banco','banco','rev12_base');
$a->query("LOCK TABLES innocua WRITE");
T('M1','base',"SELECT * FROM innocua");
T('M2','base',"SHOW CREATE TABLE innocua");
T('M3','base',"UPDATE innocua SET a = 5 WHERE id = 1",'prova',array('consenti'=>true));
$a->query("UNLOCK TABLES");
// transazione aperta che tiene la riga
$a->query("START TRANSACTION"); $a->query("UPDATE innocua SET a = 7 WHERE id = 1");
T('M4','base',"UPDATE innocua SET a = 5 WHERE id = 1",'prova',array('consenti'=>true));
$a->query("ROLLBACK");
