DROP DATABASE IF EXISTS rev13_base; DROP DATABASE IF EXISTS rev13_s3; DROP DATABASE IF EXISTS rev13_wp;
CREATE DATABASE rev13_base; CREATE DATABASE rev13_s3; CREATE DATABASE rev13_wp;
USE rev13_s3;
CREATE TABLE altrove (id INT, nome VARCHAR(50), email VARCHAR(80)) ENGINE=MyISAM;
INSERT INTO altrove VALUES (1,'ClienteS3','privato@s3.it');
USE rev13_base;
CREATE TABLE utenti (id INT PRIMARY KEY, nome VARCHAR(50), email VARCHAR(80), password VARCHAR(80), chiave_api VARCHAR(80), private_key TEXT, pin INT, credenziali VARCHAR(80), consumer_key VARCHAR(80), truncated_key CHAR(7), note TEXT, importo DECIMAL(10,2), telefono BIGINT,
  code VARCHAR(20), security_code INT, cvc INT, id_rsa TEXT, database_url VARCHAR(200), user_data JSON, tax_id VARCHAR(30), message TEXT, extra VARCHAR(100), data TEXT, numero_documento VARCHAR(30), oracle_dsn VARCHAR(200), puk BIGINT, ref VARCHAR(80), raw_data TEXT, level VARCHAR(50), type VARCHAR(80), field VARCHAR(80), client_id VARCHAR(80), fine_config TEXT, creator_note TEXT, created_by_secret_q TEXT, nascita DATE) ENGINE=InnoDB;
INSERT INTO utenti VALUES (1,'Mario','mario@x.it','pw-Mario4829','sk_live_ABCDEF123','-----BEGIN PRIVATE KEY-----MII',4829,'u:p','ck_123','abc1234','nota segreta',10.5,3331234567,
 'OTP-777111',123,456,'-----BEGIN OPENSSH PRIVATE KEY-----AAAA','mysql://root:Pw_Segreta@db/prod','{"token":"tok_SEGRETO"}','RSSMRA80A01H501U','Il tuo codice di accesso e 991122','{"api":"sk_extra"}','{"password":"Dati_segreti"}','AX1234567','oracle://sys:OraPw@h','12345678','ref_sk_live_X','raw_SEGRETO','lvl_segreto','tipo_segreto','field_segreto','client_abc','fine_segreto','creator_segreto','cbsq','1980-01-01');
INSERT INTO utenti (id,nome,email,password,pin) VALUES (2,'Anna','anna@x.it','pw-Anna',1111);
CREATE TABLE chiavi (id INT PRIMARY KEY, nome VARCHAR(40), config JSON) ENGINE=InnoDB;
INSERT INTO chiavi VALUES (1,'stripe','{"sk":"sk_live_JSON"}');
CREATE TABLE rdacademy_impostazioni (id INT AUTO_INCREMENT PRIMARY KEY, chiave VARCHAR(80), valore TEXT) ENGINE=InnoDB;
INSERT INTO rdacademy_impostazioni (chiave,valore) VALUES ('titolo','Accademia'),('claude_chiave','sk-ant-SEGRETO'),('ftp_parola','ftpPW'),('pin_firma','5555'),('bearer_claude','Bearer XYZ'),('stripe_live_sk','sk_live_KV'),('versione','0.1'),('Titolo','SEGRETO_MAIUSC'),('json_conf','{"password":"x"}');
CREATE TABLE padre (id INT PRIMARY KEY, nome VARCHAR(20)) ENGINE=InnoDB;
CREATE TABLE figlio (id INT PRIMARY KEY, padre_id INT, FOREIGN KEY (padre_id) REFERENCES padre(id) ON DELETE CASCADE) ENGINE=InnoDB;
INSERT INTO padre VALUES (1,'p1'); INSERT INTO figlio VALUES (1,1);
CREATE TABLE my_segreti (id INT PRIMARY KEY, segreto VARCHAR(100)) ENGINE=MyISAM CHECKSUM=1 ROW_FORMAT=DYNAMIC;
INSERT INTO my_segreti VALUES (1,'4829');
CREATE TABLE my_segreti2 (id INT PRIMARY KEY, segreto VARCHAR(100)) ENGINE=MyISAM CHECKSUM=1 ROW_FORMAT=DYNAMIC;
INSERT INTO my_segreti2 VALUES (1,'segretolunghissimo_segretolunghissimo');
CREATE TABLE m1 (id INT, nome VARCHAR(50), email VARCHAR(80)) ENGINE=MyISAM;
CREATE TABLE unione (id INT, nome VARCHAR(50), email VARCHAR(80)) ENGINE=MERGE UNION=(rev13_s3.altrove) INSERT_METHOD=NO;
CREATE VIEW v_unione AS SELECT id, nome, email FROM unione;
CREATE TABLE part (id INT, token VARCHAR(20), nome VARCHAR(20)) PARTITION BY RANGE COLUMNS(token) (PARTITION p0 VALUES LESS THAN ('m'), PARTITION p1 VALUES LESS THAN (MAXVALUE));
INSERT INTO part VALUES (1,'zeta','a');
CREATE VIEW v_part AS SELECT id, nome FROM part;
CREATE TABLE gen (id INT PRIMARY KEY, password VARCHAR(50), lung INT AS (LENGTH(password)) VIRTUAL);
CREATE TABLE idx (id INT PRIMARY KEY, nome VARCHAR(20), token VARCHAR(40), KEY k_tok (token)) ENGINE=InnoDB;
INSERT INTO idx VALUES (1,'a','zzz'),(2,'b','aaa');
DELIMITER //
CREATE FUNCTION f_seg() RETURNS VARCHAR(100) READS SQL DATA RETURN (SELECT password FROM utenti WHERE id=1)//
CREATE TRIGGER trg BEFORE INSERT ON padre FOR EACH ROW SET NEW.nome = NEW.nome//
DELIMITER ;
CREATE SEQUENCE sq;
USE rev13_wp;
CREATE TABLE wp_options (option_id BIGINT AUTO_INCREMENT PRIMARY KEY, option_name VARCHAR(191) UNIQUE, option_value LONGTEXT, autoload VARCHAR(20)) ENGINE=InnoDB;
INSERT INTO wp_options (option_name, option_value, autoload) VALUES ('siteurl','http://x','yes'),('smtp_pass','SMTP_SEGRETO','yes'),('mailserver_pass','MS_SEGRETO','yes'),('auth_key','AUTHK','no'),('admin_email','a@x.it','yes'),('woocommerce_stripe_settings','a:1:{s:10:"secret_key";s:7:"sk_live";}','yes');
CREATE TABLE wp_usermeta (umeta_id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, meta_key VARCHAR(255), meta_value LONGTEXT);
INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (1,'session_tokens','a:1:{tok}'),(1,'nickname','admin'),(1,'description','bio');
CREATE TABLE wp_users (ID BIGINT PRIMARY KEY, user_login VARCHAR(60), user_pass VARCHAR(255), user_email VARCHAR(100), user_activation_key VARCHAR(255));
INSERT INTO wp_users VALUES (1,'admin','$P$Bhash','a@x.it','actkey');
CREATE TABLE wp_woocommerce_api_keys (key_id BIGINT PRIMARY KEY, user_id BIGINT, description VARCHAR(200), consumer_key CHAR(64), consumer_secret CHAR(43), truncated_key CHAR(7), last_access DATETIME);
INSERT INTO wp_woocommerce_api_keys VALUES (1,1,'chiave negozio','ck_hash','cs_SEGRETO','abc1234',NOW());
