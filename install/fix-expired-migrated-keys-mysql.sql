-- Fix expired migrated keys
-- This updates keys that were migrated with old expiration logic
-- Sets expiration to 1 year from now for keys that are expired but have key_hash set

UPDATE `api_keys` 
SET `expires_at` = UNIX_TIMESTAMP() + (365 * 24 * 60 * 60)
WHERE `key_hash` IS NOT NULL 
  AND `expires_at` IS NOT NULL 
  AND `expires_at` < UNIX_TIMESTAMP()
  AND `revoked_at` IS NULL;

