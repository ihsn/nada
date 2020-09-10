--
-- Table structure for table `repositories`
--

DROP TABLE IF EXISTS `repositories`;
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
) DEFAULT CHARSET=utf8;



--
-- Table structure for table `lic_files`
--

DROP TABLE IF EXISTS `lic_files`;
CREATE TABLE `lic_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `surveyid` int(11) NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `changed` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;


--
-- Table structure for table `site_menu`
--

DROP TABLE IF EXISTS `site_menu`;
CREATE TABLE `site_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `depth` int(11) DEFAULT NULL,
  `module` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `site_menu`
--

LOCK TABLES `site_menu` WRITE;
/*!40000 ALTER TABLE `site_menu` DISABLE KEYS */;
INSERT INTO `site_menu` VALUES 
(1,0,'Dashboard','admin',0,0,'admin'),
(2,0,'Studies','admin/catalog',1,0,'catalog'),
(4,0,'Citations','admin/citations',3,0,'citations'),
(5,0,'Users','admin/users',4,0,'users'),
(6,0,'Menu','admin/menu',5,0,'menu'),
(7,0,'Reports','admin/reports',6,0,'reports'),
(8,0,'Settings','admin/configurations',7,0,'configurations'),
(12,2,'-','-',70,1,'catalog'),
(13,2,'Licensed requests','admin/licensed_requests',80,1,'catalog'),
(14,2,'-','-',90,1,'catalog'),
(15,2,'Manage collections','admin/repositories',60,1,'repositories'),
(17,4,'All citations','admin/citations',100,1,'citations'),
(18,4,'Import citations','admin/citations/import',90,1,'citations'),
(19,4,'Export citations','admin/citations/export',80,1,'citations'),
(20,5,'All users','admin/users',100,1,'users'),
(21,5,'Add user','admin/users/add',99,1,'users'),
(22,5,'-','-',65,1,'users'),
(27,6,'All pages','admin/menu',0,1,'menu'),
(28,7,'All reports','admin/reports',0,1,'reports'),
(29,8,'Settings','admin/configurations',0,1,'configurations'),
(30,8,'Countries','admin/countries',0,1,'vocabularies'),
(31,8,'Regions','admin/regions',0,1,'vocabularies'),
(32,8,'-','-',0,1,'vocabularies'),
(33,8,'Vocabularies','admin/vocabularies',-9,1,'vocabularies'),
(34,2,'Manage studies','admin/catalog',100,1,'catalog'),
(35,5,'Impersonate user','admin/users/impersonate',50,1,'users'),
(101,8,'Translate','admin/translate',0,1,'translate');

insert into site_menu(pid,title,url,weight,depth,module) 
	values (2,'-', '-',50,1,'catalog');
	
insert into site_menu(pid,title,url,weight,depth,module) 
	values (2,'Bulk access collections', 'admin/da_collections',40,1,'catalog');


/*!40000 ALTER TABLE `site_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vocabularies`
--

DROP TABLE IF EXISTS `vocabularies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vocabularies` (
  `vid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`vid`),
  UNIQUE KEY `idx_voc_title` (`title`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `variables`
--

DROP TABLE IF EXISTS `variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variables` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `fid` varchar(45) DEFAULT NULL,
  `vid` varchar(45) DEFAULT '',
  `name` varchar(100) DEFAULT '',
  `labl` varchar(255) DEFAULT '',
  `qstn` text,
  `catgry` text,
  `metadata` mediumtext,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `idxSurvey` (`vid`,`sid`),
  KEY `idxsurveyidfk` (`sid`),
  FULLTEXT KEY `idx_qstn` (`qstn`),
  FULLTEXT KEY `idx_labl` (`labl`),
  FULLTEXT KEY `idxCatgry` (`catgry`),
  FULLTEXT KEY `idx_nm_lbl_qstn` (`name`,`labl`,`qstn`),
  FULLTEXT KEY `idx_nm_lbl_cat_qstn` (`name`,`labl`,`catgry`,`qstn`),
  FULLTEXT KEY `idx_nm` (`name`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `variable_groups`
--

DROP TABLE IF EXISTS `variable_groups`;

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


--
-- Table structure for table `users_groups`
--

DROP TABLE IF EXISTS `users_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `group_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_group_UNQ` (`user_id`,`group_id`)
) AUTO_INCREMENT=148 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_relationships`
--

DROP TABLE IF EXISTS `survey_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid_1` int(11) DEFAULT NULL,
  `sid_2` int(11) DEFAULT NULL,
  `relationship_id` int(11) DEFAULT NULL,
  `pair_id` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pair` (`pair_id`)
) DEFAULT CHARSET=utf8 COMMENT='related surveys e.g. parent, child, sibling, related';
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `survey_tags`
--

DROP TABLE IF EXISTS `survey_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tag` (`sid`,`tag`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `meta`
--

DROP TABLE IF EXISTS `meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(30) NOT NULL,
  `login` varchar(100) NOT NULL,
  `time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `repository_sections`
--

DROP TABLE IF EXISTS `repository_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repository_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `weight` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repository_sections`
--

LOCK TABLES `repository_sections` WRITE;
/*!40000 ALTER TABLE `repository_sections` DISABLE KEYS */;
INSERT INTO `repository_sections` VALUES (2,'Regional Collections',5),(3,'Specialized Collections',10);
/*!40000 ALTER TABLE `repository_sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_topics`
--

DROP TABLE IF EXISTS `survey_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_topics` (
  `sid` int(11) NOT NULL,
  `tid` int(11) NOT NULL,
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `Idx_uniq` (`tid`,`sid`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `survey_citations`
--

DROP TABLE IF EXISTS `survey_citations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_citations` (
  `sid` int(11) DEFAULT NULL,
  `citationid` int(11) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Idx_s_c` (`sid`,`citationid`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `dcformats`
--

DROP TABLE IF EXISTS `dcformats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dcformats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dcformats`
--

LOCK TABLES `dcformats` WRITE;
/*!40000 ALTER TABLE `dcformats` DISABLE KEYS */;
INSERT INTO `dcformats` VALUES (1,'Compressed, Generic [application/x-compressed]'),(2,'Compressed, ZIP [application/zip]'),(3,'Data, CSPro [application/x-cspro]'),(4,'Data, dBase [application/dbase]'),(5,'Data, Microsoft Access [application/msaccess]'),(6,'Data, SAS [application/x-sas]'),(7,'Data, SPSS [application/x-spss]'),(8,'Data, Stata [application/x-stata]'),(9,'Document, Generic [text]'),(10,'Document, HTML [text/html]'),(11,'Document, Microsoft Excel [application/msexcel]'),(12,'Document, Microsoft PowerPoint [application/mspowerpoint'),(13,'Document, Microsoft Word [application/msword]'),(14,'Document, PDF [application/pdf]'),(15,'Document, Postscript [application/postscript]'),(16,'Document, Plain [text/plain]'),(17,'Document, WordPerfect [text/wordperfect]'),(18,'Image, GIF [image/gif]'),(19,'Image, JPEG [image/jpeg]'),(20,'Image, PNG [image/png]'),(21,'Image, TIFF [image/tiff]');
/*!40000 ALTER TABLE `dcformats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_repo_access`
--

DROP TABLE IF EXISTS `group_repo_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_repo_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `repo_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grp_repo_UNIQUE` (`group_id`,`repo_id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `surveys`
--

DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idno` varchar(200) NOT NULL,
  `type` varchar(15) DEFAULT NULL,
  `repositoryid` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `abbreviation` varchar(45) DEFAULT NULL,
  `authoring_entity` text,
  `nation` varchar(150) DEFAULT '',
  `year_start` int(11) DEFAULT '0',
  `year_end` int(11) DEFAULT '0',
  `metafile` varchar(255) DEFAULT NULL,
  `dirpath` varchar(255) DEFAULT NULL,
  `varcount` int(11) DEFAULT NULL,
  `link_technical` varchar(255) DEFAULT NULL COMMENT 'documentation',
  `link_study` varchar(255) DEFAULT NULL COMMENT 'study website',
  `link_report` varchar(255) DEFAULT NULL COMMENT 'reports',
  `link_indicator` varchar(255) DEFAULT NULL COMMENT 'indicators',
  `link_questionnaire` varchar(255) DEFAULT NULL,
  `formid` int(11) DEFAULT NULL,
  `keywords` text,
  `link_da` varchar(255) DEFAULT NULL,
  `published` tinyint(4) DEFAULT NULL,
  `total_views` int(11) DEFAULT '0',
  `total_downloads` int(11) DEFAULT '0',
  `stats_last_updated` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `metadata` mediumtext,
  `variable_data` mediumtext,
  `ts_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `thumbnail` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `surveyid_UNIQUE` (`idno`),
  UNIQUE KEY `idx_srvy_unq` (`idno`,`repositoryid`),
  FULLTEXT KEY `ft_titl` (`title`),
  FULLTEXT KEY `ft_all` (`title`,`authoring_entity`,`nation`,`abbreviation`,`keywords`,`idno`),
  FULLTEXT KEY `ft_keywords` (`keywords`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `dctypes`
--

DROP TABLE IF EXISTS `dctypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dctypes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dctypes`
--

LOCK TABLES `dctypes` WRITE;
/*!40000 ALTER TABLE `dctypes` DISABLE KEYS */;
INSERT INTO `dctypes` VALUES (1,'Document, Administrative [doc/adm]'),(2,'Document, Analytical [doc/anl]'),(3,'Document, Other [doc/oth]'),(4,'Document, Questionnaire [doc/qst]'),(5,'Document, Reference [doc/ref]'),(6,'Document, Report [doc/rep]'),(7,'Document, Technical [doc/tec]'),(8,'Audio [aud]'),(9,'Database [dat]'),(10,'Map [map]'),(11,'Microdata File [dat/micro]'),(12,'Photo [pic]'),(13,'Program [prg]'),(14,'Table [tbl]'),(15,'Video [vid]'),(16,'Web Site [web]');
/*!40000 ALTER TABLE `dctypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `da_collections`
--

DROP TABLE IF EXISTS `da_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `da_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COMMENT='data access by collection/set';
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uid` varchar(100) CHARACTER SET utf8 NOT NULL,
  `data` text,
  `created` int(11) DEFAULT NULL,
  `expiry` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_UNIQUE` (`uid`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forms` (
  `formid` int(11) NOT NULL DEFAULT '0',
  `fname` varchar(255) DEFAULT '',
  `model` varchar(255) DEFAULT '',
  `path` varchar(255) DEFAULT '',
  `iscustom` char(2) DEFAULT '0',
  PRIMARY KEY (`formid`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES 
(2,'Public use files','public','orderform.php','1'),
(1,'Direct access','direct','direct.php','1'),
(3,'Licensed data files','licensed','licensed.php','1'),
(4,'Data accessible only in data enclave','data_enclave','Application for Access to a Data Enclave.pdf','0'),
(5,'Data available from external repository','remote','remote','1'),
(6,'Data not available','data_na','data_na','1'),
(7,'Open access','open','open','1');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_requests`
--

CREATE TABLE `lic_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `request_title` varchar(300),
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
  `additional_info` text,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `citations`
--

DROP TABLE IF EXISTS `citations`;
CREATE TABLE `citations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(50) NOT NULL,
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
  `url_status` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `attachment` varchar(300) DEFAULT NULL,
  `lang` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_UNIQUE` (`uuid`),
  FULLTEXT KEY `ft_citations` (`title`,`subtitle`,`alt_title`,`authors`,`editors`,`translators`),
  FULLTEXT KEY `ft_cit2` (`title`,`subtitle`,`authors`,`organization`,`abstract`,`keywords`,`notes`,`doi`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;





--
-- Table structure for table `permission_urls`
--

DROP TABLE IF EXISTS `permission_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission_urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) DEFAULT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url_UNIQUE` (`url`)
) AUTO_INCREMENT=150 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_urls`
--

LOCK TABLES `permission_urls` WRITE;
/*!40000 ALTER TABLE `permission_urls` DISABLE KEYS */;
INSERT INTO `permission_urls` VALUES (1,'admin/catalog/upload',1),(4,'admin/menu',4),(5,'admin/menu/add',5),(6,'admin/menu/edit/*',6),(7,'admin/menu/add_link',7),(8,'admin/menu/menu_sort',8),(9,'admin/vocabularies',9),(10,'admin/terms/*',10),(12,'admin/users/*',12),(14,'ddibrowser',14),(16,'page/*',16),(18,'citations',18),(22,'backup*',22),(23,'access_licensed*',23),(25,'switch_language*',25),(27,'translate/*',27),(34,'admin/catalog/do_upload',1),(48,'admin/datadeposit*',40),(51,'admin/catalog/delete',42),(52,'admin/catalog/export-ddi',43),(53,'admin/catalog/import-rdf',44),(54,'admin/repositories/*',45),(55,'admin/repositories',45),(88,'admin/catalog/replace_ddi/*',46),(100,'admin/catalog/edit/*',49),(101,'admin/catalog/update/*',49),(102,'admin/catalog/update',49),(103,'admin/managefiles/*',49),(104,'admin/resources/*',49),(112,'admin/catalog',2),(113,'admin/catalog/survey/*',2),(114,'admin/catalog/search',2),(116,'access_public/*',30),(119,'admin/catalog/copy_ddi',62),(124,'admin/repositories/select',61),(125,'admin/repositories/active/*',61),(126,'admin/catalog/publish',41),(127,'admin/catalog/publish/*',41),(131,'admin/catalog/copy_study',63),(132,'admin/catalog/do_copy_study/*',63),(133,'admin/citations',64),(134,'admin/citations/edit',65),(135,'admin/citations/edit/*',65),(136,'admin/citations/delete/*',66),(137,'admin/citations/import',67),(138,'admin/citations/export',68),(141,'admin',3),(142,'admin/users/exit_impersonate',3),(143,'admin/licensed_requests',69),(145,'admin/licensed_requests/*',70),(147,'admin/users',11),(148,'admin/reports/*',71),(149,'admin/reports',71);
/*!40000 ALTER TABLE `permission_urls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_aliases`
--

DROP TABLE IF EXISTS `survey_aliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_aliases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10)  NOT NULL,
  `alternate_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `survey_id` (`alternate_id`)
) DEFAULT CHARSET=utf8;




--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `lic_files_log`
--

DROP TABLE IF EXISTS `lic_files_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lic_files_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requestid` int(11) NOT NULL,
  `fileid` int(11) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `created` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COMMENT='licensed files download log';
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `terms`
--

DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terms` (
  `tid` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`tid`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `otp_code` varchar(45) DEFAULT NULL,
  `otp_expiry` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `survey_countries`
--

DROP TABLE IF EXISTS `survey_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `cid` int(11) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sid_iso_UNIQUE` (`sid`,`country_name`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `country_aliases`
--

DROP TABLE IF EXISTS `country_aliases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `country_aliases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `countryid` int(11) NOT NULL,
  `alias` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ix_alias_uniq` (`countryid`,`alias`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `group_permissions`
--

DROP TABLE IF EXISTS `group_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL COMMENT 'permissions bit value',
  PRIMARY KEY (`id`),
  UNIQUE KEY `grp_perms_UNIQUE` (`group_id`,`permission_id`)
) AUTO_INCREMENT=340 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_permissions`
--

LOCK TABLES `group_permissions` WRITE;
/*!40000 ALTER TABLE `group_permissions` DISABLE KEYS */;
INSERT INTO `group_permissions` VALUES (5,1,2),(6,1,14),(292,3,1),(289,3,2),(301,3,3),(299,3,14),(293,3,41),(295,3,42),(296,3,43),(297,3,44),(291,3,46),(294,3,49),(300,3,61),(290,3,62),(298,3,63),(334,4,2),(339,4,3),(335,4,16),(338,4,61),(336,4,69),(337,4,70),(313,5,3),(312,5,71),(287,9,2),(288,9,63),(227,10,2),(229,10,3),(228,10,45);
/*!40000 ALTER TABLE `group_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_repos`
--

DROP TABLE IF EXISTS `survey_repos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_repos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10)  NOT NULL,
  `repositoryid` varchar(255) NOT NULL,
  `isadmin` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Dumping data for table `survey_repos`
--

LOCK TABLES `survey_repos` WRITE;
/*!40000 ALTER TABLE `survey_repos` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_repos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repo_perms_urls`
--

DROP TABLE IF EXISTS `repo_perms_urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_perms_urls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repo_pg_id` int(11) DEFAULT NULL COMMENT 'repo permission group id',
  `url` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=69 DEFAULT CHARSET=utf8 COMMENT='list of URLs defining a permission group for collections';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repo_perms_urls`
--

LOCK TABLES `repo_perms_urls` WRITE;
/*!40000 ALTER TABLE `repo_perms_urls` DISABLE KEYS */;
INSERT INTO `repo_perms_urls` VALUES (5,2,'admin/catalog/copy_ddi'),(6,2,'admin/catalog/copy_study'),(7,2,'admin/catalog/delete'),(8,2,'admin/catalog/do_copy_study/*'),(9,2,'admin/catalog/do_upload'),(10,2,'admin/catalog/edit/*'),(11,2,'admin/catalog/export-ddi'),(12,2,'admin/catalog/import-rdf'),(15,2,'admin/catalog/repladce_ddi/*'),(16,2,'admin/catalog/search'),(17,2,'admin/catalog/survey/*'),(18,2,'admin/catalog/update'),(19,2,'admin/catalog/update/*'),(20,2,'admin/catalog/upload'),(28,3,'admin/licensed_requests'),(29,3,'admin/licensed_requests/*'),(30,2,'admin/managefiles/*'),(41,2,'admin/resources/*'),(64,1,'admin/catalog/*'),(67,2,'admin/pdf_generator/*'),
(68,1,'admin/pdf_generator/*'),
(69,1,'admin/catalog/add_study'),
(70,1,'admin/catalog/batch_import'),
(71,1,'admin/catalog/refresh/*');
/*!40000 ALTER TABLE `repo_perms_urls` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) AUTO_INCREMENT=58 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES 
(53,'catalog','Microdata  Catalog','',1,'0',1300807037,1,1,0),
(55,'citations','Citations',NULL,1,'0',1281460217,1,2,0),
(56,'home','Home',NULL,1,'0',1281460217,1,0,0);
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `url_mappings`
--

DROP TABLE IF EXISTS `url_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `url_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(255) DEFAULT NULL,
  `target` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `id` tinyint(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `group_type` varchar(40) DEFAULT NULL,
  `access_type` varchar(45) DEFAULT NULL,
  `weight` int(11) DEFAULT '0',
  `is_collection_group` tinyint(4) DEFAULT '0' COMMENT 'does group control collection access? 1=yes',
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES 
(1,'admin','It is the site administrator and has access to all site content','admin','unlimited',0,0),
(2,'user','General user account with no access to site administration','user','none',-99,0),
(3,'Collection administrators','Users can manage and review studies for collections they are assigned to','admin','limited',0,1),
(5,'Report viewer','Can only generate/view reports','admin','limited',0,0),
(11,'Citation manager','has full control over the citations','admin','limited',0,0),
(12,'Global Licensed Reviewer','This account can review licensed data requests from all collections','admin','limited',0,0);
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_relationship_types`
--

DROP TABLE IF EXISTS `survey_relationship_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_relationship_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rel_group_id` int(11) DEFAULT NULL,
  `rel_name` varchar(45) DEFAULT NULL,
  `rel_dir` tinyint(4) DEFAULT NULL,
  `rel_cordinality` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_relationship_types`
--

LOCK TABLES `survey_relationship_types` WRITE;
/*!40000 ALTER TABLE `survey_relationship_types` DISABLE KEYS */;
INSERT INTO `survey_relationship_types` VALUES (0,0,'isRelatedTo',0,'1:1'),(1,1,'isHarmonized',0,'N:1'),(2,1,'isMasterOf',1,'1:N'),(3,3,'isParentOf ',0,'1:N'),(4,3,'isChildOf',1,'N:1'),(5,5,'isAnnoynimizedVersionOf ',0,'N:1'),(6,5,'isMasterOf',1,NULL),(7,7,'isSubsetOf ',0,NULL),(8,7,'isMasterOf',1,NULL),(9,9,'containsStandardizedVersion ',0,NULL),(10,9,'isOriginalVersion',1,NULL),(11,11,'isWaveOf',2,'1:1'),(13,13,'isRevisedVersionOf',0,NULL),(14,13,'isOlderVersionOf',1,NULL);
/*!40000 ALTER TABLE `survey_relationship_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lic_requests_history`
--

DROP TABLE IF EXISTS `lic_requests_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lic_requests_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lic_req_id` int(11) DEFAULT NULL,
  `user_id` varchar(100) DEFAULT NULL,
  `logtype` varchar(45) DEFAULT NULL,
  `request_status` varchar(45) DEFAULT NULL,
  `description` text,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `da_collection_surveys`
--

DROP TABLE IF EXISTS `da_collection_surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `da_collection_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT NULL,
  `sid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unq_coll_sid` (`cid`,`sid`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_UNIQUE` (`tag`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(45) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `section` varchar(45) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=72 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Upload DDI file','this is a test description','catalog',3),(2,'View catalog','this is a test description','catalog',0),(3,'Access site administration','this is a test description','site_admin',0),(4,'Access Menus','this is a test description','menu_admin',0),(5,'Add menu page','this is a test description','menu_admin',0),(6,'Edit menu','this is a test description','menu_admin',0),(7,'Add menu link','this is a test description','menu_admin',0),(8,'Sort menu items','this is a test description','menu_admin',0),(9,'Access vocabularies','this is a test description','vocab',0),(10,'Access vocabulary terms','this is a test description','vocab',0),(11,'View user accounts','View list of all user accounts','user_admin',0),(12,'Edit user information','this is a test description','user_admin',0),(14,'Access DDI Browser','this is a test description','ddibrowser',0),(16,'Access site pages','this is a test description','general_site',0),(18,'View citations','this is a test description','general_site',0),(22,'Site backup','this is a test description','site_admin',0),(23,'View licensed request form','this is a test description','general_site',0),(25,'Switch site language','this is a test description','general_site',0),(27,'Translate site','this is a test description','site_admin',0),(30,'Public use files','this is a test description','general_site',0),(40,'Data Deposit','Data Deposit','site_admin',0),(41,'Publish/Unpublish study','Allows publishing study','catalog',3),(42,'Delete Study','delete study','catalog',4),(43,'Export DDI','Export','catalog',5),(44,'Import RDF','Import RDF for study resources','catalog',5),(45,'Manage Repositories','Manage repositories','repositories',9),(46,'Replace DDI','Replace a DDI file','catalog',3),(49,'Edit survey','Edit survey','catalog',4),(61,'Select collection','','repositories',1),(62,'Copy DDI','copy DDI','catalog',0),(63,'Copy studies from other collections','','catalog',6),(64,'View citations','','citation',1),(65,'Edit citation','','citation',2),(66,'Delete citation','Delete a citation','citation',3),(67,'Import citations','','citation',4),(68,'Export citations','Export citations to various formats','citation',5),(69,'View licensed requests','View list of licensed data requests','Licensed requests',0),(70,'Edit request','Edit a licensed data request','Licensed requests',1),(71,'Reports','View and generate admin reports','reports',0);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_years`
--

DROP TABLE IF EXISTS `survey_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_years` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `data_coll_year` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_sid_year` (`sid`,`data_coll_year`),
  KEY `idx_sid` (`sid`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `region_countries`
--

DROP TABLE IF EXISTS `region_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `region_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `region_countries`
--

LOCK TABLES `region_countries` WRITE;
/*!40000 ALTER TABLE `region_countries` DISABLE KEYS */;
INSERT INTO `region_countries` VALUES (309,2,5),(310,2,35),(311,2,44),(312,2,72),(313,2,103),(314,2,87),(315,2,115),(316,2,119),(317,2,131),(318,2,164),(319,2,163),(320,2,140),(321,2,32),(322,2,165),(323,2,168),(324,2,171),(325,2,235),(326,2,211),(327,2,176),(328,2,214),(329,2,221),(330,2,153),(331,2,196),(353,4,8),(354,4,10),(355,4,26),(356,4,21),(357,4,25),(358,4,43),(359,4,48),(360,4,54),(361,4,56),(362,4,61),(363,4,62),(364,4,63),(365,4,64),(366,4,90),(367,4,93),(368,4,95),(369,4,96),(370,4,99),(371,4,110),(372,4,138),(373,4,155),(374,4,167),(375,4,169),(376,4,170),(377,4,185),(378,4,187),(379,4,204),(380,4,231),(381,4,233),(382,5,4),(383,5,79),(384,5,225),(385,5,104),(386,5,105),(387,5,113),(388,5,120),(389,5,124),(390,5,143),(391,5,209),(392,5,217),(393,5,83),(394,5,236),(395,6,1),(396,6,15),(397,6,20),(398,6,102),(399,6,132),(400,6,148),(401,6,166),(402,6,41),(403,7,7),(404,7,59),(405,7,23),(406,7,230),(407,7,33),(408,7,36),(409,7,38),(410,7,40),(411,7,42),(412,7,49),(413,7,52),(414,7,51),(415,7,109),(416,7,67),(417,7,66),(418,7,80),(419,7,82),(420,7,85),(421,7,94),(422,7,175),(423,7,114),(424,7,121),(425,7,123),(426,7,129),(427,7,130),(428,7,133),(429,7,136),(430,7,137),(431,7,144),(432,7,146),(433,7,156),(434,7,157),(435,7,181),(436,7,189),(437,7,191),(438,7,192),(439,7,193),(440,7,198),(441,7,199),(442,7,203),(443,7,206),(444,7,227),(445,7,212),(446,7,222),(447,7,238),(448,7,200),(449,3,2),(450,3,16),(451,3,9),(452,3,34),(453,3,22),(454,3,31),(455,3,81),(456,3,112),(457,3,118),(458,3,122),(459,3,126),(460,3,224),(461,3,141),(462,3,179),(463,3,180),(464,3,237),(465,3,210),(466,3,218),(467,3,219),(468,3,223),(469,3,232),(470,9,1),(471,9,15),(472,9,59),(473,9,230),(474,9,33),(475,9,35),(476,9,40),(477,9,42),(478,9,49),(479,9,52),(480,9,67),(481,9,66),(482,9,82),(483,9,94),(484,9,175),(485,9,96),(486,9,114),(487,9,115),(488,9,118),(489,9,123),(490,9,129),(491,9,130),(492,9,133),(493,9,136),(494,9,144),(495,9,32),(496,9,148),(497,9,156),(498,9,181),(499,9,193),(500,9,198),(501,9,210),(502,9,227),(503,9,212),(504,9,222),(505,9,200),(557,10,2),(558,10,16),(559,10,26),(560,10,20),(561,10,21),(562,10,36),(563,10,38),(564,10,51),(565,10,109),(566,10,79),(567,10,225),(568,10,64),(569,10,72),(570,10,81),(571,10,85),(572,10,93),(573,10,95),(574,10,99),(575,10,102),(576,10,103),(577,10,105),(578,10,87),(579,10,119),(580,10,121),(581,10,164),(582,10,163),(583,10,141),(584,10,140),(585,10,143),(586,10,155),(587,10,157),(588,10,166),(589,10,168),(590,10,169),(591,10,171),(592,10,235),(593,10,189),(594,10,191),(595,10,28),(596,10,41),(597,10,203),(598,10,206),(599,10,209),(600,10,176),(601,10,214),(602,10,223),(603,10,232),(604,10,153),(605,10,196),(606,10,83),(607,10,236),(608,10,238),(609,11,4),(610,11,5),(611,11,7),(612,11,8),(613,11,10),(614,11,9),(615,11,34),(616,11,22),(617,11,23),(618,11,25),(619,11,31),(620,11,43),(621,11,44),(622,11,48),(623,11,54),(624,11,56),(625,11,61),(626,11,62),(627,11,63),(628,11,90),(629,11,104),(630,11,110),(631,11,113),(632,11,112),(633,11,122),(634,11,120),(635,11,124),(636,11,126),(637,11,224),(638,11,131),(639,11,132),(640,11,137),(641,11,138),(642,11,146),(643,11,165),(644,11,167),(645,11,170),(646,11,179),(647,11,180),(648,11,192),(649,11,199),(650,11,185),(651,11,187),(652,11,204),(653,11,211),(654,11,217),(655,11,218),(656,11,219),(657,11,221),(658,11,231),(659,11,233),(660,12,6),(661,12,151),(662,12,11),(663,12,12),(664,12,13),(665,12,14),(666,12,17),(667,12,18),(668,12,19),(669,12,30),(670,12,37),(671,12,39),(672,12,55),(673,12,57),(674,12,58),(675,12,60),(676,12,65),(677,12,68),(678,12,69),(679,12,73),(680,12,75),(681,12,77),(682,12,84),(683,12,88),(684,12,89),(685,12,92),(686,12,100),(687,12,101),(688,12,107),(689,12,108),(690,12,111),(691,12,116),(692,12,117),(693,12,125),(694,12,127),(695,12,128),(696,12,134),(697,12,139),(698,12,149),(699,12,152),(700,12,154),(701,12,160),(702,12,145),(703,12,173),(704,12,174),(705,12,177),(706,12,178),(707,12,188),(708,12,190),(709,12,194),(710,12,195),(711,12,197),(712,12,201),(713,12,183),(714,12,207),(715,12,208),(716,12,215),(717,12,220),(718,12,216),(719,12,226),(720,12,228),(721,12,229),(722,13,11),(723,13,12),(724,13,18),(725,13,37),(726,13,58),(727,13,60),(728,13,68),(729,13,73),(730,13,75),(731,13,84),(732,13,88),(733,13,100),(734,13,101),(735,13,106),(736,13,107),(737,13,108),(738,13,111),(739,13,116),(740,13,127),(741,13,149),(742,13,154),(743,13,160),(744,13,173),(745,13,174),(746,13,195),(747,13,197),(748,13,201),(749,13,207),(750,13,208),(751,13,226),(752,13,228);
/*!40000 ALTER TABLE `region_countries` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `survey_notes`
--

DROP TABLE IF EXISTS `survey_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sid` int(10) NOT NULL,
  `note` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  `created` int(11) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;



--
-- Table structure for table `citation_authors`
--

DROP TABLE IF EXISTS `citation_authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `citation_authors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `initial` varchar(255) DEFAULT NULL,
  `author_type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cit_auth` (`cid`,`author_type`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `countryid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(65) NOT NULL,
  `iso` varchar(3) NOT NULL,
  PRIMARY KEY (`countryid`),
  UNIQUE KEY `iso_UNIQUE` (`iso`)
) AUTO_INCREMENT=241 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'Afghanistan','AFG'),(2,'Albania','ALB'),(3,'Antartica','ATA'),(4,'Algeria','DZA'),(5,'American Samoa','ASM'),(6,'Andorra','AND'),(7,'Angola','AGO'),(8,'Antigua and Barbuda','ATG'),(9,'Azerbaijan','AZE'),(10,'Argentina','ARG'),(11,'Australia','AUS'),(12,'Austria','AUT'),(13,'Bahamas','BHS'),(14,'Bahrain','BHR'),(15,'Bangladesh','BGD'),(16,'Armenia','ARM'),(17,'Barbados','BRB'),(18,'Belgium','BEL'),(19,'Bermuda','BMU'),(20,'Bhutan','BTN'),(21,'Bolivia','BOL'),(22,'Bosnia-Herzegovina','BIH'),(23,'Botswana','BWA'),(24,'Bouvet Island','BVT'),(25,'Brazil','BRA'),(26,'Belize','BLZ'),(27,'British Indian Ocean Territory','IOT'),(28,'Solomon Islands','SLB'),(29,'Virgin Isld. (British)','VGB'),(30,'Brunei','BRN'),(31,'Bulgaria','BGR'),(32,'Myanmar','MMR'),(33,'Burundi','BDI'),(34,'Belarus','BLR'),(35,'Cambodia','KHM'),(36,'Cameroon','CMR'),(37,'Canada','CAN'),(38,'Cape Verde','CPV'),(39,'Cayman Islands','CYM'),(40,'Central African Republic','CAF'),(41,'Sri Lanka','LKA'),(42,'Chad','TCD'),(43,'Chile','CHL'),(44,'China','CHN'),(45,'Taiwan','TWN'),(46,'Christmas Island','CXR'),(47,'Cocos Isld.','CCK'),(48,'Colombia','COL'),(49,'Comoros','COM'),(50,'Mayotte','MYT'),(51,'Congo, Rep.','COG'),(52,'Congo, Dem. Rep.','COD'),(53,'Cook Island','COK'),(54,'Costa Rica','CRI'),(55,'Croatia','HRV'),(56,'Cuba','CUB'),(57,'Cyprus','CYP'),(58,'Czech Republic','CZE'),(59,'Benin','BEN'),(60,'Denmark','DNK'),(61,'Dominica','DMA'),(62,'Dominican Republic','DOM'),(63,'Ecuador','ECU'),(64,'El Salvador','SLV'),(65,'Equatorial Guinea','GNQ'),(66,'Ethiopia','ETH'),(67,'Eritrea','ERI'),(68,'Estonia','EST'),(69,'Faeroe Isld.','FRO'),(70,'Falkland Isld.','FLK'),(71,'S. Georgia & S. Sandwich Isld.','SGS'),(72,'Fiji','FJI'),(73,'Finland','FIN'),(74,'France, Metrop.','FXX'),(75,'France','FRA'),(76,'French Guiana','GUF'),(77,'French Polynesia','PYF'),(78,'French S.T.','ATF'),(79,'Djibouti','DJI'),(80,'Gabon','GAB'),(81,'Georgia','GEO'),(82,'Gambia','GMB'),(83,'West Bank and Gaza','PSE'),(84,'Germany','DEU'),(85,'Ghana','GHA'),(86,'Gibraltar','GIB'),(87,'Kiribati','KIR'),(88,'Greece','GRC'),(89,'Greenland','GRL'),(90,'Grenada','GRD'),(91,'Guadeloupe','GLP'),(92,'Guam','GUM'),(93,'Guatemala','GTM'),(94,'Guinea','GIN'),(95,'Guyana','GUY'),(96,'Haiti','HTI'),(97,'Heard / McDonald Isld','HMD'),(98,'Holy See','VAT'),(99,'Honduras','HND'),(100,'Hungary','HUN'),(101,'Iceland','ISL'),(102,'India','IND'),(103,'Indonesia','IDN'),(104,'Iran, Islamic Rep.','IRN'),(105,'Iraq','IRQ'),(106,'Ireland','IRL'),(107,'Israel','ISR'),(108,'Italy','ITA'),(109,'Cote d\'Ivoire','CIV'),(110,'Jamaica','JAM'),(111,'Japan','JPN'),(112,'Kazakhstan','KAZ'),(113,'Jordan','JOR'),(114,'Kenya','KEN'),(115,'Korea, Dem. Rep.','PRK'),(116,'Korea, Rep.','KOR'),(117,'Kuwait','KWT'),(118,'Kyrgyz Republic','KGZ'),(119,'Lao PDR','LAO'),(120,'Lebanon','LBN'),(121,'Lesotho','LSO'),(122,'Latvia','LVA'),(123,'Liberia','LBR'),(124,'Libya','LBY'),(125,'Liechtenstein','LIE'),(126,'Lithuania','LTU'),(127,'Luxembourg','LUX'),(128,'Macao','MAC'),(129,'Madagascar','MDG'),(130,'Malawi','MWI'),(131,'Malaysia','MYS'),(132,'Maldives','MDV'),(133,'Mali','MLI'),(134,'Malta','MLT'),(135,'Martinique','MTQ'),(136,'Mauritania','MRT'),(137,'Mauritius','MUS'),(138,'Mexico','MEX'),(139,'Monaco','MCO'),(140,'Mongolia','MNG'),(141,'Moldova','MDA'),(142,'Montserrat','MSR'),(143,'Morocco','MAR'),(144,'Mozambique','MOZ'),(145,'Oman','OMN'),(146,'Namibia','NAM'),(147,'Nauru','NRU'),(148,'Nepal','NPL'),(149,'Netherlands','NLD'),(150,'Neth.Antilles','ANT'),(151,'Aruba','ABW'),(152,'New Caledonia','NCL'),(153,'Vanuatu','VUT'),(154,'New Zealand','NZL'),(155,'Nicaragua','NIC'),(156,'Niger','NER'),(157,'Nigeria','NGA'),(158,'Niue','NIU'),(159,'Norfolk Isld.','NFK'),(160,'Norway','NOR'),(161,'N. Mariana Isld.','MNP'),(162,'US minor outlying Islands','UMI'),(163,'Micronesia','FSM'),(164,'Marshall Isld.','MHL'),(165,'Palau','PLW'),(166,'Pakistan','PAK'),(167,'Panama','PAN'),(168,'Papua New Guinea','PNG'),(169,'Paraguay','PRY'),(170,'Peru','PER'),(171,'Philippines','PHL'),(172,'Pitcairn Island','PCN'),(173,'Poland','POL'),(174,'Portugal','PRT'),(175,'Guinea Bissau','GNB'),(176,'Timor-Leste','TLS'),(177,'Puerto Rico','PRI'),(178,'Qatar','QAT'),(179,'Romania','ROM'),(180,'Russian Federation','RUS'),(181,'Rwanda','RWA'),(182,'St. Helena','SHN'),(183,'St.Kitts and Nevis','KNA'),(184,'Anguilla','AIA'),(185,'St. Lucia','LCA'),(186,'St. Pierre and Miquelon','SPM'),(187,'St. Vincent and Grenadines','VCT'),(188,'San Marino','SMR'),(189,'São Tomé and Príncipe','STP'),(190,'Saudi Arabia','SAU'),(191,'Senegal','SEN'),(192,'Seychelles','SYC'),(193,'Sierra Leone','SLE'),(194,'Singapore','SGP'),(195,'Slovak Republic','SVK'),(196,'Viet Nam','VNM'),(197,'Slovenia','SVN'),(198,'Somalia','SOM'),(199,'South Africa','ZAF'),(200,'Zimbabwe','ZWE'),(201,'Spain','ESP'),(202,'West. Sahara','ESH'),(203,'Sudan','SDN'),(204,'Suriname','SUR'),(205,'Svalbard and Jan Mayen Islands','SJM'),(206,'Swaziland','SWZ'),(207,'Sweden','SWE'),(208,'Switzerland','CHE'),(209,'Syrian Arab Republic','SYR'),(210,'Tajikistan','TJK'),(211,'Thailand','THA'),(212,'Togo','TGO'),(213,'Tokelau','TKL'),(214,'Tonga','TON'),(215,'Trinidad and Tobago','TTO'),(216,'United Arab Emirates','ARE'),(217,'Tunisia','TUN'),(218,'Turkey','TUR'),(219,'Turkmenistan','TKM'),(220,'Turks and Caicos Islands','TCA'),(221,'Tuvalu','TUV'),(222,'Uganda','UGA'),(223,'Ukraine','UKR'),(224,'Macedonia, FYR','MKD'),(225,'Egypt, Arab Rep.','EGY'),(226,'United Kingdom','GBR'),(227,'Tanzania','TZA'),(228,'United States','USA'),(229,'Virgin Islands, U.S.','VIR'),(230,'Burkina Faso','BFA'),(231,'Uruguay','URY'),(232,'Uzbekistan','UZB'),(233,'Venezuela, RB','VEN'),(234,'Wallis and Futuna','WLF'),(235,'Samoa','WSM'),(236,'Yemen','YEM'),(237,'Serbia and Montenegro','SCG'),(238,'Zambia','ZMB'),(239,'Westbank and Gaza','WBG'),(240,'Jerusalem','JER');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repo_perms_groups`
--

DROP TABLE IF EXISTS `repo_perms_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `repo_perms_groups` (
  `repo_pg_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT '0',
  PRIMARY KEY (`repo_pg_id`)
) AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='Permission group names';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repo_perms_groups`
--

LOCK TABLES `repo_perms_groups` WRITE;
/*!40000 ALTER TABLE `repo_perms_groups` DISABLE KEYS */;
INSERT INTO `repo_perms_groups` VALUES (1,'Manage studies (full access)','Full control over the studies including adding, updating, publishing, copying from other collections, etc.',0),(2,'Manage studies (limited access)','All access except can\'t publish or unpublish studies',1),(3,'Manage licensed requests','Allows user to view and process licensed data requests for the collection',2),(4,'Reviewer','Allows user to review studies from the front-end regardless of study publish/unpublish status',3);
/*!40000 ALTER TABLE `repo_perms_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_repo_permissions`
--

DROP TABLE IF EXISTS `user_repo_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_repo_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `repo_id` int(11) DEFAULT NULL,
  `repo_pg_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 COMMENT='set user permission for a collection';
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `lic_file_downloads`
--

DROP TABLE IF EXISTS `lic_file_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lic_file_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fileid` varchar(45) NOT NULL,
  `downloads` varchar(45) DEFAULT NULL,
  `download_limit` varchar(45) DEFAULT NULL,
  `expiry` int(11) DEFAULT NULL,
  `lastdownloaded` int(11) DEFAULT NULL,
  `requestid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `regions`
--

DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT '0',
  `title` varchar(45) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `regions`
--

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` VALUES (1,0,'By Region',0),(2,1,'East Asia and Pacific',1),(3,1,'Europe and Central Asia',1),(4,1,'Latin America & the Caribbean',1),(5,1,'Middle East and North Africa',1),(6,1,'South Asia',1),(7,1,'Sub-Saharan Africa',1),(8,0,'By Income',0),(9,8,'Low-income economies',0),(10,8,'Lower-middle-income economies',1),(11,8,'Upper-middle-income economies',3),(12,8,'High-income economies',4),(13,8,'High-income OECD members',6);
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci_sessions`
--

DROP TABLE IF EXISTS `ci_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) DEFAULT '0',
  `user_agent` varchar(255) DEFAULT NULL,
  `last_activity` int(11) DEFAULT '0',
  `user_data` text,
  PRIMARY KEY (`session_id`)
) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `public_requests`
--

DROP TABLE IF EXISTS `public_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `public_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `surveyid` int(11) DEFAULT NULL,
  `abstract` text NOT NULL,
  `posted` int(11) NOT NULL,
  `request_type` varchar(45) DEFAULT 'study',
  `collectionid` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;


--
-- Table structure for table `sitelogs`
--

DROP TABLE IF EXISTS `sitelogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
   `useragent` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Table structure for table `configurations`
--

DROP TABLE IF EXISTS `configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configurations` (
  `name` varchar(200) NOT NULL,
  `value` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `helptext` varchar(255) DEFAULT NULL,
  `item_group` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`name`)
) DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configurations`
--

LOCK TABLES `configurations` WRITE;
/*!40000 ALTER TABLE `configurations` DISABLE KEYS */;
INSERT INTO `configurations` VALUES ('app_version','5.0.0','Application version',NULL,NULL);
INSERT INTO `configurations` VALUES ('cache_default_expires','7200','Cache expiry (in mili seconds)',NULL,NULL);
INSERT INTO `configurations` VALUES ('cache_disabled','1','Enable/disable site caching',NULL,NULL);
INSERT INTO `configurations` VALUES ('cache_path','cache/','Site cache folder',NULL,NULL);
INSERT INTO `configurations` VALUES ('catalog_records_per_page','15','Catalog search page - records per page',NULL,NULL);
INSERT INTO `configurations` VALUES ('catalog_root','datafiles','Survey catalog folder',NULL,NULL);
INSERT INTO `configurations` VALUES ('collections_vocab','2','survey collections vocabulary',NULL,NULL);
INSERT INTO `configurations` VALUES ('collection_search','no',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('collection_search_weight','5',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('da_search','no',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('da_search_weight','2',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('db_version','5.0.0','Database version',NULL,NULL);
INSERT INTO `configurations` VALUES ('ddi_import_folder','imports','Survey catalog import folder',NULL,NULL);
INSERT INTO `configurations` VALUES ('default_home_page','home','Default home page','Default home page',NULL);
INSERT INTO `configurations` VALUES ('html_folder','/pages',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('lang','en-us','Site Language','Site Language code',NULL);
INSERT INTO `configurations` VALUES ('language','english',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('login_timeout','40','Login timeout (minutes)',NULL,NULL);
INSERT INTO `configurations` VALUES ('mail_protocol','smtp','Select method for sending emails','Supported protocols: MAIL, SMTP, SENDMAIL',NULL);
INSERT INTO `configurations` VALUES ('min_password_length','5','Minimum password length',NULL,NULL);
INSERT INTO `configurations` VALUES ('news_feed_url','http://ihsn.org/nada/index.php?q=news/feed','','','');
INSERT INTO `configurations` VALUES ('regional_search','no','Enable regional search',NULL,NULL);
INSERT INTO `configurations` VALUES ('regional_search_weight','3',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('repository_identifier','default','Repository Identifier',NULL,NULL);
INSERT INTO `configurations` VALUES ('site_password_protect','no','Password protect website',NULL,NULL);
INSERT INTO `configurations` VALUES ('smtp_host','','SMTP Host name',NULL,NULL);
INSERT INTO `configurations` VALUES ('smtp_pass','','SMTP password',NULL,NULL);
INSERT INTO `configurations` VALUES ('smtp_port','25','SMTP port',NULL,NULL);
INSERT INTO `configurations` VALUES ('smtp_user','','SMTP username',NULL,NULL);
INSERT INTO `configurations` VALUES ('theme','default','Site theme name',NULL,NULL);
INSERT INTO `configurations` VALUES ('topics_vocab','1','Vocabulary ID for Topics',NULL,NULL);
INSERT INTO `configurations` VALUES ('topic_search','no','Topic search',NULL,NULL);
INSERT INTO `configurations` VALUES ('topic_search_weight','6',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('use_html_editor','yes','Use HTML editor for entering HTML for static pages',NULL,NULL);
INSERT INTO `configurations` VALUES ('website_footer','Powered by NADA 4.0 and DDI','Website footer text',NULL,NULL);
INSERT INTO `configurations` VALUES ('website_title','National Data Archive','Website title','Provide the title of the website','website');
INSERT INTO `configurations` VALUES ('website_url','http://localhost/nada','Website URL','URL of the website','website');
INSERT INTO `configurations` VALUES ('website_webmaster_email','','Site webmaster email address','-','website');
INSERT INTO `configurations` VALUES ('website_webmaster_name','noreply','Webmaster name','-','website');
INSERT INTO `configurations` VALUES ('year_search','no',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('year_search_weight','1',NULL,NULL,NULL);
/*!40000 ALTER TABLE `configurations` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Table structure for table `featured_surveys`
--

CREATE TABLE `featured_surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repoid` int(11) DEFAULT NULL,
  `sid` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `survey_repo` (`repoid`,`sid`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Table structure for table `survey_types`
--

CREATE  TABLE `survey_types` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `title_UNIQUE` (`title` ASC) 
)
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- 
-- Table structure for table 'survey_lic_requests'
--

CREATE TABLE `survey_lic_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `sid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `uq_survey_requests` (`request_id`,`sid`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;




-- 
-- Table structure for table 'data_files'
--
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



--
-- API KEYS table
--
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(40) NOT NULL,
  `level` int(2) NOT NULL,
  `ignore_limits` tinyint(1) NOT NULL DEFAULT '0',
  `ip_addresses` text,
  `date_created` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_private_key` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_UNIQUE` (`api_key`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- API Logs table
--
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



CREATE TABLE `data_files_resources` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `sid` INT NULL,
  `fid` VARCHAR(45) NULL,
  `resource_id` INT NULL,
  `file_format` VARCHAR(45) NULL,
  `api_use` TINYINT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC),
  UNIQUE INDEX `file_resource` (`sid` ASC, `resource_id` ASC));


CREATE TABLE `survey_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) DEFAULT NULL,
  `location` geometry NOT NULL,
  PRIMARY KEY (`id`),
  SPATIAL KEY `idx_location` (`location`)
) DEFAULT CHARSET=utf8;


CREATE TABLE `filestore` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_ext` varchar(10) DEFAULT NULL,
  `is_image` tinyint(4) DEFAULT NULL,
  `changed` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_filestore_file` (`file_name`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;