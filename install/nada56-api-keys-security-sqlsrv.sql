-- ###################################################################################
-- # API Keys Security Enhancement - SQL Server Migration
-- # Adds secure key storage with hashing and prefix lookup
-- # Run this script to upgrade api_keys table for secure key management
-- ###################################################################################

-- Modify api_key column to allow NULL (for new secure keys)
ALTER TABLE api_keys ALTER COLUMN api_key VARCHAR(40) NULL;

-- Add new columns to api_keys table
ALTER TABLE api_keys ADD key_hash VARCHAR(255) NULL;
ALTER TABLE api_keys ADD key_prefix VARCHAR(12) NULL;
ALTER TABLE api_keys ADD expires_at INT NULL;
ALTER TABLE api_keys ADD last_used_at INT NULL;
ALTER TABLE api_keys ADD name VARCHAR(255) NULL;
ALTER TABLE api_keys ADD revoked_at INT NULL;
ALTER TABLE api_keys ADD created_by INT NULL;

-- Create indexes for performance
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_key_prefix' AND object_id = OBJECT_ID('api_keys'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_key_prefix ON api_keys(key_prefix);
END;

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_key_hash' AND object_id = OBJECT_ID('api_keys'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_key_hash ON api_keys(key_hash);
END;

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_expires_at' AND object_id = OBJECT_ID('api_keys'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_expires_at ON api_keys(expires_at);
END;

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_user_revoked' AND object_id = OBJECT_ID('api_keys'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_user_revoked ON api_keys(user_id, revoked_at);
END;

-- Note: Legacy keys (where key_hash IS NULL) will not work with the new system
-- Users will need to generate new keys after this migration

