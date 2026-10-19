CREATE TABLE IF NOT EXISTS `#__belaunce_prijave` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `runda_id` INT UNSIGNED NOT NULL,
  `uporabnik_id` INT NOT NULL,
  `ustvarjeno` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_runda_uporabnik` (`runda_id`, `uporabnik_id`),
  KEY `idx_runda` (`runda_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
