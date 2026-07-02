<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Per-user, per-repository permission grants (one row per permission key).
 * Global/site ACL remains on roles + role_permissions.
 */
class Migration_Create_repositories_acl_table extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Create_repositories_acl_table::up() called');

        if ($this->db->table_exists('repositories_acl')) {
            log_message('info', 'repositories_acl table already exists, skipping creation');
            echo "⚠ repositories_acl table already exists, skipping creation\n";
            return;
        }

        $db_driver = $this->db->dbdriver;
        echo "Creating repositories_acl table...\n";

        if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
            $this->db->query("
                CREATE TABLE `repositories_acl` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) NOT NULL,
                    `repository_id` int(11) NOT NULL,
                    `permission` varchar(80) NOT NULL,
                    `created_by` int(11) DEFAULT NULL,
                    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_repositories_acl_user_repository_permission` (`user_id`, `repository_id`, `permission`),
                    KEY `idx_repositories_acl_user_repository` (`user_id`, `repository_id`),
                    KEY `idx_repositories_acl_repository_permission` (`repository_id`, `permission`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } elseif ($db_driver === 'sqlsrv') {
            $this->db->query("
                CREATE TABLE repositories_acl (
                    id int NOT NULL IDENTITY(1,1),
                    user_id int NOT NULL,
                    repository_id int NOT NULL,
                    permission varchar(80) NOT NULL,
                    created_by int NULL,
                    created datetime2(0) NOT NULL CONSTRAINT DF_repositories_acl_created DEFAULT (SYSUTCDATETIME()),
                    CONSTRAINT PK_repositories_acl PRIMARY KEY (id),
                    CONSTRAINT UQ_repositories_acl_user_repository_permission UNIQUE (user_id, repository_id, permission)
                )
            ");

            $this->db->query(
                'CREATE NONCLUSTERED INDEX IX_repositories_acl_user_repository ON dbo.repositories_acl (user_id ASC, repository_id ASC)'
            );
            $this->db->query(
                'CREATE NONCLUSTERED INDEX IX_repositories_acl_repository_permission ON dbo.repositories_acl (repository_id ASC, permission ASC)'
            );
        } else {
            throw new Exception('Unsupported database driver: ' . $db_driver);
        }

        log_message('info', 'Successfully created repositories_acl table');
        echo "✓ Successfully created repositories_acl table\n";
    }

    public function down()
    {
        log_message('info', 'Migration_Create_repositories_acl_table::down() called');

        if ($this->db->table_exists('repositories_acl')) {
            $this->dbforge->drop_table('repositories_acl', true);
            log_message('info', 'Dropped repositories_acl table');
        }
    }
}
