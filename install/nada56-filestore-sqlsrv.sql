-- NADA 5.7 — filestore table (SQL Server)
-- One statement per line; re-runs skip "already exists" errors.

CREATE TABLE filestore (
  id int NOT NULL IDENTITY(1,1),
  file_name varchar(255) NULL,
  file_path varchar(500) NULL,
  file_ext varchar(10) NULL,
  is_image tinyint NULL,
  changed int NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_filestore ON filestore (file_name ASC);
