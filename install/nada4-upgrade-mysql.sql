###################################################################################
# script to upgrade nada3 mysql database to nada4
###################################################################################

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

DROP TABLE IF EXISTS `repositories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;



ALTER TABLE `lic_files` COLLATE = utf8_general_ci ;

CREATE  TABLE IF NOT EXISTS `site_menu` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `pid` INT(11) NULL DEFAULT NULL ,
  `title` VARCHAR(100) NULL DEFAULT NULL ,
  `url` VARCHAR(255) NULL DEFAULT NULL ,
  `weight` INT(11) NULL DEFAULT NULL ,
  `depth` INT(11) NULL DEFAULT NULL ,
  `module` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 36
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `vocabularies` COLLATE = utf8_general_ci ;

ALTER TABLE `variables` COLLATE = utf8_general_ci ;

CREATE  TABLE IF NOT EXISTS `users_groups` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `user_id` INT(11) NULL DEFAULT NULL ,
  `group_id` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `user_group_UNQ` (`user_id` ASC, `group_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 148
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `survey_relationships` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sid_1` INT(11) NULL DEFAULT NULL ,
  `sid_2` INT(11) NULL DEFAULT NULL ,
  `relationship_id` INT(11) NULL DEFAULT NULL ,
  `pair_id` VARCHAR(45) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `idx_pair` (`pair_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'related surveys e.g. parent, child, sibling, related';

CREATE  TABLE IF NOT EXISTS `survey_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sid` INT(11) NOT NULL ,
  `tag` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_tag`  (`sid` ASC, `tag` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `meta` COLLATE = utf8_general_ci ;

drop table login_attempts;

CREATE  TABLE IF NOT EXISTS `login_attempts` (
  `id` MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `ip_address` VARCHAR(30) NOT NULL ,
  `login` VARCHAR(100) NOT NULL ,
  `time` INT(11) UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `repository_sections` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(100) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  `weight` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 6
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;

ALTER TABLE `survey_topics` COLLATE = utf8_general_ci ;

ALTER TABLE `blocks` COLLATE = utf8_general_ci ;

ALTER TABLE `survey_citations` COLLATE = utf8_general_ci ;

ALTER TABLE `dcformats` COLLATE = utf8_general_ci ;

CREATE  TABLE IF NOT EXISTS `group_repo_access` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `group_id` INT(11) NULL DEFAULT NULL ,
  `repo_id` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `grp_repo_UNIQUE` (`group_id` ASC, `repo_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
ADD COLUMN `link_da` varchar(255)  NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
CHANGE COLUMN `changed` `changed` INT(11) NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
CHANGE COLUMN `created` `created` INT(11) NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
ADD COLUMN `published` TINYINT(4) NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci ,  
ADD COLUMN `total_views` INT(11) NULL DEFAULT '0'  , 
ADD COLUMN `total_downloads` INT(11) NULL DEFAULT '0',
ADD COLUMN `stats_last_updated` INT(11) NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
ADD COLUMN `abbreviation` varchar(45) NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
ADD COLUMN `kindofdata` varchar(255) NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
ADD COLUMN `keywords` text NULL DEFAULT NULL;

ALTER TABLE `surveys` COLLATE = utf8_general_ci , 
ADD COLUMN `ie_program` varchar(255) NULL DEFAULT NULL, 
ADD COLUMN `ie_project_id` varchar(255) NULL DEFAULT NULL, 
ADD COLUMN `ie_project_name` varchar(255) NULL DEFAULT NULL, 
ADD COLUMN `ie_project_uri` varchar(255) NULL DEFAULT NULL, 
ADD COLUMN `ie_team_leaders` varchar(255) NULL DEFAULT NULL, 
ADD COLUMN `project_id` varchar(255) NULL DEFAULT NULL, 
ADD COLUMN `project_name` varchar(255) NULL DEFAULT NULL, 
ADD COLUMN `project_uri` varchar(255) NULL DEFAULT NULL;


ALTER TABLE `dctypes` COLLATE = utf8_general_ci ;

CREATE  TABLE IF NOT EXISTS `da_collections` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(255) NULL DEFAULT NULL ,
  `description` VARCHAR(1000) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'data access by collection/set';

CREATE  TABLE IF NOT EXISTS `cache` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT ,
  `uid` VARCHAR(100) CHARACTER SET 'utf8' NOT NULL ,
  `data` TEXT NULL DEFAULT NULL ,
  `created` INT(11) NULL DEFAULT NULL ,
  `expiry` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uid_UNIQUE` (`uid` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `schema_version` COLLATE = utf8_general_ci ;

ALTER TABLE `forms` COLLATE = utf8_general_ci ;

ALTER TABLE `lic_requests` COLLATE = utf8_general_ci , 
CHANGE COLUMN `surveyid` `surveyid` INT(11) NULL DEFAULT NULL  , 
ADD COLUMN `request_type` VARCHAR(45) NULL DEFAULT 'study'  AFTER `userid` , 
ADD COLUMN `collection_id` VARCHAR(100) NULL DEFAULT NULL  AFTER `surveyid` , 
ADD COLUMN `expiry_date` INT(11) NULL DEFAULT NULL  AFTER `ip_limit` , 
ADD COLUMN `additional_info` TEXT NULL DEFAULT NULL  AFTER `expiry_date` ;

ALTER TABLE `citations` COLLATE = utf8_general_ci , 
CHANGE COLUMN `page_from` `page_from` VARCHAR(25) NULL DEFAULT NULL  , 
CHANGE COLUMN `page_to` `page_to` VARCHAR(25) NULL DEFAULT NULL  , 
ADD COLUMN `ihsn_id` VARCHAR(50) NULL DEFAULT NULL;

ALTER TABLE `citations` COLLATE = utf8_general_ci , 
ADD COLUMN `country` VARCHAR(100) NULL DEFAULT NULL  ;

CREATE  TABLE IF NOT EXISTS `permission_urls` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `url` VARCHAR(255) NULL DEFAULT NULL ,
  `permission_id` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `url_UNIQUE` (`url` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 150
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `survey_aliases` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `sid` INT(10) UNSIGNED NOT NULL ,
  `alternate_id` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_unicode_ci' NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `survey_id` (`alternate_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci
COMMENT = 'Other codeBook IDs for the survey';

ALTER TABLE `resources` COLLATE = utf8_general_ci ;

ALTER TABLE `lic_files_log` COLLATE = utf8_general_ci ;

ALTER TABLE `terms` COLLATE = utf8_general_ci ;

CREATE  TABLE IF NOT EXISTS `survey_countries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `sid` INT(11) NULL DEFAULT NULL ,
  `cid` INT(11) NULL DEFAULT NULL ,
  `country_name` VARCHAR(100) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `sid_iso_UNIQUE` (`sid` ASC, `country_name` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `country_aliases` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `countryid` INT(11) NOT NULL ,
  `alias` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `ix_alias_uniq` (`countryid` ASC, `alias` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `group_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `group_id` INT(11) NOT NULL ,
  `permission_id` INT(11) NOT NULL COMMENT 'permissions bit value' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `grp_perms_UNIQUE` (`group_id` ASC, `permission_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 340
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `survey_repos` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `sid` INT(10) UNSIGNED NOT NULL ,
  `repositoryid` VARCHAR(255) NOT NULL ,
  `isadmin` TINYINT(3) UNSIGNED NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `repo_perms_urls` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `repo_pg_id` INT(11) NULL DEFAULT NULL COMMENT 'repo permission group id' ,
  `url` VARCHAR(100) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 69
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'list of URLs defining a permission group for collections';

ALTER TABLE `tokens` COLLATE = utf8_general_ci ;

ALTER TABLE `menus` COLLATE = utf8_general_ci ;

CREATE  TABLE IF NOT EXISTS `url_mappings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `source` VARCHAR(255) NULL DEFAULT NULL ,
  `target` VARCHAR(255) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `groups` (
  `id` TINYINT(3) NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(100) NOT NULL ,
  `description` VARCHAR(255) NOT NULL ,
  `group_type` VARCHAR(40) NULL DEFAULT NULL ,
  `access_type` VARCHAR(45) NULL DEFAULT NULL ,
  `weight` INT(11) NULL DEFAULT '0' ,
  `is_collection_group` TINYINT(4) NULL DEFAULT '0' COMMENT 'does group control collection access? 1=yes' ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 13
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
ROW_FORMAT = DYNAMIC;

CREATE  TABLE IF NOT EXISTS `survey_relationship_types` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `rel_group_id` INT(11) NULL DEFAULT NULL ,
  `rel_name` VARCHAR(45) NULL DEFAULT NULL ,
  `rel_dir` TINYINT(4) NULL DEFAULT NULL ,
  `rel_cordinality` VARCHAR(10) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 15
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `lic_requests_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `lic_req_id` INT(11) NULL DEFAULT NULL ,
  `user_id` VARCHAR(100) NULL DEFAULT NULL ,
  `logtype` VARCHAR(45) NULL DEFAULT NULL ,
  `request_status` VARCHAR(45) NULL DEFAULT NULL ,
  `description` TEXT NULL DEFAULT NULL ,
  `created` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `da_collection_surveys` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `cid` INT(11) NULL DEFAULT NULL ,
  `sid` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `unq_coll_sid` (`cid` ASC, `sid` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tag` VARCHAR(100) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `tag_UNIQUE` (`tag` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `label` VARCHAR(45) NULL DEFAULT NULL ,
  `description` VARCHAR(255) NULL DEFAULT NULL ,
  `section` VARCHAR(45) NULL DEFAULT NULL ,
  `weight` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 72
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `survey_years` COLLATE = utf8_general_ci 
, ADD UNIQUE INDEX `idx_sid_year` (`sid` ASC, `data_coll_year` ASC) 
, ADD INDEX `idx_sid` (`sid` ASC) ;

CREATE  TABLE IF NOT EXISTS `region_countries` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `region_id` INT(11) NULL DEFAULT NULL ,
  `country_id` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

CREATE  TABLE IF NOT EXISTS `survey_notes` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `sid` INT(10) UNSIGNED NULL DEFAULT NULL ,
  `note` TEXT NOT NULL ,
  `type` TINYTEXT NOT NULL ,
  `userid` INT(10) UNSIGNED NOT NULL ,
  `created` INT(11) NULL DEFAULT NULL ,
  `changed` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `citation_authors` COLLATE = utf8_general_ci ;

ALTER TABLE `countries` CHANGE COLUMN `iso3` `iso` VARCHAR(3) NOT NULL  ;

CREATE  TABLE IF NOT EXISTS `repo_perms_groups` (
  `repo_pg_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(45) NULL DEFAULT NULL ,
  `description` VARCHAR(255) NULL DEFAULT NULL ,
  `weight` INT(11) NULL DEFAULT '0' ,
  PRIMARY KEY (`repo_pg_id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 5
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Permission group names';

CREATE  TABLE IF NOT EXISTS `user_repo_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `user_id` INT(11) NULL DEFAULT NULL ,
  `repo_id` INT(11) NULL DEFAULT NULL ,
  `repo_pg_id` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'set user permission for a collection';

ALTER TABLE `lic_file_downloads` COLLATE = utf8_general_ci ;

CREATE  TABLE IF NOT EXISTS `regions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `pid` INT(11) NULL DEFAULT '0' ,
  `title` VARCHAR(45) NULL DEFAULT NULL ,
  `weight` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 14
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;

ALTER TABLE `ci_sessions` COLLATE = utf8_general_ci , CHANGE COLUMN `user_agent` `user_agent` VARCHAR(255) NULL DEFAULT NULL  ;

ALTER TABLE `public_requests` COLLATE = utf8_general_ci , CHANGE COLUMN `surveyid` `surveyid` INT(11) NULL DEFAULT NULL  , ADD COLUMN `request_type` VARCHAR(45) NULL DEFAULT 'study'  AFTER `posted` , ADD COLUMN `collectionid` VARCHAR(45) NULL DEFAULT NULL  AFTER `request_type` ;

ALTER TABLE `sitelogs` COLLATE = utf8_general_ci ;

ALTER TABLE `configurations` COLLATE = utf8_general_ci ;

DROP TABLE IF EXISTS `user_site_roles` ;

DROP TABLE IF EXISTS `user_groups` ;

DROP TABLE IF EXISTS `survey_collections` ;

DROP TABLE IF EXISTS `planned_surveys` ;

DROP TABLE IF EXISTS `harvester_queue` ;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


################################################################
# SURVEYS
################################################################

update surveys set published=1;




################################################################
# USERS
################################################################

ALTER TABLE `users` CHANGE COLUMN `group_id` `group_id` INT(11) NULL DEFAULT 0 ;

--
-- Dumping data for table `groups`
--

truncate table groups;

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
-- migrate user roles to N4 - users_groups
-- 

truncate table users_groups;

INSERT INTO USERS_GROUPS (user_id,group_id) SELECT id,group_id from users;


--
-- Dumping data for table `group_permissions`
--

truncate table group_permissions;

LOCK TABLES `group_permissions` WRITE;
/*!40000 ALTER TABLE `group_permissions` DISABLE KEYS */;
INSERT INTO `group_permissions` VALUES (5,1,2),(6,1,14),(292,3,1),(289,3,2),(301,3,3),(299,3,14),(293,3,41),(295,3,42),(296,3,43),(297,3,44),(291,3,46),(294,3,49),(300,3,61),(290,3,62),(298,3,63),(334,4,2),(339,4,3),(335,4,16),(338,4,61),(336,4,69),(337,4,70),(313,5,3),(312,5,71),(287,9,2),(288,9,63),(227,10,2),(229,10,3),(228,10,45);
/*!40000 ALTER TABLE `group_permissions` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Dumping data for table `permissions`
--

truncate table permissions;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'Upload DDI file','this is a test description','catalog',3),(2,'View catalog','this is a test description','catalog',0),(3,'Access site administration','this is a test description','site_admin',0),(4,'Access Menus','this is a test description','menu_admin',0),(5,'Add menu page','this is a test description','menu_admin',0),(6,'Edit menu','this is a test description','menu_admin',0),(7,'Add menu link','this is a test description','menu_admin',0),(8,'Sort menu items','this is a test description','menu_admin',0),(9,'Access vocabularies','this is a test description','vocab',0),(10,'Access vocabulary terms','this is a test description','vocab',0),(11,'View user accounts','View list of all user accounts','user_admin',0),(12,'Edit user information','this is a test description','user_admin',0),(14,'Access DDI Browser','this is a test description','ddibrowser',0),(16,'Access site pages','this is a test description','general_site',0),(18,'View citations','this is a test description','general_site',0),(22,'Site backup','this is a test description','site_admin',0),(23,'View licensed request form','this is a test description','general_site',0),(25,'Switch site language','this is a test description','general_site',0),(27,'Translate site','this is a test description','site_admin',0),(30,'Public use files','this is a test description','general_site',0),(40,'Data Deposit','Data Deposit','site_admin',0),(41,'Publish/Unpublish study','Allows publishing study','catalog',3),(42,'Delete Study','delete study','catalog',4),(43,'Export DDI','Export','catalog',5),(44,'Import RDF','Import RDF for study resources','catalog',5),(45,'Manage Repositories','Manage repositories','repositories',9),(46,'Replace DDI','Replace a DDI file','catalog',3),(49,'Edit survey','Edit survey','catalog',4),(61,'Select collection','','repositories',1),(62,'Copy DDI','copy DDI','catalog',0),(63,'Copy studies from other collections','','catalog',6),(64,'View citations','','citation',1),(65,'Edit citation','','citation',2),(66,'Delete citation','Delete a citation','citation',3),(67,'Import citations','','citation',4),(68,'Export citations','Export citations to various formats','citation',5),(69,'View licensed requests','View list of licensed data requests','Licensed requests',0),(70,'Edit request','Edit a licensed data request','Licensed requests',1),(71,'Reports','View and generate admin reports','reports',0);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;



--
-- Dumping data for table `permission_urls`
--

truncate table permission_urls;

LOCK TABLES `permission_urls` WRITE;
/*!40000 ALTER TABLE `permission_urls` DISABLE KEYS */;
INSERT INTO `permission_urls` VALUES (1,'admin/catalog/upload',1),(4,'admin/menu',4),(5,'admin/menu/add',5),(6,'admin/menu/edit/*',6),(7,'admin/menu/add_link',7),(8,'admin/menu/menu_sort',8),(9,'admin/vocabularies',9),(10,'admin/terms/*',10),(12,'admin/users/*',12),(14,'ddibrowser',14),(16,'page/*',16),(18,'citations',18),(22,'backup*',22),(23,'access_licensed*',23),(25,'switch_language*',25),(27,'translate/*',27),(34,'admin/catalog/do_upload',1),(48,'admin/datadeposit*',40),(51,'admin/catalog/delete',42),(52,'admin/catalog/export-ddi',43),(53,'admin/catalog/import-rdf',44),(54,'admin/repositories/*',45),(55,'admin/repositories',45),(88,'admin/catalog/replace_ddi/*',46),(100,'admin/catalog/edit/*',49),(101,'admin/catalog/update/*',49),(102,'admin/catalog/update',49),(103,'admin/managefiles/*',49),(104,'admin/resources/*',49),(112,'admin/catalog',2),(113,'admin/catalog/survey/*',2),(114,'admin/catalog/search',2),(116,'access_public/*',30),(119,'admin/catalog/copy_ddi',62),(124,'admin/repositories/select',61),(125,'admin/repositories/active/*',61),(126,'admin/catalog/publish',41),(127,'admin/catalog/publish/*',41),(131,'admin/catalog/copy_study',63),(132,'admin/catalog/do_copy_study/*',63),(133,'admin/citations',64),(134,'admin/citations/edit',65),(135,'admin/citations/edit/*',65),(136,'admin/citations/delete/*',66),(137,'admin/citations/import',67),(138,'admin/citations/export',68),(141,'admin',3),(142,'admin/users/exit_impersonate',3),(143,'admin/licensed_requests',69),(145,'admin/licensed_requests/*',70),(147,'admin/users',11),(148,'admin/reports/*',71),(149,'admin/reports',71);
/*!40000 ALTER TABLE `permission_urls` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Dumping data for table `repo_perms_urls`
--

truncate table repo_perms_urls;

LOCK TABLES `repo_perms_urls` WRITE;
/*!40000 ALTER TABLE `repo_perms_urls` DISABLE KEYS */;
INSERT INTO `repo_perms_urls` VALUES 
(5,2,'admin/catalog/copy_ddi'),
(6,2,'admin/catalog/copy_study'),
(7,2,'admin/catalog/delete'),
(8,2,'admin/catalog/do_copy_study/*'),
(9,2,'admin/catalog/do_upload'),
(10,2,'admin/catalog/edit/*'),
(11,2,'admin/catalog/export-ddi'),
(12,2,'admin/catalog/import-rdf'),
(15,2,'admin/catalog/repladce_ddi/*'),
(16,2,'admin/catalog/search'),
(17,2,'admin/catalog/survey/*'),
(18,2,'admin/catalog/update'),
(19,2,'admin/catalog/update/*'),
(20,2,'admin/catalog/upload'),
(28,3,'admin/licensed_requests'),
(29,3,'admin/licensed_requests/*'),
(30,2,'admin/managefiles/*'),
(41,2,'admin/resources/*'),
(64,1,'admin/catalog/*'),
(67,2,'admin/pdf_generator/*'),
(68,1,'admin/pdf_generator/*'),
(69,1,'admin/catalog/add_study'),
(70,1,'admin/catalog/batch_import'),
(71,1,'admin/catalog/refresh/*');
/*!40000 ALTER TABLE `repo_perms_urls` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Dumping data for table `repo_perms_groups`
--

truncate table repo_perms_groups;

LOCK TABLES `repo_perms_groups` WRITE;
/*!40000 ALTER TABLE `repo_perms_groups` DISABLE KEYS */;
INSERT INTO `repo_perms_groups` VALUES (1,'Manage studies (full access)','Full control over the studies including adding, updating, publishing, copying from other collections, etc.',0),(2,'Manage studies (limited access)','All access except can\'t publish or unpublish studies',1),(3,'Manage licensed requests','Allows user to view and process licensed data requests for the collection',2),(4,'Reviewer','Allows user to review studies from the front-end regardless of study publish/unpublish status',3);
/*!40000 ALTER TABLE `repo_perms_groups` ENABLE KEYS */;
UNLOCK TABLES;


###############################################
# repository sections
###############################################

--
-- Dumping data for table `repository_sections`
--

truncate table repository_sections;

LOCK TABLES `repository_sections` WRITE;
/*!40000 ALTER TABLE `repository_sections` DISABLE KEYS */;
INSERT INTO `repository_sections` VALUES (2,'Regional Collections',5),(3,'Specialized Collections',10);
/*!40000 ALTER TABLE `repository_sections` ENABLE KEYS */;
UNLOCK TABLES;



###################################################
# site menu
###################################################

--
-- Dumping data for table `site_menu`
--
truncate table site_menu;

LOCK TABLES `site_menu` WRITE;
/*!40000 ALTER TABLE `site_menu` DISABLE KEYS */;
INSERT INTO `site_menu` VALUES (1,0,'Dashboard','admin',0,0,'admin'),(2,0,'Studies','admin/catalog',1,0,'catalog'),(4,0,'Citations','admin/citations',3,0,'citations'),(5,0,'Users','admin/users',4,0,'users'),(6,0,'Menu','admin/menu',5,0,'menu'),(7,0,'Reports','admin/reports',6,0,'reports'),(8,0,'Settings','admin/configurations',7,0,'configurations'),(12,2,'-','-',70,1,'catalog'),(13,2,'Licensed requests','admin/licensed_requests',80,1,'catalog'),(14,2,'-','-',90,1,'catalog'),(15,2,'Manage collections','admin/repositories',60,1,'repositories'),(17,4,'All citations','admin/citations',100,1,'citations'),(18,4,'Import citations','admin/citations/import',90,1,'citations'),(19,4,'Export citations','admin/citations/export',80,1,'citations'),(20,5,'All users','admin/users',100,1,'users'),(21,5,'Add user','admin/users/add',99,1,'users'),(22,5,'-','-',65,1,'users'),(27,6,'All pages','admin/menu',0,1,'menu'),(28,7,'All reports','admin/reports',0,1,'reports'),(29,8,'Settings','admin/configurations',0,1,'configurations'),(30,8,'Countries','admin/countries',0,1,'vocabularies'),(31,8,'Regions','admin/regions',0,1,'vocabularies'),(32,8,'-','-',0,1,'vocabularies'),(33,8,'Vocabularies','admin/vocabularies',-9,1,'vocabularies'),(34,2,'Manage studies','admin/catalog',100,1,'catalog'),(35,5,'Impersonate user','admin/users/impersonate',50,1,'users');
/*!40000 ALTER TABLE `site_menu` ENABLE KEYS */;
UNLOCK TABLES;


####################################################
# REGIONS
####################################################

--
-- Dumping data for table `regions`
--

truncate table regions;

LOCK TABLES `regions` WRITE;
/*!40000 ALTER TABLE `regions` DISABLE KEYS */;
INSERT INTO `regions` VALUES (1,0,'By Region',0),(2,1,'East Asia and Pacific',1),(3,1,'Europe and Central Asia',1),(4,1,'Latin America & the Caribbean',1),(5,1,'Middle East and North Africa',1),(6,1,'South Asia',1),(7,1,'Sub-Saharan Africa',1),(8,0,'By Income',0),(9,8,'Low-income economies',0),(10,8,'Lower-middle-income economies',1),(11,8,'Upper-middle-income economies',3),(12,8,'High-income economies',4),(13,8,'High-income OECD members',6);
/*!40000 ALTER TABLE `regions` ENABLE KEYS */;
UNLOCK TABLES;


--
-- Dumping data for table `region_countries`
--

LOCK TABLES `region_countries` WRITE;
/*!40000 ALTER TABLE `region_countries` DISABLE KEYS */;
INSERT INTO `region_countries` VALUES (309,2,5),(310,2,35),(311,2,44),(312,2,72),(313,2,103),(314,2,87),(315,2,115),(316,2,119),(317,2,131),(318,2,164),(319,2,163),(320,2,140),(321,2,32),(322,2,165),(323,2,168),(324,2,171),(325,2,235),(326,2,211),(327,2,176),(328,2,214),(329,2,221),(330,2,153),(331,2,196),(353,4,8),(354,4,10),(355,4,26),(356,4,21),(357,4,25),(358,4,43),(359,4,48),(360,4,54),(361,4,56),(362,4,61),(363,4,62),(364,4,63),(365,4,64),(366,4,90),(367,4,93),(368,4,95),(369,4,96),(370,4,99),(371,4,110),(372,4,138),(373,4,155),(374,4,167),(375,4,169),(376,4,170),(377,4,185),(378,4,187),(379,4,204),(380,4,231),(381,4,233),(382,5,4),(383,5,79),(384,5,225),(385,5,104),(386,5,105),(387,5,113),(388,5,120),(389,5,124),(390,5,143),(391,5,209),(392,5,217),(393,5,83),(394,5,236),(395,6,1),(396,6,15),(397,6,20),(398,6,102),(399,6,132),(400,6,148),(401,6,166),(402,6,41),(403,7,7),(404,7,59),(405,7,23),(406,7,230),(407,7,33),(408,7,36),(409,7,38),(410,7,40),(411,7,42),(412,7,49),(413,7,52),(414,7,51),(415,7,109),(416,7,67),(417,7,66),(418,7,80),(419,7,82),(420,7,85),(421,7,94),(422,7,175),(423,7,114),(424,7,121),(425,7,123),(426,7,129),(427,7,130),(428,7,133),(429,7,136),(430,7,137),(431,7,144),(432,7,146),(433,7,156),(434,7,157),(435,7,181),(436,7,189),(437,7,191),(438,7,192),(439,7,193),(440,7,198),(441,7,199),(442,7,203),(443,7,206),(444,7,227),(445,7,212),(446,7,222),(447,7,238),(448,7,200),(449,3,2),(450,3,16),(451,3,9),(452,3,34),(453,3,22),(454,3,31),(455,3,81),(456,3,112),(457,3,118),(458,3,122),(459,3,126),(460,3,224),(461,3,141),(462,3,179),(463,3,180),(464,3,237),(465,3,210),(466,3,218),(467,3,219),(468,3,223),(469,3,232),(470,9,1),(471,9,15),(472,9,59),(473,9,230),(474,9,33),(475,9,35),(476,9,40),(477,9,42),(478,9,49),(479,9,52),(480,9,67),(481,9,66),(482,9,82),(483,9,94),(484,9,175),(485,9,96),(486,9,114),(487,9,115),(488,9,118),(489,9,123),(490,9,129),(491,9,130),(492,9,133),(493,9,136),(494,9,144),(495,9,32),(496,9,148),(497,9,156),(498,9,181),(499,9,193),(500,9,198),(501,9,210),(502,9,227),(503,9,212),(504,9,222),(505,9,200),(557,10,2),(558,10,16),(559,10,26),(560,10,20),(561,10,21),(562,10,36),(563,10,38),(564,10,51),(565,10,109),(566,10,79),(567,10,225),(568,10,64),(569,10,72),(570,10,81),(571,10,85),(572,10,93),(573,10,95),(574,10,99),(575,10,102),(576,10,103),(577,10,105),(578,10,87),(579,10,119),(580,10,121),(581,10,164),(582,10,163),(583,10,141),(584,10,140),(585,10,143),(586,10,155),(587,10,157),(588,10,166),(589,10,168),(590,10,169),(591,10,171),(592,10,235),(593,10,189),(594,10,191),(595,10,28),(596,10,41),(597,10,203),(598,10,206),(599,10,209),(600,10,176),(601,10,214),(602,10,223),(603,10,232),(604,10,153),(605,10,196),(606,10,83),(607,10,236),(608,10,238),(609,11,4),(610,11,5),(611,11,7),(612,11,8),(613,11,10),(614,11,9),(615,11,34),(616,11,22),(617,11,23),(618,11,25),(619,11,31),(620,11,43),(621,11,44),(622,11,48),(623,11,54),(624,11,56),(625,11,61),(626,11,62),(627,11,63),(628,11,90),(629,11,104),(630,11,110),(631,11,113),(632,11,112),(633,11,122),(634,11,120),(635,11,124),(636,11,126),(637,11,224),(638,11,131),(639,11,132),(640,11,137),(641,11,138),(642,11,146),(643,11,165),(644,11,167),(645,11,170),(646,11,179),(647,11,180),(648,11,192),(649,11,199),(650,11,185),(651,11,187),(652,11,204),(653,11,211),(654,11,217),(655,11,218),(656,11,219),(657,11,221),(658,11,231),(659,11,233),(660,12,6),(661,12,151),(662,12,11),(663,12,12),(664,12,13),(665,12,14),(666,12,17),(667,12,18),(668,12,19),(669,12,30),(670,12,37),(671,12,39),(672,12,55),(673,12,57),(674,12,58),(675,12,60),(676,12,65),(677,12,68),(678,12,69),(679,12,73),(680,12,75),(681,12,77),(682,12,84),(683,12,88),(684,12,89),(685,12,92),(686,12,100),(687,12,101),(688,12,107),(689,12,108),(690,12,111),(691,12,116),(692,12,117),(693,12,125),(694,12,127),(695,12,128),(696,12,134),(697,12,139),(698,12,149),(699,12,152),(700,12,154),(701,12,160),(702,12,145),(703,12,173),(704,12,174),(705,12,177),(706,12,178),(707,12,188),(708,12,190),(709,12,194),(710,12,195),(711,12,197),(712,12,201),(713,12,183),(714,12,207),(715,12,208),(716,12,215),(717,12,220),(718,12,216),(719,12,226),(720,12,228),(721,12,229),(722,13,11),(723,13,12),(724,13,18),(725,13,37),(726,13,58),(727,13,60),(728,13,68),(729,13,73),(730,13,75),(731,13,84),(732,13,88),(733,13,100),(734,13,101),(735,13,106),(736,13,107),(737,13,108),(738,13,111),(739,13,116),(740,13,127),(741,13,149),(742,13,154),(743,13,160),(744,13,173),(745,13,174),(746,13,195),(747,13,197),(748,13,201),(749,13,207),(750,13,208),(751,13,226),(752,13,228);
/*!40000 ALTER TABLE `region_countries` ENABLE KEYS */;
UNLOCK TABLES;



####################################################
# CONFIGURATIONS
####################################################

--
-- Dumping data for table `configurations`
--

LOCK TABLES `configurations` WRITE;
/*!40000 ALTER TABLE `configurations` DISABLE KEYS */;
INSERT INTO `configurations` VALUES ('app_installed','1344277715',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('app_version','4.0.0-06.02.2013','Application version',NULL,NULL);
INSERT INTO `configurations` VALUES ('cache_default_expires','7200','Cache expiry (in mili seconds)',NULL,NULL);
INSERT INTO `configurations` VALUES ('cache_disabled','0','Enable/disable site caching',NULL,NULL);
INSERT INTO `configurations` VALUES ('cache_path','cache/','Site cache folder',NULL,NULL);
INSERT INTO `configurations` VALUES ('catalog_records_per_page','15','Catalog search page - records per page',NULL,NULL);
INSERT INTO `configurations` VALUES ('catalog_root','datafiles','Survey catalog folder',NULL,NULL);
INSERT INTO `configurations` VALUES ('collections_vocab','2','survey collections vocabulary',NULL,NULL);
INSERT INTO `configurations` VALUES ('collection_search','no',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('collection_search_weight','5',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('da_search','no',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('da_search_weight','2',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('db_version','4.0.0-06.02.2013','Database version',NULL,NULL);
INSERT INTO `configurations` VALUES ('ddi_import_folder','imports','Survey catalog import folder',NULL,NULL);
INSERT INTO `configurations` VALUES ('default_home_page','catalog','Default home page','Default home page',NULL);
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
INSERT INTO `configurations` VALUES ('website_webmaster_email','nada@ihsn.org','Site webmaster email address','-','website');
INSERT INTO `configurations` VALUES ('website_webmaster_name','noreply','Webmaster name','-','website');
INSERT INTO `configurations` VALUES ('year_search','no',NULL,NULL,NULL);
INSERT INTO `configurations` VALUES ('year_search_weight','1',NULL,NULL,NULL);
/*!40000 ALTER TABLE `configurations` ENABLE KEYS */;
UNLOCK TABLES;

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES 
(5,'Data available from external repository','remote','remote','1');

INSERT INTO `forms` VALUES 
(6,'Data not available','data_na','data_na','1');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

update surveys set formid=6 where formid is null;
update surveys set formid=6 where formid=0;
update surveys set repositoryid='central';


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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Table structure for table `survey_types`
--

CREATE  TABLE `survey_types` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `title_UNIQUE` (`title` ASC) )
ENGINE = InnoDB
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
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- 
-- Alter table structure for table 'sitelogs'
--
   
ALTER TABLE `sitelogs` COLLATE = utf8_general_ci , 
ADD COLUMN `useragent` varchar(300) DEFAULT NULL;