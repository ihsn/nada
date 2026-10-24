-- NADA 5.7 — filestore table (MySQL)
-- One statement per line; re-runs skip "already exists" errors.

CREATE TABLE `filestore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_ext` varchar(10) DEFAULT NULL,
  `is_image` tinyint(4) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_filestore_file` (`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
