-- ###################################################################################
-- # API Keys Security Enhancement - MySQL Migration
-- # Adds secure key storage with hashing and prefix lookup
-- # Run this script to upgrade api_keys table for secure key management
-- ###################################################################################

-- Modify api_key column to allow NULL (for new secure keys)
ALTER TABLE `api_keys` 
  MODIFY COLUMN `api_key` VARCHAR(40) NULL;

-- Add new columns to api_keys table
ALTER TABLE `api_keys` 
  ADD COLUMN `key_hash` VARCHAR(255) NULL AFTER `api_key`,
  ADD COLUMN `key_prefix` VARCHAR(12) NULL AFTER `key_hash`,
  ADD COLUMN `expires_at` INT(11) NULL AFTER `date_created`,
  ADD COLUMN `last_used_at` INT(11) NULL AFTER `expires_at`,
  ADD COLUMN `name` VARCHAR(255) NULL AFTER `last_used_at`,
  ADD COLUMN `revoked_at` INT(11) NULL AFTER `name`,
  ADD COLUMN `created_by` INT(11) NULL AFTER `revoked_at`;

-- Create indexes for performance
CREATE INDEX `idx_key_prefix` ON `api_keys` (`key_prefix`);
CREATE INDEX `idx_key_hash` ON `api_keys` (`key_hash`);
CREATE INDEX `idx_expires_at` ON `api_keys` (`expires_at`);
CREATE INDEX `idx_user_revoked` ON `api_keys` (`user_id`, `revoked_at`);

-- Note: Legacy keys (where key_hash IS NULL) will not work with the new system
-- Users will need to generate new keys after this migration

