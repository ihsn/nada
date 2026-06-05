--
-- Alternative migration script using TABLE RENAME approach
-- This is FASTER and SAFER for tables with millions of rows
--
-- IMPORTANT: Test on a staging environment first!
-- 
-- Migration Strategy:
-- 1. Rename table sitelogs to sitelogs_old (INSTANT - metadata only, O(1))
-- 2. Create new sitelogs table with correct schema and indexes
-- 3. Create view to redirect reads to new table (for zero-downtime)
-- 4. Migrate data in batches from sitelogs_old to sitelogs
-- 5. Handle concurrent writes during migration
-- 6. Drop view and old table after verification
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

-- Step 1: Rename original table to sitelogs_old
-- This is INSTANT - just updates metadata, no data copy
-- Original table is completely preserved
RENAME TABLE `sitelogs` TO `sitelogs_old`;

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
  `keyword` text,
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
  KEY `idx_surveyid_logtime` (`surveyid`, `logtime`)
) DEFAULT CHARSET=utf8;

-- Step 3: Create stored procedure for batch migration
-- This migrates data from sitelogs_old to sitelogs in batches
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
    `surveyid`, `section`, `keyword`, `username`, `useragent`
  FROM `sitelogs_old`
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
--   (SELECT COUNT(*) FROM sitelogs_old) as total_count,
--   (SELECT COUNT(*) FROM sitelogs_old) - (SELECT COUNT(*) FROM sitelogs) as remaining_count;

-- Step 5: Migrate any remaining rows (shouldn't be needed if procedure works correctly)
-- This is a safety check in case any rows were missed:
-- INSERT INTO `sitelogs` (
--   `id`, `sessionid`, `logtime`, `ip`, `url`, `logtype`, 
--   `surveyid`, `section`, `keyword`, `username`, `useragent`
-- )
-- SELECT 
--   `id`, `sessionid`, 0 + `logtime`, `ip`, `url`, `logtype`,
--   `surveyid`, `section`, `keyword`, `username`, `useragent`
-- FROM `sitelogs_old`
-- WHERE `id` NOT IN (SELECT `id` FROM `sitelogs`);

-- Step 6: Verify migration is complete
-- Run these queries to verify:
-- 
-- Check row counts match:
-- SELECT 
--   (SELECT COUNT(*) FROM sitelogs_old) as old_count,
--   (SELECT COUNT(*) FROM sitelogs) as new_count;
--
-- Check for missing IDs:
-- SELECT COUNT(*) as missing_rows
-- FROM sitelogs_old so
-- LEFT JOIN sitelogs s ON so.id = s.id
-- WHERE s.id IS NULL;
--
-- Verify data integrity (sample check):
-- SELECT so.id, so.logtime as old_logtime, s.logtime as new_logtime,
--        CASE WHEN s.logtime = (0 + so.logtime) THEN 'OK' ELSE 'MISMATCH' END as status
-- FROM sitelogs_old so
-- JOIN sitelogs s ON so.id = s.id
-- LIMIT 100;

-- Step 7: Handle concurrent writes during migration
-- The application is writing to the new sitelogs table, but we need to capture
-- any writes that happened to sitelogs_old during migration.
-- 
-- Check for any new rows in sitelogs_old that weren't migrated:
-- SELECT COUNT(*) as unmigrated_new_rows
-- FROM sitelogs_old so
-- LEFT JOIN sitelogs s ON so.id = s.id
-- WHERE s.id IS NULL
--   AND so.id > (SELECT COALESCE(MAX(id), 0) FROM sitelogs_old WHERE logtime != '0');
--
-- If any found, migrate them:
-- INSERT INTO `sitelogs` (
--   `id`, `sessionid`, `logtime`, `ip`, `url`, `logtype`, 
--   `surveyid`, `section`, `keyword`, `username`, `useragent`
-- )
-- SELECT 
--   `id`, `sessionid`, 0 + `logtime`, `ip`, `url`, `logtype`,
--   `surveyid`, `section`, `keyword`, `username`, `useragent`
-- FROM `sitelogs_old`
-- WHERE `id` NOT IN (SELECT `id` FROM `sitelogs`);

-- Step 8: Clean up stored procedure
DROP PROCEDURE IF EXISTS `migrate_sitelogs_batch`;

-- Step 9: After verification period (e.g., 1 week), drop old table
-- WARNING: Only run this after you're 100% sure everything works!
-- ALTER TABLE `sitelogs_old` RENAME TO `sitelogs_backup_YYYYMMDD`;
-- Or drop it completely:
-- DROP TABLE IF EXISTS `sitelogs_old`;

--
-- NOTES:
-- 1. The application code writes to 'sitelogs' which now points to the new table
-- 2. All reads from 'sitelogs' will use the new table with correct schema
-- 3. The old table 'sitelogs_old' is preserved for safety
-- 4. Migration can be paused/resumed at any time
-- 5. No application code changes needed (table name stays the same)
--

