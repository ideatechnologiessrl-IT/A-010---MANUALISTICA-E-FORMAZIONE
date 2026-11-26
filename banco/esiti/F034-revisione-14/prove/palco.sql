DROP DATABASE IF EXISTS rev14_base; DROP DATABASE IF EXISTS rev14_s3; DROP DATABASE IF EXISTS rev14_wp;
CREATE DATABASE rev14_base; CREATE DATABASE rev14_s3; CREATE DATABASE rev14_wp;
USE rev14_base;
CREATE TABLE cliente (id INT PRIMARY KEY, nome VARCHAR(50), email VARCHAR(80), note TEXT, pin INT, csc INT, ccv INT, nip INT, ssn BIGINT, pan BIGINT, cvn INT,
 code VARCHAR(20), data VARCHAR(50), message VARCHAR(50), extra VARCHAR(50), type VARCHAR(20), level VARCHAR(20), ref VARCHAR(20), field VARCHAR(20),
 id_rsa TEXT, database_url VARCHAR(200), user_data JSON, oracle_dsn VARCHAR(100), tax_id VARCHAR(20), security_code INT, cvc INT, puk INT, saldo DECIMAL(10,2), nascita DATE,
 KEY k_note (note(10)), KEY k_pin (pin)) ENGINE=InnoDB;
INSERT INTO cliente VALUES (1,'Rossi','r@x.it','NOTA-SEGRETA-1',1234,111,222,333,123456789,4111111111111111,444,'CODE-SEG','DATA-SEG','MSG-SEG','EXTRA-SEG','TYPE-SEG','LVL-SEG','REF-SEG','FLD-SEG','RSA-SEG','mysql://u:p@h/db','{"token":"TOK-SEG"}','DSN-SEG','TAX-SEG',9876,555,12345678,1000.50,'1980-01-01'),
 (2,'Bianchi','b@x.it','ALTRA-NOTA-2',5678,112,223,334,987654321,5500000000000004,445,'C2','D2','M2','E2','T2','L2','R2','F2','RSA2','mysql://a:b@c/d','{"token":"TOK2"}','DSN2','TAX2',1111,556,87654321,20.00,'1990-02-02');
CREATE TABLE impostazioni (id INT PRIMARY KEY, chiave VARCHAR(50), valore TEXT) ENGINE=InnoDB;
INSERT INTO impostazioni VALUES (1,'titolo','Il mio sito'),(2,'smtp_password','PWD-SMTP-SEG'),(3,'licenza','LIC-SEG');
CREATE TABLE arch_a (id INT, nome VARCHAR(20), note VARCHAR(50)) ENGINE=MyISAM; CREATE TABLE arch_b LIKE arch_a;
INSERT INTO arch_a VALUES (1,'a','NOTA-MYISAM'); INSERT INTO arch_b VALUES (2,'b','NOTA-B');
CREATE TABLE arch_all (id INT, nome VARCHAR(20), note VARCHAR(50)) ENGINE=MERGE UNION=(arch_a,arch_b);
CREATE TABLE part_t (id INT, nome VARCHAR(20), num INT) PARTITION BY RANGE (num) (PARTITION p0 VALUES LESS THAN (100), PARTITION p1 VALUES LESS THAN MAXVALUE);
INSERT INTO part_t VALUES (1,'x',50),(2,'y',500);
CREATE TABLE gen_t (id INT PRIMARY KEY, note VARCHAR(50), titolo VARCHAR(50) AS (UPPER(note)) VIRTUAL); INSERT INTO gen_t (id,note) VALUES (1,'gen-seg');
CREATE TABLE innocua (id INT PRIMARY KEY, nome VARCHAR(20), stato VARCHAR(20), importo INT) ENGINE=InnoDB;
INSERT INTO innocua VALUES (1,'uno','attivo',10),(2,'due','spento',20);
CREATE VIEW v_ok AS SELECT id, nome FROM cliente;
CREATE VIEW v_var AS SELECT VERSION() AS titolo, CONNECTION_ID() AS id;
CREATE VIEW v_user AS SELECT CURRENT_USER() AS nome, DATABASE() AS titolo;
CREATE VIEW v_num AS SELECT id, nome FROM innocua;
CREATE TABLE trig_t (id INT, nome VARCHAR(20));
CREATE TRIGGER trg BEFORE INSERT ON trig_t FOR EACH ROW SET NEW.nome = UPPER(NEW.nome);
CREATE FUNCTION f_seg() RETURNS VARCHAR(50) DETERMINISTIC READS SQL DATA RETURN (SELECT note FROM cliente LIMIT 1);
CREATE SEQUENCE seq1;
USE rev14_s3;
CREATE TABLE locale (id INT PRIMARY KEY, nome VARCHAR(20)); INSERT INTO locale VALUES (1,'s3');
CREATE VIEW v_altro AS SELECT id, nome FROM rev14_base.cliente;
CREATE TABLE m_altro (id INT, nome VARCHAR(20), note VARCHAR(50)) ENGINE=MERGE UNION=(rev14_base.arch_a);
CREATE VIEW v_m AS SELECT id, nome FROM m_altro;
USE rev14_wp;
CREATE TABLE wp_options (option_id INT PRIMARY KEY AUTO_INCREMENT, option_name VARCHAR(191) UNIQUE, option_value LONGTEXT, autoload VARCHAR(20));
INSERT INTO wp_options (option_name, option_value, autoload) VALUES ('siteurl','http://x','yes'),('auth_key','AUTHKEY-SEG','yes'),('mailserver_pass','MAILPASS-SEG','yes');
CREATE TABLE wp_users (ID BIGINT PRIMARY KEY, user_login VARCHAR(60), user_pass VARCHAR(255), user_activation_key VARCHAR(255)); INSERT INTO wp_users VALUES (1,'admin','$P$HASHSEG','ACTKEY');
