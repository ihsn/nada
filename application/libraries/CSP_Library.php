<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Content Security Policy Library
 * 
 * Handles CSP header generation, policy building, and violation logging
 * 
 */
class CSP_Library
{
    private $CI;
    private $config;
    
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('csp', TRUE);
        $this->config = $this->CI->config->item('csp');
    }
    
    /**
     * Apply Content Security Policy headers
     * 
     * Sets CSP headers based on configuration in application/config/csp.php
     */
    public function apply_headers()
    {
        if (!$this->is_enabled()) {
            return;
        }
        
        if ($this->should_exclude()) {
            return;
        }
        
        $csp_policy = $this->build_policy();
        
        // Set CSP header
        $header_name = $this->is_report_only() ? 
            'Content-Security-Policy-Report-Only' : 
            'Content-Security-Policy';
            
        header($header_name . ': ' . $csp_policy);
        
        $this->log_policy_application($csp_policy);
    }
    
    /**
     * Check if CSP is enabled
     */
    public function is_enabled()
    {
        return isset($this->config['csp_enabled']) && $this->config['csp_enabled'];
    }
    
    /**
     * Check if CSP is in report-only mode
     */
    public function is_report_only()
    {
        return isset($this->config['csp_report_only']) && $this->config['csp_report_only'];
    }
    
    /**
     * Check if current path should be excluded from CSP
     */
    public function should_exclude()
    {
        $exclude_paths = isset($this->config['csp_exclude_paths']) ? $this->config['csp_exclude_paths'] : array();
        $include_paths = isset($this->config['csp_include_paths']) ? $this->config['csp_include_paths'] : array();
        $current_uri = $this->CI->uri->uri_string();
        
        // If include paths are specified, only apply CSP to those paths
        if (!empty($include_paths)) {
            foreach ($include_paths as $pattern) {
                if (preg_match('/^' . $pattern . '$/', $current_uri)) {
                    return FALSE;
                }
            }
            return TRUE; // Don't apply CSP
        }
        
        // Check exclude paths
        if (!empty($exclude_paths)) {
            foreach ($exclude_paths as $pattern) {
                if (preg_match('/^' . $pattern . '$/', $current_uri)) {
                    return TRUE; // Don't apply CSP
                }
            }
        }
        
        return FALSE;
    }
    
    /**
     * Build CSP policy string from configuration
     */
    public function build_policy()
    {
        $policy = isset($this->config['csp_policy']) ? $this->config['csp_policy'] : array();
        $development_mode = isset($this->config['csp_development_mode']) ? $this->config['csp_development_mode'] : FALSE;
        
        if ($development_mode && isset($this->config['csp_development_policy'])) {
            $policy = array_merge($policy, $this->config['csp_development_policy']);
        }
        
        $parts = array();
        
        foreach ($policy as $directive => $sources) {
            if (is_array($sources)) {
                $parts[] = $directive . ' ' . implode(' ', $sources);
            } else {
                $parts[] = $directive . ' ' . $sources;
            }
        }
        
        // Add report URI if configured
        $report_uri = isset($this->config['csp_report_uri']) ? $this->config['csp_report_uri'] : NULL;
        if ($report_uri) {
            $parts[] = 'report-uri ' . $report_uri;
        }
        
        // Add report-to if configured
        $report_to = isset($this->config['csp_report_to']) ? $this->config['csp_report_to'] : NULL;
        if ($report_to) {
            $parts[] = 'report-to ' . $report_to;
        }
        
        return implode('; ', $parts);
    }
    
    /**
     * Generate CSP nonce for inline scripts/styles
     */
    public function generate_nonce()
    {
        if (!isset($this->config['csp_nonce_enabled']) || !$this->config['csp_nonce_enabled']) {
            return '';
        }
        
        $nonce_length = isset($this->config['csp_nonce_length']) ? $this->config['csp_nonce_length'] : 32;
        return base64_encode(random_bytes($nonce_length));
    }
    
    /**
     * Generate CSP hash for inline content
     */
    public function generate_hash($content, $algorithm = 'sha256')
    {
        if (!isset($this->config['csp_hash_enabled']) || !$this->config['csp_hash_enabled']) {
            return '';
        }
        
        $hash_algorithm = isset($this->config['csp_hash_algorithm']) ? $this->config['csp_hash_algorithm'] : 'sha256';
        return $hash_algorithm . '-' . base64_encode(hash($hash_algorithm, $content, true));
    }
    
    /**
     * Log CSP violation
     */
    public function log_violation($violation_data)
    {
        if (!isset($this->config['csp_log_violations']) || !$this->config['csp_log_violations']) {
            return;
        }
        
        $log_level = isset($this->config['csp_log_level']) ? $this->config['csp_log_level'] : 'info';
        log_message($log_level, 'CSP Violation: ' . json_encode($violation_data));
    }
    
    /**
     * Log CSP policy application
     */
    private function log_policy_application($policy)
    {
        if (!isset($this->config['csp_log_policy_application']) || !$this->config['csp_log_policy_application']) {
            return;
        }
        
        $log_level = isset($this->config['csp_log_level']) ? $this->config['csp_log_level'] : 'info';
        log_message($log_level, 'CSP Policy applied: ' . $policy);
    }
    
    /**
     * Get CSP configuration
     */
    public function get_config()
    {
        return $this->config;
    }
    
    /**
     * Set CSP configuration
     */
    public function set_config($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Check if CSP is in development mode
     */
    public function is_development_mode()
    {
        return isset($this->config['csp_development_mode']) && $this->config['csp_development_mode'];
    }
    
    /**
     * Get CSP version
     */
    public function get_version()
    {
        return isset($this->config['csp_version']) ? $this->config['csp_version'] : '1.0.0';
    }
    
    /**
     * Get CSP last updated timestamp
     */
    public function get_last_updated()
    {
        return isset($this->config['csp_last_updated']) ? $this->config['csp_last_updated'] : '';
    }
    
    /**
     * Validate CSP configuration
     */
    public function validate_config()
    {
        $errors = array();
        
        // Check required configuration keys
        $required_keys = array('csp_enabled', 'csp_report_only', 'csp_policy');
        foreach ($required_keys as $key) {
            if (!isset($this->config[$key])) {
                $errors[] = "Missing required CSP configuration key: {$key}";
            }
        }
        
        // Validate policy structure
        if (isset($this->config['csp_policy']) && is_array($this->config['csp_policy'])) {
            foreach ($this->config['csp_policy'] as $directive => $sources) {
                if (!is_string($directive)) {
                    $errors[] = "Invalid CSP directive: {$directive}";
                }
                
                if (!is_array($sources) && !is_string($sources)) {
                    $errors[] = "Invalid CSP sources for directive {$directive}";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Get CSP policy as array for debugging
     */
    public function get_policy_array()
    {
        return isset($this->config['csp_policy']) ? $this->config['csp_policy'] : array();
    }
    
    /**
     * Get current URI for CSP path matching
     */
    public function get_current_uri()
    {
        return $this->CI->uri->uri_string();
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function is_debug_enabled()
    {
        return isset($this->config['csp_debug']) && $this->config['csp_debug'];
    }
} 