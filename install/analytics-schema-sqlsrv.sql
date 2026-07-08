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
CREATE NONCLUSTERED INDEX [idx_dedup] ON [analytics_pageview_events] ([study_id] ASC, [session_id] ASC, [section] ASC, [page_url] ASC, [ts] ASC);
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
