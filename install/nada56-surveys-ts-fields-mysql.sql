-- NADA 5.7 — surveys timeseries columns (MySQL)
-- Plain DDL; duplicate column/index errors are skipped on re-run.

ALTER TABLE `surveys` ADD COLUMN `data_structure_id` INT(11) NULL;
ALTER TABLE `surveys` ADD INDEX `idx_surveys_data_structure_id` (`data_structure_id`);
ALTER TABLE `surveys` ADD CONSTRAINT `fk_surveys_data_structure` FOREIGN KEY (`data_structure_id`) REFERENCES `data_structures` (`id`);

ALTER TABLE `surveys` ADD COLUMN `ts_db_id` INT(11) NULL;
ALTER TABLE `surveys` ADD COLUMN `ts_dimensions` VARCHAR(2000) NULL;
ALTER TABLE `surveys` ADD COLUMN `ts_frequency` VARCHAR(500) NULL;
ALTER TABLE `surveys` ADD COLUMN `ts_sync_required` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `surveys` ADD COLUMN `ts_data_count` BIGINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `surveys` ADD INDEX `idx_surveys_ts_db_id` (`ts_db_id`);
