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
