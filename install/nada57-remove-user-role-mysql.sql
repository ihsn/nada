-- NADA — remove the built-in 'user' role (MySQL)

DELETE FROM `user_roles` WHERE `role_id` = 2;
DELETE FROM `role_permissions` WHERE `role_id` = '2';
DELETE FROM `roles` WHERE `id` = 2 AND `name` = 'user';
