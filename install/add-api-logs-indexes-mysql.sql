-- Performance indexes for api_logs table
-- Run this migration to add indexes for faster queries on large datasets

-- Index on time (most common sort field)
CREATE INDEX IF NOT EXISTS idx_api_logs_time ON api_logs(time);

-- Index on method (exact match searches)
CREATE INDEX IF NOT EXISTS idx_api_logs_method ON api_logs(method);

-- Index on response_code (exact match searches)
CREATE INDEX IF NOT EXISTS idx_api_logs_response_code ON api_logs(response_code);

-- Index on authorized (exact match searches)
CREATE INDEX IF NOT EXISTS idx_api_logs_authorized ON api_logs(authorized);

-- Index on user_id (exact match searches)
CREATE INDEX IF NOT EXISTS idx_api_logs_user_id ON api_logs(user_id);

-- Prefix index on uri (for prefix matching LIKE 'keyword%')
-- Note: MySQL can use this index for prefix searches
CREATE INDEX IF NOT EXISTS idx_api_logs_uri_prefix ON api_logs(uri(100));

-- Prefix index on api_key (for prefix matching)
CREATE INDEX IF NOT EXISTS idx_api_logs_api_key_prefix ON api_logs(api_key(20));

-- Index on ip_address (for prefix matching)
CREATE INDEX IF NOT EXISTS idx_api_logs_ip_address ON api_logs(ip_address);

-- Composite index for common query patterns (time + method)
CREATE INDEX IF NOT EXISTS idx_api_logs_time_method ON api_logs(time, method);

-- Composite index for time + response_code
CREATE INDEX IF NOT EXISTS idx_api_logs_time_response_code ON api_logs(time, response_code);

