-- ============================================================
-- NADA 5.6+ sitelogs repair (SQL Server)
--
-- Use when sitelogs_legacy exists but the new optimized sitelogs
-- table was never created (e.g. rename succeeded but a later step failed).
-- Safe to re-run: skips CREATE when sitelogs already exists.
-- ============================================================

IF OBJECT_ID(N'dbo.sitelogs', N'U') IS NULL
BEGIN
    CREATE TABLE sitelogs (
        id int NOT NULL IDENTITY(1,1) PRIMARY KEY,
        sessionid varchar(255) NOT NULL DEFAULT '',
        logtime int NOT NULL DEFAULT 0,
        ip varchar(45) NOT NULL,
        url varchar(255) NOT NULL DEFAULT '',
        logtype varchar(45) NOT NULL,
        surveyid int DEFAULT '0',
        section varchar(255) DEFAULT NULL,
        keyword varchar(300) DEFAULT NULL,
        username varchar(100) DEFAULT NULL,
        useragent varchar(300) DEFAULT NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime ON sitelogs(logtime);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtype' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtype ON sitelogs(logtype);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveyid' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_surveyid ON sitelogs(surveyid);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_username' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_username ON sitelogs(username);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_ip' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_ip ON sitelogs(ip);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_section' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_section ON sitelogs(section);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime_logtype' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime_logtype ON sitelogs(logtime, logtype);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime_username' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime_username ON sitelogs(logtime, username);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_logtime_ip' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_logtime_ip ON sitelogs(logtime, ip);
GO
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'idx_surveyid_logtime' AND object_id = OBJECT_ID('sitelogs'))
    CREATE NONCLUSTERED INDEX idx_surveyid_logtime ON sitelogs(surveyid, logtime);
GO
