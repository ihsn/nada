ALTER TABLE `users` 
ADD COLUMN `forgot_request_ts` INT NULL,
ADD COLUMN `forgot_request_count` INT DEFAULT 0;


ALTER TABLE `public_requests` 
ADD COLUMN `title` VARCHAR(500) NULL AFTER `surveyid`;
