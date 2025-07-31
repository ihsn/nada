<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Content Security Policy (CSP) Configuration
|--------------------------------------------------------------------------
| 
| Configuration for Content Security Policy headers
| 
|
| CSP helps prevent XSS attacks, clickjacking, and other code injection
| attacks by controlling which resources can be loaded and executed.
|
*/

/*
|--------------------------------------------------------------------------
| CSP Enable/Disable
|--------------------------------------------------------------------------
| Set to TRUE to enable CSP headers, FALSE to disable
*/
$config['csp_enabled'] = FALSE;

/*
|--------------------------------------------------------------------------
| CSP Report Only Mode
|--------------------------------------------------------------------------
| Set to TRUE to use report-only mode (doesn't block, just reports violations)
| Useful for testing CSP policies before enforcing them
*/
$config['csp_report_only'] = FALSE;

/*
|--------------------------------------------------------------------------
| CSP Policy Directives
|--------------------------------------------------------------------------
| Define the CSP policy directives. Each directive controls a specific
| type of resource that can be loaded.
|
| Common directives:
| - default-src: Fallback for other directives
| - script-src: Controls JavaScript sources
| - style-src: Controls CSS sources  
| - img-src: Controls image sources
| - font-src: Controls font sources
| - connect-src: Controls AJAX/fetch requests
| - frame-ancestors: Controls embedding in frames
| - object-src: Controls plugin sources
| - base-uri: Controls base URI for relative URLs
| - form-action: Controls form submission URLs
*/
$config['csp_policy'] = array(
    // Default source for all resource types
    'default-src' => "'self'",
    
    // JavaScript sources - includes inline scripts and eval for Vue.js
    'script-src' => array(
        "'self'",
        "'unsafe-inline'",  // Required for Vue.js and inline scripts
        "'unsafe-eval'",    // Required for Vue.js development
        "https://cdn.jsdelivr.net",
        "https://code.jquery.com",
        "http://code.jquery.com",
        "https://unpkg.com",
        "https://cdnjs.cloudflare.com",
        "https://stackpath.bootstrapcdn.com",
        "https://www.googletagmanager.com",
        "https://www.google-analytics.com"
    ),
    
    // CSS sources
    'style-src' => array(
        "'self'",
        "'unsafe-inline'",  // Required for inline styles
        "https://cdn.jsdelivr.net",
        "https://fonts.googleapis.com",
        "https://cdnjs.cloudflare.com",
        "https://stackpath.bootstrapcdn.com"
    ),
    
    // Image sources
    'img-src' => array(
        "'self'",
        "data:",
        "https:",
        "blob:"
    ),
    
    // Font sources
    'font-src' => array(
        "'self'",
        "https://cdn.jsdelivr.net",
        "https://fonts.gstatic.com",
        "https://cdnjs.cloudflare.com"
    ),
    
    // AJAX/fetch request sources
    'connect-src' => array(
        "'self'",
        "https://doi.org",
        "https://data.crosscite.org"
    ),
    
    // Frame embedding control
    'frame-ancestors' => "'self'",
    
    // Plugin sources (blocked for security)
    'object-src' => "'none'",
    
    // Base URI for relative URLs
    'base-uri' => "'self'",
    
    // Form submission URLs
    'form-action' => "'self'",
    
    // Frame sources
    'frame-src' => "'self'",
    
    // Media sources
    'media-src' => "'self'",
    
    // Worker sources
    'worker-src' => "'self'",
    
    // Manifest sources
    'manifest-src' => "'self'"
);

/*
|--------------------------------------------------------------------------
| CSP Nonce Configuration
|--------------------------------------------------------------------------
| Generate nonces for inline scripts/styles that need to be allowed
| This is more secure than 'unsafe-inline'
*/
$config['csp_nonce_enabled'] = TRUE;
$config['csp_nonce_length'] = 32;

/*
|--------------------------------------------------------------------------
| CSP Hash Configuration
|--------------------------------------------------------------------------
| Generate hashes for inline scripts/styles
| Alternative to nonces for static inline content
*/
$config['csp_hash_enabled'] = FALSE;
$config['csp_hash_algorithm'] = 'sha256'; // sha256, sha384, sha512

/*
|--------------------------------------------------------------------------
| CSP Report URI
|--------------------------------------------------------------------------
| URL where CSP violations will be reported
| Set to NULL to disable reporting
*/
$config['csp_report_uri'] = NULL; // '/csp-report'

/*
|--------------------------------------------------------------------------
| CSP Report To
|--------------------------------------------------------------------------
| Modern CSP reporting endpoint (replaces report-uri)
| Set to NULL to disable
*/
$config['csp_report_to'] = NULL; // 'csp-endpoint'

/*
|--------------------------------------------------------------------------
| CSP Exclude Paths
|--------------------------------------------------------------------------
| Array of URI paths where CSP should not be applied
| Useful for third-party integrations or legacy pages
*/
$config['csp_exclude_paths'] = array(
    // 'api/.*',           // Exclude API endpoints
    // 'auth/.*',          // Exclude auth pages
    // 'admin/legacy/.*'   // Exclude legacy admin pages
);

/*
|--------------------------------------------------------------------------
| CSP Include Paths
|--------------------------------------------------------------------------
| Array of URI paths where CSP should be applied
| If empty, CSP applies to all paths (except excluded ones)
*/
$config['csp_include_paths'] = array();

/*
|--------------------------------------------------------------------------
| CSP Custom Headers
|--------------------------------------------------------------------------
| Additional custom headers to send with CSP
*/
$config['csp_custom_headers'] = array(
    // 'X-Content-Security-Policy' => 'custom-policy',
    // 'X-WebKit-CSP' => 'custom-policy'
);

/*
|--------------------------------------------------------------------------
| CSP Development Mode
|--------------------------------------------------------------------------
| Enable development-friendly CSP settings
| Automatically enables report-only mode and looser policies
*/
$config['csp_development_mode'] = FALSE;

/*
|--------------------------------------------------------------------------
| CSP Development Policy Overrides
|--------------------------------------------------------------------------
| Policy overrides when development mode is enabled
*/
$config['csp_development_policy'] = array(
    'script-src' => array(
        "'self'",
        "'unsafe-inline'",
        "'unsafe-eval'",
        "https://cdn.jsdelivr.net",
        "https://code.jquery.com",
        "https://unpkg.com",
        "https://cdnjs.cloudflare.com",
        "http://localhost:*",  // Allow localhost for development
        "http://127.0.0.1:*"   // Allow localhost IP for development
    ),
    'connect-src' => array(
        "'self'",
        "https://api.example.com",
        "http://localhost:*",  // Allow localhost for development
        "http://127.0.0.1:*"   // Allow localhost IP for development
    )
);

/*
|--------------------------------------------------------------------------
| CSP Logging Configuration
|--------------------------------------------------------------------------
| Configure logging for CSP violations and policy application
*/
$config['csp_log_violations'] = TRUE;
$config['csp_log_policy_application'] = FALSE;
$config['csp_log_level'] = 'info'; // debug, info, warning, error

/*
|--------------------------------------------------------------------------
| CSP Cache Configuration
|--------------------------------------------------------------------------
| Cache CSP nonces and hashes for performance
*/
$config['csp_cache_enabled'] = TRUE;
$config['csp_cache_ttl'] = 3600; // Cache TTL in seconds

/*
|--------------------------------------------------------------------------
| CSP Debug Configuration
|--------------------------------------------------------------------------
| Enable debug output for CSP configuration
*/
$config['csp_debug'] = FALSE;

/*
|--------------------------------------------------------------------------
| CSP Version
|--------------------------------------------------------------------------
| CSP configuration version for tracking changes
*/
$config['csp_version'] = '1.0.0';

/*
|--------------------------------------------------------------------------
| CSP Last Updated
|--------------------------------------------------------------------------
| Timestamp of last CSP configuration update
*/
$config['csp_last_updated'] = '2024-01-01 00:00:00'; 