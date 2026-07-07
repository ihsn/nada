<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * API Logs Controller
 * 
 * Browse, filter, paginate, and sort API logs
 * 
 */
class Api_logs extends MY_REST_Controller {

    public function __construct() {
        parent::__construct();
        
        $this->load->model('Api_logs_model');

        $this->is_authenticated_or_die();
        $this->require_access('user', 'view');
    }

    /**
     * Search, browse, filter, and paginate API logs
     * 
     * Query parameters:
     *  - limit: Records per page (default: 50, max: 200)
     *  - offset: Pagination offset (default: 0)
     *  - keywords: Search term
     *  - field: Field to search in (uri, method, api_key, ip_address, authorized, response_code, user_id)
     *  - sort_by: Column to sort by (default: time)
     *  - sort_order: Sort direction (asc/desc, default: desc)
     *  - time_from: Start timestamp (Unix timestamp) - uses index efficiently
     *  - time_to: End timestamp (Unix timestamp) - uses index efficiently
     * 
     * Returns paginated API log records with metadata
     */
    public function index_get() {
        try {
            $limit = (int)$this->input->get('limit');
            $offset = (int)$this->input->get('offset');
            $keywords = $this->input->get('keywords');
            $field = $this->input->get('field');
            $sort_by = $this->input->get('sort_by') ?: 'time';
            $sort_order = $this->input->get('sort_order') ?: 'desc';
            $time_from = $this->input->get('time_from');
            $time_to = $this->input->get('time_to');
            
            if ($limit <= 0 || $limit > 200) {
                $limit = 50;
            }
            
            if ($offset < 0) {
                $offset = 0;
            }
            
            if ($sort_order !== 'asc' && $sort_order !== 'desc') {
                $sort_order = 'desc';
            }
            
            $filter = array();
            if ($keywords && $field) {
                $filter[] = array(
                    'field' => $field,
                    'keywords' => $keywords
                );
            }
            
            // Time range filter (uses index efficiently)
            if ($time_from || $time_to) {
                $filter[] = array(
                    'field' => 'time_range',
                    'time_from' => $time_from ? (int)$time_from : null,
                    'time_to' => $time_to ? (int)$time_to : null
                );
            }
            
            $total_rows = $this->Api_logs_model->search_count($filter);
            
            if ($offset > $total_rows) {
                $offset = max(0, $total_rows - $limit);
            }
            
            $rows = $this->Api_logs_model->search($limit, $offset, $filter, $sort_by, $sort_order);
            
            $current_page = floor($offset / $limit) + 1;
            $total_pages = ceil($total_rows / $limit);
            
            $formatted_rows = array();
            foreach ($rows as $row) {
                $rtime = $row['rtime'] ? (float)$row['rtime'] : null;
                $rtime_formatted = null;
                
                if ($rtime !== null) {
                    if ($rtime < 1) {
                        // Less than 1 second, show in milliseconds
                        $rtime_formatted = round($rtime * 1000, 2) . 'ms';
                    } else {
                        // 1 second or more, show in seconds
                        $rtime_formatted = round($rtime, 3) . 's';
                    }
                }
                
                $formatted_rows[] = array(
                    'id' => (int)$row['id'],
                    'uri' => $row['uri'],
                    'method' => $row['method'],
                    'params' => $row['params'],
                    'user_id' => $row['user_id'] ? (int)$row['user_id'] : null,
                    'user_email' => isset($row['user_email']) ? $row['user_email'] : null,
                    'api_key' => $row['api_key'],
                    'ip_address' => $row['ip_address'],
                    'time' => (int)$row['time'],
                    'time_formatted' => date('Y-m-d H:i:s', $row['time']),
                    'rtime' => $rtime,
                    'rtime_formatted' => $rtime_formatted,
                    'authorized' => $row['authorized'],
                    'response_code' => (int)$row['response_code']
                );
            }
            
            $response = array(
                'status' => 'success',
                'data' => $formatted_rows,
                'pagination' => array(
                    'total_rows' => (int)$total_rows,
                    'per_page' => $limit,
                    'current_page' => $current_page,
                    'total_pages' => $total_pages,
                    'offset' => $offset,
                    'has_next' => $offset + $limit < $total_rows,
                    'has_prev' => $offset > 0
                )
            );
            
            $this->set_response($response, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $error_output = array(
                'status' => 'failed',
                'message' => $e->getMessage()
            );
            $this->set_response($error_output, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

}
