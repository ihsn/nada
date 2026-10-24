-- NADA 5.7 — timeseries_db_links table (MySQL)

CREATE TABLE `timeseries_db_links` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `series_id` INT(11) NOT NULL,
  `db_idno` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_series_db` (`series_id`, `db_idno`),
  KEY `idx_series_primary` (`series_id`, `is_primary`),
  KEY `idx_db_idno` (`db_idno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `surveys` ADD UNIQUE KEY `idx_surveys_idno` (`idno`);
