<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Migration: one vocabulary for the search engine settings
 *
 *   search_provider                db | mysql | mysqli | sqlsrv -> search_engine = database
 *                                  solr                          -> search_engine = solr
 *                                  opensearch                    -> search_engine = opensearch
 *                                  semantic                      -> search_engine = nada_ai_opensearch when
 *                                                                   semantic_search_engine was opensearch,
 *                                                                   nada_ai_qdrant when it was qdrant or qdrant_db
 *   semantic_search_url            -> nada_ai_url
 *   semantic_search_api_key        -> nada_ai_api_key
 *   semantic_search_admin_api_key  -> nada_ai_admin_api_key
 *   semantic_search_debug          -> nada_ai_debug
 *   semantic_search_engine         removed: it is part of search_engine now. "qdrant" used the plain Qdrant driver,
 *                                  which is gone: nada_ai_qdrant is the driver that combines Qdrant with the
 *                                  database keyword search (what "qdrant_db" was).
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
        'opensearch' => 'opensearch',
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

        // search_provider -> search_engine (the value is translated; nada-ai's depends on the engine it runs)
        if (array_key_exists('search_provider', $stored)) {
            if (!array_key_exists('search_engine', $stored)) {
                $old = strtolower(trim((string) $stored['search_provider']));
                if ($old === 'semantic') {
                    $behind = strtolower(trim((string) ($stored['semantic_search_engine'] ?? 'qdrant')));
                    $engine = ($behind === 'opensearch') ? 'nada_ai_opensearch' : 'nada_ai_qdrant';
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

        // semantic_search_engine is part of search_engine now
        if (array_key_exists('semantic_search_engine', $stored)) {
            $this->remove('semantic_search_engine');
            $this->emit("  semantic_search_engine removed (part of search_engine)\n");
        }

        // values of an earlier draft of these settings
        if (isset($stored['search_engine']) && $stored['search_engine'] === 'opensearch_native') {
            $this->set('search_engine', 'opensearch');
            $this->emit("  search_engine 'opensearch_native' -> 'opensearch'\n");
        }
        if (array_key_exists('nada_ai_combine_with_database', $stored)) {
            $this->remove('nada_ai_combine_with_database');
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
