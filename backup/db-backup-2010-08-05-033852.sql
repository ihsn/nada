
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
) ENGINE=MyISAM AUTO_INCREMENT=22755 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: projects
CREATE TABLE `projects` (
  `projectid` varchar(255) NOT NULL DEFAULT '',
  `projectname` varchar(255) DEFAULT NULL,
  `productline` varchar(255) DEFAULT NULL,
  `region` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`projectid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: surveys
CREATE TABLE `surveys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `repositoryid` varchar(128) NOT NULL,
  `surveyid` varchar(45) DEFAULT NULL,
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
) ENGINE=MyISAM AUTO_INCREMENT=138 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



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
  UNIQUE KEY `idxSurvey` (`varID`,`surveyid_FK`) USING BTREE,
  KEY `idxsurveyidfk` (`surveyid_FK`),
  FULLTEXT KEY `idx_qstn` (`qstn`),
  FULLTEXT KEY `idx_labl` (`labl`),
  FULLTEXT KEY `idxCatgry` (`catgry`),
  FULLTEXT KEY `idx_nm_lbl_qstn` (`name`,`labl`,`qstn`),
  FULLTEXT KEY `idx_nm_lbl_cat_qstn` (`name`,`labl`,`catgry`,`qstn`),
  FULLTEXT KEY `idx_nm` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=204059 DEFAULT CHARSET=utf8;



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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;



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
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



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
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: lic_files
CREATE TABLE `lic_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `surveyid` int(10) unsigned NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `changed` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: lic_files_log
CREATE TABLE `lic_files_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requestid` int(10) unsigned NOT NULL,
  `fileid` int(10) unsigned NOT NULL,
  `ip` varchar(20) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COMMENT='licensed files download log';



# TABLE STRUCTURE FOR: lic_requests
CREATE TABLE `lic_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `surveyid` varchar(100) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: pages
CREATE TABLE `pages` (
  `pid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `body` text,
  `url` varchar(45) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `changed` datetime DEFAULT NULL,
  `weight` smallint(5) unsigned DEFAULT NULL,
  `status` smallint(5) unsigned DEFAULT '1',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;



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
  `repositoryid` varchar(255) CHARACTER SET utf8 NOT NULL,
  `title` varchar(100) CHARACTER SET utf8 NOT NULL,
  `url` varchar(255) CHARACTER SET utf8 NOT NULL,
  `organization` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `email` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `country` varchar(45) CHARACTER SET utf8 DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# TABLE STRUCTURE FOR: resources
CREATE TABLE `resources` (
  `resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` int(10) unsigned NOT NULL,
  `dctype` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `subtitle` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `author` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `dcdate` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `country` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `language` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `id_number` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `contributor` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `publisher` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `rights` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `description` text CHARACTER SET utf8,
  `abstract` text CHARACTER SET utf8,
  `toc` text CHARACTER SET utf8,
  `subjects` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `filename` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `dcformat` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `changed` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: survey_citations
CREATE TABLE `survey_citations` (
  `sid` int(10) unsigned DEFAULT NULL,
  `citationid` int(10) unsigned DEFAULT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Idx_s_c` (`sid`,`citationid`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: survey_projects
CREATE TABLE `survey_projects` (
  `sid` int(10) unsigned NOT NULL,
  `projectid` varchar(255) NOT NULL,
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `idx_uniq` (`projectid`,`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: survey_topics
CREATE TABLE `survey_topics` (
  `sid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `Idx_uniq` (`tid`,`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=6871 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# TABLE STRUCTURE FOR: survey_years
CREATE TABLE `survey_years` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned DEFAULT NULL,
  `data_coll_year` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=187 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: terms
CREATE TABLE `terms` (
  `tid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8;



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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;



# TABLE STRUCTURE FOR: vocabularies
CREATE TABLE `vocabularies` (
  `vid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`vid`) USING BTREE,
  UNIQUE KEY `idx_voc_title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;


#
# TABLE STRUCTURE FOR: ci_sessions
#

CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) DEFAULT '0',
  `user_agent` varchar(50) DEFAULT NULL,
  `last_activity` int(10) unsigned DEFAULT '0',
  `user_data` text,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

INSERT INTO ci_sessions (`session_id`, `ip_address`, `user_agent`, `last_activity`, `user_data`) VALUES ('d685945c78955727c2d056da67999278', '127.0.0.1', 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) Ap', 1281037024, 'a:7:{s:11:\"destination\";s:19:\"/backup/create/true\";s:5:\"email\";s:21:\"masghar@worldbank.org\";s:8:\"username\";s:14:\"mehmood asghar\";s:2:\"id\";s:2:\"16\";s:7:\"user_id\";s:2:\"16\";s:8:\"group_id\";s:1:\"1\";s:5:\"group\";s:5:\"admin\";}');


#
# TABLE STRUCTURE FOR: citation_authors
#

CREATE TABLE `citation_authors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `initial` varchar(255) DEFAULT NULL,
  `author_type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8;

INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (1, 1, 'Kofi Darkwa', 'Benefo', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (2, 1, 'T. Paul', 'Schultz', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (3, 13, 'M', 'Adato', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (4, 13, 'L', 'Haddad', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (5, 14, 'Michelle', 'Adato', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (6, 14, 'Lawrence', 'Haddad', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (7, 14, 'Dudley', 'Horner', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (8, 14, 'Neetha', 'Ravjee', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (9, 14, 'Ridwaan', 'Haywood', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (10, 16, 'Harold', 'Alderman', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (11, 17, 'Harold', 'Alderman', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (12, 17, 'Carlo', 'del Ninno', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (13, 18, 'Harold', 'Alderman', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (14, 18, 'Jere', 'Behrman', 'R', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (15, 18, 'Hans-Peter', 'Kohler', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (16, 18, 'John', 'Maluccio', 'A', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (17, 18, 'Susan', 'Cotts Watkins', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (18, 19, 'Paul', 'Allanson', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (19, 19, 'Jonathan', 'Atkins', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (20, 20, 'Kermit', 'Anderson', 'G', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (21, 21, 'Angus', 'Deaton', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (22, 22, 'Anne', 'Case', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (23, 22, 'Angus', 'Deaton', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (24, 23, 'Martin', 'Wittenberg', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (25, 24, 'Sandrine', 'Rospabé', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (26, 25, 'G', 'Kingdon', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (27, 25, 'J', 'Knight', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (28, 27, 'Anne', 'Case', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (29, 28, 'Paul ', 'Glewwe', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (30, 29, 'Adriana', 'Castaldo', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (31, 29, 'Julie', 'Litchfield ', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (32, 29, 'Barry ', 'Reilly', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (33, 30, 'Calogero', 'Carletto', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (34, 30, 'Benjamin', 'Davis', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (35, 30, 'Marco', 'Stampini', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (36, 30, 'Stefano', 'Trento', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (37, 30, 'Alberto', 'Zezza', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (38, 31, 'Angus', 'Deaton', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (39, 33, 'Arnstein', 'Aassve', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (40, 33, 'Arjan', 'Gjonca', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (41, 33, 'Letizia', 'Mencarini', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (42, 34, 'World Bank', '', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (43, 35, 'David', 'Dollar', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (44, 35, 'Paul ', 'Glewwe', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (45, 35, 'Jennie', 'Litvack', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (46, 36, 'Harold', 'Alderman', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (47, 37, 'World Bank', '', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (52, 39, 'Adrian Colin ', 'Cameron', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (53, 39, 'P. K. ', 'Trivedi', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (54, 40, 'Suresh ', 'Babu', 'C.', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (55, 40, 'Prabuddha', 'Sanyal', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (62, 41, 'Erwin', 'Tiongson', 'R', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (63, 41, 'Ruslan', 'Yemtsov', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (68, 38, 'Owen ', 'O\'Donnell', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (69, 38, 'Eddy ', 'Van Doorsslaer', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (70, 38, 'Adam', 'Wagstaff', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (71, 38, 'Magnus', 'Lindelöw', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (72, 42, 'Mehmood', 'Asghar', 'A', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (77, 43, 'Mike', 'Powe', '', 'author');
INSERT INTO citation_authors (`id`, `cid`, `fname`, `lname`, `initial`, `author_type`) VALUES (78, 44, 'Maritza Ivonne', 'Byrne', '', 'author');


#
# TABLE STRUCTURE FOR: citations_backup
#

CREATE TABLE `citations_backup` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
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
  `abstract` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (1, 'citation with lots of related surveys', 'it is a magazine title', 'Anthology modified by Khurram', 'a:2:{i:0;a:3:{s:5:\"fname\";s:7:\"mehmood\";s:5:\"lname\";s:6:\"asghar\";s:7:\"initial\";s:1:\"c\";}i:1;a:3:{s:5:\"fname\";s:8:\"mashudah\";s:5:\"lname\";s:5:\"mirza\";s:7:\"initial\";s:1:\"m\";}}', 'b:0;', 'b:0;', 1278690875, 1278209942, 1, 'METRO', NULL, NULL, '34433434', 'washington', 'DC', 'publisher', 0, 'http://www.google.com', '10', '198', '2010-05-02', 'IHSN', 'website', '0', NULL, 0, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (2, 'The Twilight Saga Eclipse: The Official Illustrated Movie Companion [Paperback]', NULL, NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:5:\"Mark \";s:5:\"lname\";s:6:\"Cotta \";s:7:\"initial\";s:3:\"Vaz\";}}', 'b:0;', 'b:0;', 1278247194, 1278247194, 1, '2', NULL, NULL, 'delux', 'Washington', 'DC', 'Wiley ', 0, '', NULL, NULL, NULL, NULL, 'book', NULL, NULL, NULL, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (3, 'The 1961 Decision to Enlarge the Committee on Rules: An Analysis of the Vote in New Perspectives on the House of Representatives', NULL, NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:6:\"Robert\";s:5:\"lname\";s:7:\"Peabody\";s:7:\"initial\";s:2:\"L.\";}}', 'b:0;', 'b:0;', 1278247751, 1278247751, 1, '', NULL, NULL, '', 'Chicago', 'IL', 'Rand McNally ', 0, '', NULL, NULL, NULL, NULL, 'book', NULL, NULL, NULL, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (4, '1961 Survey of Consumer Finances', NULL, NULL, 'a:3:{i:0;a:3:{s:5:\"fname\";s:7:\"George \";s:5:\"lname\";s:6:\"Katona\";s:7:\"initial\";s:0:\"\";}i:1;a:3:{s:5:\"fname\";s:11:\"Charles A. \";s:5:\"lname\";s:8:\"Lininger\";s:7:\"initial\";s:0:\"\";}i:2;a:3:{s:5:\"fname\";s:5:\"Eva  \";s:5:\"lname\";s:7:\"Mueller\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1278248702, 1278248702, 1, '', NULL, NULL, '', 'Ann Arbor', 'MI', 'Survey Research Center, University of Michigan ', 0, '', NULL, NULL, NULL, NULL, 'book', NULL, NULL, NULL, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (5, '2002 National Survey on Drug Use and Health: Field Interviewer Manual', NULL, NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:26:\"Office of Applied Studies \";s:5:\"lname\";s:0:\"\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1278603835, 1278248830, 1, '', NULL, NULL, '', 'Rockville', 'MD', 'United States Department of Health and Human Services, Substance Abuse and Mental Health Services Administration ', 0, '', NULL, NULL, NULL, NULL, 'book', '9', '', 0, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (6, 'Violence in America: Historical and Comparative Perspectives (Official Report)', 'A 150-year study of political violence in the United States', NULL, 'a:3:{i:0;a:3:{s:5:\"fname\";s:9:\"Sheldon  \";s:5:\"lname\";s:4:\"Levy\";s:7:\"initial\";s:0:\"\";}i:1;a:3:{s:5:\"fname\";s:12:\"Hugh Davis  \";s:5:\"lname\";s:6:\"Graham\";s:7:\"initial\";s:0:\"\";}i:2;a:3:{s:5:\"fname\";s:12:\"Ted Robert  \";s:5:\"lname\";s:4:\"Gurr\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1278603819, 1278249136, 1, '', NULL, NULL, '', 'Washington', 'DC', 'National Commission on the Causes and Prevention of Violence ', 0, '', '81', '91', NULL, NULL, 'book-section', '0', '', 0, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (7, 'Midterm: The Elections of 1994 in Context', 'The 1994 Electoral Aftershock: Dealignment or Realignment in the South', NULL, 'a:2:{i:0;a:3:{s:5:\"fname\";s:4:\"Paul\";s:5:\"lname\";s:6:\"Frymer\";s:7:\"initial\";s:0:\"\";}i:1;a:3:{s:5:\"fname\";s:9:\"Philip A.\";s:5:\"lname\";s:7:\"Klinker\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1278605082, 1278249405, 1, '', NULL, NULL, '', 'Boulder', 'CO', 'Westview Press ', 0, '', '', '', NULL, NULL, 'book-section', '1', 'March', 0, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (8, 'The 10-year incidence of overweight and major weight gain in US adults', 'Archives of Internal Medicine ', NULL, 'a:4:{i:0;a:3:{s:5:\"fname\";s:6:\"D.F.  \";s:5:\"lname\";s:10:\"Williamson\";s:7:\"initial\";s:0:\"\";}i:1;a:3:{s:5:\"fname\";s:6:\"P.L.  \";s:5:\"lname\";s:9:\"Remington\";s:7:\"initial\";s:0:\"\";}i:2;a:3:{s:5:\"fname\";s:6:\"H.S.  \";s:5:\"lname\";s:4:\"Kahn\";s:7:\"initial\";s:0:\"\";}i:3;a:3:{s:5:\"fname\";s:11:\"Robert F.  \";s:5:\"lname\";s:4:\"Anda\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1278603563, 1278249616, 1, '150', '3', '0003-9926 ', NULL, NULL, NULL, NULL, 0, '', '665', '672', NULL, NULL, 'journal', '03', 'March', 1901, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (9, '12 million salaried workers are missing', 'Industrial and Labor Relations Review ', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:11:\"Daniel S.  \";s:5:\"lname\";s:9:\"Hamermesh\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1278603546, 1278249832, 1, '55', '4', '0019-7939 ', NULL, NULL, NULL, NULL, 0, '', '649', '666', NULL, NULL, 'journal', '', '', 1998, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (10, 'The 1980s: Political Earthquake or a Blip?; Scholars Debate Magnitude and Significance of Voters\' Trend Toward GOP', 'The Washington Post ', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:11:\"Thomas B.  \";s:5:\"lname\";s:6:\"Edsall\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1278603509, 1278249971, 1, '', NULL, NULL, NULL, NULL, NULL, NULL, 0, '', '', NULL, NULL, NULL, 'newspaper', '', 'Feb', 2010, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (11, '6 learning games to play with your baby: Excerpt from Learningames for the first three years', 'Introduction to citations', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:7:\" Joseph\";s:5:\"lname\";s:8:\"Sparling\";s:7:\"initial\";s:1:\"M\";}}', 'b:0;', 'b:0;', 1278603198, 1278250347, 1, '6', '1', '1539-9664 ', '78', 'city', 'country', 'publisher', 0, 'http://www.google.com', '1', '87', NULL, NULL, 'book-section', '05', 'JUNE', 2938, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (12, 'test', NULL, NULL, 'b:0;', 'b:0;', 'b:0;', 1278691044, 1278690900, 1, '', NULL, NULL, '', '', '', '', 0, '', NULL, NULL, NULL, NULL, 'book', '0', '', 0, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (18, 'test3 with quotes', NULL, '', 'Asghar C. Mehmood Welch J Matthew ', 'ts tst tets ', 'ddd dddd teddd ', 1279833506, 1278693613, 1, '8', NULL, NULL, '1', 'city', 'country', 'publisher', 0, 'http://www.google.com', '', '', NULL, NULL, 'anthology-translator', '9', 'August', 2000, 'testadfdsfkajdsfljsadf ajsdkfjl');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (19, 'Determinants of fertility and child mortality in Cote d\'Ivoire and Ghana', 'Living standards measurement study (LSMS) working paper', NULL, 'Benefo  Kofi Darkwa Schultz  T. Paul ', '0', '0', 1279895452, 1279832887, 1, '1', 'LSM103', '0-8213-278', NULL, NULL, NULL, NULL, 0, 'http://www-wds.worldbank.org/external/default/main?pagePK=64193027&piPK=64187937&theSitePK=523679&menuPK=64187510&searchMenuPK=64187283&theSitePK=523679&entityID=000009265_3970311121833&searchMenuPK=64187283&theSitePK=523679', '', '', NULL, NULL, 'journal', '31', '05', 1994, '');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (20, 'Targeting poverty through community-based public works programs: a cross-disciplinary assessment of recent experience in South Africa.', 'International Food Policy Research Institute. (FCND Discussion Paper)', NULL, 'Adato  M Haddad  L ', '0', '0', 1279895505, 1278802422, 1, '', '121', '', 'Discussion Paper', 'Washington', 'USA', 'International Food Policy Research Institute.', 0, 'http://www.ifpri.org/sites/default/files/pubs/divs/fcnd/dp/papers/fcndp121.pdf', '', '', NULL, NULL, 'journal', '0', '', 2001, '');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (21, 'From works to public works: the performance of labour-intensive public works in Western Cape Province, South Africa.', NULL, NULL, 'Adato  Michelle Haddad  Lawrence Horner  Dudley Ravjee  Neetha Haywood  Ridwaan ', '0', '0', 1279895523, 1278803158, 1, '', NULL, '', '', 'Cape Town', 'South Africa', 'Southern Africa Labour and Development Research Unit. ', 0, '', NULL, NULL, NULL, NULL, 'book', '0', '', 1999, '');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (22, 'Safety nets and income transfers in South Africa.', 'World Bank (South Africa:Poverty and Inequality, Informal Discussion Paper Series)', NULL, 'Alderman  Harold ', '0', '0', 1279895538, 1278804495, 1, '19335', '', '', 'Poverty and Inequality: Informal Discussion P', 'Washington', 'DC, USA', 'World Bank', 0, 'http://www-wds.worldbank.org/servlet/WDSContentServer/WDSP/IB/1999/09/14/000094946_99081805373164/Rendered/PDF/multi_page.pdf', '', '', NULL, NULL, 'journal', '0', 'February', 1999, '');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (23, 'Poverty issues for zero rating value-added tax (VAT) in South Africa', 'Journal of African Economies', NULL, 'Alderman  Harold del Ninno  Carlo ', '0', '0', 1279895551, 1278804696, 1, '8', '2', '', 'South Africa: Poverty and Inequality: Informa', 'Washington', 'DC, USA', 'World Bank', 0, 'http://jae.oxfordjournals.org/cgi/reprint/8/2/182.pdf', '182', '208', NULL, NULL, 'journal', '0', '', 1999, '');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (24, 'Attrition in longitudinal household survey data: some tests for three developing country samples.', 'World Bank (Policy Research Working Paper)', NULL, 'Alderman  Harold Behrman R Jere Kohler  Hans-Peter Maluccio A John Cotts Watkins  Susan ', '0', '0', 1279895567, 1278804799, 1, '', '2447', '', 'Policy Research Working Paper', 'Washington', 'DC/USA', 'World Bank', 0, 'http://www-wds.worldbank.org/servlet/WDSContentServer/WDSP/IB/2000/11/04/000094946_00101905462867/Rendered/PDF/multi_page.pdf', '', '', NULL, NULL, 'journal', '0', '', 2000, '');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (25, 'Labour market reform and the evolution of the racial wage hierarchy in post-apartheid South Africa', 'Development Policy Research Unit (DPRU Working Paper)', NULL, 'Allanson K Paul Atkins C Jonathan Asghar C Mehmood ', '0', '0', 1279904333, 1278805016, 1, '1', '59', '0-7992-210', 'DPRU Working Paper ', 'Cape Town', 'South Africa', 'Development Policy Research Unit, University of Cape Town', 0, 'http://www.commerce.uct.ac.za/Research_Units/dpru/WorkingPapers/PDF_Files/wp59.pdf', '56', '90', NULL, NULL, 'journal', '0', '', 2001, '');
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (26, 'Family structure, parental investment and educational outcomes among black South Africans', 'University of Michigan, Population Studies Centre, Research Report', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:6:\"Kermit\";s:5:\"lname\";s:8:\"Anderson\";s:7:\"initial\";s:1:\"G\";}}', 'b:0;', 'b:0;', 1279827748, 1278805444, 1, '3', '538', '', '', 'Ann Arbor', 'USA/Michigan', ' Population Studies Centre.', 0, 'http://www.psc.isr.umich.edu/pubs/pdf/rr03-538.pdf', '', '', NULL, NULL, 'journal', '0', '', 2003, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (27, 'The Analysis of Household Surveys: a Microeconomic Approach to Development Policy', NULL, NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:5:\"Angus\";s:5:\"lname\";s:6:\"Deaton\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1279827494, 1278805801, 1, '', NULL, NULL, '', 'Baltimore', 'USA', 'Johns Hopkins University Press. ', 0, '', NULL, NULL, NULL, NULL, 'book', '0', '', 1997, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (28, 'School inputs and educational outcomes in South Africa', 'The Quarterly Journal of Economics', NULL, 'a:2:{i:0;a:3:{s:5:\"fname\";s:4:\"Anne\";s:5:\"lname\";s:4:\"Case\";s:7:\"initial\";s:0:\"\";}i:1;a:3:{s:5:\"fname\";s:5:\"Angus\";s:5:\"lname\";s:6:\"Deaton\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1279827575, 1278805929, 1, '14', '3', '', 'The Quarterly Journal of Economics', '', '', 'The Quarterly Journal of Economics', 0, '', '', '', NULL, NULL, 'journal', '0', '', 1999, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (29, 'Dissecting post-apartheid labour market developments: decomposing a discrete choice model while dealing with unobservables.', 'Economic Research Southern Africa. (ERSA Working Paper)', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:6:\"Martin\";s:5:\"lname\";s:10:\"Wittenberg\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1279827468, 1278806102, 1, '', '94', '', 'ERSA Working Paper', 'Cape Town', 'South Africa', 'Economic Research Southern Africa', 0, 'http://www.econrsa.org/wp46.html', '', '', NULL, NULL, 'journal', '0', '', 2007, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (30, 'How did the labour market racial discrimination evolve after the end of Apartheid? An analysis of the evolution of employment, occupational and wage discrimination in South Africa between 1993 and 1999', 'South African Journal of Economics', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:8:\"Sandrine\";s:5:\"lname\";s:8:\"Rospabé\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1279826929, 1278806629, 1, '70', '1', '', '70', '', 'South Africa', 'South African Journal of Economics', 0, '', '185', '217', NULL, NULL, 'journal', '0', '', 2002, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (31, 'Unemployment in South Africa: the nature of the beast.', 'Centre for the Study of African Economies. (CSAE Working Paper )', NULL, 'a:2:{i:0;a:3:{s:5:\"fname\";s:1:\"G\";s:5:\"lname\";s:7:\"Kingdon\";s:7:\"initial\";s:0:\"\";}i:1;a:3:{s:5:\"fname\";s:1:\"J\";s:5:\"lname\";s:6:\"Knight\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1279826703, 1278806843, 1, '2001', '15', '', 'CSAE Working Paper 2001', 'Oxford', 'UK', 'Centre for the Study of African Economies', 0, 'http://www.csae.ox.ac.uk/workingpapers/pdfs/2001-15text.pdf', '', '', NULL, NULL, 'journal', '0', '', 2001, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (32, 'Labor and the growth crisis in Sub-Saharan Africa', 'World Bank', NULL, 'b:0;', 'b:0;', 'b:0;', 1279826591, 1278807070, 1, '', '', '', 'Regional Perspectives on World Development Re', 'Washington', 'USA/DC', 'World Bank', 0, 'http://www.datafirst.uct.ac.za/wiki/images/4/48/1995_Growth_SS_Africa.pdf', '', '', NULL, NULL, 'journal', '0', '', 1995, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (33, 'Does school quality matter? Returns to education and the characteristics of schools in South Africa.', 'National Bureau of Economic Research (NBER Working Paper)', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:4:\"Anne\";s:5:\"lname\";s:4:\"Case\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1279826542, 1278807242, 1, '', '7399', '', 'NBER Working Paper', 'Cambridge, Mass', 'USA', 'National Bureau of Economic Research', 0, 'http://www.nber.org/papers/w7399', '', '', NULL, NULL, 'journal', '0', '', 1999, NULL);
INSERT INTO citations_backup (`id`, `title`, `subtitle`, `alt_title`, `authors`, `editors`, `translators`, `changed`, `created`, `published`, `volume`, `issue`, `idnumber`, `edition`, `place_publication`, `place_state`, `publisher`, `publication_medium`, `url`, `page_from`, `page_to`, `data_accessed`, `organization`, `ctype`, `pub_day`, `pub_month`, `pub_year`, `abstract`) VALUES (34, 'Schooling, skills, and the returns to government investment in education : an exploration using data from Ghana', 'Living standards measurement study (LSMS) working paper', NULL, 'a:1:{i:0;a:3:{s:5:\"fname\";s:5:\"Paul \";s:5:\"lname\";s:6:\"Glewwe\";s:7:\"initial\";s:0:\"\";}}', 'b:0;', 'b:0;', 1279833082, 1279833082, 1, '1', 'LSM76', '0-8213-176', NULL, NULL, NULL, NULL, 0, 'http://www-wds.worldbank.org/external/default/main?pagePK=64193027&piPK=64187937&theSitePK=523679&menuPK=64187510&searchMenuPK=64187283&theSitePK=523679&entityID=000178830_98101902173486&searchMenuPK=64187283&theSitePK=523679', '', '', NULL, NULL, 'journal', '31', '3', 1991, NULL);


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

INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('cache_folder', 'application/cache', 'Site cache folder', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('catalog_records_per_page', '10', 'Catalog search page - records per page', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('catalog_root', 'C:/WB/workspace/nada2.1_data', 'Survey catalog folder', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('ddi_import_folder', 'C:\\WB\\lsms\\surveys-JUL15-2010', 'Survey catalog import folder', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('default_home_page', 'catalog', 'Default home page', 'Default home page', NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('html_folder', '/pages', NULL, NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('lang', 'en-us', 'Site Language', 'Site Language code', NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('login_timeout', '40', 'Login timeout (minutes)', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('min_password_length', '5', 'Minimum password length', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('regional_search', 'yes', 'Enable regional search', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('repository_identifier', 'default', 'Repository Identifier', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('site_password_protect', 'no', 'Password protect website', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_auth', 'no', 'Use SMTP Authentication', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_debug', 'yes', 'Enable SMTP Debugging', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_enabled', 'no', 'Enable SMTP for sending emails', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_host', 'mail.ihsn.org', 'SMTP Host name', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_password', 'dummypass', 'SMTP password', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_port', '475', 'SMTP port', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_secure', 'no', 'Use Secure SMTP?', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('smtp_username', 'mehmood', 'SMTP username', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('theme', 'default', 'Site theme name', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('topics_vocab', '1', 'Vocabulary ID for Topics', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('topic_search', 'yes', 'Topic search', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('use_html_editor', 'yes', 'Use HTML editor for entering HTML for static pages', NULL, NULL);
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_title', 'National Data Archive 3.0', 'Website title', 'Provide the title of the website', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_url', 'http://localhost/nada2.1', 'Website URL', 'URL of the website', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_webmaster_email', 'mah0001@gmail.com', 'Site webmaster email address', '-', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('website_webmaster_name', 'noreply', 'Webmaster name', '-', 'website');
INSERT INTO configurations (`name`, `value`, `label`, `helptext`, `item_group`) VALUES ('year_search', 'no', NULL, NULL, NULL);


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
  `iscustom` char(2) DEFAULT '0'
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
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;

INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (35, 'home', 'Home', '<table style=\"background-color: #f4f7ee;\" border=\"0\">\r\n<tbody>\r\n<tr valign=\"top\">\r\n<td><img src=\"files/women_niger_2010_06_15_rot.jpeg\" border=\"0\" alt=\"CLIMATE CHANGE\" /></td>\r\n<td>\r\n<div style=\"padding-left: 5px;\">\r\n<div style=\"font-size: 11px; font-weight: bold; text-align: left; padding: 10px; border-bottom: 1px solid white;\">FEATURED</div>\r\n<div style=\"padding: 10px;\">\r\n<h3 style=\"font-size: 24px;\">LSMS adopts the DDI standard and releases a new data catalog</h3>\r\nThe LSMS team has undertaken a migration of its metadata to the Data Documentation Initiative (DDI) standard. Compliance with the DDI standard will allow us to provide richer metadata and new data discovery tools to our users, through our new survey catalog.\r\n<div style=\"font-size: 11px; margin-top: 10px; color: maroon;\">Read More &raquo;</div>\r\n</div>\r\n</div>\r\n</td>\r\n</tr>\r\n</tbody>\r\n</table>', 1, '0', 1279202533, 0, 1, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (53, 'catalog', 'Data', '', 1, '0', 1279211027, 1, 4, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (54, 'about', 'About', '<div class=\"lsms\">\r\n<h1>About LSMS</h1>\r\n<p>The Living Standards Measurement Study (LSMS) is a research project that was initiated in 1980.&nbsp; It is a response to a perceived need for policy relevant data that would allow policy makers to move beyond simply measuring rates of unemployment, poverty and health care use, for example, to understanding the determinants&nbsp;of these observed social sector outcomes.&nbsp; The program is designed to assist policy makers in their efforts to identify how policies could be designed and improved to positively affect outcomes in health, education, economic activitgies, housing and utilities, etc.</p>\r\n<p>Objectives</p>\r\n<ul>\r\n<li>\r\n<div>improve the quality of household survey data</div>\r\n</li>\r\n<li>\r\n<div>increase the capacity of statistical institutes to perform household surveys</div>\r\n</li>\r\n<li>\r\n<div>improve the ability of statistical institutes to analyze household survey data for policy needs</div>\r\n</li>\r\n<li>\r\n<div>provide policy makers with data that can be used to understand the determinants of observed social and economic outcomes</div>\r\n</li>\r\n</ul>\r\n<p>For a detailed history of the early years of LSMS, read M. Grosh and P. Glewwe,&nbsp;<a href=\"http://www-wds.worldbank.org/external/default/main?pagePK=64193027&amp;piPK=64187937&amp;theSitePK=523679&amp;menuPK=64187510&amp;searchMenuPK=64187283&amp;theSitePK=523679&amp;entityID=000009265_3961219114615&amp;searchMenuPK=64187283&amp;theSitePK=523679\">A Guide to Living Standards Measurement Study Surveys and Their Data Sets</a>, LSMS Working Paper #120, The World Bank, 1995.</p>\r\n</div>', 1, '0', 1274125454, 0, 2, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (55, 'citations', 'Citations', NULL, 1, '0', 1279211038, 1, 5, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (56, 'designing-surveys', 'Designing Surveys', '<div class=\"lsms\">\n<h1>Designing Surveys</h1>\n<p>This site is primarily designed for people who would like to obtain information on the materials produced by DECRG to assist in the implementation of surveys. The following&nbsp;links describe the core of our efforts to this end.</p>\n<ol>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21555942~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">A Manual for Planning and Implementing the Living Standards Measurement Study Surveys</a>. - this manual provides practical information on how to implement an LSMS-style survey from the planning stages through implementation in the field. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21613790~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Household Survey Clinics</a>- are designed to assist in the design and implementation of household surveys in low income countries. The Clinics provide broad feedback on all phases of the household survey plans and identify areas for which the TTL needs assistance or follow-up on technical issues identified during the Clinic. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21556161~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Designing Household Survey Questionnaires for Developing Countries</a>&nbsp;- this book covers key topics in the design of household surveys with many suggestions for customizing surveys to local circumstances and improving data quality. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21556298~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Energy Policies and Multitopic Household Surveys: Guidelines for Questionnaire Design in Living Standards Measurement Studies</a>&nbsp;- this paper provides information on how questions can be added to multi-topic household surveys to yield more useful information for energy policy analysis. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21613624~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Are You Being Served?</a>&nbsp;- this book provides tools for collecting data on service delivery. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21556375~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Questionnaires</a>&nbsp;- developers of new surveys may find it useful to refer to sample questionnaires as well as to guidelines on how to develop questionnaires. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21556546~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Interviewer Manuals, Abstracts, Survey Documentation</a>, etc. - in addition to the questionnaires, we can provide samples of the other types of documents necessary for implementing a successful survey. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21556568~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Other Tools</a>&nbsp;- other materials on topics such as survey design, construction of aggregates, use of anthropometric data, assessment of income, etc. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21556594~menuPK:4417929~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Sampling with Probability Proportional to Size</a>&nbsp;- A Stata do program to select a sample using the Probability Proportional to Size (PPS) procedure. </li>\n</ol></div>', 1, '0', 1279207684, 0, 6, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (57, 'analyzing-survey', 'Analyzing Survey Data', '<div class=\"lsms\">\n<h1>Analyzing Survey Data</h1>\n<p>To encourage the use of LSMS datasets, we are providing the following tools.&nbsp; These tools will help to explain and simplify applications of LSMS data.</p>\n<ol>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21557163~menuPK:4417943~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">The Analysis of Household Surveys</a>.&nbsp; This book is about the analysis of household survey data from developing countries and about how such data can be used to cast light on a range of policy issues.&nbsp; The book also provides Stata routines used to develop many of the illustrations in the book. </li>\n<li> <a href=\"http://web.worldbank.org/WBSITE/EXTERNAL/WBI/WBIPROGRAMS/PGLP/0,,contentMDK:20282391~menuPK:461255~pagePK:64156158~piPK:64152884~theSitePK:461246,00.html\">World Bank Institute Learning Program</a>.&nbsp; The goal of the Poverty Analysis Initiative is to promote increased use of information and analysis in the formulation, implementation, monitoring and evaluation of poverty reduction policies. This calls for increased quality and ownership of poverty analysis, efficient poverty monitoring systems, and capacity to evaluate the impact of interventions. </li>\n<li> <a href=\"http://web.worldbank.org/WBSITE/EXTERNAL/TOPICS/EXTHEALTHNUTRITIONANDPOPULATION/EXTPAH/0,,contentMDK:20216933~menuPK:400482~pagePK:148956~piPK:216618~theSitePK:400476,00.html\">Analyzing Health Equity Using Household Survey Data</a>. This book provides rigorous analytic techniques for both measurement and analysis of inequalities in the health sector, with a view to stimulating health equity research that can support the design and evaluation of health policies and programs. </li>\n<li> <a href=\"http://iresearch.worldbank.org/PovcalNet/jsp/index.jsp\">PovcalNet</a>&nbsp;is an interactive computational tool that allows you to replicate the calculations made by the World Bank\'s researchers in estimating the extent of absolute poverty in the world, including the $1 a day poverty measures. PovcalNet also allows you to calculate the poverty measures under different assumptions and to assemble the estimates using alternative country groupings or for any set of individual countries of you\'re choosing. PovcalNet is self-contained; it has reliable built-in software that quickly does the relevant calculations for you from the built-in database. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21991269~menuPK:4417943~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Panel data sets</a>.&nbsp; Researchers who have made panel data sets occasionally allow us to distribute the data sets on the understanding that the documentation that is provided on the LSMS web site is the only documentation that is available. </li>\n<li> <a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21557183~menuPK:4417943~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Consumption and income aggregate programs</a>.&nbsp; The programs used to calculate the income and expenditures aggregates for the&nbsp;<a href=\"http://siteresources.worldbank.org/INTLSMS/Resources/3358986-1195506442871/za94pgm.zip\">South Africa LSMS</a>&nbsp;and the&nbsp;<a href=\"http://siteresources.worldbank.org/INTLSMS/Resources/3358986-1195506442871/pak91pgm.zip\">Pakistan LSMS&nbsp;</a>&nbsp;are provided here. </li>\n</ol></div>', 1, '0', 1279208011, 0, 6, 56);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (58, 'hh-survey-clinics', 'Household Survey Clinics', '<div class=\"lsms\">\n<h1>Household Survey Clinics</h1>\n<ul>\n<li><a id=\"top_of_page\" name=\"top_of_page\"></a><a href=\"#what_is_hh_survey\">What is a Household Survey</a>?</li>\n<li><a href=\"#what_is_hh_survey_clinic\">What is a Household Survey Clinic</a>?</li>\n<li><a href=\"#who_can_request\">Who can request a Household Survey Clinic</a>?</li>\n<li><a href=\"#when_to_request\">When can a Household Survey Clinic be requested</a>?</li>\n<li><a href=\"#how_to_request\">How to Request a Household Survey Clinic</a>?</li>\n<li><a href=\"#follow_up\">Follow up to a Household Survey Clinic</a></li>\n<li><a href=\"#charge\">Charge</a></li>\n</ul>\n<br /> <a id=\"what_is_hh_survey\" name=\"what_is_hh_survey\"></a>\n<h2>What is a Household Survey?</h2>\n<p>A household survey is any survey that is administered at the household level.&nbsp; It collects information about the household and the individuals living in those households.&nbsp; It includes Living Standards Measurement Study surveys, Integrated Surveys, Priority Surveys, Core Welfare Indicator Questionnaire (CWIQ) surveys, Household Budget Surveys, Labor Force Surveys, Demographic and Health Surveys, education surveys, etc.</p>\n<h2><a id=\"what_is_hh_survey_clinic\" name=\"what_is_hh_survey_clinic\"></a>What is a Household Survey Clinic?</h2>\n<p>The World Bank supports a wide range of household surveys that provide key inputs to the design and evaluation of social and economic policy, monitoring PRSPs and other indicators including the MDGs.&nbsp;&nbsp; To promote the quality of household surveys and the resulting data sets, and to take advantage of the experience which exists in the World Bank in the area of household surveys, the Household Survey Clinic (HSC) has been developed to provide timely, on-demand, customized assistance to TTLs and other individuals and teams involved in household surveys.&nbsp;</p>\n<p>The HSC is a joint initiative of the Living Standards Measurement Study team (LSMS) in the Development Economics Research Group (DECRG) and the Africa Results and Learning Team (AFTRL) to assist in the design and implementation of household surveys supported by the World Bank.</p>\n<p>The&nbsp;Clinic is an opportunity for those involved in planning and implementing a household survey to obtain timely inputs to the process of designing, testing and fielding the survey.&nbsp; The 2-hour long&nbsp;Clinic will:</p>\n<ul>\n<li> Provide an overall review of the steps in the planned household survey: from objectives through design, implementation, documentation and analytic uses; </li>\n<li> Supply detailed feedback on specific areas concern; </li>\n<li> Act as a forum for brainstorming on difficult issues related to the survey; </li>\n<li> Identify areas for which the TTL needs assistance or follow-up on technical issues. </li>\n</ul>\n<h2><a id=\"who_can_request\" name=\"who_can_request\"></a>Who can request a Household Survey Clinic?</h2>\n<ul>\n<li> Within the World Bank, any TTL or staff member planning or developing a household survey regardless of region or focus. </li>\n<li> Limited opportunities may be available for individuals or organizations outside of the World Bank who wish a HSC.&nbsp; Such individuals and organizations should consult directly with the LSMS team to determine the feasibility of holding such a Clinic. </li>\n</ul>\n<h2><a id=\"when_to_request\" name=\"when_to_request\"></a>When can a Household Survey Clinic be requested?</h2>\n<p>There are various points in time when a HSC can be requested.&nbsp; The sooner in the process the better in terms of being able to provide useful feedback to the survey design team.&nbsp;</p>\n<ul>\n<li> When the survey planning process starts.&nbsp; At this time, the HSC can provide assistance in such procedures as setting up the process, how to design questionnaires,&nbsp;and what should be included in the sample designs, etc.&nbsp; </li>\n<li> When the basic materials for the survey -&nbsp;sample design, household questionnaire -&nbsp;are in the early draft stages, the HSC can provide feedback to make sure that they have&nbsp;incorporated as many best practices as possible. </li>\n</ul>\n<h2><a id=\"how_to_request\" name=\"how_to_request\"></a>How to request a Household Survey Clinic</h2>\n<p>To request the Clinic, you should send an email to <a href=\"mailto:LSMS@worldbank.org\">LSMS@worldbank.org</a>.&nbsp; You will be asked to complete a&nbsp;<a href=\"http://siteresources.worldbank.org/INTLSMS/Resources/3358986-1195506442871/HSC_Preparation_Form_sept15_2008.doc\">clinic preparation form&nbsp;</a>&nbsp;that outlines the key goals and objectives of the household survey and its present status, including instruments developed, sampling plans and the like if these already exist in some form.&nbsp; This documentation must be completed and submitted to the HSC team a minimum of 3 days prior to the Clinic to (i) ensure time for review, (ii) provide an opportunity for the HSC team to consult with you on the major areas on which to focus during the HS clinic and, (iii) allow the HSC team time to consult additional experts as needed given the focus and content of the planned survey. You will also provide a list of project people who should participate in the Clinic and others relevant for the general discussion during the Clinic.</p>\n<p>The time and location of the Clinic will be arranged jointly by the requesting TTL and the&nbsp;HSC Team. It is expected that no more than one Clinic will be possible in any given month due to time constraints.&nbsp; The exact timing of any given Clinic will be subject to the availability of HSC team members and the project members.</p>\n<p>With your approval, the Clinic will be open to others in the Bank who are thinking about carrying out a household survey or already involved in designing such a survey.&nbsp; Your are more than welcome to attend other Clinics in the future if a specific topic of relevance is being covered.</p>\n<h2><a id=\"follow_up\" name=\"follow_up\"></a>Follow-up to a Household Survey Clinic</h2>\n<p>Following the Clinic, the HSC team will provide the participants with a summary note of the Clinic, detailing the discussion and any recommendations for next steps.&nbsp; As needed, the HSC team will also identify consultants to provide more in-depth technical assistance to you in the specific areas identified by the Clinic.&nbsp;&nbsp; If more extended cross-support is requested,&nbsp;you and HSC team can identify the most appropriate staff members to follow-up and devise a detailed timeline and work plan.</p>\n<p>To ensure that the Clinics are as effective as possible, you and other project members will be requested to fill out a short evaluation form for the Clinic.&nbsp; This evaluation can be done anonymously.</p>\n<p>Within the World Bank, the cost for the preparation work and the Clinic is 3 staff days per Clinic (2 staff days per clinic within the Africa Region).</p>\n<p>For individuals or organizations outside of the World Bank, fees will apply.</p>\n</div>', 1, '0', 1279209370, 0, 6, 56);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (59, 'lsms-phase-1', 'LSMS Phase I (1980-1985)', '<div class=\"lsms\">\r\n<h1>LSMS Phase I (1980 - 1985)</h1>\r\n<p>Phase I of the study was a five year comprehensive review of existing household surveys, and extensive consultations with researchers and policymakers to determine the types of data needed. In addition, consultations with survey methodologists were held on how best to design the actual field work procedures. At the end of this review, the first LSMS surveys were piloted in Côte d’Ivoire and Peru in 1985. These two first surveys were research projects testing the full LSMS methodology to determine, from the resulting data, the usefulness and quality of the data obtained. The success of these first two surveys has been responsible for the over 70 LSMS surveys that have been carried out in&nbsp;more than&nbsp;40 countries over 20 years.</p>\r\n<ul>\r\n  <li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21378432~menuPK:4198254~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1985 Côte d\'Ivoire Enquête Permanente Auprès des Ménages</a></li>\r\n  <li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21370632~menuPK:4198254~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">985 Perú Encuesta Nacional de Hogares sobre Medición de Niveles de Vida</a></li>\r\n</ul>\r\n</div>', 1, '0', 1277179040, 0, 7, 54);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (60, 'lsms-phase-2', 'LSMS Phase II (1985-1990)', '<div class=\"lsms\">\n<h1>LSMS Phase II (1985 - 1990)</h1>\n<p>Because the first surveys were designed for research purposes, there was little variation in the surveys&rsquo; design and implementation. In Phase II, the experience from Phase I was shared with a wide range of low- and middle-income countries, and many different national agencies and international organizations. LSMS surveys became increasingly customized to fit specific country circumstances, including policy issues, social and economic characteristics, and local household survey traditions. They also reflected the interests of the individuals planning the surveys. The result of Phase II was to develop improved methodology for: (a) questionnaire design; (b) field work; (c) data entry and processing; and (d) analysis.</p>\n<ul>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21378432~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1986 C&ocirc;te d\'Ivoire Enquete Permanente Aupres de Menages</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21378507~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1987 C&ocirc;te d\'Ivoire Enquete Permanente Aupres de Menages</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21378628~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1988 C&ocirc;te d\'Ivoire Enquete Permanente Aupres de Menages</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21380196~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1987/88 Ghana Living Standards Survey</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21387102~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1988/89 Ghana Living Standards Survey</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21542575~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1988 Jamaica Survey of Living Conditions</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21533553~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1989-1 Jamaica Survey of Living Conditions</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21533654~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1989-2 Jamaica Survey of Living Conditions</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21541739~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">1990 Jamaica Survey of Living Conditions</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21370663~menuPK:4198262~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Peru 1990 Encuesta de Hogares sobre Medicion de Niveles de Vida</a> </li>\n</ul>\n</div>', 1, '0', 1279492142, 0, 7, 54);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (61, 'lsms-phase-3', 'LSMS Phase III (1990-ongoing)', '<div class=\"lsms\">\r\n<h1>LSMS Phase III (1990 - ongoing)</h1>\r\n<p>&nbsp;Phase III of the LSMS represents the core tasks that will continue to be provided. It has concentrated on: (a) disseminating methodological tools for implementing and analyzing household surveys; and (b) archiving, documenting, and distributing information on the survey data that have been collected through the study. This phase has seen the development of the LSMS web site where users have access to: (a) research results and methodological tools, through for example the LSMS Working Paper series; (b) documentation on the surveys including basic information documents, questionnaires, and manuals; and (c) the micro data from the surveys themselves. One of the innovative features developed in this phase is the basic information documents which provide, in one document, details about the sample design, field work procedures, the calendar of events, codes not found in the questionnaire, and additional information needed for analyses of the data, such as consumer price indices. These documents are designed to provide data users with all of the information they need to use the complicated data from the LSMS surveys correctly.</p>\r\n<p><a href=\"http://www-wds.worldbank.org/external/default/main?pagePK=64193027&amp;piPK=64187937&amp;theSitePK=523679&amp;menuPK=64187510&amp;searchMenuPK=64187283&amp;theSitePK=523679&amp;entityID=000009265_3961219114615&amp;searchMenuPK=64187283&amp;theSitePK=523679&gt;A Guide to Living Standards Measurement Study Surveys and Their Data Sets      &lt;/a&gt;, LSMS Working Paper #120, 1995.&lt;/p&gt;&lt;p&gt;&lt;a href=\">A Manual for Planning and Implementing the Living Standards Measurement Study Survey</a>, LSMS Working Paper #126, 1996.</p>\r\n<p>Available in Russian upon&nbsp;<a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21483635~menuPK:4198282~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">request</a>.</p>\r\n<p><a href=\"http://www-wds.worldbank.org/external/default/main?pagePK=64193027&amp;piPK=64187937&amp;theSitePK=523679&amp;menuPK=64187510&amp;searchMenuPK=64187283&amp;theSitePK=523679&amp;entityID=000160016_20060914174627&amp;searchMenuPK=64187283&amp;theSitePK=523679\">Manual de Diseño y Ejecución de Encuestas Sobre Condiciones de Vida</a>, LSMS Working Paper #126S, 1999.</p>\r\n<p><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21556161~menuPK:4198282~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Designing Household Survey Questionnaires for Developing Countries: Lessons from 15 Years of the Living Standards Measurement Study</a>, Edited by Margaret Grosh and Paul Glewwe.</p>\r\n</div>', 1, '0', 1277179040, 0, 7, 54);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (62, 'lsms-phase-4', 'LSMS Phase IV (2006-2010)', '<div class=\"lsms\">\r\n<h1>LSMS Phase IV (2006 - 2010)</h1>\r\n<p>Phase IV will extend the research that has been done on survey methodologies.&nbsp; It will focus on how to continue to improve the collection of quality household micro data, and how to make the process as efficient as possible.&nbsp; it will also create new tools and update the existing ones to expand the dissemination of literature, knowledge and results in the area of survey methodology in developing countries.</p>\r\n<ul>\r\n<li><div>Expected Experiments</div><ul>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21601719~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Consumption</a></div></li>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21610099~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Income</a></div></li>\r\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21660494~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Labor</a></li>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21601748~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Subjective Welfare</a></div></li>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21610111~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Inequalities of Opportunity</a></div></li>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21610122~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Disability</a></div></li>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21601744~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Financial services</a></div></li>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21610128~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Risk and Vulnerability</a></div></li>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21610679~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Migration</a></div></li>\r\n</ul>\r\n<ul>\r\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21610693~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Infrastructure</a>&nbsp;</li>\r\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21610721~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Gender</a></li>\r\n</ul>\r\n</li>\r\n</ul>\r\n<ul>\r\n<li><div>Survey Techniques</div><ul>\r\n<li><div>Item and Unit Non-response</div></li>\r\n<li><div>Reducing Sample Attrition in Panel Surveys</div></li>\r\n<li><div>World Bank, Enterprise Institute and Yale University Conference on&nbsp;<a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTPROGRAMS/EXTFINRES/0,,contentMDK:22354533~pagePK:64168182~piPK:64168060~theSitePK:478060~isCURL:Y~isCURL:Y,00.html\">Survey Design and Measurement in Development Economics</a>&nbsp;</div></li>\r\n</ul>\r\n</li>\r\n</ul>\r\n<ul>\r\n<li><div>Technological Innovations in Survey Implementation</div><ul>\r\n<li><div><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:21601299~menuPK:4198292~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Computer Assisted Personal Interviewing</a></div></li>\r\n</ul>\r\n<ul>\r\n<li>Global Positioning Systems</li>\r\n</ul>\r\n<ul>\r\n<li>Environmental Testing</li>\r\n</ul>\r\n</li>\r\n</ul>\r\n</div>', 1, '0', 1277179040, 0, 7, 54);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (64, 'archived-corrections', 'Archived corrections', '<div class=\"lsms\">\r\n  <h1>Archived Corrections/Modifications</h1>\r\n  <table width=\"100%\">\r\n    <tbody>\r\n      <tr valign=\"top\">\r\n        <td style=\"width: 120px;\">January 28, 2010</td>\r\n        <td><p>Kyrgyz Republic 1996. The files containing expenditure and income data were  discovered to be for the Spring 1996 survey rather than the Fall 1996 survey and  have been removed from the data for distribution.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>August 3, 2006</td>\r\n        <td><p>Albania 2004. The file containing household level data did not contain  information on remittances received by the household (Module 13). The  information has been added.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>April 20, 2006</td>\r\n        <td><p>Panama 2003. TheStata version of the file E03GA10 did not convert  correctly and the variable \"rubro\" was not useable. The variable has been  recreated as \"newrubro\" to give the information needed to analyze the  data.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>May 4, 2006</td>\r\n        <td><p>Albania 2002. The data distributed was missing the file for Module 14,  Other Income. If you need a copy of this file, contact us and we will send you  the file. Be sure to tell us which format you prefer - ASCII, SAS Portable,  SPSS or Stata.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>December 8, 2003</td>\r\n        <td><p>Bosnia and Herzegovina 2001. The data distributed as Module 13, Part E  (Farm Capital Assets) was actually a copy of the data for Module 2, Part C  (Housing Durable Goods). To get a copy of the correct file, contact us and we  will send you the file. Be sure to tell us which format you prefer - ASCII, SAS  Portable or Stata.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>July 16, 2003</td>\r\n        <td><p>Tajikistan 2003. There are two records in the population point data with  the same population point identification number (pop_pt=73) and no data for  population point 69. One of the records for population point 73 was actually  the data for population point 69 and we have made the correction. To get a copy  of the corrected file, contact us and we will send you the file. Be sure to  tell us which format you prefer - ASCII, SAS Portable or Stata.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>April 7, 2003</td>\r\n        <td><p>Peru 1991. The expansion factors for the 1991 Peru Survey have been added  to the documents provided for the survey. The information is also available in  the 1994 Peru Survey Supplemental Documentation.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>February 25, 2003</td>\r\n        <td><p>Azerbaijan 1995. A user contacted us to let us know that file A06B  contained errors. We have recreated this data from the original data as key  entered. To get a copy of this file, contact us and we will send you the file.  Be sure to tell us which format you prefer - ASCII, SAS Portable or  Stata.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>August 5, 2002</td>\r\n        <td><p>Nicaragua 1993. We were provided with a new version of the expansion factor  file for the 1993 Nicaragua LSMS data set. Users who have already downloaded  the data should download the Group B data again for the file, NEWCOMP.</p>\r\n        </td>\r\n      </tr>\r\n      <tr valign=\"top\">\r\n        <td>October 26, 2001</td>\r\n        <td>South Africa 1993. Data collected in clusters 217 and 218 should be viewed  as highly unreliable and therefore removed from the data set. The data  currently available on the web site has been revised to remove the data from  these cluster. Researchers who have downloaded the data in the past should  revise their data sets.</td>\r\n      </tr>\r\n    </tbody>\r\n  </table>\r\n</div>\r\n', 1, '0', 1279210984, NULL, 8, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (65, 'lsms-isa', 'LSMS-ISA', '<div class=\"lsms\">\n<h1>LSMS-ISA</h1>\n<p>The Living Standards Measurement Study-Integrated Surveys on Agriculture  (LSMS-ISA) project is a new initiative funded by the <a href=\"http://www.gatesfoundation.org/Pages/home.aspx\">Bill &amp; Melinda Gates  Foundation (BMGF)</a> and led by the LSMS Team in the <a href=\"http://econ.worldbank.org/external/default/main?menuPK=469435&pagePK=64165236&piPK=64165141&theSitePK=469382\">Development  Research Group (DECRG)</a> of the World Bank. The project will support  governments in 7 Sub-Saharan African countries to generate nationally  representative, household panel data with a strong focus on agriculture and  rural development. The objective of this program is to improve the understanding  of development in Africa, particularly agriculture and linkages between farm and  non-farm activities.</p>\n<p>The LSMS-ISA surveys will be conducted within each project country at least  twice during the six-year duration of the project, and will be modeled on the  multi-topic household survey design of the LSMS. Hence, the panel surveys will  collect detailed information on agricultural production, non-farm income  generating activities, consumption expenditures, as well as a wealth of other  socio-economic information.</p>\n<p>The LSMS-ISA survey samples will be designed to produce national and  sub-national statistics for major geographical and/or agro-ecological zones. In  all countries, the samples will cover both rural and urban areas for a better  understanding of geographical mobility and spatial dimensions of  development.</p>\n<p>In addition to the goal of improving the availability of policy-relevant  agricultural data, the project emphasizes the design and validation of  innovative survey methods, the use of technology for improving survey data  quality, the development of analytical tools to facilitate the use and analysis  of the data collected, and investments in enhancing the capacity of national  statistics offices and ministries of agriculture to produce timely and relevant  household survey data. The data from these surveys, which will be made publicly  available within 12 months of field work completion, will be used by  governments, their development partners and the research community at large as a  basis for economic analyses and policy research as well as evaluations of  agricultural policies to foster discussions and promote effective approaches to  advance the role of agriculture and non-farm income activities in economic  development.</p>\n<p>Key dimensions to this project include:</p>\n<ul>\n<li>Original Project Proposal (<a href=\"http://siteresources.worldbank.org/INTLSMS/Resources/3358986-1233781970982/PanelSurveyAfrica_BMGF_dissemination.pdf\">English</a>)  (<a href=\"http://siteresources.worldbank.org/INTLSMS/Resources/3358986-1233781970982/PanelSurveyAfrica_BMGF_Dissemination_French.pdf\">French</a>)</li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288123~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Project  Administration</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288073~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Country  Programs</a> \n<ul>\n<li style=\"list-style-type: none;\"> </li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288781~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Tanzania</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288784~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Uganda</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288785~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Malawi</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288787~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Ethiopia</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288788~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Nigeria</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288775~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Niger</a></li>\n</ul>\n</li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288793~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Multi-Topic  Questionnaire Content</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288799~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Methodological  Validation Exercises &amp; Development of Sourcebooks</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288800~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">CAPI  Development &amp; Implementation</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288801~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Capacity  Building</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288802~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Data  Access &amp; Dissemination</a></li>\n<li><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22288822~menuPK:6194952~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Institutional  Partnerships</a></li>\n</ul>\n</div>', 1, '0', 1279211384, NULL, 9, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (66, 'training', 'Training', '<div class=\"lsms\">\n<h1>Training</h1>\n<p><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTPROGRAMS/EXTPOVRES/0,,contentMDK:22417292~menuPK:3487876~pagePK:64168182~piPK:64168060~theSitePK:477894~isCURL:Y,00.html\">Designing  and Implementing Multi-topic Household Surveys: Generating Policy Relevant  Data</a>.&nbsp;This course is one module of the multi-module course,&nbsp;<a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTPROGRAMS/EXTPOVRES/0,,contentMDK:22417084~menuPK:3487876~pagePK:64168182~piPK:64168060~theSitePK:477894,00.html\">Poverty  and Inequality Analysis</a>, which is offered annually by the Research Group of  the World Bank.&nbsp; This course is open to World Bank staff and to the staff of  other development agencies.&nbsp; The course lasts 2 to 2.5 days and is held at World  Bank headquarters in Washington DC.&nbsp; The course covers the major aspects of the  survey&nbsp;cycle - deciding whether&nbsp;an LSMS-type survey is needed; questionnaires;  sampling; field work; data entry, management and documentation; the production  of an abstract and data dissemination; ways of fostering&nbsp;data analysis.&nbsp; The  course is usually held in late January/early February.&nbsp;</p>\n<p><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTPROGRAMS/EXTPOVRES/0,,contentMDK:22421915~menuPK:3487876~pagePK:64168182~piPK:64168060~theSitePK:477894~isCURL:Y,00.html\">Sampling  for Surveys</a>. This course is one module of the multi-module course,&nbsp;<a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTPROGRAMS/EXTPOVRES/0,,contentMDK:22417084~menuPK:3487876~pagePK:64168182~piPK:64168060~theSitePK:477894,00.html\">Poverty  and Inequality Analysis</a>, which is offered annually by the Research Group of  the World Bank.&nbsp; This course is open to World Bank staff and to the staff of  other development agencies.&nbsp; The course lasts 2 and is held at World Bank  headquarters in Washington DC.&nbsp; The course covers issues related to survey  sample design.&nbsp; The course is usually held in late January/early February.&nbsp;</p>\n<p><a href=\"http://econ.worldbank.org/WBSITE/EXTERNAL/EXTDEC/EXTRESEARCH/EXTLSMS/0,,contentMDK:22137609~menuPK:3359069~pagePK:64168445~piPK:64168309~theSitePK:3358997~isCURL:Y,00.html\">Designing  and Implementing Household Surveys</a>.&nbsp; This course provides a broad overview  of the uses of surveys, how they are designed and implemented and new  innovations in surveys.&nbsp; It highlights areas relevant for impact evaluation.&nbsp; It  was presented on March 31, 2009 in Cairo, Egypt as part of the Pre-conference  Workshop for the conference&nbsp;<a href=\"http://www.impactevaluation2009.org/\">Perspectives on Impact Evaluation:  Approaches to Assessing Development Effectiveness</a>.</p>\n<p><a href=\"http://siteresources.worldbank.org/INTLSMS/Resources/3358986-1239390183563/LaborMarketCoreCourse_DataApr2009.ppt\">Labor  Market Information Systems and Data Analysis</a>.&nbsp; As part of the World Bank\'s  annual&nbsp;<a href=\"http://web.worldbank.org/WBSITE/EXTERNAL/WBI/WBIPROGRAMS/SPLP/0,,contentMDK:22161086~menuPK:461671~pagePK:64156158~piPK:64152884~theSitePK:461654~isCURL:Y~isCURL:Y,00.html\">Labor  Market Policy Core Course</a>&nbsp;in World Bank Headquarters in Washington, DC, the  LSMS Team has a session on data availability and analysis related to labor  markets.&nbsp; The most recent course was focused on \"Jobs for a Globalizing World\"  and the LSMS Session was presented on April 7, 2009.</p>\n</div>', 1, '0', 1279211624, NULL, 10, 0);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (67, 'core-team', 'Core Team', '<div class=\"lsms\">\n<h1>Core Team</h1>\n<p>The Core Team of the Living Standards Measurement Study includes:</p>\n<ul>\n<li>\n<div><a href=\"http://econ.worldbank.org/external/default/main?authorMDK=435247&theSitePK=469372&menuPK=64214916&pagePK=64214821&piPK=64214942\">Kathleen  Beegle</a></div>\n</li>\n<li>\n<div><a href=\"http://econ.worldbank.org/external/default/main?authorMDK=550790&theSitePK=469372&pagePK=64214821&menuPK=64214916&piPK=64214942\">Gero  Carletto</a></div>\n</li>\n<li>\n<div>Kinnon Scott</div>\n</li>\n<li>\n<div><a href=\"http://econ.worldbank.org/external/default/main?authorMDK=99436&theSitePK=469372&menuPK=64214916&pagePK=64214821&piPK=64214942\">Diane  Steele</a></div>\n</li>\n<li>\n<div>Qinghua Zhao</div>\n</li>\n<li>\n<div>Kristen Himelein</div>\n</li>\n<li>\n<div>Talip Kilic</div>\n</li>\n<li>\n<div><span style=\"color: #000000;\">Gbemisola Oseni</span></div>\n</li>\n<li>\n<div><span style=\"color: #000000;\">Siobhan Murray<br /></span></div>\n</li>\n</ul>\n</div>', 1, '0', 1279211706, NULL, 9, 54);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (68, 'p', 'test', '<p>tets</p>', 1, '0', 1280888286, NULL, -34, NULL);
INSERT INTO menus (`id`, `url`, `title`, `body`, `published`, `target`, `changed`, `linktype`, `weight`, `pid`) VALUES (69, 'tester', 'ttest', NULL, 1, '0', 1280761741, 1, -9, NULL);


#
# TABLE STRUCTURE FOR: public_requests
#

CREATE TABLE `public_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL,
  `surveyid` int(10) unsigned NOT NULL,
  `abstract` text NOT NULL,
  `posted` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;

INSERT INTO public_requests (`id`, `userid`, `surveyid`, `abstract`, `posted`) VALUES (1, 1, 20, 'asdfadsf', 1274375941);
INSERT INTO public_requests (`id`, `userid`, `surveyid`, `abstract`, `posted`) VALUES (2, 1, 21, 'ttttttttttttttttttttttttttttttttttttttttt', 1275664972);
INSERT INTO public_requests (`id`, `userid`, `surveyid`, `abstract`, `posted`) VALUES (3, 16, 9, 'test', 1279642959);
INSERT INTO public_requests (`id`, `userid`, `surveyid`, `abstract`, `posted`) VALUES (4, 16, 57, 'asdfasdfsd sdf asdfsdf', 1280945610);
INSERT INTO public_requests (`id`, `userid`, `surveyid`, `abstract`, `posted`) VALUES (5, 16, 56, 'dsafkjasdlkfjalsjdfldsaf', 1280946392);


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


