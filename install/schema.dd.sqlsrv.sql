--
-- Data deposit tables (SQL Server)
-- Used by the web installer and by migrations (execute_sql_file).
-- No DROP. Run on an empty catalog or when dd_projects is missing.
-- Fresh dd_projects.schema_version defaults to 2.
--

CREATE TABLE dd_citation_authors (
  id int NOT NULL IDENTITY(1,1),
  cid int DEFAULT NULL,
  fname varchar(255) DEFAULT NULL,
  lname varchar(255) DEFAULT NULL,
  initial varchar(255) DEFAULT NULL,
  author_type varchar(45) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE dd_citations (
  id int NOT NULL IDENTITY(1,1),
  pid int NOT NULL,
  title varchar(255) NOT NULL,
  subtitle varchar(255) DEFAULT NULL,
  alt_title varchar(255) DEFAULT NULL,
  authors varchar(max),
  editors varchar(max),
  translators varchar(max),
  changed int DEFAULT NULL,
  created int DEFAULT NULL,
  published int DEFAULT 1,
  volume varchar(45) DEFAULT NULL,
  issue varchar(45) DEFAULT NULL,
  idnumber varchar(45) DEFAULT NULL,
  edition varchar(45) DEFAULT NULL,
  place_publication varchar(255) DEFAULT NULL,
  place_state varchar(255) DEFAULT NULL,
  publisher varchar(255) DEFAULT NULL,
  publication_medium int DEFAULT 0,
  url varchar(255) DEFAULT NULL,
  page_from varchar(5) DEFAULT NULL,
  page_to varchar(5) DEFAULT NULL,
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
  PRIMARY KEY (id)
);

CREATE TABLE dd_collaborators (
  id int NOT NULL IDENTITY(1,1),
  pid int NOT NULL,
  email varchar(255) NOT NULL,
  access varchar(255) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE dd_datadeposit_history (
  id int NOT NULL IDENTITY(1,1),
  project_id int NOT NULL,
  user_identity varchar(100) NOT NULL,
  created_on int NOT NULL,
  project_status varchar(100) NOT NULL,
  comments varchar(max),
  PRIMARY KEY (id)
);

CREATE TABLE dd_kind_of_data (
  id int NOT NULL IDENTITY(1,1),
  kindofdata varchar(200) NOT NULL,
  PRIMARY KEY (id)
);

SET IDENTITY_INSERT dd_kind_of_data ON;
INSERT INTO dd_kind_of_data (id, kindofdata) VALUES
(1,'--'),
(2,'Sample survey data [ssd]'),
(3,'Census/enumeration data [cen]'),
(4,'Administrative records data [adm]'),
(5,'Aggregate data [agg]'),
(6,'Clinical data [cli]'),
(7,'Event/Transaction data [evn]'),
(8,'Observation data/ratings [obs]'),
(9,'Process-produced data [pro]'),
(10,'Time budget dairies [tbd]');
SET IDENTITY_INSERT dd_kind_of_data OFF;

CREATE TABLE dd_overview_methods (
  id int NOT NULL IDENTITY(1,1),
  method varchar(max) NOT NULL,
  PRIMARY KEY (id)
);

SET IDENTITY_INSERT dd_overview_methods ON;
INSERT INTO dd_overview_methods (id, method) VALUES
(1,'--'),
(2,'Propensity Score Matching'),
(3,'Pipeline Comparison'),
(4,'Other Matching Methods'),
(5,'Instrumental Variables'),
(6,'Simulated Counterfactual'),
(7,'Single Difference'),
(8,'Difference in Means'),
(9,'Difference-in-Difference'),
(10,'Regression Discontinuity Design'),
(11,'Duration Model'),
(12,'Non-Experimental'),
(13,'Natural Experiment'),
(14,'Other'),
(15,'Randomization');
SET IDENTITY_INSERT dd_overview_methods OFF;

CREATE TABLE dd_project_resources (
  id int NOT NULL IDENTITY(1,1),
  project_id int DEFAULT NULL,
  title varchar(255) DEFAULT NULL,
  author varchar(100) DEFAULT NULL,
  created int DEFAULT NULL,
  description varchar(300) DEFAULT NULL,
  filename varchar(255) DEFAULT NULL,
  dctype varchar(100) DEFAULT NULL,
  dcformat varchar(100) DEFAULT NULL,
  filesize float DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE dd_project_status_types (
  id int NOT NULL IDENTITY(1,1),
  status varchar(50) NOT NULL,
  PRIMARY KEY (id)
);

SET IDENTITY_INSERT dd_project_status_types ON;
INSERT INTO dd_project_status_types (id, status) VALUES
(1,'submitted'),
(2,'accepted'),
(3,'draft'),
(4,'closed'),
(5,'processed');
SET IDENTITY_INSERT dd_project_status_types OFF;

CREATE TABLE dd_projects (
  id int NOT NULL IDENTITY(1,1),
  created_by varchar(300) NOT NULL,
  title varchar(300) NOT NULL,
  created_on int NOT NULL,
  data_type varchar(20) DEFAULT NULL,
  last_modified int NOT NULL,
  status varchar(20) NOT NULL CONSTRAINT df_dd_projects_status DEFAULT 'draft',
  schema_version tinyint NOT NULL CONSTRAINT df_dd_projects_schema_version DEFAULT 2,
  description varchar(max) NOT NULL,
  shortname varchar(50) NOT NULL,
  collaborators varchar(max),
  uid int DEFAULT NULL,
  access_policy varchar(max),
  library_notes varchar(max),
  submit_contact varchar(max),
  submit_on_behalf varchar(max),
  cc varchar(max),
  access_authority varchar(max),
  submitted_on int DEFAULT NULL,
  submitted_by varchar(300) DEFAULT NULL,
  admin_comments varchar(max),
  administrated_by varchar(max),
  administer_date int DEFAULT NULL,
  data_folder_path varchar(300) DEFAULT NULL,
  to_catalog varchar(45) DEFAULT NULL,
  is_embargoed int DEFAULT NULL,
  embargoed varchar(500) DEFAULT NULL,
  disclosure_risk varchar(500) DEFAULT NULL,
  key_variables varchar(500) DEFAULT NULL,
  sensitive_variables varchar(500) DEFAULT NULL,
  requested_reopen tinyint DEFAULT NULL,
  requested_when int DEFAULT NULL,
  metadata varchar(max),
  submission varchar(max),
  PRIMARY KEY (id)
);

CREATE TABLE dd_study (
  id int NOT NULL CONSTRAINT df_dd_study_id DEFAULT 0,
  ident_title varchar(max),
  ident_abbr varchar(max),
  ident_study_type varchar(max),
  ident_ser_info varchar(max),
  ident_trans_title varchar(max),
  ident_id varchar(max),
  ver_desc varchar(max),
  ver_prod_date int DEFAULT NULL,
  ver_notes varchar(max),
  overview_abstract varchar(max),
  overview_kind_of_data varchar(max),
  overview_analysis varchar(max),
  overview_methods varchar(max),
  scope_definition varchar(max),
  scope_class varchar(max),
  coverage_country varchar(max),
  coverage_geo varchar(max),
  coverage_universe varchar(max),
  prod_s_investigator varchar(max),
  prod_s_other_prod varchar(max),
  prod_s_funding varchar(max),
  prod_s_acknowledgements varchar(max),
  sampling_procedure varchar(max),
  sampling_dev varchar(max),
  sampling_rates varchar(max),
  sampling_weight varchar(max),
  coll_dates varchar(max),
  coll_periods varchar(max),
  coll_mode varchar(max),
  coll_notes varchar(max),
  coll_questionnaire varchar(max),
  coll_collectors varchar(max),
  coll_supervision varchar(max),
  process_editing varchar(max),
  process_other varchar(max),
  appraisal_error varchar(max),
  appraisal_other varchar(max),
  access_authority varchar(max),
  access_confidentiality varchar(max),
  access_conditions varchar(max),
  access_cite_require varchar(max),
  disclaimer_disclaimer varchar(max),
  disclaimer_copyright varchar(max),
  contacts_contacts varchar(max),
  citations varchar(max),
  ident_ddp_id varchar(max),
  scope_keywords varchar(max),
  ident_subtitle varchar(max),
  operational_wb_name varchar(max),
  operational_wb_id varchar(max),
  operational_wb_net varchar(max),
  operational_wb_sector varchar(max),
  operational_wb_summary varchar(max),
  operational_wb_objectives varchar(max),
  impact_wb_name varchar(max),
  impact_wb_id varchar(max),
  impact_wb_area varchar(max),
  impact_wb_lead varchar(max),
  impact_wb_members varchar(max),
  impact_wb_description varchar(max),
  PRIMARY KEY (id)
);

CREATE TABLE dd_study_type (
  id int NOT NULL IDENTITY(1,1),
  studytype varchar(250) NOT NULL,
  PRIMARY KEY (id)
);

SET IDENTITY_INSERT dd_study_type ON;
INSERT INTO dd_study_type (id, studytype) VALUES
(1,'--'),
(2,'1-2-3 Survey, phase 3 [hh/123-3]'),
(3,'Administrative Records, Health [ad/hea]'),
(4,'Administrative Records, Education [ad/edu]'),
(5,'Administrative Records, Other [ad/oth]'),
(6,'Aggricultural Census [ag/census]'),
(7,'Agricultural Survey [ag/oth]'),
(8,'Child Labor Survey [hh/cls]'),
(9,'Core Welfare Indicators Questionnaire [hh/cwiq]'),
(10,'Demographic and Health Survey [hh/dhs]'),
(11,'Demographic and Health Survey, Round 1 [hh/dhs-1]'),
(12,'Demographic and Health Survey, Round 2 [hh/dhs-2]'),
(13,'Demographic and Health Survey, Round 3 [hh/dhs-3]'),
(14,'Demographic and Health Survey, Round 4 [hh/dhs-4]'),
(15,'Demographic and Health Survey, Interim [hh/dhs-int]'),
(16,'Demographic and Health Survey, Special [hh/dhs-sp]'),
(17,'Enterprise Survey [en/oth]');
SET IDENTITY_INSERT dd_study_type OFF;

CREATE TABLE dd_tasks (
  id int NOT NULL IDENTITY(1,1),
  project_id int NOT NULL,
  user_id int NOT NULL,
  assigner_id int NOT NULL,
  date_assigned int NOT NULL,
  date_completed int DEFAULT NULL,
  status int NOT NULL,
  comments varchar(max),
  PRIMARY KEY (id)
);

CREATE TABLE dd_tasks_team (
  id int NOT NULL IDENTITY(1,1),
  user_id int NOT NULL,
  PRIMARY KEY (id)
);
