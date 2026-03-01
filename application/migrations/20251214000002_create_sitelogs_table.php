<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

class Migration_Create_sitelogs_table extends MY_Migration {

    public function up()
    {
        log_message('info', 'Migration_Create_sitelogs_table::up() called');
        
        $db_driver = $this->db->dbdriver;
        
        // Check if sitelogs table already exists
        if ($this->db->table_exists('sitelogs')) {
            log_message('info', 'sitelogs table already exists, skipping creation');
            echo "⚠ sitelogs table already exists, skipping creation\n";
            return;
        }
        
        log_message('info', 'Creating new sitelogs table with optimized schema');
        echo "Creating new sitelogs table...\n";
        
        if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
            // Create table with utf8mb4 and optimized indexes
            $this->db->query("
                CREATE TABLE `sitelogs` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `sessionid` varchar(255) NOT NULL DEFAULT '',
                    `logtime` int(11) NOT NULL DEFAULT 0,
                    `ip` varchar(45) NOT NULL,
                    `url` varchar(255) NOT NULL DEFAULT '',
                    `logtype` varchar(45) NOT NULL,
                    `surveyid` int(11) DEFAULT '0',
                    `section` varchar(255) DEFAULT NULL,
                    `keyword` varchar(300) DEFAULT NULL,
                    `username` varchar(100) DEFAULT NULL,
                    `useragent` varchar(300) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_logtime` (`logtime`),
                    KEY `idx_logtype` (`logtype`),
                    KEY `idx_surveyid` (`surveyid`),
                    KEY `idx_username` (`username`),
                    KEY `idx_ip` (`ip`),
                    KEY `idx_section` (`section`),
                    KEY `idx_logtime_logtype` (`logtime`, `logtype`),
                    KEY `idx_logtime_username` (`logtime`, `username`),
                    KEY `idx_logtime_ip` (`logtime`, `ip`),
                    KEY `idx_surveyid_logtime` (`surveyid`, `logtime`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } elseif ($db_driver === 'sqlsrv' || $db_driver === 'mssql') {
            // SQL Server version
            $this->db->query("
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
                )
            ");
            
            // Create indexes for SQL Server
            $indexes = [
                "CREATE NONCLUSTERED INDEX idx_logtime ON sitelogs(logtime)",
                "CREATE NONCLUSTERED INDEX idx_logtype ON sitelogs(logtype)",
                "CREATE NONCLUSTERED INDEX idx_surveyid ON sitelogs(surveyid)",
                "CREATE NONCLUSTERED INDEX idx_username ON sitelogs(username)",
                "CREATE NONCLUSTERED INDEX idx_ip ON sitelogs(ip)",
                "CREATE NONCLUSTERED INDEX idx_section ON sitelogs(section)",
                "CREATE NONCLUSTERED INDEX idx_logtime_logtype ON sitelogs(logtime, logtype)",
                "CREATE NONCLUSTERED INDEX idx_logtime_username ON sitelogs(logtime, username)",
                "CREATE NONCLUSTERED INDEX idx_logtime_ip ON sitelogs(logtime, ip)",
                "CREATE NONCLUSTERED INDEX idx_surveyid_logtime ON sitelogs(surveyid, logtime)"
            ];
            
            foreach ($indexes as $index_sql) {
                $this->db->query($index_sql);
            }
        } else {
            throw new Exception("Unsupported database driver: " . $db_driver);
        }
        
        log_message('info', 'Successfully created new sitelogs table');
        echo "✓ Successfully created new sitelogs table with optimized schema\n";
    }

    public function down()
    {
        log_message('info', 'Migration_Create_sitelogs_table::down() called');
        
        // Only drop if sitelogs_legacy doesn't exist (safety check)
        if ($this->db->table_exists('sitelogs_legacy')) {
            log_message('info', 'sitelogs_legacy exists, refusing to drop sitelogs in rollback');
            throw new Exception("Cannot drop sitelogs table - sitelogs_legacy exists. Manual intervention required.");
        }
        
        if ($this->db->table_exists('sitelogs')) {
            log_message('info', 'Dropping sitelogs table');
            $this->dbforge->drop_table('sitelogs', TRUE);
            log_message('info', 'Successfully dropped sitelogs table');
        }
    }
}
