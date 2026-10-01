-- NADA 5.7 — timeseries_value_counts table (MySQL)

CREATE TABLE `timeseries_value_counts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `dsd_id` int(11) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `code` varchar(255) NOT NULL,
  `obs_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_tsvc_scope_value` (`sid`,`dsd_id`,`component_name`,`code`),
  KEY `idx_tsvc_scope` (`sid`,`dsd_id`,`component_name`),
  CONSTRAINT `fk_tsvc_sid` FOREIGN KEY (`sid`) REFERENCES `surveys` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tsvc_dsd` FOREIGN KEY (`dsd_id`) REFERENCES `data_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
