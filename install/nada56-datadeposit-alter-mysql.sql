-- NADA 5.7 — data deposit column upgrades (MySQL)
-- Requires dd_projects / dd_project_resources tables. Skips duplicates on re-run.

ALTER TABLE `dd_projects` ADD COLUMN `schema_version` TINYINT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `dd_projects` ADD COLUMN `submission` MEDIUMTEXT NULL;
ALTER TABLE `dd_projects` ADD COLUMN `data_type` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `dd_projects` ADD COLUMN `metadata` MEDIUMTEXT NULL;

UPDATE `dd_projects` SET `data_type` = 'survey'
WHERE `data_type` IS NULL OR `data_type` = '';

ALTER TABLE `dd_project_resources` ADD COLUMN `dctype` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `dd_project_resources` ADD COLUMN `dcformat` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `dd_project_resources` ADD COLUMN `filesize` DOUBLE DEFAULT NULL;
