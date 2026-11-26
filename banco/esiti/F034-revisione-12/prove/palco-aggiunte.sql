-- aggiunte al palco fatte durante le prove (dopo palco.sql)
USE rev12_base;
INSERT INTO rdacademy_impostazioni (chiave, valore) VALUES ('claude_chiave','sk-ant-api03-QWERTY999'),('chiave_licenza_x','LIC-ABCD-EFGH'),('ftp_parola','Rondine77'),('posta_accesso','{"utente":"info","parola":"Gabbiano88"}'),('pin_firma','99173'),('bearer_claude','eyJhbGciOiJIUzI1NiJ9.abc');
CREATE TABLE con_trg (id INT PRIMARY KEY, a INT) ENGINE=InnoDB; CREATE TABLE log_trg (x INT) ENGINE=MyISAM;
CREATE TRIGGER t_ai AFTER INSERT ON con_trg FOR EACH ROW INSERT INTO log_trg VALUES (NEW.id);
DELIMITER //
CREATE FUNCTION f_scrive(x INT) RETURNS INT MODIFIES SQL DATA BEGIN INSERT INTO log_trg VALUES (x); RETURN x; END//
DELIMITER ;
CREATE VIEW v_innocua AS SELECT id, a FROM innocua;
CREATE TABLE uniq_val (id INT AUTO_INCREMENT PRIMARY KEY, chiave VARCHAR(50) UNIQUE, valore VARCHAR(100), UNIQUE KEY (valore)) ENGINE=InnoDB;
INSERT INTO uniq_val (chiave, valore) VALUES ('smtp_password','Segretissima1');
CREATE VIEW v_info AS SELECT TABLE_NAME FROM information_schema.TABLES;
CREATE VIEW v_sub AS SELECT id, (SELECT info FROM rev12_s3.dati_s3 LIMIT 1) AS z FROM innocua;
CREATE TABLE ord (id INT PRIMARY KEY, nome VARCHAR(20), token VARCHAR(40), KEY k_tok (token), KEY k_nome_tok (nome, token)) ENGINE=InnoDB;
INSERT INTO ord SELECT seq, CONCAT('n', seq % 3), MD5(seq) FROM seq_1_to_200;
CREATE TABLE ord_nopk (id INT NOT NULL, token VARCHAR(40) NOT NULL, UNIQUE KEY (token), UNIQUE KEY(id)) ENGINE=InnoDB; INSERT INTO ord_nopk SELECT seq, MD5(seq) FROM seq_1_to_50;
CREATE VIEW v_ord AS SELECT id, nome FROM ord; ANALYZE TABLE ord;
CREATE TABLE gros (id INT PRIMARY KEY, nome VARCHAR(10), token LONGTEXT) ENGINE=InnoDB; INSERT INTO gros SELECT seq, 'n', REPEAT('t', IF(seq <= 600, 10000, 1)) FROM seq_1_to_900;
CREATE TABLE gros2 (id INT PRIMARY KEY, nome VARCHAR(10), token LONGTEXT) ENGINE=InnoDB; INSERT INTO gros2 SELECT seq, 'n', 'x' FROM seq_1_to_900;
CREATE TABLE lung (id INT PRIMARY KEY, nome VARCHAR(10), token LONGTEXT) ENGINE=InnoDB; INSERT INTO lung SELECT seq, 'n', IF(seq=1, REPEAT('s', 15000000), 'corto') FROM seq_1_to_2;
ALTER TABLE big ADD KEY kx (x), ADD KEY kp (p), ADD KEY kxp (x,p);
CREATE TABLE rdacademy_config (id INT PRIMARY KEY, dati JSON) ENGINE=InnoDB; INSERT INTO rdacademy_config VALUES (1, '{"smtp_password":"JSON-PW-SEGRETA","api_key":"JSON-APIKEY"}');
USE rev12_wp;
INSERT INTO wp_options (option_name, option_value) VALUES ('rdacademy_impostazioni','a:2:{s:6:"chiave";s:12:"CHIAVE-OPZ-1";s:6:"parola";s:9:"Pinguino9";}'),('stripe_live_sk','sk_live_51ABCDEF');
CREATE TABLE rdacademy_padre (id INT PRIMARY KEY) ENGINE=InnoDB; INSERT INTO rdacademy_padre VALUES (1),(2);
CREATE TABLE wp_postmeta_fk (id INT AUTO_INCREMENT PRIMARY KEY, rp INT, FOREIGN KEY (rp) REFERENCES rdacademy_padre(id) ON DELETE CASCADE) ENGINE=InnoDB; INSERT INTO wp_postmeta_fk (rp) VALUES (1),(1),(2);
