CREATE TABLE IF NOT EXISTS llx_moceanapi_sms_outbox
(
    ID              int(10) AUTO_INCREMENT PRIMARY KEY,
    sender          VARCHAR(20) NOT NULL,
    message         TEXT NOT NULL,
    recipient       TEXT NOT NULL,
    status          SMALLINT NOT NULL DEFAULT 1,
    date            DATETIME DEFAULT NULL
) ENGINE=innodb;


ALTER TABLE llx_moceanapi_sms_outbox MODIFY COLUMN date DATETIME DEFAULT NULL;

ALTER TABLE llx_moceanapi_sms_outbox ADD COLUMN source VARCHAR(255) DEFAULT NULL AFTER `status`;
