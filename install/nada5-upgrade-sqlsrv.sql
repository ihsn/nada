-- ###################################################################################
-- # Upgrade nada42/44 sqlsrv database to nada5
-- ###################################################################################

DROP TABLE blocks;
TRUNCATE TABLE cache;

-- rename surveys TABLE
-- EXEC sp_rename 'surveys', 'surveys_old';  

-- or
select * into surveys_old from surveys;

DROP TABLE surveys;


CREATE TABLE surveys (
  id int NOT NULL IDENTITY(1,1),
  idno varchar(200) NOT NULL,
  type varchar(15) DEFAULT NULL,
  repositoryid varchar(128) NOT NULL,
  title varchar(255) DEFAULT '',
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
  metadata text,
  variable_data text,
  keywords text,  
  PRIMARY KEY (id)
);

CREATE UNIQUE NONCLUSTERED INDEX IX_surveys on [dbo].[surveys](
	[id] ASC,
	[repositoryid] ASC
);


--drop existing fulltext index
DROP FULLTEXT INDEX ON surveys;

--add table columns to index
CREATE FULLTEXT INDEX ON surveys
( 
  keywords		Language 1033
 ) 
KEY INDEX pk_idx_surveys ; 


insert into surveys (
        metafile,
        year_start,
        year_end,
        authoring_entity,
        title,
        idno,
        nation,
        repositoryid,
        varcount
        )
select 
    ddifilename,
    data_coll_start,
    data_coll_end,
    authenty,
    titl,
    surveyid,
    nation,
    repositoryid,
    varcount
from surveys_old;


UPDATE surveys set type='survey';



ALTER TABLE users ADD otp_code varchar(45) DEFAULT NULL;
ALTER TABLE users ADD otp_expiry int(11) DEFAULT NULL;


DROP TABLE IF EXISTS variables;


CREATE TABLE variables (
  uid int NOT NULL IDENTITY(1,1),
  sid int NOT NULL,
  fid varchar(45) DEFAULT '',
  vid varchar(45) DEFAULT '',
  name varchar(100) DEFAULT '',
  labl varchar(255) DEFAULT '',
  qstn text,
  catgry text,
  metadata text,
  PRIMARY KEY (uid)
) ;

CREATE UNIQUE NONCLUSTERED INDEX IX_variables on [dbo].[variables](
	[vid] ASC,
	[sid] ASC
);

CREATE INDEX IX_var_sidfk on [dbo].[variables](
	[sid] ASC
);



--drop existing fulltext index
DROP FULLTEXT INDEX ON variables;

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
-- Table structure for table 'data_files'
--
CREATE TABLE data_files (
  id int NOT NULL identity(1,1),
  sid int NOT NULL,
  file_id varchar(100) DEFAULT NULL,
  file_name varchar(255) DEFAULT NULL,
  description text,
  case_count int DEFAULT NULL,
  var_count int DEFAULT NULL,
  producer varchar(255) DEFAULT NULL,
  data_checks varchar(255) DEFAULT NULL,
  missing_data varchar(255) DEFAULT NULL,
  version varchar(255) DEFAULT NULL,
  notes varchar(255) DEFAULT NULL,
  PRIMARY KEY (id)  
);

CREATE UNIQUE NONCLUSTERED INDEX IX_data_files on [dbo].[data_files](
	[sid] ASC,
	[file_id] ASC
);



CREATE TABLE data_files_resources (
  id int NOT NULL identity(1,1),
  sid int DEFAULT NULL,
  fid varchar(45) DEFAULT NULL,
  resource_id int DEFAULT NULL,
  file_format varchar(45) DEFAULT NULL,
  api_use tinyint DEFAULT NULL,
  PRIMARY KEY (id)
);




drop table api_keys;

--
-- API KEYS table
--
CREATE TABLE api_keys (
  id int NOT NULL identity(1,1),
  api_key varchar(40) NOT NULL,
  level int NOT NULL,
  ignore_limits tinyint NOT NULL DEFAULT '0',
  ip_addresses text,
  date_created int NOT NULL,
  user_id int DEFAULT NULL,
  is_private_key int NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
);

CREATE UNIQUE NONCLUSTERED INDEX IX_api_keys on [dbo].[api_keys](
	[api_key] ASC
);


--
-- API Logs table
--
CREATE TABLE api_logs (
  id int NOT NULL identity(1,1),
  uri varchar(255) NOT NULL,
  method varchar(6) NOT NULL,
  params text,
  api_key varchar(40) NOT NULL,
  ip_address varchar(45) NOT NULL,
  time int NOT NULL,
  rtime float DEFAULT NULL,
  authorized varchar(1) NOT NULL,
  response_code smallint DEFAULT '0',
  PRIMARY KEY (id)
);



CREATE TABLE survey_locations (
  id int NOT NULL identity(1,1),
  sid int DEFAULT NULL,
  location text NOT NULL,
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



--
-- CITATIONS
--

select * into citations_old from citations;

drop table citations;

insert into citations()
  select 
      *
      from citations_old;



UPDATE citations set ihsn_id=id where ihsn_id is NULL;
sp_rename 'citaitons.ihsn_id', 'uuid', 'COLUMN';

CREATE UNIQUE NONCLUSTERED INDEX IX_cit_uuid on [dbo].[citations](	
	[uuid] ASC
);

ALTER TABLE citations ADD url_status varchar(50) DEFAULT NULL;
ALTER TABLE citations ADD created_by int(11) DEFAULT NULL;
ALTER TABLE citations ADD changed_by int(11) DEFAULT NULL;
ALTER TABLE citations ADD attachment varchar(300) DEFAULT NULL;
ALTER TABLE citations ADD lang varchar(45) DEFAULT NULL;

-- citation fulltext indexes

--drop existing fulltext index
DROP FULLTEXT INDEX ON citations;

--add table columns to index
CREATE FULLTEXT INDEX ON citations
( 
  title,subtitle,authors,organization,abstract,keywords,notes,doi
 ) 
KEY INDEX pk_idx_citations ; 


CREATE FULLTEXT INDEX ON citations
( 
  title,subtitle,alt_title,authors,editors,translators
) 
KEY INDEX pk_idx_citations2 ; 



ALTER TABLE resources ADD filesize varchar(50) DEFAULT NULL;
ALTER TABLE resources ADD changed_by int(11) DEFAULT NULL;
ALTER TABLE resources DROP id_number;


ALTER TABLE survey_notes ALTER COLUMN sid int(10) NOT NULL;
ALTER TABLE survey_aliases ALTER COLUMN sid int(10) NOT NULL;

ALTER TABLE survey_repos ALTER COLUMN sid int(10) NOT NULL;


