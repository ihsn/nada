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
