-- Quick fix: Allow NULL for api_key column
-- Run this if you're getting "Column 'api_key' cannot be null" error

ALTER TABLE api_keys ALTER COLUMN api_key VARCHAR(40) NULL;

