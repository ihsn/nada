<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Shared sitelogs table DDL for migrations that create or repair the sitelogs table.
 */
trait Sitelogs_schema_trait {

    /**
     * Create the optimized sitelogs table (MySQL/mysqli or SQL Server).
     * No-op if the table already exists. Verifies creation on success.
     *
     * @return void
     */
    protected function create_sitelogs_table()
    {
        if ($this->db->table_exists('sitelogs')) {
            return;
        }

        $db_driver = $this->db->dbdriver;

        if ($db_driver === 'mysql' || $db_driver === 'mysqli') {
            $this->assert_db_query($this->db->query("
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
            "), 'CREATE TABLE sitelogs');
        } elseif ($db_driver === 'sqlsrv') {
            $this->assert_db_query($this->db->query("
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
            "), 'CREATE TABLE sitelogs');

            $indexes = array(
                'CREATE NONCLUSTERED INDEX idx_logtime ON sitelogs(logtime)',
                'CREATE NONCLUSTERED INDEX idx_logtype ON sitelogs(logtype)',
                'CREATE NONCLUSTERED INDEX idx_surveyid ON sitelogs(surveyid)',
                'CREATE NONCLUSTERED INDEX idx_username ON sitelogs(username)',
                'CREATE NONCLUSTERED INDEX idx_ip ON sitelogs(ip)',
                'CREATE NONCLUSTERED INDEX idx_section ON sitelogs(section)',
                'CREATE NONCLUSTERED INDEX idx_logtime_logtype ON sitelogs(logtime, logtype)',
                'CREATE NONCLUSTERED INDEX idx_logtime_username ON sitelogs(logtime, username)',
                'CREATE NONCLUSTERED INDEX idx_logtime_ip ON sitelogs(logtime, ip)',
                'CREATE NONCLUSTERED INDEX idx_surveyid_logtime ON sitelogs(surveyid, logtime)',
            );

            foreach ($indexes as $index_sql) {
                $this->assert_db_query($this->db->query($index_sql), $index_sql);
            }
        } else {
            throw new Exception('Unsupported database driver for sitelogs schema: ' . $db_driver);
        }

        // Clear CI3's table list cache — raw CREATE TABLE bypasses dbforge and leaves the cache stale.
        unset($this->db->data_cache['table_names']);

        if (!$this->db->table_exists('sitelogs')) {
            throw new Exception('sitelogs table was not created');
        }
    }
}
