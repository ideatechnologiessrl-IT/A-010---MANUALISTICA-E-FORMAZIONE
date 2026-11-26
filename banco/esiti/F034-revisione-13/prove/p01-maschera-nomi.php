<?php require __DIR__ . '/base.php';
mostra( 'SELECT * FROM utenti' );
mostra( 'SELECT * FROM chiavi' );
foreach ( array( 'code','security_code','cvc','id_rsa','database_url','user_data','tax_id','message','extra','data','numero_documento','oracle_dsn','puk','ref','raw_data','level','type','field','client_id','fine_config','creator_note','telefono','importo','note','pin','password','chiave_api' ) as $c ) mostra( "SELECT id, $c FROM utenti WHERE id = 1" );
mostra( 'SELECT id, code AS c, LENGTH(code) AS n FROM utenti' );
mostra( 'SELECT JSON_EXTRACT(user_data, \'$.token\') AS id FROM utenti' );
fine();
