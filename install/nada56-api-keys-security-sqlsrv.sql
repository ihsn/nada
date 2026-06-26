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
-- Note: These are NON-UNIQUE indexes to allow NULL values for legacy keys
-- and to support multiple keys with same expiration times
CREATE NONCLUSTERED INDEX IX_api_keys_key_prefix ON api_keys(key_prefix);
CREATE NONCLUSTERED INDEX IX_api_keys_key_hash ON api_keys(key_hash);
CREATE NONCLUSTERED INDEX IX_api_keys_expires_at ON api_keys(expires_at);
CREATE NONCLUSTERED INDEX IX_api_keys_user_revoked ON api_keys(user_id, revoked_at);

-- Note: Legacy keys (where key_hash IS NULL) will continue to work
-- They will be automatically migrated to secure format on first use

