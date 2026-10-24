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
