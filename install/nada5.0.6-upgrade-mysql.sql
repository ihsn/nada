###################################################################################
# nada 5.0.6 changes for mysql
###################################################################################

-- rename column 'key' 
ALTER TABLE `api_keys` DROP INDEX `key_UNIQUE`;
ALTER TABLE `api_keys` CHANGE `key` `api_key` VARCHAR(255) NOT NULL;
ALTER TABLE `api_keys` ADD UNIQUE KEY `idx_api_key_unq` (`api_key`);