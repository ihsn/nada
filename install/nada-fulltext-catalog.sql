---------------------------------------------------------
-- SURVEYS 
---------------------------------------------------------

-- create a unique index or use the PK
CREATE UNIQUE INDEX pk_idx_surveys ON dbo.surveys(id);

go

-- create a fulltext catalog if not created already
CREATE FULLTEXT CATALOG ft AS DEFAULT;

go

--add table columns to index
CREATE FULLTEXT INDEX ON surveys
( 
  abbreviation	Language 1033,
  authenty		Language 1033,
  geogcover		Language 1033,
  keywords		Language 1033,
  kindofdata	Language 1033,
  nation		Language 1033,
  producer		Language 1033,
  refno			Language 1033,
  scope			Language 1033,
  sponsor		Language 1033,
  titl			Language 1033,
  titlstmt		Language 1033,
  topic			Language 1033
 ) 
KEY INDEX pk_idx_surveys ; 
GO


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


/*
Changes for Variables table

qstn: change datatype from TEXT to VARCHAR(1000)
catgry: change datatype from TEXT to VARCHAR(3000)

*/