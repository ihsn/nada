-- ============================================================
-- Analytics Tracking System - MySQL Schema
-- ============================================================


SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- ============================================================
-- BACKUP: LEGACY COUNTS FOR MIGRATION/AUDIT
-- ============================================================
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_legacy_counts` (
  `survey_id` INT NOT NULL,
  `total_views` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_downloads` INT UNSIGNED NOT NULL DEFAULT 0,
  `backup_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`survey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ============================================================
-- 1. RAW PAGEVIEW EVENTS
-- Only used when built-in JS tracking is active
-- ============================================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_pageview_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ts` DATETIME NOT NULL,
    `study_id` INT NOT NULL COMMENT 'Study page referenced',
    `session_id` VARCHAR(255) NULL COMMENT 'Client-generated session token',
    `hashed_ip` CHAR(64) NULL COMMENT 'Hashed IP for dedupe/rate limiting',
    `user_agent` VARCHAR(200) NULL COMMENT 'Used for bot filtering (truncated)',
    `referrer` VARCHAR(512) NULL COMMENT 'Optional analytics',
    PRIMARY KEY (`id`),
    INDEX `idx_ts` (`ts`),
    INDEX `idx_study` (`study_id`),
    INDEX `idx_session` (`session_id`),
    INDEX `idx_ts_study` (`ts`, `study_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ============================================================
-- 2. RAW DOWNLOAD EVENTS
-- Server-side, authoritative source
-- ============================================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_download_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ts` DATETIME NOT NULL,
    `study_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(50) NULL,
    `hashed_ip` CHAR(64) NULL,
    `user_agent` VARCHAR(200) NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_ts` (`ts`),
    INDEX `idx_study` (`study_id`),
    INDEX `idx_study_file` (`study_id`, `file_name`),
    INDEX `idx_ts_study_file` (`ts`, `study_id`, `file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ============================================================
-- 3. DAILY STUDY-LEVEL AGGREGATES
-- Source: GA4 OR built-in raw pageview events
-- ============================================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_daily_studies` (
    `date` DATE NOT NULL,
    `study_id` INT NOT NULL,
    `pageviews` INT UNSIGNED NOT NULL DEFAULT 0,
    `unique_visitors` INT UNSIGNED NOT NULL DEFAULT 0,
    `downloads` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Sum of all file downloads for the study',
    PRIMARY KEY (`date`, `study_id`),
    INDEX `idx_study` (`study_id`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ============================================================
-- 4. MONTHLY STUDY-LEVEL AGGREGATES
-- Contains virtual month (year=0, month=0) for legacy totals
-- ============================================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_monthly_studies` (
  `year` smallint NOT NULL,
  `month` tinyint NOT NULL,
  `study_id` int NOT NULL,
  `pageviews` int unsigned NOT NULL DEFAULT '0',
  `unique_visitors` int unsigned NOT NULL DEFAULT '0',
  `downloads` int unsigned NOT NULL DEFAULT '0' COMMENT 'Sum of file-level monthly downloads',
  `finalized` tinyint(1) NOT NULL DEFAULT '0',
  `finalized_at` datetime DEFAULT NULL,
  PRIMARY KEY (`year`,`month`,`study_id`),
  KEY `idx_study` (`study_id`),
  KEY `idx_period` (`year`,`month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ============================================================
-- 5. DAILY FILE-LEVEL DOWNLOAD AGGREGATES
-- Derived from analytics_download_events
-- ============================================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_daily_files` (
    `date` DATE NOT NULL,
    `study_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `downloads` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`date`, `study_id`, `file_name`),
    INDEX `idx_study` (`study_id`),
    INDEX `idx_file` (`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ============================================================
-- 6. MONTHLY FILE-LEVEL DOWNLOAD AGGREGATES
-- Contains virtual month entries (year=0, month=0) if needed
-- ============================================================

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_monthly_files` (
  `year` smallint NOT NULL,
  `month` tinyint NOT NULL,
  `study_id` int NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `downloads` int unsigned NOT NULL DEFAULT '0',
  `finalized` tinyint(1) NOT NULL DEFAULT '0',
  `finalized_at` datetime DEFAULT NULL,
  PRIMARY KEY (`year`,`month`,`study_id`,`file_name`),
  KEY `idx_study` (`study_id`),
  KEY `idx_period` (`year`,`month`),
  KEY `idx_file` (`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


CREATE TABLE `analytics_aggregation_status` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(20) NOT NULL DEFAULT 'idle',
  `current_step` varchar(50) DEFAULT NULL,
  `current_item` varchar(100) DEFAULT NULL,
  `total_items` int DEFAULT '0',
  `processed_items` int DEFAULT '0',
  `progress_percent` int DEFAULT '0',
  `message` text,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `last_updated_at` datetime DEFAULT NULL,
  `error_message` text,
  `context` varchar(20) NOT NULL DEFAULT 'cli' COMMENT 'cli or web',
  `user_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `last_updated_at` (`last_updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;