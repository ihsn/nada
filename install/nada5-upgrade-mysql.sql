###################################################################################
# Upgrade nada42/44 mysql database to nada5
###################################################################################

DROP TABLE `blocks`;
TRUNCATE TABLE `cache`;

ALTER TABLE `surveys` CHANGE `ddifilename` `metafile` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `surveys` ADD `metadata` mediumtext;
ALTER TABLE `surveys` ADD `changed_by` int(11) DEFAULT NULL;
ALTER TABLE `surveys` ADD `created_by` int(11) DEFAULT NULL;
ALTER TABLE `surveys` ADD `license_id` int(11) DEFAULT NULL;
ALTER TABLE `surveys` CHANGE `data_coll_start` `year_start` int(11) DEFAULT '0';
ALTER TABLE `surveys` CHANGE `data_coll_end` `year_end` int(11) DEFAULT '0';
ALTER TABLE `surveys` CHANGE `authenty` `authoring_entity` text;
ALTER TABLE `surveys` CHANGE `titl` `title` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `surveys` ADD `type` varchar(15) DEFAULT NULL;
ALTER TABLE `surveys` CHANGE `surveyid` `idno` VARCHAR(200) NOT NULL;
ALTER TABLE `surveys` ADD UNIQUE KEY `surveyid_UNIQUE` (`idno`);
ALTER TABLE `surveys` ADD `ts_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `surveys` CHANGE `nation` `nation` varchar(150) DEFAULT '';
ALTER TABLE `surveys` CHANGE `repositoryid` `repositoryid` varchar(100) DEFAULT 'central';
ALTER TABLE `surveys` ADD `thumbnail` varchar(300) DEFAULT NULL;
ALTER TABLE `surveys` CHANGE `varcount` `varcount` int(11) DEFAULT '0';

ALTER TABLE `surveys` DROP `ie_program`;
ALTER TABLE `surveys` DROP `ie_project_id`;
ALTER TABLE `surveys` DROP `ie_project_name`;
ALTER TABLE `surveys` DROP `ie_project_uri`;
ALTER TABLE `surveys` DROP `ie_team_leaders`;
ALTER TABLE `surveys` DROP `project_id`;
ALTER TABLE `surveys` DROP `project_name`;
ALTER TABLE `surveys` DROP `project_uri`;
ALTER TABLE `surveys` DROP `scope`;
ALTER TABLE `surveys` DROP `proddate`;
ALTER TABLE `surveys` DROP `titlstmt`;
ALTER TABLE `surveys` DROP `isdeleted`;
ALTER TABLE `surveys` DROP `isshared`;
ALTER TABLE `surveys` DROP `kindofdata`;
ALTER TABLE `surveys` DROP `ddi_sh`;
ALTER TABLE `surveys` DROP `countryid`;

ALTER TABLE `surveys` DROP INDEX `idx_srvy_unq`;
ALTER TABLE `surveys` ADD UNIQUE KEY `idx_srvy_unq` (`idno`,`repositoryid`);
ALTER TABLE `surveys` DROP INDEX `ft_all`;
#ALTER TABLE `surveys` ADD FULLTEXT KEY `ft_all` (`title`,`authoring_entity`,`nation`,`abbreviation`,`keywords`,`idno`);
ALTER TABLE `surveys` DROP INDEX `ft_titl`;
ALTER TABLE `surveys` ADD FULLTEXT KEY `ft_titl` (`title`);

ALTER TABLE `surveys` ADD `var_keywords` mediumtext DEFAULT NULL;
ALTER TABLE `surveys` 
DROP INDEX `ft_keywords` ,
ADD FULLTEXT INDEX `ft_keywords` (`keywords` ASC, `var_keywords` ASC);


ALTER TABLE `surveys` DROP `refno`;
ALTER TABLE `surveys` DROP `sername`;
ALTER TABLE `surveys` DROP `geogcover`;
ALTER TABLE `surveys` DROP `producer`;
ALTER TABLE `surveys` DROP `sponsor`;
ALTER TABLE `surveys` DROP `topic`;

UPDATE `surveys` set `type`='survey';

ALTER TABLE `users` ADD `otp_code` varchar(45) DEFAULT NULL;
ALTER TABLE `users` ADD `otp_expiry` int(11) DEFAULT NULL;


DROP TABLE IF EXISTS `variables`;

CREATE TABLE `variables` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `fid` varchar(45) DEFAULT NULL,
  `vid` varchar(45) DEFAULT '',
  `name` varchar(100) DEFAULT '',
  `labl` varchar(255) DEFAULT '',
  `qstn` text,
  `catgry` text,
  `keywords` text,
  `metadata` mediumtext,  
  PRIMARY KEY (`uid`),
  UNIQUE KEY `idxSurvey` (`vid`,`sid`),
  KEY `idxsurveyidfk` (`sid`),
  FULLTEXT KEY `idx_nm_lbl_qstn` (`name`,`labl`,`qstn`,`catgry`),
  FULLTEXT KEY `idx_nm_lbl_cat_qstn` (`name`,`labl`,`catgry`,`qstn`,`keywords`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


UPDATE `citations` set ihsn_id=id where ihsn_id is NULL;
ALTER TABLE `citations` CHANGE `ihsn_id` `uuid` varchar(50) NOT NULL;
ALTER TABLE `citations` ADD UNIQUE KEY `cit_uuid` (`uuid`);
ALTER TABLE `citations` ADD `url_status` varchar(50) DEFAULT NULL;
ALTER TABLE `citations` ADD `created_by` int(11) DEFAULT NULL;
ALTER TABLE `citations` ADD `changed_by` int(11) DEFAULT NULL;
ALTER TABLE `citations` ADD `attachment` varchar(300) DEFAULT NULL;
ALTER TABLE `citations` ADD `lang` varchar(45) DEFAULT NULL;
ALTER TABLE `citations` ADD FULLTEXT KEY `ft_cit2` (`title`,`subtitle`,`authors`,`organization`,`abstract`,`keywords`,`notes`,`doi`);
ALTER TABLE `citations` ADD FULLTEXT KEY `ft_citations` (`title`,`subtitle`,`alt_title`,`authors`,`editors`,`translators`);


ALTER TABLE `resources` ADD `filesize` varchar(50) DEFAULT NULL;
ALTER TABLE `resources` ADD `changed_by` int(11) DEFAULT NULL;
ALTER TABLE `resources` DROP `id_number`;
ALTER TABLE `resources` ADD KEY `cascade_resources` (`survey_id`);


ALTER TABLE `survey_notes` ADD KEY `cascade_survey_notes` (`sid`);
ALTER TABLE `survey_notes` CHANGE `sid` `sid` int(10) NOT NULL;
ALTER TABLE `survey_aliases` CHANGE `sid` `sid` int(10) NOT NULL;

ALTER TABLE `survey_repos` CHANGE `sid` `sid` int(10) NOT NULL;
ALTER TABLE `survey_repos` ADD KEY `cascade_survey_repos` (`sid`);

ALTER TABLE `public_requests` ADD KEY `cascade_pubilc_requests` (`surveyid`);
ALTER TABLE `lic_files` ADD KEY `cascade_lic_files` (`surveyid`);
ALTER TABLE `survey_aliases` ADD KEY `cascade_survey_aliases` (`sid`);
ALTER TABLE `featured_surveys` ADD KEY `cascade_featured_surveys` (`sid`);
ALTER TABLE `da_collection_surveys` ADD KEY `del_da_coll_surveys` (`sid`);
ALTER TABLE `survey_lic_requests` ADD KEY `cascade_survey_lic_requests` (`sid`);


CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(40) NOT NULL,
  `level` int(2) NOT NULL,
  `ignore_limits` tinyint(1) NOT NULL DEFAULT '0',
  `ip_addresses` text,
  `date_created` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_private_key` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_UNIQUE` (`key`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL,
  `method` varchar(6) NOT NULL,
  `params` text,
  `api_key` varchar(40) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `time` int(11) NOT NULL,
  `rtime` float DEFAULT NULL,
  `authorized` varchar(1) NOT NULL,
  `response_code` smallint(3) DEFAULT '0',
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `data_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `file_id` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `description` text,
  `case_count` int(11) DEFAULT NULL,
  `var_count` int(11) DEFAULT NULL,
  `producer` varchar(255) DEFAULT NULL,
  `data_checks` varchar(255) DEFAULT NULL,
  `missing_data` varchar(255) DEFAULT NULL,
  `version` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `survey_file` (`sid`,`file_id`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `data_files_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `fid` varchar(45) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `file_format` varchar(45) DEFAULT NULL,
  `api_use` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `file_resource` (`sid`,`resource_id`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `deleted_objects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `object_type` varchar(45) DEFAULT NULL,
  `object_id` int(11) DEFAULT NULL,
  `object_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `obj_type` (`object_type`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `survey_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `location` geometry NOT NULL,
  PRIMARY KEY (`id`),
  SPATIAL KEY `idx_location` (`location`)
) DEFAULT CHARSET=utf8;

CREATE TABLE `variable_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `vgid` varchar(45) DEFAULT NULL,
  `variables` varchar(5000) DEFAULT NULL,
  `variable_groups` varchar(500) DEFAULT NULL,
  `group_type` varchar(45) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `universe` varchar(255) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `txt` varchar(500) DEFAULT NULL,
  `definition` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


CREATE TABLE `ts_databases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idno` varchar(150) DEFAULT NULL,
  `title` varchar(300) DEFAULT NULL,
  `abstract` text,
  `published` tinyint(4) DEFAULT NULL,
  `created` varchar(45) DEFAULT NULL,
  `changed` varchar(45) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `metadata` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idno_UNIQUE` (`idno`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



CREATE TABLE `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(300) DEFAULT NULL,
  `license_text` varchar(5000) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `code_UNIQUE` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;


LOCK TABLES `licenses` WRITE;
/*!40000 ALTER TABLE `licenses` DISABLE KEYS */;
INSERT INTO `licenses` (id,code,title) VALUES 
(9,'cc_by_40','CC-BY 4.0'),
(10,'other_cc','Other CC'),
(11,'custom','Custom'),
(12,'data_na','Data not available'),
(13,'esri','ESRI Enterprise License Agreement'),
(14,'na','License not applicable'),
(15,'external','License specified externally'),
(16,'odbl','Open Database License (ODBL)'),
(17,'licensed','Licensed Research Data'),
(18,'public','Public Research Data');
/*!40000 ALTER TABLE `licenses` ENABLE KEYS */;
UNLOCK TABLES;




--- 
--- For MYSQL 5.6 or later only
--- requires INNODB with fulltext and geometry 
--- 

--- ALTER TABLE `sitelogs` ENGINE = InnoDB;
--- ALTER TABLE `surveys` ENGINE = InnoDB;
--- ALTER TABLE `tokens` ENGINE = InnoDB;
--- ALTER TABLE `variables` ENGINE = InnoDB;
--- ALTER TABLE `citations` ENGINE = InnoDB;
--- ALTER TABLE `forms` ENGINE = InnoDB;

