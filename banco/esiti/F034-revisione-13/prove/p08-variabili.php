<?php require __DIR__ . '/base.php';
foreach ( array( 'datadir','basedir','tmpdir','plugin_dir','pid_file','log_error','general_log_file','slow_query_log_file','socket','hostname','init_connect','init_file','ssl_cert','ssl_ca','ssl_key','wsrep_sst_auth','secure_file_priv','version_comment','server_uid','character_sets_dir','lc_messages_dir','report_password','report_user','wsrep_provider_options' ) as $v ) {
	mostra( "SELECT @@$v AS id" ); mostra( "SELECT @@GLOBAL.$v AS nome" );
}
mostra( 'SHOW VARIABLES LIKE \'init_connect\'' );
mostra( 'SELECT @@init_connect AS titolo, LENGTH(@@init_connect) AS n' );
mostra( 'SELECT `@@init_connect` AS id' );
fine();
