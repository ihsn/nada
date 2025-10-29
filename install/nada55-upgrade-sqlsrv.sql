ALTER TABLE [resources] ADD [resource_idno] nvarchar(100) DEFAULT NULL;
ALTER TABLE [resources] ADD [resource_type] nvarchar(50) DEFAULT NULL;
ALTER TABLE [resources] ADD [is_url] tinyint DEFAULT 0;
ALTER TABLE [resources] ADD [checksum] nvarchar(64) DEFAULT NULL;
ALTER TABLE [resources] ADD [metadata] nvarchar(max) DEFAULT NULL;
ALTER TABLE [resources] ADD [filesize] bigint DEFAULT NULL;
ALTER TABLE [resources] ADD [data_file_id] int DEFAULT NULL;
ALTER TABLE [resources] ADD [sort_order] int DEFAULT 0;
ALTER TABLE [resources] ADD [status] nvarchar(20) DEFAULT NULL;
ALTER TABLE [resources] ADD [created] int DEFAULT NULL;
ALTER TABLE [resources] ADD [created_by] int DEFAULT NULL;
ALTER TABLE [resources] ADD [changed_by] int DEFAULT NULL;

-- Note: resource_type column values are updated via PHP migration
-- See Migration_Upgrade_resources_table::update_resource_types_from_dctype()

-- update resource_type column values - extract code from dctype
-- UPDATE [resources] SET 
--   [resource_type] = LTRIM(RTRIM(SUBSTRING([dctype], 
--     CHARINDEX('[', [dctype]) + 1, 
--     CHARINDEX(']', [dctype]) - CHARINDEX('[', [dctype]) - 1)))
--   WHERE [dctype] IS NOT NULL 
--     AND [dctype] LIKE '%[%]%';




UPDATE [resources] 
  SET [is_url] = 1 
  WHERE [filename] LIKE 'http://%' 
     OR [filename] LIKE 'https://%' 
     OR [filename] LIKE 'ftp://%'
     OR [filename] LIKE 'www.%';

UPDATE [resources] 
  SET [created] = [changed]
  WHERE [created] IS NULL 
    AND [changed] IS NOT NULL 
    AND [changed] > 0;

UPDATE [resources] 
  SET [sort_order] = [resource_id] 
  WHERE [sort_order] = 0;

CREATE NONCLUSTERED INDEX [idx_res_survey_id] ON [resources] ([survey_id] ASC);
GO