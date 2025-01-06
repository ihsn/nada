ALTER TABLE `users` 
ADD COLUMN `forgot_request_ts` INT NULL,
ADD COLUMN `forgot_request_count` INT DEFAULT 0;