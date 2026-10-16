CREATE TABLE IF NOT EXISTS `#__belaunce_zetoni` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `acym_uporabnik_id` INT NOT NULL,
  `zeton_hash` CHAR(64) NOT NULL,
  `runda_id` INT UNSIGNED NULL DEFAULT NULL,
  `potece` DATETIME NOT NULL,
  `porabljen` DATETIME NULL DEFAULT NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `ustvarjeno` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_zeton_hash` (`zeton_hash`),
  KEY `idx_acym` (`acym_uporabnik_id`),
  KEY `idx_potece` (`potece`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__belaunce_clani` (
  `uporabnik_id` INT NOT NULL,
  `acym_uporabnik_id` INT NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  `zadnja_prijava` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`uporabnik_id`),
  UNIQUE KEY `idx_acym` (`acym_uporabnik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__belaunce_poskusi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tip` VARCHAR(20) NOT NULL,
  `kljuc` CHAR(64) NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poskus` (`tip`, `kljuc`, `ustvarjeno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
