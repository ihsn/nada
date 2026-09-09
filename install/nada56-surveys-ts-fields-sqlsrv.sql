-- ============================================================
-- NADA 5.6 — surveys timeseries (ts_*) columns — SQL Server
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

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('surveys') AND name = 'ts_db_id')
    ALTER TABLE surveys ADD ts_db_id INT NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('surveys') AND name = 'ts_dimensions')
    ALTER TABLE surveys ADD ts_dimensions NVARCHAR(2000) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('surveys') AND name = 'ts_frequency')
    ALTER TABLE surveys ADD ts_frequency NVARCHAR(500) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('surveys') AND name = 'ts_sync_required')
    ALTER TABLE surveys ADD ts_sync_required TINYINT NOT NULL CONSTRAINT df_surveys_ts_sync_required DEFAULT 0;

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('surveys') AND name = 'ts_data_count')
    ALTER TABLE surveys ADD ts_data_count BIGINT NOT NULL CONSTRAINT df_surveys_ts_data_count DEFAULT 0;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveys_ts_db_id' AND object_id = OBJECT_ID('surveys'))
    CREATE NONCLUSTERED INDEX idx_surveys_ts_db_id ON surveys (ts_db_id);
