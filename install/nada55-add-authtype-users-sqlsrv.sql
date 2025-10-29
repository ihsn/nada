-- Add authentication type columns to users table for OAuth/SSO support

ALTER TABLE [users] ADD [authtype] nvarchar(40) DEFAULT NULL;
ALTER TABLE [users] ADD [authtype_id] nvarchar(300) DEFAULT NULL;

-- Add indexes for authentication lookups
CREATE NONCLUSTERED INDEX [idx_authtype] ON [users] ([authtype] ASC);
CREATE NONCLUSTERED INDEX [idx_authtype_id] ON [users] ([authtype] ASC, [authtype_id] ASC);
GO

