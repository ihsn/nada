-- Quick fix for SQL Server api_keys after 5.7 upgrade (if platform baseline already ran
-- before the IX_api_keys index fix was included in the migration SQL).
--
-- 1. Allow NULL for new secure keys
ALTER TABLE api_keys ALTER COLUMN api_key VARCHAR(40) NULL;

-- 2. Replace unique index: SQL Server allows only one NULL in IX_api_keys on api_key
IF EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID('api_keys') AND name = 'IX_api_keys'
)
    DROP INDEX [IX_api_keys] ON [api_keys];

IF NOT EXISTS (
    SELECT 1 FROM sys.indexes
    WHERE object_id = OBJECT_ID('api_keys') AND name = 'IX_api_keys_legacy'
)
    CREATE UNIQUE NONCLUSTERED INDEX IX_api_keys_legacy ON api_keys(api_key)
    WHERE api_key IS NOT NULL;

