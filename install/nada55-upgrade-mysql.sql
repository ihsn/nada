ALTER TABLE `resources` ADD COLUMN `resource_idno` varchar(100) DEFAULT NULL AFTER `resource_id`;
ALTER TABLE `resources` ADD COLUMN `resource_type` varchar(50) DEFAULT NULL AFTER `dctype`;
ALTER TABLE `resources` ADD COLUMN `is_url` tinyint(1) DEFAULT 0 AFTER `filename`;
ALTER TABLE `resources` ADD COLUMN `checksum` varchar(64) DEFAULT NULL AFTER `is_url`;
ALTER TABLE `resources` ADD COLUMN `filesize` bigint DEFAULT NULL AFTER `checksum`;
ALTER TABLE `resources` ADD COLUMN `metadata` json DEFAULT NULL AFTER `checksum`;
ALTER TABLE `resources` ADD COLUMN `data_file_id` int(11) DEFAULT NULL AFTER `subjects`;
ALTER TABLE `resources` ADD COLUMN `sort_order` int(11) DEFAULT 0 AFTER `data_file_id`;
ALTER TABLE `resources` ADD COLUMN `status` varchar(20) DEFAULT NULL AFTER `sort_order`;
ALTER TABLE `resources` ADD COLUMN `created` int(11) DEFAULT NULL AFTER `status`;
ALTER TABLE `resources` ADD COLUMN `created_by` int(11) DEFAULT NULL AFTER `created`;
ALTER TABLE `resources` ADD COLUMN `changed_by` int(11) DEFAULT NULL;


# update resource_type column values - extract code from dctype
UPDATE `resources` SET 
  `resource_type` = TRIM(BOTH ']' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(dctype, '[', -1), ']', 1))
  WHERE dctype IS NOT NULL 
    AND dctype LIKE '%[%]%';


UPDATE `resources` 
  SET `is_url` = 1 
  WHERE filename LIKE 'http://%' 
     OR filename LIKE 'https://%' 
     OR filename LIKE 'ftp://%'
     OR filename LIKE 'www.%';  

UPDATE `resources` 
  SET `created` = `changed`
  WHERE created IS NULL 
    AND changed IS NOT NULL 
    AND changed > 0;

UPDATE `resources` 
  SET `sort_order` = resource_id 
  WHERE sort_order = 0;

ALTER TABLE `resources` ADD UNIQUE KEY `uk_survey_resource_idno` (`survey_id`, `resource_idno`);
ALTER TABLE `resources` ADD KEY `idx_survey_id` (`survey_id`);
ALTER TABLE `resources` ADD KEY `idx_resource_type` (`resource_type`);
ALTER TABLE `resources` ADD KEY `idx_filename` (`survey_id`, `filename`(191));
ALTER TABLE `resources` ADD KEY `idx_status` (`status`);
ALTER TABLE `resources` ADD KEY `idx_is_url` (`is_url`);
ALTER TABLE `resources` ADD KEY `idx_sort_order` (`survey_id`, `sort_order`);
ALTER TABLE `resources` ADD KEY `idx_created` (`created`);
ALTER TABLE `resources` ADD KEY `idx_changed` (`changed`);
ALTER TABLE `resources` ADD KEY `idx_data_file_id` (`data_file_id`);

ALTER TABLE `resources` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;