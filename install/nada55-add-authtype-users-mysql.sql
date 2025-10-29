-- Add authentication type columns to users table for OAuth/SSO support

ALTER TABLE `users` ADD COLUMN `authtype` varchar(40) DEFAULT NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN `authtype_id` varchar(300) DEFAULT NULL AFTER `authtype`;

-- Add indexes for authentication lookups
ALTER TABLE `users` ADD KEY `idx_authtype` (`authtype`);
ALTER TABLE `users` ADD KEY `idx_authtype_id` (`authtype`, `authtype_id`(191));

-- Add comments for documentation (MySQL 5.5+)
ALTER TABLE `users` MODIFY COLUMN `authtype` varchar(40) DEFAULT NULL COMMENT 'Authentication type: local, oauth, saml, ldap, etc.';
ALTER TABLE `users` MODIFY COLUMN `authtype_id` varchar(300) DEFAULT NULL COMMENT 'External authentication provider user ID';

