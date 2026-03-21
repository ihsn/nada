<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use League\Event\Emitter;

/**
 *
 * Example raising events
 *
 * $this->events->emit('db.after.update', 'param value', 'param2');
 * $this->events->emit('db.after.delete', 'surveys', 42, 'delete');
 *
 */

class Events extends Emitter {

    private $ci;

    function __construct()
    {
        $this->ci = &get_instance();
        $this->solr_listeners();
        $this->opensearch_listeners();
    }


    /**
     * Event listeners for SOLR indexing
     */
    function solr_listeners()
    {
        $search_provider = $this->ci->config->item('search_provider');

        if ($search_provider !== 'solr') {
            return;
        }

        $this->ci->load->library('Solr_manager');

        $this->_add_delta_listeners(function($object_type, $object_id, $action) {
            $this->ci->solr_manager->process_delta_update($object_type, $action, $object_id);
        });
    }

    /**
     * Event listeners for OpenSearch indexing
     */
    function opensearch_listeners()
    {
        $search_provider = $this->ci->config->item('search_provider');

        if ($search_provider !== 'opensearch') {
            return;
        }

        $this->ci->load->library('OpenSearch/OpenSearch_manager');

        $this->_add_delta_listeners(function($object_type, $object_id, $action) {
            $this->ci->opensearch_manager->process_delta_update($object_type, $action, $object_id);
        });
    }

    /**
     * Register db.after.update and db.after.delete listeners that call $handler
     * for each object_id (scalar or array).
     *
     * @param callable $handler  function(string $object_type, int $object_id, string $action)
     */
    private function _add_delta_listeners(callable $handler)
    {
        $this->addListener('db.after.update', function ($event, $object_type, $object_id, $action = 'atomic') use ($handler) {
            log_message('info', 'event - ' . print_r(array($object_type, $object_id, $action), TRUE));
            $ids = is_array($object_id) ? $object_id : array($object_id);
            try {
                foreach ($ids as $id) {
                    $handler($object_type, $id, $action);
                }
            } catch (Exception $e) {
                log_message('error', 'event-exception - ' . $e->getMessage());
                throw $e;
            }
        });

        $this->addListener('db.after.delete', function ($event, $object_type, $object_id, $action = 'delete') use ($handler) {
            log_message('info', 'event - ' . print_r(array($object_type, $object_id, $action), TRUE));
            $ids = is_array($object_id) ? $object_id : array($object_id);
            try {
                foreach ($ids as $id) {
                    $handler($object_type, $id, $action);
                }
            } catch (Exception $e) {
                log_message('error', 'event-exception - ' . $e->getMessage());
                throw $e;
            }
        });
    }

}
