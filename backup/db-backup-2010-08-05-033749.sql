
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


