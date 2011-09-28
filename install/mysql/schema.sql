# TABLE STRUCTURE FOR: sitelogs
CREATE TABLE `sitelogs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sessionid` varchar(255) NOT NULL DEFAULT '',
  `logtime` varchar(45) NOT NULL DEFAULT '0',
  `ip` varchar(45) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `logtype` varchar(45) NOT NULL,
  `surveyid` int(10) unsigned NOT NULL DEFAULT '0',
  `section` varchar(255) DEFAULT NULL,
  `keyword` text,
  `username` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: surveys
CREATE TABLE `surveys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repositoryid` varchar(128) NOT NULL,
  `surveyid` varchar(200) DEFAULT NULL,
  `titl` varchar(255) DEFAULT '',
  `titlstmt` text,
  `authenty` varchar(255) DEFAULT NULL,
  `geogcover` varchar(255) DEFAULT NULL,
  `nation` varchar(100) DEFAULT '',
  `topic` text,
  `scope` text,
  `sername` varchar(255) DEFAULT NULL,
  `producer` varchar(255) DEFAULT NULL,
  `sponsor` varchar(255) DEFAULT NULL,
  `refno` varchar(255) DEFAULT NULL,
  `proddate` varchar(45) DEFAULT NULL,
  `varcount` decimal(10,0) DEFAULT NULL,
  `ddifilename` varchar(255) DEFAULT NULL,
  `dirpath` varchar(255) DEFAULT NULL,
  `link_technical` varchar(255) DEFAULT NULL COMMENT 'documentation',
  `link_study` varchar(255) DEFAULT NULL COMMENT 'study website',
  `link_report` varchar(255) DEFAULT NULL COMMENT 'reports',
  `link_indicator` varchar(255) DEFAULT NULL COMMENT 'indicators',
  `ddi_sh` char(2) DEFAULT NULL,
  `formid` int(10) unsigned DEFAULT NULL,
  `isshared` tinyint(1) NOT NULL DEFAULT '1',
  `isdeleted` tinyint(1) NOT NULL DEFAULT '0',
  `changed` varchar(255) DEFAULT NULL,
  `created` varchar(255) DEFAULT NULL,
  `link_questionnaire` varchar(255) DEFAULT NULL,
  `countryid` int(10) unsigned DEFAULT NULL,
  `data_coll_start` int(11) DEFAULT NULL,
  `data_coll_end` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_srvy_unq` (`surveyid`,`repositoryid`),
  FULLTEXT KEY `ft_titl` (`titl`),
  FULLTEXT KEY `ft_all` (`titl`,`authenty`,`geogcover`,`nation`,`topic`,`scope`,`sername`,`producer`,`sponsor`,`refno`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: variables
CREATE TABLE `variables` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `varID` varchar(45) DEFAULT '',
  `name` varchar(45) DEFAULT '',
  `labl` varchar(245) DEFAULT '',
  `qstn` text,
  `catgry` text,
  `surveyid_FK` int(10) unsigned NOT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `idxSurvey` (`varID`,`surveyid_FK`),
  KEY `idxsurveyidfk` (`surveyid_FK`),
  FULLTEXT KEY `idx_qstn` (`qstn`),
  FULLTEXT KEY `idx_labl` (`labl`),
  FULLTEXT KEY `idxCatgry` (`catgry`),
  FULLTEXT KEY `idx_nm_lbl_qstn` (`name`,`labl`,`qstn`),
  FULLTEXT KEY `idx_nm_lbl_cat_qstn` (`name`,`labl`,`catgry`,`qstn`),
  FULLTEXT KEY `idx_nm` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: blocks
CREATE TABLE `blocks` (
  `bid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `body` text,
  `region` varchar(255) DEFAULT NULL,
  `weight` int(10) unsigned DEFAULT NULL,
  `published` int(10) unsigned DEFAULT NULL,
  `pages` text,
  PRIMARY KEY (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: citations
CREATE TABLE `citations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `alt_title` varchar(255) DEFAULT NULL,
  `authors` text,
  `editors` text,
  `translators` text,
  `changed` int(10) unsigned DEFAULT NULL,
  `created` int(10) unsigned DEFAULT NULL,
  `published` tinyint(3) unsigned DEFAULT '1',
  `volume` varchar(45) DEFAULT NULL,
  `issue` varchar(45) DEFAULT NULL,
  `idnumber` varchar(45) DEFAULT NULL,
  `edition` varchar(45) DEFAULT NULL,
  `place_publication` varchar(255) DEFAULT NULL,
  `place_state` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_medium` tinyint(3) unsigned DEFAULT '0' COMMENT '0=print, 1=online',
  `url` varchar(255) DEFAULT NULL,
  `page_from` varchar(5) DEFAULT NULL,
  `page_to` varchar(5) DEFAULT NULL,
  `data_accessed` varchar(45) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `ctype` varchar(45) NOT NULL,
  `pub_day` varchar(15) DEFAULT NULL,
  `pub_month` varchar(45) DEFAULT NULL,
  `pub_year` int(10) unsigned DEFAULT NULL,
  `abstract` text,
  `keywords` text,
  `notes` text,
  `doi` varchar(255) DEFAULT NULL,,
  `flag` varchar(45) DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: lic_file_downloads
CREATE TABLE `lic_file_downloads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fileid` varchar(45) NOT NULL,
  `downloads` varchar(45) DEFAULT NULL,
  `download_limit` varchar(45) DEFAULT NULL,
  `expiry` int(10) unsigned DEFAULT NULL,
  `lastdownloaded` int(10) unsigned DEFAULT NULL,
  `requestid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: lic_files
CREATE TABLE `lic_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `surveyid` int(10) unsigned NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `changed` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: lic_files_log
CREATE TABLE `lic_files_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requestid` int(10) unsigned NOT NULL,
  `fileid` int(10) unsigned NOT NULL,
  `ip` varchar(20) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='licensed files download log';



# TABLE STRUCTURE FOR: lic_requests
CREATE TABLE `lic_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `surveyid` int(11) NOT NULL,
  `org_rec` varchar(200) DEFAULT NULL,
  `org_type` varchar(45) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tel` varchar(150) DEFAULT NULL,
  `fax` varchar(100) DEFAULT NULL,
  `datause` text,
  `outputs` text,
  `compdate` varchar(45) DEFAULT NULL,
  `datamatching` int(10) unsigned DEFAULT NULL,
  `mergedatasets` text,
  `team` text,
  `dataset_access` varchar(20) DEFAULT 'whole',
  `created` int(10) unsigned DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `comments` text,
  `locked` tinyint(4) DEFAULT NULL,
  `orgtype_other` varchar(145) DEFAULT NULL,
  `updated` int(10) unsigned DEFAULT NULL,
  `updatedby` varchar(45) DEFAULT NULL,
  `ip_limit` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: meta
CREATE TABLE `meta` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


# TABLE STRUCTURE FOR: planned_surveys
CREATE TABLE `planned_surveys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `abbreviation` varchar(255) DEFAULT NULL,
  `studytype` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `geocoverage` varchar(255) DEFAULT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `pinvestigator` varchar(255) DEFAULT NULL,
  `producers` varchar(255) DEFAULT NULL,
  `sponsors` varchar(255) DEFAULT NULL,
  `fundingstatus` int(10) unsigned DEFAULT NULL,
  `samplesize` int(10) unsigned DEFAULT NULL,
  `sampleunit` varchar(45) DEFAULT NULL,
  `datacollstart` int(10) unsigned DEFAULT NULL,
  `datacollend` int(10) unsigned DEFAULT NULL,
  `expect_rep_date` int(10) unsigned DEFAULT NULL,
  `expect_data_policy` text,
  `expect_micro_rel_date` int(10) unsigned DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: repositories
CREATE TABLE `repositories` (
  `repositoryid` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `organization` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: resources
CREATE TABLE `resources` (
  `resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` int(10) unsigned NOT NULL,
  `dctype` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `dcdate` varchar(45) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `language` varchar(255) DEFAULT NULL,
  `id_number` varchar(255) DEFAULT NULL,
  `contributor` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `rights` varchar(255) DEFAULT NULL,
  `description` text ,
  `abstract` text ,
  `toc` text ,
  `subjects` varchar(45) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `dcformat` varchar(255) DEFAULT NULL,
  `changed` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: survey_citations
CREATE TABLE `survey_citations` (
  `sid` int(10) unsigned DEFAULT NULL,
  `citationid` int(10) unsigned DEFAULT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Idx_s_c` (`sid`,`citationid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: survey_topics
CREATE TABLE `survey_topics` (
  `sid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `Idx_uniq` (`tid`,`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: survey_years
CREATE TABLE `survey_years` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned DEFAULT NULL,
  `data_coll_year` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: ci_sessions
CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) DEFAULT '0',
  `user_agent` varchar(50) DEFAULT NULL,
  `last_activity` int(10) unsigned DEFAULT '0',
  `user_data` text,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: tokens
CREATE TABLE `tokens` (
  `tokenid` varchar(100) NOT NULL,
  `dated` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tokenid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: users
CREATE TABLE `users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` mediumint(8) unsigned NOT NULL,
  `ip_address` char(16) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(1000) NOT NULL,
  `salt` varchar(40) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `activation_code` varchar(40) DEFAULT NULL,
  `forgotten_password_code` varchar(40) DEFAULT NULL,
  `remember_code` varchar(40) DEFAULT NULL,
  `created_on` int(10) unsigned NOT NULL,
  `last_login` int(10) unsigned NOT NULL,
  `active` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: citation_authors
CREATE TABLE `citation_authors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `initial` varchar(255) DEFAULT NULL,
  `author_type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: public_requests
CREATE TABLE `public_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `surveyid` int(10) unsigned NOT NULL,
  `abstract` text NOT NULL,
  `posted` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


#
# TABLE STRUCTURE FOR: configurations
#

CREATE TABLE `configurations` (
  `name` varchar(200) NOT NULL,
  `value` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `helptext` varchar(255) DEFAULT NULL,
  `item_group` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('app_version', '3.0.3-12.09.2010', 'Application version', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('cache_path', 'application/cache', 'Site cache folder', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('catalog_records_per_page', '15', 'Catalog search page - records per page', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('catalog_root', 'datafiles', 'Survey catalog folder', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('db_version', '3.0.3-12.09.2010', 'Database version', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('ddi_import_folder', 'imports', 'Survey catalog import folder', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('default_home_page', 'catalog', 'Default home page', 'Default home page', NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('html_folder', '/pages', NULL, NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('lang', 'en-us', 'Site Language', 'Site Language code', NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('login_timeout', '40', 'Login timeout (minutes)', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('mail_protocol', 'smtp', 'Select method for sending emails', 'Supported protocols: MAIL, SMTP, SENDMAIL', NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('min_password_length', '5', 'Minimum password length', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('regional_search', 'yes', 'Enable regional search', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('repository_identifier', 'default', 'Repository Identifier', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('site_password_protect', 'no', 'Password protect website', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_auth', 'no', 'Use SMTP Authentication', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_debug', 'yes', 'Enable SMTP Debugging', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_host', 'ihsn.org', 'SMTP Host name', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_pass', 'free001', 'SMTP password', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_port', '25', 'SMTP port', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_secure', 'no', 'Use Secure SMTP?', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_user', 'nada@ihsn.org', 'SMTP username', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('theme', 'default', 'Site theme name', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('topics_vocab', '1', 'Vocabulary ID for Topics', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('topic_search', 'yes', 'Topic search', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('use_html_editor', 'yes', 'Use HTML editor for entering HTML for static pages', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_footer', 'Powered by NADA 3.0 and DDI', 'Website footer text', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_title', 'Your website title here', 'Website title', 'Provide the title of the website', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_url', 'http://localhost/nada3', 'Website URL', 'URL of the website', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_webmaster_email', 'webmaster@example.com', 'Site webmaster email address', '-', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_webmaster_name', 'noreply', 'Webmaster name', '-', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('year_search', 'yes', NULL, NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('news_feed_url', 'http://ihsn.org/nada/index.php?q=news/feed', '', '', '');

#
# TABLE STRUCTURE FOR: countries
#

CREATE TABLE `countries` (
  `countryid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(65) NOT NULL,
  `iso3` varchar(3) NOT NULL,
  PRIMARY KEY (`countryid`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8;

INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (1, 'Afghanistan', 'AFG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (2, 'Albania', 'ALB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (3, 'Antartica', 'ATA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (4, 'Algeria', 'DZA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (5, 'American Samoa', 'ASM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (6, 'Andorra', 'AND');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (7, 'Angola', 'AGO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (8, 'Antigua and Barbuda', 'ATG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (9, 'Azerbaijan', 'AZE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (10, 'Argentina', 'ARG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (11, 'Australia', 'AUS');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (12, 'Austria', 'AUT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (13, 'Bahamas', 'BHS');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (14, 'Bahrain', 'BHR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (15, 'Bangladesh', 'BGD');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (16, 'Armenia', 'ARM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (17, 'Barbados', 'BRB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (18, 'Belgium', 'BEL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (19, 'Bermuda', 'BMU');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (20, 'Bhutan', 'BTN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (21, 'Bolivia', 'BOL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (22, 'Bosnia-Herzegovina', 'BIH');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (23, 'Botswana', 'BWA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (24, 'Bouvet Island', 'BVT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (25, 'Brazil', 'BRA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (26, 'Belize', 'BLZ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (27, 'British Indian Ocean Territory', 'IOT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (28, 'Solomon Islands', 'SLB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (29, 'Virgin Isld. (British)', 'VGB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (30, 'Brunei', 'BRN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (31, 'Bulgaria', 'BGR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (32, 'Myanmar', 'MMR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (33, 'Burundi', 'BDI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (34, 'Belarus', 'BLR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (35, 'Cambodia', 'KHM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (36, 'Cameroon', 'CMR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (37, 'Canada', 'CAN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (38, 'Cape Verde', 'CPV');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (39, 'Cayman Islands', 'CYM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (40, 'Central African Republic', 'CAF');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (41, 'Sri Lanka', 'LKA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (42, 'Chad', 'TCD');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (43, 'Chile', 'CHL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (44, 'China', 'CHN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (45, 'Taiwan', 'TWN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (46, 'Christmas Island', 'CXR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (47, 'Cocos Isld.', 'CCK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (48, 'Colombia', 'COL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (49, 'Comoros', 'COM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (50, 'Mayotte', 'MYT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (51, 'Congo, Rep.', 'COG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (52, 'Congo, Dem. Rep.', 'COD');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (53, 'Cook Island', 'COK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (54, 'Costa Rica', 'CRI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (55, 'Croatia', 'HRV');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (56, 'Cuba', 'CUB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (57, 'Cyprus', 'CYP');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (58, 'Czech Republic', 'CZE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (59, 'Benin', 'BEN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (60, 'Denmark', 'DNK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (61, 'Dominica', 'DMA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (62, 'Dominican Republic', 'DOM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (63, 'Ecuador', 'ECU');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (64, 'El Salvador', 'SLV');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (65, 'Equatorial Guinea', 'GNQ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (66, 'Ethiopia', 'ETH');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (67, 'Eritrea', 'ERI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (68, 'Estonia', 'EST');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (69, 'Faeroe Isld.', 'FRO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (70, 'Falkland Isld.', 'FLK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (71, 'S. Georgia & S. Sandwich Isld.', 'SGS');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (72, 'Fiji', 'FJI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (73, 'Finland', 'FIN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (74, 'France, Metrop.', 'FXX');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (75, 'France', 'FRA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (76, 'French Guiana', 'GUF');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (77, 'French Polynesia', 'PYF');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (78, 'French S.T.', 'ATF');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (79, 'Djibouti', 'DJI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (80, 'Gabon', 'GAB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (81, 'Georgia', 'GEO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (82, 'Gambia', 'GMB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (83, 'West Bank and Gaza', 'PSE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (84, 'Germany', 'DEU');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (85, 'Ghana', 'GHA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (86, 'Gibraltar', 'GIB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (87, 'Kiribati', 'KIR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (88, 'Greece', 'GRC');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (89, 'Greenland', 'GRL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (90, 'Grenada', 'GRD');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (91, 'Guadeloupe', 'GLP');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (92, 'Guam', 'GUM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (93, 'Guatemala', 'GTM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (94, 'Guinea', 'GIN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (95, 'Guyana', 'GUY');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (96, 'Haiti', 'HTI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (97, 'Heard / McDonald Isld', 'HMD');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (98, 'Holy See', 'VAT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (99, 'Honduras', 'HND');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (100, 'Hungary', 'HUN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (101, 'Iceland', 'ISL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (102, 'India', 'IND');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (103, 'Indonesia', 'IDN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (104, 'Iran, Islamic Rep.', 'IRN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (105, 'Iraq', 'IRQ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (106, 'Ireland', 'IRL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (107, 'Israel', 'ISR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (108, 'Italy', 'ITA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (109, 'CÃ´te d\'Ivoire', 'CIV');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (110, 'Jamaica', 'JAM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (111, 'Japan', 'JPN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (112, 'Kazakhstan', 'KAZ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (113, 'Jordan', 'JOR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (114, 'Kenya', 'KEN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (115, 'Korea, Dem. Rep.', 'PRK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (116, 'Korea, Rep.', 'KOR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (117, 'Kuwait', 'KWT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (118, 'Kyrgyz Republic', 'KGZ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (119, 'Lao PDR', 'LAO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (120, 'Lebanon', 'LBN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (121, 'Lesotho', 'LSO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (122, 'Latvia', 'LVA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (123, 'Liberia', 'LBR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (124, 'Libya', 'LBY');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (125, 'Liechtenstein', 'LIE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (126, 'Lithuania', 'LTU');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (127, 'Luxembourg', 'LUX');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (128, 'Macao', 'MAC');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (129, 'Madagascar', 'MDG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (130, 'Malawi', 'MWI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (131, 'Malaysia', 'MYS');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (132, 'Maldives', 'MDV');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (133, 'Mali', 'MLI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (134, 'Malta', 'MLT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (135, 'Martinique', 'MTQ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (136, 'Mauritania', 'MRT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (137, 'Mauritius', 'MUS');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (138, 'Mexico', 'MEX');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (139, 'Monaco', 'MCO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (140, 'Mongolia', 'MNG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (141, 'Moldova', 'MDA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (142, 'Montserrat', 'MSR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (143, 'Morocco', 'MAR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (144, 'Mozambique', 'MOZ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (145, 'Oman', 'OMN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (146, 'Namibia', 'NAM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (147, 'Nauru', 'NRU');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (148, 'Nepal', 'NPL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (149, 'Netherlands', 'NLD');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (150, 'Neth.Antilles', 'ANT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (151, 'Aruba', 'ABW');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (152, 'New Caledonia', 'NCL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (153, 'Vanuatu', 'VUT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (154, 'New Zealand', 'NZL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (155, 'Nicaragua', 'NIC');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (156, 'Niger', 'NER');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (157, 'Nigeria', 'NGA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (158, 'Niue', 'NIU');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (159, 'Norfolk Isld.', 'NFK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (160, 'Norway', 'NOR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (161, 'N. Mariana Isld.', 'MNP');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (162, 'US minor outlying Islands', 'UMI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (163, 'Micronesia', 'FSM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (164, 'Marshall Isld.', 'MHL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (165, 'Palau', 'PLW');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (166, 'Pakistan', 'PAK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (167, 'Panama', 'PAN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (168, 'Papua New Guinea', 'PNG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (169, 'Paraguay', 'PRY');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (170, 'Peru', 'PER');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (171, 'Philippines', 'PHL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (172, 'Pitcairn Island', 'PCN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (173, 'Poland', 'POL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (174, 'Portugal', 'PRT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (175, 'Guinea Bissau', 'GNB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (176, 'Timor-Leste', 'TLS');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (177, 'Puerto Rico', 'PRI');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (178, 'Qatar', 'QAT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (179, 'Romania', 'ROM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (180, 'Russian Federation', 'RUS');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (181, 'Rwanda', 'RWA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (182, 'St. Helena', 'SHN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (183, 'St.Kitts and Nevis', 'KNA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (184, 'Anguilla', 'AIA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (185, 'St. Lucia', 'LCA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (186, 'St. Pierre and Miquelon', 'SPM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (187, 'St. Vincent and Grenadines', 'VCT');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (188, 'San Marino', 'SMR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (189, 'SÃ£o TomÃ© and Principe', 'STP');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (190, 'Saudi Arabia', 'SAU');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (191, 'Senegal', 'SEN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (192, 'Seychelles', 'SYC');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (193, 'Sierra Leone', 'SLE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (194, 'Singapore', 'SGP');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (195, 'Slovak Republic', 'SVK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (196, 'Viet Nam', 'VNM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (197, 'Slovenia', 'SVN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (198, 'Somalia', 'SOM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (199, 'South Africa', 'ZAF');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (200, 'Zimbabwe', 'ZWE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (201, 'Spain', 'ESP');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (202, 'West. Sahara', 'ESH');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (203, 'Sudan', 'SDN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (204, 'Suriname', 'SUR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (205, 'Svalbard and Jan Mayen Islands', 'SJM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (206, 'Swaziland', 'SWZ');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (207, 'Sweden', 'SWE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (208, 'Switzerland', 'CHE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (209, 'Syrian Arab Republic', 'SYR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (210, 'Tajikistan', 'TJK');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (211, 'Thailand', 'THA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (212, 'Togo', 'TGO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (213, 'Tokelau', 'TKL');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (214, 'Tonga', 'TON');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (215, 'Trinidad and Tobago', 'TTO');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (216, 'United Arab Emirates', 'ARE');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (217, 'Tunisia', 'TUN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (218, 'Turkey', 'TUR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (219, 'Turkmenistan', 'TKM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (220, 'Turks and Caicos Islands', 'TCA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (221, 'Tuvalu', 'TUV');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (222, 'Uganda', 'UGA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (223, 'Ukraine', 'UKR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (224, 'Macedonia, FYR', 'MKD');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (225, 'Egypt, Arab Rep.', 'EGY');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (226, 'United Kingdom', 'GBR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (227, 'Tanzania', 'TZA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (228, 'United States', 'USA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (229, 'Virgin Islands, U.S.', 'VIR');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (230, 'Burkina Faso', 'BFA');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (231, 'Uruguay', 'URY');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (232, 'Uzbekistan', 'UZB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (233, 'Venezuela, RB', 'VEN');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (234, 'Wallis and Futuna', 'WLF');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (235, 'Samoa', 'WSM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (236, 'Yemen', 'YEM');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (237, 'Serbia and Montenegro', 'SCG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (238, 'Zambia', 'ZMB');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (239, 'Westbank and Gaza', 'WBG');
INSERT INTO countries (`countryid`, `name`, `iso3`) VALUES (240, 'Jerusalem', 'JER');


#
# TABLE STRUCTURE FOR: dcformats
#

CREATE TABLE `dcformats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

INSERT INTO dcformats (`id`, `title`) VALUES (1, 'Compressed, Generic [application/x-compressed]');
INSERT INTO dcformats (`id`, `title`) VALUES (2, 'Compressed, ZIP [application/zip]');
INSERT INTO dcformats (`id`, `title`) VALUES (3, 'Data, CSPro [application/x-cspro]');
INSERT INTO dcformats (`id`, `title`) VALUES (4, 'Data, dBase [application/dbase]');
INSERT INTO dcformats (`id`, `title`) VALUES (5, 'Data, Microsoft Access [application/msaccess]');
INSERT INTO dcformats (`id`, `title`) VALUES (6, 'Data, SAS [application/x-sas]');
INSERT INTO dcformats (`id`, `title`) VALUES (7, 'Data, SPSS [application/x-spss]');
INSERT INTO dcformats (`id`, `title`) VALUES (8, 'Data, Stata [application/x-stata]');
INSERT INTO dcformats (`id`, `title`) VALUES (9, 'Document, Generic [text]');
INSERT INTO dcformats (`id`, `title`) VALUES (10, 'Document, HTML [text/html]');
INSERT INTO dcformats (`id`, `title`) VALUES (11, 'Document, Microsoft Excel [application/msexcel]');
INSERT INTO dcformats (`id`, `title`) VALUES (12, 'Document, Microsoft PowerPoint [application/mspowerpoint');
INSERT INTO dcformats (`id`, `title`) VALUES (13, 'Document, Microsoft Word [application/msword]');
INSERT INTO dcformats (`id`, `title`) VALUES (14, 'Document, PDF [application/pdf]');
INSERT INTO dcformats (`id`, `title`) VALUES (15, 'Document, Postscript [application/postscript]');
INSERT INTO dcformats (`id`, `title`) VALUES (16, 'Document, Plain [text/plain]');
INSERT INTO dcformats (`id`, `title`) VALUES (17, 'Document, WordPerfect [text/wordperfect]');
INSERT INTO dcformats (`id`, `title`) VALUES (18, 'Image, GIF [image/gif]');
INSERT INTO dcformats (`id`, `title`) VALUES (19, 'Image, JPEG [image/jpeg]');
INSERT INTO dcformats (`id`, `title`) VALUES (20, 'Image, PNG [image/png]');
INSERT INTO dcformats (`id`, `title`) VALUES (21, 'Image, TIFF [image/tiff]');


#
# TABLE STRUCTURE FOR: dctypes
#

CREATE TABLE `dctypes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

INSERT INTO dctypes (`id`, `title`) VALUES (1, 'Document, Administrative [doc/adm]');
INSERT INTO dctypes (`id`, `title`) VALUES (2, 'Document, Analytical [doc/anl]');
INSERT INTO dctypes (`id`, `title`) VALUES (3, 'Document, Other [doc/oth]');
INSERT INTO dctypes (`id`, `title`) VALUES (4, 'Document, Questionnaire [doc/qst]');
INSERT INTO dctypes (`id`, `title`) VALUES (5, 'Document, Reference [doc/ref]');
INSERT INTO dctypes (`id`, `title`) VALUES (6, 'Document, Report [doc/rep]');
INSERT INTO dctypes (`id`, `title`) VALUES (7, 'Document, Technical [doc/tec]');
INSERT INTO dctypes (`id`, `title`) VALUES (8, 'Audio [aud]');
INSERT INTO dctypes (`id`, `title`) VALUES (9, 'Database [dat]');
INSERT INTO dctypes (`id`, `title`) VALUES (10, 'Map [map]');
INSERT INTO dctypes (`id`, `title`) VALUES (11, 'Microdata File [dat/micro]');
INSERT INTO dctypes (`id`, `title`) VALUES (12, 'Photo [pic]');
INSERT INTO dctypes (`id`, `title`) VALUES (13, 'Program [prg]');
INSERT INTO dctypes (`id`, `title`) VALUES (14, 'Table [tbl]');
INSERT INTO dctypes (`id`, `title`) VALUES (15, 'Video [vid]');
INSERT INTO dctypes (`id`, `title`) VALUES (16, 'Web Site [web]');


#
# TABLE STRUCTURE FOR: forms
#

CREATE TABLE `forms` (
  `formid` int(11) NOT NULL DEFAULT '0',
  `fname` varchar(255) DEFAULT '',
  `model` varchar(255) DEFAULT '',
  `path` varchar(255) DEFAULT '',
  `iscustom` char(2) DEFAULT '0',
   PRIMARY KEY (`formid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

INSERT INTO forms (`formid`, `fname`, `model`, `path`, `iscustom`) VALUES (2, 'Public use files', 'public', 'orderform.php', '1');
INSERT INTO forms (`formid`, `fname`, `model`, `path`, `iscustom`) VALUES (1, 'Direct access', 'direct', 'direct.php', '1');
INSERT INTO forms (`formid`, `fname`, `model`, `path`, `iscustom`) VALUES (3, 'Licensed data files', 'licensed', 'licensed.php', '1');
INSERT INTO forms (`formid`, `fname`, `model`, `path`, `iscustom`) VALUES (4, 'Data accessible only in data enclave', 'data_enclave', 'Application for Access to a Data Enclave.pdf', '0');


#
# TABLE STRUCTURE FOR: menus
#

CREATE TABLE `menus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text,
  `published` tinyint(1) DEFAULT NULL,
  `target` varchar(45) DEFAULT NULL,
  `changed` int(10) unsigned DEFAULT NULL,
  `linktype` tinyint(1) DEFAULT NULL,
  `weight` int(10) DEFAULT NULL,
  `pid` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_url` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (1, 'catalog', 'Data Catalog', '', 1, '0', 1281460209, 1, 4, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (2, 'citations', 'Citations', NULL, 1, '0', 1281460217, 1, 5, 0);


#
# TABLE STRUCTURE FOR: terms
#

CREATE TABLE `terms` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=utf8;

INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (1, 1, 0, 'ECONOMICS [1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (2, 1, 1, 'consumption/consumer behaviour [1.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (3, 1, 1, 'economic conditions and indicators [1.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (4, 1, 1, 'economic policy [1.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (5, 1, 1, 'economic systems and development [1.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (6, 1, 1, 'income, property and investment/saving [1.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (7, 1, 1, 'rural economics [1.6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (9, 1, 0, 'TRADE, INDUSTRY AND MARKETS [2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (10, 1, 9, 'agricultural, forestry and rural industry [2.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (11, 1, 9, 'business/industrial management and organisation [2.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (13, 1, 0, 'LABOUR AND EMPLOYMENT [3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (14, 1, 13, 'employment [3.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (15, 1, 13, 'in-job training [3.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (16, 1, 13, 'labour relations/conflict [3.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (17, 1, 13, 'retirement [3.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (18, 1, 13, 'unemployment [3.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (19, 1, 13, 'working conditions [3.6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (21, 1, 0, 'POLITICS [4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (22, 1, 21, 'conflict, security and peace [4.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (23, 1, 21, 'domestic political issues [4.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (24, 1, 21, 'elections [4.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (25, 1, 21, 'government, political systems and organisations [4.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (26, 1, 21, 'international politics and organisations [4.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (27, 1, 21, 'mass political behaviour, attitudes/opinion [4.6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (28, 1, 21, 'political ideology [4.7]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (30, 1, 0, 'LAW, CRIME AND LEGAL SYSTEMS [5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (31, 1, 30, 'crime [5.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (32, 1, 30, 'law enforcement [5.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (33, 1, 30, 'legal systems [5.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (34, 1, 30, 'legislation [5.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (35, 1, 30, 'rehabilitation/reintegration into society [5.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (37, 1, 0, 'EDUCATION [6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (38, 1, 37, 'basic skills education [6.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (39, 1, 37, 'compulsory and pre-school education [6.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (40, 1, 37, 'educational policy [6.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (41, 1, 37, 'life-long/continuing education [6.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (42, 1, 37, 'post-compulsory education [6.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (43, 1, 37, 'teaching profession [6.6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (44, 1, 37, 'vocational education [6.7]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (46, 1, 0, 'INFORMATION AND COMMUNICATION [7]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (47, 1, 46, 'advertising [7.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (48, 1, 46, 'information society [7.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (49, 1, 46, 'language and linguistics [7.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (50, 1, 46, 'mass media [7.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (52, 1, 0, 'HEALTH [8]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (53, 1, 52, 'accidents and injuries [8.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (54, 1, 52, 'childbearing, family planning and abortion [8.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (55, 1, 52, 'drug abuse, alcohol and smoking [8.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (56, 1, 52, 'general health [8.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (57, 1, 52, 'health care and medical treatment [8.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (58, 1, 52, 'health policy [8.6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (59, 1, 52, 'nutrition [8.7]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (60, 1, 52, 'physical fitness and exercise [8.8]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (61, 1, 52, 'specific diseases and medical conditions [8.9]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (63, 1, 0, 'NATURAL ENVIRONMENT [9]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (64, 1, 63, 'environmental degradation/pollution and protection [9.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (65, 1, 63, 'natural landscapes [9.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (66, 1, 63, 'natural resources and energy [9.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (67, 1, 63, 'plant and animal distribution [9.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (69, 1, 0, 'HOUSING AND LAND USE PLANNING [10]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (70, 1, 69, 'housing [10.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (71, 1, 69, 'land use and planning [10.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (73, 1, 0, 'TRANSPORT, TRAVEL AND MOBILITY [11]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (74, 1, 0, 'SOCIAL STRATIFICATION AND GROUPINGS [12]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (75, 1, 74, 'children [12.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (76, 1, 74, 'elderly [12.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (77, 1, 74, 'elites and leadership [12.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (78, 1, 74, 'equality and inequality [12.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (79, 1, 74, 'family life and marriage [12.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (80, 1, 74, 'gender and gender roles [12.6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (81, 1, 74, 'minorities [12.7]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (82, 1, 74, 'social and occupational mobility [12.8]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (83, 1, 74, 'social exclusion [12.9]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (84, 1, 74, 'youth [12.10]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (86, 1, 0, 'SOCIETY AND CULTURE [13]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (87, 1, 86, 'community, urban and rural life [13.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (88, 1, 86, 'cultural activities and participation [13.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (89, 1, 86, 'cultural and national identity [13.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (90, 1, 86, 'leisure, tourism and sport [13.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (91, 1, 86, 'religion and values [13.5]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (92, 1, 86, 'social behaviour and attitudes [13.6]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (93, 1, 86, 'social change [13.7]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (94, 1, 86, 'social conditions and indicators [13.8]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (95, 1, 86, 'time use [13.9]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (97, 1, 0, 'DEMOGRAPHY AND POPULATION [14]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (98, 1, 97, 'censuses [14.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (99, 1, 97, 'fertility [14.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (100, 1, 97, 'migration [14.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (101, 1, 97, 'morbidity and mortality [14.4]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (103, 1, 0, 'SOCIAL WELFARE POLICY AND SYSTEMS [15]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (104, 1, 103, 'social welfare policy [15.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (105, 1, 103, 'social welfare systems/structures [15.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (106, 1, 103, 'specific social services: use and provision [15.3]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (108, 1, 0, 'SCIENCE AND TECHNOLOGY [16]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (109, 1, 108, 'biotechnology [16.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (110, 1, 108, 'information technology [16.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (112, 1, 0, 'PSYCHOLOGY [17]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (113, 1, 0, 'HISTORY [18]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (114, 1, 0, 'REFERENCE AND INSTRUCTIONAL RESOURCES [19]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (115, 1, 114, 'computer and simulation programs [19.1]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (116, 1, 114, 'reference sources [19.2]');
INSERT INTO terms (`tid`, `vid`, `pid`, `title`) VALUES (117, 1, 114, 'teaching packages and test datasets [19.3]');


#
# TABLE STRUCTURE FOR: user_groups
#

CREATE TABLE `user_groups` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `description` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

INSERT INTO user_groups (`id`, `name`, `description`) VALUES (1, 'admin', 'Administrator');
INSERT INTO user_groups (`id`, `name`, `description`) VALUES (2, 'members', 'General User');


#
# TABLE STRUCTURE FOR: vocabularies
#

CREATE TABLE `vocabularies` (
  `vid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`vid`),
  UNIQUE KEY `idx_voc_title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

INSERT INTO vocabularies (`vid`, `title`) VALUES (1, 'CESSDA Topics Classifications');


