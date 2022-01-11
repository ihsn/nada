-- nada 5.0.6 db changes for mysql

-- rename column 'key' to 'api_key'
ALTER TABLE `api_keys` DROP INDEX `key_UNIQUE`;
ALTER TABLE `api_keys` CHANGE `key` `api_key` VARCHAR(255) NOT NULL;
ALTER TABLE `api_keys` ADD UNIQUE KEY `idx_api_key_unq` (`api_key`);

drop table `ci_sessions`;
CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
);

ALTER TABLE `surveys` ADD `doi` varchar(200) DEFAULT NULL;

INSERT INTO `survey_types`(`id`,`code`,`title`, weight) VALUES(9,'video','Video',40);
