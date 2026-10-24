<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: one vocabulary for the search engine settings
 *
 *   search_provider                db | mysql | mysqli | sqlsrv -> search_engine = database
 *                                  solr                          -> search_engine = solr
 *                                  opensearch                    -> search_engine = database (NADA's own OpenSearch
 *                                                                   engine was removed)
 *                                  semantic                      -> search_engine = nada_ai
 *   search_engine                  nada_ai_opensearch | nada_ai_qdrant (earlier drafts) -> nada_ai
 *                                  opensearch | opensearch_native -> database
 *   semantic_search_url            -> nada_ai_url
 *   semantic_search_api_key        -> nada_ai_api_key
 *   semantic_search_admin_api_key  removed: NADA has one key now, nada-ai's admin key. When an admin key is stored
 *   nada_ai_admin_api_key          (under either name) it becomes nada_ai_api_key, since a search-only key could not
 *                                  run indexing.
 *   semantic_search_debug          -> nada_ai_debug
 *   semantic_search_engine         removed: nada-ai always runs OpenSearch for NADA.
 *   citation_search_provider       removed: citations follow the search engine.
 *   search_index_engine            added: the engine whose index NADA keeps in sync (none | solr | nada_ai), set
 *                                  from search_engine (database -> none). Change tracking used to follow
 *                                  search_engine, so that is the engine the tracking rows were written for. A stored
 *                                  opensearch becomes none.
 *   search_index_meta              its row is added, saying the tracking rows describe that engine (the table is
 *                                  created by 20260909120002_catalog_search_queue_filters).
 *
 * A value already stored under a new name is kept (the old row is dropped). Every step is idempotent, so running
 * `migrate latest` twice changes nothing the second time. Works on both MySQL and SQL Server.
 */
class Migration_Rename_search_engine_settings extends MY_Migration {

    private static $engines = array(
        'db'         => 'database',
        'mysql'      => 'database',
        'mysqli'     => 'database',
        'sqlsrv'     => 'database',
        'solr'       => 'solr',
        'opensearch' => 'database',
    );

    private static $renames = array(
        'semantic_search_url'           => 'nada_ai_url',
        'semantic_search_api_key'       => 'nada_ai_api_key',
        'semantic_search_debug'         => 'nada_ai_debug',
    );

    public function up()
    {
        if (!$this->db->table_exists('configurations')) {
            log_message('info', 'configurations does not exist, skipping');
            return;
        }

        $stored = array();
        foreach ($this->db->select('name, value')->get('configurations')->result_array() as $row) {
            $stored[$row['name']] = $row['value'];
        }

        // search_provider -> search_engine (the value is translated)
        if (array_key_exists('search_provider', $stored)) {
            if (!array_key_exists('search_engine', $stored)) {
                $old = strtolower(trim((string) $stored['search_provider']));
                if ($old === 'semantic') {
                    $engine = 'nada_ai';
                } else {
                    $engine = isset(self::$engines[$old]) ? self::$engines[$old] : 'database';
                }
                $this->set('search_engine', $engine);
                $this->emit("  search_provider '{$old}' -> search_engine '{$engine}'\n");
            }
            $this->remove('search_provider');
        }

        // names that only change
        foreach (self::$renames as $old => $new) {
            if (!array_key_exists($old, $stored)) {
                continue;
            }
            if (!array_key_exists($new, $stored)) {
                $this->db->where('name', $old)->update('configurations', array('name' => $new));
                $this->emit("  {$old} -> {$new}\n");
            } else {
                $this->remove($old);
            }
        }

        // one key: nada-ai's admin key serves both search and indexing
        $admin_key = '';
        foreach (array('nada_ai_admin_api_key', 'semantic_search_admin_api_key') as $name) {
            if (array_key_exists($name, $stored)) {
                if ($admin_key === '') {
                    $admin_key = trim((string) $stored[$name]);
                }
                $this->remove($name);
                $this->emit("  {$name} removed (merged into nada_ai_api_key)\n");
            }
        }
        if ($admin_key !== '') {
            $this->set('nada_ai_api_key', $admin_key);
            $this->emit("  nada_ai_api_key set to the stored admin key\n");
        }

        // semantic_search_engine is part of search_engine now
        if (array_key_exists('semantic_search_engine', $stored)) {
            $this->remove('semantic_search_engine');
            $this->emit("  semantic_search_engine removed (part of search_engine)\n");
        }

        // values of an earlier draft of these settings
        // NADA's own OpenSearch engine was removed
        if (isset($stored['search_engine']) && in_array($stored['search_engine'], array('opensearch', 'opensearch_native'), true)) {
            $this->set('search_engine', 'database');
            $this->emit("  search_engine '{$stored['search_engine']}' -> 'database' (NADA's OpenSearch engine was removed)\n");
        }
        if (isset($stored['search_engine']) && in_array($stored['search_engine'], array('nada_ai_opensearch', 'nada_ai_qdrant'), true)) {
            $this->set('search_engine', 'nada_ai');
            $this->emit("  search_engine '{$stored['search_engine']}' -> 'nada_ai'\n");
        }
        if (array_key_exists('nada_ai_combine_with_database', $stored)) {
            $this->remove('nada_ai_combine_with_database');
        }

        if (array_key_exists('citation_search_provider', $stored)) {
            $this->remove('citation_search_provider');
            $this->emit("  citation_search_provider removed (citations follow search_engine)\n");
        }

        $index_engine = $this->set_index_engine();
        $this->add_index_meta_row($index_engine);
    }

    /** @return string the index engine as stored after this step */
    private function set_index_engine()
    {
        $valid = array('solr', 'nada_ai');

        // read again: search_engine may have been set above
        $stored = array();
        foreach ($this->db->select('name, value')->get('configurations')->result_array() as $row) {
            $stored[$row['name']] = $row['value'];
        }

        if (array_key_exists('search_index_engine', $stored)) {
            $engine = strtolower(trim((string) $stored['search_index_engine']));
            if (in_array($engine, $valid, true) || $engine === 'none') {
                return $engine;
            }
            // an engine NADA no longer has (opensearch)
            $this->set('search_index_engine', 'none');
            $this->emit("  search_index_engine '{$engine}' -> 'none'\n");
            return 'none';
        }

        $search_engine = isset($stored['search_engine']) ? strtolower(trim((string) $stored['search_engine'])) : '';
        $engine = in_array($search_engine, $valid, true) ? $search_engine : 'none';
        $this->set('search_index_engine', $engine);
        $this->emit("  search_index_engine = '{$engine}' (from search_engine)\n");
        return $engine;
    }

    private function add_index_meta_row($engine)
    {
        if (!$this->db->table_exists('search_index_meta')) {
            throw new Exception('search_index_meta does not exist: run 20260909120002_catalog_search_queue_filters first');
        }
        if ($this->db->where('id', 1)->count_all_results('search_index_meta') > 0) {
            return;
        }
        // rows already tracked for an engine described it before this change: count them as synced
        $now = time();
        $url = null;
        if ($engine === 'nada_ai') {
            $row = $this->db->select('value')->where('name', 'nada_ai_url')->get('configurations')->row_array();
            $url = $row ? rtrim(trim((string) $row['value']), '/') : null;
        }
        $this->db->insert('search_index_meta', array(
            'id'            => 1,
            'engine'        => $engine,
            'nada_ai_url'   => $url,
            'tracked_since' => $now,
            'last_sync_at'  => $engine !== 'none' ? $now : null,
        ));
        $this->emit("  search_index_meta row added (engine '{$engine}')\n");
    }

    private function set($name, $value)
    {
        $this->db->where('name', $name)->delete('configurations');
        $this->db->insert('configurations', array('name' => $name, 'value' => $value));
    }

    private function remove($name)
    {
        $this->db->where('name', $name)->delete('configurations');
    }

    public function down()
    {
        throw new Exception('Rollback not supported. Restore from database backup if needed.');
    }
}
