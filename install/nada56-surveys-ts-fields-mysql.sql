-- ============================================================
-- NADA 5.6 — surveys timeseries (ts_*) columns — MySQL
--
-- Adds indicator/timeseries metadata columns used by catalog
-- search, OpenSearch indexing, and Mongo sync:
--   ts_db_id         — link timeseries study -> timeseriesdb (surveys.id)
--   ts_dimensions    — cached DSD dimension list for facets
--   ts_frequency     — cached periodicity labels (e.g. "Annual")
--   ts_sync_required — flag: indicator data needs re-import/rehash
--   ts_data_count    — cached Mongo observation document count
--
-- Safe to run on existing databases (skips columns that already exist).
-- ============================================================

-- ts_db_id
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_db_id'
);
SET @after_col = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'data_structure_id') > 0,
    'data_structure_id',
    'data_class_id'
);
SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE `surveys` ADD COLUMN `ts_db_id` INT(11) NULL DEFAULT NULL AFTER `', @after_col, '`'),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ts_dimensions
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_dimensions'
);
SET @after_col = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_db_id') > 0,
    'ts_db_id',
    IF(
        (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'data_structure_id') > 0,
        'data_structure_id',
        'data_class_id'
    )
);
SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE `surveys` ADD COLUMN `ts_dimensions` VARCHAR(2000) NULL DEFAULT NULL AFTER `', @after_col, '`'),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ts_frequency
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_frequency'
);
SET @after_col = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_dimensions') > 0,
    'ts_dimensions',
    IF(
        (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_db_id') > 0,
        'ts_db_id',
        'data_structure_id'
    )
);
SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE `surveys` ADD COLUMN `ts_frequency` VARCHAR(500) NULL DEFAULT NULL AFTER `', @after_col, '`'),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ts_sync_required
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_sync_required'
);
SET @after_col = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_frequency') > 0,
    'ts_frequency',
    IF(
        (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_dimensions') > 0,
        'ts_dimensions',
        'data_structure_id'
    )
);
SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE `surveys` ADD COLUMN `ts_sync_required` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `', @after_col, '`'),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ts_data_count
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_data_count'
);
SET @after_col = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_sync_required') > 0,
    'ts_sync_required',
    IF(
        (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_frequency') > 0,
        'ts_frequency',
        IF(
            (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'surveys' AND column_name = 'ts_dimensions') > 0,
            'ts_dimensions',
            'data_structure_id'
        )
    )
);
SET @sql = IF(@col_exists = 0,
    CONCAT('ALTER TABLE `surveys` ADD COLUMN `ts_data_count` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `', @after_col, '`'),
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index for timeseriesdb lookups (may already exist on upgraded installs)
ALTER TABLE `surveys`
    ADD INDEX IF NOT EXISTS `idx_surveys_ts_db_id` (`ts_db_id`);
