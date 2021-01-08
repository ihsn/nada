
CREATE TABLE `census_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `census` int(11) DEFAULT NULL,
  `scst` varchar(3) DEFAULT NULL,
  `table_id` varchar(45) NOT NULL,
  `geo_level` varchar(45) DEFAULT NULL COMMENT 'national\nstate\ndistrict\nsubdistrict\ntown\nvillage',
  `state_code` varchar(45) DEFAULT NULL,
  `district_code` varchar(45) DEFAULT NULL,
  `town_code` varchar(45) DEFAULT NULL,
  `subdistrict_code` varchar(45) DEFAULT NULL,
  `village_code` varchar(45) DEFAULT NULL,
  `residence` varchar(45) DEFAULT NULL COMMENT 'national\nurban\nrural',
  `value` varchar(45) DEFAULT NULL,
  `feature_1` varchar(45) DEFAULT NULL,
  `feature_2` varchar(45) DEFAULT NULL,
  `feature_3` varchar(45) DEFAULT NULL,
  `feature_4` varchar(45) DEFAULT NULL,
  `feature_5` varchar(45) DEFAULT NULL,
  `feature_6` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;


CREATE TABLE `census_table_types` (
  `id` int(11) NOT NULL,
  `table_id` varchar(45) DEFAULT NULL,
  `title` varchar(45) DEFAULT NULL,
  `description` varchar(45) DEFAULT NULL,
  `unit_observation` varchar(45) DEFAULT NULL,
  `feature_1` varchar(200) DEFAULT NULL,
  `feature_2` varchar(200) DEFAULT NULL,
  `feature_3` varchar(200) DEFAULT NULL,
  `feature_4` varchar(200) DEFAULT NULL,
  `feature_5` varchar(200) DEFAULT NULL,
  `feature_6` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `data_tables_places` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lvl` tinyint(4) DEFAULT NULL,
  `uid` varchar(45) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lvl_uid` (`lvl`,`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
