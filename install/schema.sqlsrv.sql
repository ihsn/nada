--
-- Table structure for table repositories
--

CREATE TABLE repositories (
  id int NOT NULL IDENTITY(1,1),
  pid int DEFAULT NULL,
  repositoryid varchar(255) NOT NULL,
  title varchar(100) NOT NULL,
  url varchar(255) DEFAULT NULL,
  organization varchar(45) DEFAULT NULL,
  email varchar(45) DEFAULT NULL,
  country varchar(45) DEFAULT NULL,
  status varchar(255) DEFAULT NULL,
  surveys_found int DEFAULT NULL,
  changed int DEFAULT NULL,
  type int  DEFAULT NULL,
  short_text varchar(1000) DEFAULT NULL,
  long_text varchar(max),
  thumbnail varchar(255) DEFAULT NULL,
  weight int  DEFAULT NULL,
  ispublished tinyint  DEFAULT NULL,
  section int DEFAULT NULL,
  group_da_public  int DEFAULT '0',
  group_da_licensed  int DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_repositories on [dbo].[repositories]
(
	[repositoryid] ASC
);


--
-- Table structure for table lic_files
--

CREATE TABLE lic_files (
  id int NOT NULL IDENTITY(1,1),
  surveyid int NOT NULL,
  file_name varchar(100) NOT NULL,
  file_path varchar(255) NOT NULL,
  changed int NOT NULL,
  PRIMARY KEY (id)
);



--
-- Table structure for table site_menu
--

CREATE TABLE site_menu (
  id int NOT NULL IDENTITY(1,1),
  pid int DEFAULT NULL,
  title varchar(100) DEFAULT NULL,
  url varchar(255) DEFAULT NULL,
  weight int DEFAULT NULL,
  depth int DEFAULT NULL,
  module varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Dumping data for table site_menu
--

set IDENTITY_INSERT site_menu ON;
INSERT INTO site_menu(id,pid,title,url,weight,depth,module)
 VALUES (1,0,'Dashboard','admin',0,0,'admin'),(2,0,'Studies','admin/catalog',1,0,'catalog'),(4,0,'Citations','admin/citations',3,0,'citations'),(5,0,'Users','admin/users',4,0,'users'),(6,0,'Menu','admin/menu',5,0,'menu'),(7,0,'Reports','admin/reports',6,0,'reports'),(8,0,'Settings','admin/configurations',7,0,'configurations'),(12,2,'-','-',70,1,'catalog'),(13,2,'Licensed requests','admin/licensed_requests',80,1,'catalog'),(14,2,'-','-',90,1,'catalog'),(15,2,'Manage collections','admin/collections',60,1,'repositories'),(17,4,'All citations','admin/citations',100,1,'citations'),(18,4,'Import citations','admin/citations/import',90,1,'citations'),(19,4,'Export citations','admin/citations/export',80,1,'citations'),(20,5,'All users','admin/users',100,1,'users'),(21,5,'Add user','admin/users/add',99,1,'users'),(22,5,'-','-',65,1,'users'),(27,6,'All pages','admin/menu',0,1,'menu'),(28,7,'All reports','admin/reports',0,1,'reports'),(29,8,'Settings','admin/configurations',0,1,'configurations'),(30,8,'Countries','admin/countries',0,1,'vocabularies'),(31,8,'Regions','admin/regions',0,1,'vocabularies'),(32,8,'-','-',0,1,'vocabularies'),(33,8,'Vocabularies','admin/vocabularies',-9,1,'vocabularies'),(34,2,'Manage studies','admin/catalog',100,1,'catalog'),(35,5,'Impersonate user','admin/users/impersonate',50,1,'users');
set IDENTITY_INSERT site_menu OFF;

insert into site_menu(pid,title,url,weight,depth,module) 
	values (2,'-', '-',50,1,'catalog');
	
insert into site_menu(pid,title,url,weight,depth,module) 
	values (2,'Bulk access collections', 'admin/da_collections',40,1,'catalog');



--
-- Table structure for table vocabularies
--

CREATE TABLE vocabularies (
  vid int NOT NULL IDENTITY(1,1),
  title varchar(255) NOT NULL,
  PRIMARY KEY (vid)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_vocabularies on [dbo].[vocabularies](
	[title] ASC
);


--
-- Table structure for table variables
--



CREATE TABLE variables (
  uid int NOT NULL IDENTITY(1,1),
  sid int NOT NULL,
  fid nvarchar(45) DEFAULT '',
  vid nvarchar(45) DEFAULT '',
  name nvarchar(100) DEFAULT '',
  labl nvarchar(255) DEFAULT '',
  qstn nvarchar(max),
  catgry nvarchar(max),
  metadata nvarchar(max),
  keywords nvarchar(max),
  PRIMARY KEY (uid)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_variables on [dbo].[variables](
	[vid] ASC,
	[sid] ASC
);

CREATE INDEX IX_var_sidfk on [dbo].[variables](
	[sid] ASC
);



--
-- Table structure for table variable_groups
--

CREATE TABLE variable_groups (
  id int NOT NULL IdENTITY(1,1),
  sid int DEFAULT NULL,
  vgid varchar(45) DEFAULT NULL,
  variables varchar(5000) DEFAULT NULL,
  variable_groups varchar(500) DEFAULT NULL,
  group_type varchar(45) DEFAULT NULL,
  label varchar(255) DEFAULT NULL,
  universe varchar(255) DEFAULT NULL,
  notes varchar(500) DEFAULT NULL,
  txt varchar(500) DEFAULT NULL,
  definition varchar(500) DEFAULT NULL,
  PRIMARY KEY (id)
);



--
-- Table structure for table users_groups
--

CREATE TABLE users_groups (
  id int NOT NULL IDENTITY(1,1),
  user_id int DEFAULT NULL,
  group_id int DEFAULT NULL,
  PRIMARY KEY (id),
  --UNIQUE KEY user_group_UNQ (user_id,group_id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_users_groups on [dbo].[users_groups](
	[user_id] ASC,
	[group_id] ASC
);



--
-- Table structure for table survey_relationships
--

CREATE TABLE survey_relationships (
  id int NOT NULL IDENTITY(1,1),
  sid_1 int DEFAULT NULL,
  sid_2 int DEFAULT NULL,
  relationship_id int DEFAULT NULL,
  pair_id varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX IX_s_relationships on [dbo].[survey_relationships](
	[pair_id] ASC
);



--
-- Table structure for table survey_tags
--

CREATE TABLE survey_tags (
  id int NOT NULL IDENTITY(1,1),
  sid int NOT NULL,
  tag varchar(100) NOT NULL,
  PRIMARY KEY (id)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_survey_tags on [dbo].[survey_tags](
	[sid] ASC,
	[tag] ASC
);

-- tag lookup: SELECT sid FROM survey_tags WHERE tag IN (...)
CREATE NONCLUSTERED INDEX idx_survey_tags_tag ON [dbo].[survey_tags] ([tag] ASC) INCLUDE ([sid]);


--
-- Table structure for table meta
--

CREATE TABLE meta (
  id int NOT NULL IDENTITY(1,1),
  user_id int DEFAULT NULL,
  first_name varchar(50) DEFAULT NULL,
  last_name varchar(50) DEFAULT NULL,
  company varchar(100) DEFAULT NULL,
  phone varchar(20) DEFAULT NULL,
  country varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Table structure for table login_attempts
--

CREATE TABLE login_attempts (
  id int  NOT NULL IDENTITY(1,1),
  ip_address varchar(30) NOT NULL,
  login varchar(100) NOT NULL,
  time int  DEFAULT NULL,
  PRIMARY KEY (id)
) ;


--
-- Table structure for table repository_sections
--

CREATE TABLE repository_sections (
  id int  NOT NULL IDENTITY(1,1),
  title varchar(100) NOT NULL,
  weight int NOT NULL,
  PRIMARY KEY (id)
);

set IDENTITY_INSERT repository_sections ON;
INSERT INTO repository_sections (id,title,[weight])
VALUES (2,'Regional Collections',5),(3,'Specialized Collections',10);
set IDENTITY_INSERT repository_sections OFF;




--
-- Table structure for table survey_topics
--

CREATE TABLE survey_topics (
  sid int NOT NULL,
  tid int NOT NULL,
  uid int NOT NULL IDENTITY(1,1),
  PRIMARY KEY (uid)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_survey_topics on [dbo].[survey_topics](
	[tid] ASC,
	[sid] ASC
);



--
-- Table structure for table survey_citations
--

CREATE TABLE survey_citations (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  citationid int DEFAULT NULL,
  PRIMARY KEY (id)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_survey_cit on [dbo].[survey_citations](
	[sid] ASC,
	[citationid] ASC
);

CREATE NONCLUSTERED INDEX idx_survey_citations_citationid ON [dbo].[survey_citations] ([citationid]);



--
-- Table structure for table dcformats
--

CREATE TABLE dcformats (
  id int NOT NULL IDENTITY(1,1),
  title varchar(255) NOT NULL,
  PRIMARY KEY (id)
);


--
-- Dumping data for table dcformats
--

set IDENTITY_INSERT dcformats ON;
INSERT INTO dcformats (id,title)
VALUES (1,'Compressed, Generic [application/x-compressed]'),(2,'Compressed, ZIP [application/zip]'),(3,'Data, CSPro [application/x-cspro]'),(4,'Data, dBase [application/dbase]'),(5,'Data, Microsoft Access [application/msaccess]'),(6,'Data, SAS [application/x-sas]'),(7,'Data, SPSS [application/x-spss]'),(8,'Data, Stata [application/x-stata]'),(9,'Document, Generic [text]'),(10,'Document, HTML [text/html]'),(11,'Document, Microsoft Excel [application/msexcel]'),(12,'Document, Microsoft PowerPoint [application/mspowerpoint'),(13,'Document, Microsoft Word [application/msword]'),(14,'Document, PDF [application/pdf]'),(15,'Document, Postscript [application/postscript]'),(16,'Document, Plain [text/plain]'),(17,'Document, WordPerfect [text/wordperfect]'),(18,'Image, GIF [image/gif]'),(19,'Image, JPEG [image/jpeg]'),(20,'Image, PNG [image/png]'),(21,'Image, TIFF [image/tiff]');
set IDENTITY_INSERT dcformats OFF;


--
-- Table structure for table group_repo_access
--

CREATE TABLE group_repo_access (
  id int NOT NULL IDENTITY(1,1),
  group_id int DEFAULT NULL,
  repo_id int DEFAULT NULL,
  PRIMARY KEY (id)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_grp_repo_access on [dbo].[group_repo_access](
	[group_id] ASC,
	[repo_id] ASC
);




--
-- Table structure for table surveys
--

CREATE TABLE surveys (
  id int NOT NULL IDENTITY(1,1),
  idno varchar(200) NOT NULL,
  doi varchar(200) DEFAULT NULL,
  type varchar(15) DEFAULT NULL,
  repositoryid varchar(128) NOT NULL,
  title varchar(255) DEFAULT '',
  subtitle varchar(255) DEFAULT '',
  abbreviation varchar(45) DEFAULT NULL,
  authoring_entity varchar(max) DEFAULT NULL,
  nation varchar(150) DEFAULT '',
  year_start int DEFAULT '0',
  year_end int DEFAULT '0',
  metafile varchar(255) DEFAULT NULL,
  dirpath varchar(255) DEFAULT NULL,
  varcount int DEFAULT '0',
  link_technical varchar(255) DEFAULT NULL,
  link_study varchar(255) DEFAULT NULL,
  link_report varchar(255) DEFAULT NULL,
  link_indicator varchar(255) DEFAULT NULL,
  link_questionnaire varchar(255) DEFAULT NULL,
  formid int DEFAULT NULL,
  data_class_id int DEFAULT NULL,
  data_structure_id int DEFAULT NULL,
  ts_db_id int DEFAULT NULL,
  link_da varchar(255) DEFAULT NULL,
  published tinyint DEFAULT NULL,  
  total_views int DEFAULT '0',
  total_downloads int DEFAULT '0',
  stats_last_updated int DEFAULT NULL,
  changed int DEFAULT NULL,
  created int DEFAULT NULL,
  created_by int DEFAULT NULL,
  changed_by int DEFAULT NULL,
  thumbnail varchar(300) DEFAULT NULL,
  metadata varchar(max),
  var_keywords varchar(max),
  keywords varchar(max),
  abstract nvarchar(500) NULL,  
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_surveys on [dbo].[surveys](
	[id] ASC,
	[repositoryid] ASC
);

-- Filter and sort indexes for catalog search
CREATE NONCLUSTERED INDEX idx_surveys_published ON [dbo].[surveys] ([published] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_type ON [dbo].[surveys] ([type] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_repositoryid ON [dbo].[surveys] ([repositoryid] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_formid ON [dbo].[surveys] ([formid] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_data_class_id ON [dbo].[surveys] ([data_class_id] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_data_structure_id ON [dbo].[surveys] ([data_structure_id] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_ts_db_id ON [dbo].[surveys] ([ts_db_id] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_year_start ON [dbo].[surveys] ([year_start] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_total_views ON [dbo].[surveys] ([total_views] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_changed ON [dbo].[surveys] ([changed] ASC);
CREATE NONCLUSTERED INDEX idx_surveys_created ON [dbo].[surveys] ([created] ASC);


--
-- Table structure for table dctypes
--

CREATE TABLE dctypes (
  id int NOT NULL IDENTITY(1,1),
  code varchar(64) NOT NULL,
  title varchar(255) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_dctypes_code UNIQUE (code)
);


--
-- Dumping data for table dctypes
--
SET IDENTITY_INSERT dctypes ON;
INSERT INTO dctypes (id, code, title) VALUES
(1,'doc/adm','Document, Administrative'),
(2,'doc/anl','Document, Analytical'),
(3,'doc/oth','Document, Other'),
(4,'doc/qst','Document, Questionnaire'),
(5,'doc/ref','Document, Reference'),
(6,'doc/rep','Document, Report'),
(7,'doc/tec','Document, Technical'),
(8,'aud','Audio'),
(9,'dat','Database'),
(10,'map','Map'),
(11,'dat/micro','Microdata File'),
(12,'pic','Photo'),
(13,'prg','Program'),
(14,'tbl','Table'),
(15,'vid','Video'),
(16,'web','Web Site'),
(17,'dat/geo','Data, Geospatial'),
(18,'dat/table','Data, Table'),
(19,'dat/doc','Data, Document');
SET IDENTITY_INSERT dctypes OFF;

--
-- Table structure for table dctype_translations
--

CREATE TABLE dctype_translations (
  id int NOT NULL IDENTITY(1,1),
  dctype_id int NOT NULL,
  lang varchar(32) NOT NULL,
  title varchar(255) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_dctype_lang UNIQUE (dctype_id, lang),
  CONSTRAINT fk_dctype_translations_dctype FOREIGN KEY (dctype_id) REFERENCES dctypes (id) ON DELETE CASCADE
);
CREATE INDEX idx_dctype_translations_lang ON dctype_translations (lang);

--
-- Dumping data for table dctype_translations (English from dctypes.title)
--
SET IDENTITY_INSERT dctype_translations ON;
INSERT INTO dctype_translations (id, dctype_id, lang, title) VALUES
(1,1,'en','Document, Administrative'),
(2,2,'en','Document, Analytical'),
(3,3,'en','Document, Other'),
(4,4,'en','Document, Questionnaire'),
(5,5,'en','Document, Reference'),
(6,6,'en','Document, Report'),
(7,7,'en','Document, Technical'),
(8,8,'en','Audio'),
(9,9,'en','Database'),
(10,10,'en','Map'),
(11,11,'en','Microdata File'),
(12,12,'en','Photo'),
(13,13,'en','Program'),
(14,14,'en','Table'),
(15,15,'en','Video'),
(16,16,'en','Web Site'),
(17,17,'en','Data, Geospatial'),
(18,18,'en','Data, Table'),
(19,19,'en','Data, Document');
SET IDENTITY_INSERT dctype_translations OFF;

--
-- Table structure for table codelists
--

CREATE TABLE codelists (
  id int NOT NULL IDENTITY(1,1),
  pid int NULL,
  name varchar(64) NOT NULL,
  agency varchar(64) NOT NULL CONSTRAINT df_codelists_agency DEFAULT 'NADA',
  version varchar(32) NOT NULL CONSTRAINT df_codelists_version DEFAULT '1.0',
  version_seq int NOT NULL,
  idno varchar(191) NULL,
  description varchar(255) DEFAULT NULL,
  status smallint NOT NULL CONSTRAINT df_codelists_status DEFAULT 0,
  created int NULL,
  changed int NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_codelists_identity UNIQUE (agency, name, version)
);
CREATE UNIQUE INDEX unq_codelists_idno ON codelists(idno) WHERE idno IS NOT NULL;
CREATE UNIQUE INDEX unq_codelists_family_seq ON codelists(agency, name, version_seq);
CREATE INDEX idx_codelists_agency_name ON codelists(agency, name);
CREATE INDEX idx_codelists_pid ON codelists(pid);
ALTER TABLE codelists ADD CONSTRAINT fk_codelists_pid FOREIGN KEY (pid) REFERENCES codelists (id);

CREATE TABLE codelist_item (
  id int NOT NULL IDENTITY(1,1),
  codelist_id int NOT NULL,
  parent_id int DEFAULT NULL,
  code varchar(64) NOT NULL,
  title varchar(255) DEFAULT NULL,
  sort_order int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT unq_codelist_item_code UNIQUE (codelist_id, code),
  CONSTRAINT fk_codelist_item_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id) ON DELETE CASCADE,
  CONSTRAINT fk_codelist_item_parent FOREIGN KEY (parent_id) REFERENCES codelist_item (id) ON DELETE SET NULL
);
CREATE INDEX idx_codelist_item_parent ON codelist_item (parent_id);
CREATE INDEX idx_codelist_item_sort ON codelist_item (codelist_id, sort_order);

CREATE TABLE codelist_item_translation (
  id int NOT NULL IDENTITY(1,1),
  codelist_item_id int NOT NULL,
  lang varchar(32) NOT NULL,
  title varchar(255) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_codelist_item_trans UNIQUE (codelist_item_id, lang),
  CONSTRAINT fk_codelist_item_trans_item FOREIGN KEY (codelist_item_id) REFERENCES codelist_item (id) ON DELETE CASCADE
);
CREATE INDEX idx_codelist_item_trans_lang ON codelist_item_translation (lang);

CREATE TABLE codelist_group (
  id int NOT NULL IDENTITY(1,1),
  codelist_id int NOT NULL,
  name varchar(64) NOT NULL,
  sort_order int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT unq_codelist_group_name UNIQUE (codelist_id, name),
  CONSTRAINT fk_codelist_group_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id) ON DELETE CASCADE
);
CREATE INDEX idx_codelist_group_sort ON codelist_group (codelist_id, sort_order);

CREATE TABLE codelist_group_item (
  id int NOT NULL IDENTITY(1,1),
  codelist_group_id int NOT NULL,
  codelist_item_id int NOT NULL,
  sort_order int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT unq_codelist_group_item UNIQUE (codelist_group_id, codelist_item_id),
  CONSTRAINT fk_codelist_grp_item_grp FOREIGN KEY (codelist_group_id) REFERENCES codelist_group (id) ON DELETE CASCADE,
  CONSTRAINT fk_codelist_grp_item_item FOREIGN KEY (codelist_item_id) REFERENCES codelist_item (id) ON DELETE CASCADE
);

CREATE TABLE codelist_group_translation (
  id int NOT NULL IDENTITY(1,1),
  codelist_group_id int NOT NULL,
  lang varchar(32) NOT NULL,
  title varchar(255) NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_codelist_group_trans UNIQUE (codelist_group_id, lang),
  CONSTRAINT fk_codelist_group_trans_grp FOREIGN KEY (codelist_group_id) REFERENCES codelist_group (id) ON DELETE CASCADE
);
CREATE INDEX idx_codelist_group_trans_lang ON codelist_group_translation (lang);

--
-- Global data structures (DSD catalogue; one row per version)
--

CREATE TABLE data_structures (
  id int NOT NULL IDENTITY(1,1),
  pid int NULL,
  agency varchar(64) NOT NULL CONSTRAINT df_data_structures_agency DEFAULT 'NADA',
  name varchar(64) NOT NULL,
  version varchar(32) NOT NULL,
  version_seq int NOT NULL,
  idno varchar(191) NULL,
  status smallint NOT NULL CONSTRAINT df_data_structures_status DEFAULT 0,
  title varchar(255) NULL,
  description varchar(255) NULL,
  notes nvarchar(max) NULL,
  content_hash char(64) NULL,
  metadata nvarchar(max) NULL,
  created int NULL,
  updated int NULL,
  created_by int NULL,
  updated_by int NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_data_structures_identity UNIQUE (agency, name, version),
  CONSTRAINT fk_data_structures_pid FOREIGN KEY (pid) REFERENCES data_structures (id)
);
CREATE UNIQUE INDEX unq_data_structures_idno ON data_structures(idno) WHERE idno IS NOT NULL;
CREATE UNIQUE INDEX unq_data_structures_family_seq ON data_structures(agency, name, version_seq);
CREATE INDEX idx_data_structures_agency_name ON data_structures(agency, name);
CREATE INDEX idx_data_structures_pid ON data_structures(pid);

CREATE TABLE data_structure_components (
  id int NOT NULL IDENTITY(1,1),
  data_structure_id int NOT NULL,
  sort_order int NOT NULL CONSTRAINT df_dsc_sort_order DEFAULT 0,
  name varchar(100) NOT NULL,
  label varchar(255) NULL,
  description nvarchar(max) NULL,
  data_type varchar(16) NULL,
  column_type varchar(32) NOT NULL,
  time_period_format varchar(30) NULL,
  codelist_id int NULL,
  metadata nvarchar(max) NULL,
  created int NULL,
  updated int NULL,
  created_by int NULL,
  updated_by int NULL,
  PRIMARY KEY (id),
  CONSTRAINT unq_dsc_structure_name UNIQUE (data_structure_id, name),
  CONSTRAINT fk_dsc_data_structure FOREIGN KEY (data_structure_id) REFERENCES data_structures (id) ON DELETE CASCADE,
  CONSTRAINT fk_dsc_codelist FOREIGN KEY (codelist_id) REFERENCES codelists (id)
);
CREATE INDEX idx_dsc_structure_sort ON data_structure_components (data_structure_id, sort_order);
CREATE INDEX idx_dsc_codelist ON data_structure_components (codelist_id);

CREATE TABLE timeseries_value_counts (
  id int NOT NULL IDENTITY(1,1),
  sid int NOT NULL,
  dsd_id int NOT NULL,
  component_name varchar(100) NOT NULL,
  code varchar(255) NOT NULL,
  obs_count int NOT NULL CONSTRAINT df_tsvc_obs_count DEFAULT 0,
  PRIMARY KEY (id),
  CONSTRAINT unq_tsvc_scope_value UNIQUE (sid, dsd_id, component_name, code),
  CONSTRAINT fk_tsvc_sid FOREIGN KEY (sid) REFERENCES surveys (id) ON DELETE CASCADE,
  CONSTRAINT fk_tsvc_dsd FOREIGN KEY (dsd_id) REFERENCES data_structures (id) ON DELETE CASCADE
);
CREATE INDEX idx_tsvc_scope ON timeseries_value_counts (sid, dsd_id, component_name);

--
-- Link surveys.data_structure_id -> data_structures.id (declared after both tables exist)
--
ALTER TABLE surveys
  ADD CONSTRAINT fk_surveys_data_structure FOREIGN KEY (data_structure_id) REFERENCES data_structures (id) ON DELETE NO ACTION;

--
-- Dumping data for codelists (dctypes codelist + default groups)
--
SET IDENTITY_INSERT codelists ON;
INSERT INTO codelists (id, pid, name, agency, version, version_seq, idno, description, status, created, changed) VALUES (1,NULL,'dctypes','NADA','1.0',1,'NADA_dctypes_1.0','Resource types (external resources)',0,NULL,NULL);
UPDATE codelists SET pid = 1 WHERE id = 1;
SET IDENTITY_INSERT codelists OFF;

SET IDENTITY_INSERT codelist_item ON;
INSERT INTO codelist_item (id, codelist_id, parent_id, code, title, sort_order) VALUES
(1,1,NULL,'doc/adm','Document, Administrative',0),
(2,1,NULL,'doc/anl','Document, Analytical',10),
(3,1,NULL,'doc/oth','Document, Other',20),
(4,1,NULL,'doc/qst','Document, Questionnaire',30),
(5,1,NULL,'doc/ref','Document, Reference',40),
(6,1,NULL,'doc/rep','Document, Report',50),
(7,1,NULL,'doc/tec','Document, Technical',60),
(8,1,NULL,'aud','Audio',70),
(9,1,NULL,'dat','Database',80),
(10,1,NULL,'map','Map',90),
(11,1,NULL,'dat/micro','Microdata File',100),
(12,1,NULL,'pic','Photo',110),
(13,1,NULL,'prg','Program',120),
(14,1,NULL,'tbl','Table',130),
(15,1,NULL,'vid','Video',140),
(16,1,NULL,'web','Web Site',150),
(17,1,NULL,'dat/geo','Data, Geospatial',160),
(18,1,NULL,'dat/table','Data, Table',170),
(19,1,NULL,'dat/doc','Data, Document',180);
SET IDENTITY_INSERT codelist_item OFF;

INSERT INTO codelist_item_translation (codelist_item_id, lang, title) VALUES
(1,'en','Document, Administrative'),
(2,'en','Document, Analytical'),
(3,'en','Document, Other'),
(4,'en','Document, Questionnaire'),
(5,'en','Document, Reference'),
(6,'en','Document, Report'),
(7,'en','Document, Technical'),
(8,'en','Audio'),
(9,'en','Database'),
(10,'en','Map'),
(11,'en','Microdata File'),
(12,'en','Photo'),
(13,'en','Program'),
(14,'en','Table'),
(15,'en','Video'),
(16,'en','Web Site'),
(17,'en','Data, Geospatial'),
(18,'en','Data, Table'),
(19,'en','Data, Document');

SET IDENTITY_INSERT codelist_group ON;
INSERT INTO codelist_group (id, codelist_id, name, sort_order) VALUES
(1,1,'questionnaires',10),
(2,1,'reports',20),
(3,1,'technical',30)
SET IDENTITY_INSERT codelist_group OFF;

INSERT INTO codelist_group_item (codelist_group_id, codelist_item_id, sort_order) VALUES
(1,4,0),
(2,6,0),
(3,7,0);

--
-- Table structure for table da_collections
--

CREATE TABLE da_collections (
  id int NOT NULL IDENTITY(1,1),
  title varchar(255) DEFAULT NULL,
  description varchar(1000) DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Table structure for table cache
--

CREATE TABLE cache (
  id bigint NOT NULL IDENTITY(1,1),
  uid varchar(100) NOT NULL,
  data varchar(max),
  created int DEFAULT NULL,
  expiry int DEFAULT NULL,
  PRIMARY KEY (id)
);


CREATE UNIQUE NONCLUSTERED INDEX IX_cache on [dbo].[cache](
	[uid] ASC
);


--
-- Table structure for table forms
--
CREATE TABLE forms (
  formid int NOT NULL IDENTITY(1,1),
  fname varchar(255) DEFAULT '',
  model varchar(255) DEFAULT '',
  path varchar(255) DEFAULT '',
  iscustom char(2) DEFAULT '0',
  PRIMARY KEY (formid)
);


--
-- Dumping data for table forms
--
set IDENTITY_INSERT forms ON;
INSERT INTO forms (formid,fname,model,path,iscustom) VALUES 
(2,'Public use files','public','orderform.php','1'),
(1,'Direct access','direct','direct.php','1'),
(3,'Licensed data files','licensed','licensed.php','1'),
(4,'Data accessible only in data enclave','data_enclave','Application for Access to a Data Enclave.pdf','0'),
(5,'Data available from external repository','remote','remote','1'),
(6,'Data not available','data_na','data_na','1'),
(7,'Open access','open','open','1');
set IDENTITY_INSERT forms OFF;


--
-- Table structure for table lic_requests
--

CREATE TABLE lic_requests (
  id int NOT NULL IDENTITY(1,1),
  userid int NOT NULL,
  request_title varchar(300),
  org_rec varchar(200) DEFAULT NULL,
  org_type varchar(45) DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  tel varchar(150) DEFAULT NULL,
  fax varchar(100) DEFAULT NULL,
  datause varchar(max),
  outputs varchar(max),
  compdate varchar(45) DEFAULT NULL,
  datamatching int DEFAULT NULL,
  mergedatasets varchar(max),
  team varchar(max),
  dataset_access varchar(20) DEFAULT 'whole',
  created int DEFAULT NULL,
  status varchar(45) DEFAULT NULL,
  comments varchar(max),
  locked tinyint DEFAULT NULL,
  orgtype_other varchar(145) DEFAULT NULL,
  updated int DEFAULT NULL,
  updatedby varchar(45) DEFAULT NULL,
  ip_limit varchar(255) DEFAULT NULL,
  expiry_date int DEFAULT NULL,
  additional_info varchar(max),
  PRIMARY KEY (id)
);



--
-- Table structure for table citations
--

CREATE TABLE citations (
  id int NOT NULL IDENTITY(1,1),
  uuid varchar(50) NOT NULL,
  title varchar(255) NOT NULL,
  subtitle varchar(255) DEFAULT NULL,
  alt_title varchar(255) DEFAULT NULL,
  authors varchar(600),
  editors varchar(600),
  translators varchar(600),
  changed int DEFAULT NULL,
  created int DEFAULT NULL,
  published tinyint DEFAULT '1',
  volume varchar(45) DEFAULT NULL,
  issue varchar(45) DEFAULT NULL,
  idnumber varchar(45) DEFAULT NULL,
  edition varchar(45) DEFAULT NULL,
  place_publication varchar(255) DEFAULT NULL,
  place_state varchar(255) DEFAULT NULL,
  publisher varchar(255) DEFAULT NULL,
  publication_medium tinyint DEFAULT '0',
  url varchar(255) DEFAULT NULL,
  page_from varchar(25) DEFAULT NULL,
  page_to varchar(25) DEFAULT NULL,
  data_accessed varchar(45) DEFAULT NULL,
  organization varchar(255) DEFAULT NULL,
  ctype varchar(45) NOT NULL,
  pub_day varchar(15) DEFAULT NULL,
  pub_month varchar(45) DEFAULT NULL,
  pub_year int DEFAULT NULL,
  abstract varchar(max),
  keywords varchar(max),
  notes varchar(max),
  doi varchar(255) DEFAULT NULL,
  flag varchar(45) DEFAULT NULL,
  owner varchar(255) DEFAULT NULL,
  country varchar(100) DEFAULT NULL,
  url_status varchar(50) DEFAULT NULL,
  created_by int DEFAULT NULL,
  changed_by int DEFAULT NULL,
  attachment varchar(300) DEFAULT NULL,
  lang varchar(50) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE NONCLUSTERED INDEX idx_citations_published  ON [dbo].[citations] ([published]);
CREATE NONCLUSTERED INDEX idx_citations_ctype       ON [dbo].[citations] ([ctype]);
CREATE NONCLUSTERED INDEX idx_citations_pub_year    ON [dbo].[citations] ([pub_year]);
CREATE NONCLUSTERED INDEX idx_citations_flag        ON [dbo].[citations] ([flag]);
CREATE NONCLUSTERED INDEX idx_citations_url_status  ON [dbo].[citations] ([url_status]);
CREATE NONCLUSTERED INDEX idx_citations_created_by  ON [dbo].[citations] ([created_by]);
CREATE NONCLUSTERED INDEX idx_citations_changed_by  ON [dbo].[citations] ([changed_by]);

--
-- Table structure for table survey_aliases
--

CREATE TABLE survey_aliases (
  id int  NOT NULL IDENTITY(1,1),
  sid int  NOT NULL,
  alternate_id varchar(255) NOT NULL,
  PRIMARY KEY (id)
);


CREATE UNIQUE NONCLUSTERED INDEX IX_survey_alias on [dbo].[survey_aliases](
	[alternate_id] ASC
);



--
-- Table structure for table resources
--

CREATE TABLE [resources] (
  [resource_id] int NOT NULL IDENTITY(1,1),
  [resource_idno] nvarchar(100) DEFAULT NULL,
  [survey_id] int NOT NULL,
  [dctype] nvarchar(255) DEFAULT NULL,
  [resource_type] nvarchar(50) DEFAULT NULL,
  [filename] nvarchar(500) DEFAULT NULL,
  [is_url] tinyint DEFAULT 0,
  [checksum] nvarchar(64) DEFAULT NULL,
  [filesize] bigint DEFAULT NULL,
  [dcformat] nvarchar(255) DEFAULT NULL,
  [metadata] nvarchar(max) DEFAULT NULL,
  [title] nvarchar(500) NOT NULL,
  [subtitle] nvarchar(500) DEFAULT NULL,
  [author] nvarchar(500) DEFAULT NULL,
  [dcdate] nvarchar(45) DEFAULT NULL,
  [country] nvarchar(100) DEFAULT NULL,
  [language] nvarchar(255) DEFAULT NULL,
  [contributor] nvarchar(500) DEFAULT NULL,
  [publisher] nvarchar(500) DEFAULT NULL,
  [rights] nvarchar(max) DEFAULT NULL,
  [description] nvarchar(max) DEFAULT NULL,
  [abstract] nvarchar(max) DEFAULT NULL,
  [toc] nvarchar(max) DEFAULT NULL,
  [subjects] nvarchar(max) DEFAULT NULL,
  [data_file_id] int DEFAULT NULL,
  [sort_order] int DEFAULT 0,
  [status] nvarchar(20) DEFAULT NULL,
  [created] int DEFAULT NULL,
  [created_by] int DEFAULT NULL,
  [changed] int DEFAULT NULL,
  [changed_by] int DEFAULT NULL,
  PRIMARY KEY ([resource_id])
);

CREATE NONCLUSTERED INDEX [idx_res_survey_id] ON [resources] ([survey_id] ASC);
CREATE NONCLUSTERED INDEX [idx_res_resource_type] ON [resources] ([resource_type] ASC);


--
-- Table structure for table lic_files_log
--

CREATE TABLE lic_files_log (
  id int NOT NULL IDENTITY(1,1),
  requestid int NOT NULL,
  fileid int NOT NULL,
  ip varchar(20) NOT NULL,
  created int NOT NULL,
  username varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Table structure for table terms
--

CREATE TABLE terms (
  tid int NOT NULL IDENTITY(1,1),
  vid int NOT NULL,
  pid int DEFAULT NULL,
  title varchar(255) NOT NULL,
  PRIMARY KEY (tid)
);


--
-- Table structure for table users
--

CREATE TABLE users (
  id int NOT NULL IDENTITY(1,1),
  ip_address char(16) NOT NULL,
  username varchar(100) NOT NULL,
  password varchar(1000) NOT NULL,
  salt varchar(40) DEFAULT NULL,
  email varchar(100) NOT NULL,
  activation_code varchar(40) DEFAULT NULL,
  forgotten_password_code varchar(100) DEFAULT NULL,
  forgotten_code_expiry int default NULL,
  remember_code varchar(40) DEFAULT NULL,
  created_on int NOT NULL,
  last_login int NOT NULL,
  active tinyint DEFAULT NULL,
  authtype varchar(40) DEFAULT NULL,
  otp_code varchar(45) DEFAULT NULL,
  otp_expiry int DEFAULT NULL,
  forgot_request_ts INT NULL, 
  forgot_request_count INT DEFAULT 0,
  authtype varchar(40) DEFAULT NULL,
  authtype_id varchar(300) DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Table structure for table survey_countries
--

CREATE TABLE survey_countries (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  cid int DEFAULT NULL,
  country_name varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_surv_countries on [dbo].[survey_countries](
	[sid] ASC,
	[country_name] ASC
);

-- cid lookup: SELECT sid FROM survey_countries WHERE cid IN (...)
CREATE NONCLUSTERED INDEX idx_survey_countries_cid ON [dbo].[survey_countries] ([cid] ASC) INCLUDE ([sid]);


--
-- Table structure for table country_aliases
--

CREATE TABLE country_aliases (
  id int NOT NULL IDENTITY(1,1),
  countryid int NOT NULL,
  alias varchar(100) NOT NULL,
  PRIMARY KEY (id)
--  UNIQUE KEY ix_alias_uniq (countryid,alias)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_country_alias on [dbo].[country_aliases](
	[countryid] ASC,
	[alias] ASC 
);



--
-- Table structure for table group_permissions
--

CREATE TABLE group_permissions (
  id int NOT NULL IDENTITY(1,1),
  group_id int NOT NULL,
  permission_id int NOT NULL,
  PRIMARY KEY (id)
--  UNIQUE KEY grp_perms_UNIQUE (group_id,permission_id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_grp_perms on [dbo].[group_permissions](
	[group_id] ASC,
	[permission_id] ASC 
);



--
-- Dumping data for table group_permissions
--

set IDENTITY_INSERT group_permissions ON;
INSERT INTO group_permissions (id,group_id,permission_id)
VALUES (5,1,2),(6,1,14),(292,3,1),(289,3,2),(301,3,3),(299,3,14),(293,3,41),(295,3,42),(296,3,43),(297,3,44),(291,3,46),(294,3,49),(300,3,61),(290,3,62),(298,3,63),(334,4,2),(339,4,3),(335,4,16),(338,4,61),(336,4,69),(337,4,70),(313,5,3),(312,5,71),(287,9,2),(288,9,63),(227,10,2),(229,10,3),(228,10,45);
set IDENTITY_INSERT group_permissions OFF;

--- permissions for citation manager group
INSERT INTO group_permissions (group_id, permission_id) VALUES (11, 64)
INSERT INTO group_permissions (group_id, permission_id) VALUES (11, 65)
INSERT INTO group_permissions (group_id, permission_id) VALUES (11, 66)
INSERT INTO group_permissions (group_id, permission_id) VALUES (11, 67)
INSERT INTO group_permissions (group_id, permission_id) VALUES (11, 68)

--
-- Table structure for table survey_repos
--

CREATE TABLE survey_repos (
  id int  NOT NULL IDENTITY(1,1),
  sid int  NOT NULL,
  repositoryid varchar(255) NOT NULL,
  isadmin tinyint  NOT NULL,
  PRIMARY KEY (id)
);

CREATE NONCLUSTERED INDEX idx_survey_repos_repositoryid ON [dbo].[survey_repos] ([repositoryid] ASC) INCLUDE ([sid]);
CREATE NONCLUSTERED INDEX idx_survey_repos_sid ON [dbo].[survey_repos] ([sid] ASC);


--
-- Table structure for table menus
--

CREATE TABLE menus (
  id int NOT NULL IDENTITY(1,1),
  url varchar(255) NOT NULL,
  title varchar(255) NOT NULL,
  body varchar(max),
  published tinyint DEFAULT NULL,
  target varchar(45) DEFAULT NULL,
  changed int DEFAULT NULL,
  linktype tinyint DEFAULT NULL,
  weight int DEFAULT NULL,
  pid int DEFAULT '0',
  PRIMARY KEY (id)
);


CREATE UNIQUE NONCLUSTERED INDEX IX_menus on [dbo].[menus](
	[url] ASC
);



--
-- Dumping data for table menus
--

set IDENTITY_INSERT menus ON;
INSERT INTO menus (id,url,title,body,published,target,changed,linktype,weight,pid) VALUES
(53,'catalog','Microdata  Catalog','',1,'0',1300807037,1,1,0),
(55,'citations','Citations',NULL,1,'0',1281460217,1,2,0),
(56,'home','Home',NULL,1,'0',1281460217,1,0,0);
set IDENTITY_INSERT menus OFF;



--
-- Table structure for table url_mappings
--

CREATE TABLE url_mappings (
  id int NOT NULL IDENTITY(1,1),
  source varchar(255) DEFAULT NULL,
  target varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
) ;


--
-- Table structure for table groups
--

CREATE TABLE groups (
  id tinyint NOT NULL IDENTITY(1,1),
  name varchar(100) NOT NULL,
  description varchar(255) NOT NULL,
  group_type varchar(40) DEFAULT NULL,
  access_type varchar(45) DEFAULT NULL,
  weight int DEFAULT '0',
  is_collection_group tinyint DEFAULT '0',
  PRIMARY KEY (id)
);


--
-- Dumping data for table groups
--

set IDENTITY_INSERT groups ON;
INSERT INTO groups (id,name,description,group_type,access_type,weight,is_collection_group)
VALUES 
(1,'admin','It is the site administrator and has access to all site content','admin','unlimited',0,0),
(2,'user','General user account with no access to site administration','user','none',-99,0),
(3,'Collection administrators','Users can manage and review studies for collections they are assigned to','admin','limited',0,1),
(5,'Report viewer','Can only generate/view reports','admin','limited',0,0),
(11,'Citation manager','has full control over the citations','admin','limited',0,0),
(12,'Global Licensed Reviewer','This account can review licensed data requests from all collections','admin','limited',0,0);
set IDENTITY_INSERT groups OFF;



--
-- Table structure for table survey_relationship_types
--

CREATE TABLE survey_relationship_types (
  id int NOT NULL IDENTITY(1,1),
  rel_group_id int DEFAULT NULL,
  rel_name varchar(45) DEFAULT NULL,
  rel_dir tinyint DEFAULT NULL,
  rel_cordinality varchar(10) DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Dumping data for table survey_relationship_types
--

set IDENTITY_INSERT survey_relationship_types ON;
INSERT INTO survey_relationship_types (id,rel_group_id,rel_name, rel_dir, rel_cordinality)
VALUES (0,0,'isRelatedTo',0,'1:1'),(1,1,'isHarmonized',0,'N:1'),(2,1,'isMasterOf',1,'1:N'),(3,3,'isParentOf ',0,'1:N'),(4,3,'isChildOf',1,'N:1'),(5,5,'isAnnoynimizedVersionOf ',0,'N:1'),(6,5,'isMasterOf',1,NULL),(7,7,'isSubsetOf ',0,NULL),(8,7,'isMasterOf',1,NULL),(9,9,'containsStandardizedVersion ',0,NULL),(10,9,'isOriginalVersion',1,NULL),(11,11,'isWaveOf',2,'1:1'),(13,13,'isRevisedVersionOf',0,NULL),(14,13,'isOlderVersionOf',1,NULL);
set IDENTITY_INSERT survey_relationship_types OFF;


--
-- Table structure for table lic_requests_history
--

CREATE TABLE lic_requests_history (
  id int NOT NULL IDENTITY(1,1),
  lic_req_id int DEFAULT NULL,
  user_id varchar(100) DEFAULT NULL,
  logtype varchar(45) DEFAULT NULL,
  request_status varchar(45) DEFAULT NULL,
  description varchar(max),
  created int DEFAULT NULL,
  PRIMARY KEY (id)
) ;


--
-- Table structure for table da_collection_surveys
--

CREATE TABLE da_collection_surveys (
  id int NOT NULL IDENTITY(1,1),
  cid int DEFAULT NULL,
  sid int DEFAULT NULL,
  PRIMARY KEY (id)
--  UNIQUE KEY unq_coll_sid (cid,sid)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_da_coll_surveys on [dbo].[da_collection_surveys](
	[cid] ASC,
	[sid] ASC
);


--
-- Table structure for table tags
--

CREATE TABLE tags (
  id int NOT NULL IDENTITY(1,1),
  tag varchar(100) NOT NULL,
  PRIMARY KEY (id)
--  UNIQUE KEY tag_UNIQUE (tag)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_tags on [dbo].[tags](
	[tag] ASC
);



--
-- Table structure for table permissions
--

CREATE TABLE permissions (
  id int NOT NULL IDENTITY(1,1),
  label varchar(45) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  section varchar(45) DEFAULT NULL,
  weight int DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Dumping data for table permissions
--
set IDENTITY_INSERT permissions ON;
INSERT INTO permissions (id,label,description,section,weight)
VALUES (1,'Upload DDI file','this is a test description','catalog',3),(2,'View catalog','this is a test description','catalog',0),(3,'Access site administration','this is a test description','site_admin',0),(4,'Access Menus','this is a test description','menu_admin',0),(5,'Add menu page','this is a test description','menu_admin',0),(6,'Edit menu','this is a test description','menu_admin',0),(7,'Add menu link','this is a test description','menu_admin',0),(8,'Sort menu items','this is a test description','menu_admin',0),(9,'Access vocabularies','this is a test description','vocab',0),(10,'Access vocabulary terms','this is a test description','vocab',0),(11,'View user accounts','View list of all user accounts','user_admin',0),(12,'Edit user information','this is a test description','user_admin',0),(14,'Access DDI Browser','this is a test description','ddibrowser',0),(16,'Access site pages','this is a test description','general_site',0),(18,'View citations','this is a test description','general_site',0),(22,'Site backup','this is a test description','site_admin',0),(23,'View licensed request form','this is a test description','general_site',0),(25,'Switch site language','this is a test description','general_site',0),(27,'Translate site','this is a test description','site_admin',0),(30,'Public use files','this is a test description','general_site',0),(40,'Data Deposit','Data Deposit','site_admin',0),(41,'Publish/Unpublish study','Allows publishing study','catalog',3),(42,'Delete Study','delete study','catalog',4),(43,'Export DDI','Export','catalog',5),(44,'Import RDF','Import RDF for study resources','catalog',5),(45,'Manage Repositories','Manage repositories','repositories',9),(46,'Replace DDI','Replace a DDI file','catalog',3),(49,'Edit survey','Edit survey','catalog',4),(61,'Select collection','','repositories',1),(62,'Copy DDI','copy DDI','catalog',0),(63,'Copy studies from other collections','','catalog',6),(64,'View citations','','citation',1),(65,'Edit citation','','citation',2),(66,'Delete citation','Delete a citation','citation',3),(67,'Import citations','','citation',4),(68,'Export citations','Export citations to various formats','citation',5),(69,'View licensed requests','View list of licensed data requests','Licensed requests',0),(70,'Edit request','Edit a licensed data request','Licensed requests',1),(71,'Reports','View and generate admin reports','reports',0);
set IDENTITY_INSERT permissions OFF;


--
-- Table structure for table survey_years
--

CREATE TABLE survey_years (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  data_coll_year int DEFAULT NULL,
  PRIMARY KEY (id)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_sur_years on [dbo].[survey_years](
	[sid] ASC,
	[data_coll_year] ASC
);

CREATE INDEX IX_sur_yrs_sid on [dbo].[survey_years](
	[sid] ASC
);

-- year range filter: WHERE data_coll_year BETWEEN ? AND ?
-- Existing IX_sur_years has sid as leading column and cannot serve range scans on data_coll_year
CREATE NONCLUSTERED INDEX idx_survey_years_year_sid ON [dbo].[survey_years] ([data_coll_year] ASC) INCLUDE ([sid]);



--
-- Table structure for table region_countries
--

CREATE TABLE region_countries (
  id int NOT NULL IDENTITY(1,1),
  region_id int DEFAULT NULL,
  country_id int DEFAULT NULL,
  PRIMARY KEY (id)
) ;


--
-- Table structure for table survey_notes
--

CREATE TABLE survey_notes (
  id int  NOT NULL IDENTITY(1,1),
  sid int  DEFAULT NULL,
  note varchar(max) NOT NULL,
  type varchar(50) NOT NULL,
  userid int  NOT NULL,
  created int DEFAULT NULL,
  changed int DEFAULT NULL,
  PRIMARY KEY (id)
) ;



--
-- Table structure for table citation_authors
--

CREATE TABLE citation_authors (
  id int NOT NULL IDENTITY(1,1),
  cid int DEFAULT NULL,
  fname varchar(255) DEFAULT NULL,
  lname varchar(255) DEFAULT NULL,
  initial varchar(255) DEFAULT NULL,
  author_type varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
) ;

CREATE NONCLUSTERED INDEX idx_citation_authors_cid_type ON [dbo].[citation_authors] ([cid], [author_type]);



--
-- Table structure for table countries
--

CREATE TABLE countries (
  countryid int NOT NULL IDENTITY(1,1),
  name varchar(65) NOT NULL,
  iso varchar(3) NOT NULL,
  PRIMARY KEY (countryid)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_countries on [dbo].[countries](
	[iso] ASC
);


--
-- Dumping data for table countries
--

set IDENTITY_INSERT countries ON;
INSERT INTO countries (countryid,name,iso)
VALUES (1,'Afghanistan','AFG'),(2,'Albania','ALB'),(3,'Antartica','ATA'),(4,'Algeria','DZA'),(5,'American Samoa','ASM'),(6,'Andorra','AND'),(7,'Angola','AGO'),(8,'Antigua and Barbuda','ATG'),(9,'Azerbaijan','AZE'),(10,'Argentina','ARG'),(11,'Australia','AUS'),(12,'Austria','AUT'),(13,'Bahamas','BHS'),(14,'Bahrain','BHR'),(15,'Bangladesh','BGD'),(16,'Armenia','ARM'),(17,'Barbados','BRB'),(18,'Belgium','BEL'),(19,'Bermuda','BMU'),(20,'Bhutan','BTN'),(21,'Bolivia','BOL'),(22,'Bosnia-Herzegovina','BIH'),(23,'Botswana','BWA'),(24,'Bouvet Island','BVT'),(25,'Brazil','BRA'),(26,'Belize','BLZ'),(27,'British Indian Ocean Territory','IOT'),(28,'Solomon Islands','SLB'),(29,'Virgin Isld. (British)','VGB'),(30,'Brunei','BRN'),(31,'Bulgaria','BGR'),(32,'Myanmar','MMR'),(33,'Burundi','BDI'),(34,'Belarus','BLR'),(35,'Cambodia','KHM'),(36,'Cameroon','CMR'),(37,'Canada','CAN'),(38,'Cape Verde','CPV'),(39,'Cayman Islands','CYM'),(40,'Central African Republic','CAF'),(41,'Sri Lanka','LKA'),(42,'Chad','TCD'),(43,'Chile','CHL'),(44,'China','CHN'),(45,'Taiwan','TWN'),(46,'Christmas Island','CXR'),(47,'Cocos Isld.','CCK'),(48,'Colombia','COL'),(49,'Comoros','COM'),(50,'Mayotte','MYT'),(51,'Congo, Rep.','COG'),(52,'Congo, Dem. Rep.','COD'),(53,'Cook Island','COK'),(54,'Costa Rica','CRI'),(55,'Croatia','HRV'),(56,'Cuba','CUB'),(57,'Cyprus','CYP'),(58,'Czech Republic','CZE'),(59,'Benin','BEN'),(60,'Denmark','DNK'),(61,'Dominica','DMA'),(62,'Dominican Republic','DOM'),(63,'Ecuador','ECU'),(64,'El Salvador','SLV'),(65,'Equatorial Guinea','GNQ'),(66,'Ethiopia','ETH'),(67,'Eritrea','ERI'),(68,'Estonia','EST'),(69,'Faeroe Isld.','FRO'),(70,'Falkland Isld.','FLK'),(71,'S. Georgia & S. Sandwich Isld.','SGS'),(72,'Fiji','FJI'),(73,'Finland','FIN'),(74,'France, Metrop.','FXX'),(75,'France','FRA'),(76,'French Guiana','GUF'),(77,'French Polynesia','PYF'),(78,'French S.T.','ATF'),(79,'Djibouti','DJI'),(80,'Gabon','GAB'),(81,'Georgia','GEO'),(82,'Gambia','GMB'),(83,'West Bank and Gaza','PSE'),(84,'Germany','DEU'),(85,'Ghana','GHA'),(86,'Gibraltar','GIB'),(87,'Kiribati','KIR'),(88,'Greece','GRC'),(89,'Greenland','GRL'),(90,'Grenada','GRD'),(91,'Guadeloupe','GLP'),(92,'Guam','GUM'),(93,'Guatemala','GTM'),(94,'Guinea','GIN'),(95,'Guyana','GUY'),(96,'Haiti','HTI'),(97,'Heard / McDonald Isld','HMD'),(98,'Holy See','VAT'),(99,'Honduras','HND'),(100,'Hungary','HUN'),(101,'Iceland','ISL'),(102,'India','IND'),(103,'Indonesia','IDN'),(104,'Iran, Islamic Rep.','IRN'),(105,'Iraq','IRQ'),(106,'Ireland','IRL'),(107,'Israel','ISR'),(108,'Italy','ITA'),(109,'Cote d''Ivoire','CIV'),(110,'Jamaica','JAM'),(111,'Japan','JPN'),(112,'Kazakhstan','KAZ'),(113,'Jordan','JOR'),(114,'Kenya','KEN'),(115,'Korea, Dem. Rep.','PRK'),(116,'Korea, Rep.','KOR'),(117,'Kuwait','KWT'),(118,'Kyrgyz Republic','KGZ'),(119,'Lao PDR','LAO'),(120,'Lebanon','LBN'),(121,'Lesotho','LSO'),(122,'Latvia','LVA'),(123,'Liberia','LBR'),(124,'Libya','LBY'),(125,'Liechtenstein','LIE'),(126,'Lithuania','LTU'),(127,'Luxembourg','LUX'),(128,'Macao','MAC'),(129,'Madagascar','MDG'),(130,'Malawi','MWI'),(131,'Malaysia','MYS'),(132,'Maldives','MDV'),(133,'Mali','MLI'),(134,'Malta','MLT'),(135,'Martinique','MTQ'),(136,'Mauritania','MRT'),(137,'Mauritius','MUS'),(138,'Mexico','MEX'),(139,'Monaco','MCO'),(140,'Mongolia','MNG'),(141,'Moldova','MDA'),(142,'Montserrat','MSR'),(143,'Morocco','MAR'),(144,'Mozambique','MOZ'),(145,'Oman','OMN'),(146,'Namibia','NAM'),(147,'Nauru','NRU'),(148,'Nepal','NPL'),(149,'Netherlands','NLD'),(150,'Neth.Antilles','ANT'),(151,'Aruba','ABW'),(152,'New Caledonia','NCL'),(153,'Vanuatu','VUT'),(154,'New Zealand','NZL'),(155,'Nicaragua','NIC'),(156,'Niger','NER'),(157,'Nigeria','NGA'),(158,'Niue','NIU'),(159,'Norfolk Isld.','NFK'),(160,'Norway','NOR'),(161,'N. Mariana Isld.','MNP'),(162,'US minor outlying Islands','UMI'),(163,'Micronesia','FSM'),(164,'Marshall Isld.','MHL'),(165,'Palau','PLW'),(166,'Pakistan','PAK'),(167,'Panama','PAN'),(168,'Papua New Guinea','PNG'),(169,'Paraguay','PRY'),(170,'Peru','PER'),(171,'Philippines','PHL'),(172,'Pitcairn Island','PCN'),(173,'Poland','POL'),(174,'Portugal','PRT'),(175,'Guinea Bissau','GNB'),(176,'Timor-Leste','TLS'),(177,'Puerto Rico','PRI'),(178,'Qatar','QAT'),(179,'Romania','ROM'),(180,'Russian Federation','RUS'),(181,'Rwanda','RWA'),(182,'St. Helena','SHN'),(183,'St.Kitts and Nevis','KNA'),(184,'Anguilla','AIA'),(185,'St. Lucia','LCA'),(186,'St. Pierre and Miquelon','SPM'),(187,'St. Vincent and Grenadines','VCT'),(188,'San Marino','SMR'),(189,'São Tomé and Príncipe','STP'),(190,'Saudi Arabia','SAU'),(191,'Senegal','SEN'),(192,'Seychelles','SYC'),(193,'Sierra Leone','SLE'),(194,'Singapore','SGP'),(195,'Slovak Republic','SVK'),(196,'Viet Nam','VNM'),(197,'Slovenia','SVN'),(198,'Somalia','SOM'),(199,'South Africa','ZAF'),(200,'Zimbabwe','ZWE'),(201,'Spain','ESP'),(202,'West. Sahara','ESH'),(203,'Sudan','SDN'),(204,'Suriname','SUR'),(205,'Svalbard and Jan Mayen Islands','SJM'),(206,'Swaziland','SWZ'),(207,'Sweden','SWE'),(208,'Switzerland','CHE'),(209,'Syrian Arab Republic','SYR'),(210,'Tajikistan','TJK'),(211,'Thailand','THA'),(212,'Togo','TGO'),(213,'Tokelau','TKL'),(214,'Tonga','TON'),(215,'Trinidad and Tobago','TTO'),(216,'United Arab Emirates','ARE'),(217,'Tunisia','TUN'),(218,'Turkey','TUR'),(219,'Turkmenistan','TKM'),(220,'Turks and Caicos Islands','TCA'),(221,'Tuvalu','TUV'),(222,'Uganda','UGA'),(223,'Ukraine','UKR'),(224,'Macedonia, FYR','MKD'),(225,'Egypt, Arab Rep.','EGY'),(226,'United Kingdom','GBR'),(227,'Tanzania','TZA'),(228,'United States','USA'),(229,'Virgin Islands, U.S.','VIR'),(230,'Burkina Faso','BFA'),(231,'Uruguay','URY'),(232,'Uzbekistan','UZB'),(233,'Venezuela, RB','VEN'),(234,'Wallis and Futuna','WLF'),(235,'Samoa','WSM'),(236,'Yemen','YEM'),(237,'Serbia and Montenegro','SCG'),(238,'Zambia','ZMB'),(239,'Westbank and Gaza','WBG'),(240,'Jerusalem','JER');
set IDENTITY_INSERT countries OFF;

--
-- Table structure for table repo_perms_groups
--

CREATE TABLE repo_perms_groups (
  repo_pg_id int NOT NULL IDENTITY(1,1),
  title varchar(45) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  weight int DEFAULT '0',
  PRIMARY KEY (repo_pg_id)
);


--
-- Dumping data for table repo_perms_groups
--

set IDENTITY_INSERT repo_perms_groups ON;
INSERT INTO repo_perms_groups(repo_pg_id,title,[description],[weight]) 
VALUES (1,'Manage studies (full access)','Full control over the studies including adding, updating, publishing, copying from other collections, etc.',0),(2,'Manage studies (limited access)','All access except can''t publish or unpublish studies',1),(3,'Manage licensed requests','Allows user to view and process licensed data requests for the collection',2),(4,'Reviewer','Allows user to review studies from the front-end regardless of study publish/unpublish status',3);
set IDENTITY_INSERT repo_perms_groups OFF;


--
-- Table structure for table user_repo_permissions
--

CREATE TABLE user_repo_permissions (
  id int NOT NULL IDENTITY(1,1),
  user_id int DEFAULT NULL,
  repo_id int DEFAULT NULL,
  repo_pg_id int DEFAULT NULL,
  PRIMARY KEY (id)
);



--
-- Table structure for table lic_file_downloads
--

CREATE TABLE lic_file_downloads (
  id int NOT NULL IDENTITY(1,1),
  fileid varchar(45) NOT NULL,
  downloads varchar(45) DEFAULT NULL,
  download_limit varchar(45) DEFAULT NULL,
  expiry int DEFAULT NULL,
  lastdownloaded int DEFAULT NULL,
  requestid int NOT NULL,
  PRIMARY KEY (id)
) ;



--
-- Table structure for table regions
--

CREATE TABLE regions (
  id int NOT NULL IDENTITY(1,1),
  pid int DEFAULT '0',
  title varchar(45) DEFAULT NULL,
  weight int DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- Dumping data for table regions
--

set IDENTITY_INSERT regions ON;
INSERT INTO regions(id,pid,title,[weight]) 
VALUES (1,0,'By Region',0),(2,1,'East Asia and Pacific',1),(3,1,'Europe and Central Asia',1),(4,1,'Latin America & the Caribbean',1),(5,1,'Middle East and North Africa',1),(6,1,'South Asia',1),(7,1,'Sub-Saharan Africa',1),(8,0,'By Income',0),(9,8,'Low-income economies',0),(10,8,'Lower-middle-income economies',1),(11,8,'Upper-middle-income economies',3),(12,8,'High-income economies',4),(13,8,'High-income OECD members',6);
set IDENTITY_INSERT regions OFF;


--
-- Table structure for table ci_sessions
--


CREATE TABLE ci_sessions (
  id varchar(128) NOT NULL,
  ip_address varchar(45) NOT NULL,
  timestamp int DEFAULT '0',
  data blob,
  PRIMARY KEY (id)
);

CREATE INDEX ci_sess_timestamp ON dbo.ci_sessions (timestamp);


--
-- Table structure for table public_requests
--

CREATE TABLE public_requests (
  id int NOT NULL IDENTITY(1,1),
  userid int NOT NULL,
  surveyid int DEFAULT NULL,
  title varchar(max) DEFAULT NULL,
  abstract varchar(max) NOT NULL,
  posted int NOT NULL,
  request_type varchar(45) DEFAULT 'study',
  collectionid varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
) ;



--
-- Table structure for table sitelogs
--

CREATE TABLE sitelogs (
  id int NOT NULL IDENTITY(1,1),
  sessionid varchar(255) NOT NULL DEFAULT '',
  logtime int NOT NULL DEFAULT 0,
  ip varchar(45) NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  logtype varchar(45) NOT NULL,
  surveyid int DEFAULT '0',
  section varchar(255) DEFAULT NULL,
  keyword varchar(max),
  username varchar(100) DEFAULT NULL,
  useragent varchar(300) DEFAULT NULL,
  PRIMARY KEY (id)
) ;

-- Performance indexes for sitelogs table
CREATE NONCLUSTERED INDEX idx_logtime ON sitelogs(logtime);
CREATE NONCLUSTERED INDEX idx_logtype ON sitelogs(logtype);
CREATE NONCLUSTERED INDEX idx_surveyid ON sitelogs(surveyid);
CREATE NONCLUSTERED INDEX idx_username ON sitelogs(username);
CREATE NONCLUSTERED INDEX idx_ip ON sitelogs(ip);
CREATE NONCLUSTERED INDEX idx_section ON sitelogs(section);
CREATE NONCLUSTERED INDEX idx_logtime_logtype ON sitelogs(logtime, logtype);
CREATE NONCLUSTERED INDEX idx_surveyid_logtime ON sitelogs(surveyid, logtime);



--
-- Table structure for table configurations
--

CREATE TABLE configurations (
  name varchar(200) NOT NULL,
  value varchar(5000) NOT NULL,
  label varchar(255) DEFAULT NULL,
  helptext varchar(255) DEFAULT NULL,
  item_group varchar(255) DEFAULT NULL,
  PRIMARY KEY (name)
) ;


--
-- Dumping data for table configurations
--

INSERT INTO configurations VALUES ('app_version','4.0.0-06.02.2013','Application version',NULL,NULL);
INSERT INTO configurations VALUES ('cache_default_expires','7200','Cache expiry (in mili seconds)',NULL,NULL);
INSERT INTO configurations VALUES ('cache_disabled','0','Enable/disable site caching',NULL,NULL);
INSERT INTO configurations VALUES ('cache_path','cache/','Site cache folder',NULL,NULL);
INSERT INTO configurations VALUES ('catalog_records_per_page','15','Catalog search page - records per page',NULL,NULL);
INSERT INTO configurations VALUES ('catalog_root','datafiles','Survey catalog folder',NULL,NULL);
INSERT INTO configurations VALUES ('collections_vocab','2','survey collections vocabulary',NULL,NULL);
INSERT INTO configurations VALUES ('collection_search','no',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('collection_search_weight','5',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('da_search','no',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('da_search_weight','2',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('db_version','4.0.0-06.02.2013','Database version',NULL,NULL);
INSERT INTO configurations VALUES ('ddi_import_folder','imports','Survey catalog import folder',NULL,NULL);
INSERT INTO configurations VALUES ('default_home_page','home','Default home page','Default home page',NULL);
INSERT INTO configurations VALUES ('html_folder','/pages',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('lang','en-us','Site Language','Site Language code',NULL);
INSERT INTO configurations VALUES ('language','english',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('login_timeout','40','Login timeout (minutes)',NULL,NULL);
INSERT INTO configurations VALUES ('mail_protocol','smtp','Select method for sending emails','Supported protocols: MAIL, SMTP, SENDMAIL',NULL);
INSERT INTO configurations VALUES ('min_password_length','5','Minimum password length',NULL,NULL);
INSERT INTO configurations VALUES ('news_feed_url','http://ihsn.org/nada/index.php?q=news/feed','','','');
INSERT INTO configurations VALUES ('regional_search','no','Enable regional search',NULL,NULL);
INSERT INTO configurations VALUES ('regional_search_weight','3',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('repository_identifier','default','Repository Identifier',NULL,NULL);
INSERT INTO configurations VALUES ('site_password_protect','no','Password protect website',NULL,NULL);
INSERT INTO configurations VALUES ('smtp_host','','SMTP Host name',NULL,NULL);
INSERT INTO configurations VALUES ('smtp_pass','','SMTP password',NULL,NULL);
INSERT INTO configurations VALUES ('smtp_port','25','SMTP port',NULL,NULL);
INSERT INTO configurations VALUES ('smtp_user','','SMTP username',NULL,NULL);
INSERT INTO configurations VALUES ('theme','default','Site theme name',NULL,NULL);
INSERT INTO configurations VALUES ('topics_vocab','1','Vocabulary ID for Topics',NULL,NULL);
INSERT INTO configurations VALUES ('topic_search','no','Topic search',NULL,NULL);
INSERT INTO configurations VALUES ('topic_search_weight','6',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('use_html_editor','yes','Use HTML editor for entering HTML for static pages',NULL,NULL);
INSERT INTO configurations VALUES ('website_footer','Powered by NADA 4.0 and DDI','Website footer text',NULL,NULL);
INSERT INTO configurations VALUES ('website_title','National Data Archive','Website title','Provide the title of the website','website');
INSERT INTO configurations VALUES ('website_url','http://localhost/nada','Website URL','URL of the website','website');
INSERT INTO configurations VALUES ('website_webmaster_email','nada@ihsn.org','Site webmaster email address','-','website');
INSERT INTO configurations VALUES ('website_webmaster_name','noreply','Webmaster name','-','website');
INSERT INTO configurations VALUES ('year_search','no',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('year_search_weight','1',NULL,NULL,NULL);


---------------------------------------------------------
-- SURVEYS 
---------------------------------------------------------

-- create a unique index or use the PK
CREATE UNIQUE INDEX pk_idx_surveys ON dbo.surveys(id);


-- create a fulltext catalog if not created already
CREATE FULLTEXT CATALOG ft AS DEFAULT;


--drop existing fulltext index
DROP FULLTEXT INDEX ON surveys;


--add table columns to index
CREATE FULLTEXT INDEX ON surveys
(
  keywords     Language 1033,
  var_keywords Language 1033
)
KEY INDEX pk_idx_surveys ; 



---------------------------------------------------------
-- VARIABLES
---------------------------------------------------------

-- create a unique index or use the PK
CREATE UNIQUE INDEX pk_idx_variables ON dbo.variables(uid);
go

--add table columns to index
CREATE FULLTEXT INDEX ON variables
( 
  catgry	Language 1033,
  labl		Language 1033,
  name		Language 1033,
  qstn		Language 1033
 ) 
KEY INDEX pk_idx_variables; 



---
--- Table structure for table featured_surveys
---

CREATE TABLE featured_surveys (
  id int NOT NULL IDENTITY(1,1),
  repoid int DEFAULT NULL,
  sid int DEFAULT NULL,
  weight int DEFAULT '0',
  PRIMARY KEY (id)
);


CREATE UNIQUE NONCLUSTERED INDEX IX_featured_surveys on [dbo].[featured_surveys](
	[repoid] ASC,
	[sid] ASC
);



--
-- Table structure for table survey_types
--

CREATE TABLE survey_types (
  id int NOT NULL identity(1,1),
  code varchar(50) NOT NULL,
  title varchar(250) DEFAULT NULL,
  weight int DEFAULT '0',
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_survey_types on [dbo].[survey_types](
	[code] ASC
);

set IDENTITY_INSERT survey_types ON;
INSERT INTO survey_types(id,code,title, weight) VALUES(1,'survey','Survey',100);
INSERT INTO survey_types(id,code,title, weight) VALUES(2,'geospatial','Geospatial',90);
INSERT INTO survey_types(id,code,title, weight) VALUES(3,'timeseries','Time series',80);
INSERT INTO survey_types(id,code,title, weight) VALUES(4,'document','Document',50);
INSERT INTO survey_types(id,code,title, weight) VALUES(5,'table','Table',70);
INSERT INTO survey_types(id,code,title, weight) VALUES(6,'image','Photo',40);
INSERT INTO survey_types(id,code,title, weight) VALUES(7,'script','Script',30);
INSERT INTO survey_types(id,code,title, weight) VALUES(8,'visualization','Visualization',60);
INSERT INTO survey_types(id,code,title, weight) VALUES(9,'video','Video',40);
INSERT INTO survey_types(id,code,title, weight) VALUES(10,'timeseriesdb','Datasets',75);
set IDENTITY_INSERT survey_types OFF;


-- 
-- Table structure for table 'survey_lic_requests'
--

CREATE TABLE survey_lic_requests (
  id int NOT NULL IDENTITY(1,1),
  request_id int NOT NULL,
  sid int NOT NULL,
  PRIMARY KEY (id)
);


CREATE UNIQUE NONCLUSTERED INDEX IX_survey_lic_req on [dbo].[survey_lic_requests](
	[request_id] ASC,
	[sid] ASC
);




-- 
-- Table structure for table 'data_files'
--
CREATE TABLE data_files (
  id int NOT NULL identity(1,1),
  sid int NOT NULL,
  file_id varchar(100) DEFAULT NULL,
  file_name varchar(255) DEFAULT NULL,
  description varchar(max),
  case_count int DEFAULT NULL,
  var_count int DEFAULT NULL,
  producer varchar(255) DEFAULT NULL,
  data_checks varchar(255) DEFAULT NULL,
  missing_data varchar(255) DEFAULT NULL,
  version varchar(255) DEFAULT NULL,
  notes varchar(255) DEFAULT NULL,
  metadata varchar(max) DEFAULT NULL,
  PRIMARY KEY (id)  
);

CREATE UNIQUE NONCLUSTERED INDEX IX_data_files on [dbo].[data_files](
	[sid] ASC,
	[file_id] ASC
);


--
-- API KEYS table
--
CREATE TABLE api_keys (
  id int NOT NULL identity(1,1),
  api_key varchar(40) NULL,
  key_hash varchar(255) NULL,
  key_prefix varchar(12) NULL,
  level int NOT NULL,
  ignore_limits tinyint NOT NULL DEFAULT '0',
  ip_addresses varchar(max),
  date_created int NOT NULL,
  expires_at int NULL,
  last_used_at int NULL,
  name varchar(255) NULL,
  revoked_at int NULL,
  created_by int NULL,
  user_id int DEFAULT NULL,
  is_private_key int NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
);

CREATE UNIQUE NONCLUSTERED INDEX IX_api_keys on [dbo].[api_keys](
	[api_key] ASC
);

CREATE NONCLUSTERED INDEX idx_key_prefix ON api_keys(key_prefix);
CREATE NONCLUSTERED INDEX idx_key_hash ON api_keys(key_hash);
CREATE NONCLUSTERED INDEX idx_expires_at ON api_keys(expires_at);
CREATE NONCLUSTERED INDEX idx_user_revoked ON api_keys(user_id, revoked_at);


--
-- API Logs table
--
CREATE TABLE api_logs (
  id int NOT NULL identity(1,1),
  uri varchar(255) NOT NULL,
  method varchar(6) NOT NULL,
  params varchar(max),
  user_id int default NULL,
  api_key varchar(40) NOT NULL,
  ip_address varchar(45) NOT NULL,
  time int NOT NULL,
  rtime float DEFAULT NULL,
  authorized varchar(1) NOT NULL,
  response_code smallint DEFAULT '0',
  PRIMARY KEY (id)
);

-- Performance indexes for api_logs table
CREATE NONCLUSTERED INDEX idx_api_logs_time ON api_logs(time);
CREATE NONCLUSTERED INDEX idx_api_logs_method ON api_logs(method);
CREATE NONCLUSTERED INDEX idx_api_logs_response_code ON api_logs(response_code);
CREATE NONCLUSTERED INDEX idx_api_logs_authorized ON api_logs(authorized);
CREATE NONCLUSTERED INDEX idx_api_logs_user_id ON api_logs(user_id);
CREATE NONCLUSTERED INDEX idx_api_logs_uri ON api_logs(uri);
CREATE NONCLUSTERED INDEX idx_api_logs_api_key ON api_logs(api_key);
CREATE NONCLUSTERED INDEX idx_api_logs_ip_address ON api_logs(ip_address);
CREATE NONCLUSTERED INDEX idx_api_logs_time_method ON api_logs(time, method);
CREATE NONCLUSTERED INDEX idx_api_logs_time_response_code ON api_logs(time, response_code);



CREATE TABLE data_files_resources (
  id INT NOT NULL identity(1,1),
  sid INT NULL,
  fid VARCHAR(45) NULL,
  resource_id INT NULL,
  file_format VARCHAR(45) NULL,
  api_use TINYINT NULL,
  PRIMARY KEY (id)
  );

CREATE UNIQUE NONCLUSTERED INDEX IX_data_files_resources on [dbo].[data_files_resources](
	[sid] ASC,
	[resource_id] ASC
);



CREATE TABLE survey_locations (
  id int NOT NULL identity(1,1),
  sid int DEFAULT NULL,
  location varchar(max) NOT NULL,
  PRIMARY KEY (id)  
);


CREATE TABLE filestore (
  id int NOT NULL identity(1,1),
  file_name varchar(255) DEFAULT NULL,
  file_path varchar(500) DEFAULT NULL,
  file_ext varchar(10) DEFAULT NULL,
  is_image tinyint DEFAULT NULL,
  changed int DEFAULT NULL,
  PRIMARY KEY (id)  
);

CREATE UNIQUE NONCLUSTERED INDEX IX_filestore on [dbo].[filestore](	
	[file_name] ASC
);



CREATE TABLE data_classifications (
  id int NOT NULL,
  code varchar(45) DEFAULT NULL,
  title varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_data_class on [dbo].[data_classifications](
	[code] ASC
);



INSERT INTO data_classifications (id,code,title) VALUES 
(1,'public','Public use'),
(2,'official','Official use'),
(3,'confidential','Confidential');


CREATE TABLE roles (
  id int NOT NULL identity(1,1),
  name varchar(100) NOT NULL,
  description varchar(255) NOT NULL,
  weight int DEFAULT '0',
  is_admin tinyint DEFAULT '0',
  is_locked tinyint DEFAULT '0',
  PRIMARY KEY (id)
);


set IDENTITY_INSERT roles ON;
insert into roles(id,name,description, weight, is_admin, is_locked) values 
(1,'admin','It is the site administrator and has access to all site content', 0,1,1),
(2,'user','General user account with no access to site administration', 0,1,1);
set IDENTITY_INSERT roles OFF;



CREATE TABLE role_permissions (
  id int NOT NULL identity(1,1),
  role_id varchar(45) NOT NULL,
  resource varchar(45) DEFAULT NULL,
  permissions varchar(500) DEFAULT NULL,
  PRIMARY KEY (id)
);


CREATE TABLE user_roles (
  id int NOT NULL identity(1,1),
  user_id int DEFAULT NULL,
  role_id int DEFAULT NULL,
  PRIMARY KEY (id)
);


--
-- migrate admins from previous version
--

insert into user_roles (user_id, role_id) 
	select user_id, group_id from users_groups;




CREATE TABLE data_access_whitelist (
  id int NOT NULL identity(1,1),
  user_id int DEFAULT NULL,
  repository_id int DEFAULT NULL,
  PRIMARY KEY (id)
);


CREATE TABLE repositories_acl (
  id int NOT NULL IDENTITY(1,1),
  user_id int NOT NULL,
  repository_id int NOT NULL,
  permission varchar(80) NOT NULL,
  created_by int NULL,
  created datetime2(0) NOT NULL CONSTRAINT DF_repositories_acl_created DEFAULT (SYSUTCDATETIME()),
  CONSTRAINT PK_repositories_acl PRIMARY KEY (id),
  CONSTRAINT UQ_repositories_acl_user_repository_permission UNIQUE (user_id, repository_id, permission)
);

CREATE NONCLUSTERED INDEX IX_repositories_acl_user_repository ON [dbo].[repositories_acl](
  [user_id] ASC,
  [repository_id] ASC
);

CREATE NONCLUSTERED INDEX IX_repositories_acl_repository_permission ON [dbo].[repositories_acl](
  [repository_id] ASC,
  [permission] ASC
);


CREATE TABLE widgets (
  id int NOT NULL identity(1,1),
  uuid varchar(100) NOT NULL,
  title varchar(250) NOT NULL,
  thumbnail varchar(300) DEFAULT NULL,
  description varchar(450) DEFAULT NULL,
  storage_path varchar(255) DEFAULT NULL,
  published int DEFAULT NULL,
  created int DEFAULT NULL,
  changed int DEFAULT NULL,
  created_by int DEFAULT NULL,
  changed_by int DEFAULT NULL,
  options varchar(max),
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_widgets on [dbo].[widgets](
    [uuid] ASC
);



CREATE TABLE survey_widgets (
  id int NOT NULL identity(1,1),
  sid int NOT NULL,
  widget_uuid varchar(145) NOT NULL,
  url varchar(500) DEFAULT NULL,
  PRIMARY KEY (id)
);


CREATE TABLE ts_databases (
  id int NOT NULL identity(1,1),
  idno varchar(150) DEFAULT NULL,
  title varchar(300) DEFAULT NULL,
  abstract varchar(max),
  published int DEFAULT NULL,
  created varchar(45) DEFAULT NULL,
  changed varchar(45) DEFAULT NULL,
  created_by int DEFAULT NULL,
  changed_by int DEFAULT NULL,
  metadata varchar(max),
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_ts_db on [dbo].[ts_databases](
    [idno] ASC
);

 

 
 CREATE TABLE facets (
  id int NOT NULL IDENTITY(1,1),
  name varchar(20) DEFAULT NULL,
  title varchar(45) DEFAULT NULL,
  facet_type varchar(10) DEFAULT NULL,
  enabled int DEFAULT '0',
  mappings varchar(max),
  PRIMARY KEY (id)
  );

CREATE UNIQUE NONCLUSTERED INDEX IX_facets on [dbo].[facets](
  [id] ASC
);


insert into facets(name,title,facet_type,enabled)
values
('year','Years','core',1),
('data_class','Data classifications','core',1),
('dtype','License','core',1),
('country','Countries','core',1),
('collection','Collections','core',1),
('type','Data types','core',1),
('tag','Tags','core',1);


CREATE TABLE facet_terms (
  id int NOT NULL IDENTITY(1,1),
  facet_id int DEFAULT NULL,
  value varchar(300) DEFAULT NULL,
  weight int DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_facet_terms on [dbo].[facet_terms](
  [id] ASC
);

 
CREATE TABLE survey_facets (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  facet_id int DEFAULT NULL,
  term_id int DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE NONCLUSTERED INDEX idx_survey_facets_term_id ON [dbo].[survey_facets] ([term_id] ASC) INCLUDE ([sid]);
CREATE NONCLUSTERED INDEX idx_survey_facets_sid ON [dbo].[survey_facets] ([sid] ASC);

INSERT INTO configurations VALUES ('facets_all','["year","data_class","dtype","country"]',NULL,NULL,NULL);
INSERT INTO configurations VALUES ('facets_microdata','["year","data_class","dtype","country"]',NULL,NULL,NULL);


CREATE TABLE survey_data_api (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  description varchar(500) DEFAULT NULL,
  db_id varchar(45) DEFAULT NULL,
  table_id varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
);


-- ============================================================
-- analytics
-- ============================================================

CREATE TABLE [analytics_pageview_events] (
    [id] bigint NOT NULL IDENTITY(1,1),
    [ts] datetime NOT NULL,
    [study_id] nvarchar(100) NOT NULL,
    [session_id] nvarchar(255) NULL,
    [hashed_ip] nchar(64) NULL,
    [user_agent] nvarchar(200) NULL,
    [referrer] nvarchar(512) NULL,
    PRIMARY KEY ([id])
);


CREATE NONCLUSTERED INDEX [idx_ts] ON [analytics_pageview_events] ([ts] ASC);
CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_pageview_events] ([study_id] ASC);
CREATE NONCLUSTERED INDEX [idx_session] ON [analytics_pageview_events] ([session_id] ASC);
CREATE NONCLUSTERED INDEX [idx_ts_study] ON [analytics_pageview_events] ([ts] ASC, [study_id] ASC);


CREATE TABLE [analytics_download_events] (
    [id] bigint NOT NULL IDENTITY(1,1),
    [ts] datetime NOT NULL,
    [study_id] nvarchar(100) NOT NULL,
    [file_name] nvarchar(255) NOT NULL,
    [file_type] nvarchar(50) NULL,
    [hashed_ip] nchar(64) NULL,
    [user_agent] nvarchar(200) NULL,
    PRIMARY KEY ([id])
);

CREATE NONCLUSTERED INDEX [idx_ts] ON [analytics_download_events] ([ts] ASC);
CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_download_events] ([study_id] ASC);
CREATE NONCLUSTERED INDEX [idx_study_file] ON [analytics_download_events] ([study_id] ASC, [file_name] ASC);
CREATE NONCLUSTERED INDEX [idx_ts_study_file] ON [analytics_download_events] ([ts] ASC, [study_id] ASC, [file_name] ASC);


CREATE TABLE [analytics_daily_studies] (
    [date] date NOT NULL,
    [study_id] nvarchar(100) NOT NULL,
    [pageviews] int NOT NULL DEFAULT 0,
    [unique_visitors] int NOT NULL DEFAULT 0,
    [downloads] int NOT NULL DEFAULT 0,
    PRIMARY KEY ([date], [study_id])
);

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_daily_studies] ([study_id] ASC);
CREATE NONCLUSTERED INDEX [idx_date] ON [analytics_daily_studies] ([date] ASC);

CREATE TABLE [analytics_monthly_studies] (
    [year] smallint NOT NULL,
    [month] tinyint NOT NULL,
    [study_id] nvarchar(100) NOT NULL,
    [pageviews] int NOT NULL DEFAULT 0,
    [unique_visitors] int NOT NULL DEFAULT 0,
    [downloads] int NOT NULL DEFAULT 0,
    [finalized] tinyint NOT NULL DEFAULT 0,
    [finalized_at] datetime NULL,
    PRIMARY KEY ([year], [month], [study_id])
);

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_monthly_studies] ([study_id] ASC);
CREATE NONCLUSTERED INDEX [idx_period] ON [analytics_monthly_studies] ([year] ASC, [month] ASC);

CREATE TABLE [analytics_daily_files] (
    [date] date NOT NULL,
    [study_id] nvarchar(100) NOT NULL,
    [file_name] nvarchar(255) NOT NULL,
    [downloads] int NOT NULL DEFAULT 0,
    PRIMARY KEY ([date], [study_id], [file_name])
);

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_daily_files] ([study_id] ASC);
CREATE NONCLUSTERED INDEX [idx_file] ON [analytics_daily_files] ([file_name] ASC);

CREATE TABLE [analytics_monthly_files] (
    [year] smallint NOT NULL,
    [month] tinyint NOT NULL,
    [study_id] nvarchar(100) NOT NULL,
    [file_name] nvarchar(255) NOT NULL,
    [downloads] int NOT NULL DEFAULT 0,
    [finalized] tinyint NOT NULL DEFAULT 0,
    [finalized_at] datetime NULL,
    PRIMARY KEY ([year], [month], [study_id], [file_name])
);

CREATE NONCLUSTERED INDEX [idx_study] ON [analytics_monthly_files] ([study_id] ASC);
CREATE NONCLUSTERED INDEX [idx_file] ON [analytics_monthly_files] ([file_name] ASC);
CREATE NONCLUSTERED INDEX [idx_period] ON [analytics_monthly_files] ([year] ASC, [month] ASC);


-- ============================================================
-- display templates
-- ============================================================

CREATE TABLE [display_templates] (
    [id] bigint NOT NULL IDENTITY(1,1),
    [uid] nvarchar(191) NOT NULL,
    [template_type] nvarchar(20) NOT NULL DEFAULT 'custom',
    [data_type] nvarchar(64) NOT NULL,
    [name] nvarchar(255) NOT NULL,
    [version] nvarchar(50) NULL,
    [organization] nvarchar(255) NULL,
    [author] nvarchar(255) NULL,
    [description] nvarchar(max) NULL,
    [status] nvarchar(20) NOT NULL DEFAULT 'draft',
    [template_json] nvarchar(max) NOT NULL,
    [is_deleted] bit NOT NULL DEFAULT 0,
    [created_by] int NULL,
    [changed_by] int NULL,
    [created_at] datetime2 NOT NULL DEFAULT SYSDATETIME(),
    [updated_at] datetime2 NOT NULL DEFAULT SYSDATETIME(),
    PRIMARY KEY ([id]),
    CONSTRAINT [unq_display_templates_uid] UNIQUE ([uid]),
    CONSTRAINT [ck_display_templates_template_type] CHECK ([template_type] IN ('system','custom','imported')),
    CONSTRAINT [ck_display_templates_status] CHECK ([status] IN ('draft','published','archived')),
    CONSTRAINT [ck_display_templates_template_json_isjson] CHECK (ISJSON([template_json])=1)
);

CREATE NONCLUSTERED INDEX [idx_display_templates_type_status] ON [display_templates] ([data_type] ASC, [status] ASC);
CREATE NONCLUSTERED INDEX [idx_display_templates_template_type] ON [display_templates] ([template_type] ASC);
CREATE NONCLUSTERED INDEX [idx_display_templates_not_deleted] ON [display_templates] ([is_deleted] ASC, [data_type] ASC);


CREATE TABLE [display_templates_default] (
    [id] bigint NOT NULL IDENTITY(1,1),
    [data_type] nvarchar(64) NOT NULL,
    [template_uid] nvarchar(191) NOT NULL,
    [created_by] int NULL,
    [updated_by] int NULL,
    [created_at] datetime2 NOT NULL DEFAULT SYSDATETIME(),
    [updated_at] datetime2 NOT NULL DEFAULT SYSDATETIME(),
    PRIMARY KEY ([id]),
    CONSTRAINT [unq_display_default_type] UNIQUE ([data_type])
);

CREATE NONCLUSTERED INDEX [idx_display_default_template_uid] ON [display_templates_default] ([template_uid] ASC);

