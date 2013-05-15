--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` varchar(100) NOT NULL,
  `data` text,
  `created` int(11) DEFAULT NULL,
  `expiry` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_UNIQUE` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `lic_files`
--

CREATE TABLE `lic_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surveyid` int(11) NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `changed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



--
-- Table structure for table `site_menu`
--

CREATE TABLE `site_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `depth` int(11) DEFAULT NULL,
  `module` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `site_menu`
--

INSERT INTO `site_menu` VALUES (1,0,'Dashboard','admin',0,0,'admin'),(2,0,'Studies','admin/catalog',1,0,'catalog'),(4,0,'Citations','admin/citations',3,0,'citations'),(5,0,'Users','admin/users',4,0,'users'),(6,0,'Menu','admin/menu',5,0,'menu'),(7,0,'Reports','admin/reports',6,0,'reports'),(8,0,'Settings','admin/configurations',7,0,'configurations'),(12,2,'-','-',70,1,'catalog'),(13,2,'Licensed requests','admin/licensed_requests',80,1,'catalog'),(14,2,'-','-',90,1,'catalog'),(15,2,'Manage collections','admin/repositories',60,1,'repositories'),(17,4,'All citations','admin/citations',100,1,'citations'),(18,4,'Import citations','admin/citations/import',90,1,'citations'),(19,4,'Export citations','admin/citations/export',80,1,'citations'),(20,5,'All users','admin/users',100,1,'users'),(21,5,'Add user','admin/users/add',99,1,'users'),(22,5,'-','-',65,1,'users'),(23,5,'User groups','admin/user_groups',90,1,'user_groups'),(24,5,'Add user group','admin/user_groups/add',80,1,'user_groups'),(25,5,'-','-',95,1,'user_groups'),(26,5,'User permissions','admin/permissions',60,1,'permissions'),(27,6,'All pages','admin/menu',0,1,'menu'),(28,7,'All reports','admin/reports',0,1,'reports'),(29,8,'Settings','admin/configurations',0,1,'configurations'),(30,8,'Countries','admin/countries',0,1,'vocabularies'),(31,8,'Regions','admin/regions',0,1,'vocabularies'),(32,8,'-','-',0,1,'vocabularies'),(33,8,'Vocabularies','admin/vocabularies',-9,1,'vocabularies'),(34,2,'Manage studies','admin/catalog',100,1,'catalog'),(35,5,'Impersonate user','admin/users/impersonate',50,1,'users'),(36,5,'-','-',51,1,'users');

--
-- Table structure for table `repositories`
--

CREATE TABLE `repositories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `repositoryid` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `organization` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `country` varchar(45) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `surveys_found` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `type` int(10) unsigned DEFAULT NULL,
  `short_text` varchar(1000) DEFAULT NULL,
  `long_text` text,
  `thumbnail` varchar(255) DEFAULT NULL,
  `weight` int(10) unsigned DEFAULT NULL,
  `ispublished` tinyint(3) unsigned DEFAULT NULL,
  `section` int(11) DEFAULT NULL,
  `group_da_public` tinyint(1) DEFAULT '0',
  `group_da_licensed` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Ind_unq` (`repositoryid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `repositories`
--

INSERT INTO `repositories` VALUES (7,0,'central','Central Catalog','central','central',NULL,'central',NULL,NULL,1366310463,0,'Integer adipiscing dignissim porttitor. Pellentesque ut gravida sem. Vestibulum commodo accumsan sem, eu sodales ante porta quis. Vestibulum iaculis rhoncus eros id iaculis. Vivamus diam sem, laoreet vitae vulputate sit amet.','<div class=\"repository-about\">\r\n<img src=\"files/sar-fp-01.jpg\" style=\"float:left;margin-right:15px;\" />\r\nInteger adipiscing dignissim porttitor. Pellentesque ut gravida sem. Vestibulum commodo accumsan sem, eu sodales ante porta quis. Vestibulum iaculis rhoncus eros id iaculis. Vivamus diam sem, laoreet vitae vulputate sit amet. Integer adipiscing dignissim porttitor. Pellentesque ut gravida sem. Vestibulum commodo accumsan sem, eu sodales ante porta quis. Vestibulum iaculis rhoncus eros id iaculis. Vivamus diam sem, laoreet vitae vulputate sit amet. Integer adipiscing dignissim porttitor. Pellentesque ut gravida sem. Vestibulum commodo accumsan sem, eu sodales ante porta quis. Vestibulum iaculis rhoncus eros id iaculis. Vivamus diam sem, laoreet vitae vulputate sit amet.\r\n</div>','files/sar-fp-02.jpg',0,1,5,0,0);

--
-- Table structure for table `variables`
--

CREATE TABLE `variables` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `varID` varchar(45) DEFAULT '',
  `name` varchar(45) DEFAULT '',
  `labl` varchar(245) DEFAULT '',
  `qstn` text,
  `catgry` text,
  `surveyid_FK` int(11) NOT NULL,
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

--
-- Table structure for table 'users'
--

CREATE TABLE `users` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `ip_address` char(16) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(1000) NOT NULL,
  `salt` varchar(40) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `activation_code` varchar(40) DEFAULT NULL,
  `forgotten_password_code` varchar(40) DEFAULT NULL,
  `remember_code` varchar(40) DEFAULT NULL,
  `created_on` int(11) NOT NULL,
  `last_login` int(11) NOT NULL,
  `active` tinyint(3) DEFAULT NULL,
  `authtype` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;


--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_group_UNQ` (`user_id`,`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;


--
-- Table structure for table `survey_relationships`
--

CREATE TABLE `survey_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid_1` int(11) DEFAULT NULL,
  `sid_2` int(11) DEFAULT NULL,
  `relationship_id` int(11) DEFAULT NULL,
  `pair_id` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pair` (`pair_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='related surveys e.g. parent, child, sibling, related';


--
-- Table structure for table `survey_tags`
--

CREATE TABLE `survey_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag` (`sid`,`tag`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;


--
-- Table structure for table `meta`
--

CREATE TABLE `meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


--
-- Table structure for table `repository_sections`
--

CREATE TABLE `repository_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `weight` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `repository_sections`
--

INSERT INTO `repository_sections` VALUES (2,'Regional Collections',5),(3,'Specialized Collections',10),(4,'internal',13),(5,'Central',-20);

--
-- Table structure for table `survey_topics`
--

CREATE TABLE `survey_topics` (
  `sid` int(11) NOT NULL,
  `tid` int(11) NOT NULL,
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `Idx_uniq` (`tid`,`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=885 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `bid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `body` text,
  `region` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `published` int(11) DEFAULT NULL,
  `pages` text,
  PRIMARY KEY (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


--
-- Table structure for table `survey_citations`
--

CREATE TABLE `survey_citations` (
  `sid` int(11) DEFAULT NULL,
  `citationid` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Idx_s_c` (`sid`,`citationid`)
) ENGINE=InnoDB AUTO_INCREMENT=526 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


--
-- Table structure for table `dcformats`
--

CREATE TABLE `dcformats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `dcformats`
--

INSERT INTO `dcformats` VALUES (1,'Compressed, Generic [application/x-compressed]'),(2,'Compressed, ZIP [application/zip]'),(3,'Data, CSPro [application/x-cspro]'),(4,'Data, dBase [application/dbase]'),(5,'Data, Microsoft Access [application/msaccess]'),(6,'Data, SAS [application/x-sas]'),(7,'Data, SPSS [application/x-spss]'),(8,'Data, Stata [application/x-stata]'),(9,'Document, Generic [text]'),(10,'Document, HTML [text/html]'),(11,'Document, Microsoft Excel [application/msexcel]'),(12,'Document, Microsoft PowerPoint [application/mspowerpoint'),(13,'Document, Microsoft Word [application/msword]'),(14,'Document, PDF [application/pdf]'),(15,'Document, Postscript [application/postscript]'),(16,'Document, Plain [text/plain]'),(17,'Document, WordPerfect [text/wordperfect]'),(18,'Image, GIF [image/gif]'),(19,'Image, JPEG [image/jpeg]'),(20,'Image, PNG [image/png]'),(21,'Image, TIFF [image/tiff]');

--
-- Table structure for table `group_repo_access`
--

CREATE TABLE `group_repo_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `repo_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grp_repo_UNIQUE` (`group_id`,`repo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `group_repo_access`
--

INSERT INTO `group_repo_access` VALUES (58,3,7),(16,3,8),(59,4,7),(26,4,8),(60,5,7),(77,6,9),(78,6,10),(57,7,7),(29,7,8),(30,7,9),(31,7,10),(27,10,10);

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `formid` int(11) DEFAULT NULL,
  `isshared` tinyint(1) NOT NULL DEFAULT '1',
  `isdeleted` tinyint(1) NOT NULL DEFAULT '0',
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `link_questionnaire` varchar(255) DEFAULT NULL,
  `countryid` int(11) DEFAULT NULL,
  `data_coll_start` int(11) DEFAULT NULL,
  `data_coll_end` int(11) DEFAULT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `kindofdata` varchar(255) DEFAULT NULL,
  `keywords` text,
  `ie_program` varchar(255) DEFAULT NULL,
  `ie_project_id` varchar(255) DEFAULT NULL,
  `ie_project_name` varchar(255) DEFAULT NULL,
  `ie_project_uri` varchar(255) DEFAULT NULL,
  `ie_team_leaders` text,
  `project_id` varchar(255) DEFAULT NULL,
  `project_name` varchar(255) DEFAULT NULL,
  `project_uri` varchar(255) DEFAULT NULL,
  `link_da` varchar(255) DEFAULT NULL,
  `published` tinyint(4) DEFAULT NULL,
  `total_views` int(11) DEFAULT '0',
  `total_downloads` int(11) DEFAULT '0',
  `stats_last_updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_srvy_unq` (`surveyid`,`repositoryid`),
  FULLTEXT KEY `ft_titl` (`titl`),
  FULLTEXT KEY `ft_all` (`titl`,`authenty`,`geogcover`,`nation`,`topic`,`scope`,`sername`,`producer`,`sponsor`,`refno`,`abbreviation`,`kindofdata`,`keywords`)
) ENGINE=MyISAM AUTO_INCREMENT=605 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


--
-- Table structure for table `survey_repos`
--

CREATE TABLE `survey_repos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned NOT NULL,
  `repositoryid` varchar(255) NOT NULL,
  `isadmin` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `tokenid` varchar(100) NOT NULL,
  `dated` int(11) NOT NULL,
  PRIMARY KEY (`tokenid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text,
  `published` tinyint(1) DEFAULT NULL,
  `target` varchar(45) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `linktype` tinyint(1) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `pid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_url` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8;


--
-- Dumping data for table `menus`
--

INSERT INTO `menus` VALUES (53,'catalog','Microdata  Catalog','',1,'0',1300807037,1,1,0);

--
-- Table structure for table `url_mappings`
--

CREATE TABLE `url_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(255) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` tinyint(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `group_type` varchar(40) DEFAULT NULL,
  `access_type` varchar(45) DEFAULT NULL,
  `weight` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` VALUES (1,'admin','Administrator','admin','UNLIMITED',0),(2,'user','General User','user','NONE',-99),(3,'catalog-admin','Catalog Administrator','admin','LIMITED',0),(4,'lic-req-admin','Licensed Request Administrator','admin','LIMITED',0),(5,'report-admin','Reports Administrato','admin','LIMITED',0),(6,'LSMS Collection Admin','Administrators for LSMS Collection','admin','limited',0),(7,'Reviewers','Reviewers','reviewer','none',-90),(9,'LAC Administrators','LAC Collection Administrators','admin','limited',0),(10,'LAC LIC-REVIEWERS','LAC Licensed Request Reviewers','admin','limited',0);

--
-- Table structure for table `survey_relationship_types`
--

CREATE TABLE `survey_relationship_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rel_group_id` int(11) DEFAULT NULL,
  `rel_name` varchar(45) DEFAULT NULL,
  `rel_dir` tinyint(4) DEFAULT NULL,
  `rel_cordinality` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;


--
-- Table structure for table `lic_requests`
--

CREATE TABLE `lic_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `request_type` varchar(45) DEFAULT 'study',
  `surveyid` int(11) DEFAULT NULL,
  `collection_id` varchar(100) DEFAULT NULL,
  `org_rec` varchar(200) DEFAULT NULL,
  `org_type` varchar(45) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tel` varchar(150) DEFAULT NULL,
  `fax` varchar(100) DEFAULT NULL,
  `datause` text,
  `outputs` text,
  `compdate` varchar(45) DEFAULT NULL,
  `datamatching` int(11) DEFAULT NULL,
  `mergedatasets` text,
  `team` text,
  `dataset_access` varchar(20) DEFAULT 'whole',
  `created` int(11) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `comments` text,
  `locked` tinyint(4) DEFAULT NULL,
  `orgtype_other` varchar(145) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  `updatedby` varchar(45) DEFAULT NULL,
  `ip_limit` varchar(255) DEFAULT NULL,
  `expiry_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;




--
-- Table structure for table `lic_requests_history`
--

CREATE TABLE `lic_requests_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lic_req_id` int(11) DEFAULT NULL,
  `user_id` varchar(100) DEFAULT NULL,
  `logtype` varchar(45) DEFAULT NULL,
  `request_status` varchar(45) DEFAULT NULL,
  `description` text,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;


--
-- Table structure for table `da_collection_surveys`
--

CREATE TABLE `da_collection_surveys` (
  `id` int(11) NOT NULL,
  `cid` int(11) DEFAULT NULL,
  `sid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_coll_sid` (`cid`,`sid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_UNIQUE` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(45) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `section` varchar(45) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` VALUES (1,'Upload DDI file','this is a test description','catalog',3),(2,'View catalog','this is a test description','catalog',0),(3,'Access site administration','this is a test description','site_admin',0),(4,'Access Menus','this is a test description','menu_admin',0),(5,'Add menu page','this is a test description','menu_admin',0),(6,'Edit menu','this is a test description','menu_admin',0),(7,'Add menu link','this is a test description','menu_admin',0),(8,'Sort menu items','this is a test description','menu_admin',0),(9,'Access vocabularies','this is a test description','vocab',0),(10,'Access vocabulary terms','this is a test description','vocab',0),(11,'Manager users','this is a test description','user_admin',0),(12,'Edit user information','this is a test description','user_admin',0),(14,'Access DDI Browser','this is a test description','ddibrowser',0),(16,'Access site pages','this is a test description','general_site',0),(18,'View citations','this is a test description','general_site',0),(22,'Site backup','this is a test description','site_admin',0),(23,'View licensed request form','this is a test description','general_site',0),(25,'Switch site language','this is a test description','general_site',0),(27,'Translate site','this is a test description','site_admin',0),(30,'Public use files','this is a test description','general_site',0),(40,'Data Deposit','Data Deposit','site_admin',0),(41,'Publish/Unpublish study','Allows publishing study','catalog',3),(42,'Delete Study','delete study','catalog',4),(43,'Export DDI','Export','catalog',5),(44,'Import RDF','Import RDF for study resources','catalog',5),(45,'Manage Repositories','Manage repositories','repositories',9),(46,'Replace DDI','Replace a DDI file','catalog',3),(49,'Edit survey','Edit survey','catalog',4),(61,'Select collection','','repositories',1),(62,'Copy DDI','copy DDI','catalog',0),(63,'Copy studies from other collections','','catalog',6),(64,'View citations','','citation',1),(65,'Edit citation','','citation',2),(66,'Delete citation','Delete a citation','citation',3),(67,'Import citations','','citation',4),(68,'Export citations','Export citations to various formats','citation',5);

--
-- Table structure for table `survey_years`
--

CREATE TABLE `survey_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `data_coll_year` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_sid_year` (`sid`,`data_coll_year`),
  KEY `idx_sid` (`sid`)
) ENGINE=InnoDB AUTO_INCREMENT=1938 DEFAULT CHARSET=utf8;


--
-- Table structure for table `region_countries`
--

CREATE TABLE `region_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=753 DEFAULT CHARSET=latin1;

--
-- Table structure for table `survey_notes`
--

CREATE TABLE `survey_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned DEFAULT NULL,
  `note` text NOT NULL,
  `type` tinytext NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8;

--
-- Table structure for table `citation_authors`
--

CREATE TABLE `citation_authors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `initial` varchar(255) DEFAULT NULL,
  `author_type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3853 DEFAULT CHARSET=utf8;

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `countryid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(65) NOT NULL,
  `iso` varchar(3) NOT NULL,
  PRIMARY KEY (`countryid`),
  UNIQUE KEY `iso_UNIQUE` (`iso`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` VALUES (1,'Afghanistan','AFG'),(2,'Albania','ALB'),(3,'Antartica','ATA'),(4,'Algeria','DZA'),(5,'American Samoa','ASM'),(6,'Andorra','AND'),(7,'Angola','AGO'),(8,'Antigua and Barbuda','ATG'),(9,'Azerbaijan','AZE'),(10,'Argentina','ARG'),(11,'Australia','AUS'),(12,'Austria','AUT'),(13,'Bahamas','BHS'),(14,'Bahrain','BHR'),(15,'Bangladesh','BGD'),(16,'Armenia','ARM'),(17,'Barbados','BRB'),(18,'Belgium','BEL'),(19,'Bermuda','BMU'),(20,'Bhutan','BTN'),(21,'Bolivia','BOL'),(22,'Bosnia-Herzegovina','BIH'),(23,'Botswana','BWA'),(24,'Bouvet Island','BVT'),(25,'Brazil','BRA'),(26,'Belize','BLZ'),(27,'British Indian Ocean Territory','IOT'),(28,'Solomon Islands','SLB'),(29,'Virgin Isld. (British)','VGB'),(30,'Brunei','BRN'),(31,'Bulgaria','BGR'),(32,'Myanmar','MMR'),(33,'Burundi','BDI'),(34,'Belarus','BLR'),(35,'Cambodia','KHM'),(36,'Cameroon','CMR'),(37,'Canada','CAN'),(38,'Cape Verde','CPV'),(39,'Cayman Islands','CYM'),(40,'Central African Republic','CAF'),(41,'Sri Lanka','LKA'),(42,'Chad','TCD'),(43,'Chile','CHL'),(44,'China','CHN'),(45,'Taiwan','TWN'),(46,'Christmas Island','CXR'),(47,'Cocos Isld.','CCK'),(48,'Colombia','COL'),(49,'Comoros','COM'),(50,'Mayotte','MYT'),(51,'Congo, Rep.','COG'),(52,'Congo, Dem. Rep.','COD'),(53,'Cook Island','COK'),(54,'Costa Rica','CRI'),(55,'Croatia','HRV'),(56,'Cuba','CUB'),(57,'Cyprus','CYP'),(58,'Czech Republic','CZE'),(59,'Benin','BEN'),(60,'Denmark','DNK'),(61,'Dominica','DMA'),(62,'Dominican Republic','DOM'),(63,'Ecuador','ECU'),(64,'El Salvador','SLV'),(65,'Equatorial Guinea','GNQ'),(66,'Ethiopia','ETH'),(67,'Eritrea','ERI'),(68,'Estonia','EST'),(69,'Faeroe Isld.','FRO'),(70,'Falkland Isld.','FLK'),(71,'S. Georgia & S. Sandwich Isld.','SGS'),(72,'Fiji','FJI'),(73,'Finland','FIN'),(74,'France, Metrop.','FXX'),(75,'France','FRA'),(76,'French Guiana','GUF'),(77,'French Polynesia','PYF'),(78,'French S.T.','ATF'),(79,'Djibouti','DJI'),(80,'Gabon','GAB'),(81,'Georgia','GEO'),(82,'Gambia','GMB'),(83,'West Bank and Gaza','PSE'),(84,'Germany','DEU'),(85,'Ghana','GHA'),(86,'Gibraltar','GIB'),(87,'Kiribati','KIR'),(88,'Greece','GRC'),(89,'Greenland','GRL'),(90,'Grenada','GRD'),(91,'Guadeloupe','GLP'),(92,'Guam','GUM'),(93,'Guatemala','GTM'),(94,'Guinea','GIN'),(95,'Guyana','GUY'),(96,'Haiti','HTI'),(97,'Heard / McDonald Isld','HMD'),(98,'Holy See','VAT'),(99,'Honduras','HND'),(100,'Hungary','HUN'),(101,'Iceland','ISL'),(102,'India','IND'),(103,'Indonesia','IDN'),(104,'Iran, Islamic Rep.','IRN'),(105,'Iraq','IRQ'),(106,'Ireland','IRL'),(107,'Israel','ISR'),(108,'Italy','ITA'),(109,'Cote d\'Ivoire','CIV'),(110,'Jamaica','JAM'),(111,'Japan','JPN'),(112,'Kazakhstan','KAZ'),(113,'Jordan','JOR'),(114,'Kenya','KEN'),(115,'Korea, Dem. Rep.','PRK'),(116,'Korea, Rep.','KOR'),(117,'Kuwait','KWT'),(118,'Kyrgyz Republic','KGZ'),(119,'Lao PDR','LAO'),(120,'Lebanon','LBN'),(121,'Lesotho','LSO'),(122,'Latvia','LVA'),(123,'Liberia','LBR'),(124,'Libya','LBY'),(125,'Liechtenstein','LIE'),(126,'Lithuania','LTU'),(127,'Luxembourg','LUX'),(128,'Macao','MAC'),(129,'Madagascar','MDG'),(130,'Malawi','MWI'),(131,'Malaysia','MYS'),(132,'Maldives','MDV'),(133,'Mali','MLI'),(134,'Malta','MLT'),(135,'Martinique','MTQ'),(136,'Mauritania','MRT'),(137,'Mauritius','MUS'),(138,'Mexico','MEX'),(139,'Monaco','MCO'),(140,'Mongolia','MNG'),(141,'Moldova','MDA'),(142,'Montserrat','MSR'),(143,'Morocco','MAR'),(144,'Mozambique','MOZ'),(145,'Oman','OMN'),(146,'Namibia','NAM'),(147,'Nauru','NRU'),(148,'Nepal','NPL'),(149,'Netherlands','NLD'),(150,'Neth.Antilles','ANT'),(151,'Aruba','ABW'),(152,'New Caledonia','NCL'),(153,'Vanuatu','VUT'),(154,'New Zealand','NZL'),(155,'Nicaragua','NIC'),(156,'Niger','NER'),(157,'Nigeria','NGA'),(158,'Niue','NIU'),(159,'Norfolk Isld.','NFK'),(160,'Norway','NOR'),(161,'N. Mariana Isld.','MNP'),(162,'US minor outlying Islands','UMI'),(163,'Micronesia','FSM'),(164,'Marshall Isld.','MHL'),(165,'Palau','PLW'),(166,'Pakistan','PAK'),(167,'Panama','PAN'),(168,'Papua New Guinea','PNG'),(169,'Paraguay','PRY'),(170,'Peru','PER'),(171,'Philippines','PHL'),(172,'Pitcairn Island','PCN'),(173,'Poland','POL'),(174,'Portugal','PRT'),(175,'Guinea Bissau','GNB'),(176,'Timor-Leste','TLS'),(177,'Puerto Rico','PRI'),(178,'Qatar','QAT'),(179,'Romania','ROM'),(180,'Russian Federation','RUS'),(181,'Rwanda','RWA'),(182,'St. Helena','SHN'),(183,'St.Kitts and Nevis','KNA'),(184,'Anguilla','AIA'),(185,'St. Lucia','LCA'),(186,'St. Pierre and Miquelon','SPM'),(187,'St. Vincent and Grenadines','VCT'),(188,'San Marino','SMR'),(189,'SÃ£o TomÃ© and Principe','STP'),(190,'Saudi Arabia','SAU'),(191,'Senegal','SEN'),(192,'Seychelles','SYC'),(193,'Sierra Leone','SLE'),(194,'Singapore','SGP'),(195,'Slovak Republic','SVK'),(196,'Viet Nam','VNM'),(197,'Slovenia','SVN'),(198,'Somalia','SOM'),(199,'South Africa','ZAF'),(200,'Zimbabwe','ZWE'),(201,'Spain','ESP'),(202,'West. Sahara','ESH'),(203,'Sudan','SDN'),(204,'Suriname','SUR'),(205,'Svalbard and Jan Mayen Islands','SJM'),(206,'Swaziland','SWZ'),(207,'Sweden','SWE'),(208,'Switzerland','CHE'),(209,'Syrian Arab Republic','SYR'),(210,'Tajikistan','TJK'),(211,'Thailand','THA'),(212,'Togo','TGO'),(213,'Tokelau','TKL'),(214,'Tonga','TON'),(215,'Trinidad and Tobago','TTO'),(216,'United Arab Emirates','ARE'),(217,'Tunisia','TUN'),(218,'Turkey','TUR'),(219,'Turkmenistan','TKM'),(220,'Turks and Caicos Islands','TCA'),(221,'Tuvalu','TUV'),(222,'Uganda','UGA'),(223,'Ukraine','UKR'),(224,'Macedonia, FYR','MKD'),(225,'Egypt, Arab Rep.','EGY'),(226,'United Kingdom','GBR'),(227,'Tanzania','TZA'),(228,'United States','USA'),(229,'Virgin Islands, U.S.','VIR'),(230,'Burkina Faso','BFA'),(231,'Uruguay','URY'),(232,'Uzbekistan','UZB'),(233,'Venezuela, RB','VEN'),(234,'Wallis and Futuna','WLF'),(235,'Samoa','WSM'),(236,'Yemen','YEM'),(237,'Serbia and Montenegro','SCG'),(238,'Zambia','ZMB'),(239,'Westbank and Gaza','WBG'),(240,'Jerusalem','JER');

--
-- Table structure for table `vocabularies`
--

CREATE TABLE `vocabularies` (
  `vid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`vid`),
  UNIQUE KEY `idx_voc_title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Dumping data for table `vocabularies`
--

INSERT INTO `vocabularies` VALUES (1,'CESSDA Topics Classifications');

--
-- Table structure for table `lic_file_downloads`
--

CREATE TABLE `lic_file_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fileid` varchar(45) NOT NULL,
  `downloads` varchar(45) DEFAULT NULL,
  `download_limit` varchar(45) DEFAULT NULL,
  `expiry` int(11) DEFAULT NULL,
  `lastdownloaded` int(11) DEFAULT NULL,
  `requestid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT '0',
  `title` varchar(45) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` VALUES (1,0,'By Region',0),(2,1,'East Asia and Pacific',1),(3,1,'Europe and Central Asia',1),(4,1,'Latin America & the Caribbean',1),(5,1,'Middle East and North Africa',1),(6,1,'South Asia',1),(7,1,'Sub-Saharan Africa',1),(8,0,'By Income',0),(9,8,'Low-income economies',0),(10,8,'Lower-middle-income economies',1),(11,8,'Upper-middle-income economies',3),(12,8,'High-income economies',4),(13,8,'High-income OECD members',6);

--
-- Table structure for table `planned_surveys`
--

CREATE TABLE `planned_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `abbreviation` varchar(255) DEFAULT NULL,
  `studytype` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `geocoverage` varchar(255) DEFAULT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `pinvestigator` varchar(255) DEFAULT NULL,
  `producers` varchar(255) DEFAULT NULL,
  `sponsors` varchar(255) DEFAULT NULL,
  `fundingstatus` int(11) DEFAULT NULL,
  `samplesize` int(11) DEFAULT NULL,
  `sampleunit` varchar(45) DEFAULT NULL,
  `datacollstart` int(11) DEFAULT NULL,
  `datacollend` int(11) DEFAULT NULL,
  `expect_rep_date` int(11) DEFAULT NULL,
  `expect_data_policy` text,
  `expect_micro_rel_date` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) DEFAULT '0',
  `user_agent` varchar(255) DEFAULT NULL,
  `last_activity` int(11) DEFAULT '0',
  `user_data` text,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


--
-- Table structure for table `public_requests`
--

CREATE TABLE `public_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `surveyid` int(11) DEFAULT NULL,
  `abstract` text NOT NULL,
  `posted` int(11) NOT NULL,
  `request_type` varchar(45) DEFAULT 'study',
  `collectionid` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;


--
-- Table structure for table `sitelogs`
--

CREATE TABLE `sitelogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sessionid` varchar(255) NOT NULL DEFAULT '',
  `logtime` varchar(45) NOT NULL DEFAULT '0',
  `ip` varchar(45) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `logtype` varchar(45) NOT NULL,
  `surveyid` int(11) DEFAULT '0',
  `section` varchar(255) DEFAULT NULL,
  `keyword` text,
  `username` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


--
-- Table structure for table `configurations`
--

CREATE TABLE `configurations` (
  `name` varchar(200) NOT NULL,
  `value` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `helptext` varchar(255) DEFAULT NULL,
  `item_group` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `configurations`
--

INSERT INTO `configurations` VALUES ('app_version','4.0.0-05.007.2013','Application version',NULL,NULL),('cache_default_expires','7200','Cache expiry (in mili seconds)',NULL,NULL),('cache_disabled','0','Enable/disable site caching',NULL,NULL),('cache_path','cache/','Site cache folder',NULL,NULL),('catalog_records_per_page','15','Catalog search page - records per page',NULL,NULL),('catalog_root','datafiles','Survey catalog folder',NULL,NULL),('collections_vocab','2','survey collections vocabulary',NULL,NULL),('collection_search','yes',NULL,NULL,NULL),('collection_search_weight','5',NULL,NULL,NULL),('da_search','yes',NULL,NULL,NULL),('da_search_weight','2',NULL,NULL,NULL),('db_version','4.0.0-05.007.2013','Database version',NULL,NULL),('ddi_import_folder','imports','Survey catalog import folder',NULL,NULL),('default_home_page','catalog','Default home page','Default home page',NULL),('html_folder','/pages',NULL,NULL,NULL),('lang','en-us','Site Language','Site Language code',NULL),('language','english',NULL,NULL,NULL),('login_timeout','40','Login timeout (minutes)',NULL,NULL),('mail_protocol','smtp','Select method for sending emails','Supported protocols: MAIL, SMTP, SENDMAIL',NULL),('min_password_length','5','Minimum password length',NULL,NULL),('news_feed_url','http://ihsn.org/nada/index.php?q=news/feed','','',''),('regional_search','yes','Enable regional search',NULL,NULL),('regional_search_weight','3',NULL,NULL,NULL),('repository_identifier','default','Repository Identifier',NULL,NULL),('site_password_protect','no','Password protect website',NULL,NULL),('smtp_host','ihsn.org','SMTP Host name',NULL,NULL),('smtp_pass','','SMTP password',NULL,NULL),('smtp_port','25','SMTP port',NULL,NULL),('smtp_user','nada@ihsn.org','SMTP username',NULL,NULL),('theme','default','Site theme name',NULL,NULL),('topics_vocab','1','Vocabulary ID for Topics',NULL,NULL),('topic_search','yes','Topic search',NULL,NULL),('topic_search_weight','6',NULL,NULL,NULL),('use_html_editor','yes','Use HTML editor for entering HTML for static pages',NULL,NULL),('website_footer','Powered by NADA 4.0 and DDI','Website footer text',NULL,NULL),('website_title','National Data Archive','Website title','Provide the title of the website','website'),('website_url','http://localhost/nada4','Website URL','URL of the website','website'),('website_webmaster_email','mah0001@hotmail.com','Site webmaster email address','-','website'),('website_webmaster_name','noreply','Webmaster name','-','website'),('year_search','yes',NULL,NULL,NULL),('year_serach_weight','1',NULL,NULL,NULL);


--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `resource_id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
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
  `description` text,
  `abstract` text,
  `toc` text,
  `subjects` varchar(45) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `dcformat` varchar(255) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



# citations

CREATE TABLE `citations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `alt_title` varchar(255) DEFAULT NULL,
  `authors` text,
  `editors` text,
  `translators` text,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `published` tinyint(3) DEFAULT '1',
  `volume` varchar(45) DEFAULT NULL,
  `issue` varchar(45) DEFAULT NULL,
  `idnumber` varchar(45) DEFAULT NULL,
  `edition` varchar(45) DEFAULT NULL,
  `place_publication` varchar(255) DEFAULT NULL,
  `place_state` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_medium` tinyint(3) DEFAULT '0' COMMENT '0=print, 1=online',
  `url` varchar(255) DEFAULT NULL,
  `page_from` varchar(25) DEFAULT NULL,
  `page_to` varchar(25) DEFAULT NULL,
  `data_accessed` varchar(45) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `ctype` varchar(45) NOT NULL,
  `pub_day` varchar(15) DEFAULT NULL,
  `pub_month` varchar(45) DEFAULT NULL,
  `pub_year` int(11) DEFAULT NULL,
  `abstract` text,
  `keywords` text,
  `notes` text,
  `doi` varchar(255) DEFAULT NULL,
  `flag` varchar(45) DEFAULT NULL,
  `owner` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `ihsn_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


#country_aliases

CREATE TABLE `country_aliases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `countryid` int(11) NOT NULL,
  `alias` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ix_alias_uniq` (`countryid`,`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



# da_collections

CREATE TABLE `da_collections` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 COMMENT='data access by collection/set';


#  dctypes

CREATE TABLE `dctypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;


--
-- Dumping data for table `dctypes`
--

INSERT INTO dctypes (id, title) VALUES (1, 'Document, Administrative [doc/adm]');
INSERT INTO dctypes (id, title) VALUES (2, 'Document, Analytical [doc/anl]');
INSERT INTO dctypes (id, title) VALUES (3, 'Document, Other [doc/oth]');
INSERT INTO dctypes (id, title) VALUES (4, 'Document, Questionnaire [doc/qst]');
INSERT INTO dctypes (id, title) VALUES (5, 'Document, Reference [doc/ref]');
INSERT INTO dctypes (id, title) VALUES (6, 'Document, Report [doc/rep]');
INSERT INTO dctypes (id, title) VALUES (7, 'Document, Technical [doc/tec]');
INSERT INTO dctypes (id, title) VALUES (8, 'Audio [aud]');
INSERT INTO dctypes (id, title) VALUES (9, 'Database [dat]');
INSERT INTO dctypes (id, title) VALUES (10, 'Map [map]');
INSERT INTO dctypes (id, title) VALUES (11, 'Microdata File [dat/micro]');
INSERT INTO dctypes (id, title) VALUES (12, 'Photo [pic]');
INSERT INTO dctypes (id, title) VALUES (13, 'Program [prg]');
INSERT INTO dctypes (id, title) VALUES (14, 'Table [tbl]');
INSERT INTO dctypes (id, title) VALUES (15, 'Video [vid]');
INSERT INTO dctypes (id, title) VALUES (16, 'Web Site [web]');


#forms

CREATE TABLE `forms` (
  `formid` int(11) NOT NULL DEFAULT '0',
  `fname` varchar(255) DEFAULT '',
  `model` varchar(255) DEFAULT '',
  `path` varchar(255) DEFAULT '',
  `iscustom` char(2) DEFAULT '0',
  PRIMARY KEY (`formid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;



--
-- Dumping data for table `forms`
--

INSERT INTO `forms` VALUES (2,'Public use files','public','orderform.php','1'),(1,'Direct access','direct','direct.php','1'),(3,'Licensed data files','licensed','licensed.php','1'),(4,'Data accessible only in data enclave','data_enclave','Application for Access to a Data Enclave.pdf','0'),(5,'Data available from external repository','remote','remote','1'),(6,'Data not available','data_na','data_na','1');



--
-- Table structure for table `group_permissions`
--

CREATE TABLE `group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL COMMENT 'permissions bit value',
  PRIMARY KEY (`id`),
  UNIQUE KEY `grp_perms_UNIQUE` (`group_id`,`permission_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Table structure for table `lic_files_log`
--

CREATE TABLE `lic_files_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestid` int(11) NOT NULL,
  `fileid` int(11) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `created` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='licensed files download log';



--
-- Table structure for table `permission_urls`
--

CREATE TABLE `permission_urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_UNIQUE` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Data for table `permission_urls`
--

INSERT INTO `permission_urls` VALUES (1,'admin/catalog/upload',1),(4,'admin/menu',4),(5,'admin/menu/add',5),(6,'admin/menu/edit/*',6),(7,'admin/menu/add_link',7),(8,'admin/menu/menu_sort',8),(9,'admin/vocabularies',9),(10,'admin/terms/*',10),(11,'admin/users',11),(12,'admin/users/*',12),(14,'ddibrowser',14),(16,'page/*',16),(18,'citations',18),(22,'backup*',22),(23,'access_licensed*',23),(25,'switch_language*',25),(27,'translate/*',27),(34,'admin/catalog/do_upload',1),(48,'admin/datadeposit*',40),(51,'admin/catalog/delete',42),(52,'admin/catalog/export-ddi',43),(53,'admin/catalog/import-rdf',44),(54,'admin/repositories/*',45),(55,'admin/repositories',45),(88,'admin/catalog/replace_ddi/*',46),(100,'admin/catalog/edit/*',49),(101,'admin/catalog/update/*',49),(102,'admin/catalog/update',49),(103,'admin/managefiles/*',49),(104,'admin/resources/*',49),(112,'admin/catalog',2),(113,'admin/catalog/survey/*',2),(114,'admin/catalog/search',2),(116,'access_public/*',30),(119,'admin/catalog/copy_ddi',62),(124,'admin/repositories/select',61),(125,'admin/repositories/active/*',61),(126,'admin/catalog/publish',41),(127,'admin/catalog/publish/*',41),(131,'admin/catalog/copy_study',63),(132,'admin/catalog/do_copy_study/*',63),(133,'admin/citations',64),(134,'admin/citations/edit',65),(135,'admin/citations/edit/*',65),(136,'admin/citations/delete/*',66),(137,'admin/citations/import',67),(138,'admin/citations/export',68),(141,'admin',3),(142,'admin/users/exit_impersonate',3);



--
-- Table structure for table `schema_version`
--

CREATE TABLE `schema_version` (
  `version` int(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



--
-- Table structure for table `survey_aliases`
--

CREATE TABLE `survey_aliases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) unsigned NOT NULL,
  `alternate_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `survey_id` (`alternate_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Other codeBook IDs for the survey';



--
-- Table structure for table `survey_countries`
--

CREATE TABLE `survey_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `cid` int(11) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid_iso_UNIQUE` (`sid`,`country_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Dumping data for table `terms`
--

INSERT INTO `terms` VALUES (1,1,0,'Economics[1]'),(2,1,1,'consumption/consumer behaviour [1.1]'),(3,1,1,'economic conditions and indicators [1.2]'),(4,1,1,'economic policy [1.3]'),(5,1,1,'economic systems and development [1.4]'),(6,1,1,'income, property and investment/saving [1.5]'),(7,1,1,'rural economics [1.6]'),(9,1,0,'Trade, Industry and Markets [2]'),(10,1,9,'agricultural, forestry and rural industry [2.1]'),(11,1,9,'business/industrial management and organisation [2.2]'),(13,1,0,'Labour and Employment [3]'),(14,1,13,'employment [3.1]'),(15,1,13,'in-job training [3.2]'),(16,1,13,'labour relations/conflict [3.3]'),(17,1,13,'retirement [3.4]'),(18,1,13,'unemployment [3.5]'),(19,1,13,'working conditions [3.6]'),(21,1,0,'Politics [4]'),(22,1,21,'conflict, security and peace [4.1]'),(23,1,21,'domestic political issues [4.2]'),(24,1,21,'elections [4.3]'),(25,1,21,'government, political systems and organisations [4.4]'),(26,1,21,'international politics and organisations [4.5]'),(27,1,21,'mass political behaviour, attitudes/opinion [4.6]'),(28,1,21,'political ideology [4.7]'),(30,1,0,'Law, Crime and Legal Systems [5]'),(31,1,30,'crime [5.1]'),(32,1,30,'law enforcement [5.2]'),(33,1,30,'legal systems [5.3]'),(34,1,30,'legislation [5.4]'),(35,1,30,'rehabilitation/reintegration into society [5.5]'),(37,1,0,'Education [6]'),(38,1,37,'basic skills education [6.1]'),(39,1,37,'compulsory and pre-school education [6.2]'),(40,1,37,'educational policy [6.3]'),(41,1,37,'life-long/continuing education [6.4]'),(42,1,37,'post-compulsory education [6.5]'),(43,1,37,'teaching profession [6.6]'),(44,1,37,'vocational education [6.7]'),(46,1,0,'Information and Communication [7]'),(47,1,46,'advertising [7.1]'),(48,1,46,'information society [7.2]'),(49,1,46,'language and linguistics [7.3]'),(50,1,46,'mass media [7.4]'),(52,1,0,'Health [8]'),(53,1,52,'accidents and injuries [8.1]'),(54,1,52,'childbearing, family planning and abortion [8.2]'),(55,1,52,'drug abuse, alcohol and smoking [8.3]'),(56,1,52,'general health [8.4]'),(57,1,52,'health care and medical treatment [8.5]'),(58,1,52,'health policy [8.6]'),(59,1,52,'nutrition [8.7]'),(60,1,52,'physical fitness and exercise [8.8]'),(61,1,52,'specific diseases and medical conditions [8.9]'),(63,1,0,'Natural Environment [9]'),(64,1,63,'environmental degradation/pollution and protection [9.1]'),(65,1,63,'natural landscapes [9.2]'),(66,1,63,'natural resources and energy [9.3]'),(67,1,63,'plant and animal distribution [9.4]'),(69,1,0,'Housing and Land Use Planning [10]'),(70,1,69,'housing [10.1]'),(71,1,69,'land use and planning [10.2]'),(73,1,0,'Transport, Travel and Mobility [11]'),(74,1,0,'Social Stratification and Groupings [12]'),(75,1,74,'children [12.1]'),(76,1,74,'elderly [12.2]'),(77,1,74,'elites and leadership [12.3]'),(78,1,74,'equality and inequality [12.4]'),(79,1,74,'family life and marriage [12.5]'),(80,1,74,'gender and gender roles [12.6]'),(81,1,74,'minorities [12.7]'),(82,1,74,'social and occupational mobility [12.8]'),(83,1,74,'social exclusion [12.9]'),(84,1,74,'youth [12.10]'),(86,1,0,'Society and Culture [13]'),(87,1,86,'community, urban and rural life [13.1]'),(88,1,86,'cultural activities and participation [13.2]'),(89,1,86,'cultural and national identity [13.3]'),(90,1,86,'leisure, tourism and sport [13.4]'),(91,1,86,'religion and values [13.5]'),(92,1,86,'social behaviour and attitudes [13.6]'),(93,1,86,'social change [13.7]'),(94,1,86,'social conditions and indicators [13.8]'),(95,1,86,'time use [13.9]'),(97,1,0,'Demography and Population [14]'),(98,1,97,'censuses [14.1]'),(99,1,97,'fertility [14.2]'),(100,1,97,'migration [14.3]'),(101,1,97,'morbidity and mortality [14.4]'),(103,1,0,'Social Welfare Policy and Systems [15]'),(104,1,103,'social welfare policy [15.1]'),(105,1,103,'social welfare systems/structures [15.2]'),(106,1,103,'specific social services: use and provision [15.3]'),(108,1,0,'Science and Technology [16]'),(109,1,108,'biotechnology [16.1]'),(110,1,108,'information technology [16.2]'),(112,1,0,'Psychology [17]'),(113,1,0,'History [18]'),(114,1,0,'Reference and Instructional Resources [19]'),(115,1,114,'computer and simulation programs [19.1]'),(116,1,114,'reference sources [19.2]'),(117,1,114,'teaching packages and test datasets [19.3]'),(118,2,0,'Southern Africa Labour and Development Research Unit (SALDRU)'),(119,2,0,'Integrated Public Use Microdata Series (IPUMS) International'),(120,2,0,'World Bank, Poverty PPP and Modeling Consumption Patterns'),(121,2,0,'ECAPOV'),(122,2,0,'SEDLAC');



#
# TABLE STRUCTURE FOR: login_attempts
#

CREATE TABLE login_attempts (
  id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  ip_address varbinary(16) NOT NULL,
  login varchar(100) NOT NULL,
  time int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;