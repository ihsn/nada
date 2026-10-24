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
