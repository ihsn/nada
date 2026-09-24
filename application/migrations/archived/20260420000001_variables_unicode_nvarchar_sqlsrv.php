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

        if (!$this->db->table_exists('variables')) {
            throw new Exception('variables table is missing; cannot convert text columns to NVARCHAR');
        }

        $targets = array(
            'fid' => 'nvarchar(45) NULL',
            'vid' => 'nvarchar(45) NULL',
            'name' => 'nvarchar(100) NULL',
            'labl' => 'nvarchar(255) NULL',
            'qstn' => 'nvarchar(max) NULL',
            'catgry' => 'nvarchar(max) NULL',
            'metadata' => 'nvarchar(max) NULL',
            'keywords' => 'nvarchar(max) NULL',
        );
        $optional = array('keywords');
        $fulltext_columns = array('catgry', 'labl', 'name', 'qstn');

        $pending = array();
        foreach ($targets as $column => $definition) {
            if (!$this->sqlsrv_column_exists('variables', $column)) {
                if (in_array($column, $optional, TRUE)) {
                    log_message('info', 'variables.' . $column . ' missing, skipping');
                    continue;
                }
                throw new Exception('variables.' . $column . ' is missing; cannot convert text columns to NVARCHAR');
            }
            if ($this->sqlsrv_column_is_nvarchar('variables', $column)) {
                log_message('info', 'variables.' . $column . ' already nvarchar, skipping');
                continue;
            }
            $pending[$column] = $definition;
        }

        $drop_fulltext = FALSE;
        foreach ($fulltext_columns as $column) {
            if (isset($pending[$column])) {
                $drop_fulltext = TRUE;
                break;
            }
        }

        // Full-text index must be dropped before ALTER on indexed columns.
        // Leave it in place when only non-indexed columns (metadata, keywords) remain.
        if ($drop_fulltext && $this->variables_fulltext_index_exists()) {
            $this->sqlsrv_query(
                'DROP FULLTEXT INDEX ON variables',
                'DROP FULLTEXT INDEX ON variables failed'
            );
            log_message('info', 'Dropped fulltext index on variables (SQLSRV)');
        }

        if (!empty($pending)) {
            $this->drop_sqlsrv_default_constraints_for_columns('variables', array_keys($pending));
            foreach ($pending as $column => $definition) {
                $this->sqlsrv_query(
                    'ALTER TABLE variables ALTER COLUMN ' . $this->sqlsrv_bracket_quote($column) . ' ' . $definition,
                    'ALTER variables.' . $column . ' failed'
                );
                log_message('info', 'Altered variables.' . $column . ' to ' . $definition);
            }
        } else {
            log_message('info', 'variables text columns already NVARCHAR; no ALTER COLUMN needed');
        }

        // Restore empty-string defaults from install/schema (optional for inserts that omit columns)
        $this->restore_sqlsrv_empty_string_defaults();

        foreach ($fulltext_columns as $column) {
            if (!$this->sqlsrv_column_is_nvarchar('variables', $column)) {
                throw new Exception('variables.' . $column . ' is not nvarchar; refusing to recreate the full-text index');
            }
        }

        // Recreate full-text index (same columns as install/schema.sqlsrv.sql)
        if (!$this->variables_fulltext_index_exists()) {
            $this->sqlsrv_query("
                CREATE FULLTEXT INDEX ON variables
                (
                    catgry Language 1033,
                    labl   Language 1033,
                    name   Language 1033,
                    qstn   Language 1033
                )
                KEY INDEX pk_idx_variables
            ", 'CREATE FULLTEXT INDEX ON variables failed');
            log_message('info', 'Recreated fulltext index on variables (SQLSRV)');
        } else {
            log_message('info', 'Fulltext index already exists on variables (SQLSRV), skipping CREATE');
        }

        log_message('info', 'Migration_Variables_unicode_nvarchar_sqlsrv completed');
    }

    private function sqlsrv_column_is_nvarchar($table, $column)
    {
        $_r = $this->db->query("
            SELECT LOWER(ty.name) AS type_name
            FROM sys.columns c
            INNER JOIN sys.tables t ON c.object_id = t.object_id
            INNER JOIN sys.types ty ON c.user_type_id = ty.user_type_id
            WHERE t.name = " . $this->db->escape($table) . "
            AND c.name = " . $this->db->escape($column) . "
        ");
        $row = $_r ? $_r->row_array() : null;
        if (!$row) {
            return FALSE;
        }
        $row = array_change_key_case($row, CASE_LOWER);

        return isset($row['type_name']) && $row['type_name'] === 'nvarchar';
    }

    /**
     * Run DDL and abort the migration when SQL Server rejects it.
     * A FALSE result must not be treated as success, or the version watermark
     * would advance and a re-run would skip the failed statement.
     *
     * @param string $sql
     * @param string $context
     * @return void
     */
    private function sqlsrv_query($sql, $context)
    {
        $result = $this->db->query($sql);
        if ($result === FALSE) {
            $error = $this->db->error();
            $message = (is_array($error) && !empty($error['message'])) ? $error['message'] : 'unknown database error';
            throw new Exception($context . ': ' . $message);
        }
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
            if ($row) {
                $row = array_change_key_case($row, CASE_LOWER);
            }
            if (!$row || empty($row['constraint_name'])) {
                continue;
            }
            $quoted = $this->sqlsrv_bracket_quote($row['constraint_name']);
            $this->sqlsrv_query(
                'ALTER TABLE ' . $this->sqlsrv_bracket_quote($table) . ' DROP CONSTRAINT ' . $quoted,
                'DROP CONSTRAINT ' . $row['constraint_name'] . ' failed'
            );
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
            $this->sqlsrv_query(
                'ALTER TABLE variables ADD CONSTRAINT ' . $qcname . " DEFAULT ('') FOR " . $qcol,
                'ADD CONSTRAINT ' . $cname . ' failed'
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
