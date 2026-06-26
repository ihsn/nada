--
-- Migration script to fix sitelogs table schema and add indexes
-- Designed for tables with millions of rows using zero-downtime approach
--
-- IMPORTANT: Test on a staging environment first!
-- 
-- Migration Strategy:
-- 1. Rename original logtime to logtime_old (preserves all original data)
-- 2. Create new INT logtime column
-- 3. Use triggers to keep columns in sync during migration
-- 4. Migrate data in batches (can run during production)
-- 5. Keep logtime_old for safety (do not drop - can be removed later after verification)
-- 6. Add indexes (non-blocking, can run during production)
--
-- Estimated time: 
-- - Initial setup: < 1 minute
-- - Batch migration: Run repeatedly until complete (can take hours for very large tables)
-- - Index creation: 10-30 minutes per index (non-blocking)
--

-- Step 1: Rename original logtime column to logtime_old (preserves original data)
-- This is a fast metadata-only operation
ALTER TABLE `sitelogs` 
  CHANGE COLUMN `logtime` `logtime_old` varchar(45) NOT NULL DEFAULT '0';

-- Step 2: Add new INT column for logtime (fast operation, no data copy)
ALTER TABLE `sitelogs` 
  ADD COLUMN `logtime` int(11) NOT NULL DEFAULT 0 AFTER `logtime_old`;

-- Step 3: Create trigger to keep new column in sync during migration
-- This ensures new inserts/updates populate both columns
-- All logtime_old values are Unix timestamps (integers stored as varchar)
DELIMITER $$
CREATE TRIGGER `sitelogs_logtime_sync` 
BEFORE INSERT ON `sitelogs`
FOR EACH ROW
BEGIN
  SET NEW.logtime = 0 + NEW.logtime_old;
END$$

CREATE TRIGGER `sitelogs_logtime_sync_update` 
BEFORE UPDATE ON `sitelogs`
FOR EACH ROW
BEGIN
  SET NEW.logtime = 0 + NEW.logtime_old;
END$$
DELIMITER ;

-- Step 4: Create stored procedure for batch migration
-- Run this procedure repeatedly until it returns 0 affected rows
DELIMITER $$
DROP PROCEDURE IF EXISTS `migrate_logtime_batch`$$
CREATE PROCEDURE `migrate_logtime_batch`(IN batch_size INT)
BEGIN
  DECLARE affected_rows INT;
  
  -- Migrate a batch of rows (all logtime_old values are Unix timestamps)
  -- Use 0+logtime_old to convert varchar to integer
  UPDATE `sitelogs` 
    SET `logtime` = 0 + `logtime_old`
    WHERE `logtime` = 0 
      AND `logtime_old` != '0'
      AND `logtime_old` != ''
      AND `logtime_old` IS NOT NULL
    LIMIT batch_size;
  
  SET affected_rows = ROW_COUNT();
  SELECT affected_rows AS 'Rows migrated in this batch';
  
END$$
DELIMITER ;

-- Step 5: Run batch migration
-- Execute this multiple times until it returns 0 rows:
-- CALL migrate_logtime_batch(50000);
-- 
-- For very large tables, run during off-peak hours:
-- CALL migrate_logtime_batch(100000);  -- Larger batches during maintenance window

-- Step 6: Verify migration is complete
-- Run this query - it should return 0:
-- SELECT COUNT(*) AS remaining_rows 
-- FROM `sitelogs` 
-- WHERE `logtime` = 0 
--   AND `logtime_old` != '0';

-- Step 7: Verify data integrity
-- Compare a sample of migrated data:
-- SELECT id, logtime_old, logtime, 
--        (0 + logtime_old) as expected_logtime,
--        CASE WHEN logtime = (0 + logtime_old) THEN 'OK' ELSE 'MISMATCH' END as status
-- FROM sitelogs 
-- WHERE logtime_old != '0'
-- LIMIT 100;

-- Step 8: Drop triggers (no longer needed after migration)
DROP TRIGGER IF EXISTS `sitelogs_logtime_sync`;
DROP TRIGGER IF EXISTS `sitelogs_logtime_sync_update`;

-- Step 9: Clean up stored procedure
DROP PROCEDURE IF EXISTS `migrate_logtime_batch`;

-- Step 10: Fix useragent column spacing (remove leading space in column definition)
-- This is a cosmetic fix but ensures consistency
-- Note: This is a fast operation as it only changes metadata
ALTER TABLE `sitelogs` 
  MODIFY COLUMN `useragent` varchar(300) DEFAULT NULL;

-- Step 11: Add indexes for frequently queried columns
-- Add indexes one at a time to minimize lock duration
-- Note: Index creation on large tables can take time but is non-blocking
--       (read operations can continue during index creation)

-- Index for logtime (most common sort column - used in default sorting)
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_logtime` (`logtime`);

-- Index for logtype (frequently filtered in search queries)
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_logtype` (`logtype`);

-- Index for surveyid (frequently filtered by survey)
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_surveyid` (`surveyid`);

-- Index for username (frequently searched)
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_username` (`username`);

-- Index for ip (frequently searched)
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_ip` (`ip`);

-- Index for section (frequently filtered)
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_section` (`section`);

-- Composite index for common query patterns (logtime + logtype)
-- Useful for queries filtering by logtype and sorting by time
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_logtime_logtype` (`logtime`, `logtype`);

-- Composite index for survey filtering with time sorting
-- Useful for queries filtering by surveyid and sorting by time
ALTER TABLE `sitelogs` 
  ADD INDEX `idx_surveyid_logtime` (`surveyid`, `logtime`);

--
-- OPTIONAL: After verifying everything works correctly for a period of time,
-- you can drop the logtime_old column to reclaim space:
-- ALTER TABLE `sitelogs` DROP COLUMN `logtime_old`;
--
