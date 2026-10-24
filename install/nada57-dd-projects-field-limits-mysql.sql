-- NADA — widen dd_projects title/shortname and submission note columns (MySQL)

ALTER TABLE `dd_projects` MODIFY COLUMN `title` VARCHAR(500) NOT NULL;
ALTER TABLE `dd_projects` MODIFY COLUMN `shortname` VARCHAR(255) NOT NULL;
ALTER TABLE `dd_projects` MODIFY COLUMN `embargoed` MEDIUMTEXT NULL;
ALTER TABLE `dd_projects` MODIFY COLUMN `disclosure_risk` MEDIUMTEXT NULL;
ALTER TABLE `dd_projects` MODIFY COLUMN `key_variables` MEDIUMTEXT NULL;
ALTER TABLE `dd_projects` MODIFY COLUMN `sensitive_variables` MEDIUMTEXT NULL;
