CREATE TABLE IF NOT EXISTS `#__belaunce_tipi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `naziv` VARCHAR(100) NOT NULL,
  `alias` VARCHAR(100) NOT NULL,
  `opis` TEXT NULL,
  `stanje` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_alias` (`alias`),
  KEY `idx_stanje` (`stanje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__belaunce_tezavnosti` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `naziv` VARCHAR(100) NOT NULL,
  `alias` VARCHAR(100) NOT NULL,
  `opis` TEXT NULL,
  `stanje` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  `checked_out` INT UNSIGNED NULL,
  `checked_out_time` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_alias` (`alias`),
  KEY `idx_stanje` (`stanje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__belaunce_tipi` (`naziv`, `alias`, `opis`, `stanje`, `ordering`) VALUES
('MTB', 'mtb', NULL, 1, 1),
('Cestno', 'cestno', NULL, 1, 2),
('Gravel', 'gravel', NULL, 1, 3);

INSERT IGNORE INTO `#__belaunce_tezavnosti` (`naziv`, `alias`, `opis`, `stanje`, `ordering`) VALUES
('Berlingo', 'berlingo', NULL, 1, 1),
('Turbo', 'turbo', NULL, 1, 2);
