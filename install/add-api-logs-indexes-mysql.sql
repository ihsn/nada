-- Performance indexes for api_logs table
-- Safe to re-run — duplicate index errors are skipped by the migration runner.

ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_time` (`time`);
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_method` (`method`);
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_response_code` (`response_code`);
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_authorized` (`authorized`);
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_user_id` (`user_id`);
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_uri_prefix` (`uri`(100));
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_api_key_prefix` (`api_key`(20));
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_ip_address` (`ip_address`);
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_time_method` (`time`, `method`);
ALTER TABLE `api_logs` ADD INDEX `idx_api_logs_time_response_code` (`time`, `response_code`);
