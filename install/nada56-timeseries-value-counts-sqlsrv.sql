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
