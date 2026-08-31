<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Minimal Bootstrap Controller
 * This controller does nothing - it's used to bootstrap CodeIgniter
 * without executing any actual logic, allowing scripts to use CI
 * components after bootstrap.
 */
class Bootstrap extends CI_Controller {
    
    public function index() {
        // Do nothing - just return
        // This allows CodeIgniter to bootstrap and then the calling script
        // can use get_instance() to access CI components
        return;
    }
}


