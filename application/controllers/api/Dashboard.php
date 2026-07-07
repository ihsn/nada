<?php

require(APPPATH.'/libraries/MY_REST_Controller.php');

/**
 * Dashboard API Controller
 *
 */
class Dashboard extends MY_REST_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->is_authenticated_or_die();
        $this->require_access('dashboard', 'view');
        $this->load->model('Dashboard_model');
    }

    /**
     * Allow session-authenticated admin users through without an API key.
     */

    // -------------------------------------------------------------------------
    // GET  api/dashboard/stats
    // -------------------------------------------------------------------------
    /**
     * Returns one JSON object containing:
     *   catalog          – total/published/unpublished counts overall + by study type
     *   collections      – per-repository study counts and pending license requests
     *   users            – active / disabled / never-logged-in counts + recent logins
     *   recent_studies   – last 10 studies ordered by changed DESC
     *   license_requests – pending count + top 10 most recent
     *   logs_health      – row counts for sitelogs and api_logs (cached)
     *   server_info      – PHP version, server time
     */
    public function stats_get()
    {
        try {
            $data = [
                'catalog'          => $this->Dashboard_model->get_catalog_stats(),
                'collections'      => $this->Dashboard_model->get_collection_stats(),
                'users'            => $this->Dashboard_model->get_user_stats_api(),
                'recent_studies'   => $this->Dashboard_model->get_recent_studies(),
                'license_requests' => $this->Dashboard_model->get_license_request_stats(),
                'logs_health'      => $this->Dashboard_model->get_logs_health(),
                'server_info'      => $this->_server_info(),
            ];

            $this->set_response(['status' => 'success', 'data' => $data], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->set_response(['status' => 'failed', 'message' => $e->getMessage()], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Server information: PHP version and current server date/time.
     * No DB access — kept in the controller intentionally.
     */
    private function _server_info()
    {
        return [
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'server_tz'   => date('T'),
        ];
    }
}
