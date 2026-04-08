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


