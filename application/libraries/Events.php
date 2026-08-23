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
        $this->search_index_listeners();
    }

    /**
     * Queue catalog changes for the configured search provider.
     * Database search is a no-op inside Search_index_manager.
     */
    function search_index_listeners()
    {
        $this->ci->load->library('Search_index_manager');

        $this->_add_delta_listeners(function($object_type, $object_id, $action, $is_delete) {
            $this->ci->search_index_manager->handle_event($object_type, $object_id, $action, $is_delete);
        });
    }

    /**
     * Register db.after.update and db.after.delete listeners that call $handler
     * for each object_id (scalar or array).
     *
     * @param callable $handler  function(string $object_type, int $object_id, string $action, bool $is_delete)
     */
    private function _add_delta_listeners(callable $handler)
    {
        $this->addListener('db.after.update', function ($event, $object_type, $object_id, $action = 'atomic') use ($handler) {
            log_message('info', 'event - ' . print_r(array($object_type, $object_id, $action), TRUE));
            $ids = is_array($object_id) ? $object_id : array($object_id);
            try {
                foreach ($ids as $id) {
                    $handler($object_type, $id, $action, false);
                }
            } catch (Exception $e) {
                log_message('error', 'event-exception - ' . $e->getMessage());
            }
        });

        $this->addListener('db.after.delete', function ($event, $object_type, $object_id, $action = 'delete') use ($handler) {
            log_message('info', 'event - ' . print_r(array($object_type, $object_id, $action), TRUE));
            $ids = is_array($object_id) ? $object_id : array($object_id);
            try {
                foreach ($ids as $id) {
                    $handler($object_type, $id, $action, true);
                }
            } catch (Exception $e) {
                log_message('error', 'event-exception - ' . $e->getMessage());
            }
        });
    }

}
