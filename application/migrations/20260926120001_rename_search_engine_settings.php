<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: one vocabulary for the search engine settings
 *
 *   search_provider                db | mysql | mysqli | sqlsrv -> search_engine = database
 *                                  solr                          -> search_engine = solr
 *                                  opensearch                    -> search_engine = opensearch_native
 *                                  semantic                      -> search_engine = nada_ai
 *   semantic_search_url            -> nada_ai_url
 *   semantic_search_api_key        -> nada_ai_api_key
 *   semantic_search_admin_api_key  -> nada_ai_admin_api_key
 *   semantic_search_debug          -> nada_ai_debug
 *   semantic_search_engine         removed: which engine nada-ai runs is read from nada-ai itself. The
 *                                  "qdrant_db" choice becomes nada_ai_combine_with_database = true.
 *   citation_search_provider       removed: citations follow the search engine.
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
        'opensearch' => 'opensearch_native',
        'semantic'   => 'nada_ai',
    );

    private static $renames = array(
        'semantic_search_url'           => 'nada_ai_url',
        'semantic_search_api_key'       => 'nada_ai_api_key',
        'semantic_search_admin_api_key' => 'nada_ai_admin_api_key',
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
                $old    = strtolower(trim((string) $stored['search_provider']));
                $engine = isset(self::$engines[$old]) ? self::$engines[$old] : 'database';
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

        // semantic_search_engine: qdrant_db becomes a switch, the rest is no longer a setting
        if (array_key_exists('semantic_search_engine', $stored)) {
            if (!array_key_exists('nada_ai_combine_with_database', $stored)) {
                $combine = strtolower(trim((string) $stored['semantic_search_engine'])) === 'qdrant_db' ? 'true' : 'false';
                $this->set('nada_ai_combine_with_database', $combine);
                $this->emit("  semantic_search_engine '{$stored['semantic_search_engine']}' -> nada_ai_combine_with_database '{$combine}'\n");
            }
            $this->remove('semantic_search_engine');
        }

        if (array_key_exists('citation_search_provider', $stored)) {
            $this->remove('citation_search_provider');
            $this->emit("  citation_search_provider removed (citations follow search_engine)\n");
        }
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
