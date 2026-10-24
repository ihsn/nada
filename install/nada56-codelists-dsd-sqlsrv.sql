-- NADA 5.7 — codelists SDMX/PID columns and data structures (SQL Server)
-- Plain DDL/DML; duplicate object errors are skipped on re-run.

-- Codelists SDMX identity
ALTER TABLE codelists ADD agency varchar(64) NOT NULL CONSTRAINT df_codelists_agency DEFAULT 'NADA';
ALTER TABLE codelists ADD version varchar(32) NOT NULL CONSTRAINT df_codelists_version DEFAULT '1.0';
ALTER TABLE codelists ADD idno varchar(191) NULL;

UPDATE codelists SET agency = 'NADA' WHERE agency IS NULL OR agency = '';
UPDATE codelists SET version = '1.0' WHERE version IS NULL OR version = '';
UPDATE codelists SET idno = agency + '_' + name + '_' + version WHERE idno IS NULL OR idno = '';

ALTER TABLE codelists DROP CONSTRAINT unq_codelists_name;
DROP INDEX unq_codelists_name ON codelists;

ALTER TABLE codelists ADD CONSTRAINT unq_codelists_identity UNIQUE (agency, name, version);
CREATE UNIQUE INDEX unq_codelists_idno ON codelists(idno) WHERE idno IS NOT NULL;
CREATE INDEX idx_codelists_agency_name ON codelists(agency, name);

-- Codelists PID / versioning
ALTER TABLE codelists ADD pid int NULL;
ALTER TABLE codelists ADD version_seq int NULL;
ALTER TABLE codelists ADD status smallint NOT NULL CONSTRAINT df_codelists_status DEFAULT 0;
ALTER TABLE codelists ADD created int NULL;
ALTER TABLE codelists ADD changed int NULL;

UPDATE codelists SET status = 0 WHERE status IS NULL;

UPDATE c
SET version_seq = (
  SELECT COUNT(*)
  FROM codelists x
  WHERE x.agency = c.agency AND x.name = c.name AND x.id <= c.id
)
FROM codelists c
WHERE c.version_seq IS NULL OR c.version_seq <= 0;

ALTER TABLE codelists ALTER COLUMN version_seq int NOT NULL;

UPDATE c
SET pid = (
  SELECT MAX(id)
  FROM codelists x
  WHERE x.agency = c.agency AND x.name = c.name
)
FROM codelists c
WHERE c.pid IS NULL;

UPDATE codelists
SET created = DATEDIFF(SECOND, '1970-01-01', GETUTCDATE()),
    changed = DATEDIFF(SECOND, '1970-01-01', GETUTCDATE())
WHERE created IS NULL;

CREATE UNIQUE INDEX unq_codelists_family_seq ON codelists(agency, name, version_seq);
CREATE INDEX idx_codelists_pid ON codelists(pid);
ALTER TABLE codelists ADD CONSTRAINT fk_codelists_pid FOREIGN KEY (pid) REFERENCES codelists(id);

-- Data structures (new installs)
CREATE TABLE data_structures (
  id int NOT NULL IDENTITY(1,1),
  pid int NULL,
  agency varchar(64) NOT NULL CONSTRAINT df_data_structures_agency DEFAULT 'NADA',
  name varchar(64) NOT NULL,
  version varchar(32) NOT NULL,
  version_seq int NOT NULL,
  idno varchar(191) NULL,
  status smallint NOT NULL CONSTRAINT df_data_structures_status DEFAULT 0,
  title varchar(255) NULL,
  description varchar(255) NULL,
  notes nvarchar(max) NULL,
  content_hash char(64) NULL,
  metadata nvarchar(max) NULL,
  created int NULL,
  updated int NULL,
  created_by int NULL,
  updated_by int NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_data_structures_identity UNIQUE (agency, name, version)
);

CREATE UNIQUE INDEX unq_data_structures_idno ON data_structures(idno) WHERE idno IS NOT NULL;
CREATE UNIQUE INDEX unq_data_structures_family_seq ON data_structures(agency, name, version_seq);
CREATE INDEX idx_data_structures_agency_name ON data_structures(agency, name);
CREATE INDEX idx_data_structures_pid ON data_structures(pid);

CREATE TABLE data_structure_components (
  id int NOT NULL IDENTITY(1,1),
  data_structure_id int NOT NULL,
  sort_order int NOT NULL CONSTRAINT df_dsc_sort_order DEFAULT 0,
  name varchar(100) NOT NULL,
  label varchar(255) NULL,
  description nvarchar(max) NULL,
  data_type varchar(16) NULL,
  column_type varchar(32) NOT NULL,
  time_period_format varchar(30) NULL,
  codelist_id int NULL,
  metadata nvarchar(max) NULL,
  created int NULL,
  updated int NULL,
  created_by int NULL,
  updated_by int NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_dsc_structure_name UNIQUE (data_structure_id, name),
  CONSTRAINT fk_dsc_data_structure FOREIGN KEY (data_structure_id) REFERENCES data_structures (id) ON DELETE CASCADE,
  CONSTRAINT fk_dsc_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id)
);

CREATE INDEX idx_dsc_structure_sort ON data_structure_components (data_structure_id, sort_order);
CREATE INDEX idx_dsc_codelist ON data_structure_components (codelist_id);

-- Upgrade path: data_structures created by an earlier migration without PID columns
ALTER TABLE data_structures ADD pid int NULL;
ALTER TABLE data_structures ADD version_seq int NULL;

UPDATE ds
SET version_seq = (
  SELECT COUNT(*)
  FROM data_structures x
  WHERE x.agency = ds.agency
    AND x.name = ds.name
    AND (
      ISNULL(x.created, 0) < ISNULL(ds.created, 0)
      OR (ISNULL(x.created, 0) = ISNULL(ds.created, 0) AND x.id <= ds.id)
    )
)
FROM data_structures ds
WHERE ds.version_seq IS NULL OR ds.version_seq <= 0;

ALTER TABLE data_structures ALTER COLUMN version_seq int NOT NULL;

UPDATE ds
SET pid = (
  SELECT MAX(id)
  FROM data_structures x
  WHERE x.agency = ds.agency AND x.name = ds.name
)
FROM data_structures ds
WHERE ds.pid IS NULL;

CREATE UNIQUE INDEX unq_data_structures_family_seq ON data_structures(agency, name, version_seq);
CREATE INDEX idx_data_structures_pid ON data_structures(pid);
ALTER TABLE data_structures ADD CONSTRAINT fk_data_structures_pid FOREIGN KEY (pid) REFERENCES data_structures(id);
