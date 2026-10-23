ALTER TABLE `#__belaunce_tipi` ADD COLUMN `barva` CHAR(7) NULL AFTER `opis`;

-- Obstoječi tipi dobijo začetne barve; že nastavljena vrednost ostane nedotaknjena.
UPDATE `#__belaunce_tipi` SET `barva` = '#2a76e0' WHERE `alias` = 'mtb' AND `barva` IS NULL;
UPDATE `#__belaunce_tipi` SET `barva` = '#f2b974' WHERE `alias` = 'cestno' AND `barva` IS NULL;
UPDATE `#__belaunce_tipi` SET `barva` = '#8f592f' WHERE `alias` = 'gravel' AND `barva` IS NULL;
