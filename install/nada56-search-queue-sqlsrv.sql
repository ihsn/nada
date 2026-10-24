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
