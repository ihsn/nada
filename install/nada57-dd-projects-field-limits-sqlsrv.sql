-- NADA — widen dd_projects title/shortname and submission note columns (SQL Server)

ALTER TABLE dd_projects ALTER COLUMN title varchar(500) NOT NULL;
ALTER TABLE dd_projects ALTER COLUMN shortname varchar(255) NOT NULL;
ALTER TABLE dd_projects ALTER COLUMN embargoed varchar(max) NULL;
ALTER TABLE dd_projects ALTER COLUMN disclosure_risk varchar(max) NULL;
ALTER TABLE dd_projects ALTER COLUMN key_variables varchar(max) NULL;
ALTER TABLE dd_projects ALTER COLUMN sensitive_variables varchar(max) NULL;
