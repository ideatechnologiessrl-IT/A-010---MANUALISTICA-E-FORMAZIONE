<?php require __DIR__.'/lib.php';
for ($k=0;$k<3;$k++) foreach (array(1,2) as $id) { $t0=microtime(true); $r = P('base', "SELECT * FROM lung WHERE id = $id"); $ms = round((microtime(true)-$t0)*1000);
  echo "id=$id  risposta {$ms} ms  referto: ".preg_replace('/.*· (\d+ ms).*/','$1',$r['referto'][0][1])."  dati: ".json_encode($r['dati'])."\n"; }
