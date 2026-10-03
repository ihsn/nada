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
            $this->emit("SKIP: not sqlsrv\n");
            log_message('info', 'Migration_Variables_unicode_nvarchar_sqlsrv: not sqlsrv, skipping');
            return;
        }

        if (!$this->db->table_exists('variables')) {
            throw new Exception('variables table is missing; cannot convert text columns to NVARCHAR');
        }

        $this->emit("Checking variables columns for NVARCHAR conversion...\n");
        $this->emit_flush();

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
            $this->emit("Dropping fulltext index on variables (required before column ALTER)...\n");
            $this->emit_flush();
            $this->sqlsrv_query(
                'DROP FULLTEXT INDEX ON variables',
                'DROP FULLTEXT INDEX ON variables failed'
            );
            log_message('info', 'Dropped fulltext index on variables (SQLSRV)');
        }

        $dropped_indexes = array();
        if (!empty($pending)) {
            $index_names = $this->sqlsrv_nonclustered_index_names_on_columns(
                'variables',
                array_keys($pending)
            );
            if (!empty($index_names)) {
                $this->emit(
                    'Dropping index(es) on variables before column ALTER: '
                    . implode(', ', $index_names) . "\n"
                );
                $this->emit_flush();
            }
            $dropped_indexes = $this->drop_sqlsrv_nonclustered_indexes_on_columns(
                'variables',
                array_keys($pending)
            );
        }

        if (!empty($pending)) {
            $this->emit('Converting ' . count($pending) . " column(s) to NVARCHAR (can take several minutes on large catalogs)...\n");
            $this->emit_flush();
            $this->drop_sqlsrv_default_constraints_for_columns('variables', array_keys($pending));
            foreach ($pending as $column => $definition) {
                $this->emit("  ALTER variables.{$column} -> {$definition}\n");
                $this->emit_flush();
                $this->sqlsrv_query(
                    'ALTER TABLE variables ALTER COLUMN ' . $this->sqlsrv_bracket_quote($column) . ' ' . $definition,
                    'ALTER variables.' . $column . ' failed'
                );
                log_message('info', 'Altered variables.' . $column . ' to ' . $definition);
            }
        } else {
            $this->emit("SKIP: variables text columns already NVARCHAR\n");
            log_message('info', 'variables text columns already NVARCHAR; no ALTER COLUMN needed');
        }

        // Restore empty-string defaults from install/schema (optional for inserts that omit columns)
        $this->restore_sqlsrv_empty_string_defaults();

        if (!empty($dropped_indexes)) {
            $this->emit('Recreating index(es) on variables: ' . implode(', ', array_keys($dropped_indexes)) . "\n");
            $this->emit_flush();
            $this->recreate_sqlsrv_dropped_indexes('variables', $dropped_indexes);
        }

        foreach ($fulltext_columns as $column) {
            if (!$this->sqlsrv_column_is_nvarchar('variables', $column)) {
                throw new Exception('variables.' . $column . ' is not nvarchar; refusing to recreate the full-text index');
            }
        }

        // Recreate full-text index (same columns as install/schema.sqlsrv.sql)
        if (!$this->variables_fulltext_index_exists()) {
            $this->emit("Recreating fulltext index on variables...\n");
            $this->emit_flush();
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
            $this->emit("SKIP: fulltext index already exists on variables\n");
            log_message('info', 'Fulltext index already exists on variables (SQLSRV), skipping CREATE');
        }

        $this->emit("Done: variables NVARCHAR migration completed.\n");
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
     * Drop nonclustered indexes that use any of the named columns as key columns.
     * Returns map index_name => CREATE INDEX DDL for recreate.
     *
     * @param string $table
     * @param array<int,string> $columns
     * @return array<string,string>
     */
    private function sqlsrv_nonclustered_index_names_on_columns($table, array $columns)
    {
        $columns = array_values(array_unique(array_filter($columns)));
        if (empty($columns)) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $params = array_merge(array($table), $columns);
        $_r = $this->db->query("
            SELECT DISTINCT i.name AS index_name
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic
                ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            INNER JOIN sys.columns c
                ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            INNER JOIN sys.tables t ON i.object_id = t.object_id
            WHERE t.name = ?
            AND c.name IN ({$placeholders})
            AND i.is_primary_key = 0
            AND i.type > 0
            AND ic.is_included_column = 0
        ", $params);

        $names = array();
        $rows = $_r ? $_r->result_array() : array();
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            if (!empty($row['index_name'])) {
                $names[] = (string) $row['index_name'];
            }
        }

        return $names;
    }

    private function drop_sqlsrv_nonclustered_indexes_on_columns($table, array $columns)
    {
        $dropped = array();
        $index_names = $this->sqlsrv_nonclustered_index_names_on_columns($table, $columns);
        if (empty($index_names)) {
            return $dropped;
        }

        foreach ($index_names as $index_name) {
            $ddl = $this->sqlsrv_capture_index_create_ddl($table, $index_name);
            if ($ddl === null) {
                continue;
            }
            $this->sqlsrv_query(
                'DROP INDEX ' . $this->sqlsrv_bracket_quote($index_name) . ' ON '
                . $this->sqlsrv_bracket_quote($table),
                'DROP INDEX ' . $index_name . ' on ' . $table
            );
            $dropped[$index_name] = $ddl;
            log_message('info', 'Dropped index ' . $index_name . ' on ' . $table . ' before NVARCHAR conversion');
        }

        return $dropped;
    }

    /**
     * @param string $table
     * @param array<string,string> $dropped_indexes
     * @return void
     */
    private function recreate_sqlsrv_dropped_indexes($table, array $dropped_indexes)
    {
        foreach ($dropped_indexes as $index_name => $ddl) {
            if ($this->sqlsrv_index_exists($table, $index_name)) {
                continue;
            }
            $this->sqlsrv_query($ddl, 'CREATE INDEX ' . $index_name . ' on ' . $table);
            log_message('info', 'Recreated index ' . $index_name . ' on ' . $table);
        }
    }

    /**
     * @param string $table
     * @param string $index_name
     * @return string|null
     */
    private function sqlsrv_capture_index_create_ddl($table, $index_name)
    {
        $_r = $this->db->query("
            SELECT
                i.is_unique,
                c.name AS column_name,
                ic.is_descending_key,
                ic.key_ordinal,
                ic.is_included_column
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic
                ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            INNER JOIN sys.columns c
                ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            INNER JOIN sys.tables t ON i.object_id = t.object_id
            WHERE t.name = " . $this->db->escape($table) . "
            AND i.name = " . $this->db->escape($index_name) . "
            ORDER BY ic.is_included_column ASC, ic.key_ordinal ASC
        ");
        $rows = $_r ? $_r->result_array() : array();
        if (empty($rows)) {
            return null;
        }

        $is_unique = FALSE;
        $key_cols = array();
        $include_cols = array();
        foreach ($rows as $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            if (!empty($row['is_unique'])) {
                $is_unique = TRUE;
            }
            $col = $this->sqlsrv_bracket_quote($row['column_name']);
            if (!empty($row['is_included_column'])) {
                $include_cols[] = $col;
                continue;
            }
            $dir = !empty($row['is_descending_key']) ? ' DESC' : ' ASC';
            $key_cols[] = $col . $dir;
        }
        if (empty($key_cols)) {
            return null;
        }

        $ddl = ($is_unique ? 'CREATE UNIQUE NONCLUSTERED INDEX ' : 'CREATE NONCLUSTERED INDEX ')
            . $this->sqlsrv_bracket_quote($index_name)
            . ' ON ' . $this->sqlsrv_bracket_quote($table)
            . ' (' . implode(', ', $key_cols) . ')';
        if (!empty($include_cols)) {
            $ddl .= ' INCLUDE (' . implode(', ', $include_cols) . ')';
        }

        return $ddl;
    }

    /**
     * @param string $table
     * @param string $index_name
     * @return bool
     */
    private function sqlsrv_index_exists($table, $index_name)
    {
        $_r = $this->db->query(
            'SELECT 1 AS x FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
            array($index_name, $table)
        );

        return $_r && $_r->row_array();
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
