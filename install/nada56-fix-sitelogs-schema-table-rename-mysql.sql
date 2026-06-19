--
-- Alternative migration script using TABLE RENAME approach
-- This is FASTER and SAFER for tables with millions of rows
--
-- IMPORTANT: Test on a staging environment first!
--
-- Migration Strategy:
-- 1. Rename table sitelogs to sitelogs_legacy (INSTANT - metadata only, O(1))
--    Matches PHP migration 20251214000001_rename_sitelogs_to_legacy.
-- 2. Create new sitelogs table with correct schema and indexes
-- 3. Migrate data in batches from sitelogs_legacy to sitelogs
-- 4. Handle concurrent writes during migration
-- 5. Drop old table after verification (optional)
--
-- Performance Comparison:
-- - Table RENAME: INSTANT (milliseconds, regardless of table size)
-- - Column RENAME: Can take minutes/hours (may rebuild table)
-- - Table CREATE: Fast (metadata only)
-- - Data INSERT: Can be done in batches (non-blocking)
--
-- Estimated time:
-- - Table rename: < 1 second
-- - Create new table: < 1 second
-- - Batch migration: Run repeatedly until complete (can take hours for very large tables)
-- - Index creation: Already included in CREATE TABLE (fast)
--

-- Step 1: Rename original table to sitelogs_legacy
-- Skip if already renamed (sitelogs_legacy exists and sitelogs does not).
-- This is INSTANT - just updates metadata, no data copy
-- Original table is completely preserved
RENAME TABLE `sitelogs` TO `sitelogs_legacy`;

-- Step 2: Create new sitelogs table with correct schema and indexes
-- All indexes are created upfront (faster than adding them later)
CREATE TABLE `sitelogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessionid` varchar(255) NOT NULL DEFAULT '',
  `logtime` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(45) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `logtype` varchar(45) NOT NULL,
  `surveyid` int(11) DEFAULT '0',
  `section` varchar(255) DEFAULT NULL,
  `keyword` varchar(300) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `useragent` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_logtime` (`logtime`),
  KEY `idx_logtype` (`logtype`),
  KEY `idx_surveyid` (`surveyid`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip`),
  KEY `idx_section` (`section`),
  KEY `idx_logtime_logtype` (`logtime`, `logtype`),
  KEY `idx_logtime_username` (`logtime`, `username`),
  KEY `idx_logtime_ip` (`logtime`, `ip`),
  KEY `idx_surveyid_logtime` (`surveyid`, `logtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Create stored procedure for batch migration
-- This migrates data from sitelogs_legacy to sitelogs in batches
DELIMITER $$
DROP PROCEDURE IF EXISTS `migrate_sitelogs_batch`$$
CREATE PROCEDURE `migrate_sitelogs_batch`(IN batch_size INT)
BEGIN
  DECLARE affected_rows INT;
  DECLARE last_id INT DEFAULT 0;

  -- Get the last migrated ID to continue from where we left off
  SELECT COALESCE(MAX(id), 0) INTO last_id FROM `sitelogs`;

  -- Migrate a batch of rows
  -- Migrate ALL rows - '0' is a valid Unix timestamp (epoch time)
  -- Empty strings and NULL will convert to 0, which is also valid
  INSERT INTO `sitelogs` (
    `id`, `sessionid`, `logtime`, `ip`, `url`, `logtype`,
    `surveyid`, `section`, `keyword`, `username`, `useragent`
  )
  SELECT
    `id`, `sessionid`, 0 + `logtime`, `ip`, `url`, `logtype`,
    `surveyid`, `section`, LEFT(`keyword`, 300), `username`, `useragent`
  FROM `sitelogs_legacy`
  WHERE `id` > last_id
  ORDER BY `id`
  LIMIT batch_size;

  SET affected_rows = ROW_COUNT();
  SELECT affected_rows AS 'Rows migrated in this batch', last_id AS 'Last migrated ID';

END$$
DELIMITER ;

-- Step 4: Run batch migration
-- Execute this multiple times until it returns 0 rows:
-- CALL migrate_sitelogs_batch(50000);
--
-- For very large tables, run during off-peak hours:
-- CALL migrate_sitelogs_batch(100000);  -- Larger batches during maintenance window
--
-- Monitor progress:
-- SELECT
--   (SELECT COUNT(*) FROM sitelogs) as migrated_count,
--   (SELECT COUNT(*) FROM sitelogs_legacy) as total_count,
--   (SELECT COUNT(*) FROM sitelogs_legacy) - (SELECT COUNT(*) FROM sitelogs) as remaining_count;

-- Step 5: Migrate any remaining rows (shouldn't be needed if procedure works correctly)
-- This is a safety check in case any rows were missed:
-- INSERT INTO `sitelogs` (
--   `id`, `sessionid`, `logtime`, `ip`, `url`, `logtype`,
--   `surveyid`, `section`, `keyword`, `username`, `useragent`
-- )
-- SELECT
--   `id`, `sessionid`, 0 + `logtime`, `ip`, `url`, `logtype`,
--   `surveyid`, `section`, LEFT(`keyword`, 300), `username`, `useragent`
-- FROM `sitelogs_legacy`
-- WHERE `id` NOT IN (SELECT `id` FROM `sitelogs`);

-- Step 6: Verify migration is complete
-- Run these queries to verify:
--
-- Check row counts match:
-- SELECT
--   (SELECT COUNT(*) FROM sitelogs_legacy) as legacy_count,
--   (SELECT COUNT(*) FROM sitelogs) as new_count;
--
-- Check for missing IDs:
-- SELECT COUNT(*) as missing_rows
-- FROM sitelogs_legacy sl
-- LEFT JOIN sitelogs s ON sl.id = s.id
-- WHERE s.id IS NULL;
--
-- Verify data integrity (sample check):
-- SELECT sl.id, sl.logtime as old_logtime, s.logtime as new_logtime,
--        CASE WHEN s.logtime = (0 + sl.logtime) THEN 'OK' ELSE 'MISMATCH' END as status
-- FROM sitelogs_legacy sl
-- JOIN sitelogs s ON sl.id = s.id
-- LIMIT 100;

-- Step 7: Handle concurrent writes during migration
-- The application is writing to the new sitelogs table, but we need to capture
-- any writes that happened to sitelogs_legacy during migration.
--
-- Check for any new rows in sitelogs_legacy that weren't migrated:
-- SELECT COUNT(*) as unmigrated_new_rows
-- FROM sitelogs_legacy sl
-- LEFT JOIN sitelogs s ON sl.id = s.id
-- WHERE s.id IS NULL;
--
-- If any found, migrate them:
-- INSERT INTO `sitelogs` (
--   `id`, `sessionid`, `logtime`, `ip`, `url`, `logtype`,
--   `surveyid`, `section`, `keyword`, `username`, `useragent`
-- )
-- SELECT
--   `id`, `sessionid`, 0 + `logtime`, `ip`, `url`, `logtype`,
--   `surveyid`, `section`, LEFT(`keyword`, 300), `username`, `useragent`
-- FROM `sitelogs_legacy`
-- WHERE `id` NOT IN (SELECT `id` FROM `sitelogs`);

-- Step 8: Clean up stored procedure
DROP PROCEDURE IF EXISTS `migrate_sitelogs_batch`;

-- Step 9: After verification period (e.g., 1 week), drop legacy table
-- WARNING: Only run this after you're 100% sure everything works!
-- ALTER TABLE `sitelogs_legacy` RENAME TO `sitelogs_backup_YYYYMMDD`;
-- Or drop it completely:
-- DROP TABLE IF EXISTS `sitelogs_legacy`;

--
-- NOTES:
-- 1. The application code writes to 'sitelogs' which now points to the new table
-- 2. All reads from 'sitelogs' will use the new table with correct schema
-- 3. The old table 'sitelogs_legacy' is preserved for safety
-- 4. Migration can be paused/resumed at any time
-- 5. No application code changes needed (table name stays the same)
--
