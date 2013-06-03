
-- TABLE STRUCTURE FOR: sitelogs
CREATE TABLE sitelogs (
  id int NOT NULL IDENTITY(1,1),
  sessionid varchar(255) NOT NULL DEFAULT '',
  logtime varchar(45) NOT NULL DEFAULT '0',
  ip varchar(45) NOT NULL,
  url varchar(255) NOT NULL DEFAULT '',
  logtype varchar(45) NOT NULL,
  surveyid int DEFAULT '0',
  section varchar(255) DEFAULT NULL,
  keyword varchar(500),
  username varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
);


-- TABLE STRUCTURE FOR: surveys
CREATE TABLE surveys (
  id int NOT NULL IDENTITY(1,1),
  repositoryid varchar(128) NOT NULL,
  surveyid varchar(200) DEFAULT NULL,
  titl varchar(255) DEFAULT '',
  titlstmt text,
  authenty varchar(255) DEFAULT NULL,
  geogcover varchar(255) DEFAULT NULL,
  nation varchar(100) DEFAULT '',
  topic text,
  scope text,
  sername varchar(255) DEFAULT NULL,
  producer varchar(255) DEFAULT NULL,
  sponsor varchar(255) DEFAULT NULL,
  refno varchar(255) DEFAULT NULL,
  proddate varchar(45) DEFAULT NULL,
  varcount decimal(10,0) DEFAULT NULL,
  ddifilename varchar(255) DEFAULT NULL,
  dirpath varchar(255) DEFAULT NULL,
  link_technical varchar(255) DEFAULT NULL ,
  link_study varchar(255) DEFAULT NULL ,
  link_report varchar(255) DEFAULT NULL,
  link_indicator varchar(255) DEFAULT NULL ,
  ddi_sh char(2) DEFAULT NULL,
  formid int DEFAULT NULL,
  isshared tinyint NOT NULL DEFAULT '1',
  isdeleted tinyint NOT NULL DEFAULT '0',
  changed varchar(255) DEFAULT NULL,
  created varchar(255) DEFAULT NULL,
  link_questionnaire varchar(255) DEFAULT NULL,
  countryid int DEFAULT NULL,
  data_coll_start int DEFAULT NULL,
  data_coll_end int DEFAULT NULL,
  abbreviation varchar(45) DEFAULT NULL,
  kindofdata varchar(255) DEFAULT NULL,
  keywords text,
  ie_program varchar(255) DEFAULT NULL,
  ie_project_id varchar(255) DEFAULT NULL,
  ie_project_name varchar(255) DEFAULT NULL,
  ie_project_uri varchar(255) DEFAULT NULL,
  ie_team_leaders text,
  project_id varchar(255) DEFAULT NULL,
  project_name varchar(255) DEFAULT NULL,
  project_uri varchar(255) DEFAULT NULL,
  link_da varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
)

-- UNIQUE INDEX FOR: Surveys
CREATE UNIQUE NONCLUSTERED INDEX [IX_surveys] ON [dbo].[surveys] 
(
	[surveyid] ASC,
	[repositoryid] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]



-- TABLE STRUCTURE FOR: variables
CREATE TABLE variables (
  uid int NOT NULL IDENTITY(1,1),
  varID varchar(45) DEFAULT '',
  name varchar(45) DEFAULT '',
  labl varchar(245) DEFAULT '',
  qstn text,
  catgry text,
  surveyid_FK int NOT NULL,
  PRIMARY KEY (uid)
);

CREATE NONCLUSTERED INDEX [IX_variables] ON [dbo].[variables] 
(
	[surveyid_FK] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

GO

-- TABLE STRUCTURE FOR: survey_collections
CREATE TABLE survey_collections (
  uid int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  tid int DEFAULT NULL,
  PRIMARY KEY (uid)
);
GO

-- TABLE STRUCTURE FOR: harvester
CREATE TABLE harvester_queue (
  id int NOT NULL IDENTITY(1,1),
  repositoryid varchar(50) NOT NULL,
  survey_url varchar(100) NOT NULL,
  status varchar(45) NOT NULL,
  ddi_local_path varchar(255) NOT NULL,
  changed integer NOT NULL,
  title varchar(255) NOT NULL,
  survey_timestamp int NOT NULL,
  retries integer DEFAULT '0',
  country varchar(255) DEFAULT NULL,
  survey_year int DEFAULT NULL,
  accesspolicy varchar(45) DEFAULT NULL,
  checksum varchar(255) DEFAULT NULL,
  surveyid varchar(200) DEFAULT NULL,
  PRIMARY KEY (id)
);
GO

-- TABLE STRUCTURE FOR: blocks
CREATE TABLE blocks (
  bid int NOT NULL IDENTITY(1,1),
  title varchar(255) DEFAULT NULL,
  body text,
  region varchar(255) DEFAULT NULL,
  weight int DEFAULT NULL,
  published int DEFAULT NULL,
  pages text,
  PRIMARY KEY (bid)
);


-- TABLE STRUCTURE FOR: citations
CREATE TABLE [dbo].[citations](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[title] [varchar](255) NOT NULL,
	[subtitle] [varchar](255) NULL,
	[alt_title] [varchar](255) NULL,
	[authors] [varchar](1000) NULL,
	[editors] [varchar](1000) NULL,
	[translators] [varchar](1000) NULL,
	[changed] [int] NULL,
	[created] [int] NULL,
	[published] [tinyint] NULL,
	[volume] [varchar](45) NULL,
	[issue] [varchar](45) NULL,
	[idnumber] [varchar](45) NULL,
	[edition] [varchar](45) NULL,
	[place_publication] [varchar](255) NULL,
	[place_state] [varchar](255) NULL,
	[publisher] [varchar](255) NULL,
	[publication_medium] [tinyint] NULL,
	[url] [varchar](255) NULL,
	[page_from] [varchar](5) NULL,
	[page_to] [varchar](5) NULL,
	[data_accessed] [varchar](45) NULL,
	[organization] [varchar](255) NULL,
	[ctype] [varchar](45) NOT NULL,
	[pub_day] [varchar](15) NULL,
	[pub_month] [varchar](45) NULL,
	[pub_year] [int] NULL,
	[abstract] [varchar](2000) NULL,
	[keywords] [varchar](2000) NULL,
	[notes] [varchar](1000) NULL,
	[doi] [varchar](255) NULL,
	[flag] [varchar](45) NULL,
	[owner] [varchar](255) NULL,
	[country] [varchar](100) NULL,
  PRIMARY KEY (id)
); 




-- TABLE STRUCTURE FOR: lic_file_downloads
CREATE TABLE lic_file_downloads (
  id int NOT NULL IDENTITY(1,1),
  fileid varchar(45) NOT NULL,
  downloads varchar(45) DEFAULT NULL,
  download_limit varchar(45) DEFAULT NULL,
  expiry int DEFAULT NULL,
  lastdownloaded int DEFAULT NULL,
  requestid int NOT NULL,
  PRIMARY KEY (id)
);


-- TABLE STRUCTURE FOR: lic_files
CREATE TABLE lic_files (
  id int NOT NULL IDENTITY(1,1),
  surveyid int NOT NULL,
  file_name varchar(100) NOT NULL,
  file_path varchar(255) NOT NULL,
  changed int NOT NULL,
  PRIMARY KEY (id)
);



-- TABLE STRUCTURE FOR: lic_files_log
CREATE TABLE lic_files_log (
  id int NOT NULL IDENTITY(1,1),
  requestid int NOT NULL,
  fileid int NOT NULL,
  ip varchar(20) NOT NULL,
  created int NOT NULL,
  username varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);



-- TABLE STRUCTURE FOR: lic_requests
CREATE TABLE lic_requests (
  id int NOT NULL IDENTITY(1,1),
  userid int NOT NULL,
  surveyid int NOT NULL,
  org_rec varchar(200) DEFAULT NULL,
  org_type varchar(45) DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  tel varchar(150) DEFAULT NULL,
  fax varchar(100) DEFAULT NULL,
  datause text,
  outputs text,
  compdate varchar(45) DEFAULT NULL,
  datamatching int DEFAULT NULL,
  mergedatasets text,
  team text,
  dataset_access varchar(20) DEFAULT 'whole',
  created int DEFAULT NULL,
  status varchar(45) DEFAULT NULL,
  comments text,
  locked tinyint DEFAULT NULL,
  orgtype_other varchar(145) DEFAULT NULL,
  updated int DEFAULT NULL,
  updatedby varchar(45) DEFAULT NULL,
  ip_limit varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);


-- TABLE STRUCTURE FOR: meta
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


-- TABLE STRUCTURE FOR: planned_surveys
CREATE TABLE planned_surveys (
  id int NOT NULL IDENTITY(1,1),
  title varchar(255) NOT NULL,
  abbreviation varchar(255) DEFAULT NULL,
  studytype varchar(255) DEFAULT NULL,
  country varchar(255) DEFAULT NULL,
  geocoverage varchar(255) DEFAULT NULL,
  scope varchar(255) DEFAULT NULL,
  pinvestigator varchar(255) DEFAULT NULL,
  producers varchar(255) DEFAULT NULL,
  sponsors varchar(255) DEFAULT NULL,
  fundingstatus int DEFAULT NULL,
  samplesize int DEFAULT NULL,
  sampleunit varchar(45) DEFAULT NULL,
  datacollstart int DEFAULT NULL,
  datacollend int DEFAULT NULL,
  expect_rep_date int DEFAULT NULL,
  expect_data_policy text,
  expect_micro_rel_date int DEFAULT NULL,
  notes text,
  PRIMARY KEY (id)
);



-- TABLE STRUCTURE FOR: repositories
CREATE TABLE repositories (
  id int NOT NULL IDENTITY(1,1),
  repositoryid varchar(255) NOT NULL,
  title varchar(100) NOT NULL,
  url varchar(255) NOT NULL,
  organization varchar(45) DEFAULT NULL,
  email varchar(45) DEFAULT NULL,
  country varchar(45) DEFAULT NULL,
  scan_lastrun int NOT NULL,
  scan_interval int NOT NULL,
  scan_nextrun int NOT NULL,
  status varchar(255) NOT NULL,
  surveys_found int NOT NULL,
  changed int NOT NULL,
  PRIMARY KEY (id),
  --UNIQUE KEY Ind_unq (repositoryid),
  --UNIQUE KEY idx_url (url)
);


-- TABLE STRUCTURE FOR: resources
CREATE TABLE resources (
  resource_id int NOT NULL IDENTITY(1,1),
  survey_id int NOT NULL,
  dctype varchar(255) DEFAULT NULL,
  title varchar(255) NOT NULL,
  subtitle varchar(255) DEFAULT NULL,
  author varchar(255) DEFAULT NULL,
  dcdate varchar(45) DEFAULT NULL,
  country varchar(45) DEFAULT NULL,
  language varchar(255) DEFAULT NULL,
  id_number varchar(255) DEFAULT NULL,
  contributor varchar(255) DEFAULT NULL,
  publisher varchar(255) DEFAULT NULL,
  rights varchar(255) DEFAULT NULL,
  description text,
  abstract text,
  toc text,
  subjects varchar(45) DEFAULT NULL,
  filename varchar(255) DEFAULT NULL,
  dcformat varchar(255) DEFAULT NULL,
  changed int DEFAULT NULL,
  PRIMARY KEY (resource_id)
);



-- TABLE STRUCTURE FOR: survey_citations
CREATE TABLE survey_citations (
  sid int DEFAULT NULL,
  citationid int DEFAULT NULL,
  id int NOT NULL IDENTITY(1,1),
  PRIMARY KEY (id)
  --UNIQUE KEY Idx_s_c (sid,citationid)
);



-- TABLE STRUCTURE FOR: survey_topics
CREATE TABLE survey_topics (
  sid int NOT NULL,
  tid int NOT NULL,
  uid int NOT NULL IDENTITY(1,1),
  PRIMARY KEY (uid)--,
 -- UNIQUE KEY Idx_uniq (tid,sid)
);



-- TABLE STRUCTURE FOR: survey_years
CREATE TABLE survey_years (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  data_coll_year int DEFAULT NULL,
  PRIMARY KEY (id)
);



-- TABLE STRUCTURE FOR: ci_sessions
CREATE TABLE ci_sessions (
  session_id varchar(40) NOT NULL DEFAULT '0',
  ip_address varchar(16) DEFAULT '0',
  user_agent varchar(50) DEFAULT NULL,
  last_activity int DEFAULT '0',
  user_data text,
  PRIMARY KEY (session_id)
);



-- TABLE STRUCTURE FOR: tokens
CREATE TABLE tokens (
  tokenid varchar(100) NOT NULL,
  dated int NOT NULL,
  PRIMARY KEY (tokenid)
);



-- TABLE STRUCTURE FOR: users
CREATE TABLE users (
  id int NOT NULL IDENTITY(1,1),
  group_id int NOT NULL,
  ip_address char(16) NOT NULL,
  username varchar(100) NOT NULL,
  password varchar(100) NOT NULL,
  salt varchar(40) DEFAULT NULL,
  email varchar(100) NOT NULL,
  activation_code varchar(40) DEFAULT NULL,
  forgotten_password_code varchar(40) DEFAULT NULL,
  remember_code varchar(40) DEFAULT NULL,
  created_on int NOT NULL,
  last_login int NOT NULL,
  active tinyint DEFAULT NULL,
  authtype varchar(50) DEFAULT NULL,
  PRIMARY KEY (id)
);


-- TABLE STRUCTURE FOR: citation_authors
CREATE TABLE citation_authors (
  id int NOT NULL IDENTITY(1,1),
  cid int DEFAULT NULL,
  fname varchar(255) DEFAULT NULL,
  lname varchar(255) DEFAULT NULL,
  initial varchar(255) DEFAULT NULL,
  author_type varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
);




-- TABLE STRUCTURE FOR: public_requests
CREATE TABLE public_requests (
  id int NOT NULL IDENTITY(1,1),
  userid int NOT NULL,
  surveyid int NOT NULL,
  abstract text NOT NULL,
  posted int NOT NULL,
  PRIMARY KEY (id)
);


--
-- TABLE STRUCTURE FOR: configurations
--

CREATE TABLE configurations (
  name varchar(200) NOT NULL,
  value varchar(255) NOT NULL,
  label varchar(255) DEFAULT NULL,
  helptext varchar(255) DEFAULT NULL,
  item_group varchar(255) DEFAULT NULL,
  PRIMARY KEY (name)
);

INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('app_version', '3.0.1-10.12.2010', 'Application version', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('cache_path', 'application/cache', 'Site cache folder', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('catalog_records_per_page', '5', 'Catalog search page - records per page', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('catalog_root', 'datafiles', 'Survey catalog folder', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('db_version', '3.0.1-10.12.2010', 'Database version', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('ddi_import_folder', 'imports', 'Survey catalog import folder', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('default_home_page', 'catalog', 'Default home page', 'Default home page', NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('html_folder', '/pages', NULL, NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('lang', 'en-us', 'Site Language', 'Site Language code', NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('login_timeout', '40', 'Login timeout (minutes)', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('mail_protocol', 'smtp', 'Select method for sending emails', 'Supported protocols: MAIL, SMTP, SENDMAIL', NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('min_password_length', '5', 'Minimum password length', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('regional_search', 'yes', 'Enable regional search', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('repository_identifier', 'default', 'Repository Identifier', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('site_password_protect', 'no', 'Password protect website', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('smtp_auth', 'no', 'Use SMTP Authentication', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('smtp_debug', 'yes', 'Enable SMTP Debugging', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('smtp_host', 'ihsn.org', 'SMTP Host name', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('smtp_pass', 'free001', 'SMTP password', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('smtp_port', '25', 'SMTP port', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('smtp_secure', 'no', 'Use Secure SMTP?', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('smtp_user', 'nada@ihsn.org', 'SMTP username', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('theme', 'default', 'Site theme name', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('topics_vocab', '1', 'Vocabulary ID for Topics', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('topic_search', 'yes', 'Topic search', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('use_html_editor', 'yes', 'Use HTML editor for entering HTML for static pages', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('website_footer', 'Powered by NADA 3.0 and DDI', 'Website footer text', NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('website_title', 'Your website title here', 'Website title', 'Provide the title of the website', 'website');
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('website_url', 'http://localhost/nada3', 'Website URL', 'URL of the website', 'website');
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('website_webmaster_email', 'webmaster@example.com', 'Site webmaster email address', '-', 'website');
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('website_webmaster_name', 'noreply', 'Webmaster name', '-', 'website');
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('year_search', 'yes', NULL, NULL, NULL);
INSERT INTO configurations (name, value, label, helptext, item_group) VALUES ('news_feed_url', 'http://ihsn.org/nada/index.php?q=news/feed', '', '', '');

--
-- TABLE STRUCTURE FOR: countries
--

CREATE TABLE countries (
  countryid int NOT NULL IDENTITY(1,1),
  name varchar(65) NOT NULL,
  iso3 varchar(3) NOT NULL,
  PRIMARY KEY (countryid)
);
set IDENTITY_INSERT Countries ON;
INSERT INTO countries (countryid, name, iso3) VALUES (1, 'Afghanistan', 'AFG');
INSERT INTO countries (countryid, name, iso3) VALUES (2, 'Albania', 'ALB');
INSERT INTO countries (countryid, name, iso3) VALUES (3, 'Antartica', 'ATA');
INSERT INTO countries (countryid, name, iso3) VALUES (4, 'Algeria', 'DZA');
INSERT INTO countries (countryid, name, iso3) VALUES (5, 'American Samoa', 'ASM');
INSERT INTO countries (countryid, name, iso3) VALUES (6, 'Andorra', 'AND');
INSERT INTO countries (countryid, name, iso3) VALUES (7, 'Angola', 'AGO');
INSERT INTO countries (countryid, name, iso3) VALUES (8, 'Antigua and Barbuda', 'ATG');
INSERT INTO countries (countryid, name, iso3) VALUES (9, 'Azerbaijan', 'AZE');
INSERT INTO countries (countryid, name, iso3) VALUES (10, 'Argentina', 'ARG');
INSERT INTO countries (countryid, name, iso3) VALUES (11, 'Australia', 'AUS');
INSERT INTO countries (countryid, name, iso3) VALUES (12, 'Austria', 'AUT');
INSERT INTO countries (countryid, name, iso3) VALUES (13, 'Bahamas', 'BHS');
INSERT INTO countries (countryid, name, iso3) VALUES (14, 'Bahrain', 'BHR');
INSERT INTO countries (countryid, name, iso3) VALUES (15, 'Bangladesh', 'BGD');
INSERT INTO countries (countryid, name, iso3) VALUES (16, 'Armenia', 'ARM');
INSERT INTO countries (countryid, name, iso3) VALUES (17, 'Barbados', 'BRB');
INSERT INTO countries (countryid, name, iso3) VALUES (18, 'Belgium', 'BEL');
INSERT INTO countries (countryid, name, iso3) VALUES (19, 'Bermuda', 'BMU');
INSERT INTO countries (countryid, name, iso3) VALUES (20, 'Bhutan', 'BTN');
INSERT INTO countries (countryid, name, iso3) VALUES (21, 'Bolivia', 'BOL');
INSERT INTO countries (countryid, name, iso3) VALUES (22, 'Bosnia-Herzegovina', 'BIH');
INSERT INTO countries (countryid, name, iso3) VALUES (23, 'Botswana', 'BWA');
INSERT INTO countries (countryid, name, iso3) VALUES (24, 'Bouvet Island', 'BVT');
INSERT INTO countries (countryid, name, iso3) VALUES (25, 'Brazil', 'BRA');
INSERT INTO countries (countryid, name, iso3) VALUES (26, 'Belize', 'BLZ');
INSERT INTO countries (countryid, name, iso3) VALUES (27, 'British Indian Ocean Territory', 'IOT');
INSERT INTO countries (countryid, name, iso3) VALUES (28, 'Solomon Islands', 'SLB');
INSERT INTO countries (countryid, name, iso3) VALUES (29, 'Virgin Isld. (British)', 'VGB');
INSERT INTO countries (countryid, name, iso3) VALUES (30, 'Brunei', 'BRN');
INSERT INTO countries (countryid, name, iso3) VALUES (31, 'Bulgaria', 'BGR');
INSERT INTO countries (countryid, name, iso3) VALUES (32, 'Myanmar', 'MMR');
INSERT INTO countries (countryid, name, iso3) VALUES (33, 'Burundi', 'BDI');
INSERT INTO countries (countryid, name, iso3) VALUES (34, 'Belarus', 'BLR');
INSERT INTO countries (countryid, name, iso3) VALUES (35, 'Cambodia', 'KHM');
INSERT INTO countries (countryid, name, iso3) VALUES (36, 'Cameroon', 'CMR');
INSERT INTO countries (countryid, name, iso3) VALUES (37, 'Canada', 'CAN');
INSERT INTO countries (countryid, name, iso3) VALUES (38, 'Cape Verde', 'CPV');
INSERT INTO countries (countryid, name, iso3) VALUES (39, 'Cayman Islands', 'CYM');
INSERT INTO countries (countryid, name, iso3) VALUES (40, 'Central African Republic', 'CAF');
INSERT INTO countries (countryid, name, iso3) VALUES (41, 'Sri Lanka', 'LKA');
INSERT INTO countries (countryid, name, iso3) VALUES (42, 'Chad', 'TCD');
INSERT INTO countries (countryid, name, iso3) VALUES (43, 'Chile', 'CHL');
INSERT INTO countries (countryid, name, iso3) VALUES (44, 'China', 'CHN');
INSERT INTO countries (countryid, name, iso3) VALUES (45, 'Taiwan', 'TWN');
INSERT INTO countries (countryid, name, iso3) VALUES (46, 'Christmas Island', 'CXR');
INSERT INTO countries (countryid, name, iso3) VALUES (47, 'Cocos Isld.', 'CCK');
INSERT INTO countries (countryid, name, iso3) VALUES (48, 'Colombia', 'COL');
INSERT INTO countries (countryid, name, iso3) VALUES (49, 'Comoros', 'COM');
INSERT INTO countries (countryid, name, iso3) VALUES (50, 'Mayotte', 'MYT');
INSERT INTO countries (countryid, name, iso3) VALUES (51, 'Congo, Rep.', 'COG');
INSERT INTO countries (countryid, name, iso3) VALUES (52, 'Congo, Dem. Rep.', 'COD');
INSERT INTO countries (countryid, name, iso3) VALUES (53, 'Cook Island', 'COK');
INSERT INTO countries (countryid, name, iso3) VALUES (54, 'Costa Rica', 'CRI');
INSERT INTO countries (countryid, name, iso3) VALUES (55, 'Croatia', 'HRV');
INSERT INTO countries (countryid, name, iso3) VALUES (56, 'Cuba', 'CUB');
INSERT INTO countries (countryid, name, iso3) VALUES (57, 'Cyprus', 'CYP');
INSERT INTO countries (countryid, name, iso3) VALUES (58, 'Czech Republic', 'CZE');
INSERT INTO countries (countryid, name, iso3) VALUES (59, 'Benin', 'BEN');
INSERT INTO countries (countryid, name, iso3) VALUES (60, 'Denmark', 'DNK');
INSERT INTO countries (countryid, name, iso3) VALUES (61, 'Dominica', 'DMA');
INSERT INTO countries (countryid, name, iso3) VALUES (62, 'Dominican Republic', 'DOM');
INSERT INTO countries (countryid, name, iso3) VALUES (63, 'Ecuador', 'ECU');
INSERT INTO countries (countryid, name, iso3) VALUES (64, 'El Salvador', 'SLV');
INSERT INTO countries (countryid, name, iso3) VALUES (65, 'Equatorial Guinea', 'GNQ');
INSERT INTO countries (countryid, name, iso3) VALUES (66, 'Ethiopia', 'ETH');
INSERT INTO countries (countryid, name, iso3) VALUES (67, 'Eritrea', 'ERI');
INSERT INTO countries (countryid, name, iso3) VALUES (68, 'Estonia', 'EST');
INSERT INTO countries (countryid, name, iso3) VALUES (69, 'Faeroe Isld.', 'FRO');
INSERT INTO countries (countryid, name, iso3) VALUES (70, 'Falkland Isld.', 'FLK');
INSERT INTO countries (countryid, name, iso3) VALUES (71, 'S. Georgia & S. Sandwich Isld.', 'SGS');
INSERT INTO countries (countryid, name, iso3) VALUES (72, 'Fiji', 'FJI');
INSERT INTO countries (countryid, name, iso3) VALUES (73, 'Finland', 'FIN');
INSERT INTO countries (countryid, name, iso3) VALUES (74, 'France, Metrop.', 'FXX');
INSERT INTO countries (countryid, name, iso3) VALUES (75, 'France', 'FRA');
INSERT INTO countries (countryid, name, iso3) VALUES (76, 'French Guiana', 'GUF');
INSERT INTO countries (countryid, name, iso3) VALUES (77, 'French Polynesia', 'PYF');
INSERT INTO countries (countryid, name, iso3) VALUES (78, 'French S.T.', 'ATF');
INSERT INTO countries (countryid, name, iso3) VALUES (79, 'Djibouti', 'DJI');
INSERT INTO countries (countryid, name, iso3) VALUES (80, 'Gabon', 'GAB');
INSERT INTO countries (countryid, name, iso3) VALUES (81, 'Georgia', 'GEO');
INSERT INTO countries (countryid, name, iso3) VALUES (82, 'Gambia', 'GMB');
INSERT INTO countries (countryid, name, iso3) VALUES (83, 'West Bank and Gaza', 'PSE');
INSERT INTO countries (countryid, name, iso3) VALUES (84, 'Germany', 'DEU');
INSERT INTO countries (countryid, name, iso3) VALUES (85, 'Ghana', 'GHA');
INSERT INTO countries (countryid, name, iso3) VALUES (86, 'Gibraltar', 'GIB');
INSERT INTO countries (countryid, name, iso3) VALUES (87, 'Kiribati', 'KIR');
INSERT INTO countries (countryid, name, iso3) VALUES (88, 'Greece', 'GRC');
INSERT INTO countries (countryid, name, iso3) VALUES (89, 'Greenland', 'GRL');
INSERT INTO countries (countryid, name, iso3) VALUES (90, 'Grenada', 'GRD');
INSERT INTO countries (countryid, name, iso3) VALUES (91, 'Guadeloupe', 'GLP');
INSERT INTO countries (countryid, name, iso3) VALUES (92, 'Guam', 'GUM');
INSERT INTO countries (countryid, name, iso3) VALUES (93, 'Guatemala', 'GTM');
INSERT INTO countries (countryid, name, iso3) VALUES (94, 'Guinea', 'GIN');
INSERT INTO countries (countryid, name, iso3) VALUES (95, 'Guyana', 'GUY');
INSERT INTO countries (countryid, name, iso3) VALUES (96, 'Haiti', 'HTI');
INSERT INTO countries (countryid, name, iso3) VALUES (97, 'Heard / McDonald Isld', 'HMD');
INSERT INTO countries (countryid, name, iso3) VALUES (98, 'Holy See', 'VAT');
INSERT INTO countries (countryid, name, iso3) VALUES (99, 'Honduras', 'HND');
INSERT INTO countries (countryid, name, iso3) VALUES (100, 'Hungary', 'HUN');
INSERT INTO countries (countryid, name, iso3) VALUES (101, 'Iceland', 'ISL');
INSERT INTO countries (countryid, name, iso3) VALUES (102, 'India', 'IND');
INSERT INTO countries (countryid, name, iso3) VALUES (103, 'Indonesia', 'IDN');
INSERT INTO countries (countryid, name, iso3) VALUES (104, 'Iran, Islamic Rep.', 'IRN');
INSERT INTO countries (countryid, name, iso3) VALUES (105, 'Iraq', 'IRQ');
INSERT INTO countries (countryid, name, iso3) VALUES (106, 'Ireland', 'IRL');
INSERT INTO countries (countryid, name, iso3) VALUES (107, 'Israel', 'ISR');
INSERT INTO countries (countryid, name, iso3) VALUES (108, 'Italy', 'ITA');
INSERT INTO countries (countryid, name, iso3) VALUES (109, 'CÃ´te d\'Ivoire', 'CIV');
INSERT INTO countries (countryid, name, iso3) VALUES (110, 'Jamaica', 'JAM');
INSERT INTO countries (countryid, name, iso3) VALUES (111, 'Japan', 'JPN');
INSERT INTO countries (countryid, name, iso3) VALUES (112, 'Kazakhstan', 'KAZ');
INSERT INTO countries (countryid, name, iso3) VALUES (113, 'Jordan', 'JOR');
INSERT INTO countries (countryid, name, iso3) VALUES (114, 'Kenya', 'KEN');
INSERT INTO countries (countryid, name, iso3) VALUES (115, 'Korea, Dem. Rep.', 'PRK');
INSERT INTO countries (countryid, name, iso3) VALUES (116, 'Korea, Rep.', 'KOR');
INSERT INTO countries (countryid, name, iso3) VALUES (117, 'Kuwait', 'KWT');
INSERT INTO countries (countryid, name, iso3) VALUES (118, 'Kyrgyz Republic', 'KGZ');
INSERT INTO countries (countryid, name, iso3) VALUES (119, 'Lao PDR', 'LAO');
INSERT INTO countries (countryid, name, iso3) VALUES (120, 'Lebanon', 'LBN');
INSERT INTO countries (countryid, name, iso3) VALUES (121, 'Lesotho', 'LSO');
INSERT INTO countries (countryid, name, iso3) VALUES (122, 'Latvia', 'LVA');
INSERT INTO countries (countryid, name, iso3) VALUES (123, 'Liberia', 'LBR');
INSERT INTO countries (countryid, name, iso3) VALUES (124, 'Libya', 'LBY');
INSERT INTO countries (countryid, name, iso3) VALUES (125, 'Liechtenstein', 'LIE');
INSERT INTO countries (countryid, name, iso3) VALUES (126, 'Lithuania', 'LTU');
INSERT INTO countries (countryid, name, iso3) VALUES (127, 'Luxembourg', 'LUX');
INSERT INTO countries (countryid, name, iso3) VALUES (128, 'Macao', 'MAC');
INSERT INTO countries (countryid, name, iso3) VALUES (129, 'Madagascar', 'MDG');
INSERT INTO countries (countryid, name, iso3) VALUES (130, 'Malawi', 'MWI');
INSERT INTO countries (countryid, name, iso3) VALUES (131, 'Malaysia', 'MYS');
INSERT INTO countries (countryid, name, iso3) VALUES (132, 'Maldives', 'MDV');
INSERT INTO countries (countryid, name, iso3) VALUES (133, 'Mali', 'MLI');
INSERT INTO countries (countryid, name, iso3) VALUES (134, 'Malta', 'MLT');
INSERT INTO countries (countryid, name, iso3) VALUES (135, 'Martinique', 'MTQ');
INSERT INTO countries (countryid, name, iso3) VALUES (136, 'Mauritania', 'MRT');
INSERT INTO countries (countryid, name, iso3) VALUES (137, 'Mauritius', 'MUS');
INSERT INTO countries (countryid, name, iso3) VALUES (138, 'Mexico', 'MEX');
INSERT INTO countries (countryid, name, iso3) VALUES (139, 'Monaco', 'MCO');
INSERT INTO countries (countryid, name, iso3) VALUES (140, 'Mongolia', 'MNG');
INSERT INTO countries (countryid, name, iso3) VALUES (141, 'Moldova', 'MDA');
INSERT INTO countries (countryid, name, iso3) VALUES (142, 'Montserrat', 'MSR');
INSERT INTO countries (countryid, name, iso3) VALUES (143, 'Morocco', 'MAR');
INSERT INTO countries (countryid, name, iso3) VALUES (144, 'Mozambique', 'MOZ');
INSERT INTO countries (countryid, name, iso3) VALUES (145, 'Oman', 'OMN');
INSERT INTO countries (countryid, name, iso3) VALUES (146, 'Namibia', 'NAM');
INSERT INTO countries (countryid, name, iso3) VALUES (147, 'Nauru', 'NRU');
INSERT INTO countries (countryid, name, iso3) VALUES (148, 'Nepal', 'NPL');
INSERT INTO countries (countryid, name, iso3) VALUES (149, 'Netherlands', 'NLD');
INSERT INTO countries (countryid, name, iso3) VALUES (150, 'Neth.Antilles', 'ANT');
INSERT INTO countries (countryid, name, iso3) VALUES (151, 'Aruba', 'ABW');
INSERT INTO countries (countryid, name, iso3) VALUES (152, 'New Caledonia', 'NCL');
INSERT INTO countries (countryid, name, iso3) VALUES (153, 'Vanuatu', 'VUT');
INSERT INTO countries (countryid, name, iso3) VALUES (154, 'New Zealand', 'NZL');
INSERT INTO countries (countryid, name, iso3) VALUES (155, 'Nicaragua', 'NIC');
INSERT INTO countries (countryid, name, iso3) VALUES (156, 'Niger', 'NER');
INSERT INTO countries (countryid, name, iso3) VALUES (157, 'Nigeria', 'NGA');
INSERT INTO countries (countryid, name, iso3) VALUES (158, 'Niue', 'NIU');
INSERT INTO countries (countryid, name, iso3) VALUES (159, 'Norfolk Isld.', 'NFK');
INSERT INTO countries (countryid, name, iso3) VALUES (160, 'Norway', 'NOR');
INSERT INTO countries (countryid, name, iso3) VALUES (161, 'N. Mariana Isld.', 'MNP');
INSERT INTO countries (countryid, name, iso3) VALUES (162, 'US minor outlying Islands', 'UMI');
INSERT INTO countries (countryid, name, iso3) VALUES (163, 'Micronesia', 'FSM');
INSERT INTO countries (countryid, name, iso3) VALUES (164, 'Marshall Isld.', 'MHL');
INSERT INTO countries (countryid, name, iso3) VALUES (165, 'Palau', 'PLW');
INSERT INTO countries (countryid, name, iso3) VALUES (166, 'Pakistan', 'PAK');
INSERT INTO countries (countryid, name, iso3) VALUES (167, 'Panama', 'PAN');
INSERT INTO countries (countryid, name, iso3) VALUES (168, 'Papua New Guinea', 'PNG');
INSERT INTO countries (countryid, name, iso3) VALUES (169, 'Paraguay', 'PRY');
INSERT INTO countries (countryid, name, iso3) VALUES (170, 'Peru', 'PER');
INSERT INTO countries (countryid, name, iso3) VALUES (171, 'Philippines', 'PHL');
INSERT INTO countries (countryid, name, iso3) VALUES (172, 'Pitcairn Island', 'PCN');
INSERT INTO countries (countryid, name, iso3) VALUES (173, 'Poland', 'POL');
INSERT INTO countries (countryid, name, iso3) VALUES (174, 'Portugal', 'PRT');
INSERT INTO countries (countryid, name, iso3) VALUES (175, 'Guinea Bissau', 'GNB');
INSERT INTO countries (countryid, name, iso3) VALUES (176, 'Timor-Leste', 'TLS');
INSERT INTO countries (countryid, name, iso3) VALUES (177, 'Puerto Rico', 'PRI');
INSERT INTO countries (countryid, name, iso3) VALUES (178, 'Qatar', 'QAT');
INSERT INTO countries (countryid, name, iso3) VALUES (179, 'Romania', 'ROM');
INSERT INTO countries (countryid, name, iso3) VALUES (180, 'Russian Federation', 'RUS');
INSERT INTO countries (countryid, name, iso3) VALUES (181, 'Rwanda', 'RWA');
INSERT INTO countries (countryid, name, iso3) VALUES (182, 'St. Helena', 'SHN');
INSERT INTO countries (countryid, name, iso3) VALUES (183, 'St.Kitts and Nevis', 'KNA');
INSERT INTO countries (countryid, name, iso3) VALUES (184, 'Anguilla', 'AIA');
INSERT INTO countries (countryid, name, iso3) VALUES (185, 'St. Lucia', 'LCA');
INSERT INTO countries (countryid, name, iso3) VALUES (186, 'St. Pierre and Miquelon', 'SPM');
INSERT INTO countries (countryid, name, iso3) VALUES (187, 'St. Vincent and Grenadines', 'VCT');
INSERT INTO countries (countryid, name, iso3) VALUES (188, 'San Marino', 'SMR');
INSERT INTO countries (countryid, name, iso3) VALUES (189, 'SÃ£o TomÃ© and Principe', 'STP');
INSERT INTO countries (countryid, name, iso3) VALUES (190, 'Saudi Arabia', 'SAU');
INSERT INTO countries (countryid, name, iso3) VALUES (191, 'Senegal', 'SEN');
INSERT INTO countries (countryid, name, iso3) VALUES (192, 'Seychelles', 'SYC');
INSERT INTO countries (countryid, name, iso3) VALUES (193, 'Sierra Leone', 'SLE');
INSERT INTO countries (countryid, name, iso3) VALUES (194, 'Singapore', 'SGP');
INSERT INTO countries (countryid, name, iso3) VALUES (195, 'Slovak Republic', 'SVK');
INSERT INTO countries (countryid, name, iso3) VALUES (196, 'Viet Nam', 'VNM');
INSERT INTO countries (countryid, name, iso3) VALUES (197, 'Slovenia', 'SVN');
INSERT INTO countries (countryid, name, iso3) VALUES (198, 'Somalia', 'SOM');
INSERT INTO countries (countryid, name, iso3) VALUES (199, 'South Africa', 'ZAF');
INSERT INTO countries (countryid, name, iso3) VALUES (200, 'Zimbabwe', 'ZWE');
INSERT INTO countries (countryid, name, iso3) VALUES (201, 'Spain', 'ESP');
INSERT INTO countries (countryid, name, iso3) VALUES (202, 'West. Sahara', 'ESH');
INSERT INTO countries (countryid, name, iso3) VALUES (203, 'Sudan', 'SDN');
INSERT INTO countries (countryid, name, iso3) VALUES (204, 'Suriname', 'SUR');
INSERT INTO countries (countryid, name, iso3) VALUES (205, 'Svalbard and Jan Mayen Islands', 'SJM');
INSERT INTO countries (countryid, name, iso3) VALUES (206, 'Swaziland', 'SWZ');
INSERT INTO countries (countryid, name, iso3) VALUES (207, 'Sweden', 'SWE');
INSERT INTO countries (countryid, name, iso3) VALUES (208, 'Switzerland', 'CHE');
INSERT INTO countries (countryid, name, iso3) VALUES (209, 'Syrian Arab Republic', 'SYR');
INSERT INTO countries (countryid, name, iso3) VALUES (210, 'Tajikistan', 'TJK');
INSERT INTO countries (countryid, name, iso3) VALUES (211, 'Thailand', 'THA');
INSERT INTO countries (countryid, name, iso3) VALUES (212, 'Togo', 'TGO');
INSERT INTO countries (countryid, name, iso3) VALUES (213, 'Tokelau', 'TKL');
INSERT INTO countries (countryid, name, iso3) VALUES (214, 'Tonga', 'TON');
INSERT INTO countries (countryid, name, iso3) VALUES (215, 'Trinidad and Tobago', 'TTO');
INSERT INTO countries (countryid, name, iso3) VALUES (216, 'United Arab Emirates', 'ARE');
INSERT INTO countries (countryid, name, iso3) VALUES (217, 'Tunisia', 'TUN');
INSERT INTO countries (countryid, name, iso3) VALUES (218, 'Turkey', 'TUR');
INSERT INTO countries (countryid, name, iso3) VALUES (219, 'Turkmenistan', 'TKM');
INSERT INTO countries (countryid, name, iso3) VALUES (220, 'Turks and Caicos Islands', 'TCA');
INSERT INTO countries (countryid, name, iso3) VALUES (221, 'Tuvalu', 'TUV');
INSERT INTO countries (countryid, name, iso3) VALUES (222, 'Uganda', 'UGA');
INSERT INTO countries (countryid, name, iso3) VALUES (223, 'Ukraine', 'UKR');
INSERT INTO countries (countryid, name, iso3) VALUES (224, 'Macedonia, FYR', 'MKD');
INSERT INTO countries (countryid, name, iso3) VALUES (225, 'Egypt, Arab Rep.', 'EGY');
INSERT INTO countries (countryid, name, iso3) VALUES (226, 'United Kingdom', 'GBR');
INSERT INTO countries (countryid, name, iso3) VALUES (227, 'Tanzania', 'TZA');
INSERT INTO countries (countryid, name, iso3) VALUES (228, 'United States', 'USA');
INSERT INTO countries (countryid, name, iso3) VALUES (229, 'Virgin Islands, U.S.', 'VIR');
INSERT INTO countries (countryid, name, iso3) VALUES (230, 'Burkina Faso', 'BFA');
INSERT INTO countries (countryid, name, iso3) VALUES (231, 'Uruguay', 'URY');
INSERT INTO countries (countryid, name, iso3) VALUES (232, 'Uzbekistan', 'UZB');
INSERT INTO countries (countryid, name, iso3) VALUES (233, 'Venezuela, RB', 'VEN');
INSERT INTO countries (countryid, name, iso3) VALUES (234, 'Wallis and Futuna', 'WLF');
INSERT INTO countries (countryid, name, iso3) VALUES (235, 'Samoa', 'WSM');
INSERT INTO countries (countryid, name, iso3) VALUES (236, 'Yemen', 'YEM');
INSERT INTO countries (countryid, name, iso3) VALUES (237, 'Serbia and Montenegro', 'SCG');
INSERT INTO countries (countryid, name, iso3) VALUES (238, 'Zambia', 'ZMB');
INSERT INTO countries (countryid, name, iso3) VALUES (239, 'Westbank and Gaza', 'WBG');
INSERT INTO countries (countryid, name, iso3) VALUES (240, 'Jerusalem', 'JER');
set IDENTITY_INSERT Countries OFF;

--
-- TABLE STRUCTURE FOR: dcformats
--

CREATE TABLE dcformats (
  id int NOT NULL IDENTITY(1,1),
  title varchar(255) NOT NULL,
  PRIMARY KEY (id)
);
set IDENTITY_INSERT dcformats ON;
INSERT INTO dcformats (id, title) VALUES (1, 'Compressed, Generic [application/x-compressed]');
INSERT INTO dcformats (id, title) VALUES (2, 'Compressed, ZIP [application/zip]');
INSERT INTO dcformats (id, title) VALUES (3, 'Data, CSPro [application/x-cspro]');
INSERT INTO dcformats (id, title) VALUES (4, 'Data, dBase [application/dbase]');
INSERT INTO dcformats (id, title) VALUES (5, 'Data, Microsoft Access [application/msaccess]');
INSERT INTO dcformats (id, title) VALUES (6, 'Data, SAS [application/x-sas]');
INSERT INTO dcformats (id, title) VALUES (7, 'Data, SPSS [application/x-spss]');
INSERT INTO dcformats (id, title) VALUES (8, 'Data, Stata [application/x-stata]');
INSERT INTO dcformats (id, title) VALUES (9, 'Document, Generic [text]');
INSERT INTO dcformats (id, title) VALUES (10, 'Document, HTML [text/html]');
INSERT INTO dcformats (id, title) VALUES (11, 'Document, Microsoft Excel [application/msexcel]');
INSERT INTO dcformats (id, title) VALUES (12, 'Document, Microsoft PowerPoint [application/mspowerpoint');
INSERT INTO dcformats (id, title) VALUES (13, 'Document, Microsoft Word [application/msword]');
INSERT INTO dcformats (id, title) VALUES (14, 'Document, PDF [application/pdf]');
INSERT INTO dcformats (id, title) VALUES (15, 'Document, Postscript [application/postscript]');
INSERT INTO dcformats (id, title) VALUES (16, 'Document, Plain [text/plain]');
INSERT INTO dcformats (id, title) VALUES (17, 'Document, WordPerfect [text/wordperfect]');
INSERT INTO dcformats (id, title) VALUES (18, 'Image, GIF [image/gif]');
INSERT INTO dcformats (id, title) VALUES (19, 'Image, JPEG [image/jpeg]');
INSERT INTO dcformats (id, title) VALUES (20, 'Image, PNG [image/png]');
INSERT INTO dcformats (id, title) VALUES (21, 'Image, TIFF [image/tiff]');
set IDENTITY_INSERT dcformats OFF;

--
-- TABLE STRUCTURE FOR: dctypes
--

CREATE TABLE dctypes (
  id int NOT NULL IDENTITY(1,1),
  title varchar(255) NOT NULL,
  PRIMARY KEY (id)
);
set IDENTITY_INSERT dctypes ON;
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
set IDENTITY_INSERT dctypes OFF;

--
-- TABLE STRUCTURE FOR: forms
--

CREATE TABLE forms (
  formid int NOT NULL IDENTITY(1,1),
  fname varchar(255) DEFAULT '',
  model varchar(255) DEFAULT '',
  path varchar(255) DEFAULT '',
  iscustom char(2) DEFAULT '0',
  PRIMARY KEY (formid)
);
set IDENTITY_INSERT [forms] ON;
INSERT INTO forms (formid, fname, model, path, iscustom) VALUES (2, 'Public use files', 'public', 'orderform.php', '1');
INSERT INTO forms (formid, fname, model, path, iscustom) VALUES (1, 'Direct access', 'direct', 'direct.php', '1');
INSERT INTO forms (formid, fname, model, path, iscustom) VALUES (3, 'Licensed data files', 'licensed', 'licensed.php', '1');
INSERT INTO forms (formid, fname, model, path, iscustom) VALUES (4, 'Data accessible only in data enclave', 'data_enclave', 'Application for Access to a Data Enclave.pdf', '0');
INSERT INTO forms (formid, fname, model, path, iscustom) VALUES (5, 'Data available from external repository', 'remote', 'remote', '1');
set IDENTITY_INSERT [forms] OFF;

--
-- TABLE STRUCTURE FOR: menus
--

CREATE TABLE menus (
  id int NOT NULL IDENTITY(1,1),
  url varchar(255) NOT NULL,
  title varchar(255) NOT NULL,
  body text,
  published tinyint DEFAULT NULL,
  target varchar(45) DEFAULT NULL,
  changed int DEFAULT NULL,
  linktype tinyint DEFAULT NULL,
  weight int DEFAULT NULL,
  pid int DEFAULT NULL,
  PRIMARY KEY (id)--,
--  UNIQUE KEY idx_url (url)
);
set IDENTITY_INSERT menus ON;
INSERT INTO menus (id, url, title, body, published, target, changed, linktype, weight, pid) VALUES (1, 'catalog', 'Data Catalog', '', 1, '0', 1281460209, 1, 4, 0);
INSERT INTO menus (id, url, title, body, published, target, changed, linktype, weight, pid) VALUES (2, 'citations', 'Citations', NULL, 1, '0', 1281460217, 1, 5, 0);
set IDENTITY_INSERT menus OFF;

--
-- TABLE STRUCTURE FOR: terms
--

CREATE TABLE [dbo].[terms](
	[tid] [int] IDENTITY(1,1) NOT NULL,
	[vid] [int] NOT NULL,
	[pid] [int] NULL,
	[title] [varchar](255) NOT NULL,
	PRIMARY KEY (tid)
);
set IDENTITY_INSERT terms ON;
INSERT INTO terms (tid, vid, pid, title) VALUES (1, 1, 0, 'ECONOMICS [1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (2, 1, 1, 'consumption/consumer behaviour [1.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (3, 1, 1, 'economic conditions and indicators [1.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (4, 1, 1, 'economic policy [1.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (5, 1, 1, 'economic systems and development [1.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (6, 1, 1, 'income, property and investment/saving [1.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (7, 1, 1, 'rural economics [1.6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (9, 1, 0, 'TRADE, INDUSTRY AND MARKETS [2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (10, 1, 9, 'agricultural, forestry and rural industry [2.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (11, 1, 9, 'business/industrial management and organisation [2.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (13, 1, 0, 'LABOUR AND EMPLOYMENT [3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (14, 1, 13, 'employment [3.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (15, 1, 13, 'in-job training [3.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (16, 1, 13, 'labour relations/conflict [3.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (17, 1, 13, 'retirement [3.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (18, 1, 13, 'unemployment [3.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (19, 1, 13, 'working conditions [3.6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (21, 1, 0, 'POLITICS [4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (22, 1, 21, 'conflict, security and peace [4.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (23, 1, 21, 'domestic political issues [4.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (24, 1, 21, 'elections [4.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (25, 1, 21, 'government, political systems and organisations [4.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (26, 1, 21, 'international politics and organisations [4.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (27, 1, 21, 'mass political behaviour, attitudes/opinion [4.6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (28, 1, 21, 'political ideology [4.7]');
INSERT INTO terms (tid, vid, pid, title) VALUES (30, 1, 0, 'LAW, CRIME AND LEGAL SYSTEMS [5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (31, 1, 30, 'crime [5.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (32, 1, 30, 'law enforcement [5.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (33, 1, 30, 'legal systems [5.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (34, 1, 30, 'legislation [5.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (35, 1, 30, 'rehabilitation/reintegration into society [5.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (37, 1, 0, 'EDUCATION [6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (38, 1, 37, 'basic skills education [6.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (39, 1, 37, 'compulsory and pre-school education [6.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (40, 1, 37, 'educational policy [6.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (41, 1, 37, 'life-long/continuing education [6.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (42, 1, 37, 'post-compulsory education [6.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (43, 1, 37, 'teaching profession [6.6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (44, 1, 37, 'vocational education [6.7]');
INSERT INTO terms (tid, vid, pid, title) VALUES (46, 1, 0, 'INFORMATION AND COMMUNICATION [7]');
INSERT INTO terms (tid, vid, pid, title) VALUES (47, 1, 46, 'advertising [7.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (48, 1, 46, 'information society [7.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (49, 1, 46, 'language and linguistics [7.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (50, 1, 46, 'mass media [7.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (52, 1, 0, 'HEALTH [8]');
INSERT INTO terms (tid, vid, pid, title) VALUES (53, 1, 52, 'accidents and injuries [8.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (54, 1, 52, 'childbearing, family planning and abortion [8.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (55, 1, 52, 'drug abuse, alcohol and smoking [8.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (56, 1, 52, 'general health [8.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (57, 1, 52, 'health care and medical treatment [8.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (58, 1, 52, 'health policy [8.6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (59, 1, 52, 'nutrition [8.7]');
INSERT INTO terms (tid, vid, pid, title) VALUES (60, 1, 52, 'physical fitness and exercise [8.8]');
INSERT INTO terms (tid, vid, pid, title) VALUES (61, 1, 52, 'specific diseases and medical conditions [8.9]');
INSERT INTO terms (tid, vid, pid, title) VALUES (63, 1, 0, 'NATURAL ENVIRONMENT [9]');
INSERT INTO terms (tid, vid, pid, title) VALUES (64, 1, 63, 'environmental degradation/pollution and protection [9.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (65, 1, 63, 'natural landscapes [9.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (66, 1, 63, 'natural resources and energy [9.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (67, 1, 63, 'plant and animal distribution [9.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (69, 1, 0, 'HOUSING AND LAND USE PLANNING [10]');
INSERT INTO terms (tid, vid, pid, title) VALUES (70, 1, 69, 'housing [10.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (71, 1, 69, 'land use and planning [10.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (73, 1, 0, 'TRANSPORT, TRAVEL AND MOBILITY [11]');
INSERT INTO terms (tid, vid, pid, title) VALUES (74, 1, 0, 'SOCIAL STRATIFICATION AND GROUPINGS [12]');
INSERT INTO terms (tid, vid, pid, title) VALUES (75, 1, 74, 'children [12.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (76, 1, 74, 'elderly [12.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (77, 1, 74, 'elites and leadership [12.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (78, 1, 74, 'equality and inequality [12.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (79, 1, 74, 'family life and marriage [12.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (80, 1, 74, 'gender and gender roles [12.6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (81, 1, 74, 'minorities [12.7]');
INSERT INTO terms (tid, vid, pid, title) VALUES (82, 1, 74, 'social and occupational mobility [12.8]');
INSERT INTO terms (tid, vid, pid, title) VALUES (83, 1, 74, 'social exclusion [12.9]');
INSERT INTO terms (tid, vid, pid, title) VALUES (84, 1, 74, 'youth [12.10]');
INSERT INTO terms (tid, vid, pid, title) VALUES (86, 1, 0, 'SOCIETY AND CULTURE [13]');
INSERT INTO terms (tid, vid, pid, title) VALUES (87, 1, 86, 'community, urban and rural life [13.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (88, 1, 86, 'cultural activities and participation [13.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (89, 1, 86, 'cultural and national identity [13.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (90, 1, 86, 'leisure, tourism and sport [13.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (91, 1, 86, 'religion and values [13.5]');
INSERT INTO terms (tid, vid, pid, title) VALUES (92, 1, 86, 'social behaviour and attitudes [13.6]');
INSERT INTO terms (tid, vid, pid, title) VALUES (93, 1, 86, 'social change [13.7]');
INSERT INTO terms (tid, vid, pid, title) VALUES (94, 1, 86, 'social conditions and indicators [13.8]');
INSERT INTO terms (tid, vid, pid, title) VALUES (95, 1, 86, 'time use [13.9]');
INSERT INTO terms (tid, vid, pid, title) VALUES (97, 1, 0, 'DEMOGRAPHY AND POPULATION [14]');
INSERT INTO terms (tid, vid, pid, title) VALUES (98, 1, 97, 'censuses [14.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (99, 1, 97, 'fertility [14.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (100, 1, 97, 'migration [14.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (101, 1, 97, 'morbidity and mortality [14.4]');
INSERT INTO terms (tid, vid, pid, title) VALUES (103, 1, 0, 'SOCIAL WELFARE POLICY AND SYSTEMS [15]');
INSERT INTO terms (tid, vid, pid, title) VALUES (104, 1, 103, 'social welfare policy [15.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (105, 1, 103, 'social welfare systems/structures [15.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (106, 1, 103, 'specific social services: use and provision [15.3]');
INSERT INTO terms (tid, vid, pid, title) VALUES (108, 1, 0, 'SCIENCE AND TECHNOLOGY [16]');
INSERT INTO terms (tid, vid, pid, title) VALUES (109, 1, 108, 'biotechnology [16.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (110, 1, 108, 'information technology [16.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (112, 1, 0, 'PSYCHOLOGY [17]');
INSERT INTO terms (tid, vid, pid, title) VALUES (113, 1, 0, 'HISTORY [18]');
INSERT INTO terms (tid, vid, pid, title) VALUES (114, 1, 0, 'REFERENCE AND INSTRUCTIONAL RESOURCES [19]');
INSERT INTO terms (tid, vid, pid, title) VALUES (115, 1, 114, 'computer and simulation programs [19.1]');
INSERT INTO terms (tid, vid, pid, title) VALUES (116, 1, 114, 'reference sources [19.2]');
INSERT INTO terms (tid, vid, pid, title) VALUES (117, 1, 114, 'teaching packages and test datasets [19.3]');
set IDENTITY_INSERT terms OFF;

--
-- TABLE STRUCTURE FOR: user_groups
--

CREATE TABLE user_groups (
  id tinyint NOT NULL IDENTITY(1,1),
  name varchar(20) NOT NULL,
  description varchar(100) NOT NULL,
  PRIMARY KEY (id)
);
set IDENTITY_INSERT user_groups ON;
INSERT INTO user_groups (id, name, description) VALUES (1, 'admin', 'Administrator');
INSERT INTO user_groups (id, name, description) VALUES (2, 'members', 'General User');
set IDENTITY_INSERT user_groups OFF;

--
-- TABLE STRUCTURE FOR: vocabularies
--

CREATE TABLE vocabularies (
  vid int NOT NULL IDENTITY(1,1),
  title varchar(255) NOT NULL,
  PRIMARY KEY (vid)--,
  --UNIQUE KEY idx_voc_title (title)
);
set IDENTITY_INSERT vocabularies ON;
INSERT INTO vocabularies (vid, title) VALUES (1, 'CESSDA Topics Classifications');
set IDENTITY_INSERT vocabularies OFF;


--------------------------------------------------------------------------------------------------------------------------------------
-- cache

CREATE TABLE cache (
  id bigint NOT NULL IDENTITY(1,1),
  uid varchar(100) NOT NULL,
  data text,
  created int DEFAULT NULL,
  expiry int DEFAULT NULL,
  PRIMARY KEY (id)
  --UNIQUE KEY uid_UNIQUE (uid)
);


-- country_aliases

CREATE TABLE country_aliases (
  id int NOT NULL IDENTITY(1,1),
  countryid int NOT NULL,
  alias varchar(100) NOT NULL,
  PRIMARY KEY (id)
  --UNIQUE KEY ix_alias_uniq (countryid,alias)
);



-- group_permissions

CREATE TABLE group_permissions (
  id int NOT NULL IDENTITY(1,1),
  group_id int NOT NULL,
  permission_id int NOT NULL,
  PRIMARY KEY (id),
  --UNIQUE KEY grp_perms_UNIQUE (group_id,permission_id)
);


-- group_repo_access

CREATE TABLE group_repo_access (
  id int NOT NULL IDENTITY(1,1),
  group_id int DEFAULT NULL,
  repo_id int DEFAULT NULL,
  PRIMARY KEY (id)
 -- UNIQUE KEY grp_repo_UNIQUE (group_id,repo_id)
);

-- groups

CREATE TABLE groups (
  id int NOT NULL IDENTITY(1,1),
  name varchar(100) NOT NULL,
  description varchar(255) NOT NULL,
  group_type varchar(40) DEFAULT NULL,
  access_type varchar(45) DEFAULT NULL,
  weight int DEFAULT '0',
  is_collection_group tinyint DEFAULT '0' ,
  PRIMARY KEY (id)
);

set IDENTITY_INSERT groups ON;
INSERT INTO groups (id,name,description,group_type,access_type,weight) 
VALUES (1,'admin','Administrator','admin','UNLIMITED',0),(2,'user','General User','user','NONE',-99),(3,'catalog-admin','Catalog Administrator','admin','LIMITED',0),(4,'lic-req-admin','Licensed Request Administrator','admin','LIMITED',0),(5,'report-admin','Reports Administrato','admin','LIMITED',0),(6,'LSMS Collection Admin','Administrators for LSMS Collection','admin','limited',0),(7,'Reviewers','Reviewers','reviewer','none',-90),(9,'LAC Administrators','LAC Collection Administrators','admin','limited',0),(10,'LAC LIC-REVIEWERS','LAC Licensed Request Reviewers','admin','limited',0);
set IDENTITY_INSERT groups OFF;

--lic_requests --*********************************************************************

CREATE TABLE lic_requests (
  id int NOT NULL IDENTITY(1,1),
  userid int NOT NULL,
  request_type varchar(45) DEFAULT 'study',
  surveyid int DEFAULT NULL,
  collection_id varchar(100) DEFAULT NULL,
  org_rec varchar(200) DEFAULT NULL,
  org_type varchar(45) DEFAULT NULL,
  address varchar(255) DEFAULT NULL,
  tel varchar(150) DEFAULT NULL,
  fax varchar(100) DEFAULT NULL,
  datause text,
  outputs text,
  compdate varchar(45) DEFAULT NULL,
  datamatching int DEFAULT NULL,
  mergedatasets text,
  team text,
  dataset_access varchar(20) DEFAULT 'whole',
  created int DEFAULT NULL,
  status varchar(45) DEFAULT NULL,
  comments text,
  locked tinyint(4) DEFAULT NULL,
  orgtype_other varchar(145) DEFAULT NULL,
  updated int DEFAULT NULL,
  updatedby varchar(45) DEFAULT NULL,
  ip_limit varchar(255) DEFAULT NULL,
  expiry_date int DEFAULT NULL,
  additional_info text,
  PRIMARY KEY (id)
) ENGINE=InnoDB IDENTITY(1,1)=22 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC$$


-- lic_requests_history

CREATE TABLE lic_requests_history (
  id int NOT NULL IDENTITY(1,1),
  lic_req_id int DEFAULT NULL,
  user_id varchar(100) DEFAULT NULL,
  logtype varchar(45) DEFAULT NULL,
  request_status varchar(45) DEFAULT NULL,
  description text,
  created int DEFAULT NULL,
  PRIMARY KEY (id)
);



-- login_attempts

CREATE TABLE login_attempts (
  id int NOT NULL IDENTITY(1,1),
  ip_address varchar(30) NOT NULL,
  login varchar(100) NOT NULL,
  time int DEFAULT NULL,
  PRIMARY KEY (id)
);


-- permission_urls

CREATE TABLE permission_urls (
  id int NOT NULL IDENTITY(1,1),
  url varchar(255) DEFAULT NULL,
  permission_id int NOT NULL,
  PRIMARY KEY (id)
  ---UNIQUE KEY url_UNIQUE (url)
);


-- permissions

CREATE TABLE permissions (
  id int NOT NULL IDENTITY(1,1),
  label varchar(45) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  section varchar(45) DEFAULT NULL,
  weight int DEFAULT NULL,
  PRIMARY KEY (id)
);


-- regions

CREATE TABLE regions (
  id int NOT NULL IDENTITY(1,1),
  pid int DEFAULT '0',
  title varchar(45) DEFAULT NULL,
  weight int DEFAULT NULL,
  PRIMARY KEY (id)
);



-- repo_perms_groups

CREATE TABLE repo_perms_groups (
  repo_pg_id int NOT NULL IDENTITY(1,1),
  title varchar(45) DEFAULT NULL,
  description varchar(255) DEFAULT NULL,
  weight int DEFAULT '0',
  PRIMARY KEY (repo_pg_id)
);


-- repo_perms_urls

CREATE TABLE repo_perms_urls (
  id int NOT NULL IDENTITY(1,1),
  repo_pg_id int DEFAULT NULL,
  url varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
);



-- repositories ------------ **************************

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
  type int(10) unsigned DEFAULT NULL,
  short_text varchar(1000) DEFAULT NULL,
  long_text text,
  thumbnail varchar(255) DEFAULT NULL,
  weight int(10) unsigned DEFAULT NULL,
  ispublished tinyint(3) unsigned DEFAULT NULL,
  section int DEFAULT NULL,
  group_da_public tinyint(1) DEFAULT '0',
  group_da_licensed tinyint(1) DEFAULT '0',
  PRIMARY KEY (id),
  UNIQUE KEY Ind_unq (repositoryid)
) ENGINE=InnoDB IDENTITY(1,1)=17 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC$$


-- repository_sections

CREATE TABLE repository_sections (
  id int NOT NULL IDENTITY(1,1),
  title varchar(100) NOT NULL,
  weight int NOT NULL,
  PRIMARY KEY (id)
);



-- site_menu

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

-- data for site_menu

set IDENTITY_INSERT site_menu ON;
INSERT INTO site_menu (id,pid,title,url,weight,depth,module) VALUES (1,0,'Dashboard','admin',0,0,'admin'),(2,0,'Studies','admin/catalog',1,0,'catalog'),(4,0,'Citations','admin/citations',3,0,'citations'),(5,0,'Users','admin/users',4,0,'users'),(6,0,'Menu','admin/menu',5,0,'menu'),(7,0,'Reports','admin/reports',6,0,'reports'),(8,0,'Settings','admin/configurations',7,0,'configurations'),(12,2,'-','-',70,1,'catalog'),(13,2,'Licensed requests','admin/licensed_requests',80,1,'catalog'),(14,2,'-','-',90,1,'catalog'),(15,2,'Manage collections','admin/repositories',60,1,'repositories'),(17,4,'All citations','admin/citations',100,1,'citations'),(18,4,'Import citations','admin/citations/import',90,1,'citations'),(19,4,'Export citations','admin/citations/export',80,1,'citations'),(20,5,'All users','admin/users',100,1,'users'),(21,5,'Add user','admin/users/add',99,1,'users'),(22,5,'-','-',65,1,'users'),(23,5,'User groups','admin/user_groups',90,1,'user_groups'),(24,5,'Add user group','admin/user_groups/add',80,1,'user_groups'),(25,5,'-','-',95,1,'user_groups'),(26,5,'User permissions','admin/permissions',60,1,'permissions'),(27,6,'All pages','admin/menu',0,1,'menu'),(28,7,'All reports','admin/reports',0,1,'reports'),(29,8,'Settings','admin/configurations',0,1,'configurations'),(30,8,'Countries','admin/countries',0,1,'vocabularies'),(31,8,'Regions','admin/regions',0,1,'vocabularies'),(32,8,'-','-',0,1,'vocabularies'),(33,8,'Vocabularies','admin/vocabularies',-9,1,'vocabularies'),(34,2,'Manage studies','admin/catalog',100,1,'catalog'),(35,5,'Impersonate user','admin/users/impersonate',50,1,'users'),(36,5,'-','-',51,1,'users');
set IDENTITY_INSERT site_menu OFF;


-- survey_aliases

CREATE TABLE survey_aliases (
  id int NOT NULL IDENTITY(1,1),
  sid int NOT NULL,
  alternate_id varchar(255) NOT NULL,
  PRIMARY KEY (id)
  -- UNIQUE KEY survey_id (alternate_id)
);



-- survey_countries

CREATE TABLE survey_countries (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  cid int DEFAULT NULL,
  country_name varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
  --UNIQUE KEY sid_iso_UNIQUE (sid,country_name)
);



-- survey_notes

CREATE TABLE survey_notes (
  id int NOT NULL IDENTITY(1,1),
  sid int DEFAULT NULL,
  note text NOT NULL,
  type varchar(30) NOT NULL,
  userid int NOT NULL,
  created int DEFAULT NULL,
  changed int DEFAULT NULL,
  PRIMARY KEY (id)
);


-- survey_relationship_types

CREATE TABLE survey_relationship_types (
  id int NOT NULL IDENTITY(1,1),
  rel_group_id int DEFAULT NULL,
  rel_name varchar(45) DEFAULT NULL,
  rel_dir tinyint DEFAULT NULL,
  rel_cordinality varchar(10) DEFAULT NULL,
  PRIMARY KEY (id)
);


-- survey_relationships

CREATE TABLE survey_relationships (
  id int NOT NULL IDENTITY(1,1),
  sid_1 int DEFAULT NULL,
  sid_2 int DEFAULT NULL,
  relationship_id int DEFAULT NULL,
  pair_id varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
  --KEY idx_pair (pair_id)
);


--survey_tags

CREATE TABLE survey_tags (
  id int NOT NULL IDENTITY(1,1),
  sid int NOT NULL,
  tag varchar(100) NOT NULL,
  PRIMARY KEY (id)
--  UNIQUE KEY uq_tag (sid,tag) USING BTREE
);



-- surveys ---**********************************

CREATE TABLE surveys (
  id int NOT NULL IDENTITY(1,1),
  repositoryid varchar(128) NOT NULL,
  surveyid varchar(200) DEFAULT NULL,
  titl varchar(255) DEFAULT '',
  titlstmt text,
  authenty varchar(255) DEFAULT NULL,
  geogcover varchar(255) DEFAULT NULL,
  nation varchar(100) DEFAULT '',
  topic text,
  scope text,
  sername varchar(255) DEFAULT NULL,
  producer varchar(255) DEFAULT NULL,
  sponsor varchar(255) DEFAULT NULL,
  refno varchar(255) DEFAULT NULL,
  proddate varchar(45) DEFAULT NULL,
  varcount decimal(10,0) DEFAULT NULL,
  ddifilename varchar(255) DEFAULT NULL,
  dirpath varchar(255) DEFAULT NULL,
  link_technical varchar(255) DEFAULT NULL COMMENT 'documentation',
  link_study varchar(255) DEFAULT NULL COMMENT 'study website',
  link_report varchar(255) DEFAULT NULL COMMENT 'reports',
  link_indicator varchar(255) DEFAULT NULL COMMENT 'indicators',
  ddi_sh char(2) DEFAULT NULL,
  formid int DEFAULT NULL,
  isshared tinyint(1) NOT NULL DEFAULT '1',
  isdeleted tinyint(1) NOT NULL DEFAULT '0',
  changed int DEFAULT NULL,
  created int DEFAULT NULL,
  link_questionnaire varchar(255) DEFAULT NULL,
  countryid int DEFAULT NULL,
  data_coll_start int DEFAULT NULL,
  data_coll_end int DEFAULT NULL,
  abbreviation varchar(45) DEFAULT NULL,
  kindofdata varchar(255) DEFAULT NULL,
  keywords text,
  ie_program varchar(255) DEFAULT NULL,
  ie_project_id varchar(255) DEFAULT NULL,
  ie_project_name varchar(255) DEFAULT NULL,
  ie_project_uri varchar(255) DEFAULT NULL,
  ie_team_leaders text,
  project_id varchar(255) DEFAULT NULL,
  project_name varchar(255) DEFAULT NULL,
  project_uri varchar(255) DEFAULT NULL,
  link_da varchar(255) DEFAULT NULL,
  published tinyint(4) DEFAULT NULL,
  total_views int DEFAULT '0',
  total_downloads int DEFAULT '0',
  stats_last_updated int DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY idx_srvy_unq (surveyid,repositoryid),
  FULLTEXT KEY ft_titl (titl),
  FULLTEXT KEY ft_all (titl,authenty,geogcover,nation,topic,scope,sername,producer,sponsor,refno,abbreviation,kindofdata,keywords)
) ENGINE=MyISAM IDENTITY(1,1)=608 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC$$



-- tags
CREATE TABLE tags (
  id int NOT NULL IDENTITY(1,1),
  tag varchar(100) NOT NULL,
  PRIMARY KEY (id)
  --UNIQUE KEY tag_UNIQUE (tag)
);



-- url_mappings

CREATE TABLE url_mappings (
  id int NOT NULL IDENTITY(1,1),
  source varchar(255) DEFAULT NULL,
  target varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)
);



-- user_repo_permissions

CREATE TABLE user_repo_permissions (
  id int NOT NULL IDENTITY(1,1),
  user_id int DEFAULT NULL,
  repo_id int DEFAULT NULL,
  repo_pg_id int DEFAULT NULL,
  PRIMARY KEY (id)
);


-- users -------------------- *********

CREATE TABLE users (
  id int NOT NULL IDENTITY(1,1),
  ip_address char(16) NOT NULL,
  username varchar(100) NOT NULL,
  password varchar(1000) NOT NULL,
  salt varchar(40) DEFAULT NULL,
  email varchar(100) NOT NULL,
  activation_code varchar(40) DEFAULT NULL,
  forgotten_password_code varchar(40) DEFAULT NULL,
  remember_code varchar(40) DEFAULT NULL,
  created_on int NOT NULL,
  last_login int NOT NULL,
  active tinyint DEFAULT NULL,
  authtype varchar(40) DEFAULT NULL,
  PRIMARY KEY (id)
);




-- users_groups

CREATE TABLE users_groups (
  id int NOT NULL IDENTITY(1,1),
  user_id int DEFAULT NULL,
  group_id int DEFAULT NULL,
  PRIMARY KEY (id)
  --UNIQUE KEY user_group_UNQ (user_id,group_id)
);


-- region_countries

CREATE TABLE region_countries (
  id int NOT NULL IDENTITY(1,1),
  region_id int DEFAULT NULL,
  country_id int DEFAULT NULL,
  PRIMARY KEY (id)
);

-- data for region_countries

insert into region_countries (region_id,country_id) values (2,5);
insert into region_countries (region_id,country_id) values (2,35);
insert into region_countries (region_id,country_id) values (2,44);
insert into region_countries (region_id,country_id) values (2,72);
insert into region_countries (region_id,country_id) values (2,103);
insert into region_countries (region_id,country_id) values (2,87);
insert into region_countries (region_id,country_id) values (2,115);
insert into region_countries (region_id,country_id) values (2,119);
insert into region_countries (region_id,country_id) values (2,131);
insert into region_countries (region_id,country_id) values (2,164);
insert into region_countries (region_id,country_id) values (2,163);
insert into region_countries (region_id,country_id) values (2,140);
insert into region_countries (region_id,country_id) values (2,32);
insert into region_countries (region_id,country_id) values (2,165);
insert into region_countries (region_id,country_id) values (2,168);
insert into region_countries (region_id,country_id) values (2,171);
insert into region_countries (region_id,country_id) values (2,235);
insert into region_countries (region_id,country_id) values (2,211);
insert into region_countries (region_id,country_id) values (2,176);
insert into region_countries (region_id,country_id) values (2,214);
insert into region_countries (region_id,country_id) values (2,221);
insert into region_countries (region_id,country_id) values (2,153);
insert into region_countries (region_id,country_id) values (2,196);
insert into region_countries (region_id,country_id) values (4,8);
insert into region_countries (region_id,country_id) values (4,10);
insert into region_countries (region_id,country_id) values (4,26);
insert into region_countries (region_id,country_id) values (4,21);
insert into region_countries (region_id,country_id) values (4,25);
insert into region_countries (region_id,country_id) values (4,43);
insert into region_countries (region_id,country_id) values (4,48);
insert into region_countries (region_id,country_id) values (4,54);
insert into region_countries (region_id,country_id) values (4,56);
insert into region_countries (region_id,country_id) values (4,61);
insert into region_countries (region_id,country_id) values (4,62);
insert into region_countries (region_id,country_id) values (4,63);
insert into region_countries (region_id,country_id) values (4,64);
insert into region_countries (region_id,country_id) values (4,90);
insert into region_countries (region_id,country_id) values (4,93);
insert into region_countries (region_id,country_id) values (4,95);
insert into region_countries (region_id,country_id) values (4,96);
insert into region_countries (region_id,country_id) values (4,99);
insert into region_countries (region_id,country_id) values (4,110);
insert into region_countries (region_id,country_id) values (4,138);
insert into region_countries (region_id,country_id) values (4,155);
insert into region_countries (region_id,country_id) values (4,167);
insert into region_countries (region_id,country_id) values (4,169);
insert into region_countries (region_id,country_id) values (4,170);
insert into region_countries (region_id,country_id) values (4,185);
insert into region_countries (region_id,country_id) values (4,187);
insert into region_countries (region_id,country_id) values (4,204);
insert into region_countries (region_id,country_id) values (4,231);
insert into region_countries (region_id,country_id) values (4,233);
insert into region_countries (region_id,country_id) values (5,4);
insert into region_countries (region_id,country_id) values (5,79);
insert into region_countries (region_id,country_id) values (5,225);
insert into region_countries (region_id,country_id) values (5,104);
insert into region_countries (region_id,country_id) values (5,105);
insert into region_countries (region_id,country_id) values (5,113);
insert into region_countries (region_id,country_id) values (5,120);
insert into region_countries (region_id,country_id) values (5,124);
insert into region_countries (region_id,country_id) values (5,143);
insert into region_countries (region_id,country_id) values (5,209);
insert into region_countries (region_id,country_id) values (5,217);
insert into region_countries (region_id,country_id) values (5,83);
insert into region_countries (region_id,country_id) values (5,236);
insert into region_countries (region_id,country_id) values (6,1);
insert into region_countries (region_id,country_id) values (6,15);
insert into region_countries (region_id,country_id) values (6,20);
insert into region_countries (region_id,country_id) values (6,102);
insert into region_countries (region_id,country_id) values (6,132);
insert into region_countries (region_id,country_id) values (6,148);
insert into region_countries (region_id,country_id) values (6,166);
insert into region_countries (region_id,country_id) values (6,41);
insert into region_countries (region_id,country_id) values (7,7);
insert into region_countries (region_id,country_id) values (7,59);
insert into region_countries (region_id,country_id) values (7,23);
insert into region_countries (region_id,country_id) values (7,230);
insert into region_countries (region_id,country_id) values (7,33);
insert into region_countries (region_id,country_id) values (7,36);
insert into region_countries (region_id,country_id) values (7,38);
insert into region_countries (region_id,country_id) values (7,40);
insert into region_countries (region_id,country_id) values (7,42);
insert into region_countries (region_id,country_id) values (7,49);
insert into region_countries (region_id,country_id) values (7,52);
insert into region_countries (region_id,country_id) values (7,51);
insert into region_countries (region_id,country_id) values (7,109);
insert into region_countries (region_id,country_id) values (7,67);
insert into region_countries (region_id,country_id) values (7,66);
insert into region_countries (region_id,country_id) values (7,80);
insert into region_countries (region_id,country_id) values (7,82);
insert into region_countries (region_id,country_id) values (7,85);
insert into region_countries (region_id,country_id) values (7,94);
insert into region_countries (region_id,country_id) values (7,175);
insert into region_countries (region_id,country_id) values (7,114);
insert into region_countries (region_id,country_id) values (7,121);
insert into region_countries (region_id,country_id) values (7,123);
insert into region_countries (region_id,country_id) values (7,129);
insert into region_countries (region_id,country_id) values (7,130);
insert into region_countries (region_id,country_id) values (7,133);
insert into region_countries (region_id,country_id) values (7,136);
insert into region_countries (region_id,country_id) values (7,137);
insert into region_countries (region_id,country_id) values (7,144);
insert into region_countries (region_id,country_id) values (7,146);
insert into region_countries (region_id,country_id) values (7,156);
insert into region_countries (region_id,country_id) values (7,157);
insert into region_countries (region_id,country_id) values (7,181);
insert into region_countries (region_id,country_id) values (7,189);
insert into region_countries (region_id,country_id) values (7,191);
insert into region_countries (region_id,country_id) values (7,192);
insert into region_countries (region_id,country_id) values (7,193);
insert into region_countries (region_id,country_id) values (7,198);
insert into region_countries (region_id,country_id) values (7,199);
insert into region_countries (region_id,country_id) values (7,203);
insert into region_countries (region_id,country_id) values (7,206);
insert into region_countries (region_id,country_id) values (7,227);
insert into region_countries (region_id,country_id) values (7,212);
insert into region_countries (region_id,country_id) values (7,222);
insert into region_countries (region_id,country_id) values (7,238);
insert into region_countries (region_id,country_id) values (7,200);
insert into region_countries (region_id,country_id) values (3,2);
insert into region_countries (region_id,country_id) values (3,16);
insert into region_countries (region_id,country_id) values (3,9);
insert into region_countries (region_id,country_id) values (3,34);
insert into region_countries (region_id,country_id) values (3,22);
insert into region_countries (region_id,country_id) values (3,31);
insert into region_countries (region_id,country_id) values (3,81);
insert into region_countries (region_id,country_id) values (3,112);
insert into region_countries (region_id,country_id) values (3,118);
insert into region_countries (region_id,country_id) values (3,122);
insert into region_countries (region_id,country_id) values (3,126);
insert into region_countries (region_id,country_id) values (3,224);
insert into region_countries (region_id,country_id) values (3,141);
insert into region_countries (region_id,country_id) values (3,179);
insert into region_countries (region_id,country_id) values (3,180);
insert into region_countries (region_id,country_id) values (3,237);
insert into region_countries (region_id,country_id) values (3,210);
insert into region_countries (region_id,country_id) values (3,218);
insert into region_countries (region_id,country_id) values (3,219);
insert into region_countries (region_id,country_id) values (3,223);
insert into region_countries (region_id,country_id) values (3,232);
insert into region_countries (region_id,country_id) values (9,1);
insert into region_countries (region_id,country_id) values (9,15);
insert into region_countries (region_id,country_id) values (9,59);
insert into region_countries (region_id,country_id) values (9,230);
insert into region_countries (region_id,country_id) values (9,33);
insert into region_countries (region_id,country_id) values (9,35);
insert into region_countries (region_id,country_id) values (9,40);
insert into region_countries (region_id,country_id) values (9,42);
insert into region_countries (region_id,country_id) values (9,49);
insert into region_countries (region_id,country_id) values (9,52);
insert into region_countries (region_id,country_id) values (9,67);
insert into region_countries (region_id,country_id) values (9,66);
insert into region_countries (region_id,country_id) values (9,82);
insert into region_countries (region_id,country_id) values (9,94);
insert into region_countries (region_id,country_id) values (9,175);
insert into region_countries (region_id,country_id) values (9,96);
insert into region_countries (region_id,country_id) values (9,114);
insert into region_countries (region_id,country_id) values (9,115);
insert into region_countries (region_id,country_id) values (9,118);
insert into region_countries (region_id,country_id) values (9,123);
insert into region_countries (region_id,country_id) values (9,129);
insert into region_countries (region_id,country_id) values (9,130);
insert into region_countries (region_id,country_id) values (9,133);
insert into region_countries (region_id,country_id) values (9,136);
insert into region_countries (region_id,country_id) values (9,144);
insert into region_countries (region_id,country_id) values (9,32);
insert into region_countries (region_id,country_id) values (9,148);
insert into region_countries (region_id,country_id) values (9,156);
insert into region_countries (region_id,country_id) values (9,181);
insert into region_countries (region_id,country_id) values (9,193);
insert into region_countries (region_id,country_id) values (9,198);
insert into region_countries (region_id,country_id) values (9,210);
insert into region_countries (region_id,country_id) values (9,227);
insert into region_countries (region_id,country_id) values (9,212);
insert into region_countries (region_id,country_id) values (9,222);
insert into region_countries (region_id,country_id) values (9,200);
insert into region_countries (region_id,country_id) values (10,2);
insert into region_countries (region_id,country_id) values (10,16);
insert into region_countries (region_id,country_id) values (10,26);
insert into region_countries (region_id,country_id) values (10,20);
insert into region_countries (region_id,country_id) values (10,21);
insert into region_countries (region_id,country_id) values (10,36);
insert into region_countries (region_id,country_id) values (10,38);
insert into region_countries (region_id,country_id) values (10,51);
insert into region_countries (region_id,country_id) values (10,109);
insert into region_countries (region_id,country_id) values (10,79);
insert into region_countries (region_id,country_id) values (10,225);
insert into region_countries (region_id,country_id) values (10,64);
insert into region_countries (region_id,country_id) values (10,72);
insert into region_countries (region_id,country_id) values (10,81);
insert into region_countries (region_id,country_id) values (10,85);
insert into region_countries (region_id,country_id) values (10,93);
insert into region_countries (region_id,country_id) values (10,95);
insert into region_countries (region_id,country_id) values (10,99);
insert into region_countries (region_id,country_id) values (10,102);
insert into region_countries (region_id,country_id) values (10,103);
insert into region_countries (region_id,country_id) values (10,105);
insert into region_countries (region_id,country_id) values (10,87);
insert into region_countries (region_id,country_id) values (10,119);
insert into region_countries (region_id,country_id) values (10,121);
insert into region_countries (region_id,country_id) values (10,164);
insert into region_countries (region_id,country_id) values (10,163);
insert into region_countries (region_id,country_id) values (10,141);
insert into region_countries (region_id,country_id) values (10,140);
insert into region_countries (region_id,country_id) values (10,143);
insert into region_countries (region_id,country_id) values (10,155);
insert into region_countries (region_id,country_id) values (10,157);
insert into region_countries (region_id,country_id) values (10,166);
insert into region_countries (region_id,country_id) values (10,168);
insert into region_countries (region_id,country_id) values (10,169);
insert into region_countries (region_id,country_id) values (10,171);
insert into region_countries (region_id,country_id) values (10,235);
insert into region_countries (region_id,country_id) values (10,189);
insert into region_countries (region_id,country_id) values (10,191);
insert into region_countries (region_id,country_id) values (10,28);
insert into region_countries (region_id,country_id) values (10,41);
insert into region_countries (region_id,country_id) values (10,203);
insert into region_countries (region_id,country_id) values (10,206);
insert into region_countries (region_id,country_id) values (10,209);
insert into region_countries (region_id,country_id) values (10,176);
insert into region_countries (region_id,country_id) values (10,214);
insert into region_countries (region_id,country_id) values (10,223);
insert into region_countries (region_id,country_id) values (10,232);
insert into region_countries (region_id,country_id) values (10,153);
insert into region_countries (region_id,country_id) values (10,196);
insert into region_countries (region_id,country_id) values (10,83);
insert into region_countries (region_id,country_id) values (10,236);
insert into region_countries (region_id,country_id) values (10,238);
insert into region_countries (region_id,country_id) values (11,4);
insert into region_countries (region_id,country_id) values (11,5);
insert into region_countries (region_id,country_id) values (11,7);
insert into region_countries (region_id,country_id) values (11,8);
insert into region_countries (region_id,country_id) values (11,10);
insert into region_countries (region_id,country_id) values (11,9);
insert into region_countries (region_id,country_id) values (11,34);
insert into region_countries (region_id,country_id) values (11,22);
insert into region_countries (region_id,country_id) values (11,23);
insert into region_countries (region_id,country_id) values (11,25);
insert into region_countries (region_id,country_id) values (11,31);
insert into region_countries (region_id,country_id) values (11,43);
insert into region_countries (region_id,country_id) values (11,44);
insert into region_countries (region_id,country_id) values (11,48);
insert into region_countries (region_id,country_id) values (11,54);
insert into region_countries (region_id,country_id) values (11,56);
insert into region_countries (region_id,country_id) values (11,61);
insert into region_countries (region_id,country_id) values (11,62);
insert into region_countries (region_id,country_id) values (11,63);
insert into region_countries (region_id,country_id) values (11,90);
insert into region_countries (region_id,country_id) values (11,104);
insert into region_countries (region_id,country_id) values (11,110);
insert into region_countries (region_id,country_id) values (11,113);
insert into region_countries (region_id,country_id) values (11,112);
insert into region_countries (region_id,country_id) values (11,122);
insert into region_countries (region_id,country_id) values (11,120);
insert into region_countries (region_id,country_id) values (11,124);
insert into region_countries (region_id,country_id) values (11,126);
insert into region_countries (region_id,country_id) values (11,224);
insert into region_countries (region_id,country_id) values (11,131);
insert into region_countries (region_id,country_id) values (11,132);
insert into region_countries (region_id,country_id) values (11,137);
insert into region_countries (region_id,country_id) values (11,138);
insert into region_countries (region_id,country_id) values (11,146);
insert into region_countries (region_id,country_id) values (11,165);
insert into region_countries (region_id,country_id) values (11,167);
insert into region_countries (region_id,country_id) values (11,170);
insert into region_countries (region_id,country_id) values (11,179);
insert into region_countries (region_id,country_id) values (11,180);
insert into region_countries (region_id,country_id) values (11,192);
insert into region_countries (region_id,country_id) values (11,199);
insert into region_countries (region_id,country_id) values (11,185);
insert into region_countries (region_id,country_id) values (11,187);
insert into region_countries (region_id,country_id) values (11,204);
insert into region_countries (region_id,country_id) values (11,211);
insert into region_countries (region_id,country_id) values (11,217);
insert into region_countries (region_id,country_id) values (11,218);
insert into region_countries (region_id,country_id) values (11,219);
insert into region_countries (region_id,country_id) values (11,221);
insert into region_countries (region_id,country_id) values (11,231);
insert into region_countries (region_id,country_id) values (11,233);
insert into region_countries (region_id,country_id) values (12,6);
insert into region_countries (region_id,country_id) values (12,151);
insert into region_countries (region_id,country_id) values (12,11);
insert into region_countries (region_id,country_id) values (12,12);
insert into region_countries (region_id,country_id) values (12,13);
insert into region_countries (region_id,country_id) values (12,14);
insert into region_countries (region_id,country_id) values (12,17);
insert into region_countries (region_id,country_id) values (12,18);
insert into region_countries (region_id,country_id) values (12,19);
insert into region_countries (region_id,country_id) values (12,30);
insert into region_countries (region_id,country_id) values (12,37);
insert into region_countries (region_id,country_id) values (12,39);
insert into region_countries (region_id,country_id) values (12,55);
insert into region_countries (region_id,country_id) values (12,57);
insert into region_countries (region_id,country_id) values (12,58);
insert into region_countries (region_id,country_id) values (12,60);
insert into region_countries (region_id,country_id) values (12,65);
insert into region_countries (region_id,country_id) values (12,68);
insert into region_countries (region_id,country_id) values (12,69);
insert into region_countries (region_id,country_id) values (12,73);
insert into region_countries (region_id,country_id) values (12,75);
insert into region_countries (region_id,country_id) values (12,77);
insert into region_countries (region_id,country_id) values (12,84);
insert into region_countries (region_id,country_id) values (12,88);
insert into region_countries (region_id,country_id) values (12,89);
insert into region_countries (region_id,country_id) values (12,92);
insert into region_countries (region_id,country_id) values (12,100);
insert into region_countries (region_id,country_id) values (12,101);
insert into region_countries (region_id,country_id) values (12,107);
insert into region_countries (region_id,country_id) values (12,108);
insert into region_countries (region_id,country_id) values (12,111);
insert into region_countries (region_id,country_id) values (12,116);
insert into region_countries (region_id,country_id) values (12,117);
insert into region_countries (region_id,country_id) values (12,125);
insert into region_countries (region_id,country_id) values (12,127);
insert into region_countries (region_id,country_id) values (12,128);
insert into region_countries (region_id,country_id) values (12,134);
insert into region_countries (region_id,country_id) values (12,139);
insert into region_countries (region_id,country_id) values (12,149);
insert into region_countries (region_id,country_id) values (12,152);
insert into region_countries (region_id,country_id) values (12,154);
insert into region_countries (region_id,country_id) values (12,160);
insert into region_countries (region_id,country_id) values (12,145);
insert into region_countries (region_id,country_id) values (12,173);
insert into region_countries (region_id,country_id) values (12,174);
insert into region_countries (region_id,country_id) values (12,177);
insert into region_countries (region_id,country_id) values (12,178);
insert into region_countries (region_id,country_id) values (12,188);
insert into region_countries (region_id,country_id) values (12,190);
insert into region_countries (region_id,country_id) values (12,194);
insert into region_countries (region_id,country_id) values (12,195);
insert into region_countries (region_id,country_id) values (12,197);
insert into region_countries (region_id,country_id) values (12,201);
insert into region_countries (region_id,country_id) values (12,183);
insert into region_countries (region_id,country_id) values (12,207);
insert into region_countries (region_id,country_id) values (12,208);
insert into region_countries (region_id,country_id) values (12,215);
insert into region_countries (region_id,country_id) values (12,220);
insert into region_countries (region_id,country_id) values (12,216);
insert into region_countries (region_id,country_id) values (12,226);
insert into region_countries (region_id,country_id) values (12,228);
insert into region_countries (region_id,country_id) values (12,229);
insert into region_countries (region_id,country_id) values (13,11);
insert into region_countries (region_id,country_id) values (13,12);
insert into region_countries (region_id,country_id) values (13,18);
insert into region_countries (region_id,country_id) values (13,37);
insert into region_countries (region_id,country_id) values (13,58);
insert into region_countries (region_id,country_id) values (13,60);
insert into region_countries (region_id,country_id) values (13,68);
insert into region_countries (region_id,country_id) values (13,73);
insert into region_countries (region_id,country_id) values (13,75);
insert into region_countries (region_id,country_id) values (13,84);
insert into region_countries (region_id,country_id) values (13,88);
insert into region_countries (region_id,country_id) values (13,100);
insert into region_countries (region_id,country_id) values (13,101);
insert into region_countries (region_id,country_id) values (13,106);
insert into region_countries (region_id,country_id) values (13,107);
insert into region_countries (region_id,country_id) values (13,108);
insert into region_countries (region_id,country_id) values (13,111);
insert into region_countries (region_id,country_id) values (13,116);
insert into region_countries (region_id,country_id) values (13,127);
insert into region_countries (region_id,country_id) values (13,149);
insert into region_countries (region_id,country_id) values (13,154);
insert into region_countries (region_id,country_id) values (13,160);
insert into region_countries (region_id,country_id) values (13,173);
insert into region_countries (region_id,country_id) values (13,174);
insert into region_countries (region_id,country_id) values (13,195);
insert into region_countries (region_id,country_id) values (13,197);
insert into region_countries (region_id,country_id) values (13,201);
insert into region_countries (region_id,country_id) values (13,207);
insert into region_countries (region_id,country_id) values (13,208);
insert into region_countries (region_id,country_id) values (13,226);
insert into region_countries (region_id,country_id) values (13,228);
