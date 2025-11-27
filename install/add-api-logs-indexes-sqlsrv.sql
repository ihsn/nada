-- Performance indexes for api_logs table (SQL Server)
-- Run this migration to add indexes for faster queries on large datasets

-- Index on time (most common sort field)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_time' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_time ON api_logs(time);
END

-- Index on method (exact match searches)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_method' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_method ON api_logs(method);
END

-- Index on response_code (exact match searches)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_response_code' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_response_code ON api_logs(response_code);
END

-- Index on authorized (exact match searches)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_authorized' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_authorized ON api_logs(authorized);
END

-- Index on user_id (exact match searches)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_user_id' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_user_id ON api_logs(user_id);
END

-- Index on uri (SQL Server can use this for prefix searches)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_uri' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_uri ON api_logs(uri);
END

-- Index on api_key
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_api_key' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_api_key ON api_logs(api_key);
END

-- Index on ip_address
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_ip_address' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_ip_address ON api_logs(ip_address);
END

-- Composite index for common query patterns (time + method)
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_time_method' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_time_method ON api_logs(time, method);
END

-- Composite index for time + response_code
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'idx_api_logs_time_response_code' AND object_id = OBJECT_ID('api_logs'))
BEGIN
    CREATE NONCLUSTERED INDEX idx_api_logs_time_response_code ON api_logs(time, response_code);
END

