<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * SQL Server: store variable text as NVARCHAR so Arabic and other Unicode labels
 * are preserved. VARCHAR uses the database code page and maps unsupported characters to "?".
 */
class Migration_Variables_unicode_nvarchar_sqlsrv extends MY_Migration {

    public function up()
    {
        if ($this->db->dbdriver !== 'sqlsrv') {
            log_message('info', 'Migration_Variables_unicode_nvarchar_sqlsrv: not sqlsrv, skipping');
            return;
        }

        if ($this->variables_labl_is_nvarchar()) {
            log_message('info', 'Migration_Variables_unicode_nvarchar_sqlsrv: variables text columns already NVARCHAR, skipping');
            return;
        }

        // Full-text index must be dropped before ALTER on indexed columns
        if ($this->variables_fulltext_index_exists()) {
            $this->db->query('DROP FULLTEXT INDEX ON variables');
            log_message('info', 'Dropped fulltext index on variables (SQLSRV)');
        }

        // DEFAULT constraints block ALTER COLUMN — drop before changing types
        $this->drop_sqlsrv_default_constraints_for_columns(
            'variables',
            ['fid', 'vid', 'name', 'labl', 'qstn', 'catgry', 'metadata', 'keywords']
        );

        $alters = array(
            'ALTER TABLE variables ALTER COLUMN fid nvarchar(45) NULL',
            'ALTER TABLE variables ALTER COLUMN vid nvarchar(45) NULL',
            'ALTER TABLE variables ALTER COLUMN name nvarchar(100) NULL',
            'ALTER TABLE variables ALTER COLUMN labl nvarchar(255) NULL',
            'ALTER TABLE variables ALTER COLUMN qstn nvarchar(max) NULL',
            'ALTER TABLE variables ALTER COLUMN catgry nvarchar(max) NULL',
            'ALTER TABLE variables ALTER COLUMN metadata nvarchar(max) NULL',
        );

        foreach ($alters as $sql) {
            $this->db->query($sql);
        }

        if ($this->sqlsrv_column_exists('variables', 'keywords')) {
            $this->db->query('ALTER TABLE variables ALTER COLUMN keywords nvarchar(max) NULL');
        }

        // Restore empty-string defaults from install/schema (optional for inserts that omit columns)
        $this->restore_sqlsrv_empty_string_defaults();

        log_message('info', 'Altered variables string columns to NVARCHAR (SQLSRV)');

        // Recreate full-text index (same columns as install/schema.sqlsrv.sql)
        if (!$this->variables_fulltext_index_exists()) {
            $this->db->query("
                CREATE FULLTEXT INDEX ON variables
                (
                    catgry Language 1033,
                    labl   Language 1033,
                    name   Language 1033,
                    qstn   Language 1033
                )
                KEY INDEX pk_idx_variables
            ");
            log_message('info', 'Recreated fulltext index on variables (SQLSRV)');
        }

        log_message('info', 'Migration_Variables_unicode_nvarchar_sqlsrv completed');
    }

    private function variables_labl_is_nvarchar()
    {
        $_r = $this->db->query("
            SELECT LOWER(ty.name) AS type_name
            FROM sys.columns c
            INNER JOIN sys.tables t ON c.object_id = t.object_id
            INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
            WHERE t.name = 'variables' AND c.name = 'labl'
        ");
        $row = $_r ? $_r->row_array() : null;

        return $row && ($row['type_name'] === 'nvarchar');
    }

    private function variables_fulltext_index_exists()
    {
        $_r = $this->db->query("
            SELECT 1 AS x
            FROM sys.fulltext_indexes fi
            INNER JOIN sys.tables t ON fi.object_id = t.object_id
            WHERE t.name = 'variables'
        ");

        return $_r && $_r->row_array();
    }

    private function sqlsrv_column_exists($table, $column)
    {
        $_r = $this->db->query("
            SELECT 1 AS x
            FROM sys.columns c
            INNER JOIN sys.tables t ON c.object_id = t.object_id
            WHERE t.name = " . $this->db->escape($table) . "
            AND c.name = " . $this->db->escape($column) . "
        ");

        return $_r && $_r->row_array();
    }

    /**
     * Drop DEFAULT constraints on named columns so ALTER COLUMN can run.
     */
    private function drop_sqlsrv_default_constraints_for_columns($table, array $columns)
    {
        foreach ($columns as $column) {
            if (!$this->sqlsrv_column_exists($table, $column)) {
                continue;
            }
            $_r = $this->db->query("
                SELECT dc.name AS constraint_name
                FROM sys.default_constraints dc
                INNER JOIN sys.columns c
                    ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
                INNER JOIN sys.tables t ON c.object_id = t.object_id
                WHERE t.name = " . $this->db->escape($table) . "
                AND c.name = " . $this->db->escape($column) . "
            ");
            $row = $_r ? $_r->row_array() : null;
            if (!$row || empty($row['constraint_name'])) {
                continue;
            }
            $quoted = $this->sqlsrv_bracket_quote($row['constraint_name']);
            $this->db->query('ALTER TABLE ' . $this->sqlsrv_bracket_quote($table) . ' DROP CONSTRAINT ' . $quoted);
            log_message('info', 'Dropped default constraint ' . $row['constraint_name'] . ' on ' . $table . '.' . $column);
        }
    }

    private function sqlsrv_bracket_quote($identifier)
    {
        return '[' . str_replace(']', ']]', (string)$identifier) . ']';
    }

    /**
     * Match install/schema.sqlsrv.sql DEFAULT '' on fid, vid, name, labl (skip if constraint name taken).
     */
    private function restore_sqlsrv_empty_string_defaults()
    {
        $pairs = [
            ['fid', 'DF_variables_fid_default'],
            ['vid', 'DF_variables_vid_default'],
            ['name', 'DF_variables_name_default'],
            ['labl', 'DF_variables_labl_default'],
        ];
        foreach ($pairs as $pair) {
            list($column, $cname) = $pair;
            if (!$this->sqlsrv_column_exists('variables', $column)) {
                continue;
            }
            if ($this->sqlsrv_default_constraint_exists_on_column('variables', $column)) {
                continue;
            }
            $qcol = $this->sqlsrv_bracket_quote($column);
            $qcname = $this->sqlsrv_bracket_quote($cname);
            $this->db->query(
                'ALTER TABLE variables ADD CONSTRAINT ' . $qcname . " DEFAULT ('') FOR " . $qcol
            );
        }
    }

    private function sqlsrv_default_constraint_exists_on_column($table, $column)
    {
        $_r = $this->db->query("
            SELECT 1 AS x
            FROM sys.default_constraints dc
            INNER JOIN sys.columns c
                ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
            INNER JOIN sys.tables t ON c.object_id = t.object_id
            WHERE t.name = " . $this->db->escape($table) . "
            AND c.name = " . $this->db->escape($column) . "
        ");

        return $_r && $_r->row_array();
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
