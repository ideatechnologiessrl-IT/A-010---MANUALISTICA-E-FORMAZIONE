<?php require __DIR__.'/lib.php';
foreach (array("UPDATE big SET x = x + 1, p = REPEAT('z', 200)","DELETE FROM big WHERE id > 0") as $i=>$q) {
  $t0=microtime(true);
  $r = T('ZZ'.$i,'base',$q,'prova',array('consenti'=>true), false);
  $c = new mysqli('localhost','banco','banco','rev12_base'); $c->query("SET SESSION innodb_lock_wait_timeout=200");
  $t1=microtime(true);
  $pl = sq("SELECT ID, COMMAND, TIME, STATE FROM information_schema.PROCESSLIST WHERE DB='rev12_base' AND ID <> CONNECTION_ID()");
  echo "   processi subito dopo: ".json_encode($pl)."\n";
  $trx = sq("SELECT trx_state, trx_rows_modified, trx_operation_state FROM information_schema.INNODB_TRX");
  echo "   transazioni InnoDB subito dopo: ".json_encode($trx)."\n";
  $c->query("START TRANSACTION"); $c->query("SELECT id FROM big WHERE id = 1999990 FOR UPDATE"); $c->query("ROLLBACK");
  echo "   lucchetto su una riga di big ottenuto ".round(microtime(true)-$t1,1)." s dopo la risposta della console (risposta in ".round($t1-$t0,1)." s)\n";
}
