ALTER TABLE users
ADD forgot_request_ts INT NULL, forgot_request_count INT DEFAULT 0;