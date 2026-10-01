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
