ALTER TABLE users
ADD forgot_request_ts INT NULL, forgot_request_count INT DEFAULT 0;


ALTER TABLE public_requests
ADD title VARCHAR(max) DEFAULT NULL;
