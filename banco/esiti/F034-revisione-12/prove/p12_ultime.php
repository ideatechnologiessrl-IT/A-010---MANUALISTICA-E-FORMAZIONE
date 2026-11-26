<?php require __DIR__.'/lib.php';
T('J1','base',"SELECT * FROM rdacademy_config");
T('J2','base',"SELECT JSON_EXTRACT(dati, '$.smtp_password') FROM rdacademy_config");
T('E1','base',"EXPLAIN UPDATE innocua SET a = 1");
T('E2','base',"SELECT nome, COUNT(*) FROM ord GROUP BY nome WITH ROLLUP");
T('E3','base',"SELECT * FROM mrg_s3");
registro(1);
T('E4','base',"SELECT 1 AS `delete`");
T('E5','base',"WITH a AS (SELECT 1 AS `update`) SELECT * FROM a");
