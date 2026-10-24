-- ============================================================
-- NADA 5.6+ variables Unicode (SQL Server)
--
-- Converts variables text/varchar columns to NVARCHAR and
-- recreates the full-text index. Safe to re-run: skips columns
-- that are already nvarchar and skips CREATE when objects exist.
--
-- Run one ALTER at a time on very large catalogs if SSMS times out.
-- ============================================================

-- Drop full-text index when any indexed column is not yet nvarchar
IF EXISTS (
    SELECT 1
    FROM sys.fulltext_indexes fi
    INNER JOIN sys.tables t ON fi.object_id = t.object_id
    WHERE t.name = 'variables'
)
AND EXISTS (
    SELECT 1
    FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables'
      AND c.name IN ('catgry', 'labl', 'name', 'qstn')
      AND ty.name <> 'nvarchar'
)
    DROP FULLTEXT INDEX ON variables;
GO

-- Drop unique index on vid before altering vid (and related key columns)
IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_variables' AND object_id = OBJECT_ID('variables')
)
AND EXISTS (
    SELECT 1
    FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'vid' AND ty.name <> 'nvarchar'
)
    DROP INDEX [IX_variables] ON [variables];
GO

IF COL_LENGTH('variables', 'fid') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'fid' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN fid nvarchar(45) NULL;
GO

IF COL_LENGTH('variables', 'vid') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'vid' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN vid nvarchar(45) NULL;
GO

IF COL_LENGTH('variables', 'name') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'name' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN name nvarchar(100) NULL;
GO

IF COL_LENGTH('variables', 'labl') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'labl' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN labl nvarchar(255) NULL;
GO

IF COL_LENGTH('variables', 'qstn') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'qstn' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN qstn nvarchar(max) NULL;
GO

IF COL_LENGTH('variables', 'catgry') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'catgry' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN catgry nvarchar(max) NULL;
GO

IF COL_LENGTH('variables', 'metadata') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'metadata' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN metadata nvarchar(max) NULL;
GO

IF COL_LENGTH('variables', 'keywords') IS NOT NULL
AND EXISTS (
    SELECT 1 FROM sys.columns c
    INNER JOIN sys.tables t ON c.object_id = t.object_id
    INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
    WHERE t.name = 'variables' AND c.name = 'keywords' AND ty.name <> 'nvarchar'
)
    ALTER TABLE variables ALTER COLUMN keywords nvarchar(max) NULL;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE name = 'IX_variables' AND object_id = OBJECT_ID('variables')
)
    CREATE UNIQUE NONCLUSTERED INDEX [IX_variables] ON [variables] ([vid] ASC, [sid] ASC);
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.fulltext_indexes fi
    INNER JOIN sys.tables t ON fi.object_id = t.object_id
    WHERE t.name = 'variables'
)
    CREATE FULLTEXT INDEX ON variables
    (catgry Language 1033, labl Language 1033, name Language 1033, qstn Language 1033)
    KEY INDEX pk_idx_variables;
GO
