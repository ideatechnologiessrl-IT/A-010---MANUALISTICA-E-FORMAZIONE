<?php require __DIR__.'/lib.php';
function occupato($etichetta) { $t0=microtime(true); $c = new mysqli('localhost','banco','banco','rev12_base');
  $c->query("SET SESSION innodb_lock_wait_timeout=120");
  // finché il ROLLBACK del server tiene i lucchetti, questo aspetta
  try { $c->query("START TRANSACTION"); $c->query("SELECT id FROM big WHERE id = 1999999 FOR UPDATE"); $c->query("ROLLBACK"); } catch (Throwable $e) { echo "  err ".$e->getMessage()."\n"; }
  $pl = sq("SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE STATE LIKE '%ollback%' OR INFO LIKE '%big%'");
  echo "   [$etichetta] lucchetto su big ottenuto dopo ".round(microtime(true)-$t0,1)." s dalla risposta della console\n"; }
foreach (array("UPDATE big SET x = x + 1","DELETE FROM big WHERE x > 0","UPDATE big SET p = REPEAT('q', 200)","INSERT INTO innocua (a) SELECT x FROM big") as $i=>$q) {
  $r = T('Z'.$i,'base',$q,'prova',array('consenti'=>true));
  $t1 = microtime(true);
  $pl = sq("SELECT ID, TIME, STATE, LEFT(INFO,40) FROM information_schema.PROCESSLIST WHERE INFO LIKE '%big%' AND INFO NOT LIKE '%PROCESSLIST%'");
  echo "   processi ancora attivi sul server: ".json_encode($pl)."\n";
  occupato('Z'.$i);
}
// letture pesanti
foreach (array("SELECT COUNT(*) FROM big a JOIN big b ON a.x = b.x + 1 WHERE a.p = b.p","SELECT MAX(LENGTH(REPEAT(p, 1000))) FROM big","SELECT COUNT(*) FROM big a, big b WHERE a.id < 3000 AND b.id < 3000 AND MD5(CONCAT(a.p,b.id)) = 'x'","SELECT id FROM ord a, big b WHERE b.x = a.id + 0 LIMIT 3") as $i=>$q) T('Y'.$i,'base',$q);
