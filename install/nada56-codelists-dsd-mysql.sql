-- NADA 5.7 — codelists SDMX/PID columns and data structures (MySQL)
-- Plain DDL/DML; duplicate object errors are skipped on re-run.

ALTER TABLE `codelists` ADD COLUMN `agency` VARCHAR(64) NOT NULL DEFAULT 'NADA';
ALTER TABLE `codelists` ADD COLUMN `version` VARCHAR(32) NOT NULL DEFAULT '1.0';
ALTER TABLE `codelists` ADD COLUMN `idno` VARCHAR(191) DEFAULT NULL;

UPDATE `codelists` SET `agency` = 'NADA' WHERE `agency` IS NULL OR `agency` = '';
UPDATE `codelists` SET `version` = '1.0' WHERE `version` IS NULL OR `version` = '';
UPDATE `codelists` SET `idno` = CONCAT(`agency`, '_', `name`, '_', `version`) WHERE `idno` IS NULL OR `idno` = '';

ALTER TABLE `codelists` DROP INDEX `unq_codelists_name`;

ALTER TABLE `codelists` ADD UNIQUE KEY `unq_codelists_identity` (`agency`, `name`, `version`);
ALTER TABLE `codelists` ADD UNIQUE KEY `unq_codelists_idno` (`idno`);
ALTER TABLE `codelists` ADD KEY `idx_codelists_agency_name` (`agency`, `name`);

ALTER TABLE `codelists` ADD COLUMN `pid` INT(11) NULL;
ALTER TABLE `codelists` ADD COLUMN `version_seq` INT(11) NULL;
ALTER TABLE `codelists` ADD COLUMN `status` SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE `codelists` ADD COLUMN `created` INT(11) NULL;
ALTER TABLE `codelists` ADD COLUMN `changed` INT(11) NULL;

UPDATE codelists c
JOIN (
  SELECT c2.id, COUNT(*) AS seq
  FROM codelists c2
  INNER JOIN codelists x
    ON x.agency = c2.agency AND x.name = c2.name AND x.id <= c2.id
  GROUP BY c2.id
) ranked ON ranked.id = c.id
SET c.version_seq = ranked.seq
WHERE c.version_seq IS NULL OR c.version_seq <= 0;

ALTER TABLE `codelists` MODIFY COLUMN `version_seq` INT(11) NOT NULL;

UPDATE codelists c
JOIN (
  SELECT agency, name, MAX(id) AS latest_id
  FROM codelists
  GROUP BY agency, name
) latest ON latest.agency = c.agency AND latest.name = c.name
SET c.pid = latest.latest_id
WHERE c.pid IS NULL OR c.pid <> latest.latest_id;

UPDATE `codelists` SET `created` = UNIX_TIMESTAMP(), `changed` = UNIX_TIMESTAMP() WHERE `created` IS NULL;

ALTER TABLE `codelists` ADD UNIQUE KEY `unq_codelists_family_seq` (`agency`, `name`, `version_seq`);
ALTER TABLE `codelists` ADD KEY `idx_codelists_pid` (`pid`);
ALTER TABLE `codelists` ADD CONSTRAINT `fk_codelists_pid` FOREIGN KEY (`pid`) REFERENCES `codelists` (`id`);

CREATE TABLE `data_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NULL,
  `agency` varchar(64) NOT NULL DEFAULT 'NADA',
  `name` varchar(64) NOT NULL,
  `version` varchar(32) NOT NULL,
  `version_seq` int(11) NOT NULL,
  `idno` varchar(191) DEFAULT NULL,
  `status` smallint NOT NULL DEFAULT 0,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` text,
  `content_hash` char(64) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_data_structures_identity` (`agency`,`name`,`version`),
  UNIQUE KEY `unq_data_structures_idno` (`idno`),
  UNIQUE KEY `unq_data_structures_family_seq` (`agency`,`name`,`version_seq`),
  KEY `idx_data_structures_agency_name` (`agency`,`name`),
  KEY `idx_data_structures_pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `data_structure_components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_structure_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `name` varchar(100) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `description` text,
  `data_type` enum('string','integer','float','double','date','boolean') DEFAULT NULL,
  `column_type` enum('dimension','time_period','measure','attribute','indicator_id','indicator_name','annotation','geography','observation_value','periodicity') NOT NULL,
  `time_period_format` varchar(30) DEFAULT NULL,
  `codelist_id` int(11) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_dsc_structure_name` (`data_structure_id`,`name`),
  KEY `idx_dsc_structure_sort` (`data_structure_id`,`sort_order`),
  KEY `idx_dsc_codelist` (`codelist_id`),
  CONSTRAINT `fk_dsc_data_structure` FOREIGN KEY (`data_structure_id`) REFERENCES `data_structures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dsc_codelist` FOREIGN KEY (`codelist_id`) REFERENCES `codelists` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `data_structures` ADD COLUMN `pid` INT(11) NULL;
ALTER TABLE `data_structures` ADD COLUMN `version_seq` INT(11) NULL;

UPDATE data_structures ds
JOIN (
  SELECT ds2.id, COUNT(*) AS seq
  FROM data_structures ds2
  INNER JOIN data_structures x
    ON x.agency = ds2.agency AND x.name = ds2.name
    AND (COALESCE(x.created,0) < COALESCE(ds2.created,0)
      OR (COALESCE(x.created,0) = COALESCE(ds2.created,0) AND x.id <= ds2.id))
  GROUP BY ds2.id
) ranked ON ranked.id = ds.id
SET ds.version_seq = ranked.seq
WHERE ds.version_seq IS NULL OR ds.version_seq <= 0;

ALTER TABLE `data_structures` MODIFY COLUMN `version_seq` INT(11) NOT NULL;

UPDATE data_structures ds
JOIN (
  SELECT agency, name, MAX(id) AS latest_id
  FROM data_structures
  GROUP BY agency, name
) latest ON latest.agency = ds.agency AND latest.name = ds.name
SET ds.pid = latest.latest_id
WHERE ds.pid IS NULL OR ds.pid <> latest.latest_id;

ALTER TABLE `data_structures` ADD UNIQUE KEY `unq_data_structures_family_seq` (`agency`,`name`,`version_seq`);
ALTER TABLE `data_structures` ADD KEY `idx_data_structures_pid` (`pid`);
ALTER TABLE `data_structures` ADD CONSTRAINT `fk_data_structures_pid` FOREIGN KEY (`pid`) REFERENCES `data_structures` (`id`);
