-- ###################################################################################
-- # NADA 5.5 / 5.6 → 5.7 SQL Server upgrade bundle
-- ###################################################################################
--
-- Applies schema changes delivered by PHP migrations 20260701000001 through
-- 20260909120004. Prefer the web migration UI (one version per request) or
-- `php index.php cli migrate latest`. Use this script in SSMS when PHP CLI is
-- unavailable and the web UI times out.
--
-- Prerequisites:
--   - Full database backup
--   - SQL Server Full-Text Search installed
--   - application/config/migration.php → migration_enabled = TRUE
--
-- After running SQL here:
--   1. Re-run each migration version in the web UI (idempotent skips), or
--   2. Mark versions as applied only after verifying objects AND PHP-only steps.
--
-- Target watermark: 20260909120004
--
-- PHP-only steps (no SQL in this bundle — run via web UI):
--   20260701000003  dctypes/codelists seed, surveys.abstract, var_keywords, full-text rebuild
--   20260701000005  analytics legacy totals backfill (after analytics-schema-sqlsrv.sql)
--   20260701000006  ts_databases → surveys cutover
--   20260701000007  repositories_acl create + copy + delete legacy rows
--   20260701000008  configuration rows + study admin metadata tables
--   20260909120001  display templates + sync_shipped_cores()
--   20260909120003  dd_projects.data_type backfill, deposit_max_upload_size config
--   20260909120004  site_user_register configuration row
--
-- Topic SQL files (also included individually under install/):
--   nada55-upgrade-sqlsrv.sql, nada55-add-authtype-users-sqlsrv.sql,
--   nada56-api-keys-security-sqlsrv.sql, nada56-fix-sitelogs-schema-sqlsrv.sql,
--   nada56-filestore-sqlsrv.sql, nada56-codelists-dsd-sqlsrv.sql,
--   analytics-schema-sqlsrv.sql, nada56-surveys-ts-fields-sqlsrv.sql,
--   nada56-timeseries-value-counts-sqlsrv.sql, nada56-timeseries-db-links-sqlsrv.sql,
--   nada56-citation-indexes-sqlsrv.sql, nada56-search-indexes-sqlsrv.sql,
--   nada56-variables-unicode-sqlsrv.sql, nada56-analytics-pageview-tracking-sqlsrv.sql,
--   nada56-search-queue-sqlsrv.sql, nada56-datadeposit-alter-sqlsrv.sql
--
-- See also: NADA57_SQLSRV_MANUAL_RUNBOOK.md, NADA57_SQLSRV_UPGRADE.md
-- ###################################################################################

-- =============================================================================
-- Migration 20260701000001: Platform baseline
-- Source: nada55-upgrade-sqlsrv.sql
-- =============================================================================

ALTER TABLE [resources] ADD [resource_idno] nvarchar(100) DEFAULT NULL;
ALTER TABLE [resources] ADD [resource_type] nvarchar(50) DEFAULT NULL;
ALTER TABLE [resources] ADD [is_url] tinyint DEFAULT 0;
ALTER TABLE [resources] ADD [checksum] nvarchar(64) DEFAULT NULL;
ALTER TABLE [resources] ADD [metadata] nvarchar(max) DEFAULT NULL;
ALTER TABLE [resources] ADD [filesize] bigint DEFAULT NULL;
ALTER TABLE [resources] ADD [data_file_id] int DEFAULT NULL;
ALTER TABLE [resources] ADD [sort_order] int DEFAULT 0;
ALTER TABLE [resources] ADD [status] nvarchar(20) DEFAULT NULL;
ALTER TABLE [resources] ADD [created] int DEFAULT NULL;
ALTER TABLE [resources] ADD [created_by] int DEFAULT NULL;
ALTER TABLE [resources] ADD [changed_by] int DEFAULT NULL;

-- Note: resource_type column values are updated via PHP migration
-- See Migration_Upgrade_resources_table::update_resource_types_from_dctype()

-- update resource_type column values - extract code from dctype
-- UPDATE [resources] SET 
--   [resource_type] = LTRIM(RTRIM(SUBSTRING([dctype], 
--     CHARINDEX('[', [dctype]) + 1, 
--     CHARINDEX(']', [dctype]) - CHARINDEX('[', [dctype]) - 1)))
--   WHERE [dctype] IS NOT NULL 
--     AND [dctype] LIKE '%[%]%';




UPDATE [resources] 
  SET [is_url] = 1 
  WHERE [filename] LIKE 'http://%' 
     OR [filename] LIKE 'https://%' 
     OR [filename] LIKE 'ftp://%'
     OR [filename] LIKE 'www.%';

UPDATE [resources] 
  SET [created] = [changed]
  WHERE [created] IS NULL 
    AND [changed] IS NOT NULL 
    AND [changed] > 0;

UPDATE [resources] 
  SET [sort_order] = [resource_id] 
  WHERE [sort_order] = 0;

CREATE NONCLUSTERED INDEX [idx_res_survey_id] ON [resources] ([survey_id] ASC);
GO

-- =============================================================================
-- Migration 20260701000001: Platform baseline (authtype)
-- Source: nada55-add-authtype-users-sqlsrv.sql
-- =============================================================================

-- Add authentication type columns to users table for OAuth/SSO support

ALTER TABLE [users] ADD [authtype] nvarchar(40) DEFAULT NULL;
ALTER TABLE [users] ADD [authtype_id] nvarchar(300) DEFAULT NULL;

-- Add indexes for authentication lookups
CREATE NONCLUSTERED INDEX [idx_authtype] ON [users] ([authtype] ASC);
CREATE NONCLUSTERED INDEX [idx_authtype_id] ON [users] ([authtype] ASC, [authtype_id] ASC);
GO

-- =============================================================================
-- Migration 20260701000001: Platform baseline (API keys)
-- Source: nada56-api-keys-security-sqlsrv.sql
-- =============================================================================

-- ###################################################################################
-- # API Keys Security Enhancement - SQL Server Migration
-- # Adds secure key storage with hashing and prefix lookup
-- # Run this script to upgrade api_keys table for secure key management
-- ###################################################################################

-- Modify api_key column to allow NULL (for new secure keys)
ALTER TABLE api_keys ALTER COLUMN api_key VARCHAR(40) NULL;

-- Add new columns to api_keys table
ALTER TABLE api_keys ADD key_hash VARCHAR(255) NULL;
ALTER TABLE api_keys ADD key_prefix VARCHAR(12) NULL;
ALTER TABLE api_keys ADD expires_at INT NULL;
ALTER TABLE api_keys ADD last_used_at INT NULL;
ALTER TABLE api_keys ADD name VARCHAR(255) NULL;
ALTER TABLE api_keys ADD revoked_at INT NULL;
ALTER TABLE api_keys ADD created_by INT NULL;

-- Create indexes for performance
-- Note: These are NON-UNIQUE indexes to allow NULL values for legacy keys
-- and to support multiple keys with same expiration times
CREATE NONCLUSTERED INDEX IX_api_keys_key_prefix ON api_keys(key_prefix);
CREATE NONCLUSTERED INDEX IX_api_keys_key_hash ON api_keys(key_hash);
CREATE NONCLUSTERED INDEX IX_api_keys_expires_at ON api_keys(expires_at);
CREATE NONCLUSTERED INDEX IX_api_keys_user_revoked ON api_keys(user_id, revoked_at);

-- SQL Server unique indexes treat all NULLs as equal, so IX_api_keys on api_key
-- allows only one secure key (api_key IS NULL). Replace with a filtered index
-- that enforces uniqueness for legacy plaintext keys only.
IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID('api_keys') AND name = 'IX_api_keys'
)
    DROP INDEX [IX_api_keys] ON [api_keys];

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID('api_keys') AND name = 'IX_api_keys_legacy'
)
    CREATE UNIQUE NONCLUSTERED INDEX IX_api_keys_legacy ON api_keys(api_key)
    WHERE api_key IS NOT NULL;

-- Note: Legacy keys (where key_hash IS NULL) will continue to work
-- They will be automatically migrated to secure format on first use

-- =============================================================================
-- Migration 20260701000002: Sitelogs cutover
-- Rename legacy table, then create optimized sitelogs if missing.
-- =============================================================================

IF OBJECT_ID(N'dbo.sitelogs', N'U') IS NOT NULL
AND OBJECT_ID(N'dbo.sitelogs_legacy', N'U') IS NULL
    EXEC sp_rename 'sitelogs', 'sitelogs_legacy';
GO


-- =============================================================================
-- Migration 20260701000002: Sitelogs cutover (repair)
-- Source: nada56-fix-sitelogs-schema-sqlsrv.sql
-- =============================================================================

-- ============================================================
-- NADA 5.6+ sitelogs repair (SQL Server)
--
-- Use when sitelogs_legacy exists but the new optimized sitelogs
-- table was never created (e.g. rename succeeded but a later step failed).
-- Safe to re-run: skips CREATE when sitelogs already exists.
-- ============================================================

IF OBJECT_ID(N'dbo.sitelogs', N'U') IS NULL
BEGIN
    CREATE TABLE sitelogs (
        id int NOT NULL IDENTITY(1,1) PRIMARY KEY,
        sessionid varchar(255) NOT NULL DEFAULT '',
        logtime int NOT NULL DEFAULT 0,
        ip varchar(45) NOT NULL,
        url varchar(255) NOT NULL DEFAULT '',
        logtype varchar(45) NOT NULL,
        surveyid int DEFAULT '0',
        section varchar(255) DEFAULT NULL,
        keyword varchar(300) DEFAULT NULL,
        username varchar(100) DEFAULT NULL,
        useragent varchar(300) DEFAULT NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime ON sitelogs(logtime);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtype' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtype ON sitelogs(logtype);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveyid' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_surveyid ON sitelogs(surveyid);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_username' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_username ON sitelogs(username);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_ip' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_ip ON sitelogs(ip);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_section' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_section ON sitelogs(section);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime_logtype' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime_logtype ON sitelogs(logtime, logtype);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime_username' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime_username ON sitelogs(logtime, username);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime_ip' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime_ip ON sitelogs(logtime, ip);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveyid_logtime' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_surveyid_logtime ON sitelogs(surveyid, logtime);
GO

-- =============================================================================
-- Migration 20260701000003: Catalog & codelists foundation (PHP ONLY)
-- Run migration 20260701000003 in the web UI after this script.
-- =============================================================================


-- =============================================================================
-- Migration 20260701000004: Codelists & DSD versioning (filestore)
-- Source: nada56-filestore-sqlsrv.sql
-- =============================================================================

-- NADA 5.7 — filestore table (SQL Server)
-- One statement per line; re-runs skip "already exists" errors.

CREATE TABLE filestore (
  id int NOT NULL IDENTITY(1,1),
  file_name varchar(255) NULL,
  file_path varchar(500) NULL,
  file_ext varchar(10) NULL,
  is_image tinyint NULL,
  changed int NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_filestore ON filestore (file_name ASC);

-- =============================================================================
-- Migration 20260701000004: Codelists & DSD versioning
-- Source: nada56-codelists-dsd-sqlsrv.sql
-- =============================================================================

-- NADA 5.7 — codelists SDMX/PID columns and data structures (SQL Server)
-- Plain DDL/DML; duplicate object errors are skipped on re-run.

-- Codelists SDMX identity
ALTER TABLE codelists ADD agency varchar(64) NOT NULL CONSTRAINT df_codelists_agency DEFAULT 'NADA';
ALTER TABLE codelists ADD version varchar(32) NOT NULL CONSTRAINT df_codelists_version DEFAULT '1.0';
ALTER TABLE codelists ADD idno varchar(191) NULL;

UPDATE codelists SET agency = 'NADA' WHERE agency IS NULL OR agency = '';
UPDATE codelists SET version = '1.0' WHERE version IS NULL OR version = '';
UPDATE codelists SET idno = agency + '_' + name + '_' + version WHERE idno IS NULL OR idno = '';

ALTER TABLE codelists DROP CONSTRAINT unq_codelists_name;
DROP INDEX unq_codelists_name ON codelists;

ALTER TABLE codelists ADD CONSTRAINT unq_codelists_identity UNIQUE (agency, name, version);
CREATE UNIQUE INDEX unq_codelists_idno ON codelists(idno) WHERE idno IS NOT NULL;
CREATE INDEX idx_codelists_agency_name ON codelists(agency, name);

-- Codelists PID / versioning
ALTER TABLE codelists ADD pid int NULL;
ALTER TABLE codelists ADD version_seq int NULL;
ALTER TABLE codelists ADD status smallint NOT NULL CONSTRAINT df_codelists_status DEFAULT 0;
ALTER TABLE codelists ADD created int NULL;
ALTER TABLE codelists ADD changed int NULL;

UPDATE codelists SET status = 0 WHERE status IS NULL;

UPDATE c
SET version_seq = (
  SELECT COUNT(*)
  FROM codelists x
  WHERE x.agency = c.agency AND x.name = c.name AND x.id <= c.id
)
FROM codelists c
WHERE c.version_seq IS NULL OR c.version_seq <= 0;

ALTER TABLE codelists ALTER COLUMN version_seq int NOT NULL;

UPDATE c
SET pid = (
  SELECT MAX(id)
  FROM codelists x
  WHERE x.agency = c.agency AND x.name = c.name
)
FROM codelists c
WHERE c.pid IS NULL;

UPDATE codelists
SET created = DATEDIFF(SECOND, '1970-01-01', GETUTCDATE()),
    changed = DATEDIFF(SECOND, '1970-01-01', GETUTCDATE())
WHERE created IS NULL;

CREATE UNIQUE INDEX unq_codelists_family_seq ON codelists(agency, name, version_seq);
CREATE INDEX idx_codelists_pid ON codelists(pid);
ALTER TABLE codelists ADD CONSTRAINT fk_codelists_pid FOREIGN KEY (pid) REFERENCES codelists(id);

-- Data structures (new installs)
CREATE TABLE data_structures (
  id int NOT NULL IDENTITY(1,1),
  pid int NULL,
  agency varchar(64) NOT NULL CONSTRAINT df_data_structures_agency DEFAULT 'NADA',
  name varchar(64) NOT NULL,
  version varchar(32) NOT NULL,
  version_seq int NOT NULL,
  idno varchar(191) NULL,
  status smallint NOT NULL CONSTRAINT df_data_structures_status DEFAULT 0,
  title varchar(255) NULL,
  description varchar(255) NULL,
  notes nvarchar(max) NULL,
  content_hash char(64) NULL,
  metadata nvarchar(max) NULL,
  created int NULL,
  updated int NULL,
  created_by int NULL,
  updated_by int NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_data_structures_identity UNIQUE (agency, name, version)
);

CREATE UNIQUE INDEX unq_data_structures_idno ON data_structures(idno) WHERE idno IS NOT NULL;
CREATE UNIQUE INDEX unq_data_structures_family_seq ON data_structures(agency, name, version_seq);
CREATE INDEX idx_data_structures_agency_name ON data_structures(agency, name);
CREATE INDEX idx_data_structures_pid ON data_structures(pid);

CREATE TABLE data_structure_components (
  id int NOT NULL IDENTITY(1,1),
  data_structure_id int NOT NULL,
  sort_order int NOT NULL CONSTRAINT df_dsc_sort_order DEFAULT 0,
  name varchar(100) NOT NULL,
  label varchar(255) NULL,
  description nvarchar(max) NULL,
  data_type varchar(16) NULL,
  column_type varchar(32) NOT NULL,
  time_period_format varchar(30) NULL,
  codelist_id int NULL,
  metadata nvarchar(max) NULL,
  created int NULL,
  updated int NULL,
  created_by int NULL,
  updated_by int NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_dsc_structure_name UNIQUE (data_structure_id, name),
  CONSTRAINT fk_dsc_data_structure FOREIGN KEY (data_structure_id) REFERENCES data_structures (id) ON DELETE CASCADE,
  CONSTRAINT fk_dsc_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id)
);

CREATE INDEX idx_dsc_structure_sort ON data_structure_components (data_structure_id, sort_order);
CREATE INDEX idx_dsc_codelist ON data_structure_components (codelist_id);

-- Upgrade path: data_structures created by an earlier migration without PID columns
ALTER TABLE data_structures ADD pid int NULL;
ALTER TABLE data_structures ADD version_seq int NULL;

UPDATE ds
SET version_seq = (
  SELECT COUNT(*)
  FROM data_structures x
  WHERE x.agency = ds.agency
    AND x.name = ds.name
    AND (
      ISNULL(x.created, 0) < ISNULL(ds.created, 0)
      OR (ISNULL(x.created, 0) = ISNULL(ds.created, 0) AND x.id <= ds.id)
    )
)
FROM data_structures ds
WHERE ds.version_seq IS NULL OR ds.version_seq <= 0;

ALTER TABLE data_structures ALTER COLUMN version_seq int NOT NULL;

UPDATE ds
SET pid = (
  SELECT MAX(id)
  FROM data_structures x
  WHERE x.agency = ds.agency AND x.name = ds.name
)
FROM data_structures ds
WHERE ds.pid IS NULL;

CREATE UNIQUE INDEX unq_data_structures_family_seq ON data_structures(agency, name, version_seq);
CREATE INDEX idx_data_structures_pid ON data_structures(pid);
ALTER TABLE data_structures ADD CONSTRAINT fk_data_structures_pid FOREIGN KEY (pid) REFERENCES data_structures(id);

-- =============================================================================
-- Migration 20260701000005: Analytics schema
-- Source: analytics-schema-sqlsrv.sql
-- =============================================================================

-- ============================================================
-- Analytics Tracking System - SQL Server Schema
-- ============================================================

-- ============================================================
-- BACKUP: LEGACY COUNTS FOR MIGRATION/AUDIT
-- ============================================================

CREATE TABLE [analytics_legacy_counts] (
    [survey_id] int NOT NULL PRIMARY KEY,
    [total_views] int NOT NULL DEFAULT 0,
    [total_downloads] int NOT NULL DEFAULT 0,
    [backup_at] datetime NOT NULL DEFAULT GETDATE()
);
GO

-- ============================================================
-- 1. RAW PAGEVIEW EVENTS
-- Only used when built-in JS tracking is active
-- ============================================================

CREATE TABLE [analytics_pageview_events] (
    [id] bigint NOT NULL IDENTITY(1,1),
    [ts] datetime NOT NULL,
    [study_id] int NOT NULL,
    [session_id] nvarchar(255) NULL,
    [page_url] nvarchar(512) NULL,
    [section] nvarchar(100) NULL,
    [hashed_ip] nchar(64) NULL,
    [user_agent] nvarchar(200) NULL,
    [referrer] nvarchar(512) NULL,
    PRIMARY KEY ([id])
);
GO

CREATE NONCLUSTERED INDEX [idx_ts] ON [analytics_pageview_events] ([ts] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_pageview_events] ([study_id] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_session] ON [analytics_pageview_events] ([session_id] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_ts_study] ON [analytics_pageview_events] ([ts] ASC, [study_id] ASC);
GO
-- page_url omitted from key columns: nvarchar(512) would exceed SQL Server's 1700-byte index key limit.
CREATE NONCLUSTERED INDEX [idx_dedup] ON [analytics_pageview_events] ([study_id] ASC, [session_id] ASC, [section] ASC, [ts] ASC) INCLUDE ([page_url]);
GO

-- ============================================================
-- 2. RAW DOWNLOAD EVENTS
-- Server-side, authoritative source
-- ============================================================

CREATE TABLE [analytics_download_events] (
    [id] bigint NOT NULL IDENTITY(1,1),
    [ts] datetime NOT NULL,
    [study_id] int NOT NULL,
    [file_name] nvarchar(255) NOT NULL,
    [file_type] nvarchar(50) NULL,
    [hashed_ip] nchar(64) NULL,
    [user_agent] nvarchar(200) NULL,
    PRIMARY KEY ([id])
);
GO

CREATE NONCLUSTERED INDEX [idx_ts] ON [analytics_download_events] ([ts] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_download_events] ([study_id] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_study_file] ON [analytics_download_events] ([study_id] ASC, [file_name] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_ts_study_file] ON [analytics_download_events] ([ts] ASC, [study_id] ASC, [file_name] ASC);
GO

-- ============================================================
-- 3. DAILY STUDY-LEVEL AGGREGATES
-- Source: GA4 OR built-in raw pageview events
-- ============================================================

CREATE TABLE [analytics_daily_studies] (
    [date] date NOT NULL,
    [study_id] int NOT NULL,
    [pageviews] int NOT NULL DEFAULT 0,
    [unique_visitors] int NOT NULL DEFAULT 0,
    [downloads] int NOT NULL DEFAULT 0,
    PRIMARY KEY ([date], [study_id])
);
GO

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_daily_studies] ([study_id] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_date] ON [analytics_daily_studies] ([date] ASC);
GO

-- ============================================================
-- 4. MONTHLY STUDY-LEVEL AGGREGATES
-- Contains virtual month (year=0, month=0) for legacy totals
-- ============================================================

CREATE TABLE [analytics_monthly_studies] (
    [year] smallint NOT NULL,
    [month] tinyint NOT NULL,
    [study_id] int NOT NULL,
    [pageviews] int NOT NULL DEFAULT 0,
    [unique_visitors] int NOT NULL DEFAULT 0,
    [downloads] int NOT NULL DEFAULT 0,
    [finalized] tinyint NOT NULL DEFAULT 0,
    [finalized_at] datetime NULL,
    PRIMARY KEY ([year], [month], [study_id])
);
GO

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_monthly_studies] ([study_id] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_period] ON [analytics_monthly_studies] ([year] ASC, [month] ASC);
GO

-- ============================================================
-- 5. DAILY FILE-LEVEL DOWNLOAD AGGREGATES
-- Derived from analytics_download_events
-- ============================================================

CREATE TABLE [analytics_daily_files] (
    [date] date NOT NULL,
    [study_id] int NOT NULL,
    [file_name] nvarchar(255) NOT NULL,
    [downloads] int NOT NULL DEFAULT 0,
    PRIMARY KEY ([date], [study_id], [file_name])
);
GO

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_daily_files] ([study_id] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_file] ON [analytics_daily_files] ([file_name] ASC);
GO

-- ============================================================
-- 6. MONTHLY FILE-LEVEL DOWNLOAD AGGREGATES
-- Contains virtual month entries (year=0, month=0) if needed
-- ============================================================

CREATE TABLE [analytics_monthly_files] (
    [year] smallint NOT NULL,
    [month] tinyint NOT NULL,
    [study_id] int NOT NULL,
    [file_name] nvarchar(255) NOT NULL,
    [downloads] int NOT NULL DEFAULT 0,
    [finalized] tinyint NOT NULL DEFAULT 0,
    [finalized_at] datetime NULL,
    PRIMARY KEY ([year], [month], [study_id], [file_name])
);
GO

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_monthly_files] ([study_id] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_file] ON [analytics_monthly_files] ([file_name] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_period] ON [analytics_monthly_files] ([year] ASC, [month] ASC);
GO


-- analytics_aggregation_status
CREATE TABLE [analytics_aggregation_status] (
    [id] int NOT NULL IDENTITY(1,1),
    [status] nvarchar(20) NOT NULL DEFAULT 'idle',
    [current_step] nvarchar(50) NULL,
    [current_item] nvarchar(100) NULL,
    [total_items] int NULL DEFAULT 0,
    [processed_items] int NULL DEFAULT 0,
    [progress_percent] int NULL DEFAULT 0,
    [message] nvarchar(max) NULL,
    [started_at] datetime NULL,
    [completed_at] datetime NULL,
    [last_updated_at] datetime NULL,
    [error_message] nvarchar(max) NULL,
    [context] nvarchar(20) NOT NULL DEFAULT 'cli', -- 'cli' or 'web'
    [user_id] int NULL,
    PRIMARY KEY ([id])
);
GO

CREATE NONCLUSTERED INDEX [idx_status] ON [analytics_aggregation_status] ([status] ASC);
GO
CREATE NONCLUSTERED INDEX [idx_last_updated_at] ON [analytics_aggregation_status] ([last_updated_at] ASC);
GO

-- =============================================================================
-- Migration 20260701000006: Timeseries platform
-- Source: nada56-surveys-ts-fields-sqlsrv.sql
-- =============================================================================

-- NADA 5.7 — surveys timeseries columns (SQL Server)
-- Plain DDL; duplicate column/index errors are skipped on re-run.

ALTER TABLE surveys ADD data_structure_id int NULL;
CREATE NONCLUSTERED INDEX idx_surveys_data_structure_id ON surveys (data_structure_id);
ALTER TABLE surveys ADD CONSTRAINT fk_surveys_data_structure FOREIGN KEY (data_structure_id) REFERENCES data_structures (id);

ALTER TABLE surveys ADD ts_db_id int NULL;
ALTER TABLE surveys ADD ts_dimensions nvarchar(2000) NULL;
ALTER TABLE surveys ADD ts_frequency nvarchar(500) NULL;
ALTER TABLE surveys ADD ts_sync_required tinyint NOT NULL CONSTRAINT df_surveys_ts_sync_required DEFAULT 0;
ALTER TABLE surveys ADD ts_data_count bigint NOT NULL CONSTRAINT df_surveys_ts_data_count DEFAULT 0;

CREATE NONCLUSTERED INDEX idx_surveys_ts_db_id ON surveys (ts_db_id);

-- =============================================================================
-- Migration 20260701000006: Timeseries platform
-- Source: nada56-timeseries-value-counts-sqlsrv.sql
-- =============================================================================

-- NADA 5.7 — timeseries_value_counts table (SQL Server)

CREATE TABLE timeseries_value_counts (
  id int NOT NULL IDENTITY(1,1),
  sid int NOT NULL,
  dsd_id int NOT NULL,
  component_name varchar(100) NOT NULL,
  code varchar(255) NOT NULL,
  obs_count int NOT NULL CONSTRAINT df_tsvc_obs_count DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT unq_tsvc_scope_value UNIQUE (sid, dsd_id, component_name, code),
  CONSTRAINT fk_tsvc_sid FOREIGN KEY (sid) REFERENCES surveys (id) ON DELETE CASCADE,
  CONSTRAINT fk_tsvc_dsd FOREIGN KEY (dsd_id) REFERENCES data_structures (id) ON DELETE CASCADE
);

CREATE INDEX idx_tsvc_scope ON timeseries_value_counts (sid, dsd_id, component_name);

-- =============================================================================
-- Migration 20260701000006: Timeseries platform
-- Source: nada56-timeseries-db-links-sqlsrv.sql
-- =============================================================================

-- NADA 5.7 — timeseries_db_links table (SQL Server)

CREATE TABLE timeseries_db_links (
  id INT IDENTITY(1,1) PRIMARY KEY,
  series_id INT NOT NULL,
  db_idno NVARCHAR(255) NOT NULL,
  is_primary TINYINT NOT NULL DEFAULT 0,
  CONSTRAINT uq_series_db UNIQUE (series_id, db_idno)
);

CREATE NONCLUSTERED INDEX idx_tsdbl_series_primary ON timeseries_db_links (series_id ASC, is_primary ASC);
CREATE NONCLUSTERED INDEX idx_tsdbl_db_idno ON timeseries_db_links (db_idno ASC);

CREATE UNIQUE NONCLUSTERED INDEX idx_surveys_idno ON surveys (idno ASC);

-- =============================================================================
-- Migration 20260701000007: Repositories ACL (PHP ONLY)
-- Migration 20260701000008: Site configuration (PHP ONLY)
-- Run these versions in the web UI.
-- =============================================================================


-- =============================================================================
-- Migration 20260701000009: Catalog search indexes (citations)
-- Source: nada56-citation-indexes-sqlsrv.sql
-- =============================================================================

-- ============================================================
-- NADA 5.6 Citation Performance Indexes - SQL Server
--
-- Adds indexes required for citation search filtering and
-- batch author/survey-count loading. Safe to run on existing
-- databases — each index is only created if it does not
-- already exist.
-- ============================================================


-- ============================================================
-- citations
-- Filters: published, ctype, pub_year, flag, url_status
-- JOIN to users table: created_by, changed_by
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citations_published' AND object_id = OBJECT_ID('citations'))
    CREATE NONCLUSTERED INDEX idx_citations_published  ON [dbo].[citations] ([published]);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citations_ctype' AND object_id = OBJECT_ID('citations'))
    CREATE NONCLUSTERED INDEX idx_citations_ctype      ON [dbo].[citations] ([ctype]);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citations_pub_year' AND object_id = OBJECT_ID('citations'))
    CREATE NONCLUSTERED INDEX idx_citations_pub_year   ON [dbo].[citations] ([pub_year]);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citations_flag' AND object_id = OBJECT_ID('citations'))
    CREATE NONCLUSTERED INDEX idx_citations_flag       ON [dbo].[citations] ([flag]);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citations_url_status' AND object_id = OBJECT_ID('citations'))
    CREATE NONCLUSTERED INDEX idx_citations_url_status ON [dbo].[citations] ([url_status]);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citations_created_by' AND object_id = OBJECT_ID('citations'))
    CREATE NONCLUSTERED INDEX idx_citations_created_by ON [dbo].[citations] ([created_by]);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citations_changed_by' AND object_id = OBJECT_ID('citations'))
    CREATE NONCLUSTERED INDEX idx_citations_changed_by ON [dbo].[citations] ([changed_by]);


-- ============================================================
-- survey_citations
-- The existing (sid, citationid) unique index cannot serve
-- citationid-leading lookups for batch count queries and
-- the NOT EXISTS no_survey_attached filter.
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_citations_citationid' AND object_id = OBJECT_ID('survey_citations'))
    CREATE NONCLUSTERED INDEX idx_survey_citations_citationid ON [dbo].[survey_citations] ([citationid]);


-- ============================================================
-- citation_authors
-- MySQL has (cid, author_type) index; SQL Server was missing it.
-- Required for batch author loading: WHERE cid IN (...) AND author_type = 'author'
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_citation_authors_cid_type' AND object_id = OBJECT_ID('citation_authors'))
    CREATE NONCLUSTERED INDEX idx_citation_authors_cid_type ON [dbo].[citation_authors] ([cid], [author_type]);

-- =============================================================================
-- Migration 20260701000009: Catalog search indexes
-- Source: nada56-search-indexes-sqlsrv.sql
-- =============================================================================

-- ============================================================
-- NADA 5.6 Search Performance Indexes - SQL Server
--
-- Adds missing indexes required for catalog search and
-- variable search filtering. Safe to run on existing databases
-- (each index is only created if it does not already exist).
-- ============================================================


-- ============================================================
-- surveys
-- Nearly every search query filters on published=1. Type,
-- repositoryid, formid, year_start, total_views, changed,
-- and created are used for filtering and ORDER BY.
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_published' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_published ON surveys (published);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_type' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_type ON surveys (type);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_repositoryid' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_repositoryid ON surveys (repositoryid);

-- formid: used for dtype (license) filter and JOIN to forms
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_formid' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_formid ON surveys (formid);

-- data_class_id: used for data classification filter
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_data_class_id' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_data_class_id ON surveys (data_class_id);

-- Sorting columns
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_year_start' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_year_start ON surveys (year_start);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_total_views' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_total_views ON surveys (total_views);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_changed' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_changed ON surveys (changed);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_created' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_created ON surveys (created);


-- ============================================================
-- survey_years
-- Year range filter: WHERE data_coll_year BETWEEN ? AND ?
-- The existing IX_sur_years index has sid as the leading
-- column, so SQL Server cannot use it for range scans on
-- data_coll_year. A new index with data_coll_year leading
-- and sid as an included column fixes this.
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_years_year_sid' AND object_id = OBJECT_ID('survey_years'))
    CREATE NONCLUSTERED INDEX idx_survey_years_year_sid ON survey_years (data_coll_year) INCLUDE (sid);


-- ============================================================
-- survey_countries
-- Country filter: SELECT sid FROM survey_countries WHERE cid IN (...)
-- The existing IX_surv_countries index is on (sid, country_name)
-- and cannot be used for cid lookups. The join sc.sid = surveys.id
-- is already covered by the existing index (sid is leading column).
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_countries_cid' AND object_id = OBJECT_ID('survey_countries'))
    CREATE NONCLUSTERED INDEX idx_survey_countries_cid ON survey_countries (cid) INCLUDE (sid);


-- ============================================================
-- survey_repos
-- Currently has no indexes beyond the primary key.
-- Used for:
--   - Repository filter:   WHERE repositoryid = ?
--   - Collections filter:  SELECT sid FROM survey_repos WHERE repositoryid IN (...)
--   - JOIN lookups:        JOIN survey_repos ON surveys.id = survey_repos.sid
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_repos_repositoryid' AND object_id = OBJECT_ID('survey_repos'))
    CREATE NONCLUSTERED INDEX idx_survey_repos_repositoryid ON survey_repos (repositoryid) INCLUDE (sid);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_repos_sid' AND object_id = OBJECT_ID('survey_repos'))
    CREATE NONCLUSTERED INDEX idx_survey_repos_sid ON survey_repos (sid);


-- ============================================================
-- survey_facets
-- Currently has no indexes beyond the primary key.
-- Used for every user-defined facet filter:
--   SELECT sid FROM survey_facets WHERE term_id IN (...)
-- Also hit on JOIN: survey_facets.sid = surveys.id
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_facets_term_id' AND object_id = OBJECT_ID('survey_facets'))
    CREATE NONCLUSTERED INDEX idx_survey_facets_term_id ON survey_facets (term_id) INCLUDE (sid);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_facets_sid' AND object_id = OBJECT_ID('survey_facets'))
    CREATE NONCLUSTERED INDEX idx_survey_facets_sid ON survey_facets (sid);


-- ============================================================
-- survey_tags
-- Tag filter: SELECT sid FROM survey_tags WHERE tag IN (...)
-- The existing IX_survey_tags index is on (sid, tag), so
-- tag-based lookups require a full index scan.
-- ============================================================

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_survey_tags_tag' AND object_id = OBJECT_ID('survey_tags'))
    CREATE NONCLUSTERED INDEX idx_survey_tags_tag ON survey_tags (tag) INCLUDE (sid);

-- =============================================================================
-- Migration 20260701000010: Variables Unicode
-- Source: nada56-variables-unicode-sqlsrv.sql
-- =============================================================================

-- ============================================================
-- NADA 5.6+ variables Unicode (SQL Server)
--
-- Converts variables text/varchar columns to NVARCHAR and
-- recreates the full-text index. Safe to re-run: skips columns
-- that are already nvarchar and skips CREATE when objects exist.
--
-- Run one ALTER at a time on very large catalogs if SSMS times out.
-- ============================================================

-- Drop full-text index when any indexed column is not yet nvarchar
IF EXISTS (
    SELECT 1
    FROM sys.fulltext_indexes fi
    INNER JOIN sys.tables t ON fi.object_id = t.object_id
    WHERE t.name = 'variables'
)
AND EXISTS (
    SELECT 1
    FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables'
      AND c.name IN ('catgry', 'labl', 'name', 'qstn')
      AND ty.name <> 'nvarchar'
)
    DROP FULLTEXT INDEX ON variables;
GO

-- Drop unique index on vid before altering vid (and related key columns)
IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_variables' AND object_id = OBJECT_ID('variables')
)
AND EXISTS (
    SELECT 1
    FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'vid' AND ty.name <> 'nvarchar'
)
    DROP INDEX [IX_variables] ON [variables];
GO

IF COL_LENGTH('variables', 'fid') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'fid' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN fid nvarchar(45) NULL;
GO

IF COL_LENGTH('variables', 'vid') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'vid' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN vid nvarchar(45) NULL;
GO

IF COL_LENGTH('variables', 'name') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'name' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN name nvarchar(100) NULL;
GO

IF COL_LENGTH('variables', 'labl') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'labl' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN labl nvarchar(255) NULL;
GO

IF COL_LENGTH('variables', 'qstn') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'qstn' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN qstn nvarchar(max) NULL;
GO

IF COL_LENGTH('variables', 'catgry') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'catgry' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN catgry nvarchar(max) NULL;
GO

IF COL_LENGTH('variables', 'metadata') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'metadata' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN metadata nvarchar(max) NULL;
GO

IF COL_LENGTH('variables', 'keywords') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'keywords' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN keywords nvarchar(max) NULL;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_variables' AND object_id = OBJECT_ID('variables')
)
    CREATE UNIQUE NONCLUSTERED INDEX [IX_variables] ON [variables] ([vid] ASC, [sid] ASC);
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.fulltext_indexes fi
    INNER JOIN sys.tables t ON fi.object_id = t.object_id
    WHERE t.name = 'variables'
)
    CREATE FULLTEXT INDEX ON variables
    (catgry Language 1033, labl Language 1033, name Language 1033, qstn Language 1033)
    KEY INDEX pk_idx_variables;
GO

-- =============================================================================
-- Migration 20260707120001: Analytics pageview tracking
-- Source: nada56-analytics-pageview-tracking-sqlsrv.sql
-- =============================================================================

-- ============================================================
-- NADA 5.7 analytics pageview tracking (SQL Server)
--
-- Adds page_url/section columns and fixes idx_dedup for existing
-- analytics installs created before the INCLUDE-based index.
-- Safe to re-run.
-- ============================================================

IF OBJECT_ID(N'dbo.analytics_pageview_events', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.analytics_pageview_events', N'page_url') IS NULL
    ALTER TABLE [analytics_pageview_events] ADD [page_url] NVARCHAR(512) NULL;
GO

IF OBJECT_ID(N'dbo.analytics_pageview_events', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.analytics_pageview_events', N'section') IS NULL
    ALTER TABLE [analytics_pageview_events] ADD [section] NVARCHAR(100) NULL;
GO

IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_dedup' AND object_id = OBJECT_ID('analytics_pageview_events')
)
    DROP INDEX [idx_dedup] ON [analytics_pageview_events];
GO

IF OBJECT_ID(N'dbo.analytics_pageview_events', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_dedup' AND object_id = OBJECT_ID('analytics_pageview_events')
)
    CREATE NONCLUSTERED INDEX [idx_dedup]
    ON [analytics_pageview_events] ([study_id] ASC, [session_id] ASC, [section] ASC, [ts] ASC)
    INCLUDE ([page_url]);
GO

-- =============================================================================
-- Migration 20260909120001: Display templates (PHP ONLY)
-- Run migration 20260909120001 in the web UI.
-- =============================================================================


-- =============================================================================
-- Migration 20260909120002: Catalog search queue
-- Source: nada56-search-queue-sqlsrv.sql
-- =============================================================================

-- ============================================================
-- NADA 5.7 catalog search queue and filter indexes (SQL Server)
--
-- Safe to re-run: skips CREATE when tables/indexes already exist.
-- ============================================================

IF OBJECT_ID(N'dbo.search_index_queue', N'U') IS NULL
BEGIN
    CREATE TABLE search_index_queue (
        id INT NOT NULL IDENTITY(1,1),
        object_type VARCHAR(32) NOT NULL,
        object_id INT NOT NULL,
        object_key VARCHAR(200) NOT NULL,
        change_class VARCHAR(32) NOT NULL,
        status VARCHAR(16) NOT NULL CONSTRAINT df_search_index_queue_status DEFAULT 'pending',
        attempts INT NOT NULL CONSTRAINT df_search_index_queue_attempts DEFAULT 0,
        last_error VARCHAR(500) NULL,
        changed INT NOT NULL,
        PRIMARY KEY (id),
        CONSTRAINT uk_search_index_queue_object UNIQUE (object_type, object_id)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_search_index_queue_status_changed'
      AND object_id = OBJECT_ID('search_index_queue')
)
    CREATE NONCLUSTERED INDEX idx_search_index_queue_status_changed
    ON search_index_queue (status, changed);
GO

IF OBJECT_ID(N'dbo.search_index_state', N'U') IS NULL
BEGIN
    CREATE TABLE search_index_state (
        object_type VARCHAR(32) NOT NULL,
        object_id INT NOT NULL,
        object_key VARCHAR(200) NOT NULL,
        status VARCHAR(16) NOT NULL,
        changed INT NOT NULL,
        PRIMARY KEY (object_type, object_id)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_search_index_state_status'
      AND object_id = OBJECT_ID('search_index_state')
)
    CREATE NONCLUSTERED INDEX idx_search_index_state_status
    ON search_index_state (status);
GO

IF OBJECT_ID(N'dbo.survey_countries', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_survey_countries_cid' AND object_id = OBJECT_ID('survey_countries')
)
    CREATE NONCLUSTERED INDEX idx_survey_countries_cid ON survey_countries (cid ASC) INCLUDE (sid);
GO

IF OBJECT_ID(N'dbo.survey_repos', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_survey_repos_repositoryid' AND object_id = OBJECT_ID('survey_repos')
)
    CREATE NONCLUSTERED INDEX idx_survey_repos_repositoryid ON survey_repos (repositoryid ASC) INCLUDE (sid);
GO

IF OBJECT_ID(N'dbo.survey_repos', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_survey_repos_sid' AND object_id = OBJECT_ID('survey_repos')
)
    CREATE NONCLUSTERED INDEX idx_survey_repos_sid ON survey_repos (sid ASC);
GO

IF OBJECT_ID(N'dbo.survey_facets', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_survey_facets_term_id' AND object_id = OBJECT_ID('survey_facets')
)
    CREATE NONCLUSTERED INDEX idx_survey_facets_term_id ON survey_facets (term_id ASC) INCLUDE (sid);
GO

IF OBJECT_ID(N'dbo.survey_facets', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_survey_facets_sid' AND object_id = OBJECT_ID('survey_facets')
)
    CREATE NONCLUSTERED INDEX idx_survey_facets_sid ON survey_facets (sid ASC);
GO

IF OBJECT_ID(N'dbo.survey_years', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_survey_years_year_sid' AND object_id = OBJECT_ID('survey_years')
)
    CREATE NONCLUSTERED INDEX idx_survey_years_year_sid ON survey_years (data_coll_year ASC) INCLUDE (sid);
GO

IF OBJECT_ID(N'dbo.survey_tags', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_survey_tags_tag' AND object_id = OBJECT_ID('survey_tags')
)
    CREATE NONCLUSTERED INDEX idx_survey_tags_tag ON survey_tags (tag ASC) INCLUDE (sid);
GO

IF OBJECT_ID(N'dbo.region_countries', N'U') IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'idx_region_countries_region' AND object_id = OBJECT_ID('region_countries')
)
    CREATE NONCLUSTERED INDEX idx_region_countries_region ON region_countries (region_id ASC) INCLUDE (country_id);
GO

-- =============================================================================
-- Migration 20260909120003: Data deposit platform
-- Run schema.dd.sqlsrv.sql only when dd_projects is missing.
-- =============================================================================


-- =============================================================================
-- Migration 20260909120003: Data deposit alter
-- Source: nada56-datadeposit-alter-sqlsrv.sql
-- =============================================================================

-- NADA 5.7 — data deposit column upgrades (SQL Server)
-- Requires dd_projects / dd_project_resources tables. Skips duplicates on re-run.

ALTER TABLE dd_projects ADD schema_version tinyint NOT NULL CONSTRAINT df_dd_projects_schema_version DEFAULT 1;
ALTER TABLE dd_projects ADD submission nvarchar(max) NULL;
ALTER TABLE dd_projects ADD data_type varchar(20) NULL;
ALTER TABLE dd_projects ADD metadata varchar(max) NULL;

UPDATE dd_projects SET data_type = 'survey'
WHERE data_type IS NULL OR data_type = '';

ALTER TABLE dd_project_resources ADD dctype varchar(100) NULL;
ALTER TABLE dd_project_resources ADD dcformat varchar(100) NULL;
ALTER TABLE dd_project_resources ADD filesize float NULL;

-- =============================================================================
-- Migration 20260909120004: site_user_register (PHP ONLY)
-- Run migration 20260909120004 in the web UI.
-- =============================================================================

