CREATE TABLE IF NOT EXISTS `#__belaunce_gostje` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `runda_id` INT UNSIGNED NOT NULL,
  `ime` VARCHAR(100) NOT NULL,
  `telefon` VARCHAR(30) NULL DEFAULT NULL,
  `email` VARCHAR(255) NULL DEFAULT NULL,
  `odjava_zeton_hash` CHAR(64) NULL DEFAULT NULL,
  `ip` CHAR(64) NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_runda` (`runda_id`),
  KEY `idx_runda_ip` (`runda_id`, `ip`),
  UNIQUE KEY `idx_odjava_zeton_hash` (`odjava_zeton_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
