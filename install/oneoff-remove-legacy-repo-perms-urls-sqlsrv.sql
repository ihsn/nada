-- One-off cleanup for existing deployments: remove repo_perms_urls rows for routes
-- dropped from the application (legacy PHP catalog search, copy-studies flow).
-- New installs use install schema seeds without these URLs. Safe to run multiple times.

DELETE FROM repo_perms_urls
WHERE url IN (
	N'admin/catalog/search',
	N'admin/catalog/copy_study'
)
   OR url LIKE N'admin/catalog/do\_copy\_study/%' ESCAPE N'\'
   OR url = N'admin/catalog/do_copy_study';
