
--
-- Table structure for table `dd_citation_authors`
--

DROP TABLE IF EXISTS `dd_citation_authors`;

CREATE TABLE `dd_citation_authors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cid` int(11) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `initial` varchar(255) DEFAULT NULL,
  `author_type` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `dd_citations`
--

DROP TABLE IF EXISTS `dd_citations`;


CREATE TABLE `dd_citations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `alt_title` varchar(255) DEFAULT NULL,
  `authors` text,
  `editors` text,
  `translators` text,
  `changed` int(11) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `published` int(11) DEFAULT '1',
  `volume` varchar(45) DEFAULT NULL,
  `issue` varchar(45) DEFAULT NULL,
  `idnumber` varchar(45) DEFAULT NULL,
  `edition` varchar(45) DEFAULT NULL,
  `place_publication` varchar(255) DEFAULT NULL,
  `place_state` varchar(255) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_medium` int(11) DEFAULT '0',
  `url` varchar(255) DEFAULT NULL,
  `page_from` varchar(5) DEFAULT NULL,
  `page_to` varchar(5) DEFAULT NULL,
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
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;


--
-- Table structure for table `dd_collaborators`
--

DROP TABLE IF EXISTS `dd_collaborators`;


CREATE TABLE `dd_collaborators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `access` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `dd_datadeposit_history`
--

DROP TABLE IF EXISTS `dd_datadeposit_history`;


CREATE TABLE `dd_datadeposit_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_identity` varchar(100) NOT NULL,
  `created_on` int(11) NOT NULL,
  `project_status` varchar(100) NOT NULL,
  `comments` text,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `dd_kind_of_data`
--

DROP TABLE IF EXISTS `dd_kind_of_data`;


CREATE TABLE `dd_kind_of_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kindofdata` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;


--
-- Dumping data for table `dd_kind_of_data`
--

LOCK TABLES `dd_kind_of_data` WRITE;
/*!40000 ALTER TABLE `dd_kind_of_data` DISABLE KEYS */;
INSERT INTO `dd_kind_of_data` VALUES (1,'--'),(2,'Sample survey data [ssd]'),(3,'Census/enumeration data [cen]'),(4,'Administrative records data [adm]'),(5,'Aggregate data [agg]'),(6,'Clinical data [cli]'),(7,'Event/Transaction data [evn]'),(8,'Observation data/ratings [obs]'),(9,'Process-produced data [pro]'),(10,'Time budget dairies [tbd]');
/*!40000 ALTER TABLE `dd_kind_of_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dd_overview_methods`
--

DROP TABLE IF EXISTS `dd_overview_methods`;


CREATE TABLE `dd_overview_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method` text NOT NULL,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;


--
-- Dumping data for table `dd_overview_methods`
--

LOCK TABLES `dd_overview_methods` WRITE;
/*!40000 ALTER TABLE `dd_overview_methods` DISABLE KEYS */;
INSERT INTO `dd_overview_methods` VALUES (1,'--'),(2,'Propensity Score Matching'),(3,'Pipeline Comparison'),(4,'Other Matching Methods'),(5,'Instrumental Variables'),(6,'Simulated Counterfactual'),(7,'Single Difference'),(8,'Difference in Means'),(9,'Difference-in-Difference'),(10,'Regression Discontinuity Design'),(11,'Duration Model'),(12,'Non-Experimental'),(13,'Natural Experiment'),(14,'Other'),(15,'Randomization');
/*!40000 ALTER TABLE `dd_overview_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dd_project_resources`
--

DROP TABLE IF EXISTS `dd_project_resources`;


CREATE TABLE `dd_project_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  `description` varchar(300) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `dctype` varchar(100) DEFAULT NULL,
  `dcformat` varchar(100) DEFAULT NULL,
  `filesize` double DEFAULT NULL,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `dd_project_status_types`
--

DROP TABLE IF EXISTS `dd_project_status_types`;


CREATE TABLE `dd_project_status_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;


--
-- Dumping data for table `dd_project_status_types`
--

LOCK TABLES `dd_project_status_types` WRITE;
/*!40000 ALTER TABLE `dd_project_status_types` DISABLE KEYS */;
INSERT INTO `dd_project_status_types` VALUES (1,'submitted'),(2,'accepted'),(3,'draft'),(4,'closed');
/*!40000 ALTER TABLE `dd_project_status_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dd_projects`
--

DROP TABLE IF EXISTS `dd_projects`;


CREATE TABLE `dd_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_by` varchar(300) NOT NULL,
  `title` varchar(300) NOT NULL,
  `created_on` int(11) NOT NULL,
  `data_type` varchar(20) DEFAULT NULL,
  `last_modified` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `description` text NOT NULL,
  `shortname` varchar(50) NOT NULL,
  `collaborators` text,
  `uid` int(11) DEFAULT NULL,
  `access_policy` text,
  `library_notes` text,
  `submit_contact` text,
  `submit_on_behalf` text,
  `cc` text,
  `access_authority` text,
  `submitted_on` int(11) DEFAULT NULL,
  `submitted_by` varchar(300) DEFAULT NULL,
  `admin_comments` text,
  `administrated_by` text,
  `administer_date` int(11) DEFAULT NULL,
  `data_folder_path` varchar(300) DEFAULT NULL,
  `to_catalog` varchar(45) DEFAULT NULL,
  `is_embargoed` int(11) DEFAULT NULL,
  `embargoed` varchar(500) DEFAULT NULL,
  `disclosure_risk` varchar(500) DEFAULT NULL, 
  `key_variables` varchar(500) DEFAULT NULL, 
  `sensitive_variables` varchar(500) DEFAULT NULL, 
  `requested_reopen` tinyint(4) DEFAULT NULL,
  `requested_when` int(11) DEFAULT NULL,
  `metadata` text,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;



--
-- Table structure for table `dd_study`
--

DROP TABLE IF EXISTS `dd_study`;


CREATE TABLE `dd_study` (
  `id` int(11) NOT NULL DEFAULT '0',
  `ident_title` text,
  `ident_abbr` text,
  `ident_study_type` text,
  `ident_ser_info` text,
  `ident_trans_title` text,
  `ident_id` text,
  `ver_desc` text,
  `ver_prod_date` int(11) DEFAULT NULL,
  `ver_notes` text,
  `overview_abstract` text,
  `overview_kind_of_data` text,
  `overview_analysis` text,
  `overview_methods` text,
  `scope_definition` text,
  `scope_class` text,
  `coverage_country` text,
  `coverage_geo` text,
  `coverage_universe` text,
  `prod_s_investigator` text,
  `prod_s_other_prod` text,
  `prod_s_funding` text,
  `prod_s_acknowledgements` text,
  `sampling_procedure` text,
  `sampling_dev` text,
  `sampling_rates` text,
  `sampling_weight` text,
  `coll_dates` text,
  `coll_periods` text,
  `coll_mode` text,
  `coll_notes` text,
  `coll_questionnaire` text,
  `coll_collectors` text,
  `coll_supervision` text,
  `process_editing` text,
  `process_other` text,
  `appraisal_error` text,
  `appraisal_other` text,
  `access_authority` text,
  `access_confidentiality` text,
  `access_conditions` text,
  `access_cite_require` text,
  `disclaimer_disclaimer` text,
  `disclaimer_copyright` text,
  `contacts_contacts` text,
  `citations` text,
  `ident_ddp_id` text,
  `scope_keywords` text,
  `ident_subtitle` text,
  `operational_wb_name` text,
  `operational_wb_id` text,
  `operational_wb_net` text,
  `operational_wb_sector` text,
  `operational_wb_summary` text,
  `operational_wb_objectives` text,
  `impact_wb_name` text,
  `impact_wb_id` text,
  `impact_wb_area` text,
  `impact_wb_lead` text,
  `impact_wb_members` text,
  `impact_wb_description` text,
  PRIMARY KEY (`id`)
)  DEFAULT CHARSET=utf8;



--
-- Table structure for table `dd_study_type`
--

DROP TABLE IF EXISTS `dd_study_type`;


CREATE TABLE `dd_study_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `studytype` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
)  AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;


--
-- Dumping data for table `dd_study_type`
--

LOCK TABLES `dd_study_type` WRITE;
/*!40000 ALTER TABLE `dd_study_type` DISABLE KEYS */;
INSERT INTO `dd_study_type` VALUES (1,'--'),(2,'1-2-3 Survey, phase 3 [hh/123-3]'),(3,'Administrative Records, Health [ad/hea]'),(4,'Administrative Records, Education [ad/edu]'),(5,'Administrative Records, Other [ad/oth]'),(6,'Aggricultural Census [ag/census]'),(7,'Agricultural Survey [ag/oth]'),(8,'Child Labor Survey [hh/cls]'),(9,'Core Welfare Indicators Questionnaire [hh/cwiq]'),(10,'Demographic and Health Survey [hh/dhs]'),(11,'Demographic and Health Survey, Round 1 [hh/dhs-1]'),(12,'Demographic and Health Survey, Round 2 [hh/dhs-2]'),(13,'Demographic and Health Survey, Round 3 [hh/dhs-3]'),(14,'Demographic and Health Survey, Round 4 [hh/dhs-4]'),(15,'Demographic and Health Survey, Interim [hh/dhs-int]'),(16,'Demographic and Health Survey, Special [hh/dhs-sp]'),(17,'Enterprise Survey [en/oth]');
/*!40000 ALTER TABLE `dd_study_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dd_tasks`
--

DROP TABLE IF EXISTS `dd_tasks`;


CREATE TABLE `dd_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigner_id` int(11) NOT NULL,
  `date_assigned` int(11) NOT NULL,
  `date_completed` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `comments` text,
  PRIMARY KEY (`id`)
)  DEFAULT CHARSET=utf8;



--
-- Table structure for table `dd_tasks_team`
--

DROP TABLE IF EXISTS `dd_tasks_team`;


CREATE TABLE `dd_tasks_team` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
)  DEFAULT CHARSET=utf8;



--
-- Add data deposit to site admin menu
--

INSERT INTO `site_menu` VALUES 
(100,0,'Data deposit','admin/datadeposit',100,0,'datadeposit');

