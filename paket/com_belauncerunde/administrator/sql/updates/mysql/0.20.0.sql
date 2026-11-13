-- Naloga 24: naročnine na push obvestila (ena vrstica = ena naprava) in oznaka poslanega opomnika.
CREATE TABLE IF NOT EXISTS `#__belaunce_push` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uporabnik_id` INT NOT NULL,
  `endpoint` VARCHAR(2048) NOT NULL,
  `endpoint_hash` CHAR(64) NOT NULL,
  `p256dh` VARCHAR(128) NOT NULL,
  `auth` VARCHAR(48) NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  `zadnji_uspeh` DATETIME NULL,
  `napake` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_endpoint_hash` (`endpoint_hash`),
  KEY `idx_uporabnik` (`uporabnik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stolpec dodatno idempotentno zagotovi namestitveni skript (MariaDB 10.11 brez IF NOT EXISTS).
ALTER TABLE `#__belaunce_runde` ADD COLUMN `opomnik_poslan` DATETIME NULL DEFAULT NULL;

-- Oznaka poslanega obvestila o novi rundi (7.4). Obstoječe runde dobijo oznako takoj,
-- da posodobitev ne pošlje pusha za stare runde (merilo 23).
ALTER TABLE `#__belaunce_runde` ADD COLUMN `nova_obvescena` DATETIME NULL DEFAULT NULL;
UPDATE `#__belaunce_runde` SET `nova_obvescena` = UTC_TIMESTAMP() WHERE `nova_obvescena` IS NULL;
