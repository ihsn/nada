-- NADA — remove the built-in 'user' role (SQL Server)
-- Same as migration 20260701000011_remove_user_role. Only acts while role 2 is
-- still named 'user'. Safe to run multiple times.

BEGIN TRANSACTION;

DELETE FROM user_roles
 WHERE role_id = 2
   AND EXISTS (SELECT 1 FROM roles WHERE id = 2 AND name = 'user');

DELETE FROM role_permissions
 WHERE role_id = '2'
   AND EXISTS (SELECT 1 FROM roles WHERE id = 2 AND name = 'user');

DELETE FROM roles WHERE id = 2 AND name = 'user';

COMMIT TRANSACTION;
